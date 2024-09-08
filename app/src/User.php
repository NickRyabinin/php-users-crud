<?php

namespace src;

class User
{
    public string $entity = 'user';
    protected array $fillableProperties = [
        'login', 'email', 'hashed_password',
        'profile_picture', 'is_active'
    ];
    protected array $viewableProperties = [
        'id', 'login', 'email', 'hashed_password', 'last_login',
        'profile_picture', 'is_active', 'created_at', 'role'
    ];
    protected \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __toString(): string
    {
        return $this->entity;
    }

    public function index(string $id, string $page): array
    {
        $offset = ((int)$page - 1) * 10;
        $columns = implode(' ,', $this->viewableProperties);
        $query = "SELECT {$columns} FROM {$this->entity}s LIMIT 10 OFFSET {$offset}";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        $result = [
            'total' => $this->getTotalRecords(),
            'offset' => $offset,
            'limit' => 10,
            'items' => []
        ];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $result['items'][] = $row;
        }
        return $result;
    }

    public function show(string $id): array
    {
        $columns = implode(' ,', $this->viewableProperties);
        $query = "SELECT {$columns} FROM {$this->entity}s WHERE id = :id";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':id' => $id]);
        return [$stmt->fetch(\PDO::FETCH_ASSOC)];
    }

    public function store(array $data): bool
    {
        $this->compare($this->fillableProperties, $data);
        $query = "INSERT INTO {$this->entity}s (title, author, published_at)
            VALUES (:title, :author, :published_at)";
        try {
            $stmt = $this->pdo->prepare($query);
            foreach ($data as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }
            $stmt->execute();
        } catch (\PDOException $e) {
            throw new InvalidDataException();
        }
        return true;
    }

    public function update(string $id, array $data): bool
    {
        $filteredData = array_intersect_key($data, array_flip($this->fillableProperties));
        $this->checkId($id);
        if (count($filteredData) === 0) {
            throw new InvalidDataException();
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
            $stmt->execute();
        } catch (\PDOException $e) {
            throw new InvalidDataException();
        }
        return true;
    }

    public function destroy(string $id): bool
    {
        $this->checkId($id);
        $query = "DELETE FROM {$this->entity}s WHERE id= :id";
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
        } catch (\PDOException $e) {
            throw new InvalidDataException();
        }
        return true;
    }

    protected function checkId(string $id): void
    {
        $query = "SELECT EXISTS (SELECT id FROM {$this->entity}s WHERE id = :id) AS isExists";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':id' => $id]);
        if (($stmt->fetch())['isExists'] === 0) {
            throw new InvalidIdException();
        }
    }

    protected function compare(array $properties, array $input): void
    {
        if (!(count($properties) === count($input) && array_diff($properties, array_keys($input)) === [])) {
            throw new InvalidDataException();
        };
    }

    protected function getValue(string $model, string $field, string $conditionKey, string $conditionValue): mixed
    {
        $query = "SELECT {$field} AS 'result' FROM {$model}s WHERE {$conditionKey} = :{$conditionKey}";
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([":{$conditionKey}" => $conditionValue]);
        } catch (\PDOException $e) {
            throw new InvalidDataException();
        }
        return $stmt->fetch(\PDO::FETCH_ASSOC)['result'];
    }

    protected function getTotalRecords(): int
    {
        $query = "SELECT COUNT(*) FROM {$this->entity}s";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchColumn();
    }
}
