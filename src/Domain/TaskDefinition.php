<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Domain;

final class TaskDefinition
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
        public readonly string $name,
        public readonly string $label,
        public readonly ?string $description,
        public readonly ?string $command,
        public readonly array $arguments,
        public readonly ?string $group,
        public readonly ?int $groupOrder,
        public readonly ?int $order,
        public readonly ?array $schedule,
        public readonly array $dependsOn,
        public readonly ?int $timeoutMinutes,
        public readonly bool $allowManualRun,
        public readonly bool $allowConcurrentRuns,
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
    }
}
