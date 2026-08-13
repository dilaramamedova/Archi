/**
 * Page scroll lock for overlays (mobile drawer, popups, login modal, filter sheet).
 *
 * `overflow:hidden` on <body> alone does NOT lock this site: app.css sets
 * `html { overflow-x: hidden }`, and once one axis is non-`visible` the other
 * computes to `auto` — so <html> stays the scrolling element and body's overflow
 * never propagates to the viewport. Measured before this fix: a wheel over an open
 * drawer scrolled the page ~500px behind it. The lock has to sit on <html>, the
 * real `document.scrollingElement`.
 *
 * Reference-counted because overlays stack (a popup can open above the login
 * modal); the page must stay locked until the last one closes. Restoring to the
 * captured value — normally '' — hands `overflow-x: hidden` back to the stylesheet.
 */
let depth = 0;
let prevRoot = '';
let prevBody = '';

export function lockScroll() {
  if (depth === 0) {
    prevRoot = document.documentElement.style.overflow;
    prevBody = document.body.style.overflow;
    document.documentElement.style.overflow = 'hidden';
    document.body.style.overflow = 'hidden';
  }
  depth += 1;
}

export function unlockScroll() {
  if (depth === 0) return;
  depth -= 1;
  if (depth === 0) {
    document.documentElement.style.overflow = prevRoot;
    document.body.style.overflow = prevBody;
  }
}
