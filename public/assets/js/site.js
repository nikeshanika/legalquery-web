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
  // With the PDF question on, the popup is a form: a language button POSTs the answer to that PDF,
  // which opens in a new tab. A token works once. So that the reader never sees one question
  // replaced by another while typing, a question stays in the popup until it is used (or nearly
  // expired); the next one is fetched in the background right after a submit (and when the pointer
  // reaches the PDF), and while one is loading the popup shows "…" with the buttons disabled.
  const FRESH_MS = 8 * 60 * 1000; // tokens live 10 minutes on the server
  const fetchQuestion = () => fetch('/pdf-question', { cache: 'no-store', headers: { Accept: 'application/json' } })
    .then((res) => { if (!res.ok) throw new Error(String(res.status)); return res.json(); })
    .then((q) => ({ ...q, at: Date.now() }));
  let next = null; // a fetched, unused question (shared by every popup on the page)
  const prefetch = () => {
    if (!next) next = fetchQuestion().catch(() => { next = null; return null; });
    return next;
  };
  const show = (form, q) => {
    const box = form.querySelector('.pdf-q');
    const buttons = form.querySelectorAll('button[type="submit"]');
    form.dataset.at = q ? String(q.at) : '';
    form.elements.token.value = q ? q.token : '';
    form.elements.answer.value = '';
    form.elements.answer.required = !!q;
    form.querySelector('[data-q]').textContent = q ? q.a + ' + ' + q.b : '…';
    buttons.forEach((b) => { b.disabled = false; });
    // No question (fetch failed): the buttons POST without a token and the PDF URL asks it as a page.
    box.hidden = !q;
  };
  const ask = async (form) => {
    const fresh = form.elements.token.value && Date.now() - Number(form.dataset.at || 0) < FRESH_MS;
    if (fresh) return; // the unused question stays as it is
    const box = form.querySelector('.pdf-q');
    form.querySelector('[data-q]').textContent = '…';
    form.elements.answer.value = '';
    form.querySelectorAll('button[type="submit"]').forEach((b) => { b.disabled = true; });
    box.hidden = false;
    let q = await prefetch();
    next = null;
    if (q && Date.now() - q.at >= FRESH_MS) q = await fetchQuestion().catch(() => null);
    show(form, q);
    const picker = form.closest('details.pdf-pick');
    if (picker.open) { place(picker); if (q) form.elements.answer.focus(); }
  };
  pickers.forEach((picker) => {
    const form = picker.querySelector('form.pdf-menu');
    picker.addEventListener('toggle', () => {
      if (!picker.open) return;
      closeAll(picker);
      place(picker);
      if (form) {
        ask(form);
        if (!form.querySelector('.pdf-q').hidden && form.elements.token.value) form.elements.answer.focus();
      }
    });
    picker.querySelectorAll('.pdf-menu a').forEach((a) => a.addEventListener('click', () => { picker.open = false; }));
    if (form) {
      const summary = picker.querySelector('summary');
      summary.addEventListener('pointerenter', prefetch, { once: true });
      summary.addEventListener('focus', prefetch, { once: true });
      form.addEventListener('submit', () => {
        // The browser has read the form; this token is now used.
        setTimeout(() => {
          picker.open = false;
          form.elements.token.value = '';
          form.dataset.at = '';
          prefetch();
        }, 0);
      });
    }
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
