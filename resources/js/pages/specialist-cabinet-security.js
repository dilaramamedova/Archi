// Page module for "specialist-cabinet-security" — the eye button reveals the password
// value (the .dots text swaps between data-mask and data-value, both styled the same so
// the field does not reflow), the 2FA switch flips its data-on state, and the save bar
// confirms inline (no blocking alert) while Cancel discards the local state by reloading.
// Selectors follow the shared cabinet contract: .cab-save-bar / .msg / [data-save] /
// [data-cancel] (see components/cabinet/save-bar.blade.php) and .ui-toggle.
export default function init() {
  const bar = document.querySelector('.cab-save-bar');
  const msg = bar ? bar.querySelector('.msg') : null;
  const unsavedText = msg ? msg.textContent : '';
  const savedText = bar ? (bar.dataset.savedMessage || '').trim() : '';

  // `saved` only swaps the message text and the dot color — the box keeps its size.
  const setSaved = (on) => {
    if (!bar) return;
    bar.dataset.saved = on ? 'true' : 'false';
    if (msg) msg.textContent = on && savedText ? savedText : unsavedText;
  };

  document.querySelectorAll('.spsec-input .eye').forEach((eye) =>
    eye.addEventListener('click', () => {
      const on = eye.dataset.on !== 'true';
      eye.dataset.on = String(on);
      eye.setAttribute('aria-pressed', String(on));
      const dots = eye.parentElement.querySelector('.dots');
      if (dots) dots.textContent = on ? dots.dataset.value : dots.dataset.mask;
    })
  );

  document.querySelectorAll('.ui-toggle').forEach((toggle) =>
    toggle.addEventListener('click', () => {
      const on = toggle.dataset.on !== 'true';
      toggle.dataset.on = String(on);
      toggle.setAttribute('aria-pressed', String(on));
      setSaved(false);
    })
  );

  // The log-out button removes the session row it belongs to.
  document.querySelectorAll('.spsec-logout').forEach((btn) =>
    btn.addEventListener('click', () => {
      const row = btn.closest('.cab-row');
      if (row) row.hidden = true;
      setSaved(false);
    })
  );

  if (bar) {
    const saveBtn = bar.querySelector('[data-save]');
    const cancelBtn = bar.querySelector('[data-cancel]');
    if (saveBtn) saveBtn.addEventListener('click', () => setSaved(true));
    if (cancelBtn) cancelBtn.addEventListener('click', () => window.location.reload());
  }
}
