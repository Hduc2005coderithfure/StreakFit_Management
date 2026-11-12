<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

abstract class Model
{
    public function __construct(protected readonly PDO $db) {}
    abstract protected function table(): string;
    protected function one(string $sql, array $params = []): array|false
    {
        $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetch();
    }
    protected function many(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll();
    }
    public function find(int $id): array|false { return $this->one('SELECT * FROM ' . $this->table() . ' WHERE id = ? LIMIT 1', [$id]); }
}
