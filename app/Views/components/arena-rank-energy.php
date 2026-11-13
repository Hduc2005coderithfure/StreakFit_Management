<?php
// Display only: rank thresholds come from the same authoritative PHP catalog as sf_rank().
$energyPanel = $energyPanel ?? 'overview';
$energyPoints = max(0, (int)$user['total_points']);
$energyMax = $rank['next'] === null;
$energyNext = $energyMax ? $rank : sf_rank((int)$rank['next']);
$energySpan = $energyMax ? 0 : (int)$rank['next'] - (int)$rank['min'];
$energyEarned = $energyMax ? 0 : max(0, min($energySpan, $energyPoints - (int)$rank['min']));
$energyRemaining = $energyMax ? 0 : max(0, (int)$rank['next'] - $energyPoints);
// Floor to one decimal so 100% is never displayed before the next threshold.
$energyPercent = $energyMax ? 100 : ($energySpan > 0 ? floor(1000 * $energyEarned / $energySpan) / 10 : 0);
$energyFormat = static fn(int $v): string => number_format($v, 0, ',', '.');
$energyColors = ['dong'=>'#e9a16a','bac'=>'#c5e2f1','vang'=>'#ffcc66','bach-kim'=>'#89e8e3','kim-cuong'=>'#67cfff','tinh-anh'=>'#c2a0ff','cao-thu'=>'#ff945e','chien-tuong'=>'#ffcf77'];
?>
<section class="sf33-energy" data-arena-panel="<?= sf_h($energyPanel) ?>" data-sf33-energy style="--energy-accent:<?= sf_h($energyColors[$rank['slug']] ?? '#ffc475') ?>;--energy-progress:<?= $energyPercent ?>%" aria-label="Năng lượng thăng rank">
    <div class="sf33-energy-head"><div><div class="arena-kicker">ASCENSION / RANK ENERGY</div><h2>Năng lượng <em>thăng hạng.</em></h2><p>Điểm tích lũy thật · 1 điểm = 1 đơn vị năng lượng.</p></div><div class="sf33-energy-total"><small>TỔNG ĐIỂM</small><strong><?= $energyFormat($energyPoints) ?></strong><span>XP</span></div></div>
    <div class="sf33-energy-duel"><div class="sf33-energy-rank current"><?= sf_rank_emblem($rank, 'sf33-energy-emblem') ?><div><small>RANK HIỆN TẠI</small><strong><?= sf_h($rank['name']) ?></strong><span><?= $energyFormat((int)$rank['min']) ?>+ điểm</span></div></div><div class="sf33-energy-arrow" aria-hidden="true">›››</div><div class="sf33-energy-rank next"><?= sf_rank_emblem($energyNext, 'sf33-energy-emblem') ?><div><small><?= $energyMax ? 'ĐỈNH VINH QUANG' : 'RANK KẾ TIẾP' ?></small><strong><?= sf_h($energyNext['name']) ?></strong><span><?= $energyMax ? 'Đã mở khóa' : $energyFormat((int)$energyNext['min']) . '+ điểm' ?></span></div></div></div>
    <div class="sf33-energy-readout"><div><strong><?= $energyMax ? 'MAX' : number_format($energyPercent, $energyPercent == floor($energyPercent) ? 0 : 1, ',', '.') . '%' ?></strong><span><?= $energyMax ? 'Đã đạt cấp cao nhất' : $energyFormat($energyEarned) . ' / ' . $energyFormat($energySpan) . ' năng lượng' ?></span></div><span class="sf33-energy-remaining"><?= $energyMax ? 'CHIẾN TƯỚNG' : 'Còn ' . $energyFormat($energyRemaining) . ' điểm' ?></span></div>
    <div class="sf33-energy-track" role="progressbar" aria-label="Tiến độ lên rank tiếp theo" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $energyPercent ?>" aria-valuetext="<?= sf_h($energyMax ? 'Đã đạt rank tối đa' : $energyFormat($energyEarned) . ' trên ' . $energyFormat($energySpan) . ' điểm trong cấp hiện tại') ?>"><span class="sf33-energy-fill"><i></i></span><span class="sf33-energy-segments" aria-hidden="true"></span></div>
    <div class="sf33-energy-scale"><span><?= $energyMax ? 'Rank tối đa' : $energyFormat((int)$rank['min']) . ' điểm' ?></span><span><?= $energyMax ? $energyFormat($energyPoints) . ' điểm tích lũy' : $energyFormat((int)$rank['next']) . ' điểm' ?></span></div>
    <div class="sf33-energy-path" aria-label="Hành trình tám cấp bậc"><?php foreach (sf_rank_catalog() as $energyTier): $energyState = $energyTier['slug'] === $rank['slug'] ? 'current' : ($energyPoints >= $energyTier['min'] ? 'unlocked' : 'locked'); ?><a class="sf33-path-node <?= $energyState ?>" href="<?= sf_h(url('leaderboard?view=rank&inspect=' . $energyTier['slug'])) ?>" <?= $energyState === 'current' ? 'aria-current="true"' : '' ?> title="<?= sf_h($energyTier['name'] . ' · ' . $energyFormat((int)$energyTier['min']) . ' điểm') ?>"><?= sf_rank_emblem($energyTier, 'sf33-path-emblem') ?><span><?= sf_h($energyTier['name']) ?></span></a><?php endforeach; ?></div>
    <div class="sf33-energy-foot"><p><?= $energyMax ? 'Bạn đã mở khóa toàn bộ cấp bậc. Tiếp tục giữ chuỗi và tích lũy thành tích.' : 'Năng lượng chỉ tăng khi tổng điểm được máy chủ ghi nhận. Điểm tuần tạm tính chưa được cộng vào rank.' ?></p><?php if ($energyPanel !== 'rank'): ?><a href="<?= sf_h(url('leaderboard?view=rank')) ?>">Đại sảnh rank <?= $arenaIcon('rank') ?></a><?php endif; ?></div>
</section>
