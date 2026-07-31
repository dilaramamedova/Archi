// Page module for "cart" — ported from the inline <script> of the old cart.html.
// The summary panel and the empty state are server-rendered by the Blade view now,
// so this module only builds the item rows and keeps the numbers in sync.
// Text templates and the promo table arrive from Blade on #ctPage as data-* JSON.

const DELIVERY_FREE = 100;
const DELIVERY_FEE = 10;

// Row classes live here so the JS templates stay readable; Tailwind picks them up
// through the `@source "../js"` rule in app.css.
const CLS = {
  row: 'flex items-center gap-4 rounded-ds border border-black/10 bg-white p-4 max-[560px]:flex-wrap',
  thumb: 'size-[70px] shrink-0 rounded-ds bg-[#eceef1] object-cover',
  info: 'min-w-0 flex-1',
  name: 'block text-[15px] font-semibold text-ink',
  meta: 'mt-0.5 text-[13px] text-black/50',
  unit: 'mt-1 text-[13px] text-black/50',
  qty: 'flex items-center overflow-hidden rounded-ds border border-black/15',
  qtyBtn: 'size-[34px] cursor-pointer border-none bg-white text-lg text-ink [font-family:inherit] hover:bg-gray-soft',
  qtyNum: 'min-w-[34px] text-center text-sm font-semibold',
  line: 'min-w-[92px] text-right text-base font-bold text-ink max-[560px]:min-w-[auto]',
  rm: 'cursor-pointer border-none p-1 text-[22px] leading-none text-black/35 [font-family:inherit] hover:text-[#c0392b]',
};

// Thousands grouping follows the active locale (from Blade), not a hardcoded one.
const LOCALE_TAG = { az: 'az-AZ', ru: 'ru-RU', en: 'en-US' };

function makeFmt(locale) {
  const tag = LOCALE_TAG[locale] || 'az-AZ';
  let nf;
  try {
    nf = new Intl.NumberFormat(tag, { maximumFractionDigits: 0 });
  } catch (e) {
    nf = new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 0 });
  }
  return (n) => nf.format(Math.round(n));
}

