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

  // PDF download page: a token works once, so right after Open PDF (new tab) or Download the page asks a
  // new question (/pdf-question). Also when the page comes back from the browser's back/forward cache.
  // The sum is checked here first, so a wrong or expired answer shows its message on this page and
  // nothing opens; the server checks the answer again.
  const pdfForm = document.querySelector('form.pdf-check-form');
  if (pdfForm) {
    const label = pdfForm.querySelector('[data-q]');
    const wrong = pdfForm.querySelector('[data-wrong]');
    const answer = pdfForm.elements.answer;
    const renew = () => {
      pdfForm.elements.token.value = '';
      answer.value = '';
      label.textContent = '…';
      fetch('/pdf-question', { cache: 'no-store', headers: { Accept: 'application/json' } })
        .then((res) => { if (!res.ok) throw new Error(String(res.status)); return res.json(); })
        .then((q) => {
          pdfForm.elements.token.value = q.token;
          label.dataset.a = q.a;
          label.dataset.b = q.b;
          label.textContent = q.a + ' + ' + q.b;
        })
        // No new question: reload the page, which brings one.
        .catch(() => window.location.reload());
    };
    pdfForm.addEventListener('submit', (ev) => {
      const token = pdfForm.elements.token.value;
      // Token: "{expires}.{nonce}.{mac}"; a few seconds' margin for the trip to the server.
      const expired = !token || Number(token.split('.')[0]) * 1000 < Date.now() + 5000;
      const right = Number(answer.value) === Number(label.dataset.a) + Number(label.dataset.b);
      if (expired || !right) {
        ev.preventDefault();
        wrong.hidden = false;
        renew();
        answer.focus();
        return;
      }
      wrong.hidden = true;
      // The browser has read the form when the submit event is over; only then clear it.
      setTimeout(renew, 0);
    });
    window.addEventListener('pageshow', (ev) => { if (ev.persisted) renew(); });
  }

  // The year filter applies as soon as a year is picked.
  document.querySelectorAll('select[data-autosubmit]').forEach((select) => {
    select.addEventListener('change', () => select.form.submit());
  });
})();
