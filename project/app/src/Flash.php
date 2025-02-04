<?php

/**
 * Класс Flash предоставляет методы для сохранения и извлечения данных
 * о промежуточном состоянии приложения с помощью суперглобального
 * массива $_SESSION.
 */

namespace src;

class Flash
{
    /**
     * Сохраняет сообщение в сессии.
     *
     * @param string $key Ключ для сообщения
     * @param string $message Сообщение для сохранения
     * @return void
     */
    public function set(string $key, string $message): void
    {
        if (!isset($_SESSION['flash'][$key])) {
            $_SESSION['flash'][$key] = [];
        }

        $_SESSION['flash'][$key][] = $message;
    }

    /**
     * Извлекает сообщения из сессии.
     *
     * @param string $key Ключ для извлечения сообщений
     * @return array<string> Массив сообщений
     */
    public function get(string $key = ''): array
    {
        if (!isset($_SESSION['flash'])) {
            return [];
        }

        return $key ? $this->getFlashByKey($key) : $this->getAllFlash();
    }

    /**
     * Извлекает сообщения по ключу и очищает их из сессии.
     *
     * @param string $key Ключ для извлечения сообщений
     * @return array<string> Массив сообщений
     */
    private function getFlashByKey(string $key): array
    {
        if (!isset($_SESSION['flash'][$key])) {
            return [];
        }

        $flash = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);

        return $flash;
    }

    /**
     * Извлекает все сообщения и очищает их из сессии.
     *
     * @return array<string> Массив сообщений.
     */
    private function getAllFlash(): array
    {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);

        return $flash;
    }
}
