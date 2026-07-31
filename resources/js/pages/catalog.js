// Page module for "catalog" — ported from the inline <script> of the old catalog.html.
// The 20 product cards are rendered server-side, so sorting only reorders the existing
// DOM nodes (numeric values come from data-i / data-now / data-rate).
// Shared behaviour (navbar, round product cursor) lives in resources/js/shared/.

const PRICE_MIN = 0;
const PRICE_MAX = 100;

const num = (el, key) => parseFloat(el.dataset[key]) || 0;

// Sorts the cards in place; `pop` restores the server-rendered order.
function initSort(grid) {
  const sortEl = document.getElementById('catSort');
  const sortVal = document.getElementById('sortVal');
  const sortMenu = document.getElementById('sortMenu');
  if (!sortEl || !sortVal || !sortMenu) return;

  const cards = [...grid.children];
  const sorters = {
    pop: (a, b) => num(a, 'i') - num(b, 'i'),
    cheap: (a, b) => num(a, 'now') - num(b, 'now'),
    exp: (a, b) => num(b, 'now') - num(a, 'now'),
    rating: (a, b) => num(b, 'rate') - num(a, 'rate'),
    new: (a, b) => num(b, 'i') - num(a, 'i'),
  };

  sortEl.addEventListener('click', (e) => {
    const li = e.target.closest('li');
    if (li) {
      sortMenu.querySelectorAll('li').forEach((x) => (x.dataset.on = 'false'));
      li.dataset.on = 'true';
      sortVal.textContent = li.textContent;
      cards.slice().sort(sorters[li.dataset.sort] || sorters.pop).forEach((c) => grid.appendChild(c));
      sortEl.dataset.open = 'false';
      return;
    }
    sortEl.dataset.open = sortEl.dataset.open === 'true' ? 'false' : 'true';
  });

  document.addEventListener('click', (e) => {
    if (!e.target.closest('#catSort')) sortEl.dataset.open = 'false';
  });
}

