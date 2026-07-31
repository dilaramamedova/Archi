// Page module for "business-profile-company" — save bar actions and the settings-nav
// active state. Shared behaviour (navbar, cursor) lives in resources/js/shared/.
export default function init() {
  const bar = document.querySelector('.bpc-save-bar');
  const saveBtn = document.querySelector('.bpc-btn-save');
  const cancelBtn = document.querySelector('.bpc-btn-cancel');

  if (saveBtn && bar) {
    saveBtn.addEventListener('click', () => window.alert(bar.dataset.savedMessage || ''));
  }
  if (cancelBtn) {
    cancelBtn.addEventListener('click', () => window.location.reload());
  }

  const items = document.querySelectorAll('.bpc-snav .bpc-snav-item');
  items.forEach((item) =>
    item.addEventListener('click', () => {
      items.forEach((other) => {
        other.dataset.on = 'false';
        delete other.dataset.strong;
      });
      item.dataset.on = 'true';
    })
  );
}
init();
