<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class User extends Model
{
    protected function table(): string { return 'users'; }
    public function byEmail(string $email): array|false { return $this->one('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]); }
    public function create(string $email, string $passwordHash, string $name, string $role = 'member'): int
    {
        $stmt = $this->db->prepare('INSERT INTO users (email, password_hash, display_name, role) VALUES (?, ?, ?, ?)');
        $stmt->execute([$email, $passwordHash, $name, $role]); return (int)$this->db->lastInsertId();
    }
}
