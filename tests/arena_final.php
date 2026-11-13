<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
putenv('APP_URL=http://localhost/StreakFit');
require dirname(__DIR__).'/app/Helpers/functions.php';
require dirname(__DIR__).'/app/Core/Request.php';
function verify_final(bool $condition, string $name): void { if (!$condition) throw new RuntimeException($name); echo "PASS: $name\n"; }
foreach (['css/arena-v35.css','js/arena-ui.js','js/arena-v33.js'] as $file) {
    $expected = substr(hash_file('sha256', dirname(__DIR__).'/public/assets/'.$file), 0, 12);
    verify_final(sf_asset_version($file) === '/StreakFit/assets/'.$file.'?v='.$expected, 'asset revision '.$file);
}
$request = new App\Core\Request();
foreach (['/StreakFit/dashboard?view=community'=>'/dashboard','/StreakFit'=>'/','/StreakFit2/dashboard'=>'/StreakFit2/dashboard'] as $path=>$expected) {
    $_SERVER['REQUEST_URI']=$path;
    verify_final($request->path()===$expected, 'route '.$path);
}
$root=dirname(__DIR__);
foreach (['public/index.php','config/app.php','app/Views/dashboard/index.php'] as $file) {
    verify_final(stripos(file_get_contents($root.'/'.$file),'strava')===false,'no Strava in '.$file);
}
