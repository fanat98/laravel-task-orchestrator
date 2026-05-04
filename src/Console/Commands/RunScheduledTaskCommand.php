<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Malsa\TaskOrchestrator\Actions\StartTaskChainAction;
use Throwable;

final class RunScheduledTaskCommand extends Command
{
    protected $signature = 'task-orchestrator:run-scheduled-task {task}';

    protected $description = 'Starts a scheduled task through the Task Orchestrator';

    public function __construct(
        private readonly StartTaskChainAction $startTaskChain,
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
            $this->warn(sprintf(
                'Skipping scheduled task [%s]: queue worker heartbeat is stale or missing.',
                $taskName
            ));

            return self::SUCCESS;
        }

        $this->startTaskChain->execute($taskName, 'scheduled');

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
}
