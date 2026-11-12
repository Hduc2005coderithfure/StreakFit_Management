<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class WeeklyRecord extends Model
{
    protected function table(): string { return 'weekly_scores'; }
    public function forUser(int $userId, int $limit = 12): array
    {
        $stmt = $this->db->prepare('SELECT * FROM weekly_scores WHERE user_id = ? ORDER BY week_start DESC LIMIT ' . max(1, min(100, $limit)));
        $stmt->execute([$userId]); return $stmt->fetchAll();
    }
}
