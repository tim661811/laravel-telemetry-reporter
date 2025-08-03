<?php

namespace Tim661811\LaravelTelemetryReporter;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Throwable;
use Tim661811\LaravelTelemetryReporter\Commands\ListTelemetryDefinitionsCommand;
use Tim661811\LaravelTelemetryReporter\Commands\ReportTelemetryCommand;
use Tim661811\LaravelTelemetryReporter\Helpers\TelemetryDataCollector;
use Tim661811\LaravelTelemetryReporter\Helpers\TelemetryResponseProcessor;
use Tim661811\LaravelTelemetryReporter\Services\AuthTokenManager;
use Tim661811\LaravelTelemetryReporter\Services\TelemetrySender;

class LaravelTelemetryReporterServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-telemetry-reporter')
            ->hasConfigFile()
            ->hasCommand(ReportTelemetryCommand::class)
            ->hasCommand(ListTelemetryDefinitionsCommand::class);
    }

    public function registeringPackage(): void
    {
        // Register core services
        $this->app->singleton(AuthTokenManager::class);
        $this->app->singleton(TelemetrySender::class);
        $this->app->singleton(TelemetryDataCollector::class);
        $this->app->singleton(TelemetryResponseProcessor::class);

        // Register main class
        $this->app->singleton(LaravelTelemetryReporter::class);
    }

    public function bootingPackage(): void
    {
        // Only schedule if telemetry is enabled
        $this->app->booted(function () {
            if (! config('telemetry-reporter.enabled', true)) {
                return;
            }

            try {
                $this->app->make(Schedule::class)
                    ->command('telemetry:report')
                    ->everyFifteenMinutes()
                    ->name('telemetry:report')
                    ->runInBackground()
                    ->withoutOverlapping()
                    ->onFailure(function () {
                        Log::warning('Telemetry report command failed.');
                    });
            } catch (Throwable $e) {
                Log::error('Failed to schedule telemetry report command: '.$e->getMessage());
            }
        });
    }
}
