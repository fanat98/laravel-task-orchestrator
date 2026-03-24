<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;

final class TaskRunLogsController
{
    public function __invoke(string $taskRun): JsonResponse
    {
        $run = TaskRunRecord::query()
            ->with('logs')
            ->findOrFail($taskRun);

        return response()->json([
            'logs' => $run->logs->map(fn ($log) => [
                'id' => $log->id,
                'level' => $log->level,
                'message' => $log->message,
                'created_at' => $log->created_at?->toDateTimeString(),
            ])->values(),
        ]);
    }
}
