// Small progressive enhancements; every page works without JavaScript.
(() => {
  // Mobile menu.
  const toggle = document.querySelector('.menu-toggle');
  const nav = document.getElementById('mainnav');
  if (toggle && nav) {
    toggle.addEventListener('click', () => {
      const open = nav.classList.toggle('open');
      toggle.setAttribute('aria-expanded', String(open));
    });
  }

  // Expand / collapse all sections or schedules.
  const rows = document.querySelectorAll('details.sec');
  document.querySelectorAll('[data-expand]').forEach((button) => {
    button.hidden = rows.length === 0;
    button.addEventListener('click', () => {
      const open = button.dataset.expand === 'all';
      rows.forEach((row) => { row.open = open; });
    });
  });

  // ".../act#section-2" opens that section.
  const openFragment = () => {
    const id = decodeURIComponent(window.location.hash.slice(1));
    const row = id ? document.getElementById(id) : null;
    if (row && row.matches('details.sec')) {
      row.open = true;
      row.scrollIntoView({ block: 'start' });
    }
  };
  openFragment();
  window.addEventListener('hashchange', openFragment);

  // PDF in several languages: the popup sits beside the clicked PDF (left of it, else right, else
  // below), stays inside the window and is not clipped by scrolling tables. One open at a time;
  // closes on a click elsewhere, Escape, scroll or resize, and after a language is picked.
  const pickers = document.querySelectorAll('details.pdf-pick');
  const closeAll = (except) => pickers.forEach((d) => { if (d !== except) d.open = false; });
  const place = (picker) => {
    const menu = picker.querySelector('.pdf-menu');
    const box = picker.querySelector('summary').getBoundingClientRect();
    menu.classList.add('placed');
    const w = menu.offsetWidth, h = menu.offsetHeight, gap = 10, vw = window.innerWidth, vh = window.innerHeight;
    let left = box.left - w - gap, top = box.top;
    if (left < 8) left = box.right + gap;
    if (left + w > vw - 8) { left = Math.max(8, Math.min(box.left, vw - w - 8)); top = box.bottom + 6; }
    top = Math.max(8, Math.min(top, vh - h - 8));
    menu.style.left = left + 'px';
    menu.style.top = top + 'px';
  };
  // With the PDF question on, the popup is a form: a new question (/pdf-question) each time it
  // opens; a language button POSTs the answer to that PDF, which opens in a new tab. A token works
  // once, so after a submit the popup closes and the next opening asks a new question.
  const ask = async (form) => {
    const box = form.querySelector('.pdf-q');
    const answer = form.elements.answer;
    answer.value = '';
    try {
      const res = await fetch('/pdf-question', { cache: 'no-store', headers: { Accept: 'application/json' } });
      if (!res.ok) throw new Error(String(res.status));
      const q = await res.json();
      form.querySelector('[data-q]').textContent = q.a + ' + ' + q.b;
      form.elements.token.value = q.token;
      answer.required = true;
      box.hidden = false;
      place(form.closest('details.pdf-pick'));
      answer.focus();
    } catch (e) {
      // No question: the buttons POST without a token and the PDF URL asks the question as a page.
      box.hidden = true;
      answer.required = false;
      form.elements.token.value = '';
    }
  };
  pickers.forEach((picker) => {
    const form = picker.querySelector('form.pdf-menu');
    picker.addEventListener('toggle', () => {
      if (!picker.open) return;
      closeAll(picker);
      place(picker);
      if (form) ask(form);
    });
    picker.querySelectorAll('.pdf-menu a').forEach((a) => a.addEventListener('click', () => { picker.open = false; }));
    if (form) form.addEventListener('submit', () => { setTimeout(() => { picker.open = false; }, 0); });
  });
  // Typing the answer (a phone keyboard scrolls and resizes the page) keeps the popup open.
  const typing = () => document.activeElement && document.activeElement.closest && document.activeElement.closest('.pdf-menu');
  const keepOrClose = () => {
    const open = [...pickers].find((d) => d.open);
    if (open && typing()) place(open); else closeAll();
  };
  if (pickers.length) {
    document.addEventListener('click', (ev) => {
      if (!ev.target.closest('details.pdf-pick')) closeAll();
    });
    document.addEventListener('keydown', (ev) => {
      if (ev.key !== 'Escape') return;
      const open = [...pickers].find((d) => d.open);
      if (open) { open.open = false; open.querySelector('summary').focus(); }
    });
    window.addEventListener('scroll', keepOrClose, { passive: true, capture: true });
    window.addEventListener('resize', keepOrClose);
  }

  // The year filter applies as soon as a year is picked.
  document.querySelectorAll('select[data-autosubmit]').forEach((select) => {
    select.addEventListener('change', () => select.form.submit());
  });
})();
