<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\Foundation\PackageManifest;

it('sends telemetry payload from class on disk', function () {
    // Create a test class file in the app directory
    $classContent = <<<'PHP'
<?php

namespace App\Telemetry;

use Tim661811\LaravelTelemetryReporter\Attributes\TelemetryData;

class DiskTestClass
{
    #[TelemetryData(interval: 0, key: 'disk.test')]
    public function getDiskMetrics(): array
    {
        return ['free_space' => 1000];
    }
}
PHP;

    // Create directory if it doesn't exist
    if (! is_dir(app_path('Telemetry'))) {
        mkdir(app_path('Telemetry'), 0777, true);
    }

    // Write the test class file
    file_put_contents(
        app_path('Telemetry/DiskTestClass.php'),
        $classContent
    );

    // Clear any cached autoloader files
    @unlink(base_path('bootstrap/cache/packages.php'));
    @unlink(base_path('bootstrap/cache/services.php'));

    // Refresh application
    $this->app = $this->createApplication();
    (new PackageManifest(
        $this->app->make('files'),
        $this->app->basePath(),
        $this->app->getCachedPackagesPath()
    ))->build();

    Http::fake();

    Artisan::call('telemetry:report');

    Http::assertSent(function ($request) {
        return $request->url() === config('telemetry-reporter.server_url')
            && isset($request->data()['data']['disk.test'])
            && $request->data()['data']['disk.test']['free_space'] === 1000;
    });

    // Clean up
    @unlink(app_path('Telemetry/DiskTestClass.php'));
    @rmdir(app_path('Telemetry'));
});
