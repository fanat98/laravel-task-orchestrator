<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Support;

use Illuminate\Support\Str;
use Malsa\TaskOrchestrator\Domain\TaskDefinition;

final class DiscoveredTaskDefinitionFactory
{
    /**
     * @param array{
     *     name?: string,
     *     label?: string,
     *     description?: string,
     *     group?: string,
     *     group_order?: int,
     *     order?: int,
     *     depends_on?: array<int, string>,
     *     timeout_minutes?: int,
     *     queue?: string,
     *     connection?: string,
     *     schedule?: array{expression?: string, human?: string}
     * } $metadata
     */
    public function fromCommand(string $command, array $metadata = []): TaskDefinition
    {
        $name = $metadata['name'] ?? $this->makeNameFromCommand($command);
        $label = $metadata['label'] ?? $this->makeLabelFromCommand($command);
        $description = $metadata['description'] ?? null;
        $group = $metadata['group'] ?? null;
        $groupOrder = $metadata['group_order'] ?? null;
        $order = $metadata['order'] ?? null;
        $dependsOn = $metadata['depends_on'] ?? [];
        $timeoutMinutes = $metadata['timeout_minutes'] ?? null;
        $queue = $metadata['queue'] ?? null;
        $connection = $metadata['connection'] ?? null;
        $schedule = $metadata['schedule'] ?? null;

        if ($queue !== null && ! is_string($queue)) {
            throw new \InvalidArgumentException(sprintf(
                'Task "%s" has invalid queue metadata. Expected string.',
                $name
            ));
        }

        if ($connection !== null && ! is_string($connection)) {
            throw new \InvalidArgumentException(sprintf(
                'Task "%s" has invalid connection metadata. Expected string.',
                $name
            ));
        }

        return TaskDefinition::make($name)
            ->label($label)
            ->description($description)
            ->command($command)
            ->group($group)
            ->groupOrder($groupOrder)
            ->order($order)
            ->dependsOn($dependsOn)
            ->timeoutMinutes($timeoutMinutes)
            ->queue($queue)
            ->connection($connection)
            ->schedule($schedule);
    }

    private function makeNameFromCommand(string $command): string
    {
        return str_replace(':', '-', $command);
    }

    private function makeLabelFromCommand(string $command): string
    {
        return Str::of($command)
            ->replace(':', ' ')
            ->replace('-', ' ')
            ->title()
            ->toString();
    }
}
