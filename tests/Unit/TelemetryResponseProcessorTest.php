<?php

use Illuminate\Support\Facades\App;
use Tim661811\LaravelTelemetryReporter\Helpers\TelemetryResponseProcessor;
use Tim661811\LaravelTelemetryReporter\Tests\Stubs\FakeResponseHandlerService;

beforeEach(function () {
    // Bind the response processor to scan our stubs directory
    App::bind(
        TelemetryResponseProcessor::class,
        fn () => new TelemetryResponseProcessor([
            base_path('tests/Stubs'),
        ])
    );

    // Bind our fake handler service located in Tests/Stubs
    App::bind(FakeResponseHandlerService::class, fn () => new FakeResponseHandlerService);
});

afterEach(function () {
    FakeResponseHandlerService::$firstCalled = false;
    FakeResponseHandlerService::$firstData = null;
    FakeResponseHandlerService::$secondCalled = false;
    FakeResponseHandlerService::$secondData = null;
});

it('invokes response handlers for matching keys', function () {
    $processor = App::make(TelemetryResponseProcessor::class);

    $response = [
        'foo' => ['x' => 123],
        'bar' => 'hello',
    ];

    $processor->process($response);

    expect(FakeResponseHandlerService::$firstCalled)->toBeTrue()
        ->and(FakeResponseHandlerService::$firstData)->toBe(['x' => 123])
        ->and(FakeResponseHandlerService::$secondCalled)->toBeTrue()
        ->and(FakeResponseHandlerService::$secondData)->toBe('hello');
});

it('does not invoke handlers when keys are missing', function () {
    $processor = App::make(TelemetryResponseProcessor::class);

    $response = ['baz' => 'ignored'];

    $processor->process($response);

    expect(FakeResponseHandlerService::$firstCalled)->toBeFalse()
        ->and(FakeResponseHandlerService::$secondCalled)->toBeFalse();
});
