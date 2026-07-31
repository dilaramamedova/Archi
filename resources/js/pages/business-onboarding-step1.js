// Page module for "business-onboarding-step1": the city dropdown and the logo picker.
// The markup ships closed (data-on="false", nothing selected); the Figma frame only
// documents the closed state. Mirrors the step-3 category dropdown.
export default function init() {
  cityDropdown();
  logoPicker();
}

function cityDropdown() {
  const trigger = document.querySelector('[data-city-trigger]');
  const menu = document.querySelector('[data-city-menu]');
  if (!trigger || !menu) return;

  const value = trigger.querySelector('[data-city-value]');
  const caret = trigger.querySelector('[data-city-caret]');
  const field = document.querySelector('[data-city-field]');
  const options = menu.querySelectorAll('[data-city-option]');

  const setOpen = (open) => {
    trigger.dataset.on = String(open);
    menu.dataset.on = String(open);
    trigger.setAttribute('aria-expanded', String(open));
    if (caret) caret.textContent = open ? '▴' : '▾';
  };

  trigger.addEventListener('click', (e) => {
    e.stopPropagation();
    setOpen(menu.dataset.on !== 'true');
  });

  trigger.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      setOpen(menu.dataset.on !== 'true');
    } else if (e.key === 'Escape') {
      setOpen(false);
    }
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
        value.textContent = option.dataset.cityOption;
        value.dataset.filled = 'true';
      }
      if (field) field.value = option.dataset.cityOption;
      setOpen(false);
    })
  );

  document.addEventListener('click', (e) => {
    if (!trigger.contains(e.target) && !menu.contains(e.target)) setOpen(false);
  });
}

function logoPicker() {
  const input = document.querySelector('[data-logo-input]');
  const text = document.querySelector('[data-logo-text]');
  if (!input || !text) return;

  input.addEventListener('change', () => {
    const file = input.files && input.files[0];
    text.textContent = file ? file.name : input.dataset.emptyLabel;
  });
}

init();
