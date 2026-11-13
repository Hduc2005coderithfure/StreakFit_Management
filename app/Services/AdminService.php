<?php
declare(strict_types=1);

namespace App\Services;

use DomainException;
use PDO;

final class AdminService
{
    public function __construct(private readonly PDO $db) {}

    public function members(string $search = '', string $status = 'all'): array
    {
        $search = trim($search);
        $active = match ($status) { 'active' => 1, 'inactive' => 0, default => -1 };
        $stmt = $this->db->prepare('SELECT id, email, display_name, role, is_active, total_points, current_streak, total_sessions, created_at FROM users WHERE (? = \'\' OR email LIKE ? OR display_name LIKE ?) AND (? = -1 OR is_active = ?) ORDER BY role DESC, display_name LIMIT 100');
        $like = '%' . $search . '%';
        $stmt->execute([$search, $like, $like, $active, $active]);
        return $stmt->fetchAll();
    }

    public function setMember(int $adminId, int $userId, string $role, bool $active, bool $canManageMembers, bool $canChangeRole): void
    {
        if (!$canManageMembers && !$canChangeRole) throw new DomainException('Tài khoản này không có quyền quản lý thành viên.');
        $this->db->beginTransaction();
        try {
            $target = $this->db->prepare('SELECT role, is_active FROM users WHERE id = ? FOR UPDATE');
            $target->execute([$userId]);
            $old = $target->fetch();
            if (!$old) throw new DomainException('Không tìm thấy thành viên.');
            $role = $canChangeRole ? $role : (string)$old['role'];
            $active = $canManageMembers ? $active : (bool)$old['is_active'];
            if (!in_array($role, ['member', 'admin'], true)) throw new DomainException('Vai trò không hợp lệ.');
            if ($adminId === $userId && (!$active || $role !== 'admin')) throw new DomainException('Không thể tự khóa hoặc tự hạ quyền tài khoản quản trị đang đăng nhập.');
            if ($old['role'] === 'admin' && (!(bool)$active || $role !== 'admin')) {
                $admins = $this->db->query("SELECT id FROM users WHERE role = 'admin' AND is_active = 1 FOR UPDATE");
                if (count($admins->fetchAll(PDO::FETCH_COLUMN)) <= 1) throw new DomainException('Hệ thống phải luôn có ít nhất một quản trị viên đang hoạt động.');
                $permissionManagers = $this->db->prepare("SELECT COUNT(DISTINCT u.id) FROM users u JOIN user_admin_permissions p ON p.user_id = u.id WHERE u.role = 'admin' AND u.is_active = 1 AND p.permission_key = 'admin.permissions.manage'");
                $permissionManagers->execute();
                $targetHasPermissionManagement = $this->db->prepare("SELECT 1 FROM user_admin_permissions WHERE user_id = ? AND permission_key = 'admin.permissions.manage'");
                $targetHasPermissionManagement->execute([$userId]);
                if ($targetHasPermissionManagement->fetchColumn() && (int)$permissionManagers->fetchColumn() <= 1) throw new DomainException('Phải luôn có ít nhất một Admin hoạt động có quyền quản lý phân quyền.');
            }
            $this->db->prepare('UPDATE users SET role = ?, is_active = ? WHERE id = ?')->execute([$role, $active ? 1 : 0, $userId]);
            if ($old['role'] !== $role && $role === 'member') {
                $this->db->prepare('DELETE FROM user_admin_permissions WHERE user_id = ?')->execute([$userId]);
            } elseif ($old['role'] !== $role && $role === 'admin') {
                $this->db->prepare("INSERT IGNORE INTO user_admin_permissions (user_id, permission_key, granted_by) VALUES (?, 'dashboard.view', ?)")->execute([$userId, $adminId]);
            }
            (new AuditLogService($this->db))->record($adminId, 'member_updated', 'user', $userId, ['old_role' => $old['role'], 'role' => $role, 'active' => $active]);
            $this->db->commit();
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    public function reviewWorkout(int $adminId, int $workoutId, string $decision, string $reason, int $minimumSeconds): void
    {
        if (!in_array($decision, ['approve', 'reject'], true)) throw new DomainException('Quyết định kiểm duyệt không hợp lệ.');
        $reason = trim($reason);
        if (mb_strlen($reason) < 5 || mb_strlen($reason) > 500) throw new DomainException('Lý do kiểm duyệt cần từ 5 đến 500 ký tự.');
        $this->db->beginTransaction();
        try {
            $ownerLookup = $this->db->prepare('SELECT user_id FROM workouts WHERE id = ?');
            $ownerLookup->execute([$workoutId]);
            $ownerId = $ownerLookup->fetchColumn();
            if ($ownerId === false) throw new DomainException('Không tìm thấy buổi tập đã kết thúc để kiểm duyệt.');
            $this->db->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE')->execute([(int)$ownerId]);
            $stmt = $this->db->prepare('SELECT * FROM workouts WHERE id = ? FOR UPDATE');
            $stmt->execute([$workoutId]);
            $workout = $stmt->fetch();
            if (!$workout || !in_array($workout['status'], ['valid', 'invalid', 'abandoned'], true)) throw new DomainException('Không tìm thấy buổi tập đã kết thúc để kiểm duyệt.');
            $settled = $this->db->prepare('SELECT id FROM weekly_scores WHERE user_id = ? AND week_start = ? FOR UPDATE');
            $settled->execute([(int)$workout['user_id'], $workout['week_start']]);
            if ($settled->fetchColumn()) throw new DomainException('Tuần điểm đã chốt; hãy ghi điều chỉnh điểm riêng thay vì đổi lịch sử buổi tập.');
            $isValid = $workout['status'] === 'valid';
            if ($decision === 'approve' && ((int)$workout['duration_seconds'] < $minimumSeconds || !$workout['checked_out_at'] || !$workout['checkin_image'] || !$workout['checkout_image'])) throw new DomainException('Chỉ duyệt buổi tập đủ thời lượng, có giờ kết thúc và đủ hai ảnh minh chứng.');
            if ($decision === 'approve') {
                $duplicate = $this->db->prepare("SELECT id FROM workouts WHERE user_id = ? AND workout_date = ? AND status = 'valid' AND id <> ? LIMIT 1 FOR UPDATE");
                $duplicate->execute([(int)$workout['user_id'], $workout['workout_date'], $workoutId]);
                if ($duplicate->fetchColumn()) throw new DomainException('Thành viên đã có buổi hợp lệ trong ngày này.');
                $this->db->prepare("UPDATE workouts SET status = 'valid', moderation_reason = ?, moderated_by = ?, moderated_at = UTC_TIMESTAMP() WHERE id = ?")->execute([$reason, $adminId, $workoutId]);
                $post = $this->db->prepare('INSERT IGNORE INTO workout_posts (workout_id, user_id, caption) VALUES (?, ?, ?)');
                $post->execute([$workoutId, (int)$workout['user_id'], 'Buổi tập được quản trị viên xác nhận.']);
                if (!$isValid) $this->db->prepare('UPDATE users SET total_sessions = total_sessions + 1, total_minutes = total_minutes + ? WHERE id = ?')->execute([(int)ceil((int)$workout['duration_seconds'] / 60), (int)$workout['user_id']]);
            } else {
                $this->db->prepare("UPDATE workouts SET status = 'invalid', moderation_reason = ?, moderated_by = ?, moderated_at = UTC_TIMESTAMP() WHERE id = ?")->execute([$reason, $adminId, $workoutId]);
                if ($isValid) {
                    $this->db->prepare('DELETE FROM workout_posts WHERE workout_id = ?')->execute([$workoutId]);
                    $this->db->prepare('UPDATE users SET total_sessions = GREATEST(0, total_sessions - 1), total_minutes = GREATEST(0, total_minutes - ?) WHERE id = ?')->execute([(int)ceil((int)$workout['duration_seconds'] / 60), (int)$workout['user_id']]);
                }
            }
            (new AuditLogService($this->db))->record($adminId, 'workout_' . $decision, 'workout', $workoutId, ['reason' => $reason, 'old_status' => $workout['status']]);
            $this->notify((int)$workout['user_id'], $adminId, 'workout', 'Buổi tập đã được kiểm duyệt', $reason);
            $this->db->commit();
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    public function reviewAppeal(int $adminId, int $appealId, string $decision, string $reason): void
    {
        if (!in_array($decision, ['accepted', 'rejected'], true)) throw new DomainException('Quyết định kháng cáo không hợp lệ.');
        $reason = trim($reason);
        if (mb_strlen($reason) < 5 || mb_strlen($reason) > 1000) throw new DomainException('Lý do xử lý cần từ 5 đến 1.000 ký tự.');
        $this->db->beginTransaction();
        try {
            $ownerLookup = $this->db->prepare('SELECT user_id FROM appeals WHERE id = ?');
            $ownerLookup->execute([$appealId]);
            $ownerId = $ownerLookup->fetchColumn();
            if ($ownerId === false) throw new DomainException('Kháng cáo không còn chờ xử lý.');
            $this->db->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE')->execute([(int)$ownerId]);
            $stmt = $this->db->prepare("SELECT user_id FROM appeals WHERE id = ? AND status = 'pending' FOR UPDATE");
            $stmt->execute([$appealId]);
            if ($stmt->fetchColumn() === false) throw new DomainException('Kháng cáo không còn chờ xử lý.');
            $this->db->prepare("UPDATE appeals SET status = ?, review_note = ?, reviewed_by = ?, resolved_at = UTC_TIMESTAMP() WHERE id = ?")->execute([$decision, $reason, $adminId, $appealId]);
            (new AuditLogService($this->db))->record($adminId, 'appeal_' . $decision, 'appeal', $appealId, ['reason' => $reason]);
            $this->notify((int)$ownerId, $adminId, 'appeal', 'Kháng cáo đã được xử lý', $reason);
            $this->db->commit();
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    public function adjustPoints(int $adminId, int $userId, int $change, string $reason): void
    {
        $reason = trim($reason);
        if ($change === 0 || abs($change) > 100000 || mb_strlen($reason) < 5 || mb_strlen($reason) > 180) throw new DomainException('Điều chỉnh cần khác 0, tối đa 100.000 điểm và có lý do từ 5 đến 180 ký tự.');
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT total_points FROM users WHERE id = ? FOR UPDATE');
            $stmt->execute([$userId]);
            $points = $stmt->fetchColumn();
            if ($points === false) throw new DomainException('Không tìm thấy thành viên.');
            $actual = max(0, (int)$points + $change) - (int)$points;
            if ($actual === 0) throw new DomainException('Điểm không thể thấp hơn 0.');
            $this->db->prepare('UPDATE users SET total_points = total_points + ? WHERE id = ?')->execute([$actual, $userId]);
            $this->db->prepare('INSERT INTO points_ledger (user_id, week_start, points, reason) VALUES (?, NULL, ?, ?)')->execute([$userId, $actual, 'Điều chỉnh bởi quản trị viên: ' . $reason]);
            (new AuditLogService($this->db))->record($adminId, 'points_adjusted', 'user', $userId, ['points' => $actual, 'reason' => $reason]);
            $this->db->commit();
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    public function recordFundTransaction(int $adminId, int $fundId, int $userId, string $type, int $amount, string $note): void
    {
        if (!in_array($type, ['contribution', 'penalty', 'reward'], true) || $amount < 1 || $amount > 100000000 || mb_strlen(trim($note)) < 3 || mb_strlen($note) > 255) throw new DomainException('Giao dịch cần loại, số tiền hợp lệ và ghi chú từ 3 đến 255 ký tự.');
        $receipt=null;
        $bankRequestId=null;
        $this->db->beginTransaction();
        try {
            $member = $this->db->prepare('SELECT id FROM fund_members WHERE fund_id = ? AND user_id = ? FOR UPDATE');
            $member->execute([$fundId, $userId]);
            if (!$member->fetchColumn()) throw new DomainException('Thành viên chưa tham gia quỹ tháng này.');
            if ($type === 'contribution') {
                $bankRequest=$this->db->prepare("SELECT id FROM bank_transfer_requests WHERE fund_id=? AND user_id=? AND status='pending' LIMIT 1 FOR UPDATE");
                $bankRequest->execute([$fundId,$userId]);
                $bankRequestId=$bankRequest->fetchColumn() ?: null;
                $paid = $this->db->prepare("SELECT contribution_status, opted_in FROM fund_members WHERE fund_id = ? AND user_id = ?");
                $paid->execute([$fundId, $userId]);
                $membership = $paid->fetch();
                if (!$membership || !(int)$membership['opted_in']) throw new DomainException('Thành viên chưa tham gia quỹ tháng này.');
                if ($membership['contribution_status'] === 'paid') throw new DomainException('Khoản đóng góp này đã được xác nhận.');
            }
            $this->db->prepare('INSERT INTO fund_transactions (fund_id, user_id, transaction_type, amount, status, note) VALUES (?, ?, ?, ?, ?, ?)')->execute([$fundId, $userId, $type, $amount, $type === 'contribution' ? 'paid' : 'recorded', trim($note)]);
            if ($type === 'contribution') {
                $this->db->prepare("UPDATE fund_members SET contribution_status = 'paid', opted_in = 1 WHERE fund_id = ? AND user_id = ?")->execute([$fundId, $userId]);
                if ($bankRequestId !== null) $this->db->prepare("UPDATE bank_transfer_requests SET status='paid', verified_by=?, verified_at=UTC_TIMESTAMP() WHERE id=? AND status='pending'")->execute([$adminId, $bankRequestId]);
                $this->db->prepare("INSERT INTO notifications (user_id,type,title,body) VALUES (?,'fund_payment','Đã xác nhận tham gia quỹ',?)")->execute([$userId, 'Admin đã xác nhận bạn tham gia và ghi nhận khoản đóng quỹ ' . number_format($amount) . 'đ.']);
                $userQuery=$this->db->prepare('SELECT email,display_name FROM users WHERE id=?');
                $userQuery->execute([$userId]);
                $receipt=$userQuery->fetch() ?: null;
            }
            (new AuditLogService($this->db))->record($adminId, 'fund_transaction_recorded', 'fund', $fundId, ['user_id' => $userId, 'type' => $type, 'amount' => $amount]);
            $this->db->commit();
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
        if ($type === 'contribution' && $receipt) {
            $subject='StreakFit: Đã xác nhận tham gia quỹ';
            $body="Xin chào {$receipt['display_name']},\n\nAdmin đã xác nhận bạn tham gia và hoàn tất khoản đóng quỹ ".number_format($amount)."đ.\n\nCảm ơn bạn!";
            @mail((string)$receipt['email'],$subject,$body,"Content-Type: text/plain; charset=UTF-8\r\n");
        }
    }

    public function resolveReport(int $adminId, int $reportId, string $decision, string $note): void
    {
        if (!in_array($decision, ['resolved', 'dismissed'], true) || mb_strlen(trim($note)) < 3 || mb_strlen($note) > 500) throw new DomainException('Trạng thái hoặc ghi chú xử lý báo cáo không hợp lệ.');
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("UPDATE content_reports SET status = ?, resolved_by = ?, resolution_note = ?, resolved_at = UTC_TIMESTAMP() WHERE id = ? AND status = 'pending'");
            $stmt->execute([$decision, $adminId, trim($note), $reportId]);
            if ($stmt->rowCount() !== 1) throw new DomainException('Báo cáo không còn chờ xử lý.');
            (new AuditLogService($this->db))->record($adminId, 'report_' . $decision, 'content_report', $reportId, ['note' => $note]);
            $this->db->commit();
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    public function removeReportedContent(int $adminId, int $reportId, string $note): void
    {
        $note = trim($note);
        if (mb_strlen($note) < 3 || mb_strlen($note) > 500) throw new DomainException('Ghi chú xóa nội dung cần từ 3 đến 500 ký tự.');
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT content_type, content_id FROM content_reports WHERE id = ? AND status = 'pending' FOR UPDATE");
            $stmt->execute([$reportId]);
            $report = $stmt->fetch();
            if (!$report) throw new DomainException('Báo cáo không còn chờ xử lý.');
            $table = $report['content_type'] === 'post' ? 'workout_posts' : 'workout_comments';
            $column = $report['content_type'] === 'post' ? 'workout_id' : 'id';
            if ($report['content_type'] === 'post') {
                $this->db->prepare('DELETE FROM workout_comments WHERE workout_id = ?')->execute([(int)$report['content_id']]);
                $this->db->prepare('DELETE FROM workout_reactions WHERE workout_id = ?')->execute([(int)$report['content_id']]);
            }
            $delete = $this->db->prepare("DELETE FROM $table WHERE $column = ?");
            $delete->execute([(int)$report['content_id']]);
            if ($delete->rowCount() !== 1) throw new DomainException('Nội dung đã bị xóa hoặc không còn tồn tại.');
            $this->db->prepare("UPDATE content_reports SET status = 'resolved', resolved_by = ?, resolution_note = ?, resolved_at = UTC_TIMESTAMP() WHERE content_type = ? AND content_id = ? AND status = 'pending'")->execute([$adminId, $note, $report['content_type'], (int)$report['content_id']]);
            (new AuditLogService($this->db))->record($adminId, 'reported_content_removed', $report['content_type'], (int)$report['content_id'], ['report_id' => $reportId, 'note' => $note]);
            $this->db->commit();
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    private function notify(int $userId, int $adminId, string $type, string $title, string $body): void
    {
        $this->db->prepare('INSERT INTO notifications (user_id, actor_id, type, title, body) VALUES (?, ?, ?, ?, ?)')->execute([$userId, $adminId, $type, $title, mb_substr($body, 0, 500)]);
    }
}
