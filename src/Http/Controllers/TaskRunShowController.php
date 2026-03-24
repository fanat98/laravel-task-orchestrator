<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Http\Controllers;

use Illuminate\Contracts\View\View;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;

final class TaskRunShowController
{
    public function __invoke(string $taskRun): View
    {
        $run = TaskRunRecord::query()
            ->with('logs')
            ->findOrFail($taskRun);

        return view('task-orchestrator::runs.show', [
            'run' => $run,
        ]);
    }
}
