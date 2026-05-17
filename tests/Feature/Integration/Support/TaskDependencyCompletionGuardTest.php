<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Feature\Integration\Support;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Domain\TaskDefinition;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Malsa\TaskOrchestrator\Support\TaskDependencyCompletionGuard;
use Malsa\TaskOrchestrator\Tests\TestCase;

/**
 * Integration tests for TaskDependencyCompletionGuard.
 *
 * Validates: Requirements 7.7, 7.8, 7.9, 7.10
 */
class TaskDependencyCompletionGuardTest extends TestCase
{
    use RefreshDatabase;

    private TaskDependencyCompletionGuard $guard;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guard = new TaskDependencyCompletionGuard();
    }

    /**
     * Test: returns true when all dependencies have succeeded in the pipeline.
     *
     * Validates: Requirement 7.7
     */
    public function test_returns_true_when_all_dependencies_succeeded(): void
    {
        $pipelineId = 'pipeline-completion-1';

        $task = TaskDefinition::make('task-c')
            ->label('Task C')
            ->command('app:task-c')
            ->dependsOn(['task-a', 'task-b']);

        // Create succeeded runs for both dependencies in the pipeline
        TaskRunRecord::query()->create([
            'id' => 'run-a-1',
            'task_name' => 'task-a',
            'task_label' => 'Task A',
            'command' => 'app:task-a',
            'status' => TaskRunStatus::Succeeded->value,
            'trigger_type' => 'pipeline',
            'pipeline_id' => $pipelineId,
        ]);

        TaskRunRecord::query()->create([
            'id' => 'run-b-1',
            'task_name' => 'task-b',
            'task_label' => 'Task B',
            'command' => 'app:task-b',
            'status' => TaskRunStatus::Succeeded->value,
            'trigger_type' => 'pipeline',
            'pipeline_id' => $pipelineId,
        ]);

        $result = $this->guard->allDependenciesSucceeded($task, $pipelineId);

        $this->assertTrue($result);
    }

    /**
     * Test: returns false when a dependency has not succeeded.
     *
     * Validates: Requirement 7.8
     */
    public function test_returns_false_when_dependency_not_succeeded(): void
    {
        $pipelineId = 'pipeline-completion-2';

        $task = TaskDefinition::make('task-c')
            ->label('Task C')
            ->command('app:task-c')
            ->dependsOn(['task-a', 'task-b']);

        // Task A succeeded
        TaskRunRecord::query()->create([
            'id' => 'run-a-2',
            'task_name' => 'task-a',
            'task_label' => 'Task A',
            'command' => 'app:task-a',
            'status' => TaskRunStatus::Succeeded->value,
            'trigger_type' => 'pipeline',
            'pipeline_id' => $pipelineId,
        ]);

        // Task B is still running (not succeeded)
        TaskRunRecord::query()->create([
            'id' => 'run-b-2',
            'task_name' => 'task-b',
            'task_label' => 'Task B',
            'command' => 'app:task-b',
            'status' => TaskRunStatus::Running->value,
            'trigger_type' => 'pipeline',
            'pipeline_id' => $pipelineId,
        ]);

        $result = $this->guard->allDependenciesSucceeded($task, $pipelineId);

        $this->assertFalse($result);
    }

    /**
     * Test: returns true when task has no dependencies.
     *
     * Validates: Requirement 7.9
     */
    public function test_returns_true_when_no_dependencies(): void
    {
        $task = TaskDefinition::make('independent-task')
            ->label('Independent Task')
            ->command('app:independent');

        $result = $this->guard->allDependenciesSucceeded($task, 'any-pipeline-id');

        $this->assertTrue($result);
    }

    /**
     * Test: returns false when pipeline_id is null and task has dependencies.
     *
     * Validates: Requirement 7.10
     */
    public function test_returns_false_when_pipeline_id_null_with_dependencies(): void
    {
        $task = TaskDefinition::make('task-with-deps')
            ->label('Task With Deps')
            ->command('app:task-with-deps')
            ->dependsOn(['task-a']);

        $result = $this->guard->allDependenciesSucceeded($task, null);

        $this->assertFalse($result);
    }
}
