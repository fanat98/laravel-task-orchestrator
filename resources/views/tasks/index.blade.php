@extends('task-orchestrator::layout')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Registered Tasks</h1>
            <p class="page-subtitle">Available commands that can be triggered from the dashboard.</p>
        </div>
    </div>

    @include('task-orchestrator::partials.summary-cards', [
        'cards' => [
            ['label' => 'Registered Tasks', 'value' => $tasks->count()],
            ['label' => 'Manual Tasks', 'value' => $tasks->where('allow_manual_run', true)->count()],
            ['label' => 'Scheduled Tasks', 'value' => $tasks->filter(fn ($task) => !empty($task['schedule']))->count()],
        ],
    ])

    @if ($tasks->isEmpty())
        <div class="panel">
            <div class="empty">No tasks registered.</div>
        </div>
    @else
        <div class="panel">
            <div class="panel-header">Task Catalog</div>

            <div class="table-wrap">
                <table class="table-compact">
                <thead>
                <tr>
                    <th>Label</th>
                    <th class="hide-sm">Group</th>
                    <th class="hide-sm">Command</th>
                    <th>Schedule</th>
                    <th class="hide-sm">Next Run</th>
                    <th class="hide-sm">Last Run</th>
                    <th>Last Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($tasks as $task)
                    <tr>
                        <td>
                            <a href="{{ route('task-orchestrator.tasks.show', $task['name']) }}">
                                {{ $task['label'] }}
                            </a>
                        </td>
                        <td class="hide-sm">{{ $task['group'] ?: '—' }}</td>
                        <td class="hide-sm table-cell-muted truncate" title="{{ $task['command'] }}">
                            {{ Str::limit($task['command'], 25) }}
                        </td>
                        <td>
                            @if ($task['schedule'])
                                <span class="badge badge-trigger-scheduled">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    {{ $task['schedule']['human'] ?? $task['schedule']['expression'] }}
                                </span>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if ($task['next_run'])
                                <span class="table-cell-muted">
                                    ⏱ {{ $task['next_run']->format('H:i') }}
                                </span>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if ($task['last_run'])
                                <span class="table-cell-muted">
                                    ✔ {{ $task['last_run']->format('H:i') }}
                                </span>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if ($task['last_status'])
                                <span class="status-pill status-pill--{{ $task['last_status'] }}">
                                    @if($task['last_status'] === 'succeeded')
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                                        </svg>
                                    @elseif($task['last_status'] === 'failed')
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                                        </svg>
                                    @elseif($task['last_status'] === 'running')
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"/><polyline points="10 8 16 12 10 16"/>
                                        </svg>
                                    @elseif($task['last_status'] === 'queued')
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                                        </svg>
                                    @else
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                                        </svg>
                                    @endif
                                    {{ ucfirst($task['last_status']) }}
                                </span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="table-actions">
                            @if ($task['allow_manual_run'])
                                <form method="POST" action="{{ route('task-orchestrator.tasks.run', $task['name']) }}">
                                    @csrf
                                    <button
                                        class="button button-small button-primary"
                                        type="submit"
                                        @disabled(! $task['can_start'])
                                        title="{{ $task['can_start'] ? 'Run task' : ($task['is_running'] ? 'Task is already running' : ($task['is_queued'] ? 'Task is already queued' : ($task['is_blocked_by_dependencies'] ? 'Blocked by dependencies: '.implode(', ', $task['blocked_by_task_names']) : 'Task cannot be started'))) }}"
                                    >
                                        ▶
                                    </button>
                                </form>

                            @else
                                <span class="muted">Disabled</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        </div>
    @endif
@endsection
