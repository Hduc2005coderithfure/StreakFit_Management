/* Progressive visual effects; no account, camera or form logic lives here. */
(() => {
  'use strict';
  const body = document.body;
  if (!body || !body.classList.contains('sf-future')) return;
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)');

  if (finePointer.matches && !reduceMotion.matches) {
    document.addEventListener('pointermove', event => {
      const card = event.target.closest('.panel, .post-card, .metric-card, .checkin-card, .notification-card, .rank-tier, .auth-card, .admin-panel, .admin-metric');
      if (!card) return;
      const bounds = card.getBoundingClientRect();
      card.style.setProperty('--sf-x', `${event.clientX - bounds.left}px`);
      card.style.setProperty('--sf-y', `${event.clientY - bounds.top}px`);
    }, {passive: true});
  }

  if (reduceMotion.matches || !window.requestAnimationFrame) return;
  const canvas = document.createElement('canvas');
  canvas.className = 'sf-ambient-canvas';
  canvas.setAttribute('aria-hidden', 'true');
  body.prepend(canvas);
  const ctx = canvas.getContext('2d', {alpha: true});
  if (!ctx) { canvas.remove(); return; }
  let width = 0, height = 0, particles = [], frame = 0, running = false;
  let lastPaint = 0;
  function resize() {
    width = window.innerWidth;
    height = window.innerHeight;
    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    canvas.width = Math.round(width * dpr);
    canvas.height = Math.round(height * dpr);
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    const count = Math.min(width < 700 ? 18 : 42, Math.floor(width * height / 22000));
    particles = Array.from({length: count}, (_, index) => ({
      x: Math.random() * width,
      y: Math.random() * height,
      size: index % 7 === 0 ? 2 : 1,
      speed: 0.1 + Math.random() * 0.28,
      sway: Math.random() * Math.PI * 2,
      tint: index % 5 === 0 ? '255,163,114' : '115,215,255'
    }));
  }
  function draw(time) {
    if (!running) return;
    frame = window.requestAnimationFrame(draw);
    if (time - lastPaint < 38) return; // Cap the ambient effect near 25 fps.
    lastPaint = time;
    ctx.clearRect(0, 0, width, height);
    for (const particle of particles) {
      particle.y -= particle.speed;
      if (particle.y < -5) { particle.y = height + 5; particle.x = Math.random() * width; }
      const x = particle.x + Math.sin(time * .00045 + particle.sway) * 11;
      ctx.beginPath();
      ctx.fillStyle = `rgba(${particle.tint},.56)`;
      ctx.shadowColor = `rgba(${particle.tint},.7)`;
      ctx.shadowBlur = particle.size * 8;
      ctx.arc(x, particle.y, particle.size, 0, Math.PI * 2);
      ctx.fill();
    }
    ctx.shadowBlur = 0;
  }
  function updateVisibility() {
    if (document.hidden && running) {
      running = false;
      window.cancelAnimationFrame(frame);
      ctx.clearRect(0, 0, width, height);
    } else if (!document.hidden && !running && !reduceMotion.matches) {
      running = true;
      frame = window.requestAnimationFrame(draw);
    }
  }
  resize();
  updateVisibility();
  window.addEventListener('resize', resize, {passive: true});
  document.addEventListener('visibilitychange', updateVisibility);
  reduceMotion.addEventListener('change', () => {
    if (reduceMotion.matches) {
      running = false;
      window.cancelAnimationFrame(frame);
      canvas.remove();
    }
  });
})();
