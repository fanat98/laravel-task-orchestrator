<?php

declare(strict_types=1);

return [
    'route_prefix' => 'task-orchestrator',

    'middleware' => ['web', 'auth'],

    'authorization' => [
        'enabled' => true,

        /*
         * Supported modes:
         * - gate
         * - user_field
         */
        'mode' => 'gate',

        /*
         * Used when mode = gate
         */
        'gate' => 'viewTaskOrchestrator',

        /*
         * Used when mode = user_field
         * Example: is_admin
         * Supports dot notation via data_get()
         */
        'user_field' => 'is_admin',

        /*
         * Friendly message shown on 403
         */
        'forbidden_message' => 'You do not have permission to access Task Orchestrator.',
    ],

    'database_connection' => env('TASK_ORCHESTRATOR_DB_CONNECTION'),
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

    'scheduled_monitoring' => [
        'enabled' => true,
        'grace_minutes' => 20,
        'fail_command_on_missed' => true,
        'notifications' => [
            // Null means fallback to global task-orchestrator.notifications.enabled.
            'enabled' => null,
            // Empty list means fallback to global task-orchestrator.notifications.recipients.
            'recipients' => [],
        ],
    ],

    /*
     * Mail notification settings.
     * When enabled, the system sends emails on task failure and recovery.
     */
    'notifications' => [
        'enabled' => false,
        'recipients' => [],
    ],
];
