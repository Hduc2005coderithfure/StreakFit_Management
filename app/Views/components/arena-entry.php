<?php $sf38EntryDone = in_array((new DateTimeImmutable('now', new DateTimeZone(sf_config()['timezone'])))->format('Y-m-d'), array_column($weekActivity ?? [], 'workout_date'), true); ?>
<dialog class="arena-entry" id="arenaEntry" aria-labelledby="arenaEntryTitle" data-session="<?= sf_h($arenaSession) ?>" data-rank="<?= sf_h($rank['slug']) ?>">
    <canvas class="arena-entry-canvas" data-arena-scene aria-hidden="true"></canvas>
    <div class="arena-entry-shade" aria-hidden="true"></div>
    <div class="arena-entry-top"><span><b>SF</b> STREAKFIT / ĐẤU TRƯỜNG</span><button type="button" data-arena-close autofocus>Bỏ qua ↗</button></div>
    <div class="arena-entry-content">
        <div class="arena-entry-copy"><div class="arena-kicker">TUẦN · <?= sf_h(date('d/m', strtotime($currentWeek))) ?></div><h1 id="arenaEntryTitle">CHÀO MỪNG<br><em>TRỞ LẠI.</em></h1><p class="arena-entry-name"><?= sf_h($user['display_name']) ?> <span> / </span> <?= sf_h($rank['name']) ?></p></div>
        <div class="arena-entry-stats"><div><span>BUỔI ĐĂNG KÝ</span><strong data-count="<?= $arenaScheduled ?>"><?= $arenaScheduled ?></strong><small>buổi</small></div><div><span>ĐÃ TẬP</span><strong data-count="<?= $arenaDone ?>"><?= $arenaDone ?></strong><small>buổi</small></div></div>
        <div class="arena-entry-progress"><span>TIẾN ĐỘ MỤC TIÊU</span><b><?= $arenaPercent ?>%</b><i><span style="width:<?= $arenaPercent ?>%"></span></i></div>
        <a class="arena-cta sf38-entry-workout" href="<?= sf_h(url(!$activeWorkout && $sf38EntryDone ? 'workouts/history' : 'workouts')) ?>" <?= $tab === 'feed' && ($activeWorkout || !$sf38EntryDone) ? 'data-sf38-entry-workout' : '' ?>><?= $activeWorkout ? 'Kết thúc tập' : ($sf38EntryDone ? 'Xem lịch sử' : 'Tập luyện ngay') ?> <span>→</span></a>
        <button type="button" class="arena-cta" data-arena-close>Vào trang chính <span>→</span></button>
    </div>
    <div class="arena-entry-badge" aria-hidden="true"><?= sf_rank_emblem($rank, 'arena-entry-fallback') ?></div>

</dialog>
