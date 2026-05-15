<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Failed: {{ $taskLabel }}</title>
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
            max-width: 600px;
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
            background-color: #dc3545;
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
        .detail-row {
            margin-bottom: 16px;
        }
        .detail-label {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            color: #666666;
            margin-bottom: 4px;
        }
        .detail-value {
            font-size: 14px;
            color: #333333;
        }
        .email-footer {
            padding: 16px 24px;
            border-top: 1px solid #e0e0e0;
            font-size: 12px;
            color: #999999;
        }
        .run-link {
            margin: 20px 0 8px;
        }
        .run-link a {
            display: inline-block;
            background-color: #dc3545;
            color: #ffffff !important;
            text-decoration: none;
            padding: 10px 14px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-card">
            <div class="email-header">
                <h1>Task Failed: {{ $taskLabel }}</h1>
            </div>

            <div class="email-body">
                <div class="detail-row">
                    <div class="detail-label">Task</div>
                    <div class="detail-value">{{ $taskLabel }}</div>
                </div>

                @if ($startedAt)
                    <div class="detail-row">
                        <div class="detail-label">Started At</div>
                        <div class="detail-value">{{ $startedAt->format('Y-m-d H:i:s T') }}</div>
                    </div>
                @endif

                @if ($finishedAt)
                    <div class="detail-row">
                        <div class="detail-label">Finished At</div>
                        <div class="detail-value">{{ $finishedAt->format('Y-m-d H:i:s T') }}</div>
                    </div>
                @endif

                @if (! empty($runUrl))
                    <div class="run-link">
                        <a href="{{ $runUrl }}">Open Failed Run</a>
                    </div>
                @endif
            </div>

            <div class="email-footer">
                This is an automated notification from Task Orchestrator.
            </div>
        </div>
    </div>
</body>
</html>
