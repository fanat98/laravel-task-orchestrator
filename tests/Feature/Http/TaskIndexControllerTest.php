<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Feature\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Malsa\TaskOrchestrator\Domain\TaskDefinition;
use Malsa\TaskOrchestrator\Support\TaskOrchestratorManager;
use Malsa\TaskOrchestrator\Tests\TestCase;

/**
 * Feature tests for TaskIndexController.
 *
 * Validates: Requirement 9.4
 */
class TaskIndexControllerTest extends TestCase
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
     * Test: GET returns list of registered tasks.
     *
     * Validates: Requirement 9.4
     */
    public function test_get_returns_list_of_registered_tasks(): void
    {
        $manager = app(TaskOrchestratorManager::class);

        $manager->register(
            TaskDefinition::make('import-users')
                ->label('Import Users')
                ->command('app:import-users')
        );

        $manager->register(
            TaskDefinition::make('sync-data')
                ->label('Sync Data')
                ->command('app:sync-data')
        );

        $response = $this->get(route('task-orchestrator.tasks.index'));

        $response->assertStatus(200);
        $response->assertViewIs('task-orchestrator::tasks.index');
        $response->assertViewHas('tasks');

        $tasks = $response->viewData('tasks');
        $this->assertCount(2, $tasks);

        $taskNames = $tasks->pluck('name')->toArray();
        $this->assertContains('import-users', $taskNames);
        $this->assertContains('sync-data', $taskNames);
    }
}
