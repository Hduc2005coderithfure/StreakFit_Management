<?php
declare(strict_types=1);

use App\Core\Auth;
use App\Models\User;
use App\Services\CourtAppealService;
use App\Services\PenaltyPoolService;

$config = require APP_ROOT . '/config/streakfit.php';
date_default_timezone_set($config['timezone']);
require_once APP_ROOT . '/app/Helpers/functions.php';

try {
    $db = sf_db();
} catch (Throwable $error) {
    http_response_code(503);
    ?><!doctype html><html lang="vi"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>StreakFit · Cơ sở dữ liệu</title><body style="margin:0;background:#0e0e12;color:#f6f6fa;font:16px system-ui;display:grid;min-height:100vh;place-items:center"><main style="max-width:560px;padding:32px;border:1px solid #34313a;border-radius:24px;background:#1a1a21"><h1 style="color:#ff8065">StreakFit chưa kết nối được database</h1><p>Hãy kiểm tra MariaDB đang chạy và cấu hình STREAKFIT_DB_* trong tệp .env.</p><p>Schema: <code>database/streakfit.sql</code></p></main></body></html><?php
    exit;
}

$userId = (int)($_SESSION['sf_user_id'] ?? 0);
$permissionService = new \App\Services\AdminPermissionService($db);
$viewerStmt = $db->prepare('SELECT role, is_active FROM users WHERE id = ?');
$viewerStmt->execute([$userId]);
$viewer = $viewerStmt->fetch() ?: null;
$canViewAdminWorkouts = $permissionService->can($userId, 'workouts.view');

if (isset($_GET['media']) && $userId > 0 && $viewer && (int)$viewer['is_active'] === 1) {
    $workoutId = filter_var($_GET['media'], FILTER_VALIDATE_INT);
    $kind = (string)($_GET['kind'] ?? '');
    if (!$workoutId || !in_array($kind, ['checkin', 'checkout'], true)) {
        http_response_code(404);
        exit;
    }
    $column = $kind === 'checkin' ? 'checkin_image' : 'checkout_image';
    $stmt = $db->prepare("SELECT w.$column AS image_path, w.status, w.user_id FROM workouts w WHERE w.id = ? AND (w.status = 'valid' OR w.user_id = ? OR ? = 1)");
    $stmt->execute([$workoutId, $userId, $canViewAdminWorkouts ? 1 : 0]);
    $photo = $stmt->fetch();
    if (!$photo || !$photo['image_path']) {
        http_response_code(404);
        exit;
    }
    $directory = realpath(APP_ROOT . '/storage/uploads/streakfit');
    $path = $directory ? realpath($directory . DIRECTORY_SEPARATOR . basename($photo['image_path'])) : false;
    if (!$path || !str_starts_with($path, $directory . DIRECTORY_SEPARATOR) || !is_file($path)) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: image/jpeg');
    header('Content-Length: ' . (string)filesize($path));
    header('Cache-Control: private, max-age=300');
    readfile($path);
    exit;
}

