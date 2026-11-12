<?php
declare(strict_types=1);
namespace App\Services;
use DomainException;

class PayOSClient
{
    public function __construct(private readonly array $config) {}
    public function ready(): bool { return !empty($this->config['payos_client_id']) && !empty($this->config['payos_api_key']) && !empty($this->config['payos_checksum_key']); }
    public static function signature(array $data,string $key): string
    {
        ksort($data);
        $parts=[];
        foreach ($data as $name=>$value) {
            if ($value===null || $value==='null' || $value==='undefined') $value='';
            if (is_array($value)) {
                $value=array_map(static function($item){if(is_array($item)) ksort($item);return $item;},$value);
                $value=json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
            }
            $parts[]=$name.'='.$value;
        }
        return hash_hmac('sha256',implode('&',$parts),$key);
    }
    public function verify(array $payload): array
    {
        if (!$this->ready()) throw new DomainException('Cổng thanh toán chưa cấu hình.');
        if (!is_array($payload['data']??null) || !is_string($payload['signature']??null) || !hash_equals(self::signature($payload['data'],$this->config['payos_checksum_key']),$payload['signature'])) throw new DomainException('Chữ ký thanh toán không hợp lệ.');
        return $payload['data'];
    }
    public function create(array $order): array
    {
        $payload=['orderCode'=>(int)$order['order_code'],'amount'=>(int)$order['amount'],'description'=>'SF'.$order['order_code'],'returnUrl'=>$this->config['url'].'/fund/payments','cancelUrl'=>$this->config['url'].'/fund/payments'];
        $payload['signature']=self::signature($payload,$this->config['payos_checksum_key']);
        return $this->request('POST','/v2/payment-requests',$payload);
    }
    public function get(int $code): array { return $this->request('GET','/v2/payment-requests/'.$code); }
    protected function request(string $method,string $path,?array $body=null): array
    {
        if (!$this->ready()) throw new DomainException('Quỹ chưa bật thanh toán chuyển khoản trực tuyến.');
        if (!function_exists('curl_init')) throw new \RuntimeException('Máy chủ cần bật PHP cURL.');
        $ch=curl_init('https://api-merchant.payos.vn'.$path);
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_CONNECTTIMEOUT=>8,CURLOPT_TIMEOUT=>20,CURLOPT_HTTPHEADER=>['Content-Type: application/json','x-client-id: '.$this->config['payos_client_id'],'x-api-key: '.$this->config['payos_api_key']]]);
        if ($body!==null) curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($body,JSON_THROW_ON_ERROR));
        $raw=curl_exec($ch); $http=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
        if ($raw===false || $http!==200) throw new \RuntimeException('Chưa kết nối được cổng thanh toán. Hãy thử lại.');
        $response=json_decode($raw,true,512,JSON_THROW_ON_ERROR);
        if (($response['code']??'')!=='00') throw new DomainException('Cổng thanh toán chưa xử lý được yêu cầu ('.$response['code'].').');
        return $this->verify($response);
    }
}
