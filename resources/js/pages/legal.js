// Legal page: TOC scroll-spy — the sidebar item whose section is on screen gets
// the active (yellow) state; clicking scrolls smoothly to the anchor.

export default function () {
  const links = [...document.querySelectorAll('[data-toc-link]')];
  if (!links.length) return;

  const byId = new Map(links.map((l) => [l.dataset.target, l]));

  function activate(id) {
    links.forEach((l) => (l.dataset.on = l.dataset.target === id ? 'true' : 'false'));
  }

  const observer = new IntersectionObserver(
    (entries) => {
      const visible = entries
        .filter((e) => e.isIntersecting)
        .sort((a, b) => a.boundingClientRect.top - b.boundingClientRect.top);
      if (visible.length) activate(visible[0].target.id);
    },
    { rootMargin: '-10% 0px -70% 0px' }
  );

  byId.forEach((_, id) => {
    const heading = document.getElementById(id);
    if (heading) observer.observe(heading);
  });

  links.forEach((link) => {
    link.addEventListener('click', (e) => {
      const heading = document.getElementById(link.dataset.target);
      if (!heading) return;
      e.preventDefault();
      heading.scrollIntoView({ behavior: 'smooth', block: 'start' });
      history.replaceState(null, '', '#' + link.dataset.target);
      activate(link.dataset.target);
    });
  });
}
