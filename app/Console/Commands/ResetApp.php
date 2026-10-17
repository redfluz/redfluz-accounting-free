<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ResetApp extends Command
{
    protected $signature = 'app:reset
                            {--force : Skip confirmation prompt}';

    protected $description = 'Full factory reset — wipe database, storage, caches completely';

    /**
     * Wipe the entire application clean.
     *
     * Destroys everything: deletes the SQLite file or drops all MySQL tables,
     * purges all uploaded files, logs, compiled views, sessions, and caches.
     * Does not re-migrate or re-seed — run app:install afterwards for a
     * fresh setup.
     *
     * @return int The command exit code.
     */
    public function handle(): int
    {
        $this->info('');
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║          Redfluz Accounting — Full Factory Reset          ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->info('');

        if (! $this->option('force')) {
            $this->warn('  WARNING: This will destroy EVERYTHING:');
            $this->line('    • All database tables and data');
            $this->line('    • All uploaded files and attachments');
            $this->line('    • All logs, sessions, and cached data');
            $this->line('');
            $this->line('  Run app:install afterwards to set up fresh.');
            $this->line('');

            if (! $this->confirm('  Are you sure you want to continue?', false)) {
                $this->info('  Reset cancelled.');

                return self::SUCCESS;
            }
        }

        // Step 1: Clear caches
        $this->info('▸ Clearing caches...');
        try {
            $this->call('cache:clear', ['--quiet' => true]);
        } catch (Exception) {
            // Database may not exist yet (e.g. SQLite file deleted). Nothing to clear.
        }
        $this->call('config:clear', ['--quiet' => true]);
        $this->call('route:clear', ['--quiet' => true]);
        $this->call('view:clear', ['--quiet' => true]);

        // Compiled package/service manifests are not covered by the
        // cache:clear family, yet they snapshot the previous install.
        foreach (File::files(base_path('bootstrap/cache')) as $file) {
            if ($file->getFilename() === '.gitignore') {
                continue;
            }

            File::delete($file->getPathname());
        }
        $this->info('  ✓ Caches cleared');

        // Step 2: Purge storage
        $this->info('');
        $this->info('▸ Purging storage...');
        $this->purgeDirectory(storage_path('app'));
        $this->purgeDirectory(storage_path('framework/cache/data'));
        $this->purgeDirectory(storage_path('framework/sessions'));
        $this->purgeDirectory(storage_path('framework/views'));
        $this->purgeDirectory(storage_path('framework/testing'));
        $this->purgeDirectory(storage_path('logs'));
        $this->info('  ✓ Storage purged');

        // Step 3: Destroy database
        $this->info('');
        $this->info('▸ Destroying database...');

        $dbConnection = config('database.default');

        if ($dbConnection === 'sqlite') {
            $dbPath = database_path('database.sqlite');

            if (file_exists($dbPath)) {
                unlink($dbPath);
                $this->info('  ✓ SQLite database deleted');
            } else {
                $this->info('  ✓ No SQLite database file found (already clean)');
            }
        } else {
            try {
                DB::connection()->getPdo();
                $this->call('migrate:reset', [
                    '--force' => true,
                    '--quiet' => true,
                ]);
                $this->info('  ✓ All MySQL tables dropped');
            } catch (Exception $e) {
                $this->error('  ✗ Database connection failed!');
                $this->line('  '.$e->getMessage());

                return self::FAILURE;
            }
        }

        // Step 4: Remove storage link
        if (file_exists(public_path('storage'))) {
            File::delete(public_path('storage'));
            $this->info('');
            $this->info('  ✓ Storage link removed');
        }

        // Step 5: Done
        $this->info('');
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║                  Factory Reset Complete!                  ║');
        $this->info('╠════════════════════════════════════════════════════════════╣');
        $this->info('║                                                           ║');
        $this->info('║  Everything has been wiped clean.                         ║');
        $this->info('║                                                           ║');
        $this->info('║  To set up fresh:                                         ║');
        $this->info('║    php artisan app:install                                ║');
        $this->info('║                                                           ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->info('');

        return self::SUCCESS;
    }

    /**
     * Delete all files and subdirectories inside a directory.
     *
     * Preserves the directory itself and any .gitkeep/.gitignore files so the
     * folder structure remains intact for Laravel and version control.
     *
     * @param  string  $path  Absolute path to the directory to purge.
     */
    private function purgeDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        /** @var string $directory */
        foreach (File::directories($path) as $directory) {
            File::deleteDirectory($directory);
        }

        foreach (File::files($path) as $file) {
            if (in_array($file->getFilename(), ['.gitkeep', '.gitignore'], true)) {
                continue;
            }

            File::delete($file->getPathname());
        }
    }
}
