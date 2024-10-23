<?php

use src\UserController;
use src\PageController;

return [
    'GET' => [
        '/users/{id}' => [UserController::class, 'read'],
        '/users' => [UserController::class, 'read'],
        '/' => [PageController::class, 'read']
    ],
    'POST' => [
        '/users' => [UserController::class, 'create']
    ],
    'PUT' => [
        '/users/{id}' => [UserController::class, 'update']
    ],
    'DELETE' => [
        '/users/{id}' => [UserController::class, 'delete']
    ],
];
