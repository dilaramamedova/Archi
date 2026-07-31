// Page module for "business-onboarding-step4": the category listbox.
// Figma 1105:21287 documents the OPEN state, so the markup ships open with the second
// category preselected. This module drives open/close and selection from there on.
export default function init() {
  const trigger = document.querySelector('[data-cat-trigger]');
  const menu = document.querySelector('[data-cat-menu]');
  if (!trigger || !menu) return;

  const value = trigger.querySelector('[data-cat-value]');
  const options = menu.querySelectorAll('[data-cat-option]');

  const setOpen = (open) => {
    trigger.dataset.on = String(open);
    menu.dataset.on = String(open);
    trigger.setAttribute('aria-expanded', String(open));
  };

  trigger.addEventListener('click', (e) => {
    e.stopPropagation();
    setOpen(menu.dataset.on !== 'true');
  });

  options.forEach((option) =>
    option.addEventListener('click', () => {
      options.forEach((x) => {
        x.dataset.sel = 'false';
        x.setAttribute('aria-selected', 'false');
      });
      option.dataset.sel = 'true';
      option.setAttribute('aria-selected', 'true');
      if (value) {
        value.textContent = option.dataset.catOption;
        value.dataset.filled = 'true';
      }
      setOpen(false);
    })
  );

  document.addEventListener('click', (e) => {
    if (!trigger.contains(e.target) && !menu.contains(e.target)) setOpen(false);
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') setOpen(false);
  });
}
