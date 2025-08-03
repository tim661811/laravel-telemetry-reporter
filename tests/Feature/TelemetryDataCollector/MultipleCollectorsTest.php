<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tim661811\LaravelTelemetryReporter\Attributes\TelemetryData;
use Tim661811\LaravelTelemetryReporter\Helpers\TelemetryDataCollector;

it('collects data from multiple methods in single class', function () {
    Http::fake();

    $multiMethodClass = new class
    {
        #[TelemetryData(interval: 0, key: 'users.total')]
        public function getTotalUsers(): int
        {
            return 100;
        }

        #[TelemetryData(interval: 0, key: 'users.active')]
        public function getActiveUsers(): int
        {
            return 75;
        }

        #[TelemetryData(interval: 0, key: 'storage.used')]
        public function getStorageUsed(): float
        {
            return 1024.5;
        }
    };

    app()->singleton(get_class($multiMethodClass), fn () => $multiMethodClass);

    Artisan::call('telemetry:report');

    Http::assertSent(function ($request) {
        $data = $request->data()['data'];

        return isset($data['users.total']) && $data['users.total'] === 100 &&
               isset($data['users.active']) && $data['users.active'] === 75 &&
               isset($data['storage.used']) && $data['storage.used'] === 1024.5;
    });
});

it('collects data from multiple different classes', function () {
    Http::fake();

    $userService = new class
    {
        #[TelemetryData(interval: 0, key: 'service.users')]
        public function getUserCount(): int
        {
            return 50;
        }
    };

    $storageService = new class
    {
        #[TelemetryData(interval: 0, key: 'service.storage')]
        public function getStorageInfo(): array
        {
            return ['free' => 2048, 'used' => 1024];
        }
    };

    app()->singleton(get_class($userService), fn () => $userService);
    app()->singleton(get_class($storageService), fn () => $storageService);

    Artisan::call('telemetry:report');

    Http::assertSent(function ($request) {
        $data = $request->data()['data'];

        return isset($data['service.users']) && $data['service.users'] === 50 &&
               isset($data['service.storage']) && is_array($data['service.storage']);
    });
});

it('respects different intervals for different methods', function () {
    Http::fake();

    $mixedIntervalClass = new class
    {
        #[TelemetryData(interval: 0, key: 'immediate.data')]
        public function getImmediateData(): string
        {
            return 'immediate';
        }

        #[TelemetryData(interval: 3600, key: 'hourly.data')]
        public function getHourlyData(): string
        {
            return 'hourly';
        }
    };

    app()->singleton(get_class($mixedIntervalClass), fn () => $mixedIntervalClass);

    // First run - should collect immediate data but not hourly data
    Artisan::call('telemetry:report');

    // Should send at least one request with immediate data
    Http::assertSent(function ($request) {
        $data = $request->data()['data'];

        return isset($data['immediate.data']) && $data['immediate.data'] === 'immediate';
    });
});

it('lists multiple telemetry definitions correctly', function () {
    $multiMethodClass = new class
    {
        #[TelemetryData(interval: 60, key: 'method.one')]
        public function methodOne(): int
        {
            return 1;
        }

        #[TelemetryData(interval: 120, key: 'method.two')]
        public function methodTwo(): int
        {
            return 2;
        }
    };

    // Bind the class first
    app()->singleton(get_class($multiMethodClass), fn () => $multiMethodClass);

    // Then bind the collector to include test stubs path
    App::bind(TelemetryDataCollector::class, function () {
        return new TelemetryDataCollector([base_path('tests/Stubs')]);
    });

    Artisan::call('telemetry:list');

    $output = Artisan::output();

    expect($output)
        ->toContain('method.one')
        ->toContain('method.two')
        ->toContain('60')  // First method interval
        ->toContain('120'); // Second method interval
});

it('handles mixed return types from telemetry methods', function () {
    Http::fake();

    $mixedReturnClass = new class
    {
        #[TelemetryData(interval: 0, key: 'string.data')]
        public function getString(): string
        {
            return 'test string';
        }

        #[TelemetryData(interval: 0, key: 'int.data')]
        public function getInt(): int
        {
            return 42;
        }

        #[TelemetryData(interval: 0, key: 'array.data')]
        public function getArray(): array
        {
            return ['key' => 'value', 'number' => 123];
        }

        #[TelemetryData(interval: 0, key: 'bool.data')]
        public function getBool(): bool
        {
            return true;
        }
    };

    app()->singleton(get_class($mixedReturnClass), fn () => $mixedReturnClass);

    Artisan::call('telemetry:report');

    Http::assertSent(function ($request) {
        $data = $request->data()['data'];

        return isset($data['string.data']) && $data['string.data'] === 'test string' &&
               isset($data['int.data']) && $data['int.data'] === 42 &&
               isset($data['array.data']) && is_array($data['array.data']) &&
               isset($data['bool.data']) && $data['bool.data'] === true;
    });
});
