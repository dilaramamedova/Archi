// Business product create/edit form: image slot management, searchable comboboxes
// (Bölmə → Qrup → Sinif classifier cascade, creatable brand), the dynamic class
// attribute form (EAV fields fetched per class), key-value attribute rows and the
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

  // ─── Dynamic class form ("Məhsul xüsusiyyətləri") ───
  // Field naming contract: attrs[<attribute_id>] · multiselect attrs[<id>][] ·
  // range attrs[<id>][min] / attrs[<id>][max]. Definitions are fetched from the
  // class endpoint once per class id and cached for the session.
  const classForm = (() => {
    const card = document.getElementById('classAttrsCard');
    const basicWrap = document.getElementById('attrBasic');
    const proWrap = document.getElementById('attrPro');
    const proToggle = document.getElementById('attrProToggle');
    const appBlock = document.getElementById('appBlock');
    const appChips = document.getElementById('appChips');
    const appsPresent = document.getElementById('appsPresent');
    const classDesc = document.getElementById('classAttrsDesc');
    if (!card) return { load: () => {}, clear: () => {} };

    const L = card.dataset;
    const initial = JSON.parse(document.getElementById('attrInitial')?.textContent || '{}');
    const cache = new Map();
    let requestSeq = 0;

    const CTRL = 'h-[43px] w-full rounded border border-black/15 bg-white px-3.5 text-sm text-ink outline-none transition focus:border-black/40';
    const CHIP = 'inline-flex cursor-pointer items-center gap-2 rounded border border-black/15 bg-white px-3.5 py-2 text-sm text-ink transition select-none hover:border-black/40 has-[:checked]:border-ink has-[:checked]:bg-ink has-[:checked]:text-white';

    const el = (tag, cls, text) => {
      const n = document.createElement(tag);
      if (cls) n.className = cls;
      if (text != null) n.textContent = text;
      return n;
    };

    // (?) affordance next to the label — hover or click shows the popover.
    function tooltip(text) {
      const wrap = el('span', 'relative inline-flex');
      const btn = el('button', 'flex size-[18px] shrink-0 items-center justify-center rounded-full border border-black/25 text-[11px] font-semibold leading-none text-black/45 transition hover:border-black/60 hover:text-black/70', '?');
      btn.type = 'button';
      btn.setAttribute('aria-label', text);
      const pop = el('span', 'absolute bottom-[calc(100%+8px)] left-1/2 z-40 hidden w-64 max-w-[70vw] -translate-x-1/2 rounded bg-black/85 px-3 py-2 text-xs font-normal whitespace-normal normal-case leading-[1.5] text-white shadow-lg', text);
      btn.addEventListener('click', (e) => { e.preventDefault(); pop.classList.toggle('hidden'); });
      btn.addEventListener('mouseenter', () => pop.classList.remove('hidden'));
      wrap.addEventListener('mouseleave', () => pop.classList.add('hidden'));
      btn.addEventListener('blur', () => pop.classList.add('hidden'));
      wrap.append(btn, pop);
      return wrap;
    }

    function labelRow(def) {
      const row = el('div', 'flex flex-wrap items-center gap-1.5');
      const isBasic = def.complexity === 'basic';
      row.appendChild(el('span', 'ui-label-b2b', def.name + (def.required && isBasic ? ' *' : '')));
      // Professional required fields never block quick publish — badge only.
      if (def.required && !isBasic) {
        row.appendChild(el('span', 'rounded bg-gray-soft2 px-1.5 py-0.5 text-[10px] font-semibold text-black/45', L.lReqBadge || 'Nəşr üçün tövsiyə olunur'));
      }
      if (def.tooltip) row.appendChild(tooltip(def.tooltip));
      return row;
    }

    const unitWrap = (input, unit) => {
      if (!unit) return input;
      const w = el('div', 'relative w-full min-w-0');
      input.classList.add('pr-12');
      w.append(input, el('span', 'pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-sm text-black/40', unit));
      return w;
    };

    function numberInput(name, value, placeholder) {
      const i = el('input', CTRL);
      i.type = 'number';
      i.step = 'any';
      i.name = name;
      if (placeholder) i.placeholder = placeholder;
      if (value != null) i.value = String(value);
      return i;
    }

    function chip(name, value, text, checked) {
      const label = el('label', CHIP);
      const box = el('input', 'sr-only');
      box.type = 'checkbox';
      box.name = name;
      box.value = String(value);
      box.checked = !!checked;
      label.append(box, el('span', '', text));
      return label;
    }

    // The concrete control(s) for one attribute definition. `val` mirrors the
    // shape built from product_attribute_values on the edit page.
    function control(def, val) {
      const name = `attrs[${def.id}]`;
      const requiredBasic = def.required && def.complexity === 'basic';

      if (def.type === 'dropdown' || def.type === 'boolean') {
        const sel = el('select', CTRL + ' appearance-none pr-8');
        sel.name = name;
        if (requiredBasic) sel.required = true;
        sel.appendChild(new Option(L.lSelect || 'Seçin', ''));
        if (def.type === 'boolean') {
          // Three states: unanswered (no row) · Bəli · Xeyr — a checkbox cannot
          // express "unanswered", so booleans render as a compact select.
          sel.appendChild(new Option(L.lYes || 'Bəli', '1'));
          sel.appendChild(new Option(L.lNo || 'Xeyr', '0'));
          if (val && 'bool' in val) sel.value = val.bool ? '1' : '0';
        } else {
          (def.options || []).forEach((o) => sel.appendChild(new Option(o.value + (def.unit ? ` ${def.unit}` : ''), String(o.id))));
          if (val?.options?.length) sel.value = String(val.options[0]);
        }
        const w = el('div', 'relative w-full min-w-0');
        w.append(sel, el('span', 'pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-xs text-black/40', '▾'));
        return w;
      }

      if (def.type === 'multiselect') {
        const wrap = el('div', 'flex w-full flex-wrap gap-2');
        // Empty marker: lets the server distinguish "all chips unchecked" (clear
        // stored values) from "field never rendered" (leave them alone).
        const marker = el('input');
        marker.type = 'hidden';
        marker.name = `${name}[]`;
        marker.value = '';
        wrap.appendChild(marker);
        const picked = (val?.options || []).map(String);
        (def.options || []).forEach((o) => wrap.appendChild(chip(`${name}[]`, o.id, o.value, picked.includes(String(o.id)))));
        if (requiredBasic) {
          wrap.dataset.requiredGroup = def.name;
        }
        return wrap;
      }

      if (def.type === 'range') {
        const wrap = el('div', 'flex w-full items-center gap-2');
        const min = numberInput(`${name}[min]`, val?.min, L.lMin || 'Min');
        const max = numberInput(`${name}[max]`, val?.max, L.lMax || 'Maks');
        wrap.append(unitWrap(min, def.unit), el('span', 'shrink-0 text-sm text-black/40', '–'), unitWrap(max, def.unit));
        if (requiredBasic) wrap.dataset.requiredRange = def.name;
        return wrap;
      }

      if (def.type === 'textarea') {
        const ta = el('textarea', 'min-h-[90px] w-full resize-y rounded border border-black/15 bg-white px-3.5 py-2.5 text-sm text-ink outline-none transition focus:border-black/40');
        ta.name = name;
        ta.maxLength = 5000;
        if (requiredBasic) ta.required = true;
        if (val?.text != null) ta.value = val.text;
        return ta;
      }

      if (def.type === 'numeric' || def.type === 'decimal') {
        const i = numberInput(name, val?.numeric);
        if (requiredBasic) i.required = true;
        return unitWrap(i, def.unit);
      }

      // text
      const i = el('input', CTRL);
      i.type = 'text';
      i.name = name;
      i.maxLength = 255;
      if (requiredBasic) i.required = true;
      if (val?.text != null) i.value = val.text;
      return unitWrap(i, def.unit);
    }

    function fieldNode(def, val) {
      const full = def.type === 'textarea' || def.type === 'multiselect';
      const node = el('div', 'flex min-w-0 flex-col items-start gap-2' + (full ? ' sm:col-span-2' : ''));
      node.append(labelRow(def), control(def, val));
      return node;
    }

    const grid = () => el('div', 'grid w-full grid-cols-2 items-start gap-4 max-[640px]:grid-cols-1');

    function renderApplications(def, selectedApps) {
      appChips.innerHTML = '';
      const selectedIds = selectedApps.map((a) => String(a.id));
      // Union: the class's suggested areas plus already-saved ones that the
      // classifier no longer suggests (they must stay uncheckable-off-able).
      const list = (def.applications || []).slice();
      selectedApps.forEach((a) => {
        if (!list.some((x) => String(x.id) === String(a.id))) list.push(a);
      });
      list.forEach((a) => appChips.appendChild(chip('applications[]', a.id, a.name, selectedIds.includes(String(a.id)))));
      appBlock.style.display = list.length ? '' : 'none';
      appsPresent.disabled = !list.length;
    }

    // Quick-add split (classifier sheet 8, rec. R2): the FIRST screen carries
    // only the fields that block publishing — complexity=basic AND required.
    // Everything else (optional basic fields *and* every professional field)
    // moves behind the "Daha ətraflı doldur" expander, so a seller can list a
    // product by filling the core fields plus a handful of class fields.
    const isUpfront = (f) => f.complexity === 'basic' && !!f.required;

    // Layer 2 of the card: a generic field (Ölçü / Material / Rəng / Ölkə) steps
    // aside when the chosen class already asks the same thing, so the seller never
    // sees one question twice. Hidden ones are disabled as well — otherwise their
    // old free-text value would be saved next to the structured answer and the
    // product page would show the same property twice.
    //
    // Matching runs on `field.match` (the attribute's Azerbaijani name, sent by the
    // endpoint) so translating attribute names later cannot silently break it.
    // `data-generic-match` holds |-separated terms, `data-generic-except` the ones
    // that only look related — "Rəng temperaturu" is Kelvin, not the product colour,
    // and "Göz ölçüsü" is a mesh aperture, not the product's size.
    const genericFields = Array.from(document.querySelectorAll('[data-generic-field]'));
    const terms = (value) => (value || '').toLowerCase().split('|').map((t) => t.trim()).filter(Boolean);

    function syncGenericFields(fields) {
      const names = (fields || []).map((f) => (f.match || f.name || '').toLowerCase());

      let visible = 0;

      genericFields.forEach((wrap) => {
        const wanted = terms(wrap.dataset.genericMatch);
        const except = terms(wrap.dataset.genericExcept);
        const covered = wanted.length > 0 && names.some(
          (n) => wanted.some((t) => n.includes(t)) && !except.some((t) => n.includes(t)),
        );
        // style.display, not the `hidden` attribute: these wrappers sit inside
        // flex/grid containers whose display utility would win over [hidden].
        wrap.style.display = covered ? 'none' : '';
        wrap.querySelectorAll('input, select, textarea').forEach((input) => {
          input.disabled = covered;
        });
        if (!covered) visible++;
      });

      const block = document.getElementById('genericAttrs');
      if (block) block.style.display = visible ? '' : 'none';
    }

    function render(def, values, selectedApps) {
      basicWrap.innerHTML = '';
      proWrap.innerHTML = '';
      const upfront = (def.fields || []).filter(isUpfront);
      const extras = (def.fields || []).filter((f) => !isUpfront(f));

      const bGrid = grid();
      upfront.forEach((f) => bGrid.appendChild(fieldNode(f, values[f.id])));
      basicWrap.appendChild(bGrid);

      const pGrid = grid();
      extras.forEach((f) => pGrid.appendChild(fieldNode(f, values[f.id])));
      proWrap.appendChild(pGrid);

      // The extra fields hide behind the expander; open it right away when an
      // edited product already carries a value in one of them. Collapsing only
      // sets display:none — hidden controls are still part of the FormData.
      const hasExtraValue = extras.some((f) => values[f.id] != null);
      proWrap.classList.toggle('hidden', !(extras.length && hasExtraValue));
      proWrap.classList.toggle('flex', extras.length > 0 && hasExtraValue);
      proToggle.style.display = extras.length ? '' : 'none';
      proToggle.dataset.count = String(extras.length);
      syncToggleLabel();

      renderApplications(def, selectedApps);
      syncGenericFields(def.fields);

      // The card also hosts the generic and free-form layers, so it stays visible
      // even for a class that defines no fields of its own.
      if (classDesc) classDesc.style.display = def.fields?.length ? '' : 'none';
    }

    function syncToggleLabel() {
      proToggle.textContent = proWrap.classList.contains('hidden')
        ? (L.lProShow || 'Daha ətraflı doldur (:count əlavə sahə)').replace(':count', proToggle.dataset.count || '0')
        : (L.lProHide || 'Əlavə sahələri gizlət');
    }

    proToggle?.addEventListener('click', () => {
      const hidden = proWrap.classList.toggle('hidden');
      proWrap.classList.toggle('flex', !hidden);
      syncToggleLabel();
    });

    // No class picked (yet): only the class-specific layer goes away — the generic
    // fields and the free-form rows stay usable.
    function clear() {
      requestSeq++;
      basicWrap.innerHTML = '';
      proWrap.innerHTML = '';
      appChips.innerHTML = '';
      proToggle.style.display = 'none';
      appBlock.style.display = 'none';
      appsPresent.disabled = true;
      if (classDesc) classDesc.style.display = 'none';
      syncGenericFields([]);
    }

    async function load(id, useInitial = false) {
      if (!id) { clear(); return; }
      const seq = ++requestSeq;
      let def = cache.get(String(id));
      if (!def) {
        try {
          const res = await fetch(`${L.endpoint}/${id}/form`, { headers: { Accept: 'application/json' } });
          if (!res.ok) throw new Error('form definition failed');
          def = await res.json();
          cache.set(String(id), def);
        } catch {
          if (seq === requestSeq) archiPopup.error(L.lLoadError || 'Sahələri yükləmək mümkün olmadı');
          return;
        }
      }
      if (seq !== requestSeq) return; // the seller already picked another class
      render(def, useInitial ? (initial.values || {}) : {}, useInitial ? (initial.applications || []) : []);
    }

    return { load, clear };
  })();

  // ─── Searchable comboboxes (brand · Bölmə → Qrup → Sinif cascade) ───
  const comboData = JSON.parse(document.getElementById('comboData')?.textContent || '{}');
  const subRow = document.getElementById('subCatRow');
  const allGroups = comboData.groups || [];
  const allClasses = comboData.classes || [];

  const brandRoot = form.querySelector('[data-combo="brand"]');
  if (brandRoot) combobox(brandRoot, { options: comboData.brands || [], allowCreate: true });

  const groupsFor = (sectionId) => allGroups.filter((g) => String(g.parent_id) === String(sectionId));
  const classesFor = (groupId) => allClasses.filter((c) => String(c.group_id) === String(groupId));

  let groupCombo = null;
  let classCombo = null;

  function refreshClasses(groupId, reset) {
    if (!classCombo || !subRow) return;
    const list = classesFor(groupId);
    classCombo.setOptions(list);
    if (reset) {
      classCombo.clear();
      classForm.clear();
    }
    // Hidden when the group has no classes — the group alone then saves.
    subRow.style.display = list.length ? '' : 'none';
  }

  function refreshGroups(sectionId, reset) {
    if (!groupCombo) return;
    groupCombo.setOptions(groupsFor(sectionId));
    if (reset) {
      groupCombo.clear();
      refreshClasses('', true);
    }
  }

  const classRoot = form.querySelector('[data-combo="subcategory"]');
  if (classRoot) {
    classCombo = combobox(classRoot, {
      options: [],
      onPick: (o) => (o?.id ? classForm.load(o.id) : classForm.clear()),
    });
  }

  const groupRoot = form.querySelector('[data-combo="group"]');
  if (groupRoot) {
    groupCombo = combobox(groupRoot, {
      options: [],
      onPick: (o) => refreshClasses(o?.id ?? '', true),
    });
  }

  const sectionRoot = form.querySelector('[data-combo="section"]');
  const sectionCombo = sectionRoot
    ? combobox(sectionRoot, {
        options: comboData.sections || [],
        onPick: (o) => refreshGroups(o?.id ?? '', true),
      })
    : null;

  // Seed the dependent option lists without resetting — edit mode arrives with
  // server-rendered values, and the stored class's form loads with them.
  if (groupCombo) groupCombo.setOptions(groupsFor(sectionCombo?.id ?? ''));
  if (classCombo) {
    classCombo.setOptions(classesFor(groupCombo?.id ?? ''));
    if (classCombo.id) classForm.load(classCombo.id, true);
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

    // Required BASIC class fields that native validation cannot cover:
    // checkbox-chip multiselects and min–max ranges.
    const attrCard = document.getElementById('classAttrsCard');
    if (attrCard) {
      const requiredTpl = attrCard.dataset.lRequired || ':name sahəsi doldurulmalıdır';
      for (const group of form.querySelectorAll('[data-required-group]')) {
        if (!group.querySelector('input[type="checkbox"]:checked')) {
          archiPopup.error(requiredTpl.replace(':name', group.dataset.requiredGroup));
          group.scrollIntoView({ behavior: 'smooth', block: 'center' });
          return;
        }
      }
      for (const range of form.querySelectorAll('[data-required-range]')) {
        if (![...range.querySelectorAll('input[type="number"]')].some((i) => i.value.trim() !== '')) {
          archiPopup.error(requiredTpl.replace(':name', range.dataset.requiredRange));
          range.scrollIntoView({ behavior: 'smooth', block: 'center' });
          return;
        }
      }
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
