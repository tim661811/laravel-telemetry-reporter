<?php

namespace Tim661811\LaravelTelemetryReporter\Commands;

use Illuminate\Console\Command;

class LaravelTelemetryReporterCommand extends Command
{
    public $signature = 'laravel-telemetry-reporter';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
