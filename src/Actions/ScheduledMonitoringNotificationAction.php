<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Malsa\TaskOrchestrator\Mail\MissedScheduledTasksAlertMailable;
use Malsa\TaskOrchestrator\Mail\ScheduledTasksRecoveredMailable;
use Psr\Log\LoggerInterface;
use Throwable;

final class ScheduledMonitoringNotificationAction
{
    private const string INCIDENT_CACHE_KEY = 'task_orchestrator.scheduled_monitoring.active_incident';

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param array{
     *     status: string,
     *     checked_tasks: int,
     *     missed_count: int,
     *     grace_minutes: int,
     *     message: string,
     *     missed_tasks: array<int, array{
     *         task_name: string,
     *         task_label: string,
     *         group: string|null,
     *         schedule_expression: string,
     *         last_due_at: string,
     *         last_scheduled_run_at: string|null,
     *         minutes_overdue: int
     *     }>
     * } $result
     */
    public function execute(array $result): void
    {
        if (! $this->notificationsEnabled()) {
            return;
        }

        $recipients = $this->resolveRecipients();

        if ($recipients === []) {
            $this->logger->warning('Scheduled monitoring notifications are enabled but no valid recipients are configured.');

            return;
        }

        if (($result['missed_count'] ?? 0) > 0) {
            $this->notifyMissedTasks($result, $recipients);

            return;
        }

        $this->notifyRecoveryIfNeeded($recipients);
    }

    /**
     * @param array<int, string> $recipients
     * @param array{
     *     checked_tasks: int,
     *     missed_count: int,
     *     grace_minutes: int,
     *     missed_tasks: array<int, array{
     *         task_name: string,
     *         task_label: string,
     *         group: string|null,
     *         schedule_expression: string,
     *         last_due_at: string,
     *         last_scheduled_run_at: string|null,
     *         minutes_overdue: int
     *     }>
     * } $result
     */
    private function notifyMissedTasks(array $result, array $recipients): void
    {
        $taskNames = array_values(array_map(
            static fn (array $task): string => (string) ($task['task_name'] ?? ''),
            $result['missed_tasks']
        ));

        $fingerprint = hash('sha256', implode('|', array_filter($taskNames)));
        $activeIncident = Cache::get(self::INCIDENT_CACHE_KEY);

        if (is_array($activeIncident) && ($activeIncident['fingerprint'] ?? null) === $fingerprint) {
            return;
        }

        try {
            Mail::to($recipients)->queue(new MissedScheduledTasksAlertMailable(
                checkedTasks: (int) $result['checked_tasks'],
                missedCount: (int) $result['missed_count'],
                graceMinutes: (int) $result['grace_minutes'],
                missedTasks: $result['missed_tasks'],
            ));

            Cache::forever(self::INCIDENT_CACHE_KEY, [
                'fingerprint' => $fingerprint,
                'missed_count' => (int) $result['missed_count'],
                'task_names' => array_values(array_filter($taskNames)),
                'detected_at' => now()->toIso8601String(),
            ]);
        } catch (Throwable $e) {
            $this->logger->error('Failed to dispatch scheduled monitoring alert email: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }
    }

    /**
     * @param array<int, string> $recipients
     */
    private function notifyRecoveryIfNeeded(array $recipients): void
    {
        $activeIncident = Cache::pull(self::INCIDENT_CACHE_KEY);

        if (! is_array($activeIncident)) {
            return;
        }

        try {
            Mail::to($recipients)->queue(new ScheduledTasksRecoveredMailable(
                recoveredAt: now(),
                previousMissedCount: (int) ($activeIncident['missed_count'] ?? 0),
                previousTaskNames: is_array($activeIncident['task_names'] ?? null)
                    ? array_values(array_filter($activeIncident['task_names'], 'is_string'))
                    : [],
                detectedAt: is_string($activeIncident['detected_at'] ?? null)
                    ? $activeIncident['detected_at']
                    : null,
            ));
        } catch (Throwable $e) {
            $this->logger->error('Failed to dispatch scheduled monitoring recovery email: {message}', [
                'message' => $e->getMessage(),
                'exception' => $e,
            ]);
        }
    }

    private function notificationsEnabled(): bool
    {
        $override = config('task-orchestrator.scheduled_monitoring.notifications.enabled');

        if (is_bool($override)) {
            return $override;
        }

        return (bool) config('task-orchestrator.notifications.enabled', false);
    }

    /**
     * @return array<int, string>
     */
    private function resolveRecipients(): array
    {
        $overrideRecipients = config('task-orchestrator.scheduled_monitoring.notifications.recipients');
        $globalRecipients = config('task-orchestrator.notifications.recipients', []);

        $source = [];

        if (is_array($overrideRecipients) && $overrideRecipients !== []) {
            $source = $overrideRecipients;
        } elseif (is_array($globalRecipients)) {
            $source = $globalRecipients;
        }

        $validated = [];

        foreach ($source as $recipient) {
            if (! is_string($recipient)) {
                continue;
            }

            $email = trim($recipient);

            if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                continue;
            }

            $validated[] = $email;
        }

        return array_values(array_unique($validated));
    }
}

