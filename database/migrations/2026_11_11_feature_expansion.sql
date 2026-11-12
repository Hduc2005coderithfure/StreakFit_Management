-- Run once after the original StreakFit schema has been imported.
ALTER TABLE users
    ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER role,
    ADD COLUMN password_changed_at DATETIME NULL AFTER is_active;
ALTER TABLE workouts
    ADD COLUMN moderation_reason VARCHAR(500) NULL AFTER status,
    ADD COLUMN moderated_by BIGINT UNSIGNED NULL AFTER moderation_reason,
    ADD COLUMN moderated_at DATETIME NULL AFTER moderated_by,
    ADD CONSTRAINT fk_workouts_moderator FOREIGN KEY (moderated_by) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE workouts MODIFY status ENUM('in_progress','valid','invalid','abandoned') NOT NULL DEFAULT 'in_progress';
ALTER TABLE notifications
    ADD COLUMN dedupe_key VARCHAR(120) NULL AFTER is_read,
    ADD UNIQUE KEY uq_notifications_dedupe (dedupe_key);
ALTER TABLE appeals
    ADD COLUMN review_note VARCHAR(1000) NULL AFTER status,
    ADD COLUMN reviewed_by BIGINT UNSIGNED NULL AFTER review_note,
    ADD CONSTRAINT fk_appeal_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE fund_transactions
    ADD COLUMN reference_key VARCHAR(120) NULL AFTER note,
    ADD UNIQUE KEY uq_fund_tx_reference (reference_key);
-- Clean legacy values before adding stricter checks.
UPDATE users SET age = NULL WHERE age IS NOT NULL AND age NOT BETWEEN 16 AND 100;
UPDATE users SET height_cm = NULL WHERE height_cm IS NOT NULL AND height_cm NOT BETWEEN 100 AND 230;
UPDATE users SET weight_kg = NULL WHERE weight_kg IS NOT NULL AND weight_kg NOT BETWEEN 25 AND 250;
DELETE FROM health_metrics WHERE height_cm NOT BETWEEN 100 AND 230 OR weight_kg NOT BETWEEN 25 AND 250;
UPDATE weekly_goals SET target_sessions = GREATEST(1, LEAST(7, target_sessions));
UPDATE weekly_scores SET target_sessions = GREATEST(1, LEAST(7, target_sessions));
UPDATE weekly_scores SET completed_sessions = LEAST(completed_sessions, target_sessions);
DELETE FROM fund_transactions WHERE amount <= 0;
-- Downgrade legacy valid workouts that do not have both required photo references.
UPDATE users u
JOIN (
    SELECT user_id, COUNT(*) AS invalid_sessions, SUM(CEIL(duration_seconds / 60)) AS invalid_minutes
    FROM workouts
    WHERE status = 'valid' AND (checkin_image IS NULL OR checkin_image = '' OR checkout_image IS NULL OR checkout_image = '')
    GROUP BY user_id
) bad ON bad.user_id = u.id
SET u.total_sessions = GREATEST(0, u.total_sessions - bad.invalid_sessions),
    u.total_minutes = GREATEST(0, u.total_minutes - bad.invalid_minutes);
UPDATE workouts
SET status = 'invalid', moderation_reason = COALESCE(moderation_reason, 'Thiếu một trong hai ảnh minh chứng trước khi áp dụng ràng buộc.')
WHERE status = 'valid' AND (checkin_image IS NULL OR checkin_image = '' OR checkout_image IS NULL OR checkout_image = '');
-- Keep the earliest valid workout when old rows contain multiple valid sessions on one day.
UPDATE users u
JOIN (
    SELECT duplicate_row.user_id, COUNT(*) AS duplicate_sessions, SUM(CEIL(duplicate_row.duration_seconds / 60)) AS duplicate_minutes
    FROM workouts duplicate_row
    WHERE duplicate_row.status = 'valid'
      AND EXISTS (
          SELECT 1 FROM workouts earlier
          WHERE earlier.user_id = duplicate_row.user_id
            AND earlier.workout_date = duplicate_row.workout_date
            AND earlier.status = 'valid'
            AND earlier.id < duplicate_row.id
      )
    GROUP BY duplicate_row.user_id
) duplicates ON duplicates.user_id = u.id
SET u.total_sessions = GREATEST(0, u.total_sessions - duplicates.duplicate_sessions),
    u.total_minutes = GREATEST(0, u.total_minutes - duplicates.duplicate_minutes);
UPDATE workouts duplicate_row
JOIN workouts earlier ON earlier.user_id = duplicate_row.user_id
    AND earlier.workout_date = duplicate_row.workout_date
    AND earlier.status = 'valid'
    AND earlier.id < duplicate_row.id
SET duplicate_row.status = 'invalid',
    duplicate_row.moderation_reason = COALESCE(duplicate_row.moderation_reason, 'Tự chuyển thành không hợp lệ vì đã có buổi hợp lệ cùng ngày trước khi áp dụng ràng buộc.')
