-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th10 11, 2026 lúc 03:12 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `streakfit`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `appeals`
--

CREATE TABLE `appeals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `week_start` date NOT NULL,
  `appeal_month` date NOT NULL,
  `reason` varchar(1000) NOT NULL,
  `proof_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','accepted','rejected','rescue_pending','rescued','expired') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `resolved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `appeals`
--

INSERT INTO `appeals` (`id`, `user_id`, `week_start`, `appeal_month`, `reason`, `proof_path`, `status`, `created_at`, `resolved_at`) VALUES
(1, 3, '2026-11-09', '2026-11-01', 'Tuần này mình phải đi công tác gấp, xin phép bảo lưu chuỗi.', NULL, 'pending', '2026-11-10 22:13:54', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `appeal_votes`
--

CREATE TABLE `appeal_votes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `appeal_id` bigint(20) UNSIGNED NOT NULL,
  `voter_id` bigint(20) UNSIGNED NOT NULL,
  `vote` enum('yes','no') NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `appeal_votes`
--

INSERT INTO `appeal_votes` (`id`, `appeal_id`, `voter_id`, `vote`, `created_at`) VALUES
(1, 1, 2, 'yes', '2026-11-10 22:13:54'),
(2, 1, 4, 'no', '2026-11-10 22:13:54');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `fund_members`
--

CREATE TABLE `fund_members` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `fund_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `opted_in` tinyint(1) NOT NULL DEFAULT 1,
  `contribution_status` enum('unpaid','paid') NOT NULL DEFAULT 'unpaid',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `fund_members`
--

INSERT INTO `fund_members` (`id`, `fund_id`, `user_id`, `opted_in`, `contribution_status`, `created_at`) VALUES
(1, 1, 2, 1, 'paid', '2026-11-10 22:13:54'),
(2, 1, 3, 1, 'paid', '2026-11-10 22:13:54'),
(3, 1, 4, 1, 'paid', '2026-11-10 22:13:54');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `fund_transactions`
--

CREATE TABLE `fund_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `fund_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `transaction_type` enum('contribution','penalty','reward') NOT NULL,
  `amount` int(10) UNSIGNED NOT NULL,
  `status` enum('recorded','paid') NOT NULL DEFAULT 'recorded',
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `fund_transactions`
--

INSERT INTO `fund_transactions` (`id`, `fund_id`, `user_id`, `transaction_type`, `amount`, `status`, `note`, `created_at`) VALUES
(1, 1, 3, 'contribution', 20000, 'paid', 'Quản trị viên xác nhận đã đóng', '2026-11-10 15:29:07');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `health_metrics`
--

CREATE TABLE `health_metrics` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `height_cm` decimal(5,2) NOT NULL,
  `weight_kg` decimal(5,2) NOT NULL,
  `recorded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `health_metrics`
--

INSERT INTO `health_metrics` (`id`, `user_id`, `height_cm`, `weight_kg`, `recorded_at`) VALUES
(1, 2, 172.50, 70.50, '2026-10-11 22:13:54'),
(2, 2, 172.50, 68.00, '2026-11-10 22:13:54'),
(3, 3, 180.00, 75.00, '2026-11-10 22:13:54'),
(4, 2, 175.00, 68.00, '2026-11-10 15:25:46'),
(5, 2, 120.00, 50.10, '2026-11-10 15:26:20');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `monthly_funds`
--

CREATE TABLE `monthly_funds` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `month_start` date NOT NULL,
  `contribution_amount` int(10) UNSIGNED NOT NULL DEFAULT 20000,
  `final_penalty` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` enum('open','voting','closed') NOT NULL DEFAULT 'open',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `monthly_funds`
--

INSERT INTO `monthly_funds` (`id`, `month_start`, `contribution_amount`, `final_penalty`, `status`, `created_at`) VALUES
(1, '2026-11-01', 20000, 0, 'open', '2026-11-10 22:13:54');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `notifications`
--

CREATE TABLE `notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `actor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` varchar(32) NOT NULL,
  `title` varchar(160) NOT NULL,
  `body` varchar(500) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `penalty_proposals`
--

CREATE TABLE `penalty_proposals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `fund_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `amount` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `penalty_votes`
--

CREATE TABLE `penalty_votes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `fund_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `amount` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `planned_workouts`
--

CREATE TABLE `planned_workouts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `week_start` date NOT NULL,
  `planned_date` date NOT NULL,
  `planned_time` time NOT NULL,
  `note` varchar(160) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `points_ledger`
--

CREATE TABLE `points_ledger` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `week_start` date DEFAULT NULL,
  `points` int(11) NOT NULL,
  `reason` varchar(180) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `display_name` varchar(80) NOT NULL,
  `gender` enum('female','male','unspecified') NOT NULL DEFAULT 'unspecified',
  `age` tinyint(3) UNSIGNED DEFAULT NULL,
  `height_cm` decimal(5,2) DEFAULT NULL,
  `weight_kg` decimal(5,2) DEFAULT NULL,
  `total_points` int(11) NOT NULL DEFAULT 0,
  `current_streak` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `best_streak` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_sessions` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_minutes` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `role` enum('member','admin') NOT NULL DEFAULT 'member',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `display_name`, `gender`, `age`, `height_cm`, `weight_kg`, `total_points`, `current_streak`, `best_streak`, `total_sessions`, `total_minutes`, `role`, `created_at`, `updated_at`) VALUES
