<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Http\Controllers;

use Illuminate\Contracts\View\View;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;

final class TaskRunIndexController
{
    public function __invoke(): View
    {
        $runs = TaskRunRecord::query()
            ->latest('started_at')
            ->latest('created_at')
            ->paginate(20);

        return view('task-orchestrator::runs.index', [
            'runs' => $runs,
        ]);
    }
}
