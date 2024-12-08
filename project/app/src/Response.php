<?php

namespace src;

class Response
{
    private $flash;

    public function __construct(Flash $flash)
    {
        $this->flash = $flash;
    }

    public function redirect(string $route, array $data): void
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
