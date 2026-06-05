<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduled Tasks Recovered</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .email-wrapper {
            max-width: 680px;
            margin: 0 auto;
            padding: 20px;
        }
        .email-card {
            background-color: #ffffff;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            overflow: hidden;
        }
        .email-header {
            background-color: #2d8a45;
            color: #ffffff;
            padding: 20px 24px;
        }
        .email-header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }
        .email-body {
            padding: 24px;
        }
        .meta {
            font-size: 14px;
            margin-bottom: 10px;
        }
        .task-list {
            margin: 10px 0 0;
            padding-left: 18px;
        }
        .email-footer {
            padding: 16px 24px;
            border-top: 1px solid #e0e0e0;
            font-size: 12px;
            color: #999999;
        }
    </style>
</head>
<body>
<div class="email-wrapper">
    <div class="email-card">
        <div class="email-header">
            <h1>Scheduled task execution recovered</h1>
        </div>

        <div class="email-body">
            <div class="meta">Recovered at: {{ $recoveredAt->format('Y-m-d H:i:s T') }}</div>

            @if (! empty($detectedAt))
                <div class="meta">Incident detected at: {{ $detectedAt }}</div>
            @endif

            <div class="meta">Previously missed tasks: {{ $previousMissedCount }}</div>

            @if (! empty($previousTaskNames))
                <div class="meta">Previously affected tasks:</div>
                <ul class="task-list">
                    @foreach ($previousTaskNames as $taskName)
                        <li>{{ $taskName }}</li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="email-footer">
            This is an automated notification from Task Orchestrator scheduled monitoring.
        </div>
    </div>
</div>
</body>
</html>

