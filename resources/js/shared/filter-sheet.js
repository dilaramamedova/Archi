/**
 * Mobile filter sheet controller — shared by /catalog and /specialists.
 *
 * Both pages render the same `.fside` sidebar skeleton. The Figma has no mobile
 * frame for either listing, and inline the sidebar is a very tall wall the visitor
 * must scroll past before the first result, so below 980px catalog.css turns
 * `.fsheet` into a bottom sheet. This module only flips `data-open` and locks the
 * body scroll; above 980px the CSS ignores `data-open` entirely, so nothing here
 * can affect the desktop layout.
 *
 * Expected markup (see catalog.blade.php / specialists.blade.php):
 *   <button class="fsheet-btn" id={btn} aria-expanded="false"><span class="n" hidden></span></button>
 *   <aside class="fside fsheet" id={sheet} data-open="false"> … </aside>
 *   <div class="fsheet-scrim" id={scrim} hidden></div>
 */
import { lockScroll, unlockScroll } from './scroll-lock.js';

export default function initFilterSheet({ sheet, btn, scrim, close }) {
  const sheetEl = document.getElementById(sheet);
  const btnEl = document.getElementById(btn);
  const scrimEl = document.getElementById(scrim);
  const closeEl = close ? document.getElementById(close) : null;
  if (!sheetEl || !btnEl || !scrimEl) return null;

  const wide = window.matchMedia('(min-width: 981px)');
  let locked = false;

  // The lock has to sit on <html>, not <body> — see shared/scroll-lock.js for why.
  function setLock(on) {
    if (on && !locked) { lockScroll(); locked = true; }
    else if (!on && locked) { unlockScroll(); locked = false; }
  }

  // Everything focusable inside the sheet, in DOM order.
  const FOCUSABLE = 'a[href],button:not([disabled]),input:not([disabled]),select,textarea,[tabindex]:not([tabindex="-1"])';
  const focusables = () =>
    [...sheetEl.querySelectorAll(FOCUSABLE)].filter((el) => el.offsetParent !== null);

  function setOpen(open) {
    if (open === (sheetEl.dataset.open === 'true')) return;
    sheetEl.dataset.open = open ? 'true' : 'false';
    btnEl.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (open) {
      scrimEl.hidden = false;
      // one frame with the scrim mounted but still transparent, so the fade runs
      requestAnimationFrame(() => { scrimEl.dataset.open = 'true'; });
      setLock(true);
      // The sheet covers the page, so focus has to come with it — otherwise the
      // next Tab walks the hidden page behind the scrim.
      if (!wide.matches) requestAnimationFrame(() => (closeEl || focusables()[0])?.focus());
    } else {
      scrimEl.dataset.open = 'false';
      setLock(false);
      if (!wide.matches && sheetEl.contains(document.activeElement)) btnEl.focus();
      window.setTimeout(() => {
        if (sheetEl.dataset.open !== 'true') scrimEl.hidden = true;
      }, 300);
    }
  }

  // Tab trap, only while the CSS actually renders the sidebar as a sheet.
  sheetEl.addEventListener('keydown', (e) => {
    if (e.key !== 'Tab' || wide.matches || sheetEl.dataset.open !== 'true') return;
    const items = focusables();
    if (!items.length) return;
    const first = items[0];
    const last = items[items.length - 1];
    if (e.shiftKey && document.activeElement === first) {
      e.preventDefault();
      last.focus();
    } else if (!e.shiftKey && document.activeElement === last) {
      e.preventDefault();
      first.focus();
    }
  });

  btnEl.addEventListener('click', () => setOpen(sheetEl.dataset.open !== 'true'));
  scrimEl.addEventListener('click', () => setOpen(false));
  if (closeEl) closeEl.addEventListener('click', () => setOpen(false));
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') setOpen(false);
  });
  // Rotating to a desktop width must not leave the page scroll-locked behind a panel
  // the CSS has already turned back into a static sidebar.
  wide.addEventListener('change', (e) => { if (e.matches) setOpen(false); });

  return {
    close: () => setOpen(false),
    /** Badge on the trigger: how many filters are currently selected. */
    setCount(n) {
      const badge = btnEl.querySelector('.n');
      if (!badge) return;
      badge.textContent = String(n);
      badge.hidden = n < 1;
    },
  };
}
