// Page module for "business-profile-company" — save bar actions.
// The settings nav is a list of <a href> links: the active row is rendered server-side,
// so it needs no click handler. Toggling data-on on click only re-weighted the label
// (medium -> bold) and reflowed the row for one frame before the browser navigated away.
// Shared behaviour (navbar, cursor) lives in resources/js/shared/.
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
}
init();
