<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Sentry DSN & Environment
    |--------------------------------------------------------------------------
    |
    | When SENTRY_LARAVEL_DSN is provided in the environment, uncaught
    | exceptions will be reported to Sentry with the configured environment,
    | release version, and tracing sample rates.
    |
    */

    'dsn' => env('SENTRY_LARAVEL_DSN'),

    'environment' => env('APP_ENV', 'production'),

    'release' => env('APP_VERSION', '1.0.0'),

    'sample_rate' => (float) env('SENTRY_SAMPLE_RATE', 1.0),

    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.2),

    'send_default_pii' => false,

    'breadcrumbs' => [
        'logs' => true,
        'sql_queries' => true,
        'sql_bindings' => false, // Do not send raw SQL bindings to protect sensitive data
        'queue_info' => true,
        'command_info' => true,
    ],
];