(1, 'admin@streakfit.vn', '$2y$10$EDiKEU.ve7W16OwFnSKPYeD/eo.No4x0EyJxxT7AjY51oSD7imy0y', 'Quản Trị Viên', 'unspecified', 25, 175.00, 70.00, 1500, 10, 15, 50, 2500, 'admin', '2026-11-10 22:13:54', '2026-11-10 22:23:23'),
(2, 'ducnguyen@gmail.com', '$2y$10$EDiKEU.ve7W16OwFnSKPYeD/eo.No4x0EyJxxT7AjY51oSD7imy0y', 'Nguyễn Hữu Đức', 'male', 21, 120.00, 50.10, 500, 5, 5, 20, 900, 'member', '2026-11-10 22:13:54', '2026-11-10 15:26:20'),
(3, 'member1@gmail.com', '$2y$10$EDiKEU.ve7W16OwFnSKPYeD/eo.No4x0EyJxxT7AjY51oSD7imy0y', 'Hội Viên Alpha', 'male', 24, 180.00, 75.00, 300, 2, 8, 15, 750, 'member', '2026-11-10 22:13:54', '2026-11-10 22:23:23'),
(4, 'member2@gmail.com', '$2y$10$EDiKEU.ve7W16OwFnSKPYeD/eo.No4x0EyJxxT7AjY51oSD7imy0y', 'Hội Viên Beta', 'female', 22, 160.00, 50.00, 450, 4, 10, 18, 800, 'member', '2026-11-10 22:13:54', '2026-11-10 22:23:23'),
(5, 'ducnguyen.2005nh@gmail.com', '$2y$10$RMc6rzgGIyfRM3YGyfJ/Per9rtlBoTpTZ0764s0nO24FHSKe/IRGO', 'Ducnguyen', 'unspecified', NULL, NULL, NULL, 0, 0, 0, 0, 0, 'member', '2026-11-10 15:39:55', '2026-11-10 15:39:55');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `weekly_goals`
--

CREATE TABLE `weekly_goals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `week_start` date NOT NULL,
  `target_sessions` tinyint(3) UNSIGNED NOT NULL,
  `locked_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `weekly_goals`
--

