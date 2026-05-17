<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Feature\Property;

use Innmind\BlackBox\PHPUnit\BlackBox;
use Innmind\BlackBox\Set;
use Malsa\TaskOrchestrator\Domain\ValueObjects\NotificationPayload;
use Malsa\TaskOrchestrator\Tests\TestCase;

/**
 * Property-based tests for NotificationPayload value object.
 *
 * **Validates: Requirements 10.3, 10.4**
 */
class NotificationPayloadPropertyTest extends TestCase
{
    use BlackBox;

    /**
     * Property: for all arrays of valid emails with count ≤ 50, construction succeeds.
     *
     * **Validates: Requirements 10.3**
     *
     * @group property
     */
    public function test_valid_email_arrays_succeed(): void
    {
        self::forAll(
            Set\Integers::between(0, 50),
            Set\Integers::between(1, 9999),
        )
            ->take(200)
            ->then(function (int $count, int $seed) {
                $emails = [];
                for ($i = 0; $i < $count; $i++) {
                    $emails[] = "user{$seed}{$i}@example.com";
                }

                $payload = new NotificationPayload($emails);

                $this->assertSame($emails, $payload->recipients);
                $this->assertCount($count, $payload->recipients);
            });
    }

    /**
     * Property: for all arrays containing at least one invalid email, construction throws.
     *
     * **Validates: Requirements 10.4**
     *
     * @group property
     */
    public function test_invalid_emails_always_throw(): void
    {
        self::forAll(
            Set\Integers::between(0, 5),
            Set::of(
                'not-an-email',
                'missing@',
                '@no-local.com',
                'spaces in@email.com',
                'no-at-sign.com',
                '',
                'double@@at.com',
            ),
        )
            ->take(200)
            ->then(function (int $validCount, string $invalidEmail) {
                $emails = [];
                for ($i = 0; $i < $validCount; $i++) {
                    $emails[] = "valid{$i}@example.com";
                }
                $emails[] = $invalidEmail;

                $this->expectException(\InvalidArgumentException::class);

                new NotificationPayload($emails);
            });
    }
}
