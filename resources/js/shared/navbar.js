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

  // URLs and labels arrive from Blade as data-* attributes, already translated.
  const d = box.dataset;
  const URL_API = d.urlApi || '/api/search';
  const URL_SEARCH = d.urlSearch || '/search';
  const URL_SPECIALISTS = d.urlSpecialists || '/specialist';
  const URL_PRODUCT = d.urlProduct || '/product';
  const L_QUICK = d.lQuick || '';
  const L_PRODUCTS = d.lProducts || '';
  const L_MASTERS = d.lMasters || '';
  const L_ALL = d.lAll || '';
  const L_LOADING = d.lLoading || 'Loading…';
  const L_NO_RESULTS = d.lNoResults || 'No results';

  const overlay = document.createElement('div');
  overlay.className = 'search-overlay';
  (document.querySelector('.topbar') || document.body).appendChild(overlay);

  const esc = (s) =>
    String(s || '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

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

  function render(qRaw, data) {
    const sug = data.suggests || [];
    const prod = data.products || [];
    const masters = data.masters || [];
    const total = data.total != null ? data.total : (sug.length + prod.length + masters.length);

    let html = '';
    if (sug.length) {
      html +=
        '<div class="sd-head">' + esc(L_QUICK) + '</div>' +
        sug
          .map((s) => '<a class="sd-sug" href="' + resultsHref(s) + '"><img src="/assets/icon-search.svg" alt=""><span>' + hl(s, qRaw) + '</span></a>')
          .join('');
    }
    if (prod.length) {
      if (html) html += '<div class="sd-div"></div>';
      html +=
        '<div class="sd-head">' + esc(L_PRODUCTS) + '</div>' +
        prod
          .map((p) =>
            '<a class="sd-prod" href="' + (p.slug ? URL_PRODUCT + '/' + encodeURIComponent(p.slug) : resultsHref(qRaw)) + '"><span class="im"><img src="' + esc(p.img || '') + '" alt=""></span>' +
            '<span class="tx"><span class="t1">' + hl(p.name, qRaw) + '</span><br><span class="t2">' + esc(p.cat) + '</span></span>' +
            '<span class="pr">' + esc(p.price) + '</span></a>')
          .join('');
    }
    if (masters.length) {
      if (html) html += '<div class="sd-div"></div>';
      html +=
        '<div class="sd-head">' + esc(L_MASTERS) + '</div>' +
        masters
          .map((u) =>
            '<a class="sd-master" href="' + URL_SPECIALISTS + '/' + encodeURIComponent(u.id) + '"><span class="av">' + esc(u.initials) + '</span>' +
            '<span class="tx"><span class="t1">' + hl(u.name, qRaw) + '</span><br><span class="t2">' + esc(u.role) + '</span></span>' +
            '<span class="rt"><span class="st">★</span>' + esc(u.rate) + '</span></a>')
          .join('');
    }
    if (!sug.length && !prod.length && !masters.length) {
      html += '<div class="sd-head" style="text-align:center;padding:18px 0">' + esc(L_NO_RESULTS) + '</div>';
    } else {
      html += '<a class="sd-all" href="' + resultsHref(qRaw) + '">' + esc(L_ALL) + ' (' + total + ') →</a>';
    }
    drop.innerHTML = html;
  }

  // Debounce + fetch
  let timer = null;
  let abortCtrl = null;

  function fetchResults() {
    const q = (input.value || '').trim();
    if (q.length < 1) { close(); return; }

    // Show loading state
    drop.innerHTML = '<div class="sd-head" style="text-align:center;padding:18px 0">' + esc(L_LOADING) + '</div>';
    drop.classList.add('on');
    overlay.classList.add('on');

    // Abort any in-flight request
    if (abortCtrl) abortCtrl.abort();
    abortCtrl = new AbortController();

    fetch(URL_API + '?q=' + encodeURIComponent(q), { signal: abortCtrl.signal })
      .then((r) => r.json())
      .then((data) => {
        // If input changed while fetching, ignore stale response
        if ((input.value || '').trim() !== q) return;
        render(q, data);
        drop.classList.add('on');
        overlay.classList.add('on');
      })
      .catch((err) => {
        if (err.name === 'AbortError') return;
        close();
      });
  }

  function scheduleSearch() {
    if (timer) clearTimeout(timer);
    const q = (input.value || '').trim();
    if (q.length < 1) { close(); return; }
    timer = setTimeout(fetchResults, 300);
  }

  function close() {
    drop.classList.remove('on');
    overlay.classList.remove('on');
  }

  input.addEventListener('focus', () => {
    if ((input.value || '').trim().length >= 1) scheduleSearch();
  });
  input.addEventListener('input', scheduleSearch);
  input.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') { close(); input.blur(); }
    if (e.key === 'Enter') { location.href = resultsHref(input.value); }
  });
  document.addEventListener('click', (e) => { if (!e.target.closest('.search')) close(); });
  overlay.addEventListener('click', close);
}

