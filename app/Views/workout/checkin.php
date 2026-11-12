<section class="panel">
    <div class="eyebrow">BUỔI TẬP</div>
    <h1>Check-in</h1>
    <p class="muted">Một buổi hợp lệ cần tối thiểu 30 phút và ảnh camera lúc bắt đầu, kết thúc.</p>
    <?php if (empty($activeWorkout)): ?>
        <form method="post" class="stack-form" data-capture-form>
            <?= csrf_field() ?><input type="hidden" name="action" value="start_workout">
            <input type="hidden" name="image_data">
            <video id="cameraVideo" autoplay playsinline hidden></video><canvas id="captureCanvas" hidden></canvas>
            <button class="btn secondary" type="button" data-camera-start>Mở camera</button>
            <button class="btn primary" type="submit" data-capture-submit>Check-in</button>
        </form>
    <?php else: ?>
        <form method="post" class="stack-form" data-capture-form>
            <?= csrf_field() ?><input type="hidden" name="action" value="finish_workout">
            <input type="hidden" name="workout_id" value="<?= (int)$activeWorkout['id'] ?>"><input type="hidden" name="image_data">
            <label>Ghi chú<input name="caption" maxlength="500"></label>
            <video id="cameraVideo" autoplay playsinline hidden></video><canvas id="captureCanvas" hidden></canvas>
            <button class="btn secondary" type="button" data-camera-start>Mở camera</button>
            <button class="btn primary" type="submit" data-capture-submit>Check-out</button>
        </form>
    <?php endif; ?>
</section>
