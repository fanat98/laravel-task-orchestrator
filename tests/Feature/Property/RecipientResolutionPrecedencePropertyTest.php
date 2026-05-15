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
 * Feature: task-mail-notifications, Property 3: Recipient resolution precedence
 *
 * Validates: Requirements 2.3, 2.4, 6.4
 *
 * For any task with notifications enabled, if the per-task recipients array is defined and non-empty,
 * the resolved recipient list shall contain only the per-task recipients; if the per-task recipients
 * array is empty, null, or not defined, the resolved recipient list shall contain the global recipients.
 */
class RecipientResolutionPrecedencePropertyTest extends TestCase
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
     * Property 3: When per-task enabled=true and per-task recipients is non-empty,
     * the resolved payload contains ONLY the per-task recipients (not global ones).
     *
     * **Validates: Requirements 2.3**
     */
    public function test_per_task_non_empty_recipients_are_used_exclusively(): void
    {
        $perTaskRecipients = Set::of(
            ['a@example.com'],
            ['b@example.com'],
            ['c@example.com', 'd@example.com'],
            ['a@example.com', 'b@example.com', 'c@example.com'],
        );

        $globalRecipients = Set::of(
            ['global1@example.com'],
            ['global2@example.com'],
            ['global1@example.com', 'global2@example.com', 'global3@example.com'],
        );

        self::forAll($perTaskRecipients, $globalRecipients)
            ->take(100)
            ->then(function (array $perTask, array $global): void {
                config()->set('task-orchestrator.notifications.enabled', true);
                config()->set('task-orchestrator.notifications.recipients', $global);

                $definition = TaskDefinition::make('test-task')
                    ->command('app:test')
                    ->notifications([
                        'enabled' => true,
                        'recipients' => $perTask,
                    ]);

                $result = $this->resolver->resolve($definition);

                $this->assertNotNull(
                    $result,
                    'Expected a NotificationPayload when per-task enabled=true with non-empty recipients',
                );

                // Resolved recipients must be exactly the per-task recipients
                $this->assertSame(
                    $perTask,
                    $result->recipients,
                    sprintf(
                        'Expected per-task recipients %s but got %s (global was %s)',
                        json_encode($perTask),
                        json_encode($result->recipients),
                        json_encode($global),
                    ),
                );

                // Ensure no global recipients leaked into the result
                foreach ($global as $globalEmail) {
                    if (! in_array($globalEmail, $perTask, true)) {
                        $this->assertNotContains(
                            $globalEmail,
                            $result->recipients,
                            sprintf('Global recipient "%s" should not appear in resolved list', $globalEmail),
                        );
                    }
                }
            });
    }

    /**
     * Property 3: When per-task enabled=true and per-task recipients is an empty array,
     * the resolved payload contains the global recipients.
     *
     * **Validates: Requirements 2.4**
     */
    public function test_empty_per_task_recipients_falls_back_to_global(): void
    {
        $globalRecipients = Set::of(
            ['global1@example.com'],
            ['global2@example.com'],
            ['global1@example.com', 'global2@example.com'],
            ['global1@example.com', 'global2@example.com', 'global3@example.com'],
        );

        self::forAll($globalRecipients)
            ->take(100)
            ->then(function (array $global): void {
                config()->set('task-orchestrator.notifications.enabled', true);
                config()->set('task-orchestrator.notifications.recipients', $global);

                $definition = TaskDefinition::make('test-task')
                    ->command('app:test')
                    ->notifications([
                        'enabled' => true,
                        'recipients' => [],
                    ]);

                $result = $this->resolver->resolve($definition);

                $this->assertNotNull(
                    $result,
                    'Expected a NotificationPayload when falling back to global recipients',
                );

                $this->assertSame(
                    $global,
                    $result->recipients,
                    sprintf(
                        'Expected global recipients %s when per-task recipients is empty, but got %s',
                        json_encode($global),
                        json_encode($result->recipients),
                    ),
                );
            });
    }

    /**
     * Property 3: When per-task enabled=true and per-task notifications config has no
     * 'recipients' key at all, the resolved payload contains the global recipients.
     *
     * **Validates: Requirements 2.4, 6.4**
     */
    public function test_null_per_task_recipients_falls_back_to_global(): void
    {
        $globalRecipients = Set::of(
            ['global1@example.com'],
            ['global2@example.com'],
            ['global1@example.com', 'global2@example.com'],
            ['global1@example.com', 'global2@example.com', 'global3@example.com'],
        );

        self::forAll($globalRecipients)
            ->take(100)
            ->then(function (array $global): void {
                config()->set('task-orchestrator.notifications.enabled', true);
                config()->set('task-orchestrator.notifications.recipients', $global);

                // Per-task config with enabled=true but NO recipients key
                $definition = TaskDefinition::make('test-task')
                    ->command('app:test')
                    ->notifications([
                        'enabled' => true,
                    ]);

                $result = $this->resolver->resolve($definition);

                $this->assertNotNull(
                    $result,
                    'Expected a NotificationPayload when falling back to global recipients (no recipients key)',
                );

                $this->assertSame(
                    $global,
                    $result->recipients,
                    sprintf(
                        'Expected global recipients %s when per-task recipients key is absent, but got %s',
                        json_encode($global),
                        json_encode($result->recipients),
                    ),
                );
            });
    }

    /**
     * Property 3: Full combination test — generate per-task recipients as either a non-empty
     * array of valid emails, an empty array, or absent (no key), combined with global recipients.
     * Assert the precedence rule holds in all cases.
     *
     * **Validates: Requirements 2.3, 2.4, 6.4**
     */
    public function test_full_recipient_precedence_combination(): void
    {
        // Per-task recipient states: non-empty array, empty array, or absent (null means no key)
        $perTaskRecipientsState = Set::of(
            ['a@example.com'],
            ['b@example.com', 'c@example.com'],
            ['a@example.com', 'b@example.com', 'c@example.com', 'd@example.com'],
            [],
            null,
        );

        $globalRecipients = Set::of(
            ['global1@example.com'],
            ['global2@example.com', 'global3@example.com'],
            ['global1@example.com', 'global2@example.com', 'global3@example.com'],
        );

        self::forAll($perTaskRecipientsState, $globalRecipients)
            ->take(100)
            ->then(function (?array $perTaskState, array $global): void {
                config()->set('task-orchestrator.notifications.enabled', true);
                config()->set('task-orchestrator.notifications.recipients', $global);

                // Build per-task notifications config based on state
                if ($perTaskState === null) {
                    // No recipients key at all
                    $notifications = ['enabled' => true];
                } else {
                    $notifications = ['enabled' => true, 'recipients' => $perTaskState];
                }

                $definition = TaskDefinition::make('test-task')
                    ->command('app:test')
                    ->notifications($notifications);

                $result = $this->resolver->resolve($definition);

                $this->assertNotNull(
                    $result,
                    'Expected a NotificationPayload when per-task enabled=true with valid global fallback',
                );

                // Determine expected recipients based on precedence rule
                $isPerTaskNonEmpty = is_array($perTaskState) && $perTaskState !== [];
                $expectedRecipients = $isPerTaskNonEmpty ? $perTaskState : $global;

                $this->assertSame(
                    $expectedRecipients,
                    $result->recipients,
                    sprintf(
                        'Precedence rule violated: per-task state=%s, expected recipients=%s, got=%s',
                        $perTaskState === null ? 'absent' : json_encode($perTaskState),
                        json_encode($expectedRecipients),
                        json_encode($result->recipients),
                    ),
                );
            });
    }
}
