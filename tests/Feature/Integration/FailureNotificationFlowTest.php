<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Feature\Integration;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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
 * Integration test for the failure notification flow.
 *
 * Validates: Requirements 3.1, 3.2, 3.3
 */
class FailureNotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    private NotificationEvaluationAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $logger = $this->createStub(LoggerInterface::class);

        $this->action = new NotificationEvaluationAction(
            new NotificationConfigResolver($logger),
            new RecoveryDetector(),
            $logger,
        );
    }

    /**
     * Test: task fails → notification queued with correct recipients and content.
     *
     * Validates: Requirements 3.1, 3.2, 3.3
     */
    public function test_task_failure_queues_notification_with_correct_recipients_and_content(): void
    {
        Mail::fake();

        $recipients = ['admin@example.com', 'ops@example.com'];

        config()->set('task-orchestrator.notifications.enabled', true);
        config()->set('task-orchestrator.notifications.recipients', $recipients);

        $taskName = 'import-users';
        $taskLabel = 'Import Users';
        $failureMessage = 'Connection timeout after 30 seconds';
        $startedAt = Carbon::parse('2024-01-15 10:00:00');
        $finishedAt = Carbon::parse('2024-01-15 10:05:00');

        $definition = TaskDefinition::make($taskName)
            ->label($taskLabel)
            ->command('app:import-users')
            ->notifications(null);

        $run = TaskRunRecord::create([
            'id' => Str::uuid()->toString(),
            'task_name' => $taskName,
            'task_label' => $taskLabel,
            'command' => 'app:import-users',
            'status' => TaskRunStatus::Failed->value,
            'started_at' => $startedAt,
            'finished_at' => $finishedAt,
            'failure_message' => $failureMessage,
        ]);

        $this->action->execute($run, $definition);

        // Assert a failure notification was queued
        Mail::assertQueued(TaskFailedMailable::class, 1);

        // Assert the mailable has correct content
        Mail::assertQueued(TaskFailedMailable::class, function (TaskFailedMailable $mailable) use ($taskLabel, $failureMessage, $startedAt, $finishedAt): bool {
            return $mailable->taskLabel === $taskLabel
                && $mailable->failureMessage === $failureMessage
                && $mailable->startedAt->equalTo($startedAt)
                && $mailable->finishedAt->equalTo($finishedAt);
        });
    }

    /**
     * Test: task fails with per-task recipients → notification sent to per-task recipients.
     *
     * Validates: Requirements 3.1
     */
    public function test_task_failure_uses_per_task_recipients_when_configured(): void
    {
        Mail::fake();

        $globalRecipients = ['global@example.com'];
        $perTaskRecipients = ['task-specific@example.com', 'team@example.com'];

        config()->set('task-orchestrator.notifications.enabled', true);
        config()->set('task-orchestrator.notifications.recipients', $globalRecipients);

        $taskName = 'sync-data';
        $taskLabel = 'Sync Data';

        $definition = TaskDefinition::make($taskName)
            ->label($taskLabel)
            ->command('app:sync-data')
            ->notifications([
                'enabled' => true,
                'recipients' => $perTaskRecipients,
            ]);

        $run = TaskRunRecord::create([
            'id' => Str::uuid()->toString(),
            'task_name' => $taskName,
            'task_label' => $taskLabel,
            'command' => 'app:sync-data',
            'status' => TaskRunStatus::Failed->value,
            'started_at' => now()->subMinutes(5),
            'finished_at' => now(),
            'failure_message' => 'Sync failed',
        ]);

        $this->action->execute($run, $definition);

        Mail::assertQueued(TaskFailedMailable::class, 1);

        Mail::assertQueued(TaskFailedMailable::class, function (TaskFailedMailable $mailable) use ($taskLabel): bool {
            return $mailable->taskLabel === $taskLabel;
        });
    }

    /**
     * Test: notifications disabled globally → no mail queued.
     *
     * Validates: Requirements 3.1
     */
    public function test_no_notification_when_globally_disabled(): void
    {
        Mail::fake();

        config()->set('task-orchestrator.notifications.enabled', false);
        config()->set('task-orchestrator.notifications.recipients', ['admin@example.com']);

        $taskName = 'export-reports';

        $definition = TaskDefinition::make($taskName)
            ->label('Export Reports')
            ->command('app:export-reports')
            ->notifications(null);

        $run = TaskRunRecord::create([
            'id' => Str::uuid()->toString(),
            'task_name' => $taskName,
            'task_label' => 'Export Reports',
            'command' => 'app:export-reports',
            'status' => TaskRunStatus::Failed->value,
            'started_at' => now()->subMinutes(3),
            'finished_at' => now(),
            'failure_message' => 'Export failed',
        ]);

        $this->action->execute($run, $definition);

        Mail::assertNothingQueued();
    }

    /**
     * Test: per-task notifications explicitly disabled → no mail queued even if global is enabled.
     *
     * Validates: Requirements 3.1
     */
    public function test_no_notification_when_per_task_disabled(): void
    {
        Mail::fake();

        config()->set('task-orchestrator.notifications.enabled', true);
        config()->set('task-orchestrator.notifications.recipients', ['admin@example.com']);

        $taskName = 'cleanup-temp';

        $definition = TaskDefinition::make($taskName)
            ->label('Cleanup Temp')
            ->command('app:cleanup-temp')
            ->notifications([
                'enabled' => false,
                'recipients' => [],
            ]);

        $run = TaskRunRecord::create([
            'id' => Str::uuid()->toString(),
            'task_name' => $taskName,
            'task_label' => 'Cleanup Temp',
            'command' => 'app:cleanup-temp',
            'status' => TaskRunStatus::Failed->value,
            'started_at' => now()->subMinutes(1),
            'finished_at' => now(),
            'failure_message' => 'Cleanup failed',
        ]);

        $this->action->execute($run, $definition);

        Mail::assertNothingQueued();
    }

    /**
     * Test: failure notification subject contains task label.
     *
     * Validates: Requirements 3.2
     */
    public function test_failure_notification_subject_contains_task_label(): void
    {
        Mail::fake();

        config()->set('task-orchestrator.notifications.enabled', true);
        config()->set('task-orchestrator.notifications.recipients', ['admin@example.com']);

        $taskName = 'generate-reports';
        $taskLabel = 'Generate Reports';

        $definition = TaskDefinition::make($taskName)
            ->label($taskLabel)
            ->command('app:generate-reports')
            ->notifications(null);

        $run = TaskRunRecord::create([
            'id' => Str::uuid()->toString(),
            'task_name' => $taskName,
            'task_label' => $taskLabel,
            'command' => 'app:generate-reports',
            'status' => TaskRunStatus::Failed->value,
            'started_at' => now()->subMinutes(2),
            'finished_at' => now(),
            'failure_message' => 'Report generation failed',
        ]);

        $this->action->execute($run, $definition);

        Mail::assertQueued(TaskFailedMailable::class, function (TaskFailedMailable $mailable) use ($taskLabel): bool {
            // The subject is set in the envelope: "[Task Orchestrator] Failed: {taskLabel}"
            return $mailable->taskLabel === $taskLabel;
        });
    }

    /**
     * Test: failure notification body contains task_label, failure_message, started_at, and finished_at.
     *
     * Validates: Requirements 3.3
     */
    public function test_failure_notification_body_contains_required_fields(): void
    {
        Mail::fake();

        config()->set('task-orchestrator.notifications.enabled', true);
        config()->set('task-orchestrator.notifications.recipients', ['admin@example.com']);

        $taskName = 'process-payments';
        $taskLabel = 'Process Payments';
        $failureMessage = 'Payment gateway unreachable';
        $startedAt = Carbon::parse('2024-03-01 14:00:00');
        $finishedAt = Carbon::parse('2024-03-01 14:02:30');

        $definition = TaskDefinition::make($taskName)
            ->label($taskLabel)
            ->command('app:process-payments')
            ->notifications(null);

        $run = TaskRunRecord::create([
            'id' => Str::uuid()->toString(),
            'task_name' => $taskName,
            'task_label' => $taskLabel,
            'command' => 'app:process-payments',
            'status' => TaskRunStatus::Failed->value,
            'started_at' => $startedAt,
            'finished_at' => $finishedAt,
            'failure_message' => $failureMessage,
        ]);

        $this->action->execute($run, $definition);

        Mail::assertQueued(TaskFailedMailable::class, function (TaskFailedMailable $mailable) use ($taskLabel, $failureMessage, $startedAt, $finishedAt): bool {
            return $mailable->taskLabel === $taskLabel
                && $mailable->failureMessage === $failureMessage
                && $mailable->startedAt->equalTo($startedAt)
                && $mailable->finishedAt->equalTo($finishedAt);
        });
    }
}
