# Configuration

The configuration file is located at:

```
config/task-orchestrator.php
```

---

## Basic Example

```php
return [
    'route_prefix' => 'task-orchestrator',

    'middleware' => ['web', 'auth'],

    'authorization' => [
        'enabled' => true,
        'mode' => 'gate',
        'gate' => 'viewTaskOrchestrator',
        'user_field' => 'is_admin',
        'forbidden_message' => 'You do not have permission to access Task Orchestrator.',
    ],

    'database_connection' => env('TASK_ORCHESTRATOR_DB_CONNECTION'),
    'discovery_path' => app_path('TaskOrchestrator/discovery.php'),
    'fail_on_invalid_dependencies' => false,
    'stale_run_default_minutes' => 10,

    'health' => [
        'queue_stuck_threshold_seconds' => 300,
        'queue_worker' => [
            'heartbeat_max_age_seconds' => 300,
        ],
        'scheduler_heartbeat_cache_key' => 'task-orchestrator:scheduler-heartbeat',
        'scheduler_heartbeat_max_age_seconds' => 180,
        'scheduler_heartbeat_ttl_seconds' => 86400,
    ],

    'notifications' => [
        'enabled' => false,
        'recipients' => [],
    ],
];
```

---

## Options

### route_prefix

Defines the base URL:

```
/task-orchestrator
```

---

### middleware

Applied to all routes:

```php
'middleware' => ['web', 'auth']
```

---

### authorization

Controls access to the dashboard.

#### Option 1: User field

```php
'authorization' => [
    'mode' => 'user_field',
    'user_field' => 'is_admin',
]
```

#### Option 2: Gate

```php
'authorization' => [
    'mode' => 'gate',
    'gate' => 'viewTaskOrchestrator',
]
```

---

### discovery_path

Path to your task definition file:

```php
app/TaskOrchestrator/discovery.php
```

---

### fail_on_invalid_dependencies

If enabled:

* prevents execution when dependencies are missing or failed

```php
true | false
```

---

### stale_run_default_minutes

Fallback timeout for detecting stale runs:

```php
10
```

Used when a task does not define its own timeout.

---

### notifications.enabled

Enables or disables Task Orchestrator email notifications.

```php
true | false
```

When enabled, notifications are sent for failed runs and recovery runs.

---

### notifications.recipients

Global recipient list for notifications.

```php
[
    'ops@example.com',
    'engineering@example.com',
]
```

Notification emails include links to run detail pages and do not include raw failure payload details.

---

### health.queue_stuck_threshold_seconds

Threshold (in seconds) used to classify queue state as `stuck`.

```php
300
```

---

### health.queue_worker.heartbeat_max_age_seconds

Maximum age for queue worker heartbeat before worker state is considered `down`.

```php
300
```

Queue worker heartbeat is updated by:

* scheduled `QueueHeartbeatJob`
* task execution updates in `ExecuteTaskRunJob`

---

### health.scheduler_heartbeat_cache_key

Cache key used for scheduler heartbeat timestamp.

```php
'task-orchestrator:scheduler-heartbeat'
```

---

### health.scheduler_heartbeat_max_age_seconds

Maximum heartbeat age before scheduler is considered `down`.

```php
180
```

---

### health.scheduler_heartbeat_ttl_seconds

Cache TTL for heartbeat entries.

```php
86400
```

---

## Best Practices

* Use `user_field` for simple setups
* Use `gate` for advanced access control
* Always configure scheduler + queue worker
* Keep discovery file clean and structured
* Keep notification recipients to operational/team mailboxes
* Ensure failed scheduled starts are monitored through run history and alerts for failed runs

---

## Next

→ Task Discovery