INSERT INTO `weekly_goals` (`id`, `user_id`, `week_start`, `target_sessions`, `locked_at`, `created_at`) VALUES
(1, 2, '2026-11-09', 5, '2026-11-10 15:23:51', '2026-11-10 22:13:54'),
(2, 3, '2026-11-09', 4, '2026-11-10 15:29:48', '2026-11-10 22:13:54'),
(3, 4, '2026-11-09', 3, NULL, '2026-11-10 22:13:54'),
(20, 1, '2026-11-09', 3, '2026-11-10 15:28:16', '2026-11-10 15:28:16'),
(34, 5, '2026-11-09', 3, '2026-11-10 15:39:55', '2026-11-10 15:39:55');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `weekly_scores`
--

CREATE TABLE `weekly_scores` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `week_start` date NOT NULL,
  `target_sessions` tinyint(3) UNSIGNED NOT NULL,
  `valid_sessions` tinyint(3) UNSIGNED NOT NULL,
  `completed_sessions` tinyint(3) UNSIGNED NOT NULL,
  `missed_sessions` tinyint(3) UNSIGNED NOT NULL,
  `extra_sessions` tinyint(3) UNSIGNED NOT NULL,
  `workout_points` int(11) NOT NULL,
  `streak_before` int(10) UNSIGNED NOT NULL,
  `streak_after` int(10) UNSIGNED NOT NULL,
  `streak_points` int(11) NOT NULL,
  `total_week_points` int(11) NOT NULL,
  `appeal_kept_streak` tinyint(1) NOT NULL DEFAULT 0,
  `settled_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `workouts`
--

CREATE TABLE `workouts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `week_start` date NOT NULL,
  `workout_date` date NOT NULL,
  `checked_in_at` datetime NOT NULL,
  `checked_out_at` datetime DEFAULT NULL,
  `duration_seconds` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `checkin_image` varchar(255) NOT NULL,
  `checkout_image` varchar(255) DEFAULT NULL,
  `status` enum('in_progress','valid','invalid','abandoned') NOT NULL DEFAULT 'in_progress',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `workouts`
--

INSERT INTO `workouts` (`id`, `user_id`, `week_start`, `workout_date`, `checked_in_at`, `checked_out_at`, `duration_seconds`, `checkin_image`, `checkout_image`, `status`, `created_at`) VALUES
(1, 2, '2026-11-09', '2026-11-08', '2026-11-08 04:13:54', '2026-11-08 05:13:54', 3600, 'uploads/checkin_1.jpg', 'uploads/checkout_1.jpg', 'valid', '2026-11-10 22:13:54'),
(2, 2, '2026-11-09', '2026-11-09', '2026-11-09 16:13:54', '2026-11-09 16:58:54', 2700, 'uploads/checkin_2.jpg', 'uploads/checkout_2.jpg', 'valid', '2026-11-10 22:13:54'),
(3, 2, '2026-11-09', '2026-11-10', '2026-11-10 22:13:54', NULL, 0, 'uploads/checkin_3.jpg', NULL, 'in_progress', '2026-11-10 22:13:54');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `workout_comments`
--

CREATE TABLE `workout_comments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `workout_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `comment_text` varchar(500) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `workout_posts`
--

CREATE TABLE `workout_posts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `workout_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `caption` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `workout_posts`
--

INSERT INTO `workout_posts` (`id`, `workout_id`, `user_id`, `caption`, `created_at`) VALUES
(1, 1, 2, 'Hoàn thành buổi tập Kháng lực hôm nay! Cảm giác rất tốt 🔥', '2026-11-10 22:13:54'),
(2, 2, 2, 'Boxing buổi sáng khởi động ngày mới 🥊', '2026-11-10 22:13:54');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `workout_reactions`
--

CREATE TABLE `workout_reactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `workout_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `reaction` enum('heart','muscle','fire','sad','haha') NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `workout_reactions`
--

INSERT INTO `workout_reactions` (`id`, `workout_id`, `user_id`, `reaction`, `created_at`) VALUES
(1, 1, 3, 'fire', '2026-11-10 22:13:54'),
(2, 1, 4, 'muscle', '2026-11-10 22:13:54');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `appeals`
--
ALTER TABLE `appeals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_appeals_week` (`week_start`,`status`),
  ADD KEY `idx_appeals_user_month` (`user_id`,`appeal_month`);

