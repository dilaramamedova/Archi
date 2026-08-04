// Page module for "specialist-cabinet" — the "about" character counter, the skill tags
// (remove / inline add) and the save bar. The settings nav is a list of <a href> links,
// so the active row is rendered server-side and needs no handler.
// Every string comes from the markup: new chips are cloned from the <template> the Blade
// page ships, so no label or class name is written here.

// The counter is "<used> / <max>"; only the number changes.
function initCounter(root, onChange) {
  const counter = root.querySelector('.sc-counter');
  const field = counter && document.getElementById(counter.dataset.countFor || '');
  if (!counter || !field) return;

  const max = Number(counter.dataset.max) || 0;
  field.addEventListener('input', () => {
    if (max && field.value.length > max) field.value = field.value.slice(0, max);
    counter.textContent = `${field.value.length} / ${max}`;
    onChange();
  });
}

function initSkills(root, onChange) {
  const box = root.querySelector('.sc-skills');
  if (!box) return;

  const input = box.querySelector('[data-skill-input]');
  const addBtn = box.querySelector('[data-skill-add]');
  const tpl = box.querySelector('[data-skill-template]');

  // Removing is delegated so chips added at runtime behave like the rendered ones.
  box.addEventListener('click', (e) => {
    const x = e.target.closest('[data-skill-remove]');
    if (!x) return;
    x.closest('.sc-skill')?.remove();
    onChange();
  });

  if (!input || !addBtn || !tpl) return;

  const closeInput = () => {
    input.value = '';
    input.hidden = true;
    addBtn.hidden = false;
  };

  const commit = () => {
    const label = input.value.trim();
    if (label === '') return closeInput();
    const chip = tpl.content.firstElementChild.cloneNode(true);
    chip.querySelector('.lbl').textContent = label;
    box.insertBefore(chip, input);
    closeInput();
    onChange();
  };

  addBtn.addEventListener('click', () => {
    addBtn.hidden = true;
    input.hidden = false;
    input.focus();
  });

  input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      commit();
    } else if (e.key === 'Escape') {
      closeInput();
      addBtn.focus();
    }
  });
  input.addEventListener('blur', commit);
}

export default function init() {
  const root = document.querySelector('.sc-page');
  if (!root) return;

  const bar = root.querySelector('.cab-save-bar');
  const msg = bar?.querySelector('.msg');
  const unsavedText = msg ? msg.textContent : '';
  const savedText = bar ? (bar.dataset.savedMessage || '').trim() : '';

  // `saved` swaps only the dot color and the message — the bar keeps its size.
  const setSaved = (on) => {
    if (!bar) return;
    bar.dataset.saved = on ? 'true' : 'false';
    if (msg) msg.textContent = on && savedText ? savedText : unsavedText;
  };
  const markDirty = () => setSaved(false);

  initCounter(root, markDirty);
  initSkills(root, markDirty);

  root.querySelectorAll('.sc-input').forEach((el) => {
    el.addEventListener('input', markDirty);
    el.addEventListener('change', markDirty);
  });

  if (bar) {
    bar.querySelector('[data-save]')?.addEventListener('click', async () => {
      const skills = [];
      root.querySelectorAll('.sc-skill .lbl').forEach((el) => {
        const t = el.textContent.trim();
        if (t) skills.push(t);
      });

      const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
      const saveBtn = bar.querySelector('[data-save]');
      if (saveBtn) saveBtn.disabled = true;

      try {
        const res = await fetch('/specialist/cabinet', {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrf,
          },
          body: JSON.stringify({
            first_name: root.querySelector('[name="first_name"]')?.value || '',
            last_name: root.querySelector('[name="last_name"]')?.value || '',
            craft: root.querySelector('[name="craft"]')?.value || '',
            experience_years: root.querySelector('[name="experience_years"]')?.value || '',
            city: root.querySelector('[name="city"]')?.value || '',
            phone: root.querySelector('[name="phone"]')?.value || '',
            whatsapp: root.querySelector('[name="whatsapp"]')?.value || '',
            about: root.querySelector('[name="about"]')?.value || '',
            skills,
          }),
        });

        const data = await res.json();
        const errBox = document.getElementById('profileErr');
        const okBox = document.getElementById('profileOk');

        if (res.ok) {
          if (okBox) { okBox.textContent = data.message; okBox.dataset.on = 'true'; }
          if (errBox) errBox.dataset.on = 'false';
          setSaved(true);
        } else {
          const msgs = data.errors ? Object.values(data.errors).flat().join('. ') : data.message || 'Error';
          if (errBox) { errBox.textContent = msgs; errBox.dataset.on = 'true'; }
          if (okBox) okBox.dataset.on = 'false';
        }
      } catch {
        const errBox = document.getElementById('profileErr');
        if (errBox) { errBox.textContent = 'Network error'; errBox.dataset.on = 'true'; }
      } finally {
        if (saveBtn) saveBtn.disabled = false;
      }
    });
    bar.querySelector('[data-cancel]')?.addEventListener('click', () => window.location.reload());
  }
}
