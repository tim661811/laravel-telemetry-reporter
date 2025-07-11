<?php

use Tim661811\LaravelTelemetryReporter\Enum\TelemetryInterval;

return [
    'enabled' => env('TELEMETRY_ENABLED', true),
    'server_url' => env('TELEMETRY_SERVER_URL', 'localhost'),
    'app_host' => env('APP_HOST', 'localhost'),

    // Which cache store to use for last-run timestamps (null = default)
    'cache_store' => env('TELEMETRY_CACHE_STORE', 'file'),

    // Default interval for methods without an explicit interval
    'default_interval' => TelemetryInterval::OneDay->value,

    /*
     * Enable verbose logging of telemetry payloads before sending.
     * Useful for debugging and verifying payload content.
     */
    'verbose_logging' => env('TELEMETRY_VERBOSE_LOGGING', false),

    'auth_token' => env('TELEMETRY_AUTH_TOKEN'),
    'custom_headers' => [
        // 'X-My-Custom-Header' => 'value',
    ],
];
