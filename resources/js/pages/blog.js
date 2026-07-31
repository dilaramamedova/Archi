// Page module for "blog" — ported from the inline <script> of the old blog.html.
// The card grid is rendered server-side now, so only the filter tabs and the scroll
// reveal remain. Shared behaviour (navbar, cursor) lives in resources/js/shared/.

// Filter tabs. The active state lives in `data-on` (see blog.css) and is mirrored to
// `aria-selected`; the chips really filter `#blogGrid .post` through `data-cat`.
function initFilters() {
  const list = document.getElementById('blogFilters');
  const grid = document.getElementById('blogGrid');
  const empty = document.getElementById('blogEmpty');
  if (!list || !grid) return;

  const chips = Array.from(list.querySelectorAll('.fchip'));
  const posts = Array.from(grid.querySelectorAll('.post'));

  function select(chip) {
    chips.forEach((x) => {
      const on = x === chip;
      x.dataset.on = String(on);
      x.setAttribute('aria-selected', String(on));
      x.tabIndex = on ? 0 : -1;
    });

    grid.setAttribute('aria-labelledby', chip.id);

    const cat = chip.dataset.cat;
    let shown = 0;
    posts.forEach((p) => {
      const cats = (p.dataset.cat || '').split(' ');
      const match = cat === 'all' || cats.includes(cat);
      p.hidden = !match;
      // The reveal observer already unobserved anything it saw; make sure a card that
      // comes back after being filtered out is not stuck at opacity 0.
      if (match) {
        p.classList.add('in');
        shown += 1;
      }
    });
    if (empty) empty.hidden = shown > 0;
  }

  chips.forEach((c) => c.addEventListener('click', () => select(c)));

  // Arrow-key navigation, as expected of a role="tablist".
  list.addEventListener('keydown', (e) => {
    const i = chips.indexOf(document.activeElement);
    if (i === -1) return;
    const step = { ArrowRight: 1, ArrowLeft: -1 };
    let next = null;
    if (e.key in step) next = (i + step[e.key] + chips.length) % chips.length;
    else if (e.key === 'Home') next = 0;
    else if (e.key === 'End') next = chips.length - 1;
    if (next === null) return;
    e.preventDefault();
    chips[next].focus();
    select(chips[next]);
  });
}

function initReveal() {
  document.querySelectorAll('#featured, .sec-head').forEach((el) => el.classList.add('reveal'));
  document
    .querySelectorAll('#blogGrid .post')
    .forEach((el, i) => el.classList.add('reveal', 'd' + ((i % 3) + 1)));

  const io = new IntersectionObserver(
    (entries) => {
      entries.forEach((e) => {
        if (e.isIntersecting) {
          e.target.classList.add('in');
          io.unobserve(e.target);
        }
      });
    },
    { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
  );
  document.querySelectorAll('.reveal').forEach((el) => io.observe(el));
}

export default function init() {
  initFilters();
  initReveal();
}

init();
