// Page module for "catalog" — all filtering and sorting is done server-side via
// URL query parameters. The JS collects the sidebar state, builds a URL and
// navigates to it. Sort, category clicks, and the "Apply" button all work this way.

import initFilterSheet from '../shared/filter-sheet.js';

// Read actual price range from the slider data attributes (set by the server)
const sliderEl = document.getElementById('fsSlider');
const PRICE_MIN = sliderEl ? parseInt(sliderEl.dataset.priceMin, 10) || 0 : 0;
const PRICE_MAX = sliderEl ? parseInt(sliderEl.dataset.priceMax, 10) || 100 : 100;
const HAS_PRICE_FILTER = sliderEl ? sliderEl.dataset.priceFiltered === 'true' : false;

const num = (el, key) => parseFloat(el.dataset[key]) || 0;

function buildFilterUrl() {
  const params = new URLSearchParams();

  // Sort
  const sortLi = document.querySelector('#sortMenu li[data-on="true"]');
  if (sortLi && sortLi.dataset.sort !== 'pop') params.set('sort', sortLi.dataset.sort);

  // Price range
  const inMin = document.getElementById('fsMin');
  const inMax = document.getElementById('fsMax');
  if (inMin && inMin.value && parseInt(inMin.value, 10) > PRICE_MIN) {
    params.set('min_price', inMin.value);
  }
  if (inMax && inMax.value && parseInt(inMax.value, 10) < PRICE_MAX) {
    params.set('max_price', inMax.value);
  }

  // Brands (comma-separated)
  const brands = [...document.querySelectorAll('#brandBlock .fs-check[data-on="true"]')]
    .map((el) => el.dataset.brand);
  if (brands.length) params.set('brand', brands.join(','));

  // Surfaces (comma-separated)
  const surfaces = [...document.querySelectorAll('#surfBlock .fs-check[data-on="true"]')]
    .map((el) => el.dataset.surface);
  if (surfaces.length) params.set('surface', surfaces.join(','));

  // Sizes (comma-separated). data-size is the chip LABEL (and the CSS ghost
  // width); data-slug is the attribute-option slug the server filters on.
  const sizes = [...document.querySelectorAll('.fs-size[data-on="true"]')]
    .map((el) => el.dataset.slug || el.dataset.size);
  if (sizes.length) params.set('size', sizes.join(','));

  // In-stock only
  const stock = document.getElementById('stockSwitch');
  if (stock && stock.dataset.on === 'true') params.set('in_stock', '1');

  // Dynamic class-attribute filters (classifier). Option checkboxes carry
  // data-attr + data-opt; the boolean control carries data-attr + data-bool;
  // numeric min/max inputs live in .fs-attr-range[data-attr].
  const attrOpts = {};
  document.querySelectorAll('.fs-check[data-attr][data-opt][data-on="true"]').forEach((el) => {
    (attrOpts[el.dataset.attr] = attrOpts[el.dataset.attr] || []).push(el.dataset.opt);
  });
  Object.entries(attrOpts).forEach(([slug, opts]) => params.set(`attr[${slug}]`, opts.join(',')));
  document.querySelectorAll('.fs-check[data-attr][data-bool][data-on="true"]').forEach((el) => {
    params.set(`attr[${el.dataset.attr}]`, '1');
  });
  document.querySelectorAll('.fs-attr-range[data-attr]').forEach((el) => {
    const min = el.querySelector('[data-attr-min]');
    const max = el.querySelector('[data-attr-max]');
    if (min && min.value.trim() !== '') params.set(`attr_min[${el.dataset.attr}]`, min.value.trim());
    if (max && max.value.trim() !== '') params.set(`attr_max[${el.dataset.attr}]`, max.value.trim());
  });

  // Laminate card filters (class=laminat only): ticked rows carry data-lam (the
  // lam_* parameter) + data-val; values of one parameter are OR-ed server-side.
  const lam = {};
  document.querySelectorAll('.fs-check[data-lam][data-on="true"]').forEach((el) => {
    (lam[el.dataset.lam] = lam[el.dataset.lam] || []).push(el.dataset.val);
  });
  Object.entries(lam).forEach(([param, values]) => params.set(param, values.join(',')));
  document.querySelectorAll('.fs-lam-range input').forEach((el) => {
    const param = el.dataset.lamMin || el.dataset.lamMax;
    const v = el.value.trim().replace(',', '.');
    if (param && v !== '') params.set(param, v);
  });

  // Preserve context params the sidebar has no controls for: the search query,
  // the classifier drill-down position (section / group / class, plus the
  // legacy category param) and the homepage "Ətraflı bax" entry filters.
  const url = new URL(window.location.href);
  for (const key of ['q', 'section', 'group', 'class', 'category', 'sale', 'featured', 'free_delivery', 'on_sale']) {
    if (url.searchParams.has(key)) params.set(key, url.searchParams.get(key));
  }

  const qs = params.toString();
  return window.location.pathname + (qs ? '?' + qs : '');
}

