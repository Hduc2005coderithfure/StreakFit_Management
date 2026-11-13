<dialog class="arena-entry" id="arenaEntry" aria-labelledby="arenaEntryTitle" data-session="<?= sf_h($arenaSession) ?>" data-rank="<?= sf_h($rank['slug']) ?>">
    <canvas class="arena-entry-canvas" data-arena-scene aria-hidden="true"></canvas>
    <div class="arena-entry-shade" aria-hidden="true"></div>
    <div class="arena-entry-top"><span><b>SF</b> STREAKFIT / DISCIPLINE ARENA</span><button type="button" data-arena-close autofocus>Bỏ qua ↗</button></div>
    <div class="arena-entry-content">
        <div class="arena-entry-copy"><div class="arena-kicker">THE DISCIPLINE ARENA · <?= sf_h(date('d/m', strtotime($currentWeek))) ?></div><h1 id="arenaEntryTitle">CHÀO MỪNG<br><em>TRỞ LẠI.</em></h1><p class="arena-entry-name"><?= sf_h($user['display_name']) ?> <span> / </span> <?= sf_h($rank['name']) ?></p><p class="arena-quote">“<?= sf_h($arenaQuote) ?>”</p></div>
        <div class="arena-entry-stats"><div><span>ĐÃ ĐĂNG KÝ LỊCH</span><strong data-count="<?= $arenaScheduled ?>"><?= $arenaScheduled ?></strong><small>ngày có lịch</small></div><div><span>ĐÃ HOÀN THÀNH</span><strong data-count="<?= $arenaDone ?>"><?= $arenaDone ?></strong><small>buổi hợp lệ</small></div><div><span>MỤC TIÊU TUẦN</span><strong data-count="<?= $arenaGoal ?>"><?= $arenaGoal ?></strong><small>buổi</small></div><div><span>CHUỖI HIỆN TẠI</span><strong data-count="<?= (int)$user['current_streak'] ?>"><?= (int)$user['current_streak'] ?></strong><small>tuần</small></div></div>
        <div class="arena-entry-progress"><span>TIẾN ĐỘ MỤC TIÊU</span><b><?= $arenaPercent ?>%</b><i><span style="width:<?= $arenaPercent ?>%"></span></i></div>
        <button type="button" class="arena-cta" data-arena-close>BƯỚC VÀO ĐẤU TRƯỜNG <span>→</span></button>
    </div>
    <div class="arena-entry-badge" aria-hidden="true"><?= sf_rank_emblem($rank, 'arena-entry-fallback') ?></div>
    <div class="arena-entry-bottom"><span>01 / AWAKENING</span><span>02 / ASCENSION</span><span>03 / DISCIPLINE</span></div>
</dialog>
