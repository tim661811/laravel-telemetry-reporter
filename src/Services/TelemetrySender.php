<?php

namespace Tim661811\LaravelTelemetryReporter\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TelemetrySender
{
    protected AuthTokenManager $authTokenManager;

    protected string $cacheStore;

    public function __construct(AuthTokenManager $authTokenManager)
    {
        $this->authTokenManager = $authTokenManager;
        $this->cacheStore = config('telemetry-reporter.cache_store');
    }

    public function send(string $url, array $payload, array $customHeaders = []): bool
    {
        $headers = ['Accept' => 'application/json'];
        try {
            $token = $this->authTokenManager->getToken();
        } catch (Throwable $e) {
            return false;
        }

        if ($token) {
            $headers['Authorization'] = 'Bearer '.$token;
        }

        $headers = array_merge($headers, $customHeaders);

        $this->addSignatureHeaderWhenSigningIsEnabled($headers, $payload);

        try {
            $response = Http::withHeaders($headers)->post($url, $payload);

            if ($response->status() === Response::HTTP_UNAUTHORIZED) {
                Log::info('Telemetry server returned 401 Unauthorized. Clearing cached token and telemetry caches.');

                $this->authTokenManager->clearToken();
                $this->clearTelemetryLastRunCache(array_keys($payload['data']));

                return false;
            }

            $response->throw();

            return true;
        } catch (Throwable $e) {
            Log::error('Failed to post telemetry: '.$e->getMessage());

            return false;
        }
    }

    protected function clearTelemetryLastRunCache(array $keys): void
    {
        foreach ($keys as $key) {
            $cacheKey = "laravel-telemetry-reporter:{$key}:last-run-time";
            Cache::store($this->cacheStore)->forget($cacheKey);
        }
    }

    protected function addSignatureHeaderWhenSigningIsEnabled(array &$headers, array $payload): void
    {
        $enabled = config('telemetry-reporter.signing.enabled', false);
        $key = config('telemetry-reporter.signing.key');
        $header = config('telemetry-reporter.signing.header');

        if (! $enabled) {
            return;
        }

        if (empty($key)) {
            Log::warning('Telemetry signing is enabled but no signing key is provided. Signing skipped.');

            return;
        }

        if (empty($header)) {
            Log::warning('Telemetry signing is enabled but no header name is provided. Signing skipped.');

            return;
        }

        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', $payloadJson, $key);
        $headers[$header] = $signature;
    }
}
