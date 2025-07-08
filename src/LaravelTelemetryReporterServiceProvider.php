<?php

namespace Tim661811\LaravelTelemetryReporter;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Tim661811\LaravelTelemetryReporter\Commands\LaravelTelemetryReporterCommand;

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
            ->hasViews()
            ->hasMigration('create_laravel_telemetry_reporter_table')
            ->hasCommand(LaravelTelemetryReporterCommand::class);
    }
}
