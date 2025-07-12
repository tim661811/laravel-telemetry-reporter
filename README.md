# Laravel telemetry reporter

[![Latest Version on Packagist](https://img.shields.io/packagist/v/tim661811/laravel-telemetry-reporter.svg?style=flat-square)](https://packagist.org/packages/tim661811/laravel-telemetry-reporter)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/tim661811/laravel-telemetry-reporter/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/tim661811/laravel-telemetry-reporter/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/tim661811/laravel-telemetry-reporter/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/tim661811/laravel-telemetry-reporter/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/tim661811/laravel-telemetry-reporter.svg?style=flat-square)](https://packagist.org/packages/tim661811/laravel-telemetry-reporter)

A reusable Laravel 10+ package that lets you annotate any service method with a PHP attribute to collect custom telemetry (e.g. user counts, disk usage, feature flags) and automatically report it—at
configurable intervals—to a central server over HTTP. Data is grouped per application host, is fully configurable via a published telemetry.php config (backed by your chosen cache), and integrates
seamlessly with Laravel’s scheduler and HTTP client.

## Roadmap

This package is currently in early development (version 0.x) and new features, improvements, and breaking changes may be introduced before the 1.0 stable release.

You can follow planned features, milestones, and upcoming enhancements in the [Roadmap](ROADMAP.md) file.

If you'd like to contribute ideas or help implement features, feel free to open an issue or pull request!

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

    // Optional URL to fetch authentication token from.
    // If set, the package will automatically fetch & cache the token before sending telemetry.
    'auth_token_url' => env('TELEMETRY_AUTH_TOKEN_URL'),

    // Optional bearer token used for authenticating telemetry requests.
    // Sent in the Authorization header as "Bearer <token>".
    'auth_token' => env('TELEMETRY_AUTH_TOKEN'),

    // Optional custom headers to include in every telemetry request.
    // You can set this statically here or parse it from an env var.
    // Example: ['X-My-Header' => 'value']
    'custom_headers' => [
        // 'X-My-Custom-Header' => 'value',
    ],

    // Optional signing of payloads to verify authenticity and integrity.
    // If enabled, a signature header will be sent using HMAC-SHA256.
    'signing' => [
        'enabled' => env('TELEMETRY_SIGNING_ENABLED', false),
        'key' => env('TELEMETRY_SIGNING_KEY'),
        'header' => 'X-Telemetry-Signature',
    ],
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

## Available Artisan Commands

### `telemetry:report` (alias: `telemetry:send`)

Collects telemetry data from all registered collectors and sends it to the configured central server over HTTP.

This command runs automatically by the package’s scheduler integration, but you can also run it manually for testing or immediate reporting:

```bash
php artisan telemetry:report
```

### `telemetry:list`

Lists all registered telemetry data collectors detected in your application, showing their:

* Telemetry key (custom or default generated)
* Class name
* Method name
* Reporting interval (in minutes)

Use this command to verify which telemetry methods are active and configured:

```bash
php artisan telemetry:list
```

Sample output:

| Key                                            | Class                      | Method                | Interval (minutes) |
|------------------------------------------------|----------------------------|-----------------------|--------------------|
| `user_count`                                   | `App\Services\UserService` | `getTotalUsers`       | 60                 |
| `storage_usage`                                | `App\Services\UserService` | `getDiskUsage`        | 1440               |
| `App\Services\UserService@getActiveUsersCount` | `App\Services\UserService` | `getActiveUsersCount` | 1440               |

## 🔐 Telemetry Payload Signing

To improve data integrity and authenticity, you can enable HMAC signing of telemetry payloads.

### How it works

* The Laravel telemetry client generates a SHA-256 HMAC signature of the JSON payload using a shared secret key.
* The signature is sent in a configurable HTTP header with each telemetry request.
* Your telemetry server verifies the signature to confirm the request is from a trusted source and has not been tampered with.

### Configuration

Add these to your `.env`:

```env
TELEMETRY_SIGNING_ENABLED=true
TELEMETRY_SIGNING_KEY=your-super-secret-key
TELEMETRY_SIGNING_HEADER=X-Telemetry-Signature
```

Or configure in `config/telemetry-reporter.php`:

```php
'signing' => [
    'enabled' => env('TELEMETRY_SIGNING_ENABLED', false),
    'key' => env('TELEMETRY_SIGNING_KEY'),
    'header' => env('TELEMETRY_SIGNING_HEADER', 'X-Telemetry-Signature'),
],
```

### Example Server-Side Verification using a Laravel middleware

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class VerifyTelemetrySignature
{
    public function handle(Request $request, Closure $next)
    {
        $sharedSecret = config('telemetry.signing_key');
        $signatureHeader = 'X-Telemetry-Signature';
        $providedSignature = $request->header($signatureHeader);

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $sharedSecret);

        if (! hash_equals($expectedSignature, $providedSignature)) {
            Log::warning('Invalid telemetry signature detected.', [
                'ip' => $request->ip(),
            ]);
            return response('Invalid signature', Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
```

### Notes

* Always use HTTPS to protect payload confidentiality in transit.
* Signing only ensures the data is untampered and from a trusted client.
* If signing is enabled but the key or header is not set, the telemetry client will skip signing and log a warning.
* You can customize the signature header name via configuration.

## 🔑 Automatic Authentication Token Fetching

This package supports an optional **automatic bearer token fetching mechanism** to simplify client authentication with your telemetry server.

### How it works

* Instead of manually configuring a static `auth_token`, you can specify an **authentication URL** (`auth_token_url`) in your config.
* When enabled, the telemetry reporter will **try fetching a token from this URL before sending telemetry**.
* The request to the auth endpoint sends the app host as JSON payload (e.g. `{ "host": "your-app-host" }`).
* If a token is received, it is cached and used in subsequent telemetry requests as the Bearer token.
* If no token is cached or fetching fails, the telemetry command stops and will try again on the next run.
* If the telemetry server responds with an authentication error (HTTP 401), the cached token is cleared, and telemetry data caches are invalidated to allow retry.

### Configuration

Add this to your `.env` or config file:

```env
# URL to fetch a fresh auth token
TELEMETRY_AUTH_TOKEN_URL=https://your-server.com/api/telemetry/auth-token

# You can still optionally configure a static token fallback here
TELEMETRY_AUTH_TOKEN=

# Existing options still work as normal
TELEMETRY_SERVER_URL=https://your-server.com/api/telemetry
```

In `config/telemetry-reporter.php`:

```php
'auth_token_url' => env('TELEMETRY_AUTH_TOKEN_URL', null),

'auth_token' => env('TELEMETRY_AUTH_TOKEN', null),
```

### Behavior

* If `auth_token_url` is set, the package will **try to fetch the token automatically before sending telemetry**.
* If `auth_token_url` is empty but `auth_token` is set, it will use the static token.
* If neither are set, telemetry requests will be sent **without authentication**.
* When a 401 Unauthorized response is received, the cached token and telemetry last-run caches are cleared for automatic retry.

### Server-side Expectations

Your auth endpoint should accept a POST request with JSON payload:

```json
{
    "host": "your-app-host"
}
```

And respond with JSON:

```json
{
    "token": "your-generated-bearer-token"
}
```

This allows your server to control and approve telemetry clients dynamically.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Tim van de Ven](https://github.com/tim661811)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
