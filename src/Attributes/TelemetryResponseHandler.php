<?php

namespace Tim661811\LaravelTelemetryReporter\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class TelemetryResponseHandler
{
    public function __construct(
        public ?string $key = null,
    ) {}
}
