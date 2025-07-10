<?php

namespace Tim661811\LaravelTelemetryReporter\Tests\Stubs;

use Tim661811\LaravelTelemetryReporter\Attributes\TelemetryData;

class FakeTelemetryCollector
{
    #[TelemetryData(interval: 123, key: 'test.key')]
    public function testMethod(): string
    {
        return 'example-data';
    }
}