if (isset($_GET['appeal_media']) && $userId > 0 && $viewer && (int)$viewer['is_active'] === 1) {
    $appealId = filter_var($_GET['appeal_media'], FILTER_VALIDATE_INT);
    $canViewAppeals = $permissionService->can($userId, 'appeals.view');
    $stmt = $db->prepare("SELECT proof_path FROM appeals WHERE id = ? AND (user_id = ? OR (status = 'pending' AND ? = 1))");
    $stmt->execute([$appealId ?: 0, $userId, $canViewAppeals ? 1 : 0]);
    $proof = $stmt->fetchColumn();
    $directory = realpath(APP_ROOT . '/storage/uploads/streakfit');
    $path = $directory && $proof ? realpath($directory . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string)$proof)) : false;
    if (!$path || !$directory || !str_starts_with($path, $directory . DIRECTORY_SEPARATOR) || !is_file($path)) {
        http_response_code(404);
        exit;
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Content-Length: ' . (string)filesize($path));
    header('Cache-Control: private, max-age=300');
    readfile($path);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    sf_check_csrf();
    $action = (string)($_POST['action'] ?? '');
    try {
        if ($action === 'request_password_reset') {
            (new \App\Services\PasswordResetService($db))->request((string)($_POST['email'] ?? ''));
            sf_flash('Nếu email tồn tại, liên kết đặt lại mật khẩu sẽ được gửi trong ít phút.');
            header('Location: ' . url('forgot-password'), true, 303);
            exit;
        }

        if ($action === 'reset_password') {
            (new \App\Services\PasswordResetService($db))->reset((string)($_POST['token'] ?? ''), (string)($_POST['password'] ?? ''));
            sf_flash('Mật khẩu đã được cập nhật. Hãy đăng nhập lại.');
            header('Location: ' . url('login'), true, 303);
            exit;
        }

        if ($action === 'register') {
            $name = trim((string)($_POST['display_name'] ?? ''));
            $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
            $password = (string)($_POST['password'] ?? '');
            if (mb_strlen($name) < 2 || mb_strlen($name) > 80 || strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8 || strlen($password) > 72) {
                throw new DomainException('Nhập tên, email hợp lệ và mật khẩu từ 8 đến 72 byte.');
            }
            $db->beginTransaction();
            $firstAccount = $db->query('SELECT id FROM users ORDER BY id LIMIT 1 FOR UPDATE')->fetchColumn();
            $role = $firstAccount === false ? 'admin' : 'member';
            $newUserId = (new User($db))->create($email, password_hash($password, PASSWORD_DEFAULT), $name, $role);
            if ($role === 'admin') $permissionService->grantAllToUser($newUserId);
            $weekStart = sf_week_start();
            $goal = $db->prepare('INSERT INTO weekly_goals (user_id, week_start, target_sessions, locked_at) VALUES (?, ?, 3, UTC_TIMESTAMP())');
            $goal->execute([$newUserId, $weekStart]);
            $db->commit();
            session_regenerate_id(true);
            $_SESSION['sf_user_id'] = $newUserId;
            sf_flash($role === 'admin' ? 'Tài khoản quản trị đã tạo. Mục tiêu tuần đầu tiên là 3 buổi.' : 'Tài khoản đã tạo. Mục tiêu tuần đầu tiên là 3 buổi.');
            sf_redirect($role === 'admin' ? 'admin' : 'feed');
        }

        if ($action === 'login') {
            $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
            $auth = new Auth(new User($db));
            if (!$auth->login($email, (string)($_POST['password'] ?? ''))) {
                throw new DomainException('Email hoặc mật khẩu chưa chính xác.');
            }
            sf_flash('Đăng nhập thành công. Chúc bạn có buổi tập tốt!');
            sf_redirect($permissionService->can((int)$_SESSION['sf_user_id'], 'dashboard.view') ? 'admin' : 'feed');
        }

        if ($action === 'logout') {
            unset($_SESSION['sf_user_id']);
            session_regenerate_id(true);
            sf_flash('Bạn đã đăng xuất.');
            header('Location: ' . url('login'), true, 303);
            exit;
        }

        if ($userId < 1) {
            throw new DomainException('Hãy đăng nhập để tiếp tục.');
        }
        if (!$viewer || !(int)$viewer['is_active']) {
            unset($_SESSION['sf_user_id']);
            throw new DomainException('Tài khoản đã bị khóa. Hãy liên hệ quản trị viên.');
        }

        $currentWeek = sf_week_start();
        $today = (new DateTimeImmutable('now', new DateTimeZone($config['timezone'])))->format('Y-m-d');

        if ($action === 'change_password') {
            (new \App\Services\PasswordResetService($db))->change($userId, (string)($_POST['current_password'] ?? ''), (string)($_POST['new_password'] ?? ''));
            session_regenerate_id(true);
            sf_flash('Mật khẩu đã được cập nhật.');
            sf_redirect('profile');
        }

        if (str_starts_with($action, 'admin_')) {
            if ($action === 'admin_update_member') {
                if (!$permissionService->can($userId, 'members.manage') && !$permissionService->can($userId, 'members.change_role')) {
                    throw new DomainException('Tài khoản này không có quyền quản lý thành viên.');
                }
            } else {
                $requiredPermission = match ($action) {
                    'admin_assign_permissions' => 'admin.permissions.manage',
                    'admin_review_workout' => 'workouts.review',
                    'admin_review_appeal' => 'appeals.review',
                    'admin_adjust_points' => 'points.adjust',
                    'admin_fund_transaction' => 'fund.manage',
                    'admin_resolve_report', 'admin_remove_reported_content' => 'content_reports.manage',
                    'admin_update_settings' => 'settings.manage',
                    default => null,
                };
                if ($requiredPermission === null) throw new DomainException('Thao tác quản trị chưa được hỗ trợ.');
                $permissionService->require($userId, $requiredPermission);
            }
            $admin = new \App\Services\AdminService($db);
            if ($action === 'admin_update_member') {
                $admin->setMember(
                    $userId,
                    (int)($_POST['member_id'] ?? 0),
                    (string)($_POST['role'] ?? ''),
                    (string)($_POST['is_active'] ?? '') === '1',
                    $permissionService->can($userId, 'members.manage'),
                    $permissionService->can($userId, 'members.change_role')
                );
            } elseif ($action === 'admin_assign_permissions') {
                $permissionService->assign($userId, (int)($_POST['member_id'] ?? 0), (array)($_POST['permissions'] ?? []));
            } elseif ($action === 'admin_review_workout') {
                $admin->reviewWorkout($userId, (int)($_POST['workout_id'] ?? 0), (string)($_POST['decision'] ?? ''), (string)($_POST['reason'] ?? ''), sf_settings()['minimum_workout_seconds']);
            } elseif ($action === 'admin_review_appeal') {
                $admin->reviewAppeal($userId, (int)($_POST['appeal_id'] ?? 0), (string)($_POST['decision'] ?? ''), (string)($_POST['reason'] ?? ''));
            } elseif ($action === 'admin_adjust_points') {
                $admin->adjustPoints($userId, (int)($_POST['member_id'] ?? 0), (int)($_POST['points'] ?? 0), (string)($_POST['reason'] ?? ''));
            } elseif ($action === 'admin_fund_transaction') {
                $admin->recordFundTransaction($userId, (int)($_POST['fund_id'] ?? 0), (int)($_POST['member_id'] ?? 0), (string)($_POST['transaction_type'] ?? ''), (int)($_POST['amount'] ?? 0), (string)($_POST['note'] ?? ''));
            } elseif ($action === 'admin_resolve_report') {
                $admin->resolveReport($userId, (int)($_POST['report_id'] ?? 0), (string)($_POST['decision'] ?? ''), (string)($_POST['note'] ?? ''));
            } elseif ($action === 'admin_remove_reported_content') {
                $permissionService->require($userId, 'content.moderate');
                $admin->removeReportedContent($userId, (int)($_POST['report_id'] ?? 0), (string)($_POST['note'] ?? ''));
            } elseif ($action === 'admin_update_settings') {
                (new \App\Services\SettingsService($db))->save($_POST, $userId);
            } else {
                throw new DomainException('Thao tác quản trị chưa được hỗ trợ.');
            }
            sf_flash('Đã lưu thay đổi quản trị.');
            sf_redirect('admin');
        }

        if (in_array($action, ['edit_post', 'delete_post', 'edit_comment', 'delete_comment', 'report_content'], true)) {
            $isAdminActor = $permissionService->can($userId, 'content.moderate');
            $content = new \App\Services\ContentService($db);
            if ($action === 'edit_post') $content->editPost($userId, $isAdminActor, (int)($_POST['workout_id'] ?? 0), (string)($_POST['caption'] ?? ''));
            if ($action === 'delete_post') $content->deletePost($userId, $isAdminActor, (int)($_POST['workout_id'] ?? 0));
            if ($action === 'edit_comment') $content->editComment($userId, $isAdminActor, (int)($_POST['comment_id'] ?? 0), (string)($_POST['comment'] ?? ''));
            if ($action === 'delete_comment') $content->deleteComment($userId, $isAdminActor, (int)($_POST['comment_id'] ?? 0));
            if ($action === 'report_content') $content->report($userId, (string)($_POST['content_type'] ?? ''), (int)($_POST['content_id'] ?? 0), (string)($_POST['reason'] ?? ''));
            sf_flash('Đã ghi nhận thao tác nội dung.');
            sf_redirect((string)($_GET['tab'] ?? 'feed'));
        }

        if ($action === 'update_display_name') {
            $displayName = trim((string)($_POST['display_name'] ?? ''));
            if (mb_strlen($displayName) < 2 || mb_strlen($displayName) > 80) {
                throw new DomainException('Tên hiển thị cần có từ 2 đến 80 ký tự.');
            }
            $db->prepare('UPDATE users SET display_name = ? WHERE id = ?')->execute([$displayName, $userId]);
            sf_flash('Đã cập nhật tên hiển thị.');
            header('Location: ' . url('profile?section=profile'), true, 303);
            exit;
        }

        if ($action === 'update_health') {
            $weight = filter_var($_POST['weight_kg'] ?? null, FILTER_VALIDATE_FLOAT);
            $height = filter_var($_POST['height_cm'] ?? null, FILTER_VALIDATE_FLOAT);
            $age = filter_var($_POST['age'] ?? null, FILTER_VALIDATE_INT);
            $gender = (string)($_POST['gender'] ?? 'unspecified');
            $displayName = trim((string)($_POST['display_name'] ?? ''));
            if (!$weight || $weight < 25 || $weight > 250 || !$height || $height < 100 || $height > 230 || !$age || $age < 16 || $age > 100 || !in_array($gender, ['female','male','unspecified'], true)) {
                throw new DomainException('Các chỉ số sức khỏe chưa hợp lệ.');
            }
            if (mb_strlen($displayName) < 2 || mb_strlen($displayName) > 80) throw new DomainException('Tên hiển thị cần có từ 2 đến 80 ký tự.');
            $db->beginTransaction();
            $stmt = $db->prepare('UPDATE users SET weight_kg = ?, height_cm = ?, age = ?, gender = ?, display_name = ? WHERE id = ?');
            $stmt->execute([$weight, $height, $age, $gender, $displayName, $userId]);
            $stmt = $db->prepare('INSERT INTO health_metrics (user_id, height_cm, weight_kg) VALUES (?, ?, ?)');
            $stmt->execute([$userId, $height, $weight]);
            $db->commit();
            sf_flash('Đã cập nhật chỉ số sức khỏe.');
            sf_redirect('profile');
        }

        if ($action === 'save_goal') {
            $week = (string)($_POST['week_start'] ?? '');
            $target = filter_var($_POST['target_sessions'] ?? null, FILTER_VALIDATE_INT);
            $nextWeek = sf_next_week(sf_week_start());
            if ($week !== $nextWeek || $target === false || $target < 1 || $target > 7) {
                throw new DomainException('Mục tiêu chỉ được đăng ký cho tuần tới, từ 1 đến 7 buổi.');
            }
            $dates = $_POST['planned_dates'] ?? [];
            $times = $_POST['planned_times'] ?? [];
            $notes = $_POST['planned_notes'] ?? [];
            $weekEnd = (new DateTimeImmutable($week, new DateTimeZone($config['timezone'])))->modify('+7 days')->format('Y-m-d');
            $validatedPlans = [];
            foreach (is_array($dates) ? $dates : [] as $index => $date) {
                $time = trim((string)($times[$index] ?? ''));
                if ($time === '') {
                    continue;
                }
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$date) || $date < $week || $date >= $weekEnd || !preg_match('/^\d{2}:\d{2}$/', $time)) {
                    throw new DomainException('Một ngày hoặc giờ trong lịch tập chưa hợp lệ.');
                }
                $note = trim((string)($notes[$index] ?? ''));
                $validatedPlans[] = [$date, $time . ':00', mb_substr($note, 0, 160)];
            }
            $db->beginTransaction();
            $db->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE')->execute([$userId]);
            if ($week !== sf_next_week(sf_week_start())) throw new DomainException('Tuần đã bắt đầu; không thể thay đổi mục tiêu hoặc lịch tập.');
            $stmt = $db->prepare('SELECT locked_at FROM weekly_goals WHERE user_id = ? AND week_start = ? FOR UPDATE');
            $stmt->execute([$userId, $week]);
            $existing = $stmt->fetch();
            if ($existing && $existing['locked_at'] !== null) throw new DomainException('Mục tiêu tuần này đã khóa.');
            $stmt = $db->prepare('INSERT INTO weekly_goals (user_id, week_start, target_sessions) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE target_sessions = VALUES(target_sessions)');
            $stmt->execute([$userId, $week, $target]);
            $db->prepare('DELETE FROM planned_workouts WHERE user_id = ? AND week_start = ?')->execute([$userId, $week]);
            $planInsert = $db->prepare('INSERT INTO planned_workouts (user_id, week_start, planned_date, planned_time, note) VALUES (?, ?, ?, ?, ?)');
            foreach ($validatedPlans as [$date, $time, $note]) {
                $planInsert->execute([$userId, $week, $date, $time, $note]);
            }
            $db->commit();
            sf_flash('Đã lưu mục tiêu và lịch dự kiến cho tuần tới. Lịch không ảnh hưởng điểm streak.');
            sf_redirect('profile');
        }

        if ($action === 'start_workout') {
            $service = new \App\Services\CheckInService($db, sf_settings()['minimum_workout_seconds'], sf_settings()['checkout_timeout_hours'] * 3600);
            $service->start($userId, (string)($_POST['image_data'] ?? ''), $config['timezone'], (string)($_POST['sport'] ?? 'gym'));
            sf_notify_others($db, $userId, 'workout', 'Một thành viên vừa check-in', 'Có thành viên bắt đầu buổi tập. Gửi lời động viên nhé!');
            sf_flash('Check-in thành công. Thời gian bắt đầu được ghi nhận từ máy chủ.');
            sf_redirect('checkin');
        }

        if ($action === 'finish_workout') {
            $service = new \App\Services\CheckInService($db, sf_settings()['minimum_workout_seconds'], sf_settings()['checkout_timeout_hours'] * 3600);
            $result = $service->finish(
                $userId,
                (string)($_POST['image_data'] ?? ''),
                $config['timezone'],
                (string)($_POST['caption'] ?? ''),
                (int)($_POST['workout_id'] ?? 0)
            );
            if (!$result['valid']) {
                $minimumSeconds = sf_settings()['minimum_workout_seconds'];
                $message = $result['duration_seconds'] < $minimumSeconds
                    ? 'Buổi tập chưa đủ thời lượng tối thiểu ' . (int)ceil($minimumSeconds / 60) . ' phút.'
                    : 'Buổi tập không hợp lệ: ngày này đã có một buổi được tính.';
                sf_flash($message, 'error');
                sf_redirect('checkin');
            }
            sf_notify_others($db, $userId, 'workout', 'Một buổi tập vừa hoàn thành', 'Thẻ tổng kết mới đã được đăng lên bảng tin.');
            sf_flash('Buổi tập hợp lệ đã được đăng lên bảng tin. Điểm tuần sẽ chốt vào Chủ Nhật.');
            sf_redirect('feed');
        }
        if ($action === 'discard_workout') {
            (new \App\Services\CheckInService($db, sf_settings()['minimum_workout_seconds'], sf_settings()['checkout_timeout_hours'] * 3600))->discard($userId, (int)($_POST['workout_id'] ?? 0));
            sf_flash('Đã hủy buổi tập đang mở. Buổi này không được tính vào mục tiêu.');
            sf_redirect('checkin');
        }

        if ($action === 'comment') {
            $workoutId = (int)($_POST['workout_id'] ?? 0);
            $comment = trim((string)($_POST['comment'] ?? ''));
            if ($comment === '' || mb_strlen($comment) > 500) {
                throw new DomainException('Bình luận cần có nội dung và tối đa 500 ký tự.');
            }
            $stmt = $db->prepare("INSERT INTO workout_comments (workout_id, user_id, comment_text) SELECT id, ?, ? FROM workouts WHERE id = ? AND status = 'valid'");
            $stmt->execute([$userId, $comment, $workoutId]);
            if ($stmt->rowCount() !== 1) throw new DomainException('Không tìm thấy bài tập có thể bình luận.');
            sf_flash('Đã gửi bình luận.');
            sf_redirect('feed');
        }

        if ($action === 'react') {
            $workoutId = (int)($_POST['workout_id'] ?? 0);
            $reaction = (string)($_POST['reaction'] ?? '');
            if (!in_array($reaction, ['heart','muscle','fire','sad','haha'], true)) {
                throw new DomainException('Cảm xúc chưa hợp lệ.');
            }
            $stmt = $db->prepare("SELECT id FROM workouts WHERE id = ? AND status = 'valid'");
            $stmt->execute([$workoutId]);
            if (!$stmt->fetch()) {
                throw new DomainException('Không tìm thấy bài tập này.');
            }
            $stmt = $db->prepare('SELECT reaction FROM workout_reactions WHERE workout_id = ? AND user_id = ?');
            $stmt->execute([$workoutId, $userId]);
            $oldReaction = $stmt->fetchColumn();
            if ($oldReaction === $reaction) {
                $db->prepare('DELETE FROM workout_reactions WHERE workout_id = ? AND user_id = ?')->execute([$workoutId, $userId]);
            } else {
                $stmt = $db->prepare('INSERT INTO workout_reactions (workout_id, user_id, reaction) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE reaction = VALUES(reaction)');
                $stmt->execute([$workoutId, $userId, $reaction]);
            }
            sf_redirect('feed');
        }

        if ($action === 'mark_read') {
            $db->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([$userId]);
            sf_redirect('notifications');
        }

        if ($action === 'appeal') {
            $reason = trim((string)($_POST['reason'] ?? ''));
            $month = (new DateTimeImmutable('now', new DateTimeZone($config['timezone'])))->modify('first day of this month')->format('Y-m-d');
            $proof = sf_store_proof($_FILES['proof'] ?? [], $userId);
            if (!$proof) throw new DomainException('Hãy đính kèm ảnh minh chứng.');
            try {
                (new CourtAppealService($db))->submit($userId, $currentWeek, $month, $reason, $proof);
            } catch (Throwable $error) {
                if ($proof) @unlink(APP_ROOT . '/storage/uploads/streakfit/proofs/' . basename($proof));
                throw $error;
            }            $notice = $db->prepare("INSERT INTO notifications (user_id, type, title, body) SELECT id, 'appeal', 'Có kháng cáo cần biểu quyết', ? FROM users WHERE id <> ?");
            $notice->execute([$reason, $userId]);
            sf_flash('Đã gửi kháng cáo ẩn danh đến cộng đồng.');
            sf_redirect('leaderboard');
        }

        if ($action === 'vote_appeal') {
            $appealId = (int)($_POST['appeal_id'] ?? 0);
            $vote = (string)($_POST['vote'] ?? '');
            (new CourtAppealService($db))->vote($appealId, $userId, $vote);
            sf_flash('Phiếu biểu quyết đã được ghi nhận.');
            sf_redirect('leaderboard');
        }
        if (in_array($action, ['fund_join','fund_leave','fund_mark_paid','fund_propose','fund_start_vote','fund_vote','fund_close_vote'], true)) {
            $month = (new DateTimeImmutable('first day of this month', new DateTimeZone($config['timezone'])))->format('Y-m-d');
            $poolService = new PenaltyPoolService($db, sf_settings()['monthly_contribution']);
            $poolService->ensureMonth($month);
            $stmt = $db->prepare('SELECT id, status, contribution_amount FROM monthly_funds WHERE month_start = ?');
            $stmt->execute([$month]);
            $fund = $stmt->fetch();
            $fundId = (int)$fund['id'];

            if ($action === 'fund_join') {
                (new PenaltyPoolService($db))->join($fundId, $userId);
                sf_flash('Đã đăng ký tham gia quỹ tháng này. Website chỉ ghi sổ, không giữ hoặc chuyển tiền.');
                sf_redirect((string)($_POST['return_to'] ?? '') === 'admin' ? 'admin' : 'profile');
            }

            if ($action === 'fund_leave') {
                (new PenaltyPoolService($db))->leave($fundId, $userId);
                sf_flash('Đã cập nhật lựa chọn tham gia quỹ.');
                sf_redirect((string)($_POST['return_to'] ?? '') === 'admin' ? 'admin' : 'profile');
            }

            $canManageFund = $permissionService->can($userId, 'fund.manage');

            if ($action === 'fund_mark_paid') {
                $permissionService->require($userId, 'fund.manage');
                $memberId = (int)($_POST['member_id'] ?? 0);
                (new \App\Services\AdminService($db))->recordFundTransaction($userId, $fundId, $memberId, 'contribution', (int)$fund['contribution_amount'], 'Admin xác nhận tham gia và đóng quỹ');
                sf_flash('Đã xác nhận thành viên tham gia và ghi nhận đã đóng quỹ.');
                sf_redirect((string)($_POST['return_to'] ?? '') === 'admin' ? 'admin' : 'profile');
            }

            $memberStmt = $db->prepare('SELECT opted_in FROM fund_members WHERE fund_id = ? AND user_id = ?');
            $memberStmt->execute([$fundId, $userId]);
            if (!(int)$memberStmt->fetchColumn() && !($canManageFund && in_array($action, ['fund_start_vote','fund_close_vote'], true))) {
                throw new DomainException('Hãy đăng ký tham gia quỹ trước.');
            }

            if ($action === 'fund_propose') {
                if ($fund['status'] !== 'open') {
                    throw new DomainException('Đề xuất mức phạt đã đóng.');
                }
                $amount = filter_var($_POST['amount'] ?? null, FILTER_VALIDATE_INT);
                if (!$amount || $amount < 1000 || $amount > 1000000) {
                    throw new DomainException('Mức phạt đề xuất phải từ 1.000đ đến 1.000.000đ.');
                }
                (new PenaltyPoolService($db))->propose($fundId, $userId, (int)$amount);
                sf_flash('Đã lưu đề xuất mức phạt tháng.');
                sf_redirect('profile');
            }

            if ($action === 'fund_start_vote') {
                $permissionService->require($userId, 'fund.manage');
                if ($fund['status'] !== 'open') {
                    throw new DomainException('Chỉ quản trị viên mới có thể mở biểu quyết.');
                }
                $proposalCount = $db->prepare('SELECT COUNT(*) FROM penalty_proposals WHERE fund_id = ?');
                $proposalCount->execute([$fundId]);
                if ((int)$proposalCount->fetchColumn() === 0) {
                    throw new DomainException('Cần có ít nhất một đề xuất trước khi mở biểu quyết.');
                }
                (new PenaltyPoolService($db))->startVote($fundId, $userId);
                sf_flash('Đã mở biểu quyết mức phạt tháng.');
                sf_redirect((string)($_POST['return_to'] ?? '') === 'admin' ? 'admin' : 'profile');
            }

            if ($action === 'fund_vote') {
                if ($fund['status'] !== 'voting') {
                    throw new DomainException('Biểu quyết chưa mở hoặc đã kết thúc.');
                }
                $amount = filter_var($_POST['amount'] ?? null, FILTER_VALIDATE_INT);
                $validAmount = $db->prepare('SELECT id FROM penalty_proposals WHERE fund_id = ? AND amount = ? LIMIT 1');
                $validAmount->execute([$fundId, $amount ?: 0]);
                if (!$validAmount->fetch()) {
                    throw new DomainException('Hãy chọn một mức phạt đã được đề xuất.');
                }
                (new PenaltyPoolService($db))->vote($fundId, $userId, (int)$amount);
                sf_flash('Đã ghi nhận phiếu biểu quyết.');
                sf_redirect('profile');
            }

            if ($action === 'fund_close_vote') {
                $permissionService->require($userId, 'fund.manage');
                if ($fund['status'] !== 'voting') {
                    throw new DomainException('Chỉ quản trị viên mới có thể chốt biểu quyết.');
                }
                $winner = $db->prepare('SELECT amount FROM penalty_votes WHERE fund_id = ? GROUP BY amount ORDER BY COUNT(*) DESC, amount ASC LIMIT 1');
                $winner->execute([$fundId]);
                $amount = $winner->fetchColumn();
                if ($amount === false) {
                    throw new DomainException('Chưa có phiếu biểu quyết để chốt.');
                }
                (new PenaltyPoolService($db))->closeVote($fundId, $userId);
                sf_flash('Đã chốt mức phạt tháng ở ' . number_format((int)$amount) . 'đ.');
                sf_redirect((string)($_POST['return_to'] ?? '') === 'admin' ? 'admin' : 'profile');
            }
        }

        throw new DomainException('Thao tác chưa được hỗ trợ.');
    } catch (DomainException $error) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        sf_flash($error->getMessage(), 'error');
    } catch (PDOException $error) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('StreakFit database action failed: ' . $error->getMessage());
        sf_flash($action === 'register' ? 'Email này có thể đã được đăng ký.' : 'Chưa thể lưu thay đổi. Hãy thử lại.', 'error');
    } catch (Throwable $error) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('StreakFit action failed: ' . $error->getMessage());
        sf_flash($error instanceof RuntimeException ? $error->getMessage() : 'Đã có lỗi khi xử lý yêu cầu.', 'error');
    }
    if ($userId < 1) {
        $guestTarget = match ($action) {
            'request_password_reset' => 'forgot-password',
            'reset_password' => 'reset-password?token=' . rawurlencode((string)($_POST['token'] ?? '')),
            'register' => 'register',
            default => 'login',
        };
        header('Location: ' . url($guestTarget), true, 303);
        exit;
    }
    $returnToAdmin = str_starts_with($action, 'admin_') || (string)($_POST['return_to'] ?? '') === 'admin';
    sf_redirect($returnToAdmin ? 'admin' : (string)($_GET['tab'] ?? 'feed'));
}

