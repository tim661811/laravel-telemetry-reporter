<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tim661811\LaravelTelemetryReporter\Attributes\TelemetryData;

it('handles exceptions in telemetry methods gracefully', function () {
    Http::fake();

    Log::shouldReceive('warning')->once()->withArgs(function ($message) {
        return str_contains($message, 'Failed to collect data from exception.test');
    });

    $dummyClass = new class
    {
        #[TelemetryData(interval: 60, key: 'exception.test')]
        public function getFailingData(): array
        {
            throw new Exception('Simulated telemetry method failure');
        }
    };

    app()->singleton(get_class($dummyClass), fn () => $dummyClass);

    Artisan::call('telemetry:report');

    // Should not crash and should not send HTTP request due to exception
    Http::assertNothingSent();
});

it('continues collecting other telemetry data when one method fails', function () {
    Http::fake();

    Log::shouldReceive('warning')->once()->withArgs(function ($message) {
        return str_contains($message, 'Failed to collect data from failing.test');
    });

    $dummyClass = new class
    {
        #[TelemetryData(interval: 60, key: 'failing.test')]
        public function getFailingData(): array
        {
            throw new Exception('This method fails');
        }

        #[TelemetryData(interval: 60, key: 'working.test')]
        public function getWorkingData(): array
        {
            return ['status' => 'working'];
        }
    };

    app()->singleton(get_class($dummyClass), fn () => $dummyClass);

    Artisan::call('telemetry:report');

    // Should send HTTP request with working data
    Http::assertSent(function ($request) {
        $data = $request->data();

        return isset($data['data']['working.test']) &&
               ! isset($data['data']['failing.test']);
    });
});

it('handles non-serializable data gracefully', function () {
    Http::fake();

    Log::shouldReceive('warning')->once()->withArgs(function ($message) {
        return str_contains($message, 'Method non-serializable.test returned non-serializable data');
    });

    $dummyClass = new class
    {
        #[TelemetryData(interval: 60, key: 'non-serializable.test')]
        public function getNonSerializableData()
        {
            return fopen('php://temp', 'r'); // Resource cannot be serialized
        }
    };

    app()->singleton(get_class($dummyClass), fn () => $dummyClass);

    Artisan::call('telemetry:report');

    Http::assertNothingSent();
});
