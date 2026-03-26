<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Support;

final readonly class CommandDiscoveryRegistrar
{
    public function __construct(
        private TaskOrchestratorManager         $tasks,
        private DiscoveredTaskDefinitionFactory $factory,
    ) {
    }

    /**
     * @param array<int|string, string|array{
     *     name?: string,
     *     label?: string,
     *     description?: string,
     *     group?: string,
     *     group_order?: int,
     *     order?: int,
     *     depends_on?: array<int, string>,
     *     timeout_minutes?: int,
     *     schedule?: array{expression?: string, human?: string}
     * }> $commands
     */
    public function register(array $commands): void
    {
        foreach ($commands as $key => $value) {
            [$command, $metadata] = $this->normalizeEntry($key, $value);

            $this->tasks->register(
                $this->factory->fromCommand($command, $metadata)
            );
        }
    }

     /**
     * @param int|string $key
     * @param string|array{
     *     name?: string,
     *     label?: string,
     *     description?: string,
     *     group?: string,
     *     group_order?: int,
     *     order?: int,
     *     depends_on?: array<int, string>,
      *    timeout_minutes?: int,
     *     schedule?: array{expression?: string, human?: string}
     * } $value
     * @return array{0: string, 1: array{
     *     name?: string,
     *     label?: string,
     *     description?: string,
     *     group?: string,
     *     group_order?: int,
     *     order?: int,
     *     depends_on?: array<int, string>,
      *    timeout_minutes?: int,
     *     schedule?: array{expression?: string, human?: string}
     * }}
     */
    private function normalizeEntry(int|string $key, string|array $value): array
    {
        if (is_string($key) && is_array($value)) {
            return [$key, $value];
        }

        if (is_int($key) && is_string($value)) {
            return [$value, []];
        }

        throw new \InvalidArgumentException('Invalid command discovery entry format.');
    }
}
