<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Feature\Integration\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Malsa\TaskOrchestrator\Actions\StartTaskAction;
use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Domain\TaskDefinition;
use Malsa\TaskOrchestrator\Domain\TaskRun;
use Malsa\TaskOrchestrator\Jobs\ExecuteTaskRunJob;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Malsa\TaskOrchestrator\Support\TaskOrchestratorManager;
use Malsa\TaskOrchestrator\Tests\TestCase;

/**
 * Integration tests for StartTaskAction.
 *
 * Validates: Requirements 6.1, 6.2, 6.3, 6.4, 6.5
 */
class StartTaskActionTest extends TestCase
{
    use RefreshDatabase;

    private TaskOrchestratorManager $manager;
    private StartTaskAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = app(TaskOrchestratorManager::class);
        $this->action = app(StartTaskAction::class);
    }

    /**
     * Test: creates TaskRunRecord with correct status, task_name, trigger_type, and pipeline_id.
     *
     * Validates: Requirement 6.1
     */
    public function test_creates_task_run_record_with_correct_fields(): void
    {
        Queue::fake();

        $definition = TaskDefinition::make('import-users')
            ->label('Import Users')
            ->command('app:import-users');

        $this->manager->register($definition);

        $pipelineId = 'pipeline-123';
        $result = $this->action->execute('import-users', 'manual', $pipelineId);

        // Verify the returned run object
        $this->assertInstanceOf(TaskRun::class, $result['run']);
        $this->assertSame('import-users', $result['run']->taskName);
        $this->assertSame(TaskRunStatus::Queued, $result['run']->status);

        // Verify the database record
        $record = TaskRunRecord::query()->find($result['run']->id);
        $this->assertNotNull($record);
        $this->assertSame('import-users', $record->task_name);
        $this->assertSame('Import Users', $record->task_label);
        $this->assertSame('app:import-users', $record->command);
        $this->assertSame(TaskRunStatus::Queued->value, $record->status);
        $this->assertSame('manual', $record->trigger_type);
        $this->assertSame($pipelineId, $record->pipeline_id);
    }

    /**
     * Test: dispatches ExecuteTaskRunJob with correct taskRunId and timeout.
     *
     * Validates: Requirement 6.2
     */
    public function test_dispatches_execute_task_run_job_with_correct_parameters(): void
    {
        Queue::fake();

        $definition = TaskDefinition::make('sync-data')
            ->label('Sync Data')
            ->command('app:sync-data')
            ->timeoutMinutes(5);

        $this->manager->register($definition);

        $result = $this->action->execute('sync-data', 'manual');

        Queue::assertPushed(ExecuteTaskRunJob::class, function (ExecuteTaskRunJob $job) use ($result): bool {
            return $job->taskRunId === $result['run']->id
                && $job->timeoutSeconds === 300; // 5 minutes * 60
        });
    }

    /**
     * Test: throws InvalidArgumentException for unregistered task name.
     *
     * Validates: Requirement 6.3
     */
    public function test_throws_for_unregistered_task(): void
    {
        Queue::fake();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Task "non-existent-task" is not registered.');

        $this->action->execute('non-existent-task', 'manual');
    }

    /**
     * Test: throws RuntimeException when task is already running and concurrent runs are disabled.
     *
     * Validates: Requirement 6.4
     */
    public function test_throws_when_blocked_by_concurrent_run(): void
    {
        Queue::fake();

        $definition = TaskDefinition::make('export-reports')
            ->label('Export Reports')
            ->command('app:export-reports')
            ->allowConcurrentRuns(false);

        $this->manager->register($definition);

        // Create an existing running task run
        TaskRunRecord::query()->create([
            'id' => 'existing-run-id',
            'task_name' => 'export-reports',
            'task_label' => 'Export Reports',
            'command' => 'app:export-reports',
            'status' => TaskRunStatus::Running->value,
            'trigger_type' => 'manual',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Task "export-reports" is already queued or running.');

        $this->action->execute('export-reports', 'manual');
    }

    /**
     * Test: calculates timeout from definition's timeoutMinutes.
     *
     * Validates: Requirement 6.5
     */
    public function test_calculates_timeout_from_definition_timeout_minutes(): void
    {
        Queue::fake();

        $definition = TaskDefinition::make('long-task')
            ->label('Long Task')
            ->command('app:long-task')
            ->timeoutMinutes(30);

        $this->manager->register($definition);

        $result = $this->action->execute('long-task', 'manual');

        // 30 minutes * 60 = 1800 seconds
        $record = TaskRunRecord::query()->find($result['run']->id);
        $this->assertSame(1800, $record->timeout_seconds);

        Queue::assertPushed(ExecuteTaskRunJob::class, function (ExecuteTaskRunJob $job): bool {
            return $job->timeoutSeconds === 1800;
        });
    }

    /**
     * Test: calculates timeout from config default when definition has no timeoutMinutes.
     *
     * Validates: Requirement 6.5
     */
    public function test_calculates_timeout_from_config_default(): void
    {
        Queue::fake();

        config()->set('task-orchestrator.stale_run_default_minutes', 15);

        $definition = TaskDefinition::make('default-timeout-task')
            ->label('Default Timeout Task')
            ->command('app:default-timeout');

        $this->manager->register($definition);

        $result = $this->action->execute('default-timeout-task', 'manual');

        // 15 minutes * 60 = 900 seconds
        $record = TaskRunRecord::query()->find($result['run']->id);
        $this->assertSame(900, $record->timeout_seconds);

        Queue::assertPushed(ExecuteTaskRunJob::class, function (ExecuteTaskRunJob $job): bool {
            return $job->timeoutSeconds === 900;
        });
    }
}
