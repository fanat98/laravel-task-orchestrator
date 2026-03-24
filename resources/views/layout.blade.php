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
            <div class="topbar-title">Task Orchestrator</div>

            <nav class="topbar-nav">
                <a href="{{ route('task-orchestrator.dashboard') }}">Dashboard</a>
                <a href="{{ route('task-orchestrator.tasks.index') }}">Tasks</a>
                <a href="{{ route('task-orchestrator.runs.index') }}">All Runs</a>
                <a href="{{ route('task-orchestrator.runs.failed') }}">Failed Runs</a>
            </nav>
        </div>
    </header>

    <main class="container">
        @yield('content')
    </main>
</div>
</body>
</html>
