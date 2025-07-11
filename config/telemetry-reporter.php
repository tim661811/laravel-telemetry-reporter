<?php

use Tim661811\LaravelTelemetryReporter\Enum\TelemetryInterval;

return [

    // Enable or disable telemetry reporting entirely.
    'enabled' => env('TELEMETRY_ENABLED', true),

    // The full URL for where to send telemetry data to.
    'server_url' => env('TELEMETRY_SERVER_URL', 'localhost'),

    // Unique identifier for this app instance, typically the app hostname.
    'app_host' => env('APP_HOST', 'localhost'),

    // Which cache store to use for tracking when telemetry methods last ran.
    'cache_store' => env('TELEMETRY_CACHE_STORE', 'file'),

    // Default interval for methods without an explicit #[TelemetryData(interval: ...)] attribute.
    'default_interval' => TelemetryInterval::OneDay->value,

    /*
     * Enable verbose logging of telemetry payloads before sending.
     * This will print the outgoing data to the console.
     * Useful for debugging and verifying payload content.
     */
    'verbose_logging' => env('TELEMETRY_VERBOSE_LOGGING', false),

    // Optional bearer token used for authenticating telemetry requests.
    // Sent in the Authorization header as "Bearer <token>".
    'auth_token' => env('TELEMETRY_AUTH_TOKEN'),

    // Optional custom headers to include in every telemetry request.
    // You can set this statically here or parse it from an env var.
    // Example: ['X-My-Header' => 'value']
    'custom_headers' => [
        // 'X-My-Custom-Header' => 'value',
    ],
];
