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
        dump('called handleFoo');
        self::$firstCalled = true;
        self::$firstData = $data;
    }

    public static bool $secondCalled = false;

    public static mixed $secondData = null;

    #[TelemetryResponseHandler('bar')]
    public function handleBar(string $message): void
    {
        dump('called handleBar');
        self::$secondCalled = true;
        self::$secondData = $message;
    }
}
