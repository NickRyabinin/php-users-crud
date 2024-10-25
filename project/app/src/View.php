<?php

namespace src;

class View
{
    private $templatesPath;

    public function __construct($templatesPath)
    {
        $this->templatesPath = $templatesPath;
    }

    public function render(string $templateName, array $data = [], string $title = ''): void
    {
        // Устанавливаем заголовки
        header('Content-Type: text/html; charset=UTF-8');
        header('Access-Control-Allow-Origin: *');

        // Извлекаем параметры в переменные!
        extract($data);

        // Подключаем шаблоны в макет
        $contentTemplate = $this->templatesPath . $templateName . '.phtml';

        if (file_exists($contentTemplate)) {
            http_response_code(200);
            ob_start();
            include $contentTemplate;
            $content = ob_get_clean(); // Записываем содержимое буфера в $content для вставки в макет
        } else {
            http_response_code(500);
            echo "Шаблон не найден: " . htmlspecialchars($contentTemplate);
            return;
        }
        // Выводим макет
        include $this->templatesPath . 'layout.phtml';
    }
}
