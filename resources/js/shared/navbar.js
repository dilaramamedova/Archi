// Shared navbar behaviour, ported from the old project's archi.js: mega menus,
// search autocomplete, language dropdown and the localStorage cart badge.
// The i18n TreeWalker, the markup injection, the JS-computed active nav item and
// the login modal are gone — Laravel renders all of that server-side now.
// The old hover-opened menus were replaced by the click-driven controller below.

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

// Every navbar surface (mega panels + language dropdown) is CLICK-driven: hover
// never opens or closes anything, there are no open/close timers, and only one
// surface can be open at a time. The search autocomplete is not registered here —
// it is input-driven and keeps its own focus/input handling.
function initMenus() {
  let current = null;

  function closeCurrent(focusTrigger) {
    if (!current) return;
    const menu = current;
    current = null;
    menu.hide();
    menu.trigger.setAttribute('aria-expanded', 'false');
    if (focusTrigger) menu.trigger.focus();
  }

  function openMenu(menu) {
    if (current === menu) return;
    closeCurrent(false); // opening one menu closes any other
    current = menu;
    menu.show();
    menu.trigger.setAttribute('aria-expanded', 'true');
  }

  function toggleMenu(menu) {
    if (current === menu) closeCurrent(false);
    else openMenu(menu);
  }

  // `surface` is the panel the trigger controls. When the surface sits INSIDE the
  // trigger (language dropdown) its own links must keep working, so events coming
  // from within the surface are never treated as a toggle.
  function register(trigger, surface, show, hide) {
    const menu = { trigger, surface, show, hide };
    trigger.setAttribute('aria-expanded', 'false');

    trigger.addEventListener('click', (e) => {
      if (surface.contains(e.target)) return;
      e.preventDefault(); // the trigger only opens the menu; its href is the no-JS fallback
      toggleMenu(menu);
    });

    trigger.addEventListener('keydown', (e) => {
      if (surface.contains(e.target)) return;
      if (e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar') {
        e.preventDefault();
        toggleMenu(menu);
      }
    });

    return menu;
  }

  document.querySelectorAll('.nav-item[data-mega]').forEach((trigger) => {
    const panel = document.querySelector('.mega-panel[data-panel="' + trigger.dataset.mega + '"]');
    if (!panel) return;
    register(
      trigger,
      panel,
      () => { panel.classList.add('open'); trigger.classList.add('mega-active'); },
      () => { panel.classList.remove('open'); trigger.classList.remove('mega-active'); }
    );
  });

  // Switching the language is server-side (<a href="/lang/az">), so this only
  // opens and closes the dropdown.
  const langBtn = document.getElementById('langBtn');
  const langMenu = document.getElementById('langMenu');
  if (langBtn && langMenu) {
    register(langBtn, langMenu, () => langBtn.classList.add('open'), () => langBtn.classList.remove('open'));
  }

  // Click outside the open surface closes it; Escape closes it and returns focus.
  document.addEventListener('click', (e) => {
    if (!current) return;
    if (current.trigger.contains(e.target) || current.surface.contains(e.target)) return;
    closeCurrent(false);
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeCurrent(true);
  });
}

initCartBadge();
initSearch();
initMenus();
