<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Tim661811\LaravelTelemetryReporter\Helpers\TelemetryDataCollector;
use Tim661811\LaravelTelemetryReporter\Tests\Stubs\FakeTelemetryCollector;
use Tim661811\LaravelTelemetryReporter\Tests\Stubs\FakeTelemetryCollectorWithoutKeyOrInterval;

it('lists registered telemetry data collectors', function () {
    App::bind(FakeTelemetryCollector::class, fn () => new FakeTelemetryCollector);

    App::bind(TelemetryDataCollector::class, function () {
        return new TelemetryDataCollector([
            base_path('tests/Stubs'), // add the test stub path
        ]);
    });

    Artisan::call('telemetry:list');

    expect(Artisan::output())
        ->toContain('test.key')
        ->toContain(FakeTelemetryCollector::class)
        ->toContain('testMethod')
        ->toContain('123');
});

it('handles telemetry methods without custom key or interval', function () {
    App::bind(FakeTelemetryCollectorWithoutKeyOrInterval::class, fn () => new FakeTelemetryCollectorWithoutKeyOrInterval);

    App::bind(TelemetryDataCollector::class, function () {
        return new TelemetryDataCollector([
            base_path('tests/Stubs'),
        ]);
    });

    Artisan::call('telemetry:list');
    $output = Artisan::output();

    expect($output)->toContain('FakeTelemetryCollectorWithoutKeyOrInterval@testMethod')
        ->and($output)->toContain(config('telemetry-reporter.default_interval'));
});

it('shows a message when no telemetry collectors are found', function () {
    Artisan::call('telemetry:list');

    expect(Artisan::output())->toContain('No telemetry data collectors found.');
});
