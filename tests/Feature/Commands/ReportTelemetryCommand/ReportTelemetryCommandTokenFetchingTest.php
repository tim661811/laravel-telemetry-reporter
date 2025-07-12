<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tim661811\LaravelTelemetryReporter\Helpers\TelemetryHelper;
use Tim661811\LaravelTelemetryReporter\Services\AuthTokenManager;
use Tim661811\LaravelTelemetryReporter\Tests\Stubs\FakeTelemetryCollectorWithoutKeyOrInterval;

beforeEach(function () {
    Config::set('telemetry-reporter.cache_store', 'file');
    Config::set('cache.default', config('telemetry-reporter.cache_store'));
    Config::set('telemetry-reporter.enabled', true);
    Config::set('telemetry-reporter.server_url', 'https://localhost/api/report');
    Config::set('telemetry-reporter.app_host', 'test-host');
    Config::set('telemetry-reporter.custom_headers', []);
    Config::set('telemetry-reporter.auth_token_url', 'https://localhost/api/auth-token');
    Config::set('telemetry-reporter.auth_token', null);

    Cache::store(config('telemetry-reporter.cache_store'))->flush();

    // Bind the stub collector so telemetry data exists
    App::bind(FakeTelemetryCollectorWithoutKeyOrInterval::class, fn () => new FakeTelemetryCollectorWithoutKeyOrInterval);

    // Bind TelemetryHelper to use your test stubs path
    App::bind(TelemetryHelper::class, function () {
        return new TelemetryHelper([
            base_path('tests/Stubs'),
        ]);
    });
});

it('fetches and caches the auth token before sending telemetry', function () {
    Http::fake([
        'https://localhost/api/auth-token' => Http::response(['token' => 'fetched-token'], 200),
        'https://localhost/api/report' => Http::response([], 200),
    ]);

    $exitCode = Artisan::call('telemetry:report');
    $output = Artisan::output();

    expect($output)
        ->toContain('Telemetry posted to https://localhost/api/report')
        ->and($exitCode)
        ->toBe(0)
        ->and(Cache::has(AuthTokenManager::$CACHE_KEY))->toBeTrue()
        ->and(Cache::get(AuthTokenManager::$CACHE_KEY))->toBe('fetched-token');

    // Exactly two HTTP calls: auth-token then report
    Http::assertSentCount(2);

    // 1) fetch token
    Http::assertSent(function ($request) {
        return $request->url() === 'https://localhost/api/auth-token';
    });

    // 2) send telemetry with fetched token
    Http::assertSent(function ($request) {
        return $request->url() === 'https://localhost/api/report'
            && $request->hasHeader('Authorization', 'Bearer fetched-token');
    });
});

it('uses cached auth token on subsequent runs without fetching again', function () {
    // Pre-seed cache so that the same token is “fetched” again
    Cache::put(AuthTokenManager::$CACHE_KEY, 'cached-token');

    Http::fake([
        'https://localhost/api/report' => Http::response([], 200),
    ]);

    $exitCode = Artisan::call('telemetry:report');
    $output = Artisan::output();

    expect($exitCode)->toBe(0)
        ->and($output)
        ->toContain('Telemetry posted to https://localhost/api/report');
    dump(Http::recorded());

    Http::assertSentCount(1);

    // auth-token was called (re‑using the cached token)
    Http::assertNotSent(function ($request) {
        return $request->url() === 'https://localhost/api/auth-token';
    });

    // report was called with the cached token
    Http::assertSent(function ($request) {
        return $request->url() === 'https://localhost/api/report'
            && $request->hasHeader('Authorization', 'Bearer cached-token');
    });
});

it('stops if auth token endpoint returns no token', function () {
    Http::fake([
        'https://localhost/api/auth-token' => Http::response([], 401),
    ]);

    $exitCode = Artisan::call('telemetry:report');
    $output = Artisan::output();

    expect($output)->toContain('Failed to send telemetry data. See logs for details.')
        ->and($exitCode)
        ->toBe(1);

    // Only the auth-token request should have been made
    Http::assertSentCount(1);
    Http::assertSent(function ($request) {
        return $request->url() === 'https://localhost/api/auth-token';
    });

    // Token must not be cached
    expect(Cache::has(AuthTokenManager::$CACHE_KEY))->toBeFalse();
});

it('clears cached token and telemetry caches on 401 Unauthorized response', function () {
    Cache::put(AuthTokenManager::$CACHE_KEY, 'cached-token');

    Http::fake([
        'https://localhost/api/report' => Http::response([], 401),
    ]);

    $exitCode = Artisan::call('telemetry:report');
    $output = Artisan::output();

    expect($output)->toContain('Failed to send telemetry data. See logs for details.')
        ->and($exitCode)
        ->toBe(1);

    Http::assertSentCount(1);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://localhost/api/report';
    });

    // Both caches should be cleared
    expect(Cache::has(AuthTokenManager::$CACHE_KEY))->toBeFalse()
        ->and(Cache::has('laravel-telemetry-reporter:Tim661811\LaravelTelemetryReporter\Tests\Stubs\FakeTelemetryCollectorWithoutKeyOrInterval@testMethod:last-run-time'))->toBeFalse();
});
