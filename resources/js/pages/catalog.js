// Page module for "catalog" — all filtering and sorting is done server-side via
// URL query parameters. The JS collects the sidebar state, builds a URL and
// navigates to it. Sort, category clicks, and the "Apply" button all work this way.

// Read actual price range from the slider data attributes (set by the server)
const sliderEl = document.getElementById('fsSlider');
const PRICE_MIN = sliderEl ? parseInt(sliderEl.dataset.priceMin, 10) || 0 : 0;
const PRICE_MAX = sliderEl ? parseInt(sliderEl.dataset.priceMax, 10) || 100 : 100;
const HAS_PRICE_FILTER = sliderEl ? sliderEl.dataset.priceFiltered === 'true' : false;

const num = (el, key) => parseFloat(el.dataset[key]) || 0;

function buildFilterUrl() {
  const params = new URLSearchParams();

  // Category
  const cat = document.querySelector('.fs-cat[data-on="true"]');
  if (cat) params.set('category', cat.dataset.cat);

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

  // Sizes (comma-separated)
  const sizes = [...document.querySelectorAll('.fs-size[data-on="true"]')]
    .map((el) => el.dataset.size);
  if (sizes.length) params.set('size', sizes.join(','));

  // In-stock only
  const stock = document.getElementById('stockSwitch');
  if (stock && stock.dataset.on === 'true') params.set('in_stock', '1');

  // Preserve context params the sidebar has no controls for: the search query and
  // the homepage "Ətraflı bax" entry filters (sale / featured / free_delivery / on_sale).
  const url = new URL(window.location.href);
  for (const key of ['q', 'sale', 'featured', 'free_delivery', 'on_sale']) {
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

  sortEl.addEventListener('click', (e) => {
    const li = e.target.closest('li');
    if (li) {
      // Update visual state then navigate
      sortMenu.querySelectorAll('li').forEach((x) => (x.dataset.on = 'false'));
      li.dataset.on = 'true';
      sortVal.textContent = li.textContent;
      sortEl.dataset.open = 'false';
      // Navigate with new sort
      window.location.href = buildFilterUrl();
      return;
    }
    sortEl.dataset.open = sortEl.dataset.open === 'true' ? 'false' : 'true';
  });

  document.addEventListener('click', (e) => {
    if (!e.target.closest('#catSort')) sortEl.dataset.open = 'false';
  });

  window.addEventListener('scroll', () => { sortEl.dataset.open = 'false'; }, { passive: true });
}

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
    } else {
      chip._filter.el.dataset.on = 'false';
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

  // Clear all filters — navigate to clean URL
  clearBtn.addEventListener('click', () => {
    // Reset all UI state
    document
      .querySelectorAll('.fs-size[data-on="true"], #surfBlock .fs-check[data-on="true"], #brandBlock .fs-check[data-on="true"]')
      .forEach((el) => (el.dataset.on = 'false'));
    document.querySelectorAll('.fs-cat').forEach((el) => (el.dataset.on = 'false'));
    const stock = document.getElementById('stockSwitch');
    if (stock) stock.dataset.on = 'false';
    vMin = PRICE_MIN;
    vMax = PRICE_MAX;
    // Navigate to page with no filters (preserve only q if present)
    const url = new URL(window.location.href);
    const q = url.searchParams.get('q');
    window.location.href = window.location.pathname + (q ? '?q=' + encodeURIComponent(q) : '');
  });

  // Toggle sidebar filter controls
  document.querySelectorAll('.fs-check, .fs-size').forEach((el) =>
    el.addEventListener('click', () => {
      el.dataset.on = el.dataset.on === 'true' ? 'false' : 'true';
      renderChips();
    })
  );

  // Category selection (single-select)
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
  initFilters();
}
