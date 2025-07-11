<?php

namespace Tim661811\LaravelTelemetryReporter\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;
use Tim661811\LaravelTelemetryReporter\Helpers\TelemetryHelper;

class ReportTelemetryCommand extends Command
{
    protected $signature = 'telemetry:report';

    protected $aliases = ['telemetry:send'];

    protected $description = 'Collect and send telemetry data to central server';

    public function handle(): int
    {
        if (! config('telemetry-reporter.enabled')) {
            return 0;
        }

        $host = config('telemetry-reporter.app_host', config('app.url'));
        $serverUrl = config('telemetry-reporter.server_url');
        $authToken = config('telemetry-reporter.auth_token');
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

        if (count($payload['data'])) {
            try {
                $headers = [
                    'Accept' => 'application/json',
                ];

                if ($authToken) {
                    $headers['Authorization'] = 'Bearer '.$authToken;
                }
                $headers = array_merge($headers, $customHeaders);

                Http::withHeaders($headers)->post($serverUrl, $payload);

                $this->info("Telemetry posted to {$serverUrl}");
            } catch (Throwable $e) {
                $this->error("Failed to post telemetry: {$e->getMessage()}");

                return 1;
            }
        }

        return 0;
    }
}
