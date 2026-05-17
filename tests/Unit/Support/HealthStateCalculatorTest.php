<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Unit\Support;

use Carbon\CarbonImmutable;
use Malsa\TaskOrchestrator\Support\HealthStateCalculator;
use Malsa\TaskOrchestrator\Tests\TestCase;

class HealthStateCalculatorTest extends TestCase
{
    private HealthStateCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new HealthStateCalculator();
    }

    // --- queueStatus() tests ---

    public function test_queue_status_returns_healthy_when_no_pending_jobs(): void
    {
        $result = $this->calculator->queueStatus(
            pendingJobs: 0,
            oldestPendingJobAgeSeconds: null,
            stuckThresholdSeconds: 300,
        );

        $this->assertSame('healthy', $result);
    }

    public function test_queue_status_returns_stuck_when_oldest_job_exceeds_threshold(): void
    {
        $result = $this->calculator->queueStatus(
            pendingJobs: 5,
            oldestPendingJobAgeSeconds: 600,
            stuckThresholdSeconds: 300,
        );

        $this->assertSame('stuck', $result);
    }

    public function test_queue_status_returns_stuck_when_oldest_job_equals_threshold(): void
    {
        $result = $this->calculator->queueStatus(
            pendingJobs: 3,
            oldestPendingJobAgeSeconds: 300,
            stuckThresholdSeconds: 300,
        );

        $this->assertSame('stuck', $result);
    }

    public function test_queue_status_returns_busy_when_pending_but_not_stuck(): void
    {
        $result = $this->calculator->queueStatus(
            pendingJobs: 3,
            oldestPendingJobAgeSeconds: 100,
            stuckThresholdSeconds: 300,
        );

        $this->assertSame('busy', $result);
    }

    public function test_queue_status_returns_busy_when_oldest_age_is_null(): void
    {
        $result = $this->calculator->queueStatus(
            pendingJobs: 2,
            oldestPendingJobAgeSeconds: null,
            stuckThresholdSeconds: 300,
        );

        $this->assertSame('busy', $result);
    }

    // --- schedulerStatus() tests ---

    public function test_scheduler_status_returns_running_when_heartbeat_is_recent(): void
    {
        $now = CarbonImmutable::parse('2024-01-01 12:00:00');
        $lastHeartbeat = CarbonImmutable::parse('2024-01-01 11:59:00');

        $result = $this->calculator->schedulerStatus(
            lastHeartbeatAt: $lastHeartbeat,
            now: $now,
            maxAgeSeconds: 120,
        );

        $this->assertSame('running', $result);
    }

    public function test_scheduler_status_returns_down_when_heartbeat_is_stale(): void
    {
        $now = CarbonImmutable::parse('2024-01-01 12:00:00');
        $lastHeartbeat = CarbonImmutable::parse('2024-01-01 11:55:00');

        $result = $this->calculator->schedulerStatus(
            lastHeartbeatAt: $lastHeartbeat,
            now: $now,
            maxAgeSeconds: 120,
        );

        $this->assertSame('down', $result);
    }

    public function test_scheduler_status_returns_down_when_heartbeat_is_null(): void
    {
        $now = CarbonImmutable::parse('2024-01-01 12:00:00');

        $result = $this->calculator->schedulerStatus(
            lastHeartbeatAt: null,
            now: $now,
            maxAgeSeconds: 120,
        );

        $this->assertSame('down', $result);
    }

    // --- queueWorkerStatus() tests ---

    public function test_queue_worker_status_returns_running_when_heartbeat_is_recent(): void
    {
        $now = CarbonImmutable::parse('2024-01-01 12:00:00');
        $lastHeartbeat = CarbonImmutable::parse('2024-01-01 11:59:30');

        $result = $this->calculator->queueWorkerStatus(
            lastHeartbeatAt: $lastHeartbeat,
            now: $now,
            maxAgeSeconds: 60,
        );

        $this->assertSame('running', $result);
    }

    public function test_queue_worker_status_returns_down_when_heartbeat_is_stale(): void
    {
        $now = CarbonImmutable::parse('2024-01-01 12:00:00');
        $lastHeartbeat = CarbonImmutable::parse('2024-01-01 11:58:00');

        $result = $this->calculator->queueWorkerStatus(
            lastHeartbeatAt: $lastHeartbeat,
            now: $now,
            maxAgeSeconds: 60,
        );

        $this->assertSame('down', $result);
    }

    public function test_queue_worker_status_returns_down_when_heartbeat_is_null(): void
    {
        $now = CarbonImmutable::parse('2024-01-01 12:00:00');

        $result = $this->calculator->queueWorkerStatus(
            lastHeartbeatAt: null,
            now: $now,
            maxAgeSeconds: 60,
        );

        $this->assertSame('down', $result);
    }

    // --- overallStatus() tests ---

    public function test_overall_status_returns_critical_when_scheduler_is_down(): void
    {
        $result = $this->calculator->overallStatus(
            queueStatus: 'healthy',
            schedulerStatus: 'down',
            queueWorkerStatus: 'running',
            pendingJobs: 0,
        );

        $this->assertSame('critical', $result);
    }

    public function test_overall_status_returns_critical_when_queue_is_stuck(): void
    {
        $result = $this->calculator->overallStatus(
            queueStatus: 'stuck',
            schedulerStatus: 'running',
            queueWorkerStatus: 'running',
            pendingJobs: 5,
        );

        $this->assertSame('critical', $result);
    }

    public function test_overall_status_returns_critical_when_worker_is_down_with_pending_jobs(): void
    {
        $result = $this->calculator->overallStatus(
            queueStatus: 'healthy',
            schedulerStatus: 'running',
            queueWorkerStatus: 'down',
            pendingJobs: 3,
        );

        $this->assertSame('critical', $result);
    }

    public function test_overall_status_returns_warning_when_queue_is_busy(): void
    {
        $result = $this->calculator->overallStatus(
            queueStatus: 'busy',
            schedulerStatus: 'running',
            queueWorkerStatus: 'running',
            pendingJobs: 2,
        );

        $this->assertSame('warning', $result);
    }

    public function test_overall_status_returns_warning_when_worker_is_down_without_pending_jobs(): void
    {
        $result = $this->calculator->overallStatus(
            queueStatus: 'healthy',
            schedulerStatus: 'running',
            queueWorkerStatus: 'down',
            pendingJobs: 0,
        );

        $this->assertSame('warning', $result);
    }

    public function test_overall_status_returns_healthy_when_all_systems_operational(): void
    {
        $result = $this->calculator->overallStatus(
            queueStatus: 'healthy',
            schedulerStatus: 'running',
            queueWorkerStatus: 'running',
            pendingJobs: 0,
        );

        $this->assertSame('healthy', $result);
    }

    // --- message() tests ---

    public function test_message_returns_scheduler_stale_when_scheduler_is_down(): void
    {
        $result = $this->calculator->message(
            queueStatus: 'healthy',
            schedulerStatus: 'down',
            queueWorkerStatus: 'running',
            pendingJobs: 0,
        );

        $this->assertSame('Scheduler heartbeat is stale or missing.', $result);
    }

    public function test_message_returns_worker_stale_with_pending_when_worker_down_and_pending_jobs(): void
    {
        $result = $this->calculator->message(
            queueStatus: 'healthy',
            schedulerStatus: 'running',
            queueWorkerStatus: 'down',
            pendingJobs: 5,
        );

        $this->assertSame('Queue worker heartbeat is stale or missing while pending jobs exist.', $result);
    }

    public function test_message_returns_worker_stale_when_worker_down_without_pending_jobs(): void
    {
        $result = $this->calculator->message(
            queueStatus: 'healthy',
            schedulerStatus: 'running',
            queueWorkerStatus: 'down',
            pendingJobs: 0,
        );

        $this->assertSame('Queue worker heartbeat is stale or missing.', $result);
    }

    public function test_message_returns_stuck_message_when_queue_is_stuck(): void
    {
        $result = $this->calculator->message(
            queueStatus: 'stuck',
            schedulerStatus: 'running',
            queueWorkerStatus: 'running',
            pendingJobs: 5,
        );

        $this->assertSame('Queue has pending jobs that are older than the configured stuck threshold.', $result);
    }

    public function test_message_returns_busy_message_when_queue_is_busy(): void
    {
        $result = $this->calculator->message(
            queueStatus: 'busy',
            schedulerStatus: 'running',
            queueWorkerStatus: 'running',
            pendingJobs: 3,
        );

        $this->assertSame('Queue has pending jobs and is currently processing backlog.', $result);
    }

    public function test_message_returns_healthy_message_when_all_systems_operational(): void
    {
        $result = $this->calculator->message(
            queueStatus: 'healthy',
            schedulerStatus: 'running',
            queueWorkerStatus: 'running',
            pendingJobs: 0,
        );

        $this->assertSame('Queue and scheduler look healthy.', $result);
    }
}
