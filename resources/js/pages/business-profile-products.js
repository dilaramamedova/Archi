// Page module for "business-profile-products" — the visibility switch of each product
// row, the search / category / status filters, the pager and the save bar. The old page
// shipped an empty handler: the switches were static images and the filters and the pager
// were plain <div>s. Save confirms inline inside the bar (no blocking alert), Cancel
// discards the local state by reloading. Same save behaviour on the sibling
// business-profile-* pages.

// A row's status is derived, not stored: flipping the visibility switch has to move the
// row between the "active" / "low" / "hidden" buckets immediately.
function statusOf(row) {
  if (row.querySelector('.ui-toggle')?.dataset.on !== 'true') return 'hidden';
  return row.querySelector('.ui-badge')?.dataset.tone === 'warn' ? 'low' : 'active';
}

// Returns the function that re-applies the current filter state to the rows.
function initFilters(card) {
  const rows = Array.from(card.querySelectorAll('.cab-row'));
  const empty = card.querySelector('.bpp-empty');
  const pager = card.querySelector('.bpp-pager');
  const input = card.querySelector('.bpp-search input');
  const state = { q: '', cat: 'all', status: 'all' };

  const apply = () => {
    let shown = 0;
    rows.forEach((row) => {
      const text = (row.querySelector('.bpp-info')?.textContent || '').toLowerCase();
      const ok =
        (state.q === '' || text.includes(state.q)) &&
        (state.cat === 'all' || row.dataset.cat === state.cat) &&
        (state.status === 'all' || statusOf(row) === state.status);
      row.hidden = !ok;
      if (ok) shown += 1;
    });
    if (empty) empty.hidden = shown > 0;
    // Paging a client-filtered subset would be a lie, so the pager is only on screen
    // while the full list is.
    if (pager) pager.hidden = state.q !== '' || state.cat !== 'all' || state.status !== 'all';
  };

  if (input) {
    input.addEventListener('input', () => {
      state.q = input.value.trim().toLowerCase();
      apply();
    });
  }

  const wraps = Array.from(card.querySelectorAll('.bpp-drop-wrap'));
  const closeAll = (except) =>
    wraps.forEach((w) => {
      if (w === except) return;
      w.dataset.open = 'false';
      w.querySelector('.bpp-drop')?.setAttribute('aria-expanded', 'false');
    });

  wraps.forEach((wrap) => {
    const btn = wrap.querySelector('.bpp-drop');
    const menu = wrap.querySelector('.bpp-drop-menu');
    if (!btn || !menu) return;

    btn.addEventListener('click', () => {
      const open = wrap.dataset.open !== 'true';
      closeAll(wrap);
      wrap.dataset.open = open ? 'true' : 'false';
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });

    menu.addEventListener('click', (e) => {
      const li = e.target.closest('li');
      if (!li) return;
      menu.querySelectorAll('li').forEach((x) => {
        x.dataset.on = 'false';
        x.setAttribute('aria-selected', 'false');
      });
      li.dataset.on = 'true';
      li.setAttribute('aria-selected', 'true');
      state[wrap.dataset.filter] = li.dataset.value;
      // Only a tint — the trigger keeps its label so the filter row never resizes.
      btn.dataset.active = li.dataset.value === 'all' ? 'false' : 'true';
      closeAll();
      apply();
    });
  });

  document.addEventListener('click', (e) => {
    if (!e.target.closest('.bpp-drop-wrap')) closeAll();
  });
  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    const open = wraps.find((w) => w.dataset.open === 'true');
    if (!open) return;
    closeAll();
    open.querySelector('.bpp-drop')?.focus();
  });

  return apply;
}

// No server-side pages exist, so the pager only moves the current-page marker. It is
// still wired: an arrow at the end of the range disables itself instead of doing nothing.
function initPager(card) {
  const pager = card.querySelector('.bpp-pager');
  if (!pager) return;
  const pages = Array.from(pager.querySelectorAll('.bpp-pg[data-page]'));
  const prev = pager.querySelector('.bpp-pg[data-nav="prev"]');
  const next = pager.querySelector('.bpp-pg[data-nav="next"]');
  if (!pages.length) return;

  const select = (i) => {
    pages.forEach((p, n) => {
      const on = n === i;
      p.dataset.on = on ? 'true' : 'false';
      if (on) p.setAttribute('aria-current', 'page');
      else p.removeAttribute('aria-current');
    });
    if (prev) prev.disabled = i === 0;
    if (next) next.disabled = i === pages.length - 1;
  };
  const current = () => Math.max(0, pages.findIndex((p) => p.dataset.on === 'true'));

  pages.forEach((p, i) => p.addEventListener('click', () => select(i)));
  if (prev) prev.addEventListener('click', () => select(Math.max(0, current() - 1)));
  if (next) next.addEventListener('click', () => select(Math.min(pages.length - 1, current() + 1)));
  select(current());
}

export default function init() {
  const card = document.querySelector('.cab-card');
  const bar = document.querySelector('.cab-save-bar');
  const msg = bar ? bar.querySelector('.msg') : null;
  const unsavedText = msg ? msg.textContent : '';
  const savedText = bar ? (bar.dataset.savedMessage || '').trim() : '';

  // `saved` only swaps the message text and the dot color — the box keeps its size.
  const setSaved = (on) => {
    if (!bar) return;
    bar.dataset.saved = on ? 'true' : 'false';
    if (msg) msg.textContent = on && savedText ? savedText : unsavedText;
  };

  const apply = card ? initFilters(card) : () => {};

  document.querySelectorAll('.ui-toggle').forEach((toggle) => {
    toggle.addEventListener('click', () => {
      const on = toggle.dataset.on !== 'true';
      toggle.dataset.on = on ? 'true' : 'false';
      toggle.setAttribute('aria-pressed', on ? 'true' : 'false');
      setSaved(false);
      apply();
    });
  });

  if (card) initPager(card);

  if (bar) {
    const saveBtn = bar.querySelector('[data-save]');
    const cancelBtn = bar.querySelector('[data-cancel]');
    if (saveBtn) saveBtn.addEventListener('click', () => setSaved(true));
    if (cancelBtn) cancelBtn.addEventListener('click', () => window.location.reload());
  }
}
