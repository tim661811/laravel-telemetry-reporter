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
     *
     * @param  string  $url  The telemetry server URL
     * @param  array<string, mixed>  $payload  The telemetry data payload
     * @param  array<string, string>  $customHeaders  Additional headers to include
     */
    public function send(string $url, array $payload, array $customHeaders = []): ?\Illuminate\Http\Client\Response
    {
        if (empty($url)) {
            Log::error('Telemetry server URL is not configured');

            return null;
        }

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => 'Laravel-Telemetry-Reporter/0.4',
        ];

        try {
            $token = $this->authTokenManager->getToken();
        } catch (Throwable $e) {
            Log::warning('Failed to get authentication token: '.$e->getMessage());

            return null;
        }

        if ($token) {
            $headers['Authorization'] = 'Bearer '.$token;
        }

        $headers = array_merge($headers, $customHeaders);
        $this->addSignatureHeaderWhenSigningIsEnabled($headers, $payload);

        try {
            $response = Http::timeout(30)
                ->withHeaders($headers)
                ->post($url, $payload);

            if ($response->status() === Response::HTTP_UNAUTHORIZED) {
                Log::info('Telemetry server returned 401 Unauthorized. Clearing cached token.');
                $this->authTokenManager->clearToken();

                return null;
            }

            if (! $response->successful()) {
                Log::warning('Telemetry server returned non-successful status: '.$response->status());
            }

            return $response;
        } catch (Throwable $e) {
            Log::error('Failed to post telemetry: '.$e->getMessage(), [
                'url' => $url,
                'exception' => get_class($e),
            ]);

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
