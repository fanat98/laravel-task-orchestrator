<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Console;

use Malsa\TaskOrchestrator\Support\TaskContext;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\Output;

final class TaskCommandOutput extends Output
{
    private string $buffer = '';

    public function __construct(
        private readonly TaskContext $context,
        int $verbosity = self::VERBOSITY_NORMAL,
        bool $decorated = false,
        ?OutputFormatterInterface $formatter = null,
    ) {
        parent::__construct($verbosity, $decorated, $formatter);
    }

    protected function doWrite(string $message, bool $newline): void
    {
        $this->buffer .= $message;

        if ($newline) {
            $this->flushBuffer();
            return;
        }

        $this->flushCompleteLines();
    }

    public function flushRemainingBuffer(): void
    {
        $remaining = trim($this->stripTrailingLineBreaks($this->buffer));

        $this->buffer = '';

        if ($remaining === '') {
            return;
        }

        foreach (preg_split("/\r\n|\n|\r/", $remaining) as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $this->context->log($line, $this->detectLogLevel($line));
        }
    }

    private function flushBuffer(): void
    {
        $message = trim($this->stripTrailingLineBreaks($this->buffer));

        $this->buffer = '';

        if ($message === '') {
            return;
        }

        foreach (preg_split("/\r\n|\n|\r/", $message) as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $this->context->log($line, $this->detectLogLevel($line));
        }
    }

    private function flushCompleteLines(): void
    {
        $lines = preg_split("/(\r\n|\n|\r)/", $this->buffer);

        if ($lines === false || count($lines) <= 1) {
            return;
        }

        $last = array_pop($lines);

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $this->context->log($line, $this->detectLogLevel($line));
        }

        $this->buffer = $last ?? '';
    }

    private function stripTrailingLineBreaks(string $value): string
    {
        return rtrim($value, "\r\n");
    }

    private function detectLogLevel(string $line): string
    {
        $normalized = mb_strtolower($line);

        return match (true) {
            str_contains($normalized, 'error') => 'error',
            str_contains($normalized, 'failed') => 'error',
            str_contains($normalized, 'warning') => 'warning',
            str_contains($normalized, 'warn') => 'warning',
            default => 'info',
        };
    }
}
