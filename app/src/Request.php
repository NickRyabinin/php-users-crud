<?php

/**
 * Класс Request предоставляет приложению методы для разбора запроса клиента.
 */

namespace src;

class Request
{
    public function getRequest(): array
    {
        return explode('/', trim($_SERVER['REQUEST_URI'], '/'));
    }

    public function getHttpMethod(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    public function getId(): string | bool
    {
        $request = $this->getRequest();
        $id = $request[1] ?? '';

        if ($id !== '' && preg_match('/^\d+$/', $id)) {
            return (string)$id;
        }

        return false;
    }

    public function getPage(): string
    {
        $page = isset($_GET['page']) ? $_GET['page'] : 1;
        return (preg_match('/^[1-9]\d*$/', $page)) ? (string)$page : 1;
    }

    public function getResource(string $parent = ''): string
    {
        if ($parent) {
            $resource = $this->getRequest()[0];
        } else {
            $resource = isset($this->getRequest()[2]) ? $this->getRequest()[2] : $this->getRequest()[0];
        }
        return $this->sanitize($this->validate($resource));
    }

    public function getInputData(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }
}
