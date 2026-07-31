// Shared navbar behaviour, ported from the old project's archi.js: mega menu,
// search autocomplete, language dropdown toggle and the localStorage cart badge.
// The i18n TreeWalker, the markup injection, the JS-computed active nav item and
// the login modal are gone — Laravel renders all of that server-side now.

function initCartBadge() {
  try {
    const cart = JSON.parse(localStorage.getItem('archi-cart') || '[]');
    const badge = document.getElementById('navCartCount');
    if (!badge) return;
    if (cart.length) {
      badge.textContent = cart.length;
      badge.style.display = 'flex';
    } else {
      badge.style.display = 'none';
    }
  } catch (e) {
    // localStorage blocked — skip silently
  }
}

function initSearch() {
  const box = document.querySelector('.search');
  const input = document.getElementById('navSearch');
  const drop = document.getElementById('searchDrop');
  if (!box || !input || !drop) return;

  // URLs, labels and the demo dataset all arrive from Blade as data-* attributes,
  // already translated for the current locale.
  const d = box.dataset;
  const URL_SEARCH = d.urlSearch || '/search';
  const URL_PRODUCT = d.urlProduct || '/product';
  const URL_SPECIALISTS = d.urlSpecialists || '/specialists';
  const L_QUICK = d.lQuick || '';
  const L_PRODUCTS = d.lProducts || '';
  const L_MASTERS = d.lMasters || '';
  const L_ALL = d.lAll || '';

  const parse = (raw) => {
    try {
      const v = JSON.parse(raw || '[]');
      return Array.isArray(v) ? v : [];
    } catch (e) {
      return [];
    }
  };

  // Placeholder dataset — replaced by the API response once a backend exists.
  const SUGGESTS = parse(d.demoSuggests);
  const PRODUCTS = parse(d.demoProducts);
  const MASTERS = parse(d.demoMasters);

  const overlay = document.createElement('div');
  overlay.className = 'search-overlay';
  (document.querySelector('.topbar') || document.body).appendChild(overlay);

  const esc = (s) =>
    s.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

  // Comparison key that folds Azerbaijani diacritics to their ASCII base letter
  const norm = (s) =>
    (s || '')
      .toLowerCase()
      .replace(/i̇/g, 'i')
      .replace(/ı/g, 'i')
      .replace(/ə/g, 'e')
      .replace(/ö/g, 'o')
      .replace(/ü/g, 'u')
      .replace(/ç/g, 'c')
      .replace(/ş/g, 's')
      .replace(/ğ/g, 'g');

  // Nothing matched means the dropdown stays closed
  function match(qRaw) {
    const q = norm((qRaw || '').trim());
    if (q.length < 2) return null; // do not open on a single character
    const has = (txt) => norm(txt).indexOf(q) !== -1;
    const sug = SUGGESTS.filter(has).slice(0, 4);
    const prod = PRODUCTS.filter((p) => has(p.name) || has(p.cat)).slice(0, 3);
    const masters = MASTERS.filter((m) => has(m.name) || has(m.role)).slice(0, 2);
    if (!sug.length && !prod.length && !masters.length) return null;
    return { sug, prod, masters, total: sug.length + prod.length + masters.length };
  }

  function hl(txt, qRaw) {
    const q = norm((qRaw || '').trim());
    const i = norm(txt).indexOf(q);
    if (i === -1) return esc(txt);
    return esc(txt.slice(0, i)) + '<b>' + esc(txt.slice(i, i + q.length)) + '</b>' + esc(txt.slice(i + q.length));
  }

  function resultsHref(qRaw) {
    const q = (qRaw || '').trim();
    return URL_SEARCH + (q ? '?q=' + encodeURIComponent(q) : '');
  }

  function render(qRaw, m) {
    let html = '';
    if (m.sug.length) {
      html +=
        '<div class="sd-head">' + esc(L_QUICK) + '</div>' +
        m.sug
          .map((s) => '<a class="sd-sug" href="' + resultsHref(s) + '"><img src="/assets/ic-search.svg" alt=""><span>' + hl(s, qRaw) + '</span></a>')
          .join('');
    }
    if (m.prod.length) {
      if (html) html += '<div class="sd-div"></div>';
      html +=
        '<div class="sd-head">' + esc(L_PRODUCTS) + '</div>' +
        m.prod
          .map((p) =>
            '<a class="sd-prod" href="' + URL_PRODUCT + '"><span class="im"><img src="' + p.img + '" alt=""></span>' +
            '<span class="tx"><span class="t1">' + hl(p.name, qRaw) + '</span><br><span class="t2">' + esc(p.cat) + '</span></span>' +
            '<span class="pr">' + esc(p.price) + '</span></a>')
          .join('');
    }
    if (m.masters.length) {
      if (html) html += '<div class="sd-div"></div>';
      html +=
        '<div class="sd-head">' + esc(L_MASTERS) + '</div>' +
        m.masters
          .map((u) =>
            '<a class="sd-master" href="' + URL_SPECIALISTS + '"><span class="av">' + esc(u.initials) + '</span>' +
            '<span class="tx"><span class="t1">' + hl(u.name, qRaw) + '</span><br><span class="t2">' + esc(u.role) + '</span></span>' +
            '<span class="rt"><span class="st">★</span>' + esc(u.rate) + '</span></a>')
          .join('');
    }
    html += '<a class="sd-all" href="' + resultsHref(qRaw) + '">' + esc(L_ALL) + ' (' + m.total + ') →</a>';
    drop.innerHTML = html;
  }

  function update() {
    const m = match(input.value);
    if (!m) {
      close();
      return;
    }
    render(input.value, m);
    drop.classList.add('on');
    overlay.classList.add('on');
  }
  function close() {
    drop.classList.remove('on');
    overlay.classList.remove('on');
  }

  input.addEventListener('focus', update);
  input.addEventListener('input', update);
  input.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') { close(); input.blur(); }
    if (e.key === 'Enter') { location.href = resultsHref(input.value); }
  });
  document.addEventListener('click', (e) => { if (!e.target.closest('.search')) close(); });
  overlay.addEventListener('click', close);
}

