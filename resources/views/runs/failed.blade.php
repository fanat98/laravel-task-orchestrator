@extends('task-orchestrator::layout')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Failed Task Runs</h1>
            <p class="page-subtitle">Quick view of runs that need attention.</p>
        </div>
    </div>

    <div class="stat-cards" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 1.25rem;">
        <div class="stat-card stat-card--failed">
            <div class="stat-card-body">
                <div class="stat-card-label">FAILED RUNS TOTAL</div>
                <div class="stat-card-value stat-card-value--failed">{{ $runs->total() }}</div>
            </div>
            <div class="stat-card-icon stat-card-icon--failed">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-body">
                <div class="stat-card-label">SHOWN ON PAGE</div>
                <div class="stat-card-value">{{ $runs->count() }}</div>
            </div>
            <div class="stat-card-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                </svg>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-body">
                <div class="stat-card-label">OPERATIONAL FOCUS</div>
                <div class="stat-card-value" style="font-size: 1.1rem;">Errors Only</div>
            </div>
            <div class="stat-card-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
        </div>
    </div>

    @if ($runs->isEmpty())
        <div class="panel">
            <div class="empty">No failed task runs found.</div>
        </div>
    @else
        <div class="panel">
            <div class="panel-header">Failure Queue</div>

            <div class="table-wrap">
                <table class="table-compact">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Task</th>
                        <th>Trigger</th>
                        <th>Failure</th>
                        <th class="hide-sm">Finished</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($runs as $run)
                        <tr>
                            <td>
                                <a href="{{ route('task-orchestrator.runs.show', $run->id) }}">
                                    {{ Str::limit($run->id, 6, '') }}
                                </a>
                            </td>

                            <td title="{{ $run->task_label }}">
                                {{ $run->task_label }}
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
                                    @if (($run->trigger_type ?? 'manual') === 'pipeline')
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                                    @elseif (($run->trigger_type ?? 'manual') === 'scheduled')
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    @elseif (($run->trigger_type ?? 'manual') === 'manual')
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 9V5a3 3 0 0 0-6 0v4"/><rect x="2" y="9" width="20" height="13" rx="2"/></svg>
                                    @elseif (($run->trigger_type ?? 'manual') === 'retry')
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.08"/></svg>
                                    @else
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                    @endif
                                    {{ ucfirst($run->trigger_type ?? 'manual') }}
                                </span>
                            </td>

                            <td class="wrap-text">
                                {{ $run->failure_message ?: '—' }}
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
                    @empty
                        <tr>
                            <td colspan="6" class="table-cell-muted">No failed runs found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div style="margin-top: 1rem;">
            {{ $runs->links('task-orchestrator::partials.pagination') }}
        </div>
    @endif
@endsection
