<?php
$maxAdminSessions = max(1, max(array_column($adminWeeklyActivity, 'sessions') ?: [0]));
$adminTasks = [];
foreach ($pendingAppeals as $item) $adminTasks[] = ['label'=>'Kháng cáo giữ chuỗi','name'=>$item['display_name'],'status'=>'Chờ duyệt','page'=>'reviews'];
foreach ($pendingReports as $item) $adminTasks[] = ['label'=>'Báo cáo nội dung','name'=>$item['reporter_name'],'status'=>'Chưa xử lý','page'=>'reviews'];
foreach ($pendingWorkouts as $item) $adminTasks[] = ['label'=>'Kiểm duyệt buổi tập','name'=>$item['display_name'],'status'=>'Chờ duyệt','page'=>'reviews'];
foreach ($adminFundMembers as $item) if ($item['contribution_status'] === 'unpaid') $adminTasks[] = ['label'=>'Xác nhận đóng quỹ','name'=>$item['display_name'],'status'=>'Chờ xác nhận','page'=>'fund'];
$recentEvents = [];
foreach ($adminRecentWorkouts as $item) $recentEvents[] = ['icon'=>'activity','title'=>$item['display_name'].($item['status'] === 'in_progress' ? ' đang tập' : ' đã hoàn thành buổi tập'),'detail'=>$item['status'] === 'in_progress' ? 'Đang trong phiên check-in' : (int)floor((int)$item['duration_seconds']/60).' phút','time'=>$item['checked_out_at'] ?: $item['checked_in_at']];
foreach (array_slice($pendingAppeals,0,3) as $item) $recentEvents[] = ['icon'=>'reviews','title'=>'Kháng cáo mới · '.$item['display_name'],'detail'=>'Chờ xem xét','time'=>$item['created_at']];
foreach (array_slice($adminFundTransactions,0,3) as $item) $recentEvents[] = ['icon'=>'fund','title'=>'Cập nhật quỹ · '.$item['display_name'],'detail'=>number_format((int)$item['amount']).' ₫','time'=>$item['created_at']];
usort($recentEvents, static fn(array $a,array $b): int => strcmp($b['time'],$a['time']));
?>
<div class="admin-metrics">
    <?php if ($canViewMembers): ?><article class="admin-metric"><div class="admin-metric-caption">Thành viên <?= $adminIcon('members') ?></div><strong><?= (int)$adminStats['members'] ?></strong><small class="good"><?= (int)$adminStats['active_members'] ?> tài khoản đang hoạt động</small></article><?php endif; ?>
    <?php if ($canViewWorkouts): ?><article class="admin-metric"><div class="admin-metric-caption">Đang tập <?= $adminIcon('activity') ?></div><strong><?= (int)$adminStats['active_workouts'] ?></strong><small>Phiên check-in chưa kết thúc</small></article><?php endif; ?>
    <?php if ($canViewAnalytics): ?><article class="admin-metric"><div class="admin-metric-caption">Hoàn thành mục tiêu <?= $adminIcon('check') ?></div><strong><?= (int)$adminStats['goal_percent'] ?>%</strong><small class="good"><?= (int)$adminStats['goals_met'] ?>/<?= (int)$adminStats['goal_members'] ?> thành viên đạt mục tiêu tuần</small></article><?php endif; ?>
    <?php if ($canViewWorkouts || $canViewAppeals || $canViewContentReports || $canViewFund): ?><article class="admin-metric"><div class="admin-metric-caption">Cần xử lý <?= $adminIcon('alert') ?></div><strong><?= (int)$adminStats['pending_total'] ?></strong><small class="warn">Kiểm duyệt, kháng cáo và xác nhận quỹ</small></article><?php endif; ?>
