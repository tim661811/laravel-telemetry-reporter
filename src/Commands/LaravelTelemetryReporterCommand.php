<?php

namespace Tim661811\LaravelTelemetryReporter\Commands;

use Composer\ClassMapGenerator\ClassMapGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use ReflectionClass;
use Throwable;
use Tim661811\LaravelTelemetryReporter\Attributes\TelemetryData;

class LaravelTelemetryReporterCommand extends Command
{
    protected $signature = 'telemetry:report';

    protected $description = 'Collect and send telemetry data to central server';

    public function handle(): int
    {
        if (! config('telemetry-reporter.enabled')) {
            return 0;
        }

        $host = config('telemetry-reporter.app_host', config('app.url'));
        $serverUrl = config('telemetry-reporter.server_url');
        $cacheStore = config('telemetry-reporter.cache_store');

        $payload = [
            'host' => $host,
            'timestamp' => now()->toIso8601ZuluString(),
            'data' => [],
        ];

        // First check all bound instances in the container
        foreach (app()->getBindings() as $abstract => $binding) {
            try {
                $instance = app()->make($abstract);
                $this->collectTelemetry($instance, $payload['data'], $cacheStore);
            } catch (Throwable $e) {
                // Skip if we can't instantiate
                continue;
            }
        }

        // Then check classes in app/ directory
        $appPath = App::path();
        $classMap = ClassMapGenerator::createMap($appPath);

        foreach ($classMap as $class => $file) {
            // 1) If PHP doesn’t know about this class, pull in the file directly
            if (! class_exists($class, false)) {
                @require_once $file;
            }

            // 2) Now skip anything still missing or not instantiable
            if (! class_exists($class) || ! (new ReflectionClass($class))->isInstantiable()) {
                continue;
            }

            // 3) Finally, resolve via the container and collect
            try {
                $object = App::make($class);
                $this->collectTelemetry($object, $payload['data'], $cacheStore);
            } catch (Throwable $e) {
                // swallow any errors and move on
                continue;
            }
        }

        dump($payload);
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
            if ($lastRun && Carbon::parse($lastRun)->addMinutes($config->interval)->gt($now)) {
                continue;
            }

            // Invoke and collect result
            $result = $method->invoke($object);
            $data[$key] = $result;

            // Update last-run timestamp
            Cache::store($cacheStore)
                ->put($cacheKey, $now->toIso8601ZuluString());
        }
    }
}
