<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Support;

use Malsa\TaskOrchestrator\Domain\TaskDefinition;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;

final class TaskStartabilityStateResolver
{
    public function __construct(
        private readonly TaskStartBlockingEvaluator $evaluator,
    ) {
    }

    /**
     * @param array<int, string> $taskNames
     * @return array<string, TaskRunRecord>
     */
    public function activeRunsByTaskName(array $taskNames): array
    {
        return $this->evaluator->activeRunsByTaskName($taskNames);
    }

    /**
     * @param array<int, string> $taskNames
     * @return array<string, TaskRunRecord>
     */
    public function latestRunsByTaskName(array $taskNames): array
    {
        return $this->evaluator->latestRunsByTaskName($taskNames);
    }

    /**
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
    public function stateFor(
        TaskDefinition $task,
        array $activeRunsByTask,
        array $latestRunsByTask,
    ): array
    {
        return $this->evaluator->evaluate($task, $activeRunsByTask, $latestRunsByTask, true);
    }
}

