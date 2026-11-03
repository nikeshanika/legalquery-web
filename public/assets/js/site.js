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

  // Act PDF: English shows first; the Hindi PDF only when हिन्दी is picked.
  document.querySelectorAll('[data-pdf-switch]').forEach((box) => {
    const group = box.querySelector('.pdf-langs');
    if (!group) return;
    group.hidden = false;
    group.querySelectorAll('[data-pdf-lang]').forEach((button) => {
      button.addEventListener('click', () => {
        const lang = button.dataset.pdfLang;
        group.querySelectorAll('[data-pdf-lang]').forEach((b) => b.setAttribute('aria-pressed', String(b === button)));
        box.querySelectorAll('.pdf-thumb').forEach((thumb) => { thumb.hidden = thumb.dataset.lang !== lang; });
      });
    });
  });

  // The year filter applies as soon as a year is picked.
  document.querySelectorAll('select[data-autosubmit]').forEach((select) => {
    select.addEventListener('change', () => select.form.submit());
  });
})();