// Dual-range price slider + the active filter chips above the grid.
function initFilters() {
  const chipWrap = document.getElementById('catChips');
  const clearBtn = document.getElementById('catClear');
  const slider = document.getElementById('fsSlider');
  const fill = document.getElementById('fsFill');
  const knobMin = document.getElementById('fsKnobMin');
  const knobMax = document.getElementById('fsKnobMax');
  const inMin = document.getElementById('fsMin');
  const inMax = document.getElementById('fsMax');
  if (!chipWrap || !clearBtn || !slider) return;

  // Label formats come from Blade, e.g. ":v sm" / ":min–:max ₼".
  const fmt = (tpl, values) =>
    Object.keys(values).reduce((s, k) => s.split(':' + k).join(values[k]), tpl || '');
  const L = chipWrap.dataset;

  let vMin = parseFloat(inMin.value) || PRICE_MIN;
  let vMax = parseFloat(inMax.value) || PRICE_MAX;

  const pct = (v) => ((v - PRICE_MIN) / (PRICE_MAX - PRICE_MIN)) * 100;

  function activeFilters() {
    const out = [];
    document.querySelectorAll('.fs-size[data-on="true"]').forEach((el) =>
      out.push({ el, label: fmt(L.lSize, { v: el.textContent.trim() }) })
    );
    document.querySelectorAll('#surfBlock .fs-check[data-on="true"]').forEach((el) =>
      out.push({ el, label: fmt(L.lSurface, { v: el.querySelector('.lbl').textContent.trim() }) })
    );
    document.querySelectorAll('#brandBlock .fs-check[data-on="true"]').forEach((el) =>
      out.push({ el, label: el.querySelector('.lbl').textContent.trim() })
    );
    if (!(Math.round(vMin) === PRICE_MIN && Math.round(vMax) === PRICE_MAX)) {
      out.push({
        price: true,
        label: fmt(L.lPrice, { min: Math.round(vMin), max: Math.round(vMax) }),
      });
    }
    return out;
  }

  function renderChips() {
    chipWrap.querySelectorAll('.cat-chip').forEach((c) => c.remove());
    const items = activeFilters();
    items.forEach((it) => {
      const chip = document.createElement('span');
      chip.className = 'cat-chip';
      chip.appendChild(document.createTextNode(it.label + ' '));
      const x = document.createElement('span');
      x.className = 'x';
      x.textContent = '✕';
      chip.appendChild(x);
      chip._filter = it;
      chipWrap.insertBefore(chip, clearBtn);
    });
    clearBtn.style.display = items.length ? '' : 'none';
  }

  function paint() {
    fill.style.left = pct(vMin) + '%';
    fill.style.right = 100 - pct(vMax) + '%';
    knobMin.style.left = pct(vMin) + '%';
    knobMax.style.left = pct(vMax) + '%';
    inMin.value = Math.round(vMin);
    inMax.value = Math.round(vMax);
    renderChips();
  }

  function drag(knob, isMin) {
    knob.addEventListener('pointerdown', (e) => {
      e.preventDefault();
      knob.setPointerCapture(e.pointerId);
      const move = (ev) => {
        const r = slider.getBoundingClientRect();
        const p = Math.min(1, Math.max(0, (ev.clientX - r.left) / r.width));
        const v = PRICE_MIN + p * (PRICE_MAX - PRICE_MIN);
        if (isMin) vMin = Math.min(v, vMax);
        else vMax = Math.max(v, vMin);
        paint();
      };
      const up = () => {
        knob.releasePointerCapture(e.pointerId);
        knob.removeEventListener('pointermove', move);
        knob.removeEventListener('pointerup', up);
      };
      knob.addEventListener('pointermove', move);
      knob.addEventListener('pointerup', up);
    });
  }
  drag(knobMin, true);
  drag(knobMax, false);

  function clampInputs() {
    let a = parseInt(inMin.value, 10);
    let b = parseInt(inMax.value, 10);
    if (isNaN(a)) a = PRICE_MIN;
    if (isNaN(b)) b = PRICE_MAX;
    a = Math.min(Math.max(a, PRICE_MIN), PRICE_MAX);
    b = Math.min(Math.max(b, PRICE_MIN), PRICE_MAX);
    if (a > b) a = b;
    vMin = a;
    vMax = b;
    paint();
  }
  inMin.addEventListener('change', clampInputs);
  inMax.addEventListener('change', clampInputs);

  // the chip's ✕ switches the matching filter off
  chipWrap.addEventListener('click', (e) => {
    const chip = e.target.closest('.cat-chip');
    if (!chip || !chip._filter) return;
    if (chip._filter.price) {
      vMin = PRICE_MIN;
      vMax = PRICE_MAX;
      paint();
    } else {
      chip._filter.el.dataset.on = 'false';
      renderChips();
    }
  });

  clearBtn.addEventListener('click', () => {
    document
      .querySelectorAll('.fs-size[data-on="true"], #surfBlock .fs-check[data-on="true"], #brandBlock .fs-check[data-on="true"]')
      .forEach((el) => (el.dataset.on = 'false'));
    vMin = PRICE_MIN;
    vMax = PRICE_MAX;
    paint();
  });

  document.querySelectorAll('.fs-check, .fs-size').forEach((el) =>
    el.addEventListener('click', () => {
      el.dataset.on = el.dataset.on === 'true' ? 'false' : 'true';
      renderChips();
    })
  );

  document.querySelectorAll('.fs-cat').forEach((c) =>
    c.addEventListener('click', () => {
      document.querySelectorAll('.fs-cat').forEach((x) => (x.dataset.on = 'false'));
      c.dataset.on = 'true';
    })
  );

  const stock = document.getElementById('stockSwitch');
  if (stock) {
    stock.addEventListener('click', () => {
      stock.dataset.on = stock.dataset.on === 'true' ? 'false' : 'true';
    });
  }

  paint(); // initial render — also builds the chips
}

export default function init() {
  const grid = document.getElementById('catGrid');
  if (!grid) return;
  initSort(grid);
  initFilters();
}

init();
