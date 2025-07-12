<?php

namespace Tim661811\LaravelTelemetryReporter\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;
use Tim661811\LaravelTelemetryReporter\Helpers\TelemetryHelper;
use Tim661811\LaravelTelemetryReporter\Helpers\TelemetryResponseProcessorHelper;
use Tim661811\LaravelTelemetryReporter\Services\AuthTokenManager;
use Tim661811\LaravelTelemetryReporter\Services\TelemetrySender;

class ReportTelemetryCommand extends Command
{
    protected $signature = 'telemetry:report';

    protected $aliases = ['telemetry:send'];

    protected $description = 'Collect, send telemetry data to central server, and process any response handlers';

    public function __construct(
        protected AuthTokenManager $authTokenManager,
        protected TelemetrySender $telemetrySender,
        protected TelemetryResponseProcessorHelper $responseProcessor,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! config('telemetry-reporter.enabled')) {
            $this->info('Telemetry disabled via config.');

            return 0;
        }

        $host = config('telemetry-reporter.app_host', config('app.url'));
        $serverUrl = config('telemetry-reporter.server_url');
        $customHeaders = config('telemetry-reporter.custom_headers', []);

        $collector = new TelemetryHelper;
        $payload = [
            'host' => $host,
            'timestamp' => now()->toIso8601ZuluString(),
            'data' => $collector->collectData(),
        ];

        if (config('telemetry-reporter.verbose_logging')) {
            $this->info('Telemetry payload:');
            $this->line(json_encode($payload, JSON_PRETTY_PRINT));
        }

        if (! count($payload['data'])) {
            $this->info('No telemetry data to send.');

            return 0;
        }

        // Send and get the response
        $response = $this->telemetrySender->send($serverUrl, $payload, $customHeaders);

        if ($response === null || ! $response->successful()) {
            $this->error('Failed to send telemetry data. See logs for details.');
            $this->clearTelemetryLastRunCache(array_keys($payload['data']));

            return 1;
        }

        $this->info("Telemetry posted to {$serverUrl}");

        // Process any response handlers if the server sent data
        try {
            $responseData = $response->json();
            if (is_array($responseData) && count($responseData)) {
                if (config('telemetry-reporter.verbose_logging')) {
                    $this->info('Telemetry response data:');
                    $this->line(json_encode($responseData, JSON_PRETTY_PRINT));
                }

                $this->responseProcessor->process($responseData);
                $this->info('Processed telemetry response handlers.');
            }
        } catch (Throwable $e) {
            $this->error('Failed to process telemetry response: '.$e->getMessage());
        }

        return 0;
    }

    protected function clearTelemetryLastRunCache(array $keys): void
    {
        foreach ($keys as $key) {
            $cacheKey = "laravel-telemetry-reporter:{$key}:last-run-time";
            Cache::store(config('telemetry-reporter.cache_store'))->forget($cacheKey);
        }
    }
}
