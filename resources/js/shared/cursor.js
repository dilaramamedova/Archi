// Shared round CTA "cursor", ported from the `prodCursor()` block of the old archi.js.
// `.prod-cursor` is injected into every matching card and the events are delegated on
// document, so it also works on pages that render (and re-render) cards from JS.
// Labels come from <body data-cur-product="..." data-cur-details="...">, already
// translated server-side. Adding a new card type is one line in TARGETS.

const ds = document.body.dataset;
const TARGETS = [
  { sel: '.pcard', label: ds.curProduct || '' },
  { sel: '.sp-card', label: ds.curDetails || '' },
];
const SEL = TARGETS.map((t) => t.sel).join(',');

let active = null;
let pending = false;

function labelFor(card) {
  const t = TARGETS.find((t) => card.matches(t.sel));
  return t ? t.label : ds.curDetails || '';
}

function ensure(card) {
  let cur = card.querySelector('.prod-cursor');
  if (cur) return cur;
  cur = document.createElement('div');
  cur.className = 'prod-cursor';
  cur.appendChild(document.createElement('span')).textContent = labelFor(card);
  card.insertBefore(cur, card.firstChild);
  return cur;
}

function mountAll() {
  document.querySelectorAll(SEL).forEach((card) => {
    card.classList.add('hascur');
    ensure(card);
  });
}

function clear() {
  if (active) {
    active.classList.remove('cursing');
    active = null;
  }
}

mountAll();

const mo = new MutationObserver(() => {
  if (pending) return;
  pending = true;
  requestAnimationFrame(() => { pending = false; mountAll(); });
});
mo.observe(document.body, { childList: true, subtree: true });

document.addEventListener(
  'mousemove',
  (e) => {
    const card = e.target && e.target.closest ? e.target.closest(SEL) : null;
    if (card !== active) {
      clear();
      if (card) {
        active = card;
        ensure(card);
        card.classList.add('cursing');
      }
    }
    if (!active) return;
    const cur = active.querySelector('.prod-cursor');
    if (!cur) return;
    const r = active.getBoundingClientRect();
    cur.style.left = e.clientX - r.left + 'px';
    cur.style.top = e.clientY - r.top + 'px';
  },
  { passive: true }
);

document.addEventListener('mouseleave', clear);
window.addEventListener('blur', clear);
