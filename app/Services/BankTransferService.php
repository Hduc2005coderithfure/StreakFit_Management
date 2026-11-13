<?php
declare(strict_types=1);
namespace App\Services;
use DomainException;
use PDO;

final class BankTransferService
{
    public function __construct(private readonly PDO $db) {}
    public function configuration(): array
    {
        $rows=$this->db->query("SELECT setting_key,setting_value FROM system_settings WHERE setting_key IN ('bank_bin','bank_account','bank_holder')")->fetchAll(PDO::FETCH_KEY_PAIR);
        return ['bank_bin'=>(string)($rows['bank_bin']??''),'bank_account'=>(string)($rows['bank_account']??''),'bank_holder'=>(string)($rows['bank_holder']??'')];
    }
    public function configured(): bool
    {
        $bank=$this->configuration();
        return self::valid($bank);
    }
    private static function valid(array $bank): bool
    {
        return (bool)preg_match('/^\d{6}$/',(string)$bank['bank_bin'])
            && (bool)preg_match('/^\d{6,19}$/',(string)$bank['bank_account'])
            && (bool)preg_match('/^[A-Z0-9 ]{5,50}$/',(string)$bank['bank_holder']);
    }
    public function saveConfiguration(int $adminId,array $input): void
    {
        $bank=[
            'bank_bin'=>trim((string)($input['bank_bin']??'')),
            'bank_account'=>trim((string)($input['bank_account']??'')),
            'bank_holder'=>strtoupper(trim((string)($input['bank_holder']??''))),
        ];
        if (!self::valid($bank)) throw new DomainException('Nhập BIN ngân hàng 6 số, số tài khoản 6–19 số và tên chủ tài khoản không dấu từ 5–50 ký tự.');
        $this->db->beginTransaction();
        try {
            $stmt=$this->db->prepare('INSERT INTO system_settings (setting_key,setting_value,updated_by) VALUES (?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_by=VALUES(updated_by)');
            foreach($bank as $key=>$value)$stmt->execute([$key,$value,$adminId]);
            (new AuditLogService($this->db))->record($adminId,'bank_recipient_updated','system_settings',null,['bank_bin'=>$bank['bank_bin'],'account_last4'=>substr($bank['bank_account'],-4)]);
            $this->db->commit();
        } catch (\Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }
    public function create(int $userId,int $fundId): array
    {
        $bank=$this->configuration();
        if(!self::valid($bank))throw new DomainException('Quỹ chưa được quản trị viên cấu hình tài khoản nhận tiền.');
        $this->db->beginTransaction();
        try {
            $s=$this->db->prepare('SELECT contribution_amount FROM monthly_funds WHERE id=? FOR UPDATE');$s->execute([$fundId]);$amount=(int)$s->fetchColumn();
            $s=$this->db->prepare('SELECT contribution_status,opted_in FROM fund_members WHERE fund_id=? AND user_id=? FOR UPDATE');$s->execute([$fundId,$userId]);$member=$s->fetch();
            if ($amount<1 || !$member || !(int)$member['opted_in'] || $member['contribution_status']==='paid') throw new DomainException('Bạn cần tham gia quỹ và chưa đóng khoản này.');
            $s=$this->db->prepare('SELECT id FROM payment_orders WHERE active_key=? LIMIT 1');$s->execute([$fundId.':'.$userId]);
            if ($s->fetchColumn()) throw new DomainException('Đang có đơn payOS. Hãy hoàn tất hoặc đối soát đơn trước khi tạo QR ngân hàng.');
            $s=$this->db->prepare('SELECT * FROM bank_transfer_requests WHERE fund_id=? AND user_id=? FOR UPDATE');$s->execute([$fundId,$userId]);$request=$s->fetch();
            if (!$request) {
                $memo='SFQ'.strtoupper(substr(bin2hex(random_bytes(10)),0,17));
                $this->db->prepare('INSERT INTO bank_transfer_requests (fund_id,user_id,amount,bank_bin,account_number,account_name,transfer_memo) VALUES (?,?,?,?,?,?,?)')->execute([$fundId,$userId,$amount,$bank['bank_bin'],$bank['bank_account'],$bank['bank_holder'],$memo]);
                $request=$this->find((int)$this->db->lastInsertId());
            }
            $this->db->commit();return $request;
        } catch (\Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }
    public function find(int $id): array
    {
        $s=$this->db->prepare('SELECT * FROM bank_transfer_requests WHERE id=?');$s->execute([$id]);$row=$s->fetch();
        if(!$row)throw new DomainException('Không tìm thấy yêu cầu nộp quỹ.');return $row;
    }
    public function forFund(int $fundId): array
    {
        $s=$this->db->prepare('SELECT r.*,u.display_name FROM bank_transfer_requests r JOIN users u ON u.id=r.user_id WHERE r.fund_id=? ORDER BY r.id DESC LIMIT 100');
        $s->execute([$fundId]);return $s->fetchAll();
    }
    public static function qrUrl(array $request): string
    {
        $path=$request['bank_bin'].'-'.$request['account_number'].'-compact2.png';
        return 'https://img.vietqr.io/image/'.$path.'?'.http_build_query([
            'amount'=>(int)$request['amount'],
            'addInfo'=>$request['transfer_memo'],
            'accountName'=>$request['account_name']
        ],'','&',PHP_QUERY_RFC3986);
    }
}
