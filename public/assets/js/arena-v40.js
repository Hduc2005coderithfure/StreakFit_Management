(() => {
  'use strict';
  const sidebar=document.querySelector('.sf40-sidebar');
  if(!sidebar)return;
  const body=document.body, toggle=sidebar.querySelector('.sf40-collapse'), menu=sidebar.querySelector('.sf39-menu');
  const motion=matchMedia('(prefers-reduced-motion: reduce)'), desktop=matchMedia('(min-width:1100px)');
  const key='streakfit.workspace.compact.v40';
  const setCompact=(compact,save=false)=>{
    body.classList.toggle('sf40-compact',compact);
    if(toggle){toggle.setAttribute('aria-expanded',String(!compact));toggle.setAttribute('aria-label',compact?'Mở rộng thanh điều hướng':'Thu gọn thanh điều hướng');toggle.title=compact?'Mở rộng thanh điều hướng':'Thu gọn thanh điều hướng';const label=toggle.querySelector('span');if(label)label.textContent=compact?'Mở rộng':'Thu gọn';}
    if(save)try{localStorage.setItem(key,compact?'1':'0');}catch(_){}
  };
  try{setCompact(localStorage.getItem(key)==='1');}catch(_){setCompact(false);}
  toggle?.addEventListener('click',()=>setCompact(!body.classList.contains('sf40-compact'),true));
  const links=[...sidebar.querySelectorAll('.sf39-nav')];
  let lastActive=null;
  function syncActive(animate=false){
    const active=sidebar.querySelector('[aria-current="page"]');
    if(!active||active===lastActive)return;
    if(animate&&!motion.matches&&desktop.matches&&typeof active.animate==='function'){
      const icon=active.querySelector('.sf40-nav-icon');
      icon?.animate([{transform:'scale(.85) rotate(-9deg)'},{transform:'scale(1.14) rotate(3deg)',offset:.6},{transform:'scale(1.04) rotate(0deg)'}],{duration:390,easing:'cubic-bezier(.2,.8,.25,1)'});
    }
    if(menu&&desktop.matches){const a=active.getBoundingClientRect(),m=menu.getBoundingClientRect();if(a.top<m.top)menu.scrollTop+=a.top-m.top-5;else if(a.bottom>m.bottom)menu.scrollTop+=a.bottom-m.bottom+5;}
    lastActive=active;
  }
  links.forEach(a=>{
    a.addEventListener('pointermove',e=>{if(motion.matches||e.pointerType==='touch')return;const r=a.getBoundingClientRect();a.style.setProperty('--sf40-mx',`${e.clientX-r.left}px`);a.style.setProperty('--sf40-my',`${e.clientY-r.top}px`);},{passive:true});
  });
  document.addEventListener('sf:arena-view',()=>syncActive(true));
  window.addEventListener('pageshow',()=>syncActive());
  desktop.addEventListener?.('change',()=>{lastActive=null;syncActive();});
  syncActive();
})();