--
-- Chỉ mục cho bảng `appeal_votes`
--
ALTER TABLE `appeal_votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_appeal_vote` (`appeal_id`,`voter_id`),
  ADD KEY `fk_appeal_vote_user` (`voter_id`);

--
-- Chỉ mục cho bảng `fund_members`
--
ALTER TABLE `fund_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_fund_member` (`fund_id`,`user_id`),
  ADD KEY `fk_fund_member_user` (`user_id`);

--
-- Chỉ mục cho bảng `fund_transactions`
--
ALTER TABLE `fund_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fund_tx` (`fund_id`,`user_id`,`transaction_type`),
  ADD KEY `fk_fund_tx_user` (`user_id`);

--
-- Chỉ mục cho bảng `health_metrics`
--
ALTER TABLE `health_metrics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_health_user_date` (`user_id`,`recorded_at`);

--
-- Chỉ mục cho bảng `monthly_funds`
--
ALTER TABLE `monthly_funds`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_fund_month` (`month_start`);

--
-- Chỉ mục cho bảng `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user` (`user_id`,`is_read`,`created_at`),
  ADD KEY `fk_notification_actor` (`actor_id`);

--
-- Chỉ mục cho bảng `penalty_proposals`
--
ALTER TABLE `penalty_proposals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_penalty_proposal` (`fund_id`,`user_id`),
  ADD KEY `fk_proposal_user` (`user_id`);

--
-- Chỉ mục cho bảng `penalty_votes`
--
ALTER TABLE `penalty_votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_penalty_vote` (`fund_id`,`user_id`),
  ADD KEY `fk_penalty_vote_user` (`user_id`);

--
-- Chỉ mục cho bảng `planned_workouts`
--
ALTER TABLE `planned_workouts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_planned_day` (`user_id`,`planned_date`),
  ADD KEY `idx_plan_week` (`user_id`,`week_start`,`planned_date`);

--
-- Chỉ mục cho bảng `points_ledger`
--
ALTER TABLE `points_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ledger_user_date` (`user_id`,`created_at`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`),
  ADD KEY `idx_users_points` (`total_points`),
  ADD KEY `idx_users_streak` (`current_streak`);

--
-- Chỉ mục cho bảng `weekly_goals`
--
ALTER TABLE `weekly_goals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_weekly_goal` (`user_id`,`week_start`);

--
-- Chỉ mục cho bảng `weekly_scores`
--
ALTER TABLE `weekly_scores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_weekly_score` (`user_id`,`week_start`),
  ADD KEY `idx_scores_week` (`week_start`,`total_week_points`);

--
-- Chỉ mục cho bảng `workouts`
--
ALTER TABLE `workouts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_workouts_user_day` (`user_id`,`workout_date`,`status`),
  ADD KEY `idx_workouts_week` (`user_id`,`week_start`,`status`),
  ADD KEY `idx_workouts_feed` (`status`,`checked_out_at`);

--
-- Chỉ mục cho bảng `workout_comments`
--
ALTER TABLE `workout_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_comments_workout` (`workout_id`,`created_at`),
  ADD KEY `fk_comment_user` (`user_id`);

--
-- Chỉ mục cho bảng `workout_posts`
--
ALTER TABLE `workout_posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_post_workout` (`workout_id`),
  ADD KEY `idx_posts_feed` (`created_at`),
  ADD KEY `fk_post_user` (`user_id`);

