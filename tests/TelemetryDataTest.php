<?php

use Tim661811\LaravelTelemetryReporter\Attributes\TelemetryData;
use Tim661811\LaravelTelemetryReporter\Enum\TelemetryInterval;

it('resolves enum value to seconds', function () {
    $attribute = new TelemetryData(interval: TelemetryInterval::ThreeHours->value);

    expect($attribute->interval)->toBe(10800); // 3 * 60 * 60
});

it('can be created with custom seconds', function () {
    $attribute = new TelemetryData(interval: 4500);

    expect($attribute->interval)->toBe(4500);
});
