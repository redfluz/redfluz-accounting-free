<?php

namespace App\Providers;

use App\Models\System\Setting;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Spatie\Backup\Events\DumpingDatabase;

/**
 * @noinspection PhpUnused
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(DumpingDatabase::class, function (DumpingDatabase $event): void {
            $rawPath = config('database.dump_binary_path');
            $configuredPath = is_string($rawPath) ? $rawPath : '';
            $binaryPath = collect([
                $configuredPath,
                '/opt/homebrew/bin',
                '/usr/local/bin',
            ])->first(fn (string $path): bool => $path !== '' && is_file($path.'/mariadb-dump'));

            if (is_string($binaryPath)) {
                $event->dbDumper->setDumpBinaryPath($binaryPath);
            }
        });

        Paginator::useBootstrapFive();

        View::share('currencySymbol', Setting::getCurrencySymbol());
    }
}
