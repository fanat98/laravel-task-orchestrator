<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Support;

use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Domain\TaskDefinition;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;

final class TaskDependencyCompletionGuard
{
    public function allDependenciesSucceeded(TaskDefinition $task, ?string $pipelineId): bool
    {
        if ($task->dependsOn === []) {
            return true;
        }

        if ($pipelineId === null) {
            return false;
        }

        foreach ($task->dependsOn as $dependencyTaskName) {
            $hasSucceededRun = TaskRunRecord::query()
                ->where('pipeline_id', $pipelineId)
                ->where('task_name', $dependencyTaskName)
                ->where('status', TaskRunStatus::Succeeded->value)
                ->exists();

            if (! $hasSucceededRun) {
                return false;
            }
        }

        return true;
    }
}
