<?php

namespace src;

class View
{
    private $templatesPath;

    public function __construct($templatesPath)
    {
        $this->templatesPath = $templatesPath;
    }

    public function render(string $templateName, array $params = [], string $title = '', string $menu = ''): void
    {
        // Устанавливаем заголовки
        header('Content-Type: text/html; charset=UTF-8');
        header('Access-Control-Allow-Origin: *');

        // Извлекаем параметры в переменные!
        extract($params);

        // Подключаем шаблоны в макет
        $template = $this->templatesPath . $templateName . '.phtml';
        // тут нужно разобраться с шаблоном $menu

        if (file_exists($template)) {
            http_response_code(200);
            ob_start();
            include $template;
            $content = ob_get_clean(); // Записываем содержимое буфера в $content для вставки в макет
        } else {
            http_response_code(500);
            echo "Шаблон не найден: " . htmlspecialchars($template);
            return;
        }
        // Выводим макет
        include $this->templatesPath . 'layout.phtml';
    }
}

/*
$params = [
    'user' => $user
];
$view->render('edit', $params, 'Изменение пользователя', $menu);
*/
