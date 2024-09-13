<?php

/**
 * Единая точка входа в приложение.
 * Выполняет подключение к БД, миграцию (только создание таблиц),
 * добавляет в роутер существующие маршруты, запускает роутинг.
 */

/**
 * Composer для автозагрузки классов не применяется - используется
 * spl_autoload_register()
 */

spl_autoload_register(function ($className) {
    $file = __DIR__ . '/' . str_replace('\\', '/', $className) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use src\Database;
use src\Router;
use src\UserController;

$userController = new UserController();
$router = new Router();

const ENV_FILE_PATH = __DIR__ . '/.env';
const MIGRATION_PATH = __DIR__ . '/src/migrations/migration.sql';

$pdo = Database::get()->connect(ENV_FILE_PATH);
Database::get()->migrate($pdo, MIGRATION_PATH);

// Добавление маршрутов
$router->addRoute('GET', '/users', function() use ($userController) {
    $userController->read();
});
$router->addRoute('GET', '/users/{id}', function($id) use ($userController) {
    $userController->read($id);
});
$router->addRoute('POST', '/users', function() use ($userController) {
    $userController->create();
});
$router->addRoute('PUT', '/users/{id}', function($id) use ($userController) {
    $userController->update($id);
});
$router->addRoute('DELETE', '/users/{id}', function($id) use ($userController) {
    $userController->delete($id);
});

$router->route();
