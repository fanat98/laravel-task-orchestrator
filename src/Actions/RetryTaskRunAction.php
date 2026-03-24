<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Actions;

use Malsa\TaskOrchestrator\Models\TaskRunRecord;

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

        return $this->startTask->execute($run->task_name, 'retry');
    }
}
