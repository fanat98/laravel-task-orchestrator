<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Unit\Domain\ValueObjects;

use InvalidArgumentException;
use Malsa\TaskOrchestrator\Domain\ValueObjects\NotificationPayload;
use Malsa\TaskOrchestrator\Tests\TestCase;

class NotificationPayloadTest extends TestCase
{
    public function test_valid_emails_accepted(): void
    {
        $payload = new NotificationPayload(['user@example.com', 'admin@company.org']);

        $this->assertSame(['user@example.com', 'admin@company.org'], $payload->recipients);
    }

    public function test_invalid_email_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email address: not-an-email');

        new NotificationPayload(['valid@example.com', 'not-an-email']);
    }

    public function test_more_than_50_recipients_throws(): void
    {
        $recipients = array_map(
            fn (int $i) => "user{$i}@example.com",
            range(1, 51)
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Recipients list cannot exceed 50 entries, 51 given.');

        new NotificationPayload($recipients);
    }

    public function test_empty_array_is_valid(): void
    {
        $payload = new NotificationPayload([]);

        $this->assertSame([], $payload->recipients);
    }
}
