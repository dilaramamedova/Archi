// Page module for "calculator-detailed" — the six-step estimate wizard.
//
// This file collects answers and renders what comes back. It does not price anything:
// every quantity, coefficient, package count and total is computed by the backend
// estimate engine (POST /api/calculator/estimate), which is the only place the work
// rates, material fallbacks and consumption norms exist. That split is the master
// specification's central rule — "ничего не хардкодить" — and it is what lets Archi
// add a work or change a rate in the admin panel without touching this module.
//
// The legacy `dc-*` class names and the .on/.done/.active/.exp/.off state classes are
// kept, because resources/css/pages/calculator-detailed.css is written against them.

import popup from '../shared/popup.js';
import { HANDOFF_KEY, formatMoney, readJson } from '../shared/calculator-pricing.js';

const CHK = '<svg><use href="#chk"/></svg>';

/** Utility class strings reused across templates; Tailwind scans ../js for them. */
const CLS = {
  editHint: 'text-[13px] font-medium text-black/50',
  colStack: 'flex flex-col gap-2.5',
  sumRow: 'flex items-baseline justify-between border-t border-black/10 pt-4',
  sumLabel: 'text-[15px] font-semibold',
  sumValue: 'text-[22px] font-bold whitespace-nowrap',
  sumUnit: 'text-sm text-black/50',
  cartRow: 'flex items-center justify-between gap-3 rounded-ds border border-black/10 px-5 py-4 max-[640px]:px-3.5',
  cartLabel: 'text-sm font-semibold',
  sideMeta: 'text-[13px] text-black/50',
  sideBanner: 'flex-col items-stretch gap-2.5',
  sideBannerRow: 'flex items-center justify-between',
  sideBannerHint: 'text-[13px] text-black/60',
  sideHint: 'mt-0.5 mb-1.5 text-[12px] leading-[1.4] text-black/45',
  addAll: 'p-4',
  note: 'text-[12px] leading-[1.5] text-black/45',
  warn: 'rounded-ds bg-amber-50 px-4 py-3 text-[13px] text-amber-900',
  trace: 'text-[12px] text-black/45',
  select: 'w-full rounded-ds border border-black/12 bg-white px-3 py-2 text-sm text-ink',
};

