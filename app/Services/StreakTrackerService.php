<?php
declare(strict_types=1);

namespace App\Services;

use PDO;

final class StreakTrackerService
{
    public function __construct(private readonly PDO $db) {}
    public function current(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT current_streak FROM users WHERE id = ?'); $stmt->execute([$userId]); return (int)$stmt->fetchColumn();
    }
    public function best(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT best_streak FROM users WHERE id = ?'); $stmt->execute([$userId]); return (int)$stmt->fetchColumn();
    }
    public function update(int $userId, int $streak): void
    {
        $stmt = $this->db->prepare('UPDATE users SET current_streak = ?, best_streak = GREATEST(best_streak, ?) WHERE id = ?'); $stmt->execute([max(0, $streak), max(0, $streak), $userId]);
    }
}
