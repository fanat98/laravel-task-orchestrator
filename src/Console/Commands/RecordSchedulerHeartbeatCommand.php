<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

final class RecordSchedulerHeartbeatCommand extends Command
{
    protected $signature = 'task-orchestrator:record-scheduler-heartbeat';

    protected $description = 'Records a scheduler heartbeat for dashboard health monitoring';

    public function handle(): int
    {
        $cacheKey = (string) config(
            'task-orchestrator.health.scheduler_heartbeat_cache_key',
            'task-orchestrator:scheduler-heartbeat'
        );
        $ttlSeconds = (int) config('task-orchestrator.health.scheduler_heartbeat_ttl_seconds', 86400);

        Cache::put($cacheKey, now()->toIso8601String(), now()->addSeconds($ttlSeconds));

        return self::SUCCESS;
    }
}