function initSort() {
  const sortEl = document.getElementById('catSort');
  const sortVal = document.getElementById('sortVal');
  const sortMenu = document.getElementById('sortMenu');
  if (!sortEl || !sortVal || !sortMenu) return;

  const items = [...sortMenu.querySelectorAll('li')];

  function setOpen(open) {
    sortEl.dataset.open = open ? 'true' : 'false';
    sortEl.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  function choose(li) {
    items.forEach((x) => {
      x.dataset.on = 'false';
      x.setAttribute('aria-selected', 'false');
    });
    li.dataset.on = 'true';
    li.setAttribute('aria-selected', 'true');
    sortVal.textContent = li.textContent;
    setOpen(false);
    window.location.href = buildFilterUrl();
  }

  sortEl.addEventListener('click', (e) => {
    const li = e.target.closest('li');
    if (li) {
      choose(li);
      return;
    }
    setOpen(sortEl.dataset.open !== 'true');
  });

  // The control is a div with role="button" and the options are <li> — neither
  // raises a click from the keyboard the way a native control does, so the
  // whole sort menu used to be unreachable without a mouse.
  sortEl.addEventListener('keydown', (e) => {
    const open = sortEl.dataset.open === 'true';

    if (e.key === 'Escape') {
      if (open) {
        setOpen(false);
        sortEl.focus();
      }
      return;
    }

    const li = e.target.closest('li');

    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      if (li) {
        choose(li);
      } else if (!open) {
        setOpen(true);
        (items.find((x) => x.dataset.on === 'true') || items[0])?.focus();
      } else {
        setOpen(false);
      }

      return;
    }

    if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') return;
    e.preventDefault();
    if (!open) {
      setOpen(true);
      (items.find((x) => x.dataset.on === 'true') || items[0])?.focus();

      return;
    }
    const i = items.indexOf(li);
    const next = e.key === 'ArrowDown' ? i + 1 : i - 1;
    items[(next + items.length) % items.length]?.focus();
  });

  document.addEventListener('click', (e) => {
    if (!e.target.closest('#catSort')) setOpen(false);
  });

  document.addEventListener('focusin', (e) => {
    if (!e.target.closest('#catSort')) setOpen(false);
  });

  window.addEventListener('scroll', () => { setOpen(false); }, { passive: true });
}

