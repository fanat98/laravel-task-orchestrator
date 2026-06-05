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

final class ScheduledTasksRecoveredMailable extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * @param array<int, string> $previousTaskNames
     */
    public function __construct(
        public readonly CarbonInterface $recoveredAt,
        public readonly int $previousMissedCount,
        public readonly array $previousTaskNames,
        public readonly ?string $detectedAt,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[Task Orchestrator] Scheduled task execution recovered',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'task-orchestrator::emails.scheduled-tasks-recovered',
            with: [
                'recoveredAt' => $this->recoveredAt,
                'previousMissedCount' => $this->previousMissedCount,
                'previousTaskNames' => $this->previousTaskNames,
                'detectedAt' => $this->detectedAt,
            ],
        );
    }
}

