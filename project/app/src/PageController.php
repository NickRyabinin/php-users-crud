<?php

/**
 * Класс PageController - контроллер "домашней страницы", имеет единственный
 * метод read(), вызывающий на View рендер шаблона pages/home.phtml
 */

namespace src;

class PageController
{
    private View $view;
    private Flash $flash;

    public function __construct(array $params)
    {
        $this->view = $params['view'];
        $this->flash = $params['flash'];
    }

    public function read(): void
    {
        $statusCode = $this->flash->get('status_code');
        $httpStatusCode = $statusCode === [] ? 200 : $statusCode[0];

        $flashMessages = $this->flash->get();
        $pageTitle = 'О приложении';

        $this->view->render(
            'pages/home',
            [
                'flash' => $flashMessages,
                'title' => $pageTitle,
            ],
            $httpStatusCode
        );
    }
}
