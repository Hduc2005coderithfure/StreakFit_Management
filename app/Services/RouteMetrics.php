<?php
declare(strict_types=1);
namespace App\Services;
final class RouteMetrics
{
    public static function calculate(array $points,string $sport): array
    {
        $meters=0.0;$seconds=0.0;
        $limit=match($sport){'running'=>10,'walking'=>4,default=>30};
        for($i=1;$i<count($points);$i++){
            $a=$points[$i-1];$b=$points[$i];
            $dt=((float)$b['t']-(float)$a['t'])/1000;
            if(!empty($b['segment_start']) || $dt<=0 || $dt>60 || $a['accuracy']>35 || $b['accuracy']>35)continue;
            $v=sin(deg2rad($b['lat']-$a['lat'])/2)**2+cos(deg2rad((float)$a['lat']))*cos(deg2rad((float)$b['lat']))*sin(deg2rad($b['lng']-$a['lng'])/2)**2;
            $distance=6371000*2*atan2(sqrt(max(0,$v)),sqrt(max(0,1-$v)));
            if($distance/$dt>$limit)continue;
            $seconds+=$dt;
            if($distance>=max(3,min($a['accuracy'],$b['accuracy'])/2))$meters+=$distance;
        }
        return ['distance_meters'=>round($meters,2),'tracked_seconds'=>(int)round($seconds)];
    }
}
