<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

final class PenaltyLog extends Model
{
    protected function table(): string { return 'fund_transactions'; }
    public function forFund(int $fundId): array { return $this->many('SELECT * FROM fund_transactions WHERE fund_id = ? ORDER BY created_at DESC', [$fundId]); }
}
