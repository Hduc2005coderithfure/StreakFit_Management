<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\User;

final class Auth
{
    public function __construct(private readonly User $users) {}
    public function user(): array|false { return Session::userId() ? $this->users->find(Session::userId()) : false; }
    public function check(): bool { return Session::userId() > 0; }
    public function login(string $email, string $password): bool
    {
        $user = $this->users->byEmail(mb_strtolower(trim($email)));
        if (!$user || !(int)($user['is_active'] ?? 1) || !password_verify($password, (string)$user['password_hash'])) return false;
        Session::login((int)$user['id']); return true;
    }
    public function requireRole(string $role): bool { $user = $this->user(); return is_array($user) && $user['role'] === $role; }
}
