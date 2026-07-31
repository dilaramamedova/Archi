// Page module for "business-profile-showrooms" — save bar actions. Save confirms inline
// inside the bar (no blocking alert), Cancel discards the local state by reloading. The
// settings-nav active item is server-rendered. Shared behaviour (navbar, cursor) lives in
// resources/js/shared/.
export default function init() {
  const bar = document.querySelector('.bpsh-save-bar');
  if (!bar) return;

  const msg = bar.querySelector('.bpsh-save-msg');
  const unsavedText = msg ? msg.textContent : '';
  const savedText = (bar.dataset.savedMessage || '').trim();

  // `saved` only swaps the message text and the dot color — the box keeps its size.
  const setSaved = (on) => {
    bar.dataset.saved = on ? 'true' : 'false';
    if (msg) msg.textContent = on && savedText ? savedText : unsavedText;
  };

  const saveBtn = bar.querySelector('.bpsh-btn-save');
  const cancelBtn = bar.querySelector('.bpsh-btn-cancel');
  if (saveBtn) saveBtn.addEventListener('click', () => setSaved(true));
  if (cancelBtn) cancelBtn.addEventListener('click', () => window.location.reload());
}
init();
