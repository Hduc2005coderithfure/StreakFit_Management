(() => {
  const cameraFrame = document.querySelector('.camera-frame');
  const checkinCard = document.querySelector('[data-checkin-start]');
  const minimumSeconds = Number(checkinCard?.dataset.minimumSeconds || 1800);
  const timezone = document.body.dataset.timezone || 'Asia/Ho_Chi_Minh';
  const video = document.getElementById('cameraVideo');
  const placeholder = document.getElementById('cameraPlaceholder');
  const startCameraButton = document.querySelector('[data-camera-start]');
  const captureButton = document.querySelector('[data-capture-submit]');
  const captureForm = document.querySelector('[data-capture-form]');
  const canvas = document.getElementById('captureCanvas');
  let stream = null;
  let facingMode = 'environment';

  async function openCamera() {
    if (!navigator.mediaDevices?.getUserMedia) {
      alert('Camera cần HTTPS hoặc localhost và trình duyệt được hỗ trợ.');
      return;
    }
    try {
      if (stream) stream.getTracks().forEach(track => track.stop());
      stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: { ideal: facingMode }, width: { ideal: 960 }, height: { ideal: 1280 } },
        audio: false
      });
      video.srcObject = stream;
      await video.play();
      cameraFrame?.classList.add('live');
      if (placeholder) placeholder.hidden = true;
      const startedAt = Number(document.querySelector('[data-checkin-start]')?.dataset.checkinStart || 0);
      const timeReady = !startedAt || (Date.now() / 1000 - startedAt) >= minimumSeconds;
      if (captureButton) captureButton.disabled = !timeReady;
      const flip = document.querySelector('[data-camera-flip]');
      if (flip) flip.disabled = false;
      if (startCameraButton) startCameraButton.textContent = 'Camera đã sẵn sàng';
    } catch (error) {
      alert('Không thể mở camera. Hãy cấp quyền camera cho localhost rồi thử lại.');
    }
  }

  function capturePhoto() {
    if (!video || !canvas || !video.videoWidth || !captureForm) return false;
    const width = 900;
    const height = Math.round(width * video.videoHeight / video.videoWidth);
    canvas.width = width;
    canvas.height = height;
    const context = canvas.getContext('2d');
    context.drawImage(video, 0, 0, width, height);
    const stamp = new Date().toLocaleString('vi-VN', { timeZone: timezone, dateStyle: 'short', timeStyle: 'medium' });
    const bandHeight = Math.max(46, Math.round(height * .075));
    const gradient = context.createLinearGradient(0, height - bandHeight - 12, 0, height);
    gradient.addColorStop(0, 'rgba(0,0,0,0)');
    gradient.addColorStop(1, 'rgba(0,0,0,.72)');
    context.fillStyle = gradient;
    context.fillRect(0, height - bandHeight - 12, width, bandHeight + 12);
    context.fillStyle = '#fff';
    context.font = `700 ${Math.max(18, Math.round(width * .025))}px system-ui`;
    context.textBaseline = 'bottom';
    context.fillText(`STREAKFIT · ${stamp}`, 22, height - 18);
    const input = captureForm.querySelector('[name="image_data"]');
    input.value = canvas.toDataURL('image/jpeg', .82);
    return true;
  }

  startCameraButton?.addEventListener('click', openCamera);
  document.querySelector('[data-camera-flip]')?.addEventListener('click', () => {
    facingMode = facingMode === 'environment' ? 'user' : 'environment';
    openCamera();
  });

  captureForm?.addEventListener('submit', event => {
    if (!capturePhoto()) {
      event.preventDefault();
      alert('Hãy mở camera và đợi hình ảnh hiện lên trước khi chụp.');
      return;
    }
    if (captureButton) {
      captureButton.disabled = true;
      captureButton.textContent = 'Đang lưu ảnh…';
    }
  });

  const timer = document.getElementById('workoutTimer');
  const hint = document.getElementById('timerHint');
  const checkinStart = Number(checkinCard?.dataset.checkinStart || 0);
  if (checkinStart && timer) {
    const refreshTimer = () => {
      const seconds = Math.max(0, Math.floor(Date.now() / 1000) - checkinStart);
      const hours = String(Math.floor(seconds / 3600)).padStart(2, '0');
      const minutes = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
      const remainder = String(seconds % 60).padStart(2, '0');
      timer.textContent = `${hours}:${minutes}:${remainder}`;
      const ready = seconds >= minimumSeconds;
      if (captureButton) captureButton.disabled = !ready || !stream;
      if (hint) hint.textContent = ready ? 'Đã đủ 30 phút · Có thể check-out sau khi mở camera.' : `Cần thêm ${Math.ceil((minimumSeconds - seconds) / 60)} phút để đủ điều kiện.`;
    };
    refreshTimer();
    window.setInterval(refreshTimer, 1000);
  }

  window.addEventListener('pagehide', () => stream?.getTracks().forEach(track => track.stop()));

  const assistantForm = document.getElementById('assistantForm');
  assistantForm?.addEventListener('submit', event => {
    event.preventDefault();
    const input = document.getElementById('assistantQuestion');
    const answer = document.getElementById('assistantAnswer');
    const question = input.value.trim().toLowerCase();
    if (!question) return;
    if (question.includes('bmi') || question.includes('cân nặng')) {
      answer.textContent = 'BMI là chỉ số tham khảo dựa trên cân nặng và chiều cao. Xem kết quả trong hồ sơ và trao đổi với chuyên gia nếu cần đánh giá sức khỏe.';
    } else if (question.includes('nghỉ') || question.includes('đau')) {
      answer.textContent = 'Hãy nghỉ nếu cơ thể đau hoặc mệt bất thường. Nếu triệu chứng kéo dài, hãy hỏi chuyên gia y tế.';
    } else {
      answer.textContent = 'Hãy chọn lịch tập có thể duy trì, tăng khối lượng từ từ và ưu tiên kỹ thuật an toàn. Đây là trợ lý demo, không phải tư vấn y tế.';
    }
    input.value = '';
  });
})();
