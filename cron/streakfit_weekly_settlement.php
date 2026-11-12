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

$db = App\Core\Database::connection();
$db->prepare("INSERT INTO cron_runs (task_name, status) VALUES ('weekly_settlement', 'running')")->execute();
$runId = (int)$db->lastInsertId();
try {
    $currentWeek = sf_week_start();
    $users = $db->query('SELECT id FROM users ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($users as $userId) sf_settle_user($db, (int)$userId, $currentWeek);
    $summary = sprintf('Processed overdue weeks for %d account(s).', count($users));
    $db->prepare("UPDATE cron_runs SET status = 'success', summary = ?, finished_at = UTC_TIMESTAMP() WHERE id = ?")->execute([$summary, $runId]);
    fwrite(STDOUT, $summary . PHP_EOL);
} catch (Throwable $error) {
    $db->prepare("UPDATE cron_runs SET status = 'failed', summary = ?, finished_at = UTC_TIMESTAMP() WHERE id = ?")->execute([mb_substr($error->getMessage(), 0, 1000), $runId]);
    error_log('StreakFit weekly settlement failed: ' . $error->getMessage());
    fwrite(STDERR, $error->getMessage() . PHP_EOL);
    exit(1);
}