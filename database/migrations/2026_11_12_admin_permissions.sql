-- Granular Admin permissions. Safe to run once on an existing StreakFit database.
CREATE TABLE IF NOT EXISTS admin_permissions (
    permission_key VARCHAR(64) NOT NULL,
    permission_group VARCHAR(80) NOT NULL,
    permission_name VARCHAR(120) NOT NULL,
    description VARCHAR(255) NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (permission_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_admin_permissions (
    user_id BIGINT UNSIGNED NOT NULL,
    permission_key VARCHAR(64) NOT NULL,
    granted_by BIGINT UNSIGNED DEFAULT NULL,
    granted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, permission_key),
    KEY idx_admin_permission_key (permission_key),
    CONSTRAINT fk_user_admin_permission_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_user_admin_permission_granted_by FOREIGN KEY (granted_by) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_user_admin_permission_permission FOREIGN KEY (permission_key) REFERENCES admin_permissions (permission_key) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO admin_permissions (permission_key, permission_group, permission_name, description, sort_order) VALUES
('dashboard.view', 'Bảng điều khiển', 'Xem tổng quan', 'Mở trang Admin và các chỉ số tổng quan.', 10),
('members.view', 'Thành viên', 'Xem danh sách thành viên', 'Tìm kiếm và xem thông tin tài khoản, điểm, streak.', 20),
('members.manage', 'Thành viên', 'Khóa hoặc mở tài khoản', 'Thay đổi trạng thái hoạt động của tài khoản.', 21),
('members.change_role', 'Thành viên', 'Đổi vai trò Member/Admin', 'Nâng hoặc hạ vai trò cơ bản của tài khoản.', 22),
('members.health.view', 'Thành viên', 'Xem dữ liệu sức khỏe', 'Xem lịch sử cân nặng và chỉ số sức khỏe riêng tư.', 23),
('admin.permissions.manage', 'Phân quyền', 'Cấp và thu hồi quyền Admin', 'Chọn chính xác các quyền chức năng cho tài khoản Admin.', 30),
('workouts.view', 'Buổi tập', 'Xem buổi tập', 'Xem danh sách buổi tập và ảnh minh chứng.', 40),
('workouts.review', 'Buổi tập', 'Duyệt buổi tập', 'Chấp nhận hoặc từ chối buổi tập cần kiểm duyệt.', 41),
('appeals.view', 'Kháng cáo', 'Xem kháng cáo', 'Xem lý do và ảnh minh chứng kháng cáo.', 50),
('appeals.review', 'Kháng cáo', 'Quyết định kháng cáo', 'Chấp nhận hoặc từ chối kháng cáo.', 51),
('content_reports.view', 'Báo cáo nội dung', 'Xem báo cáo nội dung', 'Xem bài viết, bình luận và lý do bị báo cáo.', 60),
('content_reports.manage', 'Báo cáo nội dung', 'Xử lý báo cáo nội dung', 'Đánh dấu báo cáo, bác báo cáo hoặc gỡ nội dung.', 61),
('content.moderate', 'Bài viết', 'Sửa hoặc gỡ nội dung', 'Sửa hoặc xóa bài viết và bình luận của thành viên khác.', 62),
('points.adjust', 'Điểm', 'Điều chỉnh điểm', 'Cộng hoặc trừ điểm và ghi lý do vào sổ điểm.', 70),
('fund.view', 'Quỹ kỷ luật', 'Xem sổ quỹ', 'Xem thành viên tham gia và các khoản đã ghi nhận.', 80),
('fund.manage', 'Quỹ kỷ luật', 'Quản lý quỹ', 'Xác nhận đóng góp, ghi giao dịch và chốt biểu quyết.', 81),
('analytics.view', 'Báo cáo', 'Xem thống kê', 'Xem biểu đồ tập luyện, tỷ lệ mục tiêu và điểm tuần.', 90),
('audit.view', 'Lịch sử hệ thống', 'Xem audit log', 'Xem lịch sử thao tác quản trị.', 100),
('cron.view', 'Lịch sử hệ thống', 'Xem lịch sử tác vụ', 'Xem trạng thái và lỗi các tác vụ Cron.', 101),
('settings.view', 'Cài đặt', 'Xem cấu hình', 'Xem cấu hình nghiệp vụ hiện hành.', 110),
('settings.manage', 'Cài đặt', 'Thay đổi cấu hình', 'Lưu các thông số ảnh hưởng đến hoạt động hệ thống.', 111);

-- Preserve current behavior for existing Admin accounts; trim their rights in Admin > Phân quyền.
INSERT IGNORE INTO user_admin_permissions (user_id, permission_key, granted_by)
SELECT u.id, p.permission_key, u.id
FROM users u CROSS JOIN admin_permissions p
WHERE u.role = 'admin';
