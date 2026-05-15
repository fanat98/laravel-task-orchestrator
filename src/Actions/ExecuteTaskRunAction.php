<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Actions;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Log;
use Malsa\TaskOrchestrator\Console\TaskCommandOutput;
use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Malsa\TaskOrchestrator\Support\CurrentTaskRunStore;
use Malsa\TaskOrchestrator\Support\TaskContext;
use Malsa\TaskOrchestrator\Support\TaskOrchestratorManager;
use Throwable;

final class ExecuteTaskRunAction
{
    public function __construct(
        private readonly Kernel $artisan,
        private readonly CurrentTaskRunStore $currentTaskRunStore,
        private readonly StartDownstreamTasksAction $startDownstreamTasks,
        private readonly ?NotificationEvaluationAction $notificationEvaluation = null,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function execute(string $taskRunId): void
    {
        $run = TaskRunRecord::query()->findOrFail($taskRunId);

        if (in_array($run->status, [
            TaskRunStatus::Succeeded->value,
            TaskRunStatus::Failed->value,
            TaskRunStatus::Cancelled->value,
        ], true)) {
            return;
        }

        $startedAt = $run->started_at ?? now();
        $timeoutAt = $run->timeout_at ?? $startedAt->copy()->addSeconds(
            $this->resolveTimeoutSeconds($run)
        );

        $run->update([
            'status' => TaskRunStatus::Running->value,
            'started_at' => $startedAt,
            'timeout_at' => $timeoutAt,
            'finished_at' => null,
            'failure_message' => null,
        ]);

        $context = new TaskContext(
            taskRunId: $run->id,
            taskName: $run->task_name,
        );

        $output = new TaskCommandOutput($context);

        $this->currentTaskRunStore->set($run->id);

        try {
            $context->log(sprintf('Starting command [%s]', $run->command));

            $exitCode = $this->artisan->call(
                $run->command,
                $run->command_arguments ?? [],
                $output
            );

            $output->flushRemainingBuffer();

            $context->log(sprintf('Command finished with exit code [%d]', $exitCode));

            $progress = $context->progress();

            $run->update([
                'status' => $exitCode === 0 ? TaskRunStatus::Succeeded->value : TaskRunStatus::Failed->value,
                'progress_current' => $progress?->current ?? $run->fresh()->progress_current,
                'progress_total' => $progress?->total ?? $run->fresh()->progress_total,
                'progress_message' => $progress?->message ?? $run->fresh()->progress_message,
                'failure_message' => $exitCode === 0 ? null : sprintf(
                    'Command [%s] failed with exit code [%d].',
                    $run->command,
                    $exitCode
                ),
                'finished_at' => now(),
            ]);

            if ($exitCode === 0) {
                $this->startDownstreamTasks->execute($run->task_name, $run->pipeline_id);
            }

            $this->evaluateNotifications($run);
        } catch (Throwable $exception) {
            $output->flushRemainingBuffer();

            $context->log(sprintf(
                '%s: %s',
                $exception::class,
                $exception->getMessage()
            ), 'error');

            $context->log($exception->getTraceAsString(), 'error');

            $progress = $context->progress();

            $run->update([
                'status' => TaskRunStatus::Failed->value,
                'progress_current' => $progress?->current ?? $run->fresh()->progress_current,
                'progress_total' => $progress?->total ?? $run->fresh()->progress_total,
                'progress_message' => $progress?->message ?? $run->fresh()->progress_message,
                'failure_message' => $exception->getMessage(),
                'finished_at' => now(),
            ]);

            $this->evaluateNotifications($run);

            throw $exception;
        } finally {
            $this->currentTaskRunStore->clear();
        }
    }

    private function resolveTimeoutSeconds(TaskRunRecord $run): int
    {
        return max(
            (int) ($run->timeout_seconds ?? ((int) config('task-orchestrator.stale_run_default_minutes', 10) * 60)),
            60
        );
    }

    private function evaluateNotifications(TaskRunRecord $run): void
    {
        if ($this->notificationEvaluation === null) {
            return;
        }

        try {
            $manager = app(TaskOrchestratorManager::class);
            $definition = $manager->find($run->task_name);

            if ($definition === null) {
                return;
            }

            $this->notificationEvaluation->execute($run->fresh(), $definition);
        } catch (Throwable $e) {
            Log::error('Notification evaluation failed', [
                'run_id' => $run->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
