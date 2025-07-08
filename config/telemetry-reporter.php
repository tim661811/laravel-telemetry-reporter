<?php

use Carbon\CarbonInterval;

return [
    'enabled' => env('TELEMETRY_ENABLED', true),
    'server_url' => env('TELEMETRY_SERVER_URL'),
    'app_host' => env('APP_HOST', 'localhost'),

    // How often the artisan command may fire (in minutes)
    'command_interval_minutes' => env('TELEMETRY_COMMAND_INTERVAL', 60),

    // Which cache store to use for last-run timestamps (null = default)
    'cache_store' => env('TELEMETRY_CACHE_STORE', null),

    // Default interval for methods without an explicit interval
    'default_interval' => CarbonInterval::hours(24),
];
