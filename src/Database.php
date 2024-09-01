<?php

/**
 * Класс Database - singleton класс для подключения к БД через PDO.
 * Параметры подключения берёт из файла .env в директории уровнем выше.
 * В случае отсутствия этого файла - из переменной окружения DATABASE_URL
 * вида pdoDBType://user:password@host:port/dbName
 */

namespace src;

use PDO;

final class Database
{
    private const ENV_FILE_PATH = dirname(__DIR__) . '/.env';
    private static ?Database $connection = null;

    private function __construct() {}

    private function __clone() {}

    public function connect(): PDO
    {
        $databaseUrl = $this->getDatabaseConfig();

        $dbType = $databaseUrl['scheme'] ?? '';
        $username = $databaseUrl['user'] ?? '';
        $password = $databaseUrl['pass'] ?? '';
        $host = $databaseUrl['host'] ?? '';
        $port = $databaseUrl['port'] ?? '';
        $dbName = ltrim($databaseUrl['path'] ?? '', '/');
        $dsn = "{$dbType}:host={$host};port={$port};dbname={$dbName}";
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        ];

        try {
            $pdo = new \PDO($dsn, $username, $password, $options);
        } catch (\PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }

        return $pdo;
    }

    private function getDatabaseConfig(): array
    {
        $databaseUrl = [];
        $envFilePath = self::ENV_FILE_PATH;

        if (file_exists($envFilePath)) {
            $envContent = file_get_contents($envFilePath);
            $lines = explode("\n", $envContent);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || strpos($line, '#') === 0) {
                    continue;
                }
                list($key, $value) = explode('=', $line, 2);
                $databaseUrl[trim($key)] = trim($value);
            }
        } else {
            $databaseUrl = parse_url((string) getenv('DATABASE_URL'));
        }

        return $databaseUrl;
    }

    public function migrate(\PDO $pdo, string $migrationPath): void
    {
        try {
            $migration = file_get_contents($migrationPath);
            $statements = explode("\r\n\r\n", $migration);
            foreach ($statements as $statement) {
                $pdo->exec($statement);
            }
        } catch (\PDOException $e) {
            die("Database migration failed: " . $e->getMessage());
        }
    }

    public static function get(): Database
    {
        if (static::$connection === null) {
            static::$connection = new static();
        }
        return static::$connection;
    }
}
