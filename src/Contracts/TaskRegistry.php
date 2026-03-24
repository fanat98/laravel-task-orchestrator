<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Contracts;

use Illuminate\Support\Collection;
use Malsa\TaskOrchestrator\Domain\TaskDefinition;

interface TaskRegistry
{
    /**
     * @return Collection<int, TaskDefinition>
     */
    public function all(): Collection;

    public function findByName(string $name): ?TaskDefinition;

    public function register(TaskDefinition $task): void;
}
