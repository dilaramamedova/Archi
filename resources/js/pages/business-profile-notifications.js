// Page module for "business-profile-notifications" — the notification switches and the
// channel chips flip their data-on state (the CSS draws both states), and the save bar
// confirms inline (no blocking alert) while Cancel discards the local state by reloading.
// Shared behaviour (navbar, cursor) lives in resources/js/shared/.
export default function init() {
  const bar = document.querySelector('.bpn-save-bar');
  const msg = bar ? bar.querySelector('.bpn-save-msg') : null;
  const unsavedText = msg ? msg.textContent : '';
  const savedText = bar ? (bar.dataset.savedMessage || '').trim() : '';

  // `saved` only swaps the message text and the dot color — the box keeps its size.
  const setSaved = (on) => {
    if (!bar) return;
    bar.dataset.saved = on ? 'true' : 'false';
    if (msg) msg.textContent = on && savedText ? savedText : unsavedText;
  };

  document.querySelectorAll('.bpn-toggle').forEach((toggle) =>
    toggle.addEventListener('click', () => {
      const on = toggle.dataset.on !== 'true';
      toggle.dataset.on = on ? 'true' : 'false';
      toggle.setAttribute('aria-pressed', on ? 'true' : 'false');
      setSaved(false);
    })
  );

  document.querySelectorAll('.bpn-chip').forEach((chip) =>
    chip.addEventListener('click', () => {
      const on = chip.dataset.on !== 'true';
      chip.dataset.on = on ? 'true' : 'false';
      chip.setAttribute('aria-checked', on ? 'true' : 'false');
      setSaved(false);
    })
  );

  if (bar) {
    const saveBtn = bar.querySelector('.bpn-btn-save');
    const cancelBtn = bar.querySelector('.bpn-btn-cancel');
    if (saveBtn) saveBtn.addEventListener('click', () => setSaved(true));
    if (cancelBtn) cancelBtn.addEventListener('click', () => window.location.reload());
  }
}
init();
