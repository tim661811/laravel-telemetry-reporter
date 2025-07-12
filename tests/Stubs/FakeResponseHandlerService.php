<?php

namespace Tim661811\LaravelTelemetryReporter\Tests\Stubs;

use Tim661811\LaravelTelemetryReporter\Attributes\TelemetryResponseHandler;

class FakeResponseHandlerService
{
    public static bool $firstCalled = false;

    public static mixed $firstData = null;

    #[TelemetryResponseHandler('foo')]
    public function handleFoo(array $data): void
    {
        self::$firstCalled = true;
        self::$firstData = $data;
    }

    public static bool $secondCalled = false;

    public static mixed $secondData = null;

    #[TelemetryResponseHandler('bar')]
    public function handleBar(string $message): void
    {
        self::$secondCalled = true;
        self::$secondData = $message;
    }

    public static bool $thirdCalled = false;

    public static mixed $thirdData = null;

    #[TelemetryResponseHandler('bar')]
    public function setMaintenanceMode(bool $on): void
    {
        self::$thirdCalled = true;
        self::$thirdData = $on;
    }
}
