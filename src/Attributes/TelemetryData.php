<?php

namespace Tim661811\LaravelTelemetryReporter\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class TelemetryData
{
    public int $interval;

    public ?string $key;

    public function __construct(
        ?int $interval = null,
        ?string $key = null,
    ) {
        $this->interval = $interval ?? config('telemetry.default_interval');
        $this->key = $key;
    }
}
