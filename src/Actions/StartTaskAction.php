<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Actions;

use Illuminate\Support\Str;
use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Domain\TaskRun;
use Malsa\TaskOrchestrator\Jobs\ExecuteTaskRunJob;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Malsa\TaskOrchestrator\Support\TaskContext;
use Malsa\TaskOrchestrator\Support\TaskOrchestratorManager;

final class StartTaskAction
{
    public function __construct(
        private readonly TaskOrchestratorManager $tasks,
    ) {
    }

    /**
     * @return array{run: TaskRun, context: TaskContext, record: TaskRunRecord}
     */
    public function execute(string $taskName, string $triggerType = 'manual'): array
    {
        $definition = $this->tasks->find($taskName);

        if ($definition === null) {
            throw new \InvalidArgumentException(sprintf(
                'Task "%s" is not registered.',
                $taskName
            ));
        }

        $definition->ensureValid();

        $taskRunId = (string) Str::uuid();

        $record = TaskRunRecord::query()->create([
            'id' => $taskRunId,
            'task_name' => $definition->name,
            'task_label' => $definition->label,
            'command' => $definition->command,
            'command_arguments' => $definition->arguments,
            'status' => TaskRunStatus::Queued->value,
            'trigger_type' => $triggerType,
            'started_at' => null,
            'finished_at' => null,
        ]);

        ExecuteTaskRunJob::dispatch($taskRunId);

        return [
            'run' => new TaskRun(
                id: $record->id,
                taskName: $record->task_name,
                status: TaskRunStatus::Queued,
                progress: null,
                startedAt: null,
                finishedAt: null,
                failureMessage: null,
            ),
            'context' => new TaskContext(
                taskRunId: $record->id,
                taskName: $record->task_name,
            ),
            'record' => $record,
        ];
    }
}
