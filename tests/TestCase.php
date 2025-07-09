<?php

namespace Tim661811\LaravelTelemetryReporter\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Tim661811\LaravelTelemetryReporter\LaravelTelemetryReporterServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelTelemetryReporterServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
