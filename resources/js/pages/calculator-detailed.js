// Page module for "calculator-detailed" — ported from the inline script of the old
// calculator-detailed.html. The whole wizard (stepper, current step, side panel) is
// built from templates here; the legacy `dc-*` class names and the .on/.done/.active/
// .exp/.off state classes are kept, because resources/css/pages/calculator-detailed.css
// is written against them. Every string and all demo data come from Blade as data-*
// JSON on #dcPage — nothing is hardcoded in this file.

const CHK = '<svg><use href="#chk"/></svg>';
const RESERVE_RATE = 0.1;
const DELIVERY_COST = 2000;

// Utility classes used inside the templates; Tailwind picks them up through the
// `@source "../js"` rule in app.css. They replace the inline styles of the old page.
const CLS = {
  editHint: 'text-[13px] font-medium text-black/50',
  colStack: 'flex flex-col gap-2.5',
  sumRow: 'flex items-baseline justify-between border-t border-black/10 pt-4',
  sumLabel: 'text-[15px] font-semibold',
  sumValue: 'text-[22px] font-bold',
  sumUnit: 'text-sm text-black/50',
  cartRow: 'flex items-center justify-between rounded-ds border border-black/10 px-5 py-4',
  cartLabel: 'text-sm font-semibold',
  sideMeta: 'text-[13px] text-black/50',
  sideBanner: 'flex-col items-stretch gap-2.5',
  sideBannerRow: 'flex items-center justify-between',
  sideBannerHint: 'text-[13px] text-black/60',
  addAll: 'p-4',
};