if ($userId < 1) {
    require APP_ROOT . '/app/Views/auth/login.php';
    exit;
}

$userStmt = $db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$userStmt->execute([$userId]);
$user = $userStmt->fetch();
if (!$user || !(int)($user['is_active'] ?? 1)) {
    unset($_SESSION['sf_user_id']);
    header('Location: ' . url('login'), true, 303);
    exit;
}

$currentWeek = sf_week_start();
$nextWeek = sf_next_week($currentWeek);
sf_settle_user($db, $userId, $currentWeek);
$db->prepare('INSERT IGNORE INTO weekly_goals (user_id, week_start, target_sessions, locked_at) VALUES (?, ?, 3, UTC_TIMESTAMP())')->execute([$userId, $currentWeek]);
$db->prepare('UPDATE weekly_goals SET locked_at = UTC_TIMESTAMP() WHERE user_id = ? AND week_start <= ? AND locked_at IS NULL')->execute([$userId, $currentWeek]);
$userStmt->execute([$userId]);
$user = $userStmt->fetch();
$goalStmt = $db->prepare('SELECT * FROM weekly_goals WHERE user_id = ? AND week_start = ? LIMIT 1');
$goalStmt->execute([$userId, $currentWeek]);
$goal = $goalStmt->fetch();
$nextGoalStmt = $db->prepare('SELECT * FROM weekly_goals WHERE user_id = ? AND week_start = ? LIMIT 1');
$nextGoalStmt->execute([$userId, $nextWeek]);
$nextGoal = $nextGoalStmt->fetch() ?: ['target_sessions' => $goal['target_sessions'] ?? 3];
$countStmt = $db->prepare("SELECT COUNT(DISTINCT workout_date) FROM workouts WHERE user_id = ? AND week_start = ? AND status = 'valid'");
$countStmt->execute([$userId, $currentWeek]);
$weeklyCount = (int)$countStmt->fetchColumn();
$effectiveTarget = sf_member_week_target($db, $userId, $currentWeek, (int)$goal['target_sessions']);
$score = $effectiveTarget > 0
    ? sf_week_score($effectiveTarget, $weeklyCount, (int)$user['current_streak'])
    : ['total' => 0, 'completed' => false, 'n' => (int)$goal['target_sessions'], 'k' => $weeklyCount, 'c' => 0, 'm' => 0, 'e' => 0, 'p' => 0, 'streak_after' => (int)$user['current_streak'], 'streak_points' => 0];
