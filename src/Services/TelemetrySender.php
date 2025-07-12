<?php

namespace Tim661811\LaravelTelemetryReporter\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TelemetrySender
{
    protected string $cacheStore;

    public function __construct(protected AuthTokenManager $authTokenManager)
    {
        $this->cacheStore = config('telemetry-reporter.cache_store');
    }

    /**
     * Send telemetry and return the HTTP response, or null on failure.
     */
    public function send(string $url, array $payload, array $customHeaders = []): ?\Illuminate\Http\Client\Response
    {
        $headers = ['Accept' => 'application/json'];

        try {
            $token = $this->authTokenManager->getToken();
        } catch (Throwable $e) {
            return null;
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

                return null;
            }

            $response->throw();

            return $response;
        } catch (Throwable $e) {
            Log::error('Failed to post telemetry: '.$e->getMessage());

            return null;
        }
    }

    protected function addSignatureHeaderWhenSigningIsEnabled(array &$headers, array $payload): void
    {
        $enabled = config('telemetry-reporter.signing.enabled', false);
        if (! $enabled) {
            return;
        }

        $key = config('telemetry-reporter.signing.key');
        $header = config('telemetry-reporter.signing.header');

        if (empty($key) || empty($header)) {
            Log::warning('Signing enabled but key/header missing.');

            return;
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $headers[$header] = hash_hmac('sha256', $json, $key);
    }
}
