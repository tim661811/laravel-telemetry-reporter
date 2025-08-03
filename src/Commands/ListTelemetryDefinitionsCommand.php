<?php

namespace Tim661811\LaravelTelemetryReporter\Commands;

use Illuminate\Console\Command;
use Tim661811\LaravelTelemetryReporter\Helpers\TelemetryDataCollector;

class ListTelemetryDefinitionsCommand extends Command
{
    protected $signature = 'telemetry:list';

    protected $description = 'List all registered telemetry data collectors and their intervals';

    public function handle(): int
    {
        $collector = new TelemetryDataCollector;

        $definitions = $collector->listDefinitions();

        if (empty($definitions)) {
            $this->info('No telemetry data collectors found.');

            return 0;
        }

        $this->table(
            ['Key', 'Class', 'Method', 'Interval (seconds)'],
            collect($definitions)->map(fn ($d) => [
                $d['key'],
                $d['class'],
                $d['method'],
                $d['interval'],
            ])
        );

        return 0;
    }
}
