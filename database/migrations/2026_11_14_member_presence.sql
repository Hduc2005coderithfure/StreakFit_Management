-- V4.1: optional isolated presence storage. Apply after existing migrations.
-- No changes to users, workouts, health, points or fund tables.
CREATE TABLE IF NOT EXISTS member_presence (
    user_id BIGINT UNSIGNED NOT NULL,
    last_seen_at BIGINT UNSIGNED NULL COMMENT 'UTC Unix seconds, latest visible heartbeat',
    share_status TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (user_id),
    KEY idx_member_presence_seen (last_seen_at),
    CONSTRAINT fk_member_presence_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
