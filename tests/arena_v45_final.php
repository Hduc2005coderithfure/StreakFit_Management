<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require dirname(__DIR__).'/app/Helpers/functions.php';
require dirname(__DIR__).'/app/Services/SportGroupService.php';
$cases=[[0,0,'unplanned'],[0,3,'idle'],[1,3,'charge'],[2,3,'surge'],[3,3,'complete'],[4,3,'surplus'],[3,7,'charge'],[5,7,'surge'],[1,1,'complete'],[7,7,'complete']];
foreach($cases as [$done,$goal,$state]) {
    if(sf_week_energy_state($done,$goal)!==$state) throw new RuntimeException("Energy state failed: $done/$goal");
}
if (\App\Services\SportGroupService::SPORTS !== ['running'=>'Chạy bộ','gym'=>'Gym']) throw new RuntimeException('Unexpected sport catalog');
$root=dirname(__DIR__);
foreach(['app/Services/MemberPresenceService.php','app/Controllers/Api/PresenceController.php','app/Views/components/arena-presence-config.php','database/migrations/2026_11_14_member_presence.sql','tests/arena_presence.php','tests/arena_presence.js'] as $obsolete) {
    if(is_file($root.'/'.$obsolete)) throw new RuntimeException('Remove obsolete V4.1 file: '.$obsolete);
}
foreach(['public/index.php','public/assets/js/arena-v41.js'] as $file) {
    if(preg_match('~api/presence|MemberPresenceService|PresenceController~i',file_get_contents($root.'/'.$file))) throw new RuntimeException('Presence integration remains: '.$file);
}
echo "PASS: 10 energy states, running/gym only, obsolete presence files/routes removed.\n";