</div>
<div class="admin-overview-grid">
    <?php if ($canViewAnalytics): ?>
    <section class="admin-panel"><div class="admin-panel-head"><div><h2>Hoạt động tập luyện tuần</h2><p>Số buổi hoàn thành của cả nhóm theo ngày</p></div><button type="button" class="admin-text-button" data-admin-nav="reports">Xem báo cáo →</button></div>
        <div class="admin-chart" role="img" aria-label="Buổi tập hoàn thành theo ngày"><?php foreach ($adminWeeklyActivity as $day): ?><div class="admin-chart-day" title="<?= sf_h($day['date']) ?>: <?= (int)$day['sessions'] ?> buổi"><span><?= (int)$day['sessions'] ?></span><i style="height:<?= max(3,(int)round((int)$day['sessions']/$maxAdminSessions*125)) ?>px"></i><small><?= sf_h($day['label']) ?></small></div><?php endforeach; ?></div><div class="admin-chart-legend"><i></i>Số lượt hoàn thành · Tuần <?= sf_h(date('d/m', strtotime($currentWeek))) ?> – <?= sf_h(date('d/m', strtotime($currentWeek.' +6 days'))) ?></div>
    </section><?php endif; ?>
    <section class="admin-panel"><div class="admin-panel-head"><div><h2>Hoạt động gần đây</h2><p>Cập nhật mới nhất từ cộng đồng</p></div></div><div class="admin-recent-list">
        <?php foreach (array_slice($recentEvents,0,4) as $event): ?><div class="admin-recent-item"><span class="admin-event-icon"><?= $adminIcon($event['icon']) ?></span><div><strong><?= sf_h($event['title']) ?></strong><small><?= sf_h($event['detail']) ?> · <?= sf_h(sf_format_time($event['time'],'d/m · H:i')) ?></small></div></div><?php endforeach; ?>
        <?php if (!$recentEvents): ?><p class="admin-empty">Chưa có hoạt động trong phạm vi bạn được xem.</p><?php endif; ?>
    </div></section>
</div>
<section class="admin-panel"><div class="admin-panel-head"><div><h2>Thao tác nhanh</h2><p>Các công việc thường dùng của quản trị viên</p></div></div><div class="admin-quick-grid">
    <?php if ($canViewMembers || $canAdjustPoints): ?><button type="button" data-admin-nav="members"><?= $adminIcon('members') ?><strong>Quản lý thành viên</strong><small>Tìm kiếm, vai trò, trạng thái</small></button><?php endif; ?>
    <?php if (isset($adminNavigation['reviews'])): ?><button type="button" data-admin-nav="reviews"><?= $adminIcon('reviews') ?><strong>Kiểm duyệt hoạt động</strong><small>Báo cáo bài đăng, kháng cáo</small></button><?php endif; ?>
    <?php if ($canViewFund): ?><button type="button" data-admin-nav="fund"><?= $adminIcon('fund') ?><strong>Xử lý quỹ</strong><small>Xác nhận đóng và xem giao dịch</small></button><?php endif; ?>
    <?php if (count($adminNavigation) === 1): ?><p class="admin-empty">Tài khoản hiện chỉ có quyền xem tổng quan.</p><?php endif; ?>
</div></section>
<section class="admin-panel"><div class="admin-panel-head"><div><h2>Việc cần xử lý</h2><p>Tập trung các yêu cầu quan trọng</p></div><span class="admin-status wait"><?= (int)$adminStats['pending_total'] ?> yêu cầu</span></div>
    <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Loại yêu cầu</th><th>Người gửi</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody>
        <?php foreach (array_slice($adminTasks,0,8) as $task): ?><tr><td><?= sf_h($task['label']) ?></td><td><?= sf_h($task['name']) ?></td><td><span class="admin-status wait"><?= sf_h($task['status']) ?></span></td><td><button type="button" class="btn secondary" data-admin-nav="<?= sf_h($task['page']) ?>">Xem chi tiết</button></td></tr><?php endforeach; ?>
        <?php if (!$adminTasks): ?><tr><td colspan="4" class="admin-empty">Đã xử lý hết yêu cầu. Các yêu cầu mới sẽ xuất hiện tại đây.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>
