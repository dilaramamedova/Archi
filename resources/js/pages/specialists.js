// Page module for "specialists" — all filtering and sorting is done server-side
// via URL query parameters. The JS collects the sidebar state, builds a URL and
// navigates to it.

const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => Array.prototype.slice.call(root.querySelectorAll(sel));

function buildFilterUrl() {
  const params = new URLSearchParams();

  // Specialization
  const cat = $('#spSpecBlock .sp-cat[data-on="true"]');
  if (cat) params.set('spec', cat.dataset.spec);

  // City
  const city = $('#spCityBlock .sp-check[data-on="true"]');
  if (city) params.set('city', city.dataset.city);

  // Rating
  const rating = $('#spRateBlock .sp-radio[data-on="true"]');
  if (rating) params.set('min_rating', rating.dataset.min);

  // Experience years
  const year = $('#spYearBlock .sp-year[data-on="true"]');
  if (year) {
    params.set('min_years', year.dataset.min);
    params.set('max_years', year.dataset.max);
  }

  // Verified
  const swVerified = document.getElementById('spVerified');
  if (swVerified && swVerified.dataset.on === 'true') params.set('verified', '1');

  // Free this week
  const swFree = document.getElementById('spFree');
  if (swFree && swFree.dataset.on === 'true') params.set('free', '1');

  // Sort
  const sortLi = $('#spSortMenu li[data-on="true"]');
  if (sortLi && sortLi.dataset.sort !== 'rating') params.set('sort', sortLi.dataset.sort);

  const qs = params.toString();
  return window.location.pathname + (qs ? '?' + qs : '');
}

export default function init() {
  const grid = document.getElementById('spGrid');
  if (!grid) return;

  const cats = $$('#spSpecBlock .sp-cat');
  const checks = $$('#spCityBlock .sp-check');
  const radios = $$('#spRateBlock .sp-radio');
  const years = $$('#spYearBlock .sp-year');
  const swVerified = document.getElementById('spVerified');
  const swFree = document.getElementById('spFree');

  /* ---- sort dropdown ---- */
  const sortEl = document.getElementById('spSort');
  const sortVal = document.getElementById('spSortVal');
  const sortMenu = document.getElementById('spSortMenu');

  if (sortEl && sortVal && sortMenu) {
    sortEl.addEventListener('click', (e) => {
      const li = e.target.closest('li');
      if (li) {
        $$('li', sortMenu).forEach((x) => { x.dataset.on = 'false'; });
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
      if (!e.target.closest('#spSort')) sortEl.dataset.open = 'false';
    });
  }

  /* ---- sidebar filter controls (toggle data-on, visual only until Apply) ---- */
  cats.forEach((c) =>
    c.addEventListener('click', () => {
      cats.forEach((x) => { x.dataset.on = 'false'; });
      c.dataset.on = 'true';
      renderChips();
    })
  );
  checks.forEach((c) =>
    c.addEventListener('click', () => {
      c.dataset.on = c.dataset.on === 'true' ? 'false' : 'true';
      renderChips();
    })
  );
  radios.forEach((r) =>
    r.addEventListener('click', () => {
      radios.forEach((x) => { x.dataset.on = 'false'; });
      r.dataset.on = 'true';
      renderChips();
    })
  );
  years.forEach((y) =>
    y.addEventListener('click', () => {
      const was = y.dataset.on === 'true';
      years.forEach((x) => { x.dataset.on = 'false'; });
      if (!was) y.dataset.on = 'true';
    })
  );
  const switches = [swVerified, swFree].filter(Boolean);
  switches.forEach((s) =>
    s.addEventListener('click', () => {
      s.dataset.on = s.dataset.on === 'true' ? 'false' : 'true';
    })
  );

  /* ---- active filter chips ---- */
  const chipWrap = document.getElementById('spChips');
  const clearBtn = document.getElementById('spClear');
  const chipSource = new WeakMap();

  function activeFilters() {
    const out = [];
    const cat = $('#spSpecBlock .sp-cat[data-on="true"]');
    if (cat) out.push({ el: cat, label: cat.querySelector('.t').textContent.trim() });
    checks.forEach((c) => {
      if (c.dataset.on === 'true') out.push({ el: c, label: c.querySelector('.lbl').textContent.trim() });
    });
    const r = $('#spRateBlock .sp-radio[data-on="true"]');
    if (r) out.push({ el: r, label: r.dataset.chip });
    return out;
  }

  function renderChips() {
    if (!chipWrap || !clearBtn) return;
    $$('.sp-chip', chipWrap).forEach((c) => c.remove());
    const items = activeFilters();
    items.forEach((it) => {
      const chip = document.createElement('span');
      chip.className = 'sp-chip';
      chip.appendChild(document.createTextNode(it.label + ' '));
      const x = document.createElement('span');
      x.className = 'x';
      x.textContent = '✕';
      chip.appendChild(x);
      chipSource.set(chip, it.el);
      chipWrap.insertBefore(chip, clearBtn);
    });
    clearBtn.style.display = items.length ? '' : 'none';
  }

  if (chipWrap && clearBtn) {
    chipWrap.addEventListener('click', (e) => {
      const chip = e.target.closest('.sp-chip');
      if (!chip) return;
      const source = chipSource.get(chip);
      if (!source) return;
      source.dataset.on = 'false';
      renderChips();
    });

    // Clear all filters — navigate to clean URL
    clearBtn.addEventListener('click', () => {
      window.location.href = window.location.pathname;
    });

    renderChips();
  }

  /* ---- "Apply filters" — navigate to filtered URL ---- */
  const applyBtn = document.getElementById('spApply');
  if (applyBtn) {
    applyBtn.addEventListener('click', () => {
      window.location.href = buildFilterUrl();
    });
  }
}
