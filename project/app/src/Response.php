<?php

/**
 * Класс Response предоставляет приложению метод для перенаправления
 * на указанный маршрут, с возможностью сохранения состояния во Flash.
 */

namespace src;

class Response
{
    private Flash $flash;

    public function __construct(Flash $flash)
    {
        $this->flash = $flash;
    }

    /**
     * @param array<string, mixed> $data
     */

    public function redirect(string $route, array $data = []): void
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $this->flash->set($key, $value);
            }
        }

        header("Location: $route");
        exit();
    }
}
