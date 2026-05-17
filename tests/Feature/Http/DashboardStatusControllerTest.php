<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Feature\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Malsa\TaskOrchestrator\Domain\TaskDefinition;
use Malsa\TaskOrchestrator\Support\TaskOrchestratorManager;
use Malsa\TaskOrchestrator\Tests\TestCase;

/**
 * Feature tests for DashboardStatusController.
 *
 * Validates: Requirement 9.6
 */
class DashboardStatusControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('task-orchestrator.authorization.enabled', false);
        Cache::flush();
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('task-orchestrator.middleware', ['web']);
    }

    /**
     * Test: GET returns JSON with health status information.
     *
     * Validates: Requirement 9.6
     */
    public function test_get_returns_json_with_health_status(): void
    {
        $manager = app(TaskOrchestratorManager::class);
        $manager->register(
            TaskDefinition::make('import-users')
                ->label('Import Users')
                ->command('app:import-users')
        );

        // Set scheduler heartbeat so health inspector can report
        Cache::put('task-orchestrator:scheduler-heartbeat', now()->toIso8601String(), 3600);
        Cache::put('task_orchestrator.queue_worker_heartbeat', now()->toIso8601String(), 3600);

        $response = $this->getJson(route('task-orchestrator.api.dashboard'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'summary' => [
                'registered_tasks',
                'total_runs',
                'running_runs',
                'failed_runs',
            ],
            'health' => [
                'status',
                'queue',
                'scheduler',
                'queue_worker',
                'message',
            ],
            'latest_runs',
            'latest_failed_runs',
            'task_groups',
        ]);

        $response->assertJsonPath('summary.registered_tasks', 1);
        $response->assertJsonPath('health.status', 'healthy');
    }
}
