(() => {
  'use strict';
  // Progressive enhancement only. Native POST and redirect remain available without JS.
  if (!window.fetch || !window.FormData) return;
  const busy = new WeakSet();
  document.addEventListener('submit', async event => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.closest('.reaction-buttons')) return;
    const article = form.closest('[data-sf33-reaction-api]');
    if (!article) return;
    // Stop older scroll/pending-tab listeners from recording a navigation that will not happen.
    event.preventDefault(); event.stopImmediatePropagation();
    if (busy.has(article)) return;
    busy.add(article);
    const group = article.querySelector('.reaction-buttons');
    const buttons = [...group.querySelectorAll('button')];
    const status = article.querySelector('[data-sf33-post-status]');
    const data = new FormData(form);
    const payload = new URLSearchParams();
    for (const [key, value] of data) if (typeof value === 'string') payload.append(key, value);
    group.setAttribute('aria-busy', 'true'); buttons.forEach(button => button.disabled = true);
    status.hidden = true;
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 12000);
    try {
      const response = await fetch(article.dataset.sf33ReactionApi, {method:'POST', credentials:'same-origin', headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},body:payload,signal:controller.signal});
      const result = await response.json();
      if (!response.ok || !result.ok) throw new Error(result.message || 'Không thể cập nhật cảm xúc.');
      if (result.reaction !== null && !['heart','muscle','fire','sad','haha'].includes(result.reaction)) throw new Error('Dữ liệu phản hồi không hợp lệ.');
      const count = Number(result.reaction_count);
      if (!Number.isInteger(count) || count < 0) throw new Error('Đã gửi thao tác. Tải lại để đồng bộ số cảm xúc.');
      buttons.forEach(button => {
        const selected = button.form.elements.namedItem('reaction').value === result.reaction;
        button.classList.toggle('selected', selected); button.setAttribute('aria-pressed', String(selected));
      });
      article.querySelector('[data-sf33-reaction-count]').textContent = String(count);
      status.textContent = result.reaction === null ? 'Đã bỏ cảm xúc.' : 'Đã gửi cảm xúc.';
      status.classList.remove('is-error'); status.hidden = false;
    } catch (error) {
      status.textContent = (error instanceof TypeError || error.name === 'AbortError') ? 'Mất kết nối. Tải lại trang để kiểm tra trạng thái trước khi gửi lại.' : (error.message || 'Không thể cập nhật cảm xúc.');
      status.classList.add('is-error'); status.hidden = false;
    } finally {
      clearTimeout(timeout); busy.delete(article); group.removeAttribute('aria-busy'); buttons.forEach(button => button.disabled = false);
    }
  }, true);
})();
