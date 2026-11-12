<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
$config = require APP_ROOT . '/config/app.php';
date_default_timezone_set($config['timezone']);

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $file = APP_ROOT . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) require $file;
});
require_once APP_ROOT . '/app/Helpers/functions.php';

\App\Core\Session::start();
$request = new \App\Core\Request();
$router = new \App\Core\Router($request);
$legacy = new \App\Controllers\LegacyApplicationController();
$auth = new \App\Controllers\AuthController($legacy);
$home = new \App\Controllers\HomeController($legacy);
$dashboard = new \App\Controllers\DashboardController($legacy);
$workouts = new \App\Controllers\WorkoutController($legacy);
$court = new \App\Controllers\CourtController($legacy);
$leaderboard = new \App\Controllers\LeaderboardController($legacy);
$community = new \App\Controllers\CommunityController();
foreach (['/groups'=>'groups','/admin/groups'=>'admin_groups','/fund/payments'=>'payments','/admin/payments'=>'admin_payments','/workouts/route'=>'route'] as $path=>$page) {
    $router->get($path, static fn($request) => $community->handle($request,$page));
    $router->post($path, static fn($request) => $community->handle($request,$page));
}
$router->post('/api/payos/webhook', [$community,'webhook']);
$router->post('/api/workout-route', [$community,'track']);

$router->get('/', [$home, 'index']);
$router->post('/', [$home, 'index']);
$router->get('/login', [$auth, 'login']);
$router->post('/login', [$auth, 'login']);
$router->get('/register', [$auth, 'register']);
$router->post('/register', [$auth, 'register']);
$router->get('/forgot-password', [$auth, 'forgot']);
$router->post('/forgot-password', [$auth, 'forgot']);
$router->get('/reset-password', [$auth, 'reset']);
$router->post('/reset-password', [$auth, 'reset']);
$router->get('/dashboard', [$dashboard, 'index']);
$router->post('/dashboard', [$dashboard, 'index']);
$router->get('/workouts', [$workouts, 'index']);
$router->post('/workouts', [$workouts, 'index']);
$router->get('/workouts/history', [$workouts, 'history']);
$router->post('/workouts/history', [$workouts, 'history']);
$router->get('/court', [$court, 'index']);
$router->post('/court', [$court, 'index']);
$router->get('/leaderboard', [$leaderboard, 'index']);
$router->post('/leaderboard', [$leaderboard, 'index']);
$router->get('/notifications', static function ($request) use ($legacy): void { $legacy->handle($request, 'notifications'); });
$router->post('/notifications', static function ($request) use ($legacy): void { $legacy->handle($request, 'notifications'); });
$router->get('/profile', static function ($request) use ($legacy): void { $legacy->handle($request, 'profile'); });
$router->post('/profile', static function ($request) use ($legacy): void { $legacy->handle($request, 'profile'); });
$router->get('/admin', static function ($request) use ($legacy): void { $legacy->handle($request, 'admin'); });
$router->post('/admin', static function ($request) use ($legacy): void { $legacy->handle($request, 'admin'); });
$router->post('/api/action', static function ($request): \App\Core\Response {
    return (new \App\Controllers\Api\ActionController(\App\Core\Database::connection()))->index($request);
});

$response = $router->dispatch();
if ($response !== null) $response->send();
