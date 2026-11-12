<section class="panel admin-page-panel">
    <div class="section-heading"><div><h2>Phân quyền từng tài khoản Admin</h2><p class="muted">Bảng điều khiển là quyền bắt buộc. Các quyền phụ thuộc sẽ được cấp kèm quyền xem tương ứng.</p></div></div>
    <div class="admin-permission-note"><b>Nguyên tắc an toàn</b><span>Hệ thống luôn giữ ít nhất một Admin đang hoạt động có quyền quản lý phân quyền. Khi hạ tài khoản xuống Member, toàn bộ quyền Admin bị thu hồi.</span></div>
    <?php $permissionRequirements = ['members.manage'=>'members.view','members.change_role'=>'members.view','members.health.view'=>'members.view','workouts.review'=>'workouts.view','appeals.review'=>'appeals.view','content_reports.manage'=>'content_reports.view','fund.manage'=>'fund.view','settings.manage'=>'settings.view']; ?>
    <?php foreach ($adminAccounts as $account): $grantedPermissions = $permissionAssignments[(int)$account['id']] ?? []; ?>
        <details class="admin-account-permissions" <?= (int)$account['id'] === (int)$user['id'] ? 'open' : '' ?>>
            <summary><span><strong><?= sf_h($account['display_name']) ?></strong><small><?= sf_h($account['email']) ?> · <?= (int)$account['is_active'] === 1 ? 'Đang hoạt động' : 'Đã khóa' ?></small></span><b><?= count($grantedPermissions) ?> quyền</b></summary>
            <form method="post" class="admin-permission-form"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="admin_assign_permissions"><input type="hidden" name="member_id" value="<?= (int)$account['id'] ?>"><input type="hidden" name="permissions[]" value="dashboard.view">
                <?php foreach (\App\Services\AdminPermissionService::groups() as $group => $items): ?><fieldset class="admin-permission-group"><legend><?= sf_h($group) ?></legend><?php foreach ($items as $key => $permission): ?>
                    <?php if ($key === 'dashboard.view'): ?><label class="admin-permission-option is-required"><input type="checkbox" checked disabled><span><strong><?= sf_h($permission['label']) ?> <em>Bắt buộc</em></strong><small><?= sf_h($permission['description']) ?></small></span></label>
                    <?php else: ?><label class="admin-permission-option"><input type="checkbox" name="permissions[]" value="<?= sf_h($key) ?>" data-permission-key="<?= sf_h($key) ?>" data-requires="<?= sf_h($permissionRequirements[$key] ?? '') ?>" <?= in_array($key, $grantedPermissions, true) ? 'checked' : '' ?>><span><strong><?= sf_h($permission['label']) ?></strong><small><?= sf_h($permission['description']) ?></small></span></label><?php endif; ?>
                <?php endforeach; ?></fieldset><?php endforeach; ?>
                <button class="btn primary">Lưu quyền cho <?= sf_h($account['display_name']) ?></button>
            </form>
        </details>
    <?php endforeach; ?>
    <?php if (!$adminAccounts): ?><p class="empty-inline">Không có tài khoản Admin để phân quyền.</p><?php endif; ?>
</section>
