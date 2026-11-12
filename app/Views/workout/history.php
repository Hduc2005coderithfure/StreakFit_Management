<section class="panel">
    <div class="eyebrow">NHẬT KÝ</div><h1>Lịch sử tập luyện</h1>
    <?php if (empty($workouts)): ?><p class="empty-state">Chưa có buổi tập nào.</p><?php else: ?>
        <div class="plan-list"><?php foreach ($workouts as $workout): ?>
            <article class="plan-row"><strong><?= e($workout['workout_date']) ?></strong><span><?= e($workout['status']) ?></span><span><?= (int)floor((int)$workout['duration_seconds'] / 60) ?> phút</span><a href="<?= e(url('workouts/route?id='.(int)$workout['id'])) ?>">GPS ↗</a></article>
        <?php endforeach; ?></div>
    <?php endif; ?>
</section>
