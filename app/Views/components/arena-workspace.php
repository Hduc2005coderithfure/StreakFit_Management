<?php
// Navigation only. Keep all original routes and access checks.
$workspaceGroups = [
    'Luyện tập' => [
        ['overview','Tổng quan','dashboard?view=overview','grid'],
        ['checkin','Ghi nhận buổi tập','workouts','plus'],
        ['schedule','Lịch tập','profile?section=schedule','calendar'],
        ['history','Lịch sử tập','workouts/history','history'],
        ['insights','Thống kê','dashboard?view=insights','activity'],
        ['health','Sức khỏe','profile?section=health','heart'],
    ],
    'Cộng đồng' => [
        ['community','Bảng tin','dashboard?view=community','people'],
        ['groups','Nhóm thể thao','groups','activity'],
        ['leaderboard','Bảng vàng','leaderboard?view=rank','trophy'],
        ['court','Nghỉ tập & giải trình','leaderboard?view=court','calendar'],
    ],
    'Cá nhân' => [
        ['fund','Quỹ nhóm','profile?section=fund','wallet'],
        ['other','Trợ lý AI','profile?section=other','spark'],
        ['notifications','Thông báo','notifications','bell'],
        ['profile','Hồ sơ & cài đặt','profile','user'],
    ],
];
$workspaceActive = $tab === 'feed' ? $arenaView : ($tab === 'leaderboard' && $arenaView === 'court' ? 'court' : ($tab === 'profile' && in_array($profileSection,['schedule','health','fund','other'],true) ? $profileSection : ($tab==='payments'?'fund':($tab==='route'?'checkin':$tab))));
?>
<aside class="sf39-sidebar sf40-sidebar" aria-label="Không gian làm việc StreakFit">
    <a class="sf39-brand" href="<?= sf_h(url('dashboard')) ?>"><span class="sf39-brand-icon">SF</span><span><strong>Streak<span>Fit</span></strong><small>Không gian luyện tập</small></span></a>
    <button type="button" class="sf40-collapse" aria-label="Thu gọn thanh điều hướng" aria-expanded="true" title="Thu gọn thanh điều hướng"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M14 6l-6 6 6 6M20 6l-6 6 6 6"/></svg><span>Thu gọn</span></button>
    <nav class="sf39-menu" aria-label="Điều hướng desktop">
        <?php foreach($workspaceGroups as $groupName=>$items): ?>
        <div class="sf39-menu-group" data-sf40-group="<?= sf_h($groupName) ?>"><h2><?= sf_h($groupName) ?></h2>
            <?php foreach($items as [$key,$label,$path,$icon]): $selected=$workspaceActive===$key; $tileColors=['overview'=>'blue','checkin'=>'green','schedule'=>'violet','history'=>'orange','insights'=>'cyan','community'=>'pink','groups'=>'teal','leaderboard'=>'gold','court'=>'violet','health'=>'pink','fund'=>'green','other'=>'violet','notifications'=>'blue','profile'=>'cyan']; ?>
            <a class="sf39-nav<?= $selected?' is-current':'' ?>" data-sf40-tone="<?= sf_h($tileColors[$key]??'blue') ?>" aria-label="<?= sf_h($label) ?>" title="<?= sf_h($label) ?>" href="<?= sf_h(url($path)) ?>" <?= in_array($key,['overview','community','insights'],true)?'data-sf33-view="'.sf_h($key).'"':'' ?> <?= $tab==='leaderboard' && in_array($key,['leaderboard','court'],true)?'data-sf39-leader-view="'.($key==='court'?'court':'rank').'"':'' ?> <?= $selected?'aria-current="page"':'' ?>><span class="sf40-nav-icon"><?= $arenaIcon($icon) ?></span><span class="sf40-nav-label"><?= sf_h($label) ?></span><?php if($key==='notifications' && $unreadCount): ?><b class="sf39-count"><?= (int)$unreadCount ?></b><?php elseif($selected): ?><i aria-hidden="true"></i><?php endif; ?></a>
            <?php endforeach; ?>
        </div><?php endforeach; ?>
        <?php if($canAdminDashboard): ?><div class="sf39-menu-group"><h2>Quản trị</h2><a class="sf39-nav" data-sf40-tone="gold" aria-label="Trung tâm quản trị" title="Trung tâm quản trị" href="<?= sf_h(url('admin')) ?>"><span class="sf40-nav-icon"><?= $arenaIcon('grid') ?></span><span class="sf40-nav-label">Trung tâm quản trị</span></a></div><?php endif; ?>
    </nav>
    <div class="sf39-sidebar-foot"><a class="sf39-side-profile" href="<?= sf_h(url('profile')) ?>"><?= sf_rank_emblem($rank,'sf39-side-emblem') ?><span><strong><?= sf_h($user['display_name']) ?></strong><small><?= sf_h($rank['name']) ?> · <?= number_format($arenaPoints,0,',','.') ?> XP</small></span></a><div class="sf39-side-progress"><span>Thăng hạng</span><b><?= $rank['next']===null?'MAX':number_format(floor(1000*max(0,$arenaPoints-(int)$rank['min'])/max(1,(int)$rank['next']-(int)$rank['min']))/10,1,',','.').'%' ?></b></div><div class="sf39-side-track" aria-hidden="true"><i style="width:<?= $rank['next']===null?100:min(100,max(0,floor(1000*($arenaPoints-(int)$rank['min'])/max(1,(int)$rank['next']-(int)$rank['min']))/10)) ?>%"></i></div><a class="sf39-side-rank" href="<?= sf_h(url('leaderboard?view=rank')) ?>">Xem cấp bậc →</a></div>
    <div class="sf41-clock" aria-label="Đồng hồ ngày giờ"><span class="sf41-clock-icon" aria-hidden="true"><?= $arenaIcon('history') ?></span><div><time data-sf41-time>--:--</time><small data-sf41-date>--/--/----</small></div></div>
</aside>
