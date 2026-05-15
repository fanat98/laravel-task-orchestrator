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
 * Feature: task-mail-notifications, Property 4: Invalid email filtering
 *
 * Validates: Requirements 1.7, 6.5
 *
 * For any resolved recipient list containing a mix of valid and invalid RFC 5322
 * email addresses, the notification shall be sent only to addresses that pass
 * validation, and invalid addresses shall be excluded.
 */
class EmailValidationFilteringPropertyTest extends TestCase
{
    use BlackBox;

    private LoggerInterface $logger;

    private NotificationConfigResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createStub(LoggerInterface::class);
        $this->resolver = new NotificationConfigResolver($this->logger);

        // Enable global notifications so config resolution proceeds to email filtering
        config()->set('task-orchestrator.notifications.enabled', true);
        config()->set('task-orchestrator.notifications.recipients', []);
    }

    /**
     * Property 4a: Valid emails are included in the resolved recipient list.
     *
     * For any set of valid RFC 5322 email addresses provided as per-task recipients,
     * all valid addresses shall appear in the resolved payload.
     *
     * **Validates: Requirements 1.7, 6.5**
     */
    public function test_valid_emails_are_included_in_payload(): void
    {
        $validEmailSets = Set::sequence(Set::email())
            ->between(1, 10);

        $this
            ->forAll($validEmailSets)
            ->take(100)
            ->then(function (array $validEmails): void {
                $definition = TaskDefinition::make('test-task')
                    ->command('app:test')
                    ->notifications([
                        'enabled' => true,
                        'recipients' => $validEmails,
                    ]);

                $result = $this->resolver->resolve($definition);

                $this->assertNotNull(
                    $result,
                    'Payload should not be null when valid emails are provided',
                );

                foreach ($validEmails as $email) {
                    $this->assertContains(
                        $email,
                        $result->recipients,
                        sprintf('Valid email "%s" should be included in the payload', $email),
                    );
                }
            });
    }

    /**
     * Property 4b: Invalid emails are excluded from the resolved recipient list.
     *
     * For any set of invalid email addresses (not conforming to RFC 5322) mixed with
     * at least one valid address, the invalid addresses shall not appear in the payload.
     *
     * **Validates: Requirements 1.7, 6.5**
     */
    public function test_invalid_emails_are_excluded_from_payload(): void
    {
        $invalidEmailSet = Set::either(
            Set::of(
                'not-an-email',
                '@missing-local.com',
                'missing-domain@',
                'spaces in@email.com',
                'double@@at.com',
                'no-tld@domain',
                '',
                'just-text',
                '@',
                'user@.com',
                '.user@domain.com',
                'user@domain..com',
            ),
            Set::strings()->between(1, 30)->filter(
                fn (string $s) => filter_var($s, FILTER_VALIDATE_EMAIL) === false
            ),
        );

        $invalidEmailsSets = Set::sequence($invalidEmailSet)->between(1, 5);
        $validEmailSets = Set::sequence(Set::email())->between(1, 3);

        $this
            ->forAll($invalidEmailsSets, $validEmailSets)
            ->take(100)
            ->then(function (array $invalidEmails, array $validEmails): void {
                $allRecipients = array_merge($validEmails, $invalidEmails);
                shuffle($allRecipients);

                $definition = TaskDefinition::make('test-task')
                    ->command('app:test')
                    ->notifications([
                        'enabled' => true,
                        'recipients' => $allRecipients,
                    ]);

                $result = $this->resolver->resolve($definition);

                $this->assertNotNull(
                    $result,
                    'Payload should not be null when at least one valid email exists',
                );

                foreach ($invalidEmails as $invalidEmail) {
                    $this->assertNotContains(
                        $invalidEmail,
                        $result->recipients,
                        sprintf('Invalid email "%s" should be excluded from the payload', $invalidEmail),
                    );
                }
            });
    }

    /**
     * Property 4c: When all emails are invalid, no notification is sent (null payload).
     *
     * For any set of exclusively invalid email addresses, the resolver shall return
     * null (no notification sent).
     *
     * **Validates: Requirements 1.7, 6.5**
     */
    public function test_all_invalid_emails_results_in_null_payload(): void
    {
        $invalidEmailSet = Set::either(
            Set::of(
                'not-an-email',
                '@missing-local.com',
                'missing-domain@',
                'spaces in@email.com',
                'double@@at.com',
                'no-tld@domain',
                'just-text',
                '@',
                'user@.com',
            ),
            Set::strings()->between(1, 30)->filter(
                fn (string $s) => filter_var($s, FILTER_VALIDATE_EMAIL) === false
            ),
        );

        $invalidEmailsSets = Set::sequence($invalidEmailSet)->between(1, 10);

        $this
            ->forAll($invalidEmailsSets)
            ->take(100)
            ->then(function (array $invalidEmails): void {
                $definition = TaskDefinition::make('test-task')
                    ->command('app:test')
                    ->notifications([
                        'enabled' => true,
                        'recipients' => $invalidEmails,
                    ]);

                $result = $this->resolver->resolve($definition);

                $this->assertNull(
                    $result,
                    sprintf(
                        'Payload should be null when all emails are invalid. Emails: [%s]',
                        implode(', ', $invalidEmails),
                    ),
                );
            });
    }

    /**
     * Property 4d: The payload contains only valid emails from the original list.
     *
     * For any mixed list of valid and invalid emails, the resulting payload recipients
     * shall be a subset of the original list containing only addresses that pass
     * RFC 5322 validation.
     *
     * **Validates: Requirements 1.7, 6.5**
     */
    public function test_payload_contains_only_valid_subset_of_original_list(): void
    {
        $invalidEmailSet = Set::either(
            Set::of(
                'not-an-email',
                '@missing-local.com',
                'missing-domain@',
                'double@@at.com',
                'no-tld@domain',
            ),
            Set::strings()->between(1, 20)->filter(
                fn (string $s) => filter_var($s, FILTER_VALIDATE_EMAIL) === false
            ),
        );

        $mixedRecipients = Set::sequence(
            Set::either(
                Set::email(),
                $invalidEmailSet,
            )
        )->between(2, 15);

        $this
            ->forAll($mixedRecipients)
            ->take(100)
            ->then(function (array $recipients): void {
                $definition = TaskDefinition::make('test-task')
                    ->command('app:test')
                    ->notifications([
                        'enabled' => true,
                        'recipients' => $recipients,
                    ]);

                $result = $this->resolver->resolve($definition);

                // Determine expected valid emails from the input
                $expectedValid = array_values(array_filter(
                    $recipients,
                    fn (mixed $r) => is_string($r) && filter_var($r, FILTER_VALIDATE_EMAIL) !== false,
                ));

                // Cap at 50 (max recipients)
                $expectedValid = array_slice($expectedValid, 0, 50);

                if ($expectedValid === []) {
                    $this->assertNull(
                        $result,
                        'Payload should be null when no valid emails exist in the list',
                    );
                } else {
                    $this->assertNotNull($result, 'Payload should not be null when valid emails exist');
                    $this->assertEquals(
                        $expectedValid,
                        $result->recipients,
                        'Payload recipients should contain exactly the valid emails from the input (in order, capped at 50)',
                    );

                    // Verify every recipient in the payload passes validation
                    foreach ($result->recipients as $recipient) {
                        $this->assertNotFalse(
                            filter_var($recipient, FILTER_VALIDATE_EMAIL),
                            sprintf('Recipient "%s" in payload should be a valid email', $recipient),
                        );
                    }
                }
            });
    }

    /**
     * Property 4e: Invalid email filtering applies to global recipients as well.
     *
     * When per-task recipients are empty and global recipients contain invalid emails,
     * the filtering shall still exclude invalid addresses from the global list.
     *
     * **Validates: Requirements 1.7, 6.5**
     */
    public function test_invalid_email_filtering_applies_to_global_recipients(): void
    {
        $invalidEmailSet = Set::either(
            Set::of(
                'not-an-email',
                '@missing-local.com',
                'missing-domain@',
                'double@@at.com',
            ),
            Set::strings()->between(1, 20)->filter(
                fn (string $s) => filter_var($s, FILTER_VALIDATE_EMAIL) === false
            ),
        );

        $validEmailSets = Set::sequence(Set::email())->between(1, 3);
        $invalidEmailsSets = Set::sequence($invalidEmailSet)->between(1, 3);

        $this
            ->forAll($validEmailSets, $invalidEmailsSets)
            ->take(100)
            ->then(function (array $validEmails, array $invalidEmails): void {
                $globalRecipients = array_merge($validEmails, $invalidEmails);
                shuffle($globalRecipients);

                config()->set('task-orchestrator.notifications.recipients', $globalRecipients);

                // Per-task enabled but no per-task recipients → falls back to global
                $definition = TaskDefinition::make('test-task')
                    ->command('app:test')
                    ->notifications([
                        'enabled' => true,
                        'recipients' => [],
                    ]);

                $result = $this->resolver->resolve($definition);

                $this->assertNotNull(
                    $result,
                    'Payload should not be null when global recipients contain valid emails',
                );

                // Verify no invalid emails in the result
                foreach ($invalidEmails as $invalidEmail) {
                    $this->assertNotContains(
                        $invalidEmail,
                        $result->recipients,
                        sprintf('Invalid global email "%s" should be excluded from the payload', $invalidEmail),
                    );
                }

                // Verify all valid emails are present
                foreach ($validEmails as $validEmail) {
                    $this->assertContains(
                        $validEmail,
                        $result->recipients,
                        sprintf('Valid global email "%s" should be included in the payload', $validEmail),
                    );
                }
            });
    }
}
