<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Domain\ValueObjects;

final readonly class NotificationPayload
{
    private const int MAX_RECIPIENTS = 50;

    /**
     * @param array<int, string> $recipients Validated email addresses (max 50)
     */
    public function __construct(
        public array $recipients,
    ) {
        if (count($this->recipients) > self::MAX_RECIPIENTS) {
            throw new \InvalidArgumentException(
                sprintf('Recipients list cannot exceed %d entries, %d given.', self::MAX_RECIPIENTS, count($this->recipients))
            );
        }

        foreach ($this->recipients as $recipient) {
            if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === false) {
                throw new \InvalidArgumentException(
                    sprintf('Invalid email address: %s', $recipient)
                );
            }
        }
    }
}
