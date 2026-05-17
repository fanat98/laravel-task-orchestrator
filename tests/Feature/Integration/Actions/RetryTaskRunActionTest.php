<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Feature\Integration\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Malsa\TaskOrchestrator\Actions\RetryTaskRunAction;
use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Domain\TaskDefinition;
use Malsa\TaskOrchestrator\Domain\TaskRun;
use Malsa\TaskOrchestrator\Jobs\ExecuteTaskRunJob;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Malsa\TaskOrchestrator\Support\TaskContext;
use Malsa\TaskOrchestrator\Support\TaskOrchestratorManager;
use Malsa\TaskOrchestrator\Tests\TestCase;

/**
 * Integration tests for RetryTaskRunAction.
 *
 * Validates: Requirements 6.6, 6.7
 */
class RetryTaskRunActionTest extends TestCase
{
    use RefreshDatabase;

    private TaskOrchestratorManager $manager;
    private RetryTaskRunAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = app(TaskOrchestratorManager::class);
        $this->action = app(RetryTaskRunAction::class);
    }

    /**
     * Test: returns existing active run when one exists (does not create a new one).
     *
     * Validates: Requirement 6.6
     */
    public function test_returns_existing_active_run_when_one_exists(): void
    {
        Queue::fake();

        $definition = TaskDefinition::make('import-users')
            ->label('Import Users')
            ->command('app:import-users');

        $this->manager->register($definition);

        // Create a failed run (the one we want to retry)
        $failedRun = TaskRunRecord::query()->create([
            'id' => Str::uuid()->toString(),
            'task_name' => 'import-users',
            'task_label' => 'Import Users',
            'command' => 'app:import-users',
            'status' => TaskRunStatus::Failed->value,
            'trigger_type' => 'manual',
        ]);

        // Create an active (running) run for the same task
        $activeRun = TaskRunRecord::query()->create([
            'id' => Str::uuid()->toString(),
            'task_name' => 'import-users',
            'task_label' => 'Import Users',
            'command' => 'app:import-users',
            'status' => TaskRunStatus::Running->value,
            'trigger_type' => 'manual',
        ]);

        $result = $this->action->execute($failedRun->id);

        // Should return the existing active run, not create a new one
        $this->assertInstanceOf(TaskRun::class, $result['run']);
        $this->assertSame($activeRun->id, $result['run']->id);
        $this->assertSame('import-users', $result['run']->taskName);
        $this->assertSame(TaskRunStatus::Running, $result['run']->status);

        // Verify context is created for the active run
        $this->assertInstanceOf(TaskContext::class, $result['context']);

        // Verify record is the active run
        $this->assertInstanceOf(TaskRunRecord::class, $result['record']);
        $this->assertSame($activeRun->id, $result['record']->id);

        // Verify no new run was created (only the 2 original records exist)
        $this->assertSame(2, TaskRunRecord::query()->count());

        // Verify no job was dispatched
        Queue::assertNothingPushed();
    }

    /**
     * Test: returns existing queued run when one exists.
     *
     * Validates: Requirement 6.6
     */
    public function test_returns_existing_queued_run_when_one_exists(): void
    {
        Queue::fake();

        $definition = TaskDefinition::make('sync-data')
            ->label('Sync Data')
            ->command('app:sync-data');

        $this->manager->register($definition);

        // Create a failed run (the one we want to retry)
        $failedRun = TaskRunRecord::query()->create([
            'id' => Str::uuid()->toString(),
            'task_name' => 'sync-data',
            'task_label' => 'Sync Data',
            'command' => 'app:sync-data',
            'status' => TaskRunStatus::Failed->value,
            'trigger_type' => 'manual',
        ]);

        // Create an active (queued) run for the same task
        $queuedRun = TaskRunRecord::query()->create([
            'id' => Str::uuid()->toString(),
            'task_name' => 'sync-data',
            'task_label' => 'Sync Data',
            'command' => 'app:sync-data',
            'status' => TaskRunStatus::Queued->value,
            'trigger_type' => 'manual',
        ]);

        $result = $this->action->execute($failedRun->id);

        // Should return the existing queued run
        $this->assertSame($queuedRun->id, $result['run']->id);
        $this->assertSame(TaskRunStatus::Queued, $result['run']->status);

        // Verify no new run was created
        $this->assertSame(2, TaskRunRecord::query()->count());

        // Verify no job was dispatched
        Queue::assertNothingPushed();
    }

    /**
     * Test: creates new run via StartTaskAction when no active run exists.
     *
     * Validates: Requirement 6.7
     */
    public function test_creates_new_run_when_no_active_run_exists(): void
    {
        Queue::fake();

        $definition = TaskDefinition::make('export-reports')
            ->label('Export Reports')
            ->command('app:export-reports');

        $this->manager->register($definition);

        // Create a failed run (the one we want to retry)
        $failedRun = TaskRunRecord::query()->create([
            'id' => Str::uuid()->toString(),
            'task_name' => 'export-reports',
            'task_label' => 'Export Reports',
            'command' => 'app:export-reports',
            'status' => TaskRunStatus::Failed->value,
            'trigger_type' => 'manual',
        ]);

        $result = $this->action->execute($failedRun->id);

        // Should create a new run
        $this->assertInstanceOf(TaskRun::class, $result['run']);
        $this->assertNotSame($failedRun->id, $result['run']->id);
        $this->assertSame('export-reports', $result['run']->taskName);
        $this->assertSame(TaskRunStatus::Queued, $result['run']->status);

        // Verify context and record are returned
        $this->assertInstanceOf(TaskContext::class, $result['context']);
        $this->assertInstanceOf(TaskRunRecord::class, $result['record']);

        // Verify the new record has trigger_type 'retry'
        $newRecord = TaskRunRecord::query()->find($result['run']->id);
        $this->assertNotNull($newRecord);
        $this->assertSame('retry', $newRecord->trigger_type);
        $this->assertSame(TaskRunStatus::Queued->value, $newRecord->status);

        // Verify a new run was created (original failed + new queued)
        $this->assertSame(2, TaskRunRecord::query()->count());

        // Verify job was dispatched for the new run
        Queue::assertPushed(ExecuteTaskRunJob::class, function (ExecuteTaskRunJob $job) use ($result): bool {
            return $job->taskRunId === $result['run']->id;
        });
    }
}
