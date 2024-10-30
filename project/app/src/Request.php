<?php

/**
 * Класс Request предоставляет приложению методы для разбора запроса клиента.
 */

namespace src;

class Request
{
    private $data;
    private $files;

    public function __construct()
    {
        $this->data = $_POST;
        $this->files = $_FILES;
    }

    public function getFormData(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function getFile(string $key): mixed
    {
        return $this->files[$key] ?? null;
    }

    public function getHttpMethod(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    public function getParsedUrl(): array
    {
        return parse_url($_SERVER['REQUEST_URI']);
    }
/*
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
*/
}
