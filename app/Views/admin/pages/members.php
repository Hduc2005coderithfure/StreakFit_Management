<?php if ($canViewMembers): ?>
<section class="panel">
    <div class="section-heading"><div><h2>Danh sách thành viên</h2><p>Tra cứu tài khoản và trạng thái tham gia</p></div><?php if ($canManageMembers): ?><button type="button" class="btn primary" data-open-invite>+ Mời thành viên</button><?php endif; ?></div>
    <form method="get" action="<?= sf_h(url('admin')) ?>#members" class="admin-search-form">
        <input name="q" value="<?= sf_h((string)($_GET['q'] ?? '')) ?>" placeholder="Tìm theo tên hoặc email..." aria-label="Tìm thành viên">
        <select name="member_status" aria-label="Lọc trạng thái"><?php foreach (['all'=>'Tất cả trạng thái','active'=>'Đang hoạt động','inactive'=>'Tạm ngưng'] as $key=>$label): ?><option value="<?= sf_h($key) ?>" <?= $adminMemberStatus === $key ? 'selected' : '' ?>><?= sf_h($label) ?></option><?php endforeach; ?></select><button class="btn secondary">Tìm kiếm</button>
    </form>
    <div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Thành viên</th><th>Vai trò</th><th>Điểm</th><th>Trạng thái</th><th>Quản lý</th></tr></thead><tbody>
        <?php foreach ($adminMembers as $member): ?><tr><td><div class="admin-person"><span class="admin-initial"><?= sf_h(mb_substr($member['display_name'],0,1,'UTF-8')) ?></span><span><strong><?= sf_h($member['display_name']) ?></strong><small><?= sf_h($member['email']) ?></small></span></div></td><td><?= $member['role'] === 'admin' ? 'Admin' : 'Member' ?></td><td><?= number_format((int)$member['total_points']) ?></td><td><span class="admin-status <?= (int)$member['is_active'] ? '' : 'off' ?>"><?= (int)$member['is_active'] ? 'Hoạt động' : 'Tạm ngưng' ?></span></td><td><a class="btn secondary" href="<?= sf_h(url('admin?member_id='.(int)$member['id'])) ?>#members">Xem hồ sơ</a></td></tr><?php endforeach; ?>
        <?php if (!$adminMembers): ?><tr><td colspan="5" class="admin-empty">Không tìm thấy thành viên phù hợp.</td></tr><?php endif; ?>
    </tbody></table></div>
    <p class="muted" style="margin-top:14px"><?= count($adminMembers) ?> kết quả · Tối đa 100 tài khoản mỗi lần tìm kiếm</p>
</section>
<?php endif; ?>

<?php if ($selectedMember && ($canManageMembers || $canChangeMemberRole)): ?>
<section class="panel"><div class="section-heading"><div><h2>Quản lý · <?= sf_h($selectedMember['display_name']) ?></h2><p>Vai trò và trạng thái hoạt động của tài khoản</p></div></div>
<form method="post" class="admin-member-edit-form"><?= csrf_field() ?><input type="hidden" name="action" value="admin_update_member"><input type="hidden" name="member_id" value="<?= (int)$selectedMember['id'] ?>">
<?php if ($canChangeMemberRole): ?><label>Vai trò <select name="role"><option value="member" <?= $selectedMember['role']==='member'?'selected':'' ?>>Member</option><option value="admin" <?= $selectedMember['role']==='admin'?'selected':'' ?>>Admin</option></select></label><?php endif; ?>
<?php if ($canManageMembers): ?><label>Trạng thái <select name="is_active"><option value="1" <?= (int)$selectedMember['is_active']?'selected':'' ?>>Hoạt động</option><option value="0" <?= !(int)$selectedMember['is_active']?'selected':'' ?>>Tạm ngưng</option></select></label><?php endif; ?>
<button class="btn primary">Lưu thay đổi</button></form>
<?php if ($canManageAdminPermissions && $selectedMember['role']==='admin'): ?><button type="button" class="admin-text-button" data-admin-nav="permissions" style="margin-top:16px">Phân quyền chức năng →</button><?php endif; ?></section>
<?php endif; ?>

<?php if ($selectedMember): ?>
<section class="panel admin-page-panel"><div class="section-heading"><div><h2>Hồ sơ · <?= sf_h($selectedMember['display_name']) ?></h2><p class="muted">Thông tin được giới hạn theo quyền của tài khoản.</p></div></div>
    <?php if ($canViewWorkouts): ?><h3>Lịch sử buổi tập</h3><?php foreach ($selectedMemberWorkouts as $workout): ?><div class="admin-member-row"><span><?= sf_h($workout['workout_date']) ?> · <?= sf_h($workout['status']) ?> · <?= (int)floor((int)$workout['duration_seconds'] / 60) ?> phút</span><span><?php if ($workout['checkin_image']): ?><a href="?media=<?= (int)$workout['id'] ?>&amp;kind=checkin" target="_blank" rel="noopener">Ảnh vào</a><?php endif; ?> <?php if ($workout['checkout_image']): ?><a href="?media=<?= (int)$workout['id'] ?>&amp;kind=checkout" target="_blank" rel="noopener">Ảnh ra</a><?php endif; ?></span></div><?php endforeach; ?><?php endif; ?>
    <?php if ($canViewMemberHealth): ?><h3>Dữ liệu sức khỏe riêng tư</h3><?php foreach ($selectedMemberHealth as $metric): ?><div class="admin-member-row"><span><?= sf_h($metric['recorded_at']) ?></span><b><?= sf_h($metric['height_cm']) ?> cm · <?= sf_h($metric['weight_kg']) ?> kg</b></div><?php endforeach; ?><?php endif; ?>
    <?php if ($canViewAnalytics): ?><h3>Điểm tuần</h3><?php foreach ($selectedMemberScores as $score): ?><div class="admin-member-row"><span><?= sf_h($score['week_start']) ?> · <?= (int)$score['valid_sessions'] ?>/<?= (int)$score['target_sessions'] ?> buổi · chuỗi <?= (int)$score['streak_after'] ?></span><b><?= (int)$score['total_week_points'] ?> điểm</b></div><?php endforeach; ?><?php endif; ?>
    <?php if ($canAdjustPoints): ?><h3>Sổ điểm</h3><?php foreach ($selectedMemberLedger as $entry): ?><div class="admin-member-row"><span><?= sf_h($entry['created_at']) ?> · <?= sf_h($entry['reason']) ?></span><b><?= (int)$entry['points'] ?></b></div><?php endforeach; ?><?php endif; ?>
</section>
<?php endif; ?>

<?php if ($canAdjustPoints): ?><section class="panel admin-page-panel"><div class="section-heading"><div><h2>Điều chỉnh điểm</h2><p class="muted">Mọi thay đổi cần có lý do và được ghi vào sổ điểm.</p></div></div><form method="post" class="admin-inline-form"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="admin_adjust_points"><select name="member_id" required><?php foreach ($adminActionMembers as $member): ?><option value="<?= (int)$member['id'] ?>"><?= sf_h($member['display_name']) ?> · <?= (int)$member['total_points'] ?> điểm</option><?php endforeach; ?></select><input name="points" type="number" min="-100000" max="100000" required placeholder="+/- điểm"><input name="reason" minlength="5" maxlength="180" required placeholder="Lý do"><button class="btn secondary">Điều chỉnh</button></form></section><?php endif; ?>
