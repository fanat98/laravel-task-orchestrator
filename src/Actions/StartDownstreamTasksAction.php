<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Actions;

use Malsa\TaskOrchestrator\Support\TaskDependencyCompletionGuard;
use Malsa\TaskOrchestrator\Support\TaskDownstreamResolver;

final class StartDownstreamTasksAction
{
    public function __construct(
        private readonly TaskDownstreamResolver $resolver,
        private readonly TaskDependencyCompletionGuard $completionGuard,
        private readonly StartTaskAction $startTask,
    ) {
    }

    public function execute(string $completedTaskName, ?string $pipelineId = null): void
    {
        $dependents = $this->resolver->directDependentsOf($completedTaskName);

        foreach ($dependents as $dependent) {
            if (! $this->completionGuard->allDependenciesSucceeded($dependent, $pipelineId)) {
                continue;
            }

            try {
                $this->startTask->execute($dependent->name, 'pipeline', $pipelineId);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }
    }
}
