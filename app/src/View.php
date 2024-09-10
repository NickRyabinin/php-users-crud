<?php

namespace src;

class View
{
    public function render(string $template): void
    {
        header('Content-Type: text/html');
        header('Access-Control-Allow-Origin: *');
        http_response_code('200');
        echo file_get_contents(__DIR__ . '/../templates/' . $template . 'html');
    }
}
