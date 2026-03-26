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
];
