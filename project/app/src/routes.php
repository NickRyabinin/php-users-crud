<?php

use src\UserController;
use src\PageController;

return [
    'GET' => [
        '/users/register' => [UserController::class, 'showRegistrationForm', []],
        '/users/captcha' => [UserController::class, 'showCaptcha', []],
        '/users/login' => [UserController::class, 'showLoginForm', []],
        '/users/logout' => [UserController::class, 'logout', ['auth' => true]],
        '/users/new' => [UserController::class, 'create', ['admin' => true]],
        '/users/{id}/edit' => [UserController::class, 'edit', ['auth' => true]],
        '/users/{id}' => [UserController::class, 'show', ['auth' => true]],
        '/users' => [UserController::class, 'index', ['admin' => true]],
        '/' => [PageController::class, 'read', []]
    ],
    'POST' => [
        '/users/register' => [UserController::class, 'register', []],
        '/users/login' => [UserController::class, 'login', []],
        '/users' => [UserController::class, 'store', ['admin' => true]],
    ],
    'PUT' => [
        '/users/{id}' => [UserController::class, 'update', ['auth' => true]],
    ],
    'DELETE' => [
        '/users/{id}' => [UserController::class, 'delete', ['admin' => true]],
    ],
];
