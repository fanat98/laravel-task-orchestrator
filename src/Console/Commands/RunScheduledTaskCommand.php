<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Malsa\TaskOrchestrator\Actions\StartTaskChainAction;
use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Malsa\TaskOrchestrator\Support\TaskOrchestratorManager;
use Throwable;

final class RunScheduledTaskCommand extends Command
{
    protected $signature = 'task-orchestrator:run-scheduled-task {task}';

    protected $description = 'Starts a scheduled task through the Task Orchestrator';

    public function __construct(
        private readonly StartTaskChainAction $startTaskChain,
        private readonly TaskOrchestratorManager $tasks,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $taskName = (string) $this->argument('task');

        // Guard: do not dispatch new task runs when the queue worker is down.
        // This prevents thousands of queued records accumulating during prolonged
        // queue outages, which can crash the application on recovery.
        if (! $this->isQueueWorkerHealthy()) {
            $message = 'Queue worker heartbeat is stale or missing.';
            $this->recordSchedulingFailure($taskName, $message);

            $this->warn(sprintf(
                'Scheduled task [%s] failed to start: %s',
                $taskName,
                $message
            ));

            return self::SUCCESS;
        }

        try {
            $this->startTaskChain->execute($taskName, 'scheduled');
        } catch (Throwable $exception) {
            $this->recordSchedulingFailure($taskName, $exception->getMessage());

            report($exception);

            $this->error(sprintf(
                'Scheduled task [%s] failed to start: %s',
                $taskName,
                $exception->getMessage()
            ));

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Scheduled task [%s] was dispatched through the orchestrator.',
            $taskName
        ));

        return self::SUCCESS;
    }

    /**
     * Checks whether the queue worker has recently sent a heartbeat.
     *
     * Uses the same cache key and threshold as the health dashboard so behaviour
     * is consistent across the system.
     */
    private function isQueueWorkerHealthy(): bool
    {
        $heartbeatValue = Cache::get('task_orchestrator.queue_worker_heartbeat');

        if (! is_string($heartbeatValue) || trim($heartbeatValue) === '') {
            return false;
        }

        $maxAgeSeconds = (int) config(
            'task-orchestrator.health.queue_worker.heartbeat_max_age_seconds',
            60
        );

        try {
            return Carbon::parse($heartbeatValue)
                ->greaterThanOrEqualTo(now()->subSeconds($maxAgeSeconds));
        } catch (Throwable) {
            return false;
        }
    }

    private function recordSchedulingFailure(string $taskName, string $reason): void
    {
        $definition = $this->tasks->find($taskName);

        if ($definition === null) {
            report(new \RuntimeException(sprintf(
                'Scheduled task [%s] failed to start but is no longer registered. Reason: %s',
                $taskName,
                $reason
            )));

            return;
        }

        TaskRunRecord::query()->create([
            'id' => (string) Str::uuid(),
            'task_name' => $definition->name,
            'task_label' => $definition->label,
            'command' => $definition->command,
            'command_arguments' => $definition->arguments,
            'status' => TaskRunStatus::Failed->value,
            'trigger_type' => 'scheduled',
            'failure_message' => sprintf(
                'Scheduled task was not started. Reason: %s',
                $reason
            ),
            'started_at' => null,
            'finished_at' => now(),
        ]);
    }
}