--
-- Chỉ mục cho bảng `workout_reactions`
--
ALTER TABLE `workout_reactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_reaction_user_workout` (`workout_id`,`user_id`),
  ADD KEY `fk_reaction_user` (`user_id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `appeals`
--
ALTER TABLE `appeals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `appeal_votes`
--
ALTER TABLE `appeal_votes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `fund_members`
--
ALTER TABLE `fund_members`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `fund_transactions`
--
ALTER TABLE `fund_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT cho bảng `health_metrics`
--
ALTER TABLE `health_metrics`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `monthly_funds`
--
ALTER TABLE `monthly_funds`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT cho bảng `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `penalty_proposals`
--
ALTER TABLE `penalty_proposals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `penalty_votes`
--
ALTER TABLE `penalty_votes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `planned_workouts`
--
ALTER TABLE `planned_workouts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `points_ledger`
--
ALTER TABLE `points_ledger`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT cho bảng `weekly_goals`
--
ALTER TABLE `weekly_goals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT cho bảng `weekly_scores`
--
ALTER TABLE `weekly_scores`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `workouts`
--
ALTER TABLE `workouts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `workout_comments`
--
ALTER TABLE `workout_comments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT cho bảng `workout_posts`
--
ALTER TABLE `workout_posts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `workout_reactions`
--
ALTER TABLE `workout_reactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `appeals`
--
ALTER TABLE `appeals`
  ADD CONSTRAINT `fk_appeal_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `appeal_votes`
--
ALTER TABLE `appeal_votes`
  ADD CONSTRAINT `fk_appeal_vote_appeal` FOREIGN KEY (`appeal_id`) REFERENCES `appeals` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_appeal_vote_user` FOREIGN KEY (`voter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `fund_members`
--
ALTER TABLE `fund_members`
  ADD CONSTRAINT `fk_fund_member_fund` FOREIGN KEY (`fund_id`) REFERENCES `monthly_funds` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fund_member_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `fund_transactions`
--
ALTER TABLE `fund_transactions`
  ADD CONSTRAINT `fk_fund_tx_fund` FOREIGN KEY (`fund_id`) REFERENCES `monthly_funds` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fund_tx_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `health_metrics`
--
ALTER TABLE `health_metrics`
  ADD CONSTRAINT `fk_health_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notification_actor` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_notification_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `penalty_proposals`
--
ALTER TABLE `penalty_proposals`
  ADD CONSTRAINT `fk_proposal_fund` FOREIGN KEY (`fund_id`) REFERENCES `monthly_funds` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_proposal_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `penalty_votes`
--
ALTER TABLE `penalty_votes`
  ADD CONSTRAINT `fk_penalty_vote_fund` FOREIGN KEY (`fund_id`) REFERENCES `monthly_funds` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_penalty_vote_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `planned_workouts`
--
ALTER TABLE `planned_workouts`
  ADD CONSTRAINT `fk_plan_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `points_ledger`
--
ALTER TABLE `points_ledger`
  ADD CONSTRAINT `fk_ledger_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `weekly_goals`
--
ALTER TABLE `weekly_goals`
  ADD CONSTRAINT `fk_weekly_goal_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `weekly_scores`
--
ALTER TABLE `weekly_scores`
  ADD CONSTRAINT `fk_week_score_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `workouts`
--
ALTER TABLE `workouts`
  ADD CONSTRAINT `fk_workouts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `workout_comments`
--
ALTER TABLE `workout_comments`
  ADD CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_comment_workout` FOREIGN KEY (`workout_id`) REFERENCES `workouts` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `workout_posts`
--
ALTER TABLE `workout_posts`
  ADD CONSTRAINT `fk_post_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_post_workout` FOREIGN KEY (`workout_id`) REFERENCES `workouts` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `workout_reactions`
--
ALTER TABLE `workout_reactions`
  ADD CONSTRAINT `fk_reaction_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reaction_workout` FOREIGN KEY (`workout_id`) REFERENCES `workouts` (`id`) ON DELETE CASCADE;

-- Danh mục quyền Admin chi tiết cho database mới.
CREATE TABLE `admin_permissions` (
  `permission_key` varchar(64) NOT NULL,
  `permission_group` varchar(80) NOT NULL,
  `permission_name` varchar(120) NOT NULL,
  `description` varchar(255) NOT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`permission_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_admin_permissions` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `permission_key` varchar(64) NOT NULL,
  `granted_by` bigint(20) UNSIGNED DEFAULT NULL,
  `granted_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`,`permission_key`),
  KEY `idx_admin_permission_key` (`permission_key`),
  KEY `fk_user_admin_permission_granted_by` (`granted_by`),
  CONSTRAINT `fk_user_admin_permission_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_admin_permission_granted_by` FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_user_admin_permission_permission` FOREIGN KEY (`permission_key`) REFERENCES `admin_permissions` (`permission_key`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `admin_permissions` (`permission_key`,`permission_group`,`permission_name`,`description`,`sort_order`) VALUES
('dashboard.view','Bảng điều khiển','Xem tổng quan','Mở trang Admin và các chỉ số tổng quan.',10),
('members.view','Thành viên','Xem danh sách thành viên','Tìm kiếm và xem thông tin tài khoản, điểm, streak.',20),
('members.manage','Thành viên','Khóa hoặc mở tài khoản','Thay đổi trạng thái hoạt động của tài khoản.',21),
('members.change_role','Thành viên','Đổi vai trò Member/Admin','Nâng hoặc hạ vai trò cơ bản của tài khoản.',22),
('members.health.view','Thành viên','Xem dữ liệu sức khỏe','Xem lịch sử cân nặng và chỉ số sức khỏe riêng tư.',23),
('admin.permissions.manage','Phân quyền','Cấp và thu hồi quyền Admin','Chọn chính xác các quyền chức năng cho tài khoản Admin.',30),
('workouts.view','Buổi tập','Xem buổi tập','Xem danh sách buổi tập và ảnh minh chứng.',40),
('workouts.review','Buổi tập','Duyệt buổi tập','Chấp nhận hoặc từ chối buổi tập cần kiểm duyệt.',41),
('appeals.view','Kháng cáo','Xem kháng cáo','Xem lý do và ảnh minh chứng kháng cáo.',50),
('appeals.review','Kháng cáo','Quyết định kháng cáo','Chấp nhận hoặc từ chối kháng cáo.',51),
('content_reports.view','Báo cáo nội dung','Xem báo cáo nội dung','Xem bài viết, bình luận và lý do bị báo cáo.',60),
('content_reports.manage','Báo cáo nội dung','Xử lý báo cáo nội dung','Đánh dấu báo cáo, bác báo cáo hoặc gỡ nội dung.',61),
('content.moderate','Bài viết','Sửa hoặc gỡ nội dung','Sửa hoặc xóa bài viết và bình luận của thành viên khác.',62),
('points.adjust','Điểm','Điều chỉnh điểm','Cộng hoặc trừ điểm và ghi lý do vào sổ điểm.',70),
('fund.view','Quỹ kỷ luật','Xem sổ quỹ','Xem thành viên tham gia và các khoản đã ghi nhận.',80),
('fund.manage','Quỹ kỷ luật','Quản lý quỹ','Xác nhận đóng góp, ghi giao dịch và chốt biểu quyết.',81),
('analytics.view','Báo cáo','Xem thống kê','Xem biểu đồ tập luyện, tỷ lệ mục tiêu và điểm tuần.',90),
('audit.view','Lịch sử hệ thống','Xem audit log','Xem lịch sử thao tác quản trị.',100),
('cron.view','Lịch sử hệ thống','Xem lịch sử tác vụ','Xem trạng thái và lỗi các tác vụ Cron.',101),
('settings.view','Cài đặt','Xem cấu hình','Xem cấu hình nghiệp vụ hiện hành.',110),
('settings.manage','Cài đặt','Thay đổi cấu hình','Lưu các thông số ảnh hưởng đến hoạt động hệ thống.',111);

INSERT INTO `user_admin_permissions` (`user_id`,`permission_key`,`granted_by`)
SELECT u.id, p.permission_key, u.id FROM `users` u CROSS JOIN `admin_permissions` p WHERE u.role = 'admin';
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
