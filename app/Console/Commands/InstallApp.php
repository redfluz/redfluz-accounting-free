<?php

namespace App\Console\Commands;

use Database\Seeders\DatabaseSeeder;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InstallApp extends Command
{
    protected $signature = 'app:install
                            {--demo : Seed with demo data}';

    protected $description = 'Install and configure Redfluz Accounting (first-time setup wizard)';

    public function handle(): int
    {
        $this->info('');
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║       Redfluz Accounting Free — Installation Wizard       ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->info('');

        // Step 1: Check .env exists
        if (! file_exists(base_path('.env'))) {
            $this->error('.env file not found. Please copy .env.example to .env first:');
            $this->line('  cp .env.example .env');

            return self::FAILURE;
        }

        // Step 2: Generate APP_KEY if missing
        $currentKey = config('app.key');
        if (empty($currentKey)) {
            $this->info('▸ Generating application key...');
            $this->call('key:generate', ['--quiet' => true]);
            $this->info('  ✓ APP_KEY generated');
        } else {
            $this->info('▸ Application key: ✓ already set');
        }

        // Step 3: Setup database
        $this->info('');
        $this->info('▸ Setting up database...');

        $dbConnection = config('database.default');

        if ($dbConnection === 'sqlite') {
            // Create SQLite database file if it doesn't exist
            $dbPath = database_path('database.sqlite');
            if (! file_exists($dbPath)) {
                touch($dbPath);
                $this->info("  ✓ Created SQLite database: $dbPath");
            } else {
                $this->info("  ✓ SQLite database exists: $dbPath");
            }
        }

        // Test database connection
        try {
            DB::connection()->getPdo();
            if ($dbConnection === 'sqlite') {
                $this->info('  ✓ SQLite connection successful');
            } else {
                $connectionString = is_string($dbConnection) ? $dbConnection : '';
                $dbName = is_string(config("database.connections.{$connectionString}.database"))
                    ? (string) config("database.connections.{$connectionString}.database")
                    : '';
                $this->info("  ✓ Connected to MySQL: $dbName");
            }
        } catch (Exception $e) {
            $this->error('  ✗ Database connection failed!');
            $this->line('');

            if ($dbConnection === 'sqlite') {
                $this->line('  SQLite error: '.$e->getMessage());
                $this->line('  Make sure the database/ directory is writable.');
            } else {
                $this->line('  MySQL error: '.$e->getMessage());
                $this->line('');
                $this->line('  Please check your .env DB_ settings.');
                $this->line('  Or switch to SQLite (zero config):');
                $this->line('    DB_CONNECTION=sqlite');
            }

            return self::FAILURE;
        }

        // Step 4: Create database if not exists
        $this->info('');
        $this->info('▸ Running migrations...');
        $this->call('migrate', [
            '--force' => true,
            '--quiet' => false,
        ]);
        $this->info('  ✓ Migrations complete');

        // Step 5: Seed data
        $this->info('');
        $useDemo = $this->option('demo') === true;

        if (! $useDemo) {
            $useDemo = $this->confirm('  Seed with demo data (customers, products, transactions)?', false);
        }

        $this->info('');
        $this->info('▸ Seeding database...');

        if ($useDemo) {
            // Instantiate DatabaseSeeder directly and pass THIS command
            // (app:install already defines --demo in its signature, so the
            // seeder can read it via $this->command->option('demo'))
            $seeder = new DatabaseSeeder;
            $seeder->setContainer($this->laravel);
            $seeder->setCommand($this);
            // The answer to the prompt above, not re-derived from --demo. Passing
            // only the command would leave the seeder reading its own option,
            // which is false for anyone who answered yes to the question.
            $seeder->withDemoData = $useDemo;
            $seeder->run();
            $this->info('  ✓ Demo data seeded');
        } else {
            $this->call('db:seed', ['--force' => true]);
            $this->info('  ✓ Essential data seeded (clean install)');
        }

        // Step 6: Create storage link
        $this->info('');
        $this->info('▸ Creating storage link...');
        if (! file_exists(public_path('storage'))) {
            $this->call('storage:link', ['--quiet' => true]);
            $this->info('  ✓ Storage link created');
        } else {
            $this->info('  ✓ Storage link already exists');
        }

        // Step 7: Setup PHPStan/Larastan
        $this->info('');
        $setupPhpStan = $this->confirm('  Set up PHPStan and Larastan for static analysis?', false);

        if ($setupPhpStan) {
            $this->info('▸ Setting up PHPStan...');
            $this->createPhpStanConfig();
            $this->info('  ✓ PHPStan configuration created');
        }

        // Step 8: Show credentials
        $this->info('');
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║                    Installation Complete!                 ║');
        $this->info('╠════════════════════════════════════════════════════════════╣');
        $this->info('║                                                           ║');
        $this->info('║  Login credentials:                                       ║');
        $this->info('║    Email:    admin@example.com                            ║');
        $this->info('║    Password: password                                     ║');
        $this->info('║                                                           ║');
        $this->info('║  Start the server:                                        ║');
        $this->info('║    php artisan dev                                        ║');
        $this->info('║                                                           ║');
        $this->info('║  Then visit: http://localhost:8000                        ║');
        $this->info('║                                                           ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->info('');

        return self::SUCCESS;
    }

    /**
     * Create PHPStan configuration file.
     */
    private function createPhpStanConfig(): void
    {
        $config = <<<'NEON'
includes:
    - ./vendor/larastan/larastan/extension.neon

parameters:
    paths:
        - app
    level: 10
NEON;

        file_put_contents(base_path('phpstan.neon'), $config);
    }
}
