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
  pickers.forEach((picker) => {
    picker.addEventListener('toggle', () => {
      if (picker.open) { closeAll(picker); place(picker); }
    });
    picker.querySelectorAll('.pdf-menu a').forEach((a) => a.addEventListener('click', () => { picker.open = false; }));
  });
  if (pickers.length) {
    document.addEventListener('click', (ev) => {
      if (!ev.target.closest('details.pdf-pick')) closeAll();
    });
    document.addEventListener('keydown', (ev) => {
      if (ev.key !== 'Escape') return;
      const open = [...pickers].find((d) => d.open);
      if (open) { open.open = false; open.querySelector('summary').focus(); }
    });
    window.addEventListener('scroll', () => closeAll(), { passive: true, capture: true });
    window.addEventListener('resize', () => closeAll());
  }

  // The year filter applies as soon as a year is picked.
  document.querySelectorAll('select[data-autosubmit]').forEach((select) => {
    select.addEventListener('change', () => select.form.submit());
  });
})();