const fmt = (n) => Math.round(n).toLocaleString('ru-RU');

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
  const page = document.getElementById('dcPage');
  if (!page) return;

  const T = readJson(page.dataset.i18n, null);
  const D = readJson(page.dataset.defaults, null);
  if (!T || !D) return;

  const mainEl = document.getElementById('dcMain');
  const sideEl = document.getElementById('dcSide');
  const stepEl = document.getElementById('dcStepper');
  if (!mainEl || !sideEl || !stepEl) return;

  const STEPS = [
    T.steps.object,
    T.steps.rooms,
    T.steps.works,
    T.steps.materials,
    T.steps.cart,
    T.steps.summary,
  ];
  const WORKS = D.works;
  const money = (n) => tr(T.money, { amount: fmt(n) });
  const catalogUrl = page.dataset.urlCatalog || '#';
  // The step-1 "demolition needed" switch and this work item are the same decision.
  const DEMOLITION = WORKS.findIndex((w) => w.key === 'demolition');

  // Cart from localStorage ('archi-cart', written by the product and cart pages),
  // with the demo list from Blade as a fallback.
  function loadCart() {
    let c = [];
    try {
      c = JSON.parse(localStorage.getItem('archi-cart') || '[]');
    } catch (e) {
      // localStorage blocked — the demo cart below is used
    }
    if (!Array.isArray(c) || !c.length) c = D.cart;
    return c.map((x) => ({ included: true, ...x }));
  }

  let saved = {};
  try {
    saved = JSON.parse(localStorage.getItem('archi-quickcalc') || '{}');
  } catch (e) {
    // no quick-calculator result stored — the default area is used
  }

  const state = {
    step: 0,
    objV: D.objectTypes[0],
    area: saved.area || D.area,
    city: D.city,
    floor: D.floor,
    prop: D.propertyTypes[0],
    cond: D.conditions[0],
    design: false,
    drawings: false,
    demolition: true,
    rooms: D.rooms.map((r) => ({ ...r })),
    editRoom: 0,
    works: {},
    useCart: true,
    cart: loadCart(),
  };

  WORKS.forEach((w, i) => {
    if (state.works[i] === undefined) state.works[i] = w.on;
  });

  // ---------- compute ----------
  const totalArea = () => state.rooms.reduce((s, r) => s + (+r.area || 0), 0) || state.area;

  function bathrooms() {
    const re = new RegExp(T.bathroom_pattern, 'i');
    return state.rooms.filter((r) => re.test(r.name)).length || 1;
  }

  // Object type, property type and condition scale the labour, positionally parallel
  // to the chip lists they are picked from (see the *Factors arrays in Blade).
  function pick(list, factors, value) {
    const i = list.indexOf(value);
    return i < 0 ? 1 : factors[i];
  }

  const siteFactor = () =>
    pick(D.objectTypes, D.objectFactors, state.objV) *
    pick(D.propertyTypes, D.propertyFactors, state.prop) *
    pick(D.conditions, D.conditionFactors, state.cond);

  function worksCost() {
    const A = totalArea();
    const pts = Math.max(1, Math.round(A / 8));
    let s = 0;
    WORKS.forEach((w, i) => {
      if (!state.works[i]) return;
      s += w.unit === 'm2' ? w.price * A : w.unit === 'point' ? w.price * pts : w.price;
    });
    return Math.round(s * siteFactor());
  }

  // Both switches read "I already have it", so the fee applies while they are OFF.
  const servicesCost = () =>
    (state.design ? 0 : D.services.design) + (state.drawings ? 0 : D.services.drawings);

  const cartTotal = () =>
    state.useCart ? state.cart.filter((c) => c.included).reduce((s, c) => s + (+c.price || 0), 0) : 0;

  function computeEstimate() {
    const A = totalArea();
    const byCat = (cat) =>
      state.useCart
        ? state.cart.filter((x) => x.included && x.cat === cat).reduce((s, x) => s + x.price, 0)
        : 0;
    const finishCats = [D.cats.floor, D.cats.tile, D.cats.paint];
    const cartMat = state.useCart
      ? state.cart
          .filter((c) => c.included && finishCats.includes(c.cat))
          .reduce((s, c) => s + c.price, 0)
      : 0;

    const rough = Math.round(A * 150);
    const finish = cartMat || Math.round(A * 280);
    const plumbing = byCat(D.cats.plumbing) + bathrooms() * 1800;
    const electrical = Math.round(A * 52);
    const lighting = Math.round(A * 44);
    const doors = byCat(D.cats.doors) + state.rooms.length * 400;
    const works = worksCost();
    const extras = servicesCost();
    const sub =
      rough + finish + plumbing + electrical + lighting + doors + works + DELIVERY_COST + extras;
    const reserve = Math.round(sub * RESERVE_RATE);
    const L = T.summary.rows;
    const rows = [
      [L.rough, rough],
      [L.finish, finish],
      [L.plumbing, plumbing],
      [L.electrical, electrical],
      [L.lighting, lighting],
      [L.doors, doors],
      [L.works, works],
      [L.delivery, DELIVERY_COST],
      [L.services, extras],
      [L.reserve, reserve],
    ];
    return { rows, total: sub + reserve };
  }

  // ---------- render ----------
  function chips(key, opts) {
    return (
      `<div class="dc-chips" data-key="${key}">` +
      opts
        .map(
          (o) =>
            `<button class="dc-chip ${state[key] === o ? 'on' : ''}" data-v="${esc(o)}">${esc(o)}</button>`
        )
        .join('') +
      `</div>`
    );
  }

  function roomChips(field, index, opts, current) {
    return (
      `<div class="dc-chips" data-rk="${field}" data-ri="${index}">` +
      opts
        .map(
          (o) =>
            `<button class="dc-chip ${current === o ? 'on' : ''}" data-v="${esc(o)}">${esc(o)}</button>`
        )
        .join('') +
      `</div>`
    );
  }

  function renderStepper() {
    stepEl.innerHTML = STEPS.map((n, i) => {
      const cls = i < state.step ? 'done' : i === state.step ? 'active' : '';
      const inner = `<div class="dc-step ${cls}" data-i="${i}"><div class="c">${i < state.step ? '✓' : i + 1}</div><div class="l">${esc(n)}</div></div>`;
      return inner + (i < STEPS.length - 1 ? '<div class="dc-conn"></div>' : '');
    }).join('');
    stepEl.querySelectorAll('.dc-step').forEach((s) =>
      s.addEventListener('click', () => {
        const i = +s.dataset.i;
        if (i <= state.step) goToStep(i);
      })
    );
  }

  // Only a step change scrolls back up. `render()` also runs for every chip, switch and
  // tick inside a step, and scrolling there would throw the user out of a long list.
  function goToStep(i) {
    if (i === state.step) return;
    state.step = i;
    render();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function stepObject() {
    const t = T.object;
    return `<div class="dc-sh"><div class="k">${esc(t.k)}</div><h2>${esc(t.h)}</h2></div>
  <div class="dc-group"><label>${esc(t.type)}</label>${chips('objV', D.objectTypes)}</div>
  <div class="dc-fields">
    <div class="dc-fb"><span>${esc(t.area)}</span><div class="ip"><input id="dcArea" type="number" value="${esc(state.area)}"><span class="u">${esc(T.units.m2)}</span></div></div>
    <div class="dc-fb"><span>${esc(t.city)}</span><div class="ip"><input id="dcCity" value="${esc(state.city)}"></div></div>
    <div class="dc-fb"><span>${esc(t.floor)}</span><div class="ip"><input id="dcFloor" value="${esc(state.floor)}"></div></div>
  </div>
  <div class="dc-group"><label>${esc(t.property)}</label>${chips('prop', D.propertyTypes)}</div>
  <div class="dc-group"><label>${esc(t.condition)}</label>${chips('cond', D.conditions)}</div>
  <div class="dc-group"><label>${esc(t.extra)}</label>
    <div class="dc-toggles">
      <div class="dc-tg ${state.design ? 'on' : ''}" data-t="design"><span>${esc(t.design)}</span><div class="dc-sw"></div></div>
      <div class="dc-tg ${state.drawings ? 'on' : ''}" data-t="drawings"><span>${esc(t.drawings)}</span><div class="dc-sw"></div></div>
      <div class="dc-tg ${state.demolition ? 'on' : ''}" data-t="demolition"><span>${esc(t.demolition)}</span><div class="dc-sw"></div></div>
    </div>
  </div>`;
  }

  function stepRooms() {
    const t = T.rooms;
    const rooms = state.rooms
      .map((r, i) => {
        if (i === state.editRoom) {
          // the last room cannot be removed, so the button is disabled rather than inert
          const rm = state.rooms.length > 1 ? `data-rm="${i}"` : 'disabled';
          return `<div class="dc-room exp"><div class="dc-room-h"><b>${esc(r.name)}</b><button class="rm" ${rm}>${esc(t.remove)}</button></div>
      <div class="dc-fields">
        <div class="dc-fb"><span>${esc(t.floor_area)}</span><div class="ip"><input data-r="${i}" data-f="area" type="number" value="${esc(r.area)}"><span class="u">${esc(T.units.m2)}</span></div></div>
        <div class="dc-fb"><span>${esc(t.height)}</span><div class="ip"><input data-r="${i}" data-f="h" type="number" value="${esc(r.h)}"><span class="u">${esc(T.units.m)}</span></div></div>
        <div class="dc-fb"><span>${esc(t.perimeter)}</span><div class="ip"><input data-r="${i}" data-f="per" type="number" value="${esc(r.per)}"><span class="u">${esc(T.units.m)}</span></div></div>
        <div class="dc-fb"><span>${esc(t.doors)}</span><div class="ip"><input data-r="${i}" data-f="doors" type="number" value="${esc(r.doors)}"></div></div>
        <div class="dc-fb"><span>${esc(t.windows)}</span><div class="ip"><input data-r="${i}" data-f="win" type="number" value="${esc(r.win)}"></div></div>
      </div>
      <div class="dc-group"><label>${esc(t.floor_cover)}</label>${roomChips('floor', i, D.floorCovers, r.floor)}</div>
      <div class="dc-group"><label>${esc(t.wall_cover)}</label>${roomChips('wall', i, D.wallCovers, r.wall)}</div>
      <div class="dc-tg ${r.heat ? 'on' : ''}" data-rheat="${i}"><span>${esc(t.heating)}</span><div class="dc-sw"></div></div>
      </div>`;
        }
        return `<div class="dc-collapsed" data-open="${i}"><div><b>${esc(r.name)}</b><span class="a">· ${esc(r.area)} ${esc(T.units.m2)}</span></div><span class="${CLS.editHint}">${esc(t.edit)}</span></div>`;
      })
      .join('');
    return `<div class="dc-sh"><div class="k">${esc(t.k)}</div><h2>${esc(t.h)}</h2><div class="d">${esc(t.d)}</div></div>
  ${rooms}<button class="dc-add" id="dcAddRoom">${esc(t.add)}</button>`;
  }

  function stepWorks() {
    const t = T.works;
    const half = Math.ceil(WORKS.length / 2);
    const col = (from, to) =>
      WORKS.slice(from, to)
        .map((w, idx) => {
          const i = from + idx;
          const price = tr(t.price_unit, { price: w.price, unit: w.unitLabel });
          return `<div class="dc-wr ${state.works[i] ? 'on' : ''}" data-w="${i}"><div class="lft"><div class="dc-cb">${CHK}</div><div class="nm">${esc(w.name)}</div></div><div class="pr">${esc(price)}</div></div>`;
        })
        .join('');
    const sel = Object.values(state.works).filter(Boolean).length;
    return `<div class="dc-sh"><div class="k">${esc(t.k)}</div><h2>${esc(t.h)}</h2><div class="d">${esc(t.d)}</div></div>
  <div class="dc-cols"><div class="${CLS.colStack}">${col(0, half)}</div><div class="${CLS.colStack}">${col(half, WORKS.length)}</div></div>
  <div class="${CLS.sumRow}"><b class="${CLS.sumLabel}">${esc(tr(t.selected, { count: sel }))}</b><span class="${CLS.sumValue}">${fmt(worksCost())} <span class="${CLS.sumUnit}">${esc(T.currency)}</span></span></div>`;
  }

  function stepMaterials() {
    const t = T.materials;
    const rows = D.materials
      .map(({ label, cat }) => {
        const item = state.useCart ? state.cart.find((c) => c.included && c.cat === cat) : null;
        if (item) {
          const qty = String(item.calc || '').split('→').pop().trim();
          return `<div class="dc-mr"><div class="ml"><b>${esc(label)}</b><span>${esc(item.name)} · ${esc(qty)}</span></div><div class="mr-r"><span class="price">${esc(money(item.price))}</span><span class="dc-badge">${esc(t.from_cart)}</span></div></div>`;
        }
        return `<div class="dc-mr"><div class="ml"><b>${esc(label)}</b><span class="no">${esc(t.not_selected)}</span></div><div class="mr-r"><a class="dc-pick" href="${esc(catalogUrl)}">${esc(t.pick)}</a></div></div>`;
      })
      .join('');
    const n = state.useCart ? state.cart.filter((c) => c.included).length : 0;
    return `<div class="dc-sh"><div class="k">${esc(t.k)}</div><h2>${esc(t.h)}</h2><div class="d">${esc(t.d)}</div></div>
  <div class="dc-banner"><b>${esc(tr(t.banner, { count: n }))}</b><div class="dc-tg grn ${state.useCart ? 'on' : ''}" data-t="useCart"><span>${esc(T.side.use_in_calc)}</span><div class="dc-sw"></div></div></div>
  ${rows}`;
  }

  function stepCart() {
    const t = T.cart;
    const rows = state.cart
      .map(
        (c, i) =>
          `<div class="dc-cart-row ${c.included ? '' : 'off'}" data-c="${i}"><div class="cbx">${CHK}</div><div class="dc-thumb"></div><div class="info"><b>${esc(c.name)}</b><div class="meta">${esc(c.cat)} · ${esc(c.brand)}</div><div class="calc">${esc(c.calc)}</div></div><div class="rr"><div class="p">${esc(money(c.price))}</div><span class="dc-stock ${c.inStock ? '' : 'ord'}">${esc(c.stock)}</span></div></div>`
      )
      .join('');
    const n = state.cart.filter((c) => c.included).length;
    return `<div class="dc-sh"><div class="k">${esc(t.k)}</div><h2>${esc(t.h)}</h2><div class="d">${esc(t.d)}</div></div>
  ${rows}
  <div class="${CLS.cartRow}"><b class="${CLS.cartLabel}">${esc(tr(t.summary, { count: n }))}</b><span class="${CLS.sumValue}">${fmt(cartTotal())} <span class="${CLS.sumUnit}">${esc(T.currency)}</span></span></div>`;
  }

  function stepSummary() {
    const t = T.summary;
    const e = computeEstimate();
    const max = Math.max(...e.rows.map((r) => r[1]));
    const bd = e.rows
      .map(
        ([l, v]) =>
          `<div class="row"><div class="top"><span class="l">${esc(l)}</span><span class="v">${esc(money(v))}</span></div><div class="dc-bar ${v === max ? 'hi' : ''}"><i style="width:${Math.max(4, Math.round((v / max) * 100))}%"></i></div></div>`
      )
      .join('');
    return `<div class="dc-sh"><div class="k">${esc(t.k)}</div><h2>${esc(tr(t.h, { area: totalArea() }))}</h2><div class="d">${esc(t.d)}</div></div>
  <div class="dc-bd">${bd}</div>
  <div class="dc-total"><b>${esc(t.total)}</b><div class="v"><b>${fmt(e.total)}</b><span>${esc(T.currency_code)}</span></div></div>`;
  }

  function renderSide() {
    if (state.step === 5) {
      const tips = [T.tips.tile, T.tips.doors, T.tips.top]
        .map(
          (tip) =>
            `<div class="dc-tip"><div class="d">✓</div><div><div class="t1">${esc(tip.t1)}</div><div class="t2">${esc(tip.t2)}</div></div></div>`
        )
        .join('');
      const a = T.actions;
      sideEl.innerHTML = `
    <div class="dc-tips">${tips}</div>
    <div class="dc-act">
      <button class="dc-next ${CLS.addAll}" id="dcAddAll">${esc(a.add_all)}</button>
      <div class="dc-sec"><button id="dcPdf">${esc(a.pdf)}</button><button id="dcWa">${esc(a.whatsapp)}</button><button id="dcSave">${esc(a.save)}</button></div>
      <button class="dc-outline" id="dcTurnkey">${esc(a.turnkey)}</button>
    </div>`;
      document.getElementById('dcAddAll').addEventListener('click', () => alert(T.alerts.added));
      document.getElementById('dcPdf').addEventListener('click', () => window.print());
      document.getElementById('dcSave').addEventListener('click', () => {
        try {
          localStorage.setItem('archi-estimate', JSON.stringify(computeEstimate()));
        } catch (err) {
          // localStorage blocked — nothing is persisted
        }
        alert(T.alerts.saved);
      });
      document.getElementById('dcWa').addEventListener('click', () => {
        const est = computeEstimate();
        window.open(
          'https://wa.me/?text=' +
            encodeURIComponent(tr(T.alerts.whatsapp, { total: fmt(est.total) })),
          '_blank'
        );
      });
      document.getElementById('dcTurnkey').addEventListener('click', () => alert(T.alerts.turnkey));
      return;
    }

    const s = T.side;
    const e = computeEstimate();
    const cart = cartTotal();
    const works = worksCost();
    sideEl.innerHTML = `
  <div class="dc-sum">
    <div class="lbl">${esc(s.label)}</div>
    <div class="big"><b>${esc(tr(T.approx, { amount: fmt(e.total) }))}</b><span>${esc(T.currency_code)}</span></div>
    <div class="${CLS.sideMeta}">${esc(tr(s.meta, { area: totalArea(), rooms: state.rooms.length }))}</div>
    <div class="rows">
      <div class="r"><span>${esc(s.works)}</span><b>${esc(money(works))}</b></div>
      <div class="r"><span>${esc(s.cart_materials)}</span><b>${esc(money(cart))}</b></div>
      <div class="r"><span>${esc(s.reserve)}</span><b>${esc(money(e.rows[9][1]))}</b></div>
    </div>
  </div>
  ${
    state.step >= 3
      ? `<div class="dc-banner ${CLS.sideBanner}"><b>${esc(tr(s.cart_banner, { count: state.cart.length }))}</b><div class="${CLS.sideBannerRow}"><span class="${CLS.sideBannerHint}">${esc(s.use_in_calc)}</span><div class="dc-tg grn ${state.useCart ? 'on' : ''}" data-t="useCart"><div class="dc-sw"></div></div></div></div>`
      : ''
  }
  <div class="dc-nav">
    ${state.step > 0 ? `<button class="dc-back" id="dcBack">${esc(s.back)}</button>` : ''}
    <button class="dc-next" id="dcNextBtn">${esc(state.step === 4 ? s.calculate : s.next)}  →</button>
  </div>`;
    const back = document.getElementById('dcBack');
    if (back) back.addEventListener('click', () => goToStep(state.step - 1));
    document
      .getElementById('dcNextBtn')
      .addEventListener('click', () => goToStep(Math.min(state.step + 1, 5)));
    bindToggles(sideEl);
  }

  function bindToggles(root) {
    root.querySelectorAll('.dc-tg[data-t]').forEach((t) =>
      t.addEventListener('click', () => {
        const k = t.dataset.t;
        state[k] = !state[k];
        if (k === 'demolition' && DEMOLITION >= 0) state.works[DEMOLITION] = state.demolition;
        render();
      })
    );
  }

  function render() {
    renderStepper();
    const fns = [stepObject, stepRooms, stepWorks, stepMaterials, stepCart, stepSummary];
    mainEl.innerHTML = fns[state.step]();
    bindMain();
    renderSide();
  }

  function bindMain() {
    // chips bound to a state key
    mainEl.querySelectorAll('.dc-chips[data-key]').forEach((g) =>
      g.addEventListener('click', (ev) => {
        const btn = ev.target.closest('.dc-chip');
        if (!btn) return;
        state[g.dataset.key] = btn.dataset.v;
        render();
      })
    );
    // chips bound to a room field
    mainEl.querySelectorAll('.dc-chips[data-rk]').forEach((g) =>
      g.addEventListener('click', (ev) => {
        const btn = ev.target.closest('.dc-chip');
        if (!btn) return;
        state.rooms[+g.dataset.ri][g.dataset.rk] = btn.dataset.v;
        render();
      })
    );
    // area / city / floor
    const area = document.getElementById('dcArea');
    if (area) {
      area.addEventListener('input', () => {
        state.area = +area.value || 0;
        renderSide();
      });
    }
    const city = document.getElementById('dcCity');
    if (city) city.addEventListener('input', () => (state.city = city.value));
    const floor = document.getElementById('dcFloor');
    if (floor) floor.addEventListener('input', () => (state.floor = floor.value));
    // toggles inside the step
    bindToggles(mainEl);
    mainEl.querySelectorAll('.dc-tg[data-rheat]').forEach((t) =>
      t.addEventListener('click', () => {
        const i = +t.dataset.rheat;
        state.rooms[i].heat = !state.rooms[i].heat;
        render();
      })
    );
    // room inputs
    mainEl.querySelectorAll('input[data-r]').forEach((inp) =>
      inp.addEventListener('input', () => {
        const i = +inp.dataset.r;
        const field = inp.dataset.f;
        state.rooms[i][field] = /area|h|per|doors|win/.test(field) ? +inp.value || 0 : inp.value;
        renderSide();
      })
    );
    // open / remove / add a room
    mainEl.querySelectorAll('[data-open]').forEach((el) =>
      el.addEventListener('click', () => {
        state.editRoom = +el.dataset.open;
        render();
      })
    );
    mainEl.querySelectorAll('[data-rm]').forEach((el) =>
      el.addEventListener('click', (ev) => {
        ev.stopPropagation();
        // `data-rm` is only rendered while more than one room is left
        state.rooms.splice(+el.dataset.rm, 1);
        state.editRoom = 0;
        render();
      })
    );
    const add = document.getElementById('dcAddRoom');
    if (add) {
      add.addEventListener('click', () => {
        state.rooms.push({ ...D.newRoom });
        state.editRoom = state.rooms.length - 1;
        render();
      });
    }
    // works
    mainEl.querySelectorAll('.dc-wr[data-w]').forEach((el) =>
      el.addEventListener('click', () => {
        const i = +el.dataset.w;
        state.works[i] = !state.works[i];
        if (i === DEMOLITION) state.demolition = state.works[i];
        render();
      })
    );
    // cart rows
    mainEl.querySelectorAll('.dc-cart-row[data-c]').forEach((el) =>
      el.querySelector('.cbx').addEventListener('click', () => {
        const i = +el.dataset.c;
        state.cart[i].included = !state.cart[i].included;
        render();
      })
    );
  }

  render();
}

init();
