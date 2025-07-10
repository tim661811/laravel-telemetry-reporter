<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // Ensure telemetry is enabled and the cache is fresh
    config()->set('telemetry-reporter.enabled', true);
    config()->set('telemetry-reporter.server_url', 'http://example.com/telemetry');
    Cache::flush();
});

it('does not send HTTP when no telemetry methods are present', function () {
    Http::fake();
    Artisan::call('telemetry:report');
    Http::assertNothingSent();
});

it('outputs telemetry payload when verbose logging enabled', function () {
    config()->set('telemetry-reporter.verbose_logging', true);

    Artisan::call('telemetry:report');

    $output = Artisan::output();

    expect($output)->toContain('Telemetry payload:')
        ->and($output)->toContain('"host"')
        ->and($output)->toContain('"data"');
});

it('does not outputs telemetry payload when verbose logging disabled', function () {
    Artisan::call('telemetry:report');

    $output = Artisan::output();

    expect($output)->not()->toContain('Telemetry payload:')
        ->and($output)->not()->toContain('"host"')
        ->and($output)->not()->toContain('"data"');
});
