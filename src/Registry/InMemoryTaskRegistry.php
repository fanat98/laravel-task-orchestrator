<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Registry;

use Illuminate\Support\Collection;
use Malsa\TaskOrchestrator\Contracts\TaskRegistry;
use Malsa\TaskOrchestrator\Domain\TaskDefinition;

final class InMemoryTaskRegistry implements TaskRegistry
{
    /**
     * @var Collection<int, TaskDefinition>
     */
    private Collection $tasks;

    public function __construct()
    {
        $this->tasks = collect();
    }

    public function all(): Collection
    {
        return $this->tasks->values();
    }

    public function findByName(string $name): ?TaskDefinition
    {
        return $this->tasks->first(
            fn (TaskDefinition $task): bool => $task->name === $name
        );
    }

    public function register(TaskDefinition $task): void
    {
        $task->ensureValid();

        if ($this->findByName($task->name) !== null) {
            throw new \InvalidArgumentException(sprintf(
                'A task with the name "%s" is already registered.',
                $task->name
            ));
        }

        $this->tasks->push($task);
    }
}
