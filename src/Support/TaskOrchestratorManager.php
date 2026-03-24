<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Support;

use Illuminate\Support\Collection;
use Malsa\TaskOrchestrator\Contracts\TaskRegistry;
use Malsa\TaskOrchestrator\Domain\TaskDefinition;

final class TaskOrchestratorManager
{
    public function __construct(
        private readonly TaskRegistry $registry,
    ) {
    }

    public function register(TaskDefinition $task): void
    {
        $this->registry->register($task);
    }

    /**
     * @return Collection<int, TaskDefinition>
     */
    public function all(): Collection
    {
        return $this->registry->all();
    }

    public function find(string $name): ?TaskDefinition
    {
        return $this->registry->findByName($name);
    }
}