function initFilters(sheet) {
  const chipWrap = document.getElementById('catChips');
  const clearBtn = document.getElementById('catClear');
  const slider = document.getElementById('fsSlider');
  const fill = document.getElementById('fsFill');
  const knobMin = document.getElementById('fsKnobMin');
  const knobMax = document.getElementById('fsKnobMax');
  const inMin = document.getElementById('fsMin');
  const inMax = document.getElementById('fsMax');
  if (!chipWrap || !clearBtn || !slider) return;

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
    // Dynamic attribute filters: checked options / booleans get their label,
    // filled numeric ranges get "Attribute: min–max".
    document.querySelectorAll('.fs-attr .fs-check[data-on="true"]').forEach((el) => {
      const title = el.closest('.fs-attr')?.querySelector('.fs-title .t')?.textContent.trim();
      const lbl = el.querySelector('.lbl').textContent.trim();
      out.push({ el, label: el.dataset.bool ? title || lbl : lbl });
    });
    document.querySelectorAll('.fs-attr-range[data-attr]').forEach((el) => {
      const min = el.querySelector('[data-attr-min]');
      const max = el.querySelector('[data-attr-max]');
      const vMinT = min ? min.value.trim() : '';
      const vMaxT = max ? max.value.trim() : '';
      if (vMinT === '' && vMaxT === '') return;
      const title = el.closest('.fs-attr')?.querySelector('.fs-title .t')?.textContent.trim() || el.dataset.attr;
      out.push({ range: el, label: `${title}: ${vMinT || '…'} – ${vMaxT || '…'}` });
    });
    // Laminate filters: the chip names the group too ("Panelin qalınlığı: 8 mm"),
    // a bare "8 mm" or "32" would not say what it filters.
    document.querySelectorAll('.fs-check[data-lam][data-on="true"]').forEach((el) =>
      out.push({ el, label: el.dataset.chip || el.querySelector('.lbl').textContent.trim() })
    );
    document.querySelectorAll('.fs-lam-range').forEach((el) => {
      const vMinT = el.querySelector('[data-lam-min]')?.value.trim() || '';
      const vMaxT = el.querySelector('[data-lam-max]')?.value.trim() || '';
      if (vMinT === '' && vMaxT === '') return;
      out.push({ range: el, label: `${el.dataset.chip}: ${vMinT || '…'} – ${vMaxT || '…'} ${el.dataset.unit || ''}`.trim() });
    });
    // "In stock only" is a filter like any other, but it used to produce no
    // chip and was missing from the mobile "Filtrlər (n)" badge, so on a phone
    // it could be on with nothing on screen saying so.
    const stockEl = document.getElementById('stockSwitch');
    if (stockEl && stockEl.dataset.on === 'true') {
      out.push({
        stock: stockEl,
        label: document.getElementById('stockSwitchLabel')?.textContent.trim() || '',
      });
    }
    // Show price chip only when the range differs from the full product range
    // (i.e. user has explicitly narrowed it via URL params or slider drag)
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
      x.setAttribute('role', 'button');
      x.setAttribute('tabindex', '0');
      x.setAttribute('aria-label', fmt(L.lRemove, { v: it.label }));
      chip.appendChild(x);
      chip._filter = it;
      chipWrap.insertBefore(chip, clearBtn);
    });
    clearBtn.style.visibility = items.length ? '' : 'hidden';
    if (sheet) sheet.setCount(items.length);
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

  // Chip removal
  function removeChip(chip) {
    if (!chip || !chip._filter) return;
    if (chip._filter.price) {
      vMin = PRICE_MIN;
      vMax = PRICE_MAX;
      paint();
    } else if (chip._filter.range) {
      chip._filter.range.querySelectorAll('input').forEach((i) => (i.value = ''));
      renderChips();
    } else if (chip._filter.stock) {
      chip._filter.stock.dataset.on = 'false';
      chip._filter.stock.setAttribute('aria-checked', 'false');
      renderChips();
    } else {
      chip._filter.el.dataset.on = 'false';
      chip._filter.el.setAttribute('aria-checked', 'false');
      renderChips();
    }
  }
  chipWrap.addEventListener('click', (e) => {
    if (!e.target.closest('.cat-chip .x')) return;
    removeChip(e.target.closest('.cat-chip'));
  });
  chipWrap.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    const x = e.target.closest('.cat-chip .x');
    if (!x) return;
    e.preventDefault();
    removeChip(x.closest('.cat-chip'));
  });

  // Clear all filters — navigate to clean URL. The drill-down position
  // (section / group / class) and the search query are context, not filters,
  // so they survive the reset.
  clearBtn.addEventListener('click', () => {
    // Reset all UI state
    document
      .querySelectorAll('.fs-size[data-on="true"], .fs-check[data-on="true"]')
      .forEach((el) => (el.dataset.on = 'false'));
    document.querySelectorAll('.fs-attr-range input').forEach((i) => (i.value = ''));
    const stock = document.getElementById('stockSwitch');
    if (stock) stock.dataset.on = 'false';
    vMin = PRICE_MIN;
    vMax = PRICE_MAX;
    const url = new URL(window.location.href);
    const keep = new URLSearchParams();
    for (const key of ['q', 'section', 'group', 'class', 'category']) {
      if (url.searchParams.has(key)) keep.set(key, url.searchParams.get(key));
    }
    const qs = keep.toString();
    window.location.href = window.location.pathname + (qs ? '?' + qs : '');
  });

  // Toggle sidebar filter controls (brands, surfaces, sizes, dynamic attribute
  // options). They are divs/spans with role="checkbox", so Enter and Space have
  // to be wired by hand — without this the whole sidebar was mouse-only.
  function toggleCheck(el) {
    const on = el.dataset.on !== 'true';
    el.dataset.on = on ? 'true' : 'false';
    el.setAttribute('aria-checked', on ? 'true' : 'false');
    renderChips();
  }

  document.querySelectorAll('.fs-check, .fs-size').forEach((el) => {
    el.addEventListener('click', () => toggleCheck(el));
    el.addEventListener('keydown', (e) => {
      if (e.key !== 'Enter' && e.key !== ' ') return;
      e.preventDefault();
      toggleCheck(el);
    });
  });

  // Attribute range inputs feed the chips band too
  document.querySelectorAll('.fs-attr-range input, .fs-lam-range input').forEach((i) =>
    i.addEventListener('change', renderChips)
  );

  // Secondary / "Bütün filtrlər" expanders
  document.querySelectorAll('.fs-expander .fs-exp-head').forEach((head) =>
    head.addEventListener('click', () => {
      const exp = head.closest('.fs-expander');
      exp.dataset.open = exp.dataset.open === 'true' ? 'false' : 'true';
    })
  );

  const stock = document.getElementById('stockSwitch');
  if (stock) {
    const flip = () => {
      const on = stock.dataset.on !== 'true';
      stock.dataset.on = on ? 'true' : 'false';
      stock.setAttribute('aria-checked', on ? 'true' : 'false');
      renderChips();
    };
    stock.addEventListener('click', flip);
    stock.addEventListener('keydown', (e) => {
      if (e.key !== 'Enter' && e.key !== ' ') return;
      e.preventDefault();
      flip();
    });
  }

  // "Apply filters" — build URL from current sidebar state and navigate
  const applyBtn = document.getElementById('catApply');
  if (applyBtn) {
    applyBtn.addEventListener('click', () => {
      window.location.href = buildFilterUrl();
    });
  }

  paint(); // initial render — also builds the chips
}

export default function init() {
  const grid = document.getElementById('catGrid');
  if (!grid) return;
  initSort();
  const sheet = initFilterSheet({
    sheet: 'catFside',
    btn: 'catFilterBtn',
    scrim: 'catFilterScrim',
    close: 'catFilterClose',
  });
  initFilters(sheet);
}
