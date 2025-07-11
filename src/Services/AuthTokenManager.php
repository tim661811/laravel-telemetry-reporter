<?php

namespace Tim661811\LaravelTelemetryReporter\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuthTokenManager
{
    protected string $cacheStore;

    public static string $CACHE_KEY = 'laravel-telemetry-reporter:auth_token';

    public function __construct()
    {
        $this->cacheStore = config('telemetry-reporter.cache_store');
    }

    public function getToken(): ?string
    {
        $authUrl = config('telemetry-reporter.auth_token_url');
        $staticToken = config('telemetry-reporter.auth_token');

        if (empty($authUrl)) {
            // No auth endpoint configured - use static token if any, or null
            return $staticToken ?: null;
        }

        $cached = Cache::store($this->cacheStore)->get(self::$CACHE_KEY, null);

        if ($cached) {
            return $cached;
        }

        return $this->fetchTokenFromServer();
    }

    protected function fetchTokenFromServer(): ?string
    {
        $authUrl = config('telemetry-reporter.auth_token_url');

        if (! $authUrl) {
            return null;
        }

        $host = config('telemetry-reporter.app_host', config('app.url'));

        try {
            $response = Http::post($authUrl, ['host' => $host]);

            $response->throw();
            if ($response->successful()) {
                $token = $response->json('token');
                if ($token) {
                    Cache::store($this->cacheStore)->put(self::$CACHE_KEY, $token);

                    return $token;
                }
                Log::warning('Auth endpoint did not return a token.');
            } else {
                Log::warning("Auth token request failed with status {$response->status()}");
            }
        } catch (Throwable $e) {
            Log::warning('Exception while fetching auth token: '.$e->getMessage());
            throw $e;
        }

        return null;
    }

    public function clearToken(): void
    {
        Cache::store($this->cacheStore)->forget(self::$CACHE_KEY);
    }
}
