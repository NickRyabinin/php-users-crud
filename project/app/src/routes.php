<?php

return [
    'GET' => [
        '/users' => function($userController) {
            $userController->read();
        },
        '/users/{id}' => function($userController, $id) {
            $userController->read($id);
        },
    ],
    'POST' => [
        '/users' => function($userController) {
            $userController->create();
        },
    ],
    'PUT' => [
        '/users/{id}' => function($userController, $id) {
            $userController->update($id);
        },
    ],
    'DELETE' => [
        '/users/{id}' => function($userController, $id) {
            $userController->delete($id);
        },
    ],
];
