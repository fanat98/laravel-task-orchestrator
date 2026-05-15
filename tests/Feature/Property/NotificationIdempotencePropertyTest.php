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
use Malsa\TaskOrchestrator\Mail\TaskRecoveredMailable;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Malsa\TaskOrchestrator\Support\NotificationConfigResolver;
use Malsa\TaskOrchestrator\Support\RecoveryDetector;
use Psr\Log\LoggerInterface;
use Malsa\TaskOrchestrator\Tests\TestCase;

/**
 * Feature: task-mail-notifications, Property 12: Notification idempotence
 *
 * Validates: Requirements 6.7
 *
 * For any task run reaching a terminal status, even if the notification evaluation logic
 * is triggered multiple times for the same run and same status, at most one notification
 * email shall be dispatched.
 */
class NotificationIdempotencePropertyTest extends TestCase
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
     * Property 12a: For any failed task run with notifications enabled, triggering
     * the evaluation action multiple times dispatches exactly one failure notification.
     *
     * **Validates: Requirements 6.7**
     *
     * @group property
     */
    public function test_duplicate_evaluation_for_failed_run_dispatches_only_one_notification(): void
    {
        self::forAll(
            $this->taskNameSet(),
            $this->invocationCountSet(),
            $this->recipientSet(),
        )
            ->take(100)
            ->then(function (string $taskName, int $invocationCount, array $recipients): void {
                // Clear cache to ensure clean state for each iteration
                Cache::flush();
                Mail::fake();

                // Configure notifications as enabled with valid recipients
                config()->set('task-orchestrator.notifications.enabled', true);
                config()->set('task-orchestrator.notifications.recipients', $recipients);

                $definition = TaskDefinition::make($taskName)
                    ->command("app:task:{$taskName}")
                    ->notifications(null);

                // Create a failed task run
                $run = $this->createTaskRun($taskName, TaskRunStatus::Failed);

                // Trigger evaluation multiple times for the same run and status
                for ($i = 0; $i < $invocationCount; $i++) {
                    $this->action->execute($run, $definition);
                }

                // Assert exactly one failure notification was dispatched
                Mail::assertQueued(TaskFailedMailable::class, 1);

                // Clean up for next iteration
                TaskRunRecord::where('task_name', $taskName)->delete();
            });
    }

    /**
     * Property 12b: For any succeeded task run with a previous failure (recovery scenario),
     * triggering the evaluation action multiple times dispatches exactly one recovery notification.
     *
     * **Validates: Requirements 6.7**
     *
     * @group property
     */
    public function test_duplicate_evaluation_for_recovered_run_dispatches_only_one_notification(): void
    {
        self::forAll(
            $this->taskNameSet(),
            $this->invocationCountSet(),
            $this->recipientSet(),
        )
            ->take(100)
            ->then(function (string $taskName, int $invocationCount, array $recipients): void {
                // Clear cache to ensure clean state for each iteration
                Cache::flush();
                Mail::fake();

                // Configure notifications as enabled with valid recipients
                config()->set('task-orchestrator.notifications.enabled', true);
                config()->set('task-orchestrator.notifications.recipients', $recipients);

                $definition = TaskDefinition::make($taskName)
                    ->command("app:task:{$taskName}")
                    ->notifications(null);

                // Create a previous failed run to trigger recovery detection
                $this->createTaskRun($taskName, TaskRunStatus::Failed, now()->subHour());

                // Create the current succeeded run
                $run = $this->createTaskRun($taskName, TaskRunStatus::Succeeded);

                // Trigger evaluation multiple times for the same run and status
                for ($i = 0; $i < $invocationCount; $i++) {
                    $this->action->execute($run, $definition);
                }

                // Assert exactly one recovery notification was dispatched
                Mail::assertQueued(TaskRecoveredMailable::class, 1);

                // Clean up for next iteration
                TaskRunRecord::where('task_name', $taskName)->delete();
            });
    }

    /**
     * Property 12c: For any succeeded task run without a previous failure (no recovery),
     * triggering the evaluation action multiple times dispatches zero notifications.
     * The idempotence cache key is still set, preventing any future dispatch.
     *
     * **Validates: Requirements 6.7**
     *
     * @group property
     */
    public function test_duplicate_evaluation_for_succeeded_run_without_recovery_dispatches_zero(): void
    {
        self::forAll(
            $this->taskNameSet(),
            $this->invocationCountSet(),
            $this->recipientSet(),
        )
            ->take(100)
            ->then(function (string $taskName, int $invocationCount, array $recipients): void {
                // Clear cache to ensure clean state for each iteration
                Cache::flush();
                Mail::fake();

                // Configure notifications as enabled with valid recipients
                config()->set('task-orchestrator.notifications.enabled', true);
                config()->set('task-orchestrator.notifications.recipients', $recipients);

                $definition = TaskDefinition::make($taskName)
                    ->command("app:task:{$taskName}")
                    ->notifications(null);

                // Create a succeeded run with no previous failure
                $run = $this->createTaskRun($taskName, TaskRunStatus::Succeeded);

                // Trigger evaluation multiple times
                for ($i = 0; $i < $invocationCount; $i++) {
                    $this->action->execute($run, $definition);
                }

                // No notifications should be dispatched (no recovery scenario)
                Mail::assertNothingQueued();

                // Clean up for next iteration
                TaskRunRecord::where('task_name', $taskName)->delete();
            });
    }

    /**
     * Property 12d: The idempotence mechanism is scoped per run and per status.
     * A notification for one run does not prevent notification for a different run.
     *
     * **Validates: Requirements 6.7**
     *
     * @group property
     */
    public function test_idempotence_is_scoped_per_run(): void
    {
        self::forAll(
            $this->taskNameSet(),
            $this->recipientSet(),
        )
            ->take(100)
            ->then(function (string $taskName, array $recipients): void {
                // Clear cache to ensure clean state for each iteration
                Cache::flush();
                Mail::fake();

                // Configure notifications as enabled with valid recipients
                config()->set('task-orchestrator.notifications.enabled', true);
                config()->set('task-orchestrator.notifications.recipients', $recipients);

                $definition = TaskDefinition::make($taskName)
                    ->command("app:task:{$taskName}")
                    ->notifications(null);

                // Create two separate failed runs
                $runA = $this->createTaskRun($taskName, TaskRunStatus::Failed, now()->subMinutes(10));
                $runB = $this->createTaskRun($taskName, TaskRunStatus::Failed, now());

                // Evaluate both runs — each should dispatch its own notification
                $this->action->execute($runA, $definition);
                $this->action->execute($runB, $definition);

                // Both runs should have dispatched a failure notification (2 total)
                Mail::assertQueued(TaskFailedMailable::class, 2);

                // Evaluating again should not dispatch additional notifications
                $this->action->execute($runA, $definition);
                $this->action->execute($runB, $definition);

                // Still only 2 total
                Mail::assertQueued(TaskFailedMailable::class, 2);

                // Clean up for next iteration
                TaskRunRecord::where('task_name', $taskName)->delete();
            });
    }

    // --- Helper methods ---

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
     * Generate a set of invocation counts (2 to 5 repeated calls).
     */
    private function invocationCountSet(): Set
    {
        return Set::integers()->between(2, 5)->toSet();
    }

    /**
     * Generate a set of valid recipient email arrays.
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
     * Create a TaskRunRecord with the given parameters.
     */
    private function createTaskRun(
        string $taskName,
        TaskRunStatus $status,
        ?\DateTimeInterface $finishedAt = null,
    ): TaskRunRecord {
        $finishedAt = $finishedAt ?? now();

        return TaskRunRecord::create([
            'id' => Str::uuid()->toString(),
            'task_name' => $taskName,
            'task_label' => ucfirst(str_replace('-', ' ', $taskName)),
            'command' => "app:task:{$taskName}",
            'status' => $status->value,
            'started_at' => (clone $finishedAt)->modify('-5 minutes'),
            'finished_at' => $finishedAt,
            'failure_message' => $status === TaskRunStatus::Failed ? 'Test failure message' : null,
        ]);
    }
}
