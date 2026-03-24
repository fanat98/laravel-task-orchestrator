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
        $schedule = $metadata['schedule'] ?? null;

        return TaskDefinition::make($name)
            ->label($label)
            ->description($description)
            ->command($command)
            ->group($group)
            ->groupOrder($groupOrder)
            ->order($order)
            ->dependsOn($dependsOn)
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
