<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Malsa\TaskOrchestrator\Actions\RetryTaskRunAction;

final class TaskRetryController
{
    public function __invoke(string $taskRun, RetryTaskRunAction $retryTaskRun): RedirectResponse
    {
        $result = $retryTaskRun->execute($taskRun);

        return redirect()->route('task-orchestrator.runs.show', $result['run']->id);
    }
}
