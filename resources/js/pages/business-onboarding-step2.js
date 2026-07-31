// Page module for "business-onboarding-step2": the communication-languages chips.
// They are the only interactive element the frame documents — a plain multi-select.
// The chips ship with az/ru/en selected (the state the Figma frame shows).
export default function init() {
  languageChips();
}

function languageChips() {
  const chips = document.querySelectorAll('[data-language]');
  if (!chips.length) return;

  chips.forEach((chip) =>
    chip.addEventListener('click', () => {
      const on = chip.dataset.on !== 'true';
      chip.dataset.on = String(on);
      chip.setAttribute('aria-checked', String(on));
    })
  );
}
