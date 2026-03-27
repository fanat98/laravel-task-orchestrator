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

final readonly class StartTaskAction
{
    public function __construct(
        private TaskOrchestratorManager $tasks,
    ) {
    }

    /**
     * @return array{run: TaskRun, context: TaskContext, record: TaskRunRecord}
     */
    public function execute(
        string $taskName,
        string $triggerType = 'manual',
        ?string $pipelineId = null,
    ): array
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

        if (! $definition->allowConcurrentRuns) {
            $existingQueuedOrRunning = TaskRunRecord::query()
                ->where('task_name', $definition->name)
                ->whereIn('status', [
                    TaskRunStatus::Queued->value,
                    TaskRunStatus::Running->value,
                ])
                ->exists();

            if ($existingQueuedOrRunning) {
                throw new \RuntimeException(sprintf(
                    'Task "%s" is already queued or running.',
                    $definition->name
                ));
            }
        }

        $record = TaskRunRecord::query()->create([
            'id' => $taskRunId,
            'task_name' => $definition->name,
            'task_label' => $definition->label,
            'command' => $definition->command,
            'command_arguments' => $definition->arguments,
            'status' => TaskRunStatus::Queued->value,
            'trigger_type' => $triggerType,
            'pipeline_id' => $pipelineId,
            'started_at' => null,
            'finished_at' => null,
        ]);

        $timeoutSeconds = max(
            (int) (($definition->timeoutMinutes ?? (int) config('task-orchestrator.stale_run_default_minutes', 10)) * 60),
            60
        );


        $dispatch = ExecuteTaskRunJob::dispatch($taskRunId, $timeoutSeconds);

        if ($definition->connection) {
            $dispatch->onConnection($definition->connection);
        }

        if ($definition->queue) {
            $dispatch->onQueue($definition->queue);
        }

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
