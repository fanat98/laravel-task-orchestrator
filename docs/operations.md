# Operations

This guide covers the runtime pieces Laravel Task Orchestrator needs in a real application: Laravel's scheduler, queue workers, health checks and stale run recovery.

## Scheduler

Task Orchestrator registers discovered schedules with Laravel's scheduler. A task is scheduled when its discovery entry includes a cron expression:

```php
'reports:send-daily' => [
    'name' => 'send-daily-reports',
    'label' => 'Send daily reports',
    'schedule' => [
        'expression' => '0 8 * * *',
        'human' => 'Daily at 08:00',
    ],
],
```

For local development, run:

```bash
php artisan schedule:work
```

For production, run Laravel's scheduler every minute:

```cron
* * * * * cd /path/to/application && php artisan schedule:run >> /dev/null 2>&1
```

The package also registers internal scheduled tasks for scheduler heartbeat, queue heartbeat and stale run recovery.

## Queue Workers

Task runs are executed through Laravel queues. Start a worker for the queues used by your tasks:

```bash
php artisan queue:work
```

If a task uses a custom queue, include it in the worker:

```bash
php artisan queue:work --queue=imports,default
```

In production, manage queue workers with Supervisor, systemd, Laravel Horizon or your hosting platform.

## Queue And Scheduler Health

The dashboard shows three operational health signals:

- queue state: `healthy`, `busy` or `stuck`
- scheduler state: `running` or `down`
- queue worker state: `running` or `down`

Queue state is based on pending jobs and `health.queue_stuck_threshold_seconds`.

Scheduler state is based on the heartbeat written by `task-orchestrator:record-scheduler-heartbeat`.

Queue worker state is based on a worker heartbeat refreshed by scheduled heartbeat jobs and task execution jobs.

If a scheduled task cannot be started (for example because queue worker heartbeat is stale or the start fails), Task Orchestrator writes a failed scheduled run with a clear failure reason. The run is not retried immediately; it is attempted again at the next regular schedule time.

## Stale Run Recovery

Queued or running runs can become stale if a worker dies, the process is killed or a task never finishes. The recovery command marks stale runs as failed:

```bash
php artisan task-orchestrator:recover-stale-runs
```

Timeouts are resolved in this order:

- explicit `--minutes=` command option
- timeout stored on the run
- task-level `timeout_minutes`
- global `stale_run_default_minutes`

You can run recovery manually during an incident:

```bash
php artisan task-orchestrator:recover-stale-runs --minutes=30
```

## Troubleshooting

### Scheduled tasks are not starting

Check that Laravel's scheduler is running and that the task has a valid `schedule.expression` in the discovery file.

### Runs stay queued

Check that a queue worker is running for the queue configured on the task. A task using `queue => imports` needs a worker listening to `imports`.

### Queue worker health is down

Check whether workers are actually processing jobs and whether the application cache is writable. Heartbeat state is stored in cache.

### Scheduler health is down

Check the production cron entry or local `schedule:work` process. The scheduler heartbeat must be refreshed every minute.

### Downstream tasks do not run

Check that upstream dependencies finished with a successful status and that `depends_on` references task names, not command names.
