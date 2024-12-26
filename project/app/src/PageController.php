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
    private Auth $auth;

    public function __construct(array $params)
    {
        $this->view = $params['view'];
        $this->flash = $params['flash'];
        $this->auth = $params['auth'];
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
                'auth' => $this->auth->isAuth(),
                'authId' => $this->auth->getAuthId(),
                'admin' => $this->auth->isAdmin(),
            ],
            $httpStatusCode
        );
    }
}
