
<header class="sf-header">
    <div class="sf-header-inner">

        <!-- Logo -->
        <a class="sf-brand" href="<?= e(url('dashboard')) ?>">
            <div class="sf-logo">
                Streak<span>Fit</span><b>✦</b>
            </div>
            <small>Hành trình kỷ luật của bạn</small>
        </a>

        <!-- Các thao tác -->
        <div class="sf-header-actions">

            <!-- Thông báo -->
            <a href="<?= e(url('notifications')) ?>"
               class="sf-icon-btn"
               aria-label="Thông báo">
                <span>♧</span>
            </a>

            <!-- Avatar -->
            <a href="<?= e(url('profile')) ?>"
               class="sf-avatar"
               aria-label="Hồ sơ cá nhân">
                <?= e(mb_substr(
                    $user['display_name'] ?? 'U',
                    0, 1, 'UTF-8'
                )) ?>
            </a>

            <!-- Menu -->
            <button type="button"
                    class="sf-icon-btn sf-menu-btn"
                    id="sfMenuToggle"
                    aria-controls="sfSidebar"
                    aria-expanded="false"
                    aria-label="Mở menu tài khoản">
                <span>☰</span>
            </button>

        </div>
    </div>

    <!-- Đường viền trang trí -->
    <div class="sf-header-accent"></div>
</header>