$activeStmt = $db->prepare("SELECT * FROM workouts WHERE user_id = ? AND status = 'in_progress' ORDER BY id DESC LIMIT 1");
$activeStmt->execute([$userId]);
$activeWorkout = $activeStmt->fetch() ?: null;

$tab = (string)($_GET['tab'] ?? ($permissionService->can($userId, 'dashboard.view') ? 'admin' : 'feed'));
if (!in_array($tab, ['feed','checkin','history','leaderboard','notifications','profile','admin'], true)) {
    $tab = 'feed';
}
$period = (string)($_GET['period'] ?? 'week');
if (!in_array($period, ['week','month','all'], true)) {
    $period = 'week';
}

$workouts = [];
if ($tab === 'history') {
    $historyStmt = $db->prepare('SELECT id, workout_date, status, duration_seconds, sport, distance_meters, tracked_seconds FROM workouts WHERE user_id = ? ORDER BY workout_date DESC, id DESC LIMIT 100');
    $historyStmt->execute([$userId]);
    $workouts = $historyStmt->fetchAll();
}

$feedStmt = $db->prepare("SELECT w.id AS workout_id, w.user_id, w.checked_in_at, w.checked_out_at, w.duration_seconds, u.display_name, u.total_points, u.current_streak, p.caption, (SELECT COUNT(*) FROM workout_reactions r WHERE r.workout_id = w.id) AS reaction_count, (SELECT COUNT(*) FROM workout_comments c WHERE c.workout_id = w.id) AS comment_count, (SELECT r2.reaction FROM workout_reactions r2 WHERE r2.workout_id = w.id AND r2.user_id = ?) AS my_reaction FROM workouts w JOIN workout_posts p ON p.workout_id = w.id JOIN users u ON u.id = w.user_id WHERE w.status = 'valid' ORDER BY w.checked_out_at DESC LIMIT 20");
$feedStmt->execute([$userId]);
$feed = $feedStmt->fetchAll();
$activityMembers = [];
$activeTrainingCount = 0;
if ($tab === 'feed') {
    $activityStmt = $db->prepare("SELECT u.id, u.display_name, u.current_streak, w.status, w.checked_in_at, w.checked_out_at FROM users u JOIN workouts w ON w.id = (SELECT MAX(w2.id) FROM workouts w2 WHERE w2.user_id = u.id) WHERE u.id <> ? AND (w.status = 'in_progress' OR (w.status = 'valid' AND w.checked_out_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 7 DAY))) ORDER BY (w.status = 'in_progress') DESC, w.checked_in_at DESC LIMIT 8");
    $activityStmt->execute([$userId]);
    $activityMembers = $activityStmt->fetchAll();
    $activeTrainingCount = count(array_filter($activityMembers, static fn(array $member): bool => $member['status'] === 'in_progress'));
}
$commentsMap = [];
if ($feed) {
    $ids = array_map(static fn(array $post): int => (int)$post['workout_id'], $feed);
    $marks = implode(',', array_fill(0, count($ids), '?'));
    $commentStmt = $db->prepare("SELECT c.id, c.workout_id, c.user_id, c.comment_text, u.display_name FROM workout_comments c JOIN users u ON u.id = c.user_id WHERE c.workout_id IN ($marks) ORDER BY c.created_at DESC");
    $commentStmt->execute($ids);
    foreach ($commentStmt->fetchAll() as $comment) {
        $id = (int)$comment['workout_id'];
        if (count($commentsMap[$id] ?? []) < 3) {
            $commentsMap[$id][] = $comment;
        }
    }
}
foreach ($feed as &$post) {
    $post['comments'] = $commentsMap[(int)$post['workout_id']] ?? [];
}
unset($post);

