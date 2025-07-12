<?php

namespace Tim661811\LaravelTelemetryReporter\Helpers;

use Composer\ClassMapGenerator\ClassMapGenerator;
use Illuminate\Support\Facades\App;
use ReflectionClass;
use Throwable;
use Tim661811\LaravelTelemetryReporter\Attributes\TelemetryResponseHandler;

class TelemetryResponseProcessorHelper
{
    /** @var array Directories to scan */
    protected array $paths;

    public function __construct(array $extraPaths = [])
    {
        $this->paths = array_merge([App::path()], $extraPaths);
    }

    /**
     * Discover all handler methods.
     *
     * @return array [ [object, methodName, key], ... ]
     */
    protected function resolveHandlers(): array
    {
        $handlers = [];

        // 1. Container bindings
        foreach (app()->getBindings() as $abstract => $binding) {
            try {
                $instance = app()->make($abstract);
                $this->inspect($instance, $handlers);
            } catch (Throwable) {
                continue;
            }
        }

        // 2. Classmap scan
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
                    $instance = App::make($class);
                    $this->inspect($instance, $handlers);
                } catch (Throwable) {
                    continue;
                }
            }
        }

        return $handlers;
    }

    /**
     * Inspect an object for TelemetryResponseHandler methods.
     */
    protected function inspect(object $object, array &$handlers): void
    {
        $reflect = new ReflectionClass($object);
        foreach ($reflect->getMethods() as $method) {
            $attrs = $method->getAttributes(TelemetryResponseHandler::class);
            if (empty($attrs)) {
                continue;
            }
            $attr = $attrs[0]->newInstance();
            $key = $attr->key ?? $reflect->getName().'@'.$method->getName();
            $handlers[] = [$object, $method->getName(), $key];
        }
    }

    /**
     * Process incoming server data.
     */
    public function process(array $response): void
    {
        $handlers = $this->resolveHandlers();
        foreach ($response as $key => $value) {
            foreach ($handlers as [$instance, $method, $expectedKey]) {
                if ($expectedKey === $key) {
                    $instance->{$method}($value);
                }
            }
        }
    }
}
