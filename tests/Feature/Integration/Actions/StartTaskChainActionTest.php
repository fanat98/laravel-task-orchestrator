<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Feature\Integration\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Malsa\TaskOrchestrator\Actions\StartTaskChainAction;
use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Domain\TaskDefinition;
use Malsa\TaskOrchestrator\Domain\TaskRun;
use Malsa\TaskOrchestrator\Jobs\ExecuteTaskRunJob;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Malsa\TaskOrchestrator\Support\TaskOrchestratorManager;
use Malsa\TaskOrchestrator\Tests\TestCase;
use RuntimeException;

/**
 * Integration tests for StartTaskChainAction.
 *
 * Validates: Requirement 6.8
 */
class StartTaskChainActionTest extends TestCase
{
    use RefreshDatabase;

    private TaskOrchestratorManager $manager;
    private StartTaskChainAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = app(TaskOrchestratorManager::class);
        $this->action = app(StartTaskChainAction::class);
    }

    /**
     * Test: starts a single task with no dependencies and assigns a pipeline_id.
     *
     * Validates: Requirement 6.8
     */
    public function test_starts_single_task_with_no_dependencies(): void
    {
        Queue::fake();

        $task = TaskDefinition::make('standalone-task')
            ->label('Standalone Task')
            ->command('app:standalone');

        $this->manager->register($task);

        $results = $this->action->execute('standalone-task', 'manual');

        // Should return a single result
        $this->assertCount(1, $results);
        $this->assertSame('standalone-task', $results[0]['run']->taskName);
        $this->assertSame(TaskRunStatus::Queued, $results[0]['run']->status);

        // Should have a pipeline_id assigned
        $record = TaskRunRecord::query()->find($results[0]['run']->id);
        $this->assertNotNull($record->pipeline_id);

        Queue::assertPushed(ExecuteTaskRunJob::class, 1);
    }

    /**
     * Test: resolves dependencies and starts the root task, but dependent tasks
     * are blocked by the execution blocking guard (dependencies are active).
     *
     * The chain action resolves the full dependency graph and attempts to start
     * all tasks in topological order. However, the blocking guard prevents
     * starting tasks whose dependencies have active (queued/running) runs.
     * This is by design — downstream tasks are triggered by StartDownstreamTasksAction
     * after their dependencies complete.
     *
     * Validates: Requirement 6.8
     */
    public function test_chain_with_dependencies_blocks_dependent_tasks(): void
    {
        Queue::fake();

        $taskA = TaskDefinition::make('task-a')
            ->label('Task A')
            ->command('app:task-a');

        $taskB = TaskDefinition::make('task-b')
            ->label('Task B')
            ->command('app:task-b')
            ->dependsOn(['task-a']);

        $this->manager->register($taskA);
        $this->manager->register($taskB);

        // Starting the chain for task-b should start task-a first,
        // then fail on task-b because task-a is now queued (active dependency)
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Task "task-b" is blocked by dependencies.');

        $this->action->execute('task-b', 'manual');
    }

    /**
     * Test: uses the specified trigger type for the started task.
     *
     * Validates: Requirement 6.8
     */
    public function test_uses_specified_trigger_type(): void
    {
        Queue::fake();

        $task = TaskDefinition::make('scheduled-chain-task')
            ->label('Scheduled Chain Task')
            ->command('app:scheduled-chain');

        $this->manager->register($task);

        $results = $this->action->execute('scheduled-chain-task', 'scheduled');

        $record = TaskRunRecord::query()->find($results[0]['run']->id);
        $this->assertSame('scheduled', $record->trigger_type);
    }

    /**
     * Test: throws for unregistered task.
     *
     * Validates: Requirement 6.8
     */
    public function test_throws_for_unregistered_task(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Task "non-existent" is not registered.');

        $this->action->execute('non-existent', 'manual');
    }
}
