<?php

namespace src;

class Flash
{
    public function set(string $key, string $message): void
    {
        // ! Инициализируем массив, если он не существует
        if (!isset($_SESSION['flash'][$key])) {
            $_SESSION['flash'][$key] = [];
        }

        $_SESSION['flash'][$key][] = $message;
    }

    public function get(): array
    {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return [];
    }
}
