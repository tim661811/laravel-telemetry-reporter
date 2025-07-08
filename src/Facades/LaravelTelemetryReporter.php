<?php

namespace Tim661811\LaravelTelemetryReporter\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Tim661811\LaravelTelemetryReporter\LaravelTelemetryReporter
 */
class LaravelTelemetryReporter extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Tim661811\LaravelTelemetryReporter\LaravelTelemetryReporter::class;
    }
}