WHERE duplicate_row.status = 'valid';
-- Normalize legacy date keys before enforcing same-week integrity.
UPDATE planned_workouts SET week_start = DATE_SUB(planned_date, INTERVAL WEEKDAY(planned_date) DAY);
UPDATE workouts SET week_start = DATE_SUB(workout_date, INTERVAL WEEKDAY(workout_date) DAY);
UPDATE workouts SET checked_out_at = COALESCE(checked_out_at, UTC_TIMESTAMP()) WHERE status <> 'in_progress' AND checked_out_at IS NULL;
UPDATE appeals SET status = 'expired', review_note = COALESCE(review_note, 'Đơn cũ thiếu ảnh minh chứng; đã đóng trước khi áp dụng ràng buộc.'), resolved_at = COALESCE(resolved_at, UTC_TIMESTAMP()) WHERE status = 'pending' AND (proof_path IS NULL OR proof_path = '');
ALTER TABLE workouts
    ADD COLUMN valid_workout_date DATE GENERATED ALWAYS AS (CASE WHEN status = 'valid' THEN workout_date ELSE NULL END) STORED AFTER status,
    ADD UNIQUE KEY uq_workout_valid_day (user_id, valid_workout_date);
ALTER TABLE users
    ADD CONSTRAINT ck_users_age CHECK (age IS NULL OR age BETWEEN 16 AND 100),
    ADD CONSTRAINT ck_users_height CHECK (height_cm IS NULL OR height_cm BETWEEN 100 AND 230),
    ADD CONSTRAINT ck_users_weight CHECK (weight_kg IS NULL OR weight_kg BETWEEN 25 AND 250);
ALTER TABLE health_metrics
    ADD CONSTRAINT ck_health_height CHECK (height_cm BETWEEN 100 AND 230),
    ADD CONSTRAINT ck_health_weight CHECK (weight_kg BETWEEN 25 AND 250);
ALTER TABLE weekly_goals ADD CONSTRAINT ck_weekly_goal_target CHECK (target_sessions BETWEEN 1 AND 7);
ALTER TABLE planned_workouts ADD CONSTRAINT ck_plan_date_in_week CHECK (planned_date >= week_start AND planned_date < DATE_ADD(week_start, INTERVAL 7 DAY));
ALTER TABLE workouts
    ADD CONSTRAINT ck_workout_week_date CHECK (workout_date >= week_start AND workout_date < DATE_ADD(week_start, INTERVAL 7 DAY)),
    ADD CONSTRAINT ck_workout_finished CHECK (status = 'in_progress' OR checked_out_at IS NOT NULL),
    ADD CONSTRAINT ck_workout_valid_photos CHECK (status <> 'valid' OR (checkin_image <> '' AND checkout_image IS NOT NULL AND checkout_image <> ''));
ALTER TABLE weekly_scores
    ADD CONSTRAINT ck_week_score_target CHECK (target_sessions BETWEEN 1 AND 7),
    ADD CONSTRAINT ck_week_score_parts CHECK (completed_sessions <= target_sessions AND total_week_points = workout_points + streak_points);
ALTER TABLE appeals ADD CONSTRAINT ck_appeal_pending_proof CHECK (status <> 'pending' OR (proof_path IS NOT NULL AND proof_path <> ''));
ALTER TABLE fund_transactions ADD CONSTRAINT ck_fund_tx_amount CHECK (amount > 0);

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_password_reset_hash (token_hash),
    KEY idx_password_reset_user (user_id, expires_at),
    CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_reports (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    reporter_id BIGINT UNSIGNED NOT NULL,
    content_type ENUM('post','comment') NOT NULL,
    content_id BIGINT UNSIGNED NOT NULL,
    reason VARCHAR(500) NOT NULL,
    status ENUM('pending','resolved','dismissed') NOT NULL DEFAULT 'pending',
    resolved_by BIGINT UNSIGNED NULL,
    resolution_note VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_content_reports_status (status, created_at),
    KEY idx_content_reports_content (content_type, content_id),
    CONSTRAINT fk_content_reporter FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_content_report_resolver FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS system_settings (
    setting_key VARCHAR(80) NOT NULL,
    setting_value VARCHAR(255) NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (setting_key),
    CONSTRAINT fk_setting_updater FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    actor_id BIGINT UNSIGNED NULL,
    action VARCHAR(80) NOT NULL,
    entity_type VARCHAR(80) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    details JSON NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_actor_date (actor_id, created_at),
    KEY idx_audit_entity (entity_type, entity_id, created_at),
    CONSTRAINT fk_audit_actor FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cron_runs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    task_name VARCHAR(80) NOT NULL,
    status ENUM('running','success','failed') NOT NULL,
    summary VARCHAR(1000) NULL,
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    finished_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_cron_runs_task_date (task_name, started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO system_settings (setting_key, setting_value) VALUES
('minimum_workout_seconds', '1800'),
('checkout_timeout_hours', '12'),
('monthly_contribution', '20000'),
('session_points', '40'),
('miss_penalty', '25'),
('extra_session_points', '20'),
('streak_weekly_bonus', '5'),
('streak_bonus_cap_weeks', '6'),
('max_extra_sessions', '2'),
('appeal_monthly_limit', '2'),
('appeal_minimum_votes', '3')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
