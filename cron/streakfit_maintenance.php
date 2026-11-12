<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

define('APP_ROOT', dirname(__DIR__));
$config = require APP_ROOT . '/config/app.php';
date_default_timezone_set($config['timezone']);
require_once APP_ROOT . '/app/Helpers/functions.php';
spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) return;
    $file = APP_ROOT . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) require $file;
});

try {
    $summary = (new App\Services\MaintenanceService(App\Core\Database::connection(), $config['timezone']))->run();
    fwrite(STDOUT, json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);
} catch (Throwable $error) {
    error_log('StreakFit maintenance failed: ' . $error->getMessage());
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
}
