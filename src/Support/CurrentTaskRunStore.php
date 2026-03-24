<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Support;

final class CurrentTaskRunStore
{
    private ?string $taskRunId = null;

    public function set(string $taskRunId): void
    {
        $this->taskRunId = $taskRunId;
    }

    public function get(): ?string
    {
        return $this->taskRunId;
    }

    public function clear(): void
    {
        $this->taskRunId = null;
    }
}
