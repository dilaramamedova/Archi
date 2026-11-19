// Page module for "specialist-cabinet" — the "about" character counter, the skill tags
// (remove / inline add) and the save bar. The settings nav is a list of <a href> links,
// so the active row is rendered server-side and needs no handler.
// Every string comes from the markup: new chips are cloned from the <template> the Blade
// page ships, so no label or class name is written here.
import { success, error } from '../shared/popup.js';

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

// Briqadir / Makler blocks (docs/specialist-kinds.md). The blade renders both; the one
// matching the selected specialty's kind is shown, and switching the specialty select
// swaps them live so the visitor sees what they are about to save.
function initKindBlocks(root, onChange) {
  const blocks = root.querySelectorAll('[data-kind-block]');
  if (!blocks.length) return;

  let kinds = {};
  try {
    kinds = JSON.parse(root.querySelector('[data-specialty-kinds]')?.dataset.specialtyKinds || '{}');
  } catch {
    kinds = {};
  }

  const select = root.querySelector('[name="specialist_specialty_id"]');
  const sync = () => {
    const kind = kinds[select?.value] || null;
    blocks.forEach((b) => { b.hidden = b.dataset.kindBlock !== kind; });
  };
  select?.addEventListener('change', sync);

  // Delegated, so crew rows added at runtime mark the page dirty too.
  blocks.forEach((b) => {
    b.addEventListener('input', onChange);
    b.addEventListener('change', onChange);
  });

  const crew = root.querySelector('[data-crew]');
  const addBtn = root.querySelector('[data-crew-add]');
  const tpl = root.querySelector('[data-crew-template]');
  if (!crew || !addBtn || !tpl) return;

  const max = Number(crew.dataset.max) || 30;
  const syncAdd = () => { addBtn.disabled = crew.querySelectorAll('[data-crew-row]').length >= max; };

  addBtn.addEventListener('click', () => {
    if (crew.querySelectorAll('[data-crew-row]').length >= max) return;
    const row = tpl.content.firstElementChild.cloneNode(true);
    crew.appendChild(row);
    row.querySelector('[data-f="name"]')?.focus();
    syncAdd();
    onChange();
  });

  crew.addEventListener('click', (e) => {
    const x = e.target.closest('[data-crew-remove]');
    if (!x) return;
    x.closest('[data-crew-row]')?.remove();
    syncAdd();
    onChange();
  });

  syncAdd();
}

// The visible kind block as the `details` payload; null when the profile has no kind.
function collectDetails(root) {
  const block = root.querySelector('[data-kind-block]:not([hidden])');
  if (!block) return null;

  const details = {};
  block.querySelectorAll('[name^="details["]').forEach((el) => {
    const key = el.name.slice(8, -1);
    details[key] = el.type === 'checkbox' ? el.checked : el.value.trim();
  });
  block.querySelectorAll('[data-multi]').forEach((group) => {
    details[group.dataset.multi] = Array.from(group.querySelectorAll('input:checked')).map((i) => i.value);
  });

  const crew = block.querySelector('[data-crew]');
  const crewRows = [];
  if (crew) {
    details.crew_members = [];
    crew.querySelectorAll('[data-crew-row]').forEach((row) => {
      const val = (f) => row.querySelector(`[data-f="${f}"]`)?.value.trim() || '';
      const member = {
        name: val('name'),
        specialty_id: val('specialty_id') || null,
        experience_years: val('experience_years') || null,
      };
      // Only a row nobody touched is dropped. A row with a trade or years but no
      // name is posted as it is, so the server rejects it with a message instead of
      // the member silently vanishing on save.
      if (member.name === '' && !member.specialty_id && !member.experience_years) return;
      details.crew_members.push(member);
      crewRows.push(row);
    });
  }

  // Empty strings travel as null so "nullable|integer" accepts a cleared field.
  Object.keys(details).forEach((k) => { if (details[k] === '') details[k] = null; });

  // The posted rows, in order, so `details.crew_members.N.*` errors find their row.
  Object.defineProperty(details, '__rows', { value: crewRows, enumerable: false });

  return details;
}

function clearFieldErrors(root) {
  root.querySelectorAll('.ui-control-error').forEach((el) => el.classList.remove('ui-control-error'));
  root.querySelectorAll('.sc-opts-error').forEach((el) => el.classList.remove('sc-opts-error'));
  root.querySelectorAll('.field-error[data-js]').forEach((el) => el.remove());
}

