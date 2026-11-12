<?php
declare(strict_types=1);

namespace App\Services;

use PDO;

final class AuditLogService
{
    public function __construct(private readonly PDO $db) {}

    public function record(?int $actorId, string $action, string $entityType, ?int $entityId, array $details = []): void
    {
        $stmt = $this->db->prepare('INSERT INTO audit_logs (actor_id, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $actorId,
            $action,
            $entityType,
            $entityId,
            $details ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) : null,
            substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: null,
        ]);
    }
}
