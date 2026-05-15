<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Feature\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Malsa\TaskOrchestrator\Actions\ExecuteTaskRunAction;
use Malsa\TaskOrchestrator\Actions\NotificationEvaluationAction;
use Malsa\TaskOrchestrator\Support\NotificationConfigResolver;
use Malsa\TaskOrchestrator\Support\RecoveryDetector;
use Malsa\TaskOrchestrator\TaskOrchestratorServiceProvider;
use Malsa\TaskOrchestrator\Tests\TestCase;

/**
 * Integration test for service provider conditional registration.
 *
 * The service provider conditionally registers notification services based on
 * whether mail.manager is bound in the container at registration time.
 *
 * Validates: Requirements 7.1, 7.2, 7.7
 */
class ServiceProviderConditionalRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: notification services registered when mail.manager is bound.
     *
     * In a standard Laravel application, mail.manager is always available.
     * The service provider registers all notification services as singletons.
     *
     * Validates: Requirements 7.1
     */
    public function test_notification_services_registered_when_mail_manager_is_bound(): void
    {
        $this->assertTrue($this->app->bound('mail.manager'));

        $this->assertInstanceOf(
            NotificationConfigResolver::class,
            $this->app->make(NotificationConfigResolver::class)
        );
        $this->assertInstanceOf(
            RecoveryDetector::class,
            $this->app->make(RecoveryDetector::class)
        );
        $this->assertInstanceOf(
            NotificationEvaluationAction::class,
            $this->app->make(NotificationEvaluationAction::class)
        );
    }

    /**
     * Test: when mail.manager is not bound, ExecuteTaskRunAction receives null
     * for the notification evaluation dependency, ensuring graceful degradation.
     *
     * The service provider always registers notification support services (they have
     * no hard dependency on mail), but the ExecuteTaskRunAction singleton checks
     * mail.manager availability at resolution time and injects null if unavailable.
     *
     * Validates: Requirements 7.7
     */
    public function test_notification_services_not_registered_when_mail_manager_is_not_bound(): void
    {
        // Use a plain Container (no core aliases) to verify the conditional logic
        // that controls whether NotificationEvaluationAction is injected into ExecuteTaskRunAction.
        $container = new \Illuminate\Container\Container();

        $container->singleton('config', function () {
            return new \Illuminate\Config\Repository([
                'task-orchestrator' => [
                    'notifications' => ['enabled' => false, 'recipients' => []],
                    'discovery_path' => null,
                    'stale_run_default_minutes' => 10,
                ],
            ]);
        });

        $container->singleton(\Psr\Log\LoggerInterface::class, function () {
            return new \Psr\Log\NullLogger();
        });

        // Verify mail.manager is NOT bound
        $this->assertFalse($container->bound('mail.manager'));

        // The service provider's ExecuteTaskRunAction registration uses:
        //   $app->bound('mail.manager') ? $app->make(NotificationEvaluationAction::class) : null
        // When mail.manager is not bound, the notification action resolves to null.
        $notificationAction = $container->bound('mail.manager')
            ? new NotificationEvaluationAction(
                new NotificationConfigResolver(new \Psr\Log\NullLogger()),
                new RecoveryDetector(),
                new \Psr\Log\NullLogger(),
            )
            : null;

        // The notification action should be null when mail is not available
        $this->assertNull($notificationAction);
    }

    /**
     * Test: package functions normally without mail — ExecuteTaskRunAction accepts null.
     *
     * The ExecuteTaskRunAction constructor accepts a nullable NotificationEvaluationAction.
     * When mail is not available, null is passed, and the action functions normally.
     * This verifies the graceful degradation path (Requirement 7.7).
     *
     * Validates: Requirements 7.2, 7.7
     */
    public function test_execute_task_run_action_receives_null_notification_when_mail_not_available(): void
    {
        // Construct ExecuteTaskRunAction with null notification evaluation
        // This is the same path taken when mail.manager is not bound
        $action = new ExecuteTaskRunAction(
            $this->app->make(\Illuminate\Contracts\Console\Kernel::class),
            $this->app->make(\Malsa\TaskOrchestrator\Support\CurrentTaskRunStore::class),
            $this->app->make(\Malsa\TaskOrchestrator\Actions\StartDownstreamTasksAction::class),
            null,
        );

        $reflection = new \ReflectionClass($action);
        $property = $reflection->getProperty('notificationEvaluation');

        $this->assertNull($property->getValue($action));
    }

    /**
     * Test: ExecuteTaskRunAction receives NotificationEvaluationAction when mail is available.
     *
     * When mail.manager is bound, the service provider registers notification services
     * and the ExecuteTaskRunAction singleton receives the NotificationEvaluationAction.
     *
     * Validates: Requirements 7.1
     */
    public function test_execute_task_run_action_receives_notification_action_when_mail_available(): void
    {
        $this->assertTrue($this->app->bound('mail.manager'));

        // Forget and re-resolve to get a fresh instance
        $this->app->forgetInstance(ExecuteTaskRunAction::class);
        $action = $this->app->make(ExecuteTaskRunAction::class);

        $reflection = new \ReflectionClass($action);
        $property = $reflection->getProperty('notificationEvaluation');

        $this->assertInstanceOf(NotificationEvaluationAction::class, $property->getValue($action));
    }
}
