<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\{Request,Response,Database,Session};
use App\Services\{AdminPermissionService,SportGroupService,FundPaymentService,PayOSClient,PenaltyPoolService,RouteTrackingService,BankTransferService};
use DomainException;

final class CommunityController
{
    public function handle(Request $request,string $page): Response
    {
        $db=Database::connection();$userId=Session::userId();
        $s=$db->prepare('SELECT * FROM users WHERE id=? AND is_active=1');$s->execute([$userId]);$user=$s->fetch();
        if (!$user) return Response::redirect(url('login'));
        $permissions=new AdminPermissionService($db);
        $admin=$page==='admin_groups' || $page==='admin_payments';
        $canManageGroups=$permissions->can($userId,'groups.manage');
        if ($admin && !$permissions->can($userId,$page==='admin_groups'?'groups.manage':'fund.view')) return new Response('Bạn chưa được cấp quyền.',403);
        $payments=new FundPaymentService($db,new PayOSClient(app_config()));
        $bankTransfers=new BankTransferService($db);
        $groups=new SportGroupService($db);
        $error=null;
        try {
            if ($request->method()==='POST') {
                sf_check_csrf();
                $action=(string)$request->input('action');
                if ($page==='groups' || $page==='admin_groups') {
                    $id=(int)$request->input('group_id',0);
                    if ($action==='save') $groups->save($userId,$request->all(),$id);
                    else $groups->change($userId,$id,$action);
                } elseif ($page==='payments' || $page==='admin_payments') {
                    if ($action==='bank_config' && $admin) {
                        $permissions->require($userId,'fund.manage');
                        $bankTransfers->saveConfiguration($userId,$request->all());
                    } elseif ($action==='bank_confirm' && $admin) {
                        $permissions->require($userId,'fund.manage');
                        $bankTransfers->confirm($userId,(int)$request->input('request_id'),(string)$request->input('bank_reference'),(int)$request->input('received_amount'),(string)$request->input('statement_memo'));
                    } elseif ($action==='bank_create' && !$admin) {
                        $bankTransfers->create($userId,(int)$request->input('fund_id'));
                    } elseif ($action==='pay' && !$admin) {
                        $order=$payments->create($userId,(int)$request->input('fund_id'));
                        if ($order['status']==='paid') return Response::redirect(url('fund/payments'));
                        return Response::redirect($order['checkout_url']);
                    } elseif ($action==='sync') {
                        $order=$payments->find((int)$request->input('order_id'));
                        if ((int)$order['user_id']!==$userId) $permissions->require($userId,'fund.manage');
                        $payments->sync((int)$order['id']);
                    } else throw new DomainException('Thao tác không hợp lệ.');
                }
                sf_flash('Đã cập nhật.');
                return Response::redirect(url(ltrim($request->path(),'/')));
            }
        } catch (DomainException $e) {$error=$e->getMessage();}
          catch (\Throwable $e) {error_log('StreakFit feature error: '.$e->getMessage());$error='Chưa thể xử lý yêu cầu. Hãy thử lại sau.';}
        $rows=[];$fund=null;$fundMember=null;$edit=null;$route=null;
        $bankConfig=[];$bankReady=false;$bankRequest=null;$bankRequests=[];
        if ($page==='groups' || $page==='admin_groups') {
            $rows=$groups->listing($userId,(string)$request->query('sport',''),$canManageGroups);
            $editId=(int)$request->query('edit',0);
            foreach($rows as $row) if ((int)$row['id']===$editId && ((int)$row['owner_id']===$userId || $canManageGroups)) $edit=$row;
        } elseif ($page==='payments' || $page==='admin_payments') {
            $s=$db->prepare('SELECT p.*,u.display_name,f.month_start FROM payment_orders p JOIN users u ON u.id=p.user_id JOIN monthly_funds f ON f.id=p.fund_id WHERE (?=1 OR p.user_id=?) ORDER BY p.id DESC LIMIT 100');$s->execute([$admin?1:0,$userId]);$rows=$s->fetchAll();
            $fundId=(new PenaltyPoolService($db,sf_settings()['monthly_contribution']))->ensureMonth(date('Y-m-01'));
            $s=$db->prepare('SELECT * FROM monthly_funds WHERE id=?');$s->execute([$fundId]);$fund=$s->fetch();
            $s=$db->prepare('SELECT * FROM fund_members WHERE fund_id=? AND user_id=?');$s->execute([$fundId,$userId]);$fundMember=$s->fetch();
            $bankConfig=$bankTransfers->configuration();
            $bankReady=$bankTransfers->configured();
            if ($admin) $bankRequests=$bankTransfers->forFund($fundId);
            else {
                $s=$db->prepare('SELECT * FROM bank_transfer_requests WHERE fund_id=? AND user_id=?');$s->execute([$fundId,$userId]);$bankRequest=$s->fetch()?:null;
            }
        } elseif ($page==='route') {
            try {$route=(new RouteTrackingService($db))->read($userId,(int)$request->query('id',0));}
            catch(DomainException $e){return new Response(sf_h($e->getMessage()),403);}
        }
        $ready=(new PayOSClient(app_config()))->ready();
        return new Response(view('community/index',compact('page','user','userId','rows','edit','error','admin','canManageGroups','permissions','fund','fundMember','ready','route','bankConfig','bankReady','bankRequest','bankRequests')),200,['Content-Type'=>'text/html; charset=utf-8','Cache-Control'=>'no-store']);
    }
    public function webhook(Request $request): Response
    {
        try {
            $raw=file_get_contents('php://input',false,null,0,65537);
            if (strlen((string)$raw)>65536) return Response::json(['ok'=>false],413);
            $payload=json_decode((string)$raw,true,64,JSON_THROW_ON_ERROR);
            (new FundPaymentService(Database::connection(),new PayOSClient(app_config())))->webhook($payload);
            return Response::json(['ok'=>true]);
        } catch (\DomainException|\JsonException|\TypeError $e) {return Response::json(['ok'=>false],400);}
          catch (\Throwable $e) {error_log('payOS webhook: '.$e->getMessage());return Response::json(['ok'=>false],503);}
    }
    public function track(Request $request): Response
    {
        $db=Database::connection();$id=Session::userId();
        $s=$db->prepare('SELECT id FROM users WHERE id=? AND is_active=1');$s->execute([$id]);
        if (!$s->fetchColumn()) return Response::json(['message'=>'Hãy đăng nhập lại.'],401);
        sf_check_csrf();
        try {
            $points=json_decode((string)$request->input('points','[]'),true,64,JSON_THROW_ON_ERROR);
            $service=new RouteTrackingService($db);
            if ($request->input('action')==='delete') $service->delete($id,(int)$request->input('workout_id'));
            else $service->append($id,(int)$request->input('workout_id'),$points);
            return Response::json(['ok'=>true]);
        } catch (\DomainException|\JsonException|\TypeError $e) {return Response::json(['message'=>$e->getMessage()],422);}
    }
}
