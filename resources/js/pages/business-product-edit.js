// Business product create/edit form: image slot management, searchable comboboxes
// (category → subcategory chain, creatable brand), key-value attribute rows and the
// multipart AJAX submit.

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

// Searchable dropdown over a plain text input + hidden id input.
//  - strict mode (category/subcategory): the text must resolve to a listed option,
//    otherwise blur reverts to the last valid choice.
//  - allowCreate (brand): unknown text is kept and submitted through the
//    [data-combo-new] hidden input, the id input is cleared.
function combobox(root, { options = [], allowCreate = false, onPick = null } = {}) {
  const input = root.querySelector('[data-combo-input]');
  const idInput = root.querySelector('[data-combo-id]');
  const newInput = root.querySelector('[data-combo-new]');
  const list = root.querySelector('[data-combo-list]');
  if (!input || !idInput || !list) return null;

  let opts = options.slice();
  let active = -1;
  let lastValid = { id: idInput.value, name: input.value };

  const norm = (s) => String(s).trim().toLocaleLowerCase('az');
  const exactMatch = () => opts.find((o) => norm(o.name) === norm(input.value));

  function item(text, cls) {
    const li = document.createElement('li');
    li.textContent = text;
    li.className = cls;
    return li;
  }

  function render() {
    const q = norm(input.value);
    const matches = q ? opts.filter((o) => norm(o.name).includes(q)) : opts.slice();
    list.innerHTML = '';

    matches.forEach((o, i) => {
      const li = item(o.name, 'cursor-pointer px-3.5 py-2 text-sm text-ink hover:bg-gray-soft2' + (i === active ? ' bg-gray-soft2' : ''));
      // mousedown fires before the input's blur, so the click always lands.
      li.addEventListener('mousedown', (e) => { e.preventDefault(); choose(o); });
      list.appendChild(li);
    });

    const typed = input.value.trim();
    if (allowCreate && typed && !exactMatch()) {
      const label = (root.dataset.lAdd || '+ :name').replace(':name', typed);
      const li = item(label, 'cursor-pointer px-3.5 py-2 text-sm font-semibold text-ink hover:bg-gray-soft2' + (matches.length ? ' border-t border-black/10' : ''));
      li.addEventListener('mousedown', (e) => { e.preventDefault(); chooseNew(typed); });
      list.appendChild(li);
    } else if (!matches.length) {
      list.appendChild(item(root.dataset.lEmpty || '—', 'px-3.5 py-2 text-sm text-black/40'));
    }
  }

  function open() { render(); list.classList.remove('hidden'); input.setAttribute('aria-expanded', 'true'); }
  function close() { list.classList.add('hidden'); active = -1; input.setAttribute('aria-expanded', 'false'); }

  function choose(o) {
    const changed = String(o.id) !== String(lastValid.id);
    input.value = o.name;
    idInput.value = String(o.id);
    if (newInput) newInput.value = '';
    lastValid = { id: idInput.value, name: input.value };
    close();
    // Only a real change may cascade (e.g. category → reset subcategory); a blur
    // that re-confirms the current choice must not wipe dependent fields.
    if (changed) onPick?.(o);
  }

  function chooseNew(name) {
    input.value = name;
    idInput.value = '';
    if (newInput) newInput.value = name;
    lastValid = { id: '', name };
    close();
    onPick?.(null);
  }

  // Keep the hidden inputs in sync while typing so a submit without an explicit
  // pick still sends the right thing.
  function sync() {
    const match = exactMatch();
    if (match) {
      idInput.value = String(match.id);
      if (newInput) newInput.value = '';
    } else {
      idInput.value = '';
      if (newInput) newInput.value = input.value.trim();
    }
  }

  input.addEventListener('focus', open);
  input.addEventListener('click', open);
  input.addEventListener('input', () => { active = -1; sync(); open(); });
  input.addEventListener('blur', () => {
    const match = exactMatch();
    if (match) {
      choose(match);
      return;
    }
    if (allowCreate) {
      const typed = input.value.trim();
      typed ? chooseNew(typed) : choose({ id: '', name: '' });
    } else if (input.value.trim() === '') {
      choose({ id: '', name: '' });
    } else {
      // Strict combobox: unknown text reverts to the last valid choice.
      input.value = lastValid.name;
      idInput.value = lastValid.id;
      close();
    }
  });
  input.addEventListener('keydown', (e) => {
    const items = [...list.querySelectorAll('li')].filter((li) => li.classList.contains('cursor-pointer'));
    if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
      e.preventDefault();
      if (list.classList.contains('hidden')) { open(); return; }
      active = e.key === 'ArrowDown' ? Math.min(active + 1, items.length - 1) : Math.max(active - 1, 0);
      items.forEach((li, i) => li.classList.toggle('bg-gray-soft2', i === active));
      items[active]?.scrollIntoView({ block: 'nearest' });
    } else if (e.key === 'Enter') {
      if (!list.classList.contains('hidden') && items[active]) {
        e.preventDefault();
        items[active].dispatchEvent(new MouseEvent('mousedown'));
      }
    } else if (e.key === 'Escape') {
      close();
    }
  });

  return {
    setOptions(next) { opts = next.slice(); },
    clear() { input.value = ''; idInput.value = ''; if (newInput) newInput.value = ''; lastValid = { id: '', name: '' }; },
    get id() { return idInput.value; },
  };
}

