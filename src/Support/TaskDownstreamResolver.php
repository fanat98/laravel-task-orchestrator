<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Support;

use Malsa\TaskOrchestrator\Domain\TaskDefinition;

final class TaskDownstreamResolver
{
    public function __construct(
        private readonly TaskOrchestratorManager $tasks,
    ) {
    }

    /**
     * @return array<int, TaskDefinition>
     */
    public function directDependentsOf(string $taskName): array
    {
        return $this->tasks->all()
            ->filter(fn (TaskDefinition $task) => in_array($taskName, $task->dependsOn, true))
            ->sortBy(fn (TaskDefinition $task) => [
                $task->groupOrder ?? 999999,
                $task->order ?? 999999,
                $task->label,
            ])
            ->values()
            ->all();
    }
}
