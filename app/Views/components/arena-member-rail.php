<?php // Workout activity from the existing feed query, never browser presence. ?>
<aside class="sf40-member-rail sf41-member-rail" aria-label="Hoạt động luyện tập của thành viên">
  <header class="sf41-rail-heading"><span><?= $arenaIcon('people') ?></span><div><strong>Hoạt động nhóm</strong><small><?= (int)($activeTrainingCount ?? 0) ?> đang tập</small></div></header>
  <div class="sf40-rail-list sf41-rail-list">
  <?php if (empty($activityMembers)): ?><p class="sf41-rail-message">Chưa có hoạt động tập gần đây.</p><?php endif; ?>
  <?php foreach ($activityMembers ?? [] as $member): $isTraining=$member['status']==='in_progress'; ?>
    <div class="sf41-person" data-state="<?= $isTraining?'training':'finished' ?>">
      <span class="sf41-avatar"><?= sf_h(mb_substr($member['display_name'],0,1,'UTF-8')) ?><i aria-hidden="true"></i></span>
      <div class="sf41-person-copy"><strong title="<?= sf_h($member['display_name']) ?>"><?= sf_h($member['display_name']) ?></strong><small><?= $isTraining?'Đang tập':'Đã hoàn thành' ?><?php if(!$isTraining && !empty($member['checked_out_at'])): ?> · <?= sf_h(sf_format_time($member['checked_out_at'],'d/m')) ?><?php endif; ?></small></div>
    </div>
  <?php endforeach; ?>
  </div>
  <div class="sf41-rail-footer"><a href="<?= sf_h(url('dashboard?view=community')) ?>" data-sf33-view="community">Xem bảng tin <?= $arenaIcon('people') ?></a></div>
</aside>
