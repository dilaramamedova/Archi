// Homepage mini calculator (#miniCalc, rendered by components/mini-calculator.blade.php).
//
// Two things are worth knowing before editing this file:
//
// 1. The panel is position:fixed and placed by script rather than being a child of the
//    header. The header nav row that holds "Təmir kalkulyatoru" (.nav-calc) is
//    `display:none` below 900px (app.css), so a CSS-only dropdown would simply not exist
//    on phones and tablets — where the product owner still wants the calculator on
//    landing. Fixed + measured therefore serves both cases: hung under the nav item when
//    that item is on screen, centred under the sticky header when it is not.
// 2. Every price constant comes from shared/calculator-pricing.js, including the chip
//    multipliers — the chips are built here from those maps so the panel cannot quote a
//    number the /calculator page disagrees with.

import {
  RATES,
  TYPE_MULTIPLIERS,
  OBJECT_MULTIPLIERS,
  ROOMS_MULTIPLIERS,
  DEFAULTS,
  clampArea,
  priceFor,
} from './calculator-pricing.js';

// The key /calculator already uses for the quick-calc hand-off — see calculator.js.
const HANDOFF_KEY = 'archi-quickcalc';

const GUTTER = 16; // matches the .wrap gutter at 375px, so the panel lines up with the page
const MAX_WIDTH = 380;

