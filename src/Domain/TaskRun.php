<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Domain;

use DateTimeImmutable;
use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Domain\ValueObjects\Progress;

final class TaskRun
{
    public function __construct(
        public readonly string $id,
        public readonly string $taskName,
        public readonly TaskRunStatus $status,
        public readonly ?Progress $progress = null,
        public readonly ?DateTimeImmutable $startedAt = null,
        public readonly ?DateTimeImmutable $finishedAt = null,
        public readonly ?string $failureMessage = null,
    ) {
        if ($this->id === '') {
            throw new \InvalidArgumentException('Task run id cannot be empty.');
        }

        if ($this->taskName === '') {
            throw new \InvalidArgumentException('Task name cannot be empty.');
        }
    }
}
