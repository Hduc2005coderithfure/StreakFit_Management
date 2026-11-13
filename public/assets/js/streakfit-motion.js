(() => {
  'use strict';
  const body = document.body;
  if (!body?.classList.contains('sf-future')) return;
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
  const fine = window.matchMedia('(hover: hover) and (pointer: fine)');
  const motion = () => !reduced.matches;

  function celebrate() {
    if (!motion() || !document.querySelector('[data-sf-celebrate]')) return;
    const layer = document.createElement('div');
    layer.className = 'sf-celebration';
    layer.setAttribute('aria-hidden', 'true');
    const card = document.createElement('div');
    card.className = 'sf-celebration-card';
    card.innerHTML = '<span>✦</span><strong>BUỔI TẬP HOÀN THÀNH</strong><small>Thêm một bước trên hành trình của bạn.</small>';
    layer.append(card);
    const colors = ['#ffad77', '#7ce7f5', '#f880a0', '#a994ff', '#8be8ba'];
    for (let i = 0; i < 34; i++) {
      const p = document.createElement('i');
      const angle = (Math.PI * 2 * i / 34) + Math.random() * .25;
      const distance = 130 + Math.random() * 250;
      p.className = 'sf-confetti';
      p.style.setProperty('--sf-dx', `${Math.cos(angle) * distance}px`);
      p.style.setProperty('--sf-dy', `${Math.sin(angle) * distance + 140}px`);
      p.style.setProperty('--sf-spin', `${Math.round((Math.random() - .5) * 1100)}deg`);
      p.style.setProperty('--sf-size', `${4 + Math.round(Math.random() * 5)}px`);
      p.style.setProperty('--sf-delay', `${i % 7 * 24}ms`);
      p.style.setProperty('--sf-color', colors[i % colors.length]);
      layer.append(p);
    }
    body.append(layer);
    window.setTimeout(() => layer.remove(), 2600);
  }

  // The cinematic dialog is handled by arena-ui.js. All page links stay native.
  // Never delay navigation or cover the viewport with a wipe.
  document.documentElement.classList.remove('sf-motion-preboot');
  body.classList.add('sf-entered');
  try { sessionStorage.setItem('sf-intro-v2', 'seen'); } catch (_) {}
  window.setTimeout(celebrate, 300);
  window.addEventListener('pageshow', () => {
    document.documentElement.classList.remove('sf-motion-preboot');
    body.classList.add('sf-entered');
  });

  // Pointer ripples are decorative and never intercept submit/click handlers.
  const pressables = '.btn, .icon-button, .feed-filters button, .nav-item, .sub-tabs a, .rank-tier, .profile-hub-card, .admin-nav button, .admin-circle, .reaction, .drawer-link';
  document.addEventListener('pointerdown', event => {
    if (!motion() || event.button !== 0) return;
    const target = event.target.closest(pressables);
    if (!target || target.disabled || target.closest('.arena-dock,.arena-mini-nav')) return;
    target.classList.add('sf-pressable');
    const r = target.getBoundingClientRect();
    const ripple = document.createElement('span');
    ripple.className = 'sf-ripple';
    ripple.style.setProperty('--sf-ripple-size', `${Math.hypot(r.width,r.height) * 2}px`);
    ripple.style.left = `${event.clientX - r.left}px`;
    ripple.style.top = `${event.clientY - r.top}px`;
    target.append(ripple);
    window.setTimeout(() => ripple.remove(), 700);
  }, {passive: true});

  document.addEventListener('click', event => {
    if (!motion()) return;
    if (event.target.closest('[data-admin-nav]')) {
      window.requestAnimationFrame(() => {
        const active = document.querySelector('.admin-view:not([hidden])');
        if (!active) return;
        active.classList.remove('sf-view-switch');
        void active.offsetWidth;
        active.classList.add('sf-view-switch');
        window.setTimeout(() => active.classList.remove('sf-view-switch'), 430);
      });
    }
    if (event.target.closest('[data-feed-filter]')) {
      window.requestAnimationFrame(() => {
        document.querySelectorAll('[data-feed-post]:not([hidden]), [data-activity-strip]:not([hidden])').forEach((item, index) => {
          item.classList.remove('sf-filter-in');
          item.style.animationDelay = `${Math.min(index,4)*40}ms`;
          void item.offsetWidth;
          item.classList.add('sf-filter-in');
          window.setTimeout(() => {item.classList.remove('sf-filter-in');item.style.animationDelay = '';}, 550);
        });
      });
    }
  });

  if (fine.matches && motion()) {
    document.querySelectorAll('.metric-card, .rank-tier, .profile-hub-card, .admin-metric').forEach(card => {
      card.classList.add('sf-tilt');
      card.addEventListener('pointermove', event => {
        const r = card.getBoundingClientRect();
        card.style.setProperty('--sf-rot-x', `${((event.clientY - r.top) / r.height - .5) * -7}deg`);
        card.style.setProperty('--sf-rot-y', `${((event.clientX - r.left) / r.width - .5) * 7}deg`);
      }, {passive: true});
      card.addEventListener('pointerleave', () => {
        card.style.removeProperty('--sf-rot-x');
        card.style.removeProperty('--sf-rot-y');
      });
    });
  }

  if ('IntersectionObserver' in window && motion() && !body.classList.contains('sf-arena')) {
    const observed = new WeakSet();
    const observer = new IntersectionObserver(entries => {
      for (const entry of entries) {
        if (!entry.isIntersecting) continue;
        entry.target.classList.remove('sf-waiting');
        entry.target.classList.add('sf-arrived');
        observer.unobserve(entry.target);
      }
    }, {threshold: .06, rootMargin: '0px 0px 30px 0px'});
    const selectors = '.page-content>.post-card, .page-content>.panel, .page-content>.empty-state, .page-content>.notification-list, .page-content>.profile-hub-grid, .community-shell .panel, .metric-card, .rank-tier, .notification-card, .profile-hub-card, .admin-panel, .admin-metric, .admin-view .panel';
    function scan() {
      document.querySelectorAll(selectors).forEach((element, i) => {
        if (observed.has(element) || element.closest('[hidden]')) return;
        observed.add(element);
        element.classList.add('sf-reveal-item');
        element.style.setProperty('--sf-stagger', `${(i % 5) * 45}ms`);
        if (element.getBoundingClientRect().top < window.innerHeight - 45) {
          element.classList.add('sf-arrived');
        } else {
          element.classList.add('sf-waiting');
          observer.observe(element);
        }
      });
    }
    scan();
    document.querySelectorAll('.admin-view').forEach(view => new MutationObserver(scan).observe(view, {attributes: true, attributeFilter:['hidden']}));
  }
  reduced.addEventListener('change', () => {
    if (!reduced.matches) return;
    document.querySelector('.sf-intro')?.remove();
    body.classList.add('sf-entered');
    document.querySelectorAll('.sf-waiting').forEach(el => { el.classList.remove('sf-waiting'); el.classList.add('sf-arrived'); });
  });
})();
