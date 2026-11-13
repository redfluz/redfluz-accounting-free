<?php

/**
 * Easy, one-command reinstall for first-time users.
 *
 * Removes installed dependencies and local data, then performs a fresh install
 * with demo data so the application is ready to open in a browser.
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class EasyInstall extends Command
{
    protected $signature = 'app:easy-install
                            {--force : Skip the confirmation prompt}';

    protected $description = 'Remove dependencies and local data, then do a fresh clean install with demo data';

    /**
     * Execute the easy install.
     */
    public function handle(): int
    {
        $this->info('');
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║        Redfluz Accounting — Easy Install (Fresh Start)     ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->info('');

        if (! $this->option('force') && ! $this->confirm(
            'This will DELETE vendor/, node_modules/, the database, uploads, caches and build files, then reinstall everything with demo data. Continue?',
            false
        )) {
            $this->info('  Easy install cancelled.');

            return self::SUCCESS;
        }

        $base = base_path();

        // Ensure .env exists so the installer can run.
        if (! file_exists($base.'/.env') && file_exists($base.'/.env.example')) {
            copy($base.'/.env.example', $base.'/.env');
            $this->info('▸ Created .env from .env.example');
        }

        // 1) Remove installed dependencies and generated/build artifacts.
        $this->info('');
        $this->info('▸ Removing installed dependencies and build files...');
        $this->deletePath($base.'/node_modules');
        $this->deletePath($base.'/vendor');
        $this->deletePath($base.'/public/build');
        $this->removeStorageLink($base.'/public/storage');
        $this->purgeBootstrapCache($base.'/bootstrap/cache');
        $this->info('  ✓ Dependencies and build files removed');

        // 2) Remove database and local storage data.
        $this->info('');
        $this->info('▸ Removing database and local data...');
        $this->deleteDatabase($base);
        $this->purgeDirectory($base.'/storage/app');
        $this->purgeDirectory($base.'/storage/framework/cache/data');
        $this->purgeDirectory($base.'/storage/framework/sessions');
        $this->purgeDirectory($base.'/storage/framework/views');
        $this->purgeDirectory($base.'/storage/framework/testing');
        $this->purgeDirectory($base.'/storage/logs');
        $this->info('  ✓ Database and local data removed');

        // 3) Reinstall PHP dependencies.
        $this->info('');
        $this->info('▸ Installing PHP dependencies (composer install)...');
        if (! $this->runExternal($this->composerCommand(['install', '--no-interaction', '--prefer-dist']), $base)) {
            $this->error('  ✗ Composer install failed. Make sure Composer is installed and try again.');

            return self::FAILURE;
        }
        $this->info('  ✓ PHP dependencies installed');

        // 4) Fresh app install with demo data (migrations + seed + key/link as needed).
        $this->info('');
        $this->info('▸ Setting up the application with demo data...');
        $php = PHP_BINARY;
        if (! $this->runExternal([$php, 'artisan', 'app:install', '--demo', '--no-interaction'], $base)) {
            $this->error('  ✗ Application setup failed.');

            return self::FAILURE;
        }

        // 5) Reinstall frontend dependencies and build assets.
        $this->info('');
        $this->info('▸ Installing frontend dependencies (npm install)...');
        if (! $this->runExternal(['npm', 'install'], $base)) {
            $this->error('  ✗ npm install failed. Make sure Node.js and npm are installed and try again.');

            return self::FAILURE;
        }
        $this->info('  ✓ Frontend dependencies installed');

        $this->info('');
        $this->info('▸ Building frontend assets (npm run build)...');
        if (! $this->runExternal(['npm', 'run', 'build'], $base)) {
            $this->error('  ✗ npm run build failed.');

            return self::FAILURE;
        }
        $this->info('  ✓ Frontend assets built');

        // 6) Ensure a portable storage symlink (matches README).
        $this->info('');
        $this->info('▸ Creating storage link...');
        $this->runExternal([$php, 'artisan', 'storage:link', '--relative', '--force'], $base);

        $this->info('');
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║                  Easy Install Complete!                   ║');
        $this->info('╠════════════════════════════════════════════════════════════╣');
        $this->info('║  Demo data is ready.                                      ║');
        $this->info('║                                                           ║');
        $this->info('║  Login:    admin@example.com                              ║');
        $this->info('║  Password: password                                       ║');
        $this->info('║                                                           ║');
        $this->info('║  Start:    php artisan dev                                ║');
        $this->info('║  Open:     http://localhost:8000                          ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->info('');

        return self::SUCCESS;
    }

    /**
     * Remove the SQLite database file(s) for a clean reset.
     */
    private function deleteDatabase(string $base): void
    {
        $paths = array_unique(array_filter([
            $base.'/database/database.sqlite',
            config('database.connections.sqlite.database'),
        ], static fn ($value): bool => is_string($value) && $value !== ''));

        foreach ($paths as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
            // SQLite may leave journal/wal/shm files behind.
            foreach ([$path.'-journal', $path.'-wal', $path.'-shm'] as $sidecar) {
                if (is_file($sidecar)) {
                    @unlink($sidecar);
                }
            }
        }
    }

    /**
     * Remove a file, symlink, or directory tree if it exists.
     */
    private function deletePath(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            @unlink($path);

            return;
        }

        if (is_dir($path)) {
            $this->deleteDirectory($path);
        }
    }

    /**
     * Recursively delete a directory and its contents.
     */
    private function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = scandir($directory);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$item;
            if (is_dir($path) && ! is_link($path)) {
                $this->deleteDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }

    /**
     * Remove the public/storage symlink if present.
     */
    private function removeStorageLink(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * Purge compiled files in bootstrap/cache, keeping .gitignore.
     */
    private function purgeBootstrapCache(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        foreach (scandir($path) ?: [] as $item) {
            if ($item === '.' || $item === '..' || $item === '.gitignore') {
                continue;
            }

            $file = $path.DIRECTORY_SEPARATOR.$item;
            if (is_file($file) || is_link($file)) {
                @unlink($file);
            }
        }
    }

    /**
     * Delete contents of a directory while preserving the directory and dot-keep files.
     */
    private function purgeDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        foreach (scandir($path) ?: [] as $item) {
            if ($item === '.' || $item === '..' || $item === '.gitkeep' || $item === '.gitignore') {
                continue;
            }

            $this->deletePath($path.DIRECTORY_SEPARATOR.$item);
        }
    }

    /**
     * Build a composer command, preferring a local composer.phar when present.
     *
     * @param  list<string>  $arguments
     * @return list<string>
     */
    private function composerCommand(array $arguments): array
    {
        $phar = base_path('composer.phar');
        if (is_file($phar)) {
            return array_merge([PHP_BINARY, $phar], $arguments);
        }

        return array_merge(['composer'], $arguments);
    }

    /**
     * Run an external command, streaming output to the console.
     *
     * @param  list<string>  $command
     */
    private function runExternal(array $command, string $workingDirectory, int $timeout = 600): bool
    {
        $process = new Process($command, $workingDirectory, null, null, $timeout);
        $process->run(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        return $process->isSuccessful();
    }
}
