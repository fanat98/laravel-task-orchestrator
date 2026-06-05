<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class MissedScheduledTasksAlertMailable extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * @param array<int, array{
     *     task_name: string,
     *     task_label: string,
     *     group: string|null,
     *     schedule_expression: string,
     *     last_due_at: string,
     *     last_scheduled_run_at: string|null,
     *     minutes_overdue: int
     * }> $missedTasks
     */
    public function __construct(
        public readonly int $checkedTasks,
        public readonly int $missedCount,
        public readonly int $graceMinutes,
        public readonly array $missedTasks,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: sprintf('[Task Orchestrator] Missed scheduled tasks detected (%d)', $this->missedCount),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'task-orchestrator::emails.scheduled-tasks-missed',
            with: [
                'checkedTasks' => $this->checkedTasks,
                'missedCount' => $this->missedCount,
                'graceMinutes' => $this->graceMinutes,
                'missedTasks' => $this->missedTasks,
            ],
        );
    }
}

