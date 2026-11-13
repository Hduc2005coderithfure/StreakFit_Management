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
$tabLabels = ['feed' => 'Tổng quan', 'leaderboard' => 'Bảng vàng', 'checkin' => 'Check-in', 'notifications' => 'Thông báo', 'profile' => 'Cá nhân'];
$profileSection = is_string($_GET['section'] ?? null) ? $_GET['section'] : 'overview';
if (!in_array($profileSection, ['overview','profile','health','schedule','fund','security','other'], true)) $profileSection = 'overview';
$profileSectionLabels = ['profile' => 'H&#7891; s&#417; &amp; S&#7913;c kh&#7887;e', 'health' => 'H&#7891; s&#417; &amp; S&#7913;c kh&#7887;e', 'schedule' => 'M&#7909;c ti&#234;u &amp; L&#7883;ch t&#7853;p', 'fund' => 'Qu&#7921; k&#7927; lu&#7853;t', 'security' => 'B&#7843;o m&#7853;t', 'other' => 'C&#224;i &#273;&#7863;t kh&#225;c'];
$arenaViews = [
    'feed' => ['overview' => ['Tổng quan', 'grid'], 'community' => ['Cộng đồng', 'people']],
    'leaderboard' => ['rank' => ['Rank 3D', 'rank'], 'board' => ['Bảng vàng', 'trophy'], 'court' => ['Tòa án', 'scale']],
];
$arenaView = '';
if (isset($arenaViews[$tab])) {
    $arenaRequestPath = (string)parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $arenaDefault = $tab === 'feed' ? 'overview' : (str_ends_with(rtrim($arenaRequestPath, '/'), '/court') ? 'court' : (isset($_GET['period']) ? 'board' : 'rank'));
    $arenaRequested = is_string($_GET['view'] ?? null) ? $_GET['view'] : $arenaDefault;
    $arenaView = isset($arenaViews[$tab][$arenaRequested]) ? $arenaRequested : $arenaDefault;
}
$arenaIcons = [
    'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
    'people' => '<circle cx="9" cy="8" r="3"/><path d="M3 20v-2a6 6 0 0 1 12 0v2M16 5a3 3 0 0 1 0 6m2 3a5 5 0 0 1 3 4v2"/>',
    'rank' => '<path d="m12 2 8 4v6c0 5-8 10-8 10S4 17 4 12V6z"/><path d="m12 6 3 5-3 5-3-5z"/>',
    'trophy' => '<path d="M8 3h8v8a4 4 0 0 1-8 0zM8 5H4v3a4 4 0 0 0 4 4m8-7h4v3a4 4 0 0 1-4 4m-4 3v5m-4 1h8"/>',
    'scale' => '<path d="M12 3v18M4 7h16M5 7l-3 7h6zm14 0-3 7h6zM8 21h8"/>',
    'plus' => '<path d="M12 5v14M5 12h14"/>',
    'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
    'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>',
    'heart' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0l-1 1-1-1a5.5 5.5 0 0 0-7.8 7.8l8.8 8.6 8.8-8.6a5.5 5.5 0 0 0 0-7.8z"/>',
    'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4m8-4v4m-8 8h2m4 0h2"/>',
    'wallet' => '<rect x="3" y="5" width="18" height="15" rx="3"/><path d="M21 10h-5a3 3 0 0 0 0 6h5m-5-3h.01"/>',
    'lock' => '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 4v3"/>',
    'spark' => '<path d="m12 2 2.5 7.5L22 12l-7.5 2.5L12 22l-2.5-7.5L2 12l7.5-2.5z"/>',
    'history' => '<path d="M3 11a9 9 0 1 1 2 7M3 4v7h7m2-4v5l4 2"/>',
];
$arenaIcon = static fn(string $name): string => '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($arenaIcons[$name] ?? $arenaIcons['grid']) . '</svg>';
$arenaProfileTabs = [
    'overview' => ['Tổng quan', 'grid'], 'profile' => ['Hồ sơ', 'user'], 'health' => ['Sức khỏe', 'heart'],
    'schedule' => ['Lịch tập', 'calendar'], 'fund' => ['Quỹ', 'wallet'], 'security' => ['Bảo mật', 'lock'], 'other' => ['AI Coach', 'spark'],
];
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0e0e12">
    <meta name="description" content="StreakFit - Theo dõi thời gian tập gym và giữ chuỗi cùng cộng đồng.">
    <title>StreakFit · <?= sf_h($arenaView === 'community' ? 'Cộng đồng' : ($tabLabels[$tab] ?? 'Luyện tập')) ?></title>
    <link rel="stylesheet" href="<?= sf_h(sf_asset_version('css/streakfit.css')) ?>">
    <?php if (in_array($tab, ['feed', 'leaderboard'], true)): ?><link rel="stylesheet" href="<?= sf_h(sf_asset_version('css/rank-cinema.css')) ?>"><?php endif; ?>
    <link rel="stylesheet" href="<?= sf_h(sf_asset_version('css/streakfit-future.css')) ?>">
    <link rel="stylesheet" href="<?= sf_h(sf_asset_version('css/streakfit-motion.css')) ?>">
    <link rel="stylesheet" href="<?= sf_h(sf_asset_version('css/arena-v3.css')) ?>">
    <link rel="stylesheet" href="<?= sf_h(sf_asset_version('css/arena-v32.css')) ?>">
    <link rel="stylesheet" href="<?= sf_h(sf_asset_version('css/arena-v33.css')) ?>">
    <link rel="stylesheet" href="<?= sf_h(sf_asset_version('css/arena-v35.css')) ?>">
    <link rel="stylesheet" href="<?= sf_h(sf_asset_version('css/arena-atmosphere.css')) ?>">
