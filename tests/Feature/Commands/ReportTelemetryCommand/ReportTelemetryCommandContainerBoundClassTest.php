<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tim661811\LaravelTelemetryReporter\Attributes\TelemetryData;

it('sends telemetry payload when a container-bound class has telemetry method', function () {
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

    app()->singleton(get_class($dummyClass), fn () => $dummyClass);

    Artisan::call('telemetry:report');

    Http::assertSent(function ($request) {
        return $request->url() === config('telemetry-reporter.server_url')
            && isset($request->data()['data']['test.key'])
            && $request->data()['data']['test.key']['value'] === 42;
    });
});

it('handles multiple telemetry methods in same class', function () {
    Http::fake();

    $dummyClass = new class
    {
        #[TelemetryData(interval: 0, key: 'test.first')]
        public function firstMetric(): array
        {
            return ['value' => 1];
        }

        #[TelemetryData(interval: 0, key: 'test.second')]
        public function secondMetric(): array
        {
            return ['value' => 2];
        }
    };

    app()->singleton(get_class($dummyClass), fn () => $dummyClass);

    Artisan::call('telemetry:report');

    Http::assertSent(function ($request) {
        $data = $request->data()['data'];

        return isset($data['test.first'])
            && isset($data['test.second'])
            && $data['test.first']['value'] === 1
            && $data['test.second']['value'] === 2;
    });
});
