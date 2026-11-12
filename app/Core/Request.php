<?php
declare(strict_types=1);

namespace App\Core;

final class Request
{
    public function method(): string { return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'); }
    public function path(): string
    {
        $path = rawurldecode((string)(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'));
        $base = (string)(require dirname(__DIR__, 2) . '/config/app.php')['base_path'];
        if ($base !== '' && str_starts_with($path, $base)) $path = substr($path, strlen($base)) ?: '/';
        return '/' . trim($path, '/');
    }
    public function query(string $key, mixed $default = null): mixed { return $_GET[$key] ?? $default; }
    public function input(string $key, mixed $default = null): mixed { return $_POST[$key] ?? $default; }
    public function all(): array { return $_POST; }
    public function files(): array { return $_FILES; }
    public function isAjax(): bool { return strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest'; }
}
