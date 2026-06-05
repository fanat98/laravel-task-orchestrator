<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Missed Scheduled Tasks</title>
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
            background-color: #b02a37;
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
        .summary {
            margin-bottom: 18px;
            font-size: 14px;
        }
        .task-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .task-item {
            border: 1px solid #ececec;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 10px;
            background-color: #fafafa;
        }
        .task-item strong {
            display: block;
            margin-bottom: 4px;
        }
        .meta {
            font-size: 12px;
            color: #666666;
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
            <h1>Missed scheduled tasks detected</h1>
        </div>

        <div class="email-body">
            <p class="summary">
                Checked <strong>{{ $checkedTasks }}</strong> scheduled task(s), detected
                <strong>{{ $missedCount }}</strong> missed task(s)
                with grace period <strong>{{ $graceMinutes }}</strong> minute(s).
            </p>

            <ul class="task-list">
                @foreach ($missedTasks as $task)
                    <li class="task-item">
                        <strong>{{ $task['task_label'] }} ({{ $task['task_name'] }})</strong>
                        <div class="meta">Group: {{ $task['group'] ?? 'Ungrouped' }}</div>
                        <div class="meta">Cron: {{ $task['schedule_expression'] }}</div>
                        <div class="meta">Last due at: {{ $task['last_due_at'] }}</div>
                        <div class="meta">Last scheduled run: {{ $task['last_scheduled_run_at'] ?? 'never' }}</div>
                        <div class="meta">Overdue: {{ $task['minutes_overdue'] }} minute(s)</div>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="email-footer">
            This is an automated notification from Task Orchestrator scheduled monitoring.
        </div>
    </div>
</div>
</body>
</html>

