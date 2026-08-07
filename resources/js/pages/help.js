// Help / FAQ page: topic tabs switch which question set is shown, and each
// question is an expand/collapse accordion. Heights are recomputed on tab switch
// so a panel that was hidden at load (scrollHeight 0) still animates correctly.
export default function init() {
  const tabs = Array.from(document.querySelectorAll('[data-faq-tab]'));
  const panels = Array.from(document.querySelectorAll('[data-faq-panel]'));
  if (!tabs.length || !panels.length) return;

  const refresh = (item) => {
    const body = item.querySelector('[data-faq-body]');
    if (!body) return;
    body.style.maxHeight = item.dataset.open === 'true' ? `${body.scrollHeight}px` : '0px';
  };

  const activate = (slug) => {
    tabs.forEach((t) => (t.dataset.active = String(t.dataset.slug === slug)));
    panels.forEach((p) => {
      const on = p.dataset.slug === slug;
      p.hidden = !on;
      if (on) p.querySelectorAll('[data-faq-item]').forEach(refresh);
    });
  };

  tabs.forEach((tab) => tab.addEventListener('click', () => activate(tab.dataset.slug)));

  // Accordion toggle (one handler; heights recomputed live so hidden panels work).
  document.querySelectorAll('[data-faq-item]').forEach((item) => {
    const toggle = item.querySelector('[data-faq-toggle]');
    toggle?.addEventListener('click', () => {
      item.dataset.open = item.dataset.open === 'true' ? 'false' : 'true';
      refresh(item);
    });
  });

  // Search: filter questions across all topics; empty query restores tab behaviour.
  const search = document.getElementById('helpSearch');
  search?.addEventListener('input', () => {
    const q = search.value.trim().toLowerCase();
    if (!q) {
      tabs.forEach((t) => (t.hidden = false));
      activate(tabs.find((t) => t.dataset.active === 'true')?.dataset.slug || tabs[0].dataset.slug);
      return;
    }
    // Show every panel, filter items by text match.
    tabs.forEach((t) => (t.dataset.active = 'false'));
    panels.forEach((p) => {
      let anyVisible = false;
      p.querySelectorAll('[data-faq-item]').forEach((item) => {
        const text = item.textContent.toLowerCase();
        const match = text.includes(q);
        item.hidden = !match;
        if (match) { anyVisible = true; item.dataset.open = 'true'; refresh(item); }
      });
      p.hidden = !anyVisible;
    });
  });

  activate(tabs[0].dataset.slug);
}