export default function initMiniCalculator() {
  const panel = document.getElementById('miniCalc');
  if (!panel) return;

  const labels = JSON.parse(panel.dataset.labels || '{}');
  const areaEl = document.getElementById('miniCalcArea');
  const priceEl = document.getElementById('miniCalcPrice');
  const resultLabelEl = document.getElementById('miniCalcResultLabel');
  const closeEl = document.getElementById('miniCalcClose');

  // Only the two high-leverage factors are selectable; the rest keep the defaults the
  // /calculator page starts from, so the two screens agree until the visitor changes
  // something the mini panel does not expose.
  const state = { area: DEFAULTS.area, type: DEFAULTS.type, level: DEFAULTS.level };
  const fixedObj = OBJECT_MULTIPLIERS[DEFAULTS.obj];
  const fixedRooms = ROOMS_MULTIPLIERS[DEFAULTS.rooms];

  const fmt = (n) => Math.round(n).toLocaleString('ru-RU');

  // ---- chips -------------------------------------------------------------------
  // Built from the shared multiplier maps rather than authored in Blade: Blade would
  // have to repeat 0.55 / 0.7 / 1 / 1.25 as data-m, which is the duplication this whole
  // module is arranged to avoid. Blade ships only the labels.
  function buildChips(key, entries) {
    const box = panel.querySelector(`[data-mini-key="${key}"]`);
    if (!box) return;
    box.innerHTML = entries
      .map(
        (v) =>
          `<button type="button" class="qc-chip" data-v="${v}" data-on="${v === state[key]}"` +
          ` aria-pressed="${v === state[key]}">${labels[key]?.[v] ?? v}</button>`
      )
      .join('');

    box.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-v]');
      if (!btn) return;
      state[key] = btn.dataset.v;
      box.querySelectorAll('[data-v]').forEach((b) => {
        const on = b.dataset.v === state[key];
        b.dataset.on = String(on);
        b.setAttribute('aria-pressed', String(on));
      });
      render();
    });
  }

  function render() {
    const price = priceFor({
      area: state.area,
      rate: RATES[state.level],
      obj: fixedObj,
      type: TYPE_MULTIPLIERS[state.type],
      rooms: fixedRooms,
    });
    priceEl.textContent = fmt(price);
    resultLabelEl.textContent = (labels.resultLabel || '').replace('{area}', String(state.area));

    // Hand the selection to /calculator. Same key and shape the full page already
    // writes for the detailed calculator, so the CTA continues the session instead of
    // resetting it to the defaults.
    try {
      localStorage.setItem(
        HANDOFF_KEY,
        JSON.stringify({ area: state.area, level: state.level, type: state.type })
      );
    } catch {
      // Private mode / storage disabled: the panel still prices correctly, the
      // hand-off is simply lost. Not worth breaking render() over.
    }
  }

  buildChips('type', Object.keys(TYPE_MULTIPLIERS));
  buildChips('level', Object.keys(RATES));

  areaEl.addEventListener('input', () => {
    state.area = clampArea(areaEl.value);
    render();
  });
  // write the clamped value back once the field is left, so field and price agree
  areaEl.addEventListener('change', () => {
    if (parseFloat(areaEl.value) !== state.area) areaEl.value = String(state.area);
  });

  // ---- placement ---------------------------------------------------------------
  // Re-measured on resize/scroll instead of being computed once: .topbar is sticky, so
  // the anchor's viewport position changes as the page moves under it, and a breakpoint
  // crossing can take the anchor away entirely.
  function place() {
    const vw = document.documentElement.clientWidth;
    const width = Math.min(MAX_WIDTH, vw - GUTTER * 2);
    panel.style.width = `${width}px`;

    const anchor = document.querySelector('.nav-calc');
    // offsetParent is null when an ancestor is display:none — i.e. below 900px, where
    // the whole second nav row is hidden and there is nothing to hang from.
    const rect = anchor && anchor.offsetParent !== null ? anchor.getBoundingClientRect() : null;
    const header = document.querySelector('.topbar');

    let top;
    let left;
    if (rect) {
      top = rect.bottom + 8;
      // right edges aligned, so the panel reads as belonging to that nav item
      left = rect.right - width;
    } else {
      top = (header ? header.getBoundingClientRect().bottom : 0) + 8;
      left = (vw - width) / 2;
    }
    panel.style.top = `${Math.max(GUTTER, top)}px`;
    panel.style.left = `${Math.min(Math.max(GUTTER, left), vw - width - GUTTER)}px`;
    // the phone viewport is shorter than the panel wants to be; it scrolls inside itself
    // rather than pushing the CTA off screen
    panel.style.maxHeight = `${Math.max(160, window.innerHeight - top - GUTTER)}px`;
  }

  // Throttled with a timer rather than requestAnimationFrame on purpose: rAF is frozen
  // while the tab is in the background, so a window resized in a background tab (or in a
  // headless/uncomposited viewport) would leave the panel measured for the old width and
  // only correct itself once the tab is looked at again.
  let queued = 0;
  function schedulePlace() {
    if (queued) return;
    queued = setTimeout(() => {
      queued = 0;
      if (!panel.hidden) place();
    }, 16);
  }

  // ---- open / close ------------------------------------------------------------
  // Bound on the document rather than on the panel: Escape has to close it whether or
  // not focus is still inside, e.g. after the visitor has tabbed on into the page.
  function onKeydown(e) {
    if (e.key === 'Escape') close();
  }

  function close() {
    if (panel.hidden) return;
    panel.hidden = true;
    panel.classList.remove('open');
    window.removeEventListener('resize', schedulePlace);
    window.removeEventListener('scroll', schedulePlace);
    document.removeEventListener('keydown', onKeydown);
    // hand focus back to the nav item the panel belongs to, so a keyboard visitor lands
    // somewhere meaningful instead of at the top of the document
    const anchor = document.querySelector('.nav-calc');
    if (anchor && anchor.offsetParent !== null) anchor.focus({ preventScroll: true });
  }

  function open(moveFocus = false) {
    panel.hidden = false;
    place();
    // The class drives the entrance transition, and a transition needs a starting state
    // the browser has already computed — reading offsetWidth flushes layout so the
    // hidden→open change animates. A requestAnimationFrame would do the same only in a
    // foreground tab; in a background one the panel would stay at opacity 0 forever.
    void panel.offsetWidth;
    panel.classList.add('open');
    window.addEventListener('resize', schedulePlace);
    window.addEventListener('scroll', schedulePlace, { passive: true });
    document.addEventListener('keydown', onKeydown);
    // the module runs before the webfonts land, and the header's own height depends on
    // them — measured once at DOMContentLoaded the panel sat a couple of pixels off
    window.addEventListener('load', schedulePlace, { once: true });
    // Focus is moved only for a deliberate re-open (the nav item, a keyboard user).
    // On the automatic open at page load it is left alone: the panel is the last node
    // in <body>, so auto-focusing it starts a keyboard visitor at the bottom of the
    // document and makes Shift+Tab walk backwards into the footer. Never the number
    // field either — that raises the on-screen keyboard before anything has been read.
    if (moveFocus) panel.focus({ preventScroll: true });
  }

  closeEl.addEventListener('click', close);

  // The header menus are the panel's only real neighbours in the stacking order, and
  // they are the one thing a visitor opens *on purpose*. Yield to them rather than
  // fighting with z-index, which would only move the collision somewhere else.
  document.addEventListener(
    'click',
    (e) => {
      if (panel.hidden) return;
      if (e.target.closest('.nav-item, .nav-menu, .mob-burger, .mega-panel')) close();
    },
    true
  );

  // Clicking anywhere outside dismisses it, the way a popover is expected to behave.
  // Without this the only exit was the X, and QA measured the panel covering the hero
  // CTA ("Endirimlərə bax", 92%) and the register button (88%) with no way past them.
  document.addEventListener('pointerdown', (e) => {
    if (panel.hidden || panel.contains(e.target)) return;
    if (e.target.closest('.nav-calc')) return; // that click is handled as a re-open below
    close();
  });

  // Re-open from the nav item it hangs off. It is a link to /calculator, so the first
  // click now brings the panel back instead of navigating; a visitor who wants the full
  // page has the CTA inside the panel, and a second click on the item still navigates.
  const navCalc = document.querySelector('.nav-calc');
  if (navCalc) {
    navCalc.addEventListener('click', (e) => {
      if (!panel.hidden) return;
      e.preventDefault();
      open(true);
    });
  }

  render();
  open();
}
