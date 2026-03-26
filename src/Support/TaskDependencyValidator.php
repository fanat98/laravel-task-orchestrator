<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Support;

use Malsa\TaskOrchestrator\Domain\TaskDefinition;

final class TaskDependencyValidator
{
    /**
     * @param iterable<TaskDefinition> $tasks
     */
    public function validate(iterable $tasks): void
    {
        $taskMap = [];

        foreach ($tasks as $task) {
            $taskMap[$task->name] = $task;
        }

        $this->validateReferencedTasksExist($taskMap);
        $this->validateNoSelfDependencies($taskMap);
        $this->validateNoCycles($taskMap);
    }

    /**
     * @param array<string, TaskDefinition> $taskMap
     */
    private function validateReferencedTasksExist(array $taskMap): void
    {
        foreach ($taskMap as $task) {
            foreach ($task->dependsOn as $dependency) {
                if (! array_key_exists($dependency, $taskMap)) {
                    throw new \InvalidArgumentException(sprintf(
                        'Task "%s" depends on unknown task "%s".',
                        $task->name,
                        $dependency
                    ));
                }
            }
        }
    }

    /**
     * @param array<string, TaskDefinition> $taskMap
     */
    private function validateNoSelfDependencies(array $taskMap): void
    {
        foreach ($taskMap as $task) {
            if (in_array($task->name, $task->dependsOn, true)) {
                throw new \InvalidArgumentException(sprintf(
                    'Task "%s" cannot depend on itself.',
                    $task->name
                ));
            }
        }
    }

    /**
     * @param array<string, TaskDefinition> $taskMap
     */
    private function validateNoCycles(array $taskMap): void
    {
        $visiting = [];
        $visited = [];

        foreach (array_keys($taskMap) as $taskName) {
            $this->visit($taskName, $taskMap, $visiting, $visited, []);
        }
    }

    /**
     * @param array<string, TaskDefinition> $taskMap
     * @param array<string, bool> $visiting
     * @param array<string, bool> $visited
     * @param array<int, string> $path
     */
    private function visit(
        string $taskName,
        array $taskMap,
        array &$visiting,
        array &$visited,
        array $path,
    ): void {
        if (isset($visited[$taskName])) {
            return;
        }

        if (isset($visiting[$taskName])) {
            $cyclePath = array_merge($path, [$taskName]);

            throw new \InvalidArgumentException(sprintf(
                'Circular task dependency detected: %s',
                implode(' -> ', $cyclePath)
            ));
        }

        $visiting[$taskName] = true;
        $path[] = $taskName;

        foreach ($taskMap[$taskName]->dependsOn as $dependency) {
            $this->visit($dependency, $taskMap, $visiting, $visited, $path);
        }

        unset($visiting[$taskName]);
        $visited[$taskName] = true;
    }
}
