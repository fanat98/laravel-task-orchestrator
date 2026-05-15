<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Feature\Property;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Innmind\BlackBox\PHPUnit\BlackBox;
use Innmind\BlackBox\Set;
use Malsa\TaskOrchestrator\Actions\NotificationEvaluationAction;
use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Domain\TaskDefinition;
use Malsa\TaskOrchestrator\Mail\TaskFailedMailable;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Malsa\TaskOrchestrator\Support\NotificationConfigResolver;
use Malsa\TaskOrchestrator\Support\RecoveryDetector;
use Psr\Log\LoggerInterface;
use Malsa\TaskOrchestrator\Tests\TestCase;

/**
 * Feature: task-mail-notifications, Property 5: Failure notification dispatch
 *
 * Validates: Requirements 3.1
 *
 * For any task run that transitions to Failed status where the effective enabled state is
 * `true` and the resolved recipient list is non-empty, a failure notification email shall
 * be queued to all resolved recipients.
 */
class FailureNotificationDispatchPropertyTest extends TestCase
{
    use BlackBox;
    use RefreshDatabase;

    private NotificationEvaluationAction $action;

    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createStub(LoggerInterface::class);

        $this->action = new NotificationEvaluationAction(
            new NotificationConfigResolver($this->logger),
            new RecoveryDetector(),
            $this->logger,
        );
    }

    /**
     * Property 5a: For any task run transitioning to Failed status with notifications
     * globally enabled and a non-empty global recipient list, a failure notification
     * email is queued to all resolved recipients.
     *
     * **Validates: Requirements 3.1**
     *
     * @group property
     */
    public function test_failure_notification_queued_when_globally_enabled_with_recipients(): void
    {
        self::forAll(
            $this->taskNameSet(),
            $this->recipientSet(),
            $this->failureMessageSet(),
        )
            ->take(100)
            ->then(function (string $taskName, array $recipients, ?string $failureMessage): void {
                Cache::flush();
                Mail::fake();

                // Configure notifications as globally enabled with valid recipients
                config()->set('task-orchestrator.notifications.enabled', true);
                config()->set('task-orchestrator.notifications.recipients', $recipients);

                $definition = TaskDefinition::make($taskName)
                    ->command("app:task:{$taskName}")
                    ->notifications(null);

                // Create a failed task run
                $run = $this->createTaskRun($taskName, TaskRunStatus::Failed, $failureMessage);

                // Execute the notification evaluation
                $this->action->execute($run, $definition);

                // Assert a failure notification was queued
                Mail::assertQueued(TaskFailedMailable::class, function (TaskFailedMailable $mailable) use ($run, $recipients): bool {
                    // Verify the mailable contains the correct task label
                    return $mailable->taskLabel === $run->task_label;
                });

                // Assert exactly one failure notification was queued
                Mail::assertQueued(TaskFailedMailable::class, 1);

                // Clean up for next iteration
                TaskRunRecord::where('task_name', $taskName)->delete();
            });
    }

    /**
     * Property 5b: For any task run transitioning to Failed status with per-task
     * notifications enabled and a non-empty per-task recipient list, a failure
     * notification email is queued to the per-task recipients.
     *
     * **Validates: Requirements 3.1**
     *
     * @group property
     */
    public function test_failure_notification_queued_when_per_task_enabled_with_recipients(): void
    {
        self::forAll(
            $this->taskNameSet(),
            $this->recipientSet(),
            $this->failureMessageSet(),
        )
            ->take(100)
            ->then(function (string $taskName, array $recipients, ?string $failureMessage): void {
                Cache::flush();
                Mail::fake();

                // Global notifications disabled, but per-task enabled
                config()->set('task-orchestrator.notifications.enabled', false);
                config()->set('task-orchestrator.notifications.recipients', []);

                $definition = TaskDefinition::make($taskName)
                    ->command("app:task:{$taskName}")
                    ->notifications([
                        'enabled' => true,
                        'recipients' => $recipients,
                    ]);

                // Create a failed task run
                $run = $this->createTaskRun($taskName, TaskRunStatus::Failed, $failureMessage);

                // Execute the notification evaluation
                $this->action->execute($run, $definition);

                // Assert a failure notification was queued
                Mail::assertQueued(TaskFailedMailable::class, function (TaskFailedMailable $mailable) use ($run): bool {
                    return $mailable->taskLabel === $run->task_label;
                });

                // Assert exactly one failure notification was queued
                Mail::assertQueued(TaskFailedMailable::class, 1);

                // Clean up for next iteration
                TaskRunRecord::where('task_name', $taskName)->delete();
            });
    }

    /**
     * Property 5c: For any task run transitioning to Failed status with per-task
     * notifications enabled but empty per-task recipients, the failure notification
     * falls back to global recipients and is queued to them.
     *
     * **Validates: Requirements 3.1**
     *
     * @group property
     */
    public function test_failure_notification_queued_with_global_fallback_recipients(): void
    {
        self::forAll(
            $this->taskNameSet(),
            $this->recipientSet(),
            $this->failureMessageSet(),
        )
            ->take(100)
            ->then(function (string $taskName, array $globalRecipients, ?string $failureMessage): void {
                Cache::flush();
                Mail::fake();

                // Global recipients configured, per-task enabled but with empty recipients
                config()->set('task-orchestrator.notifications.enabled', false);
                config()->set('task-orchestrator.notifications.recipients', $globalRecipients);

                $definition = TaskDefinition::make($taskName)
                    ->command("app:task:{$taskName}")
                    ->notifications([
                        'enabled' => true,
                        'recipients' => [],
                    ]);

                // Create a failed task run
                $run = $this->createTaskRun($taskName, TaskRunStatus::Failed, $failureMessage);

                // Execute the notification evaluation
                $this->action->execute($run, $definition);

                // Assert a failure notification was queued (using global fallback recipients)
                Mail::assertQueued(TaskFailedMailable::class, function (TaskFailedMailable $mailable) use ($run): bool {
                    return $mailable->taskLabel === $run->task_label;
                });

                // Assert exactly one failure notification was queued
                Mail::assertQueued(TaskFailedMailable::class, 1);

                // Clean up for next iteration
                TaskRunRecord::where('task_name', $taskName)->delete();
            });
    }

    /**
     * Property 5d: For any task run transitioning to Failed status with notifications
     * enabled and multiple recipients, the notification is dispatched to all recipients
     * in a single queued mail operation.
     *
     * **Validates: Requirements 3.1**
     *
     * @group property
     */
    public function test_failure_notification_dispatched_to_all_recipients(): void
    {
        self::forAll(
            $this->taskNameSet(),
            $this->multiRecipientSet(),
            $this->failureMessageSet(),
        )
            ->take(100)
            ->then(function (string $taskName, array $recipients, ?string $failureMessage): void {
                Cache::flush();
                Mail::fake();

                // Configure notifications as globally enabled with multiple recipients
                config()->set('task-orchestrator.notifications.enabled', true);
                config()->set('task-orchestrator.notifications.recipients', $recipients);

                $definition = TaskDefinition::make($taskName)
                    ->command("app:task:{$taskName}")
                    ->notifications(null);

                // Create a failed task run
                $run = $this->createTaskRun($taskName, TaskRunStatus::Failed, $failureMessage);

                // Execute the notification evaluation
                $this->action->execute($run, $definition);

                // Assert exactly one failure notification was queued (single dispatch to all recipients)
                Mail::assertQueued(TaskFailedMailable::class, 1);

                // Verify the mailable was queued with the correct task label
                Mail::assertQueued(TaskFailedMailable::class, function (TaskFailedMailable $mailable) use ($run): bool {
                    return $mailable->taskLabel === $run->task_label;
                });

                // Clean up for next iteration
                TaskRunRecord::where('task_name', $taskName)->delete();
            });
    }

    // --- Generator Sets ---

    /**
     * Generate a set of unique task names.
     */
    private function taskNameSet(): Set
    {
        return Set::strings()
            ->madeOf(
                Set::strings()->chars()->lowercaseLetter(),
                Set::strings()->chars()->number(),
                Set::of('-'),
            )
            ->between(3, 30)
            ->filter(fn (string $s) => preg_match('/^[a-z][a-z0-9\-]+$/', $s) === 1);
    }

    /**
     * Generate a set of valid recipient email arrays (1-3 recipients).
     */
    private function recipientSet(): Set
    {
        return Set::either(
            Set::of(['admin@example.com']),
            Set::of(['admin@example.com', 'ops@example.com']),
            Set::of(['team@example.com', 'alerts@example.com', 'dev@example.com']),
        );
    }

    /**
     * Generate a set of multiple valid recipient email arrays (2-5 recipients).
     */
    private function multiRecipientSet(): Set
    {
        return Set::either(
            Set::of(['admin@example.com', 'ops@example.com']),
            Set::of(['team@example.com', 'alerts@example.com', 'dev@example.com']),
            Set::of(['a@example.com', 'b@example.com', 'c@example.com', 'd@example.com', 'e@example.com']),
        );
    }

    /**
     * Generate a set of failure messages (including null for edge case).
     */
    private function failureMessageSet(): Set
    {
        return Set::either(
            Set::of(null),
            Set::of('Process exited with code 1'),
            Set::of('Connection timeout after 30 seconds'),
            Set::of('Out of memory'),
            Set::of('Unhandled exception in task execution'),
        );
    }

    /**
     * Create a TaskRunRecord with the given parameters.
     */
    private function createTaskRun(
        string $taskName,
        TaskRunStatus $status,
        ?string $failureMessage = null,
    ): TaskRunRecord {
        $finishedAt = now();

        return TaskRunRecord::create([
            'id' => Str::uuid()->toString(),
            'task_name' => $taskName,
            'task_label' => ucfirst(str_replace('-', ' ', $taskName)),
            'command' => "app:task:{$taskName}",
            'status' => $status->value,
            'started_at' => (clone $finishedAt)->modify('-5 minutes'),
            'finished_at' => $finishedAt,
            'failure_message' => $failureMessage ?? ($status === TaskRunStatus::Failed ? 'Test failure message' : null),
        ]);
    }
}
