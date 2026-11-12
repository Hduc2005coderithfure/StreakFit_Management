<?php
declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use DomainException;
use PDO;
use RuntimeException;

final class CheckInService
{
    public function __construct(private readonly PDO $db, private readonly int $minimumSeconds = 1800, private readonly int $maximumOpenSeconds = 43200) {}

    public function start(int $userId, string $imageData, string $timezone): int
    {
        $local = new DateTimeImmutable('now', new DateTimeZone($timezone));
        $image = $this->storeCapture($imageData, $userId, 'checkin');
        $this->db->beginTransaction();
        try {
            $this->lockUser($userId);
            $expire = $this->db->prepare("UPDATE workouts SET status = 'abandoned', checked_out_at = UTC_TIMESTAMP(), duration_seconds = LEAST(GREATEST(0, TIMESTAMPDIFF(SECOND, checked_in_at, UTC_TIMESTAMP())), ?) WHERE user_id = ? AND status = 'in_progress' AND checked_in_at <= DATE_SUB(UTC_TIMESTAMP(), INTERVAL ? SECOND)");
            $expire->execute([$this->maximumOpenSeconds, $userId, $this->maximumOpenSeconds]);
            $active = $this->db->prepare("SELECT id, TIMESTAMPDIFF(SECOND, checked_in_at, UTC_TIMESTAMP()) AS elapsed FROM workouts WHERE user_id = ? AND status = 'in_progress' ORDER BY id DESC LIMIT 1 FOR UPDATE");
            $active->execute([$userId]);
            $activeWorkout = $active->fetch();
            if ($activeWorkout) throw new DomainException('A workout is already in progress.');
            $daily = $this->db->prepare("SELECT COUNT(*) FROM workouts WHERE user_id = ? AND workout_date = ? AND status = 'valid'");
            $daily->execute([$userId, $local->format('Y-m-d')]);
            if ((int)$daily->fetchColumn() > 0) throw new DomainException('Mỗi ngày chỉ ghi nhận một buổi tập hợp lệ.');
            $stmt = $this->db->prepare('INSERT INTO workouts (user_id, week_start, workout_date, checked_in_at, checkin_image, status) VALUES (?, ?, ?, UTC_TIMESTAMP(), ?, \'in_progress\')');
            $stmt->execute([$userId, $local->modify('monday this week')->format('Y-m-d'), $local->format('Y-m-d'), $image]);
            $id = (int)$this->db->lastInsertId(); $this->db->commit(); return $id;
        } catch (\Throwable $error) { if ($this->db->inTransaction()) $this->db->rollBack(); $this->deleteCapture($image); throw $error; }
    }

