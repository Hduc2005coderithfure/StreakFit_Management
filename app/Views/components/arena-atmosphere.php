<?php
// Weather is opt-in, independent of workout GPS and private to this browser tab.
// Only the signed-in member identifier is used to namespace session preferences.
?>
<section class="sf36-weather" data-sf36-panel data-member="<?= sf_h((string)(int)$user['id']) ?>" aria-label="Thời tiết và không gian luyện tập">
    <div class="sf36-sky" aria-hidden="true"><i class="sf36-stars"></i><i class="sf36-clouds"></i><i class="sf36-orb"></i><i class="sf36-rain"></i><i class="sf36-horizon"></i></div>
    <div class="sf36-weather-inner">
        <div class="sf36-weather-lead"><span class="sf36-eyebrow"><i></i> ĐIỀU KIỆN NGOÀI TRỜI</span><strong>Thời tiết khu vực</strong><small data-sf36-phase>Giao diện theo giờ thiết bị</small></div>
        <div class="sf36-weather-summary"><span class="sf36-weather-icon" data-sf36-icon aria-hidden="true">◈</span><div><strong data-sf36-temp>—</strong><span data-sf36-condition>Thời tiết theo vị trí</span><small data-sf36-extra>Chưa bật định vị thời tiết</small></div></div>
        <div class="sf36-weather-actions"><button class="sf36-weather-button" type="button" data-sf36-enable>Bật thời tiết</button><button class="sf36-weather-button sf36-secondary" type="button" data-sf36-disable hidden>Tắt thời tiết</button><small data-sf36-status role="status" aria-live="polite">Chỉ lấy vị trí khi bạn bật và cấp quyền.</small></div>
    </div>
    <div class="sf36-weather-foot"><span>Chỉ gửi vị trí làm tròn khoảng 1 km đến dịch vụ dự báo. Không lưu tọa độ hay dùng GPS buổi tập.</span><a href="https://open-meteo.com/" target="_blank" rel="noopener noreferrer">Dữ liệu: Open-Meteo (CC BY 4.0)</a></div>
</section>
