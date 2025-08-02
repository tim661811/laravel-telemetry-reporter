<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Tim661811\LaravelTelemetryReporter\Attributes\TelemetryData;

it('handles missing server_url configuration gracefully', function () {
    config(['telemetry-reporter.server_url' => '']);

    Log::shouldReceive('error')->once()->withArgs(function ($message) {
        return str_contains($message, 'Telemetry server URL is not configured');
    });

    $dummyClass = new class
    {
        #[TelemetryData(interval: 60, key: 'test.data')]
        public function getData(): array
        {
            return ['test' => 'data'];
        }
    };

    app()->singleton(get_class($dummyClass), fn () => $dummyClass);

    Artisan::call('telemetry:report');

    expect(Artisan::output())->toContain('Failed to send telemetry data');
});

it('skips telemetry when disabled via config', function () {
    config(['telemetry-reporter.enabled' => false]);

    Artisan::call('telemetry:report');

    expect(Artisan::output())->toContain('Telemetry disabled via config');
});

it('validates telemetry interval cannot be negative', function () {
    expect(function () {
        new TelemetryData(interval: -60, key: 'test');
    })->toThrow(InvalidArgumentException::class, 'Telemetry interval cannot be negative');
});

it('allows zero interval for immediate execution', function () {
    $attribute = new TelemetryData(interval: 0, key: 'test');

    expect($attribute->interval)->toBe(0)
        ->and($attribute->isBelowSchedulerFrequency())->toBeTrue();
});

it('uses default interval when none specified', function () {
    config(['telemetry-reporter.default_interval' => 3600]);

    $attribute = new TelemetryData(key: 'test');

    expect($attribute->interval)->toBe(3600);
});

it('handles invalid cache store configuration', function () {
    config(['telemetry-reporter.cache_store' => 'non-existent-store']);

    $dummyClass = new class
    {
        #[TelemetryData(interval: 60, key: 'test.data')]
        public function getData(): array
        {
            return ['test' => 'data'];
        }
    };

    app()->singleton(get_class($dummyClass), fn () => $dummyClass);

    // Should not crash even with invalid cache store
    Artisan::call('telemetry:report');

    // Test passes if no exception is thrown
    expect(true)->toBeTrue();
});
