<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Support;

use Malsa\TaskOrchestrator\Domain\TaskDefinition;
use Malsa\TaskOrchestrator\Domain\ValueObjects\NotificationPayload;
use Psr\Log\LoggerInterface;

final class NotificationConfigResolver
{
    private const int MAX_RECIPIENTS = 50;

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Resolve effective notification configuration for a task.
     *
     * @return NotificationPayload|null null when notifications should not be sent
     */
    public function resolve(TaskDefinition $definition): ?NotificationPayload
    {
        $perTask = $definition->notifications;
        $globalEnabled = (bool) config('task-orchestrator.notifications.enabled', false);
        $globalRecipients = config('task-orchestrator.notifications.recipients', []);

        if (! is_array($globalRecipients)) {
            $globalRecipients = [];
        }

        // Handle per-task configuration
        if ($perTask !== null) {
            return $this->resolveWithPerTaskConfig($perTask, $globalRecipients, $definition->name);
        }

        // No per-task config — use global settings
        if (! $globalEnabled) {
            return null;
        }

        return $this->buildPayload($globalRecipients, $definition->name);
    }

    /**
     * Resolve when per-task notification config is present.
     *
     * @param array<string, mixed> $perTask
     * @param array<int, string> $globalRecipients
     */
    private function resolveWithPerTaskConfig(array $perTask, array $globalRecipients, string $taskName): ?NotificationPayload
    {
        // Validate per-task config structure
        if (! array_key_exists('enabled', $perTask) || ! is_bool($perTask['enabled'])) {
            $this->logger->warning('Task "{task}" has invalid notification config: "enabled" must be a boolean.', [
                'task' => $taskName,
            ]);

            return null;
        }

        if (array_key_exists('recipients', $perTask) && ! is_array($perTask['recipients'])) {
            $this->logger->warning('Task "{task}" has invalid notification config: "recipients" must be an array.', [
                'task' => $taskName,
            ]);

            return null;
        }

        // Per-task explicitly disabled
        if ($perTask['enabled'] === false) {
            return null;
        }

        // Per-task explicitly enabled — use per-task recipients, fallback to global if empty
        $recipients = $perTask['recipients'] ?? [];

        if (! is_array($recipients) || $recipients === []) {
            $recipients = $globalRecipients;
        }

        return $this->buildPayload($recipients, $taskName);
    }

    /**
     * Validate emails, filter invalid, cap at max, and build the payload.
     *
     * @param array<int, mixed> $recipients
     */
    private function buildPayload(array $recipients, string $taskName): ?NotificationPayload
    {
        $validRecipients = [];
        $invalidRecipients = [];

        foreach ($recipients as $recipient) {
            if (! is_string($recipient)) {
                $invalidRecipients[] = (string) $recipient;

                continue;
            }

            if (filter_var($recipient, FILTER_VALIDATE_EMAIL) !== false) {
                $validRecipients[] = $recipient;
            } else {
                $invalidRecipients[] = $recipient;
            }
        }

        if ($invalidRecipients !== []) {
            $this->logger->warning('Task "{task}" has invalid email recipients: {emails}', [
                'task' => $taskName,
                'emails' => implode(', ', $invalidRecipients),
            ]);
        }

        // Cap at maximum recipients
        $validRecipients = array_slice($validRecipients, 0, self::MAX_RECIPIENTS);

        if ($validRecipients === []) {
            $this->logger->warning('Task "{task}" has notifications enabled but no valid recipients configured.', [
                'task' => $taskName,
            ]);

            return null;
        }

        return new NotificationPayload($validRecipients);
    }
}
