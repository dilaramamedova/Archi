// Page module for "business-onboarding-step3": the category dropdown.
// The markup ships closed (data-on="false", nothing selected); the Figma frame that shows
// the menu open only documents the open state. This module drives open/close and selection.
export default function init() {
  const trigger = document.querySelector('[data-cat-trigger]');
  const menu = document.querySelector('[data-cat-menu]');
  if (!trigger || !menu) return;

  const value = trigger.querySelector('[data-cat-value]');
  const caret = trigger.querySelector('[data-cat-caret]');
  const options = menu.querySelectorAll('[data-cat-option]');

  const setOpen = (open) => {
    trigger.dataset.on = String(open);
    menu.dataset.on = String(open);
    if (caret) caret.textContent = open ? '▴' : '▾';
  };

  trigger.addEventListener('click', (e) => {
    e.stopPropagation();
    setOpen(menu.dataset.on !== 'true');
  });

  options.forEach((option) =>
    option.addEventListener('click', () => {
      options.forEach((x) => (x.dataset.sel = 'false'));
      option.dataset.sel = 'true';
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
}
