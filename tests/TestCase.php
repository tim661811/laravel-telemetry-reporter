<?php

namespace Tim661811\LaravelTelemetryReporter\Tests;

use Log;
use Mockery;
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

    protected function mockLogFacade(array $expectations = []): void
    {
        // Create channel mock with flexible expectations
        $logChannelMock = Mockery::mock();

        // Apply channel-specific expectations or defaults
        $channelExpectations = $expectations['channel'] ?? [];
        foreach (['warning', 'error', 'info', 'debug'] as $level) {
            if (isset($channelExpectations[$level])) {
                $logChannelMock->shouldReceive($level)->withArgs($channelExpectations[$level])->once();
            } else {
                $logChannelMock->shouldReceive($level)->andReturnSelf();
            }
        }

        Log::shouldReceive('channel')->andReturn($logChannelMock);

        // Apply direct Log expectations or defaults
        $directExpectations = $expectations['direct'] ?? [];
        foreach (['warning', 'error', 'info', 'debug'] as $level) {
            if (isset($directExpectations[$level])) {
                Log::shouldReceive($level)->withArgs($directExpectations[$level])->once();
            } else {
                Log::shouldReceive($level)->andReturnSelf();
            }
        }
    }
}
