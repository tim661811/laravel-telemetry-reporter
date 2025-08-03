<?php

namespace Tim661811\LaravelTelemetryReporter\Helpers;

use Composer\ClassMapGenerator\ClassMapGenerator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use JsonException;
use ReflectionClass;
use Throwable;
use Tim661811\LaravelTelemetryReporter\Attributes\TelemetryData;

class TelemetryDataCollector
{
    protected string $cacheStore;

    /**
     * @var array|string[] Directories to scan for telemetry classes
     */
    private array $paths;

    public function __construct(array $extraPaths = [])
    {
        $this->cacheStore = config('telemetry-reporter.cache_store');
        $this->paths = array_merge([App::path()], $extraPaths);
    }

    public function collect(callable $callback): void
    {
        // 1. Bound instances in Laravel container
        foreach (app()->getBindings() as $abstract => $binding) {
            try {
                $instance = app()->make($abstract);
                $this->inspect($instance, $callback);
            } catch (Throwable) {
                continue;
            }
        }

        // 2. Classes from the specified paths (including test stubs)
        foreach ($this->paths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            $classMap = ClassMapGenerator::createMap($path);

            foreach ($classMap as $class => $file) {
                if (! class_exists($class, false)) {
                    @require_once $file;
                }

                if (! class_exists($class) || ! (new ReflectionClass($class))->isInstantiable()) {
                    continue;
                }

                try {
                    $object = App::make($class);
                    $this->inspect($object, $callback);
                } catch (Throwable) {
                    continue;
                }
            }
        }
    }

    protected function inspect(object $object, callable $callback): void
    {
        $reflection = new ReflectionClass($object);

        foreach ($reflection->getMethods() as $method) {
            $attributes = $method->getAttributes(TelemetryData::class);

            if (count($attributes) === 0) {
                continue;
            }

            /** @var TelemetryData $config */
            $config = $attributes[0]->newInstance();
            $key = $config->key ?? $reflection->getName().'@'.$method->getName();
            $interval = $config->interval;

            $callback($object, $method->getName(), $key, $interval);
        }
    }

    /**
     * Collect telemetry data from all registered methods that are due for collection.
     *
     * @return array<string, mixed>
     */
    public function collectData(): array
    {
        $data = [];
        $now = now();
        $errors = [];

        $this->collect(function ($object, $method, $key, $interval) use (&$data, &$errors, $now) {
            $cacheKey = "laravel-telemetry-reporter:{$key}:last-run-time";

            try {
                $lastRun = Cache::store($this->cacheStore)->get($cacheKey);

                if ($lastRun && Carbon::parse($lastRun)->addSeconds($interval)->gt($now)) {
                    return;
                }

                $result = $object->{$method}();

                // Validate the result is serializable
                if (! $this->isSerializable($result)) {
                    $errors[] = "Method {$key} returned non-serializable data";

                    return;
                }

                $data[$key] = $result;
                Cache::store($this->cacheStore)->forever($cacheKey, $now->toIso8601ZuluString());

            } catch (Throwable $e) {
                $errors[] = "Failed to collect data from {$key}: ".$e->getMessage();
            }
        });

        if (! empty($errors)) {
            foreach ($errors as $error) {
                Log::warning($error);
            }
        }

        return $data;
    }

    /**
     * Check if a value can be safely serialized to JSON.
     */
    protected function isSerializable(mixed $value): bool
    {
        try {
            json_encode($value, JSON_THROW_ON_ERROR);

            return true;
        } catch (JsonException) {
            return false;
        }
    }

    public function listDefinitions(): array
    {
        $definitions = [];

        $this->collect(function ($object, $method, $key, $interval) use (&$definitions) {
            $definitions[] = [
                'class' => get_class($object),
                'method' => $method,
                'key' => $key,
                'interval' => $interval,
            ];
        });

        return $definitions;
    }
}
