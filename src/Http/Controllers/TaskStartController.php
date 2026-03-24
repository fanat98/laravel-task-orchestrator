<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Malsa\TaskOrchestrator\Actions\StartTaskAction;

final class TaskStartController
{
    public function __invoke(string $task, StartTaskAction $startTask): RedirectResponse
    {
        $result = $startTask->execute($task);

        return redirect()->route('task-orchestrator.runs.show', $result['run']->id);
    }
}
