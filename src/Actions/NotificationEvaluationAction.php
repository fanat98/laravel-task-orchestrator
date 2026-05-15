<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Actions;

use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Domain\TaskDefinition;
use Malsa\TaskOrchestrator\Mail\TaskFailedMailable;
use Malsa\TaskOrchestrator\Mail\TaskRecoveredMailable;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Malsa\TaskOrchestrator\Support\NotificationConfigResolver;
use Malsa\TaskOrchestrator\Support\RecoveryDetector;
use Psr\Log\LoggerInterface;
use Throwable;

final class NotificationEvaluationAction
{
    private const int IDEMPOTENCE_TTL_SECONDS = 3600;

    public function __construct(
        private readonly NotificationConfigResolver $configResolver,
        private readonly RecoveryDetector $recoveryDetector,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Evaluate and dispatch notifications for a completed task run.
     * Catches all mail exceptions to prevent task disruption.
     */
    public function execute(TaskRunRecord $run, TaskDefinition $definition): void
    {
        $status = $run->status;

        if ($status !== TaskRunStatus::Failed->value && $status !== TaskRunStatus::Succeeded->value) {
            return;
        }

        $payload = $this->configResolver->resolve($definition);

        if ($payload === null) {
            return;
        }

        // Idempotence check: prevent duplicate notifications for the same run and status
        $cacheKey = "task_orchestrator.notification_sent.{$run->id}.{$status}";

        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, self::IDEMPOTENCE_TTL_SECONDS);

        if ($status === TaskRunStatus::Failed->value) {
            $this->dispatchFailureNotification($run, $payload->recipients);

            return;
        }

        // Succeeded status — check for recovery
        $this->dispatchRecoveryNotificationIfApplicable($run, $payload->recipients);
    }

    /**
     * @param array<int, string> $recipients
     */
    private function dispatchFailureNotification(TaskRunRecord $run, array $recipients): void
    {
        try {
            $mailable = new TaskFailedMailable(
                taskLabel: $run->task_label,
                failureMessage: $run->failure_message,
                startedAt: $run->started_at,
                finishedAt: $run->finished_at,
                runUrl: $this->buildRunUrl($run->id),
            );

            Mail::to($recipients)->queue($mailable);
        } catch (Throwable $e) {
            $this->logger->error('Failed to dispatch failure notification for task run "{run_id}": {message}', [
                'run_id' => $run->id,
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }
    }

    /**
     * @param array<int, string> $recipients
     */
    private function dispatchRecoveryNotificationIfApplicable(TaskRunRecord $run, array $recipients): void
    {
        try {
            $previousFailedRun = $this->recoveryDetector->detect($run);

            if ($previousFailedRun === null) {
                return;
            }

            $recoveryDuration = null;

            if ($previousFailedRun->finished_at !== null && $run->finished_at !== null) {
                $recoveryDuration = CarbonInterval::make(
                    $previousFailedRun->finished_at->diff($run->finished_at)
                );
            }

            $mailable = new TaskRecoveredMailable(
                taskLabel: $run->task_label,
                taskName: $run->task_name,
                previousFailureMessage: $previousFailedRun->failure_message,
                recoveryDuration: $recoveryDuration,
                failedAt: $previousFailedRun->finished_at,
                recoveredAt: $run->finished_at,
                failedRunUrl: $this->buildRunUrl($previousFailedRun->id),
                recoveredRunUrl: $this->buildRunUrl($run->id),
            );

            Mail::to($recipients)->queue($mailable);
        } catch (Throwable $e) {
            $this->logger->error('Failed to dispatch recovery notification for task run "{run_id}": {message}', [
                'run_id' => $run->id,
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }
    }

    private function buildRunUrl(string $runId): ?string
    {
        try {
            return route('task-orchestrator.runs.show', ['taskRun' => $runId]);
        } catch (Throwable) {
            return null;
        }
    }
}
