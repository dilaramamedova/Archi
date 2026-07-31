// Page module for "blog" — ported from the inline <script> of the old blog.html.
// The card grid is rendered server-side now, so only the filter chips and the scroll
// reveal remain. Shared behaviour (navbar, cursor) lives in resources/js/shared/.

// Filter tabs — active state lives in the `data-on` attribute (see blog.css).
function initFilters() {
  const chips = document.querySelectorAll('.fchip');
  chips.forEach((c) =>
    c.addEventListener('click', (e) => {
      e.preventDefault();
      chips.forEach((x) => { x.dataset.on = 'false'; });
      c.dataset.on = 'true';
    })
  );
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
