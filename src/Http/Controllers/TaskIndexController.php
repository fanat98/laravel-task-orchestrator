<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Http\Controllers;

use Illuminate\Contracts\View\View;
use Malsa\TaskOrchestrator\Support\TaskOrchestratorManager;
use Malsa\TaskOrchestrator\Support\TaskScheduleCalculator;
use Malsa\TaskOrchestrator\Support\TaskStartabilityStateResolver;

final class TaskIndexController
{
    public function __invoke(
        TaskOrchestratorManager $tasks,
        TaskScheduleCalculator $scheduleCalculator,
        TaskStartabilityStateResolver $startabilityResolver,
    ): View {
        $allTasks = $tasks->all();

        $activeRunsByTask = $startabilityResolver->activeRunsByTaskName(
            $allTasks->pluck('name')->all()
        );
        $latestRunsByTask = $startabilityResolver->latestRunsByTaskName(
            $allTasks->pluck('name')->all()
        );

        $taskItems = $allTasks
            ->sortBy(fn ($task) => [
                $task->groupOrder ?? 999999,
                $task->group ?? 'Ungrouped',
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

                return [
                    'name' => $task->name,
                    'label' => $task->label,
                    'description' => $task->description,
                    'command' => $task->command,
                    'group' => $task->group,
                    'group_order' => $task->groupOrder,
                    'order' => $task->order,
                    'schedule' => $task->schedule,
                    'allow_manual_run' => $task->allowManualRun,
                    'allow_concurrent_runs' => $task->allowConcurrentRuns,
                    'next_run' => $nextRun,
                    'last_run' => $lastRun?->started_at,
                    'last_status' => $lastRun?->status,
                    'last_trigger_type' => $lastRun?->trigger_type,
                    'is_queued' => $startabilityState['is_queued'],
                    'is_running' => $startabilityState['is_running'],
                    'is_blocked_by_dependencies' => $startabilityState['is_blocked_by_dependencies'],
                    'blocked_by_task_names' => $startabilityState['blocked_by_task_names'],
                    'start_block_reason' => $startabilityState['start_block_reason'],
                    'can_start' => $startabilityState['can_start'],
                    'active_run_id' => $startabilityState['active_run_id'],
                ];
        });

        return view('task-orchestrator::tasks.index', [
            'tasks' => $taskItems,
        ]);
    }
}
