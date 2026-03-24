@php
    $packageRoot = dirname(
        (new ReflectionClass(\Malsa\TaskOrchestrator\TaskOrchestratorServiceProvider::class))->getFileName(),
        2
    );

    $manifestPath = $packageRoot . '/public/build/.vite/manifest.json';

    $manifest = file_exists($manifestPath)
        ? json_decode(file_get_contents($manifestPath), true)
        : null;

    $entry = $manifest['resources/js/app.js'] ?? null;
@endphp

@if ($entry)
    @if (!empty($entry['css']))
        @foreach ($entry['css'] as $cssFile)
            <link rel="stylesheet" href="{{ asset('vendor/task-orchestrator/build/' . $cssFile) }}">
        @endforeach
    @endif

    @if (!empty($entry['file']))
        <script type="module" src="{{ asset('vendor/task-orchestrator/build/' . $entry['file']) }}"></script>
    @endif
@endif
