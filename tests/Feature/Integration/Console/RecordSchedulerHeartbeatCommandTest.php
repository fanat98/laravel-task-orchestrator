<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Feature\Integration\Console;

use Illuminate\Support\Facades\Cache;
use Malsa\TaskOrchestrator\Tests\TestCase;

/**
 * Integration tests for RecordSchedulerHeartbeatCommand.
 *
 * Validates: Requirement 12.1
 */
class RecordSchedulerHeartbeatCommandTest extends TestCase
{
    /**
     * Test: command writes scheduler heartbeat to cache.
     *
     * Validates: Requirement 12.1
     */
    public function test_command_writes_scheduler_heartbeat_to_cache(): void
    {
        $cacheKey = config('task-orchestrator.health.scheduler_heartbeat_cache_key', 'task-orchestrator:scheduler-heartbeat');

        Cache::forget($cacheKey);
        $this->assertNull(Cache::get($cacheKey));

        $this->artisan('task-orchestrator:record-scheduler-heartbeat')
            ->assertSuccessful();

        $heartbeat = Cache::get($cacheKey);

        $this->assertNotNull($heartbeat, 'Heartbeat should be written to cache');
        $this->assertIsString($heartbeat);

        // Verify the heartbeat is a recent ISO 8601 timestamp
        $parsed = \Carbon\Carbon::parse($heartbeat);
        $this->assertTrue(
            $parsed->greaterThanOrEqualTo(now()->subSeconds(5)),
            'Heartbeat timestamp should be recent'
        );
    }

    /**
     * Test: command uses configured cache key.
     *
     * Validates: Requirement 12.1
     */
    public function test_command_uses_configured_cache_key(): void
    {
        $customKey = 'custom:scheduler-heartbeat';
        config()->set('task-orchestrator.health.scheduler_heartbeat_cache_key', $customKey);

        Cache::forget($customKey);

        $this->artisan('task-orchestrator:record-scheduler-heartbeat')
            ->assertSuccessful();

        $this->assertNotNull(Cache::get($customKey));
    }
}
