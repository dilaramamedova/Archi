// Page module for "business-profile-showrooms" — the add button opens an empty
// #showroomModal, a row's edit button opens it prefilled from the row's data-*
// attributes, save POSTs (new) or PUTs (edit) to /business/showrooms and reloads,
// delete confirms then DELETEs and reloads. The save bar keeps its inline confirm.
export default function init() {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

  // ---- save bar (shared contract) -----------------------------------------
  const bar = document.querySelector('.cab-save-bar');
  if (bar) {
    const msg = bar.querySelector('.msg');
    const unsavedText = msg ? msg.textContent : '';
    const savedText = (bar.dataset.savedMessage || '').trim();
    const setSaved = (on) => {
      bar.dataset.saved = on ? 'true' : 'false';
      // setSaved is only ever called in response to a real edit, so a false here means dirty.
      bar.dataset.dirty = on ? 'false' : 'true';
      if (msg) msg.textContent = on && savedText ? savedText : unsavedText;
    };
    const saveBtn = bar.querySelector('[data-save]');
    const cancelBtn = bar.querySelector('[data-cancel]');
    if (saveBtn) saveBtn.addEventListener('click', () => setSaved(true));
    if (cancelBtn) cancelBtn.addEventListener('click', () => window.location.reload());
  }

  // ---- modal ---------------------------------------------------------------
  const modal = document.getElementById('showroomModal');
  if (!modal) return;
  document.body.appendChild(modal);

  const title = modal.querySelector('[data-modal-title]');
  const fields = {
    name: modal.querySelector('#showroomName'),
    address: modal.querySelector('#showroomAddress'),
    city: modal.querySelector('#showroomCity'),
    phone: modal.querySelector('#showroomPhone'),
    work_hours: modal.querySelector('#showroomHours'),
    status: modal.querySelector('#showroomStatus'),
  };

  let editingId = null;

  const openModal = (row) => {
    editingId = row ? row.dataset.showroomId : null;
    if (title) {
      title.textContent = row
        ? (title.dataset.editTitle || title.textContent)
        : (title.dataset.addTitle || title.textContent);
    }
    fields.name.value = row ? (row.dataset.name || '') : '';
    fields.address.value = row ? (row.dataset.address || '') : '';
    fields.city.value = row ? (row.dataset.city || '') : '';
    fields.phone.value = row ? (row.dataset.phone || '') : '';
    fields.work_hours.value = row ? (row.dataset.hours || '') : '';
    fields.status.value = row ? (row.dataset.status || 'active') : 'active';
    modal.hidden = false;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
  };

  const closeModal = () => {
    modal.hidden = true;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    editingId = null;
  };

  modal.addEventListener('click', (e) => {
    if (e.target === modal) closeModal();
  });
  modal.querySelector('[data-modal-cancel]')?.addEventListener('click', closeModal);

  // Add button (card header action) — skip the modal's own save button.
  const addBtn = Array.from(document.querySelectorAll('.cab-btn-add')).find((btn) => !modal.contains(btn));
  if (addBtn) {
    addBtn.addEventListener('click', () => openModal(null));
  }

  // Row edit / delete buttons
  document.querySelectorAll('[data-showroom-id]').forEach((row) => {
    row.querySelector('[data-edit]')?.addEventListener('click', () => openModal(row));
    row.querySelector('[data-delete]')?.addEventListener('click', async () => {
      const id = row.dataset.showroomId;
      const ask = window.ARCHI?.confirm || window.confirm;
      const ok = await Promise.resolve(ask.call(window, row.dataset.name || ''));
      if (!ok) return;
      try {
        const res = await fetch(`/business/showrooms/${id}`, {
          method: 'DELETE',
          headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        });
        const data = await res.json().catch(() => ({}));
        if (res.ok && data.success) window.location.reload();
        else window.alert(data.message || 'Error');
      } catch {
        window.alert('Network error');
      }
    });
  });

  // Modal save
  modal.querySelector('[data-modal-save]')?.addEventListener('click', async () => {
    const payload = {
      name: fields.name.value,
      address: fields.address.value,
      city: fields.city.value,
      phone: fields.phone.value,
      work_hours: fields.work_hours.value,
      status: fields.status.value,
    };
    const url = editingId ? `/business/showrooms/${editingId}` : '/business/showrooms';
    const method = editingId ? 'PUT' : 'POST';

    try {
      const res = await fetch(url, {
        method,
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify(payload),
      });
      const data = await res.json().catch(() => ({}));
      if (res.ok && data.success) window.location.reload();
      else window.alert(data.message || 'Error');
    } catch {
      window.alert('Network error');
    }
  });
}
