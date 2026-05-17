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

## 5. Scheduler Setup

Task Orchestrator registers discovered task schedules, scheduler heartbeat, queue heartbeat and stale run recovery with Laravel's scheduler.

For local development, run:

```bash
php artisan schedule:work
```

For production, run Laravel's scheduler every minute:

```cron
* * * * * cd /path/to/application && php artisan schedule:run >> /dev/null 2>&1
```

---

## 6. Queue Worker

Make sure a queue worker is running:

```bash
php artisan queue:work
```

Queue worker liveness monitoring uses heartbeat updates from scheduled queue heartbeat jobs and task execution jobs.

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
