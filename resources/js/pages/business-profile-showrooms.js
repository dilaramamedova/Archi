// Page module for "business-profile-showrooms" — save bar actions. Save confirms inline
// inside the bar (no blocking alert), Cancel discards the local state by reloading. The
// selectors are the shared <x-cabinet.save-bar> contract (.cab-save-bar / .msg /
// [data-save] / [data-cancel]). The settings-nav active item is server-rendered. Shared
// behaviour (navbar, cursor) lives in resources/js/shared/.
export default function init() {
  const bar = document.querySelector('.cab-save-bar');
  if (!bar) return;

  const msg = bar.querySelector('.msg');
  const unsavedText = msg ? msg.textContent : '';
  const savedText = (bar.dataset.savedMessage || '').trim();

  // `saved` only swaps the message text and the dot color — the box keeps its size.
  const setSaved = (on) => {
    bar.dataset.saved = on ? 'true' : 'false';
    if (msg) msg.textContent = on && savedText ? savedText : unsavedText;
  };

  const saveBtn = bar.querySelector('[data-save]');
  const cancelBtn = bar.querySelector('[data-cancel]');
  if (saveBtn) saveBtn.addEventListener('click', () => setSaved(true));
  if (cancelBtn) cancelBtn.addEventListener('click', () => window.location.reload());
}