const esc = (s) =>
  String(s == null ? '' : s).replace(
    /[&<>"']/g,
    (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]
  );

// Laravel-style :placeholder substitution on the raw templates from Blade.
const tr = (tpl, vars) =>
  Object.keys(vars || {}).reduce((s, k) => s.split(':' + k).join(vars[k]), tpl || '');

function readJson(raw, fallback) {
  try {
    return JSON.parse(raw || '');
  } catch (e) {
    return fallback;
  }
}

export default function init() {
  const page = document.getElementById('ctPage');
  if (!page) return;

  const T = readJson(page.dataset.i18n, {});
  const PROMOS = readJson(page.dataset.promos, {});
  const fmt = makeFmt(T.locale);
  const money = (n) => tr(T.money, { amount: fmt(n) });

  const rowsEl = document.getElementById('ctRows');
  const emptyEl = document.getElementById('ctEmpty');
  const msgEl = document.getElementById('ctMsg');
  const subLabelEl = document.getElementById('ctSubLabel');
  const subEl = document.getElementById('ctSub');
  const discRowEl = document.getElementById('ctDiscRow');
  const discLabelEl = document.getElementById('ctDiscLabel');
  const discEl = document.getElementById('ctDisc');
  const delivEl = document.getElementById('ctDeliv');
  const totalEl = document.getElementById('ctTotal');
  const promoInput = document.getElementById('ctPromo');
  const applyBtn = document.getElementById('ctApply');
  const checkoutBtn = document.getElementById('ctCheckout');

  let cart = [];
  let promo = null;
  try {
    cart = readJson(localStorage.getItem('archi-cart'), []);
    promo = localStorage.getItem('archi-promo') || null;
  } catch (e) {
    // localStorage blocked — the page still works, nothing is persisted
  }
  if (!Array.isArray(cart)) cart = [];
  cart = cart.map((c) => ({ ...c, qty: c.qty || 1 }));
  if (promoInput) promoInput.value = promo || '';

  function save() {
    try {
      localStorage.setItem('archi-cart', JSON.stringify(cart));
    } catch (e) {
      // localStorage blocked — skip silently
    }
    const badge = document.getElementById('navCartCount');
    if (!badge) return;
    badge.textContent = cart.length;
    badge.style.display = cart.length ? 'flex' : 'none';
  }

  const subtotal = () => cart.reduce((s, c) => s + (+c.price || 0) * (c.qty || 1), 0);

  function discount() {
    const p = promo ? PROMOS[promo] : null;
    if (!p) return 0;
    const sub = subtotal();
    if (p.min && sub < p.min) return 0;
    return p.type === 'pct' ? sub * p.val : p.val;
  }

  function renderItems() {
    if (!rowsEl || !emptyEl) return;
    emptyEl.hidden = cart.length > 0;
    rowsEl.innerHTML = cart
      .map(
        (c, i) => `
    <div class="${CLS.row}" data-i="${i}">
      <div class="${CLS.thumb}"></div>
      <div class="${CLS.info}"><b class="${CLS.name}">${esc(c.name)}</b><div class="${CLS.meta}">${esc(c.cat || '')}${c.brand ? ' · ' + esc(c.brand) : ''}</div><div class="${CLS.unit}">${esc(tr(T.unitPrice, { price: fmt(+c.price || 0) }))}</div></div>
      <div class="${CLS.qty}"><button type="button" class="${CLS.qtyBtn}" data-a="dec" aria-label="${esc(T.decrease)}">−</button><span class="${CLS.qtyNum}" data-qty>${c.qty}</span><button type="button" class="${CLS.qtyBtn}" data-a="inc" aria-label="${esc(T.increase)}">+</button></div>
      <div class="${CLS.line}" data-line>${esc(money((+c.price || 0) * (c.qty || 1)))}</div>
      <button type="button" class="${CLS.rm}" data-a="rm" title="${esc(T.remove)}" aria-label="${esc(T.remove)}">×</button>
    </div>`
      )
      .join('');
  }

  function renderSum() {
    const sub = subtotal();
    const disc = discount();
    const afterDisc = sub - disc;
    const delivery = cart.length ? (afterDisc >= DELIVERY_FREE ? 0 : DELIVERY_FEE) : 0;
    const total = afterDisc + delivery;

    if (msgEl) {
      const p = promo ? PROMOS[promo] : null;
      if (!promo) {
        msgEl.hidden = true;
        msgEl.removeAttribute('data-ok');
        msgEl.textContent = '';
      } else if (!p) {
        msgEl.hidden = false;
        msgEl.dataset.ok = 'false';
        msgEl.textContent = T.promoUnknown;
      } else if (p.min && sub < p.min) {
        msgEl.hidden = false;
        msgEl.dataset.ok = 'false';
        msgEl.textContent = tr(T.promoMin, { code: promo, min: p.min });
      } else {
        msgEl.hidden = false;
        msgEl.dataset.ok = 'true';
        msgEl.textContent = tr(T.promoApplied, { code: promo, label: p.label });
      }
    }

    if (subLabelEl) subLabelEl.textContent = tr(T.subtotal, { count: cart.reduce((s, c) => s + c.qty, 0) });
    if (subEl) subEl.textContent = money(sub);

    if (discRowEl) {
      discRowEl.hidden = disc <= 0;
      if (disc > 0) {
        if (discLabelEl) discLabelEl.textContent = tr(T.discount, { code: promo });
        if (discEl) discEl.textContent = '− ' + money(disc);
      }
    }

    if (delivEl) delivEl.textContent = delivery ? money(delivery) : T.deliveryFree;
    if (totalEl) totalEl.textContent = money(total);
    return total;
  }

  let currentTotal = 0;
  function render() {
    renderItems();
    currentTotal = renderSum();
  }

  // Quantity and remove buttons — one delegated listener survives every re-render.
  // Quantity changes patch the affected row in place instead of rebuilding the list:
  // re-rendering would destroy the button being clicked, dropping keyboard focus back
  // to <body> and losing :hover/:active mid-click.
  if (rowsEl) {
    rowsEl.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-a]');
      if (!btn) return;
      const row = btn.closest('[data-i]');
      if (!row) return;
      const i = +row.dataset.i;
      const item = cart[i];
      if (!item) return;
      const a = btn.dataset.a;

      if (a === 'rm') {
        const next = row.nextElementSibling || row.previousElementSibling;
        cart.splice(i, 1);
        row.remove();
        // indices are positional, so everything after the removed row shifts up
        rowsEl.querySelectorAll('[data-i]').forEach((el, n) => {
          el.dataset.i = n;
        });
        if (emptyEl) emptyEl.hidden = cart.length > 0;
        // the focused button just left the document — keep the tab position nearby
        const nextRm = next && next.querySelector('[data-a="rm"]');
        if (nextRm) nextRm.focus();
        else if (emptyEl && !emptyEl.hidden) {
          const cta = emptyEl.querySelector('a');
          if (cta) cta.focus();
        }
      } else if (a === 'inc' || a === 'dec') {
        item.qty = a === 'inc' ? item.qty + 1 : Math.max(1, item.qty - 1);
        const qtyEl = row.querySelector('[data-qty]');
        const lineEl = row.querySelector('[data-line]');
        if (qtyEl) qtyEl.textContent = item.qty;
        if (lineEl) lineEl.textContent = money((+item.price || 0) * item.qty);
      } else {
        return;
      }

      save();
      currentTotal = renderSum();
    });
  }

  function applyPromo() {
    const code = (promoInput.value || '').trim().toUpperCase();
    promo = code || null;
    try {
      localStorage.setItem('archi-promo', promo || '');
    } catch (e) {
      // localStorage blocked — skip silently
    }
    currentTotal = renderSum();
  }

  if (applyBtn && promoInput) {
    applyBtn.addEventListener('click', applyPromo);
    promoInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') applyPromo();
    });
  }

  if (checkoutBtn) {
    checkoutBtn.addEventListener('click', () => {
      if (!cart.length) {
        alert(T.alertEmpty);
        return;
      }
      alert(tr(T.alertDone, { total: money(currentTotal) }));
    });
  }

  save();
  render();
}

init();
