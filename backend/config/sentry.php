<?php

return [
    'dsn' => env('SENTRY_LARAVEL_DSN'),
    'breadcrumbs' => [
        'logs' => true,
        'sql_bindings' => true,
        'sql_queries' => true,
        'queue_info' => true,
        'command_info' => true,
    ],
    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.1),
    'send_default_pii' => false,
    'environment' => env('APP_ENV', 'production'),
];
