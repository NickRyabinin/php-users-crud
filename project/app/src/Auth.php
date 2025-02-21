<?php

namespace src;

class Auth
{
    private int $attemptsLimit;
    private int $blockTime;

    public function __construct(int $attemptsLimit, int $blockTime)
    {
        $this->attemptsLimit = $attemptsLimit;
        $this->blockTime = $blockTime;
    }

    public function login(array $user): void
    {
        $_SESSION['auth']['user_id'] = $user['id'];
        $_SESSION['auth']['user_role'] = $user['role'];
    }

    public function logout(): void
    {
        session_unset();
    }

    public function isAuth(): bool
    {
        return isset($_SESSION['auth']['user_id']);
    }

    public function getAuthId(): ?int
    {
        return $this->isAuth() ? $_SESSION['auth']['user_id'] : null;
    }

    public function isAdmin(): bool
    {
        return $this->isAuth()
            && isset($_SESSION['auth']['user_role'])
            && $_SESSION['auth']['user_role'] === 'admin';
    }

    public function recordLoginAttempt(string $email): void
    {
        if (!isset($_SESSION['auth']['login_attempts'])) {
            $_SESSION['auth']['login_attempts'] = [];
        }

        $_SESSION['auth']['login_attempts'][$email][] = time();
    }

    public function hasTooManyLoginAttempts(string $email): bool
    {
        if (isset($_SESSION['auth']['login_attempts'][$email])) {
            // Удаляем старые (больше blockTime) попытки входа
            $callback = function ($timestamp) {
                return (time() - $timestamp) < $this->blockTime;
            };
            $_SESSION['auth']['login_attempts'][$email] = array_filter(
                $_SESSION['auth']['login_attempts'][$email],
                $callback
            );

            return count($_SESSION['auth']['login_attempts'][$email]) >= $this->attemptsLimit;
        }

        return false;
    }
}
