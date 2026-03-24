<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Domain\ValueObjects;

final class Progress
{
    public function __construct(
        public readonly int $current,
        public readonly ?int $total = null,
        public readonly ?string $message = null,
    ) {
        if ($this->current < 0) {
            throw new \InvalidArgumentException('Progress current value cannot be negative.');
        }

        if ($this->total !== null && $this->total < 0) {
            throw new \InvalidArgumentException('Progress total value cannot be negative.');
        }

        if ($this->total !== null && $this->current > $this->total) {
            throw new \InvalidArgumentException('Progress current value cannot be greater than total.');
        }
    }

    public function isComplete(): bool
    {
        return $this->total !== null && $this->current === $this->total;
    }

    public function percentage(): ?float
    {
        if ($this->total === null || $this->total === 0) {
            return null;
        }

        return ($this->current / $this->total) * 100;
    }
}
