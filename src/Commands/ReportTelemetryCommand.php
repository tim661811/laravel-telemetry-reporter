<?php

namespace Tim661811\LaravelTelemetryReporter\Commands;

use Illuminate\Console\Command;
use Tim661811\LaravelTelemetryReporter\Helpers\TelemetryHelper;
use Tim661811\LaravelTelemetryReporter\Services\AuthTokenManager;
use Tim661811\LaravelTelemetryReporter\Services\TelemetrySender;

class ReportTelemetryCommand extends Command
{
    protected $signature = 'telemetry:report';

    protected $aliases = ['telemetry:send'];

    protected $description = 'Collect and send telemetry data to central server';

    protected AuthTokenManager $authTokenManager;

    protected TelemetrySender $telemetrySender;

    public function __construct(AuthTokenManager $authTokenManager, TelemetrySender $telemetrySender)
    {
        parent::__construct();

        $this->authTokenManager = $authTokenManager;
        $this->telemetrySender = $telemetrySender;
    }

    public function handle(): int
    {
        if (! config('telemetry-reporter.enabled')) {
            $this->info('Sending telemetry is disabled via config (telemetry-reporter.enabled = false).');

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

        if (! $this->telemetrySender->send($serverUrl, $payload, $customHeaders)) {
            $this->error('Failed to send telemetry data. See logs for details.');

            return 1;
        }

        $this->info("Telemetry posted to {$serverUrl}");

        return 0;
    }
}
