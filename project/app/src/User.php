<?php

/**
 * Класс User - модель сущности 'User'.
 * Коммуницирует с БД, выполняя стандартные CRUD-операции.
 */

namespace src;

class User
{
    private string $entity = 'user';
    private array $fillableProperties = [
        'login',
        'email',
        'hashed_password',
        'profile_picture',
        'is_active',
        'role'
    ];
    private array $viewableProperties = [
        'id',
        'login',
        'email',
        'hashed_password',
        'last_login',
        'profile_picture',
        'is_active',
        'created_at',
        'role'
    ];
    private \PDO $pdo;
    private Logger $logger;

    public function __construct(\PDO $pdo, Logger $logger)
    {
        $this->pdo = $pdo;
        $this->logger = $logger;
    }

    public function __toString(): string
    {
        return $this->entity;
    }

    public function index(int $page = 1, array $searchParams = []): array
    {
        $offset = ($page - 1) * 10;
        $columns = implode(' ,', $this->viewableProperties);
        $query = "SELECT {$columns} FROM {$this->entity}s WHERE 1=1";
        $params = [];

        if (!empty($searchParams['login'])) {
            $query .= " AND login LIKE :login";
            $params[':login'] = "%{$searchParams['login']}%";
        }
        if (!empty($searchParams['email'])) {
            $query .= " AND email LIKE :email";
            $params[':email'] = "%{$searchParams['email']}%";
        }
        if (!empty($searchParams['last_login'])) {
            $query .= " AND last_login >= :last_login_start AND last_login < :last_login_end";
            $params[':last_login_start'] = $searchParams['last_login'] . ' 00:00:00';
            $params[':last_login_end'] = $searchParams['last_login'] . ' 23:59:59';
        }
        if (!empty($searchParams['created_at'])) {
            $query .= " AND created_at >= :created_at_start AND created_at < :created_at_end";
            $params[':created_at_start'] = $searchParams['created_at'] . ' 00:00:00';
            $params[':created_at_end'] = $searchParams['created_at'] . ' 23:59:59';
        }
        if (!empty($searchParams['role'])) {
            $query .= " AND role = :role";
            $params[':role'] = $searchParams['role'];
        }
        if (!empty($searchParams['is_active'])) {
            $query .= " AND is_active = :is_active";
            $params[':is_active'] = $searchParams['is_active'];
        }

        $query .= " ORDER BY id LIMIT 10 OFFSET {$offset}";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        $result = [
            'total' => $this->getTotalRecords($searchParams),
            'offset' => $offset,
            'limit' => 10,
            'items' => []
        ];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $result['items'][] = $row;
        }
        return $result;
    }

    public function show(int $id): array
    {
        if (!$this->checkId($id)) {
            return [];
        };

        $columns = implode(' ,', $this->viewableProperties);
        $query = "SELECT {$columns} FROM {$this->entity}s WHERE id = :id";

        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([':id' => $id]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->logger->log($e->getMessage());
            return [];
        }
    }

    public function store(array $data): bool
    {
        // Проверка на наличие обязательных полей
        $filteredData = array_intersect_key($data, array_flip($this->fillableProperties));
        if (count($filteredData) !== count($this->fillableProperties)) {
            return false;
        }

        $columns = implode(', ', array_keys($filteredData));
        $placeholders = ':' . implode(', :', array_keys($filteredData));

        $query = "INSERT INTO {$this->entity}s ($columns) VALUES ($placeholders)";

        try {
            $stmt = $this->pdo->prepare($query);
            foreach ($filteredData as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }
            return $stmt->execute();
        } catch (\PDOException $e) {
            $this->logger->log($e->getMessage());
            return false;
        }
    }

    public function update(int $id, array $data): bool
    {
        $filteredData = array_intersect_key($data, array_flip($this->fillableProperties));
        if (count($filteredData) === 0 || !$this->checkId($id)) {
            return false;
        }

        $query = "UPDATE {$this->entity}s SET";
        foreach ($filteredData as $key => $value) {
            $query = $query . " {$key} = :{$key},";
        }
        $query = rtrim($query, ',') . " WHERE id = :id";

        try {
            $stmt = $this->pdo->prepare($query);
            foreach ($filteredData as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }
            $stmt->bindValue(":id", $id);
            return $stmt->execute();
        } catch (\PDOException $e) {
            $this->logger->log($e->getMessage());
            return false;
        }
    }

    public function destroy(int $id): bool
    {
        if (!$this->checkId($id)) {
            return false;
        };

        $query = "DELETE FROM {$this->entity}s WHERE id= :id";
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(":id", $id);
            return $stmt->execute();
        } catch (\PDOException $e) {
            $this->logger->log($e->getMessage());
            return false;
        }
    }

    private function checkId(int $id): bool
    {
        $query = "SELECT EXISTS (SELECT id FROM {$this->entity}s WHERE id = :id) AS isexists";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':id' => $id]);

        return ($stmt->fetch())['isexists'];
    }

    public function getValue(string $model, string $field, string $conditionKey, string $conditionValue): mixed
    {
        $query = "SELECT {$field} AS \"result\" FROM {$model}s WHERE {$conditionKey} = :{$conditionKey}";
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([":{$conditionKey}" => $conditionValue]);
        } catch (\PDOException $e) {
            $this->logger->log($e->getMessage());
            return false;
        }
        return $stmt->fetch(\PDO::FETCH_ASSOC)['result'] ?? false;
    }

    private function getTotalRecords(array $searchParams): int
    {
        $query = "SELECT COUNT(*) FROM {$this->entity}s WHERE 1=1";
        $params = [];

        if (!empty($searchParams['login'])) {
            $query .= " AND login LIKE :login";
            $params[':login'] = "%{$searchParams['login']}%";
        }
        if (!empty($searchParams['email'])) {
            $query .= " AND email LIKE :email";
            $params[':email'] = "%{$searchParams['email']}%";
        }
        if (!empty($searchParams['last_login'])) {
            $query .= " AND last_login >= :last_login_start AND last_login < :last_login_end";
            $params[':last_login_start'] = $searchParams['last_login'] . ' 00:00:00';
            $params[':last_login_end'] = $searchParams['last_login'] . ' 23:59:59';
        }
        if (!empty($searchParams['created_at'])) {
            $query .= " AND created_at >= :created_at_start AND created_at < :created_at_end";
            $params[':created_at_start'] = $searchParams['created_at'] . ' 00:00:00';
            $params[':created_at_end'] = $searchParams['created_at'] . ' 23:59:59';
        }
        if (!empty($searchParams['role'])) {
            $query .= " AND role = :role";
            $params[':role'] = $searchParams['role'];
        }
        if (!empty($searchParams['is_active'])) {
            $query .= " AND is_active = :is_active";
            $params[':is_active'] = $searchParams['is_active'];
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function updateLastLogin(string $email): bool
    {
        $query = "UPDATE {$this->entity}s SET last_login = NOW() WHERE email = :email";
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->bindValue(':email', $email);
            return $stmt->execute();
        } catch (\PDOException $e) {
            $this->logger->log($e->getMessage());
            return false;
        }
    }
}
