<?php
declare(strict_types=1);

$env = [];
$envPath = dirname(__DIR__) . '/.env';
if (is_file($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $env[$key] = trim($value, " \t\n\r\0\x0B\"'");
    }
}
$read = static function (string $key, string $fallback = '') use ($env): string {
    $value = getenv($key);
    return $value === false ? (string)($env[$key] ?? $fallback) : (string)$value;
};

return [
    'host' => $read('STREAKFIT_DB_HOST', 'localhost'),
    'port' => $read('STREAKFIT_DB_PORT', '3306'),
    'database' => $read('STREAKFIT_DB_DATABASE', 'streakfit'),
    'username' => $read('STREAKFIT_DB_USERNAME', 'root'),
    'password' => $read('STREAKFIT_DB_PASSWORD'),
    'charset' => 'utf8mb4',
];
