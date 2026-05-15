![Laravel](https://img.shields.io/badge/Laravel-12-red)
![License](https://img.shields.io/badge/license-MIT-blue)

# Laravel Task Orchestrator

A lightweight task orchestration layer for Laravel that adds visibility, dependencies, pipelines, and a modern UI on top of your Artisan commands.

---

## ✨ Features

* 🔗 Task dependencies (`depends_on`)
* 🔄 Automatic pipeline execution (upstream → downstream)
* 🧠 Task discovery via config file
* 📊 Dashboard with real-time status
* 🧩 Pipeline view (visual flow of tasks)
* 🎯 Manual & scheduled triggers
* 🟢 Status tracking (queued, running, succeeded, failed)
* 🩺 Queue + scheduler health monitoring in dashboard
* 👷 Queue worker liveness monitoring (running/down)
* 🧯 Stale run recovery (auto-fail hanging tasks)
* ⏱ Per-task timeout configuration
* 📧 Optional failure/recovery notifications
* 🔐 Security-focused notification emails (no raw failure payload in email body)
* 🔗 Direct links from notification emails to run detail pages
* 🌙 Dark / Light mode
* 📱 Responsive UI

---

## 📸 Screenshots
![Dashboard](./docs/dashboard.png)

---

## 🚀 Installation

```bash
composer require fanat98/laravel-task-orchestrator
```

Publish config and assets:

```bash
php artisan vendor:publish --tag=task-orchestrator-config
php artisan vendor:publish --tag=task-orchestrator-assets
```

Run migrations:

```bash
php artisan migrate
```

---

## ⚙️ Basic Configuration

Config file:

```php
config/task-orchestrator.php
```

Example:

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

    'health' => [
        'queue_stuck_threshold_seconds' => 300,
        'queue_worker' => [
            'heartbeat_max_age_seconds' => 60,
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

## 📧 Notifications

Enable task notifications in `config/task-orchestrator.php`:

```php
'notifications' => [
    'enabled' => true,
    'recipients' => [
        'ops@example.com',
        'engineering@example.com',
    ],
],
```

Behavior:

* on **failed** task runs: sends a failure notification email
* on **recovered** task runs: sends a recovery notification email
* emails include direct links to run details in Task Orchestrator
* emails intentionally hide raw failure payload/details for security

---

## 🧩 Task Discovery

Create:

```bash
app/TaskOrchestrator/discovery.php
```

Example:

```php
<?php

return [
    'commands' => [
        'import:control-requirements' => [
            'name' => 'control-requirements',
            'label' => 'Import Control Requirements',
            'group' => 'ETL Imports',
            'group_order' => 10,
            'order' => 10,
            'connection' => 'database',
            'queue' => 'imports',
            'schedule' => [
                'expression' => '* * * * *',
                'human' => 'Every minute',
            ],
            'timeout_minutes' => 30,
        ],

        'import:resources' => [
            'name' => 'import-resources',
            'label' => 'Import Resources',
            'group' => 'ETL Imports',
            'group_order' => 10,
            'order' => 20,
            'depends_on' => ['control-requirements'],
            'timeout_minutes' => 30,
        ],

        'import:services' => [
            'name' => 'import-services',
            'label' => 'Import Services',
            'group' => 'ETL Imports',
            'group_order' => 10,
            'order' => 30,
            'depends_on' => ['import-resources'],
            'timeout_minutes' => 30,
        ],

        'control-verifications:notify-inactive' => [
            'name' => 'notify-inactive-control-verifications',
            'label' => 'Notify inactive control verifications',
            'group' => 'Control Verifications',
            'group_order' => 20,
            'order' => 10,
            'depends_on' => ['import-services'],
            'timeout_minutes' => 5,
        ],
    ],
];
```

---

## 🔄 Pipelines

Tasks with dependencies automatically form pipelines:

```
control-requirements → resources → services → notify
```

When a task succeeds:

* downstream tasks are triggered automatically
* all runs are grouped into a pipeline

Manual starts trigger only the selected task.

---

## 🧯 Stale Run Recovery

Recover hanging tasks:

```bash
php artisan task-orchestrator:recover-stale-runs
```

Behavior:

* uses per-task `timeout_minutes` if defined
* otherwise uses global config default

---

## 🩺 Dashboard Health Monitoring

The dashboard reports queue, scheduler, and queue worker health:

* queue `healthy`: no pending jobs
* queue `busy`: pending jobs exist but oldest pending age is below threshold
* queue `stuck`: oldest pending age exceeds threshold
* scheduler `running`: heartbeat is recent
* scheduler `down`: heartbeat is stale or missing
* queue worker `running`: worker heartbeat is recent
* queue worker `down`: worker heartbeat is stale or missing

Queue worker heartbeat is updated by a hybrid mechanism:

* scheduled `QueueHeartbeatJob` (every minute)
* task execution heartbeat updates inside `ExecuteTaskRunJob`

---

## 🔐 Authorization

### Option 1 – User field

```php
'authorization' => [
    'mode' => 'user_field',
    'field' => 'is_admin',
],
```

### Option 2 – Gate

```php
Gate::define('viewTaskOrchestrator', fn ($user) => $user->is_admin);
```

---

## 🎨 UI

* Dashboard overview
* Task groups
* Pipeline visualization
* Latest runs
* Failed runs
* Dark / Light mode toggle

---

## 🧪 Testing

Run the package tests with the package phpunit config.

When developing this package as a local path repository inside a host project,
use the host project's PHPUnit binary:

```bash
cd packages/laravel-task-orchestrator
../../vendor/bin/phpunit --configuration /absolute/path/to/packages/laravel-task-orchestrator/phpunit.xml --no-coverage
```

If you install package dependencies locally (`packages/laravel-task-orchestrator/vendor` exists),
you can run:

```bash
cd packages/laravel-task-orchestrator
vendor/bin/phpunit --configuration phpunit.xml --no-coverage
```

---

## 📚 Documentation

More detailed documentation:

* [Installation](docs/installation.md)
* [Configuration](docs/configuration.md)
* [Task Discovery](docs/discovery.md)
* [Pipelines](docs/pipelines.md)
* [Authorization](docs/authorization.md)

---

## 🛠 Requirements

* PHP 8.2+
* Laravel 10 / 11 / 12

---

## 📄 License

MIT
