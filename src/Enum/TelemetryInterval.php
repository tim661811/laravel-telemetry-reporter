<?php

namespace Tim661811\LaravelTelemetryReporter\Enum;

enum TelemetryInterval: int
{
    case FifteenMinutes = 900;
    case ThirtyMinutes = 1800;
    case FortyFiveMinutes = 2700;
    case OneHour = 3600;
    case TwoHours = 7200;
    case ThreeHours = 10800;
    case SixHours = 21600;
    case TwelveHours = 43200;
    case OneDay = 86400;
    case TwoDays = 172800;
    case ThreeDays = 259200;
    case OneWeek = 604800;

    public function label(): string
    {
        return match ($this) {
            self::FifteenMinutes => '15 minutes',
            self::ThirtyMinutes => '30 minutes',
            self::FortyFiveMinutes => '45 minutes',
            self::OneHour => '1 hour',
            self::TwoHours => '2 hours',
            self::ThreeHours => '3 hours',
            self::SixHours => '6 hours',
            self::TwelveHours => '12 hours',
            self::OneDay => '1 day',
            self::TwoDays => '2 days',
            self::ThreeDays => '3 days',
            self::OneWeek => '1 week',
        };
    }
}
