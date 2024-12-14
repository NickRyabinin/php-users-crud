<?php

/**
 * Класс View выводит пользователю приложения указанные контроллером шаблоны страниц,
 * включенные в макет и заполненные переданными данными.
 */

namespace src;

class View
{
    private $templatesPath;

    public function __construct($templatesPath)
    {
        $this->templatesPath = $templatesPath;
    }

    public function render(string $templateName, array $data = [], int $httpStatusCode = 200): void
    {
        header('Content-Type: text/html; charset=UTF-8');
        header('Access-Control-Allow-Origin: *');

        extract($data);

        $contentTemplate = $this->templatesPath . $templateName . '.phtml';

        if (file_exists($contentTemplate)) {
            http_response_code($httpStatusCode);
            // Шаблон выводим в буфер, а содержимое буфера записываем в $content для вставки в макет
            ob_start();
            include $contentTemplate;
            $content = ob_get_clean();
        } else {
            http_response_code(500);
            echo "Не найден шаблон для отображения";
            return;
        }

        include $this->templatesPath . 'layout.phtml';
    }
}
