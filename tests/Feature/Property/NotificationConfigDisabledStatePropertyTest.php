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
 * Feature: task-mail-notifications, Property 1: Disabled state produces no notifications
 *
 * Validates: Requirements 1.3, 2.6, 6.3, 7.2
 *
 * For any task run reaching a terminal status (Failed or Succeeded), if the effective enabled
 * state resolves to false (either globally disabled, or per-task explicitly disabled), then no
 * notification email shall be dispatched. In terms of the ConfigResolver, this means resolve()
 * returns null.
 */
class NotificationConfigDisabledStatePropertyTest extends TestCase
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
     * Property 1: When global notifications are disabled and no per-task config is set,
     * resolve() returns null for any task name and any global recipients list.
     *
     * **Validates: Requirements 1.3, 7.2**
     */
    public function test_globally_disabled_with_no_per_task_config_produces_null(): void
    {
        // Generate arbitrary task names and global recipient lists
        $taskNames = Set::strings()->atLeast(1);
        $recipientLists = Set::either(
            Set::of([]),
            Set::of(['admin@example.com']),
            Set::of(['admin@example.com', 'ops@example.com']),
        );

        self::forAll($taskNames, $recipientLists)
            ->take(100)
            ->then(function (string $taskName, array $recipients): void {
                config()->set('task-orchestrator.notifications.enabled', false);
                config()->set('task-orchestrator.notifications.recipients', $recipients);

                // Ensure task name is valid (non-empty)
                $safeName = trim($taskName) !== '' ? $taskName : 'fallback-task';

                $definition = TaskDefinition::make($safeName)
                    ->command('app:test')
                    ->notifications(null);

                $result = $this->resolver->resolve($definition);

                $this->assertNull(
                    $result,
                    sprintf(
                        'Expected null (no notification) when global enabled=false and per-task is null, '
                        . 'but got NotificationPayload for task "%s" with %d global recipients',
                        $safeName,
                        count($recipients),
                    ),
                );
            });
    }

    /**
     * Property 1: When per-task notifications are explicitly disabled, resolve() returns null
     * regardless of the global enabled setting and regardless of configured recipients.
     *
     * **Validates: Requirements 2.6, 6.3**
     */
    public function test_per_task_disabled_produces_null_regardless_of_global(): void
    {
        $globalEnabled = Set::of(true, false);
        $globalRecipients = Set::either(
            Set::of([]),
            Set::of(['global@example.com']),
            Set::of(['global@example.com', 'ops@example.com']),
        );
        $perTaskRecipients = Set::either(
            Set::of([]),
            Set::of(['pertask@example.com']),
            Set::of(['pertask@example.com', 'team@example.com']),
        );

        self::forAll($globalEnabled, $globalRecipients, $perTaskRecipients)
            ->take(100)
            ->then(function (bool $globalEnabledValue, array $globalRecips, array $perTaskRecips): void {
                config()->set('task-orchestrator.notifications.enabled', $globalEnabledValue);
                config()->set('task-orchestrator.notifications.recipients', $globalRecips);

                $definition = TaskDefinition::make('test-task')
                    ->command('app:test')
                    ->notifications([
                        'enabled' => false,
                        'recipients' => $perTaskRecips,
                    ]);

                $result = $this->resolver->resolve($definition);

                $this->assertNull(
                    $result,
                    sprintf(
                        'Expected null (no notification) when per-task enabled=false, '
                        . 'but got NotificationPayload (global enabled=%s, global recipients=%d, per-task recipients=%d)',
                        $globalEnabledValue ? 'true' : 'false',
                        count($globalRecips),
                        count($perTaskRecips),
                    ),
                );
            });
    }

    /**
     * Property 1: Combined disabled scenarios — for any combination where the effective
     * enabled state is false (global disabled with null per-task, or per-task explicitly
     * disabled), resolve() always returns null.
     *
     * **Validates: Requirements 1.3, 2.6, 6.3, 7.2**
     */
    public function test_any_disabled_state_produces_null(): void
    {
        // Generate disabled scenarios:
        // Scenario A: global disabled, per-task null
        // Scenario B: per-task explicitly disabled (global can be anything)
        $globalEnabled = Set::of(true, false);
        $disabledScenario = Set::of('global_disabled', 'per_task_disabled');
        $recipientEmails = Set::either(
            Set::of([]),
            Set::of(['user@example.com']),
            Set::of(['a@example.com', 'b@example.com', 'c@example.com']),
        );

        self::forAll($disabledScenario, $globalEnabled, $recipientEmails, $recipientEmails)
            ->take(100)
            ->then(function (
                string $scenario,
                bool $globalEnabledValue,
                array $globalRecips,
                array $perTaskRecips,
            ): void {
                if ($scenario === 'global_disabled') {
                    // Scenario A: global disabled, no per-task config
                    config()->set('task-orchestrator.notifications.enabled', false);
                    config()->set('task-orchestrator.notifications.recipients', $globalRecips);

                    $definition = TaskDefinition::make('test-task')
                        ->command('app:test')
                        ->notifications(null);
                } else {
                    // Scenario B: per-task explicitly disabled
                    config()->set('task-orchestrator.notifications.enabled', $globalEnabledValue);
                    config()->set('task-orchestrator.notifications.recipients', $globalRecips);

                    $definition = TaskDefinition::make('test-task')
                        ->command('app:test')
                        ->notifications([
                            'enabled' => false,
                            'recipients' => $perTaskRecips,
                        ]);
                }

                $result = $this->resolver->resolve($definition);

                $this->assertNull(
                    $result,
                    sprintf(
                        'Expected null (no notification) for disabled scenario "%s" '
                        . '(global enabled=%s, global recipients=%d, per-task recipients=%d)',
                        $scenario,
                        $globalEnabledValue ? 'true' : 'false',
                        count($globalRecips),
                        count($perTaskRecips),
                    ),
                );
            });
    }
}
