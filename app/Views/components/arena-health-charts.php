<?php
// Private measurements, sourced exclusively from this member's existing health_metrics rows.
$sf38Measurements = [];
foreach (array_reverse($healthHistory ?? []) as $m) {
    $w = (float)($m['weight_kg'] ?? 0); $h = (float)($m['height_cm'] ?? 0);
    if ($w <= 0 || $h <= 0 || empty($m['recorded_at'])) continue;
    $sf38Measurements[] = ['date'=>sf_format_time($m['recorded_at'], 'd/m H:i'), 'weight'=>$w, 'height'=>$h, 'bmi'=>round($w / (($h/100)**2), 1)];
}
$sf38Fmt = static fn($v): string => $v === null ? '—' : number_format((float)$v, 1, ',', '.');
$sf38Last = $sf38Measurements ? $sf38Measurements[count($sf38Measurements)-1] : null;
$sf38First = $sf38Measurements[0] ?? null;
?>
<section class="sf38-health sf38-card" <?= $tab === 'feed' ? 'data-arena-panel="insights"' : '' ?> aria-label="Theo dõi sức khỏe">
    <header class="sf38-heading"><div><span class="sf38-kicker">SỨC KHỎE CÁ NHÂN</span><h2>Chỉ số cơ thể</h2></div><?php if ($tab==='feed'): ?><a href="<?= sf_h(url('profile?section=health#health')) ?>" class="sf38-link">Cập nhật ↗</a><?php endif; ?></header>
    <div class="sf38-vitals">
        <div><span>Cân nặng</span><strong><?= $sf38Fmt($weight > 0 ? $weight : null) ?> <small>kg</small></strong></div>
        <div><span>Chiều cao</span><strong><?= $sf38Fmt($height > 0 ? $height : null) ?> <small>cm</small></strong></div>
        <div><span>BMI tham khảo</span><strong><?= $sf38Fmt($bmi) ?></strong></div>
        <div><span>BMR ước tính</span><strong><?= $bmr === null ? '—' : number_format($bmr, 0, ',', '.') ?> <small>kcal/ngày</small></strong></div>
    </div>
    <?php if ($sf38Measurements): ?>
    <div class="sf38-chart-switch" role="group" aria-label="Chọn biểu đồ">
        <?php foreach (['weight'=>'Cân nặng','bmi'=>'BMI','height'=>'Chiều cao'] as $key=>$label): ?><button type="button" data-sf38-chart="<?= $key ?>" aria-pressed="<?= $key==='weight'?'true':'false' ?>"><?= $label ?></button><?php endforeach; ?>
    </div>
    <?php foreach (['weight'=>['Cân nặng','kg'],'bmi'=>['BMI',''],'height'=>['Chiều cao','cm']] as $key=>[$label,$unit]):
        $vals = array_column($sf38Measurements,$key); $n=count($vals); $lo=min($vals); $hi=max($vals); $pad=max(($hi-$lo)*.12, .5); $lo-=$pad; $hi+=$pad;
        $xy=[]; foreach ($vals as $i=>$v) $xy[]=[round($n===1?180:40+$i*280/($n-1),2),round(18+($hi-$v)*100/($hi-$lo),2)];
        $path=implode(' ',array_map(static fn($p)=>implode(',',$p),$xy));
        $delta=$vals[$n-1]-$vals[0]; ?>
    <div class="sf38-plot" data-sf38-plot="<?= $key ?>" <?= $key==='weight'?'':'hidden' ?>>
        <div class="sf38-plot-top"><span><?= sf_h($label) ?> · <?= $n ?> lần đo gần nhất</span><b><?= $n>1 ? ($delta>0?'+':'').$sf38Fmt($delta).' '.sf_h($unit) : '1 lần đo' ?></b></div>
        <svg viewBox="0 0 360 152" role="img" aria-label="<?= sf_h($label . ' theo thời gian; ' . $n . ' lần đo') ?>">
            <?php for($i=0;$i<3;$i++): $y=18+50*$i; ?><line x1="40" x2="320" y1="<?= $y ?>" y2="<?= $y ?>" stroke="currentColor" opacity=".14"/><text x="35" y="<?= $y+3 ?>" text-anchor="end"><?= $sf38Fmt($hi-($hi-$lo)*$i/2) ?></text><?php endfor; ?>
            <?php if($n>1): ?><polygon points="<?= $xy[0][0] ?>,118 <?= $path ?> <?= $xy[$n-1][0] ?>,118" fill="currentColor" opacity=".09"/><polyline points="<?= $path ?>" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/><?php endif; ?>
            <?php foreach ($xy as $i=>$p): ?><circle cx="<?= $p[0] ?>" cy="<?= $p[1] ?>" r="3.6" fill="currentColor"><title><?= sf_h($sf38Measurements[$i]['date'].' · '.$sf38Fmt($vals[$i]).' '.$unit) ?></title></circle><?php endforeach; ?>
            <text x="40" y="141"><?= sf_h(substr($sf38Measurements[0]['date'],0,5)) ?></text><?php if($n>1): ?><text x="320" y="141" text-anchor="end"><?= sf_h(substr($sf38Measurements[$n-1]['date'],0,5)) ?></text><?php endif; ?>
        </svg>
        <details class="sf38-data"><summary>Xem số liệu</summary><div class="sf38-table-wrap"><table><thead><tr><th>Thời điểm</th><th><?= sf_h($label . ($unit?' ('.$unit.')':'')) ?></th></tr></thead><tbody><?php foreach(array_reverse($sf38Measurements) as $m): ?><tr><td><?= sf_h($m['date']) ?></td><td><?= $sf38Fmt($m[$key]) ?></td></tr><?php endforeach; ?></tbody></table></div></details>
    </div><?php endforeach; ?>
    <?php else: ?><div class="sf38-empty">Chưa có lịch sử đo. <a href="<?= sf_h(url('profile?section=health#health')) ?>">Nhập chỉ số đầu tiên</a>.</div><?php endif; ?>
    <p class="sf38-note">BMI và BMR chỉ mang tính tham khảo; không dùng để chẩn đoán hoặc xác định mức độ phục hồi. Số liệu này chỉ hiển thị trong tài khoản của bạn.</p>
</section>
