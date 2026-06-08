<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Feature\Integration\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Domain\TaskDefinition;
use Malsa\TaskOrchestrator\Jobs\ExecuteTaskRunJob;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Malsa\TaskOrchestrator\Support\TaskOrchestratorManager;
use Malsa\TaskOrchestrator\Tests\TestCase;

/**
 * Integration tests for RunScheduledTaskCommand.
 *
 * Validates: Requirement 12.3
 */
class RunScheduledTaskCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: starts tasks due according to schedule when queue worker is healthy.
     *
     * Validates: Requirement 12.3
     */
    public function test_starts_task_when_queue_worker_is_healthy(): void
    {
        Queue::fake();

        // Simulate a healthy queue worker heartbeat
        Cache::put('task_orchestrator.queue_worker_heartbeat', now()->toIso8601String(), now()->addDay());

        $manager = app(TaskOrchestratorManager::class);
        $manager->register(
            TaskDefinition::make('scheduled-task')
                ->label('Scheduled Task')
                ->command('app:scheduled-task')
        );

        $this->artisan('task-orchestrator:run-scheduled-task', ['task' => 'scheduled-task'])
            ->assertSuccessful();

        Queue::assertPushed(ExecuteTaskRunJob::class);

        $this->assertTrue(
            TaskRunRecord::query()
                ->where('task_name', 'scheduled-task')
                ->where('trigger_type', 'scheduled')
                ->exists(),
            'Expected a task run record to be created with scheduled trigger'
        );
    }

    /**
     * Test: marks a scheduled start as failed when queue worker heartbeat is stale.
     *
     * Validates: Requirement 12.3
     */
    public function test_marks_task_as_failed_when_queue_worker_is_down(): void
    {
        Queue::fake();

        // Simulate a stale queue worker heartbeat (2 minutes old, threshold is 60 seconds)
        Cache::put('task_orchestrator.queue_worker_heartbeat', now()->subMinutes(2)->toIso8601String(), now()->addDay());

        $manager = app(TaskOrchestratorManager::class);
        $manager->register(
            TaskDefinition::make('scheduled-task')
                ->label('Scheduled Task')
                ->command('app:scheduled-task')
        );

        $this->artisan('task-orchestrator:run-scheduled-task', ['task' => 'scheduled-task'])
            ->assertSuccessful();

        Queue::assertNothingPushed();

        $failedRun = TaskRunRecord::query()
            ->where('task_name', 'scheduled-task')
            ->where('trigger_type', 'scheduled')
            ->latest('created_at')
            ->first();

        $this->assertNotNull($failedRun);
        $this->assertSame(TaskRunStatus::Failed->value, $failedRun->status);
        $this->assertNotNull($failedRun->finished_at);
        $this->assertStringContainsString('Scheduled task was not started.', (string) $failedRun->failure_message);
    }

    /**
     * Test: marks a scheduled start as failed when queue worker heartbeat is missing.
     *
     * Validates: Requirement 12.3
     */
    public function test_marks_task_as_failed_when_queue_worker_heartbeat_missing(): void
    {
        Queue::fake();

        // No heartbeat in cache
        Cache::forget('task_orchestrator.queue_worker_heartbeat');

        $manager = app(TaskOrchestratorManager::class);
        $manager->register(
            TaskDefinition::make('scheduled-task')
                ->label('Scheduled Task')
                ->command('app:scheduled-task')
        );

        $this->artisan('task-orchestrator:run-scheduled-task', ['task' => 'scheduled-task'])
            ->assertSuccessful();

        Queue::assertNothingPushed();

        $this->assertTrue(
            TaskRunRecord::query()
                ->where('task_name', 'scheduled-task')
                ->where('trigger_type', 'scheduled')
                ->where('status', TaskRunStatus::Failed->value)
                ->exists()
        );
    }
}
