// Page module for "cart" — ported from the inline <script> of the old cart.html.
// The summary panel and the empty state are server-rendered by the Blade view now,
// so this module only builds the item rows and keeps the numbers in sync.
// Text templates and the delivery city list arrive from Blade on #ctPage as data-* JSON.

import { open as openLoginModal } from '../shared/login-modal.js';
import popup from '../shared/popup.js';

const DELIVERY_FREE = 100;
const DELIVERY_FEE = 10;

// Row classes live here so the JS templates stay readable; Tailwind picks them up
// through the `@source "../js"` rule in app.css.
// Below 560px the row is the two-line grid from the mobile design — the areas are in
// resources/css/pages/cart.css, the `ct-*` classes below are only its hooks. Everything
// else stays a utility so the desktop row keeps its exact sizes.
const CLS = {
  row: 'ct-row flex items-center gap-4 rounded-ds border border-black/10 bg-white p-4 max-[560px]:grid max-[560px]:items-start max-[560px]:gap-3 max-[560px]:p-3.5',
  thumb: 'ct-thumb size-[70px] shrink-0 rounded-ds bg-[#eceef1] object-cover max-[560px]:size-16',
  info: 'ct-info min-w-0 flex-1',
  name: 'block text-[15px] font-semibold text-ink max-[560px]:text-sm',
  meta: 'mt-0.5 text-[13px] text-black/50 max-[560px]:text-xs',
  unit: 'mt-1 text-[13px] text-black/50 max-[560px]:text-xs',
  qty: 'ct-qty flex items-center overflow-hidden rounded-ds border border-black/15 max-[560px]:justify-self-start',
  qtyBtn: 'size-[34px] cursor-pointer border-none bg-white text-lg text-ink [font-family:inherit] hover:bg-gray-soft max-[560px]:size-[38px]',
  qtyNum: 'min-w-[34px] text-center text-sm font-semibold max-[560px]:min-w-10',
  line: 'ct-total min-w-[92px] text-right text-base font-bold text-ink max-[560px]:min-w-[auto] max-[560px]:self-center max-[560px]:text-[17px]',
  // the mobile box is padded out to a 32px tap target and pulled back into the card
  // corner, so it still reads as the small × the design draws
  rm: 'ct-rm cursor-pointer border-none p-1 text-[22px] leading-none text-black/35 [font-family:inherit] hover:text-[#c0392b] max-[560px]:-mt-1 max-[560px]:-mr-1 max-[560px]:flex max-[560px]:size-8 max-[560px]:items-start max-[560px]:justify-center max-[560px]:p-0 max-[560px]:text-xl',
  // Checkout modal classes. The form is taller than a phone viewport, so the overlay
  // scrolls and the dialog is centred with `my-auto` instead of `items-center` — with
  // items-center the top of a too-tall dialog is pushed off-screen and unreachable.
  overlay: 'fixed inset-0 z-[9999] flex justify-center overflow-y-auto overscroll-contain bg-black/50 p-4',
  modal: 'relative my-auto w-full max-w-[440px] rounded-ds bg-white p-6 shadow-xl max-[560px]:p-[18px]',
  modalTitle: 'mb-5 text-lg font-bold text-ink',
  field: 'flex flex-col gap-1.5',
  label: 'text-[13px] font-semibold text-ink',
  input: 'rounded-ds border border-black/15 px-3.5 py-[11px] text-sm outline-none [font-family:inherit] focus:border-ink',
  select: 'rounded-ds border border-black/15 bg-white px-3.5 py-[11px] text-sm outline-none [font-family:inherit] focus:border-ink',
  textarea: 'rounded-ds border border-black/15 px-3.5 py-[11px] text-sm outline-none [font-family:inherit] focus:border-ink resize-none',
  fieldError: 'text-xs text-[#c0392b]',
  modalBtns: 'mt-5 flex gap-3',
};

// Thousands grouping follows the active locale (from Blade), not a hardcoded one.
// Bug fix: use minimumFractionDigits:2 + maximumFractionDigits:2 to preserve decimals
// (e.g. 29.90 stays "29,90" instead of rounding to "30").
const LOCALE_TAG = { az: 'az-AZ', ru: 'ru-RU', en: 'en-US' };

