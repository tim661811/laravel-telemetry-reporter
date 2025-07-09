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

    protected function getEnvironmentSetUp($app): void
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set up telemetry config
        $app['config']->set('telemetry-reporter.enabled', true);
        $app['config']->set('telemetry-reporter.server_url', 'http://example.com/api/telemetry');
        $app['config']->set('telemetry-reporter.app_host', 'test-host');
        $app['config']->set('telemetry-reporter.cache_store', 'array');
    }
}
