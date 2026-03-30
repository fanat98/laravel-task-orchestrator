<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Malsa\TaskOrchestrator\Support\TaskDetailDataProvider;

final class TaskLogsController
{
    public function __invoke(string $task, Request $request, TaskDetailDataProvider $dataProvider): JsonResponse
    {
        $runId = $request->query('run_id');

        return response()->json(
            $dataProvider->logsTab($task, is_string($runId) ? $runId : null)
        );
    }
}

