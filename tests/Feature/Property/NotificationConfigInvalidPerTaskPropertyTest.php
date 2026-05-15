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
 * Feature: task-mail-notifications, Property 11: Invalid per-task config rejection
 *
 * Validates: Requirements 2.8
 *
 * For any per-task notifications configuration that does not conform to the expected
 * structure (e.g., enabled is not a boolean, recipients is not an array), the notification
 * system shall not send notifications for that task and shall log a warning.
 */
class NotificationConfigInvalidPerTaskPropertyTest extends TestCase
{
    use BlackBox;

    private LoggerInterface $logger;

    private NotificationConfigResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->resolver = new NotificationConfigResolver($this->logger);

        // Enable global notifications so we can verify per-task rejection overrides it
        config()->set('task-orchestrator.notifications.enabled', true);
        config()->set('task-orchestrator.notifications.recipients', ['global@example.com']);
    }

    /**
     * Property 11: When "enabled" is not a boolean, resolver returns null and logs a warning.
     *
     * **Validates: Requirements 2.8**
     */
    public function test_enabled_not_boolean_rejects_config_and_logs_warning(): void
    {
        // Generate non-boolean values for the "enabled" field
        $nonBooleanValues = Set::either(
            Set::integers(),
            Set::strings()->between(1, 50),
            Set::of(null, 0, 1, 'true', 'false', '', '0', '1', 0.5, 2.7),
        );

        $this->logger
            ->expects($this->atLeastOnce())
            ->method('warning')
            ->with(
                $this->stringContains('invalid notification config'),
                $this->anything(),
            );

        self::forAll($nonBooleanValues)
            ->take(100)
            ->then(function (mixed $invalidEnabled): void {
                $definition = TaskDefinition::make('test-task')
                    ->command('app:test')
                    ->notifications([
                        'enabled' => $invalidEnabled,
                        'recipients' => ['valid@example.com'],
                    ]);

                $result = $this->resolver->resolve($definition);

                $this->assertNull(
                    $result,
                    sprintf(
                        'Expected null for invalid enabled value of type %s, got NotificationPayload',
                        get_debug_type($invalidEnabled),
                    ),
                );
            });
    }

    /**
     * Property 11: When "recipients" is not an array, resolver returns null and logs a warning.
     *
     * **Validates: Requirements 2.8**
     */
    public function test_recipients_not_array_rejects_config_and_logs_warning(): void
    {
        // Generate non-array values for the "recipients" field
        $nonArrayValues = Set::either(
            Set::integers(),
            Set::strings()->between(1, 50),
            Set::of('user@example.com', 42, 3.14),
        );

        $this->logger
            ->expects($this->atLeastOnce())
            ->method('warning')
            ->with(
                $this->stringContains('invalid notification config'),
                $this->anything(),
            );

        self::forAll($nonArrayValues)
            ->take(100)
            ->then(function (mixed $invalidRecipients): void {
                $definition = TaskDefinition::make('test-task')
                    ->command('app:test')
                    ->notifications([
                        'enabled' => true,
                        'recipients' => $invalidRecipients,
                    ]);

                $result = $this->resolver->resolve($definition);

                $this->assertNull(
                    $result,
                    sprintf(
                        'Expected null for invalid recipients value of type %s, got NotificationPayload',
                        get_debug_type($invalidRecipients),
                    ),
                );
            });
    }

    /**
     * Property 11: When "enabled" key is missing entirely, resolver returns null and logs a warning.
     *
     * **Validates: Requirements 2.8**
     */
    public function test_missing_enabled_key_rejects_config_and_logs_warning(): void
    {
        // Generate arbitrary email arrays for the recipients field
        $arbitraryRecipients = Set::sequence(Set::email())
            ->between(0, 5);

        $this->logger
            ->expects($this->atLeastOnce())
            ->method('warning')
            ->with(
                $this->stringContains('invalid notification config'),
                $this->anything(),
            );

        self::forAll($arbitraryRecipients)
            ->take(100)
            ->then(function (array $recipients): void {
                // Config without "enabled" key at all
                $definition = TaskDefinition::make('test-task')
                    ->command('app:test')
                    ->notifications([
                        'recipients' => $recipients,
                    ]);

                $result = $this->resolver->resolve($definition);

                $this->assertNull(
                    $result,
                    'Expected null when "enabled" key is missing from per-task config',
                );
            });
    }
}
