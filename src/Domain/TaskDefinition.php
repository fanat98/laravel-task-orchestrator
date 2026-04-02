<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Domain;

final readonly class TaskDefinition
{
    /**
     * @param array<string|int, mixed> $arguments
     * @param array{
     *     expression?: string,
     *     human?: string
     * }|null $schedule
     * @param array<int, string> $dependsOn
     */
    private function __construct(
        public string  $name,
        public string  $label,
        public ?string $description,
        public ?string $command,
        public array   $arguments,
        public ?string $group,
        public ?int    $groupOrder,
        public ?int    $order,
        public ?array  $schedule,
        public array   $dependsOn,
        public ?int    $timeoutMinutes,
        public bool    $allowManualRun,
        public bool    $allowConcurrentRuns,
        public ?string $queue,
        public ?string $connection,
    ) {
        if ($this->name === '') {
            throw new \InvalidArgumentException('Task name cannot be empty.');
        }

        if ($this->label === '') {
            throw new \InvalidArgumentException('Task label cannot be empty.');
        }
    }

    public static function make(string $name): self
    {
        return new self(
            name: $name,
            label: $name,
            description: null,
            command: null,
            arguments: [],
            group: null,
            groupOrder: null,
            order: null,
            schedule: null,
            dependsOn: [],
            timeoutMinutes: null,
            allowManualRun: true,
            allowConcurrentRuns: false,
            queue: null,
            connection: null,
        );
    }

    public function label(string $label): self
    {
        return new self(
            name: $this->name,
            label: $label,
            description: $this->description,
            command: $this->command,
            arguments: $this->arguments,
            group: $this->group,
            groupOrder: $this->groupOrder,
            order: $this->order,
            schedule: $this->schedule,
            dependsOn: $this->dependsOn,
            timeoutMinutes: $this->timeoutMinutes,
            allowManualRun: $this->allowManualRun,
            allowConcurrentRuns: $this->allowConcurrentRuns,
            queue: $this->queue,
            connection: $this->connection,
        );
    }

    public function description(?string $description): self
    {
        return new self(
            name: $this->name,
            label: $this->label,
            description: $description,
            command: $this->command,
            arguments: $this->arguments,
            group: $this->group,
            groupOrder: $this->groupOrder,
            order: $this->order,
            schedule: $this->schedule,
            dependsOn: $this->dependsOn,
            timeoutMinutes: $this->timeoutMinutes,
            allowManualRun: $this->allowManualRun,
            allowConcurrentRuns: $this->allowConcurrentRuns,
            queue: $this->queue,
            connection: $this->connection,
        );
    }

    public function command(string $command): self
    {
        return new self(
            name: $this->name,
            label: $this->label,
            description: $this->description,
            command: $command,
            arguments: $this->arguments,
            group: $this->group,
            groupOrder: $this->groupOrder,
            order: $this->order,
            schedule: $this->schedule,
            dependsOn: $this->dependsOn,
            timeoutMinutes: $this->timeoutMinutes,
            allowManualRun: $this->allowManualRun,
            allowConcurrentRuns: $this->allowConcurrentRuns,
            queue: $this->queue,
            connection: $this->connection,
        );
    }

    /**
     * @param array<string|int, mixed> $arguments
     */
    public function arguments(array $arguments): self
    {
        return new self(
            name: $this->name,
            label: $this->label,
            description: $this->description,
            command: $this->command,
            arguments: $arguments,
            group: $this->group,
            groupOrder: $this->groupOrder,
            order: $this->order,
            schedule: $this->schedule,
            dependsOn: $this->dependsOn,
            timeoutMinutes: $this->timeoutMinutes,
            allowManualRun: $this->allowManualRun,
            allowConcurrentRuns: $this->allowConcurrentRuns,
            queue: $this->queue,
            connection: $this->connection,
        );
    }

    public function group(?string $group): self
    {
        return new self(
            name: $this->name,
            label: $this->label,
            description: $this->description,
            command: $this->command,
            arguments: $this->arguments,
            group: $group,
            groupOrder: $this->groupOrder,
            order: $this->order,
            schedule: $this->schedule,
            dependsOn: $this->dependsOn,
            timeoutMinutes: $this->timeoutMinutes,
            allowManualRun: $this->allowManualRun,
            allowConcurrentRuns: $this->allowConcurrentRuns,
            queue: $this->queue,
            connection: $this->connection,
        );
    }

    public function groupOrder(?int $groupOrder): self
    {
        return new self(
            name: $this->name,
            label: $this->label,
            description: $this->description,
            command: $this->command,
            arguments: $this->arguments,
            group: $this->group,
            groupOrder: $groupOrder,
            order: $this->order,
            schedule: $this->schedule,
            dependsOn: $this->dependsOn,
            timeoutMinutes: $this->timeoutMinutes,
            allowManualRun: $this->allowManualRun,
            allowConcurrentRuns: $this->allowConcurrentRuns,
            queue: $this->queue,
            connection: $this->connection,
        );
    }

    public function order(?int $order): self
    {
        return new self(
            name: $this->name,
            label: $this->label,
            description: $this->description,
            command: $this->command,
            arguments: $this->arguments,
            group: $this->group,
            groupOrder: $this->groupOrder,
            order: $order,
            schedule: $this->schedule,
            dependsOn: $this->dependsOn,
            timeoutMinutes: $this->timeoutMinutes,
            allowManualRun: $this->allowManualRun,
            allowConcurrentRuns: $this->allowConcurrentRuns,
            queue: $this->queue,
            connection: $this->connection,
        );
    }

    /**
     * @param array{
     *     expression?: string,
     *     human?: string
     * }|null $schedule
     */
    public function schedule(?array $schedule): self
    {
        return new self(
            name: $this->name,
            label: $this->label,
            description: $this->description,
            command: $this->command,
            arguments: $this->arguments,
            group: $this->group,
            groupOrder: $this->groupOrder,
            order: $this->order,
            schedule: $schedule,
            dependsOn: $this->dependsOn,
            timeoutMinutes: $this->timeoutMinutes,
            allowManualRun: $this->allowManualRun,
            allowConcurrentRuns: $this->allowConcurrentRuns,
            queue: $this->queue,
            connection: $this->connection,
        );
    }

    /**
     * @param array<int, string> $dependsOn
     */
    public function dependsOn(array $dependsOn): self
    {
        return new self(
            name: $this->name,
            label: $this->label,
            description: $this->description,
            command: $this->command,
            arguments: $this->arguments,
            group: $this->group,
            groupOrder: $this->groupOrder,
            order: $this->order,
            schedule: $this->schedule,
            dependsOn: $dependsOn,
            timeoutMinutes: $this->timeoutMinutes,
            allowManualRun: $this->allowManualRun,
            allowConcurrentRuns: $this->allowConcurrentRuns,
            queue: $this->queue,
            connection: $this->connection,
        );
    }

    public function timeoutMinutes(?int $timeoutMinutes): self
    {
        return new self(
            name: $this->name,
            label: $this->label,
            description: $this->description,
            command: $this->command,
            arguments: $this->arguments,
            group: $this->group,
            groupOrder: $this->groupOrder,
            order: $this->order,
            schedule: $this->schedule,
            dependsOn: $this->dependsOn,
            timeoutMinutes: $timeoutMinutes,
            allowManualRun: $this->allowManualRun,
            allowConcurrentRuns: $this->allowConcurrentRuns,
            queue: $this->queue,
            connection: $this->connection,
        );
    }

    public function allowManualRun(bool $allowManualRun = true): self
    {
        return new self(
            name: $this->name,
            label: $this->label,
            description: $this->description,
            command: $this->command,
            arguments: $this->arguments,
            group: $this->group,
            groupOrder: $this->groupOrder,
            order: $this->order,
            schedule: $this->schedule,
            dependsOn: $this->dependsOn,
            timeoutMinutes: $this->timeoutMinutes,
            allowManualRun: $allowManualRun,
            allowConcurrentRuns: $this->allowConcurrentRuns,
            queue: $this->queue,
            connection: $this->connection,
        );
    }

    public function allowConcurrentRuns(bool $allowConcurrentRuns = true): self
    {
        return new self(
            name: $this->name,
            label: $this->label,
            description: $this->description,
            command: $this->command,
            arguments: $this->arguments,
            group: $this->group,
            groupOrder: $this->groupOrder,
            order: $this->order,
            schedule: $this->schedule,
            dependsOn: $this->dependsOn,
            timeoutMinutes: $this->timeoutMinutes,
            allowManualRun: $this->allowManualRun,
            allowConcurrentRuns: $allowConcurrentRuns,
            queue: $this->queue,
            connection: $this->connection,
        );
    }

    public function queue(?string $queue): self
    {
        return new self(
            name: $this->name,
            label: $this->label,
            description: $this->description,
            command: $this->command,
            arguments: $this->arguments,
            group: $this->group,
            groupOrder: $this->groupOrder,
            order: $this->order,
            schedule: $this->schedule,
            dependsOn: $this->dependsOn,
            timeoutMinutes: $this->timeoutMinutes,
            allowManualRun: $this->allowManualRun,
            allowConcurrentRuns: $this->allowConcurrentRuns,
            queue: $queue,
            connection: $this->connection,
        );
    }

    public function connection(?string $connection): self
    {
        return new self(
            name: $this->name,
            label: $this->label,
            description: $this->description,
            command: $this->command,
            arguments: $this->arguments,
            group: $this->group,
            groupOrder: $this->groupOrder,
            order: $this->order,
            schedule: $this->schedule,
            dependsOn: $this->dependsOn,
            timeoutMinutes: $this->timeoutMinutes,
            allowManualRun: $this->allowManualRun,
            allowConcurrentRuns: $this->allowConcurrentRuns,
            queue: $this->queue,
            connection: $connection,
        );
    }

    public function isValid(): bool
    {
        return filled($this->command);
    }

    public function ensureValid(): void
    {
        if (! filled($this->command)) {
            throw new \InvalidArgumentException(sprintf(
                'Task "%s" must define a command before registration.',
                $this->name
            ));
        }

        if ($this->queue !== null && trim($this->queue) === '') {
            throw new \InvalidArgumentException(sprintf(
                'Task "%s" defines an invalid queue.',
                $this->name
            ));
        }

        if ($this->connection !== null && trim($this->connection) === '') {
            throw new \InvalidArgumentException(sprintf(
                'Task "%s" defines an invalid queue connection.',
                $this->name
            ));
        }
    }
}