function initMega() {
  const triggers = document.querySelectorAll('.nav-item[data-mega]');
  const panels = document.querySelectorAll('.mega-panel');
  if (!triggers.length) return;
  let hideTimer;

  function closeAll() {
    panels.forEach((p) => p.classList.remove('open'));
    triggers.forEach((t) => t.classList.remove('mega-active'));
  }
  function open(key) {
    closeAll();
    const p = document.querySelector('.mega-panel[data-panel="' + key + '"]');
    const t = document.querySelector('.nav-item[data-mega="' + key + '"]');
    if (p) p.classList.add('open');
    if (t) t.classList.add('mega-active');
  }
  function toggle(key) {
    const p = document.querySelector('.mega-panel[data-panel="' + key + '"]');
    if (p && p.classList.contains('open')) closeAll();
    else open(key);
  }

  triggers.forEach((t) => {
    t.addEventListener('mouseenter', () => { clearTimeout(hideTimer); open(t.dataset.mega); });
    t.addEventListener('mouseleave', () => { hideTimer = setTimeout(closeAll, 160); });
    t.addEventListener('click', (e) => { if (t.getAttribute('href')) return; e.preventDefault(); toggle(t.dataset.mega); });
    t.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') { closeAll(); return; }
      if (e.key === 'Enter' || e.key === ' ') {
        if (t.getAttribute('href')) return;
        e.preventDefault();
        toggle(t.dataset.mega);
      }
    });
  });
  panels.forEach((p) => {
    p.addEventListener('mouseenter', () => clearTimeout(hideTimer));
    p.addEventListener('mouseleave', () => { hideTimer = setTimeout(closeAll, 160); });
  });
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.nav-item[data-mega]') && !e.target.closest('.mega-panel')) closeAll();
  });
}

// Switching the language is server-side (<a href="/lang/az">), so this only opens
// and closes the dropdown.
function initLang() {
  const langBtn = document.getElementById('langBtn');
  if (!langBtn) return;
  langBtn.addEventListener('click', (e) => { e.stopPropagation(); langBtn.classList.toggle('open'); });
  langBtn.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); langBtn.classList.toggle('open'); }
    if (e.key === 'Escape') langBtn.classList.remove('open');
  });
  document.addEventListener('click', () => langBtn.classList.remove('open'));
}

initCartBadge();
initSearch();
initMega();
initLang();
