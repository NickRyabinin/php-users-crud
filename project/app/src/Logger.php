<?php

/**
 * Класс Logger - записывает передаваемые извне сообщения в указанный файл.
 */

namespace src;

class Logger
{
    private string $logFile;

    public function __construct(string $logFile)
    {
        $this->logFile = $logFile;
    }

    public function log(string $message): void
    {
        error_log($message . PHP_EOL, 3, $this->logFile);
        return;
    }
}
