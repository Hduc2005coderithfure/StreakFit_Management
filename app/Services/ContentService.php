<?php
declare(strict_types=1);

namespace App\Services;

use DomainException;
use PDO;

final class ContentService
{
    public function __construct(private readonly PDO $db) {}

    public function editPost(int $actorId, bool $isAdmin, int $workoutId, string $caption): void
    {
        $caption = trim($caption);
        if (mb_strlen($caption) > 500) throw new DomainException('Chú thích tối đa 500 ký tự.');
        $this->mutate($actorId, 'post_edited', 'post', $workoutId, function () use ($actorId, $isAdmin, $workoutId, $caption): void {
            $stmt = $this->db->prepare('UPDATE workout_posts SET caption = ? WHERE workout_id = ? AND (? = 1 OR user_id = ?)');
            $stmt->execute([$caption, $workoutId, $isAdmin ? 1 : 0, $actorId]);
            if ($stmt->rowCount() < 1) {
                $check = $this->db->prepare('SELECT 1 FROM workout_posts WHERE workout_id = ? AND (? = 1 OR user_id = ?)');
                $check->execute([$workoutId, $isAdmin ? 1 : 0, $actorId]);
                if (!$check->fetchColumn()) throw new DomainException('Bài đăng không tồn tại hoặc bạn không có quyền sửa.');
            }
        });
    }

    public function deletePost(int $actorId, bool $isAdmin, int $workoutId): void
    {
        $this->mutate($actorId, 'post_deleted', 'post', $workoutId, function () use ($actorId, $isAdmin, $workoutId): void {
            $stmt = $this->db->prepare('DELETE FROM workout_posts WHERE workout_id = ? AND (? = 1 OR user_id = ?)');
            $stmt->execute([$workoutId, $isAdmin ? 1 : 0, $actorId]);
            if ($stmt->rowCount() !== 1) throw new DomainException('Bài đăng không tồn tại hoặc bạn không có quyền xóa.');
            $this->db->prepare('DELETE FROM workout_comments WHERE workout_id = ?')->execute([$workoutId]);
            $this->db->prepare('DELETE FROM workout_reactions WHERE workout_id = ?')->execute([$workoutId]);
        });
    }

    public function editComment(int $actorId, bool $isAdmin, int $commentId, string $comment): void
    {
        $comment = trim($comment);
        if ($comment === '' || mb_strlen($comment) > 500) throw new DomainException('Bình luận cần có nội dung và tối đa 500 ký tự.');
        $this->mutate($actorId, 'comment_edited', 'comment', $commentId, function () use ($actorId, $isAdmin, $commentId, $comment): void {
            $stmt = $this->db->prepare('UPDATE workout_comments SET comment_text = ? WHERE id = ? AND (? = 1 OR user_id = ?)');
            $stmt->execute([$comment, $commentId, $isAdmin ? 1 : 0, $actorId]);
            if ($stmt->rowCount() < 1) {
                $check = $this->db->prepare('SELECT 1 FROM workout_comments WHERE id = ? AND (? = 1 OR user_id = ?)');
                $check->execute([$commentId, $isAdmin ? 1 : 0, $actorId]);
                if (!$check->fetchColumn()) throw new DomainException('Bình luận không tồn tại hoặc bạn không có quyền sửa.');
            }
        });
    }

    public function deleteComment(int $actorId, bool $isAdmin, int $commentId): void
    {
        $this->mutate($actorId, 'comment_deleted', 'comment', $commentId, function () use ($actorId, $isAdmin, $commentId): void {
            $stmt = $this->db->prepare('DELETE FROM workout_comments WHERE id = ? AND (? = 1 OR user_id = ?)');
            $stmt->execute([$commentId, $isAdmin ? 1 : 0, $actorId]);
            if ($stmt->rowCount() !== 1) throw new DomainException('Bình luận không tồn tại hoặc bạn không có quyền xóa.');
        });
    }

    public function report(int $reporterId, string $type, int $contentId, string $reason): int
    {
        $reason = trim($reason);
        if (!in_array($type, ['post', 'comment'], true) || $contentId < 1 || mb_strlen($reason) < 5 || mb_strlen($reason) > 500) throw new DomainException('Báo cáo cần chọn nội dung hợp lệ và lý do từ 5 đến 500 ký tự.');
        $table = $type === 'post' ? 'workout_posts' : 'workout_comments';
        $column = $type === 'post' ? 'workout_id' : 'id';
        $this->db->beginTransaction();
        try {
            $exists = $this->db->prepare("SELECT user_id FROM $table WHERE $column = ? FOR UPDATE");
            $exists->execute([$contentId]);
            $ownerId = $exists->fetchColumn();
            if ($ownerId === false) throw new DomainException('Không tìm thấy nội dung cần báo cáo.');
            if ((int)$ownerId === $reporterId) throw new DomainException('Bạn không thể báo cáo nội dung của chính mình.');
            $duplicate = $this->db->prepare("SELECT 1 FROM content_reports WHERE reporter_id = ? AND content_type = ? AND content_id = ? AND status = 'pending' FOR UPDATE");
            $duplicate->execute([$reporterId, $type, $contentId]);
            if ($duplicate->fetchColumn()) throw new DomainException('Bạn đã gửi báo cáo cho nội dung này.');
            $stmt = $this->db->prepare('INSERT INTO content_reports (reporter_id, content_type, content_id, reason) VALUES (?, ?, ?, ?)');
            $stmt->execute([$reporterId, $type, $contentId, $reason]);
            $id = (int)$this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    private function mutate(int $actorId, string $action, string $entityType, int $entityId, callable $operation): void
    {
        $this->db->beginTransaction();
        try {
            $operation();
            $isAdmin = (new \App\Models\User($this->db))->find($actorId)['role'] === 'admin';
            if ($isAdmin) (new AuditLogService($this->db))->record($actorId, $action, $entityType, $entityId);
            $this->db->commit();
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }
}
