<?php
declare(strict_types=1);

namespace App\Services;

use DomainException;
use PDO;

final class PenaltyPoolService
{
    public function __construct(private readonly PDO $db, private readonly int $monthlyContribution = 20000) {}

    public function ensureMonth(string $monthStart): int
    {
        $stmt = $this->db->prepare('INSERT INTO monthly_funds (month_start, contribution_amount) VALUES (?, ?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)');
        $stmt->execute([$monthStart, $this->monthlyContribution]);
        return (int)$this->db->lastInsertId();
    }

    public function join(int $fundId, int $userId): void
    {
        $this->withLockedFund($fundId, function (string $status) use ($fundId, $userId): void {
            if (!in_array($status, ['open', 'voting'], true)) throw new DomainException('Quỹ tháng này đã chốt, không thể đăng ký.');
            $this->db->prepare("INSERT INTO fund_members (fund_id, user_id, opted_in) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE opted_in = 1")->execute([$fundId, $userId]);
        });
    }

    public function leave(int $fundId, int $userId): void
    {
        $this->withLockedFund($fundId, function (string $status) use ($fundId, $userId): void {
            if ($status !== 'open') throw new DomainException('Không thể rời quỹ sau khi đợt biểu quyết đã bắt đầu.');
            $pending = $this->db->prepare('SELECT id FROM payment_orders WHERE active_key = ? LIMIT 1');
            $pending->execute([$fundId . ':' . $userId]);
            if ($pending->fetchColumn()) throw new DomainException('Hãy hoàn tất hoặc hủy thanh toán trên payOS và kiểm tra trạng thái trước khi rời quỹ.');
            $bankPending=$this->db->prepare("SELECT id FROM bank_transfer_requests WHERE fund_id=? AND user_id=? AND status='pending' LIMIT 1");
            $bankPending->execute([$fundId,$userId]);
            if ($bankPending->fetchColumn()) throw new DomainException('Đang có mã QR chuyển khoản. Hãy liên hệ quản trị viên để đối soát trước khi rời quỹ.');
            $stmt = $this->db->prepare("UPDATE fund_members SET opted_in = 0 WHERE fund_id = ? AND user_id = ? AND contribution_status = 'unpaid' AND opted_in = 1");
            $stmt->execute([$fundId, $userId]);
            if ($stmt->rowCount() !== 1) throw new DomainException('Không tìm thấy đăng ký chưa thanh toán để rời quỹ.');
        });
    }

    public function propose(int $fundId, int $userId, int $amount): void
    {
        if ($amount < 1000 || $amount > 1000000 || $amount % 1000 !== 0) throw new DomainException('Mức phạt phải từ 1.000 đến 1.000.000 đồng, theo bước 1.000 đồng.');
        $this->withLockedFund($fundId, function (string $status) use ($fundId, $userId, $amount): void {
            if ($status !== 'open') throw new DomainException('Quỹ hiện không nhận đề xuất mới.');
            $this->assertActiveMember($fundId, $userId);
            $this->db->prepare('INSERT INTO penalty_proposals (fund_id, user_id, amount) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE amount = VALUES(amount)')->execute([$fundId, $userId, $amount]);
        });
    }

    public function vote(int $fundId, int $userId, int $amount): void
    {
        $this->withLockedFund($fundId, function (string $status) use ($fundId, $userId, $amount): void {
            if ($status !== 'voting') throw new DomainException('Đợt biểu quyết chưa mở hoặc đã đóng.');
            $this->assertActiveMember($fundId, $userId);
            $proposal = $this->db->prepare('SELECT 1 FROM penalty_proposals WHERE fund_id = ? AND amount = ? LIMIT 1');
            $proposal->execute([$fundId, $amount]);
            if (!$proposal->fetchColumn()) throw new DomainException('Mức phạt chưa được đề xuất.');
            $this->db->prepare('INSERT INTO penalty_votes (fund_id, user_id, amount) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE amount = VALUES(amount)')->execute([$fundId, $userId, $amount]);
        });
    }

    public function startVote(int $fundId, ?int $adminId = null): void
    {
        $this->withLockedFund($fundId, function (string $status) use ($fundId, $adminId): void {
            if ($status !== 'open') throw new DomainException('Quỹ không ở trạng thái mở đề xuất.');
            $count = $this->db->prepare('SELECT COUNT(*) FROM penalty_proposals WHERE fund_id = ?');
            $count->execute([$fundId]);
            if ((int)$count->fetchColumn() < 1) throw new DomainException('Cần có đề xuất trước khi mở biểu quyết.');
            $this->db->prepare("UPDATE monthly_funds SET status = 'voting' WHERE id = ?")->execute([$fundId]);
            if ($adminId !== null) (new AuditLogService($this->db))->record($adminId, 'fund_vote_started', 'fund', $fundId);
        });
    }

    public function closeVote(int $fundId, ?int $adminId = null): int
    {
        return $this->withLockedFund($fundId, function (string $status) use ($fundId, $adminId): int {
            if ($status !== 'voting') throw new DomainException('Đợt biểu quyết không còn mở.');
            $stmt = $this->db->prepare('SELECT amount FROM penalty_votes WHERE fund_id = ? GROUP BY amount ORDER BY COUNT(*) DESC, amount ASC LIMIT 1');
            $stmt->execute([$fundId]);
            $amount = $stmt->fetchColumn();
            if ($amount === false) throw new DomainException('Chưa có phiếu hợp lệ để chốt mức phạt.');
            $this->db->prepare("UPDATE monthly_funds SET final_penalty = ?, status = 'closed' WHERE id = ?")->execute([(int)$amount, $fundId]);
            if ($adminId !== null) (new AuditLogService($this->db))->record($adminId, 'fund_vote_closed', 'fund', $fundId, ['final_penalty' => (int)$amount]);
            return (int)$amount;
        });
    }

    private function withLockedFund(int $fundId, callable $operation): mixed
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT status FROM monthly_funds WHERE id = ? FOR UPDATE');
            $stmt->execute([$fundId]);
            $status = $stmt->fetchColumn();
            if ($status === false) throw new DomainException('Không tìm thấy quỹ tháng.');
            $result = $operation((string)$status);
            $this->db->commit();
            return $result;
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    private function assertActiveMember(int $fundId, int $userId): void
    {
        $member = $this->db->prepare('SELECT opted_in FROM fund_members WHERE fund_id = ? AND user_id = ?');
        $member->execute([$fundId, $userId]);
        if (!(int)$member->fetchColumn()) throw new DomainException('Bạn cần tham gia quỹ trước.');
    }
}