$notificationsStmt = $db->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 40');
$notificationsStmt->execute([$userId]);
$notifications = $notificationsStmt->fetchAll();
$unreadStmt = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
$unreadStmt->execute([$userId]);
$unreadCount = (int)$unreadStmt->fetchColumn();

$currentPlansStmt = $db->prepare('SELECT * FROM planned_workouts WHERE user_id = ? AND week_start = ? ORDER BY planned_date, planned_time');
$currentPlansStmt->execute([$userId, $currentWeek]);
$plansCurrent = $currentPlansStmt->fetchAll();
$nextPlansStmt = $db->prepare('SELECT * FROM planned_workouts WHERE user_id = ? AND week_start = ? ORDER BY planned_date, planned_time');
$nextPlansStmt->execute([$userId, $nextWeek]);
$plansNext = $nextPlansStmt->fetchAll();
$nextPlanByDate = [];
foreach ($plansNext as $plan) {
    $nextPlanByDate[$plan['planned_date']] = $plan;
}
$nextDates = [];
$baseMonday = new DateTimeImmutable($nextWeek, new DateTimeZone($config['timezone']));
for ($day = 0; $day < 7; $day++) {
    $nextDates[] = $baseMonday->modify('+' . $day . ' days');
}

$healthStmt = $db->prepare('SELECT recorded_at, weight_kg FROM health_metrics WHERE user_id = ? ORDER BY recorded_at DESC LIMIT 8');
$healthStmt->execute([$userId]);
$healthHistory = $healthStmt->fetchAll();
$pointsLedgerStmt = $db->prepare('SELECT points, reason, created_at FROM points_ledger WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 30');
$pointsLedgerStmt->execute([$userId]);
$pointsLedger = $pointsLedgerStmt->fetchAll();

