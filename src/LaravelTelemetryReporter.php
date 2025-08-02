<?php

namespace Tim661811\LaravelTelemetryReporter;

use Illuminate\Http\Client\Response;
use Tim661811\LaravelTelemetryReporter\Helpers\TelemetryDataCollector;
use Tim661811\LaravelTelemetryReporter\Services\AuthTokenManager;
use Tim661811\LaravelTelemetryReporter\Services\TelemetrySender;

/**
 * Main telemetry reporter class providing convenient methods for telemetry operations.
 */
class LaravelTelemetryReporter
{
    public function __construct(
        protected TelemetryDataCollector $collector,
        protected TelemetrySender $sender,
        protected AuthTokenManager $authTokenManager
    ) {}

    /**
     * Check if telemetry reporting is enabled.
     */
    public function isEnabled(): bool
    {
        return (bool) config('telemetry-reporter.enabled', true);
    }

    /**
     * Get all registered telemetry definitions.
     *
     * @return array<int, array{class: string, method: string, key: string, interval: int}>
     */
    public function getDefinitions(): array
    {
        return $this->collector->listDefinitions();
    }

    /**
     * Collect telemetry data from all registered collectors.
     *
     * @return array<string, mixed>
     */
    public function collectData(): array
    {
        return $this->collector->collectData();
    }

    /**
     * Send telemetry data to the configured server.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $customHeaders
     */
    public function sendData(array $data, array $customHeaders = []): ?Response
    {
        $serverUrl = config('telemetry-reporter.server_url');
        $host = config('telemetry-reporter.app_host', config('app.url'));

        $payload = [
            'host' => $host,
            'timestamp' => now()->toIso8601ZuluString(),
            'data' => $data,
        ];

        return $this->sender->send($serverUrl, $payload, $customHeaders);
    }

    /**
     * Clear cached authentication token.
     */
    public function clearAuthToken(): void
    {
        $this->authTokenManager->clearToken();
    }

    /**
     * Get the current authentication token (cached or fresh).
     */
    public function getAuthToken(): ?string
    {
        return $this->authTokenManager->getToken();
    }
}
