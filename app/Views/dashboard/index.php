<?php
$flash = $_SESSION['sf_flash'] ?? null;
unset($_SESSION['sf_flash']);
$rank = sf_rank((int)$user['total_points']);
require APP_ROOT . '/app/Views/components/arena-data.php';
$height = (float)($user['height_cm'] ?? 0);
$weight = (float)($user['weight_kg'] ?? 0);
$age = (int)($user['age'] ?? 0);
$bmi = $height > 0 && $weight > 0 ? $weight / (($height / 100) ** 2) : null;
$bmr = $height > 0 && $weight > 0 && $age > 0 && $user['gender'] !== 'unspecified'
    ? (int)round(10 * $weight + 6.25 * $height - 5 * $age + ($user['gender'] === 'male' ? 5 : -161))
    : null;
$tabLabels = ['feed' => 'Tổng quan', 'leaderboard' => 'Bảng vàng', 'checkin' => 'Tập luyện', 'notifications' => 'Thông báo', 'profile' => 'Cá nhân'];
$profileSection = is_string($_GET['section'] ?? null) ? $_GET['section'] : 'overview';
if (!in_array($profileSection, ['overview','profile','health','schedule','fund','security','other'], true)) $profileSection = 'overview';
$profileSectionLabels = ['profile' => 'Hồ sơ cá nhân', 'health' => 'Sức khỏe', 'schedule' => 'M&#7909;c ti&#234;u &amp; L&#7883;ch t&#7853;p', 'fund' => 'Qu&#7921; nh&#243;m', 'security' => 'B&#7843;o m&#7853;t', 'other' => 'Tr&#7907; l&#253; AI'];
$arenaViews = [
    'feed' => ['overview' => ['Tổng quan', 'grid'], 'community' => ['Cộng đồng', 'people'], 'insights' => ['Thống kê', 'activity']],
    'leaderboard' => ['rank' => ['Huy hiệu & Thăng hạng', 'rank'], 'board' => ['Bảng vàng', 'trophy'], 'court' => ['Nghỉ tập', 'calendar']],
];
$arenaView = '';
if (isset($arenaViews[$tab])) {
    $arenaRequestPath = (string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $arenaDefault = $tab === 'feed' ? 'overview' : (str_ends_with(rtrim($arenaRequestPath, '/'), '/court') ? 'court' : (isset($_GET['period']) ? 'board' : 'rank'));
    $arenaRequested = is_string($_GET['view'] ?? null) ? $_GET['view'] : $arenaDefault;
    $arenaView = isset($arenaViews[$tab][$arenaRequested]) ? $arenaRequested : $arenaDefault;
}
require APP_ROOT . '/app/Views/components/arena-icons.php';
$arenaProfileTabs = [
    'overview' => ['Tổng quan', 'grid'], 'profile' => ['Hồ sơ', 'user'], 'health' => ['Sức khỏe', 'heart'],
    'schedule' => ['Lịch tập', 'calendar'], 'fund' => ['Quỹ nhóm', 'wallet'], 'security' => ['Bảo mật', 'lock'], 'other' => ['Trợ lý AI', 'spark'],
];
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0e0e12">
    <meta name="description" content="StreakFit - Theo dõi chạy bộ, gym và tiến độ luyện tập cùng cộng đồng.">
    <title>StreakFit · <?= sf_h($arenaView === 'community' ? 'Cộng đồng' : ($arenaView === 'insights' ? 'Thống kê' : ($tabLabels[$tab] ?? 'Luyện tập'))) ?></title>
    <link rel="stylesheet" href="<?= sf_h(sf_asset_version('css/streakfit.css')) ?>">
    <?php if (in_array($tab, ['feed', 'leaderboard'], true)): ?><link rel="stylesheet" href="<?= sf_h(sf_asset_version('css/rank-cinema.css')) ?>"><?php endif; ?>
    <link rel="stylesheet" href="<?= sf_h(sf_asset_version('css/streakfit-future.css')) ?>">
    <link rel="stylesheet" href="<?= sf_h(sf_asset_version('css/streakfit-motion.css')) ?>">
    <link rel="stylesheet" href="<?= sf_h(sf_asset_version('css/arena-v3.css')) ?>">
    <link rel="stylesheet" href="<?= sf_h(sf_asset_version('css/arena-v32.css')) ?>">
    <link rel="stylesheet" href="<?= sf_h(sf_asset_version('css/arena-v33.css')) ?>">
    <link rel="stylesheet" href="<?= sf_h(sf_asset_version('css/arena-v35.css')) ?>">
    <link rel="stylesheet" href="<?= sf_h(sf_asset_version('css/arena-atmosphere.css')) ?>">
<link rel="stylesheet" href="<?= sf_h(sf_asset_version('css/arena-v37.css')) ?>">
<link rel="stylesheet" href="<?= sf_h(sf_asset_version('css/arena-v38.css')) ?>">
<link rel="stylesheet" href="<?= sf_h(sf_asset_version('css/arena-v39.css')) ?>">
<link rel="stylesheet" href="<?= sf_h(sf_asset_version('css/arena-v40.css')) ?>">
<link rel="stylesheet" href="<?= sf_h(sf_asset_version('css/arena-v41.css')) ?>">
<link rel="stylesheet" href="<?= sf_h(sf_asset_version('css/arena-v42.css')) ?>">
<?php if ($tab === 'leaderboard'): ?><link rel="stylesheet" href="<?= sf_h(sf_asset_version('css/arena-v43.css')) ?>"><?php endif; ?>
<?php if (in_array($tab, ['feed', 'leaderboard'], true)): ?><link rel="stylesheet" href="<?= sf_h(sf_asset_version('css/arena-v44.css')) ?>"><?php endif; ?>
<link rel="stylesheet" href="<?= sf_h(sf_asset_version('css/arena-v45.css')) ?>">
</head>
<body class="sf-future sf-arena sf40-workspace" data-tab="<?= sf_h($tab) ?>" data-arena-view="<?= sf_h($arenaView) ?>" data-timezone="<?= sf_h(sf_config()['timezone']) ?>" <?= $tab === 'profile' ? 'data-profile-section="' . sf_h($profileSection) . '"' : '' ?>>
<?php require APP_ROOT . '/app/Views/components/arena-entry.php'; ?>
<div class="app-shell" id="appShell">
    <?php require APP_ROOT . '/app/Views/components/arena-workspace.php'; ?>
    <header class="topbar">
        <a class="brand" href="<?= sf_h(url('dashboard')) ?>"><span>Streak<i>Fit</i><b>✦</b></span><small><?= sf_h($user['display_name']) ?> · Chuỗi <?= (int)$user['current_streak'] ?> tuần</small></a>
        <div class="sf39-top-title"><span>Không gian làm việc</span><strong data-sf39-title><?= sf_h($tab === 'feed' ? ($arenaViews['feed'][$arenaView][0] ?? 'Tổng quan') : ($tab === 'leaderboard' ? ($arenaViews['leaderboard'][$arenaView][0] ?? 'Bảng vàng') : ($tab === 'profile' ? ($arenaProfileTabs[$profileSection][0] ?? 'Cá nhân') : ($tabLabels[$tab] ?? ($tab === 'history' ? 'Lịch sử tập' : 'Luyện tập'))))) ?></strong></div>
        <div class="top-actions">
            <a class="icon-button notification-link" href="<?= sf_h(url('notifications')) ?>" aria-label="Thông báo"><svg class="notification-bell-icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg><?php if ($unreadCount): ?><em><?= (int)$unreadCount ?></em><?php endif; ?></a>
            <button class="icon-button account-menu-trigger" type="button" aria-label="Mở menu tài khoản" aria-controls="accountDrawer" aria-expanded="false">☰</button>
            <?php if ($canAdminDashboard): ?><a class="rank-chip" href="<?= sf_h(url('admin')) ?>" aria-label="Mở Trung tâm quản trị">Quản trị ↗</a><?php endif; ?>
        </div>
    </header>
    <div class="account-overlay" id="accountDrawer" hidden>
        <button class="account-backdrop" type="button" data-drawer-close aria-label="Đóng menu"></button>
        <aside class="account-drawer" tabindex="-1" aria-label="Menu tài khoản">
            <div class="drawer-top"><a class="drawer-brand" href="<?= sf_h(url('dashboard')) ?>"><span class="drawer-brand-mark">♨</span><strong>StreakFit</strong></a><button class="drawer-close" type="button" data-drawer-close aria-label="Đóng">&times;</button></div>
            <section class="drawer-profile">
                <div class="drawer-user"><div class="avatar drawer-avatar"><?= sf_h(mb_substr($user['display_name'], 0, 1, 'UTF-8')) ?></div><div class="drawer-user-info"><strong><?= sf_h($user['display_name']) ?></strong><small>@<?= (int)$user['id'] ?></small><span class="drawer-rank"><?= sf_rank_emblem($rank, 'rank-emblem rank-emblem-small') ?> <?= sf_h($rank['name']) ?> · <?= number_format((int)$user['total_points']) ?> điểm</span></div></div>
                <a class="drawer-rename" href="<?= sf_h(url('profile?section=profile')) ?>">✎ &nbsp; Đổi tên hiển thị</a>
            </section>
            <div class="drawer-divider"></div><p class="drawer-section-label">TÀI KHOẢN</p>
            <nav class="drawer-links" aria-label="Điều hướng tài khoản">
<button class="drawer-link arena-replay-link" type="button" data-arena-replay><span class="drawer-icon">↻</span><span><strong>Xem lại màn giới thiệu</strong><small>Đấu trường kỷ luật</small></span></button>
<a class="drawer-link" href="<?= sf_h(url('dashboard?view=community')) ?>"><span class="drawer-icon">♟</span><span><strong>Cộng đồng</strong><small>Bảng tin và hoạt động thành viên</small></span><i>›</i></a>
<a class="drawer-link" href="<?= e(url('groups')) ?>"><span class="drawer-icon">🏃</span><span><strong>Nhóm thể thao</strong><small>Chạy bộ, gym và hẹn tập</small></span></a>
<a class="drawer-link" href="<?= e(url('fund/payments')) ?>"><span class="drawer-icon">₫</span><span><strong>Nộp quỹ qua QR</strong><small>Chuyển khoản và lịch sử thanh toán</small></span></a>
                <a class="drawer-link" href="<?= sf_h(url('profile?section=profile')) ?>"><span class="drawer-icon">♙</span><span><strong>Hồ sơ cá nhân</strong><small>Thông tin và thành tích</small></span><i>›</i></a>
                <a class="drawer-link" href="<?= sf_h(url('profile?section=schedule')) ?>"><span class="drawer-icon">▦</span><span><strong>Lịch tập của tôi</strong><small>Xem và chỉnh sửa lịch</small></span><i>›</i></a>
                <a class="drawer-link" href="<?= sf_h(url('leaderboard')) ?>"><span class="drawer-icon">♜</span><span><strong>Cấp bậc &amp; Huy hiệu</strong><small>Thành tích và thứ hạng</small></span><i>›</i></a>
                <a class="drawer-link" href="<?= sf_h(url('profile?section=security')) ?>"><span class="drawer-icon">⚙</span><span><strong>Cài đặt tài khoản</strong><small>Mật khẩu và quyền riêng tư</small></span><i>›</i></a>
                <?php if ($canAdminDashboard): ?><a class="drawer-link drawer-link-admin" href="<?= sf_h(url('admin')) ?>"><span class="drawer-icon">♙</span><span><strong>Quản trị hệ thống</strong><small>Thành viên và vận hành nhóm</small></span><i>›</i></a><?php endif; ?>
                <a class="drawer-link drawer-link-notifications" href="<?= sf_h(url('notifications')) ?>"><span class="drawer-icon">♧</span><span><strong>Thông báo</strong><small>Tùy chỉnh nhắc nhở</small></span><?php if ($unreadCount): ?><b class="drawer-unread"><?= (int)$unreadCount ?></b><?php endif; ?><i>›</i></a>
            </nav>
            <div class="drawer-divider drawer-bottom-divider"></div>
            <form method="post" class="drawer-logout"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="logout"><button type="submit">↪ &nbsp; Đăng xuất</button></form>
            <small class="drawer-footer">StreakFit · Arena V4.5</small>
        </aside>
    </div>
    <main class="page-content">
        <?php if (isset($arenaViews[$tab])): ?>
            <nav class="arena-mini-nav" data-arena-local-tabs aria-label="Các mục <?= sf_h($tabLabels[$tab] ?? '') ?>">
                <span class="arena-mini-caption"><i></i><?= $tab === 'feed' ? 'STREAKFIT' : 'THÀNH TÍCH' ?></span>
                <div class="arena-mini-track">
                <?php foreach ($arenaViews[$tab] as $viewKey => [$viewLabel, $viewIcon]): ?>
                    <a class="arena-mini-tab <?= $arenaView === $viewKey ? 'is-active' : '' ?>" href="<?= sf_h(url(($tab === 'feed' ? 'dashboard' : 'leaderboard') . '?view=' . $viewKey . ($tab === 'leaderboard' && $viewKey === 'board' ? '&period=' . $period : ''))) ?>" data-arena-tab="<?= sf_h($viewKey) ?>" <?= $arenaView === $viewKey ? 'aria-current="page"' : '' ?>><?= $arenaIcon($viewIcon) ?><span><?= sf_h($viewLabel) ?></span></a>
                <?php endforeach; ?>
                <?php if ($tab === 'feed'): ?><a class="arena-mini-tab arena-mini-external" href="<?= sf_h(url('profile?section=schedule')) ?>"><?= $arenaIcon('calendar') ?><span>Lịch tập</span></a><?php endif; ?>
                </div>
            </nav>
        <?php elseif ($tab === 'profile'): ?>
            <nav class="arena-mini-nav arena-profile-tabs" aria-label="Chức năng cá nhân"><span class="arena-mini-caption"><i></i>HỒ SƠ CÁ NHÂN</span><div class="arena-mini-track">
                <?php foreach ($arenaProfileTabs as $sectionKey => [$sectionLabel, $sectionIcon]): ?><a class="arena-mini-tab <?= $profileSection === $sectionKey ? 'is-active' : '' ?>" href="<?= sf_h(url('profile?section=' . $sectionKey)) ?>" <?= $profileSection === $sectionKey ? 'aria-current="page"' : '' ?>><?= $arenaIcon($sectionIcon) ?><span><?= sf_h($sectionLabel) ?></span></a><?php endforeach; ?>
            </div></nav>
        <?php elseif (in_array($tab, ['checkin','history'], true)): ?>
            <nav class="arena-mini-nav arena-workout-tabs" aria-label="Luyện tập"><span class="arena-mini-caption"><i></i>CHẾ ĐỘ LUYỆN TẬP</span><div class="arena-mini-track"><a class="arena-mini-tab <?= $tab === 'checkin' ? 'is-active' : '' ?>" href="<?= sf_h(url('workouts')) ?>" <?= $tab === 'checkin' ? 'aria-current="page"' : '' ?>><?= $arenaIcon('plus') ?><span>Bắt đầu / Kết thúc</span></a><a class="arena-mini-tab <?= $tab === 'history' ? 'is-active' : '' ?>" href="<?= sf_h(url('workouts/history')) ?>" <?= $tab === 'history' ? 'aria-current="page"' : '' ?>><?= $arenaIcon('history') ?><span>Lịch sử</span></a><a class="arena-mini-tab" href="<?= sf_h(url('profile?section=schedule')) ?>"><?= $arenaIcon('calendar') ?><span>Lịch tập</span></a></div></nav>
        <?php endif; ?>
        <?php if ($tab === 'profile'): ?>
            <?php if ($profileSection === 'overview'): ?>
                <div class="personal-intro"><div class="eyebrow">STREAKFIT</div><h1>C&#225; nh&#226;n</h1><p class="muted">Qu&#7843;n l&#253; h&#7891; s&#417; v&#224; c&#224;i &#273;&#7863;t d&#7877; h&#417;n.</p></div>
                <?php if ($canAdminDashboard): ?><a class="panel" style="display:flex;justify-content:space-between;align-items:center;gap:12px" href="<?= sf_h(url('admin')) ?>"><span><strong>Trung tâm quản trị</strong><small class="muted" style="display:block">Mở bảng điều khiển quản trị cộng đồng</small></span><span>↗</span></a><?php endif; ?>
            <?php else: ?>
                <div class="profile-subhead"><a href="<?= sf_h(url('profile')) ?>">&lsaquo; <span>C&#225; nh&#226;n</span></a><h1><?= sf_h(html_entity_decode($profileSectionLabels[$profileSection] ?? 'C&#225; nh&#226;n', ENT_QUOTES, 'UTF-8')) ?></h1></div>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($flash): ?><div class="alert <?= sf_h($flash['type']) ?>" <?= $flash['type'] !== 'error' && str_contains((string)$flash['message'], 'Buổi tập hợp lệ đã được đăng') ? 'data-sf-celebrate' : '' ?>><?= sf_h($flash['message']) ?></div><?php endif; ?>

        <?php if ($tab === 'feed'): ?>
            <?php require APP_ROOT . '/app/Views/components/arena-today.php'; ?>
            <div data-arena-panel="overview" class="sf38-weather-slot sf41-weather-slot"><?php require APP_ROOT . '/app/Views/components/arena-atmosphere.php'; ?></div>
            <section class="sf39-insights-head" data-arena-panel="insights"><div><span class="sf39-kicker">PHÂN TÍCH CÁ NHÂN</span><h1>Thống kê luyện tập</h1></div><a class="sf39-outline" href="<?= sf_h(url('profile?section=health')) ?>">Cập nhật số đo →</a></section>
            <?php require APP_ROOT . '/app/Views/components/arena-performance.php'; ?>
            <div class="sf38-bento" data-arena-panel="insights">
            <?php require APP_ROOT . '/app/Views/components/arena-week-chart.php'; ?>
            <?php require APP_ROOT . '/app/Views/components/arena-health-charts.php'; ?>
            </div>
            
            <div data-arena-panel="insights" class="sf39-week-slot"><?php require APP_ROOT . '/app/Views/components/arena-week-plan.php'; ?></div>

            <?php require APP_ROOT . '/app/Views/components/arena-community-hub.php'; ?>
            <?php require APP_ROOT . '/app/Views/components/arena-stories.php'; ?>
            <?php $timelinePanel = 'community'; require APP_ROOT . '/app/Views/components/arena-timeline.php'; ?>
            <section class="activity-strip" data-arena-panel="community" data-activity-strip>
                <div class="activity-strip-head"><strong>Hoạt động thành viên</strong><a href="<?= sf_h(url('leaderboard?view=board')) ?>">Bảng vàng &rsaquo;</a></div>
                <?php if ($activityMembers): ?><div class="activity-people"><?php foreach ($activityMembers as $member): $memberWorking = $member['status'] === 'in_progress'; ?><div class="activity-person" data-activity-kind="<?= $memberWorking ? 'working' : 'completed' ?>"><span class="activity-avatar-wrap <?= $memberWorking ? 'is-working' : 'is-completed' ?>"><span class="avatar activity-avatar"><?= sf_h(mb_substr($member['display_name'], 0, 1, 'UTF-8')) ?></span></span><strong><?= sf_h($member['display_name']) ?></strong><small><?= $memberWorking ? '<i></i> Đang tập' : '&#10003; Đã xong' ?> · <?= (int)$member['current_streak'] ?> tuần</small></div><?php endforeach; ?></div><?php endif; ?>
                <p class="activity-empty" data-activity-none <?= $activityMembers ? 'hidden' : '' ?>>Chưa có hoạt động mới từ thành viên khác.</p>
                <p class="activity-filter-empty" data-activity-empty hidden>Hiện chưa có thành viên nào đang tập.</p>
            </section>
            <div class="section-heading feed-heading" data-arena-panel="community"><div><h2><span class="community-icon">♟</span> Bảng tin cộng đồng</h2><p class="muted">Chia sẻ tiến bộ, cổ vũ nhau giữ nhịp.</p></div></div>
            <nav class="feed-filters" data-arena-panel="community" aria-label="Lọc hoạt động bảng tin" role="group"><button class="active" type="button" data-feed-filter="all" aria-pressed="true">▦ &nbsp; Tất cả</button><button type="button" data-feed-filter="working" aria-pressed="false">⚡ &nbsp; Đang tập <b><?= $activeTrainingCount ?></b></button><button type="button" data-feed-filter="completed" aria-pressed="false">✓ &nbsp; Hoàn thành</button></nav>
            <?php if (!$feed): ?><section class="empty-state feed-empty-state" data-arena-panel="community" data-feed-empty><span>🏋️</span><h3>Bảng tin đang chờ buổi tập đầu tiên</h3><p>Khi bạn hoàn thành ghi nhận buổi tập, thẻ tổng kết sẽ xuất hiện ở đây.</p><a class="btn primary" href="<?= sf_h(url('workouts')) ?>">Bắt đầu tập</a></section><?php endif; ?>
            <?php foreach ($feed as $post): $postRank = sf_rank((int)$post['total_points']); ?>
                <article class="post-card" id="workout-<?= (int)$post['workout_id'] ?>" data-arena-panel="community" data-feed-post data-feed-kind="completed" data-sf33-workout="<?= (int)$post['workout_id'] ?>" data-sf33-reaction-api="<?= sf_h(url('api/action')) ?>">
                    <div class="post-author"><div class="avatar"><?= sf_h(mb_substr($post['display_name'], 0, 1, 'UTF-8')) ?></div><div class="post-author-meta"><strong><?= sf_h($post['display_name']) ?> <span class="rank-mini rank-with-emblem"><?= sf_rank_emblem($postRank, 'rank-emblem rank-emblem-small') ?> <?= sf_h($postRank['name']) ?></span></strong><small><?= sf_h(sf_format_time($post['checked_out_at'], 'd/m · H:i')) ?></small></div><span class="post-duration">🔥 <?= (int)round((int)$post['duration_seconds'] / 60) ?> phút</span></div>
                    <div class="post-complete-label"><span>✓</span> Đã hoàn thành buổi tập <b>🔥 <?= (int)$post['current_streak'] ?> tuần liên tiếp</b></div>
                    <div class="workout-photos sf38-photos"><?php foreach(['checkin'=>'Bắt đầu','checkout'=>'Kết thúc'] as $kind=>$label): ?><a href="?media=<?= (int)$post['workout_id'] ?>&kind=<?= $kind ?>" data-sf38-photo><img src="?media=<?= (int)$post['workout_id'] ?>&kind=<?= $kind ?>" alt="<?= sf_h($label . ' · ' . $post['display_name']) ?>" loading="lazy" decoding="async"><span><?= sf_h($label) ?></span></a><?php endforeach; ?></div>
                    <?php if ($post['caption']): ?><p class="post-caption"><?= nl2br(sf_h($post['caption'])) ?></p><?php endif; ?>
                    <div class="post-actions"><div class="reaction-buttons"><?php foreach (['heart' => '❤️', 'muscle' => '💪', 'fire' => '🔥', 'sad' => '🥹', 'haha' => '😂'] as $reaction => $emoji): ?><form method="post"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="react"><input type="hidden" name="workout_id" value="<?= (int)$post['workout_id'] ?>"><input type="hidden" name="reaction" value="<?= sf_h($reaction) ?>"><button class="reaction <?= $post['my_reaction'] === $reaction ? 'selected' : '' ?>" title="Thả cảm xúc" aria-pressed="<?= $post['my_reaction'] === $reaction ? 'true' : 'false' ?>"><?= $emoji ?></button></form><?php endforeach; ?></div><span><b data-sf33-reaction-count><?= (int)$post['reaction_count'] ?></b> cảm xúc · <?= (int)$post['comment_count'] ?> bình luận</span></div><p class="sf33-post-status" data-sf33-post-status role="status" hidden></p>
                    <div class="comment-list"><?php foreach ($post['comments'] as $comment): ?><p><b><?= sf_h($comment['display_name']) ?></b> <?= sf_h($comment['comment_text']) ?></p><?php endforeach; ?></div>
                    <form method="post" class="comment-form"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="comment"><input type="hidden" name="workout_id" value="<?= (int)$post['workout_id'] ?>"><input name="comment" maxlength="500" placeholder="Viết lời động viên…" required><button aria-label="Gửi bình luận">➤</button></form>
                </article>
            <?php endforeach; ?>


        <?php elseif ($tab === 'checkin'): ?>
            <div class="page-intro"><div class="eyebrow">GHI NHẬN BUỔI TẬP</div><h1><?= $activeWorkout ? 'Bạn đang tập luyện 🔥' : 'Ghi nhận buổi tập' ?></h1><p class="muted"><?= $activeWorkout ? 'Chụp ảnh kết thúc khi đã tập tối thiểu ' . (int)ceil($settings['minimum_workout_seconds'] / 60) . ' phút.' : 'Chụp trực tiếp bằng camera để bắt đầu. Không nhận ảnh từ thư viện.' ?></p></div>
            <?php require APP_ROOT . '/app/Views/components/arena-capture.php'; ?>


        <?php elseif ($tab === 'leaderboard'): ?>
            <div class="page-intro"><h1 data-arena-view-title><?= sf_h(['rank' => 'Huy hiệu & Thăng hạng', 'board' => 'Bảng vàng', 'court' => 'Nghỉ tập & giải trình'][$arenaView] ?? 'Bảng vàng') ?></h1></div>
            <section id="sf43-rank-stage" class="rank-cinema sf43-rank-stage" data-arena-panel="rank" data-rank-cinema data-rank-slug="<?= sf_h($rank['slug']) ?>" data-rank-name="<?= sf_h($rank['name']) ?>" data-rank-min="<?= (int)$rank['min'] ?>" data-rank-image="<?= sf_h(sf_asset_version('ranks/' . $rank['slug'] . '-v44.webp')) ?>" data-rank-scene="<?= sf_h(sf_asset_version('ranks/' . $rank['slug'] . '-scene.svg')) ?>" style="--sf43-scene:url('<?= sf_h(sf_asset_version('ranks/' . $rank['slug'] . '-scene.svg')) ?>')" aria-label="Huy hiệu cấp bậc và mô hình 3D">
                <div class="rank-cinema-grid" aria-hidden="true"></div>
                <div class="rank-cinema-copy sf43-rank-copy">
                    <span class="sf43-kicker">BỘ SƯU TẬP HUY HIỆU</span>
                    <div class="sf43-rank-portrait"><img data-sf43-portrait src="<?= sf_h(sf_asset_version('ranks/' . $rank['slug'] . '-v44.webp')) ?>" alt="" width="768" height="768" decoding="async"></div>
                    <div class="rank-cinema-info" aria-live="polite"><span data-sf43-preview-state>HẠNG HIỆN TẠI</span><strong data-rank-title><?= sf_h($rank['name']) ?></strong><small data-rank-points><?= number_format((int)$rank['min'], 0, ',', '.') ?>+ điểm tích lũy</small></div>
                </div>
                <div class="sf43-model-panel">
                    <div class="sf43-model-head"><span>Mô hình 3D</span><span class="sf43-model-dot" aria-hidden="true"></span></div>
                    <div class="rank-cinema-visual" data-rank-surface role="group" tabindex="0" aria-label="Huy hiệu 3D, kéo để xoay hoặc dùng phím mũi tên">
                        <canvas class="rank-cinema-canvas" data-rank-canvas aria-hidden="true"></canvas>
                        <?= sf_rank_emblem($rank, 'rank-emblem rank-cinema-fallback') ?>
                        <span class="rank-cinema-halo" aria-hidden="true"></span>
                    </div>
                    <div class="rank-cinema-instruction"><div class="arena-rank-controls"><button type="button" data-rank-step="-1" aria-label="Hạng trước">‹</button><button type="button" data-rank-step="1" aria-label="Hạng tiếp theo">›</button><button type="button" data-rank-reset>Góc gốc</button><button type="button" data-rank-zoom="-1" aria-label="Thu nhỏ">−</button><button type="button" data-rank-zoom="1" aria-label="Phóng to">+</button></div></div>
                </div>
            </section>
            <?php $energyPanel = 'rank'; require APP_ROOT . '/app/Views/components/arena-rank-energy.php'; ?>
            <section class="panel rank-showcase sf43-showcase" data-arena-panel="rank" aria-labelledby="rank-showcase-title">
                <div class="section-heading"><div><h2 id="rank-showcase-title">Các cấp bậc</h2><p class="muted">Cấp bậc của bạn được tính từ tổng điểm tích lũy.</p></div><span class="rank-chip rank-with-emblem"><?= sf_rank_emblem($rank, 'rank-emblem rank-emblem-small') ?> <?= sf_h($rank['name']) ?></span></div>

                <div class="rank-gallery"><?php foreach (sf_rank_catalog() as $tier): $selected = $tier['slug'] === $rank['slug']; ?><button type="button" class="rank-tier <?= $selected ? 'is-current is-preview' : '' ?>" data-rank-select data-rank-scene="<?= sf_h(sf_asset_version('ranks/' . $tier['slug'] . '-scene.svg')) ?>" style="--sf43-scene:url('<?= sf_h(sf_asset_version('ranks/' . $tier['slug'] . '-scene.svg')) ?>')" data-rank-name="<?= sf_h($tier['name']) ?>" data-rank-image="<?= sf_h(sf_asset_version('ranks/' . $tier['slug'] . '-v44.webp')) ?>" data-rank-min="<?= (int)$tier['min'] ?>" data-rank-slug="<?= sf_h($tier['slug']) ?>" aria-pressed="<?= $selected ? 'true' : 'false' ?>" aria-label="Xem huy hiệu <?= sf_h($tier['name']) ?>, từ <?= number_format((int)$tier['min'], 0, ',', '.') ?> điểm"><span class="sf43-card-art" aria-hidden="true"><?= sf_rank_emblem($tier, 'rank-emblem rank-emblem-gallery') ?></span><strong><?= sf_h($tier['name']) ?></strong><small><?= number_format($tier['min'], 0, ',', '.') ?>+ điểm</small><span class="arena-tier-status"><?= $selected ? 'HIỆN TẠI' : ($arenaPoints >= $tier['min'] ? 'ĐÃ MỞ' : 'CHƯA MỞ') ?></span></button><?php endforeach; ?></div>
                <?php if ($rank['next'] !== null): ?><p class="rank-next">Còn <?= number_format(max(0, $rank['next'] - (int)$user['total_points']), 0, ',', '.') ?> điểm để lên cấp kế tiếp.</p><?php else: ?><p class="rank-next">Bạn đã đạt cấp cao nhất.</p><?php endif; ?>
            </section>
            <?php require APP_ROOT . '/app/Views/components/arena-achievements.php'; ?>
            <div class="sub-tabs" data-arena-panel="board"><a class="<?= $period === 'week' ? 'active' : '' ?>" href="<?= sf_h(url('leaderboard?view=board&period=week')) ?>">Tuần</a><a class="<?= $period === 'month' ? 'active' : '' ?>" href="<?= sf_h(url('leaderboard?view=board&period=month')) ?>">Tháng</a><a class="<?= $period === 'all' ? 'active' : '' ?>" href="<?= sf_h(url('leaderboard?view=board&period=all')) ?>">Toàn thời gian</a></div>
            <section class="panel leaderboard-panel" data-arena-panel="board"><div class="section-heading"><div><h2><?= $period === 'all' ? 'Thành tích tích lũy' : ($period === 'month' ? 'Tổng điểm tháng này' : 'Điểm tuần tạm tính') ?></h2><p class="muted"><?= $period === 'week' ? 'Điểm tuần được chốt khi tổng kết tuần.' : ($period === 'month' ? 'Điểm tích lũy trong tháng hiện tại.' : 'Điểm tích lũy của tất cả các kỳ.') ?></p></div><span class="rank-chip"><?= count($leaderboard) ?> thành viên</span></div>
                <?php if ($leaderboard): ?><div class="arena-podium" aria-label="Ba vị trí dẫn đầu"><?php foreach (array_slice($leaderboard, 0, 3) as $arenaPlace => $arenaLeader): $arenaLeaderRank = sf_rank((int)$arenaLeader['total_points']); ?><div class="arena-podium-slot place-<?= $arenaPlace + 1 ?>"><span class="arena-podium-place">0<?= $arenaPlace + 1 ?></span><?= sf_rank_emblem($arenaLeaderRank, 'arena-podium-emblem') ?><strong><?= sf_h($arenaLeader['display_name']) ?></strong><small><?= sf_h($arenaLeaderRank['name']) ?></small><b><?= number_format((int)$arenaLeader['points'], 0, ',', '.') ?> điểm</b></div><?php endforeach; ?></div><?php endif; ?>
                <div class="leaderboard-list"><?php foreach ($leaderboard as $index => $row): $memberRank = sf_rank((int)$row['total_points']); ?><div class="leader-row <?= (int)$row['id'] === (int)$user['id'] ? 'mine' : '' ?>"><span class="leader-place"><?= $index < 3 ? ['🥇','🥈','🥉'][$index] : (string)($index + 1) ?></span><?= sf_rank_emblem($memberRank, 'rank-emblem rank-emblem-list') ?><span class="leader-name"><b><?= sf_h($row['display_name']) ?></b><small><?= sf_h($memberRank['name']) ?> · <?= (int)$row['streak'] ?> tuần liên tiếp · <?= (int)$row['sessions'] ?> buổi</small></span><strong class="leader-score"><?= number_format((int)$row['points']) ?><small>điểm <?= $period === 'week' ? 'tuần' : ($period === 'month' ? 'tháng' : 'tích lũy') ?></small></strong></div><?php endforeach; ?></div>
            </section>
            <?php require APP_ROOT . '/app/Views/components/arena-appeal-guide.php'; ?>
            <section class="panel court-panel" data-arena-panel="court"><div class="section-heading"><div><h2>Nghỉ tập & giải trình</h2><p class="muted">Gửi lý do nếu không hoàn thành mục tiêu. Thành viên biểu quyết ẩn danh; gửi giải trình không tự động được miễn tập.</p></div><span>⚖️</span></div>
                <?php foreach ($appeals as $appeal): ?><article class="appeal-card"><div class="appeal-state">CẦN BỎ PHIẾU · <?= (int)$appeal['yes_votes'] ?> đồng ý / <?= (int)$appeal['no_votes'] ?> không đồng ý</div><p><?= sf_h($appeal['reason']) ?></p><?php if ($appeal['proof_path']): ?><a href="?appeal_media=<?= (int)$appeal['id'] ?>" target="_blank" rel="noopener"><img class="appeal-proof" src="?appeal_media=<?= (int)$appeal['id'] ?>" alt="Ảnh minh chứng giải trình"></a><?php else: ?><small>Không có ảnh minh chứng</small><?php endif; ?><?php if ((int)$appeal['user_id'] !== (int)$user['id'] && !$appeal['my_vote']): ?><form method="post" class="vote-row"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="vote_appeal"><input type="hidden" name="appeal_id" value="<?= (int)$appeal['id'] ?>"><button class="btn vote-yes" name="vote" value="yes">Đồng ý giữ chuỗi</button><button class="btn vote-no" name="vote" value="no">Không đồng ý</button></form><?php elseif ($appeal['my_vote']): ?><small>Bạn đã bỏ phiếu: <?= $appeal['my_vote'] === 'yes' ? 'đồng ý' : 'không đồng ý' ?></small><?php endif; ?></article><?php endforeach; ?>
                <?php if (!$appeals): ?><p class="empty-inline">Chưa có giải trình nào cần biểu quyết.</p><?php endif; ?>
                <details class="appeal-details" id="sf42-appeal-form"><summary>Gửi giải trình nghỉ tập</summary><form method="post" enctype="multipart/form-data" class="stack-form"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="appeal"><label>Lý do<textarea name="reason" minlength="10" maxlength="1000" required placeholder="Mô tả ngắn lý do bạn không thể hoàn thành mục tiêu…"></textarea></label><label>Ảnh minh chứng<input type="file" name="proof" accept="image/jpeg,image/png" required></label><button class="btn secondary full">Gửi giải trình (tối đa <?= (int)($settings['appeal_monthly_limit'] ?? 2) ?> lần/tháng)</button></form></details>
            </section>

        <?php elseif ($tab === 'notifications'): ?>
            <div class="page-intro"><div class="eyebrow">CẬP NHẬT CỘNG ĐỒNG</div><h1>Thông báo</h1><p class="muted">Những lời nhắc và thành tích mới.</p></div>
            <?php if ($notifications): ?><form method="post" class="mark-read-form"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="mark_read"><button class="text-link">Đánh dấu tất cả đã đọc</button></form><?php endif; ?>
            <section class="notification-list"><?php foreach ($notifications as $notice): ?><article class="notification-card <?= !(int)$notice['is_read'] ? 'unread' : '' ?>"><span class="notification-icon"><?= $notice['type'] === 'workout' ? '🔥' : ($notice['type'] === 'appeal' ? '⚖️' : '✦') ?></span><div><strong><?= sf_h($notice['title']) ?></strong><p><?= sf_h($notice['body']) ?></p><small><?= sf_h(sf_format_time($notice['created_at'], 'd/m · H:i')) ?></small></div></article><?php endforeach; ?><?php if (!$notifications): ?><p class="empty-state">Bạn chưa có thông báo mới.</p><?php endif; ?></section>

        <?php elseif ($tab === 'history'): ?>
            <?php require APP_ROOT . '/app/Views/workout/history.php'; ?>

        <?php else: ?>
            
            <?php if (in_array($profileSection,['overview','profile'],true)): ?><section class="profile-hero panel" id="profile"><div class="avatar profile-avatar"><?= sf_h(mb_substr($user['display_name'], 0, 1, 'UTF-8')) ?></div><div><h2><?= sf_h($user['display_name']) ?></h2><p class="muted rank-profile-line"><?= sf_rank_emblem($rank, 'rank-emblem rank-emblem-small') ?> <?= sf_h($rank['name']) ?> · <?= number_format((int)$user['total_points']) ?> điểm</p></div><div class="profile-highlights"><div><b><?= (int)$user['current_streak'] ?></b><small>Chuỗi hiện tại</small></div><div><b><?= (int)$user['best_streak'] ?></b><small>Chuỗi dài nhất</small></div><div><b><?= (int)$user['total_sessions'] ?></b><small>buổi tập</small></div></div></section><?php endif; ?>
            <?php if ($profileSection === 'overview'): ?>
            <section class="profile-hub-grid" aria-label="C&#224;i &#273;&#7863;t c&#225; nh&#226;n">
                <a class="profile-hub-card" href="<?= sf_h(url('profile?section=profile')) ?>"><span class="hub-icon"><?= $arenaIcon('user') ?></span><span><strong>H&#7891; s&#417;</strong><small>Th&#244;ng tin c&#225; nh&#226;n, t&#234;n hi&#7875;n th&#7883;</small></span><i>&rsaquo;</i></a>
                <a class="profile-hub-card" href="<?= sf_h(url('leaderboard?view=court')) ?>"><span class="hub-icon"><?= $arenaIcon('calendar') ?></span><span><strong>Nghỉ tập &amp; giải trình</strong><small>Gửi lý do và theo dõi biểu quyết</small></span><i>&rsaquo;</i></a>
                <a class="profile-hub-card" href="<?= sf_h(url('profile?section=health')) ?>"><span class="hub-icon"><?= $arenaIcon('heart') ?></span><span><strong>S&#7913;c kh&#7887;e</strong><small>Ch&#7881; s&#7889; c&#417; th&#7875;, BMI, BMR</small></span><i>&rsaquo;</i></a>
                <a class="profile-hub-card" href="<?= sf_h(url('profile?section=schedule')) ?>"><span class="hub-icon"><?= $arenaIcon('calendar') ?></span><span><strong>M&#7909;c ti&#234;u &amp; L&#7883;ch t&#7853;p</strong><small>S&#7889; bu&#7893;i m&#7895;i tu&#7847;n, l&#7883;ch tu&#7847;n t&#7899;i</small></span><i>&rsaquo;</i></a>
                <a class="profile-hub-card" href="<?= sf_h(url('profile?section=fund')) ?>"><span class="hub-icon"><?= $arenaIcon('wallet') ?></span><span><strong>Qu&#7921; nh&#243;m</strong><small>Ph&#237; tham gia v&#224; s&#7893; qu&#7921;</small></span><i>&rsaquo;</i></a>
                <a class="profile-hub-card" href="<?= sf_h(url('profile?section=security')) ?>"><span class="hub-icon"><?= $arenaIcon('lock') ?></span><span><strong>B&#7843;o m&#7853;t</strong><small>&#272;&#7893;i m&#7853;t kh&#7849;u</small></span><i>&rsaquo;</i></a>
                <a class="profile-hub-card" href="<?= sf_h(url('notifications')) ?>"><span class="hub-icon"><?= $arenaIcon('bell') ?></span><span><strong>Th&#244;ng b&#225;o</strong><small>L&#7901;i nh&#7855;c v&#224; c&#7853;p nh&#7853;t</small></span><i>&rsaquo;</i></a>
                <a class="profile-hub-card" href="<?= sf_h(url('profile?section=other')) ?>"><span class="hub-icon"><?= $arenaIcon('spark') ?></span><span><strong>Tr&#7907; l&#253; AI</strong><small>G&#7907;i &#253; l&#7883;ch t&#7853;p</small></span><i>&rsaquo;</i></a>
            </section>
            <?php elseif ($profileSection === 'profile'): ?>
            <section class="panel profile-name-panel"><div class="section-heading"><div><h2>Th&#244;ng tin c&#417; b&#7843;n</h2><p class="muted">C&#7853;p nh&#7853;t t&#234;n hi&#7875;n th&#7883; tr&#234;n StreakFit.</p></div></div><form method="post" class="stack-form"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="update_display_name"><label>T&#234;n hi&#7875;n th&#7883;<input name="display_name" minlength="2" maxlength="80" value="<?= sf_h($user['display_name']) ?>" required></label><button class="btn primary full">L&#432;u th&#244;ng tin</button></form></section>
            <?php elseif ($profileSection === 'security'): ?>

            <section class="panel" id="account"><div class="section-heading"><div><h2>B&#7843;o m&#7853;t t&#224;i kho&#7843;n</h2><p class="muted">Thay &#273;&#7893;i m&#7853;t kh&#7849;u c&#7911;a b&#7841;n.</p></div></div><form method="post" class="stack-form"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="change_password"><label>M&#7853;t kh&#7849;u hi&#7879;n t&#7841;i<input type="password" name="current_password" minlength="8" required autocomplete="current-password"></label><label>M&#7853;t kh&#7849;u m&#7899;i<input type="password" name="new_password" minlength="8" maxlength="72" required autocomplete="new-password"></label><button class="btn primary full">C&#7853;p nh&#7853;t m&#7853;t kh&#7849;u</button></form></section>
            <?php endif; ?>
            <?php if ($profileSection === 'health'): ?><section class="panel" id="health"><div class="section-heading"><div><h2>Chỉ số sức khỏe</h2><p class="muted">Cập nhật chiều cao, cân nặng và tuổi.</p></div></div><form method="post" class="health-form"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="update_health"><input type="hidden" name="display_name" value="<?= sf_h($user['display_name']) ?>"><label>Cân nặng (kg)<input type="number" min="25" max="250" step="0.1" name="weight_kg" value="<?= sf_h($user['weight_kg'] ?? '') ?>" required></label><label>Chiều cao (cm)<input type="number" min="100" max="230" step="0.1" name="height_cm" value="<?= sf_h($user['height_cm'] ?? '') ?>" required></label><label>Tuổi<input type="number" min="16" max="100" name="age" value="<?= sf_h($user['age'] ?? '') ?>" required></label><label>Giới tính<select name="gender"><option value="unspecified" <?= $user['gender'] === 'unspecified' ? 'selected' : '' ?>>Chưa chọn</option><option value="female" <?= $user['gender'] === 'female' ? 'selected' : '' ?>>Nữ</option><option value="male" <?= $user['gender'] === 'male' ? 'selected' : '' ?>>Nam</option></select></label><button class="btn primary full">Lưu chỉ số sức khỏe</button></form></section>
            <?php require APP_ROOT . '/app/Views/components/arena-health-charts.php'; endif; ?>
            <?php if ($profileSection === 'schedule') require APP_ROOT . '/app/Views/components/arena-schedule.php'; ?>
            <?php if ($profileSection === 'fund'): require APP_ROOT . '/app/Views/components/arena-finance.php'; ?>
            <a class="btn primary full fund-payment-link" href="<?= e(url('fund/payments')) ?>">Nộp quỹ qua QR / Lịch sử chuyển khoản</a><section class="panel fund-panel"><div class="section-heading"><div><h2>Quỹ nhóm tháng</h2><p class="muted">Sổ quỹ minh bạch · chuyển khoản ngân hàng qua QR.</p></div><span>💰</span></div><div class="fund-summary"><div><small>Tham gia</small><b><?= (int)$fundSummary['participants'] ?></b></div><div><small>Đã xác nhận đóng</small><b><?= (int)$fundSummary['paid_count'] ?></b></div><div><small>Sổ quỹ</small><b><?= number_format((int)$fundSummary['recorded_total']) ?>đ</b></div></div><p class="fund-note">Mức đóng tự nguyện: <?= number_format((int)$fund['contribution_amount']) ?>đ/tháng. Thanh toán trực tuyến được xác nhận sau khi đối soát với ngân hàng.</p><?php if (!$fundMember || !(int)$fundMember['opted_in']): ?><form method="post"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="fund_join"><button class="btn secondary full">Tự nguyện tham gia quỹ tháng này</button></form><?php else: ?><div class="fund-member-status">Bạn đã tham gia · <?= $fundMember['contribution_status'] === 'paid' ? 'Đã xác nhận đóng' : 'Chưa xác nhận đóng' ?></div><?php if ($fundMember['contribution_status'] === 'unpaid'): ?><form method="post" class="fund-inline-form"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="fund_leave"><button class="text-link">Rời quỹ tháng này</button></form><?php endif; ?><?php endif; ?><div class="fund-proposal"><h3>Đề xuất & biểu quyết mức phạt</h3><?php foreach ($fundProposals as $proposal): ?><div class="fund-proposal-row"><span><?= sf_h($proposal['display_name']) ?></span><b><?= number_format((int)$proposal['amount']) ?>đ</b></div><?php endforeach; ?><?php if ($fund['status'] === 'open' && $fundMember && (int)$fundMember['opted_in']): ?><form method="post" class="fund-inline-form"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="fund_propose"><label>Mức phạt đề xuất<input name="amount" type="number" min="1000" max="1000000" step="1000" required placeholder="Ví dụ: 50.000"></label><button class="btn secondary">Lưu đề xuất</button></form><?php endif; ?><?php if ($fund['status'] === 'voting' && $fundMember && (int)$fundMember['opted_in'] && $fundProposals): ?><form method="post" class="fund-inline-form"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="fund_vote"><label>Bỏ phiếu<select name="amount"><?php $seenAmounts = []; foreach ($fundProposals as $proposal): if (isset($seenAmounts[$proposal['amount']])) continue; $seenAmounts[$proposal['amount']] = true; ?><option value="<?= (int)$proposal['amount'] ?>"><?= number_format((int)$proposal['amount']) ?>đ</option><?php endforeach; ?></select></label><button class="btn secondary">Gửi phiếu</button></form><?php endif; ?><?php if ($fundVotes): ?><div class="fund-votes"><?php foreach ($fundVotes as $vote): ?><span><?= number_format((int)$vote['amount']) ?>đ · <?= (int)$vote['votes'] ?> phiếu</span><?php endforeach; ?></div><?php endif; ?></div><?php if ($fund['status'] === 'closed'): ?><div class="final-penalty">Mức phạt tháng đã chốt: <b><?= number_format((int)$fund['final_penalty']) ?>đ</b></div><?php endif; ?><?php if ($canManageFund): ?><div class="admin-fund-controls"><?php foreach ($fundMembers as $member): ?><?php if ($member['contribution_status'] === 'unpaid'): ?><form method="post" class="fund-member-row"><span><?= sf_h($member['display_name']) ?></span><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="fund_mark_paid"><input type="hidden" name="member_id" value="<?= (int)$member['user_id'] ?>"><button class="text-link">Đánh dấu đã đóng</button></form><?php endif; ?><?php endforeach; ?><?php if ($fund['status'] === 'open' && $fundProposals): ?><form method="post"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="fund_start_vote"><button class="btn secondary full">Mở biểu quyết mức phạt</button></form><?php elseif ($fund['status'] === 'voting'): ?><form method="post"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="fund_close_vote"><button class="btn secondary full">Chốt mức phạt theo phiếu</button></form><?php endif; ?></div><?php endif; ?></section>
            <?php endif; ?>
            <?php if ($profileSection === 'other'): ?><section class="panel assistant-panel"><div class="section-heading"><div><h2>Trợ lý luyện tập</h2><p class="muted">Gợi ý dựa trên tiến độ tập của bạn, không thay thế tư vấn y tế.</p></div></div><div class="assistant-answer" id="assistantAnswer" role="status" aria-live="polite">Hỏi về mục tiêu tuần này hoặc cách sắp xếp các buổi tập.</div><small class="muted" id="assistantMode"><?= (app_config()['gemini_api_key'] ?? '') !== '' ? 'Gemini được bật · Câu hỏi và tiến độ tuần được gửi đến Google khi bạn nhấn Gửi. Không nhập thông tin sức khỏe nhạy cảm.' : 'Chế độ gợi ý từ dữ liệu StreakFit.' ?></small><form class="assistant-form" id="assistantForm" data-endpoint="<?= sf_h(url('api/coach')) ?>"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input id="assistantQuestion" name="question" maxlength="300" minlength="3" required placeholder="Hỏi về mục tiêu và lịch tập…"><button class="btn primary" type="submit">Gửi</button></form><small class="muted">Tối đa 20 câu hỏi mỗi ngày cho mỗi phiên đăng nhập.</small></section><?php endif; ?>
        <?php endif; ?>
    </main>
    <?php if ($tab === 'feed') require APP_ROOT . '/app/Views/components/arena-member-rail.php'; ?>
    <?php require APP_ROOT . '/app/Views/components/arena-dock.php'; ?>
</div>
<script src="<?= sf_h(sf_asset_version('js/streakfit.js')) ?>" defer></script>
<script src="<?= sf_h(sf_asset_version('js/streakfit-future.js')) ?>" defer></script>
<script src="<?= sf_h(sf_asset_version('js/streakfit-motion.js')) ?>" defer></script>

<script src="<?= sf_h(sf_asset_version('js/arena-3d.js')) ?>" defer></script>
<script src="<?= sf_h(sf_asset_version('js/arena-ui.js')) ?>" defer></script>
<?php if ($tab === 'profile' && $profileSection === 'schedule'): ?><script src="<?= sf_h(sf_asset_version('js/arena-v32.js')) ?>" defer></script><?php endif; ?>
<?php if ($tab === 'feed'): ?><script src="<?= sf_h(sf_asset_version('js/arena-v33.js')) ?>" defer></script><?php endif; ?>
<?php if ($tab === 'feed'): ?><script src="<?= sf_h(sf_asset_version('js/arena-atmosphere.js')) ?>" defer></script><?php endif; ?>
<script src="<?= sf_h(sf_asset_version('js/arena-v37.js')) ?>" defer></script>
<script src="<?= sf_h(sf_asset_version('js/arena-v38.js')) ?>" defer></script>
<script src="<?= sf_h(sf_asset_version('js/arena-v39.js')) ?>" defer></script>
<script src="<?= sf_h(sf_asset_version('js/arena-v40.js')) ?>" defer></script>
<script src="<?= sf_h(sf_asset_version('js/arena-v42.js')) ?>" defer></script>
<script src="<?= sf_h(sf_asset_version('js/arena-v41.js')) ?>" defer></script>
</body>
</html>
