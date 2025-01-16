<?php

namespace src;

abstract class BaseController
{
    protected View $view;
    protected Flash $flash;
    protected Auth $auth;

    public function __construct(array $params)
    {
        $this->view = $params['view'];
        $this->flash = $params['flash'];
        $this->auth = $params['auth'];
    }

    protected function renderView(string $template, array $data): void
    {
        $statusCode = $this->flash->get('status_code');
        $httpStatusCode = $statusCode === [] ? 200 : $statusCode[0];

        $this->view->render($template, array_merge($data, [
            'flash' => $this->flash->get(),
            'auth' => $this->auth->isAuth(),
            'authId' => $this->auth->getAuthId(),
            'admin' => $this->auth->isAdmin(),
        ]), $httpStatusCode);
    }
}
