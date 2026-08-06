// Page module for "business-profile-contact" — language chips toggle locally
// (data-on drives the CSS, aria-checked the assistive tech), and the save bar
// PUTs the contact fields + selected languages to /business/profile/contact.
export default function init() {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

  const bar = document.querySelector('.cab-save-bar');
  const msg = bar ? bar.querySelector('.msg') : null;
  const unsavedText = msg ? msg.textContent : '';
  const savedText = bar ? (bar.dataset.savedMessage || '').trim() : '';

  const setSaved = (on) => {
    if (!bar) return;
    bar.dataset.saved = on ? 'true' : 'false';
    if (msg) msg.textContent = on && savedText ? savedText : unsavedText;
  };

  document.querySelectorAll('.ui-chip').forEach((chip) =>
    chip.addEventListener('click', () => {
      const on = chip.dataset.on !== 'true';
      chip.dataset.on = on ? 'true' : 'false';
      chip.setAttribute('aria-checked', on ? 'true' : 'false');
      setSaved(false);
    })
  );

  const saveBtn = bar?.querySelector('[data-save]');
  const cancelBtn = bar?.querySelector('[data-cancel]');

  if (saveBtn) {
    saveBtn.addEventListener('click', async () => {
      const payload = {};
      [
        'contact_person', 'contact_role', 'contact_phone', 'whatsapp', 'telegram',
        'contact_email', 'website', 'work_hours', 'instagram', 'linkedin', 'facebook',
      ].forEach((name) => {
        const field = document.querySelector(`[name="${name}"]`);
        if (field) payload[name] = field.value;
      });

      payload.languages = Array.from(
        document.querySelectorAll('.ui-chip[data-lang][data-on="true"]')
      ).map((chip) => chip.dataset.lang);

      try {
        const res = await fetch('/business/profile/contact', {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrf,
          },
          body: JSON.stringify(payload),
        });
        const data = await res.json().catch(() => ({}));
        if (res.ok && data.success) setSaved(true);
        else window.alert(data.message || 'Error');
      } catch {
        window.alert('Network error');
      }
    });
  }
  if (cancelBtn) {
    cancelBtn.addEventListener('click', () => window.location.reload());
  }
}
