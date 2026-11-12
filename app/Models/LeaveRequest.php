<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class LeaveRequest extends Model
{
    protected function table(): string { return 'appeals'; }
    public function pendingForWeek(string $weekStart): array { return $this->many("SELECT * FROM appeals WHERE week_start = ? AND status = 'pending' ORDER BY created_at", [$weekStart]); }
}