export default function () {
  const form = document.getElementById('productForm');
  if (!form) return;

  // ─── Searchable comboboxes (brand · category · subcategory) ───
  const comboData = JSON.parse(document.getElementById('comboData')?.textContent || '{}');
  const subRow = document.getElementById('subCatRow');
  const allSubs = comboData.subcategories || [];

  const brandRoot = form.querySelector('[data-combo="brand"]');
  if (brandRoot) combobox(brandRoot, { options: comboData.brands || [], allowCreate: true });

  let subCombo = null;
  const subRoot = form.querySelector('[data-combo="subcategory"]');
  const subsFor = (catId) => allSubs.filter((s) => String(s.category_id) === String(catId));

  function refreshSubcategories(catId, reset) {
    if (!subCombo || !subRow) return;
    const subs = subsFor(catId);
    subCombo.setOptions(subs);
    if (reset) subCombo.clear();
    // Hidden when the category has no subcategories — the parent alone then saves.
    subRow.style.display = subs.length ? '' : 'none';
  }

  const catRoot = form.querySelector('[data-combo="category"]');
  if (catRoot) {
    const catCombo = combobox(catRoot, {
      options: comboData.categories || [],
      onPick: (o) => refreshSubcategories(o?.id ?? '', true),
    });
    if (subRoot) {
      subCombo = combobox(subRoot, { options: subsFor(catCombo?.id ?? '') });
    }
  }

  // ─── Key-value attribute rows ("Xüsusiyyət əlavə et") ───
  const attrRows = document.getElementById('attrRows');
  const attrAdd = document.getElementById('attrAdd');
  const ATTR_INPUT_CLS = 'h-[43px] w-full min-w-0 flex-1 rounded border border-black/15 bg-white px-3.5 text-sm text-ink outline-none transition focus:border-black/40';
  let attrIndex = attrRows ? attrRows.querySelectorAll('[data-attr-row]').length : 0;

  const bindAttrRemove = (row) =>
    row.querySelector('[data-attr-remove]')?.addEventListener('click', () => row.remove());

  attrRows?.querySelectorAll('[data-attr-row]').forEach(bindAttrRemove);

  attrAdd?.addEventListener('click', () => {
    const row = document.createElement('div');
    row.className = 'flex w-full items-center gap-4 max-[640px]:gap-2';
    row.dataset.attrRow = '';

    const key = document.createElement('input');
    key.type = 'text';
    key.name = `attributes[${attrIndex}][key]`;
    key.maxLength = 60;
    key.placeholder = attrRows.dataset.lKeyPh || '';
    key.className = ATTR_INPUT_CLS;

    const val = document.createElement('input');
    val.type = 'text';
    val.name = `attributes[${attrIndex}][value]`;
    val.maxLength = 255;
    val.placeholder = attrRows.dataset.lValuePh || '';
    val.className = ATTR_INPUT_CLS;

    const del = document.createElement('button');
    del.type = 'button';
    del.dataset.attrRemove = '';
    del.setAttribute('aria-label', attrRows.dataset.lRemove || '');
    del.textContent = '✕';
    del.className = 'flex size-[43px] shrink-0 items-center justify-center rounded border border-black/15 text-black/40 transition hover:border-error hover:text-error';

    row.append(key, val, del);
    attrRows.appendChild(row);
    bindAttrRemove(row);
    attrIndex++;
    key.focus();
  });

  const slots = document.getElementById('imageSlots');
  const addSlot = document.getElementById('addImageSlot');
  const input = document.getElementById('imageInput');
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
      archiPopup.error(form.dataset.lImageRequired);
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
        // The Promise resolves when the popup closes (autoClose or user action).
        const shown = archiPopup.success(data.message, data.redirect ? { autoClose: 1800 } : {});
        if (data.redirect) shown.then(() => (location.href = data.redirect));
      } else {
        const firstError = data.errors ? Object.values(data.errors)[0][0] : (data.message || document.body.dataset.errGeneric);
        archiPopup.error(firstError);
        buttons.forEach((b) => (b.disabled = false));
      }
    } catch {
      buttons.forEach((b) => (b.disabled = false));
    }
  });
}
