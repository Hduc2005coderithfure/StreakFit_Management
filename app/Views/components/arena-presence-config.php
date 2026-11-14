<?php // Only render on authenticated member/admin pages. No browser-side identity parameter. ?>
<div hidden data-sf41-presence data-endpoint="<?= sf_h(url('api/presence')) ?>" data-csrf="<?= sf_h(sf_csrf_token()) ?>"></div>
<script src="<?= sf_h(sf_asset_version('js/arena-v41.js')) ?>" defer></script>
