<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Actions;

use Malsa\TaskOrchestrator\Support\TaskDependencyResolver;
use Illuminate\Support\Str;

final class StartTaskChainAction
{
    public function __construct(
        private readonly TaskDependencyResolver $resolver,
        private readonly StartTaskAction $startTask,
    ) {
    }

    /**
     * @return array<int, array{run: mixed, context: mixed, record: mixed}>
     */
    public function execute(string $taskName, string $triggerType = 'manual'): array
    {
        $tasks = $this->resolver->resolveWithDependencies($taskName);

        $results = [];
        $pipelineId = (string) Str::uuid();

        foreach ($tasks as $task) {
            $results[] = $this->startTask->execute($task->name, $triggerType, $pipelineId);
        }

        return $results;
    }
}
