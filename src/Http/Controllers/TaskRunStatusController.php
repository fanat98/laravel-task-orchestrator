<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;

final class TaskRunStatusController
{
    public function __invoke(string $taskRun): JsonResponse
    {
        $run = TaskRunRecord::query()->findOrFail($taskRun);

        return response()->json([
            'id' => $run->id,
            'task_name' => $run->task_name,
            'task_label' => $run->task_label,
            'command' => $run->command,
            'status' => $run->status,
            'progress_current' => $run->progress_current,
            'progress_total' => $run->progress_total,
            'progress_message' => $run->progress_message,
            'failure_message' => $run->failure_message,
            'trigger_type' => $run->trigger_type,
            'started_at' => $run->started_at?->toDateTimeString(),
            'finished_at' => $run->finished_at?->toDateTimeString(),
        ]);
    }
}
