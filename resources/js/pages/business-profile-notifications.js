// Page module for "business-profile-notifications" — the switches and channel chips
// flip their data-on state locally; Save PUTs the assembled notification_settings
// object to /business/profile/notifications. Cancel reloads to discard local state.
export default function init() {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

  const bar = document.querySelector('.cab-save-bar');
  const msg = bar ? bar.querySelector('.msg') : null;
  const unsavedText = msg ? msg.textContent : '';
  const savedText = bar ? (bar.dataset.savedMessage || '').trim() : '';

  // `saved` only swaps the message text and the dot color — the box keeps its size.
  const setSaved = (on) => {
    if (!bar) return;
    bar.dataset.saved = on ? 'true' : 'false';
    // setSaved is only ever called in response to a real edit, so a false here means dirty.
    bar.dataset.dirty = on ? 'false' : 'true';
    if (msg) msg.textContent = on && savedText ? savedText : unsavedText;
  };

  document.querySelectorAll('.bpn-row .ui-toggle').forEach((toggle) =>
    toggle.addEventListener('click', () => {
      const on = toggle.dataset.on !== 'true';
      toggle.dataset.on = on ? 'true' : 'false';
      toggle.setAttribute('aria-pressed', on ? 'true' : 'false');
      setSaved(false);
    })
  );

  document.querySelectorAll('.bpn-chips .ui-chip').forEach((chip) =>
    chip.addEventListener('click', () => {
      const on = chip.dataset.on !== 'true';
      chip.dataset.on = on ? 'true' : 'false';
      chip.setAttribute('aria-checked', on ? 'true' : 'false');
      setSaved(false);
    })
  );

  if (bar) {
    const saveBtn = bar.querySelector('[data-save]');
    const cancelBtn = bar.querySelector('[data-cancel]');

    if (saveBtn) {
      saveBtn.addEventListener('click', async () => {
        const settings = {};
        document.querySelectorAll('.bpn-row .ui-toggle[data-setting]').forEach((toggle) => {
          settings[toggle.dataset.setting] = toggle.dataset.on === 'true';
        });
        settings.channels = Array.from(
          document.querySelectorAll('.bpn-chips .ui-chip[data-channel][data-on="true"]')
        ).map((chip) => chip.dataset.channel);

        try {
          const res = await fetch('/business/profile/notifications', {
            method: 'PUT',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ notification_settings: settings }),
          });
          const data = await res.json().catch(() => ({}));
          if (res.ok && data.success) {
            setSaved(true);
            if (data.message) archiPopup.success(data.message);
          } else {
            archiPopup.error(data.message || document.body.dataset.errGeneric);
          }
        } catch {
          archiPopup.error(document.body.dataset.errNetwork);
        }
      });
    }
    if (cancelBtn) cancelBtn.addEventListener('click', () => window.location.reload());
  }
}
