// Page module for "business-profile-company" — save bar actions.
// The settings nav is a list of <a href> links: the active row is rendered server-side,
// so it needs no click handler. Toggling data-on on click only re-weighted the label
// (medium -> bold) and reflowed the row for one frame before the browser navigated away.
// Shared behaviour (navbar, cursor) lives in resources/js/shared/.
export default function init() {
  const bar = document.querySelector('.cab-save-bar');
  const saveBtn = bar?.querySelector('[data-save]');
  const cancelBtn = bar?.querySelector('[data-cancel]');

  if (saveBtn && bar) {
    saveBtn.addEventListener('click', () => window.alert(bar.dataset.savedMessage || ''));
  }
  if (cancelBtn) {
    cancelBtn.addEventListener('click', () => window.location.reload());
  }
}
init();
