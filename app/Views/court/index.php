<section class="panel">
    <div class="eyebrow">CỘNG ĐỒNG</div><h1>Tòa án kỷ luật</h1>
    <?php foreach (($appeals ?? []) as $appeal): ?>
        <article class="appeal-card"><p><?= e($appeal['reason']) ?></p><small><?= e($appeal['created_at']) ?></small>
            <?php if (empty($appeal['my_vote'])): ?><form method="post" class="vote-row"><?= csrf_field() ?><input type="hidden" name="action" value="vote_appeal"><input type="hidden" name="appeal_id" value="<?= (int)$appeal['id'] ?>"><button class="btn vote-yes" name="vote" value="yes">Đồng ý</button><button class="btn vote-no" name="vote" value="no">Không đồng ý</button></form><?php endif; ?>
        </article>
    <?php endforeach; ?>
</section>
