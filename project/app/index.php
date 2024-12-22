<?php

/**
 * Единая точка входа в приложение.
 * Выполняет подключение к БД, миграцию (только создание таблиц),
 * создание экземпляров сущностей, инжекцию зависимостей,
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

use src\Auth;
use src\Captcha;
use src\Database;
use src\Flash;
use src\PageController;
use src\Router;
use src\UserController;
use src\User;
use src\View;
use src\Request;
use src\Response;
use src\Validator;

session_start();

const ENV_FILE_PATH = __DIR__ . '/.env';
const MIGRATION_PATH = __DIR__ . '/src/migrations/migration.sql';
const ROUTES_PATH = __DIR__ . '/src/routes.php';
const TEMPLATES_PATH = __DIR__ . '/templates/';

// Подключение к БД и миграция
$pdo = Database::get()->connect(ENV_FILE_PATH);
Database::get()->migrate($pdo, MIGRATION_PATH);

// Создание экземпляров сущностей
$request = new Request();
$router = new Router($request);
$view = new View(TEMPLATES_PATH);
$captcha = new Captcha();
$flash = new Flash();
$response = new Response($flash);
$user = new User($pdo);
$validator = new Validator($user);
$auth = new Auth();
$userController = new UserController(
    [
        'request' => $request,
        'response' => $response,
        'user' => $user,
        'view' => $view,
        'captcha' => $captcha,
        'flash' => $flash,
        'validator' => $validator,
        'auth' => $auth,
    ]
);
$pageController = new PageController(
    [
        'view' => $view,
        'flash' => $flash,
    ]
);

$controllers = [
    UserController::class => $userController,
    PageController::class => $pageController,
];

// Загрузка маршрутов из файла конфигурации
if (file_exists(ROUTES_PATH)) {
    $routes = require ROUTES_PATH;
} else {
    http_response_code(500);
    echo json_encode(['message' => 'Файл маршрутов не найден.']);
    exit;
}

$router->loadRoutes($routes, $controllers);

// Запуск маршрутизации
$router->route();
