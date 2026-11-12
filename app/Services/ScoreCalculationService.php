<?php
declare(strict_types=1);

namespace App\Services;

use PDO;

final class ScoreCalculationService
{
    public static function calculate(int $targetSessions, int $validSessions, int $streakBefore, array $rules = []): array
    {
        $sessionPoints = max(0, (int)($rules['session_points'] ?? 40));
        $missPenalty = max(0, (int)($rules['miss_penalty'] ?? 25));
        $extraPoints = max(0, (int)($rules['extra_session_points'] ?? 20));
        $streakBonus = max(0, (int)($rules['streak_weekly_bonus'] ?? 5));
        $streakCap = max(1, (int)($rules['streak_bonus_cap_weeks'] ?? 6));
        $maxExtra = max(0, (int)($rules['max_extra_sessions'] ?? 2));
        $n = max(1, min(7, $targetSessions));
        $k = max(0, $validSessions);
        $c = min($k, $n);
        $m = max(0, $n - $k);
        $e = min($maxExtra, max(0, $k - $n));
        $workoutPoints = $sessionPoints * $c - $missPenalty * $m + $extraPoints * $e;
        $completed = $k >= $n;
        $streakAfter = $completed ? $streakBefore + 1 : 0;
        $streakPoints = $completed ? $streakBonus * min($streakAfter, $streakCap) : 0;
        return ['n' => $n, 'k' => $k, 'c' => $c, 'm' => $m, 'e' => $e, 'p' => $workoutPoints, 'streak_after' => $streakAfter, 'streak_points' => $streakPoints, 'total' => $workoutPoints + $streakPoints, 'completed' => $completed];
    }

    public function settleWeek(PDO $db, int $userId, string $weekStart, int $target, int $valid, bool $keepStreak = false): array
    {
        $db->beginTransaction();
        try {
            $stmt = $db->prepare('SELECT total_points, current_streak, best_streak FROM users WHERE id = ? FOR UPDATE');
            $stmt->execute([$userId]); $user = $stmt->fetch();
            if (!$user) throw new \RuntimeException('Không tìm thấy hội viên.');
            $score = self::calculate($target, $valid, (int)$user['current_streak']);
            if ($keepStreak && !$score['completed']) { $score['streak_after'] = (int)$user['current_streak']; $score['streak_points'] = 0; $score['total'] = $score['p']; }
            $best = max((int)$user['best_streak'], (int)$score['streak_after']);
            $insert = $db->prepare('INSERT INTO weekly_scores (user_id, week_start, target_sessions, valid_sessions, completed_sessions, missed_sessions, extra_sessions, workout_points, streak_before, streak_after, streak_points, total_week_points, appeal_kept_streak) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $insert->execute([$userId, $weekStart, $score['n'], $valid, $score['c'], $score['m'], $score['e'], $score['p'], (int)$user['current_streak'], $score['streak_after'], $score['streak_points'], $score['total'], $keepStreak ? 1 : 0]);
            $update = $db->prepare('UPDATE users SET total_points = GREATEST(0, total_points + ?), current_streak = ?, best_streak = ? WHERE id = ?');
            $update->execute([$score['total'], $score['streak_after'], $best, $userId]);
            $db->commit(); return $score;
        } catch (\Throwable $error) { if ($db->inTransaction()) $db->rollBack(); throw $error; }
    }
}
