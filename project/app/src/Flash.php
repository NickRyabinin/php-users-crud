<?php

namespace src;

class Flash
{
    public function set(string $key, string $message): void
    {
        $_SESSION['flash'][$key] = $message;
    }

    public function get(): ?array
    {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
}
