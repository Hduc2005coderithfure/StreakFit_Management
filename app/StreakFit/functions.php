<?php
declare(strict_types=1);

function sf_config(): array
{
    static $config;
    return $config ??= require dirname(__DIR__, 2) . '/config/streakfit.php';
}

function sf_db(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = sf_config();
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $config['db_host'], $config['db_port'], $config['db_name']);
    $pdo = new PDO($dsn, $config['db_user'], $config['db_password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET time_zone = '+00:00'");
    return $pdo;
}

function sf_h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function sf_week_start(?DateTimeImmutable $date = null): string
{
    $timezone = new DateTimeZone(sf_config()['timezone']);
    $date = ($date ?? new DateTimeImmutable('now', $timezone))->setTimezone($timezone);
    return $date->modify('monday this week')->setTime(0, 0)->format('Y-m-d');
}

function sf_next_week(string $weekStart): string
{
    return (new DateTimeImmutable($weekStart, new DateTimeZone(sf_config()['timezone'])))->modify('+7 days')->format('Y-m-d');
}

function sf_week_score(int $target, int $validSessions, int $currentStreak): array
{
    $n = max(1, min(7, $target));
    $k = max(0, $validSessions);
    $c = min($k, $n);
    $m = max($n - $k, 0);
    $e = min(max($k - $n, 0), 2);
    $p = 40 * $c - 25 * $m + 20 * $e;
    $completed = $k >= $n;
    $streakAfter = $completed ? $currentStreak + 1 : 0;
    $streakPoints = $completed ? 5 * min($streakAfter, 6) : 0;

    return [
        'n' => $n,
        'k' => $k,
        'c' => $c,
        'm' => $m,
        'e' => $e,
        'p' => $p,
        'streak_after' => $streakAfter,
        'streak_points' => $streakPoints,
        'total' => $p + $streakPoints,
        'completed' => $completed,
    ];
}

function sf_rank(int $points): array
{
    $ranks = [
        ['name' => 'Đồng', 'min' => 0, 'next' => 301, 'symbol' => '🥉'],
        ['name' => 'Bạc', 'min' => 301, 'next' => 1001, 'symbol' => '🥈'],
        ['name' => 'Vàng', 'min' => 1001, 'next' => 3001, 'symbol' => '🥇'],
        ['name' => 'Bạch Kim', 'min' => 3001, 'next' => 7001, 'symbol' => '💠'],
        ['name' => 'Kim Cương', 'min' => 7001, 'next' => 12001, 'symbol' => '💎'],
        ['name' => 'Tinh Anh', 'min' => 12001, 'next' => 19001, 'symbol' => '🔮'],
        ['name' => 'Cao Thủ', 'min' => 19001, 'next' => 30001, 'symbol' => '🔥'],
        ['name' => 'Chiến Tướng', 'min' => 30001, 'next' => null, 'symbol' => '👑'],
    ];
    $points = max(0, $points);
    for ($i = count($ranks) - 1; $i >= 0; $i--) {
        if ($points >= $ranks[$i]['min']) {
            return $ranks[$i];
        }
    }
    return $ranks[0];
}

function sf_csrf_token(): string
{
    if (empty($_SESSION['sf_csrf'])) {
        $_SESSION['sf_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['sf_csrf'];
}

function sf_check_csrf(): void
{
    $submitted = (string)($_POST['_csrf'] ?? '');
    if ($submitted === '' || !hash_equals(sf_csrf_token(), $submitted)) {
        http_response_code(419);
        exit('Phiên đã hết hạn. Hãy tải lại trang và thử lại.');
    }
}

function sf_flash(string $message, string $type = 'success'): void
{
    $_SESSION['sf_flash'] = ['message' => $message, 'type' => $type];
}

function sf_redirect(string $tab = 'feed'): never
{
    $allowed = ['feed', 'checkin', 'leaderboard', 'notifications', 'profile'];
    $tab = in_array($tab, $allowed, true) ? $tab : 'feed';
    header('Location: ?tab=' . rawurlencode($tab), true, 303);
    exit;
}

function sf_store_capture(string $dataUrl, int $userId, string $kind): string
{
    if (!in_array($kind, ['checkin', 'checkout'], true) || !preg_match('#^data:image/jpeg;base64,([A-Za-z0-9+/=]+)$#', $dataUrl, $match)) {
        throw new DomainException('Hãy chụp ảnh trực tiếp bằng camera để tiếp tục.');
    }
    $binary = base64_decode($match[1], true);
    if ($binary === false || strlen($binary) > 4 * 1024 * 1024) {
        throw new DomainException('Ảnh chụp không hợp lệ hoặc vượt quá 4 MB.');
    }
    $info = @getimagesizefromstring($binary);
    if (!$info || ($info['mime'] ?? '') !== 'image/jpeg') {
        throw new DomainException('Tệp ảnh phải là ảnh JPEG chụp từ camera.');
    }

    $directory = dirname(__DIR__, 2) . '/storage/uploads/streakfit';
    if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
        throw new RuntimeException('Không thể tạo thư mục lưu ảnh.');
    }
    $filename = sprintf('%d_%s_%s.jpg', $userId, $kind, bin2hex(random_bytes(12)));
    if (file_put_contents($directory . '/' . $filename, $binary, LOCK_EX) === false) {
        throw new RuntimeException('Không thể lưu ảnh buổi tập.');
    }
    return $filename;
}

function sf_store_proof(array $upload, int $userId): ?string
{
    if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || (int)($upload['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new DomainException('Ảnh minh chứng phải nhỏ hơn 5 MB.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string)$upload['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    if (!isset($extensions[$mime])) {
        throw new DomainException('Ảnh minh chứng chỉ nhận JPG hoặc PNG.');
    }
    $directory = dirname(__DIR__, 2) . '/storage/uploads/streakfit/proofs';
    if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
        throw new RuntimeException('Không thể tạo thư mục ảnh minh chứng.');
    }
    $filename = sprintf('%d_proof_%s.%s', $userId, bin2hex(random_bytes(12)), $extensions[$mime]);
    if (!move_uploaded_file((string)$upload['tmp_name'], $directory . '/' . $filename)) {
        throw new RuntimeException('Không thể lưu ảnh minh chứng.');
    }
    return 'proof/' . $filename;
}

function sf_notify_others(PDO $db, int $actorId, string $type, string $title, string $body): void
{
    $stmt = $db->prepare('INSERT INTO notifications (user_id, actor_id, type, title, body) SELECT id, ?, ?, ?, ? FROM users WHERE id <> ?');
    $stmt->execute([$actorId, $type, $title, $body, $actorId]);
}

function sf_settle_user(PDO $db, int $userId, string $currentWeek): void
{
    $stmt = $db->prepare('SELECT g.week_start, g.target_sessions FROM weekly_goals g LEFT JOIN weekly_scores s ON s.user_id = g.user_id AND s.week_start = g.week_start WHERE g.user_id = ? AND g.week_start < ? AND s.id IS NULL ORDER BY g.week_start');
    $stmt->execute([$userId, $currentWeek]);
    $weeks = $stmt->fetchAll();

    foreach ($weeks as $week) {
        $weekStart = (string)$week['week_start'];
        $target = (int)$week['target_sessions'];
        $countStmt = $db->prepare("SELECT COUNT(DISTINCT workout_date) FROM workouts WHERE user_id = ? AND week_start = ? AND status = 'valid'");
        $countStmt->execute([$userId, $weekStart]);
        $valid = (int)$countStmt->fetchColumn();
        $pendingAppeals = $db->prepare("SELECT id FROM appeals WHERE user_id = ? AND week_start = ? AND status = 'pending'");
        $pendingAppeals->execute([$userId, $weekStart]);
        foreach ($pendingAppeals->fetchAll(PDO::FETCH_COLUMN) as $appealId) {
            $votes = $db->prepare("SELECT SUM(vote = 'yes') AS yes_count, SUM(vote = 'no') AS no_count FROM appeal_votes WHERE appeal_id = ?");
            $votes->execute([(int)$appealId]);
            $result = $votes->fetch() ?: ['yes_count' => 0, 'no_count' => 0];
            $yes = (int)($result['yes_count'] ?? 0);
            $no = (int)($result['no_count'] ?? 0);
            $status = $yes > $no ? 'accepted' : ($yes === $no && $yes > 0 ? 'rescue_pending' : 'rejected');
            $db->prepare('UPDATE appeals SET status = ?, resolved_at = UTC_TIMESTAMP() WHERE id = ? AND status = \'pending\'')->execute([$status, (int)$appealId]);
        }
        $appealStmt = $db->prepare("SELECT id, status FROM appeals WHERE user_id = ? AND week_start = ? AND status IN ('accepted','rescue_pending') ORDER BY id DESC LIMIT 1");
        $appealStmt->execute([$userId, $weekStart]);
        $appeal = $appealStmt->fetch();
        $appealKept = $appeal && $appeal['status'] === 'accepted';
        if ($appeal && $appeal['status'] === 'rescue_pending') {
            $mondayWorkout = $db->prepare("SELECT COUNT(*) FROM workouts WHERE user_id = ? AND workout_date = ? AND status = 'valid'");
            $mondayWorkout->execute([$userId, $currentWeek]);
            $rescued = (int)$mondayWorkout->fetchColumn() > 0;
            $today = (new DateTimeImmutable('now', new DateTimeZone(sf_config()['timezone'])))->format('Y-m-d');
            if (!$rescued && $today === $currentWeek) {
                break;
            }
            $appealState = $rescued ? 'rescued' : 'expired';
            $db->prepare('UPDATE appeals SET status = ?, resolved_at = UTC_TIMESTAMP() WHERE id = ?')->execute([$appealState, (int)$appeal['id']]);
            $appealKept = $rescued;
        }

        $db->beginTransaction();
        try {
            $userStmt = $db->prepare('SELECT total_points, current_streak, best_streak FROM users WHERE id = ? FOR UPDATE');
            $userStmt->execute([$userId]);
            $user = $userStmt->fetch();
            if (!$user) {
                $db->rollBack();
                continue;
            }
            $score = sf_week_score($target, $valid, (int)$user['current_streak']);
            $keepMissedStreak = $appealKept && !$score['completed'];
            if ($keepMissedStreak) {
                $score['streak_after'] = (int)$user['current_streak'];
                $score['streak_points'] = 0;
                $score['total'] = $score['p'];
            }
            $newPoints = max(0, (int)$user['total_points'] + $score['total']);
            $newBest = max((int)$user['best_streak'], (int)$score['streak_after']);

            $insert = $db->prepare('INSERT INTO weekly_scores (user_id, week_start, target_sessions, valid_sessions, completed_sessions, missed_sessions, extra_sessions, workout_points, streak_before, streak_after, streak_points, total_week_points, appeal_kept_streak) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $insert->execute([$userId, $weekStart, $target, $valid, $score['c'], $score['m'], $score['e'], $score['p'], (int)$user['current_streak'], $score['streak_after'], $score['streak_points'], $score['total'], $keepMissedStreak ? 1 : 0]);

            $update = $db->prepare('UPDATE users SET total_points = ?, current_streak = ?, best_streak = ? WHERE id = ?');
            $update->execute([$newPoints, $score['streak_after'], $newBest, $userId]);
            $ledger = $db->prepare('INSERT INTO points_ledger (user_id, week_start, points, reason) VALUES (?, ?, ?, ?)');
            $ledger->execute([$userId, $weekStart, $score['total'], 'Chốt điểm tuần: P ' . $score['p'] . ', PS ' . $score['streak_points']]);

            $notice = $db->prepare('INSERT INTO notifications (user_id, type, title, body) VALUES (?, ?, ?, ?)');
            $notice->execute([$userId, 'weekly_score', 'Đã chốt điểm tuần', 'Điểm tập P ' . $score['p'] . ', điểm chuỗi PS ' . $score['streak_points'] . ', tổng PT ' . $score['total'] . '.']);
            $db->commit();
        } catch (Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $error;
        }
    }
}

function sf_format_time(?string $utcDateTime, string $format = 'H:i'): string
{
    if (!$utcDateTime) {
        return '—';
    }
    $date = new DateTimeImmutable($utcDateTime, new DateTimeZone('UTC'));
    return $date->setTimezone(new DateTimeZone(sf_config()['timezone']))->format($format);
}
