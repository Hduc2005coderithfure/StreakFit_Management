<?php
declare(strict_types=1);

namespace App\Services;

use DomainException;
use PDO;

final class AdminPermissionService
{
    private array $cache = [];

    private const CATALOG = [
        'groups.manage' => ['group' => 'Nhóm thể thao', 'label' => 'Quản lý nhóm thể thao', 'description' => 'Sửa thông tin, đóng và mở lại nhóm thể thao.'],
        'dashboard.view' => ['group' => 'Bảng điều khiển', 'label' => 'Xem tổng quan', 'description' => 'Mở trang Admin và các chỉ số tổng quan.'],
        'members.view' => ['group' => 'Thành viên', 'label' => 'Xem danh sách thành viên', 'description' => 'Tìm kiếm và xem thông tin tài khoản, điểm, streak.'],
        'members.manage' => ['group' => 'Thành viên', 'label' => 'Khóa hoặc mở tài khoản', 'description' => 'Thay đổi trạng thái hoạt động của tài khoản.'],
        'members.change_role' => ['group' => 'Thành viên', 'label' => 'Đổi vai trò Member/Admin', 'description' => 'Nâng hoặc hạ vai trò cơ bản của tài khoản.'],
        'members.health.view' => ['group' => 'Thành viên', 'label' => 'Xem dữ liệu sức khỏe', 'description' => 'Xem lịch sử cân nặng và chỉ số sức khỏe riêng tư.'],
        'admin.permissions.manage' => ['group' => 'Phân quyền', 'label' => 'Cấp và thu hồi quyền Admin', 'description' => 'Chọn chính xác các quyền chức năng cho tài khoản Admin.'],
        'workouts.view' => ['group' => 'Buổi tập', 'label' => 'Xem buổi tập', 'description' => 'Xem danh sách buổi tập và ảnh minh chứng.'],
        'workouts.review' => ['group' => 'Buổi tập', 'label' => 'Duyệt buổi tập', 'description' => 'Chấp nhận hoặc từ chối buổi tập cần kiểm duyệt.'],
        'appeals.view' => ['group' => 'Kháng cáo', 'label' => 'Xem kháng cáo', 'description' => 'Xem lý do và ảnh minh chứng kháng cáo.'],
        'appeals.review' => ['group' => 'Kháng cáo', 'label' => 'Quyết định kháng cáo', 'description' => 'Chấp nhận hoặc từ chối kháng cáo.'],
        'content_reports.view' => ['group' => 'Báo cáo nội dung', 'label' => 'Xem báo cáo nội dung', 'description' => 'Xem bài viết, bình luận và lý do bị báo cáo.'],
        'content_reports.manage' => ['group' => 'Báo cáo nội dung', 'label' => 'Xử lý báo cáo nội dung', 'description' => 'Đánh dấu báo cáo, bác báo cáo hoặc gỡ nội dung.'],
        'content.moderate' => ['group' => 'Bài viết', 'label' => 'Sửa hoặc gỡ nội dung', 'description' => 'Sửa hoặc xóa bài viết và bình luận của thành viên khác.'],
        'points.adjust' => ['group' => 'Điểm', 'label' => 'Điều chỉnh điểm', 'description' => 'Cộng hoặc trừ điểm và ghi lý do vào sổ điểm.'],
        'fund.view' => ['group' => 'Quỹ kỷ luật', 'label' => 'Xem sổ quỹ', 'description' => 'Xem thành viên tham gia và các khoản đã ghi nhận.'],
        'fund.manage' => ['group' => 'Quỹ kỷ luật', 'label' => 'Quản lý quỹ', 'description' => 'Xác nhận đóng góp, ghi giao dịch và chốt biểu quyết.'],
        'analytics.view' => ['group' => 'Báo cáo', 'label' => 'Xem thống kê', 'description' => 'Xem biểu đồ tập luyện, tỷ lệ mục tiêu và điểm tuần.'],
        'audit.view' => ['group' => 'Lịch sử hệ thống', 'label' => 'Xem audit log', 'description' => 'Xem lịch sử thao tác quản trị.'],
        'cron.view' => ['group' => 'Lịch sử hệ thống', 'label' => 'Xem lịch sử tác vụ', 'description' => 'Xem trạng thái và lỗi các tác vụ Cron.'],
        'settings.view' => ['group' => 'Cài đặt', 'label' => 'Xem cấu hình', 'description' => 'Xem cấu hình nghiệp vụ hiện hành.'],
        'settings.manage' => ['group' => 'Cài đặt', 'label' => 'Thay đổi cấu hình', 'description' => 'Lưu các thông số ảnh hưởng đến hoạt động hệ thống.'],
    ];

    public function __construct(private readonly PDO $db) {}

    public static function catalog(): array
    {
        return self::CATALOG;
    }

    public static function groups(): array
    {
        $groups = [];
        foreach (self::CATALOG as $key => $details) {
            $groups[$details['group']][$key] = $details;
        }
        return $groups;
    }

