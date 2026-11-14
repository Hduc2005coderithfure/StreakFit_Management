(() => {
  'use strict';
  // Progressive enhancement: links and forms continue to work without this file.
  const dock = document.querySelector('.arena-dock');
  if (dock) {
    const lens = document.createElement('span');
    lens.className = 'sf37-dock-lens';
    lens.setAttribute('aria-hidden', 'true');
    dock.prepend(lens);
    let frame = 0;
    const position = () => {
      frame = 0;
      const active = dock.querySelector('.nav-item.active');
      if (!active || !active.offsetWidth || !active.offsetHeight) {
        dock.classList.remove('sf37-glass-ready'); lens.classList.remove('is-ready'); return;
      }
      lens.style.width = `${active.offsetWidth}px`;
      lens.style.height = `${active.offsetHeight}px`;
      lens.style.transform = `translate(${active.offsetLeft}px, ${active.offsetTop}px)`;
      dock.classList.add('sf37-glass-ready');
      lens.classList.add('is-ready');
    };
    const schedule = () => { if (!frame) frame = requestAnimationFrame(position); };
    position();
    document.addEventListener('sf:arena-view', schedule);
    window.addEventListener('resize', schedule, {passive: true});
    window.addEventListener('pageshow', schedule);
    if ('ResizeObserver' in window) new ResizeObserver(schedule).observe(dock);
    if ('MutationObserver' in window) {
      const observer = new MutationObserver(schedule);
      dock.querySelectorAll('.nav-item').forEach(a => observer.observe(a, {attributes:true, attributeFilter:['class','aria-current']}));
    }
  }
  document.querySelectorAll('[data-sf37-password]').forEach(button => {
    const input = document.getElementById(button.dataset.sf37Password);
    if (!(input instanceof HTMLInputElement) || input.type !== 'password') return;
    button.hidden = false;
    const conceal = () => { input.type = 'password'; button.textContent = 'Hiện'; button.setAttribute('aria-label','Hiện mật khẩu'); button.setAttribute('aria-pressed','false'); };
    button.addEventListener('click', () => {
      if (input.type === 'text') { conceal(); return; }
      input.type = 'text'; button.textContent = 'Ẩn'; button.setAttribute('aria-label','Ẩn mật khẩu'); button.setAttribute('aria-pressed','true');
    });
    input.form?.addEventListener('submit', conceal);
    window.addEventListener('pagehide', conceal);
  });
})();
