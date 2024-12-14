<?php

/**
 * Класс Flash предоставляет методы для сохранения и извлечения данных
 * о промежуточном состоянии приложения с помощью суперглобального
 * массива $_SESSION.
 */

namespace src;

class Flash
{
    public function set(string $key, string $message): void
    {
        if (!isset($_SESSION['flash'][$key])) {
            $_SESSION['flash'][$key] = [];
        }

        $_SESSION['flash'][$key][] = $message;
    }

    public function get(string $key = ""): array
    {
        $flash = [];
        if (isset($_SESSION['flash'])) {
            if ($key) {
                if (isset($_SESSION['flash'][$key])) {
                    $flash = $_SESSION['flash'][$key];
                    unset($_SESSION['flash'][$key]);
                }
            } else {
                $flash = $_SESSION['flash'];
                unset($_SESSION['flash']);
            }
        }
        return $flash;
    }
}