    public function finish(int $userId, string $imageData, string $timezone, string $caption = '', ?int $expectedWorkoutId = null): array
    {
        $image = $this->storeCapture($imageData, $userId, 'checkout');
        $this->db->beginTransaction();
        try {
            $this->lockUser($userId);
            $stmt = $this->db->prepare("SELECT id, workout_date, TIMESTAMPDIFF(SECOND, checked_in_at, UTC_TIMESTAMP()) AS elapsed FROM workouts WHERE user_id = ? AND status = 'in_progress' ORDER BY id DESC LIMIT 1 FOR UPDATE");
            $stmt->execute([$userId]); $workout = $stmt->fetch();
            if (!$workout || ($expectedWorkoutId !== null && (int)$workout['id'] !== $expectedWorkoutId)) throw new DomainException('Không tìm thấy buổi tập đang diễn ra.');
            $elapsed = max(0, (int)$workout['elapsed']);
            if ($elapsed >= $this->maximumOpenSeconds) {
                $this->db->prepare("UPDATE workouts SET checked_out_at = UTC_TIMESTAMP(), duration_seconds = ?, status = 'abandoned' WHERE id = ?")->execute([$elapsed, (int)$workout['id']]);
                $this->db->commit();
                $this->deleteCapture($image);
                throw new DomainException('This workout exceeded the check-out time limit and was closed.');
            }
            $daily = $this->db->prepare("SELECT COUNT(*) FROM workouts WHERE user_id = ? AND workout_date = ? AND status = 'valid'");
            $daily->execute([$userId, $workout['workout_date']]);
            $valid = $elapsed >= $this->minimumSeconds && (int)$daily->fetchColumn() === 0;
            $stmt = $this->db->prepare('UPDATE workouts SET checked_out_at = UTC_TIMESTAMP(), duration_seconds = ?, checkout_image = ?, status = ? WHERE id = ?');
            $stmt->execute([$elapsed, $image, $valid ? 'valid' : 'invalid', (int)$workout['id']]);
            if ($valid) {
                $post = $this->db->prepare('INSERT INTO workout_posts (workout_id, user_id, caption) VALUES (?, ?, ?)');
                $cleanCaption = trim($caption);
                $post->execute([(int)$workout['id'], $userId, mb_substr($cleanCaption !== '' ? $cleanCaption : 'Vừa hoàn thành một buổi tập cùng StreakFit 🔥', 0, 500)]);
                $minutes = (int)ceil($elapsed / 60);
                $this->db->prepare('UPDATE users SET total_sessions = total_sessions + 1, total_minutes = total_minutes + ? WHERE id = ?')->execute([$minutes, $userId]);
            }
            $this->db->commit(); return ['valid' => $valid, 'duration_seconds' => $elapsed, 'workout_id' => (int)$workout['id']];
        } catch (\Throwable $error) { if ($this->db->inTransaction()) $this->db->rollBack(); $this->deleteCapture($image); throw $error; }
    }

    public function discard(int $userId, int $workoutId): void
    {
        $this->db->beginTransaction();
        try {
            $this->lockUser($userId);
            $stmt = $this->db->prepare("SELECT id FROM workouts WHERE id = ? AND user_id = ? AND status = 'in_progress' FOR UPDATE");
            $stmt->execute([$workoutId, $userId]);
            if (!$stmt->fetchColumn()) throw new DomainException('Không tìm thấy buổi tập đang mở để hủy.');
            $update = $this->db->prepare("UPDATE workouts SET status = 'abandoned', checked_out_at = UTC_TIMESTAMP(), duration_seconds = GREATEST(0, TIMESTAMPDIFF(SECOND, checked_in_at, UTC_TIMESTAMP())) WHERE id = ?");
            $update->execute([$workoutId]);
            $this->db->commit();
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    private function lockUser(int $userId): void
    {
        $stmt = $this->db->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE'); $stmt->execute([$userId]);
        if (!$stmt->fetchColumn()) throw new RuntimeException('Không tìm thấy hội viên.');
    }
    private function storeCapture(string $dataUrl, int $userId, string $kind): string
    {
        if (!in_array($kind, ['checkin', 'checkout'], true) || !preg_match('#^data:image/jpeg;base64,([A-Za-z0-9+/=]+)$#', $dataUrl, $match)) throw new DomainException('Ảnh phải được chụp bằng camera dưới dạng JPEG.');
        $binary = base64_decode($match[1], true);
        $info = $binary === false ? false : @getimagesizefromstring($binary);
        if ($binary === false || strlen($binary) > 4 * 1024 * 1024 || !$info || ($info['mime'] ?? '') !== 'image/jpeg') throw new DomainException('Ảnh JPEG không hợp lệ hoặc vượt quá 4 MB.');
        $directory = dirname(__DIR__, 2) . '/storage/uploads/streakfit';
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) throw new RuntimeException('Không thể tạo thư mục lưu ảnh.');
        $file = sprintf('%d_%s_%s.jpg', $userId, $kind, bin2hex(random_bytes(12)));
        if (file_put_contents($directory . '/' . $file, $binary, LOCK_EX) === false) throw new RuntimeException('Không thể lưu ảnh.');
        return $file;
    }
    private function deleteCapture(string $file): void { @unlink(dirname(__DIR__, 2) . '/storage/uploads/streakfit/' . basename($file)); }
}
