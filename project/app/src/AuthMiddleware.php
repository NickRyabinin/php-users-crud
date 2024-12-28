<?php

namespace src;

class AuthMiddleware
{
    private Auth $auth;

    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    public function checkAuth(): void
    {
        if (!$this->auth->isAuth()) {
            header('Location: /users/login');
            http_response_code(401);
            exit();
        }
    }

    public function checkAdmin(): void
    {
        if (!$this->auth->isAdmin()) {
            header('Location: /');
            http_response_code(403);
            exit();
        }
    }
}
