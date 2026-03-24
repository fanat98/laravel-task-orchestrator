![Laravel](https://img.shields.io/badge/Laravel-12-red)
![License](https://img.shields.io/badge/license-MIT-blue)

# Laravel Task Orchestrator

A lightweight task orchestration dashboard for Laravel applications.

![Dashboard](./docs/dashboard.png)

Run, monitor, and schedule your Artisan commands with a clean UI — inspired by tools like Airflow, but built for Laravel simplicity.

---

## ✨ Features

* Run Laravel Artisan commands from a web dashboard
* Real-time logs & progress streaming
* Queue-based execution (non-blocking)
* Task scheduling (cron + human-readable)
* Task grouping and ordering
* Task dependencies (mini DAG support)
* Run history with status indicators
* Live dashboard updates (auto refresh)
* System health monitoring (queue, stale runs)
* Flexible authorization (Gate or user field)

---

## 📦 Installation

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

## ⚙️ Configuration

```php
// config/task-orchestrator.php

return [
    'route_prefix' => 'task-orchestrator',

    'middleware' => ['web', 'auth'],

    'authorization' => [
        'enabled' => true,

        // 'gate' or 'user_field'
        'mode' => 'user_field',

        // used when mode = user_field
        'user_field' => 'is_admin',

        // used when mode = gate
        'gate' => 'viewTaskOrchestrator',

        'forbidden_message' => 'You do not have permission to access Task Orchestrator.',
    ],
];
```

---

## 🔐 Authorization

### Option 1 — User field (simple)

```php
'mode' => 'user_field',
'user_field' => 'is_admin',
```

User must have:

```php
$user->is_admin === true
```

---

### Option 2 — Gate (advanced)

```php
Gate::define('viewTaskOrchestrator', fn ($user) => $user->is_admin);
```

---

## 🧠 Defining Tasks

Tasks are defined in a discovery config file:

```php
app/TaskOrchestrator/discovery.php
```

Example:

```php
return [
    'commands' => [
        'import:services' => [
            'name' => 'import-services',
            'label' => 'Import Services',
            'group' => 'ETL Imports',
            'group_order' => 10,
            'order' => 10,
            'schedule' => [
                'expression' => '0 */3 * * *',
                'human' => 'Every 3 hours',
            ],
        ],

        'import:control-requirements' => [
            'name' => 'control-requirements',
            'label' => 'Import Control Requirements',
            'group' => 'ETL Imports',
            'order' => 20,
            'depends_on' => ['import-services'],
            'schedule' => [
                'expression' => '0 20 * * *',
                'human' => 'Daily at 20:00',
            ],
        ],
    ],
];
```

---

## ⏱ Scheduling

Uses Laravel’s native scheduler.

Run:

```bash
php artisan schedule:work
```

---

## ▶️ Running Tasks

### From dashboard

Open:

```
/task-orchestrator
```

---

### From CLI

```bash
php artisan task-orchestrator:run-task control-requirements
```

---

## 📊 Dashboard

The dashboard provides:

* Task groups
* Status badges (success, failed, running)
* Last & next execution time
* Task dependencies
* Run history (last 5 runs)
* System health overview

---

## 🧩 Task Dependencies

Define dependencies between tasks:

```php
'depends_on' => ['import-services']
```

This is visualized in the dashboard and prepares for future workflow execution.

---

## 🧪 Queue Requirements

Tasks run via Laravel queues.

Make sure you run:

```bash
php artisan queue:work
```

---

## 🧱 Architecture Overview

* Tasks = Laravel Artisan commands
* Runs = stored in database
* Execution = queued jobs
* Logs = streamed + persisted
* UI = Vue.js (inside the package)
* Scheduler = Laravel native scheduler

---

## 📁 Database Tables

* `task_runs`
* `task_run_logs`

---

## 🚀 Future Improvements

* Full DAG execution (auto-run dependencies)
* Retry failed tasks
* Notifications & alerts
* Parallel pipelines
* Metrics dashboard

---

## 🤝 Contributing

Contributions are welcome!

---

## 📄 License

MIT
