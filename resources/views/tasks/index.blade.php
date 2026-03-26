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
                        <td>{{ $task['label'] }}</td>
                        <td class="hide-sm">{{ $task['group'] ?: '—' }}</td>
                        <td class="hide-sm table-cell-muted truncate" title="{{ $task['command'] }}">
                            {{ Str::limit($task['command'], 25) }}
                        </td>
                        <td>
                            @if ($task['schedule'])
                                <span class="badge badge-trigger-scheduled">
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
                                <span class="status-badge status-{{ $task['last_status'] }}">
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
                                        title="Run task"
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
