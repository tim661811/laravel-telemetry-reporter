<?php

namespace Tim661811\LaravelTelemetryReporter\Tests\Stubs;

use Tim661811\LaravelTelemetryReporter\Attributes\TelemetryData;

class FakeTelemetryCollectorWithoutKeyOrInterval
{
    #[TelemetryData]
    public function testMethod(): string
    {
        return 'default values';
    }
}
