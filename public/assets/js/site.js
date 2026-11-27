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

  // Header height for the sticky header on laptops (CSS --head-h): section links and the contents column stay below it.
  const header = document.querySelector('.site-header');
  const setHeadH = () => document.documentElement.style.setProperty('--head-h', header.offsetHeight + 'px');
  if (header) {
    setHeadH(); // now, before "#section-N" below scrolls to its section
    if ('ResizeObserver' in window) new ResizeObserver(setHeadH).observe(header);
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

  // Contents of an Act's Sections tab: the link of the section being read is marked, and on phones and tablets
  // (up to 900px, see site.css) the Contents are a bar at the top of the screen that names that section and
  // opens the list (owner, 2026-11-27).
  const toc = document.querySelector('nav[data-toc]');
  if (toc && rows.length) {
    const current = toc.querySelector('[data-toc-current]');
    const tocHead = toc.querySelector('.toc-head');
    const tocToggle = toc.querySelector('.toc-toggle');
    const links = new Map([...toc.querySelectorAll('ol a')].map((a) => [decodeURIComponent(a.hash.slice(1)), a]));
    const narrow = window.matchMedia('(max-width: 900px)');
    toc.classList.add('toc-bar');
    document.documentElement.classList.add('has-toc-bar');
    const setOpen = (open) => {
      toc.classList.toggle('open', open);
      tocToggle.setAttribute('aria-expanded', String(open));
      tocToggle.setAttribute('aria-label', open ? 'Hide contents' : 'Show contents');
    };
    tocHead.addEventListener('click', () => { if (narrow.matches) setOpen(!toc.classList.contains('open')); });
    toc.querySelector('.toc-list').addEventListener('click', (ev) => { if (ev.target.closest('a')) setOpen(false); });
    document.addEventListener('click', (ev) => { if (!toc.contains(ev.target)) setOpen(false); });
    document.addEventListener('keydown', (ev) => { if (ev.key === 'Escape') setOpen(false); });
    let active = null;
    const mark = () => {
      // The last section whose top has passed the bar (phones) or the sticky header (laptops).
      const line = (narrow.matches ? tocHead.getBoundingClientRect().bottom : (header ? header.offsetHeight : 0)) + 32;
      let now = null;
      // On phones the bar names a section only once it is stuck to the top of the screen: before that it still
      // sits above Section 1 and says "Contents".
      const reading = !narrow.matches || toc.getBoundingClientRect().top < 1;
      for (const row of rows) {
        if (!reading || row.getBoundingClientRect().top > line) break;
        now = row;
      }
      const link = now ? links.get(now.id) || null : null;
      if (link !== active) {
        if (active) active.classList.remove('active');
        if (link) link.classList.add('active');
        active = link;
      }
      // The bar names the section, e.g. "7. Acceptance must be absolute"; the column on laptops keeps "Contents".
      const [number, title] = link ? [...link.children].map((part) => part.textContent.trim()) : [];
      const text = link && narrow.matches ? (number ? number.replace(/\.$/, '') + '. ' : '') + title : 'Contents';
      if (current.textContent !== text) current.textContent = text;
    };
    // At most 20 or so rows a page, so every scroll event can measure them (no requestAnimationFrame: it waits
    // while the tab is in the background and the bar would keep an old name).
    window.addEventListener('scroll', mark, { passive: true });
    window.addEventListener('resize', mark);
    rows.forEach((row) => row.addEventListener('toggle', mark));
    mark();
  }

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

  // Language menu in the header (owner, 2026-11-25): one language for the whole site, kept in this browser.
  // On an Act page and on the download page of an Act PDF it lists only the languages the Act has a PDF in (owner, 2026-11-25).
  // Other pages list only the languages of the PDFs on the page (a Rules tab: English, हिन्दी …), and English alone when
  // the page has none (home, States, Act lists, search; owner, 2026-11-26); the saved choice is kept for the next page. The download page of an Act PDF: see pdfPage below. It picks the "PDF: …" link: the official PDF in that language (English + हिन्दी counts for both),
  // else the AI translation in it. A saved language this page has no PDF in is kept for the next page; this page
  // then shows its first PDF (English).
  const LANG_KEY = 'lq-lang';
  const picker = document.querySelector('select[data-lang-pick]');
  const pdfBox = document.querySelector('[data-act-pdf]');
  // Download page of a PDF (Act PDF, or rule / notification … since 2026-11-26): this PDF's language is on the page,
  // the other PDFs of the Act or document are its "Other PDFs" links. The menu shows this PDF's language, lists only the languages the Act has a PDF in, and picking
  // another one opens that PDF's download page.
  const pdfPage = document.querySelector('.pdf-check[data-pdf-lang]');
  const pdfLinks = [...(pdfBox || pdfPage || document.createElement('i')).querySelectorAll('a[data-lang]')];
  // PDF language code → menu language codes it serves.
  const servesLangs = (code) => (code === 'bi' ? ['en', 'hi'] : code === 'unknown' ? ['en'] : [code]);
  const readLang = () => { try { return localStorage.getItem(LANG_KEY) || 'en'; } catch (e) { return 'en'; } };
  const pdfFor = (lang) => {
    const official = pdfLinks.filter((a) => !a.hasAttribute('data-ai'));
    const wanted = lang === 'en' ? ['en', 'unknown', 'bi'] : lang === 'hi' ? ['hi', 'bi'] : [lang];
    let pick = null;
    wanted.some((code) => (pick = official.find((a) => a.dataset.lang === code)));
    return pick || pdfLinks.find((a) => a.hasAttribute('data-ai') && a.dataset.lang === lang) || null;
  };
  const showPdf = (pick) => {
    pdfLinks.forEach((a) => { a.hidden = a !== pick; });
  };
  if (picker) {
    const saved = readLang();
    let start = saved;
    const here = pdfPage ? servesLangs(pdfPage.dataset.pdfLang) : [];
    if (!pdfLinks.length && !pdfPage) {
      // The PDF links on the page ("View", "English", "हिन्दी" …: partials/file-links.php), else English only.
      const onPage = new Set(['en', ...[...document.querySelectorAll('a.view[data-lang]')].flatMap((a) => servesLangs(a.dataset.lang))]);
      [...picker.options].forEach((option) => { if (!onPage.has(option.value)) option.remove(); });
      start = onPage.has(saved) ? saved : 'en';
    } else {
      const available = new Set([...here, ...pdfLinks.flatMap((a) => servesLangs(a.dataset.lang))]);
      // Languages the Act has no PDF in are taken out of the menu (owner, 2026-11-25; `hidden` on an option does
      // not work in every browser).
      [...picker.options].forEach((option) => { if (!available.has(option.value)) option.remove(); });
      if (pdfPage) start = here.includes(saved) ? saved : here[0];
      else if (!pdfFor(saved)) start = servesLangs(pdfLinks[0].dataset.lang)[0];
    }
    const option = picker.querySelector(`option[value="${start}"]`);
    if (option) picker.value = start;
    if (pdfBox && pdfLinks.length) showPdf(pdfFor(picker.value) || pdfLinks[0]);
    picker.closest('.lang-pick').hidden = false;
    picker.addEventListener('change', () => {
      try { localStorage.setItem(LANG_KEY, picker.value); } catch (e) { /* private window: this page only */ }
      if (pdfPage) {
        const other = here.includes(picker.value) ? null : pdfFor(picker.value);
        if (other) window.location.assign(other.href);
        return;
      }
      if (pdfLinks.length) showPdf(pdfFor(picker.value) || pdfLinks[0]);
    });
  }

  // The year filter applies as soon as a year is picked.
  document.querySelectorAll('select[data-autosubmit]').forEach((select) => {
    select.addEventListener('change', () => select.form.submit());
  });
})();
