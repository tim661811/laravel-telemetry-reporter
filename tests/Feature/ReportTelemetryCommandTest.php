<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tim661811\LaravelTelemetryReporter\Attributes\TelemetryData;

beforeEach(function () {
    // Ensure telemetry is enabled and the cache is fresh
    config()->set('telemetry.enabled', true);
    config()->set('telemetry.server_url', 'http://example.com/telemetry'); // Set a test URL
    Cache::flush();
});

it('does not send HTTP when no telemetry methods are present', function () {
    Http::fake();

    Artisan::call('telemetry:report');

    Http::assertNothingSent();
});

it('sends telemetry payload when a telemetry method is present and due', function () {
    Http::fake();

    // Create a dummy service with a zero-interval telemetry method
    $dummyClass = new class
    {
        #[TelemetryData(interval: 0, key: 'test.key')]
        public function sample(): array
        {
            return ['value' => 42];
        }
    };

    // Bind the dummy into the container with a concrete class name
    $className = get_class($dummyClass);
    app()->instance($className, $dummyClass);

    // Register the class in the container so it can be discovered
    app()->bind($className, fn () => $dummyClass);

    Artisan::call('telemetry:report');

    Http::assertSent(function ($request) {
        return $request->url() === config('telemetry.server_url')
            && isset($request->data()['data']['test.key'])
            && $request->data()['data']['test.key']['value'] === 42;
    });
});
