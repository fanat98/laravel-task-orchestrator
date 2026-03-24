@extends('task-orchestrator::layout')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Failed Task Runs</h1>
            <p class="page-subtitle">Quick view of runs that need attention.</p>
        </div>
    </div>

    @include('task-orchestrator::partials.summary-cards', [
        'cards' => [
            ['label' => 'Failed Runs Total', 'value' => $runs->total()],
            ['label' => 'Shown On Page', 'value' => $runs->count()],
            ['label' => 'Operational Focus', 'value' => 'Errors Only'],
        ],
    ])

    @if ($runs->isEmpty())
        <div class="panel">
            <div class="empty">No failed task runs found.</div>
        </div>
    @else
        <div class="panel">
            <div class="panel-header">Failure Queue</div>

            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Task</th>
                    <th>Label</th>
                    <th>Command</th>
                    <th>Status</th>
                    <th>Failure</th>
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
                            <span class="status-badge status-failed">
                                {{ ucfirst($run->status) }}
                            </span>
                        </td>
                        <td class="truncate">{{ $run->failure_message ?: '—' }}</td>
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
