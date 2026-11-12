<?php
declare(strict_types=1);

namespace App\Services;

use DomainException;
use PDO;

final class CourtAppealService
{
    public function __construct(private readonly PDO $db) {}

    public function submit(int $userId, string $weekStart, string $monthStart, string $reason, ?string $proofPath): int
    {
        $reason = trim($reason);
        if ($proofPath === null || trim($proofPath) === '') throw new DomainException('Kháng cáo cần có ảnh minh chứng.');
        $today = new \DateTimeImmutable('now', new \DateTimeZone(sf_config()['timezone']));
        if ($weekStart !== sf_week_start($today) || $monthStart !== $today->modify('first day of this month')->format('Y-m-d')) throw new DomainException('Chỉ nhận kháng cáo cho tuần và tháng hiện tại.');
        if (mb_strlen($reason) < 10 || mb_strlen($reason) > 1000) throw new DomainException('Lý do kháng cáo cần từ 10 đến 1.000 ký tự.');
        $this->db->beginTransaction();
        try {
            $lock = $this->db->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE');
            $lock->execute([$userId]);
            if (!$lock->fetchColumn()) throw new DomainException('Không tìm thấy thành viên.');
            $goal = $this->db->prepare('SELECT target_sessions FROM weekly_goals WHERE user_id = ? AND week_start = ?');
            $goal->execute([$userId, $weekStart]);
            $target = (int)$goal->fetchColumn();
            $valid = $this->db->prepare("SELECT COUNT(DISTINCT workout_date) FROM workouts WHERE user_id = ? AND week_start = ? AND status = 'valid'");
            $valid->execute([$userId, $weekStart]);
            if ($target < 1 || (int)$valid->fetchColumn() >= $target) throw new DomainException('Chỉ có thể kháng cáo tuần chưa hoàn thành mục tiêu.');
            $uses = $this->db->prepare('SELECT COUNT(*) FROM appeals WHERE user_id = ? AND appeal_month = ?');
            $uses->execute([$userId, $monthStart]);
            if ((int)$uses->fetchColumn() >= sf_settings()['appeal_monthly_limit']) throw new DomainException('Bạn đã đạt giới hạn kháng cáo trong tháng.');
            $pending = $this->db->prepare("SELECT 1 FROM appeals WHERE user_id = ? AND week_start = ? AND status IN ('pending','accepted','rescue_pending') LIMIT 1");
            $pending->execute([$userId, $weekStart]);
            if ($pending->fetchColumn()) throw new DomainException('Tuần này đã có kháng cáo đang xử lý.');
            $stmt = $this->db->prepare('INSERT INTO appeals (user_id, week_start, appeal_month, reason, proof_path) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$userId, $weekStart, $monthStart, $reason, $proofPath]);
            $id = (int)$this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    public function vote(int $appealId, int $voterId, string $vote): void
    {
        if (!in_array($vote, ['yes', 'no'], true)) throw new DomainException('Phiếu biểu quyết không hợp lệ.');
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT user_id FROM appeals WHERE id = ? AND status = 'pending' FOR UPDATE");
            $stmt->execute([$appealId]);
            $ownerId = $stmt->fetchColumn();
            if ($ownerId === false || (int)$ownerId === $voterId) throw new DomainException('Không thể bỏ phiếu cho đơn của mình hoặc đơn đã đóng.');
            $this->db->prepare('INSERT INTO appeal_votes (appeal_id, voter_id, vote) VALUES (?, ?, ?)')->execute([$appealId, $voterId, $vote]);
            $this->db->commit();
        } catch (\PDOException $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            if ((string)$error->getCode() === '23000') throw new DomainException('Mỗi thành viên chỉ được bỏ một phiếu cho mỗi đơn.');
            throw $error;
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    public function voteSummary(int $appealId): array
    {
        $stmt = $this->db->prepare("SELECT SUM(vote = 'yes') AS yes_count, SUM(vote = 'no') AS no_count FROM appeal_votes WHERE appeal_id = ?");
        $stmt->execute([$appealId]);
        $row = $stmt->fetch() ?: [];
        $yes = (int)($row['yes_count'] ?? 0);
        $no = (int)($row['no_count'] ?? 0);
        $total = $yes + $no;
        $minimum = (new SettingsService($this->db))->all()['appeal_minimum_votes'];
        return ['yes' => $yes, 'no' => $no, 'total' => $total, 'minimum' => $minimum, 'advisory' => $total < $minimum ? 'insufficient' : ($yes > $no ? 'accepted' : ($no > $yes ? 'rejected' : 'tie'))];
    }
}
