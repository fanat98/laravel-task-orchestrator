<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Http\Controllers;

use Illuminate\Contracts\View\View;
use Malsa\TaskOrchestrator\Support\TaskDetailDataProvider;

final class TaskShowController
{
    public function __invoke(string $task, TaskDetailDataProvider $dataProvider): View
    {
        $payload = $dataProvider->taskDetail($task);

        return view('task-orchestrator::tasks.show', [
            'task' => $payload['task'],
            'recentRuns' => $payload['recent_runs'],
        ]);
    }
}

