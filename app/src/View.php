<?php

namespace src;

class View
{
    private $templatesPath;

    public function __construct($templatesPath)
    {
        $this->templatesPath = $templatesPath;
    }

    public function render(string $templateName, array $params): void
    {
        // Устанавливаем заголовки
        header('Content-Type: text/html; charset=UTF-8');
        header('Access-Control-Allow-Origin: *');

        // Извлекаем параметры в переменные!
        extract($params);

        // Подключаем шаблон
        $template = $this->templatesPath . $templateName . '.phtml';
        
        if (file_exists($template)) {
            http_response_code(200);
            include $template;
        } else {
            http_response_code(500);
            echo "Шаблон не найден: " . htmlspecialchars($template);
        }
    }
}
