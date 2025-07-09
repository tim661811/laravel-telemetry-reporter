<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tim661811\LaravelTelemetryReporter\Attributes\TelemetryData;

it('respects telemetry intervals', function () {
    Http::fake();

    // Create a class with a 60-second interval
    $dummyClass = new class
    {
        #[TelemetryData(interval: 60, key: 'interval.test')]
        public function getIntervalData(): array
        {
            return ['count' => 1];
        }
    };

    app()->singleton(get_class($dummyClass), fn () => $dummyClass);

    // The first call should send data
    Artisan::call('telemetry:report');

    Http::assertSent(function ($request) {
        return isset($request->data()['data']['interval.test'])
            && $request->data()['data']['interval.test']['count'] === 1;
    });

    Http::fake(); // Reset fake

    // The second immediate call should not send data
    Artisan::call('telemetry:report');

    Http::assertNothingSent();
});
