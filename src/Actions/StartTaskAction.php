<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Actions;

use Illuminate\Support\Str;
use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Domain\TaskRun;
use Malsa\TaskOrchestrator\Jobs\ExecuteTaskRunJob;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Malsa\TaskOrchestrator\Support\ExecutionBlockingGuard;
use Malsa\TaskOrchestrator\Support\TaskContext;
use Malsa\TaskOrchestrator\Support\TaskOrchestratorManager;

final readonly class StartTaskAction
{
    public function __construct(
        private TaskOrchestratorManager $tasks,
        private ExecutionBlockingGuard $executionBlockingGuard,
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

        $this->executionBlockingGuard->ensureCanStart($definition);

        $taskRunId = (string) Str::uuid();

        $timeoutSeconds = max(
            (int) (($definition->timeoutMinutes ?? (int) config('task-orchestrator.stale_run_default_minutes', 10)) * 60),
            60
        );


        $record = TaskRunRecord::query()->create([
            'id' => $taskRunId,
            'task_name' => $definition->name,
            'task_label' => $definition->label,
            'command' => $definition->command,
            'command_arguments' => $definition->arguments,
            'status' => TaskRunStatus::Queued->value,
            'trigger_type' => $triggerType,
            'pipeline_id' => $pipelineId,
            'timeout_seconds' => $timeoutSeconds,
            'started_at' => null,
            'finished_at' => null,
        ]);



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
