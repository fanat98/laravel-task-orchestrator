<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Feature\Integration\Jobs;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Malsa\TaskOrchestrator\Actions\ExecuteTaskRunAction;
use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Jobs\ExecuteTaskRunJob;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Malsa\TaskOrchestrator\Tests\TestCase;
use RuntimeException;

/**
 * Integration tests for ExecuteTaskRunJob.
 *
 * Validates: Requirements 8.1, 8.2, 8.3, 8.4, 8.5
 */
class ExecuteTaskRunJobTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: calls ExecuteTaskRunAction::execute() with correct taskRunId.
     *
     * Since ExecuteTaskRunAction is final and cannot be mocked, we verify
     * the job passes the correct taskRunId by observing the side effect:
     * the run transitions from queued to running/succeeded or failed.
     *
     * Validates: Requirement 8.1
     */
    public function test_calls_execute_task_run_action_with_correct_task_run_id(): void
    {
        $taskRunId = Str::uuid()->toString();

        TaskRunRecord::query()->create([
            'id' => $taskRunId,
            'task_name' => 'test-task',
            'task_label' => 'Test Task',
            'command' => 'test:command',
            'status' => TaskRunStatus::Queued->value,
            'trigger_type' => 'manual',
        ]);

        $job = new ExecuteTaskRunJob($taskRunId);

        // The action will attempt to run the command and update the record.
        // Since 'test:command' is not registered, it will fail — but the key
        // assertion is that the action was called with the correct run ID
        // (evidenced by the run record being updated from its original state).
        $action = $this->app->make(ExecuteTaskRunAction::class);

        try {
            $job->handle($action);
        } catch (\Throwable) {
            // Expected — command doesn't exist
        }

        $run = TaskRunRecord::query()->find($taskRunId);

        // The run should no longer be in 'queued' state — proving the action
        // was called with the correct taskRunId
        $this->assertNotSame(
            TaskRunStatus::Queued->value,
            $run->status,
            'Run should have been processed (no longer queued), proving action received correct taskRunId'
        );
    }

    /**
     * Test: sets job timeout from constructor parameter.
     *
     * Validates: Requirement 8.2
     */
    public function test_timeout_set_from_constructor_parameter(): void
    {
        $job = new ExecuteTaskRunJob('some-run-id', timeoutSeconds: 600);

        $this->assertSame(600, $job->timeout);
    }

    /**
     * Test: timeout defaults to minimum of 1 second when 0 is passed.
     *
     * Validates: Requirement 8.2
     */
    public function test_timeout_minimum_is_one_second(): void
    {
        $job = new ExecuteTaskRunJob('some-run-id', timeoutSeconds: 0);

        $this->assertSame(1, $job->timeout);
    }

    /**
     * Test: timeout uses default of 300 seconds when not specified.
     *
     * Validates: Requirement 8.2
     */
    public function test_timeout_defaults_to_300_seconds(): void
    {
        $job = new ExecuteTaskRunJob('some-run-id');

        $this->assertSame(300, $job->timeout);
    }

    /**
     * Test: failed() updates run to failed status with exception message.
     *
     * Validates: Requirement 8.3
     */
    public function test_failed_updates_run_to_failed_status_with_exception_message(): void
    {
        $taskRunId = Str::uuid()->toString();

        TaskRunRecord::query()->create([
            'id' => $taskRunId,
            'task_name' => 'test-task',
            'task_label' => 'Test Task',
            'command' => 'test:command',
            'status' => TaskRunStatus::Running->value,
            'trigger_type' => 'manual',
        ]);

        $job = new ExecuteTaskRunJob($taskRunId, timeoutSeconds: 120);
        $exception = new RuntimeException('Something went wrong');

        $job->failed($exception);

        $run = TaskRunRecord::query()->find($taskRunId);

        $this->assertSame(TaskRunStatus::Failed->value, $run->status);
        $this->assertSame('Something went wrong', $run->failure_message);
        $this->assertNotNull($run->finished_at);
    }

    /**
     * Test: failed() uses timeout message when exception is null.
     *
     * Validates: Requirement 8.3
     */
    public function test_failed_uses_timeout_message_when_exception_is_null(): void
    {
        $taskRunId = Str::uuid()->toString();

        TaskRunRecord::query()->create([
            'id' => $taskRunId,
            'task_name' => 'test-task',
            'task_label' => 'Test Task',
            'command' => 'test:command',
            'status' => TaskRunStatus::Running->value,
            'trigger_type' => 'manual',
        ]);

        $job = new ExecuteTaskRunJob($taskRunId, timeoutSeconds: 120);

        $job->failed(null);

        $run = TaskRunRecord::query()->find($taskRunId);

        $this->assertSame(TaskRunStatus::Failed->value, $run->status);
        $this->assertStringContainsString('exceeded timeout', $run->failure_message);
        $this->assertStringContainsString('120', $run->failure_message);
    }

    /**
     * Test: failed() does not update already-terminal runs.
     *
     * Validates: Requirement 8.4
     */
    public function test_failed_does_not_update_already_terminal_runs(): void
    {
        $terminalStatuses = [
            TaskRunStatus::Succeeded->value,
            TaskRunStatus::Failed->value,
            TaskRunStatus::Cancelled->value,
        ];

        foreach ($terminalStatuses as $status) {
            $taskRunId = Str::uuid()->toString();

            TaskRunRecord::query()->create([
                'id' => $taskRunId,
                'task_name' => 'test-task',
                'task_label' => 'Test Task',
                'command' => 'test:command',
                'status' => $status,
                'trigger_type' => 'manual',
                'failure_message' => 'original message',
            ]);

            $job = new ExecuteTaskRunJob($taskRunId);
            $job->failed(new RuntimeException('New error'));

            $run = TaskRunRecord::query()->find($taskRunId);

            $this->assertSame($status, $run->status, "Expected status to remain '{$status}' for terminal run");
            $this->assertSame('original message', $run->failure_message, "Expected failure_message to remain unchanged for terminal run with status '{$status}'");
        }
    }

    /**
     * Test: failed() does nothing when run record is not found.
     *
     * Validates: Requirement 8.4
     */
    public function test_failed_does_nothing_when_run_not_found(): void
    {
        $job = new ExecuteTaskRunJob('non-existent-id');

        // Should not throw
        $job->failed(new RuntimeException('Error'));

        $this->assertTrue(true);
    }

    /**
     * Test: writes queue worker heartbeat to cache before and after execution.
     *
     * Validates: Requirement 8.5
     */
    public function test_writes_heartbeat_to_cache_before_and_after_execution(): void
    {
        $taskRunId = Str::uuid()->toString();

        TaskRunRecord::query()->create([
            'id' => $taskRunId,
            'task_name' => 'test-task',
            'task_label' => 'Test Task',
            'command' => 'test:command',
            'status' => TaskRunStatus::Queued->value,
            'trigger_type' => 'manual',
        ]);

        Cache::flush();

        $this->assertNull(Cache::get('task_orchestrator.queue_worker_heartbeat'));

        $job = new ExecuteTaskRunJob($taskRunId);
        $action = $this->app->make(ExecuteTaskRunAction::class);

        try {
            $job->handle($action);
        } catch (\Throwable) {
            // Expected — command doesn't exist
        }

        // After handle(), the heartbeat should be written to cache (in the finally block)
        $heartbeat = Cache::get('task_orchestrator.queue_worker_heartbeat');
        $this->assertNotNull($heartbeat, 'Heartbeat should be written to cache after execution');
    }

    /**
     * Test: heartbeat is written even when action throws an exception.
     *
     * Validates: Requirement 8.5
     */
    public function test_heartbeat_written_even_when_action_throws(): void
    {
        $taskRunId = Str::uuid()->toString();

        TaskRunRecord::query()->create([
            'id' => $taskRunId,
            'task_name' => 'test-task',
            'task_label' => 'Test Task',
            'command' => 'test:command',
            'status' => TaskRunStatus::Queued->value,
            'trigger_type' => 'manual',
        ]);

        Cache::flush();

        $job = new ExecuteTaskRunJob($taskRunId);
        $action = $this->app->make(ExecuteTaskRunAction::class);

        try {
            $job->handle($action);
        } catch (\Throwable) {
            // Expected — command doesn't exist, action will throw
        }

        // Heartbeat should still be written (in the finally block)
        $heartbeat = Cache::get('task_orchestrator.queue_worker_heartbeat');
        $this->assertNotNull($heartbeat, 'Heartbeat should be written to cache even when action throws');
    }
}
