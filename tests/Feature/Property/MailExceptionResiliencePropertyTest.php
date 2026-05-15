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
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Malsa\TaskOrchestrator\Support\NotificationConfigResolver;
use Malsa\TaskOrchestrator\Support\RecoveryDetector;
use Psr\Log\LoggerInterface;
use Malsa\TaskOrchestrator\Tests\TestCase;

/**
 * Feature: task-mail-notifications, Property 13: Mail exception resilience
 *
 * Validates: Requirements 7.4
 *
 * For any exception thrown by the mail system during notification dispatch, the notification
 * system shall catch the exception, log it, and allow the task run to complete with the same
 * status, exit code, and return value it would have produced without the notification system.
 */
class MailExceptionResiliencePropertyTest extends TestCase
{
    use BlackBox;
    use RefreshDatabase;

    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createStub(LoggerInterface::class);
    }

    /**
     * Property 13a: For any exception thrown by the mail system during failure notification
     * dispatch, the NotificationEvaluationAction catches it, logs it, and does not re-throw.
     * The task run status remains unchanged.
     *
     * **Validates: Requirements 7.4**
     *
     * @group property
     */
    public function test_mail_exception_during_failure_notification_is_caught_and_logged(): void
    {
        self::forAll(
            $this->taskNameSet(),
            $this->exceptionSet(),
            $this->recipientSet(),
        )
            ->take(100)
            ->then(function (string $taskName, \Throwable $exception, array $recipients): void {
                Cache::flush();

                // Configure notifications as enabled with valid recipients
                config()->set('task-orchestrator.notifications.enabled', true);
                config()->set('task-orchestrator.notifications.recipients', $recipients);

                $definition = TaskDefinition::make($taskName)
                    ->command("app:task:{$taskName}")
                    ->notifications(null);

                // Create a failed task run
                $run = $this->createTaskRun($taskName, TaskRunStatus::Failed);
                $originalStatus = $run->status;
                $originalId = $run->id;

                // Mock the Mail facade to throw the exception
                Mail::shouldReceive('to')
                    ->andThrow($exception);

                // Create the action with a logger that expects an error log
                $logger = $this->createMock(LoggerInterface::class);
                $logger->expects($this->once())
                    ->method('error')
                    ->with(
                        $this->stringContains('failure notification'),
                        $this->callback(fn (array $context) => isset($context['run_id'], $context['message'])
                            && $context['run_id'] === $originalId
                        ),
                    );

                $action = new NotificationEvaluationAction(
                    new NotificationConfigResolver($this->logger),
                    new RecoveryDetector(),
                    $logger,
                );

                // Execute should NOT throw — the exception is caught internally
                $action->execute($run, $definition);

                // Verify the task run status is unchanged
                $run->refresh();
                $this->assertSame($originalStatus, $run->status);

                // Clean up for next iteration
                TaskRunRecord::where('task_name', $taskName)->delete();
                \Mockery::close();
            });
    }

    /**
     * Property 13b: For any exception thrown by the mail system during recovery notification
     * dispatch, the NotificationEvaluationAction catches it, logs it, and does not re-throw.
     * The task run status remains unchanged.
     *
     * **Validates: Requirements 7.4**
     *
     * @group property
     */
    public function test_mail_exception_during_recovery_notification_is_caught_and_logged(): void
    {
        self::forAll(
            $this->taskNameSet(),
            $this->exceptionSet(),
            $this->recipientSet(),
        )
            ->take(100)
            ->then(function (string $taskName, \Throwable $exception, array $recipients): void {
                Cache::flush();

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
                $originalStatus = $run->status;
                $originalId = $run->id;

                // Mock the Mail facade to throw the exception
                Mail::shouldReceive('to')
                    ->andThrow($exception);

                // Create the action with a logger that expects an error log
                $logger = $this->createMock(LoggerInterface::class);
                $logger->expects($this->once())
                    ->method('error')
                    ->with(
                        $this->stringContains('recovery notification'),
                        $this->callback(fn (array $context) => isset($context['run_id'], $context['message'])
                            && $context['run_id'] === $originalId
                        ),
                    );

                $action = new NotificationEvaluationAction(
                    new NotificationConfigResolver($this->logger),
                    new RecoveryDetector(),
                    $logger,
                );

                // Execute should NOT throw — the exception is caught internally
                $action->execute($run, $definition);

                // Verify the task run status is unchanged
                $run->refresh();
                $this->assertSame($originalStatus, $run->status);

                // Clean up for next iteration
                TaskRunRecord::where('task_name', $taskName)->delete();
                \Mockery::close();
            });
    }

    /**
     * Property 13c: For any exception type (RuntimeException, TransportException, generic Exception),
     * the notification action returns void without propagating the exception, ensuring the calling
     * code (ExecuteTaskRunAction) can continue unaffected.
     *
     * **Validates: Requirements 7.4**
     *
     * @group property
     */
    public function test_no_exception_propagates_regardless_of_exception_type(): void
    {
        self::forAll(
            $this->taskNameSet(),
            $this->exceptionSet(),
            $this->terminalStatusSet(),
            $this->recipientSet(),
        )
            ->take(100)
            ->then(function (string $taskName, \Throwable $exception, TaskRunStatus $status, array $recipients): void {
                Cache::flush();

                // Configure notifications as enabled with valid recipients
                config()->set('task-orchestrator.notifications.enabled', true);
                config()->set('task-orchestrator.notifications.recipients', $recipients);

                $definition = TaskDefinition::make($taskName)
                    ->command("app:task:{$taskName}")
                    ->notifications(null);

                // Create a previous failed run for recovery scenario
                if ($status === TaskRunStatus::Succeeded) {
                    $this->createTaskRun($taskName, TaskRunStatus::Failed, now()->subHour());
                }

                // Create the task run with the given terminal status
                $run = $this->createTaskRun($taskName, $status);

                // Mock the Mail facade to throw the exception
                Mail::shouldReceive('to')
                    ->andThrow($exception);

                $action = new NotificationEvaluationAction(
                    new NotificationConfigResolver($this->logger),
                    new RecoveryDetector(),
                    $this->logger,
                );

                // This MUST NOT throw — the property under test
                $caughtException = null;

                try {
                    $action->execute($run, $definition);
                } catch (\Throwable $e) {
                    $caughtException = $e;
                }

                $this->assertNull(
                    $caughtException,
                    sprintf(
                        'NotificationEvaluationAction propagated exception of type %s with message "%s" '
                        . 'for task "%s" with status "%s". It should have been caught internally.',
                        $exception::class,
                        $exception->getMessage(),
                        $taskName,
                        $status->value,
                    ),
                );

                // Clean up for next iteration
                TaskRunRecord::where('task_name', $taskName)->delete();
                \Mockery::close();
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
     * Generate a set of various exception types that the mail system might throw.
     */
    private function exceptionSet(): Set
    {
        return Set::either(
            Set::of(
                new \RuntimeException('SMTP connection refused'),
                new \RuntimeException('Connection timed out'),
                new \InvalidArgumentException('Invalid mail configuration'),
                new \Exception('Mail transport error'),
            ),
            Set::of(
                new \RuntimeException('Authentication failed'),
                new \LogicException('Mailer not configured'),
                new \RuntimeException('DNS resolution failed for mail server'),
                new \OverflowException('Message size exceeds limit'),
            ),
        );
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
     * Generate a set of terminal statuses that trigger notification evaluation.
     */
    private function terminalStatusSet(): Set
    {
        return Set::of(
            TaskRunStatus::Failed,
            TaskRunStatus::Succeeded,
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
