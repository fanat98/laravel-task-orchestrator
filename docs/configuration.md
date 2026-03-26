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
        'mode' => 'user_field',
        'field' => 'is_admin',
    ],

    'discovery_path' => app_path('TaskOrchestrator/discovery.php'),

    'fail_on_invalid_dependencies' => false,

    'stale_run_default_minutes' => 10,
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
    'field' => 'is_admin',
]
```

#### Option 2: Gate

```php
'authorization' => [
    'mode' => 'gate',
    'ability' => 'viewTaskOrchestrator',
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

## Best Practices

* Use `user_field` for simple setups
* Use `gate` for advanced access control
* Always configure scheduler + queue worker
* Keep discovery file clean and structured

---

## Next

→ Task Discovery
