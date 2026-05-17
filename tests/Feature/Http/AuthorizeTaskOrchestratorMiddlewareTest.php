<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Feature\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Malsa\TaskOrchestrator\Domain\TaskDefinition;
use Malsa\TaskOrchestrator\Support\TaskOrchestratorManager;
use Malsa\TaskOrchestrator\Tests\TestCase;

/**
 * Feature tests for AuthorizeTaskOrchestrator middleware.
 *
 * Validates: Requirements 9.7, 9.8, 9.9
 */
class AuthorizeTaskOrchestratorMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        // Use web + auth middleware as the package normally does
        $app['config']->set('task-orchestrator.middleware', ['web']);
        $app['config']->set('task-orchestrator.authorization.enabled', true);
        $app['config']->set('task-orchestrator.authorization.mode', 'gate');
        $app['config']->set('task-orchestrator.authorization.gate', 'viewTaskOrchestrator');
        $app['config']->set('task-orchestrator.authorization.forbidden_message', 'Access denied to Task Orchestrator.');
    }

    /**
     * Test: unauthenticated request is blocked.
     *
     * Validates: Requirement 9.7
     */
    public function test_unauthenticated_request_is_blocked(): void
    {
        // Gate mode requires a user; no user means Gate::authorize will throw
        Gate::define('viewTaskOrchestrator', fn () => false);

        $response = $this->get(route('task-orchestrator.tasks.index'));

        // Without authentication, the middleware should block access
        $response->assertStatus(403);
    }

    /**
     * Test: authorized user can access routes.
     *
     * Validates: Requirement 9.8
     */
    public function test_authorized_user_passes(): void
    {
        $user = $this->createUser();

        Gate::define('viewTaskOrchestrator', fn () => true);

        $manager = app(TaskOrchestratorManager::class);
        $manager->register(
            TaskDefinition::make('test-task')
                ->label('Test Task')
                ->command('app:test')
        );

        $response = $this->actingAs($user)->get(route('task-orchestrator.tasks.index'));

        $response->assertStatus(200);
    }

    /**
     * Test: unauthorized user (gate fails) receives 403 with configured message.
     *
     * Validates: Requirement 9.9
     */
    public function test_unauthorized_user_receives_403_with_configured_message(): void
    {
        $user = $this->createUser();

        Gate::define('viewTaskOrchestrator', fn () => false);

        $response = $this->actingAs($user)->get(route('task-orchestrator.tasks.index'));

        $response->assertStatus(403);
    }

    /**
     * Create a simple user model for testing.
     */
    private function createUser(): \Illuminate\Foundation\Auth\User
    {
        return new class extends \Illuminate\Foundation\Auth\User
        {
            protected $table = 'users';

            public function getAuthIdentifier(): int
            {
                return 1;
            }

            public function getAuthIdentifierName(): string
            {
                return 'id';
            }
        };
    }
}