$monthStart = (new DateTimeImmutable('first day of this month', new DateTimeZone($config['timezone'])))->format('Y-m-d');
$db->prepare('INSERT IGNORE INTO monthly_funds (month_start, contribution_amount) VALUES (?, ?)')->execute([$monthStart, sf_settings()['monthly_contribution']]);
$fundStmt = $db->prepare('SELECT * FROM monthly_funds WHERE month_start = ?');
$fundStmt->execute([$monthStart]);
$fund = $fundStmt->fetch();
$fundMemberStmt = $db->prepare('SELECT * FROM fund_members WHERE fund_id = ? AND user_id = ?');
$fundMemberStmt->execute([(int)$fund['id'], $userId]);
$fundMember = $fundMemberStmt->fetch() ?: null;
$fundSummaryStmt = $db->prepare("SELECT COUNT(DISTINCT fm.user_id) AS participants, COUNT(DISTINCT CASE WHEN fm.contribution_status = 'paid' THEN fm.user_id END) AS paid_count, (SELECT COALESCE(SUM(amount), 0) FROM fund_transactions WHERE fund_id = ? AND transaction_type IN ('contribution','penalty')) AS recorded_total FROM fund_members fm WHERE fm.fund_id = ? AND fm.opted_in = 1 GROUP BY fm.fund_id");
$fundSummaryStmt->execute([(int)$fund['id'], (int)$fund['id']]);
$fundSummary = $fundSummaryStmt->fetch() ?: ['participants' => 0, 'paid_count' => 0, 'recorded_total' => 0];
$fundTransactionsStmt = $db->prepare('SELECT ft.*, u.display_name FROM fund_transactions ft JOIN users u ON u.id = ft.user_id WHERE ft.fund_id = ? ORDER BY ft.created_at DESC, ft.id DESC LIMIT 100');
$fundTransactionsStmt->execute([(int)$fund['id']]);
$fundTransactions = $fundTransactionsStmt->fetchAll();
$fundProposalStmt = $db->prepare('SELECT p.user_id, p.amount, u.display_name FROM penalty_proposals p JOIN users u ON u.id = p.user_id WHERE p.fund_id = ? ORDER BY p.amount, p.created_at');
$fundProposalStmt->execute([(int)$fund['id']]);
$fundProposals = $fundProposalStmt->fetchAll();
$fundVoteStmt = $db->prepare('SELECT amount, COUNT(*) AS votes FROM penalty_votes WHERE fund_id = ? GROUP BY amount ORDER BY votes DESC');
$fundVoteStmt->execute([(int)$fund['id']]);
$fundVotes = $fundVoteStmt->fetchAll();
$canAdminDashboard = $permissionService->can($userId, 'dashboard.view');
$canViewMembers = $permissionService->can($userId, 'members.view');
$canManageMembers = $permissionService->can($userId, 'members.manage');
$canChangeMemberRole = $permissionService->can($userId, 'members.change_role');
$canViewMemberHealth = $permissionService->can($userId, 'members.health.view');
$canManageAdminPermissions = $permissionService->can($userId, 'admin.permissions.manage');
$canViewWorkouts = $permissionService->can($userId, 'workouts.view');
$canReviewWorkouts = $permissionService->can($userId, 'workouts.review');
$canViewAppeals = $permissionService->can($userId, 'appeals.view');
$canReviewAppeals = $permissionService->can($userId, 'appeals.review');
$canViewContentReports = $permissionService->can($userId, 'content_reports.view');
$canManageContentReports = $permissionService->can($userId, 'content_reports.manage');
$canModerateContent = $permissionService->can($userId, 'content.moderate');
$canAdjustPoints = $permissionService->can($userId, 'points.adjust');
$canViewFund = $permissionService->can($userId, 'fund.view');
$canManageFund = $permissionService->can($userId, 'fund.manage');
$canViewAnalytics = $permissionService->can($userId, 'analytics.view');
$canViewAudit = $permissionService->can($userId, 'audit.view');
$canViewCron = $permissionService->can($userId, 'cron.view');
$canViewSettings = $permissionService->can($userId, 'settings.view');
$canManageSettings = $permissionService->can($userId, 'settings.manage');
if ($tab === 'admin' && !$canAdminDashboard) {
    http_response_code(403);
    require APP_ROOT . '/app/Views/admin/access-denied.php';
    exit;
}
$settings = sf_settings();
$adminMembers = $adminActionMembers = $adminAccounts = [];
$permissionAssignments = [];
$adminFundTransactions = $adminFundMembers = [];
$pendingWorkouts = $pendingAppeals = $pendingReports = $auditLogs = $adminScores = [];
$cronRuns = [];
$adminStats = ['members' => 0, 'active_members' => 0, 'sessions' => 0, 'points' => 0, 'active_workouts' => 0, 'pending_workouts' => 0, 'pending_appeals' => 0, 'pending_reports' => 0, 'pending_total' => 0, 'goal_members' => 0, 'goals_met' => 0, 'goal_percent' => 0];
$adminWeeklyActivity = [];
$adminRecentWorkouts = [];
$adminReportPeriod = in_array($_GET['admin_period'] ?? '', ['week', 'month', 'all'], true) ? (string)$_GET['admin_period'] : 'week';
$adminReportActivity = $adminReportSummary = [];
$adminMemberStatus = in_array($_GET['member_status'] ?? '', ['active', 'inactive'], true) ? (string)$_GET['member_status'] : 'all';
$selectedMember = null;
$selectedMemberWorkouts = $selectedMemberHealth = $selectedMemberScores = $selectedMemberLedger = [];
if ($tab === 'admin' && $canAdminDashboard) {
    $adminPermissionService = new \App\Services\AdminPermissionService($db);
    if ($canViewMembers) {
        $adminMembers = (new \App\Services\AdminService($db))->members((string)($_GET['q'] ?? ''), $adminMemberStatus);
        $memberStats = $db->query('SELECT COUNT(*) AS members, SUM(is_active = 1) AS active_members, SUM(total_sessions) AS sessions, SUM(total_points) AS points FROM users')->fetch() ?: [];
        foreach (['members', 'active_members', 'sessions', 'points'] as $key) $adminStats[$key] = (int)($memberStats[$key] ?? 0);
        $selectedMemberId = filter_var($_GET['member_id'] ?? null, FILTER_VALIDATE_INT);
        if ($selectedMemberId) {
            $memberLookup = $db->prepare('SELECT id, email, display_name, role, is_active, total_points, current_streak, total_sessions, created_at FROM users WHERE id = ?');
            $memberLookup->execute([(int)$selectedMemberId]);
            $selectedMember = $memberLookup->fetch() ?: null;
            if ($selectedMember && $canViewWorkouts) {
                $detail = $db->prepare('SELECT id, workout_date, duration_seconds, status, checked_in_at, checked_out_at, checkin_image, checkout_image, moderation_reason FROM workouts WHERE user_id = ? ORDER BY workout_date DESC, id DESC LIMIT 50');
                $detail->execute([(int)$selectedMember['id']]);
                $selectedMemberWorkouts = $detail->fetchAll();
            }
            if ($selectedMember && $canViewMemberHealth) {
                $detail = $db->prepare('SELECT height_cm, weight_kg, recorded_at FROM health_metrics WHERE user_id = ? ORDER BY recorded_at DESC LIMIT 30');
                $detail->execute([(int)$selectedMember['id']]);
                $selectedMemberHealth = $detail->fetchAll();
            }
            if ($selectedMember && $canViewAnalytics) {
                $detail = $db->prepare('SELECT week_start, target_sessions, valid_sessions, total_week_points, streak_after FROM weekly_scores WHERE user_id = ? ORDER BY week_start DESC LIMIT 30');
                $detail->execute([(int)$selectedMember['id']]);
                $selectedMemberScores = $detail->fetchAll();
            }
            if ($selectedMember && $canAdjustPoints) {
                $detail = $db->prepare('SELECT points, reason, created_at FROM points_ledger WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 50');
                $detail->execute([(int)$selectedMember['id']]);
                $selectedMemberLedger = $detail->fetchAll();
            }
        }
    }
    if ($canAdjustPoints || $canManageFund) {
        $memberColumns = $canAdjustPoints ? 'id, display_name, total_points' : 'id, display_name, 0 AS total_points';
        $adminActionMembers = $db->query("SELECT $memberColumns FROM users WHERE is_active = 1 ORDER BY display_name LIMIT 250")->fetchAll();
    }
    if ($canManageAdminPermissions) {
        $adminAccounts = $db->query("SELECT id, display_name, email, is_active FROM users WHERE role = 'admin' ORDER BY display_name")->fetchAll();
        $permissionAssignments = $adminPermissionService->assignmentsForUsers(array_column($adminAccounts, 'id'));
    }
    if ($canViewWorkouts) {
        $pendingWorkouts = $db->query("SELECT w.id, w.user_id, w.workout_date, w.duration_seconds, w.status, w.checkin_image, w.checkout_image, u.display_name FROM workouts w JOIN users u ON u.id = w.user_id WHERE w.status IN ('invalid','abandoned') AND w.moderated_at IS NULL ORDER BY w.created_at DESC LIMIT 50")->fetchAll();
        $adminStats['active_workouts'] = (int)$db->query("SELECT COUNT(*) FROM workouts WHERE status = 'in_progress'")->fetchColumn();
        $adminStats['pending_workouts'] = (int)$db->query("SELECT COUNT(*) FROM workouts WHERE status IN ('invalid','abandoned') AND moderated_at IS NULL")->fetchColumn();
        $adminRecentWorkouts = $db->query("SELECT w.id, w.status, w.checked_in_at, w.checked_out_at, w.duration_seconds, u.display_name FROM workouts w JOIN users u ON u.id = w.user_id WHERE w.status IN ('in_progress','valid') ORDER BY COALESCE(w.checked_out_at, w.checked_in_at) DESC LIMIT 5")->fetchAll();
    }
    if ($canViewAppeals) {
        $pendingAppeals = $db->query("SELECT a.id, a.user_id, a.week_start, a.reason, a.proof_path, a.created_at, u.display_name, (SELECT COUNT(*) FROM appeal_votes v WHERE v.appeal_id = a.id AND v.vote = 'yes') AS yes_votes, (SELECT COUNT(*) FROM appeal_votes v WHERE v.appeal_id = a.id AND v.vote = 'no') AS no_votes FROM appeals a JOIN users u ON u.id = a.user_id WHERE a.status = 'pending' ORDER BY a.created_at LIMIT 50")->fetchAll();
        $adminStats['pending_appeals'] = (int)$db->query("SELECT COUNT(*) FROM appeals WHERE status = 'pending'")->fetchColumn();
    }
    if ($canViewContentReports) {
        $pendingReports = $db->query("SELECT r.*, u.display_name AS reporter_name, CASE WHEN r.content_type = 'post' THEN p.caption ELSE c.comment_text END AS reported_text, CASE WHEN r.content_type = 'post' THEN r.content_id ELSE c.workout_id END AS related_workout_id FROM content_reports r JOIN users u ON u.id = r.reporter_id LEFT JOIN workout_posts p ON r.content_type = 'post' AND p.workout_id = r.content_id LEFT JOIN workout_comments c ON r.content_type = 'comment' AND c.id = r.content_id WHERE r.status = 'pending' ORDER BY r.created_at LIMIT 50")->fetchAll();
        $adminStats['pending_reports'] = (int)$db->query("SELECT COUNT(*) FROM content_reports WHERE status = 'pending'")->fetchColumn();
    }
    $adminStats['pending_total'] = $adminStats['pending_workouts'] + $adminStats['pending_appeals'] + $adminStats['pending_reports'];
    if ($canViewAnalytics) {
        $adminScores = $db->query('SELECT s.*, u.display_name FROM weekly_scores s JOIN users u ON u.id = s.user_id ORDER BY s.week_start DESC, s.total_week_points DESC LIMIT 50')->fetchAll();
        $goalStatsStmt = $db->prepare("SELECT COUNT(*) AS members, SUM(CASE WHEN COALESCE(progress.sessions, 0) >= COALESCE(g.target_sessions, 3) THEN 1 ELSE 0 END) AS goals_met FROM users u LEFT JOIN weekly_goals g ON g.user_id = u.id AND g.week_start = ? LEFT JOIN (SELECT user_id, week_start, COUNT(DISTINCT workout_date) AS sessions FROM workouts WHERE week_start = ? AND status = 'valid' GROUP BY user_id, week_start) progress ON progress.user_id = u.id AND progress.week_start = ? WHERE u.role = 'member' AND u.is_active = 1");
        $goalStatsStmt->execute([$currentWeek, $currentWeek, $currentWeek]);
        $goalStats = $goalStatsStmt->fetch() ?: ['members' => 0, 'goals_met' => 0];
        $adminStats['goal_members'] = (int)$goalStats['members'];
        $adminStats['goals_met'] = (int)$goalStats['goals_met'];
        $adminStats['goal_percent'] = $adminStats['goal_members'] ? (int)round($adminStats['goals_met'] / $adminStats['goal_members'] * 100) : 0;
        $weeklyActivityStmt = $db->prepare("SELECT workout_date, COUNT(*) AS sessions FROM workouts WHERE week_start = ? AND status = 'valid' GROUP BY workout_date");
        $weeklyActivityStmt->execute([$currentWeek]);
        $dailySessions = [];
        foreach ($weeklyActivityStmt->fetchAll() as $dailyActivity) $dailySessions[$dailyActivity['workout_date']] = (int)$dailyActivity['sessions'];
        $weekdayLabels = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
        for ($day = 0; $day < 7; $day++) {
            $dateKey = date('Y-m-d', strtotime($currentWeek . ' +' . $day . ' days'));
            $adminWeeklyActivity[] = ['date' => $dateKey, 'label' => $weekdayLabels[$day], 'sessions' => $dailySessions[$dateKey] ?? 0];
        }
        $reportStart = match ($adminReportPeriod) { 'week' => $currentWeek, 'month' => $monthStart, default => '1000-01-01' };
        $reportEnd = match ($adminReportPeriod) { 'week' => date('Y-m-d', strtotime($currentWeek . ' +7 days')), 'month' => date('Y-m-d', strtotime($monthStart . ' +1 month')), default => '9999-12-31' };
        $reportQuery = $db->prepare("SELECT COUNT(*) AS sessions, COUNT(DISTINCT w.user_id) AS members, COALESCE(SUM(w.duration_seconds), 0) AS seconds, COALESCE(MAX(u.current_streak), 0) AS longest_streak FROM workouts w JOIN users u ON u.id = w.user_id WHERE w.status = 'valid' AND w.workout_date >= ? AND w.workout_date < ?");
        $reportQuery->execute([$reportStart, $reportEnd]);
        $adminReportSummary = $reportQuery->fetch();
        if ($adminReportPeriod === 'week') {
            $adminReportActivity = $adminWeeklyActivity;
        } elseif ($adminReportPeriod === 'month') {
            $reportQuery = $db->prepare("SELECT workout_date, COUNT(*) AS sessions FROM workouts WHERE status = 'valid' AND workout_date >= ? AND workout_date < ? GROUP BY workout_date");
            $reportQuery->execute([$reportStart, $reportEnd]);
            $reportDays = array_column($reportQuery->fetchAll(), 'sessions', 'workout_date');
            for ($day = 0; $day < (int)date('t', strtotime($monthStart)); $day++) {
                $dateKey = date('Y-m-d', strtotime($monthStart . ' +' . $day . ' days'));
                $adminReportActivity[] = ['label' => (string)($day + 1), 'sessions' => (int)($reportDays[$dateKey] ?? 0)];
            }
        } else {
            $adminReportActivity = array_reverse($db->query("SELECT DATE_FORMAT(workout_date, '%Y-%m') AS label, COUNT(*) AS sessions FROM workouts WHERE status = 'valid' GROUP BY label ORDER BY label DESC LIMIT 12")->fetchAll());
        }
    }
    if ($canViewAudit) $auditLogs = $db->query('SELECT a.*, u.display_name FROM audit_logs a LEFT JOIN users u ON u.id = a.actor_id ORDER BY a.created_at DESC, a.id DESC LIMIT 50')->fetchAll();
    if ($canViewCron) $cronRuns = $db->query('SELECT task_name, status, summary, started_at, finished_at FROM cron_runs ORDER BY started_at DESC, id DESC LIMIT 20')->fetchAll();
    if ($canViewFund) {
        $fundRows = $db->prepare('SELECT ft.transaction_type, ft.amount, ft.note, ft.created_at, u.display_name FROM fund_transactions ft JOIN users u ON u.id = ft.user_id WHERE ft.fund_id = ? ORDER BY ft.created_at DESC, ft.id DESC LIMIT 100');
        $fundRows->execute([(int)$fund['id']]);
        $adminFundTransactions = $fundRows->fetchAll();
        $memberRows = $db->prepare('SELECT fm.user_id, fm.contribution_status, u.display_name FROM fund_members fm JOIN users u ON u.id = fm.user_id WHERE fm.fund_id = ? AND fm.opted_in = 1 ORDER BY u.display_name');
        $memberRows->execute([(int)$fund['id']]);
        $adminFundMembers = $memberRows->fetchAll();
        $adminStats['pending_fund'] = count(array_filter($adminFundMembers, static fn(array $member): bool => $member['contribution_status'] === 'unpaid'));
        $adminStats['pending_total'] += $adminStats['pending_fund'];
    }
}
$fundMembers = [];
if ($tab !== 'admin' && $canManageFund) {
    $fundMembersStmt = $db->prepare('SELECT fm.user_id, fm.contribution_status, u.display_name FROM fund_members fm JOIN users u ON u.id = fm.user_id WHERE fm.fund_id = ? AND fm.opted_in = 1 ORDER BY u.display_name');
    $fundMembersStmt->execute([(int)$fund['id']]);
    $fundMembers = $fundMembersStmt->fetchAll();
}

