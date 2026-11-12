(() => {
  const drawer = document.getElementById('accountDrawer');
  const drawerTrigger = document.querySelector('.account-menu-trigger');
  const drawerPanel = drawer?.querySelector('.account-drawer');
  const drawerCloseButtons = drawer?.querySelectorAll('[data-drawer-close]');
  let drawerCloseTimer = 0;

  function closeDrawer() {
    if (!drawer || drawer.hidden) return;
    drawer.classList.remove('is-open');
    drawerTrigger?.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('drawer-open');
    window.clearTimeout(drawerCloseTimer);
    drawerCloseTimer = window.setTimeout(() => {
      if (drawer.classList.contains('is-open')) return;
      drawer.hidden = true;
      drawerTrigger?.focus();
    }, 240);
  }

  drawerTrigger?.addEventListener('click', () => {
    if (!drawer) return;
    window.clearTimeout(drawerCloseTimer);
    drawer.hidden = false;
    drawerTrigger.setAttribute('aria-expanded', 'true');
    document.body.classList.add('drawer-open');
    requestAnimationFrame(() => {
      drawer.classList.add('is-open');
      drawerPanel?.focus();
    });
  });
  drawerCloseButtons?.forEach(button => button.addEventListener('click', closeDrawer));
  drawer?.querySelectorAll('.drawer-link, .drawer-rename').forEach(link => link.addEventListener('click', closeDrawer));
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && drawer && !drawer.hidden) closeDrawer();
  });

  const feedFilters = document.querySelectorAll('[data-feed-filter]');
  if (feedFilters.length) {
    const activityStrip = document.querySelector('[data-activity-strip]');
    const activityItems = [...document.querySelectorAll('[data-activity-kind]')];
    const activityEmpty = document.querySelector('[data-activity-empty]');
    const activityNone = document.querySelector('[data-activity-none]');
    const feedPosts = [...document.querySelectorAll('[data-feed-post]')];
    const feedEmpty = document.querySelector('[data-feed-empty]');

    feedFilters.forEach(button => button.addEventListener('click', () => {
      const selected = button.dataset.feedFilter;
      feedFilters.forEach(filter => {
        const active = filter === button;
        filter.classList.toggle('active', active);
        filter.setAttribute('aria-pressed', active ? 'true' : 'false');
      });

      const showActivity = selected !== 'completed';
      if (activityStrip) activityStrip.hidden = !showActivity;
      activityItems.forEach(item => {
        item.hidden = selected === 'working' && item.dataset.activityKind !== 'working';
      });
      if (activityEmpty) activityEmpty.hidden = selected !== 'working' || activityItems.some(item => item.dataset.activityKind === 'working');
      if (activityNone) activityNone.hidden = selected !== 'all' || activityItems.length > 0;
      feedPosts.forEach(post => { post.hidden = selected === 'working'; });
      if (feedEmpty) feedEmpty.hidden = selected === 'working' || feedPosts.length > 0;
    }));
  }

  const adminConsole = document.querySelector('.admin-console');
  if (adminConsole) {
    const adminViews = [...adminConsole.querySelectorAll('[data-admin-view]')];
    const adminNavButtons = [...adminConsole.querySelectorAll('[data-admin-nav]')];
    const adminTitle = adminConsole.querySelector('[data-admin-title]');
    const adminDescription = adminConsole.querySelector('[data-admin-description]');
    const adminSidebar = adminConsole.querySelector('#adminSidebar');
    const adminScrim = adminConsole.querySelector('[data-admin-scrim]');
    const pageCopy = {
      overview: ['Tổng quan cộng đồng', 'Theo dõi và quản lý hoạt động của nhóm trong một nơi.'],
      members: ['Quản lý thành viên', 'Xem hồ sơ, phân quyền và trạng thái tài khoản.'],
      reviews: ['Buổi tập & kiểm duyệt', 'Theo dõi buổi tập, kháng cáo và báo cáo nội dung.'],
      permissions: ['Phân quyền Admin', 'Cấp chính xác các quyền chức năng cho từng tài khoản quản trị.'],
      fund: ['Quỹ kỷ luật', 'Ghi nhận giao dịch và quản lý sổ quỹ minh bạch.'],
      reports: ['Báo cáo & lịch sử', 'Xem điểm tuần, audit log và trạng thái tác vụ nền.'],
      settings: ['Cài đặt nhóm', 'Điều chỉnh cấu hình ảnh hưởng đến hoạt động cộng đồng.']
    };
    const validAdminPages = Object.keys(pageCopy).filter(page => adminViews.some(view => view.dataset.adminView === page));

    function showAdminPage(page, updateHash = true) {
      if (!pageCopy[page] || !adminViews.some(view => view.dataset.adminView === page)) return;
      adminViews.forEach(view => { view.hidden = view.dataset.adminView !== page; });
      adminNavButtons.forEach(button => {
        const active = button.dataset.adminNav === page;
        button.classList.toggle('active', active);
        if (active) button.setAttribute('aria-current', 'page');
        else button.removeAttribute('aria-current');
      });
      if (adminTitle) adminTitle.textContent = pageCopy[page][0];
      if (adminDescription) adminDescription.textContent = pageCopy[page][1];
      adminSidebar?.classList.remove('open');
      adminScrim?.classList.remove('open');
      if (adminScrim) adminScrim.hidden = true;
      if (updateHash) {
        history.replaceState(null, '', `${location.pathname}${location.search}#${page}`);
        try { sessionStorage.setItem('streakfitAdminPage', page); } catch (_) {}
      }
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    adminNavButtons.forEach(button => button.addEventListener('click', () => showAdminPage(button.dataset.adminNav)));
    adminConsole.querySelectorAll('.admin-permission-form').forEach(form => {
      const permissionInputs = [...form.querySelectorAll('[data-permission-key]')];
      form.addEventListener('change', event => {
        const changed = event.target;
        if (!changed.matches('[data-permission-key]')) return;
        if (changed.checked && changed.dataset.requires) {
          permissionInputs.find(input => input.dataset.permissionKey === changed.dataset.requires)?.click();
        } else if (!changed.checked) {
          permissionInputs.filter(input => input.dataset.requires === changed.dataset.permissionKey && input.checked).forEach(input => input.click());
        }
      });
    });
    adminConsole.querySelectorAll('[data-admin-toggle]').forEach(button => button.addEventListener('click', () => {
      const shouldOpen = !adminSidebar?.classList.contains('open');
      adminSidebar?.classList.toggle('open', shouldOpen);
      if (adminScrim) adminScrim.hidden = !shouldOpen;
      requestAnimationFrame(() => adminScrim?.classList.toggle('open', shouldOpen));
    }));
    adminScrim?.addEventListener('click', () => {
      adminSidebar?.classList.remove('open');
      adminScrim.classList.remove('open');
      adminScrim.hidden = true;
    });
    document.addEventListener('keydown', event => {
      if (event.key === 'Escape' && adminSidebar?.classList.contains('open')) {
        adminSidebar.classList.remove('open');
        adminScrim?.classList.remove('open');
        if (adminScrim) adminScrim.hidden = true;
      }
    });

    const hashPage = location.hash.slice(1);
    let storedAdminPage = '';
    try { storedAdminPage = sessionStorage.getItem('streakfitAdminPage') || ''; } catch (_) {}
    const hasMemberQuery = new URLSearchParams(location.search).has('q') || new URLSearchParams(location.search).has('member_id');
    const initialAdminPage = validAdminPages.includes(hashPage) ? hashPage : (hasMemberQuery ? 'members' : (validAdminPages.includes(storedAdminPage) ? storedAdminPage : 'overview'));
    showAdminPage(initialAdminPage, false);
  }

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
