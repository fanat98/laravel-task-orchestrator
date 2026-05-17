<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Feature\Http;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Domain\TaskDefinition;
use Malsa\TaskOrchestrator\Jobs\ExecuteTaskRunJob;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Malsa\TaskOrchestrator\Support\TaskOrchestratorManager;
use Malsa\TaskOrchestrator\Tests\TestCase;

/**
 * Feature tests for TaskStartController.
 *
 * Validates: Requirements 9.1, 9.2
 */
class TaskStartControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        // Disable authorization so HTTP tests can reach the controller
        $app['config']->set('task-orchestrator.authorization.enabled', false);
    }

    protected function defineDatabaseMigrations(): void
    {
        // Must explicitly load migrations since defineEnvironment is overridden
        $this->loadMigrationsFrom(__DIR__.'/../../../database/migrations');
    }

    /**
     * Test: POST creates task run for valid registered task.
     *
     * Validates: Requirement 9.1
     */
    public function test_post_creates_task_run_for_valid_task(): void
    {
        Queue::fake();

        $manager = app(TaskOrchestratorManager::class);
        $manager->register(
            TaskDefinition::make('import-users')
                ->label('Import Users')
                ->command('app:import-users')
        );

        $user = new class extends Authenticatable {
            public $id = 1;
        };

        $response = $this->actingAs($user)
            ->post(route('task-orchestrator.tasks.run', ['task' => 'import-users']));

        $response->assertRedirect();

        $this->assertTrue(
            TaskRunRecord::query()
                ->where('task_name', 'import-users')
                ->where('status', TaskRunStatus::Queued->value)
                ->where('trigger_type', 'manual')
                ->exists(),
            'Expected a task_run_record to be created with correct fields'
        );

        Queue::assertPushed(ExecuteTaskRunJob::class);
    }

    /**
     * Test: POST returns error for unregistered task.
     *
     * Validates: Requirement 9.2
     */
    public function test_post_returns_error_for_unregistered_task(): void
    {
        Queue::fake();

        $user = new class extends Authenticatable {
            public $id = 1;
        };

        $response = $this->actingAs($user)
            ->post(route('task-orchestrator.tasks.run', ['task' => 'non-existent-task']));

        $response->assertStatus(500);

        $this->assertSame(0, TaskRunRecord::query()->count());

        Queue::assertNothingPushed();
    }
}
