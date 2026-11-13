<?php
// Existing save_goal contract: indexed date/time/note arrays, one day per row.
$sfPlanDays = ['Thứ 2','Thứ 3','Thứ 4','Thứ 5','Thứ 6','Thứ 7','Chủ nhật'];
$sfPlanMonday = new DateTimeImmutable($nextWeek);
$sfPlanRows = [];
$sfCurrentTimes = [];
foreach ($plansCurrent as $sfExisting) {
    $sfCurrentTimes[(int)date('N', strtotime($sfExisting['planned_date'])) - 1] = substr((string)$sfExisting['planned_time'], 0, 5);
}
for ($sfDay = 0; $sfDay < 7; $sfDay++) {
    $sfDate = $sfPlanMonday->modify('+' . $sfDay . ' days');
    $sfKey = $sfDate->format('Y-m-d');
    $sfSaved = $nextPlanByDate[$sfKey] ?? [];
    $sfPlanRows[] = ['key'=>$sfKey,'label'=>$sfPlanDays[$sfDay],'date'=>$sfDate->format('d/m'),'number'=>$sfDate->format('d'),'time'=>substr((string)($sfSaved['planned_time'] ?? ''),0,5),'note'=>(string)($sfSaved['note'] ?? ''),'copy'=>$sfCurrentTimes[$sfDay] ?? ''];
}
$sfSelected = count(array_filter($sfPlanRows, static fn($row) => $row['time'] !== ''));
$sfTarget = max(1, min(7, (int)($nextGoal['target_sessions'] ?? 3)));
?>
<section class="panel sf32-planner" id="schedule" aria-labelledby="sf32-title">
    <header class="sf32-hero">
        <div class="sf32-orbit" aria-hidden="true"><i></i><i></i><i></i><span>✦</span></div>
        <div class="sf32-hero-copy"><span class="sf32-kicker"><i></i> TRAINING PLANNER / 01</span><h1 id="sf32-title">Lên lịch.<br><em>Chinh phục.</em></h1><p>Chọn ngày, chọn giờ. Biến lời hứa thành hành động.</p><span class="sf32-week-label"><?= sf_h($sfPlanMonday->format('d/m/Y')) ?> — <?= sf_h($sfPlanMonday->modify('+6 days')->format('d/m/Y')) ?> <b>· TUẦN TỚI</b></span></div>
        <div class="sf32-hero-stats"><div class="sf32-dial" data-sf32-dial style="--fill:<?= $sfSelected / 7 * 100 ?>%"><div><strong data-sf32-count><?= $sfSelected ?></strong><small>/ 7 NGÀY</small></div></div><span>Ngày đã chọn</span></div>
    </header>
    <form method="post" action="<?= sf_h(url('profile?section=schedule')) ?>" class="sf32-plan-form" data-sf32-plan>
        <input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="save_goal"><input type="hidden" name="week_start" value="<?= sf_h($nextWeek) ?>">
        <div class="sf32-step-head"><div><span class="sf32-step-index">01</span><h2>Chọn ngày tập</h2><p>Nhấn vào ngày bạn muốn đăng ký.</p></div><div class="sf32-tools" data-sf32-enhanced hidden><button type="button" data-sf32-copy <?= !$sfCurrentTimes ? 'disabled' : '' ?>>↻ Sao chép tuần này</button><button type="button" data-sf32-clear>Bỏ chọn</button></div></div>
        <div class="sf32-days" data-sf32-enhanced hidden aria-label="Chọn ngày tập trong tuần">
            <?php foreach ($sfPlanRows as $i => $row): ?>
                <label class="sf32-day <?= $row['time'] !== '' ? 'is-selected' : '' ?>"><input type="checkbox" data-sf32-day="<?= $i ?>" <?= $row['time'] !== '' ? 'checked' : '' ?> aria-label="<?= sf_h($row['label'].' '.$row['date']) ?>"><span class="sf32-day-name"><span class="sf32-day-long"><?= sf_h($row['label']) ?></span><span class="sf32-day-short" aria-hidden="true"><?= $i === 6 ? 'CN' : 'T'.($i+2) ?></span></span><strong><?= sf_h($row['number']) ?></strong><span class="sf32-tick" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5L20 7"/></svg></span><small data-sf32-day-time><?= $row['time'] !== '' ? sf_h($row['time']) : 'Nghỉ' ?></small></label>
            <?php endforeach; ?>
        </div>
        <noscript><p>Nhập giờ cho những ngày muốn đăng ký. Để trống giờ nếu không tập.</p></noscript>
        <div class="sf32-step-head sf32-time-heading"><div><span class="sf32-step-index">02</span><h2>Chọn giờ tập</h2><p>Đặt một giờ chung hoặc chỉnh riêng từng ngày.</p></div></div>
        <div class="sf32-common-time" data-sf32-enhanced hidden><label for="sf32-common">Giờ chung <input id="sf32-common" type="time" value="17:00" step="60"></label><button type="button" class="sf32-apply" data-sf32-apply>Áp dụng cho ngày đã chọn</button><div class="sf32-presets" aria-label="Giờ tập gợi ý"><?php foreach (['06:00','07:00','12:00','17:00','18:00','19:00'] as $time): ?><button type="button" data-sf32-preset="<?= $time ?>"><?= $time ?></button><?php endforeach; ?></div></div>
        <div class="sf32-time-list">
            <?php foreach ($sfPlanRows as $i => $row): ?>
                <div class="sf32-time-row" data-sf32-row="<?= $i ?>" data-copy="<?= sf_h($row['copy']) ?>"><input type="hidden" name="planned_dates[<?= $i ?>]" value="<?= sf_h($row['key']) ?>"><input type="hidden" name="planned_notes[<?= $i ?>]" value="<?= sf_h($row['note']) ?>"><span class="sf32-time-icon" aria-hidden="true">◷</span><label for="sf32-time-<?= $i ?>"><strong><?= sf_h($row['label']) ?></strong><small><?= sf_h($row['date']) ?></small></label><input id="sf32-time-<?= $i ?>" type="time" name="planned_times[<?= $i ?>]" value="<?= sf_h($row['time']) ?>" step="60" aria-label="Giờ tập <?= sf_h($row['label']) ?>"></div>
            <?php endforeach; ?>
            <p class="sf32-empty" data-sf32-empty hidden>Chưa chọn ngày nào. Tick một ngày phía trên để đặt giờ tập.</p>
        </div>
        <div class="sf32-plan-footer"><div class="sf32-goal"><label for="sf32-target">Mục tiêu tuần tới</label><select id="sf32-target" name="target_sessions"><?php for($i=1;$i<=7;$i++): ?><option value="<?= $i ?>" <?= $sfTarget === $i ? 'selected' : '' ?>><?= $i ?> buổi</option><?php endfor; ?></select><small data-sf32-goal-note>Mục tiêu dùng để tính điểm; bạn có thể chỉnh riêng với lịch.</small></div><div class="sf32-save"><p data-sf32-status role="status" aria-live="polite"><?= $sfSelected ?> ngày đăng ký · Mục tiêu <?= $sfTarget ?> buổi</p><button type="submit" class="sf32-save-button"><span>LƯU LỊCH TUẦN TỚI</span><b aria-hidden="true">→</b></button></div></div>
        <p class="sf32-footnote">Lịch dự kiến không trực tiếp thay đổi điểm streak. Chỉ có thể đăng ký hoặc chỉnh sửa tuần tới.</p>
    </form>
</section>
