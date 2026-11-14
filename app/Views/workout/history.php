<section class="panel">
     <h1>Lịch sử tập luyện</h1>
    <?php if (empty($workouts)): ?><p class="empty-state">Chưa có buổi tập nào.</p><?php else: ?>
        <div class="plan-list"><?php foreach ($workouts as $workout): ?>
            <article class="plan-row"><strong><?= e($workout['workout_date']) ?></strong><span><?= e(['valid'=>'Hợp lệ','in_progress'=>'Đang tập','invalid'=>'Không hợp lệ','abandoned'=>'Đã hủy','pending'=>'Chờ duyệt'][$workout['status']] ?? 'Chưa xác định') ?></span><span><?= (int)floor((int)$workout['duration_seconds'] / 60) ?> phút</span><a href="<?= e(url('workouts/route?id='.(int)$workout['id'])) ?>">Đường tập GPS ↗</a></article>
        <?php endforeach; ?></div>
    <?php endif; ?>
</section>
