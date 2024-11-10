<?php

use src\UserController;
use src\PageController;

return [
    'GET' => [
        '/users/register' => [UserController::class, 'showRegistrationForm'],
        '/users/captcha' => [UserController::class, 'showCaptcha'],
        '/users/login' => [UserController::class, 'login'],
        '/users/new' => [UserController::class, 'create'],
        '/users/{id}/edit' => [UserController::class, 'edit'],
        '/users/{id}' => [UserController::class, 'show'],
        '/users' => [UserController::class, 'index'],
        '/' => [PageController::class, 'read']
    ],
    'POST' => [
        '/users/register' => [UserController::class, 'register'],
        '/users' => [UserController::class, 'store']
    ],
    'PUT' => [
        '/users/{id}' => [UserController::class, 'update']
    ],
    'DELETE' => [
        '/users/{id}' => [UserController::class, 'delete']
    ],
];
