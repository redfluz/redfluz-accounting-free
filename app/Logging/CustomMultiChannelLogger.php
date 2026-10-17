<?php

declare(strict_types=1);
/** @noinspection PhpUnused */

namespace App\Logging;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\FilterHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

final class CustomMultiChannelLogger
{
    /**
     * @param  array<string, mixed>  $config  The logger configuration.
     * @return Logger The configured Monolog logger instance.
     */
    public function __invoke(array $config): Logger
    {
        $dateFolder = date('Y-m-d');
        $basePath = storage_path('logs/'.$dateFolder.'/');

        if (! is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }

        $logger = new Logger('clean');

        // Each level has its own file
        $channels = [
            ['file' => 'error.log', 'level' => Level::Error],
            ['file' => 'warning.log', 'level' => Level::Warning],
            ['file' => 'info.log', 'level' => Level::Info],
            ['file' => 'debug.log', 'level' => Level::Debug],
        ];

        foreach ($channels as $channel) {
            $stream = new StreamHandler($basePath.$channel['file'], $channel['level'], false);
            $handler = new FilterHandler($stream, $channel['level'], $channel['level']); // exact match
            $handler->setFormatter($this->formatter());
            $logger->pushHandler($handler);
        }

        return $logger;
    }

    private function formatter(): LineFormatter
    {
        return new LineFormatter("[%datetime%] [%level_name%] %message% %context%\n", 'Y-m-d H:i:s', true, true);
    }
}
