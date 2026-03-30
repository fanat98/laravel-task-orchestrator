<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Malsa\TaskOrchestrator\Actions\ExecuteTaskRunAction;
use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Throwable;

final class ExecuteTaskRunJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public bool $failOnTimeout = true;
    public int $timeout;

    public function __construct(
        public readonly string $taskRunId,
        public readonly int $timeoutSeconds = 300,
    ) {
        $this->timeout = max($timeoutSeconds, 1);
    }

    /**
     * @throws Throwable
     */
    public function handle(ExecuteTaskRunAction $executeTaskRun): void
    {
        $this->touchQueueWorkerHeartbeat();

        try {
            $executeTaskRun->execute($this->taskRunId);
        } finally {
            $this->touchQueueWorkerHeartbeat();
        }
    }

    public function failed(?Throwable $exception): void
    {
        $run = TaskRunRecord::query()->find($this->taskRunId);

        if (! $run) {
            return;
        }

        if (in_array($run->status, [
            TaskRunStatus::Succeeded->value,
            TaskRunStatus::Failed->value,
            TaskRunStatus::Cancelled->value,
        ], true)) {
            return;
        }

        $message = $exception?->getMessage()
            ?: sprintf('Task run exceeded timeout of %d seconds.', $this->timeout);

        $run->update([
            'status' => TaskRunStatus::Failed->value,
            'failure_message' => $message,
            'finished_at' => now(),
        ]);
    }

    private function touchQueueWorkerHeartbeat(): void
    {
        Cache::put('task_orchestrator.queue_worker_heartbeat', now()->toIso8601String(), now()->addDay());
    }
}
