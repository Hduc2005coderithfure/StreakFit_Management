<?php
$sf38TodayDate=(new DateTimeImmutable('now',new DateTimeZone(sf_config()['timezone'])))->format('Y-m-d');
$sf38TodayDone=in_array($sf38TodayDate,array_column($weekActivity??[],'workout_date'),true);
$sf39WorkoutLabel=$activeWorkout?'Kết thúc tập':($sf38TodayDone?'Xem lịch sử':'Tập luyện ngay');
?>
<section class="sf38-card sf38-today sf39-hero <?= $activeWorkout ? 'sf42-training' : '' ?>" data-arena-panel="overview" aria-label="Buổi tập hôm nay">
    <div class="sf39-hero-grid">
        <div class="sf39-hero-copy">
            <div class="sf39-hero-top"><span class="sf39-kicker"><?= sf_h((new DateTimeImmutable('now',new DateTimeZone(sf_config()['timezone'])))->format('d/m/Y')) ?></span><span class="sf38-state <?= $activeWorkout?'working':'' ?>"><i></i><?= $activeWorkout?'Đang tập':($sf38TodayDone?'Đã hoàn thành':'Chưa tập hôm nay') ?></span></div>
            <h1>Chào, <?= sf_h($user['display_name']) ?>.</h1>
            <div class="sf39-stats"><div><span>Buổi đăng ký</span><strong><?= $arenaScheduled ?></strong></div><div><span>Buổi đã tập</span><strong><?= $arenaDone ?></strong></div></div>
            <div class="sf39-cta-zone">
                <?php if($activeWorkout): $sf42Start=(int)strtotime($activeWorkout['checked_in_at'].' UTC'); $sf42Minimum=max(1,(int)$settings['minimum_workout_seconds']); $sf42Elapsed=max(0,time()-$sf42Start); ?>
                <div class="sf42-live" data-sf42-live data-start="<?= $sf42Start ?>" data-min="<?= $sf42Minimum ?>" data-server="<?= time() ?>">
                    <div class="sf42-live-head"><span><i></i> ĐANG LUYỆN TẬP</span><small>Bắt đầu <?= sf_h(sf_format_time($activeWorkout['checked_in_at'],'H:i')) ?></small></div>
                    <strong data-sf42-elapsed class="sf42-elapsed"><?= sprintf('%02d:%02d:%02d',intdiv($sf42Elapsed,3600),intdiv($sf42Elapsed%3600,60),$sf42Elapsed%60) ?></strong>
                    <div class="sf42-live-track" role="progressbar" aria-label="Thời lượng tối thiểu" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= min(100,(int)floor($sf42Elapsed*100/$sf42Minimum)) ?>"><i data-sf42-fill style="width:<?= min(100,$sf42Elapsed*100/$sf42Minimum) ?>%"></i></div>
                    <p data-sf42-min-status><?= $sf42Elapsed >= $sf42Minimum ? 'Đã đạt thời lượng tối thiểu' : 'Còn '.ceil(($sf42Minimum-$sf42Elapsed)/60).' phút để đạt thời lượng tối thiểu' ?></p>
                </div><?php endif; ?>
                <a class="btn primary sf39-train" <?= !$activeWorkout && $sf38TodayDone?'':'data-sf38-open' ?> href="<?= sf_h(url(!$activeWorkout && $sf38TodayDone?'workouts/history':'workouts')) ?>"><?= $arenaIcon($activeWorkout?'history':'plus') ?><span><?= sf_h($sf39WorkoutLabel) ?></span><b aria-hidden="true">→</b></a>
                <?php if(!$activeWorkout && !$sf38TodayDone): ?><small>Chụp ảnh để bắt đầu buổi tập.</small><?php elseif($sf38TodayDone && !$activeWorkout): ?><small>Buổi tập hợp lệ hôm nay đã được ghi nhận.</small><?php endif; ?>
            </div>
        </div>
        <?php
        $sf44Goal = max(0, $arenaGoal);
        $sf44Done = max(0, $arenaDone);
        $sf44Segments = min(7, $sf44Goal);
        $sf44Color = ['dong'=>'#e18b4b','bac'=>'#cbb5ff','vang'=>'#ffd76a','bach-kim'=>'#a9edff','kim-cuong'=>'#45c5ff','tinh-anh'=>'#e66aff','cao-thu'=>'#ffb352','chien-tuong'=>'#ff5f67'][$rank['slug']] ?? '#e18b4b';
        $sf44Complete = $sf44Goal > 0 && $sf44Done >= $sf44Goal;
        // Weekly energy is presentation only, never a new XP calculation.
        $sf45Energy = sf_week_energy_state($sf44Done, $sf44Goal);
        $sf45ParticleCount = match($sf45Energy){'unplanned','idle'=>0,'charge'=>8,'surge'=>14,'complete'=>20,'surplus'=>24};
        $sf45StageLabel = match($sf45Energy){'unplanned'=>'Chưa đặt mục tiêu','idle'=>'Chờ nạp năng lượng','charge'=>'Đang tích lũy','surge'=>'Năng lượng tăng cao','complete'=>'Đạt mục tiêu','surplus'=>'Vượt mục tiêu'};
        ?>
        <div class="sf39-visual sf44-week-visual <?= $sf44Complete ? 'is-complete' : '' ?>" data-rank-slug="<?= sf_h($rank['slug']) ?>" data-energy="<?= sf_h($sf45Energy) ?>" style="--sf44-color:<?= sf_h($sf44Color) ?>;--sf44-progress:<?= $arenaPercent ?>%;--sf44-scene:url('<?= sf_h(sf_asset_version('ranks/'.$rank['slug'].'-scene.svg')) ?>')" aria-label="Tiến độ mục tiêu tuần">
            <div class="sf45-energy-world" aria-hidden="true"><div class="sf45-aura"></div><div class="sf45-beams"></div><div class="sf45-rings"></div><div class="sf45-stars"><?php for($i=0;$i<$sf45ParticleCount;$i++): ?><i style="--i:<?= $i ?>;--x:<?= 7+($i*37)%86 ?>%;--y:<?= 8+($i*53)%76 ?>%;--d:<?= 3+($i%5)*.6 ?>s"></i><?php endfor; ?></div></div>
            <div class="sf44-visual-head"><span class="sf44-rank-chip"><?= sf_rank_emblem($rank, 'sf44-rank-mini') ?><span><?= sf_h($rank['name']) ?></span></span><span class="sf44-week-label">MỤC TIÊU TUẦN</span></div>
            <div class="arena-core sf39-core sf44-rank-core" data-core-progress="<?= $arenaPercent ?>" data-core-over="<?= $sf44Done>$sf44Goal && $sf44Goal>0?'1':'0' ?>" data-core-rank="<?= sf_h($rank['slug']) ?>" data-core-goal="<?= $sf44Goal ?>" data-core-done="<?= $sf44Done ?>">
                <div class="sf44-orbit" aria-hidden="true"></div>
                <img class="sf44-core-fallback" src="<?= sf_h(sf_asset_version('ranks/'.$rank['slug'].'-v44.webp')) ?>" alt="" width="768" height="768" decoding="async">
                <canvas data-core-canvas aria-hidden="true"></canvas>
            </div>
            <div class="sf44-progress-panel"><div class="sf45-energy-status"><i aria-hidden="true"></i><span><?= sf_h($sf45StageLabel) ?></span></div>
                <div class="sf44-progress-head"><div><strong><?= $sf44Goal > 0 ? $sf44Done.' / '.$sf44Goal : (string)$sf44Done ?><small> buổi</small></strong><span><?= $sf44Goal > 0 ? ($sf44Complete ? 'Đã đạt mục tiêu' : 'Đã hoàn thành') : 'Chưa đặt mục tiêu' ?></span></div><b><?= $sf44Goal > 0 ? $arenaPercent.'%' : '—' ?></b></div>
                <?php if($sf44Goal > 0): ?>
                <div class="sf44-segments" style="--sf44-count:<?= max(1,$sf44Segments) ?>" role="progressbar" aria-label="Tiến độ mục tiêu tuần" aria-valuemin="0" aria-valuemax="<?= $sf44Goal ?>" aria-valuenow="<?= min($sf44Done,$sf44Goal) ?>" aria-valuetext="<?= sf_h($sf44Done.' / '.$sf44Goal.' buổi') ?>">
                    <?php for($i=1;$i<=$sf44Segments;$i++): $sf44Filled=$i<=$sf44Done; ?><div class="sf44-segment <?= $sf44Filled?'is-filled':($i===$sf44Done+1?'is-next':'') ?>" style="--i:<?= $i ?>" aria-hidden="true"><span class="sf44-segment-orb"><?= $sf44Filled?'✓':$i ?></span><span class="sf44-segment-track"><i></i></span></div><?php endfor; ?>
                </div>
                <p class="sf44-progress-note"><?= $sf44Done > $sf44Goal ? 'Vượt mục tiêu '.($sf44Done-$sf44Goal).' buổi' : ($sf44Complete ? 'Hoàn thành mục tiêu tuần' : 'Còn '.$arenaRemaining.' buổi để đạt mục tiêu') ?></p>
                <?php else: ?><a class="sf44-progress-note" href="<?= sf_h(url('profile?section=schedule')) ?>">Đăng ký lịch tuần tới →</a><?php endif; ?>
            </div>
        </div>
    </div>
    <?php if ($activeWorkout || !$sf38TodayDone): ?><details id="sf38Capture" class="sf38-capture"><summary>Camera ghi nhận buổi tập</summary><?php require APP_ROOT . '/app/Views/components/arena-capture.php'; ?></details><?php endif; ?>
</section>
