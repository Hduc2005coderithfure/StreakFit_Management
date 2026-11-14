(() => {
  'use strict';
  const capture = document.getElementById('sf38Capture');
  const entry = document.getElementById('arenaEntry');
  const reduce = matchMedia('(prefers-reduced-motion: reduce)');
  function revealCapture() {
    if (!capture || document.body.dataset.arenaView !== 'overview') return false;
    capture.open = true;
    requestAnimationFrame(() => { capture.scrollIntoView({block:'start',behavior:reduce.matches?'auto':'smooth'}); capture.querySelector('[data-camera-start]')?.focus({preventScroll:true}); });
    return true;
  }
  document.querySelector('[data-sf38-open]')?.addEventListener('click', e => { if (revealCapture()) e.preventDefault(); });
  document.querySelector('[data-sf38-entry-workout]')?.addEventListener('click', e => {
    if (!capture || document.body.dataset.arenaView !== 'overview') return;
    e.preventDefault(); if(entry?.open) entry.close(); revealCapture();
  });
  capture?.addEventListener('toggle', () => { if(!capture.open) document.dispatchEvent(new Event('sf:camera-stop')); });
  document.addEventListener('sf:arena-view', e => { if(e.detail?.view !== 'overview' && capture) { capture.open=false; document.dispatchEvent(new Event('sf:camera-stop')); } });
  const clock = document.querySelector('[data-sf38-clock]');
  if(clock) { const tick=()=>{const t=Math.max(0,Math.floor(Date.now()/1000)-Number(clock.dataset.start));clock.textContent=[Math.floor(t/3600),Math.floor(t%3600/60),t%60].map(x=>String(x).padStart(2,'0')).join(':');};tick();setInterval(()=>{if(!document.hidden)tick();},1000);document.addEventListener('visibilitychange',tick); }
  document.querySelectorAll('.sf38-health').forEach(card => {
    const buttons=[...card.querySelectorAll('[data-sf38-chart]')], plots=[...card.querySelectorAll('[data-sf38-plot]')];
    buttons.forEach(b=>b.addEventListener('click',()=>{buttons.forEach(x=>x.setAttribute('aria-pressed',String(x===b)));plots.forEach(p=>p.hidden=p.dataset.sf38Plot!==b.dataset.sf38Chart);}));
  });
  if(typeof HTMLDialogElement !== 'undefined' && typeof HTMLDialogElement.prototype.showModal==='function') {
    const dialog=document.createElement('dialog');dialog.className='sf38-photo-dialog';dialog.setAttribute('aria-label','Ảnh buổi tập');
    const close=document.createElement('button');close.type='button';close.textContent='Đóng ×';const img=document.createElement('img');dialog.append(close,img);document.body.append(dialog);
    close.addEventListener('click',()=>dialog.close());dialog.addEventListener('click',e=>{if(e.target===dialog)dialog.close();});dialog.addEventListener('close',()=>{img.removeAttribute('src');});
    document.querySelectorAll('[data-sf38-photo]').forEach(a=>a.addEventListener('click',e=>{if(e.ctrlKey||e.metaKey||e.shiftKey||e.altKey)return;e.preventDefault();img.alt=a.querySelector('img')?.alt||'Ảnh buổi tập';img.src=a.href;dialog.showModal();close.focus();}));
  }
})();
