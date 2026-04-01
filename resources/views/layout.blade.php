<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Task Orchestrator' }}</title>
    @include('task-orchestrator::partials.assets')
</head>
<body>
<div class="app-shell">
    <header class="topbar">
        <div class="topbar-inner">
            <div class="topbar-brand">
                <div class="topbar-logo">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>
                    </svg>
                </div>
                <span class="topbar-title">Task Orchestrator</span>
            </div>

            <nav class="topbar-nav">
                <a href="{{ route('task-orchestrator.dashboard') }}"
                   class="{{ request()->routeIs('task-orchestrator.dashboard') ? 'is-active' : '' }}">Dashboard</a>
                <a href="{{ route('task-orchestrator.tasks.index') }}"
                   class="{{ request()->routeIs('task-orchestrator.tasks.*') ? 'is-active' : '' }}">Tasks</a>
                <a href="{{ route('task-orchestrator.runs.index') }}"
                   class="{{ request()->routeIs('task-orchestrator.runs.index') ? 'is-active' : '' }}">Runs</a>
                <a href="{{ route('task-orchestrator.runs.failed') }}"
                   class="{{ request()->routeIs('task-orchestrator.runs.failed') ? 'is-active' : '' }}">Failed</a>
                <a href="{{ route('task-orchestrator.pipelines.index') }}"
                   class="{{ request()->routeIs('task-orchestrator.pipelines.*') ? 'is-active' : '' }}">Pipelines</a>
            </nav>

            <div class="topbar-actions">
                <button class="topbar-icon-btn" title="Notifications" aria-label="Notifications">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                </button>
                <button id="theme-toggle" class="topbar-icon-btn" title="Toggle theme" aria-label="Toggle theme">
                    <svg class="icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                    </svg>
                    <svg class="icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none">
                        <circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
                    </svg>
                </button>
                @php
                    $user = auth()->user();
                    $initials = $user
                        ? mb_strtoupper(mb_substr($user->first_name ?? '', 0, 1) . mb_substr($user->last_name ?? '', 0, 1))
                        : 'TO';
                    $fullName = $user
                        ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))
                        : 'Task Orchestrator';
                @endphp
                <div class="topbar-avatar" title="{{ $fullName }}">{{ $initials ?: 'TO' }}</div>
            </div>
        </div>
    </header>

    <main class="container">
        @yield('content')
    </main>
</div>
</body>
</html>
