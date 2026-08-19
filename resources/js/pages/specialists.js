// Page module for "specialists" — all filtering and sorting is done server-side
// via URL query parameters. The JS collects the sidebar state, builds a URL and
// navigates to it.

import initFilterSheet from '../shared/filter-sheet.js';

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

  /* ---- mobile filter sheet (≤980px) ---- */
  const sheet = initFilterSheet({
    sheet: 'spFside',
    btn: 'spFilterBtn',
    scrim: 'spFilterScrim',
    close: 'spFilterClose',
  });

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
    return out;
  }

  function renderChips() {
    if (!chipWrap || !clearBtn) return;
    // :not(.sp-chip-static) — the ?type= chip is rendered by Blade, not by this
    // function, and has no sidebar control to rebuild itself from. It shares .sp-chip
    // for styling, so clearing the row by class alone deleted it on the first pass and
    // left a filtered page with nothing to say it was filtered.
    $$('.sp-chip:not(.sp-chip-static)', chipWrap).forEach((c) => c.remove());
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
    // badge on the phone filter trigger — chips only cover spec + city, the
    // experience chip and the two switches are counted here as well
    if (sheet) {
      let n = items.length;
      if ($('#spYearBlock .sp-year[data-on="true"]')) n += 1;
      if (swVerified && swVerified.dataset.on === 'true') n += 1;
      if (swFree && swFree.dataset.on === 'true') n += 1;
      sheet.setCount(n);
    }
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
