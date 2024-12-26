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
            exit();
        }
    }
}