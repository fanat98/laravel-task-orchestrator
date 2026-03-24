<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Support;

use Malsa\TaskOrchestrator\Domain\ValueObjects\Progress;
use Malsa\TaskOrchestrator\Models\TaskRunLog;

final class TaskContext
{
    private ?Progress $progress = null;

    /**
     * @var array<int, array{level: string, message: string}>
     */
    private array $logs = [];

    public function __construct(
        public readonly string $taskRunId,
        public readonly string $taskName,
    ) {
        if ($this->taskRunId === '') {
            throw new \InvalidArgumentException('Task run id cannot be empty.');
        }

        if ($this->taskName === '') {
            throw new \InvalidArgumentException('Task name cannot be empty.');
        }
    }

    public function log(string $message, string $level = 'info'): void
    {
        $this->logs[] = [
            'level' => $level,
            'message' => $message,
        ];

        TaskRunLog::query()->create([
            'task_run_id' => $this->taskRunId,
            'level' => $level,
            'message' => $message,
        ]);
    }

    public function setProgress(int $current, ?int $total = null, ?string $message = null): void
    {
        $this->progress = new Progress($current, $total, $message);
    }

    public function progress(): ?Progress
    {
        return $this->progress;
    }

    /**
     * @return array<int, array{level: string, message: string}>
     */
    public function logs(): array
    {
        return $this->logs;
    }
}
