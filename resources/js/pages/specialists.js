// Page module for "specialists" — ported from the inline <script> of the old
// specialists.html. The 20 cards are rendered server-side now, so this module only
// sorts, filters and mirrors the active filters as chips; every value it reads comes
// from a data-* attribute. Shared behaviour (navbar, round cursor) lives in
// resources/js/shared/.

const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => Array.prototype.slice.call(root.querySelectorAll(sel));

// Cities the sidebar lists explicitly; anything else counts as "other".
const KNOWN_CITIES = ['baku', 'sumgait', 'ganja'];

const num = (el, key) => parseFloat(el.dataset[key]) || 0;

const SORTERS = {
  rating: (a, b) => num(b, 'rate') - num(a, 'rate') || num(b, 'reviews') - num(a, 'reviews'),
  reviews: (a, b) => num(b, 'reviews') - num(a, 'reviews'),
  exp: (a, b) => num(b, 'years') - num(a, 'years'),
  cheap: (a, b) => num(a, 'price') - num(b, 'price'),
  projects: (a, b) => num(b, 'projects') - num(a, 'projects'),
};

export default function init() {
  const grid = document.getElementById('spGrid');
  if (!grid) return;

  const cards = $$('.sp-card', grid);
  const empty = document.getElementById('spEmpty');

  // The first paint keeps the Figma order; "rating" only kicks in on the next render.
  let sortKey = 'rating';

  function render() {
    const list = cards.slice().sort(SORTERS[sortKey]);
    list.forEach((card) => grid.appendChild(card));
    if (empty) {
      grid.appendChild(empty);
      empty.hidden = list.some((card) => !card.hidden);
    }
  }

  /* ---- sort dropdown ---- */
  const sortEl = document.getElementById('spSort');
  const sortVal = document.getElementById('spSortVal');
  const sortMenu = document.getElementById('spSortMenu');

  // Every block below is guarded on its own nodes, so a missing id disables that one
  // feature instead of aborting init() and leaving the page half-wired (same as catalog.js).
  if (sortEl && sortVal && sortMenu) {
    sortEl.addEventListener('click', (e) => {
      const li = e.target.closest('li');
      if (li) {
        $$('li', sortMenu).forEach((x) => { x.dataset.on = 'false'; });
        li.dataset.on = 'true';
        sortVal.textContent = li.textContent;
        sortKey = li.dataset.sort;
        render();
        sortEl.dataset.open = 'false';
        return;
      }
      sortEl.dataset.open = sortEl.dataset.open === 'true' ? 'false' : 'true';
    });
    document.addEventListener('click', (e) => {
      if (!e.target.closest('#spSort')) sortEl.dataset.open = 'false';
    });
  }

  /* ---- sidebar filter controls ---- */
  const cats = $$('#spSpecBlock .sp-cat');
  const checks = $$('#spCityBlock .sp-check');
  const radios = $$('#spRateBlock .sp-radio');
  const years = $$('#spYearBlock .sp-year');
  const swVerified = document.getElementById('spVerified');
  const swFree = document.getElementById('spFree');

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

  /* ---- active filter chips (Figma: specialization + city/cities + rating) ---- */
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

    // Clearing must reset EVERY control (the experience chips and the two switches are not
    // mirrored as chips, and #spVerified ships on) and re-run the filter, otherwise the grid
    // keeps showing a filtered result the chip row claims is no longer active.
    clearBtn.addEventListener('click', () => {
      cats.concat(checks, radios, years, switches).forEach((el) => { el.dataset.on = 'false'; });
      renderChips();
      applyFilters();
    });

    renderChips();
  }

  /* ---- "Apply filters" — narrows the list down to the current selection ---- */
  function applyFilters() {
    const cat = $('#spSpecBlock .sp-cat[data-on="true"]');
    const spec = cat ? cat.dataset.spec : null;
    const cities = checks.filter((c) => c.dataset.on === 'true').map((c) => c.dataset.city);
    const r = $('#spRateBlock .sp-radio[data-on="true"]');
    const minRate = r ? parseFloat(r.dataset.min) : 0;
    const y = $('#spYearBlock .sp-year[data-on="true"]');
    const yearMin = y ? +y.dataset.min : 0;
    const yearMax = y ? +y.dataset.max : 99;
    const needVerified = !!swVerified && swVerified.dataset.on === 'true';
    const needFree = !!swFree && swFree.dataset.on === 'true';

    cards.forEach((card) => {
      const d = card.dataset;
      let ok = true;
      if (spec && d.specs.split(',').indexOf(spec) === -1) ok = false;
      // "other" = any city outside the three listed above
      if (ok && cities.length) {
        ok = cities.indexOf(d.city) !== -1 ||
          (cities.indexOf('other') !== -1 && KNOWN_CITIES.indexOf(d.city) === -1);
      }
      if (ok && num(card, 'rate') < minRate) ok = false;
      if (ok && (num(card, 'years') < yearMin || num(card, 'years') > yearMax)) ok = false;
      if (ok && needVerified && d.verified !== 'true') ok = false;
      if (ok && needFree && d.free !== 'true') ok = false;
      card.hidden = !ok;
    });

    render();
  }

  const applyBtn = document.getElementById('spApply');
  if (applyBtn) applyBtn.addEventListener('click', applyFilters);
}
