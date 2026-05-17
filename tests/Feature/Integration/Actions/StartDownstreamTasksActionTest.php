<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Feature\Integration\Actions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Malsa\TaskOrchestrator\Actions\StartDownstreamTasksAction;
use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Domain\TaskDefinition;
use Malsa\TaskOrchestrator\Jobs\ExecuteTaskRunJob;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Malsa\TaskOrchestrator\Support\TaskOrchestratorManager;
use Malsa\TaskOrchestrator\Tests\TestCase;

/**
 * Integration tests for StartDownstreamTasksAction.
 *
 * Validates: Requirements 6.9, 6.10
 */
class StartDownstreamTasksActionTest extends TestCase
{
    use RefreshDatabase;

    private TaskOrchestratorManager $manager;
    private StartDownstreamTasksAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = app(TaskOrchestratorManager::class);
        $this->action = app(StartDownstreamTasksAction::class);
    }

    /**
     * Test: starts dependent tasks when all dependencies have succeeded in the pipeline.
     *
     * Given: Task B depends on Task A. Task A has a succeeded run in the pipeline.
     * When: StartDownstreamTasksAction is executed for Task A with the pipeline_id.
     * Then: Task B is started (a new queued run is created).
     *
     * Validates: Requirement 6.9
     */
    public function test_starts_dependents_when_all_deps_succeeded(): void
    {
        Queue::fake();

        $pipelineId = 'pipeline-downstream-1';

        // Register tasks: B depends on A
        $taskA = TaskDefinition::make('task-a')
            ->label('Task A')
            ->command('app:task-a');

        $taskB = TaskDefinition::make('task-b')
            ->label('Task B')
            ->command('app:task-b')
            ->dependsOn(['task-a']);

        $this->manager->register($taskA);
        $this->manager->register($taskB);

        // Create a succeeded run for Task A in the pipeline
        TaskRunRecord::query()->create([
            'id' => 'run-a-1',
            'task_name' => 'task-a',
            'task_label' => 'Task A',
            'command' => 'app:task-a',
            'status' => TaskRunStatus::Succeeded->value,
            'trigger_type' => 'pipeline',
            'pipeline_id' => $pipelineId,
        ]);

        // Execute downstream action for task-a
        $this->action->execute('task-a', $pipelineId);

        // Task B should have been started (a new queued run created)
        $taskBRun = TaskRunRecord::query()
            ->where('task_name', 'task-b')
            ->where('pipeline_id', $pipelineId)
            ->first();

        $this->assertNotNull($taskBRun);
        $this->assertSame(TaskRunStatus::Queued->value, $taskBRun->status);
        $this->assertSame('pipeline', $taskBRun->trigger_type);
        $this->assertSame($pipelineId, $taskBRun->pipeline_id);

        // Verify job was dispatched for Task B
        Queue::assertPushed(ExecuteTaskRunJob::class, 1);
    }

    /**
     * Test: does not start dependent tasks when dependencies have not all succeeded.
     *
     * Given: Task C depends on Task A and Task B. Only Task A has succeeded.
     * When: StartDownstreamTasksAction is executed for Task A with the pipeline_id.
     * Then: Task C is NOT started because Task B has not succeeded yet.
     *
     * Validates: Requirement 6.10
     */
    public function test_does_not_start_when_deps_not_all_succeeded(): void
    {
        Queue::fake();

        $pipelineId = 'pipeline-downstream-2';

        // Register tasks: C depends on both A and B
        $taskA = TaskDefinition::make('task-a')
            ->label('Task A')
            ->command('app:task-a');

        $taskB = TaskDefinition::make('task-b')
            ->label('Task B')
            ->command('app:task-b');

        $taskC = TaskDefinition::make('task-c')
            ->label('Task C')
            ->command('app:task-c')
            ->dependsOn(['task-a', 'task-b']);

        $this->manager->register($taskA);
        $this->manager->register($taskB);
        $this->manager->register($taskC);

        // Only Task A has succeeded in the pipeline
        TaskRunRecord::query()->create([
            'id' => 'run-a-1',
            'task_name' => 'task-a',
            'task_label' => 'Task A',
            'command' => 'app:task-a',
            'status' => TaskRunStatus::Succeeded->value,
            'trigger_type' => 'pipeline',
            'pipeline_id' => $pipelineId,
        ]);

        // Task B is still running (not succeeded)
        TaskRunRecord::query()->create([
            'id' => 'run-b-1',
            'task_name' => 'task-b',
            'task_label' => 'Task B',
            'command' => 'app:task-b',
            'status' => TaskRunStatus::Running->value,
            'trigger_type' => 'pipeline',
            'pipeline_id' => $pipelineId,
        ]);

        // Execute downstream action for task-a
        $this->action->execute('task-a', $pipelineId);

        // Task C should NOT have been started
        $taskCRun = TaskRunRecord::query()
            ->where('task_name', 'task-c')
            ->where('pipeline_id', $pipelineId)
            ->first();

        $this->assertNull($taskCRun);

        // No jobs should have been dispatched
        Queue::assertNothingPushed();
    }
}
