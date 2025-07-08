<?php

namespace Tim661811\LaravelTelemetryReporter\Attributes;

use Attribute;
use Carbon\CarbonInterval;

#[Attribute(Attribute::TARGET_METHOD)]
class TelemetryData
{
    public CarbonInterval $interval;

    public ?string $key;

    public function __construct(?CarbonInterval $interval = null, ?string $key = null)
    {
        $this->interval = $interval ?? config('telemetry.default_interval');
        $this->key = $key;
    }
}
