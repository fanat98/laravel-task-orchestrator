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
                            <div class="pipeline-summary-value truncate">{{ $pipeline['pipeline_id'] }}</div>
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

                                        <span class="status-badge status-{{ $run->status }}">
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
