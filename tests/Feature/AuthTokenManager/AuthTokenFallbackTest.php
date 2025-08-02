<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tim661811\LaravelTelemetryReporter\Services\AuthTokenManager;

it('uses static token when auth_token_url is not configured', function () {
    config(['telemetry-reporter.auth_token_url' => null]);
    config(['telemetry-reporter.auth_token' => 'static-test-token']);

    $authManager = new AuthTokenManager;
    $token = $authManager->getToken();

    expect($token)->toBe('static-test-token');
});

it('returns null when neither auth_token_url nor auth_token are configured', function () {
    config(['telemetry-reporter.auth_token_url' => null]);
    config(['telemetry-reporter.auth_token' => null]);

    $authManager = new AuthTokenManager;
    $token = $authManager->getToken();

    expect($token)->toBeNull();
});

it('returns cached token without making HTTP request', function () {
    config(['telemetry-reporter.auth_token_url' => 'https://auth.example.com/token']);
    config(['telemetry-reporter.cache_store' => 'array']);

    // Put token in cache first
    Cache::store('array')->put(AuthTokenManager::$CACHE_KEY, 'cached-token');

    $authManager = new AuthTokenManager;
    $token = $authManager->getToken();

    expect($token)->toBe('cached-token');
});

it('handles auth token fetch failure gracefully', function () {
    Http::fake([
        'https://auth.example.com/token' => Http::response('Server Error', 500),
    ]);

    config(['telemetry-reporter.auth_token_url' => 'https://auth.example.com/token']);
    config(['telemetry-reporter.app_host' => 'test-host']);
    config(['telemetry-reporter.cache_store' => 'array']);

    $authManager = new AuthTokenManager;

    expect(fn () => $authManager->getToken())->toThrow(Exception::class);
});

it('clears cached token when requested', function () {
    config(['telemetry-reporter.cache_store' => 'array']);

    // Put token in cache first
    Cache::store('array')->put(AuthTokenManager::$CACHE_KEY, 'test-token');

    $authManager = new AuthTokenManager;
    $authManager->clearToken();

    // Verify token is cleared
    $token = Cache::store('array')->get(AuthTokenManager::$CACHE_KEY);
    expect($token)->toBeNull();
});