// Inline highlight + message under the control for each 422 key the page can place.
function showFieldErrors(root, errors, details) {
  const rows = details?.__rows || [];
  const placed = new Set();

  Object.entries(errors).forEach(([key, messages]) => {
    let control = null;
    let anchor = null;
    let m;

    if ((m = key.match(/^details\.crew_members\.(\d+)\.(\w+)$/))) {
      const row = rows[Number(m[1])];
      control = row?.querySelector(`[data-f="${m[2]}"]`) || null;
      anchor = row;
    } else if ((m = key.match(/^details\.(deal_types|property_types|crew_trades)(\.\d+)?$/))) {
      const group = root.querySelector(`[data-multi="${m[1]}"]`);
      group?.classList.add('sc-opts-error');
      anchor = group;
    } else if ((m = key.match(/^details\.(\w+)$/))) {
      control = root.querySelector(`[name="details[${m[1]}]"]`);
      anchor = control?.closest('.cab-field') ? control : null;
    } else {
      control = root.querySelector(`[name="${key}"]`);
      anchor = control;
    }

    control?.classList.add('ui-control-error');
    if (!anchor || placed.has(anchor)) return;
    placed.add(anchor);

    const p = document.createElement('p');
    p.className = 'field-error';
    p.dataset.js = '';
    p.textContent = messages[0];
    // A select sits inside .sc-select; put the message under the wrapper, not inside it.
    const target = anchor.closest('.sc-select') || anchor;
    target.insertAdjacentElement('afterend', p);
  });
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
    // setSaved is only ever called in response to a real edit, so a false here means dirty.
    bar.dataset.dirty = on ? 'false' : 'true';
    if (msg) msg.textContent = on && savedText ? savedText : unsavedText;
  };
  const markDirty = () => setSaved(false);

  initCounter(root, markDirty);
  initSkills(root, markDirty);
  initKindBlocks(root, markDirty);

  const avatar = root.querySelector('[data-avatar]');
  const avatarPicker = root.querySelector('[data-avatar-picker]');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

  root.querySelector('[data-avatar-change]')?.addEventListener('click', () => avatarPicker?.click());
  avatarPicker?.addEventListener('change', async () => {
    const file = avatarPicker.files?.[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('avatar', file);

    const res = await fetch('/specialist/cabinet/avatar', {
      method: 'POST',
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
      body: formData,
    });
    const data = await res.json();
    if (res.ok && avatar) {
      avatar.innerHTML = '';
      const image = document.createElement('img');
      image.className = 'size-full rounded-full object-cover';
      image.src = data.url;
      image.alt = file.name;
      avatar.appendChild(image);
    }
    avatarPicker.value = '';
  });

  root.querySelector('[data-avatar-delete]')?.addEventListener('click', async () => {
    const res = await fetch('/specialist/cabinet/avatar', {
      method: 'DELETE',
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
    });
    if (res.ok && avatar) {
      avatar.innerHTML = '';
      const initials = document.createElement('p');
      initials.textContent = avatar.dataset.initials || '';
      avatar.appendChild(initials);
    }
  });

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

      const saveBtn = bar.querySelector('[data-save]');
      if (saveBtn) saveBtn.disabled = true;

      clearFieldErrors(root);
      const details = collectDetails(root);

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
            specialist_specialty_id: root.querySelector('[name="specialist_specialty_id"]')?.value || '',
            experience_years: root.querySelector('[name="experience_years"]')?.value || '',
            city: root.querySelector('[name="city"]')?.value || '',
            phone: root.querySelector('[name="phone"]')?.value || '',
            whatsapp: root.querySelector('[name="whatsapp"]')?.value || '',
            about: root.querySelector('[name="about"]')?.value || '',
            skills,
            details,
          }),
        });

        const data = await res.json();

        if (res.ok) {
          success(data.message);
          setSaved(true);
        } else {
          if (data.errors) showFieldErrors(root, data.errors, details);
          // De-duplicated: three nameless crew rows are one message, not three.
          const msgs = data.errors
            ? [...new Set(Object.values(data.errors).flat().map((m) => m.replace(/\.\s*$/, '')))].join('. ')
            : data.message || document.body.dataset.errGeneric;
          error(msgs);
        }
      } catch {
        error(document.body.dataset.errNetwork);
      } finally {
        if (saveBtn) saveBtn.disabled = false;
      }
    });
    bar.querySelector('[data-cancel]')?.addEventListener('click', () => window.location.reload());
  }
}
