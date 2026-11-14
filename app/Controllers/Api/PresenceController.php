<?php
declare(strict_types=1);
namespace App\Controllers\Api;
use App\Core\{Database,Request,Response,Session};
use App\Services\MemberPresenceService;

final class PresenceController
{
    private function reply(array $payload, int $status=200): Response
    {
        return new Response((string)json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$status,['Content-Type'=>'application/json; charset=utf-8','Cache-Control'=>'no-store, private']);
    }
    public function index(Request $request): Response
    {
        $id=Session::userId();
        if ($id<1) return $this->reply(['message'=>'Bạn cần đăng nhập.'],401);
        $token=$request->input('_csrf');
        if (!is_string($token)||$token===''||empty($_SESSION['sf_csrf'])||!hash_equals((string)$_SESSION['sf_csrf'],$token)) return $this->reply(['message'=>'Phiên đã hết hạn. Hãy tải lại trang.'],419);
        $action=$request->input('action','heartbeat');
        if (!in_array($action,['heartbeat','sharing'],true)) return $this->reply(['message'=>'Yêu cầu không hợp lệ.'],422);
        $enabled=$request->input('enabled');
        if ($action==='sharing' && !in_array($enabled,['0','1'],true)) return $this->reply(['message'=>'Tùy chọn không hợp lệ.'],422);
        $now=time();
        // Shared session throttle across tabs; release PHP session lock before database work.
        if ($action==='heartbeat' && $now-(int)($_SESSION['sf41_presence_ping']??0)<8) return $this->reply(['message'=>'Đang cập nhật.'],429);
        if ($action==='heartbeat') $_SESSION['sf41_presence_ping']=$now;
        if (session_status()===PHP_SESSION_ACTIVE) session_write_close();
        try {
            $db=Database::connection();$q=$db->prepare('SELECT is_active FROM users WHERE id=?');$q->execute([$id]);
            if ((int)$q->fetchColumn()!==1) return $this->reply(['message'=>'Tài khoản không khả dụng.'],403);
            $presence=new MemberPresenceService($db);
            if ($action==='sharing') $presence->setSharing($id,$enabled==='1',$now); else $presence->heartbeat($id,$now);
            $data=['server_time'=>$now,'online_seconds'=>MemberPresenceService::ONLINE_SECONDS,'sharing'=>$presence->sharing($id)];
            if ($request->input('list')==='1') $data['members']=$presence->members($id,$now);
            return $this->reply($data);
        } catch (\Throwable $e) {
            // Missing migration must not break any member page; log only the error code.
            error_log('StreakFit presence unavailable: '.$e->getCode());
            return $this->reply(['message'=>'Trạng thái thành viên chưa khả dụng. Hãy liên hệ quản trị viên.'],503);
        }
    }
}
