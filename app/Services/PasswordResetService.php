<?php
declare(strict_types=1);

namespace App\Services;

use DomainException;
use PDO;

final class PasswordResetService
{
    public function __construct(private readonly PDO $db) {}

    public function request(string $email): void
    {
        $stmt = $this->db->prepare('SELECT id, email, display_name FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([mb_strtolower(trim($email))]);
        $user = $stmt->fetch();
        if (!$user) return;
        $token = bin2hex(random_bytes(32));
        $this->db->prepare('DELETE FROM password_reset_tokens WHERE user_id = ? OR expires_at < UTC_TIMESTAMP()')->execute([(int)$user['id']]);
        $this->db->prepare('INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 1 HOUR))')->execute([(int)$user['id'], hash('sha256', $token)]);
        $link = url('reset-password?token=' . rawurlencode($token));
        $subject = '=?UTF-8?B?' . base64_encode('Đặt lại mật khẩu StreakFit') . '?=';
        $body = "Xin chào {$user['display_name']},\n\nMở liên kết sau trong vòng 1 giờ để đặt lại mật khẩu:\n$link\n\nNếu bạn không yêu cầu, hãy bỏ qua email này.";
        @mail((string)$user['email'], $subject, $body, "Content-Type: text/plain; charset=UTF-8\r\n");
    }

    public function reset(string $token, string $password): void
    {
        if (strlen($password) < 8 || strlen($password) > 72) throw new DomainException('Mật khẩu cần có từ 8 đến 72 byte.');
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) throw new DomainException('Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.');
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT id, user_id FROM password_reset_tokens WHERE token_hash = ? AND used_at IS NULL AND expires_at > UTC_TIMESTAMP() FOR UPDATE');
            $stmt->execute([hash('sha256', $token)]);
            $reset = $stmt->fetch();
            if (!$reset) throw new DomainException('Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.');
            $this->updatePassword((int)$reset['user_id'], $password);
            (new AuditLogService($this->db))->record((int)$reset['user_id'], 'password_reset', 'user', (int)$reset['user_id']);
            $this->db->commit();
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    public function change(int $userId, string $currentPassword, string $newPassword): void
    {
        if (strlen($newPassword) < 8 || strlen($newPassword) > 72) throw new DomainException('Mật khẩu mới cần có từ 8 đến 72 byte.');
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT password_hash FROM users WHERE id = ? AND is_active = 1 FOR UPDATE');
            $stmt->execute([$userId]);
            $hash = $stmt->fetchColumn();
            if ($hash === false || !password_verify($currentPassword, (string)$hash)) throw new DomainException('Mật khẩu hiện tại chưa chính xác.');
            $this->updatePassword($userId, $newPassword);
            (new AuditLogService($this->db))->record($userId, 'password_changed', 'user', $userId);
            $this->db->commit();
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    private function updatePassword(int $userId, string $password): void
    {
        $stmt = $this->db->prepare('UPDATE users SET password_hash = ?, password_changed_at = UTC_TIMESTAMP() WHERE id = ? AND is_active = 1');
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $userId]);
        if ($stmt->rowCount() !== 1) throw new DomainException('Tài khoản không còn hoạt động.');
        $this->db->prepare('UPDATE password_reset_tokens SET used_at = UTC_TIMESTAMP() WHERE user_id = ? AND used_at IS NULL')->execute([$userId]);
    }
}
