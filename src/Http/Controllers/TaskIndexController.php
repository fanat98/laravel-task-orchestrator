<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Http\Controllers;

use Illuminate\Contracts\View\View;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Malsa\TaskOrchestrator\Support\TaskOrchestratorManager;
use Malsa\TaskOrchestrator\Support\TaskScheduleCalculator;

final class TaskIndexController
{
    public function __invoke(
        TaskOrchestratorManager $tasks,
        TaskScheduleCalculator $scheduleCalculator,
    ): View {
        $taskItems = $tasks->all()
            ->sortBy(fn ($task) => [
                $task->groupOrder ?? 999999,
                $task->group ?? 'Ungrouped',
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
                ];
        });

        return view('task-orchestrator::tasks.index', [
            'tasks' => $taskItems,
        ]);
    }
}
