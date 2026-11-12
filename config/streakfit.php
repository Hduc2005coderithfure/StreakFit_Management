<?php
declare(strict_types=1);

$app = require __DIR__ . '/app.php';
$database = require __DIR__ . '/database.php';

return [
    'timezone' => $app['timezone'],
    'app_url' => $app['url'],
    'base_path' => $app['base_path'],
    'db_host' => $database['host'],
    'db_port' => $database['port'],
    'db_name' => $database['database'],
    'db_user' => $database['username'],
    'db_password' => $database['password'],
];