function makeFmt(locale) {
  const tag = LOCALE_TAG[locale] || 'az-AZ';
  let nf;
  try {
    nf = new Intl.NumberFormat(tag, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  } catch (e) {
    nf = new Intl.NumberFormat('ru-RU', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  }
  return (n) => nf.format(n);
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

function csrf() {
  return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

export default function init() {
  const page = document.getElementById('ctPage');
  if (!page) return;

  const T = readJson(page.dataset.i18n, {});
  const CITIES = readJson(page.dataset.cities, {});
  // product_id => image URL from the server cart — fills in rows whose localStorage
  // entry predates the `img` field.
  const IMAGES = readJson(page.dataset.images, {});
  const authUser = readJson(page.dataset.auth, null);
  const orderUrl = page.dataset.orderUrl || '/api/orders';
  const fmt = makeFmt(T.locale);
  const money = (n) => tr(T.money, { amount: fmt(n) });

  const rowsEl = document.getElementById('ctRows');
  const emptyEl = document.getElementById('ctEmpty');
  const subLabelEl = document.getElementById('ctSubLabel');
  const subEl = document.getElementById('ctSub');
  const delivEl = document.getElementById('ctDeliv');
  const totalEl = document.getElementById('ctTotal');
  const checkoutBtn = document.getElementById('ctCheckout');

  let cart = [];
  try {
    cart = readJson(localStorage.getItem('archi-cart'), []);
  } catch (e) {
    // localStorage blocked — the page still works, nothing is persisted
  }
  if (!Array.isArray(cart)) cart = [];
  cart = cart.map((c) => ({ ...c, qty: c.qty || 1 }));

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

  function renderItems() {
    if (!rowsEl || !emptyEl) return;
    emptyEl.hidden = cart.length > 0;
    rowsEl.innerHTML = cart
      .map(
        (c, i) => `
    <div class="${CLS.row}" data-i="${i}">
      <img class="${CLS.thumb}" src="${esc(c.img || (c.id && IMAGES[c.id]) || '/assets/product-marble-tile.png')}" alt="" loading="lazy">
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
    const delivery = cart.length ? (sub >= DELIVERY_FREE ? 0 : DELIVERY_FEE) : 0;
    const total = sub + delivery;

    if (subLabelEl) subLabelEl.textContent = tr(T.subtotal, { count: cart.reduce((s, c) => s + c.qty, 0) });
    if (subEl) subEl.textContent = money(sub);

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

  // ---- Checkout flow ----
  // For all users: show a modal form collecting name/phone/address.
  // Auth users get their name/phone pre-filled from the server.

  function removeModal() {
    const existing = document.getElementById('ctModal');
    if (existing) existing.remove();
  }

  function showCheckoutForm() {
    removeModal();

    const cityOptions = Object.keys(CITIES)
      .map((value) => `<option value="${esc(value)}">${esc(CITIES[value])}</option>`)
      .join('');

    const overlay = document.createElement('div');
    overlay.id = 'ctModal';
    overlay.className = CLS.overlay;
    overlay.innerHTML = `
      <div class="${CLS.modal}">
        <h3 class="${CLS.modalTitle}">${esc(T.checkoutTitle)}</h3>
        <div style="display:flex;flex-direction:column;gap:14px">
          <div class="${CLS.field}">
            <label class="${CLS.label}">${esc(T.checkoutName)} *</label>
            <input class="${CLS.input}" id="coName" value="${esc(authUser?.name || '')}" autocomplete="name">
            <div class="${CLS.fieldError}" id="coNameErr" hidden></div>
          </div>
          <div class="${CLS.field}">
            <label class="${CLS.label}">${esc(T.checkoutPhone)} *</label>
            <input class="${CLS.input}" id="coPhone" type="tel" value="${esc(authUser?.phone || '')}" autocomplete="tel">
            <div class="${CLS.fieldError}" id="coPhoneErr" hidden></div>
          </div>
          <div class="${CLS.field}">
            <label class="${CLS.label}">${esc(T.checkoutCity)} *</label>
            <select class="${CLS.select}" id="coCity" autocomplete="address-level2">
              <option value="">${esc(T.checkoutCityPlaceholder || '')}</option>
              ${cityOptions}
            </select>
            <div class="${CLS.fieldError}" id="coCityErr" hidden></div>
          </div>
          <div class="${CLS.field}">
            <label class="${CLS.label}">${esc(T.checkoutAddress)} *</label>
            <input class="${CLS.input}" id="coAddr" autocomplete="street-address">
            <div class="${CLS.fieldError}" id="coAddrErr" hidden></div>
          </div>
          <div class="${CLS.field}">
            <label class="${CLS.label}">${esc(T.checkoutNotes)}</label>
            <textarea class="${CLS.textarea}" id="coNotes" rows="2"></textarea>
          </div>
        </div>
        <div class="${CLS.modalBtns}">
          <button type="button" id="coCancel" class="flex-1 rounded-ds border border-black/15 bg-white px-5 py-3 text-sm font-semibold text-ink [font-family:inherit] hover:bg-gray-soft cursor-pointer">${esc(T.checkoutCancel)}</button>
          <button type="button" id="coSubmit" class="flex-1 rounded-ds bg-[#c8a24e] px-5 py-3 text-sm font-semibold text-white [font-family:inherit] hover:brightness-[.93] cursor-pointer">${esc(T.checkoutSubmit)}</button>
        </div>
      </div>
    `;

    document.body.appendChild(overlay);

    // Close on overlay click (not on modal body)
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) removeModal();
    });

    document.getElementById('coCancel').addEventListener('click', removeModal);
    document.getElementById('coSubmit').addEventListener('click', submitOrder);

    // Close on Escape
    overlay.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') removeModal();
    });

    // Focus first empty field
    const nameEl = document.getElementById('coName');
    if (nameEl) nameEl.focus();
  }

  async function submitOrder() {
    const nameEl = document.getElementById('coName');
    const phoneEl = document.getElementById('coPhone');
    const cityEl = document.getElementById('coCity');
    const addrEl = document.getElementById('coAddr');
    const notesEl = document.getElementById('coNotes');
    const submitBtn = document.getElementById('coSubmit');

    // Validation
    let valid = true;

    function showFieldErr(id, msg) {
      const el = document.getElementById(id);
      if (el) { el.textContent = msg; el.hidden = false; }
    }
    function hideFieldErr(id) {
      const el = document.getElementById(id);
      if (el) el.hidden = true;
    }

    hideFieldErr('coNameErr');
    hideFieldErr('coPhoneErr');
    hideFieldErr('coCityErr');
    hideFieldErr('coAddrErr');

    const name = (nameEl?.value || '').trim();
    const phone = (phoneEl?.value || '').trim();
    const city = (cityEl?.value || '').trim();
    const address = (addrEl?.value || '').trim();
    const notes = (notesEl?.value || '').trim();

    if (!name) { showFieldErr('coNameErr', T.checkoutNameReq); valid = false; }
    if (!phone) { showFieldErr('coPhoneErr', T.checkoutPhoneReq); valid = false; }
    if (!city) { showFieldErr('coCityErr', T.checkoutCityReq); valid = false; }
    if (!address) { showFieldErr('coAddrErr', T.checkoutAddrReq); valid = false; }

    if (!valid) return;

    // Disable button
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = T.checkoutSending || '...';
    }

    try {
      const res = await fetch(orderUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf(),
        },
        // No price is sent: the server prices every line from the database.
        // `name` stays only as a fallback for carts saved before product_id existed.
        body: JSON.stringify({
          items: cart.map((c) => ({
            product_id: c.id ? +c.id : null,
            name: c.name,
            qty: c.qty || 1,
          })),
          delivery_name: name,
          delivery_phone: phone,
          delivery_city: city,
          delivery_address: address,
          notes: notes || null,
        }),
      });

      const data = await res.json();

      if (res.ok && data.success) {
        // Clear cart
        cart = [];
        save();
        removeModal();
        // Redirect to success page
        window.location.href = data.redirect;
      } else {
        // Global (non-field) error → popup. The checkout modal stays open under
        // it (popup is z-[10000], the modal z-[9999]); per-field errors are inline.
        popup.error(data.message || T.checkoutError);
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = T.checkoutSubmit;
        }
      }
    } catch (e) {
      popup.error(T.checkoutError);
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = T.checkoutSubmit;
      }
    }
  }

  if (checkoutBtn) {
    checkoutBtn.addEventListener('click', () => {
      if (!cart.length) {
        // Show the empty state instead of alert()
        if (emptyEl) {
          emptyEl.hidden = false;
          const cta = emptyEl.querySelector('a');
          if (cta) cta.focus();
        }
        return;
      }
      // Ordering requires an account. Prompt for sign-in and come back to the cart so
      // the visitor can finish checking out instead of losing their place.
      if (!authUser) {
        openLoginModal({ redirectTo: window.location.href });
        return;
      }
      showCheckoutForm();
    });
  }

  save();
  render();
}
