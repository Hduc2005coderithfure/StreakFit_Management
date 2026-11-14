/* Local clock only. No presence tracking or network requests. */
(() => {
  'use strict';
  const times = [...document.querySelectorAll('[data-sf41-time]')];
  const dates = [...document.querySelectorAll('[data-sf41-date]')];
  if (!times.length && !dates.length) return;
  const tf = new Intl.DateTimeFormat('vi-VN',{hour:'2-digit',minute:'2-digit',hourCycle:'h23'});
  const df = new Intl.DateTimeFormat('vi-VN',{day:'2-digit',month:'2-digit',year:'numeric'});
  let timer;
  function clock(){const now=new Date();times.forEach(e=>{e.textContent=tf.format(now);e.dateTime=now.toISOString();});dates.forEach(e=>e.textContent=df.format(now));}
  function sync(){clearInterval(timer);if(!document.hidden){clock();timer=setInterval(clock,15000);}}
  document.addEventListener('visibilitychange',sync);window.addEventListener('pageshow',sync);window.addEventListener('pagehide',()=>clearInterval(timer));sync();
})();
