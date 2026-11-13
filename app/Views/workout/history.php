<section class="panel">
    <div class="eyebrow">NHẬT KÝ</div><h1>Lịch sử tập luyện</h1>
    <?php if (empty($workouts)): ?><p class="empty-state">Chưa có buổi tập nào.</p><?php else: ?>
        <div class="plan-list"><?php foreach ($workouts as $workout): ?>
            <article class="plan-row"><strong><?= e($workout['workout_date']) ?></strong><span><?= e(\App\Services\SportGroupService::SPORTS[$workout['sport']]??'Tập luyện') ?> · <?= e($workout['status']) ?></span><span><?= (int)floor((int)$workout['duration_seconds'] / 60) ?> phút · <?= number_format((float)$workout['distance_meters']/1000,2) ?> km</span><a href="<?= e(url('workouts/route?id='.(int)$workout['id'])) ?>">Đường tập ↗</a></article>
        <?php endforeach; ?></div>
    <?php endif; ?>
</section>
