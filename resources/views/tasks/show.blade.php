@extends('task-orchestrator::layout')

@section('content')
    <div
        id="task-orchestrator-task-detail-app"
        data-run-base-url="{{ url(config('task-orchestrator.route_prefix') . '/runs') }}"
        data-task-index-url="{{ route('task-orchestrator.tasks.index') }}"
        data-task-run-url="{{ route('task-orchestrator.tasks.run', $task['name']) }}"
        data-csrf-token="{{ csrf_token() }}"
        data-task-runs-url="{{ route('task-orchestrator.tasks.runs', $task['name']) }}"
        data-task-failures-url="{{ route('task-orchestrator.tasks.failures', $task['name']) }}"
        data-task-logs-url="{{ route('task-orchestrator.tasks.logs', $task['name']) }}"
        data-task-documentation-url="{{ route('task-orchestrator.tasks.documentation', $task['name']) }}"
        data-initial-task='@json($task)'
        data-initial-recent-runs='@json($recentRuns)'
    ></div>
@endsection

