<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Malsa\TaskOrchestrator\Actions\ExecuteTaskRunAction;
use Malsa\TaskOrchestrator\Actions\NotificationEvaluationAction;
use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Malsa\TaskOrchestrator\Support\TaskOrchestratorManager;
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

        $this->evaluateNotifications($run);
    }

    private function evaluateNotifications(TaskRunRecord $run): void
    {
        try {
            if (! app()->bound('mail.manager')) {
                return;
            }

            $notificationAction = app(NotificationEvaluationAction::class);
            $manager = app(TaskOrchestratorManager::class);

            $definition = $manager->find($run->task_name);

            if ($definition === null) {
                return;
            }

            $notificationAction->execute($run->fresh(), $definition);
        } catch (Throwable $e) {
            Log::error('Notification evaluation failed in ExecuteTaskRunJob::failed()', [
                'run_id' => $run->id,
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }
    }

    private function touchQueueWorkerHeartbeat(): void
    {
        Cache::put('task_orchestrator.queue_worker_heartbeat', now()->toIso8601String(), now()->addDay());
    }
}
