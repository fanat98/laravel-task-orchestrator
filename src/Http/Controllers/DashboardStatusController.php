<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Malsa\TaskOrchestrator\Support\SystemHealthInspector;
use Malsa\TaskOrchestrator\Support\TaskOrchestratorManager;
use Malsa\TaskOrchestrator\Support\TaskScheduleCalculator;
use Malsa\TaskOrchestrator\Support\TaskStartabilityStateResolver;

final class DashboardStatusController
{
    public function __invoke(
        TaskOrchestratorManager $tasks,
        SystemHealthInspector $healthInspector,
        TaskScheduleCalculator $scheduleCalculator,
        TaskStartabilityStateResolver $startabilityResolver,
    ): JsonResponse {
        $allTasks = $tasks->all();
        $registeredTasksCount = $allTasks->count();

        $activeRunsByTask = $startabilityResolver->activeRunsByTaskName(
            $allTasks->pluck('name')->all()
        );
        $latestRunsByTask = $startabilityResolver->latestRunsByTaskName(
            $allTasks->pluck('name')->all()
        );

        $totalRuns = TaskRunRecord::query()->count();

        $runningRunsCount = TaskRunRecord::query()
            ->where('status', TaskRunStatus::Running->value)
            ->count();

        $failedRunsCount = TaskRunRecord::query()
            ->where('status', TaskRunStatus::Failed->value)
            ->count();

        $latestRuns = TaskRunRecord::query()
            ->orderByRaw('COALESCE(finished_at, started_at, created_at) DESC')
            ->limit(8)
            ->get()
            ->map(fn (TaskRunRecord $run) => [
                'id' => $run->id,
                'task_label' => $run->task_label,
                'command' => $run->command,
                'status' => $run->status,
                'trigger_type' => $run->trigger_type,
                'started_at' => $run->started_at?->toDateTimeString(),
                'pipeline_id' => $run->pipeline_id,
            ])
            ->values();

        $latestFailedRuns = TaskRunRecord::query()
            ->where('status', TaskRunStatus::Failed->value)
            ->orderByRaw('COALESCE(finished_at, started_at, created_at) DESC')
            ->limit(5)
            ->get()
            ->map(fn (TaskRunRecord $run) => [
                'id' => $run->id,
                'task_label' => $run->task_label,
                'failure_message' => $run->failure_message,
                'trigger_type' => $run->trigger_type,
                'finished_at' => $run->finished_at?->toDateTimeString(),
                'pipeline_id' => $run->pipeline_id,
            ])
            ->values();

        $groupedTasks = $allTasks
            ->sortBy(fn ($task) => [
                $task->groupOrder ?? 999999,
                $task->order ?? 999999,
                $task->label,
            ])
            ->groupBy(fn ($task) => $task->group ?: 'Ungrouped');

        $taskGroups = $groupedTasks
            ->map(function ($groupTasks, $groupName) use ($scheduleCalculator, $startabilityResolver, $activeRunsByTask, $latestRunsByTask) {
                $firstTask = $groupTasks->first();

                return [
                    'name' => $groupName,
                    'group_order' => $firstTask?->groupOrder ?? 999999,
                    'tasks' => $groupTasks
                        ->sortBy(fn ($task) => [
                            $task->order ?? 999999,
                            $task->label,
                        ])
                        ->map(function ($task) use ($scheduleCalculator, $startabilityResolver, $activeRunsByTask, $latestRunsByTask) {
                            $lastRun = $latestRunsByTask[$task->name] ?? null;

                            $nextRun = $scheduleCalculator->nextRun($task->schedule);
                            $startabilityState = $startabilityResolver->stateFor(
                                $task,
                                $activeRunsByTask,
                                $latestRunsByTask,
                            );

                            $recentRuns = TaskRunRecord::query()
                                ->where('task_name', $task->name)
                                ->orderByRaw('COALESCE(finished_at, started_at, created_at) DESC')
                                ->limit(5)
                                ->get()
                                ->map(fn (TaskRunRecord $run) => [
                                    'id' => $run->id,
                                    'status' => $run->status,
                                    'trigger_type' => $run->trigger_type,
                                    'started_at' => $run->started_at?->toDateTimeString(),
                                    'finished_at' => $run->finished_at?->toDateTimeString(),
                                    'pipeline_id' => $run->pipeline_id,
                                ])
                                ->values();

                            return [
                                'name' => $task->name,
                                'label' => $task->label,
                                'description' => $task->description,
                                'command' => $task->command,
                                'group' => $task->group,
                                'schedule' => $task->schedule,
                                'next_run' => $nextRun?->toDateTimeString(),
                                'last_run' => $lastRun?->started_at?->toDateTimeString(),
                                'last_status' => $lastRun?->status,
                                'last_trigger_type' => $lastRun?->trigger_type,
                                'allow_manual_run' => $task->allowManualRun,
                                'is_queued' => $startabilityState['is_queued'],
                                'is_running' => $startabilityState['is_running'],
                                'is_blocked_by_dependencies' => $startabilityState['is_blocked_by_dependencies'],
                                'blocked_by_task_names' => $startabilityState['blocked_by_task_names'],
                                'start_block_reason' => $startabilityState['start_block_reason'],
                                'can_start' => $startabilityState['can_start'],
                                'active_run_id' => $startabilityState['active_run_id'],
                                'recent_runs' => $recentRuns,
                                'depends_on' => $task->dependsOn,
                            ];
                        })
                        ->values(),
                ];
            })
            ->sortBy(fn ($group) => [
                $group['group_order'],
                $group['name'],
            ])
            ->values();

        return response()->json([
            'summary' => [
                'registered_tasks' => $registeredTasksCount,
                'total_runs' => $totalRuns,
                'running_runs' => $runningRunsCount,
                'failed_runs' => $failedRunsCount,
            ],
            'health' => $healthInspector->inspect(),
            'latest_runs' => $latestRuns,
            'latest_failed_runs' => $latestFailedRuns,
            'task_groups' => $taskGroups,
        ]);
    }
}