</head>
<body class="sf-future sf-arena" data-tab="<?= sf_h($tab) ?>" data-arena-view="<?= sf_h($arenaView) ?>" data-timezone="<?= sf_h(sf_config()['timezone']) ?>" <?= $tab === 'profile' ? 'data-profile-section="' . sf_h($profileSection) . '"' : '' ?>>
<?php require APP_ROOT . '/app/Views/components/arena-entry.php'; ?>
<div class="app-shell" id="appShell">
    <header class="topbar">
        <a class="brand" href="<?= sf_h(url('dashboard')) ?>"><span>Streak<i>Fit</i><b>✦</b></span><small><?= sf_h($user['display_name']) ?> · Chuỗi <?= (int)$user['current_streak'] ?> tuần</small></a>
        <div class="top-actions">
            <a class="icon-button notification-link" href="<?= sf_h(url('notifications')) ?>" aria-label="Thông báo"><svg class="notification-bell-icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg><?php if ($unreadCount): ?><em><?= (int)$unreadCount ?></em><?php endif; ?></a>
            <button class="icon-button account-menu-trigger" type="button" aria-label="Mở menu tài khoản" aria-controls="accountDrawer" aria-expanded="false">☰</button>
            <?php if ($canAdminDashboard): ?><a class="rank-chip" href="<?= sf_h(url('admin')) ?>" aria-label="Mở Admin Center">Admin ↗</a><?php endif; ?>
        </div>
    </header>
    <?php require APP_ROOT . '/app/Views/components/arena-atmosphere.php'; ?>
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
<button class="drawer-link arena-replay-link" type="button" data-arena-replay><span class="drawer-icon">↻</span><span><strong>Xem lại cinematic</strong><small>Đấu trường kỷ luật</small></span></button>
<a class="drawer-link" href="<?= sf_h(url('dashboard?view=community')) ?>"><span class="drawer-icon">♟</span><span><strong>Cộng đồng</strong><small>Bảng tin và hoạt động thành viên</small></span><i>›</i></a>
<a class="drawer-link" href="<?= e(url('groups')) ?>"><span class="drawer-icon">🚴</span><span><strong>Nhóm thể thao</strong><small>Chạy bộ, đạp xe và hẹn tập</small></span></a>
<a class="drawer-link" href="<?= e(url('fund/payments')) ?>"><span class="drawer-icon">₫</span><span><strong>Nộp quỹ qua QR</strong><small>Chuyển khoản và lịch sử thanh toán</small></span></a>
                <a class="drawer-link" href="<?= sf_h(url('profile?section=profile')) ?>"><span class="drawer-icon">♙</span><span><strong>Hồ sơ cá nhân</strong><small>Thông tin và thành tích</small></span><i>›</i></a>
                <a class="drawer-link" href="<?= sf_h(url('profile?section=schedule')) ?>"><span class="drawer-icon">▦</span><span><strong>Lịch tập của tôi</strong><small>Xem và chỉnh sửa lịch</small></span><i>›</i></a>
                <a class="drawer-link" href="<?= sf_h(url('leaderboard')) ?>"><span class="drawer-icon">♜</span><span><strong>Rank &amp; Huy hiệu</strong><small>Thành tích và thứ hạng</small></span><i>›</i></a>
                <a class="drawer-link" href="<?= sf_h(url('profile?section=security')) ?>"><span class="drawer-icon">⚙</span><span><strong>Cài đặt tài khoản</strong><small>Mật khẩu và quyền riêng tư</small></span><i>›</i></a>
                <?php if ($canAdminDashboard): ?><a class="drawer-link drawer-link-admin" href="<?= sf_h(url('admin')) ?>"><span class="drawer-icon">♙</span><span><strong>Quản trị hệ thống</strong><small>Thành viên và vận hành nhóm</small></span><i>›</i></a><?php endif; ?>
                <a class="drawer-link drawer-link-notifications" href="<?= sf_h(url('notifications')) ?>"><span class="drawer-icon">♧</span><span><strong>Thông báo</strong><small>Tùy chỉnh nhắc nhở</small></span><?php if ($unreadCount): ?><b class="drawer-unread"><?= (int)$unreadCount ?></b><?php endif; ?><i>›</i></a>
            </nav>
            <div class="drawer-divider drawer-bottom-divider"></div>
            <form method="post" class="drawer-logout"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="logout"><button type="submit">↪ &nbsp; Đăng xuất</button></form>
            <small class="drawer-footer">StreakFit · Arena V3.5 Final</small>
        </aside>
    </div>
    <main class="page-content">
        <?php if (isset($arenaViews[$tab])): ?>
            <nav class="arena-mini-nav" data-arena-local-tabs aria-label="Các mục <?= sf_h($tabLabels[$tab] ?? '') ?>">
                <span class="arena-mini-caption"><i></i><?= $tab === 'feed' ? 'COMMAND CENTER' : 'HALL OF LEGENDS' ?></span>
                <div class="arena-mini-track">
                <?php foreach ($arenaViews[$tab] as $viewKey => [$viewLabel, $viewIcon]): ?>
                    <a class="arena-mini-tab <?= $arenaView === $viewKey ? 'is-active' : '' ?>" href="<?= sf_h(url(($tab === 'feed' ? 'dashboard' : 'leaderboard') . '?view=' . $viewKey . ($tab === 'leaderboard' && $viewKey === 'board' ? '&period=' . $period : ''))) ?>" data-arena-tab="<?= sf_h($viewKey) ?>" <?= $arenaView === $viewKey ? 'aria-current="page"' : '' ?>><?= $arenaIcon($viewIcon) ?><span><?= sf_h($viewLabel) ?></span></a>
                <?php endforeach; ?>
                <?php if ($tab === 'feed'): ?><a class="arena-mini-tab arena-mini-external" href="<?= sf_h(url('profile?section=schedule')) ?>"><?= $arenaIcon('calendar') ?><span>Lịch tập</span></a><?php endif; ?>
                </div>
            </nav>
        <?php elseif ($tab === 'profile'): ?>
            <nav class="arena-mini-nav arena-profile-tabs" aria-label="Chức năng cá nhân"><span class="arena-mini-caption"><i></i>PLAYER PROFILE</span><div class="arena-mini-track">
                <?php foreach ($arenaProfileTabs as $sectionKey => [$sectionLabel, $sectionIcon]): ?><a class="arena-mini-tab <?= $profileSection === $sectionKey ? 'is-active' : '' ?>" href="<?= sf_h(url('profile?section=' . $sectionKey)) ?>" <?= $profileSection === $sectionKey ? 'aria-current="page"' : '' ?>><?= $arenaIcon($sectionIcon) ?><span><?= sf_h($sectionLabel) ?></span></a><?php endforeach; ?>
            </div></nav>
        <?php elseif (in_array($tab, ['checkin','history'], true)): ?>
            <nav class="arena-mini-nav arena-workout-tabs" aria-label="Luyện tập"><span class="arena-mini-caption"><i></i>TRAINING MODE</span><div class="arena-mini-track"><a class="arena-mini-tab <?= $tab === 'checkin' ? 'is-active' : '' ?>" href="<?= sf_h(url('workouts')) ?>" <?= $tab === 'checkin' ? 'aria-current="page"' : '' ?>><?= $arenaIcon('plus') ?><span>Check-in / out</span></a><a class="arena-mini-tab <?= $tab === 'history' ? 'is-active' : '' ?>" href="<?= sf_h(url('workouts/history')) ?>" <?= $tab === 'history' ? 'aria-current="page"' : '' ?>><?= $arenaIcon('history') ?><span>Lịch sử</span></a><a class="arena-mini-tab" href="<?= sf_h(url('profile?section=schedule')) ?>"><?= $arenaIcon('calendar') ?><span>Lịch tập</span></a></div></nav>
        <?php endif; ?>
        <?php if ($tab === 'profile'): ?>
            <?php if ($profileSection === 'overview'): ?>
                <div class="personal-intro"><div class="eyebrow">STREAKFIT</div><h1>C&#225; nh&#226;n</h1><p class="muted">Qu&#7843;n l&#253; h&#7891; s&#417; v&#224; c&#224;i &#273;&#7863;t d&#7877; h&#417;n.</p></div>
                <?php if ($canAdminDashboard): ?><a class="panel" style="display:flex;justify-content:space-between;align-items:center;gap:12px" href="<?= sf_h(url('admin')) ?>"><span><strong>Admin Center</strong><small class="muted" style="display:block">Mở bảng điều khiển quản trị cộng đồng</small></span><span>↗</span></a><?php endif; ?>
            <?php else: ?>
                <div class="profile-subhead"><a href="<?= sf_h(url('profile')) ?>">&lsaquo; <span>C&#225; nh&#226;n</span></a><h1><?= sf_h(html_entity_decode($profileSectionLabels[$profileSection] ?? 'C&#225; nh&#226;n', ENT_QUOTES, 'UTF-8')) ?></h1></div>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($flash): ?><div class="alert <?= sf_h($flash['type']) ?>" <?= $flash['type'] !== 'error' && str_contains((string)$flash['message'], 'Buổi tập hợp lệ đã được đăng') ? 'data-sf-celebrate' : '' ?>><?= sf_h($flash['message']) ?></div><?php endif; ?>

        <?php if ($tab === 'feed'): ?>
            <?php require APP_ROOT . '/app/Views/components/arena-command.php'; ?>
            <div class="sf33-quick-actions" data-arena-panel="overview"><a data-sf33-view="community" href="<?= sf_h(url('dashboard?view=community')) ?>"><?= $arenaIcon('people') ?><span><strong>Cộng đồng</strong><small><?= count($feed) ?> bài đăng mới nhất</small></span><b>↗</b></a><a href="<?= sf_h(url('profile?section=schedule')) ?>"><?= $arenaIcon('calendar') ?><span><strong>Đăng ký lịch</strong><small>Chọn 7 ngày và giờ tập</small></span><b>↗</b></a><a href="<?= sf_h(url('leaderboard?view=rank')) ?>"><?= $arenaIcon('rank') ?><span><strong>Hành trình rank</strong><small>Khám phá cấp bậc</small></span><b>↗</b></a></div>
            <?php $energyPanel = 'overview'; require APP_ROOT . '/app/Views/components/arena-rank-energy.php'; ?>
            <section class="rank-cinema rank-cinema-home" data-arena-panel="overview" data-rank-cinema data-rank-name="<?= sf_h($rank['name']) ?>" data-rank-image="<?= sf_h(asset('ranks/' . $rank['slug'] . '.svg')) ?>" data-rank-min="<?= (int)$user['total_points'] ?>" data-rank-slug="<?= sf_h($rank['slug']) ?>" aria-label="Huy hiệu rank 3D của bạn">
                <div class="rank-cinema-grid" aria-hidden="true"></div>
                <div class="rank-cinema-copy"><span class="rank-cinema-overline">STREAKFIT <i></i> ĐƯỜNG ĐẾN VINH QUANG</span><h2>GIỮ NHỊP.<br><em>VƯỢT GIỚI HẠN.</em></h2><p>Mỗi buổi tập đưa bạn tiến gần hơn đến rank tiếp theo.</p></div>
                <div class="rank-cinema-visual" data-rank-surface role="group" tabindex="0" aria-label="Huy hiệu 3D, kéo để xoay hoặc dùng phím mũi tên"><canvas class="rank-cinema-canvas" data-rank-canvas aria-hidden="true"></canvas><?= sf_rank_emblem($rank, 'rank-emblem rank-cinema-fallback') ?><span class="rank-cinema-halo" aria-hidden="true"></span></div>
                <div class="rank-cinema-info"><span>RANK HIỆN TẠI</span><strong data-rank-title><?= sf_h($rank['name']) ?></strong><small data-rank-points><?= number_format((int)$user['total_points'], 0, ',', '.') ?> điểm tích lũy</small></div>
                <div class="rank-cinema-instruction"><span>↔ KÉO ĐỂ XOAY</span><a href="<?= sf_h(url('leaderboard')) ?>">KHÁM PHÁ 8 RANK ↗</a></div>
            </section>

            <?php require APP_ROOT . '/app/Views/components/arena-community-hub.php'; ?>
            <section class="activity-strip" data-arena-panel="community" data-activity-strip>
                <div class="activity-strip-head"><strong>Hoạt động thành viên</strong><a href="<?= sf_h(url('leaderboard?view=board')) ?>">Bảng vàng &rsaquo;</a></div>
                <?php if ($activityMembers): ?><div class="activity-people"><?php foreach ($activityMembers as $member): $memberWorking = $member['status'] === 'in_progress'; ?><div class="activity-person" data-activity-kind="<?= $memberWorking ? 'working' : 'completed' ?>"><span class="activity-avatar-wrap <?= $memberWorking ? 'is-working' : 'is-completed' ?>"><span class="avatar activity-avatar"><?= sf_h(mb_substr($member['display_name'], 0, 1, 'UTF-8')) ?></span></span><strong><?= sf_h($member['display_name']) ?></strong><small><?= $memberWorking ? '<i></i> Đang tập' : '&#10003; Đã xong' ?> · <?= (int)$member['current_streak'] ?> tuần</small></div><?php endforeach; ?></div><?php endif; ?>
                <p class="activity-empty" data-activity-none <?= $activityMembers ? 'hidden' : '' ?>>Chưa có hoạt động mới từ thành viên khác.</p>
                <p class="activity-filter-empty" data-activity-empty hidden>Hiện chưa có thành viên nào đang tập.</p>
            </section>
            <div class="section-heading feed-heading" data-arena-panel="community"><div><h2><span class="community-icon">♟</span> Bảng tin cộng đồng</h2><p class="muted">Chia sẻ tiến bộ, cổ vũ nhau giữ nhịp.</p></div></div>
            <nav class="feed-filters" data-arena-panel="community" aria-label="Lọc hoạt động bảng tin" role="group"><button class="active" type="button" data-feed-filter="all" aria-pressed="true">▦ &nbsp; Tất cả</button><button type="button" data-feed-filter="working" aria-pressed="false">⚡ &nbsp; Đang tập <b><?= $activeTrainingCount ?></b></button><button type="button" data-feed-filter="completed" aria-pressed="false">✓ &nbsp; Hoàn thành</button></nav>
            <?php if (!$feed): ?><section class="empty-state feed-empty-state" data-arena-panel="community" data-feed-empty><span>🏋️</span><h3>Bảng tin đang chờ buổi tập đầu tiên</h3><p>Khi bạn hoàn thành check-in và check-out, thẻ tổng kết sẽ xuất hiện ở đây.</p><a class="btn primary" href="<?= sf_h(url('workouts')) ?>">Bắt đầu tập</a></section><?php endif; ?>
            <?php foreach ($feed as $post): $postRank = sf_rank((int)$post['total_points']); ?>
                <article class="post-card" id="workout-<?= (int)$post['workout_id'] ?>" data-arena-panel="community" data-feed-post data-feed-kind="completed" data-sf33-workout="<?= (int)$post['workout_id'] ?>" data-sf33-reaction-api="<?= sf_h(url('api/action')) ?>">
                    <div class="post-author"><div class="avatar"><?= sf_h(mb_substr($post['display_name'], 0, 1, 'UTF-8')) ?></div><div class="post-author-meta"><strong><?= sf_h($post['display_name']) ?> <span class="rank-mini rank-with-emblem"><?= sf_rank_emblem($postRank, 'rank-emblem rank-emblem-small') ?> <?= sf_h($postRank['name']) ?></span></strong><small><?= sf_h(sf_format_time($post['checked_out_at'], 'd/m · H:i')) ?></small></div><span class="post-duration">🔥 <?= (int)round((int)$post['duration_seconds'] / 60) ?> phút</span></div>
                    <div class="post-complete-label"><span>✓</span> Đã hoàn thành buổi tập <b>🔥 <?= (int)$post['current_streak'] ?> tuần streak</b></div>
                    <div class="workout-photos"><img src="?media=<?= (int)$post['workout_id'] ?>&kind=checkin" alt="Ảnh check-in" loading="lazy"><img src="?media=<?= (int)$post['workout_id'] ?>&kind=checkout" alt="Ảnh check-out" loading="lazy"></div>
                    <?php if ($post['caption']): ?><p class="post-caption"><?= nl2br(sf_h($post['caption'])) ?></p><?php endif; ?>
                    <div class="post-actions"><div class="reaction-buttons"><?php foreach (['heart' => '❤️', 'muscle' => '💪', 'fire' => '🔥', 'sad' => '🥹', 'haha' => '😂'] as $reaction => $emoji): ?><form method="post"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="react"><input type="hidden" name="workout_id" value="<?= (int)$post['workout_id'] ?>"><input type="hidden" name="reaction" value="<?= sf_h($reaction) ?>"><button class="reaction <?= $post['my_reaction'] === $reaction ? 'selected' : '' ?>" title="Thả cảm xúc" aria-pressed="<?= $post['my_reaction'] === $reaction ? 'true' : 'false' ?>"><?= $emoji ?></button></form><?php endforeach; ?></div><span><b data-sf33-reaction-count><?= (int)$post['reaction_count'] ?></b> cảm xúc · <?= (int)$post['comment_count'] ?> bình luận</span></div><p class="sf33-post-status" data-sf33-post-status role="status" hidden></p>
                    <div class="comment-list"><?php foreach ($post['comments'] as $comment): ?><p><b><?= sf_h($comment['display_name']) ?></b> <?= sf_h($comment['comment_text']) ?></p><?php endforeach; ?></div>
                    <form method="post" class="comment-form"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="comment"><input type="hidden" name="workout_id" value="<?= (int)$post['workout_id'] ?>"><input name="comment" maxlength="500" placeholder="Viết lời động viên…" required><button aria-label="Gửi bình luận">➤</button></form>
                </article>
            <?php endforeach; ?>
            <?php if ($plansCurrent): ?><section class="panel today-panel" data-arena-panel="overview"><div class="section-heading"><div><h2>Lịch dự kiến tuần này</h2><p class="muted">Lịch giúp bạn chủ động, không ảnh hưởng điểm streak.</p></div><a class="text-link" href="<?= sf_h(url('profile?section=schedule')) ?>">Chỉnh sửa</a></div><div class="plan-list"><?php foreach ($plansCurrent as $plan): ?><div class="plan-row"><span class="plan-day"><?= sf_h(date('D d/m', strtotime($plan['planned_date']))) ?></span><strong><?= sf_h(substr($plan['planned_time'], 0, 5)) ?></strong><span><?= sf_h($plan['note'] ?: 'Buổi tập dự kiến') ?></span></div><?php endforeach; ?></div></section><?php endif; ?>

        <?php elseif ($tab === 'checkin'): ?>
            <div class="page-intro"><div class="eyebrow">GHI NHẬN BUỔI TẬP</div><h1><?= $activeWorkout ? 'Bạn đang tập luyện 🔥' : 'Đóng dấu kỷ luật 📸' ?></h1><p class="muted"><?= $activeWorkout ? 'Chụp ảnh check-out khi đã tập tối thiểu ' . (int)ceil($settings['minimum_workout_seconds'] / 60) . ' phút.' : 'Chụp trực tiếp bằng camera để bắt đầu. Không nhận ảnh từ thư viện.' ?></p></div>
            <section class="sf-energy-stage" data-sf-energy aria-label="Mô hình năng lượng tập luyện 3D">
                <div class="sf-energy-copy"><span class="sf-energy-overline">STREAKFIT / TRAINING MODE</span><h2><?= $activeWorkout ? 'GIỮ NHỊP.<br><em>VỀ ĐÍCH.</em>' : 'BẬT NĂNG LƯỢNG.<br><em>VÀO NHỊP.</em>' ?></h2><p><?= $activeWorkout ? 'Mỗi phút bạn tập là một bước tiến. Hoàn thành bằng ảnh check-out.' : 'Chụp ảnh check-in để ghi nhận thời điểm bắt đầu trên máy chủ.' ?></p><span class="sf-energy-meta">◈ &nbsp; KÉO MÔ HÌNH ĐỂ XOAY</span></div>
                <div class="sf-energy-visual" data-sf-energy-surface role="group" tabindex="0" aria-label="Mô hình năng lượng 3D; kéo để xoay, dùng phím mũi tên khi có bàn phím"><canvas aria-hidden="true"></canvas><span class="sf-energy-fallback" aria-hidden="true"></span></div>
            </section>
            <section class="checkin-card" data-checkin-start="<?= $activeWorkout ? (int)strtotime($activeWorkout['checked_in_at'] . ' UTC') : '' ?>" data-minimum-seconds="<?= (int)$settings['minimum_workout_seconds'] ?>">
                <div class="camera-frame"><video id="cameraVideo" playsinline muted autoplay></video><div class="camera-placeholder" id="cameraPlaceholder"><span>◎</span><strong>Camera StreakFit</strong><small>Nhấn mở camera để bắt đầu</small></div><div class="camera-watermark" id="cameraWatermark">STREAKFIT · <?= date('d/m/Y H:i') ?></div></div>
                <div class="camera-controls"><button class="btn secondary" type="button" data-camera-start>Mở camera</button><button class="btn secondary" type="button" data-camera-flip disabled>Đổi camera</button></div>
                <div class="timer-display" id="workoutTimer"><?= $activeWorkout ? '00:00:00' : '—' ?></div>
                <p class="timer-hint" id="timerHint"><?= $activeWorkout ? 'Đang tính thời gian từ máy chủ…' : 'Mỗi ngày tối đa một buổi hợp lệ · Tối thiểu ' . (int)ceil($settings['minimum_workout_seconds'] / 60) . ' phút' ?></p>
                <?php if (!$activeWorkout && $weeklyCount >= 7): ?><div class="alert info">Bạn đã có 7 buổi tập hợp lệ trong tuần này.</div><?php endif; ?>
                <form method="post" class="capture-form" data-capture-form>
                    <input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="<?= $activeWorkout ? 'finish_workout' : 'start_workout' ?>"><input type="hidden" name="image_data" value="">
                    <?php if ($activeWorkout): ?><input type="hidden" name="workout_id" value="<?= (int)$activeWorkout['id'] ?>"><?php endif; ?>
                    <label class="caption-label">Lời nhắn cho bảng tin <textarea name="caption" maxlength="500" placeholder="Hôm nay mình đã hoàn thành…"></textarea></label>
                    <button class="btn primary full" type="submit" data-capture-submit disabled><?= $activeWorkout ? 'Chụp ảnh & check-out' : 'Chụp ảnh & check-in' ?></button>
                </form>
                <?php if ($activeWorkout): ?><form method="post" class="discard-form"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="discard_workout"><input type="hidden" name="workout_id" value="<?= (int)$activeWorkout['id'] ?>"><button class="text-link danger-link">Hủy buổi tập đang mở</button></form><?php endif; ?>
            </section>
            <canvas id="captureCanvas" hidden></canvas>
