// Page module for "business-profile-showrooms" — save bar actions and the settings-nav
// active state. Shared behaviour (navbar, cursor) lives in resources/js/shared/.
export default function init() {
  const bar = document.querySelector('.bpsh-save-bar');
  const saveBtn = document.querySelector('.bpsh-btn-save');
  const cancelBtn = document.querySelector('.bpsh-btn-cancel');

  if (saveBtn && bar) {
    saveBtn.addEventListener('click', () => window.alert(bar.dataset.savedMessage || ''));
  }
  if (cancelBtn) {
    cancelBtn.addEventListener('click', () => window.location.reload());
  }

  const items = document.querySelectorAll('.bpsh-snav .bpsh-snav-item');
  items.forEach((item) =>
    item.addEventListener('click', () => {
      items.forEach((other) => (other.dataset.on = 'false'));
      item.dataset.on = 'true';
    })
  );
}
init();
