            <section class="checkin-card" data-checkin-start="<?= $activeWorkout ? (int)strtotime($activeWorkout['checked_in_at'] . ' UTC') : '' ?>" data-minimum-seconds="<?= (int)$settings['minimum_workout_seconds'] ?>">
                <div class="camera-frame"><video id="cameraVideo" playsinline muted autoplay></video><div class="camera-placeholder" id="cameraPlaceholder"><span>◎</span><strong>Camera StreakFit</strong><small>Nhấn mở camera để bắt đầu</small></div><div class="camera-watermark" id="cameraWatermark">STREAKFIT · <?= date('d/m/Y H:i') ?></div></div>
                <div class="camera-controls"><button class="btn secondary" type="button" data-camera-start>Mở camera</button><button class="btn secondary" type="button" data-camera-flip disabled>Đổi camera</button></div>
                <div class="timer-display" id="workoutTimer"><?= $activeWorkout ? '00:00:00' : '—' ?></div>
                <p class="timer-hint" id="timerHint"><?= $activeWorkout ? 'Đang tính thời gian từ máy chủ…' : 'Mỗi ngày tối đa một buổi hợp lệ · Tối thiểu ' . (int)ceil($settings['minimum_workout_seconds'] / 60) . ' phút' ?></p>
                <?php if (!$activeWorkout && $weeklyCount >= 7): ?><div class="alert info">Bạn đã có 7 buổi tập hợp lệ trong tuần này.</div><?php endif; ?>
                <form method="post" class="capture-form" data-capture-form>
                    <input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="<?= $activeWorkout ? 'finish_workout' : 'start_workout' ?>"><input type="hidden" name="image_data" value="">
                    <?php if ($activeWorkout): ?><input type="hidden" name="workout_id" value="<?= (int)$activeWorkout['id'] ?>"><?php endif; ?>
                    <?php if ($activeWorkout): ?><label class="caption-label">Lời nhắn cho bảng tin <textarea name="caption" maxlength="500" placeholder="Hôm nay mình đã hoàn thành…"></textarea></label><?php endif; ?>
                    <button class="btn primary full" type="submit" data-capture-submit disabled><?= $activeWorkout ? 'Chụp ảnh & kết thúc' : 'Chụp ảnh & bắt đầu' ?></button>
                </form>
                <?php if ($activeWorkout): ?><form method="post" class="discard-form"><input type="hidden" name="_csrf" value="<?= sf_h(sf_csrf_token()) ?>"><input type="hidden" name="action" value="discard_workout"><input type="hidden" name="workout_id" value="<?= (int)$activeWorkout['id'] ?>"><button class="text-link danger-link">Hủy buổi tập đang mở</button></form><?php endif; ?>
            </section>
            <canvas id="captureCanvas" hidden></canvas>
<?php if ($activeWorkout): ?><a class="btn secondary full" href="<?= e(url('workouts/route?id='.(int)$activeWorkout['id'])) ?>">Theo dõi đường chạy GPS</a><?php endif; ?>
            <p class="privacy-note">Ảnh được lưu trong website để tạo thẻ tổng kết. Thời gian bắt đầu và kết thúc được ghi bởi máy chủ.</p>
