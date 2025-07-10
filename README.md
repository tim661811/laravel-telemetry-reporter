# Laravel telemetry reporter

[![Latest Version on Packagist](https://img.shields.io/packagist/v/tim661811/laravel-telemetry-reporter.svg?style=flat-square)](https://packagist.org/packages/tim661811/laravel-telemetry-reporter)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/tim661811/laravel-telemetry-reporter/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/tim661811/laravel-telemetry-reporter/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/tim661811/laravel-telemetry-reporter/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/tim661811/laravel-telemetry-reporter/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/tim661811/laravel-telemetry-reporter.svg?style=flat-square)](https://packagist.org/packages/tim661811/laravel-telemetry-reporter)

A reusable Laravel 10+ package that lets you annotate any service method with a PHP attribute to collect custom telemetry (e.g. user counts, disk usage, feature flags) and automatically report it—at
configurable intervals—to a central server over HTTP. Data is grouped per application host, is fully configurable via a published telemetry.php config (backed by your chosen cache), and integrates
seamlessly with Laravel’s scheduler and HTTP client.

## Installation

You can install the package via composer:

```bash
composer require tim661811/laravel-telemetry-reporter
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="telemetry-reporter-config"
```

These are the contents of the published config file:

```php
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
];
```

## Usage

1. First, annotate your service methods with the telemetry attribute:

```php
namespace App\Services;

use Tim661811\LaravelTelemetryReporter\Attributes\TelemetryData;
use Tim661811\LaravelTelemetryReporter\Enum\TelemetryInterval;

class UserService
{
    #[TelemetryData(key: 'user_count', interval: TelemetryInterval::OneHour)]
    public function getTotalUsers(): int
    {
        return User::count();
    }

    #[TelemetryData(key: 'storage_usage')]
    public function getDiskUsage(): float
    {
        // This will use the default interval from config (One day by default)
        return Storage::size('uploads') / 1024 / 1024; // MB
    }

    #[TelemetryData]
    public function getActiveUsersCount(): int
    {
        // When no key is specified, the fully qualified class name and method name
        // are used as the key (e.g. 'App\Services\UserService@getActiveUsersCount')
        // This also uses the default interval from config (OneDay)
        return User::where('last_active_at', '>', now()->subDays(7))->count();
    }
}
```

2. That's it! The package automatically schedules the telemetry reporting command through its service provider. No additional configuration is needed for scheduling.

> **Important Note**: While you can specify any interval for your telemetry collectors, the minimum effective interval is 15 minutes (FifteenMinutes). This is because the scheduled command in the
> service provider runs every 15 minutes. Setting an interval lower than 900 seconds will still work, but data collection will only happen at 15-minute intervals at most.
>
> For convenience, the package provides a `TelemetryInterval` enum with commonly used time intervals (FifteenMinutes, ThirtyMinutes, OneHour, OneDay, etc.). You can use these values or specify your
> own custom interval in seconds.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Tim van de Ven](https://github.com/tim661811)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
