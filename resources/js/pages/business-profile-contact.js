// Page module for "business-profile-contact" — communication language chips toggle
// between the selected and unselected state (data-on drives the CSS, aria-checked the
// assistive tech), plus the save bar actions (same behaviour as business-profile-company).
// Selectors follow the shared component contract: .ui-chip and .cab-save-bar.
export default function init() {
  document.querySelectorAll('.ui-chip').forEach((chip) =>
    chip.addEventListener('click', () => {
      const on = chip.dataset.on !== 'true';
      chip.dataset.on = on ? 'true' : 'false';
      chip.setAttribute('aria-checked', on ? 'true' : 'false');
    })
  );

  const bar = document.querySelector('.cab-save-bar');
  const saveBtn = document.querySelector('.cab-save-bar [data-save]');
  const cancelBtn = document.querySelector('.cab-save-bar [data-cancel]');

  if (saveBtn && bar) {
    saveBtn.addEventListener('click', () => window.alert(bar.dataset.savedMessage || ''));
  }
  if (cancelBtn) {
    cancelBtn.addEventListener('click', () => window.location.reload());
  }
}
