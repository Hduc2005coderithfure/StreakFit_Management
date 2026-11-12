<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class Workout extends Model
{
    protected function table(): string { return 'workouts'; }
    public function activeFor(int $userId): array|false { return $this->one("SELECT * FROM workouts WHERE user_id = ? AND status = 'in_progress' ORDER BY id DESC LIMIT 1", [$userId]); }
    public function validDays(int $userId, string $weekStart): int
    {
        $row = $this->one("SELECT COUNT(DISTINCT workout_date) AS n FROM workouts WHERE user_id = ? AND week_start = ? AND status = 'valid'", [$userId, $weekStart]);
        return (int)($row['n'] ?? 0);
    }
    public function history(int $userId, int $limit = 50): array
    {
        $stmt = $this->db->prepare('SELECT * FROM workouts WHERE user_id = ? ORDER BY checked_in_at DESC LIMIT ' . max(1, min(200, $limit)));
        $stmt->execute([$userId]); return $stmt->fetchAll();
    }
}
