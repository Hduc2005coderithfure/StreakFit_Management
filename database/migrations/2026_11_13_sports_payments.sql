CREATE TABLE IF NOT EXISTS sport_groups (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 owner_id BIGINT UNSIGNED NOT NULL,
 name VARCHAR(100) NOT NULL,
 sport VARCHAR(20) NOT NULL,
 description VARCHAR(1000) NOT NULL DEFAULT '',
 meeting_point VARCHAR(255) NOT NULL,
 destination VARCHAR(255) NOT NULL DEFAULT '',
 meeting_at DATETIME NULL,
 status ENUM('active','archived') NOT NULL DEFAULT 'active',
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (owner_id) REFERENCES users(id),
 INDEX idx_groups_sport (status,sport)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS sport_group_members (
 group_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NOT NULL,
 joined_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (group_id,user_id),
 FOREIGN KEY (group_id) REFERENCES sport_groups(id) ON DELETE CASCADE,
 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS payment_orders (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 order_code BIGINT UNSIGNED NOT NULL UNIQUE,
 fund_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NOT NULL,
 amount INT UNSIGNED NOT NULL,
 status ENUM('creating','pending','paid','cancelled','expired','review') NOT NULL DEFAULT 'creating',
 active_key VARCHAR(80) NULL UNIQUE,
 payment_link_id VARCHAR(100) NULL UNIQUE,
 checkout_url VARCHAR(1000) NULL,
 provider_reference VARCHAR(100) NULL UNIQUE,
 review_note VARCHAR(255) NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 paid_at DATETIME NULL,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY (fund_id) REFERENCES monthly_funds(id),
 FOREIGN KEY (user_id) REFERENCES users(id),
 INDEX idx_payment_history (user_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO admin_permissions (permission_key,permission_group,permission_name,description,sort_order) VALUES
 ('groups.manage','Nhóm thể thao','Quản lý nhóm thể thao','Sửa thông tin, đóng và mở lại nhóm thể thao.',120);
INSERT IGNORE INTO user_admin_permissions (user_id,permission_key,granted_by)
 SELECT user_id,'groups.manage',user_id FROM user_admin_permissions WHERE permission_key='admin.permissions.manage';
CREATE TABLE IF NOT EXISTS workout_route_points (
 workout_id BIGINT UNSIGNED NOT NULL,
 recorded_ms BIGINT UNSIGNED NOT NULL,
 latitude DECIMAL(10,7) NOT NULL,
 longitude DECIMAL(10,7) NOT NULL,
 accuracy DECIMAL(7,2) NOT NULL,
 PRIMARY KEY (workout_id,recorded_ms),
 FOREIGN KEY (workout_id) REFERENCES workouts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
