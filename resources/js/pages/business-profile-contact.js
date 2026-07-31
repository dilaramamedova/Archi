// Page module for "business-profile-contact" — communication language chips toggle
// between the selected and unselected state (data-on drives the CSS, aria-checked the
// assistive tech), plus the save bar actions (same behaviour as business-profile-company).
export default function init() {
  document.querySelectorAll('.bpco-lchip').forEach((chip) =>
    chip.addEventListener('click', () => {
      const on = chip.dataset.on !== 'true';
      chip.dataset.on = on ? 'true' : 'false';
      chip.setAttribute('aria-checked', on ? 'true' : 'false');
    })
  );

  const bar = document.querySelector('.bpco-save-bar');
  const saveBtn = document.querySelector('.bpco-btn-save');
  const cancelBtn = document.querySelector('.bpco-btn-cancel');

  if (saveBtn && bar) {
    saveBtn.addEventListener('click', () => window.alert(bar.dataset.savedMessage || ''));
  }
  if (cancelBtn) {
    cancelBtn.addEventListener('click', () => window.location.reload());
  }
}
init();
