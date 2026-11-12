<?php
$mode = ($_GET['mode'] ?? 'login') === 'register' ? 'register' : 'login';
$flash = $_SESSION['sf_flash'] ?? null;
unset($_SESSION['sf_flash']);
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0e0e12">
    <title>StreakFit · Kỷ luật mỗi tuần</title>
    <link rel="stylesheet" href="assets/css/streakfit.css">
</head>
<body class="auth-page">
<main class="auth-shell">
    <a class="brand auth-brand" href="?"><span>Streak<i>Fit</i></span><small>Rèn luyện cùng nhau</small></a>
    <section class="auth-card">
        <div class="eyebrow">CỘNG ĐỒNG TẬP LUYỆN</div>
        <h1><?= $mode === 'register' ? 'Bắt đầu hành trình' : 'Chào mừng trở lại' ?></h1>
        <p class="muted">Tạo thói quen bền vững, theo dõi từng buổi tập và giữ chuỗi cùng cộng đồng.</p>
        <?php if ($flash): ?>
            <div class="alert <?= sf_h($flash['type']) ?>"><?= sf_h($flash['message']) ?></div>
        <?php endif; ?>
        <div class="auth-switch">
            <a class="<?= $mode === 'login' ? 'active' : '' ?>" href="?mode=login">Đăng nhập</a>
            <a class="<?= $mode === 'register' ? 'active' : '' ?>" href="?mode=register">Tạo tài khoản</a>
        </div>
        <form method="post" class="stack-form">
            <input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>">
            <input type="hidden" name="action" value="<?= $mode ?>">
            <?php if ($mode === 'register'): ?>
                <label>Tên hiển thị<input name="display_name" maxlength="80" required autocomplete="name" placeholder="Ví dụ: Sơn Nguyễn"></label>
            <?php endif; ?>
            <label>Email<input name="email" type="email" maxlength="190" required autocomplete="email" placeholder="ban@email.com"></label>
            <label>Mật khẩu<input name="password" type="password" minlength="8" required autocomplete="<?= $mode === 'register' ? 'new-password' : 'current-password' ?>" placeholder="Tối thiểu 8 ký tự"></label>
            <button class="btn primary full" type="submit"><?= $mode === 'register' ? 'Tạo tài khoản StreakFit' : 'Đăng nhập' ?><span>→</span></button>
        </form>
        <p class="auth-foot">Tập đúng sức · Nghỉ đúng lúc · Giữ lời hứa với chính mình.</p>
    </section>
    <p class="auth-note">StreakFit · Thời gian của bạn, tiến bộ của bạn.</p>
</main>
</body>
</html>
