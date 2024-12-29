<?php

namespace src;

class AuthMiddleware
{
    private Auth $auth;
    private Response $response;

    public function __construct(Auth $auth, Response $response)
    {
        $this->auth = $auth;
        $this->response = $response;
    }

    public function checkAuth(): void
    {
        if (!$this->auth->isAuth()) {
            $this->response->redirect(
                '/users/login',
                [
                    'error' => 'Действие доступно только аутентифицированным пользователям',
                    'status_code' => '401',
                ]
            );
        }
    }

    public function checkAdmin(): void
    {
        if (!$this->auth->isAdmin()) {
            $this->response->redirect(
                '/',
                [
                    'error' => 'Действие доступно только пользователям с правами администратора',
                    'status_code' => '403',
                ]
            );
        }
    }
}
