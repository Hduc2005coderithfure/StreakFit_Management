<?php
declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use PDO;

final class MaintenanceService
{
    public function __construct(private readonly PDO $db, private readonly string $timezone) {}

    public function run(): array
    {
        $this->db->prepare("INSERT INTO cron_runs (task_name, status) VALUES ('maintenance', 'running')")->execute();
        $runId = (int)$this->db->lastInsertId();
        $summary = ['abandoned' => 0, 'settled_users' => 0, 'reminders' => 0, 'penalties' => 0];
        try {
            $settings = (new SettingsService($this->db))->all();
            $limit = $settings['checkout_timeout_hours'] * 3600;
            $stmt = $this->db->prepare("UPDATE workouts SET status = 'abandoned', checked_out_at = UTC_TIMESTAMP(), duration_seconds = LEAST(TIMESTAMPDIFF(SECOND, checked_in_at, UTC_TIMESTAMP()), ?) WHERE status = 'in_progress' AND checked_in_at <= DATE_SUB(UTC_TIMESTAMP(), INTERVAL ? SECOND)");
            $stmt->execute([$limit, $limit]);
            $summary['abandoned'] = $stmt->rowCount();

            $now = new DateTimeImmutable('now', new DateTimeZone($this->timezone));
            $weekStart = sf_week_start($now);
            $users = $this->db->query('SELECT id FROM users ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
            foreach ($users as $id) sf_settle_user($this->db, (int)$id, $weekStart);
            $summary['settled_users'] = count($users);
            $summary['reminders'] += $this->sendScheduleReminders($now);
            $summary['reminders'] += $this->sendStreakReminders($now, $weekStart);
            $summary['penalties'] = $this->settlePreviousMonth($now);

            $this->db->prepare("UPDATE cron_runs SET status = 'success', summary = ?, finished_at = UTC_TIMESTAMP() WHERE id = ?")->execute([json_encode($summary, JSON_UNESCAPED_UNICODE), $runId]);
            return $summary;
        } catch (\Throwable $error) {
            $this->db->prepare("UPDATE cron_runs SET status = 'failed', summary = ?, finished_at = UTC_TIMESTAMP() WHERE id = ?")->execute([mb_substr($error->getMessage(), 0, 1000), $runId]);
            throw $error;
        }
    }

    private function sendScheduleReminders(DateTimeImmutable $now): int
    {
        $from = $now->format('Y-m-d H:i:s');
        $until = $now->modify('+1 hour')->format('Y-m-d H:i:s');
        $stmt = $this->db->prepare('SELECT id, user_id, planned_date, planned_time FROM planned_workouts WHERE TIMESTAMP(planned_date, planned_time) >= ? AND TIMESTAMP(planned_date, planned_time) < ?');
        $stmt->execute([$from, $until]);
        $insert = $this->db->prepare("INSERT IGNORE INTO notifications (user_id, type, title, body, dedupe_key) VALUES (?, 'reminder', 'Sắp đến lịch tập', ?, ?)");
        $sent = 0;
        foreach ($stmt->fetchAll() as $plan) {
            $insert->execute([(int)$plan['user_id'], 'Bạn đã lên lịch tập lúc ' . substr((string)$plan['planned_time'], 0, 5) . '. Chuẩn bị dụng cụ và tập vừa sức nhé.', 'plan:' . (int)$plan['id'] . ':' . $plan['planned_date']]);
            $sent += $insert->rowCount();
        }
        return $sent;
    }

    private function sendStreakReminders(DateTimeImmutable $now, string $weekStart): int
    {
        if ((int)$now->format('N') < 5) return 0;
        $stmt = $this->db->prepare('SELECT u.id, g.target_sessions, COUNT(DISTINCT CASE WHEN w.status = \'valid\' THEN w.workout_date END) AS valid_sessions FROM users u JOIN weekly_goals g ON g.user_id = u.id AND g.week_start = ? LEFT JOIN workouts w ON w.user_id = u.id AND w.week_start = ? WHERE u.is_active = 1 GROUP BY u.id, g.target_sessions HAVING valid_sessions < g.target_sessions');
        $stmt->execute([$weekStart, $weekStart]);
        $insert = $this->db->prepare("INSERT IGNORE INTO notifications (user_id, type, title, body, dedupe_key) VALUES (?, 'reminder', 'Chuỗi tuần này đang có nguy cơ', ?, ?)");
        $sent = 0;
        foreach ($stmt->fetchAll() as $member) {
            $remaining = max(0, (int)$member['target_sessions'] - (int)$member['valid_sessions']);
            $insert->execute([(int)$member['id'], 'Bạn còn ' . $remaining . ' buổi để hoàn thành mục tiêu tuần.', 'streak-risk:' . (int)$member['id'] . ':' . $weekStart]);
            $sent += $insert->rowCount();
        }
        return $sent;
    }

    private function settlePreviousMonth(DateTimeImmutable $now): int
    {
        $month = $now->modify('first day of last month')->format('Y-m-d');
        $stmt = $this->db->prepare("SELECT id, status, final_penalty FROM monthly_funds WHERE month_start = ?");
        $stmt->execute([$month]);
        $fund = $stmt->fetch();
        if (!$fund) return 0;
        if ($fund['status'] === 'voting') {
            try {
                $amount = (new PenaltyPoolService($this->db))->closeVote((int)$fund['id']);
            } catch (\DomainException) {
                return 0;
            }
            $fund['status'] = 'closed';
            $fund['final_penalty'] = $amount;
        }
        if ($fund['status'] !== 'closed' || (int)$fund['final_penalty'] < 1) return 0;
        $end = (new DateTimeImmutable($month, new DateTimeZone($this->timezone)))->modify('+1 month')->format('Y-m-d');
        $scores = $this->db->prepare("SELECT s.user_id, s.week_start FROM weekly_scores s JOIN fund_members fm ON fm.user_id = s.user_id AND fm.fund_id = ? AND fm.opted_in = 1 WHERE s.week_start >= ? AND s.week_start < ? AND s.missed_sessions > 0");
        $scores->execute([(int)$fund['id'], $month, $end]);
        $insert = $this->db->prepare("INSERT IGNORE INTO fund_transactions (fund_id, user_id, transaction_type, amount, status, note, reference_key) VALUES (?, ?, 'penalty', ?, 'recorded', ?, ?)");
        $count = 0;
        foreach ($scores->fetchAll() as $score) {
            $key = 'penalty:' . (int)$score['user_id'] . ':' . $score['week_start'];
            $insert->execute([(int)$fund['id'], (int)$score['user_id'], (int)$fund['final_penalty'], 'Mức phạt tháng ' . $month . ' cho tuần ' . $score['week_start'], $key]);
            $count += $insert->rowCount();
        }
        return $count;
    }
}
