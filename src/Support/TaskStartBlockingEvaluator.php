<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Support;

use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Domain\TaskDefinition;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;

final class TaskStartBlockingEvaluator
{
    /**
     * @param array<int, string> $taskNames
     * @return array<string, TaskRunRecord>
     */
    public function activeRunsByTaskName(array $taskNames): array
    {
        if ($taskNames === []) {
            return [];
        }

        return TaskRunRecord::query()
            ->whereIn('task_name', array_values(array_unique($taskNames)))
            ->whereIn('status', [
                TaskRunStatus::Queued->value,
                TaskRunStatus::Running->value,
            ])
            ->orderByDesc('created_at')
            ->get()
            ->unique('task_name')
            ->mapWithKeys(fn (TaskRunRecord $run) => [$run->task_name => $run])
            ->all();
    }

    /**
     * @param array<int, string> $taskNames
     * @return array<string, TaskRunRecord>
     */
    public function latestRunsByTaskName(array $taskNames): array
    {
        if ($taskNames === []) {
            return [];
        }

        return TaskRunRecord::query()
            ->whereIn('task_name', array_values(array_unique($taskNames)))
            ->orderByDesc('started_at')
            ->orderByDesc('created_at')
            ->get()
            ->unique('task_name')
            ->mapWithKeys(fn (TaskRunRecord $run) => [$run->task_name => $run])
            ->all();
    }

    /**
     * @param array<string, TaskRunRecord> $activeRunsByTask
     * @param array<string, TaskRunRecord> $latestRunsByTask
     * @return array{
     *     is_queued: bool,
     *     is_running: bool,
     *     is_blocked_by_dependencies: bool,
     *     blocked_by_task_names: array<int, string>,
     *     start_block_reason: string|null,
     *     can_start: bool,
     *     active_run_id: string|null
     * }
     */
    public function evaluate(
        TaskDefinition $task,
        array $activeRunsByTask,
        array $latestRunsByTask,
        bool $respectManualRun = true,
    ): array {
        $activeRun = $activeRunsByTask[$task->name] ?? null;
        $isQueued = $activeRun?->status === TaskRunStatus::Queued->value;
        $isRunning = $activeRun?->status === TaskRunStatus::Running->value;
        $isSameTaskBlocked = ! $task->allowConcurrentRuns && ($isQueued || $isRunning);
        $blockedByTaskNames = $this->blockedDependencyTaskNames($task, $activeRunsByTask, $latestRunsByTask);
        $isBlockedByDependencies = $blockedByTaskNames !== [];

        $startBlockReason = null;

        if ($respectManualRun && ! $task->allowManualRun) {
            $startBlockReason = 'manual_disabled';
        } elseif ($isSameTaskBlocked && $isRunning) {
            $startBlockReason = 'same_task_running';
        } elseif ($isSameTaskBlocked && $isQueued) {
            $startBlockReason = 'same_task_queued';
        } elseif ($isBlockedByDependencies) {
            $startBlockReason = $this->hasActiveDependency($task, $activeRunsByTask)
                ? 'dependency_active'
                : 'dependency_not_succeeded';
        }

        return [
            'is_queued' => $isQueued,
            'is_running' => $isRunning,
            'is_blocked_by_dependencies' => $isBlockedByDependencies,
            'blocked_by_task_names' => $blockedByTaskNames,
            'start_block_reason' => $startBlockReason,
            'can_start' => (! $respectManualRun || $task->allowManualRun)
                && ! $isSameTaskBlocked
                && ! $isBlockedByDependencies,
            'active_run_id' => $activeRun?->id,
        ];
    }

    /**
     * @param array<string, TaskRunRecord> $activeRunsByTask
     * @param array<string, TaskRunRecord> $latestRunsByTask
     * @return array<int, string>
     */
    private function blockedDependencyTaskNames(TaskDefinition $task, array $activeRunsByTask, array $latestRunsByTask): array
    {
        $blockedBy = [];

        foreach ($task->dependsOn as $dependencyTaskName) {
            $dependencyActiveRun = $activeRunsByTask[$dependencyTaskName] ?? null;

            if ($dependencyActiveRun !== null) {
                $blockedBy[] = $dependencyTaskName;

                continue;
            }

            $dependencyLatestRun = $latestRunsByTask[$dependencyTaskName] ?? null;

            if (! $dependencyLatestRun || $dependencyLatestRun->status !== TaskRunStatus::Succeeded->value) {
                $blockedBy[] = $dependencyTaskName;
            }
        }

        return array_values(array_unique($blockedBy));
    }

    /**
     * @param array<string, TaskRunRecord> $activeRunsByTask
     */
    private function hasActiveDependency(TaskDefinition $task, array $activeRunsByTask): bool
    {
        foreach ($task->dependsOn as $dependencyTaskName) {
            if (isset($activeRunsByTask[$dependencyTaskName])) {
                return true;
            }
        }

        return false;
    }
}

