/**
 * Card rows (.grid4 / .blog-grid / .fbt-cards) scroll sideways instead of
 * wrapping — see the .crow-row rules in app.css. This module adds the arrow
 * buttons on top of that, but only for rows that actually overflow: with four
 * cards per view on desktop that means "more than 4 cards", which is exactly
 * when a row would otherwise have grown a second line.
 *
 * The buttons are injected rather than written into every blade, so any current
 * or future card row gets them for free. Touch devices keep the native swipe
 * (the arrows are hidden under 900px in CSS).
 *
 * Opt a row out with data-no-slider.
 */

const ROW_SELECTOR = '.grid4, .blog-grid, .fbt-cards';
const ARROW_ICON = '/assets/icon-arrow-right.svg';

function button(kind, label) {
  const b = document.createElement('button');
  b.type = 'button';
  b.className = `crow-btn ${kind}`;
  b.setAttribute('aria-label', label);
  b.innerHTML = `<img src="${ARROW_ICON}" alt="">`;
  return b;
}

function setup(row) {
  if (row.dataset.crowReady || row.dataset.noSlider !== undefined) return;
  row.dataset.crowReady = '1';

  const d = document.body.dataset;
  const wrap = document.createElement('div');
  wrap.className = 'crow';
  row.parentNode.insertBefore(wrap, row);
  wrap.appendChild(row);

  const prev = button('prev', d.lPrev || 'Əvvəlki');
  const next = button('next', d.lNext || 'Növbəti');
  wrap.append(prev, next);

  const sync = () => {
    // 2px of slack: sub-pixel widths otherwise leave a permanently "scrollable" row
    const max = row.scrollWidth - row.clientWidth;
    const overflows = max > 2;
    prev.hidden = !overflows || row.scrollLeft <= 2;
    next.hidden = !overflows || row.scrollLeft >= max - 2;
  };

  const step = () => Math.max(row.clientWidth * 0.9, 1);
  prev.addEventListener('click', () => row.scrollBy({ left: -step(), behavior: 'smooth' }));
  next.addEventListener('click', () => row.scrollBy({ left: step(), behavior: 'smooth' }));

  row.addEventListener('scroll', sync, { passive: true });
  if (window.ResizeObserver) new ResizeObserver(sync).observe(row);
  // page modules run after this one and can add cards (home.js seeds the
  // product grid from localStorage), which changes scrollWidth silently
  if (window.MutationObserver) new MutationObserver(sync).observe(row, { childList: true });
  window.addEventListener('resize', sync);
  // images load after this runs and change scrollWidth too
  window.addEventListener('load', sync);
  sync();
}

export function initCardSliders(root = document) {
  root.querySelectorAll(ROW_SELECTOR).forEach(setup);
}

initCardSliders();
