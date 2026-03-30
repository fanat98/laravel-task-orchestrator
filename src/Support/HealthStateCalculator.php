<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Support;

use Carbon\CarbonImmutable;

final class HealthStateCalculator
{
    public function queueStatus(int $pendingJobs, ?int $oldestPendingJobAgeSeconds, int $stuckThresholdSeconds): string
    {
        if ($pendingJobs <= 0) {
            return 'healthy';
        }

        if ($oldestPendingJobAgeSeconds !== null && $oldestPendingJobAgeSeconds >= $stuckThresholdSeconds) {
            return 'stuck';
        }

        return 'busy';
    }

    public function schedulerStatus(?CarbonImmutable $lastHeartbeatAt, CarbonImmutable $now, int $maxAgeSeconds): string
    {
        if ($lastHeartbeatAt === null) {
            return 'down';
        }

        return $lastHeartbeatAt->greaterThanOrEqualTo($now->subSeconds($maxAgeSeconds))
            ? 'running'
            : 'down';
    }

    public function overallStatus(string $queueStatus, string $schedulerStatus): string
    {
        if ($schedulerStatus === 'down' || $queueStatus === 'stuck') {
            return 'critical';
        }

        if ($queueStatus === 'busy') {
            return 'warning';
        }

        return 'healthy';
    }

    public function message(string $queueStatus, string $schedulerStatus): string
    {
        if ($schedulerStatus === 'down') {
            return 'Scheduler heartbeat is stale or missing.';
        }

        if ($queueStatus === 'stuck') {
            return 'Queue has pending jobs that are older than the configured stuck threshold.';
        }

        if ($queueStatus === 'busy') {
            return 'Queue has pending jobs and is currently processing backlog.';
        }

        return 'Queue and scheduler look healthy.';
    }
}
