<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Tests\Unit\Mail;

use Carbon\Carbon;
use Malsa\TaskOrchestrator\Mail\TaskFailedMailable;
use Malsa\TaskOrchestrator\Tests\TestCase;

class TaskFailedMailableTest extends TestCase
{
    public function test_subject_contains_task_label(): void
    {
        $mailable = new TaskFailedMailable(
            taskLabel: 'Import Users',
            failureMessage: 'Connection timeout',
            startedAt: Carbon::parse('2024-01-15 10:00:00'),
            finishedAt: Carbon::parse('2024-01-15 10:05:00'),
        );

        $mailable->assertHasSubject('[Task Orchestrator] Failed: Import Users');
    }

    public function test_body_contains_task_and_timestamps_but_no_failure_details(): void
    {
        $startedAt = Carbon::parse('2024-01-15 10:00:00');
        $finishedAt = Carbon::parse('2024-01-15 10:05:00');

        $mailable = new TaskFailedMailable(
            taskLabel: 'Import Users',
            failureMessage: 'Connection timeout',
            startedAt: $startedAt,
            finishedAt: $finishedAt,
        );

        $rendered = $mailable->render();

        $this->assertStringContainsString('Import Users', $rendered);
        $this->assertStringContainsString($startedAt->format('Y-m-d H:i:s T'), $rendered);
        $this->assertStringContainsString($finishedAt->format('Y-m-d H:i:s T'), $rendered);
        $this->assertStringNotContainsString('Failure Message', $rendered);
        $this->assertStringNotContainsString('Connection timeout', $rendered);
    }

    public function test_run_link_is_rendered_when_provided(): void
    {
        $runUrl = 'https://example.test/task-orchestrator/runs/abc-123';

        $mailable = new TaskFailedMailable(
            taskLabel: 'Import Users',
            failureMessage: 'Connection timeout',
            startedAt: Carbon::parse('2024-01-15 10:00:00'),
            finishedAt: Carbon::parse('2024-01-15 10:05:00'),
            runUrl: $runUrl,
        );

        $rendered = $mailable->render();

        $this->assertStringContainsString($runUrl, $rendered);
        $this->assertStringContainsString('Open Failed Run', $rendered);
    }

    public function test_run_link_is_omitted_when_missing(): void
    {
        $mailable = new TaskFailedMailable(
            taskLabel: 'Import Users',
            failureMessage: 'Connection timeout',
            startedAt: Carbon::parse('2024-01-15 10:00:00'),
            finishedAt: Carbon::parse('2024-01-15 10:05:00'),
            runUrl: null,
        );

        $rendered = $mailable->render();

        $this->assertStringNotContainsString('Open Failed Run', $rendered);
    }
}