const esc = (s) =>
  String(s == null ? '' : s).replace(
    /[&<>"']/g,
    (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c],
  );

/** Laravel-style :placeholder substitution on the raw templates from Blade. */
const tr = (tpl, vars) =>
  Object.keys(vars || {}).reduce((s, k) => s.split(`:${k}`).join(vars[k]), tpl || '');

/**
 * Numbers with a thousands separator and qəpik only where there are any (TZ §19).
 *
 * The line amount and the trace under it have to agree to the qəpik: a row reading
 * "474 ₼" above "59.29 m² × 8 ₼/m² = 474.34 ₼" looks like the calculator made a
 * mistake, even though neither figure is wrong.
 */
const num = (n) => {
  const value = Number(n) || 0;
  const whole = Math.abs(value - Math.round(value)) < 0.005;
  const [int, frac] = (whole ? String(Math.round(value)) : value.toFixed(2)).split('.');

  // Group only the integer part — "1 234.56", never "1 234.5 6".
  return int.replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + (frac ? `.${frac}` : '');
};

/** Quantities: same rule, no currency. */
const qty = (n) => num(n);

export default function init() {
  const page = document.getElementById('dcPage');
  if (! page) return;

  const T = readJson(page.dataset.i18n, null);
  const CONFIG = readJson(page.dataset.config, null);
  if (! T || ! CONFIG) return;

  const mainEl = document.getElementById('dcMain');
  const sideEl = document.getElementById('dcSide');
  const stepEl = document.getElementById('dcStepper');
  if (! mainEl || ! sideEl || ! stepEl) return;

  const prefill = readJson(page.dataset.prefill, {}) || {};
  const catalogUrl = page.dataset.urlCatalog || '#';

  /**
   * The visitor's cart, from BOTH places it lives. The storefront cart is
   * localStorage "archi-cart" — the only cart a guest has — while a logged-in
   * account also carries a server-side one (handed over by Blade). Merged by
   * product id, server first. Display uses these rows; the estimate itself sends
   * only ids and quantities, and the server re-prices everything from the catalog.
   */
  const mergeCart = (server) => {
    const seen = new Set(server.map((p) => p.id));
    const local = (readJson(localStorage.getItem('archi-cart'), []) || [])
      // A row without a usable product id is from an older storefront build and
      // cannot be priced — the server would have nothing to look up.
      .filter((c) => c && Number.isFinite(+c.id) && +c.id > 0 && ! seen.has(+c.id))
      .map((c) => ({
        id: Math.trunc(+c.id),
        name: c.name || '',
        price: +c.price || 0,
        unit: null,
        // The storefront saves the thumbnail with the cart row, so a guest's basket
        // shows the same picture here as it does on /cart.
        image: c.img || null,
        packageSize: null,
        quantity: Math.max(1, Math.ceil(+c.qty || 1)),
      }));

    return [...server, ...local];
  };

  let cartProducts = mergeCart(readJson(page.dataset.cart, []) || []);
  // Line and group amounts keep their qəpik so they match the trace beneath them.
  // Only the headline totals — the grand total and the side panel — are rounded to
  // whole manats, because the estimate as a whole is an approximation.
  const money = (n) => tr(T.money, { amount: num(n) });

  const STEPS = [T.steps.object, T.steps.rooms, T.steps.works, T.steps.materials, T.steps.cart, T.steps.summary];
  const STEP_CAPTIONS = [T.object.k, T.rooms.k, T.works.k, T.materials.k, T.cart.k, T.summary.k];

  const enums = CONFIG.enums;
  const finishesFor = (surface) => enums.finishes.filter((f) => f.surface === surface);
  const labelOf = (list, value) => (list.find((x) => x.value === value) || {}).label || value;

  const state = {
    step: 0,
    object: {
      objectType: enums.objectTypes[0]?.value || 'APARTMENT',
      area: CONFIG.quick.defaults.area,
      cityKey: (CONFIG.cities.find((c) => c.isDefault) || CONFIG.cities[0] || {}).key,
      floor: 1,
      totalFloors: 9,
      marketType: 'SECONDARY',
      condition: 'SHELL',
      hasFreightElevator: false,
      demolitionRequired: false,
      designProject: false,
      segment: CONFIG.quick.defaults.segment,
    },
    rooms: [],
    works: {},
    materials: {},
    modules: {
      hvac: { systemType: 'NONE', indoorUnits: 1, outdoorUnits: 1, routeMeters: 0, conditionedArea: 0, zones: 1 },
      lighting: { spots: 0, pendants: 0, chandeliers: 0 },
      services: { finalCleaning: true, techSupervisionMonths: 0, authorSupervisionMonths: 0 },
      demolition: { estimateDebris: true },
      logistics: { externalAccess: false, platformDays: 0 },
    },
    basket: { useCart: cartProducts.length > 0, excluded: [] },
    estimate: null,
    loading: false,
    error: null,
    editRoom: 0,
    summaryView: 'category',
    roomsTouched: false,
    demolitionTouched: false,
    conditionTouched: false,
  };

  /**
   * Continue the quick calculator: the URL query wins (the CTA carries it), then
   * whatever the mini panel left in storage. TZ §47 — the visitor never retypes.
   */
  function hydrateFromQuick() {
    const saved = readJson(localStorage.getItem(HANDOFF_KEY), {}) || {};
    const area = prefill.area ?? saved.area;
    const segment = prefill.segment ?? saved.segment;
    const type = prefill.renovationType ?? saved.renovationType;

    if (Number.isFinite(parseFloat(area))) state.object.area = parseFloat(area);
    if (enums.segments.some((s) => s.value === segment)) state.object.segment = segment;

    // White Box is a shell-and-core finish: the object arrives bare, and the visitor
    // said so one screen ago. Full renovation is normally an occupied flat.
    if (type === 'WHITE_BOX') state.object.condition = 'SHELL';
    else if (type === 'FULL_RENOVATION') state.object.condition = 'OLD_RENOVATION';

    state.object.demolitionRequired = state.object.condition === 'OLD_RENOVATION';
  }

  /**
   * A starting set of rooms so step 2 is never an empty screen. Which rooms belong
   * to which kind of object is the server's answer (ObjectType::defaultRoomTypes) —
   * a commercial fit-out must not open with a bedroom.
   */
  function seedRooms() {
    const area = state.object.area || 80;
    const type = enums.objectTypes.find((t) => t.value === state.object.objectType);
    const roomTypes = type?.defaultRoomTypes?.length ? type.defaultRoomTypes : ['OTHER'];

    // Weighted so the living space dominates and the wet rooms stay small; the
    // visitor corrects every dimension on the next screen anyway.
    const weights = roomTypes.map((roomType) =>
      roomType === 'BATHROOM' || roomType === 'WC' ? 0.5 : roomType === 'CORRIDOR' ? 0.8 : 1.5,
    );
    const totalWeight = weights.reduce((sum, w) => sum + w, 0) || 1;

    state.rooms = roomTypes.map((roomType, index) =>
      newRoom(roomType, (area * weights[index]) / totalWeight, index),
    );
  }

  function newRoom(roomType, roomArea, index) {
    // A plausible rectangle for the share of the area this room takes: the visitor
    // corrects the two dimensions, and every wall quantity follows from them.
    const side = Math.max(1.5, Math.sqrt(Math.max(roomArea, 4)));
    const wet = (enums.roomTypes.find((r) => r.value === roomType) || {}).isWet;

    return {
      id: `room-${index + 1}-${Math.random().toString(36).slice(2, 7)}`,
      name: labelOf(enums.roomTypes, roomType),
      roomType,
      length: Math.round(side * 1.2 * 10) / 10,
      width: Math.round((side / 1.2) * 10) / 10,
      height: CONFIG.settings.defaultRoomHeight,
      currentState: null, // null → the object condition seeds it on the server
      floorFinish: wet ? 'FLOOR_TILE' : 'LAMINATE',
      wallFinish: wet ? 'WALL_TILE' : 'PAINT',
      ceilingFinish: 'CEILING_PAINT',
      openings: [
        { type: 'DOOR', width: CONFIG.settings.defaultDoor.width, height: CONFIG.settings.defaultDoor.height, qty: 1, subtype: 'INTERIOR', installNewDoor: true },
        { type: 'WINDOW', width: CONFIG.settings.defaultWindow.width, height: CONFIG.settings.defaultWindow.height, qty: roomType === 'BATHROOM' || roomType === 'WC' || roomType === 'CORRIDOR' ? 0 : 1 },
      ],
      warmFloor: { type: wet ? 'ELECTRIC' : 'NONE', area: null },
      points: {},
    };
  }

  // ---------- the engine ----------------------------------------------------------

  function payload() {
    return {
      object: state.object,
      rooms: state.rooms.map((room) => ({
        ...room,
        openings: room.openings.filter((o) => o.qty > 0),
        warmFloor: room.warmFloor.type === 'NONE' ? { type: 'NONE' } : room.warmFloor,
      })),
      works: state.works,
      materials: state.materials,
      modules: state.modules,
      basket: {
        ...state.basket,
        // Ids and quantities only — the server re-reads every price from the
        // catalog, so nothing in localStorage can change an estimate.
        items: cartProducts.map((p) => ({ productId: p.id, quantity: p.quantity })),
      },
    };
  }

  // ---------- persistence ---------------------------------------------------------

  /** Everything the visitor answered, so leaving the page loses nothing. */
  const STATE_KEY = 'archi-detailed-calc';

  function persist() {
    try {
      localStorage.setItem(STATE_KEY, JSON.stringify({
        v: 1,
        step: state.step,
        object: state.object,
        rooms: state.rooms,
        works: state.works,
        materials: state.materials,
        modules: state.modules,
        basket: { useCart: state.basket.useCart, excluded: state.basket.excluded },
        roomsTouched: state.roomsTouched,
        demolitionTouched: state.demolitionTouched,
      }));
    } catch {
      // Storage blocked (private window) — the calculator still works, the
      // answers simply do not survive a page change.
    }
  }

  /** @returns {boolean} true when a previous session was restored */
  function restore() {
    const saved = readJson(localStorage.getItem(STATE_KEY), null);

    if (! saved || saved.v !== 1 || ! Array.isArray(saved.rooms) || ! saved.rooms.length) {
      return false;
    }

    state.object = { ...state.object, ...saved.object };
    state.rooms = saved.rooms;
    state.works = saved.works || {};
    state.materials = saved.materials || {};
    state.modules = { ...state.modules, ...saved.modules };
    state.basket.useCart = saved.basket?.useCart ?? state.basket.useCart;
    state.basket.excluded = Array.isArray(saved.basket?.excluded) ? saved.basket.excluded : [];
    state.step = Math.min(5, Math.max(0, +saved.step || 0));
    state.roomsTouched = !! saved.roomsTouched;
    state.demolitionTouched = !! saved.demolitionTouched;
    state.editRoom = 0;

    return true;
  }

  function resetAll() {
    popup.confirm(T.alerts.reset_confirm, {
      confirmText: T.actions.reset,
      danger: true,
      onConfirm: () => {
        try {
          localStorage.removeItem(STATE_KEY);
        } catch {
          // nothing stored — the reload below still restarts the wizard
        }
        // A clean restart, with the URL query dropped so old hand-over
        // parameters do not resurrect the answers that were just discarded.
        window.location.href = window.location.pathname;
      },
    });
  }

  let pending = 0;
  let inflight = null;

  /**
   * Recalculate. Debounced because a room dimension is typed digit by digit, and
   * every keystroke would otherwise be its own round trip; the previous request is
   * abandoned so a slow one can never overwrite a newer result.
   */
  function recalculate({ immediate = false } = {}) {
    // Every state mutation funnels through here, so this is also the save point.
    persist();

    clearTimeout(pending);

    pending = setTimeout(async () => {
      inflight?.abort();
      const controller = new AbortController();
      inflight = controller;

      state.loading = true;
      state.error = null;
      renderSide();

      try {
        const response = await fetch(page.dataset.urlEstimate, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': page.dataset.csrf || '',
          },
          body: JSON.stringify(payload()),
          signal: controller.signal,
        });

        if (! response.ok) throw new Error(`HTTP ${response.status}`);

        state.estimate = await response.json();
      } catch (error) {
        if (error.name === 'AbortError') return;
        // The specification is explicit: when the engine cannot be reached the
        // calculator says so rather than showing a stale or invented figure.
        state.error = T.error?.failed || '';
      } finally {
        if (inflight === controller) {
          state.loading = false;
          inflight = null;
          // The one automatic re-render: it must not snatch the field the visitor
          // is still typing into. See render().
          render({ deferWhileTyping: true });
        }
      }
    }, immediate ? 0 : 300);
  }

  /**
   * Re-read both carts and redraw. Needed when the basket changes without this
   * page being re-initialised: the visitor removes a product on /cart in another
   * tab (storage event fires here), or comes back to this page through the
   * browser's back/forward cache (pageshow with persisted=true) — in both cases
   * the list built at load time would keep showing the deleted product.
   */
  let refreshingCart = false;

  async function refreshCartProducts() {
    if (refreshingCart) return;
    refreshingCart = true;

    let server = [];
    if (page.dataset.authenticated === '1' && page.dataset.urlCartItems) {
      try {
        const response = await fetch(page.dataset.urlCartItems, { headers: { Accept: 'application/json' } });
        if (response.ok) server = (await response.json()).items || [];
      } catch {
        // Offline — merge the browser cart alone rather than keep a stale list.
      }
    }

    const next = mergeCart(server);
    const key = (list) => JSON.stringify(list.map((p) => [p.id, p.quantity]));
    refreshingCart = false;

    if (key(next) === key(cartProducts)) return;

    cartProducts = next;
    const ids = new Set(cartProducts.map((p) => p.id));
    state.basket.excluded = state.basket.excluded.filter((id) => ids.has(id));
    if (! cartProducts.length) state.basket.useCart = false;
    render();
    recalculate({ immediate: true });
  }

  window.addEventListener('pageshow', (event) => {
    if (event.persisted) refreshCartProducts();
  });
  window.addEventListener('storage', (event) => {
    if (event.key === 'archi-cart') refreshCartProducts();
  });

  const totals = () => state.estimate?.totals || null;
  const lines = () => state.estimate?.lines || [];
  const roomsDerived = () => state.estimate?.rooms || [];

  // ---------- shared markup -------------------------------------------------------

  function chips(groupKey, options, current, extra = '') {
    return (
      `<div class="dc-chips" ${extra} data-group="${groupKey}">` +
      options
        .map((o) => `<button type="button" class="dc-chip ${current === o.value ? 'on' : ''}" data-v="${esc(o.value)}">${esc(o.label)}</button>`)
        .join('') +
      '</div>'
    );
  }

  function select(name, options, current, attrs = '') {
    return (
      `<select class="${CLS.select}" ${attrs} data-select="${name}">` +
      options
        .map((o) => `<option value="${esc(o.value)}" ${o.value === current ? 'selected' : ''}>${esc(o.label)}</option>`)
        .join('') +
      '</select>'
    );
  }

  function field(label, inner) {
    return `<div class="dc-fb"><span>${esc(label)}</span><div class="ip">${inner}</div></div>`;
  }

  function numberInput(attrs, value, unit = '') {
    return `<input type="number" inputmode="decimal" min="0" ${attrs} value="${esc(value)}">${unit ? `<span class="u">${esc(unit)}</span>` : ''}`;
  }

  // ---------- steps ---------------------------------------------------------------

  function stepObject() {
    const t = T.object;
    const o = state.object;

    return `<div class="dc-sh"><div class="k">${esc(t.k)}</div><h2>${esc(t.h)}</h2></div>
  <div class="dc-group"><label>${esc(t.type)}</label>${chips('objectType', enums.objectTypes, o.objectType)}</div>
  <div class="dc-fields">
    ${field(t.area, numberInput('data-o="area"', o.area, T.units.m2))}
    <div class="dc-fb"><span>${esc(t.city)}</span><div class="ip">${select('cityKey', CONFIG.cities.map((c) => ({ value: c.key, label: c.name })), o.cityKey)}</div></div>
    ${field(t.floor, numberInput('data-o="floor" step="1"', o.floor))}
  </div>
  <div class="dc-group"><label>${esc(t.property)}</label>${chips('marketType', [
      { value: 'NEW_BUILD', label: t.properties.new },
      { value: 'SECONDARY', label: t.properties.used },
    ], o.marketType)}</div>
  <div class="dc-group"><label>${esc(t.condition)}</label>${chips('condition', enums.conditions, o.condition)}</div>
  <div class="dc-group"><label>${esc(t.segment)}</label>${chips('segment', enums.segments, o.segment)}</div>
  <div class="dc-group"><label>${esc(t.extra)}</label>
    <div class="dc-toggles">
      <div class="dc-tg ${o.hasFreightElevator ? 'on' : ''}" data-flag="hasFreightElevator"><span>${esc(t.elevator)}</span><div class="dc-sw"></div></div>
      <div class="dc-tg ${o.demolitionRequired ? 'on' : ''}" data-flag="demolitionRequired"><span>${esc(t.demolition)}</span><div class="dc-sw"></div></div>
      <div class="dc-tg ${o.designProject ? 'on' : ''}" data-flag="designProject"><span>${esc(t.design_needed)}</span><div class="dc-sw"></div></div>
    </div>
  </div>`;
  }

  /**
   * Rozetka, açar, su nöqtəsi… — the per-room point counts. The engine has always
   * billed them (rate × count) from the room type's defaults, but until this editor
   * existed the visitor could neither see nor change the counts their estimate was
   * built on. Only the keys that exist for this room type are shown, defaults
   * pre-filled, and one edited key never resets the others.
   */
  function pointsEditor(room, index) {
    const defaults = (enums.roomTypes.find((r) => r.value === room.roomType) || {}).defaultPoints || {};
    const keys = Object.keys(defaults);

    if (! keys.length) return '';

    const labels = T.points || {};
    const inputs = keys
      .map((key) => {
        const value = room.points?.[key] ?? defaults[key];

        return field(labels[key] || key, numberInput(`data-pt="${index}" data-pk="${esc(key)}" step="1"`, value));
      })
      .join('');

    return `<div class="dc-group"><label>${esc(T.rooms.points_label || '')}</label><div class="dc-fields">${inputs}</div></div>`;
  }

  function stepRooms() {
    const t = T.rooms;
    const derived = roomsDerived();

    const rooms = state.rooms
      .map((room, index) => {
        const measured = derived.find((r) => r.id === room.id);

        if (index !== state.editRoom) {
          // Name the figures. "Hamam otağı · 22.80 m²" leaves the reader guessing
          // whether that is the floor, the walls or the room's share of the flat.
          const floorArea = measured ? measured.floorArea : (room.length || 0) * (room.width || 0);
          // The separators are literal text, not margins: the row is read aloud by
          // screen readers and copied into WhatsApp, where CSS spacing does not exist.
          const summary = [
            '',
            `${qty(room.length)} × ${qty(room.width)} ${T.units.m}`,
            `${t.floor_short} ${qty(floorArea)} ${T.units.m2}`,
          ].join(' · ');

          return `<div class="dc-collapsed" data-open="${index}"><div><b>${esc(room.name)}</b><span class="a">${esc(summary)}</span></div><span class="${CLS.editHint}">${esc(t.edit)}</span></div>`;
        }

        const door = room.openings.find((o) => o.type === 'DOOR') || {};
        const win = room.openings.find((o) => o.type === 'WINDOW') || {};
        const removable = state.rooms.length > 1 ? `data-rm="${index}"` : 'disabled';

        // Every derived quantity is shown back to the visitor: the specification asks
        // for real opening sizes precisely because NetWallArea cannot be guessed from
        // a door count, and seeing the result is what makes that worth typing.
        const derivedLine = measured
          ? `<div class="${CLS.trace}">${esc(tr(t.derived, {
              floor: qty(measured.floorArea),
              wall: qty(measured.netWallArea),
              skirting: qty(measured.skirtingLength),
            }))}</div>`
          : '';

        return `<div class="dc-room exp">
      <div class="dc-room-h"><b>${esc(room.name)}</b><button class="rm" ${removable}>${esc(t.remove)}</button></div>
      <div class="dc-group"><label>${esc(t.type)}</label>${chips('roomType', enums.roomTypes.map((r) => ({ value: r.value, label: r.label })), room.roomType, `data-ri="${index}"`)}</div>
      <div class="dc-fields">
        ${field(t.length, numberInput(`data-r="${index}" data-f="length" step="0.1"`, room.length, T.units.m))}
        ${field(t.width, numberInput(`data-r="${index}" data-f="width" step="0.1"`, room.width, T.units.m))}
        <div class="dc-fb dc-fb-sm"><span>${esc(t.height)}</span><div class="ip">${numberInput(`data-r="${index}" data-f="height" step="0.05"`, room.height, T.units.m)}</div></div>
        <div class="dc-fb dc-fb-sm"><span>${esc(t.doors)}</span><div class="ip">${numberInput(`data-op="${index}" data-ot="DOOR" data-of="qty" step="1"`, door.qty ?? 0)}</div></div>
        <div class="dc-fb dc-fb-sm"><span>${esc(t.windows)}</span><div class="ip">${numberInput(`data-op="${index}" data-ot="WINDOW" data-of="qty" step="1"`, win.qty ?? 0)}</div></div>
        ${field(t.door_width, numberInput(`data-op="${index}" data-ot="DOOR" data-of="width" step="0.05"`, door.width ?? 0, T.units.m))}
        ${field(t.door_height, numberInput(`data-op="${index}" data-ot="DOOR" data-of="height" step="0.05"`, door.height ?? 0, T.units.m))}
        ${field(t.window_width, numberInput(`data-op="${index}" data-ot="WINDOW" data-of="width" step="0.05"`, win.width ?? 0, T.units.m))}
        ${field(t.window_height, numberInput(`data-op="${index}" data-ot="WINDOW" data-of="height" step="0.05"`, win.height ?? 0, T.units.m))}
      </div>
      ${derivedLine}
      ${pointsEditor(room, index)}
      <div class="dc-tg ${door.installNewDoor ? 'on' : ''}" data-newdoor="${index}"><span>${esc(t.install_door)}</span><div class="dc-sw"></div></div>
      <div class="dc-group"><label>${esc(t.state)}</label>${chips('currentState', enums.surfaceStates, room.currentState, `data-ri="${index}"`)}</div>
      <div class="dc-group"><label>${esc(t.floor_cover)}</label>${chips('floorFinish', finishesFor('FLOOR'), room.floorFinish, `data-ri="${index}"`)}</div>
      <div class="dc-group"><label>${esc(t.wall_cover)}</label>${chips('wallFinish', finishesFor('WALL'), room.wallFinish, `data-ri="${index}"`)}</div>
      <div class="dc-group"><label>${esc(t.ceiling_cover)}</label>${chips('ceilingFinish', finishesFor('CEILING'), room.ceilingFinish, `data-ri="${index}"`)}</div>
      <div class="dc-tg ${room.warmFloor.type !== 'NONE' ? 'on' : ''}" data-warm="${index}"><span>${esc(t.heating)}</span><div class="dc-sw"></div></div>
      </div>`;
      })
      .join('');

    return `<div class="dc-sh"><div class="k">${esc(t.k)}</div><h2>${esc(t.h)}</h2><div class="d">${esc(t.d)}</div></div>
  ${rooms}<button class="dc-add" id="dcAddRoom">${esc(t.add)}</button>`;
  }

  /**
   * The works step. The dependency engine has already proposed the technological
   * chain for every surface; the visitor edits that list. No price is shown here —
   * the specification is explicit about it, and the side panel carries the money.
   */
  function stepWorks() {
    const t = T.works;
    const suggested = state.estimate?.suggestedWorks || [];
    const byId = new Map(CONFIG.works.map((w) => [w.workId, w]));
    const active = new Set(lines().filter((l) => l.lineType !== 'MATERIAL').map((l) => l.itemId));

    const groups = {};

    suggested.forEach((workId) => {
      const work = byId.get(workId);
      if (! work) return;

      (groups[work.category] ??= []).push(work);
    });

    const cards = Object.entries(groups)
      .map(([category, works]) => {
        const rows = works
          .map((work) => {
            const on = state.works[work.workId]?.selected !== false && active.has(work.workId);
            return `<div class="dc-wr ${on ? 'on' : ''}" data-w="${esc(work.workId)}"><div class="lft"><div class="dc-cb">${CHK}</div><div class="nm">${esc(work.name)}</div></div><div class="pr">${esc(work.unit)}</div></div>`;
          })
          .join('');

        return `<div class="dc-group"><label>${esc(T.category?.[category.toLowerCase()] || category)}</label><div class="${CLS.colStack}">${rows}</div></div>`;
      })
      .join('');

    const removedSuggestion = Object.values(state.works).some((w) => w.selected === false);

    return `<div class="dc-sh"><div class="k">${esc(t.k)}</div><h2>${esc(t.h)}</h2><div class="d">${esc(t.d)}</div></div>
  <p class="${CLS.note}">${esc(t.no_prices)}</p>
  ${removedSuggestion ? `<div class="${CLS.warn}">${esc(t.removed_warning)}</div>` : ''}
  ${cards}
  ${modulesBlock()}`;
  }

  /** Engineering modules open only once they are relevant (master spec §20). */
  function modulesBlock() {
    const m = state.modules;
    const hvac = m.hvac;
    const estimated = enums.hvacSystems.find((s) => s.value === hvac.systemType)?.precisionClass === 'ESTIMATED';

    const hvacInputs = hvac.systemType === 'NONE'
      ? ''
      : estimated
        ? `<div class="dc-fields">
             ${field(T.modules.hvac_area, numberInput('data-m="hvac.conditionedArea"', hvac.conditionedArea || '', T.units.m2))}
           </div>
           <p class="${CLS.note}">${esc(T.notice.estimated)}</p>`
        : `<div class="dc-fields">
             ${field(T.modules.hvac_units, numberInput('data-m="hvac.indoorUnits" step="1"', hvac.indoorUnits))}
             ${hvac.systemType === 'MULTI_SPLIT'
               ? field(T.modules.hvac_outdoor, numberInput('data-m="hvac.outdoorUnits" step="1"', hvac.outdoorUnits || 1))
               : ''}
             ${field(T.modules.hvac_route, numberInput('data-m="hvac.routeMeters"', hvac.routeMeters, T.units.m))}
           </div>`;

    return `<div class="dc-group"><label>${esc(T.modules.hvac)}</label>
    ${chips('hvacSystem', enums.hvacSystems.map((s) => ({ value: s.value, label: s.label })), hvac.systemType)}
    ${hvacInputs}
  </div>
  <div class="dc-group"><label>${esc(T.modules.lighting)}</label>
    <div class="dc-fields">
      ${field(T.modules.lighting_spots, numberInput('data-m="lighting.spots" step="1"', m.lighting.spots))}
      ${field(T.modules.lighting_pendants, numberInput('data-m="lighting.pendants" step="1"', m.lighting.pendants))}
      ${field(T.modules.lighting_chandeliers, numberInput('data-m="lighting.chandeliers" step="1"', m.lighting.chandeliers))}
    </div>
  </div>
  <div class="dc-group"><label>${esc(T.modules.services)}</label>
    <div class="dc-toggles">
      <div class="dc-tg ${m.services.finalCleaning ? 'on' : ''}" data-mflag="services.finalCleaning"><span>${esc(T.modules.final_cleaning)}</span><div class="dc-sw"></div></div>
    </div>
  </div>`;
  }

  /**
   * Materials. Every requirement the engine derived is listed with its quantity and
   * the three ways it can be priced (master spec §12): a real catalog product, the
   * category's average price for the chosen segment, or "I will buy it myself".
   */
  function stepMaterials() {
    const t = T.materials;

    const rows = lines()
      .filter((line) => line.lineType === 'MATERIAL' && line.meta?.materialCategoryId)
      .map((line) => {
        const categoryId = line.meta.materialCategoryId;
        const selection = state.materials[categoryId] || { mode: 'FALLBACK_SEGMENT' };
        const options = [
          { value: 'FALLBACK_SEGMENT', label: t.mode.fallback },
          ...cartProducts.map((p) => ({ value: `PRODUCT:${p.id}`, label: `${p.name} — ${money(p.price)}` })),
          { value: 'EXCLUDED', label: t.mode.excluded },
        ];
        const current = selection.mode === 'CATALOG_PRODUCT' ? `PRODUCT:${selection.productId}` : selection.mode;
        const packages = line.meta.packageQty ? ` · ${tr(t.packages, { qty: line.meta.packageQty })}` : '';

        return `<div class="dc-mr">
      <div class="ml"><b>${esc(line.name)}</b><span>${esc(tr(t.required, { qty: qty(line.quantity), unit: line.unit }))}${esc(packages)}</span></div>
      <div class="mr-r">
        <span class="price">${esc(line.unitPrice === null ? T.notice.price_missing : money(line.lineTotal))}</span>
        ${select(`material:${categoryId}`, options, current)}
      </div>
    </div>`;
      })
      .join('');

    // "Səbətinizdəki 0 məhsul…" is not information, it is noise — the banner and
    // its toggle only appear when there actually is a basket to include.
    const cartBanner = cartProducts.length
      ? `<div class="dc-banner"><b>${esc(tr(t.banner, { count: cartProducts.length }))}</b><div class="dc-tg grn ${state.basket.useCart ? 'on' : ''}" data-basket="useCart"><span>${esc(T.side.use_in_calc)}</span><div class="dc-sw"></div></div></div>`
      : '';

    return `<div class="dc-sh"><div class="k">${esc(t.k)}</div><h2>${esc(t.h)}</h2><div class="d">${esc(t.d)}</div></div>
  ${cartBanner}
  ${rows || `<p class="${CLS.note}">${esc(t.not_selected)}</p>`}
  <a class="dc-pick" href="${esc(catalogUrl)}">${esc(t.pick)}</a>`;
  }

  function stepCart() {
    const t = T.cart;

    if (! cartProducts.length) {
      return `<div class="dc-sh"><div class="k">${esc(t.k)}</div><h2>${esc(t.h)}</h2></div>
    <p class="${CLS.note}">${esc(T.materials.d)}</p>
    <a class="dc-pick" href="${esc(catalogUrl)}">${esc(T.materials.pick)}</a>`;
    }

    const rows = cartProducts
      .map((product) => {
        const excluded = state.basket.excluded.includes(product.id);
        const line = lines().find((l) => l.itemId === `CART_${product.id}`);
        // The same product may already be priced as the material for a surface, in
        // which case it is deliberately absent from the cart lines. Saying so is the
        // difference between "the calculator handled it" and "the calculator lost it".
        const asMaterial = ! line && ! excluded
          ? lines().find((l) => l.meta?.productId === product.id)
          : null;

        const right = asMaterial
          ? `<div class="p">${esc(money(asMaterial.lineTotal))}</div>`
            + `<span class="dc-badge">${esc(t.counted_as_material)}</span>`
          : `<div class="p">${esc(money(line ? line.lineTotal : product.price * product.quantity))}</div>`;

        const detail = asMaterial
          ? `${qty(asMaterial.quantity)} ${asMaterial.unit} · ${asMaterial.name}`
          : `${qty(line ? line.quantity : product.quantity)} × ${money(line?.unitPrice ?? product.price)}`;

        const thumb = product.image
          ? `<img class="dc-thumb" src="${esc(product.image)}" alt="" loading="lazy">`
          : '<div class="dc-thumb"></div>';

        return `<div class="dc-cart-row ${excluded ? 'off' : ''}" data-c="${product.id}">
      <div class="cbx">${CHK}</div>${thumb}
      <div class="info"><b>${esc(line?.name || product.name)}</b><div class="calc">${esc(detail)}</div></div>
      <div class="rr">${right}</div>
    </div>`;
      })
      .join('');

    const included = cartProducts.length - state.basket.excluded.length;
    // Only the rows actually billed as cart items; a product counted as a material
    // belongs to the materials subtotal, not this one.
    const cartTotal = lines()
      .filter((l) => l.meta?.fromCart)
      .reduce((sum, l) => sum + l.lineTotal, 0);

    return `<div class="dc-sh"><div class="k">${esc(t.k)}</div><h2>${esc(t.h)}</h2><div class="d">${esc(t.d)}</div></div>
  ${rows}
  <div class="${CLS.cartRow}"><b class="${CLS.cartLabel}">${esc(tr(t.summary, { count: included }))}</b><span class="${CLS.sumValue}">${formatMoney(cartTotal)} <span class="${CLS.sumUnit}">${esc(T.currency)}</span></span></div>`;
  }

  /**
   * The final estimate. Every group opens down to the individual lines, each with the
   * short trace the engine produced — "184 m² × 8 AZN = 1 472 AZN" — because a client
   * who cannot see where a number came from does not believe it (master spec §16.1).
   */
  function stepSummary() {
    const t = T.summary;
    const e = state.estimate;

    if (! e) return `<div class="dc-sh"><h2>${esc(T.side.calculating)}</h2></div>`;

    const grouped = state.summaryView === 'room'
      ? groupByRoom()
      : groupByCategory();

    const max = Math.max(1, ...grouped.map((g) => g.total));

    const blocks = grouped
      .map((group) => {
        const rows = group.lines
          // In the category view the same work repeats once per room ("Divarların
          // suvanması" × 4) and reads like a duplicate; the room name says why.
          .map((line) => {
            // Only rendered when there is a room to name — an empty span would
            // still be a styled element in the layout.
            const room = state.summaryView === 'category' && line.roomName
              ? `<span class="dc-lroom"> · ${esc(line.roomName)}</span>`
              : '';

            return `<div class="row"><div class="top"><span class="l">${esc(line.name)}${line.precisionClass === 'ESTIMATED' ? ' *' : ''}${room}</span><span class="v">${esc(line.unitPrice === null ? T.notice.price_missing : money(line.lineTotal))}</span></div><div class="${CLS.trace}">${esc(line.trace)}</div></div>`;
          })
          .join('');

        return `<div class="dc-bd">
      <div class="row"><div class="top"><span class="l"><b>${esc(group.label)}</b></span><span class="v"><b>${esc(money(group.total))}</b></span></div>
      <div class="dc-bar ${group.total === max ? 'hi' : ''}"><i style="width:${Math.max(4, Math.round((group.total / max) * 100))}%"></i></div></div>
      ${rows}
    </div>`;
      })
      .join('');

    const warnings = (e.warnings || [])
      .map((w) => {
        if (w.code === 'PRICE_MISSING') return `${T.notice.price_missing}: ${w.name}`;
        if (w.code === 'LOGISTICS_INCOMPLETE') return T.notice.logistics_incomplete;
        if (w.code === 'AREA_MISMATCH') return tr(T.notice.area_mismatch, { deviation: w.deviationPercent });
        return null;
      })
      .filter(Boolean);

    return `<div class="dc-sh"><div class="k">${esc(t.k)}</div><h2>${esc(tr(t.h, { area: qty(e.derived.TOTAL_FLOOR_AREA || 0) }))}</h2><div class="d">${esc(t.d)}</div></div>
  <div class="dc-chips" data-group="summaryView">
    <button type="button" class="dc-chip ${state.summaryView === 'category' ? 'on' : ''}" data-v="category">${esc(t.by_category)}</button>
    <button type="button" class="dc-chip ${state.summaryView === 'room' ? 'on' : ''}" data-v="room">${esc(t.by_room)}</button>
  </div>
  ${warnings.length ? `<div class="${CLS.warn}">${warnings.map(esc).join('<br>')}</div>` : ''}
  ${blocks}
  ${e.hasEstimatedLines ? `<p class="${CLS.note}">* ${esc(T.notice.estimated)}</p>` : ''}
  <div class="dc-total"><b>${esc(t.total)}</b><div class="v"><b>${num(e.totals.grandTotalRounded)}</b><span> ${esc(T.currency_code)}</span></div></div>`;
  }

  function groupByCategory() {
    const groups = new Map();

    lines().forEach((line) => {
      const key = line.category;
      const group = groups.get(key) || { label: T.category?.[key.toLowerCase()] || key, total: 0, lines: [] };

      group.total = Math.round((group.total + line.lineTotal) * 100) / 100;
      group.lines.push(line);
      groups.set(key, group);
    });

    return [...groups.values()].sort((a, b) => b.total - a.total);
  }

  function groupByRoom() {
    const groups = new Map();

    lines().forEach((line) => {
      const key = line.roomId || '_object';
      const group = groups.get(key) || { label: line.roomName || T.steps.object, total: 0, lines: [] };

      group.total = Math.round((group.total + line.lineTotal) * 100) / 100;
      group.lines.push(line);
      groups.set(key, group);
    });

    return [...groups.values()].sort((a, b) => b.total - a.total);
  }

  // ---------- side panel ----------------------------------------------------------

  /**
   * The running estimate. Every row that carries money is listed and the rows add up
   * to the figure above them — a panel whose parts do not sum to its own total is
   * the fastest way to lose a client's trust. Rows worth 0 ₼ are shown only when the
   * zero itself means something the visitor should act on (see logistics below).
   */
  function sideSummary(total) {
    const logisticsIncomplete = state.estimate?.logistics?.complete === false;

    const rows = [
      { label: T.side.works, value: total.works },
      { label: T.side.materials, value: total.materials },
      { label: T.side.services, value: total.services },
      {
        label: T.side.logistics,
        value: total.logistics,
        // 0 ₼ here is not "free delivery" — it is "we cannot know yet", and the
        // reason is that no chosen product carries a weight.
        hint: total.logistics === 0 && logisticsIncomplete ? T.side.logistics_unknown : null,
        show: total.logistics > 0 || logisticsIncomplete,
      },
      {
        label: tr(T.side.contingency, { percent: num(total.contingencyPercent) }),
        value: total.contingency,
      },
      { label: tr(T.side.vat, { percent: num(CONFIG.settings.vatPercent) }), value: total.vat },
    ]
      .filter((row) => (row.show ?? row.value > 0))
      .map((row) => `<div class="r"><span>${esc(row.label)}</span><b>${esc(money(row.value))}</b></div>`
        + (row.hint ? `<div class="${CLS.sideHint}">${esc(row.hint)}</div>` : ''))
      .join('');

    return `<div class="big"><b>${esc(tr(T.approx, { amount: num(total.grandTotalRounded) }))}</b><span>${esc(T.currency_code)}</span></div>
     <div class="${CLS.sideMeta}">${esc(tr(T.side.meta, { area: qty(state.estimate.derived.TOTAL_FLOOR_AREA || 0), rooms: state.rooms.length }))}</div>
     <div class="rows">
       ${rows}
       <div class="r total"><span>${esc(T.side.grand_total)}</span><b>${esc(money(total.grandTotal))}</b></div>
     </div>
     ${hasOnlyFallbackMaterials() ? `<div class="${CLS.sideMeta}">${esc(T.side.estimate_only)}</div>` : ''}`;
  }

  function renderSide() {
    if (state.step === 5) return renderSummaryActions();

    const s = T.side;
    const total = totals();

    const body = state.error
      ? `<div class="${CLS.warn}">${esc(state.error)}</div>`
      : ! total || state.loading
        ? `<div class="${CLS.sideMeta}">${esc(s.calculating)}</div>`
        : sideSummary(total);

    sideEl.innerHTML = `
  <div class="dc-sum">
    <div class="lbl">${esc(s.label)}</div>
    ${body}
  </div>
  ${
    state.step >= 3 && cartProducts.length
      ? `<div class="dc-banner ${CLS.sideBanner}"><b>${esc(tr(s.cart_banner, { count: cartProducts.length }))}</b><div class="${CLS.sideBannerRow}"><span class="${CLS.sideBannerHint}">${esc(s.use_in_calc)}</span><div class="dc-tg grn bare ${state.basket.useCart ? 'on' : ''}" data-basket="useCart"><div class="dc-sw"></div></div></div></div>`
      : ''
  }
  <div class="dc-nav">
    ${state.step > 0 ? `<button class="dc-back" id="dcBack">${esc(s.back)}</button>` : ''}
    <button class="dc-next" id="dcNextBtn">${esc(state.step === 4 ? s.calculate : s.next)}  →</button>
  </div>`;

    sideEl.querySelector('#dcBack')?.addEventListener('click', () => goToStep(state.step - 1));
    sideEl.querySelector('#dcNextBtn')?.addEventListener('click', () => goToStep(Math.min(state.step + 1, 5)));
    bindBasketToggles(sideEl);
  }

  const hasOnlyFallbackMaterials = () =>
    lines().some((l) => l.source === 'FALLBACK_CATEGORY_PRICE') &&
    ! lines().some((l) => l.source === 'CATALOG_PRICE');

  /**
   * "Bütün materialları səbətə at" is switched off for now at Archi's request: the
   * quantities it would push are only as good as the packaging data on the products,
   * and most of the catalog has none yet. Set ADD_ALL_TO_CART_ENABLED to true to
   * bring it back — addAllToCart() below is intact.
   */
  const ADD_ALL_TO_CART_ENABLED = false;

  function renderSummaryActions() {
    const a = T.actions;

    sideEl.innerHTML = `
  <div class="dc-act">
    ${ADD_ALL_TO_CART_ENABLED ? `<button class="dc-next ${CLS.addAll}" id="dcAddAll">${esc(a.add_all)}</button>` : ''}
    <div class="dc-sec"><button id="dcPdf">${esc(a.pdf)}</button><button id="dcWa">${esc(a.whatsapp)}</button></div>
    <button class="dc-outline" id="dcTurnkey">${esc(a.turnkey)}</button>
  </div>`;

    // The PDF is generated server-side from a frozen snapshot, so the figures in
    // the file can never differ from the ones on this screen — even if an admin
    // edits a rate a second later. The click waits for the snapshot, then starts
    // the download.
    sideEl.querySelector('#dcPdf').addEventListener('click', async (event) => {
      const button = event.currentTarget;
      if (button.disabled) return;
      button.disabled = true;

      const result = await submitRequest('PDF');
      button.disabled = false;

      if (result?.snapshotUuid && page.dataset.urlSnapshotPdf) {
        window.location.href = page.dataset.urlSnapshotPdf.replace('__UUID__', result.snapshotUuid);
      } else {
        popup.info(T.error?.failed || '');
      }
    });

    sideEl.querySelector('#dcAddAll')?.addEventListener('click', addAllToCart);
    sideEl.querySelector('#dcTurnkey').addEventListener('click', requestTurnkey);

    sideEl.querySelector('#dcWa').addEventListener('click', () => {
      const total = totals();
      if (! total) return;

      submitRequest('WHATSAPP');
      window.open(
        `https://wa.me/?text=${encodeURIComponent(tr(T.alerts.whatsapp, { total: num(total.grandTotalRounded) }))}`,
        '_blank',
      );
    });
  }

  /**
   * Freeze the estimate server-side. PDF, WhatsApp and a turnkey order all have to
   * quote the same sum even if a rate is edited a minute later, and only a stored
   * snapshot can guarantee that (master spec §22).
   */
  const jsonHeaders = () => ({
    'Content-Type': 'application/json',
    Accept: 'application/json',
    'X-CSRF-TOKEN': page.dataset.csrf || '',
  });

  /**
   * Store the answers and freeze the estimate, then raise a request of the given
   * type so it appears in the admin panel.
   *
   * @returns {Promise<object|null>} the created request, or null when it failed
   */
  async function submitRequest(type, extra = {}) {
    if (! state.estimate) return null;

    try {
      const created = await fetch(page.dataset.urlSessions, {
        method: 'POST',
        headers: jsonHeaders(),
        body: JSON.stringify({ ...payload(), currentStep: 'SUMMARY' }),
      });

      if (! created.ok) throw new Error(`HTTP ${created.status}`);

      const { uuid } = await created.json();

      const raised = await fetch(`${page.dataset.urlSessions}/${uuid}/request`, {
        method: 'POST',
        headers: jsonHeaders(),
        body: JSON.stringify({ type, ...extra }),
      });

      if (! raised.ok) throw new Error(`HTTP ${raised.status}`);

      return await raised.json();
    } catch {
      return null;
    }
  }

  /**
   * The turnkey request is the one action that is a real lead, so it asks for a name
   * and a phone number first — everything else on this page deliberately collects
   * nothing (TZ §46).
   */
  async function requestTurnkey() {
    const name = window.prompt(T.alerts.turnkey_name);
    if (name === null) return;

    const phone = window.prompt(T.alerts.turnkey_phone);
    if (phone === null) return;

    if (! String(phone).trim()) {
      popup.info(T.alerts.turnkey_phone_required);

      return;
    }

    const result = await submitRequest('TURNKEY', { name: String(name).trim(), phone: String(phone).trim() });

    if (result) popup.success(T.alerts.turnkey);
    else popup.info(T.error?.failed || '');
  }

  /** Add every priced material line to the cart, in the quantity the estimate needs. */
  async function addAllToCart() {
    // The cart belongs to an account; a guest is told so rather than watching six
    // silent 401s go by.
    if (page.dataset.authenticated !== '1') {
      popup.info(T.modules.login_required, {
        buttons: [{ text: T.actions.add_all, variant: 'primary', href: page.dataset.urlLogin }],
      });

      return;
    }

    const productLines = lines().filter((l) => l.meta?.productId && ! l.meta.fromCart);

    for (const line of productLines) {
      // Sequential on purpose: the cart endpoint is an upsert per product, and a
      // parallel burst of six writes on the same row is how duplicates appear.
      // eslint-disable-next-line no-await-in-loop
      await fetch(page.dataset.urlCart, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': page.dataset.csrf || '',
        },
        body: JSON.stringify({ product_id: line.meta.productId, quantity: Math.max(1, Math.round(line.quantity)) }),
      }).catch(() => null);
    }

    // The server cart just changed; the cart step should show the new rows without
    // waiting for a reload.
    refreshCartProducts();

    const gotoCart = page.dataset.gotoCart;
    const cont = page.dataset.continue;

    popup.success(
      T.alerts.added,
      gotoCart && cont
        ? { buttons: [{ text: gotoCart, variant: 'primary', href: '/cart' }, { text: cont, variant: 'outline' }] }
        : undefined,
    );
  }

  // ---------- stepper & wiring ----------------------------------------------------

  function renderStepper() {
    const strip = STEPS.map((name, i) => {
      const cls = i < state.step ? 'done' : i === state.step ? 'active' : '';
      const inner = `<div class="dc-step ${cls}" data-i="${i}"><div class="c">${i < state.step ? '✓' : i + 1}</div><div class="l">${esc(name)}</div></div>`;
      return inner + (i < STEPS.length - 1 ? '<div class="dc-conn"></div>' : '');
    }).join('');

    stepEl.innerHTML = `<div class="dc-strip">${strip}</div><div class="dc-stepcap">${esc(STEP_CAPTIONS[state.step] || '')}</div>`
      + `<button type="button" class="dc-reset" id="dcReset" title="${esc(T.alerts.reset_confirm)}">${esc(T.actions.reset)}</button>`;

    stepEl.querySelectorAll('.dc-step').forEach((el) =>
      el.addEventListener('click', () => {
        const i = +el.dataset.i;
        if (i <= state.step) goToStep(i);
      }),
    );

    stepEl.querySelector('#dcReset').addEventListener('click', resetAll);
  }

  function goToStep(i) {
    if (i === state.step) return;
    state.step = i;
    persist();
    render();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  /**
   * Is the visitor typing into a field of the current step?
   *
   * Only fields inside `mainEl` count. The side panel holds buttons, and the stepper
   * holds none, so rebuilding either one interrupts nothing.
   */
  function editingMainField() {
    const el = document.activeElement;

    return !! el
      && mainEl.contains(el)
      && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.tagName === 'SELECT');
  }

  // Set when an automatic re-render was skipped because a field had focus, so the
  // step knows it still owes itself a rebuild once the visitor leaves that field.
  let mainRenderPending = false;

  /**
   * @param {{deferWhileTyping?: boolean}} options
   *   `deferWhileTyping` is passed only by recalculate(): every other caller is a
   *   deliberate click that changes what the step must show — a new room, a removed
   *   line, a step change — and those must always rebuild.
   */
  function render({ deferWhileTyping = false } = {}) {
    renderStepper();

    // recalculate() lands ~300ms after the last keystroke and rebuilt the whole step
    // through innerHTML, which replaces the very <input> being typed into. The field
    // lost focus mid-word; worse, on `type="number"` a half-typed decimal reads back
    // as "" (the browser rejects "4." as an incomplete number), so the rebuild wiped
    // the entry as well. Nothing in the step is worth that: the results panel keeps
    // updating live, and the step's own markup waits for the field to be released.
    if (deferWhileTyping && editingMainField()) {
      mainRenderPending = true;
      renderSide();

      return;
    }

    mainEl.innerHTML = [stepObject, stepRooms, stepWorks, stepMaterials, stepCart, stepSummary][state.step]();
    bindMain();
    mainRenderPending = false;
    renderSide();
  }

  // `mainEl` outlives every render, so this is bound once here rather than inside
  // bindMain(). The timeout lets focus settle first: tabbing from one field to the
  // next fires focusout before focusin, and rebuilding in between would destroy the
  // field the visitor has just reached.
  mainEl.addEventListener('focusout', () => {
    setTimeout(() => {
      if (mainRenderPending && ! editingMainField()) render();
    }, 0);
  });

  function bindBasketToggles(root) {
    root.querySelectorAll('[data-basket]').forEach((el) =>
      el.addEventListener('click', () => {
        state.basket.useCart = ! state.basket.useCart;
        recalculate({ immediate: true });
        render();
      }),
    );
  }

  function bindMain() {
    // chip groups — object fields, room fields and the summary view switch
    mainEl.querySelectorAll('.dc-chips[data-group]').forEach((group) =>
      group.addEventListener('click', (event) => {
        const button = event.target.closest('.dc-chip');
        if (! button) return;

        const key = group.dataset.group;
        const value = button.dataset.v;
        const roomIndex = group.dataset.ri;

        if (key === 'summaryView') {
          state.summaryView = value;
          render();

          return;
        }

        if (roomIndex !== undefined) {
          const room = state.rooms[+roomIndex];
          room[key] = value;
          state.roomsTouched = true;

          if (key === 'roomType') {
            room.name = labelOf(enums.roomTypes, value);
            // The point counts belong to the old room type; keeping them would
            // give the new bathroom a living room's eight sockets.
            room.points = {};
          }
        } else if (key === 'hvacSystem') {
          state.modules.hvac.systemType = value;
        } else {
          state.object[key] = value;

          // A commercial fit-out should not keep the flat's bedroom, but only while
          // the visitor has not started editing the rooms themselves.
          if (key === 'objectType' && ! state.roomsTouched) seedRooms();

          // New build / second-hand seeds the condition — a new build arrives bare,
          // a resale carries someone's old renovation (master spec §7). Only until
          // the visitor picks a condition themselves.
          if (key === 'marketType' && ! state.conditionTouched) {
            state.object.condition = value === 'NEW_BUILD' ? 'SHELL' : 'OLD_RENOVATION';
          }

          if (key === 'condition') state.conditionTouched = true;

          // A flat with an old renovation in it needs stripping out; a bare new build
          // does not. The switch follows that until the visitor decides otherwise
          // (master spec §7).
          if ((key === 'condition' || key === 'marketType') && ! state.demolitionTouched) {
            state.object.demolitionRequired = state.object.condition === 'OLD_RENOVATION';
          }
        }

        recalculate({ immediate: true });
        render();
      }),
    );

    mainEl.querySelectorAll('select[data-select]').forEach((el) =>
      el.addEventListener('change', () => {
        const [kind, id] = el.dataset.select.split(':');

        if (kind === 'material') {
          if (el.value.startsWith('PRODUCT:')) {
            state.materials[id] = { mode: 'CATALOG_PRODUCT', productId: +el.value.split(':')[1] };
          } else {
            state.materials[id] = { mode: el.value };
          }
        } else if (kind === 'cityKey') {
          state.object.cityKey = el.value;
        }

        recalculate({ immediate: true });
      }),
    );

    mainEl.querySelectorAll('input[data-o]').forEach((el) =>
      el.addEventListener('input', () => {
        state.object[el.dataset.o] = +el.value || 0;

        // The seeded rooms exist to match the stated area. While the visitor has
        // not edited them, a new area re-seeds them — otherwise typing 80 over the
        // default 120 leaves 120 m² worth of rooms behind the visitor's back, and
        // the whole estimate is silently 50% too big.
        if (el.dataset.o === 'area' && ! state.roomsTouched && state.object.area > 0) {
          seedRooms();
        }

        recalculate();
      }),
    );

    mainEl.querySelectorAll('input[data-r]').forEach((el) =>
      el.addEventListener('input', () => {
        state.rooms[+el.dataset.r][el.dataset.f] = +el.value || 0;
        state.roomsTouched = true;
        recalculate();
      }),
    );

    mainEl.querySelectorAll('input[data-pt]').forEach((el) =>
      el.addEventListener('input', () => {
        const room = state.rooms[+el.dataset.pt];
        if (! room) return;

        room.points = { ...(room.points || {}), [el.dataset.pk]: Math.max(0, Math.trunc(+el.value || 0)) };
        state.roomsTouched = true;
        recalculate();
      }),
    );

    mainEl.querySelectorAll('input[data-op]').forEach((el) =>
      el.addEventListener('input', () => {
        const room = state.rooms[+el.dataset.op];
        const opening = room.openings.find((o) => o.type === el.dataset.ot);
        if (! opening) return;

        state.roomsTouched = true;
        opening[el.dataset.of] = +el.value || 0;
        recalculate();
      }),
    );

    mainEl.querySelectorAll('input[data-m]').forEach((el) =>
      el.addEventListener('input', () => {
        const [group, key] = el.dataset.m.split('.');
        state.modules[group][key] = +el.value || 0;
        recalculate();
      }),
    );

    mainEl.querySelectorAll('[data-flag]').forEach((el) =>
      el.addEventListener('click', () => {
        state.object[el.dataset.flag] = ! state.object[el.dataset.flag];

        // Once the visitor has an opinion about demolition, the condition chips stop
        // overwriting it.
        if (el.dataset.flag === 'demolitionRequired') state.demolitionTouched = true;

        recalculate({ immediate: true });
        render();
      }),
    );

    mainEl.querySelectorAll('[data-mflag]').forEach((el) =>
      el.addEventListener('click', () => {
        const [group, key] = el.dataset.mflag.split('.');
        state.modules[group][key] = ! state.modules[group][key];
        recalculate({ immediate: true });
        render();
      }),
    );

    mainEl.querySelectorAll('[data-newdoor]').forEach((el) =>
      el.addEventListener('click', () => {
        const door = state.rooms[+el.dataset.newdoor].openings.find((o) => o.type === 'DOOR');
        if (! door) return;

        door.installNewDoor = ! door.installNewDoor;
        recalculate({ immediate: true });
        render();
      }),
    );

    mainEl.querySelectorAll('[data-warm]').forEach((el) =>
      el.addEventListener('click', () => {
        const room = state.rooms[+el.dataset.warm];
        room.warmFloor = { type: room.warmFloor.type === 'NONE' ? 'ELECTRIC' : 'NONE', area: null };
        recalculate({ immediate: true });
        render();
      }),
    );

    // A suggested work can be removed; the warning above the list is the consequence.
    mainEl.querySelectorAll('.dc-wr[data-w]').forEach((el) =>
      el.addEventListener('click', () => {
        const workId = el.dataset.w;
        const on = state.works[workId]?.selected !== false;

        state.works[workId] = { selected: ! on };
        recalculate({ immediate: true });
        render();
      }),
    );

    mainEl.querySelectorAll('.dc-cart-row[data-c]').forEach((el) =>
      el.querySelector('.cbx').addEventListener('click', () => {
        const id = +el.dataset.c;
        const excluded = state.basket.excluded;

        state.basket.excluded = excluded.includes(id)
          ? excluded.filter((x) => x !== id)
          : [...excluded, id];

        recalculate({ immediate: true });
        render();
      }),
    );

    mainEl.querySelectorAll('[data-open]').forEach((el) =>
      el.addEventListener('click', () => {
        state.editRoom = +el.dataset.open;
        render();
      }),
    );

    mainEl.querySelectorAll('[data-rm]').forEach((el) =>
      el.addEventListener('click', (event) => {
        event.stopPropagation();
        state.rooms.splice(+el.dataset.rm, 1);
        state.editRoom = 0;
        recalculate({ immediate: true });
        render();
      }),
    );

    mainEl.querySelector('#dcAddRoom')?.addEventListener('click', () => {
      state.rooms.push(newRoom('OTHER', 12, state.rooms.length));
      state.editRoom = state.rooms.length - 1;
      recalculate({ immediate: true });
      render();
    });

    bindBasketToggles(mainEl);
  }

  // A returning visitor continues where they left off; only a fresh session is
  // seeded from the quick calculator's hand-over and the default room set. Explicit
  // URL parameters (the quick calculator's CTA) still win over a restored area and
  // segment — the visitor set them a moment ago, on purpose.
  const restored = restore();

  if (! restored) {
    hydrateFromQuick();
    seedRooms();
  } else {
    if (prefill.area !== undefined) state.object.area = prefill.area;
    if (prefill.segment !== undefined) state.object.segment = prefill.segment;
  }

  // ?step=1..6 opens the wizard on a given step. Used for deep links from support
  // material and for the admin panel's screenshots, which are captured from the real
  // page so they can never drift from what the visitor sees.
  const requestedStep = parseInt(new URLSearchParams(window.location.search).get('step'), 10);

  if (Number.isFinite(requestedStep)) {
    state.step = Math.min(STEPS.length - 1, Math.max(0, requestedStep - 1));
  }

  render();
  recalculate({ immediate: true });
}