// Navbar surface controller. The language dropdown stays CLICK-driven; the mega
// panels additionally open/close on HOVER on devices that actually have a hover
// pointer (a short close delay bridges the trigger→panel move so the panel never
// flickers shut mid-way). On touch/keyboard the click/Enter toggle still rules,
// and only one surface can be open at a time. The search autocomplete is not
// registered here — it is input-driven and keeps its own focus/input handling.
function initMenus() {
  let current = null;
  const hoverable = window.matchMedia('(hover: hover) and (pointer: fine)');
  let hoverCloseTimer = null;

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
      // On hover devices the panel is already open on hover, so a click on a
      // trigger with a real destination should follow the link instead of
      // re-toggling the panel it is hovering over.
      const href = trigger.getAttribute('href');
      if (menu.hoverOpens && hoverable.matches && href && href !== '#') return;
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
    const menu = register(
      trigger,
      panel,
      () => { panel.classList.add('open'); trigger.classList.add('mega-active'); },
      () => { panel.classList.remove('open'); trigger.classList.remove('mega-active'); }
    );
    menu.hoverOpens = true;

    // Hover behaviour (hover-capable pointers only). The trigger's bottom edge and
    // the panel's top edge are contiguous (both sit on `.topbar`'s bottom), and the
    // 200ms grace timer absorbs the border crossing and diagonal mouse paths.
    const cancelClose = () => { if (hoverCloseTimer) { clearTimeout(hoverCloseTimer); hoverCloseTimer = null; } };
    const scheduleClose = () => {
      cancelClose();
      hoverCloseTimer = setTimeout(() => { if (current === menu) closeCurrent(false); }, 200);
    };
    trigger.addEventListener('mouseenter', () => {
      if (!hoverable.matches) return;
      cancelClose();
      openMenu(menu);
    });
    trigger.addEventListener('mouseleave', () => { if (hoverable.matches && current === menu) scheduleClose(); });
    panel.addEventListener('mouseenter', () => { if (hoverable.matches && current === menu) cancelClose(); });
    panel.addEventListener('mouseleave', () => { if (hoverable.matches && current === menu) scheduleClose(); });
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

function initMobileDrawer() {
  const burger = document.getElementById('mobBurger');
  const drawer = document.getElementById('mobDrawer');
  const overlay = document.getElementById('mobOverlay');
  const close = document.getElementById('mobClose');
  if (!burger || !drawer || !overlay) return;

  function open() {
    drawer.classList.add('open');
    overlay.classList.add('open');
    burger.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
  }

  function shut() {
    drawer.classList.remove('open');
    overlay.classList.remove('open');
    burger.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  }

  burger.addEventListener('click', open);
  if (close) close.addEventListener('click', shut);
  overlay.addEventListener('click', shut);
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && drawer.classList.contains('open')) shut();
  });
}

