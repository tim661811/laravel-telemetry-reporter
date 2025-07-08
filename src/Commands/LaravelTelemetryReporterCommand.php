<?php

namespace Tim661811\LaravelTelemetryReporter\Commands;

use Carbon\Carbon;
use Composer\Autoload\ClassMapGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use ReflectionClass;
use Tim661811\LaravelTelemetryReporter\Attributes\TelemetryData;

class LaravelTelemetryReporterCommand extends Command
{
    protected $signature = 'telemetry:report';

    protected $description = 'Collect and send telemetry data to central server';

    public function handle(): int
    {
        if (! config('telemetry.enabled')) {
            return 0;
        }

        $host = config('telemetry.app_host', config('app.url'));
        $serverUrl = config('telemetry.server_url');
        $cacheStore = config('telemetry.cache_store', null);

        $payload = [
            'host' => $host,
            'timestamp' => now()->toIso8601ZuluString(),
            'data' => [],
        ];

        // Auto‑discover all classes in app/ via Composer’s class map
        $appPath = App::path();
        $classMap = ClassMapGenerator::createMap($appPath);

        foreach ($classMap as $class => $file) {
            if (! class_exists($class)) {
                continue;
            }
            $ref = new ReflectionClass($class);
            if (! $ref->isInstantiable()) {
                continue;
            }

            $object = App::make($class);
            $this->collectTelemetry($object, $payload['data'], $cacheStore);
        }

        if (count($payload['data'])) {
            Http::post($serverUrl, $payload);
            $this->info("Telemetry posted to {$serverUrl}");
        }

        return 0;
    }

    protected function collectTelemetry($object, array &$data, $cacheStore): void
    {
        $reflect = new ReflectionClass($object);

        foreach ($reflect->getMethods() as $method) {
            $attrs = $method->getAttributes(TelemetryData::class);
            if (! count($attrs)) {
                continue;
            }

            /** @var TelemetryData $config */
            $config = $attrs[0]->newInstance();
            $key = $config->key
                ?? $reflect->getName().'@'.$method->getName();
            $cacheKey = "telemetry:last:{$key}";

            $lastRun = Cache::store($cacheStore)->get($cacheKey);
            $now = now();

            // Only run if interval has elapsed
            if ($lastRun && Carbon::parse($lastRun)->add($config->interval)->gt($now)) {
                continue;
            }

            // Invoke and collect result
            $result = $method->invoke($object);
            $data[$key] = $result;

            // Update last‐run timestamp
            Cache::store($cacheStore)
                ->put($cacheKey, $now->toIso8601ZuluString());
        }
    }
}
