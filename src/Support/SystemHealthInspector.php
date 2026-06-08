<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

final class SystemHealthInspector
{
    public function __construct(
        private readonly HealthStateCalculator $stateCalculator,
    ) {
    }

    /**
     * @return array{
     *     status: string,
     *     queue: array{status: string, pending_jobs: int|null, oldest_pending_job_age_seconds: int|null},
     *     scheduler: array{status: string, last_heartbeat_at: string|null},
     *     queue_worker: array{status: string, last_heartbeat_at: string|null},
     *     pending_jobs: int|null,
     *     oldest_pending_job_age_seconds: int|null,
     *     message: string
     * }
     */
    public function inspect(): array
    {
        $queueMetrics = $this->getQueueMetrics();
        $pendingJobs = $queueMetrics['pending_jobs'];
        $oldestPendingJobAgeSeconds = $queueMetrics['oldest_pending_job_age_seconds'];

        $stuckThresholdSeconds = (int) config('task-orchestrator.health.queue_stuck_threshold_seconds', 300);
        $lastHeartbeatAt = $this->getSchedulerHeartbeat();
        $heartbeatMaxAgeSeconds = (int) config('task-orchestrator.health.scheduler_heartbeat_max_age_seconds', 180);
        $lastQueueWorkerHeartbeatAt = $this->getQueueWorkerHeartbeat();
        $queueWorkerHeartbeatMaxAgeSeconds = (int) config(
            'task-orchestrator.health.queue_worker.heartbeat_max_age_seconds',
            60
        );
        $now = CarbonImmutable::now();

        $queueStatus = $this->stateCalculator->queueStatus(
            $pendingJobs ?? 0,
            $oldestPendingJobAgeSeconds,
            $stuckThresholdSeconds
        );

        $schedulerStatus = $this->stateCalculator->schedulerStatus(
            $lastHeartbeatAt,
            $now,
            $heartbeatMaxAgeSeconds
        );

        $queueWorkerStatus = $this->stateCalculator->queueWorkerStatus(
            $lastQueueWorkerHeartbeatAt,
            $now,
            $queueWorkerHeartbeatMaxAgeSeconds
        );
        $pendingJobsForAggregation = $pendingJobs ?? 0;
        $overallStatus = $this->stateCalculator->overallStatus(
            $queueStatus,
            $schedulerStatus,
            $queueWorkerStatus,
            $pendingJobsForAggregation,
        );
        $message = $this->stateCalculator->message(
            $queueStatus,
            $schedulerStatus,
            $queueWorkerStatus,
            $pendingJobsForAggregation,
        );

        return [
            'status' => $overallStatus,
            'queue' => [
                'status' => $queueStatus,
                'pending_jobs' => $pendingJobs,
                'oldest_pending_job_age_seconds' => $oldestPendingJobAgeSeconds,
            ],
            'scheduler' => [
                'status' => $schedulerStatus,
                'last_heartbeat_at' => $lastHeartbeatAt?->toIso8601String(),
            ],
            'queue_worker' => [
                'status' => $queueWorkerStatus,
                'last_heartbeat_at' => $lastQueueWorkerHeartbeatAt?->toIso8601String(),
            ],
            // Keep top-level metrics for backward compatibility in the dashboard payload.
            'pending_jobs' => $pendingJobs,
            'oldest_pending_job_age_seconds' => $oldestPendingJobAgeSeconds,
            'message' => $message,
        ];
    }

    /**
     * @return array{pending_jobs: int|null, oldest_pending_job_age_seconds: int|null}
     */
    private function getQueueMetrics(): array
    {
        try {
            $pendingJobs = (int) DB::table('jobs')
                ->whereNull('reserved_at')
                ->count();

            if ($pendingJobs === 0) {
                return [
                    'pending_jobs' => 0,
                    'oldest_pending_job_age_seconds' => null,
                ];
            }

            $oldestPendingTimestamp = DB::table('jobs')
                ->whereNull('reserved_at')
                ->min('available_at');

            if (! is_numeric($oldestPendingTimestamp)) {
                return [
                    'pending_jobs' => $pendingJobs,
                    'oldest_pending_job_age_seconds' => null,
                ];
            }

            return [
                'pending_jobs' => $pendingJobs,
                'oldest_pending_job_age_seconds' => max(0, now()->timestamp - (int) $oldestPendingTimestamp),
            ];
        } catch (Throwable) {
            return [
                'pending_jobs' => null,
                'oldest_pending_job_age_seconds' => null,
            ];
        }
    }

    private function getSchedulerHeartbeat(): ?CarbonImmutable
    {
        $heartbeatValue = Cache::get((string) config(
            'task-orchestrator.health.scheduler_heartbeat_cache_key',
            'task-orchestrator:scheduler-heartbeat'
        ));

        if (! is_string($heartbeatValue) || trim($heartbeatValue) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($heartbeatValue);
        } catch (Throwable) {
            return null;
        }
    }

    private function getQueueWorkerHeartbeat(): ?CarbonImmutable
    {
        $heartbeatValue = Cache::get('task_orchestrator.queue_worker_heartbeat');

        if (! is_string($heartbeatValue) || trim($heartbeatValue) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($heartbeatValue);
        } catch (Throwable) {
            return null;
        }
    }
}
