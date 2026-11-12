<?php
declare(strict_types=1);
namespace App\Services;
use PDO;
use DomainException;

final class SportGroupService
{
    public const SPORTS = ['running'=>'Chạy bộ', 'cycling'=>'Đạp xe', 'walking'=>'Đi bộ', 'gym'=>'Gym', 'swimming'=>'Bơi lội', 'other'=>'Khác'];
    public function __construct(private readonly PDO $db) {}
    public function save(int $actor, array $input, int $id = 0): int
    {
        $name = trim((string)($input['name'] ?? ''));
        $sport = (string)($input['sport'] ?? '');
        $description = trim((string)($input['description'] ?? ''));
        $meeting = trim((string)($input['meeting_point'] ?? ''));
        $destination = trim((string)($input['destination'] ?? ''));
        if (mb_strlen($name) < 3 || mb_strlen($name) > 100 || !isset(self::SPORTS[$sport]) || mb_strlen($description) > 1000 || $meeting === '' || mb_strlen($meeting) > 255 || mb_strlen($destination) > 255) throw new DomainException('Tên nhóm, môn thể thao hoặc điểm hẹn chưa hợp lệ.');
        $date = trim((string)($input['meeting_at'] ?? ''));
        $when = $date === '' ? null : \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $date, new \DateTimeZone(app_config()['timezone']));
        if ($date !== '' && (!$when || $when->format('Y-m-d\TH:i') !== $date)) throw new DomainException('Giờ hẹn chưa hợp lệ.');
        $this->db->beginTransaction();
        try {
            if ($id) $this->assertManager($actor, $id);
            $values = [$name,$sport,$description,$meeting,$destination,$when ? $when->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s') : null];
            if ($id) $this->db->prepare('UPDATE sport_groups SET name=?,sport=?,description=?,meeting_point=?,destination=?,meeting_at=? WHERE id=?')->execute([...$values,$id]);
            else {
                $this->db->prepare('INSERT INTO sport_groups (name,sport,description,meeting_point,destination,meeting_at,owner_id) VALUES (?,?,?,?,?,?,?)')->execute([...$values,$actor]);
                $id=(int)$this->db->lastInsertId();
                $this->db->prepare('INSERT INTO sport_group_members (group_id,user_id) VALUES (?,?)')->execute([$id,$actor]);
            }
            (new AuditLogService($this->db))->record($actor,'group_saved','sport_group',$id);
            $this->db->commit(); return $id;
        } catch (\Throwable $e) { if ($this->db->inTransaction()) $this->db->rollBack(); throw $e; }
    }
    private function assertManager(int $actor,int $id): array
    {
        $s=$this->db->prepare('SELECT * FROM sport_groups WHERE id=? FOR UPDATE'); $s->execute([$id]); $g=$s->fetch();
        if (!$g || ((int)$g['owner_id'] !== $actor && !(new AdminPermissionService($this->db))->can($actor,'groups.manage'))) throw new DomainException('Bạn không có quyền quản lý nhóm này.');
        return $g;
    }
    public function change(int $actor,int $id,string $action): void
    {
        $this->db->beginTransaction();
        try {
            $s=$this->db->prepare('SELECT * FROM sport_groups WHERE id=? FOR UPDATE');$s->execute([$id]);$g=$s->fetch();
            if (!$g) throw new DomainException('Không tìm thấy nhóm.');
            if (in_array($action,['archive','reopen'],true)) {
                $this->assertManager($actor,$id);
                $this->db->prepare('UPDATE sport_groups SET status=? WHERE id=?')->execute([$action==='archive'?'archived':'active',$id]);
                (new AuditLogService($this->db))->record($actor,'group_'.$action,'sport_group',$id);
            } elseif ($action==='join') {
                if ($g['status']!=='active') throw new DomainException('Nhóm đã đóng.');
                $this->db->prepare('INSERT IGNORE INTO sport_group_members (group_id,user_id) VALUES (?,?)')->execute([$id,$actor]);
            } elseif ($action==='leave') {
                if ((int)$g['owner_id']===$actor) throw new DomainException('Trưởng nhóm cần đóng nhóm thay vì rời nhóm.');
                $this->db->prepare('DELETE FROM sport_group_members WHERE group_id=? AND user_id=?')->execute([$id,$actor]);
            } else throw new DomainException('Thao tác không hợp lệ.');
            $this->db->commit();
        } catch (\Throwable $e) { if ($this->db->inTransaction()) $this->db->rollBack(); throw $e; }
    }
    public function listing(int $actor,string $sport='',bool $all=false): array
    {
        $s=$this->db->prepare("SELECT g.*,u.display_name,(SELECT COUNT(*) FROM sport_group_members m WHERE m.group_id=g.id) AS members,EXISTS(SELECT 1 FROM sport_group_members m WHERE m.group_id=g.id AND m.user_id=?) AS joined FROM sport_groups g JOIN users u ON u.id=g.owner_id WHERE (?='' OR g.sport=?) AND (g.status='active' OR ?=1 OR g.owner_id=?) ORDER BY g.created_at DESC,g.id DESC LIMIT 100");
        $s->execute([$actor,$sport,$sport,$all?1:0,$actor]);return $s->fetchAll();
    }
}
