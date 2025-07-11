<?php

namespace Tim661811\LaravelTelemetryReporter\Helpers;

use Composer\ClassMapGenerator\ClassMapGenerator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use ReflectionClass;
use Throwable;
use Tim661811\LaravelTelemetryReporter\Attributes\TelemetryData;

class TelemetryHelper
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

    public function collectData(): array
    {
        $data = [];
        $now = now();

        $this->collect(function ($object, $method, $key, $interval) use (&$data, $now) {
            $cacheKey = "laravel-telemetry-reporter:{$key}:last-run-time";
            $lastRun = Cache::store($this->cacheStore)->get($cacheKey);

            if ($lastRun && Carbon::parse($lastRun)->addMinutes($interval)->gt($now)) {
                return;
            }

            $result = $object->{$method}();
            $data[$key] = $result;

            Cache::store($this->cacheStore)->put($cacheKey, $now->toIso8601ZuluString());
        });

        return $data;
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
