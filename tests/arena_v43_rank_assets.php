<?php
// Static current asset and rank data regression. No database access.
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require dirname(__DIR__).'/app/Helpers/functions.php';
$root=dirname(__DIR__).'/public/assets/ranks/';
$expected=['dong'=>0,'bac'=>301,'vang'=>1001,'bach-kim'=>3001,'kim-cuong'=>7001,'tinh-anh'=>12001,'cao-thu'=>19001,'chien-tuong'=>30001];
$actual=[];
foreach (sf_rank_catalog() as $rank) {
    $slug=$rank['slug']; $actual[$slug]=(int)$rank['min'];
    $img=$root.$slug.'-v44.webp'; $scene=$root.$slug.'-scene.svg';
    if (!is_file($img) || !is_file($scene)) throw new RuntimeException('Missing rank asset '.$slug);
    $size=getimagesize($img);
    if (!$size || $size[0]!==768 || $size[1]!==768 || $size['mime']!=='image/webp') throw new RuntimeException('Invalid artwork '.$slug);
    if (!str_contains(sf_rank_emblem($rank),$slug.'-v44.webp?v=')) throw new RuntimeException('Wrong helper asset '.$slug);
    echo "PASS: $slug artwork, scene and versioned URL\n";
}
if ($actual!==$expected) throw new RuntimeException('Rank thresholds changed');
echo "PASS: all 8 rank thresholds unchanged.\n";
