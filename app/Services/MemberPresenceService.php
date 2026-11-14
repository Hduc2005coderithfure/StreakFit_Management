<?php
declare(strict_types=1);
namespace App\Services;
use PDO;

final class MemberPresenceService
{
    public const ONLINE_SECONDS = 120;
    public function __construct(private PDO $db) {}
    public function heartbeat(int $userId, int $now): void
    {
        // Atomic upsert; a disabled preference is never overridden by a heartbeat.
        $q = $this->db->prepare('INSERT INTO member_presence (user_id,last_seen_at,share_status) VALUES (?,?,1) ON DUPLICATE KEY UPDATE last_seen_at = IF(share_status=1 AND (last_seen_at IS NULL OR last_seen_at < VALUES(last_seen_at)-20), VALUES(last_seen_at), last_seen_at)');
        $q->execute([$userId,$now]);
    }
    public function setSharing(int $userId, bool $enabled, int $now): void
    {
        $q = $this->db->prepare('INSERT INTO member_presence (user_id,last_seen_at,share_status) VALUES (?,?,?) ON DUPLICATE KEY UPDATE last_seen_at=VALUES(last_seen_at), share_status=VALUES(share_status)');
        $q->execute([$userId,$enabled?$now:null,$enabled?1:0]);
    }
    public function sharing(int $userId): bool
    {
        $q=$this->db->prepare('SELECT share_status FROM member_presence WHERE user_id=?');$q->execute([$userId]);
        $value=$q->fetchColumn();return $value===false || (int)$value===1;
    }
    public static function state(?int $lastSeen, bool $sharing, int $now): string
    {
        if (!$sharing) return 'hidden';
        if ($lastSeen===null || $lastSeen<=0) return 'unknown';
        return max(0,$now-$lastSeen)<=self::ONLINE_SECONDS?'online':'offline';
    }
    public function members(int $viewer, int $now): array
    {
        // Only active community accounts. Never expose email, IP, route, or session identifier.
        $q=$this->db->prepare('SELECT u.id,u.display_name,p.last_seen_at,COALESCE(p.share_status,1) AS share_status FROM users u LEFT JOIN member_presence p ON p.user_id=u.id WHERE u.is_active=1 AND u.id<>? ORDER BY CASE WHEN p.share_status=1 THEN p.last_seen_at ELSE NULL END DESC,u.display_name,u.id LIMIT 100');
        $q->execute([$viewer]);$result=[];
        foreach($q->fetchAll() as $row){
            $share=(int)$row['share_status']===1;$last=$share && $row['last_seen_at']!==null?(int)$row['last_seen_at']:null;
            $result[]=['id'=>(int)$row['id'],'name'=>(string)$row['display_name'],'state'=>self::state($last,$share,$now),'last_seen_at'=>$last];
        }
        return $result;
    }
}
