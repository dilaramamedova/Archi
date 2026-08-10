// Page module for "specialist-cabinet-services" — the service & price list of the
// specialist cabinet. Everything the Figma frame shows as interactive is wired: the
// visibility switch, the price field, the unit select, the delete and add-service buttons and
// the ⠿ reorder handle. Save confirms in the dark bar plus a popup,
// Cancel discards the local state by reloading — same behaviour as the business cabinet.
import { success, error } from '../shared/popup.js';

// The count appears twice: in the card title "… (4)" and in the sidebar counter. Both
// are rendered server-side, so the template of the title is passed down from Blade.
function syncCount(card) {
  const n = card.querySelectorAll('.scs-list .cab-row').length;

  const title = card.querySelector('.cab-card-title');
  const tpl = card.dataset.titleTpl;
  if (title && tpl) title.textContent = tpl.replace(':n', String(n));

  const counter = document.querySelector('.cab-snav-item[data-on="true"] .cnt');
  if (counter) counter.textContent = String(n);
}

// Reordering by pointer (⠿ handle) and by keyboard (Alt + Arrow on the focused handle).
function initReorder(list, onChange) {
  let dragged = null;

  const clearOver = () =>
    list.querySelectorAll('.cab-row[data-over="true"]').forEach((r) => delete r.dataset.over);

  list.addEventListener('mousedown', (e) => {
    const grip = e.target.closest('[data-grip]');
    if (!grip) return;
    // Rows are draggable only while the handle is held — dragging from the price field
    // or the switch would fight with their own behaviour.
    grip.closest('.cab-row').draggable = true;
  });

  list.addEventListener('dragstart', (e) => {
    const row = e.target.closest('.cab-row');
    if (!row || !row.draggable) return;
    dragged = row;
    row.dataset.dragging = 'true';
    e.dataTransfer.effectAllowed = 'move';
    // Firefox does not start a drag without a payload.
    e.dataTransfer.setData('text/plain', '');
  });

  list.addEventListener('dragover', (e) => {
    if (!dragged) return;
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    const row = e.target.closest('.cab-row');
    if (!row || row === dragged) return;
    clearOver();
    row.dataset.over = 'true';
  });

  list.addEventListener('drop', (e) => {
    if (!dragged) return;
    e.preventDefault();
    const row = e.target.closest('.cab-row');
    clearOver();
    if (row && row !== dragged) {
      const box = row.getBoundingClientRect();
      const after = e.clientY > box.top + box.height / 2;
      row.parentNode.insertBefore(dragged, after ? row.nextSibling : row);
      onChange();
    }
  });

  list.addEventListener('dragend', () => {
    if (!dragged) return;
    delete dragged.dataset.dragging;
    dragged.draggable = false;
    dragged = null;
    clearOver();
  });

  list.addEventListener('keydown', (e) => {
    const grip = e.target.closest('[data-grip]');
    if (!grip || !e.altKey) return;
    const up = e.key === 'ArrowUp';
    if (!up && e.key !== 'ArrowDown') return;
    e.preventDefault();
    const row = grip.closest('.cab-row');
    const sibling = up ? row.previousElementSibling : row.nextElementSibling;
    if (!sibling) return;
    row.parentNode.insertBefore(up ? row : sibling, up ? sibling : row);
    grip.focus();
    onChange();
  });
}

export default function init() {
  const card = document.querySelector('.cab-card');
  const list = card ? card.querySelector('.scs-list') : null;
  const bar = document.querySelector('.cab-save-bar');
  const msg = bar ? bar.querySelector('.msg') : null;
  const unsavedText = msg ? msg.textContent : '';
  const savedText = bar ? (bar.dataset.savedMessage || '').trim() : '';

  // `saved` only swaps the message text and the dot color — the bar keeps its size.
  const setSaved = (on) => {
    if (!bar) return;
    bar.dataset.saved = on ? 'true' : 'false';
    // setSaved is only ever called in response to a real edit, so a false here means dirty.
    bar.dataset.dirty = on ? 'false' : 'true';
    if (msg) msg.textContent = on && savedText ? savedText : unsavedText;
  };
  const touched = () => setSaved(false);

  if (bar) {
    const cancelBtn = bar.querySelector('[data-cancel]');
    if (cancelBtn) cancelBtn.addEventListener('click', () => window.location.reload());
  }

  if (!card || !list) return;

  // Switch and delete: one delegated handler, so cloned rows work without rebinding.
  list.addEventListener('click', (e) => {
    const toggle = e.target.closest('.ui-toggle');
    if (toggle) {
      const on = toggle.dataset.on !== 'true';
      toggle.dataset.on = on ? 'true' : 'false';
      toggle.setAttribute('aria-pressed', on ? 'true' : 'false');
      touched();
      return;
    }
    const del = e.target.closest('[data-del]');
    if (del) {
      del.closest('.cab-row').remove();
      syncCount(card);
      touched();
    }
  });

  list.addEventListener('input', (e) => {
    if (e.target.closest('.scs-price') || e.target.closest('[contenteditable="true"]')) touched();
  });
  list.addEventListener('change', (e) => {
    if (e.target.closest('.scs-unit')) touched();
  });

  // A dedicated template also supports specialists who do not have any service yet.
  const addBtn = card.querySelector('[data-add]');
  const template = document.getElementById('specialistServiceRowTemplate');
  if (addBtn && template) {
    addBtn.addEventListener('click', () => {
      const row = template.content.firstElementChild.cloneNode(true);
      row.querySelector('.scs-info .n').textContent = card.dataset.newName || '';
      row.querySelector('.scs-info .d').textContent = card.dataset.newDesc || '';
      const price = row.querySelector('.scs-price');
      price.value = '';
      row.querySelector('.scs-unit').selectedIndex = 0;
      const toggle = row.querySelector('.ui-toggle');
      toggle.dataset.on = 'false';
      toggle.setAttribute('aria-pressed', 'false');
      row.draggable = false;
      delete row.dataset.dragging;
      delete row.dataset.over;
      list.appendChild(row);
      syncCount(card);
      touched();
      price.focus();
    });
  }

  initReorder(list, touched);

  const saveBtn = bar ? bar.querySelector('[data-save]') : null;
  if (saveBtn) {
    saveBtn.addEventListener('click', async () => {
      saveBtn.disabled = true;
      const services = Array.from(list.querySelectorAll('.cab-row')).map((row) => ({
        id: row.dataset.id ? Number(row.dataset.id) : null,
        name: row.querySelector('.scs-info .n')?.textContent.trim() || '',
        description: row.querySelector('.scs-info .d')?.textContent.trim() || null,
        price: row.querySelector('.scs-price')?.value || null,
        unit: row.querySelector('.scs-unit')?.value || 'sqm',
        is_active: row.querySelector('.ui-toggle')?.dataset.on === 'true',
      }));

      try {
        const res = await fetch('/specialist/cabinet/services', {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
          },
          body: JSON.stringify({ services }),
        });
        const data = await res.json();
        if (res.ok) {
          setSaved(true);
          // The reload waits for the popup so the confirmation stays readable.
          success(data.message, { autoClose: 1800 }).then(() => window.location.reload());
        } else {
          error(data.message || Object.values(data.errors || {}).flat().join('. '));
        }
      } finally {
        saveBtn.disabled = false;
      }
    });
  }
}
