<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Mail;

use Carbon\CarbonInterface;
use Carbon\CarbonInterval;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class TaskRecoveredMailable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $taskLabel,
        public readonly string $taskName,
        public readonly ?string $previousFailureMessage,
        public readonly ?CarbonInterval $recoveryDuration,
        public readonly ?CarbonInterface $failedAt,
        public readonly ?CarbonInterface $recoveredAt,
        public readonly ?string $failedRunUrl = null,
        public readonly ?string $recoveredRunUrl = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[Task Orchestrator] Recovered: {$this->taskName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'task-orchestrator::emails.task-recovered',
            with: [
                'taskLabel' => $this->taskLabel,
                'taskName' => $this->taskName,
                'recoveryDuration' => $this->recoveryDuration,
                'failedAt' => $this->failedAt,
                'recoveredAt' => $this->recoveredAt,
                'failedRunUrl' => $this->failedRunUrl,
                'recoveredRunUrl' => $this->recoveredRunUrl,
            ],
        );
    }
}
