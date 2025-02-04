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
        $dateTime = date('d/m/Y H:i:s');
        $formattedMessage = "[$dateTime] $message";

        error_log($formattedMessage . PHP_EOL, 3, $this->logFile);
        return;
    }
}