if ($period === 'week') {
    $rankStmt = $db->prepare("SELECT u.id, u.display_name, u.total_points, u.current_streak, u.total_sessions, u.total_minutes, COALESCE(g.target_sessions, 3) AS target_sessions, COUNT(DISTINCT CASE WHEN w.status = 'valid' THEN w.workout_date END) AS valid_sessions FROM users u LEFT JOIN weekly_goals g ON g.user_id = u.id AND g.week_start = ? LEFT JOIN workouts w ON w.user_id = u.id AND w.week_start = ? GROUP BY u.id, u.display_name, u.total_points, u.current_streak, u.total_sessions, u.total_minutes, g.target_sessions");
    $rankStmt->execute([$currentWeek, $currentWeek]);
    $leaderboard = array_map(static function (array $row) use ($db, $currentWeek): array {
        $target = sf_member_week_target($db, (int)$row['id'], $currentWeek, (int)$row['target_sessions']);
        $weekly = $target > 0 ? sf_week_score($target, (int)$row['valid_sessions'], (int)$row['current_streak']) : ['total' => 0];
        return ['id' => (int)$row['id'], 'display_name' => $row['display_name'], 'points' => $weekly['total'], 'streak' => (int)$row['current_streak'], 'sessions' => (int)$row['valid_sessions'], 'target' => (int)$row['target_sessions'], 'minutes' => (int)$row['total_minutes']];
    }, $rankStmt->fetchAll());
} elseif ($period === 'month') {
    $monthStart = (new DateTimeImmutable('first day of this month', new DateTimeZone($config['timezone'])))->format('Y-m-d');
    $nextMonth = (new DateTimeImmutable($monthStart, new DateTimeZone($config['timezone'])))->modify('+1 month')->format('Y-m-d');
    $rankStmt = $db->prepare('SELECT u.id, u.display_name, u.current_streak, u.total_sessions, u.total_minutes, COALESCE(SUM(s.total_week_points), 0) AS points FROM users u LEFT JOIN weekly_scores s ON s.user_id = u.id AND s.week_start >= ? AND s.week_start < ? GROUP BY u.id, u.display_name, u.current_streak, u.total_sessions, u.total_minutes');
    $rankStmt->execute([$monthStart, $nextMonth]);
    $leaderboard = array_map(static fn(array $row): array => ['id' => (int)$row['id'], 'display_name' => $row['display_name'], 'points' => (int)$row['points'], 'streak' => (int)$row['current_streak'], 'sessions' => (int)$row['total_sessions'], 'minutes' => (int)$row['total_minutes']], $rankStmt->fetchAll());
} else {
    $rankStmt = $db->query('SELECT id, display_name, total_points AS points, current_streak, total_sessions, total_minutes FROM users');
    $leaderboard = array_map(static fn(array $row): array => ['id' => (int)$row['id'], 'display_name' => $row['display_name'], 'points' => (int)$row['points'], 'streak' => (int)$row['current_streak'], 'sessions' => (int)$row['total_sessions'], 'minutes' => (int)$row['total_minutes']], $rankStmt->fetchAll());
}
usort($leaderboard, static function (array $a, array $b): int {
    return ($b['points'] <=> $a['points']) ?: ($b['sessions'] <=> $a['sessions']) ?: ((int)($b['target'] ?? 0) <=> (int)($a['target'] ?? 0)) ?: ($b['minutes'] <=> $a['minutes']);
});
$leaderboard = array_slice($leaderboard, 0, 50);

$appealStmt = $db->prepare("SELECT a.id, a.user_id, a.reason, a.proof_path, a.created_at, (SELECT COUNT(*) FROM appeal_votes v WHERE v.appeal_id = a.id AND v.vote = 'yes') AS yes_votes, (SELECT COUNT(*) FROM appeal_votes v WHERE v.appeal_id = a.id AND v.vote = 'no') AS no_votes, (SELECT v.vote FROM appeal_votes v WHERE v.appeal_id = a.id AND v.voter_id = ?) AS my_vote FROM appeals a WHERE a.status = 'pending' ORDER BY a.created_at DESC LIMIT 20");
$appealStmt->execute([$userId]);
$appeals = $appealStmt->fetchAll();

if ($tab === 'admin' && $canAdminDashboard) {
    require APP_ROOT . '/app/Views/admin/index.php';
} else {
    require APP_ROOT . '/app/Views/dashboard/index.php';
}
