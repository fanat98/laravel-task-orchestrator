<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Feature\Integration\Support;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Malsa\TaskOrchestrator\Models\TaskRunLog;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Malsa\TaskOrchestrator\Support\TaskContext;
use Malsa\TaskOrchestrator\Tests\TestCase;

/**
 * Integration tests for TaskContext.
 *
 * Validates: Requirements 11.1, 11.2, 11.3
 */
class TaskContextTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: log() creates a TaskRunLog record with correct task_run_id, level, and message.
     *
     * Validates: Requirement 11.1
     */
    public function test_log_creates_task_run_log_record(): void
    {
        $taskRunId = 'run-context-log-1';

        TaskRunRecord::query()->create([
            'id' => $taskRunId,
            'task_name' => 'test-task',
            'task_label' => 'Test Task',
            'command' => 'app:test-task',
            'status' => 'running',
            'trigger_type' => 'manual',
        ]);

        $context = new TaskContext($taskRunId, 'test-task');

        $context->log('Processing started', 'info');

        $this->assertDatabaseHas('task_run_logs', [
            'task_run_id' => $taskRunId,
            'level' => 'info',
            'message' => 'Processing started',
        ]);
    }

    /**
     * Test: log() uses default level 'info' and supports custom levels.
     *
     * Validates: Requirement 11.1
     */
    public function test_log_supports_custom_levels(): void
    {
        $taskRunId = 'run-context-log-2';

        TaskRunRecord::query()->create([
            'id' => $taskRunId,
            'task_name' => 'test-task',
            'task_label' => 'Test Task',
            'command' => 'app:test-task',
            'status' => 'running',
            'trigger_type' => 'manual',
        ]);

        $context = new TaskContext($taskRunId, 'test-task');

        $context->log('Something went wrong', 'error');

        $this->assertDatabaseHas('task_run_logs', [
            'task_run_id' => $taskRunId,
            'level' => 'error',
            'message' => 'Something went wrong',
        ]);
    }

    /**
     * Test: setProgress() stores progress in memory, retrievable via progress().
     *
     * Validates: Requirement 11.2
     */
    public function test_set_progress_stores_in_memory(): void
    {
        $context = new TaskContext('run-progress-1', 'test-task');

        $this->assertNull($context->progress());

        $context->setProgress(5, 10, 'Halfway there');

        $progress = $context->progress();

        $this->assertNotNull($progress);
        $this->assertSame(5, $progress->current);
        $this->assertSame(10, $progress->total);
        $this->assertSame('Halfway there', $progress->message);
    }

    /**
     * Test: setProgress() updates progress on subsequent calls.
     *
     * Validates: Requirement 11.2
     */
    public function test_set_progress_updates_on_subsequent_calls(): void
    {
        $context = new TaskContext('run-progress-2', 'test-task');

        $context->setProgress(1, 10, 'Starting');
        $context->setProgress(10, 10, 'Done');

        $progress = $context->progress();

        $this->assertSame(10, $progress->current);
        $this->assertSame(10, $progress->total);
        $this->assertSame('Done', $progress->message);
    }

    /**
     * Test: constructor throws InvalidArgumentException for empty taskRunId.
     *
     * Validates: Requirement 11.3
     */
    public function test_throws_for_empty_task_run_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Task run id cannot be empty.');

        new TaskContext('', 'test-task');
    }

    /**
     * Test: constructor throws InvalidArgumentException for empty taskName.
     *
     * Validates: Requirement 11.3
     */
    public function test_throws_for_empty_task_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Task name cannot be empty.');

        new TaskContext('run-id-1', '');
    }
}
