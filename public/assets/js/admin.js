'use strict';
(() => {
  const root = document.querySelector('.admin-console');
  if (!root) return;
  const views = [...root.querySelectorAll('[data-admin-view]')];
  const nav = [...document.querySelectorAll('[data-admin-nav]')];
  const sidebar = document.getElementById('adminSidebar');
  const scrim = document.querySelector('[data-admin-scrim]');
  const toggles = [...document.querySelectorAll('[data-admin-toggle]')];
  const titles = {
    overview: ['Tổng quan cộng đồng', 'Theo dõi và quản lý hoạt động của nhóm trong một nơi.'],
    members: ['Quản lý thành viên', 'Xem thông tin, vai trò và trạng thái tài khoản.'],
    reviews: ['Buổi tập & kiểm duyệt', 'Theo dõi check-in và xử lý báo cáo, kháng cáo.'],
    fund: ['Quỹ kỷ luật', 'Theo dõi và xác nhận quỹ tự nguyện.'],
    reports: ['Báo cáo & thống kê', 'Phân tích hoạt động và kỷ luật của nhóm.'],
    settings: ['Cài đặt nhóm', 'Điều chỉnh những cấu hình quản trị được cấp phép.'],
    permissions: ['Phân quyền Admin', 'Cấp quyền chức năng riêng cho từng quản trị viên.']
  };
  const available = new Set(views.map(view => view.dataset.adminView));
  let opener = null;
  function closeSidebar(restoreFocus = false) {
    sidebar.classList.remove('open'); scrim.classList.remove('open'); scrim.hidden = true;
    toggles.forEach(button => button.setAttribute('aria-expanded', 'false'));
    document.body.style.overflow = '';
    if (restoreFocus) opener?.focus();
  }
  function showPage(page, updateHash = true) {
    if (!available.has(page)) page = 'overview';
    views.forEach(view => { view.hidden = view.dataset.adminView !== page; });
    nav.forEach(button => {
      const active = button.dataset.adminNav === page;
      button.classList.toggle('active', active);
      if (active) button.setAttribute('aria-current', 'page'); else button.removeAttribute('aria-current');
    });
    root.querySelector('[data-admin-title]').textContent = titles[page][0];
    root.querySelector('[data-admin-description]').textContent = titles[page][1];
    document.title = 'StreakFit · ' + titles[page][0];
    closeSidebar();
    if (updateHash) history.replaceState(null, '', location.pathname + location.search + '#' + page);
    window.scrollTo({top: 0, behavior: 'instant'});
  }
  nav.forEach(button => button.addEventListener('click', () => showPage(button.dataset.adminNav)));
  toggles.forEach(button => button.addEventListener('click', () => {
    if (sidebar.classList.contains('open')) { closeSidebar(true); return; }
    opener = button;
    sidebar.classList.add('open'); scrim.hidden = false; scrim.classList.add('open');
    toggles.forEach(toggle => toggle.setAttribute('aria-expanded', 'true'));
    document.body.style.overflow = 'hidden';
    sidebar.querySelector('button, a')?.focus();
  }));
  scrim.addEventListener('click', () => closeSidebar(true));
  document.addEventListener('keydown', event => {
    if (!sidebar.classList.contains('open')) return;
    if (event.key === 'Escape') closeSidebar(true);
    if (event.key === 'Tab') {
      const items = [...sidebar.querySelectorAll('a,button')];
      const first = items[0], last = items[items.length - 1];
      if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
      else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    }
  });
  matchMedia('(min-width:721px)').addEventListener('change', event => { if (event.matches) closeSidebar(); });
  window.addEventListener('hashchange', () => showPage(location.hash.slice(1), false));
  const query = new URLSearchParams(location.search);
  const initial = location.hash.slice(1) || (query.has('admin_period') ? 'reports' : query.has('q') || query.has('member_id') || query.has('member_status') ? 'members' : 'overview');
  showPage(initial, false);

  document.querySelectorAll('.admin-permission-form').forEach(form => {
    const inputs = [...form.querySelectorAll('[data-permission-key]')];
    form.addEventListener('change', event => {
      const changed = event.target;
      if (!changed.matches('[data-permission-key]')) return;
      if (changed.checked && changed.dataset.requires) {
        const required = inputs.find(input => input.dataset.permissionKey === changed.dataset.requires);
        if (required) required.checked = true;
      } else if (!changed.checked) {
        inputs.filter(input => input.dataset.requires === changed.dataset.permissionKey).forEach(input => { input.checked = false; });
      }
    });
  });
  const invite = document.getElementById('adminInvite');
  document.querySelector('[data-open-invite]')?.addEventListener('click', () => invite.showModal());
  document.querySelector('[data-copy-invite]')?.addEventListener('click', async () => {
    const input = document.getElementById('adminInviteUrl');
    const status = document.querySelector('[data-invite-status]');
    try { await navigator.clipboard.writeText(input.value); status.textContent = 'Đã sao chép liên kết đăng ký.'; }
    catch (_) { input.select(); status.textContent = 'Liên kết đã được chọn. Nhấn Ctrl+C để sao chép.'; }
  });
})();
