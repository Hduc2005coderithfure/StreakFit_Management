(() => {
  'use strict';
  const engine=window.StreakArena3D, reduced=matchMedia('(prefers-reduced-motion: reduce)');
  // Local pages already exist in the PHP document. Switch visibility, not the DOM or server data.
  // The URL remains shareable; native links are the fallback when JS is disabled.
  const localNav = document.querySelector('[data-arena-local-tabs]');
  const body = document.body;
  if (localNav) {
    const links = [...localNav.querySelectorAll('[data-arena-tab]')];
    const panels = [...document.querySelectorAll('.page-content > [data-arena-panel]')];
    const track = localNav.querySelector('.arena-mini-track');
    const indicator = document.createElement('span');
    indicator.className = 'arena-mini-indicator';
    indicator.setAttribute('aria-hidden', 'true');
    track.prepend(indicator);
    const defaultView = body.dataset.arenaView;
    const baseView = body.dataset.tab === 'feed' ? 'overview' : (location.pathname.endsWith('/court') ? 'court' : (new URL(location.href).searchParams.has('period') ? 'board' : 'rank'));
    const pendingKey = 'sf-arena-v31-return:' + (document.getElementById('arenaEntry')?.dataset.session || '') + ':' + body.dataset.tab;
    let animations = [];
    function moveIndicator(active) {
      if (!active) return;
      indicator.style.width = active.offsetWidth + 'px';
      indicator.style.height = active.offsetHeight + 'px';
      indicator.style.transform = `translate(${active.offsetLeft}px, ${active.offsetTop}px)`;
      indicator.style.opacity = '1';
    }
    function selectView(view, options = {}) {
      const active = links.find(a => a.dataset.arenaTab === view);
      if (!active) return false;
      const changed = view !== body.dataset.arenaView;
      animations.forEach(animation => animation.cancel()); animations = [];
      body.dataset.arenaView = view;
      panels.forEach(p => p.toggleAttribute('data-sf45-tab-hidden', p.dataset.arenaPanel !== view));
      if (body.dataset.tab === 'feed') {
        document.querySelectorAll('.arena-dock [data-sf33-view], .sf39-sidebar [data-sf33-view]').forEach(a => {
          const selected = a.dataset.sf33View === view || (a.closest('.arena-dock') && view === 'insights' && a.dataset.sf33View === 'overview');
          a.classList.toggle('active', selected); if(a.classList.contains('sf39-nav')) a.classList.toggle('is-current',selected);
          if (selected) a.setAttribute('aria-current', 'page'); else a.removeAttribute('aria-current');
        });
        document.title = 'StreakFit · ' + ({community:'Cộng đồng',insights:'Thống kê',overview:'Tổng quan'}[view] || 'Tổng quan');
      }
      if(body.dataset.tab === 'leaderboard') document.querySelectorAll('[data-sf39-leader-view]').forEach(a=>{const selected=a.dataset.sf39LeaderView==='court'?view==='court':view!=='court';a.classList.toggle('is-current',selected);if(selected)a.setAttribute('aria-current','page');else a.removeAttribute('aria-current');});
      const workspaceTitle=document.querySelector('[data-sf39-title]'); if(workspaceTitle) workspaceTitle.textContent=({overview:'Tổng quan',community:'Cộng đồng',insights:'Thống kê',rank:'Huy hiệu & Thăng hạng',board:'Bảng vàng',court:'Nghỉ tập & giải trình'})[view] || workspaceTitle.textContent;
      document.dispatchEvent(new CustomEvent('sf:arena-view', {detail:{view}}));
      const title = document.querySelector('[data-arena-view-title]');
      if (title) title.textContent = ({rank:'Huy hiệu & Thăng hạng',board:'Bảng vàng',court:'Nghỉ tập & giải trình'})[view] || title.textContent;
      links.forEach(a => {
        const selected = a === active;
        a.classList.toggle('is-active', selected);
        if (selected) a.setAttribute('aria-current', 'page'); else a.removeAttribute('aria-current');
      });
      moveIndicator(active);
      if (changed) {
        const left = active.offsetLeft, right = left + active.offsetWidth;
        if (left < track.scrollLeft) track.scrollLeft = left;
        else if (right > track.scrollLeft + track.clientWidth) track.scrollLeft = right - track.clientWidth;
      }
      if (options.url) {
        try { history[options.replace ? 'replaceState' : 'pushState'](history.state, '', active.href); } catch (_) { location.assign(active.href); }
      }
      if (changed && options.animate && !reduced.matches && typeof Element.prototype.animate === 'function') {
        panels.filter(p => p.dataset.arenaPanel === view && !p.hidden).slice(0, 8).forEach((p, i) => {
          animations.push(p.animate([{opacity: .70, transform: 'translateY(7px)'}, {opacity: 1, transform: 'translateY(0)'}], {duration: 235, delay: Math.min(i, 3)*16, easing: 'cubic-bezier(.2,.8,.2,1)'}));
        });
      }
      return true;
    }
    let restored = null;
    try {
      const pending = JSON.parse(sessionStorage.getItem(pendingKey) || 'null');
      sessionStorage.removeItem(pendingKey);
      if (!new URL(location.href).searchParams.has('view') && pending && Date.now() - pending.at < 120000 && links.some(a => a.dataset.arenaTab === pending.view)) restored = pending.view;
    } catch (_) {}
    selectView(restored || defaultView, {url: !!restored, replace: true});
    requestAnimationFrame(() => indicator.classList.add('is-ready'));
    links.forEach(link => link.addEventListener('click', event => {
      if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
      event.preventDefault();
      if (link.dataset.arenaTab === body.dataset.arenaView) return;
      selectView(link.dataset.arenaTab, {animate: true, url: true});
    }));
    document.querySelectorAll('[data-sf33-view]').forEach(link => link.addEventListener('click', event => {
      if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || !links.some(a => a.dataset.arenaTab === link.dataset.sf33View)) return;
      event.preventDefault();
      if (link.dataset.sf33View !== body.dataset.arenaView) {
        selectView(link.dataset.sf33View, {animate:true,url:true});
        window.scrollTo({top:0,behavior:'instant'});
      }
    }));
    document.querySelectorAll('[data-sf39-leader-view]').forEach(link => link.addEventListener('click', event => {
      const view=link.dataset.sf39LeaderView;
      if(event.defaultPrevented||event.button!==0||event.metaKey||event.ctrlKey||event.shiftKey||event.altKey||!links.some(a=>a.dataset.arenaTab===view))return;
      event.preventDefault();if(view!==body.dataset.arenaView){selectView(view,{animate:true,url:true});window.scrollTo({top:0,behavior:'instant'});}
    }));
    window.addEventListener('popstate', () => {
      const view = new URL(location.href).searchParams.get('view');
      selectView(links.some(a => a.dataset.arenaTab === view) ? view : baseView, {animate:true});
    });
    if ('ResizeObserver' in window) new ResizeObserver(() => moveIndicator(links.find(a => a.classList.contains('is-active')))).observe(track);
    window.addEventListener('pageshow', () => moveIndicator(links.find(a => a.classList.contains('is-active'))));
    // PHP redirects reactions/comments and appeal votes to the base route. Restore the current tab once.
    document.addEventListener('submit', event => {
      if (!(event.target instanceof HTMLFormElement) || event.target.method.toLowerCase() !== 'post') return;
      const group = event.target.closest('[data-arena-panel]');
      if (!group) return;
      try { sessionStorage.setItem(pendingKey, JSON.stringify({view: group.dataset.arenaPanel, at: Date.now()})); } catch (_) {}
    });
  }
  // Keep the current personal section in view without scrolling the document.
  document.querySelectorAll('.arena-profile-tabs .arena-mini-track').forEach(track => {
    const active = track.querySelector('[aria-current="page"]');
    if (active) track.scrollLeft = Math.max(0, active.offsetLeft - (track.clientWidth - active.offsetWidth) / 2);
  });
  if (matchMedia('(hover: hover) and (pointer: fine)').matches && !reduced.matches) {
    document.querySelectorAll('.profile-hub-card,.arena-mini-tab,.arena-dock .nav-item').forEach(el => {
      el.addEventListener('pointermove', e => {
        const bounds = el.getBoundingClientRect();
        el.style.setProperty('--sf-x', `${e.clientX - bounds.left}px`);
        el.style.setProperty('--sf-y', `${e.clientY - bounds.top}px`);
      }, {passive:true});
    });
  }
  const authCanvas=document.querySelector('[data-arena-auth]');
  if(authCanvas&&engine){const authScene=engine.mount(authCanvas,'entry',{slug:'chien-tuong'});if(authScene){authCanvas.parentElement.classList.add('arena-webgl');authCanvas.parentElement.dataset.renderer=authScene.backend;authCanvas.addEventListener('arena-context-lost',()=>{authScene.dispose();authCanvas.parentElement.classList.remove('arena-webgl');});}}
  const dialog=document.getElementById('arenaEntry');
  let entryScene=null, countFrame=0, lastFocus=null;
  function stopEntry(){if(entryScene){entryScene.dispose();entryScene=null;}cancelAnimationFrame(countFrame);dialog?.classList.remove('arena-webgl');document.body.classList.remove('arena-modal-open');if(lastFocus?.isConnected)lastFocus.focus({preventScroll:true});}
  function openEntry(force=false){if(!dialog||typeof dialog.showModal!=='function'||dialog.open)return;const key='sf-arena-v3:'+dialog.dataset.session;if(!force){try{if(sessionStorage.getItem(key))return;}catch(_){return;}if(reduced.matches||document.body.dataset.tab!=='feed'||document.body.dataset.arenaView!=='overview')return;}
    try{sessionStorage.setItem(key,'1');}catch(_){}lastFocus=document.activeElement;dialog.showModal();document.body.classList.add('arena-modal-open');dialog.classList.remove('arena-playing');void dialog.offsetWidth;dialog.classList.add('arena-playing');
    const canvas=dialog.querySelector('[data-arena-scene]');if(engine){entryScene=engine.mount(canvas,'entry',{slug:dialog.dataset.rank});if(entryScene){dialog.classList.add('arena-webgl');dialog.dataset.renderer=entryScene.backend;}}
    const counters=[...dialog.querySelectorAll('[data-count]')];if(reduced.matches){counters.forEach(e=>e.textContent=e.dataset.count);return;}const start=performance.now();function tick(t){const p=Math.min(1,Math.max(0,(t-start-650)/1550));counters.forEach(e=>e.textContent=Math.round(Number(e.dataset.count)*(1-Math.pow(1-p,3))).toLocaleString('vi-VN'));if(p<1)countFrame=requestAnimationFrame(tick);}countFrame=requestAnimationFrame(tick);
  }
  dialog?.querySelectorAll('[data-arena-close]').forEach(b=>b.addEventListener('click',()=>dialog.close()));dialog?.addEventListener('close',stopEntry);dialog?.querySelector('canvas')?.addEventListener('arena-context-lost',()=>{dialog.classList.remove('arena-webgl');entryScene?.dispose();entryScene=null;});
  document.querySelectorAll('[data-arena-replay]').forEach(b=>b.addEventListener('click',()=>{const drawer=document.querySelector('#accountDrawer:not([hidden])');if(drawer)drawer.querySelector('[data-drawer-close]')?.click();openEntry(true);}));
  if(dialog&&document.body.dataset.tab==='feed')openEntry();
  const core=document.querySelector('[data-core-progress]');if(core&&engine){const canvas=core.querySelector('canvas');const scene=engine.mount(canvas,'core',{progress:Number(core.dataset.coreProgress),slug:core.dataset.coreRank||'dong',goal:Number(core.dataset.coreGoal||0),done:Number(core.dataset.coreDone||0)});if(scene){core.classList.add('arena-webgl');core.dataset.renderer=scene.backend;canvas.addEventListener('arena-context-lost',()=>{scene.dispose();core.classList.remove('arena-webgl');});}}
  document.querySelectorAll('[data-rank-cinema]').forEach(stage=>{
    const surface=stage.querySelector('[data-rank-surface]'),canvas=stage.querySelector('[data-rank-canvas]'),fallback=stage.querySelector('.rank-cinema-fallback');if(!surface||!canvas||!fallback)return;
    const buttons=[...document.querySelectorAll('[data-rank-select]')];let scene=engine?.mount(canvas,'rank',{slug:stage.dataset.rankSlug})||null;if(scene){stage.classList.add('has-webgl');stage.dataset.renderer=scene.backend;}
    function select(button){if(!button)return;buttons.forEach(b=>{const active=b===button;b.classList.toggle('is-preview',active);b.setAttribute('aria-pressed',String(active));});const d=button.dataset;stage.querySelector('[data-rank-title]').textContent=d.rankName;stage.querySelector('[data-rank-points]').textContent=Number(d.rankMin).toLocaleString('vi-VN')+'+ điểm tích lũy';fallback.src=d.rankImage;const portrait=stage.querySelector('[data-sf43-portrait]');if(portrait)portrait.src=d.rankImage;const state=stage.querySelector('[data-sf43-preview-state]');if(state)state.textContent=button.classList.contains('is-current')?'HẠNG HIỆN TẠI':(button.querySelector('.arena-tier-status')?.textContent.trim()||'ĐANG XEM');if(d.rankScene)stage.style.setProperty('--sf43-scene',`url("${d.rankScene}")`);stage.dataset.previewSlug=d.rankSlug;stage.style.setProperty('--cinema-accent',engine?.palette[d.rankSlug]?.[1]||'#ffb56d');scene?.select(d.rankSlug);stage.classList.remove('arena-rank-reveal');void stage.offsetWidth;stage.classList.add('arena-rank-reveal');}
    buttons.forEach(b=>{b.addEventListener('click',()=>select(b));if(b.closest('.sf43-showcase')&&matchMedia('(hover:hover) and (pointer:fine)').matches&&!matchMedia('(prefers-reduced-motion: reduce)').matches){b.addEventListener('pointermove',e=>{const rect=b.getBoundingClientRect();b.style.setProperty('--sf43-px',`${((e.clientX-rect.left)/rect.width-.5)*12}px`);b.style.setProperty('--sf43-py',`${((e.clientY-rect.top)/rect.height-.5)*12}px`);});b.addEventListener('pointerleave',()=>{b.style.removeProperty('--sf43-px');b.style.removeProperty('--sf43-py');});}});if(buttons.length){const requested=new URL(location.href).searchParams.get('inspect');select(buttons.find(b=>b.dataset.rankSlug===requested)||buttons.find(b=>b.classList.contains('is-current'))||buttons[0]);}else stage.style.setProperty('--cinema-accent',engine?.palette[stage.dataset.rankSlug]?.[1]||'#ffb56d');
    let pointer=null;surface.addEventListener('pointerdown',e=>{if(e.button!==0||!scene)return;pointer={id:e.pointerId,x:e.clientX,y:e.clientY};surface.setPointerCapture(e.pointerId);});surface.addEventListener('pointermove',e=>{if(!pointer||e.pointerId!==pointer.id)return;scene?.rotate((e.clientX-pointer.x)*.012,(e.clientY-pointer.y)*.009);pointer.x=e.clientX;pointer.y=e.clientY;});['pointerup','pointercancel','lostpointercapture'].forEach(t=>surface.addEventListener(t,()=>{pointer=null;}));
    surface.addEventListener('keydown',e=>{const directions={ArrowLeft:[-.22,0],ArrowRight:[.22,0],ArrowUp:[0,-.18],ArrowDown:[0,.18]};if(scene&&directions[e.key]){e.preventDefault();scene.rotate(...directions[e.key]);}});
    stage.querySelectorAll('[data-rank-step]').forEach(b=>b.addEventListener('click',()=>{const i=buttons.findIndex(item=>item.getAttribute('aria-pressed')==='true');if(buttons.length)select(buttons[(i+Number(b.dataset.rankStep)+buttons.length)%buttons.length]);}));
    stage.querySelector('[data-rank-reset]')?.addEventListener('click',()=>scene?.reset());stage.querySelectorAll('[data-rank-zoom]').forEach(b=>b.addEventListener('click',()=>scene?.zoom(Number(b.dataset.rankZoom)*.16)));canvas.addEventListener('arena-context-lost',()=>{scene?.dispose();scene=null;stage.classList.remove('has-webgl');});
  });
})();
