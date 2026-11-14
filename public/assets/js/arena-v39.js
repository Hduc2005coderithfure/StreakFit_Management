(() => {
  'use strict';
  // The workspace uses native links for distinct pages, existing local tab engine for feed.
  const capture=document.getElementById('sf38Capture');
  if(capture){
    capture.classList.add('sf39-enhanced');
    const summary=capture.querySelector('summary');
    const sync=()=>{if(summary)summary.textContent=capture.open?'Đóng camera':'Camera ghi nhận buổi tập';};
    capture.addEventListener('toggle',sync);sync();
  }
  const sidebar=document.querySelector('.sf39-sidebar');
  if(!sidebar)return;
  const update=()=>{
    const active=sidebar.querySelector('[aria-current="page"]');
    if(active && matchMedia('(min-width:1100px)').matches){
      const menu=sidebar.querySelector('.sf39-menu');
      if(menu && active.offsetTop<menu.scrollTop) menu.scrollTop=active.offsetTop;
    }
  };
  document.addEventListener('sf:arena-view',update);
  window.addEventListener('pageshow',update);
  // Handle rank and leave views through their existing local tabs where available.
  sidebar.querySelectorAll('[data-sf39-leader-view]').forEach(a=>a.addEventListener('click',e=>{
    if(e.button!==0||e.metaKey||e.ctrlKey||e.shiftKey||e.altKey)return;
    const tab=document.querySelector('[data-arena-tab="'+a.dataset.sf39LeaderView+'"]');
    if(tab){e.preventDefault();tab.click();window.scrollTo({top:0,behavior:'instant'});}
  }));
})();
