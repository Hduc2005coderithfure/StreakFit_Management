<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

define('APP_ROOT', dirname(__DIR__));
$config = require APP_ROOT . '/config/database.php';
$database = getenv('STREAKFIT_SEED_TEST_DB') ?: 'streakfit_test_seed';
if (!preg_match('/^streakfit_test_seed(?:_[a-z0-9_]+)?$/i', $database) || strcasecmp($database, $config['database']) === 0) {
    fwrite(STDERR, "Refusing to reset a database that is not a dedicated seed-test database.\n");
    exit(2);
}

$serverDsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $config['host'], $config['port']);
$server = new PDO($serverDsn, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$quotedDatabase = '`' . str_replace('`', '``', $database) . '`';
$server->exec('DROP DATABASE IF EXISTS ' . $quotedDatabase);
$server->exec('CREATE DATABASE ' . $quotedDatabase . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $config['host'], $config['port'], $database);
$db = new PDO($dsn, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$db->exec("SET time_zone = '+00:00'");

$schema = file_get_contents(APP_ROOT . '/database/streakfit.sql');
$schema = preg_replace('/\ACREATE DATABASE IF NOT EXISTS streakfit\s+CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\s*USE streakfit;\s*/', '', $schema, 1);
if ($schema === null || str_starts_with(ltrim($schema), 'USE streakfit;')) throw new RuntimeException('Could not safely isolate the schema from its production database name.');
foreach (array_filter(array_map('trim', explode(';', $schema))) as $statement) $db->exec($statement);
foreach (glob(APP_ROOT . '/database/migrations/*.sql') as $migration) {
    foreach (array_filter(array_map('trim', preg_split('/;\r?\n/', (string)file_get_contents($migration)))) as $statement) $db->exec($statement);
}

$users = (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn();
$workouts = (int)$db->query('SELECT COUNT(*) FROM workouts')->fetchColumn();
$badDates = (int)$db->query('SELECT COUNT(*) FROM workouts WHERE workout_date < week_start OR workout_date >= DATE_ADD(week_start, INTERVAL 7 DAY)')->fetchColumn();
$pendingWithoutProof = (int)$db->query("SELECT COUNT(*) FROM appeals WHERE status = 'pending' AND (proof_path IS NULL OR proof_path = '')")->fetchColumn();
if ($users !== 5 || $workouts !== 3 || $badDates !== 0 || $pendingWithoutProof !== 0) throw new RuntimeException('Full schema and demo seed verification failed.');

fwrite(STDOUT, sprintf("Full schema and demo seed import passed: %d users, %d workouts, no week/date mismatches. Database: %s\n", $users, $workouts, $database));
