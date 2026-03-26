<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Malsa\TaskOrchestrator\Actions\StartTaskChainAction;

final class TaskStartController
{
    public function __invoke(string $task, StartTaskChainAction $startTaskChain): RedirectResponse
    {
        $results = $startTaskChain->execute($task, 'manual');

        $last = end($results);

        return redirect()->route('task-orchestrator.runs.show', $last['record']->id);
    }
}
