<?php
$mode = (string)($_GET['mode'] ?? 'login');
if (!in_array($mode, ['login', 'register', 'forgot', 'reset'], true)) $mode = 'login';
$flash = $_SESSION['sf_flash'] ?? null;
unset($_SESSION['sf_flash']);
$action = ['forgot' => 'request_password_reset', 'reset' => 'reset_password'][$mode] ?? $mode;
$target = ['forgot' => 'forgot-password', 'reset' => 'reset-password', 'register' => 'register'][$mode] ?? 'login';
$title = ['register' => 'Bắt đầu hành trình', 'forgot' => 'Quên mật khẩu?', 'reset' => 'Đặt mật khẩu mới'][$mode] ?? 'Chào mừng trở lại';
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0e0e12">
    <title>StreakFit · <?= sf_h($title) ?></title>
    <link rel="stylesheet" href="<?= sf_h(asset('css/streakfit.css')) ?>">
</head>
<body class="auth-page">
<main class="auth-shell">
    <a class="brand auth-brand" href="<?= sf_h(url('login')) ?>"><span>Streak<i>Fit</i></span><small>Rèn luyện cùng nhau</small></a>
    <section class="auth-card">
        <div class="eyebrow">CỘNG ĐỒNG TẬP LUYỆN</div>
        <h1><?= sf_h($title) ?></h1>
        <p class="muted">Tạo thói quen bền vững, theo dõi từng buổi tập và giữ chuỗi cùng cộng đồng.</p>
        <?php if ($flash): ?><div class="alert <?= sf_h($flash['type']) ?>"><?= sf_h($flash['message']) ?></div><?php endif; ?>
        <?php if (in_array($mode, ['login', 'register'], true)): ?>
        <div class="auth-switch">
            <a class="<?= $mode === 'login' ? 'active' : '' ?>" href="<?= sf_h(url('login')) ?>">Đăng nhập</a>
            <a class="<?= $mode === 'register' ? 'active' : '' ?>" href="<?= sf_h(url('register')) ?>">Tạo tài khoản</a>
        </div>
        <?php endif; ?>
        <form method="post" action="<?= sf_h(url($target)) ?>" class="stack-form">
            <input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>">
            <input type="hidden" name="action" value="<?= sf_h($action) ?>">
            <?php if ($mode === 'reset'): ?><input type="hidden" name="token" value="<?= sf_h($_GET['token'] ?? '') ?>"><?php endif; ?>
            <?php if ($mode === 'register'): ?><label>Tên hiển thị<input name="display_name" maxlength="80" required autocomplete="name"></label><?php endif; ?>
            <?php if (in_array($mode, ['login', 'register', 'forgot'], true)): ?><label>Email<input name="email" type="email" maxlength="190" required autocomplete="email" placeholder="ban@email.com"></label><?php endif; ?>
            <?php if (in_array($mode, ['login', 'register', 'reset'], true)): ?><label><?= $mode === 'reset' ? 'Mật khẩu mới' : 'Mật khẩu' ?><input name="password" type="password" minlength="8" maxlength="72" required autocomplete="<?= $mode === 'login' ? 'current-password' : 'new-password' ?>" placeholder="8 đến 72 byte"></label><?php endif; ?>
            <button class="btn primary full" type="submit"><?= ['register' => 'Tạo tài khoản StreakFit', 'forgot' => 'Gửi liên kết đặt lại', 'reset' => 'Lưu mật khẩu mới'][$mode] ?? 'Đăng nhập' ?><span>→</span></button>
        </form>
        <?php if ($mode === 'login'): ?><p class="auth-foot"><a href="<?= sf_h(url('forgot-password')) ?>">Quên mật khẩu?</a></p><?php elseif (in_array($mode, ['forgot', 'reset'], true)): ?><p class="auth-foot"><a href="<?= sf_h(url('login')) ?>">Quay lại đăng nhập</a></p><?php endif; ?>
        <p class="auth-foot">Tập đúng sức · Nghỉ đúng lúc · Giữ lời hứa với chính mình.</p>
    </section>
    <p class="auth-note">StreakFit · Thời gian của bạn, tiến bộ của bạn.</p>
</main>
</body>
</html>
