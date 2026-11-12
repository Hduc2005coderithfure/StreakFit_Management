<?php
declare(strict_types=1);
namespace App\Services;
use PDO;
use DomainException;

final class FundPaymentService
{
    public function __construct(private readonly PDO $db,private readonly PayOSClient $gateway) {}
    public function create(int $user,int $fund): array
    {
        if (!$this->gateway->ready()) throw new DomainException('Quỹ chưa bật thanh toán chuyển khoản trực tuyến.');
        $this->db->beginTransaction();
        try {
            $s=$this->db->prepare('SELECT contribution_amount FROM monthly_funds WHERE id=? FOR UPDATE');$s->execute([$fund]);$amount=$s->fetchColumn();
            $s=$this->db->prepare('SELECT * FROM fund_members WHERE fund_id=? AND user_id=? FOR UPDATE');$s->execute([$fund,$user]);$member=$s->fetch();
            if (!$amount || !$member || !(int)$member['opted_in'] || $member['contribution_status']==='paid') throw new DomainException('Bạn cần tham gia quỹ và chưa đóng khoản này.');
            $s=$this->db->prepare("SELECT id FROM bank_transfer_requests WHERE fund_id=? AND user_id=? AND status='pending' LIMIT 1");$s->execute([$fund,$user]);
            if ($s->fetchColumn()) throw new DomainException('Đang có mã QR chuyển khoản ngân hàng. Hãy hoàn tất đơn đó trước khi tạo đơn payOS.');
            $s=$this->db->prepare('SELECT * FROM payment_orders WHERE active_key=? FOR UPDATE');$s->execute([$fund.':'.$user]);$order=$s->fetch();
            if (!$order) {
                $code=random_int(100000000000,999999999999);
                $this->db->prepare('INSERT INTO payment_orders (order_code,fund_id,user_id,amount,active_key) VALUES (?,?,?,?,?)')->execute([$code,$fund,$user,(int)$amount,$fund.':'.$user]);
                $order=$this->find((int)$this->db->lastInsertId());
            }
            if ($order['status']==='review') throw new DomainException('Khoản chuyển tiền này cần quản trị viên đối soát.');
            $this->db->commit();
        } catch (\Throwable $e) {if($this->db->inTransaction())$this->db->rollBack();throw $e;}
        if ($order['checkout_url']) return $order;
        // Retry the same merchant order code after a network timeout; never create a second debt.
        try { $data=$this->gateway->create($order); }
        catch (\Throwable $e) {
            // A successful remote create may have lost its response. Synchronize before retrying.
            try {
                $recovered=$this->sync((int)$order['id']);
                if ($recovered['checkout_url'] || $recovered['status']==='paid') return $recovered;
            } catch (\Throwable) {}
            throw $e;
        }
        if ((int)($data['orderCode']??0)!==(int)$order['order_code'] || (int)($data['amount']??0)!==(int)$order['amount'] || empty($data['paymentLinkId'])) throw new DomainException('Thông tin đơn thanh toán không khớp.');
        $link=(string)($data['checkoutUrl']??'');
        if (parse_url($link,PHP_URL_SCHEME)!=='https' || !in_array(parse_url($link,PHP_URL_HOST),['pay.payos.vn'],true)) throw new DomainException('Địa chỉ thanh toán không hợp lệ.');
        $this->db->prepare("UPDATE payment_orders SET payment_link_id=?,checkout_url=?,status=IF(status='creating','pending',status) WHERE id=?")->execute([$data['paymentLinkId'],$link,$order['id']]);
        return $this->find((int)$order['id']);
    }
    public function find(int $id): array
    {
        $s=$this->db->prepare('SELECT * FROM payment_orders WHERE id=?');$s->execute([$id]);$row=$s->fetch();
        if (!$row) throw new DomainException('Không tìm thấy giao dịch.');return $row;
    }
    public function webhook(array $payload): void
    {
        $data=$this->gateway->verify($payload);
        if (($data['code']??'')!=='00' || ($data['currency']??'')!=='VND') throw new DomainException('Thông báo không phải thanh toán VND thành công.');
        $s=$this->db->prepare('SELECT id FROM payment_orders WHERE order_code=?');$s->execute([(int)($data['orderCode']??0)]);$id=$s->fetchColumn();
        // payOS also sends a signed sample when a webhook is registered.
        if (!$id) return;
        $order=$this->find((int)$id);
        if ($order['payment_link_id'] && $order['payment_link_id']!==($data['paymentLinkId']??'')) throw new DomainException('Mã liên kết thanh toán không khớp.');
        $this->sync((int)$id);
    }
    public function sync(int $id): array
    {
        $order=$this->find($id);
        if ($order['status']==='paid') return $order;
        $data=$this->gateway->get((int)$order['order_code']);
        if ((int)($data['orderCode']??0)!==(int)$order['order_code'] || (int)($data['amount']??0)!==(int)$order['amount'] || empty($data['id']) || ($order['payment_link_id'] && $order['payment_link_id']!==$data['id'])) throw new DomainException('Thông tin đối soát không khớp.');
        $this->db->beginTransaction();
        try {
            // Same lock order as manual contributions and fund membership operations.
            $s=$this->db->prepare('SELECT id FROM monthly_funds WHERE id=? FOR UPDATE');$s->execute([$order['fund_id']]);
            $s=$this->db->prepare('SELECT * FROM fund_members WHERE fund_id=? AND user_id=? FOR UPDATE');$s->execute([$order['fund_id'],$order['user_id']]);$member=$s->fetch();
            $s=$this->db->prepare('SELECT * FROM payment_orders WHERE id=? FOR UPDATE');$s->execute([$id]);$order=$s->fetch();
            if ($order['status']==='paid') {$this->db->commit();return $order;}
            $paid=(int)($data['amountPaid']??0);$status=(string)($data['status']??'');
            $note=null;
            if ($status==='PAID' || $paid>(int)$order['amount']) {
                if ($paid!==(int)$order['amount'] || !$member || $member['contribution_status']==='paid') {
                    $local='review';$note='Cần đối soát số tiền hoặc khoản đóng đã được ghi nhận trước đó.';
                } else {
                    $local='paid';
                    $reference='payos:'.$order['order_code'];
                    $this->db->prepare("INSERT INTO fund_transactions (fund_id,user_id,transaction_type,amount,status,note,reference_key) VALUES (?,?,'contribution',?,'paid',?,?)")->execute([$order['fund_id'],$order['user_id'],$order['amount'],'Đóng quỹ qua payOS #'.$order['order_code'],$reference]);
                    $this->db->prepare("UPDATE fund_members SET contribution_status='paid',opted_in=1 WHERE fund_id=? AND user_id=?")->execute([$order['fund_id'],$order['user_id']]);
                    $this->db->prepare("INSERT INTO notifications (user_id,type,title,body) VALUES (?,'fund_payment','Đã nhận tiền nộp quỹ',?)")->execute([$order['user_id'],'Đã nhận '.number_format((int)$order['amount']).'đ. Mã giao dịch '.$order['order_code']]);
                    (new AuditLogService($this->db))->record(null,'payment_verified','payment_order',$id,['amount'=>$paid]);
                }
            } elseif ($paid>0) {
                $local='review';$note='Đã nhận một phần tiền; cần đối soát trước khi ghi đóng quỹ.';
            } else $local=match($status){'CANCELLED'=>'cancelled','EXPIRED'=>'expired',default=>'pending'};
            $this->db->prepare("UPDATE payment_orders SET status=?,payment_link_id=?,review_note=?,active_key=?,paid_at=IF(?='paid',UTC_TIMESTAMP(),paid_at) WHERE id=?")->execute([$local,$data['id'],$note,in_array($local,['cancelled','expired'],true)?null:$order['active_key'],$local,$id]);
            if (!$order['checkout_url'] && preg_match('/^[a-f0-9]{32}$/i',(string)$data['id'])) {
                $this->db->prepare('UPDATE payment_orders SET checkout_url=? WHERE id=?')->execute(['https://pay.payos.vn/web/'.$data['id'],$id]);
            }
            $this->db->commit();return $this->find($id);
        } catch (\Throwable $e) {if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }
}
