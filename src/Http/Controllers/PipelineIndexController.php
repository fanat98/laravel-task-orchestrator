<?php

declare(strict_types=1);

namespace Malsa\TaskOrchestrator\Http\Controllers;

use Illuminate\Contracts\View\View;
use Malsa\TaskOrchestrator\Models\TaskRunRecord;

final class PipelineIndexController
{
    public function __invoke(): View
    {
        $pipelineGroups = TaskRunRecord::query()
            ->whereNotNull('pipeline_id')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('pipeline_id')
            ->map(function ($runs, $pipelineId) {
                $sortedRuns = $runs->sortBy([
                    ['started_at', 'asc'],
                    ['created_at', 'asc'],
                ])->values();

                $firstRun = $sortedRuns->first();
                $lastRun = $sortedRuns->last();

                return [
                    'pipeline_id' => $pipelineId,
                    'started_at' => $firstRun?->started_at,
                    'finished_at' => $lastRun?->finished_at,
                    'runs' => $sortedRuns,
                ];
            })
            ->values();

        return view('task-orchestrator::pipelines.index', [
            'pipelineGroups' => $pipelineGroups,
        ]);
    }
}
