<?php
declare(strict_types=1);
require __DIR__.'/../app/Services/MemberPresenceService.php';
use App\Services\MemberPresenceService as P;
$now=1700000000;
$cases=[[null,true,'unknown'],[0,true,'unknown'],[$now,true,'online'],[$now-120,true,'online'],[$now-121,true,'offline'],[$now-86400,true,'offline'],[$now,false,'hidden'],[null,false,'hidden']];
foreach($cases as [$last,$share,$expected]){if(P::state($last,$share,$now)!==$expected)throw new RuntimeException('Presence state mismatch');}
echo count($cases)." presence state cases passed\n";

require __DIR__.'/../app/Core/Request.php';
require __DIR__.'/../app/Core/Response.php';
require __DIR__.'/../app/Core/Session.php';
require __DIR__.'/../app/Controllers/Api/PresenceController.php';
$controller=new App\Controllers\Api\PresenceController();
$status=new ReflectionProperty(App\Core\Response::class,'status');
$_SESSION=[];$_POST=[];
if($status->getValue($controller->index(new App\Core\Request()))!==401)throw new RuntimeException('Missing authentication guard');
$_SESSION=['sf_user_id'=>1,'sf_csrf'=>'test-token'];$_POST=['_csrf'=>'incorrect'];
if($status->getValue($controller->index(new App\Core\Request()))!==419)throw new RuntimeException('Missing CSRF guard');
$_POST=['_csrf'=>'test-token','action'=>'unrecognized'];
if($status->getValue($controller->index(new App\Core\Request()))!==422)throw new RuntimeException('Missing action validation');
echo "3 API guard cases passed\n";
