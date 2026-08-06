// Page module for "blog" — filter tabs now navigate via URL query parameters
// (server-side filtering). The chips are rendered as <a> tags by Blade, so clicking
// them navigates to the filtered URL. This JS handles keyboard navigation and the
// scroll reveal animation.

function initFilters() {
  const list = document.getElementById('blogFilters');
  if (!list) return;

  const chips = Array.from(list.querySelectorAll('.fchip'));

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
  });
}

function initReveal() {
  const hasFeatured = !!document.getElementById('featured');

  document.querySelectorAll('#featured, .sec-head').forEach((el) => el.classList.add('reveal'));

  if (hasFeatured) {
    document
      .querySelectorAll('#blogGrid .post')
      .forEach((el, i) => el.classList.add('reveal', 'd' + ((i % 3) + 1)));
  }

  const io = new IntersectionObserver(
    (entries) => {
      entries.forEach((e) => {
        if (e.isIntersecting) {
          e.target.classList.add('in');
          io.unobserve(e.target);
        }
      });
    },
    { threshold: 0.05, rootMargin: '0px 0px -20px 0px' }
  );
  document.querySelectorAll('.reveal').forEach((el) => io.observe(el));
}

export default function init() {
  initFilters();
  initReveal();
}
