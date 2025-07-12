<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tim661811\LaravelTelemetryReporter\Services\AuthTokenManager;
use Tim661811\LaravelTelemetryReporter\Services\TelemetrySender;

beforeEach(function () {
    Cache::flush();

    Config::set('telemetry-reporter.enabled', true);
    Config::set('telemetry-reporter.auth_token_url', 'https://localhost/api/auth-token');
    Config::set('telemetry-reporter.server_url', 'https://localhost/api/report');
    Config::set('telemetry-reporter.cache_store', 'file');

    // Reset signing config
    Config::set('telemetry-reporter.signing.enabled', false);
    Config::set('telemetry-reporter.signing.key', null);
    Config::set('telemetry-reporter.signing.header', null);
});

it('does not add signature header when signing is disabled', function () {
    Config::set('telemetry-reporter.signing.enabled', false);
    Config::set('telemetry-reporter.signing.key', 'dummy-key');
    Config::set('telemetry-reporter.signing.header', 'X-Signature');

    Http::fake([
        'https://localhost/api/auth-token' => Http::response(['token' => 'cached-token'], 200),
        'https://localhost/api/report' => Http::response([], 200),
    ]);

    $sender = new TelemetrySender(app(AuthTokenManager::class));
    $payload = ['data' => ['example-key' => ['value' => 'something']]];
    $sender->send('https://localhost/api/report', $payload);

    Http::assertSent(function ($request) {
        return ! $request->hasHeader('X-Signature');
    });
});

it('does not add signature header when signing key is missing', function () {
    Config::set('telemetry-reporter.signing.enabled', true);
    Config::set('telemetry-reporter.signing.key', null);
    Config::set('telemetry-reporter.signing.header', 'X-Signature');

    Http::fake([
        'https://localhost/api/auth-token' => Http::response(['token' => 'cached-token'], 200),
        'https://localhost/api/report' => Http::response([], 200),
    ]);

    $sender = new TelemetrySender(app(AuthTokenManager::class));
    $payload = ['data' => ['another-key' => ['value' => 'thing']]];
    $sender->send('https://localhost/api/report', $payload);

    Http::assertSent(function ($request) {
        return ! $request->hasHeader('X-Signature');
    });
});

it('does not add signature header when header name is missing', function () {
    Config::set('telemetry-reporter.signing.enabled', true);
    Config::set('telemetry-reporter.signing.key', 'my-secret');
    Config::set('telemetry-reporter.signing.header', null);

    Http::fake([
        'https://localhost/api/auth-token' => Http::response(['token' => 'cached-token'], 200),
        'https://localhost/api/report' => Http::response([], 200),
    ]);

    $sender = new TelemetrySender(app(AuthTokenManager::class));
    $payload = ['data' => ['keyX' => ['value' => 'x']]];
    $sender->send('https://localhost/api/report', $payload);

    Http::assertSent(function ($request) {
        return ! $request->hasHeader('X-Signature');
    });
});

it('adds correct signature header when signing is enabled and configured', function () {
    Config::set('telemetry-reporter.signing.enabled', true);
    Config::set('telemetry-reporter.signing.key', 'super-secret-key');
    Config::set('telemetry-reporter.signing.header', 'X-Telemetry-Signature');

    $payload = ['data' => ['sig-key' => ['value' => 'yes']]];
    $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $expectedSignature = hash_hmac('sha256', $jsonPayload, 'super-secret-key');

    Http::fake([
        'https://localhost/api/auth-token' => Http::response(['token' => 'cached-token'], 200),
        'https://localhost/api/report' => Http::response([], 200),
    ]);

    $sender = new TelemetrySender(app(AuthTokenManager::class));
    $sender->send('https://localhost/api/report', $payload);

    Http::assertSent(function ($request) use ($expectedSignature) {
        return $request->url() === 'https://localhost/api/report'
            && $request->hasHeader('X-Telemetry-Signature')
            && $request->header('X-Telemetry-Signature')[0] === $expectedSignature;
    });
});
