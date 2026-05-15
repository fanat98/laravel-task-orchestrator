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
class NotificationRecipientResolutionPropertyTest extends TestCase
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
     * Property 3: When per-task notifications is enabled with a non-empty recipients array,
     * the resolved payload contains ONLY the per-task recipients (not global).
     *
     * **Validates: Requirements 2.3**
     */
    public function test_non_empty_per_task_recipients_are_used_exclusively(): void
    {
        $perTaskEmails = Set::sequence(Set::email())->between(1, 5);
        $globalEmails = Set::sequence(Set::email())->between(1, 5);

        self::forAll($perTaskEmails, $globalEmails)
            ->take(100)
            ->then(function (array $perTaskRecipients, array $globalRecipients): void {
                config()->set('task-orchestrator.notifications.enabled', true);
                config()->set('task-orchestrator.notifications.recipients', $globalRecipients);

                $definition = TaskDefinition::make('test-task')
                    ->command('app:test')
                    ->notifications([
                        'enabled' => true,
                        'recipients' => $perTaskRecipients,
                    ]);

                $result = $this->resolver->resolve($definition);

                $this->assertNotNull(
                    $result,
                    'Expected a NotificationPayload when per-task enabled=true with non-empty recipients',
                );

                // The resolved recipients must be exactly the per-task recipients
                $this->assertEqualsCanonicalizing(
                    array_values(array_unique($perTaskRecipients)),
                    array_values(array_unique($result->recipients)),
                    sprintf(
                        'Expected resolved recipients to contain only per-task recipients [%s], but got [%s]',
                        implode(', ', $perTaskRecipients),
                        implode(', ', $result->recipients),
                    ),
                );

                // None of the global recipients should appear (unless they also happen to be in per-task)
                foreach ($result->recipients as $recipient) {
                    $this->assertContains(
                        $recipient,
                        $perTaskRecipients,
                        sprintf(
                            'Resolved recipient "%s" is not in the per-task list — global recipients should not leak through',
                            $recipient,
                        ),
                    );
                }
            });
    }

    /**
     * Property 3: When per-task notifications is enabled with an empty recipients array,
     * the resolved payload falls back to global recipients.
     *
     * **Validates: Requirements 2.4, 6.4**
     */
    public function test_empty_per_task_recipients_falls_back_to_global(): void
    {
        $globalEmails = Set::sequence(Set::email())->between(1, 5);

        self::forAll($globalEmails)
            ->take(100)
            ->then(function (array $globalRecipients): void {
                config()->set('task-orchestrator.notifications.enabled', true);
                config()->set('task-orchestrator.notifications.recipients', $globalRecipients);

                $definition = TaskDefinition::make('test-task')
                    ->command('app:test')
                    ->notifications([
                        'enabled' => true,
                        'recipients' => [],
                    ]);

                $result = $this->resolver->resolve($definition);

                $this->assertNotNull(
                    $result,
                    'Expected a NotificationPayload when per-task enabled=true with empty recipients and global recipients available',
                );

                // The resolved recipients must be the global recipients
                $this->assertEqualsCanonicalizing(
                    array_values(array_unique($globalRecipients)),
                    array_values(array_unique($result->recipients)),
                    sprintf(
                        'Expected resolved recipients to fall back to global [%s], but got [%s]',
                        implode(', ', $globalRecipients),
                        implode(', ', $result->recipients),
                    ),
                );
            });
    }

    /**
     * Property 3: When per-task notifications is enabled but the recipients key is not defined
     * (null), the resolved payload falls back to global recipients.
     *
     * **Validates: Requirements 2.4, 6.4**
     */
    public function test_null_per_task_recipients_falls_back_to_global(): void
    {
        $globalEmails = Set::sequence(Set::email())->between(1, 5);

        self::forAll($globalEmails)
            ->take(100)
            ->then(function (array $globalRecipients): void {
                config()->set('task-orchestrator.notifications.enabled', true);
                config()->set('task-orchestrator.notifications.recipients', $globalRecipients);

                // Per-task config with enabled=true but no recipients key at all
                $definition = TaskDefinition::make('test-task')
                    ->command('app:test')
                    ->notifications([
                        'enabled' => true,
                    ]);

                $result = $this->resolver->resolve($definition);

                $this->assertNotNull(
                    $result,
                    'Expected a NotificationPayload when per-task enabled=true without recipients key and global recipients available',
                );

                // The resolved recipients must be the global recipients
                $this->assertEqualsCanonicalizing(
                    array_values(array_unique($globalRecipients)),
                    array_values(array_unique($result->recipients)),
                    sprintf(
                        'Expected resolved recipients to fall back to global [%s], but got [%s]',
                        implode(', ', $globalRecipients),
                        implode(', ', $result->recipients),
                    ),
                );
            });
    }

    /**
     * Property 3: Full combination — for any per-task recipients state (non-empty, empty, or absent)
     * and any global recipients, the precedence rule holds: per-task recipients are used when
     * defined and non-empty; global recipients are used otherwise.
     *
     * **Validates: Requirements 2.3, 2.4, 6.4**
     */
    public function test_full_recipient_precedence_combination(): void
    {
        $perTaskEmails = Set::sequence(Set::email())->between(1, 5);
        $globalEmails = Set::sequence(Set::email())->between(1, 5);
        // Represents the per-task recipients state: 'non-empty', 'empty', 'absent'
        $recipientState = Set::of('non-empty', 'empty', 'absent');

        self::forAll($recipientState, $perTaskEmails, $globalEmails)
            ->take(100)
            ->then(function (string $state, array $perTaskRecipients, array $globalRecipients): void {
                config()->set('task-orchestrator.notifications.enabled', true);
                config()->set('task-orchestrator.notifications.recipients', $globalRecipients);

                // Build per-task notifications config based on state
                $notifications = match ($state) {
                    'non-empty' => ['enabled' => true, 'recipients' => $perTaskRecipients],
                    'empty' => ['enabled' => true, 'recipients' => []],
                    'absent' => ['enabled' => true],
                };

                $definition = TaskDefinition::make('test-task')
                    ->command('app:test')
                    ->notifications($notifications);

                $result = $this->resolver->resolve($definition);

                $this->assertNotNull(
                    $result,
                    sprintf(
                        'Expected a NotificationPayload when per-task enabled=true (state=%s) with global recipients available',
                        $state,
                    ),
                );

                // Determine expected recipients based on precedence rule
                $expectedRecipients = match ($state) {
                    'non-empty' => $perTaskRecipients,
                    'empty', 'absent' => $globalRecipients,
                };

                $this->assertEqualsCanonicalizing(
                    array_values(array_unique($expectedRecipients)),
                    array_values(array_unique($result->recipients)),
                    sprintf(
                        'Recipient precedence failed for state=%s. Expected [%s], got [%s]',
                        $state,
                        implode(', ', $expectedRecipients),
                        implode(', ', $result->recipients),
                    ),
                );
            });
    }
}
