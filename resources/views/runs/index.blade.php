@extends('task-orchestrator::layout')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">All Task Runs</h1>
            <p class="page-subtitle">Operational history of all executed tasks.</p>
        </div>
    </div>

    @include('task-orchestrator::partials.summary-cards', [
        'cards' => [
            ['label' => 'Total Runs', 'value' => $runs->total()],
            ['label' => 'Shown On Page', 'value' => $runs->count()],
            ['label' => 'Failed Link', 'value' => 'Open Failed Runs'],
        ],
    ])

    @if ($runs->isEmpty())
        <div class="panel">
            <div class="empty">No task runs found.</div>
        </div>
    @else
        <div class="panel">
            <div class="panel-header">Run History</div>

            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Task</th>
                    <th>Label</th>
                    <th>Command</th>
                    <th>Status</th>
                    <th>Started</th>
                    <th>Finished</th>
                    <th>Trigger</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($runs as $run)
                    <tr>
                        <td>
                            <a href="{{ route('task-orchestrator.runs.show', $run->id) }}">
                                {{ $run->id }}
                            </a>
                        </td>
                        <td>{{ $run->task_name }}</td>
                        <td>{{ $run->task_label }}</td>
                        <td>{{ $run->command }}</td>
                        <td>
                            <span class="status-badge status-{{ $run->status }}">
                                {{ ucfirst($run->status) }}
                            </span>
                        </td>
                        <td>{{ $run->started_at?->format('Y-m-d H:i:s') }}</td>
                        <td>{{ $run->finished_at?->format('Y-m-d H:i:s') ?? '—' }}</td>
                        <td>{{ ucfirst($run->trigger_type ?? 'manual') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top: 1rem;">
            {{ $runs->links() }}
        </div>
    @endif
@endsection