    public function permissionsForUser(int $userId): array
    {
        if (isset($this->cache[$userId])) return $this->cache[$userId];
        $stmt = $this->db->prepare("SELECT p.permission_key FROM users u JOIN user_admin_permissions up ON up.user_id = u.id JOIN admin_permissions p ON p.permission_key = up.permission_key WHERE u.id = ? AND u.role = 'admin' AND u.is_active = 1");
        $stmt->execute([$userId]);
        $this->cache[$userId] = array_fill_keys($stmt->fetchAll(PDO::FETCH_COLUMN), true);
        return $this->cache[$userId];
    }

    public function can(int $userId, string $permission): bool
    {
        return isset($this->permissionsForUser($userId)[$permission]);
    }

    public function require(int $userId, string $permission): void
    {
        if (!$this->can($userId, $permission)) {
            throw new DomainException('Tài khoản này chưa được cấp quyền: ' . $permission . '.');
        }
    }

    public function grantAllToUser(int $userId): void
    {
        $stmt = $this->db->prepare('INSERT IGNORE INTO user_admin_permissions (user_id, permission_key, granted_by) SELECT ?, permission_key, ? FROM admin_permissions');
        $stmt->execute([$userId, $userId]);
        unset($this->cache[$userId]);
    }

    public function assignmentsForUsers(array $userIds): array
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds), static fn(int $id): bool => $id > 0)));
        if (!$userIds) return [];
        $marks = implode(',', array_fill(0, count($userIds), '?'));
        $stmt = $this->db->prepare("SELECT user_id, permission_key FROM user_admin_permissions WHERE user_id IN ($marks)");
        $stmt->execute($userIds);
        $assignments = [];
        foreach ($stmt->fetchAll() as $row) $assignments[(int)$row['user_id']][] = $row['permission_key'];
        return $assignments;
    }

    public function assign(int $actorId, int $targetId, array $requestedPermissions): void
    {
        $this->require($actorId, 'admin.permissions.manage');
        $validKeys = array_keys(self::CATALOG);
        $permissions = array_values(array_unique(array_filter(
            array_map(static fn(mixed $key): string => (string)$key, $requestedPermissions),
            static fn(string $key): bool => in_array($key, $validKeys, true)
        )));
        if (!in_array('dashboard.view', $permissions, true)) {
            throw new DomainException('Tài khoản Admin phải được giữ quyền xem bảng điều khiển.');
        }
        $dependencies = [
            'members.manage' => 'members.view',
            'members.change_role' => 'members.view',
            'members.health.view' => 'members.view',
            'workouts.review' => 'workouts.view',
            'appeals.review' => 'appeals.view',
            'content_reports.manage' => 'content_reports.view',
            'fund.manage' => 'fund.view',
            'settings.manage' => 'settings.view',
        ];
        foreach ($dependencies as $permission => $required) {
            if (in_array($permission, $permissions, true) && !in_array($required, $permissions, true)) {
                throw new DomainException('Quyền ' . $permission . ' cần đi kèm quyền ' . $required . '.');
            }
        }

        $this->db->beginTransaction();
        try {
            $target = $this->db->prepare("SELECT role, is_active FROM users WHERE id = ? FOR UPDATE");
            $target->execute([$targetId]);
            $account = $target->fetch();
            if (!$account || $account['role'] !== 'admin') throw new DomainException('Chỉ có thể gán quyền cho tài khoản đang mang vai trò Admin.');

            if ((int)$account['is_active'] && !in_array('admin.permissions.manage', $permissions, true)) {
                $this->db->query("SELECT id FROM users WHERE role = 'admin' AND is_active = 1 FOR UPDATE")->fetchAll(PDO::FETCH_COLUMN);
                $otherManagers = $this->db->prepare("SELECT COUNT(DISTINCT u.id) FROM users u JOIN user_admin_permissions up ON up.user_id = u.id WHERE u.role = 'admin' AND u.is_active = 1 AND u.id <> ? AND up.permission_key = 'admin.permissions.manage'");
                $otherManagers->execute([$targetId]);
                if ((int)$otherManagers->fetchColumn() === 0) throw new DomainException('Phải luôn giữ ít nhất một Admin đang hoạt động có quyền quản lý phân quyền.');
            }

            $this->db->prepare('DELETE FROM user_admin_permissions WHERE user_id = ?')->execute([$targetId]);
            $insert = $this->db->prepare('INSERT INTO user_admin_permissions (user_id, permission_key, granted_by) VALUES (?, ?, ?)');
            foreach ($permissions as $permission) $insert->execute([$targetId, $permission, $actorId]);
            (new AuditLogService($this->db))->record($actorId, 'admin_permissions_updated', 'user', $targetId, ['permissions' => $permissions]);
            $this->db->commit();
            unset($this->cache[$targetId]);
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }
}
