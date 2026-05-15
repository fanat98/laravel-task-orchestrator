<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Unit\Mail;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Malsa\TaskOrchestrator\Mail\TaskRecoveredMailable;
use Malsa\TaskOrchestrator\Tests\TestCase;

class TaskRecoveredMailableTest extends TestCase
{
    public function test_subject_contains_task_name(): void
    {
        $mailable = new TaskRecoveredMailable(
            taskLabel: 'Import Users',
            taskName: 'import-users',
            previousFailureMessage: 'Connection timeout',
            recoveryDuration: CarbonInterval::hours(2),
            failedAt: Carbon::parse('2024-01-15 10:00:00'),
            recoveredAt: Carbon::parse('2024-01-15 12:00:00'),
        );

        $mailable->assertHasSubject('[Task Orchestrator] Recovered: import-users');
    }

    public function test_body_contains_recovery_context_without_previous_failure_details(): void
    {
        $mailable = new TaskRecoveredMailable(
            taskLabel: 'Import Users',
            taskName: 'import-users',
            previousFailureMessage: 'Connection timeout',
            recoveryDuration: CarbonInterval::hours(2),
            failedAt: Carbon::parse('2024-01-15 10:00:00'),
            recoveredAt: Carbon::parse('2024-01-15 12:00:00'),
        );

        $rendered = $mailable->render();

        $this->assertStringContainsString('Import Users', $rendered);
        $this->assertStringContainsString('2 hours', $rendered);
        $this->assertStringContainsString('Failed At', $rendered);
        $this->assertStringContainsString('Recovered At', $rendered);
        $this->assertStringNotContainsString('Previous Failure Message', $rendered);
        $this->assertStringNotContainsString('Connection timeout', $rendered);
    }

    public function test_failed_and_recovered_run_links_are_rendered_when_provided(): void
    {
        $failedRunUrl = 'https://example.test/task-orchestrator/runs/failed-123';
        $recoveredRunUrl = 'https://example.test/task-orchestrator/runs/recovered-123';

        $mailable = new TaskRecoveredMailable(
            taskLabel: 'Import Users',
            taskName: 'import-users',
            previousFailureMessage: null,
            recoveryDuration: CarbonInterval::hours(2),
            failedAt: Carbon::parse('2024-01-15 10:00:00'),
            recoveredAt: Carbon::parse('2024-01-15 12:00:00'),
            failedRunUrl: $failedRunUrl,
            recoveredRunUrl: $recoveredRunUrl,
        );

        $rendered = $mailable->render();

        $this->assertStringContainsString($failedRunUrl, $rendered);
        $this->assertStringContainsString($recoveredRunUrl, $rendered);
        $this->assertStringContainsString('Open Failed Run', $rendered);
        $this->assertStringContainsString('Open Recovered Run', $rendered);
    }

    public function test_links_are_omitted_when_missing(): void
    {
        $mailable = new TaskRecoveredMailable(
            taskLabel: 'Import Users',
            taskName: 'import-users',
            previousFailureMessage: null,
            recoveryDuration: CarbonInterval::hours(2),
            failedAt: Carbon::parse('2024-01-15 10:00:00'),
            recoveredAt: Carbon::parse('2024-01-15 12:00:00'),
            failedRunUrl: null,
            recoveredRunUrl: null,
        );

        $rendered = $mailable->render();

        $this->assertStringNotContainsString('Open Failed Run', $rendered);
        $this->assertStringNotContainsString('Open Recovered Run', $rendered);
    }

    public function test_null_recovery_duration_is_omitted(): void
    {
        $mailable = new TaskRecoveredMailable(
            taskLabel: 'Import Users',
            taskName: 'import-users',
            previousFailureMessage: 'Connection timeout',
            recoveryDuration: null,
            failedAt: Carbon::parse('2024-01-15 10:00:00'),
            recoveredAt: Carbon::parse('2024-01-15 12:00:00'),
        );

        $rendered = $mailable->render();

        $this->assertStringNotContainsString('Recovery Duration', $rendered);
    }
}
