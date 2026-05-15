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
use Malsa\TaskOrchestrator\Mail\TaskRecoveredMailable;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Malsa\TaskOrchestrator\Support\NotificationConfigResolver;
use Malsa\TaskOrchestrator\Support\RecoveryDetector;
use Psr\Log\LoggerInterface;
use Malsa\TaskOrchestrator\Tests\TestCase;

/**
 * Integration test for the recovery notification flow.
 *
 * Validates: Requirements 4.1, 4.2, 4.3
 */
class RecoveryNotificationFlowTest extends TestCase
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
     * Test: task succeeds after previous failure → recovery notification queued.
     *
     * Validates: Requirements 4.1
     */
    public function test_task_succeeds_after_previous_failure_queues_recovery_notification(): void
    {
        Mail::fake();

        $recipients = ['admin@example.com', 'ops@example.com'];

        config()->set('task-orchestrator.notifications.enabled', true);
        config()->set('task-orchestrator.notifications.recipients', $recipients);

        $taskName = 'import-users';
        $taskLabel = 'Import Users';
        $previousFailureMessage = 'Connection timeout after 30 seconds';
        $previousFinishedAt = Carbon::parse('2024-01-15 10:05:00');
        $currentFinishedAt = Carbon::parse('2024-01-15 11:05:00');

        $definition = TaskDefinition::make($taskName)
            ->label($taskLabel)
            ->command('app:import-users')
            ->notifications(null);

        // Create a previous failed run
        TaskRunRecord::create([
            'id' => Str::uuid()->toString(),
            'task_name' => $taskName,
            'task_label' => $taskLabel,
            'command' => 'app:import-users',
            'status' => TaskRunStatus::Failed->value,
            'started_at' => Carbon::parse('2024-01-15 10:00:00'),
            'finished_at' => $previousFinishedAt,
            'failure_message' => $previousFailureMessage,
        ]);

        // Create the current successful run
        $currentRun = TaskRunRecord::create([
            'id' => Str::uuid()->toString(),
            'task_name' => $taskName,
            'task_label' => $taskLabel,
            'command' => 'app:import-users',
            'status' => TaskRunStatus::Succeeded->value,
            'started_at' => Carbon::parse('2024-01-15 11:00:00'),
            'finished_at' => $currentFinishedAt,
        ]);

        $this->action->execute($currentRun, $definition);

        // Assert a recovery notification was queued
        Mail::assertQueued(TaskRecoveredMailable::class, 1);

        // Assert the mailable has correct content
        Mail::assertQueued(TaskRecoveredMailable::class, function (TaskRecoveredMailable $mailable) use ($taskLabel, $taskName, $previousFailureMessage): bool {
            return $mailable->taskLabel === $taskLabel
                && $mailable->taskName === $taskName
                && $mailable->previousFailureMessage === $previousFailureMessage;
        });
    }

    /**
     * Test: task succeeds after previous success → no recovery notification.
     *
     * Validates: Requirements 4.2
     */
    public function test_task_succeeds_after_previous_success_no_recovery_notification(): void
    {
        Mail::fake();

        config()->set('task-orchestrator.notifications.enabled', true);
        config()->set('task-orchestrator.notifications.recipients', ['admin@example.com']);

        $taskName = 'recovery-test-sync-data';
        $taskLabel = 'Recovery Test Sync Data';

        $definition = TaskDefinition::make($taskName)
            ->label($taskLabel)
            ->command('app:recovery-test-sync-data')
            ->notifications(null);

        // Create a previous successful run
        TaskRunRecord::create([
            'id' => Str::uuid()->toString(),
            'task_name' => $taskName,
            'task_label' => $taskLabel,
            'command' => 'app:recovery-test-sync-data',
            'status' => TaskRunStatus::Succeeded->value,
            'started_at' => Carbon::parse('2024-01-15 09:00:00'),
            'finished_at' => Carbon::parse('2024-01-15 09:05:00'),
        ]);

        // Create the current successful run
        $currentRun = TaskRunRecord::create([
            'id' => Str::uuid()->toString(),
            'task_name' => $taskName,
            'task_label' => $taskLabel,
            'command' => 'app:recovery-test-sync-data',
            'status' => TaskRunStatus::Succeeded->value,
            'started_at' => Carbon::parse('2024-01-15 10:00:00'),
            'finished_at' => Carbon::parse('2024-01-15 10:05:00'),
        ]);

        $this->action->execute($currentRun, $definition);

        Mail::assertNothingQueued();
    }

    /**
     * Test: first run succeeds → no recovery notification.
     *
     * Validates: Requirements 4.3
     */
    public function test_first_run_succeeds_no_recovery_notification(): void
    {
        Mail::fake();

        config()->set('task-orchestrator.notifications.enabled', true);
        config()->set('task-orchestrator.notifications.recipients', ['admin@example.com']);

        $taskName = 'recovery-test-generate-reports';
        $taskLabel = 'Recovery Test Generate Reports';

        $definition = TaskDefinition::make($taskName)
            ->label($taskLabel)
            ->command('app:recovery-test-generate-reports')
            ->notifications(null);

        // Create only the current successful run (no previous run exists)
        $run = TaskRunRecord::create([
            'id' => Str::uuid()->toString(),
            'task_name' => $taskName,
            'task_label' => $taskLabel,
            'command' => 'app:recovery-test-generate-reports',
            'status' => TaskRunStatus::Succeeded->value,
            'started_at' => Carbon::parse('2024-01-15 10:00:00'),
            'finished_at' => Carbon::parse('2024-01-15 10:05:00'),
        ]);

        $this->action->execute($run, $definition);

        Mail::assertNothingQueued();
    }
}
