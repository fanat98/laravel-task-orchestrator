<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

/**
 * Records a heartbeat to track queue worker health.
 *
 * This job implements ShouldBeUnique to prevent accumulation of duplicate
 * jobs when the queue worker is down. Only one instance can exist in the
 * queue at any time, preventing database overflow and system crashes.
 */
final class QueueHeartbeatJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of seconds the unique lock should be maintained.
     *
     * Set to 2 minutes to cover the scheduling interval (1 min) plus a buffer.
     * If the queue is down longer, the lock will expire and allow a new job,
     * but only one will exist at a time.
     */
    public int $uniqueFor = 120;

    /**
     * The unique ID used for deduplication.
     *
     * Using a static key ensures all QueueHeartbeatJob instances are
     * considered duplicates and only one can exist in the queue.
     */
    public function uniqueId(): string
    {
        return 'task-orchestrator-queue-heartbeat';
    }

    public function handle(): void
    {
        Cache::put(
            'task_orchestrator.queue_worker_heartbeat',
            now()->toIso8601String(),
            now()->addDay()
        );
    }
}

