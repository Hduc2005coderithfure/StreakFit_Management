<?php
$flash = $_SESSION['sf_flash'] ?? null;
unset($_SESSION['sf_flash']);
require __DIR__ . '/partials/icons.php';
$adminNavigation = [
    'overview' => ['Tổng quan', $canAdminDashboard],
    'members' => ['Thành viên', $canViewMembers || $canAdjustPoints],
    'reviews' => ['Buổi tập & kiểm duyệt', $canViewWorkouts || $canViewAppeals || $canViewContentReports],
    'fund' => ['Quỹ kỷ luật', $canViewFund],
    'reports' => ['Báo cáo & thống kê', $canViewAnalytics || $canViewAudit || $canViewCron],
    'settings' => ['Cài đặt nhóm', $canViewSettings],
    'permissions' => ['Phân quyền Admin', $canManageAdminPermissions],
];
$adminNavigation = array_filter($adminNavigation, static fn(array $item): bool => (bool)$item[1]);
$pendingAdminCount = (int)$adminStats['pending_total'];
$mobileLabels = ['overview'=>'Tổng quan','members'=>'Thành viên','reviews'=>'Duyệt','fund'=>'Quỹ'];
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="theme-color" content="#0d0d13">
    <title>StreakFit · Admin Center</title>
    <link rel="stylesheet" href="<?= sf_h(asset('css/admin.css')) ?>?v=20261111-demo1">
</head>
<body class="admin-root" data-tab="admin">
<a class="sr-only skip-link" href="#adminMain">Đến nội dung chính</a>
<div class="admin-console">
    <aside class="admin-sidebar" id="adminSidebar" aria-label="Điều hướng quản trị">
        <a class="admin-brand" href="<?= sf_h(url('admin')) ?>">Streak<span>Fit</span><i>✦</i></a>
        <div><p class="admin-side-label">QUẢN TRỊ HỆ THỐNG</p>
            <nav class="admin-nav">
                <?php foreach ($adminNavigation as $page => [$label]): ?>
                    <button type="button" data-admin-nav="<?= sf_h($page) ?>" <?= $page === 'overview' ? 'class="active" aria-current="page"' : '' ?>><?= $adminIcon($page) ?><span><?= sf_h($label) ?></span></button>
                <?php endforeach; ?>
            </nav>
        </div>
        <div class="admin-sidebar-footer"><strong><?= $adminIcon('reviews') ?> Chế độ quản trị</strong><p>Quản lý cộng đồng bằng các quyền được cấp riêng.</p><a href="<?= sf_h(url('dashboard')) ?>">↗ Chuyển sang giao diện Member</a>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="logout"><button class="admin-logout" type="submit"><?= $adminIcon('logout') ?> Đăng xuất</button></form>
        </div>
    </aside>
    <button class="admin-scrim" type="button" data-admin-scrim aria-label="Đóng menu quản trị" hidden></button>
    <main class="admin-workspace" id="adminMain">
<div class="admin-header-actions" style="padding:16px">
<?php if ($permissionService->can($userId, 'groups.manage')): ?><a class="btn secondary" href="<?= e(url('admin/groups')) ?>">Nhóm thể thao</a><?php endif; ?>
<?php if ($canViewFund): ?><a class="btn secondary" href="<?= e(url('admin/payments')) ?>">Đối soát chuyển khoản QR</a><?php endif; ?>
</div>
        <header class="admin-workspace-header">
            <div><span class="admin-overline">STREAKFIT / ADMIN CENTER</span><h1 data-admin-title>Tổng quan cộng đồng</h1><p data-admin-description>Theo dõi và quản lý hoạt động của nhóm trong một nơi.</p></div>
            <div class="admin-header-actions">
                <span class="admin-role-chip"><?= $adminIcon('reviews') ?> Admin</span>
                <button class="admin-circle admin-mobile-trigger" type="button" data-admin-toggle aria-controls="adminSidebar" aria-expanded="false" aria-label="Mở menu quản trị"><?= $adminIcon('menu') ?></button>
                <?php if (isset($adminNavigation['reviews'])): ?><button class="admin-circle admin-notifications" type="button" data-admin-nav="reviews" aria-label="Xem yêu cầu cần xử lý"><?= $adminIcon('bell') ?><?php if ($pendingAdminCount): ?><b><?= $pendingAdminCount ?></b><?php endif; ?></button><?php endif; ?>
                <a class="admin-avatar" href="<?= sf_h(url('profile')) ?>" aria-label="Hồ sơ <?= sf_h($user['display_name']) ?>" title="<?= sf_h($user['display_name']) ?>"><?= sf_h(mb_substr($user['display_name'], 0, 1, 'UTF-8')) ?></a>
            </div>
        </header>
        <?php if ($flash): ?><div class="alert <?= sf_h($flash['type']) ?>" role="status"><?= sf_h($flash['message']) ?></div><?php endif; ?>
        <?php foreach (array_keys($adminNavigation) as $page): ?>
            <section class="admin-view" data-admin-view="<?= sf_h($page) ?>" <?= $page !== 'overview' ? 'hidden' : '' ?>><?php require __DIR__ . '/pages/' . $page . '.php'; ?></section>
        <?php endforeach; ?>
    </main>
</div>
<nav class="admin-mobilebar" aria-label="Điều hướng quản trị trên điện thoại">
    <?php foreach ($mobileLabels as $page => $label): if (!isset($adminNavigation[$page])) continue; ?><button type="button" data-admin-nav="<?= sf_h($page) ?>"><?= $adminIcon($page) ?><span><?= sf_h($label) ?></span></button><?php endforeach; ?>
    <button type="button" data-admin-toggle aria-controls="adminSidebar" aria-expanded="false"><?= $adminIcon('menu') ?><span>Thêm</span></button>
</nav>
<?php if ($canManageMembers): ?>
<dialog id="adminInvite" class="admin-dialog"><form method="dialog"><button class="admin-circle" aria-label="Đóng lời mời">×</button></form><h2>Mời thành viên</h2><p>Gửi liên kết này để bạn bè tạo tài khoản StreakFit.</p><label>Liên kết đăng ký<input id="adminInviteUrl" readonly value="<?= sf_h(rtrim($config['app_url'], '/') . '/register') ?>"></label><button type="button" class="btn primary" data-copy-invite>Sao chép liên kết</button><p role="status" data-invite-status></p></dialog>
<?php endif; ?>
<script src="<?= sf_h(asset('js/admin.js')) ?>?v=20261111-demo1" defer></script>
</body>
</html>
