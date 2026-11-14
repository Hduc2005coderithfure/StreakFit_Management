<?php
$sf38Events=[];
foreach ($feed as $p) {
    foreach (['checked_in_at'=>'Bắt đầu tập','checked_out_at'=>'Hoàn thành'] as $field=>$label) if(!empty($p[$field])) $sf38Events[]=['time'=>$p[$field],'name'=>$p['display_name'],'label'=>$label,'id'=>(int)$p['workout_id'],'done'=>$field==='checked_out_at','minutes'=>(int)round((int)$p['duration_seconds']/60)];
}
foreach ($activityMembers as $m) if($m['status']==='in_progress' && !empty($m['checked_in_at'])) $sf38Events[]=['time'=>$m['checked_in_at'],'name'=>$m['display_name'],'label'=>'Đang tập','id'=>0,'done'=>false,'minutes'=>0];
if($activeWorkout && !empty($activeWorkout['checked_in_at'])) $sf38Events[]=['time'=>$activeWorkout['checked_in_at'],'name'=>$user['display_name'],'label'=>'Đang tập','id'=>0,'done'=>false,'minutes'=>0];
usort($sf38Events,static fn($a,$b)=>strcmp($b['time'],$a['time']));
?>
<section class="sf38-card sf38-timeline" data-arena-panel="<?= sf_h($timelinePanel ?? 'community') ?>">
<header class="sf38-heading"><div><span class="sf38-kicker">HOẠT ĐỘNG GẦN ĐÂY</span><h2>Timeline luyện tập</h2></div><?php if(($timelinePanel??'community')==='overview'): ?><a class="sf38-link" data-sf33-view="community" href="<?= sf_h(url('dashboard?view=community')) ?>">Bảng tin ↗</a><?php endif; ?></header>
<?php if(!$sf38Events): ?><div class="sf38-empty">Chưa có hoạt động để hiển thị.</div><?php else: ?><ol><?php foreach(array_slice($sf38Events,0,($timelinePanel??'community')==='overview'?4:12) as $event): ?><li><i class="<?= $event['done']?'done':'' ?>" aria-hidden="true"></i><div><strong><?= sf_h($event['name']) ?></strong><span><?= sf_h($event['label']) ?><?= $event['done']?' · '.$event['minutes'].' phút':'' ?></span></div><time><?= sf_h(sf_format_time($event['time'],'d/m · H:i')) ?></time><?php if($event['id'] && ($timelinePanel??'community')==='community'): ?><a href="#workout-<?= $event['id'] ?>" aria-label="Xem bài tập của <?= sf_h($event['name']) ?>">↗</a><?php endif; ?></li><?php endforeach; ?></ol><?php endif; ?>
</section>
