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
use src\AuthMiddleware;
use src\Captcha;
use src\Database;
use src\FileHandler;
use src\Flash;
use src\PageController;
use src\Router;
use src\UserController;
use src\AuthController;
use src\User;
use src\View;
use src\Request;
use src\Response;
use src\Validator;
use src\Logger;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const ENV_FILE_PATH = __DIR__ . '/.env';
const MIGRATION_PATH = __DIR__ . '/src/migrations/migration.sql';
const ROUTES_PATH = __DIR__ . '/src/routes.php';
const TEMPLATES_PATH = __DIR__ . '/templates/';
const FONT_PATH = __DIR__ . '/assets/fonts/OpenSans-Regular.ttf';
const LOG_PATH = __DIR__ . '/logs/error.log';

const SERVER_UPLOAD_DIR = __DIR__ . '/assets/avatars/';

const MAX_LOGIN_ATTEMPTS = 5;
const LOGIN_BLOCK_TIME = 15 * 60; // 15 минут в секундах

// Подключение к БД и миграция
$pdo = Database::get()->connect(ENV_FILE_PATH);
Database::get()->migrate($pdo, MIGRATION_PATH);

// Создание экземпляров сущностей
$logger = new Logger(LOG_PATH);
$captcha = new Captcha(FONT_PATH);
$flash = new Flash();
$request = new Request();
$response = new Response($flash);
$auth = new Auth(MAX_LOGIN_ATTEMPTS, LOGIN_BLOCK_TIME);
$authMiddleware = new AuthMiddleware($auth, $response);
$router = new Router($request, $authMiddleware);
$view = new View(TEMPLATES_PATH);
$user = new User($pdo, $logger);
$fileHandler = new FileHandler(SERVER_UPLOAD_DIR);
$validator = new Validator($user, $fileHandler);
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
        'fileHandler' => $fileHandler,
        'logger' => $logger,
    ]
);
$authController = new AuthController(
    [
        'request' => $request,
        'response' => $response,
        'user' => $user,
        'view' => $view,
        'captcha' => $captcha,
        'flash' => $flash,
        'validator' => $validator,
        'auth' => $auth,
        'fileHandler' => $fileHandler,
        'logger' => $logger,
    ]
);
$pageController = new PageController(
    [
        'request' => $request,
        'response' => $response,
        'view' => $view,
        'flash' => $flash,
        'auth' => $auth,
    ]
);

$controllers = [
    UserController::class => $userController,
    AuthController::class => $authController,
    PageController::class => $pageController,
];

// Загрузка маршрутов из файла конфигурации
if (file_exists(ROUTES_PATH)) {
    $routes = require ROUTES_PATH;
} else {
    // надо заменить этот код на переброс к странице-заглушке
    http_response_code(500);
    echo json_encode(['message' => 'Файл маршрутов не найден.']);
    exit;
}

$router->loadRoutes($routes, $controllers);

// Запуск маршрутизации
$router->route();
