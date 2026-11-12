<?php
declare(strict_types=1);

$env = [];
$envPath = dirname(__DIR__) . '/.env';
if (is_file($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $env[$key] = trim($value, " \t\n\r\0\x0B\"'");
    }
}
$read = static function (string $key, string $fallback = '') use ($env): string {
    $value = getenv($key);
    return $value === false ? (string)($env[$key] ?? $fallback) : (string)$value;
};
$appUrl = rtrim($read('APP_URL', 'http://localhost/StreakFit'), '/');
$urlPath = (string)(parse_url($appUrl, PHP_URL_PATH) ?: '');

return [
    'name' => $read('APP_NAME', 'StreakFit'),
    'environment' => $read('APP_ENV', 'local'),
    'debug' => filter_var($read('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOL),
    'url' => $appUrl,
    'base_path' => rtrim($urlPath, '/'),
    'timezone' => $read('APP_TIMEZONE', 'Asia/Ho_Chi_Minh'),
    'workout_min_seconds' => 1800,
    'monthly_contribution' => 20000,
    'google_maps_key' => $read('GOOGLE_MAPS_API_KEY'),
    'payos_client_id' => $read('PAYOS_CLIENT_ID'),
    'payos_api_key' => $read('PAYOS_API_KEY'),
    'payos_checksum_key' => $read('PAYOS_CHECKSUM_KEY'),
];
