@extends('task-orchestrator::layout')

@section('content')
    @php
        $initialSummary = [
            'registered_tasks' => $registeredTasksCount,
            'total_runs' => $totalRuns,
            'running_runs' => $runningRunsCount,
            'failed_runs' => $failedRunsCount,
        ];

        $initialLatestRuns = $latestRuns->map(fn ($run) => [
            'id' => $run->id,
            'task_label' => $run->task_label,
            'command' => $run->command,
            'status' => $run->status,
            'trigger_type' => $run->trigger_type,
            'started_at' => $run->started_at?->toDateTimeString(),
            'pipeline_id' => $run->pipeline_id,
        ])->values();

        $initialLatestFailedRuns = $latestFailedRuns->map(fn ($run) => [
            'id' => $run->id,
            'task_label' => $run->task_label,
            'failure_message' => $run->failure_message,
            'trigger_type' => $run->trigger_type,
            'finished_at' => $run->finished_at?->toDateTimeString(),
            'pipeline_id' => $run->pipeline_id,
        ])->values();
    @endphp

    <div class="page-header">
        <div>
            <h1 class="page-title">Operations Dashboard</h1>
            <p class="page-subtitle">Overview of registered tasks, recent executions, and current failures.</p>
        </div>

        <div class="nav-actions">
            <a class="button" href="{{ route('task-orchestrator.tasks.index') }}">View tasks</a>
            <a class="button button-secondary" href="{{ route('task-orchestrator.runs.index') }}">View runs</a>
            <a class="button button-secondary" href="{{ route('task-orchestrator.pipelines.index') }}">View pipelines</a>
        </div>
    </div>

    <div
        id="task-orchestrator-dashboard-app"
        data-dashboard-api-url="{{ route('task-orchestrator.api.dashboard') }}"
        data-run-base-url="{{ url(config('task-orchestrator.route_prefix') . '/runs') }}"
        data-task-run-base-url="{{ url(config('task-orchestrator.route_prefix') . '/tasks') }}"
        data-csrf-token="{{ csrf_token() }}"
        data-initial-summary='@json($initialSummary)'
        data-initial-health='@json($health)'
        data-initial-latest-runs='@json($initialLatestRuns)'
        data-initial-latest-failed-runs='@json($initialLatestFailedRuns)'
        data-initial-task-groups='@json($taskGroups)'
        data-poll-interval="5000"
    ></div>
@endsection
