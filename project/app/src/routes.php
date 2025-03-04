<?php

use src\UserController;
use src\AuthController;
use src\PageController;

return [
    'GET' => [
        '/users/register' => [AuthController::class, 'showRegistrationForm', []],
        '/users/captcha' => [UserController::class, 'showCaptcha', []],
        '/users/login' => [AuthController::class, 'showLoginForm', []],
        '/users/logout' => [AuthController::class, 'logout', ['auth' => true]],
        '/users/new' => [UserController::class, 'create', ['admin' => true]],
        '/users/{id}/edit' => [UserController::class, 'edit', ['auth' => true]],
        '/users/{id}' => [UserController::class, 'show', ['auth' => true]],
        '/users' => [UserController::class, 'index', ['admin' => true]],
        '/' => [PageController::class, 'read', []]
    ],
    'POST' => [
        '/users/register' => [AuthController::class, 'register', []],
        '/users/login' => [AuthController::class, 'login', []],
        '/users' => [UserController::class, 'store', ['admin' => true]],
    ],
    'PUT' => [
        '/users/{id}' => [UserController::class, 'update', ['auth' => true]],
    ],
    'DELETE' => [
        '/users/{id}' => [UserController::class, 'delete', ['admin' => true]],
    ],
];
