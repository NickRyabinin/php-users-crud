<?php

/**
 * Класс Request предоставляет приложению методы для разбора запроса клиента.
 */

namespace src;

class Request
{
    /**
     * @var array<string, mixed> $data
     */
    private array $data;

    /**
     * @var array<string, mixed> $files
     */
    private array $files;

    public function __construct()
    {
        $this->data = $_POST;
        $this->files = $_FILES;
    }

    public function getFormData(string $key): mixed
    {
        return isset($this->data[$key]) ? htmlspecialchars($this->data[$key], ENT_QUOTES, 'UTF-8') : null;
    }

    /**
     * @return array<string, mixed> | false
     */

    public function getFile(string $key): array | false
    {
        return $this->files[$key] ?? false;
    }

    public function getHttpMethod(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    public function getParsedUrl(): mixed
    {
        return parse_url($_SERVER['REQUEST_URI']);
    }

    public function getResourceId(): int
    {
        $parsedUrl = $this->getParsedUrl();
        $path = $parsedUrl['path'] ?? '';

        $segments = explode('/', trim($path, '/')); // Разбиваем путь по слешам
        $id = $segments[1] ?? ''; // Путь должен иметь формат /resource/{id}/...

        // id должен быть числом больше 0
        if ($id !== '' && preg_match('/^[1-9]\d*$/', $id)) {
            return (int) $id;
        }

        return 0;
    }

    public function getPage(): int
    {
        $page = isset($_GET['page']) ? $_GET['page'] : 1;
        return (preg_match('/^[1-9]\d*$/', $page)) ? (int) $page : 1;
    }

    public function getQueryParam(string $key, mixed $default = null): mixed
    {
        return isset($_GET[$key]) ? htmlspecialchars($_GET[$key], ENT_QUOTES, 'UTF-8') : $default;
    }
}
