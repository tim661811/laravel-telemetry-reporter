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