<?php if ($activeWorkout): ?><a class="btn secondary full" href="<?= e(url('workouts/route?id='.(int)$activeWorkout['id'])) ?>">📍 Theo dõi đường tập GPS</a><?php endif; ?>
            <p class="privacy-note">Ảnh được lưu trong website để tạo thẻ tổng kết. Thời gian check-in/check-out được ghi bởi máy chủ.</p>

        <?php elseif ($tab === 'leaderboard'): ?>
            <div class="page-intro"><div class="eyebrow">CỘNG ĐỒNG STREAKFIT</div><h1 data-arena-view-title><?= sf_h(['rank' => 'Đại sảnh Rank', 'board' => 'Bảng vàng', 'court' => 'Tòa án kỷ luật'][$arenaView] ?? 'Bảng vàng') ?></h1><p class="muted">Cùng giữ lời hứa, cùng công nhận nỗ lực.</p></div>
            <section class="rank-cinema" data-arena-panel="rank" data-rank-cinema data-rank-slug="<?= sf_h($rank['slug']) ?>" data-rank-name="<?= sf_h($rank['name']) ?>" data-rank-min="<?= (int)$rank['min'] ?>" data-rank-image="<?= sf_h(asset('ranks/' . $rank['slug'] . '.svg')) ?>" aria-label="Trình diễn huy hiệu rank 3D">
                <div class="rank-cinema-grid" aria-hidden="true"></div>
                <div class="rank-cinema-copy"><span class="rank-cinema-overline">STREAKFIT <i></i> HÀNH TRÌNH RANK</span><h2>MỖI BUỔI TẬP<br><em>MỘT DẤU ẤN.</em></h2><p>Tích lũy nỗ lực, mở khóa những cột mốc mới. Chọn một huy hiệu bên dưới để khám phá.</p></div>
                <div class="rank-cinema-visual" data-rank-surface role="group" tabindex="0" aria-label="Huy hiệu 3D, kéo để xoay hoặc dùng phím mũi tên">
                    <canvas class="rank-cinema-canvas" data-rank-canvas aria-hidden="true"></canvas>
                    <?= sf_rank_emblem($rank, 'rank-emblem rank-cinema-fallback') ?>
                    <span class="rank-cinema-halo" aria-hidden="true"></span>
                </div>
                <div class="rank-cinema-info" aria-live="polite"><span>RANK ĐANG XEM <i>—</i> <?= sf_h($rank['name']) ?> LÀ HẠNG CỦA BẠN</span><strong data-rank-title><?= sf_h($rank['name']) ?></strong><small data-rank-points><?= number_format((int)$rank['min'], 0, ',', '.') ?>+ điểm tích lũy</small></div>
                <div class="rank-cinema-instruction"><span>↔ KÉO ĐỂ XOAY</span><div class="arena-rank-controls"><button type="button" data-rank-step="-1" aria-label="Rank trước">‹</button><button type="button" data-rank-step="1" aria-label="Rank tiếp theo">›</button><button type="button" data-rank-reset>Góc gốc</button><button type="button" data-rank-zoom="-1" aria-label="Thu nhỏ">−</button><button type="button" data-rank-zoom="1" aria-label="Phóng to">+</button></div></div>
            </section>
            <?php $energyPanel = 'rank'; require APP_ROOT . '/app/Views/components/arena-rank-energy.php'; ?>
            <section class="panel rank-showcase" data-arena-panel="rank" aria-labelledby="rank-showcase-title">
                <div class="section-heading"><div><h2 id="rank-showcase-title">Hệ thống rank</h2><p class="muted">Rank của bạn được tính từ tổng điểm tích lũy.</p></div><span class="rank-chip rank-with-emblem"><?= sf_rank_emblem($rank, 'rank-emblem rank-emblem-small') ?> <?= sf_h($rank['name']) ?></span></div>

                <div class="rank-gallery"><?php foreach (sf_rank_catalog() as $tier): $selected = $tier['slug'] === $rank['slug']; ?><button type="button" class="rank-tier <?= $selected ? 'is-current is-preview' : '' ?>" data-rank-select data-rank-name="<?= sf_h($tier['name']) ?>" data-rank-image="<?= sf_h(asset('ranks/' . $tier['slug'] . '.svg')) ?>" data-rank-min="<?= (int)$tier['min'] ?>" data-rank-slug="<?= sf_h($tier['slug']) ?>" aria-pressed="<?= $selected ? 'true' : 'false' ?>" aria-label="Xem huy hiệu <?= sf_h($tier['name']) ?>, từ <?= number_format((int)$tier['min'], 0, ',', '.') ?> điểm"><?= sf_rank_emblem($tier, 'rank-emblem rank-emblem-gallery') ?><strong><?= sf_h($tier['name']) ?></strong><small><?= number_format($tier['min'], 0, ',', '.') ?>+ điểm</small><span class="arena-tier-status"><?= $selected ? 'HIỆN TẠI' : ($arenaPoints >= $tier['min'] ? 'ĐÃ MỞ' : 'CHƯA MỞ') ?></span></button><?php endforeach; ?></div>
                <?php if ($rank['next'] !== null): ?><p class="rank-next">Còn <?= number_format(max(0, $rank['next'] - (int)$user['total_points']), 0, ',', '.') ?> điểm để lên rank kế tiếp.</p><?php else: ?><p class="rank-next">Bạn đã đạt rank cao nhất.</p><?php endif; ?>
            </section>
            <div class="sub-tabs" data-arena-panel="board"><a class="<?= $period === 'week' ? 'active' : '' ?>" href="<?= sf_h(url('leaderboard?view=board&period=week')) ?>">Tuần</a><a class="<?= $period === 'month' ? 'active' : '' ?>" href="<?= sf_h(url('leaderboard?view=board&period=month')) ?>">Tháng</a><a class="<?= $period === 'all' ? 'active' : '' ?>" href="<?= sf_h(url('leaderboard?view=board&period=all')) ?>">Toàn thời gian</a></div>
            <section class="panel leaderboard-panel" data-arena-panel="board"><div class="section-heading"><div><h2><?= $period === 'all' ? 'Thành tích tích lũy' : ($period === 'month' ? 'Tổng điểm tháng này' : 'Điểm tuần tạm tính') ?></h2><p class="muted">Thứ hạng tuần sẽ chốt vào cuối Chủ Nhật.</p></div><span class="rank-chip"><?= count($leaderboard) ?> thành viên</span></div>
                <?php if ($leaderboard): ?><div class="arena-podium" aria-label="Ba vị trí dẫn đầu"><?php foreach (array_slice($leaderboard, 0, 3) as $arenaPlace => $arenaLeader): $arenaLeaderRank = sf_rank((int)$arenaLeader['total_points']); ?><div class="arena-podium-slot place-<?= $arenaPlace + 1 ?>"><span class="arena-podium-place">0<?= $arenaPlace + 1 ?></span><?= sf_rank_emblem($arenaLeaderRank, 'arena-podium-emblem') ?><strong><?= sf_h($arenaLeader['display_name']) ?></strong><small><?= sf_h($arenaLeaderRank['name']) ?></small><b><?= number_format((int)$arenaLeader['points'], 0, ',', '.') ?> điểm</b></div><?php endforeach; ?></div><?php endif; ?>
                <div class="leaderboard-list"><?php foreach ($leaderboard as $index => $row): $memberRank = sf_rank((int)$row['total_points']); ?><div class="leader-row <?= (int)$row['id'] === (int)$user['id'] ? 'mine' : '' ?>"><span class="leader-place"><?= $index < 3 ? ['🥇','🥈','🥉'][$index] : (string)($index + 1) ?></span><?= sf_rank_emblem($memberRank, 'rank-emblem rank-emblem-list') ?><span class="leader-name"><b><?= sf_h($row['display_name']) ?></b><small><?= sf_h($memberRank['name']) ?> · <?= (int)$row['streak'] ?> tuần streak · <?= (int)$row['sessions'] ?> buổi</small></span><strong class="leader-score"><?= number_format((int)$row['points']) ?><small>điểm <?= $period === 'week' ? 'tuần' : ($period === 'month' ? 'tháng' : 'tích lũy') ?></small></strong></div><?php endforeach; ?></div>
            </section>
            <section class="panel court-panel" data-arena-panel="court"><div class="section-heading"><div><h2>Tòa án cộng đồng</h2><p class="muted">Kháng cáo hiển thị ẩn danh.</p></div><span>⚖️</span></div>
                <?php foreach ($appeals as $appeal): ?><article class="appeal-card"><div class="appeal-state">CẦN BỎ PHIẾU · <?= (int)$appeal['yes_votes'] ?> đồng ý / <?= (int)$appeal['no_votes'] ?> không đồng ý</div><p><?= sf_h($appeal['reason']) ?></p><?php if ($appeal['proof_path']): ?><a href="?appeal_media=<?= (int)$appeal['id'] ?>" target="_blank" rel="noopener"><img class="appeal-proof" src="?appeal_media=<?= (int)$appeal['id'] ?>" alt="Ảnh minh chứng kháng cáo"></a><?php else: ?><small>Không có ảnh minh chứng</small><?php endif; ?><?php if ((int)$appeal['user_id'] !== (int)$user['id'] && !$appeal['my_vote']): ?><form method="post" class="vote-row"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="vote_appeal"><input type="hidden" name="appeal_id" value="<?= (int)$appeal['id'] ?>"><button class="btn vote-yes" name="vote" value="yes">Đồng ý giữ chuỗi</button><button class="btn vote-no" name="vote" value="no">Không đồng ý</button></form><?php elseif ($appeal['my_vote']): ?><small>Bạn đã bỏ phiếu: <?= $appeal['my_vote'] === 'yes' ? 'đồng ý' : 'không đồng ý' ?></small><?php endif; ?></article><?php endforeach; ?>
                <?php if (!$appeals): ?><p class="empty-inline">Chưa có kháng cáo nào cần biểu quyết.</p><?php endif; ?>
                <details class="appeal-details"><summary>Gửi kháng cáo nghỉ tập</summary><form method="post" enctype="multipart/form-data" class="stack-form"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="appeal"><label>Lý do<textarea name="reason" minlength="10" maxlength="1000" required placeholder="Mô tả ngắn lý do bạn không thể hoàn thành mục tiêu…"></textarea></label><label>Ảnh minh chứng<input type="file" name="proof" accept="image/jpeg,image/png" required></label><button class="btn secondary full">Gửi kháng cáo (tối đa 2 lần/tháng)</button></form></details>
            </section>

        <?php elseif ($tab === 'notifications'): ?>
            <div class="page-intro"><div class="eyebrow">CẬP NHẬT CỘNG ĐỒNG</div><h1>Thông báo</h1><p class="muted">Những lời nhắc và thành tích mới.</p></div>
            <?php if ($notifications): ?><form method="post" class="mark-read-form"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="mark_read"><button class="text-link">Đánh dấu tất cả đã đọc</button></form><?php endif; ?>
            <section class="notification-list"><?php foreach ($notifications as $notice): ?><article class="notification-card <?= !(int)$notice['is_read'] ? 'unread' : '' ?>"><span class="notification-icon"><?= $notice['type'] === 'workout' ? '🔥' : ($notice['type'] === 'appeal' ? '⚖️' : '✦') ?></span><div><strong><?= sf_h($notice['title']) ?></strong><p><?= sf_h($notice['body']) ?></p><small><?= sf_h(sf_format_time($notice['created_at'], 'd/m · H:i')) ?></small></div></article><?php endforeach; ?><?php if (!$notifications): ?><p class="empty-state">Bạn chưa có thông báo mới.</p><?php endif; ?></section>

        <?php elseif ($tab === 'history'): ?>
            <?php require APP_ROOT . '/app/Views/workout/history.php'; ?>

        <?php else: ?>
            <div class="page-intro"><div class="eyebrow">HỒ SƠ SỨC KHỎE</div><h1>Hành trình của bạn</h1><p class="muted">Theo dõi chỉ số và mục tiêu của mình.</p></div>
            <section class="profile-hero panel" id="profile"><div class="avatar profile-avatar"><?= sf_h(mb_substr($user['display_name'], 0, 1, 'UTF-8')) ?></div><div><h2><?= sf_h($user['display_name']) ?></h2><p class="muted rank-profile-line"><?= sf_rank_emblem($rank, 'rank-emblem rank-emblem-small') ?> <?= sf_h($rank['name']) ?> · <?= number_format((int)$user['total_points']) ?> điểm</p></div><div class="profile-highlights"><div><b><?= (int)$user['current_streak'] ?></b><small>streak hiện tại</small></div><div><b><?= (int)$user['best_streak'] ?></b><small>streak dài nhất</small></div><div><b><?= (int)$user['total_sessions'] ?></b><small>buổi tập</small></div></div></section>
            <?php if ($profileSection === 'overview'): ?>
            <section class="profile-hub-grid" aria-label="C&#224;i &#273;&#7863;t c&#225; nh&#226;n">
                <a class="profile-hub-card" href="<?= sf_h(url('profile?section=profile')) ?>"><span class="hub-icon"><?= $arenaIcon('user') ?></span><span><strong>H&#7891; s&#417;</strong><small>Th&#244;ng tin c&#225; nh&#226;n, t&#234;n hi&#7875;n th&#7883;</small></span><i>&rsaquo;</i></a>
                <a class="profile-hub-card" href="<?= sf_h(url('profile?section=health')) ?>"><span class="hub-icon"><?= $arenaIcon('heart') ?></span><span><strong>S&#7913;c kh&#7887;e</strong><small>Ch&#7881; s&#7889; c&#417; th&#7875;, BMI, BMR</small></span><i>&rsaquo;</i></a>
                <a class="profile-hub-card" href="<?= sf_h(url('profile?section=schedule')) ?>"><span class="hub-icon"><?= $arenaIcon('calendar') ?></span><span><strong>M&#7909;c ti&#234;u &amp; L&#7883;ch t&#7853;p</strong><small>S&#7889; bu&#7893;i m&#7895;i tu&#7847;n, l&#7883;ch tu&#7847;n t&#7899;i</small></span><i>&rsaquo;</i></a>
                <a class="profile-hub-card" href="<?= sf_h(url('profile?section=fund')) ?>"><span class="hub-icon"><?= $arenaIcon('wallet') ?></span><span><strong>Qu&#7921; k&#7927; lu&#7853;t</strong><small>Ph&#237; tham gia v&#224; s&#7893; qu&#7921;</small></span><i>&rsaquo;</i></a>
                <a class="profile-hub-card" href="<?= sf_h(url('profile?section=security')) ?>"><span class="hub-icon"><?= $arenaIcon('lock') ?></span><span><strong>B&#7843;o m&#7853;t</strong><small>&#272;&#7893;i m&#7853;t kh&#7849;u</small></span><i>&rsaquo;</i></a>
                <a class="profile-hub-card" href="<?= sf_h(url('notifications')) ?>"><span class="hub-icon"><?= $arenaIcon('bell') ?></span><span><strong>Th&#244;ng b&#225;o</strong><small>L&#7901;i nh&#7855;c v&#224; c&#7853;p nh&#7853;t</small></span><i>&rsaquo;</i></a>
                <a class="profile-hub-card" href="<?= sf_h(url('profile?section=other')) ?>"><span class="hub-icon"><?= $arenaIcon('spark') ?></span><span><strong>C&#224;i &#273;&#7863;t kh&#225;c</strong><small>Tr&#7907; l&#253; s&#7913;c kh&#7887;e</small></span><i>&rsaquo;</i></a>
            </section>
            <?php elseif ($profileSection === 'profile'): ?>
            <section class="panel profile-name-panel"><div class="section-heading"><div><h2>Th&#244;ng tin c&#417; b&#7843;n</h2><p class="muted">C&#7853;p nh&#7853;t t&#234;n hi&#7875;n th&#7883; tr&#234;n StreakFit.</p></div></div><form method="post" class="stack-form"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="update_display_name"><label>T&#234;n hi&#7875;n th&#7883;<input name="display_name" minlength="2" maxlength="80" value="<?= sf_h($user['display_name']) ?>" required></label><button class="btn primary full">L&#432;u th&#244;ng tin</button></form></section>
            <?php elseif ($profileSection === 'security'): ?>
            <section class="panel" id="account"><div class="section-heading"><div><h2>B&#7843;o m&#7853;t t&#224;i kho&#7843;n</h2><p class="muted">Thay &#273;&#7893;i m&#7853;t kh&#7849;u c&#7911;a b&#7841;n.</p></div></div><form method="post" class="stack-form"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="change_password"><label>M&#7853;t kh&#7849;u hi&#7879;n t&#7841;i<input type="password" name="current_password" minlength="8" required autocomplete="current-password"></label><label>M&#7853;t kh&#7849;u m&#7899;i<input type="password" name="new_password" minlength="8" maxlength="72" required autocomplete="new-password"></label><button class="btn primary full">C&#7853;p nh&#7853;t m&#7853;t kh&#7849;u</button></form></section>
            <?php endif; ?>
            <section class="panel" id="health"><div class="section-heading"><div><h2>Chỉ số sức khỏe</h2><p class="muted">Cập nhật chiều cao, cân nặng và tuổi.</p></div></div><form method="post" class="health-form"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="update_health"><input type="hidden" name="display_name" value="<?= sf_h($user['display_name']) ?>"><input type="hidden" name="display_name" value="<?= sf_h($user['display_name']) ?>"><label>Cân nặng (kg)<input type="number" min="25" max="250" step="0.1" name="weight_kg" value="<?= sf_h($user['weight_kg'] ?? '') ?>" required></label><label>Chiều cao (cm)<input type="number" min="100" max="230" step="0.1" name="height_cm" value="<?= sf_h($user['height_cm'] ?? '') ?>" required></label><label>Tuổi<input type="number" min="16" max="100" name="age" value="<?= sf_h($user['age'] ?? '') ?>" required></label><label>Giới tính<select name="gender"><option value="unspecified" <?= $user['gender'] === 'unspecified' ? 'selected' : '' ?>>Chưa chọn</option><option value="female" <?= $user['gender'] === 'female' ? 'selected' : '' ?>>Nữ</option><option value="male" <?= $user['gender'] === 'male' ? 'selected' : '' ?>>Nam</option></select></label><button class="btn primary full">Lưu chỉ số sức khỏe</button></form><div class="health-results"><div><small>BMI tham khảo</small><strong><?= $bmi !== null ? number_format($bmi, 1) : '—' ?></strong></div><div><small>BMR tham khảo</small><strong><?= $bmr !== null ? number_format($bmr) . ' kcal' : '—' ?></strong></div></div><?php if ($healthHistory): ?><div class="weight-history"><h3>Lịch sử cân nặng</h3><?php foreach ($healthHistory as $metric): ?><div><span><?= sf_h(sf_format_time($metric['recorded_at'], 'd/m/Y')) ?></span><b><?= number_format((float)$metric['weight_kg'], 1) ?> kg</b></div><?php endforeach; ?></div><?php endif; ?></section>
            <?php if ($profileSection === 'schedule') require APP_ROOT . '/app/Views/components/arena-schedule.php'; ?>
            <a class="btn primary full fund-payment-link" href="<?= e(url('fund/payments')) ?>">Nộp quỹ qua QR / Lịch sử chuyển khoản</a><section class="panel fund-panel"><div class="section-heading"><div><h2>Quỹ kỷ luật tháng</h2><p class="muted">Sổ quỹ minh bạch · chuyển khoản ngân hàng qua QR.</p></div><span>💰</span></div><div class="fund-summary"><div><small>Tham gia</small><b><?= (int)$fundSummary['participants'] ?></b></div><div><small>Đã xác nhận đóng</small><b><?= (int)$fundSummary['paid_count'] ?></b></div><div><small>Sổ quỹ</small><b><?= number_format((int)$fundSummary['recorded_total']) ?>đ</b></div></div><p class="fund-note">Mức đóng tự nguyện: <?= number_format((int)$fund['contribution_amount']) ?>đ/tháng. Thanh toán trực tuyến được xác nhận sau khi đối soát với ngân hàng.</p><?php if (!$fundMember || !(int)$fundMember['opted_in']): ?><form method="post"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="fund_join"><button class="btn secondary full">Tự nguyện tham gia quỹ tháng này</button></form><?php else: ?><div class="fund-member-status">Bạn đã tham gia · <?= $fundMember['contribution_status'] === 'paid' ? 'Đã xác nhận đóng' : 'Chưa xác nhận đóng' ?></div><?php if ($fundMember['contribution_status'] === 'unpaid'): ?><form method="post" class="fund-inline-form"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="fund_leave"><button class="text-link">Rời quỹ tháng này</button></form><?php endif; ?><?php endif; ?><div class="fund-proposal"><h3>Đề xuất & biểu quyết mức phạt</h3><?php foreach ($fundProposals as $proposal): ?><div class="fund-proposal-row"><span><?= sf_h($proposal['display_name']) ?></span><b><?= number_format((int)$proposal['amount']) ?>đ</b></div><?php endforeach; ?><?php if ($fund['status'] === 'open' && $fundMember && (int)$fundMember['opted_in']): ?><form method="post" class="fund-inline-form"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="fund_propose"><label>Mức phạt đề xuất<input name="amount" type="number" min="1000" max="1000000" step="1000" required placeholder="Ví dụ: 50.000"></label><button class="btn secondary">Lưu đề xuất</button></form><?php endif; ?><?php if ($fund['status'] === 'voting' && $fundMember && (int)$fundMember['opted_in'] && $fundProposals): ?><form method="post" class="fund-inline-form"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="fund_vote"><label>Bỏ phiếu<select name="amount"><?php $seenAmounts = []; foreach ($fundProposals as $proposal): if (isset($seenAmounts[$proposal['amount']])) continue; $seenAmounts[$proposal['amount']] = true; ?><option value="<?= (int)$proposal['amount'] ?>"><?= number_format((int)$proposal['amount']) ?>đ</option><?php endforeach; ?></select></label><button class="btn secondary">Gửi phiếu</button></form><?php endif; ?><?php if ($fundVotes): ?><div class="fund-votes"><?php foreach ($fundVotes as $vote): ?><span><?= number_format((int)$vote['amount']) ?>đ · <?= (int)$vote['votes'] ?> phiếu</span><?php endforeach; ?></div><?php endif; ?></div><?php if ($fund['status'] === 'closed'): ?><div class="final-penalty">Mức phạt tháng đã chốt: <b><?= number_format((int)$fund['final_penalty']) ?>đ</b></div><?php endif; ?><?php if ($canManageFund): ?><div class="admin-fund-controls"><?php foreach ($fundMembers as $member): ?><?php if ($member['contribution_status'] === 'unpaid'): ?><form method="post" class="fund-member-row"><span><?= sf_h($member['display_name']) ?></span><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="fund_mark_paid"><input type="hidden" name="member_id" value="<?= (int)$member['user_id'] ?>"><button class="text-link">Đánh dấu đã đóng</button></form><?php endif; ?><?php endforeach; ?><?php if ($fund['status'] === 'open' && $fundProposals): ?><form method="post"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="fund_start_vote"><button class="btn secondary full">Mở biểu quyết mức phạt</button></form><?php elseif ($fund['status'] === 'voting'): ?><form method="post"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="fund_close_vote"><button class="btn secondary full">Chốt mức phạt theo phiếu</button></form><?php endif; ?></div><?php endif; ?></section>
            <section class="panel assistant-panel"><div class="section-heading"><div><h2>Trợ lý sức khỏe ✨</h2><p class="muted">Gợi ý dựa trên tiến độ tập của bạn, không thay thế tư vấn y tế.</p></div></div><div class="assistant-answer" id="assistantAnswer" role="status" aria-live="polite">Hỏi về mục tiêu tuần này hoặc cách sắp xếp các buổi tập.</div><small class="muted" id="assistantMode"><?= (app_config()['gemini_api_key'] ?? '') !== '' ? 'Gemini được bật · Chỉ gửi câu hỏi và số liệu tiến độ tuần khi bạn nhấn Gửi.' : 'Chế độ gợi ý từ dữ liệu StreakFit.' ?></small><form class="assistant-form" id="assistantForm" data-endpoint="<?= sf_h(url('api/coach')) ?>"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input id="assistantQuestion" name="question" maxlength="300" minlength="3" required placeholder="Hỏi về mục tiêu và lịch tập…"><button class="btn primary" type="submit">Gửi</button></form><small class="muted">Tối đa 20 câu hỏi mỗi ngày cho mỗi phiên đăng nhập.</small></section>
        <?php endif; ?>
    </main>
    <nav class="bottom-nav arena-dock" aria-label="Điều hướng chính">
        <?php $arenaDock = ['overview'=>['Tổng quan','dashboard?view=overview','grid'], 'community'=>['Cộng đồng','dashboard?view=community','people'], 'checkin'=>['Check-in','workouts','plus'], 'leaderboard'=>['Bảng vàng','leaderboard?view=rank','trophy'], 'profile'=>['Cá nhân','profile','user']]; foreach ($arenaDock as $key => [$label,$path,$icon]): $dockActive = $tab === 'feed' ? $key === $arenaView : ($tab === $key || ($tab === 'history' && $key === 'checkin')); ?>
        <a class="nav-item <?= $dockActive ? 'active' : '' ?> <?= $key === 'checkin' ? 'nav-checkin' : '' ?>" href="<?= sf_h(url($path)) ?>" <?= in_array($key,['overview','community'],true) ? 'data-sf33-view="' . $key . '"' : '' ?> <?= $dockActive ? 'aria-current="page"' : '' ?>><span class="arena-dock-icon"><?= $arenaIcon($icon) ?></span><small><?= sf_h($label) ?></small></a>
        <?php endforeach; ?>
    </nav>
</div>
<script src="<?= sf_h(sf_asset_version('js/streakfit.js')) ?>" defer></script>
<script src="<?= sf_h(sf_asset_version('js/streakfit-future.js')) ?>" defer></script>
<script src="<?= sf_h(sf_asset_version('js/streakfit-motion.js')) ?>" defer></script>
<?php if ($tab === 'checkin'): ?><script src="<?= sf_h(sf_asset_version('js/streakfit-energy.js')) ?>" defer></script><?php endif; ?>
<script src="<?= sf_h(sf_asset_version('js/arena-3d.js')) ?>" defer></script>
<script src="<?= sf_h(sf_asset_version('js/arena-ui.js')) ?>" defer></script>
<?php if ($tab === 'profile' && $profileSection === 'schedule'): ?><script src="<?= sf_h(sf_asset_version('js/arena-v32.js')) ?>" defer></script><?php endif; ?>
<?php if ($tab === 'feed'): ?><script src="<?= sf_h(sf_asset_version('js/arena-v33.js')) ?>" defer></script><?php endif; ?>
<script src="<?= sf_h(sf_asset_version('js/arena-atmosphere.js')) ?>" defer></script>
</body>
</html>
