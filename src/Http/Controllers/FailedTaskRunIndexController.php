<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Http\Controllers;

use Illuminate\Contracts\View\View;
use Malsa\TaskOrchestrator\Domain\Enums\TaskRunStatus;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;

final class FailedTaskRunIndexController
{
    public function __invoke(): View
    {
        $runs = TaskRunRecord::query()
            ->where('status', TaskRunStatus::Failed->value)
            ->latest('started_at')
            ->latest('created_at')
            ->paginate(20);

        return view('task-orchestrator::runs.failed', [
            'runs' => $runs,
        ]);
    }
}
