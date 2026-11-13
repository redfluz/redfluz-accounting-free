#!/usr/bin/env php
<?php

/**
 * Easy one-click install for first-time users (cross-platform: Windows, macOS, Linux).
 * Works from a clean checkout (no vendor/node_modules). Does not require Artisan/vendor.
 *
 * Usage: php easy-install.php
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

$root = __DIR__;
chdir($root);

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║        Redfluz Accounting — Easy Install (Fresh Start)     ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

function need(string $command): void
{
    $isWin = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    $null = $isWin ? 'nul' : '/dev/null';
    $which = $isWin ? 'where '.escapeshellarg($command) : 'command -v '.escapeshellarg($command);
    exec($which.' 2>'.$null, $out, $code);

    if ($code !== 0) {
        fwrite(STDERR, "  ✗ Missing required command: $command\n");
        fwrite(STDERR, "    Please install it and run this script again.\n");
        exit(1);
    }
}

function runCmd(array $command, string $cwd, bool $silent = false): bool
{
    $descriptors = $silent
        ? [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']]
        : [0 => STDIN, 1 => STDOUT, 2 => STDERR];

    $process = proc_open($command, $descriptors, $pipes, $cwd);
    if (! is_resource($process)) {
        return false;
    }

    if ($silent) {
        fclose($pipes[0]);
        stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[2]);
    }

    $status = proc_close($process);

    return $status === 0;
}

function runComposer(array $args, string $cwd): bool
{
    $phar = $cwd.DIRECTORY_SEPARATOR.'composer.phar';
    if (is_file($phar)) {
        return runCmd(array_merge([PHP_BINARY, $phar], $args), $cwd);
    }

    // On Windows "composer" is often composer.bat; proc_open with array handles it on PHP 7.4+.
    return runCmd(array_merge(['composer'], $args), $cwd);
}

need('php');
need('composer');
need('npm');

// Ensure .env exists
if (! file_exists($root.'/.env') && file_exists($root.'/.env.example')) {
    copy($root.'/.env.example', $root.'/.env');
    echo "▸ Created .env from .env.example\n";
}

// Ensure SQLite database file exists (default setup)
if (! is_dir($root.'/database')) {
    mkdir($root.'/database', 0777, true);
}
$dbPath = $root.'/database/database.sqlite';
if (! file_exists($dbPath)) {
    file_put_contents($dbPath, '');
    echo "▸ Created database/database.sqlite\n";
}

echo "\n▸ Installing PHP dependencies (composer install)...\n";
if (! runComposer(['install', '--no-interaction', '--prefer-dist', '--optimize-autoloader'], $root)) {
    fwrite(STDERR, "  ✗ Composer install failed. Make sure Composer is installed and try again.\n");
    exit(1);
}
echo "  ✓ PHP dependencies installed\n";

echo "\n▸ Setting up the application with demo data...\n";
if (! runCmd([PHP_BINARY, 'artisan', 'app:install', '--demo', '--no-interaction'], $root)) {
    fwrite(STDERR, "  ✗ Application setup failed.\n");
    exit(1);
}

echo "\n▸ Installing frontend dependencies (npm install)...\n";
if (! runCmd(['npm', 'install'], $root)) {
    fwrite(STDERR, "  ✗ npm install failed. Make sure Node.js and npm are installed and try again.\n");
    exit(1);
}
echo "  ✓ Frontend dependencies installed\n";

echo "\n▸ Building frontend assets (npm run build)...\n";
$npmBuild = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? ['npm.cmd', 'run', 'build'] : ['npm', 'run', 'build'];
// try plain npm first (works in most environments including Windows with proper PATH)
if (! runCmd(['npm', 'run', 'build'], $root)) {
    fwrite(STDERR, "  ✗ npm run build failed.\n");
    exit(1);
}
echo "  ✓ Frontend assets built\n";

echo "\n▸ Creating storage link...\n";
// Relative links require symfony/filesystem; fall back to a standard (absolute) link if unavailable.
if (! runCmd([PHP_BINARY, 'artisan', 'storage:link', '--relative', '--force'], $root, true)) {
    echo "  (Relative link unavailable, creating standard storage link...)\n";
    runCmd([PHP_BINARY, 'artisan', 'storage:link', '--force'], $root);
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║                  Easy Install Complete!                   ║\n";
echo "╠════════════════════════════════════════════════════════════╣\n";
echo "║  Demo data is ready.                                      ║\n";
echo "║                                                           ║\n";
echo "║  Login:    admin@example.com                              ║\n";
echo "║  Password: password                                       ║\n";
echo "║                                                           ║\n";
echo "║  Start:    php artisan dev                                ║\n";
echo "║  Open:     http://localhost:8000                          ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";
