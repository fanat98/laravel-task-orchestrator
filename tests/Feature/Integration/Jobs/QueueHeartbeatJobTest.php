<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Feature\Integration\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Cache;
use Malsa\TaskOrchestrator\Jobs\QueueHeartbeatJob;
use Malsa\TaskOrchestrator\Tests\TestCase;

/**
 * Integration tests for QueueHeartbeatJob.
 *
 * Validates: Requirements 8.6, 8.7
 */
class QueueHeartbeatJobTest extends TestCase
{
    /**
     * Test: handle() writes heartbeat timestamp to cache.
     *
     * Validates: Requirement 8.6
     */
    public function test_writes_heartbeat_timestamp_to_cache(): void
    {
        Cache::flush();

        $job = new QueueHeartbeatJob();
        $job->handle();

        $heartbeat = Cache::get('task_orchestrator.queue_worker_heartbeat');

        $this->assertNotNull($heartbeat);
        $this->assertIsString($heartbeat);

        // Verify it's a valid ISO 8601 timestamp
        $parsed = \Carbon\Carbon::parse($heartbeat);
        $this->assertTrue($parsed->isToday());
    }

    /**
     * Test: implements ShouldBeUnique interface.
     *
     * Validates: Requirement 8.7
     */
    public function test_implements_should_be_unique(): void
    {
        $job = new QueueHeartbeatJob();

        $this->assertInstanceOf(ShouldBeUnique::class, $job);
    }

    /**
     * Test: uniqueId returns expected static value.
     *
     * Validates: Requirement 8.7
     */
    public function test_unique_id_returns_expected_value(): void
    {
        $job = new QueueHeartbeatJob();

        $this->assertSame('task-orchestrator-queue-heartbeat', $job->uniqueId());
    }
}
