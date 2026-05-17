<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Feature\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Domain\TaskDefinition;
use Malsa\TaskOrchestrator\Jobs\ExecuteTaskRunJob;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Malsa\TaskOrchestrator\Support\TaskOrchestratorManager;
use Malsa\TaskOrchestrator\Tests\TestCase;

/**
 * Feature tests for TaskRetryController.
 *
 * Validates: Requirement 9.3
 */
class TaskRetryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('task-orchestrator.authorization.enabled', false);
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('task-orchestrator.middleware', ['web']);
    }

    /**
     * Test: POST returns success and creates/returns a task run.
     *
     * Validates: Requirement 9.3
     */
    public function test_post_retries_task_and_redirects(): void
    {
        Queue::fake();

        $manager = app(TaskOrchestratorManager::class);
        $manager->register(
            TaskDefinition::make('sync-data')
                ->label('Sync Data')
                ->command('app:sync-data')
        );

        // Create a failed run to retry
        $failedRun = TaskRunRecord::query()->create([
            'id' => Str::uuid()->toString(),
            'task_name' => 'sync-data',
            'task_label' => 'Sync Data',
            'command' => 'app:sync-data',
            'status' => TaskRunStatus::Failed->value,
            'trigger_type' => 'manual',
        ]);

        $response = $this->post(route('task-orchestrator.runs.retry', ['taskRun' => $failedRun->id]));

        // Should redirect to the run show page
        $response->assertRedirect();

        // Verify a new task run was created with retry trigger
        $this->assertTrue(
            TaskRunRecord::query()
                ->where('task_name', 'sync-data')
                ->where('status', TaskRunStatus::Queued->value)
                ->where('trigger_type', 'retry')
                ->exists(),
            'Expected a new task_run_record with retry trigger to be created'
        );

        // Verify job was dispatched
        Queue::assertPushed(ExecuteTaskRunJob::class);
    }
}
