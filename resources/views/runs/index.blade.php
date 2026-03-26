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

            <div class="table-wrap">
                <table class="table-compact">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Task</th>
                        <th>Status</th>
                        <th>Trigger</th>
                        <th class="hide-sm">Started</th>
                        <th class="hide-sm">Finished</th>
                        <th>Action</th>
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

                            <td title="{{ $run->task_label }}">
                                {{ $run->task_label }}
                            </td>

                            <td>
                        <span class="status-badge status-{{ $run->status }}">
                            {{ ucfirst($run->status) }}
                        </span>
                            </td>

                            <td>
                        <span class="badge
                            @if (($run->trigger_type ?? 'manual') === 'scheduled') badge-trigger-scheduled
                            @elseif (($run->trigger_type ?? 'manual') === 'pipeline') badge-trigger-pipeline
                            @elseif (($run->trigger_type ?? 'manual') === 'retry') badge-trigger-retry
                            @elseif (($run->trigger_type ?? 'manual') === 'manual') badge-trigger-manual
                            @else badge-trigger-default
                            @endif
                        ">
                            {{ ucfirst($run->trigger_type ?? 'manual') }}
                        </span>
                            </td>

                            <td class="hide-sm">
                                {{ $run->started_at?->format('Y-m-d H:i:s') ?? '—' }}
                            </td>

                            <td class="hide-sm">
                                {{ $run->finished_at?->format('Y-m-d H:i:s') ?? '—' }}
                            </td>

                            <td class="table-actions">
                                <a
                                    class="button button-small button-primary"
                                    href="{{ route('task-orchestrator.runs.show', $run->id) }}"
                                    title="Open run details"
                                >
                                    ↗ View
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div style="margin-top: 1rem;">
            {{ $runs->links() }}
        </div>
    @endif
@endsection
