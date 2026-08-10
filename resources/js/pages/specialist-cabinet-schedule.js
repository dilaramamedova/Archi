// Page module for "specialist-cabinet-schedule" — a day switch opens/closes its row
// (the CSS swaps the time fields for the "day off" note), the free-slot stepper counts
// up and down, and the save bar confirms inline while Cancel discards by reloading.
// Shared behaviour (navbar, cursor) lives in resources/js/shared/.
const SLOT_MIN = 0;
const SLOT_MAX = 20;

export default function init() {
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

  const flip = (toggle) => {
    const on = toggle.dataset.on !== 'true';
    toggle.dataset.on = on ? 'true' : 'false';
    toggle.setAttribute('aria-pressed', on ? 'true' : 'false');
    return on;
  };

  // day rows — the switch drives the row state and the two time fields
  document.querySelectorAll('.sch-day').forEach((row) => {
    const toggle = row.querySelector('.ui-toggle');
    if (!toggle) return;
    toggle.addEventListener('click', () => {
      const on = flip(toggle);
      row.dataset.on = on ? 'true' : 'false';
      row.querySelectorAll('.sch-time').forEach((input) => {
        input.disabled = !on;
      });
      setSaved(false);
    });
  });

  document.querySelectorAll('.sch-time').forEach((input) =>
    input.addEventListener('input', () => setSaved(false))
  );

  // free-slot stepper
  const value = document.querySelector('[data-slots]');
  document.querySelectorAll('.sch-step').forEach((btn) =>
    btn.addEventListener('click', () => {
      if (!value) return;
      const step = Number(btn.dataset.step) || 0;
      const next = Math.min(SLOT_MAX, Math.max(SLOT_MIN, (parseInt(value.textContent, 10) || 0) + step));
      value.textContent = String(next);
      setSaved(false);
    })
  );

  // vacation mode
  const vacation = document.querySelector('.sch-vacation .ui-toggle');
  if (vacation) {
    vacation.addEventListener('click', () => {
      flip(vacation);
      setSaved(false);
    });
  }

  if (bar) {
    const saveBtn = bar.querySelector('[data-save]');
    const cancelBtn = bar.querySelector('[data-cancel]');
    if (saveBtn) saveBtn.addEventListener('click', async () => {
      saveBtn.disabled = true;
      const days = Array.from(document.querySelectorAll('.sch-day')).map((row) => {
        const times = row.querySelectorAll('.sch-time');
        const isOpen = row.dataset.on === 'true';
        return {
          day_of_week: Number(row.dataset.day),
          is_day_off: !isOpen,
          start_time: isOpen ? times[0]?.value || null : null,
          end_time: isOpen ? times[1]?.value || null : null,
        };
      });

      try {
        const res = await fetch('/specialist/cabinet/schedule', {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
          },
          body: JSON.stringify({
            days,
            available_slots: Number(value?.textContent || 0),
            is_on_vacation: vacation?.dataset.on === 'true',
          }),
        });
        const data = await res.json();
        const err = document.getElementById('scheduleErr');
        const ok = document.getElementById('scheduleOk');
        if (res.ok) {
          if (ok) { ok.textContent = data.message; ok.dataset.on = 'true'; }
          if (err) err.dataset.on = 'false';
          setSaved(true);
        } else if (err) {
          err.textContent = data.message || Object.values(data.errors || {}).flat().join('. ');
          err.dataset.on = 'true';
        }
      } finally {
        saveBtn.disabled = false;
      }
    });
    if (cancelBtn) cancelBtn.addEventListener('click', () => window.location.reload());
  }
}
