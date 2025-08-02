<?php

namespace Tim661811\LaravelTelemetryReporter\Attributes;

use Attribute;
use InvalidArgumentException;

/**
 * Marks a method for telemetry data collection.
 *
 * Note: While you can specify any interval, the minimum effective interval when using
 * the automatic scheduler is 15 minutes (900 seconds) as the command runs every 15 minutes.
 * However, you can run the telemetry:report command manually for faster collection.
 *
 * @property int $interval Collection interval in seconds
 * @property string|null $key Unique key for the telemetry data point
 */
#[Attribute(Attribute::TARGET_METHOD)]
class TelemetryData
{
    public int $interval;

    public ?string $key;

    /**
     * @param  int|null  $interval  Collection interval in seconds (must be positive)
     * @param  string|null  $key  Unique key for the telemetry data point
     *
     * @throws InvalidArgumentException If interval is not positive
     */
    public function __construct(
        ?int $interval = null,
        ?string $key = null,
    ) {
        $this->interval = $interval ?? config('telemetry-reporter.default_interval', 86400);

        if ($this->interval < 0) {
            throw new InvalidArgumentException('Telemetry interval cannot be negative');
        }

        $this->key = $key;
    }

    /**
     * Get the interval in minutes for display purposes.
     */
    public function getIntervalInMinutes(): float
    {
        return $this->interval / 60;
    }

    /**
     * Check if this interval is below the automatic scheduler frequency.
     */
    public function isBelowSchedulerFrequency(): bool
    {
        return $this->interval < 900; // 15 minutes
    }
}
