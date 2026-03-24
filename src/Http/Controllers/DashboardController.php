<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Http\Controllers;

use Illuminate\Contracts\View\View;
use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Malsa\TaskOrchestrator\Support\SystemHealthInspector;
use Malsa\TaskOrchestrator\Support\TaskOrchestratorManager;
use Malsa\TaskOrchestrator\Support\TaskScheduleCalculator;

final class DashboardController
{
    public function __invoke(
        TaskOrchestratorManager $tasks,
        SystemHealthInspector $healthInspector,
        TaskScheduleCalculator $scheduleCalculator,
    ): View {
        $registeredTasksCount = $tasks->all()->count();

        $totalRuns = TaskRunRecord::query()->count();

        $runningRunsCount = TaskRunRecord::query()
            ->where('status', TaskRunStatus::Running->value)
            ->count();

        $failedRunsCount = TaskRunRecord::query()
            ->where('status', TaskRunStatus::Failed->value)
            ->count();

        $latestRuns = TaskRunRecord::query()
            ->latest('started_at')
            ->latest('created_at')
            ->limit(8)
            ->get();

        $latestFailedRuns = TaskRunRecord::query()
            ->where('status', TaskRunStatus::Failed->value)
            ->latest('started_at')
            ->latest('created_at')
            ->limit(5)
            ->get();

        $groupedTasks = $tasks->all()
            ->sortBy(fn ($task) => [
                $task->groupOrder ?? 999999,
                $task->order ?? 999999,
                $task->label,
            ])
            ->groupBy(fn ($task) => $task->group ?: 'Ungrouped');

        $taskGroups = $groupedTasks
            ->map(function ($groupTasks, $groupName) use ($scheduleCalculator) {
                $firstTask = $groupTasks->first();

                return [
                    'name' => $groupName,
                    'group_order' => $firstTask?->groupOrder ?? 999999,
                    'tasks' => $groupTasks
                        ->sortBy(fn ($task) => [
                            $task->order ?? 999999,
                            $task->label,
                        ])
                        ->map(function ($task) use ($scheduleCalculator) {
                            $lastRun = TaskRunRecord::query()
                                ->where('task_name', $task->name)
                                ->latest('started_at')
                                ->latest('created_at')
                                ->first();

                            $nextRun = $scheduleCalculator->nextRun($task->schedule);

                            $recentRuns = TaskRunRecord::query()
                                ->where('task_name', $task->name)
                                ->latest('started_at')
                                ->latest('created_at')
                                ->limit(5)
                                ->get()
                                ->map(fn (TaskRunRecord $run) => [
                                    'id' => $run->id,
                                    'status' => $run->status,
                                    'trigger_type' => $run->trigger_type,
                                    'started_at' => $run->started_at?->toDateTimeString(),
                                    'finished_at' => $run->finished_at?->toDateTimeString(),
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

        $health = $healthInspector->inspect();

        return view('task-orchestrator::dashboard', [
            'registeredTasksCount' => $registeredTasksCount,
            'totalRuns' => $totalRuns,
            'runningRunsCount' => $runningRunsCount,
            'failedRunsCount' => $failedRunsCount,
            'latestRuns' => $latestRuns,
            'latestFailedRuns' => $latestFailedRuns,
            'health' => $health,
            'taskGroups' => $taskGroups,
        ]);
    }
}
