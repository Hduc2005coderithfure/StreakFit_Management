(() => {
  'use strict';
  const form = document.querySelector('[data-sf32-plan]');
  if (form) {
    const root = form.closest('#schedule');
    const days = [...form.querySelectorAll('[data-sf32-day]')];
    const rows = [...form.querySelectorAll('[data-sf32-row]')];
    const times = rows.map(row => row.querySelector('input[type=time]'));
    const common = form.querySelector('#sf32-common');
    const target = form.querySelector('#sf32-target');
    const count = root.querySelector('[data-sf32-count]');
    const dial = root.querySelector('[data-sf32-dial]');
    const status = form.querySelector('[data-sf32-status]');
    const note = form.querySelector('[data-sf32-goal-note]');
    const empty = form.querySelector('[data-sf32-empty]');
    const apply = form.querySelector('[data-sf32-apply]');
    const memory = times.map(time => time.value);
    let manualGoal = false;
    const reduced = matchMedia('(prefers-reduced-motion: reduce)');
    const valid = value => /^([01]\d|2[0-3]):[0-5]\d$/.test(value);
    const pulse = el => {
      if (!reduced.matches && el.animate) el.animate([{transform:'scale(.96)'},{transform:'scale(1.045)'},{transform:'scale(1)'}], {duration:320,easing:'cubic-bezier(.2,.8,.2,1)'});
    };
    function render(syncGoal = false) {
      const selected = days.filter(day => day.checked).length;
      if (syncGoal && !manualGoal && selected) target.value = String(selected);
      days.forEach((day, i) => {
        const active = day.checked;
        day.closest('.sf32-day').classList.toggle('is-selected', active);
        rows[i].hidden = !active;
        times[i].disabled = !active;
        times[i].required = active;
        day.closest('.sf32-day').querySelector('[data-sf32-day-time]').textContent = active ? (times[i].value || 'Chọn giờ') : 'Nghỉ';
      });
      count.textContent = selected;
      dial.style.setProperty('--fill', `${selected / 7 * 100}%`);
      empty.hidden = selected !== 0;
      apply.disabled = selected === 0;
      form.querySelectorAll('[data-sf32-preset]').forEach(b => b.disabled = selected === 0);
      status.textContent = `${selected} ngày đăng ký · Mục tiêu ${target.value} buổi`;
      note.textContent = selected === Number(target.value) ? 'Mục tiêu khớp với số ngày đã chọn.' : 'Mục tiêu dùng để tính điểm, có thể khác số ngày đăng ký.';
    }
    form.querySelectorAll('[data-sf32-enhanced]').forEach(el => el.hidden = false);
    days.forEach((day, i) => day.addEventListener('change', () => {
      if (day.checked) {
        times[i].value = memory[i] || (valid(common.value) ? common.value : '17:00');
      } else { memory[i] = times[i].value; }
      render(true); pulse(day.closest('.sf32-day')); pulse(dial);
    }));
    times.forEach((time,i) => time.addEventListener('input', () => {memory[i] = time.value; render();}));
    target.addEventListener('change', () => {manualGoal = true; render();});
    function applyTime() {
      if (!valid(common.value)) {common.reportValidity(); common.focus(); return;}
      days.forEach((day,i) => {if (day.checked) {times[i].value = memory[i] = common.value;}});
      render(); pulse(apply);
    }
    apply.addEventListener('click', applyTime);
    form.querySelectorAll('[data-sf32-preset]').forEach(button => button.addEventListener('click', () => {common.value = button.dataset.sf32Preset;applyTime();pulse(button);}));
    form.querySelector('[data-sf32-clear]').addEventListener('click', () => {days.forEach((day,i)=>{memory[i]=times[i].value;day.checked=false;});render(true);});
    form.querySelector('[data-sf32-copy]').addEventListener('click', () => {
      rows.forEach((row,i)=>{const value=row.dataset.copy;days[i].checked=valid(value);if(days[i].checked)times[i].value=memory[i]=value;});
      render(true);pulse(dial);
    });
    form.addEventListener('submit', event => {
      if (!form.checkValidity()) {event.preventDefault();form.reportValidity();return;}
      const button = form.querySelector('button[type=submit]');
      if (button.dataset.saving) {event.preventDefault();return;}
      button.dataset.saving = '1';button.querySelector('span').textContent='ĐANG LƯU LỊCH…';
      button.setAttribute('aria-busy','true');
    });
    window.addEventListener('pageshow',()=>{const b=form.querySelector('button[type=submit]');delete b.dataset.saving;b.removeAttribute('aria-busy');b.querySelector('span').textContent='LƯU LỊCH TUẦN TỚI';render();});
    render();
  }
  // Local pointer lighting, never intercepts navigation or form submission.
  if (matchMedia('(hover: hover) and (pointer: fine)').matches && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
    document.querySelectorAll('.arena-metrics>div,.profile-hub-card,.sf32-day,.leader-row,.post-card,.sf32-time-row').forEach(el=>{
      el.addEventListener('pointermove',e=>{const r=el.getBoundingClientRect();el.style.setProperty('--sf32-x',`${e.clientX-r.left}px`);el.style.setProperty('--sf32-y',`${e.clientY-r.top}px`);},{passive:true});
    });
  }
})();
