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

    public function isAuth(int $userId): bool
    {
        return isset($_SESSION['auth']['user_id'])
            && $_SESSION['auth']['user_id'] === $userId;
    }

    public function isAdmin(int $userId): bool
    {
        return $this->isAuth($userId)
            && isset($_SESSION['auth']['user_role'])
            && $_SESSION['auth']['user_role'] === 'admin';
    }
}
