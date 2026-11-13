<?php
$titles=['groups'=>'Nhóm thể thao','admin_groups'=>'Quản lý nhóm thể thao','payments'=>'Nộp quỹ trực tuyến','admin_payments'=>'Cấu hình QR nhận quỹ','route'=>'Đường tập GPS'];
$sports=\App\Services\SportGroupService::SPORTS;
$states=['creating'=>'Đang tạo','pending'=>'Chờ thanh toán','paid'=>'Đã nhận tiền','cancelled'=>'Đã hủy','expired'=>'Hết hạn','review'=>'Cần đối soát'];
$flash=$_SESSION['sf_flash']??null;unset($_SESSION['sf_flash']);
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e($titles[$page]) ?> · StreakFit</title><link rel="stylesheet" href="<?= e(asset('css/streakfit.css')) ?>"><link rel="stylesheet" href="<?= e(asset('css/community.css')) ?>"></head>
<body><div class="community-shell"><header class="topbar"><a class="brand" href="<?= e(url('dashboard')) ?>"><span>Streak<i>Fit</i></span><small><?= e($user['display_name']) ?></small></a><a href="<?= e(url($admin?'admin':'profile')) ?>">← <?= $admin?'Admin':'Cá nhân' ?></a></header><main class="page-content">
<nav class="sub-tabs"><a href="<?= e(url('groups')) ?>">Nhóm thể thao</a><a href="<?= e(url('workouts')) ?>">Check-in</a><a href="<?= e(url('fund/payments')) ?>">Nộp quỹ</a></nav>
<div class="page-intro"><h1><?= e($titles[$page]) ?></h1></div>
<?php if($error): ?><p class="alert error" role="alert"><?= e($error) ?></p><?php endif; ?>
<?php if(!$admin && $bankRequest && $bankRequest['status']==='paid'): ?><p class="alert" role="status">Chuyển khoản thành công! Admin đã xác nhận khoản nộp quỹ của bạn.</p><?php endif; ?>
<?php if($flash): ?><p class="alert" role="status"><?= e($flash['message']) ?></p><?php endif; ?>
<?php if(in_array($page,['groups','admin_groups'],true)): ?>
<form method="get" class="stack-form"><label>Lọc môn thể thao<select name="sport"><option value="">Tất cả</option><?php foreach($sports as $key=>$label): ?><option value="<?= e($key) ?>" <?= ($_GET['sport']??'')===$key?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></label><button class="btn secondary">Lọc nhóm</button></form>
<details class="panel" <?= $edit?'open':'' ?>><summary><?= $edit?'Chỉnh sửa nhóm':'＋ Tạo nhóm thể thao' ?></summary>
<form method="post" class="stack-form"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="group_id" value="<?= (int)($edit['id']??0) ?>">
<label>Tên nhóm<input name="name" minlength="3" maxlength="100" required value="<?= e($edit['name']??'') ?>"></label>
<label>Môn thể thao<select name="sport"><?php foreach($sports as $key=>$label): ?><option value="<?= e($key) ?>" <?= ($edit['sport']??'running')===$key?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
<label>Giới thiệu<textarea name="description" maxlength="1000"><?= e($edit['description']??'') ?></textarea></label>
<label>Điểm hẹn<input name="meeting_point" maxlength="255" required placeholder="Tên công viên, địa chỉ…" value="<?= e($edit['meeting_point']??'') ?>"></label>
<label>Điểm đến dự kiến (tùy chọn)<input name="destination" maxlength="255" value="<?= e($edit['destination']??'') ?>"></label>
<label>Buổi hẹn tiếp theo (<?= e(app_config()['timezone']) ?>)<input name="meeting_at" type="datetime-local" value="<?= !empty($edit['meeting_at'])?e(sf_format_time($edit['meeting_at'],'Y-m-d\TH:i')):'' ?>"></label><button class="btn primary">Lưu nhóm</button></form></details>
<?php if(!$rows): ?><div class="empty-state">Chưa có nhóm phù hợp. Hãy tạo nhóm đầu tiên.</div><?php endif; ?>
<?php foreach($rows as $g): ?>
<article class="panel"><div class="section-heading"><h2><?= e($g['name']) ?></h2><span class="rank-chip"><?= e($sports[$g['sport']]??$g['sport']) ?></span></div><p><?= nl2br(e($g['description'])) ?></p>
<p class="muted"><?= e($g['display_name']) ?> · <?= (int)$g['members'] ?> thành viên · <?= $g['status']==='active'?'Đang mở':'Đã đóng' ?></p><p>📍 <?= e($g['meeting_point']) ?><?= $g['destination']?' → '.e($g['destination']):'' ?></p>
<?php if($g['meeting_at']): ?><p>📅 <?= e(sf_format_time($g['meeting_at'],'d/m/Y H:i')) ?></p><?php endif; ?>
<a class="btn secondary" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?<?= e(http_build_query(['api'=>1,'query'=>$g['meeting_point']])) ?>">Mở điểm hẹn trên Google Maps ↗</a>
<div class="community-actions">
<?php if((int)$g['owner_id']===$userId || $canManageGroups): ?>
<a class="btn secondary" href="?edit=<?= (int)$g['id'] ?>">Chỉnh sửa</a>
<form method="post"><?= csrf_field() ?><input type="hidden" name="group_id" value="<?= (int)$g['id'] ?>"><button class="btn secondary" name="action" value="<?= $g['status']==='active'?'archive':'reopen' ?>"><?= $g['status']==='active'?'Đóng nhóm':'Mở lại nhóm' ?></button></form>
<?php endif; ?>
<?php if((int)$g['owner_id']!==$userId && ($g['joined'] || $g['status']==='active')): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="group_id" value="<?= (int)$g['id'] ?>"><button class="btn primary" name="action" value="<?= $g['joined']?'leave':'join' ?>"><?= $g['joined']?'Rời nhóm':'Tham gia' ?></button></form><?php endif; ?>
</div></article>
<?php endforeach; ?>
<?php elseif(in_array($page,['payments','admin_payments'],true)): ?>
<?php if(!$admin): ?><section class="panel"><h2>Quỹ tháng <?= e(date('m/Y')) ?></h2><p>Mức đóng: <strong><?= number_format((int)$fund['contribution_amount']) ?>đ</strong></p>
<?php if(!$fundMember || !$fundMember['opted_in']): ?><a class="btn secondary" href="<?= e(url('profile?section=fund')) ?>">Tham gia quỹ trước khi nộp</a>
<?php elseif($fundMember['contribution_status']==='paid'): ?><p class="alert">Bạn đã đóng quỹ tháng này.</p>
<?php else: ?>
<?php if($bankRequest && $bankRequest['status']==='pending'): ?>
<h3 id="bankQr" tabindex="-1">QR chuyển khoản quỹ</h3>
<form method="post"><?= csrf_field() ?><input type="hidden" name="fund_id" value="<?= (int)$fund['id'] ?>"><button class="btn secondary" name="action" value="bank_create">Mở lại QR chuyển khoản</button></form>
<img class="bank-qr" src="<?= e(\App\Services\BankTransferService::qrUrl($bankRequest)) ?>" alt="QR chuyển khoản <?= number_format((int)$bankRequest['amount']) ?> đồng vào <?= e($bankRequest['bank_bin'].' '.$bankRequest['account_number']) ?>" referrerpolicy="no-referrer">
<dl class="bank-details"><div><dt>Ngân hàng (BIN)</dt><dd><?= e($bankRequest['bank_bin']) ?></dd></div><div><dt>Số tài khoản</dt><dd><?= e($bankRequest['account_number']) ?></dd></div><div><dt>Chủ tài khoản</dt><dd><?= e($bankRequest['account_name']) ?></dd></div><div><dt>Số tiền</dt><dd><?= number_format((int)$bankRequest['amount']) ?>đ</dd></div><div><dt>Nội dung chuyển khoản</dt><dd><?= e($bankRequest['transfer_memo']) ?></dd></div></dl>
<p class="alert info">Hãy kiểm tra tên người nhận, số tiền và nội dung chuyển khoản. Sau khi gửi yêu cầu, Admin sẽ xác nhận bạn tham gia và ghi nhận khoản đóng trong trang Quản lý quỹ.</p>
<?php elseif($bankReady): ?><p>Tạo QR với đúng số tiền và nội dung riêng. Admin xác nhận khoản đóng tại trang Quản lý quỹ.</p><form method="post"><?= csrf_field() ?><input type="hidden" name="fund_id" value="<?= (int)$fund['id'] ?>"><button class="btn primary full" name="action" value="bank_create">Tạo QR chuyển khoản <?= number_format((int)$fund['contribution_amount']) ?>đ</button></form>
<?php elseif(!$ready): ?><p class="alert info">Admin chưa thiết lập tài khoản ngân hàng nhận quỹ.</p><?php endif; ?>
<?php if($ready && !$bankRequest): ?><p>Bạn cũng có thể thanh toán qua payOS để được tự động xác nhận.</p><form method="post"><?= csrf_field() ?><input type="hidden" name="fund_id" value="<?= (int)$fund['id'] ?>"><button class="btn secondary full" name="action" value="pay">Nộp qua payOS</button></form><?php endif; ?>
<?php endif; ?></section><?php endif; ?>
<?php if($admin): ?>
<section class="panel"><h2>Tài khoản ngân hàng nhận quỹ</h2>
<?php if($permissions->can($userId,'fund.manage')): ?>
<form method="post" class="stack-form"><?= csrf_field() ?><input type="hidden" name="action" value="bank_config">
<label>BIN ngân hàng (6 số)<input name="bank_bin" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required value="<?= e($bankConfig['bank_bin']) ?>" placeholder="970415"></label>
<label>Số tài khoản nhận tiền<input name="bank_account" inputmode="numeric" pattern="[0-9]{6,19}" maxlength="19" required value="<?= e($bankConfig['bank_account']) ?>"></label>
<label>Tên chủ tài khoản (không dấu, viết hoa)<input name="bank_holder" maxlength="50" required value="<?= e($bankConfig['bank_holder']) ?>" placeholder="NGUYEN VAN A"></label>
<button class="btn primary">Lưu tài khoản nhận tiền</button></form>
<p class="muted">BIN tra từ danh sách ngân hàng VietQR. Tự quét QR bằng ứng dụng ngân hàng để kiểm tra chủ tài khoản trước khi cho thành viên sử dụng. QR đã tạo vẫn giữ thông tin tài khoản tại thời điểm tạo.</p>
<?php else: ?><p><?= $bankReady?e($bankConfig['bank_bin'].' · '.$bankConfig['bank_account'].' · '.$bankConfig['bank_holder']):'Chưa cấu hình tài khoản ngân hàng.' ?></p><?php endif; ?>
</section>
<section class="panel"><h2>Yêu cầu nộp quỹ tháng này</h2><p class="muted">Admin xác nhận thành viên và khoản đóng tại trang Quản lý quỹ.</p>
<?php if(!$bankRequests): ?><p>Chưa có QR ngân hàng.</p><?php endif; ?>
<?php foreach($bankRequests as $req): ?><article class="bank-request"><strong><?= e($req['display_name']) ?> · <?= number_format((int)$req['amount']) ?>đ</strong><p><?= e($req['transfer_memo']) ?> · <?= e($req['status']) ?> · <?= e($req['account_number']) ?></p>
<?php if($req['status']==='pending'): ?><p class="muted">Chờ Admin xác nhận tại trang Quản lý quỹ.</p><?php endif; ?></article><?php endforeach; ?></section>
<p class="alert info"><?= $ready?'payOS đã cấu hình.':'payOS chưa cấu hình; thành viên vẫn có thể dùng QR ngân hàng nếu bạn đã nhập tài khoản trên.' ?></p>
<?php endif; ?>
<h2>Lịch sử chuyển khoản</h2><p class="muted">Trạng thái được cập nhật khi Admin xác nhận tại trang Quản lý quỹ.</p>
<?php if(!$admin && $bankRequest): ?><article class="panel"><strong>Chuyển khoản ngân hàng · <?= number_format((int)$bankRequest['amount']) ?>đ</strong><p>Tháng <?= e(substr((string)$fund['month_start'],0,7)) ?> · <?= e($bankRequest['transfer_memo']) ?></p><p class="rank-chip"><?= $bankRequest['status']==='paid'?'Đã thanh toán':($bankRequest['status']==='pending'?'Đang chờ Admin xác nhận':e($bankRequest['status'])) ?></p></article><?php endif; ?>
<?php if(!$rows): ?><p class="empty-state">Chưa có giao dịch.</p><?php endif; ?>
<?php foreach($rows as $p): ?><article class="panel"><strong>#<?= e($p['order_code']) ?> · <?= number_format((int)$p['amount']) ?>đ</strong><p><?= $admin?e($p['display_name']).' · ':'' ?>Tháng <?= e(substr($p['month_start'],0,7)) ?></p><p class="rank-chip"><?= e($states[$p['status']]) ?></p><?php if($p['review_note']): ?><p><?= e($p['review_note']) ?></p><?php endif; ?>
<?php if($p['status']!=='paid' && (!$admin || $permissions->can($userId,'fund.manage'))): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="order_id" value="<?= (int)$p['id'] ?>"><button name="action" value="sync" class="btn secondary">Kiểm tra thanh toán</button></form><?php endif; ?>
<?php if(!$admin && $p['checkout_url'] && in_array($p['status'],['creating','pending'],true)): ?><a class="btn primary" href="<?= e($p['checkout_url']) ?>">Mở lại mã QR</a><?php endif; ?></article><?php endforeach; ?>
<?php else: ?>
<section class="panel"><h2><?= e($sports[$route['workout']['sport']]??'Tập luyện') ?> · <?= e($route['workout']['workout_date']) ?></h2><p id="gpsStatus" role="status">Đã lưu <?= count($route['points']) ?> điểm GPS.</p><p id="gpsDistance"><?= number_format((float)$route['workout']['distance_meters']/1000,2) ?> km đã lưu</p><p id="gpsSpeed"></p>
<figure><canvas id="routeSketch" width="340" height="340" style="max-width:100%;background:#24242e;border-radius:12px" aria-label="Sơ đồ tuyến GPS"></canvas><figcaption>Sơ đồ đường GPS · quãng đường ước tính, không bao gồm khoảng mất tín hiệu hoặc tạm dừng.</figcaption></figure>
<?php if((int)$route['workout']['user_id']===$userId): ?>
<?php if($route['workout']['status']==='in_progress'): ?><div class="community-actions"><button class="btn primary" id="gpsStart">Bật theo dõi GPS</button><button class="btn secondary" id="gpsStop" disabled>Dừng theo dõi</button></div><p class="muted">Cần cho phép vị trí và giữ trang này mở khi tập. Trình duyệt có thể ngừng GPS khi khóa màn hình. Điểm có độ chính xác thấp sẽ được bỏ qua.</p><?php endif; ?>
<button class="text-link danger-link" id="gpsDelete">Xóa dữ liệu GPS buổi này</button><?php endif; ?>
<div id="routeMap" class="route-map"><?php if(!app_config()['google_maps_key']): ?><p>Bản đồ đang chờ cấu hình Google Maps. Bạn vẫn có thể lưu GPS.</p><?php endif; ?></div><a class="btn secondary" href="<?= e(url('workouts')) ?>">Về check-in / check-out</a></section>
<script id="routeData" type="application/json"><?= json_encode(['id'=>(int)$route['workout']['id'],'owner'=>(int)$route['workout']['user_id']===$userId,'sport'=>$route['workout']['sport'],'started_ms'=>(int)$route['workout']['started_ms'],'points'=>$route['points'],'csrf'=>sf_csrf_token(),'endpoint'=>url('api/workout-route')],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR) ?></script>
<script src="<?= e(asset('js/route-metrics.js')) ?>"></script>
<script src="<?= e(asset('js/route.js')) ?>"></script>
<?php if(app_config()['google_maps_key']): ?><script async src="https://maps.googleapis.com/maps/api/js?key=<?= e(rawurlencode(app_config()['google_maps_key'])) ?>&amp;callback=initStreakFitRoute&amp;loading=async"></script><?php endif; ?>
<?php endif; ?>
</main></div></body></html>
