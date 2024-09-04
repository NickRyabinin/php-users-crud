<?php

/**
 * Единая точка входа в приложение.
 * Запускает подключение к БД, миграцию (только создание таблиц).
 */

/**
 * Composer для автозагрузки классов не применяется - используется
 * spl_autoload_register()
 */

spl_autoload_register(function ($className) {
    $file = __DIR__ . '/../' . str_replace('\\', '/', $className) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

use app\src\Database;

const MIGRATION_PATH = __DIR__ . '/src/migrations/migration.sql';

$pdo = Database::get()->connect();
Database::get()->migrate($pdo, MIGRATION_PATH);
