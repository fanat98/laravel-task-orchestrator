# Installation

This guide walks you through installing and setting up the Laravel Task Orchestrator.

---

## 1. Install via Composer

```bash
composer require fanat98/laravel-task-orchestrator
```

---

## 2. Publish Configuration & Assets

```bash
php artisan vendor:publish --tag=task-orchestrator-config
php artisan vendor:publish --tag=task-orchestrator-assets
```

---

## 3. Run Migrations

```bash
php artisan migrate
```

---

## 4. Configure Task Discovery

Create the discovery file:

```bash
mkdir -p app/TaskOrchestrator
touch app/TaskOrchestrator/discovery.php
```

Example:

```php
<?php

return [
    'commands' => [
        // your tasks here
    ],
];
```

---

## 5. Scheduler Setup (IMPORTANT)

Add this to your scheduler:

```php
$schedule->command('task-orchestrator:run-scheduled')->everyMinute();
```

Optional (recommended):

```php
$schedule->command('task-orchestrator:recover-stale-runs')
    ->everyTenMinutes()
    ->withoutOverlapping();
```

---

## 6. Queue Worker

Make sure a queue worker is running:

```bash
php artisan queue:work
```

---

## 7. Access Dashboard

Open in browser:

```
/task-orchestrator
```

---

## ✅ Done

You now have:

* Task dashboard
* Pipeline execution
* Scheduling
* Monitoring

Next: → Configuration