function initAccountDropdowns() {
  const accountBtn = document.getElementById('navAccountBtn');
  const accountPanel = document.getElementById('navAccountPanel');
  const notifyBtn = document.getElementById('navNotifyBtn');
  const notifyPanel = document.getElementById('navNotifyPanel');

  function toggle(btn, panel) {
    if (!btn || !panel) return;
    const isOpen = !panel.classList.contains('hidden');
    // close both first
    [accountPanel, notifyPanel].forEach(p => p && p.classList.add('hidden'));
    [accountBtn, notifyBtn].forEach(b => b && b.setAttribute('aria-expanded', 'false'));
    if (!isOpen) {
      panel.classList.remove('hidden');
      btn.setAttribute('aria-expanded', 'true');
    }
  }

  if (accountBtn && accountPanel) {
    accountBtn.addEventListener('click', (e) => { e.stopPropagation(); toggle(accountBtn, accountPanel); });
  }
  if (notifyBtn && notifyPanel) {
    notifyBtn.addEventListener('click', (e) => { e.stopPropagation(); toggle(notifyBtn, notifyPanel); });
  }

  document.addEventListener('click', (e) => {
    if (accountPanel && !accountPanel.classList.contains('hidden') && !e.target.closest('#navAccountWrap')) {
      accountPanel.classList.add('hidden');
      accountBtn?.setAttribute('aria-expanded', 'false');
    }
    if (notifyPanel && !notifyPanel.classList.contains('hidden') && !e.target.closest('#navNotifyWrap')) {
      notifyPanel.classList.add('hidden');
      notifyBtn?.setAttribute('aria-expanded', 'false');
    }
  });
}

function initPopupCompat() {
  // Backward-compat shims for the retired #globalToast / #confirmDialog infra:
  // any straggler still calling ARCHI.toast / ARCHI.confirm lands on the global
  // popup system (window.archiPopup, resources/js/shared/popup.js).
  window.ARCHI = window.ARCHI || {};
  window.ARCHI.toast = {
    show(opts) {
      const { type = 'info', title = '', body = '' } = opts || {};
      const kind = ['success', 'error', 'warning', 'info'].includes(type) ? type : 'info';
      window.archiPopup?.[kind]?.([title, body].filter(Boolean).join('\n'));
    },
    hide() {
      window.archiPopup?.close();
    }
  };
  window.ARCHI.confirm = function(opts) {
    const isObj = opts !== null && typeof opts === 'object';
    const message = isObj ? (opts.body || opts.title || '') : (opts || '');
    if (!window.archiPopup) return Promise.resolve(window.confirm(message));
    // only pass the title as a heading when it isn't already the message body
    return window.archiPopup.confirm(message, isObj && opts.body && opts.title ? { title: opts.title } : {});
  };
}

function initFaqAccordion() {
  document.querySelectorAll('.help-faq-item').forEach(item => {
    const content = item.querySelector('div[style]') || item.querySelector('.overflow-hidden');
    if (!content) return;
    const observer = new MutationObserver(() => {
      content.style.maxHeight = item.dataset.expanded === 'true' ? content.scrollHeight + 'px' : '0';
    });
    observer.observe(item, { attributes: true, attributeFilter: ['data-expanded'] });
    content.style.maxHeight = item.dataset.expanded === 'true' ? content.scrollHeight + 'px' : '0';
  });
}

function initOtpInputs() {
  const wrap = document.getElementById('otpInputs');
  if (!wrap) return;
  const inputs = wrap.querySelectorAll('input[data-otp]');
  inputs.forEach((inp, i) => {
    inp.addEventListener('input', () => {
      inp.value = inp.value.replace(/\D/g, '').slice(0, 1);
      if (inp.value && i < inputs.length - 1) inputs[i + 1].focus();
    });
    inp.addEventListener('keydown', (e) => {
      if (e.key === 'Backspace' && !inp.value && i > 0) inputs[i - 1].focus();
    });
    inp.addEventListener('paste', (e) => {
      e.preventDefault();
      const text = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6);
      text.split('').forEach((ch, j) => { if (inputs[j]) inputs[j].value = ch; });
      if (inputs[text.length - 1]) inputs[text.length - 1].focus();
    });
  });
}

initCartBadge();
initSearch();
initMenus();
initMobileDrawer();
initAccountDropdowns();
initPopupCompat();
initFaqAccordion();
initOtpInputs();
