<?php
declare(strict_types=1);

namespace App\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        session_set_cookie_params(['httponly' => true, 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'samesite' => 'Lax', 'path' => (string)(require dirname(__DIR__, 2) . '/config/app.php')['base_path'] . '/']);
        session_start();
    }
    public static function userId(): int { return (int)($_SESSION['sf_user_id'] ?? 0); }
    public static function login(int $userId): void { session_regenerate_id(true); $_SESSION['sf_user_id'] = $userId; }
    public static function logout(): void { unset($_SESSION['sf_user_id']); session_regenerate_id(true); }
    public static function flash(string $key, mixed $value = null): mixed
    {
        if (func_num_args() === 2) { $_SESSION['_flash'][$key] = $value; return null; }
        $value = $_SESSION['_flash'][$key] ?? null; unset($_SESSION['_flash'][$key]); return $value;
    }
}
