<?php

namespace src;

class Auth
{
    public function login(array $user): void
    {
        $_SESSION['auth']['user_id'] = $user['id'];
        $_SESSION['auth']['user_role'] = $user['role'];
    }

    public function logout(): void
    {
        unset($_SESSION['auth']);
    }

    public function isAdmin(): bool
    {
        return isset($_SESSION['auth']['user_role']) && $_SESSION['auth']['user_role'] === 'admin';
    }
}
