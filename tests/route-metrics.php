<?php
require __DIR__.'/../app/Services/RouteMetrics.php';
$p=[['lat'=>0,'lng'=>0,'t'=>0,'accuracy'=>5],['lat'=>0,'lng'=>0.001,'t'=>20000,'accuracy'=>5]];
$n=0;
$check=static function(bool $ok,string $message)use(&$n){if(!$ok)throw new RuntimeException($message);$n++;};
$r=App\Services\RouteMetrics::calculate($p,'running');
$check(abs($r['distance_meters']-111.19)<0.1&&$r['tracked_seconds']===20,'known equatorial distance');
$p[1]['segment_start']=1;$check(App\Services\RouteMetrics::calculate($p,'running')['distance_meters']===0.0,'pause boundary');
$p[1]['segment_start']=0;$p[1]['t']=120000;$check(App\Services\RouteMetrics::calculate($p,'cycling')['distance_meters']===0.0,'signal gap');
$p[1]['t']=5000;$check(App\Services\RouteMetrics::calculate($p,'running')['distance_meters']===0.0,'implausible running speed');
$check(App\Services\RouteMetrics::calculate($p,'cycling')['distance_meters']>111,'plausible cycling speed');
$p[1]['accuracy']=60;$check(App\Services\RouteMetrics::calculate($p,'cycling')['distance_meters']===0.0,'inaccurate point');
$p[1]['accuracy']=5;$p[1]['lng']=0.000001;$check(App\Services\RouteMetrics::calculate($p,'running')['distance_meters']===0.0,'stationary jitter');
echo "PHP route metrics: $n passed.\n";
