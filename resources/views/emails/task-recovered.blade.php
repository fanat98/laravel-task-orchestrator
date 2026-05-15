<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Recovered: {{ $taskLabel }}</title>
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
            background-color: #28a745;
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
        .recovery-badge {
            display: inline-block;
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
        }
        .email-footer {
            padding: 16px 24px;
            border-top: 1px solid #e0e0e0;
            font-size: 12px;
            color: #999999;
        }
        .run-links {
            margin: 20px 0 8px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .run-links a {
            display: inline-block;
            background-color: #1f6feb;
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
                <h1>Task Recovered: {{ $taskLabel }}</h1>
            </div>

            <div class="email-body">
                <div class="detail-row">
                    <div class="detail-label">Task</div>
                    <div class="detail-value">{{ $taskLabel }}</div>
                </div>

                @if ($recoveryDuration)
                    <div class="detail-row">
                        <div class="detail-label">Recovery Duration</div>
                        <div class="detail-value">
                            <span class="recovery-badge">{{ $recoveryDuration->forHumans() }}</span>
                        </div>
                    </div>
                @endif

                @if ($failedAt)
                    <div class="detail-row">
                        <div class="detail-label">Failed At</div>
                        <div class="detail-value">{{ $failedAt->format('Y-m-d H:i:s T') }}</div>
                    </div>
                @endif

                @if ($recoveredAt)
                    <div class="detail-row">
                        <div class="detail-label">Recovered At</div>
                        <div class="detail-value">{{ $recoveredAt->format('Y-m-d H:i:s T') }}</div>
                    </div>
                @endif

                @if (! empty($failedRunUrl) || ! empty($recoveredRunUrl))
                    <div class="run-links">
                        @if (! empty($failedRunUrl))
                            <a href="{{ $failedRunUrl }}">Open Failed Run</a>
                        @endif
                        @if (! empty($recoveredRunUrl))
                            <a href="{{ $recoveredRunUrl }}">Open Recovered Run</a>
                        @endif
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
