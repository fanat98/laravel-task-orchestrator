<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Mail;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class TaskFailedMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $taskLabel,
        public readonly ?string $failureMessage,
        public readonly ?CarbonInterface $startedAt,
        public readonly ?CarbonInterface $finishedAt,
        public readonly ?string $runUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[Task Orchestrator] Failed: {$this->taskLabel}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'task-orchestrator::emails.task-failed',
            with: [
                'taskLabel' => $this->taskLabel,
                'startedAt' => $this->startedAt,
                'finishedAt' => $this->finishedAt,
                'runUrl' => $this->runUrl,
            ],
        );
    }
}
