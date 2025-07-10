<?php

namespace Tim661811\LaravelTelemetryReporter;

use Illuminate\Console\Scheduling\Schedule;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Tim661811\LaravelTelemetryReporter\Commands\ListTelemetryDefinitionsCommand;
use Tim661811\LaravelTelemetryReporter\Commands\ReportTelemetryCommand;

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

    public function bootingPackage(): void
    {
        // (2) Scheduling belongs in `bootingPackage`, not inside configurePackage
        $this->app->booted(function () {
            $this->app->make(Schedule::class)
                ->command('telemetry:report')
                ->everyFifteenMinutes()     // internally gated by cache
                ->name('telemetry:report');
        });
    }
}
