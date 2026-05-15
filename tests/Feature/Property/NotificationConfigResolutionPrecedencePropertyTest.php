<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Feature\Property;

use Innmind\BlackBox\PHPUnit\BlackBox;
use Innmind\BlackBox\Set;
use Malsa\TaskOrchestrator\Domain\TaskDefinition;
use Malsa\TaskOrchestrator\Support\NotificationConfigResolver;
use Psr\Log\LoggerInterface;
use Malsa\TaskOrchestrator\Tests\TestCase;

/**
 * Feature: task-mail-notifications, Property 2: Effective enabled resolution precedence
 *
 * Validates: Requirements 6.2
 *
 * For any combination of per-task notification config (enabled: true, enabled: false, or null/absent)
 * and global notification config (enabled: true or enabled: false), the resolved effective enabled
 * state shall equal the per-task value when explicitly set, or the global value when per-task is null.
 */
class NotificationConfigResolutionPrecedencePropertyTest extends TestCase
{
    use BlackBox;

    private LoggerInterface $logger;

    private NotificationConfigResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createStub(LoggerInterface::class);
        $this->resolver = new NotificationConfigResolver($this->logger);
    }

    /**
     * Property 2: When per-task enabled is explicitly true, the resolved state is enabled
     * regardless of the global enabled setting.
     *
     * **Validates: Requirements 6.2**
     */
    public function test_per_task_enabled_true_overrides_global(): void
    {
        $globalEnabled = Set::of(true, false);

        self::forAll($globalEnabled)
            ->take(100)
            ->then(function (bool $globalEnabledValue): void {
                config()->set('task-orchestrator.notifications.enabled', $globalEnabledValue);
                config()->set('task-orchestrator.notifications.recipients', ['global@example.com']);

                $definition = TaskDefinition::make('test-task')
                    ->command('app:test')
                    ->notifications([
                        'enabled' => true,
                        'recipients' => ['pertask@example.com'],
                    ]);

                $result = $this->resolver->resolve($definition);

                // Per-task enabled=true should always produce a payload (notifications enabled)
                $this->assertNotNull(
                    $result,
                    sprintf(
                        'Expected notifications enabled when per-task enabled=true, but got null (global enabled=%s)',
                        $globalEnabledValue ? 'true' : 'false',
                    ),
                );
            });
    }

    /**
     * Property 2: When per-task enabled is explicitly false, the resolved state is disabled
     * regardless of the global enabled setting.
     *
     * **Validates: Requirements 6.2**
     */
    public function test_per_task_enabled_false_overrides_global(): void
    {
        $globalEnabled = Set::of(true, false);

        self::forAll($globalEnabled)
            ->take(100)
            ->then(function (bool $globalEnabledValue): void {
                config()->set('task-orchestrator.notifications.enabled', $globalEnabledValue);
                config()->set('task-orchestrator.notifications.recipients', ['global@example.com']);

                $definition = TaskDefinition::make('test-task')
                    ->command('app:test')
                    ->notifications([
                        'enabled' => false,
                        'recipients' => ['pertask@example.com'],
                    ]);

                $result = $this->resolver->resolve($definition);

                // Per-task enabled=false should always produce null (notifications disabled)
                $this->assertNull(
                    $result,
                    sprintf(
                        'Expected notifications disabled when per-task enabled=false, but got NotificationPayload (global enabled=%s)',
                        $globalEnabledValue ? 'true' : 'false',
                    ),
                );
            });
    }

    /**
     * Property 2: When per-task notifications is null (absent), the resolved effective
     * enabled state equals the global enabled value.
     *
     * **Validates: Requirements 6.2**
     */
    public function test_null_per_task_falls_back_to_global_enabled(): void
    {
        $globalEnabled = Set::of(true, false);

        self::forAll($globalEnabled)
            ->take(100)
            ->then(function (bool $globalEnabledValue): void {
                config()->set('task-orchestrator.notifications.enabled', $globalEnabledValue);
                config()->set('task-orchestrator.notifications.recipients', ['global@example.com']);

                $definition = TaskDefinition::make('test-task')
                    ->command('app:test')
                    ->notifications(null);

                $result = $this->resolver->resolve($definition);

                if ($globalEnabledValue) {
                    // Global enabled=true with valid recipients → should produce a payload
                    $this->assertNotNull(
                        $result,
                        'Expected notifications enabled when per-task is null and global enabled=true',
                    );
                } else {
                    // Global enabled=false → should produce null
                    $this->assertNull(
                        $result,
                        'Expected notifications disabled when per-task is null and global enabled=false',
                    );
                }
            });
    }

    /**
     * Property 2: Full combination test — for any combination of per-task enabled state
     * (true, false, or null) and global enabled state (true or false), the precedence
     * rule holds: per-task takes priority when set, global is used when per-task is null.
     *
     * **Validates: Requirements 6.2**
     */
    public function test_full_precedence_combination(): void
    {
        // Per-task enabled: true, false, or null (represented as absent notifications)
        $perTaskEnabled = Set::of(true, false, null);
        $globalEnabled = Set::of(true, false);

        self::forAll($perTaskEnabled, $globalEnabled)
            ->take(100)
            ->then(function (?bool $perTaskEnabledValue, bool $globalEnabledValue): void {
                config()->set('task-orchestrator.notifications.enabled', $globalEnabledValue);
                config()->set('task-orchestrator.notifications.recipients', ['global@example.com']);

                // Build the task definition based on per-task enabled value
                $notifications = $perTaskEnabledValue !== null
                    ? ['enabled' => $perTaskEnabledValue, 'recipients' => ['pertask@example.com']]
                    : null;

                $definition = TaskDefinition::make('test-task')
                    ->command('app:test')
                    ->notifications($notifications);

                $result = $this->resolver->resolve($definition);

                // Determine expected effective enabled state
                $expectedEnabled = $perTaskEnabledValue ?? $globalEnabledValue;

                if ($expectedEnabled) {
                    $this->assertNotNull(
                        $result,
                        sprintf(
                            'Expected notifications enabled (per-task=%s, global=%s)',
                            $perTaskEnabledValue === null ? 'null' : ($perTaskEnabledValue ? 'true' : 'false'),
                            $globalEnabledValue ? 'true' : 'false',
                        ),
                    );
                } else {
                    $this->assertNull(
                        $result,
                        sprintf(
                            'Expected notifications disabled (per-task=%s, global=%s)',
                            $perTaskEnabledValue === null ? 'null' : ($perTaskEnabledValue ? 'true' : 'false'),
                            $globalEnabledValue ? 'true' : 'false',
                        ),
                    );
                }
            });
    }
}
