// Page module for "about". The design is static — the only behaviour is the site-wide
// scroll reveal (shared .reveal / .reveal.in classes from app.css), applied the same way
// home.js and blog.js apply it. The hero is above the fold and is deliberately excluded,
// so the page never renders blank before the observer fires.
export default function init() {
  // section blocks fade in as a whole
  document
    .querySelectorAll('.about-story, .about-values-head, .about-cta')
    .forEach((el) => el.classList.add('reveal'));
  // tiles and cards stagger in threes
  document
    .querySelectorAll('.about-stat, .about-value')
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
