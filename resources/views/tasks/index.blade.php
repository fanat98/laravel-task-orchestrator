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

            <table>
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Label</th>
                    <th>Group</th>
                    <th>Description</th>
                    <th>Command</th>
                    <th>Schedule</th>
                    <th>Next Run</th>
                    <th>Last Run</th>
                    <th>Last Status</th>
                    <th>Action</th>
                    <th>Last Trigger</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($tasks as $task)
                    <tr>
                        <td>{{ $task['name'] }}</td>
                        <td>{{ $task['label'] }}</td>
                        <td>{{ $task['group'] ?: '—' }}</td>
                        <td>{{ $task['description'] ?: '—' }}</td>
                        <td>{{ $task['command'] ?: '—' }}</td>
                        <td>{{ $task['schedule']['human'] ?? $task['schedule']['expression'] ?? '—' }}</td>
                        <td>{{ $task['next_run']?->format('Y-m-d H:i:s') ?? '—' }}</td>
                        <td>{{ $task['last_run']?->format('Y-m-d H:i:s') ?? '—' }}</td>
                        <td>
                            @if ($task['last_status'])
                                <span class="status-badge status-{{ $task['last_status'] }}">
                    {{ ucfirst($task['last_status']) }}
                </span>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if ($task['allow_manual_run'])
                                <form method="POST" action="{{ route('task-orchestrator.tasks.run', $task['name']) }}">
                                    @csrf
                                    <button class="button" type="submit">Run now</button>
                                </form>
                            @else
                                <span class="muted">Manual start disabled</span>
                            @endif
                        </td>
                        <td>{{ $task['last_trigger_type'] ? ucfirst($task['last_trigger_type']) : '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
