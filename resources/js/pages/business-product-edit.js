// Business product create/edit form: image slot management + multipart submit.

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

export default function () {
  const form = document.getElementById('productForm');
  if (!form) return;

  const slots = document.getElementById('imageSlots');
  const addSlot = document.getElementById('addImageSlot');
  const input = document.getElementById('imageInput');
  const msg = document.getElementById('productFormMsg');
  const MAX_IMAGES = 5;

  // Newly picked File objects (existing images stay as data-existing-id divs).
  const newFiles = [];

  function slotCount() {
    return slots.querySelectorAll('.image-slot').length;
  }

  function renderNewImage(file) {
    const div = document.createElement('div');
    div.className = 'image-slot group relative h-[150px] w-[200px] overflow-hidden rounded border border-black/15';
    div.dataset.newIndex = String(newFiles.length - 1);
    const img = document.createElement('img');
    img.className = 'size-full object-cover';
    img.src = URL.createObjectURL(file);
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'absolute right-2 top-2 hidden size-7 items-center justify-center rounded-full bg-black/60 text-white group-hover:flex';
    btn.textContent = '✕';
    btn.addEventListener('click', () => {
      newFiles[Number(div.dataset.newIndex)] = null;
      div.remove();
      addSlot.style.display = slotCount() < MAX_IMAGES ? '' : 'none';
    });
    div.append(img, btn);
    slots.insertBefore(div, addSlot);
  }

  input?.addEventListener('change', () => {
    for (const file of input.files) {
      if (slotCount() >= MAX_IMAGES) break;
      newFiles.push(file);
      renderNewImage(file);
    }
    input.value = '';
    addSlot.style.display = slotCount() < MAX_IMAGES ? '' : 'none';
  });

  // Existing image removal
  slots.querySelectorAll('[data-remove-image]').forEach((btn) => {
    btn.addEventListener('click', () => {
      btn.closest('.image-slot').remove();
      addSlot.style.display = slotCount() < MAX_IMAGES ? '' : 'none';
    });
  });

  let publish = '0';
  form.querySelectorAll('button[type="submit"]').forEach((btn) => {
    btn.addEventListener('click', () => { publish = btn.dataset.publish || '0'; });
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    // Publishing needs at least one image (kept + newly picked) — inline error
    // instead of a bare 422 from the server. Drafts may be saved without one.
    if (publish === '1' && slotCount() === 0) {
      const err = form.dataset.lImageRequired;
      if (msg) msg.textContent = err;
      window.ARCHI?.toast?.show({ type: 'error', title: err });
      addSlot?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return;
    }

    const fd = new FormData(form);
    fd.set('publish', publish);
    if (form.dataset.method === 'PUT') fd.set('_method', 'PUT');

    // Surviving pre-existing images
    slots.querySelectorAll('.image-slot[data-existing-id]').forEach((div) => {
      fd.append('kept_image_ids[]', div.dataset.existingId);
    });

    for (const file of newFiles) {
      if (file) fd.append('images[]', file);
    }

    const buttons = form.querySelectorAll('button[type="submit"]');
    buttons.forEach((b) => (b.disabled = true));

    try {
      const res = await fetch(form.dataset.action, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
        body: fd,
      });
      const data = await res.json().catch(() => ({}));

      if (res.ok && data.success) {
        window.ARCHI?.toast?.show({ type: 'success', title: data.message });
        if (data.redirect) setTimeout(() => (location.href = data.redirect), 600);
      } else {
        const firstError = data.errors ? Object.values(data.errors)[0][0] : (data.message || document.body.dataset.errGeneric);
        if (msg) msg.textContent = firstError;
        window.ARCHI?.toast?.show({ type: 'error', title: firstError });
        buttons.forEach((b) => (b.disabled = false));
      }
    } catch {
      buttons.forEach((b) => (b.disabled = false));
    }
  });
}
