<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Support;

use Malsa\TaskOrchestrator\Domain\TaskDefinition;

final readonly class TaskDependencyResolver
{
    public function __construct(
        private TaskOrchestratorManager $tasks,
    ) {
    }

    /**
     * @return array<int, TaskDefinition>
     */
    public function resolveWithDependencies(string $taskName): array
    {
        $resolved = [];
        $visited = [];

        $this->resolve($taskName, $resolved, $visited);

        return array_values($resolved);
    }

    /**
     * @param array<string, TaskDefinition> $resolved
     * @param array<string, bool> $visited
     */
    private function resolve(string $taskName, array &$resolved, array &$visited): void
    {
        if (isset($visited[$taskName])) {
            return;
        }

        $task = $this->tasks->find($taskName);

        if ($task === null) {
            throw new \InvalidArgumentException(sprintf(
                'Task "%s" is not registered.',
                $taskName
            ));
        }

        $visited[$taskName] = true;

        foreach ($task->dependsOn as $dependency) {
            $this->resolve($dependency, $resolved, $visited);
        }

        $resolved[$task->name] = $task;
    }
}
