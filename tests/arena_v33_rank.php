<?php
// No database connection, no writes. Run: php tests/arena_v33_rank.php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require dirname(__DIR__) . '/app/Helpers/functions.php';
$arenaIcon = static fn(string $icon): string => '';
$energyPanel = 'overview';
$cases = [0=>['Đồng',0,301], 300=>['Đồng',99.6,1], 301=>['Bạc',0,700], 1000=>['Bạc',99.8,1], 1001=>['Vàng',0,2000], 3001=>['Bạch Kim',0,4000], 4500=>['Bạch Kim',37.4,2501], 7001=>['Kim Cương',0,5000], 12001=>['Tinh Anh',0,7000], 19001=>['Cao Thủ',0,11000], 30000=>['Cao Thủ',99.9,1], 30001=>['Chiến Tướng',100,0], 50000=>['Chiến Tướng',100,0]];
foreach ($cases as $points => [$expectedRank, $expectedProgress, $expectedRemaining]) {
    $user = ['total_points'=>$points]; $rank = sf_rank($points);
    ob_start(); require dirname(__DIR__) . '/app/Views/components/arena-rank-energy.php'; $html = ob_get_clean();
    if ($rank['name'] !== $expectedRank || abs($energyPercent - $expectedProgress) > .0001 || $energyRemaining !== $expectedRemaining || substr_count($html, 'sf33-path-node ') !== 8) {
        fwrite(STDERR, "FAIL for $points points\n"); exit(1);
    }
}
echo 'PASS: ' . count($cases) . " rank boundaries, progress, remaining points, 8 rank nodes.\n";
