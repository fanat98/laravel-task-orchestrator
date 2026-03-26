@extends('task-orchestrator::layout')

@section('content')
    @php
        $initialRun = [
            'id' => $run->id,
            'task_name' => $run->task_name,
            'task_label' => $run->task_label,
            'command' => $run->command,
            'status' => $run->status,
            'progress_current' => $run->progress_current,
            'progress_total' => $run->progress_total,
            'progress_message' => $run->progress_message,
            'failure_message' => $run->failure_message,
            'started_at' => $run->started_at?->toDateTimeString(),
            'finished_at' => $run->finished_at?->toDateTimeString(),
            'trigger_type' => $run->trigger_type,
            'pipeline_id' => $run->pipeline_id,
        ];

        $initialLogs = $run->logs->map(fn ($log) => [
            'id' => $log->id,
            'level' => $log->level,
            'message' => $log->message,
            'created_at' => $log->created_at?->toDateTimeString(),
        ])->values();
    @endphp

    <div
        id="task-orchestrator-run-detail-app"
        data-run-status-url="{{ route('task-orchestrator.api.runs.show', $run->id) }}"
        data-run-logs-url="{{ route('task-orchestrator.api.runs.logs', $run->id) }}"
        data-runs-index-url="{{ route('task-orchestrator.runs.index') }}"
        data-retry-url="{{ route('task-orchestrator.runs.retry', $run->id) }}"
        data-csrf-token="{{ csrf_token() }}"
        data-initial-run='@json($initialRun)'
        data-initial-logs='@json($initialLogs)'
        data-poll-interval="3000"
    ></div>
@endsection
