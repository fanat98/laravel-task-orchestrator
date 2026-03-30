<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Malsa\TaskOrchestrator\Support\TaskDetailDataProvider;

final class TaskDocumentationController
{
    public function __invoke(string $task, TaskDetailDataProvider $dataProvider): JsonResponse
    {
        return response()->json(
            $dataProvider->documentationTab($task)
        );
    }
}

