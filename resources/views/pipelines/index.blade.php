@extends('task-orchestrator::layout')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Pipelines</h1>
            <p class="page-subtitle">Grouped view of related task runs executed as part of the same pipeline.</p>
        </div>

        <div class="nav-actions">
            <a class="button button-secondary" href="{{ route('task-orchestrator.dashboard') }}">Back to dashboard</a>
        </div>
    </div>

    @if ($pipelineGroups->isEmpty())
        <div class="panel">
            <div class="empty">No pipeline runs found yet.</div>
        </div>
    @else
        <div class="stack">
            @foreach ($pipelineGroups as $pipeline)
                <div class="panel">
                    <div class="panel-header">
                        Pipeline Run
                    </div>

                    <div class="pipeline-summary">
                        <div class="pipeline-summary-item">
                            <div class="pipeline-summary-label">Pipeline ID</div>
                            <div class="pipeline-summary-value">{{ Str::limit($pipeline['pipeline_id'], 8, '...') }}</div>
                        </div>

                        <div class="pipeline-summary-item">
                            <div class="pipeline-summary-label">Started</div>
                            <div class="pipeline-summary-value">{{ $pipeline['started_at']?->format('Y-m-d H:i:s') ?? '—' }}</div>
                        </div>

                        <div class="pipeline-summary-item">
                            <div class="pipeline-summary-label">Finished</div>
                            <div class="pipeline-summary-value">{{ $pipeline['finished_at']?->format('Y-m-d H:i:s') ?? '—' }}</div>
                        </div>
                    </div>

                    <div class="pipeline-flow">
                        @foreach ($pipeline['runs'] as $index => $run)
                            <div class="pipeline-step">
                                <div class="pipeline-step-card">
                                    <div class="pipeline-step-header">
                                        <div class="pipeline-step-title">
                                            {{ $run->task_label }}
                                        </div>

                                        <span class="status-pill status-pill--{{ $run->status }}">
                                            @if($run->status === 'succeeded')
                                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                                                </svg>
                                            @elseif($run->status === 'failed')
                                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                                                </svg>
                                            @elseif($run->status === 'running')
                                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="10"/><polyline points="10 8 16 12 10 16"/>
                                                </svg>
                                            @elseif($run->status === 'queued')
                                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                                                </svg>
                                            @else
                                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                                                </svg>
                                            @endif
                                            {{ ucfirst($run->status) }}
                                        </span>
                                    </div>

                                    <div class="pipeline-step-meta">
                                        <span class="badge
                                            @if ($run->trigger_type === 'scheduled') badge-trigger-scheduled
                                            @elseif ($run->trigger_type === 'pipeline') badge-trigger-pipeline
                                            @elseif ($run->trigger_type === 'retry') badge-trigger-retry
                                            @elseif ($run->trigger_type === 'manual') badge-trigger-manual
                                            @else badge-trigger-default
                                            @endif
                                        ">
                                            @if ($run->trigger_type === 'pipeline')
                                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                                            @elseif ($run->trigger_type === 'scheduled')
                                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                            @elseif ($run->trigger_type === 'manual')
                                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 9V5a3 3 0 0 0-6 0v4"/><rect x="2" y="9" width="20" height="13" rx="2"/></svg>
                                            @elseif ($run->trigger_type === 'retry')
                                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.08"/></svg>
                                            @else
                                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                            @endif
                                            {{ ucfirst($run->trigger_type ?? 'unknown') }}
                                        </span>

                                        <div class="pipeline-step-time">
                                            {{ $run->started_at?->format('H:i:s') ?? '—' }}
                                        </div>
                                    </div>

                                    <div class="pipeline-step-actions">
                                        <a href="{{ route('task-orchestrator.runs.show', $run->id) }}">
                                            ↗ View
                                        </a>
                                    </div>
                                </div>

                                @if ($index < count($pipeline['runs']) - 1)
                                    <div class="pipeline-arrow">→</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
