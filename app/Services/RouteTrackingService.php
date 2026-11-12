<?php
declare(strict_types=1);
namespace App\Services;
use PDO;
use DomainException;
final class RouteTrackingService
{
    public function __construct(private readonly PDO $db) {}
    public function read(int $user,int $id): array
    {
        $s=$this->db->prepare('SELECT *,UNIX_TIMESTAMP(checked_in_at)*1000 AS started_ms FROM workouts WHERE id=?');$s->execute([$id]);$w=$s->fetch();
        if (!$w || ((int)$w['user_id']!==$user && !(new AdminPermissionService($this->db))->can($user,'workouts.view'))) throw new DomainException('Bạn không có quyền xem tuyến đường này.');
        $s=$this->db->prepare('SELECT latitude AS lat,longitude AS lng,accuracy,recorded_ms AS t FROM workout_route_points WHERE workout_id=? ORDER BY recorded_ms');$s->execute([$id]);
        return ['workout'=>$w,'points'=>$s->fetchAll()];
    }
    public function append(int $user,int $id,array $points): void
    {
        if (count($points)>100) throw new DomainException('Chỉ nhận tối đa 100 điểm mỗi lần.');
        $this->db->beginTransaction();
        try {
            $s=$this->db->prepare("SELECT *,UNIX_TIMESTAMP(checked_in_at)*1000 AS start_ms FROM workouts WHERE id=? AND user_id=? FOR UPDATE");$s->execute([$id,$user]);$w=$s->fetch();
            if (!$w || $w['status']!=='in_progress') throw new DomainException('Buổi tập đã kết thúc hoặc không thuộc tài khoản này.');
            $s=$this->db->prepare('SELECT COUNT(*) FROM workout_route_points WHERE workout_id=?');$s->execute([$id]);
            if ((int)$s->fetchColumn()+count($points)>10000) throw new DomainException('Buổi tập đã đạt giới hạn điểm GPS.');
            $insert=$this->db->prepare('INSERT IGNORE INTO workout_route_points (workout_id,recorded_ms,latitude,longitude,accuracy) VALUES (?,?,?,?,?)');
            foreach($points as $p) {
                foreach(['lat','lng','accuracy','t'] as $key) if (!isset($p[$key]) || !is_numeric($p[$key]) || !is_finite((float)$p[$key])) throw new DomainException('Dữ liệu GPS không hợp lệ.');
                if (abs((float)$p['lat'])>90 || abs((float)$p['lng'])>180 || $p['accuracy']<0 || $p['accuracy']>100 || $p['t']<$w['start_ms'] || $p['t']>microtime(true)*1000+30000) throw new DomainException('Điểm GPS ngoài phạm vi hoặc sai thời gian.');
                $insert->execute([$id,(int)$p['t'],(float)$p['lat'],(float)$p['lng'],(float)$p['accuracy']]);
            }
            $this->db->commit();
        } catch (\Throwable $e) {if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }
    public function delete(int $user,int $id): void
    {
        $this->db->beginTransaction();
        try {
            $s=$this->db->prepare('SELECT id FROM workouts WHERE id=? AND user_id=? FOR UPDATE');$s->execute([$id,$user]);
            if (!$s->fetchColumn()) throw new DomainException('Bạn chỉ được xóa GPS buổi tập của mình.');
            $this->db->prepare('DELETE FROM workout_route_points WHERE workout_id=?')->execute([$id]);
            $this->db->commit();
        } catch (\Throwable $e) {if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }
}
