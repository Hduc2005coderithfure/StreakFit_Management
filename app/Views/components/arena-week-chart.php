<?php
$sf38WeekMap = array_column($weekActivity ?? [], 'seconds', 'workout_date');
$sf38WeekDays=[]; $sf38WeekMax=1; $sf38WeekTotal=0;
$sf38Monday=new DateTimeImmutable($currentWeek);
for($i=0;$i<7;$i++) { $date=$sf38Monday->modify('+'.$i.' days')->format('Y-m-d'); $seconds=max(0,(int)($sf38WeekMap[$date]??0)); $sf38WeekDays[]=['date'=>$date,'minutes'=>round($seconds/60,1),'seconds'=>$seconds]; $sf38WeekMax=max($sf38WeekMax,$seconds); $sf38WeekTotal+=$seconds; }
?>
<section class="sf38-card sf38-week" data-arena-panel="overview" aria-label="Thời gian tập tuần này">
    <header class="sf38-heading"><div><span class="sf38-kicker">TUẦN NÀY</span><h2>Nhịp luyện tập</h2></div><a class="sf38-link" href="<?= sf_h(url('workouts/history')) ?>">Lịch sử ↗</a></header>
    <div class="sf38-week-summary"><strong><?= number_format($sf38WeekTotal/60, 1, ',', '.') ?> <small>phút</small></strong><span><?= $arenaDone ?>/<?= $arenaGoal ?> buổi hợp lệ</span></div>
    <div class="sf39-week-facts"><div><span>Ngày có buổi tập</span><strong><?= count(array_filter($sf38WeekDays, static fn($d)=>$d['seconds']>0)) ?></strong></div><div><span>Trung bình / buổi</span><strong><?= $arenaDone>0?number_format($sf38WeekTotal/60/$arenaDone,1,',','.'):'—' ?> <small>phút</small></strong></div></div>
    <div class="sf38-bars" role="group" aria-label="Số phút tập mỗi ngày">
        <?php foreach($sf38WeekDays as $i=>$d): ?><div class="sf38-bar-col"><span class="sf38-bar-value"><?= $d['minutes']>0?number_format($d['minutes'],1,',','.'):'0' ?></span><div class="sf38-bar-track"><div style="--sf38-bar:<?= round(100*$d['seconds']/$sf38WeekMax,2) ?>%" class="<?= $d['seconds']>0?'has-value':'' ?>"></div></div><b><?= ['T2','T3','T4','T5','T6','T7','CN'][$i] ?></b><small><?= sf_h(substr($d['date'],8,2).'/'.substr($d['date'],5,2)) ?></small></div><?php endforeach; ?>
    </div><p class="sf38-note">Tổng thời gian các buổi hợp lệ đã hoàn thành, không tính buổi đang mở.</p>
</section>
