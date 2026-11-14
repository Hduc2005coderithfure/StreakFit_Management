<?php
$titles=['groups'=>'Nhóm thể thao','admin_groups'=>'Quản lý nhóm thể thao','payments'=>'Nộp quỹ trực tuyến','admin_payments'=>'Đối soát chuyển khoản','route'=>'Đường chạy GPS'];
$sports=\App\Services\SportGroupService::SPORTS;
// Defense in depth: member pages never offer unsupported legacy sports.
if (!$admin && $page==='groups') $rows=array_values(array_filter($rows,static fn($g)=>isset($sports[$g['sport']])));
$legacySportLabels=['cycling'=>'Đạp xe','walking'=>'Đi bộ','swimming'=>'Bơi lội','other'=>'Khác'];
$sportLabel=static fn($sport)=>$sports[$sport]??('Môn cũ: '.($legacySportLabels[$sport]??$sport));
$states=['creating'=>'Đang tạo','pending'=>'Chờ thanh toán','paid'=>'Đã nhận tiền','cancelled'=>'Đã hủy','expired'=>'Hết hạn','review'=>'Cần đối soát'];
if (!$admin) {
    $tab=$page; $arenaView=''; $profileSection='overview';
    $arenaPoints=(int)($user['total_points']??0); $rank=sf_rank($arenaPoints);
    $unreadCount=0; // Independent view has no notification count; do not invent one.
    $canAdminDashboard=isset($permissions) && $permissions->can($userId,'dashboard.view');
    require APP_ROOT . '/app/Views/components/arena-icons.php';
}
$flash=$_SESSION['sf_flash']??null;unset($_SESSION['sf_flash']);
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e($titles[$page]) ?> · StreakFit</title><link rel="stylesheet" href="<?= e(sf_asset_version('css/streakfit.css')) ?>"><link rel="stylesheet" href="<?= e(sf_asset_version('css/community.css')) ?>"><link rel="stylesheet" href="<?= e(sf_asset_version('css/streakfit-future.css')) ?>"><link rel="stylesheet" href="<?= e(sf_asset_version('css/streakfit-motion.css')) ?>"><link rel="stylesheet" href="<?= e(sf_asset_version('css/arena-v37.css')) ?>">
<?php if (!$admin): ?>
<?php foreach (['arena-v3','arena-v32','arena-v33','arena-v35','arena-v38','arena-v39','arena-v40','arena-v41','arena-v42','arena-v45'] as $style): ?><link rel="stylesheet" href="<?= e(sf_asset_version('css/'.$style.'.css')) ?>"><?php endforeach; ?>
<?php endif; ?></head>
<body class="sf-future sf-community<?= !$admin?' sf-arena sf40-workspace sf40-independent':'' ?>" <?= !$admin?'data-tab="'.e($page).'" data-timezone="'.e(app_config()['timezone']).'"':'' ?>><div class="community-shell<?= !$admin?' app-shell':'' ?>"><?php if (!$admin) require APP_ROOT . '/app/Views/components/arena-workspace.php'; ?><header class="topbar"><a class="brand" href="<?= e(url('dashboard')) ?>"><span>Streak<i>Fit</i></span><small><?= e($user['display_name']) ?></small></a><?php if (!$admin): ?><div class="sf39-top-title"><span>StreakFit</span><strong><?= e($titles[$page]) ?></strong></div><?php endif; ?><a href="<?= e(url($admin?'admin':'profile')) ?>"><?= $admin?'← Quản trị':'Cá nhân' ?></a></header><main class="page-content">
<?php if ($admin): ?><nav class="sub-tabs sf40-community-tabs"><a href="<?= e(url('groups')) ?>" <?= $page==='groups'?'aria-current="page"':'' ?>>Nhóm thể thao</a><a href="<?= e(url('workouts')) ?>">Tập luyện</a><a href="<?= e(url('fund/payments')) ?>" <?= $page==='payments'?'aria-current="page"':'' ?>>Nộp quỹ</a></nav><?php endif; ?>
<div class="page-intro"><h1><?= e($titles[$page]) ?></h1><?php if (!$admin && $page==='groups'): ?><p class="muted">Tìm đồng đội chạy bộ hoặc tập gym.</p><?php endif; ?></div>
<?php if($error): ?><p class="alert error" role="alert"><?= e($error) ?></p><?php endif; ?>
<?php if($flash): ?><p class="alert" role="status"><?= e($flash['message']) ?></p><?php endif; ?>
<?php if(in_array($page,['groups','admin_groups'],true)): ?>
<?php if(!$admin): ?><noscript><?php endif; ?><form method="get" class="stack-form"><label>Lọc môn thể thao<select name="sport"><option value="">Tất cả</option><?php foreach($sports as $key=>$label): ?><option value="<?= e($key) ?>" <?= ($_GET['sport']??'')===$key?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></label><button class="btn secondary">Lọc nhóm</button></form><?php if(!$admin): ?></noscript><?php if(!empty($_GET['sport'])): ?><p class="muted">Đang xem một môn thể thao từ bộ lọc trước. <a href="<?= e(url('groups')) ?>">Xem tất cả nhóm</a></p><?php endif; endif; ?>
<details class="panel" <?= $edit?'open':'' ?>><summary><?= $edit?'Chỉnh sửa nhóm':'＋ Tạo nhóm thể thao' ?></summary>
<form method="post" class="stack-form"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="group_id" value="<?= (int)($edit['id']??0) ?>">
<label>Tên nhóm<input name="name" minlength="3" maxlength="100" required value="<?= e($edit['name']??'') ?>"></label>
<label>Môn thể thao<select name="sport" required><?php if($edit && !isset($sports[$edit['sport']])): ?><option value="" disabled selected>Chọn môn mới</option><?php endif; ?><?php foreach($sports as $key=>$label): ?><option value="<?= e($key) ?>" <?= ($edit['sport']??'running')===$key?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
<label>Giới thiệu<textarea name="description" maxlength="1000"><?= e($edit['description']??'') ?></textarea></label>
<label>Điểm hẹn<input name="meeting_point" maxlength="255" required placeholder="Tên công viên, địa chỉ…" value="<?= e($edit['meeting_point']??'') ?>"></label>
<label>Điểm đến dự kiến khi chạy (tùy chọn)<input name="destination" maxlength="255" value="<?= e($edit['destination']??'') ?>"></label>
<label>Buổi hẹn tiếp theo (giờ địa phương)<input name="meeting_at" type="datetime-local" value="<?= !empty($edit['meeting_at'])?e(sf_format_time($edit['meeting_at'],'Y-m-d\TH:i')):'' ?>"></label><button class="btn primary">Lưu nhóm</button></form></details>
<?php if($admin && $page==='admin_groups'): ?><p class="muted">Nhóm thuộc môn cũ được giữ nguyên dữ liệu. Chuyển sang Chạy bộ hoặc Gym trước khi mở lại.</p><?php endif; ?><?php if(!$rows): ?><div class="empty-state">Chưa có nhóm phù hợp. Hãy tạo nhóm đầu tiên.</div><?php endif; ?>
<?php if (!$admin): ?><section class="sf42-group-summary"><div><small>Nhóm hiện có</small><strong><?= count($rows) ?></strong></div><div><small>Đang tham gia</small><strong><?= count(array_filter($rows,static fn($g)=>!empty($g['joined']) || (int)$g['owner_id']===$userId)) ?></strong></div><div><small>Đang mở</small><strong><?= count(array_filter($rows,static fn($g)=>$g['status']==='active')) ?></strong></div></section>
<div class="sf42-filter" data-sf42-groups><label>Tìm nhóm<input type="search" data-sf42-group-search placeholder="Tên nhóm, môn thể thao…"></label><label>Môn thể thao<select data-sf42-group-sport><option value="">Tất cả</option><?php foreach($sports as $key=>$label): ?><option value="<?= e($key) ?>"><?= e($label) ?></option><?php endforeach; ?></select></label><label>Trạng thái<select data-sf42-group-state><option value="">Tất cả</option><option value="mine">Đang tham gia</option><option value="active">Đang mở</option></select></label></div><p data-sf42-group-empty hidden>Không tìm thấy nhóm phù hợp.</p><?php endif; ?>
<div class="sf40-group-grid">
<?php foreach($rows as $g): ?>
<article class="panel sf40-group-card" data-sport="<?= e($g['sport']) ?>" data-sf42-group data-state="<?= e($g['status']) ?>" data-mine="<?= !empty($g['joined']) || (int)$g['owner_id']===$userId ? '1':'0' ?>" data-name="<?= e($g['name'].' '.($sportLabel($g['sport']))) ?>">
<?php if(!$admin): ?><div class="sf42-group-cover" aria-hidden="true"><span><?= $arenaIcon(match($g['sport']){'running'=>'activity','gym'=>'plus',default=>'people'}) ?></span><b><?= e($sportLabel($g['sport'])) ?></b><i></i></div><?php endif; ?><div class="section-heading"><h2><?= e($g['name']) ?></h2><span class="rank-chip"><?= e($sportLabel($g['sport'])) ?></span></div><p><?= nl2br(e($g['description'])) ?></p>
<p class="muted"><?= e($g['display_name']) ?> · <?= (int)$g['members'] ?> thành viên · <?= $g['status']==='active'?'Đang mở':'Đã đóng' ?></p><p>📍 <?= e($g['meeting_point']) ?><?= $g['destination']?' → '.e($g['destination']):'' ?></p>
<?php if($g['meeting_at']): ?><p>📅 <?= e(sf_format_time($g['meeting_at'],'d/m/Y H:i')) ?></p><?php endif; ?>
<a class="btn secondary" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?<?= e(http_build_query(['api'=>1,'query'=>$g['meeting_point']])) ?>">Mở điểm hẹn trên Google Maps ↗</a>
<div class="community-actions">
<?php if((int)$g['owner_id']===$userId || $canManageGroups): ?>
<a class="btn secondary" href="?edit=<?= (int)$g['id'] ?>">Chỉnh sửa</a>
<?php if ($g['status']==='active' || isset($sports[$g['sport']])): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="group_id" value="<?= (int)$g['id'] ?>"><button class="btn secondary" name="action" value="<?= $g['status']==='active'?'archive':'reopen' ?>"><?= $g['status']==='active'?'Đóng nhóm':'Mở lại nhóm' ?></button></form><?php endif; ?>
<?php endif; ?>
<?php if((int)$g['owner_id']!==$userId && ($g['joined'] || ($g['status']==='active' && isset($sports[$g['sport']])))): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="group_id" value="<?= (int)$g['id'] ?>"><button class="btn primary" name="action" value="<?= $g['joined']?'leave':'join' ?>"><?= $g['joined']?'Rời nhóm':'Tham gia' ?></button></form><?php endif; ?>
</div></article>
<?php endforeach; ?>
</div>
<?php elseif(in_array($page,['payments','admin_payments'],true)): ?>
<?php if(!$admin): ?><section class="panel"><h2>Quỹ tháng <?= e(date('m/Y')) ?></h2><p>Mức đóng: <strong><?= number_format((int)$fund['contribution_amount']) ?>đ</strong></p>
<?php if(!$fundMember || !$fundMember['opted_in']): ?><a class="btn secondary" href="<?= e(url('profile?section=fund')) ?>">Tham gia quỹ trước khi nộp</a>
<?php elseif($fundMember['contribution_status']==='paid'): ?><p class="alert">Bạn đã đóng quỹ tháng này.</p>
<?php else: ?>
<?php if($bankRequest && $bankRequest['status']==='pending'): ?>
<h3>QR chuyển khoản quỹ</h3>
<img class="bank-qr" src="<?= e(\App\Services\BankTransferService::qrUrl($bankRequest)) ?>" alt="QR chuyển khoản <?= number_format((int)$bankRequest['amount']) ?> đồng vào <?= e($bankRequest['bank_bin'].' '.$bankRequest['account_number']) ?>" referrerpolicy="no-referrer">
<dl class="bank-details"><div><dt>Ngân hàng (BIN)</dt><dd><?= e($bankRequest['bank_bin']) ?></dd></div><div><dt>Số tài khoản</dt><dd><?= e($bankRequest['account_number']) ?></dd></div><div><dt>Chủ tài khoản</dt><dd><?= e($bankRequest['account_name']) ?></dd></div><div><dt>Số tiền</dt><dd><?= number_format((int)$bankRequest['amount']) ?>đ</dd></div><div><dt>Nội dung chuyển khoản</dt><dd><?= e($bankRequest['transfer_memo']) ?></dd></div></dl>
<p class="alert info">Hãy kiểm tra tên người nhận trong ứng dụng ngân hàng trước khi chuyển. Sau khi chuyển, đợi Admin đối chiếu sao kê và xác nhận; bạn không cần chuyển lại.</p>
<?php elseif($bankReady): ?><p>Quét QR để chuyển đúng số tiền vào tài khoản quỹ. Quản trị viên sẽ đối chiếu sao kê trước khi xác nhận.</p><form method="post"><?= csrf_field() ?><input type="hidden" name="fund_id" value="<?= (int)$fund['id'] ?>"><button class="btn primary full" name="action" value="bank_create">Tạo QR chuyển khoản <?= number_format((int)$fund['contribution_amount']) ?>đ</button></form>
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
<section class="panel"><h2>Chờ đối soát chuyển khoản tháng này</h2><p class="muted">Chỉ xác nhận sau khi thấy tiền vào sao kê ngân hàng, đúng số tiền và nội dung chuyển khoản.</p>
<?php if(!$bankRequests): ?><p>Chưa có QR ngân hàng.</p><?php endif; ?>
<?php foreach($bankRequests as $req): ?><article class="bank-request"><strong><?= e($req['display_name']) ?> · <?= number_format((int)$req['amount']) ?>đ</strong><p><?= e($req['transfer_memo']) ?> · <?= e($req['status']) ?> · <?= e($req['account_number']) ?></p>
<?php if($req['status']==='pending' && $permissions->can($userId,'fund.manage')): ?><form method="post" class="stack-form"><?= csrf_field() ?><input type="hidden" name="action" value="bank_confirm"><input type="hidden" name="request_id" value="<?= (int)$req['id'] ?>">
<label>Mã giao dịch từ sao kê ngân hàng<input name="bank_reference" minlength="6" maxlength="100" required></label><label>Nội dung trong sao kê<input name="statement_memo" maxlength="250" required placeholder="Phải chứa <?= e($req['transfer_memo']) ?>"></label><label>Số tiền thực nhận (đ)<input name="received_amount" type="number" min="1" required></label><button class="btn primary">Đã đối chiếu sao kê, xác nhận đóng quỹ</button></form><?php endif; ?></article><?php endforeach; ?></section>
<p class="alert info"><?= $ready?'payOS đã cấu hình.':'payOS chưa cấu hình; thành viên vẫn có thể dùng QR ngân hàng nếu bạn đã nhập tài khoản trên.' ?></p>
<?php endif; ?>
<h2>Lịch sử chuyển khoản</h2><p class="muted">Sau khi chuyển tiền, chọn “Kiểm tra thanh toán” nếu trạng thái chưa cập nhật.</p>
<?php if(!$rows): ?><p class="empty-state">Chưa có giao dịch.</p><?php endif; ?>
<?php foreach($rows as $p): ?><article class="panel"><strong>#<?= e($p['order_code']) ?> · <?= number_format((int)$p['amount']) ?>đ</strong><p><?= $admin?e($p['display_name']).' · ':'' ?>Tháng <?= e(substr($p['month_start'],0,7)) ?></p><p class="rank-chip"><?= e($states[$p['status']]) ?></p><?php if($p['review_note']): ?><p><?= e($p['review_note']) ?></p><?php endif; ?>
<?php if($p['status']!=='paid' && (!$admin || $permissions->can($userId,'fund.manage'))): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="order_id" value="<?= (int)$p['id'] ?>"><button name="action" value="sync" class="btn secondary">Kiểm tra thanh toán</button></form><?php endif; ?>
<?php if(!$admin && $p['checkout_url'] && in_array($p['status'],['creating','pending'],true)): ?><a class="btn primary" href="<?= e($p['checkout_url']) ?>">Mở lại mã QR</a><?php endif; ?></article><?php endforeach; ?>
<?php else: ?>
<section class="panel"><h2>Buổi tập <?= e($route['workout']['workout_date']) ?></h2><p id="gpsStatus" role="status">Đã lưu <?= count($route['points']) ?> điểm GPS.</p><p id="gpsDistance"></p>
<?php if((int)$route['workout']['user_id']===$userId): ?>
<?php if($route['workout']['status']==='in_progress'): ?><div class="community-actions"><button class="btn primary" id="gpsStart">Bật theo dõi GPS</button><button class="btn secondary" id="gpsStop" disabled>Dừng theo dõi</button></div><p class="muted">Cần cho phép vị trí và giữ trang này mở khi tập. Trình duyệt có thể ngừng GPS khi khóa màn hình. Điểm có độ chính xác thấp sẽ được bỏ qua.</p><?php endif; ?>
<button class="text-link danger-link" id="gpsDelete">Xóa dữ liệu GPS buổi này</button><?php endif; ?>
<div id="routeMap" class="route-map"><?php if(!app_config()['google_maps_key']): ?><p>Bản đồ đang chờ cấu hình Google Maps. Bạn vẫn có thể lưu GPS.</p><?php endif; ?></div><a class="btn secondary" href="<?= e(url('workouts')) ?>">Về trang tập luyện</a></section>
<script id="routeData" type="application/json"><?= json_encode(['id'=>(int)$route['workout']['id'],'owner'=>(int)$route['workout']['user_id']===$userId,'points'=>$route['points'],'csrf'=>sf_csrf_token(),'endpoint'=>url('api/workout-route')],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR) ?></script>
<script src="<?= e(sf_asset_version('js/route.js')) ?>"></script>
<?php if(app_config()['google_maps_key']): ?><script async src="https://maps.googleapis.com/maps/api/js?key=<?= e(rawurlencode(app_config()['google_maps_key'])) ?>&amp;callback=initStreakFitRoute&amp;loading=async"></script><?php endif; ?>
<?php endif; ?>
</main><?php if (!$admin) require APP_ROOT . '/app/Views/components/arena-dock.php'; ?></div><script src="<?= e(sf_asset_version('js/streakfit-future.js')) ?>" defer></script><script src="<?= e(sf_asset_version('js/streakfit-motion.js')) ?>" defer></script><?php if (!$admin): ?><script src="<?= e(sf_asset_version('js/arena-v40.js')) ?>" defer></script><?php endif; ?><?php if (!$admin): ?><script src="<?= e(sf_asset_version('js/arena-v42.js')) ?>" defer></script><?php endif; ?><script src="<?= sf_h(sf_asset_version('js/arena-v41.js')) ?>" defer></script></body></html>
