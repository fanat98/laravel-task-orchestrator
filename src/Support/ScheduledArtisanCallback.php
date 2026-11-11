<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Runs an Artisan command in-process from the scheduler.
 *
 * Used instead of Schedule::command(), which spawns the command through a shell
 * and therefore fails on shell-less images (e.g. Docker Hardened Images).
 */
final class ScheduledArtisanCallback
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    public function __construct(
        public readonly string $command,
        public readonly array $parameters = [],
    ) {
    }

    /**
     * Returns false on a non-zero exit code so the scheduler marks the event as failed.
     */
    public function __invoke(): bool
    {
        // Capture output explicitly: it is discarded on success (like Schedule::command()
        // without sendOutputTo) and only logged when the command fails.
        $output = new BufferedOutput();

        $exitCode = Artisan::call($this->command, $this->parameters, $output);

        if ($exitCode === 0) {
            return true;
        }

        Log::warning(sprintf(
            'Scheduled command [%s] finished with exit code %d.',
            $this->command,
            $exitCode
        ), [
            'parameters' => $this->parameters,
            'output' => trim($output->fetch()),
        ]);

        return false;
    }
}

