<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Actions;

use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Domain\TaskRun;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;
use Malsa\TaskOrchestrator\Support\TaskContext;

final class RetryTaskRunAction
{
    public function __construct(
        private readonly StartTaskAction $startTask,
    ) {
    }

    /**
     * @return array{run: \Malsa\TaskOrchestrator\Domain\TaskRun, context: \Malsa\TaskOrchestrator\Support\TaskContext, record: \Malsa\TaskOrchestrator\Models\TaskRunRecord}
     */
    public function execute(string $taskRunId): array
    {
        $run = TaskRunRecord::query()->findOrFail($taskRunId);

        $activeRun = TaskRunRecord::query()
            ->where('task_name', $run->task_name)
            ->whereIn('status', [
                TaskRunStatus::Queued->value,
                TaskRunStatus::Running->value,
            ])
            ->orderByDesc('created_at')
            ->first();

        if ($activeRun !== null) {
            return [
                'run' => new TaskRun(
                    id: $activeRun->id,
                    taskName: $activeRun->task_name,
                    status: TaskRunStatus::from($activeRun->status),
                    progress: null,
                    startedAt: null,
                    finishedAt: null,
                    failureMessage: $activeRun->failure_message,
                ),
                'context' => new TaskContext(
                    taskRunId: $activeRun->id,
                    taskName: $activeRun->task_name,
                ),
                'record' => $activeRun,
            ];
        }

        return $this->startTask->execute($run->task_name, 'retry');
    }
}
