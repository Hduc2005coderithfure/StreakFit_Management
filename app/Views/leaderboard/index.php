<section class="panel">
    <div class="eyebrow">THI ĐUA</div><h1>Bảng xếp hạng</h1>
    <?php foreach (($leaders ?? []) as $rank => $leader): ?>
        <article class="plan-row"><b><?= $rank + 1 ?></b><span><?= e($leader['display_name']) ?></span><strong><?= format_score($leader['points']) ?> điểm</strong></article>
    <?php endforeach; ?>
</section>
