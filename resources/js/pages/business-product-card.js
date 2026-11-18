// Category product card (docs/product-cards.md) for the seller product form.
//
// The class form endpoint answers with `card` — the class's parameters as data —
// and this module renders it into #cardBlock. Field names follow the server's
// input contract: card[<id>][option] · card[<id>][options][] · card[<id>][text] ·
// card[<id>][value] · card[<id>][length|length_max|width].
//
// Conditions are evaluated live: a field (or one option) whose `visible_when` does
// not hold is hidden AND disabled, so it is neither validated by the browser nor
// posted. The server re-checks everything (CardInput) and is the authority.

const CTRL = 'h-[43px] w-full rounded border border-black/15 bg-white px-3.5 text-sm text-ink outline-none transition focus:border-black/40';
const CHIP = 'inline-flex cursor-pointer items-center gap-2 rounded border border-black/15 bg-white px-3.5 py-2 text-sm text-ink transition select-none hover:border-black/40 has-[:checked]:border-ink has-[:checked]:bg-ink has-[:checked]:text-white has-[:disabled]:hidden';
const ERR = 'text-[13px] leading-[1.45] text-error';

const el = (tag, cls, text) => {
  const n = document.createElement(tag);
  if (cls) n.className = cls;
  if (text != null) n.textContent = text;
  return n;
};

// Show/hide a wrapper and keep its controls out of validation and FormData while hidden.
function setShown(node, shown) {
  if (!node) return;
  node.hidden = !shown;
  node.style.display = shown ? '' : 'none';
  node.querySelectorAll('input, select, textarea').forEach((i) => {
    if (i.dataset.cardKeepDisabled) return;
    i.disabled = !shown;
  });
}

// One {attr, in} condition, or a list of them where any one holding is enough.
const holds = (cond, answers) => {
  if (Array.isArray(cond)) return cond.some((c) => holds(c, answers));
  return !cond || !cond.attr || (answers[cond.attr] || []).some((slug) => (cond.in || []).includes(slug));
};

export function createCardForm({ form }) {
  const block = document.getElementById('cardBlock');
  const L = block?.dataset || {};
  const initial = JSON.parse(document.getElementById('cardInitial')?.textContent || 'null') || {};
  const missingBox = document.getElementById('cardMissing');
  const quoteNote = document.getElementById('cardQuoteNote');
  const priceRow = document.getElementById('priceRow');
  const priceHint = document.getElementById('cardPriceHint');
  const stockField = document.getElementById('stockField');
  const stockHint = document.getElementById('cardStockHint');
  const leadField = document.getElementById('leadTimeField');
  const availability = document.getElementById('pAvailability');
  const collection = document.getElementById('pCollection');
  const collectionList = document.getElementById('pCollectionList');

  let schema = null;

  const state = { active: false };

  // ── Field controls ─────────────────────────────────────────────────────────

  function labelRow(f) {
    const row = el('div', 'flex flex-wrap items-center gap-1.5');
    row.appendChild(el('span', 'ui-label-b2b', f.name + (f.unit ? `, ${f.unit}` : '') + (f.required ? ' *' : '')));
    return row;
  }

  // A field with a list (`presets`) is a choice from it — a plain select, nothing typed.
  const show = (v) => String(v).replace('.', ',');
  const same = (a, b) => a != null && b != null && a !== '' && b !== ''
    && Math.abs(parseFloat(String(a).replace(',', '.')) - parseFloat(String(b).replace(',', '.'))) < 0.0001;

  function listSelect(name, values, current, label = show, placeholder = null, isSame = same) {
    const holder = el('div', 'relative w-full min-w-0');
    const sel = el('select', CTRL + ' appearance-none pr-8');
    sel.name = name;
    sel.appendChild(new Option(placeholder || L.lSelect || 'Seçin', ''));
    values.forEach((v) => sel.appendChild(new Option(label(v), String(v))));
    if (current != null && current !== '') {
      const hit = values.find((v) => isSame(v, current) || String(v) === String(current));
      // A value saved before the list existed stays visible instead of vanishing.
      if (hit === undefined) sel.appendChild(new Option(label(current), String(current)));
      sel.value = String(hit ?? current);
    }
    holder.append(sel, el('span', 'pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-xs text-black/40', '▾'));
    return holder;
  }

  const splitSize = (pair) => String(pair).split(/\s*[×xX*]\s*/).map((s) => s.trim().replace(',', '.'));
  const pairLabel = (pair) => splitSize(pair).map(show).join(' × ');
  function sizeParts(presets) {
    const lengths = new Set();
    const widths = new Set();
    presets.forEach((p) => {
      const [l, w] = splitSize(p);
      if (l) lengths.add(parseFloat(l));
      if (w) widths.add(parseFloat(w));
    });
    const sorted = (s) => [...s].filter((n) => !Number.isNaN(n)).sort((a, b) => a - b);
    return [sorted(lengths), sorted(widths)];
  }

  const numberInput = (name, value, placeholder) => {
    const i = el('input', CTRL);
    i.type = 'text';
    i.inputMode = 'decimal';
    i.name = name;
    i.autocomplete = 'off';
    if (placeholder) i.placeholder = placeholder;
    if (value != null && value !== '') i.value = String(value);
    return i;
  };

  function textBox(f, val) {
    const i = el('input', CTRL + ' mt-2');
    i.type = 'text';
    i.maxLength = 255;
    i.name = `card[${f.id}][text]`;
    i.dataset.cardText = '';
    if (val?.text) i.value = val.text;
    return i;
  }

  function dropdown(f, val) {
    const wrap = el('div', 'flex w-full flex-col');
    const holder = el('div', 'relative w-full min-w-0');
    const sel = el('select', CTRL + ' appearance-none pr-8');
    sel.name = `card[${f.id}][option]`;
    sel.dataset.cardSlug = f.slug;
    sel.appendChild(new Option(L.lSelect || 'Seçin', ''));
    f.options.forEach((o) => {
      const opt = new Option(o.value, String(o.id));
      opt.dataset.slug = o.slug;
      if (o.text_prompt) opt.dataset.prompt = o.text_prompt;
      sel.appendChild(opt);
    });
    if (val?.option) sel.value = String(val.option);
    holder.append(sel, el('span', 'pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-xs text-black/40', '▾'));
    wrap.appendChild(holder);
    if (f.options.some((o) => o.text_prompt)) wrap.appendChild(textBox(f, val));
    return wrap;
  }

  function multiselect(f, val) {
    const wrap = el('div', 'flex w-full flex-col');
    const chips = el('div', 'flex w-full flex-wrap gap-2');
    const picked = (val?.options || []).map(String);
    f.options.forEach((o) => {
      const label = el('label', CHIP);
      const box = el('input', 'sr-only');
      box.type = 'checkbox';
      box.name = `card[${f.id}][options][]`;
      box.value = String(o.id);
      box.dataset.slug = o.slug;
      box.dataset.cardSlug = f.slug;
      if (o.exclusive) box.dataset.exclusive = '1';
      if (o.text_prompt) box.dataset.prompt = o.text_prompt;
      box.checked = picked.includes(String(o.id));
      // «Göstərilməyib» stands alone: picking it clears the rest, and vice versa.
      box.addEventListener('change', () => {
        if (!box.checked) return;
        chips.querySelectorAll('input[type="checkbox"]').forEach((other) => {
          if (other !== box && (box.dataset.exclusive || other.dataset.exclusive)) other.checked = false;
        });
      });
      label.dataset.optionWhen = o.visible_when ? JSON.stringify(o.visible_when) : '';
      label.append(box, el('span', '', o.value));
      chips.appendChild(label);
    });
    wrap.appendChild(chips);
    if (f.options.some((o) => o.text_prompt)) wrap.appendChild(textBox(f, val));
    return wrap;
  }

  function decimal(f, val) {
    if (f.presets?.length) return listSelect(`card[${f.id}][value]`, f.presets, val?.value);
    return numberInput(`card[${f.id}][value]`, val?.value);
  }

  // Size: with a list, one select of "L × W" pairs; a mixed-length format picks
  // min length, max length and width from the listed values instead. Without a
  // list, typed numbers. Only the block for the current format is enabled.
  function size(f, val) {
    const wrap = el('div', 'flex w-full flex-col gap-2');
    const listed = !!f.presets?.length;

    const fixed = el('div', 'flex w-full items-center gap-2 max-[640px]:flex-wrap');
    fixed.dataset.sizeFixed = '';
    const mixedRow = el('div', 'flex w-full items-center gap-2 max-[640px]:flex-wrap');
    mixedRow.dataset.sizeMixed = '';
    const sep = (t) => el('span', 'shrink-0 text-sm text-black/40', t);

    if (listed) {
      const [lengths, widths] = sizeParts(f.presets);
      // Pairs match on both numbers — 1380×193 is not 1380×157.
      const samePair = (a, b) => {
        const [al, aw] = splitSize(a);
        const [bl, bw] = splitSize(b);
        return same(al, bl) && same(aw, bw);
      };
      const pairBox = listSelect(`card[${f.id}][pair]`, f.presets, val?.length_max ? null : val?.pair, pairLabel, null, samePair);
      fixed.appendChild(pairBox);
      // An open list: «Başqa ölçü» reveals typed length × width. A saved size
      // outside the list stays its own option (listSelect), so an edit needs nothing.
      if (f.rules?.open) {
        const sel = pairBox.querySelector('select');
        sel.appendChild(new Option(L.lOtherSize || 'Başqa ölçü', '__other__'));
        const typed = el('div', 'flex w-full items-center gap-2 max-[640px]:flex-wrap');
        typed.append(
          numberInput(`card[${f.id}][length]`, null, L.lLength || 'Uzunluq'),
          sep('×'),
          numberInput(`card[${f.id}][width]`, null, L.lWidth || 'En'),
        );
        fixed.classList.add('flex-wrap');
        fixed.appendChild(typed);
        const sync = () => setShown(typed, sel.value === '__other__');
        sel.addEventListener('change', sync);
        sync();
      }
      mixedRow.append(
        listSelect(`card[${f.id}][length]`, lengths, val?.length_max ? val?.length : null, show, L.lLengthMin || 'Min. uzunluq'),
        sep('–'),
        listSelect(`card[${f.id}][length_max]`, lengths, val?.length_max, show, L.lLengthMax || 'Maks. uzunluq'),
        sep('×'),
        listSelect(`card[${f.id}][width]`, widths, val?.length_max ? val?.width : null, show, L.lWidth || 'En'),
      );
    } else {
      fixed.append(
        numberInput(`card[${f.id}][length]`, val?.length_max ? null : val?.length, L.lLength || 'Uzunluq'),
        sep('×'),
        numberInput(`card[${f.id}][width]`, val?.length_max ? null : val?.width, L.lWidth || 'En'),
      );
      mixedRow.append(
        numberInput(`card[${f.id}][length]`, val?.length_max ? val?.length : null, L.lLengthMin || 'Min. uzunluq'),
        sep('–'),
        numberInput(`card[${f.id}][length_max]`, val?.length_max, L.lLengthMax || 'Maks. uzunluq'),
        sep('×'),
        numberInput(`card[${f.id}][width]`, val?.length_max ? val?.width : null, L.lWidth || 'En'),
      );
    }

    wrap.append(fixed, mixedRow);
    return wrap;
  }

  function text(f, val) {
    if (f.type === 'textarea') {
      const ta = el('textarea', 'min-h-[90px] w-full resize-y rounded border border-black/15 bg-white px-3.5 py-2.5 text-sm text-ink outline-none transition focus:border-black/40');
      ta.name = `card[${f.id}][value]`;
      ta.maxLength = 2000;
      if (val?.value) ta.value = val.value;
      return ta;
    }
    const i = el('input', CTRL);
    i.type = 'text';
    i.maxLength = 255;
    i.name = `card[${f.id}][value]`;
    if (val?.value) i.value = val.value;
    return i;
  }

  // A document: upload a file (PDF / image) or give an https link. On an edit the
  // stored one is shown and kept unless replaced.
  function documentField(f, val) {
    const wrap = el('div', 'flex w-full flex-col gap-2');
    const current = val?.value || '';
    if (current) {
      const row = el('div', 'flex items-center gap-2 text-[13px]');
      const keep = el('input');
      keep.type = 'hidden';
      keep.name = `card[${f.id}][keep]`;
      keep.value = current;
      const link = el('a', 'font-semibold text-ink underline', L.lDocCurrent || 'Mövcud sənəd');
      link.href = current.startsWith('http') ? current : `/storage/${current}`;
      link.target = '_blank';
      link.rel = 'noopener noreferrer';
      row.append(keep, link, el('span', 'text-black/45', L.lDocReplace || '— dəyişmək üçün yeni fayl və ya link verin'));
      wrap.appendChild(row);
    }
    const file = el('input', 'block w-full text-sm text-black/70 file:mr-3 file:h-[38px] file:rounded file:border file:border-black/20 file:bg-white file:px-4 file:text-[13px] file:font-semibold file:text-ink');
    file.type = 'file';
    file.accept = 'application/pdf,image/jpeg,image/png,image/webp';
    file.name = `card[${f.id}][file]`;
    const url = el('input', CTRL);
    url.type = 'url';
    url.maxLength = 500;
    url.placeholder = L.lDocUrl || 'və ya link: https://…';
    url.name = `card[${f.id}][url]`;
    if (current.startsWith('http')) url.value = current;
    wrap.append(file, url);
    return wrap;
  }

  function fieldNode(f, val) {
    const full = ['multiselect', 'textarea', 'document'].includes(f.type)
      || (f.type === 'size' && (!f.presets?.length || !!f.rules?.mixed_when));
    const node = el('div', 'flex min-w-0 flex-col items-start gap-2' + (full ? ' sm:col-span-2' : ''));
    node.dataset.cardField = String(f.id);
    node.appendChild(labelRow(f));
    const control = f.type === 'dropdown' ? dropdown(f, val)
      : f.type === 'multiselect' ? multiselect(f, val)
        : f.type === 'size' ? size(f, val)
          : (f.type === 'decimal' || f.type === 'numeric') ? decimal(f, val)
            : f.type === 'document' ? documentField(f, val)
              : text(f, val);
    node.appendChild(control);
    if (f.hint) node.appendChild(el('p', 'text-[13px] leading-[1.45] text-black/50', f.hint));
    const err = el('p', ERR);
    err.hidden = true;
    err.dataset.cardErr = String(f.id);
    node.appendChild(err);
    return node;
  }

  // ── Live conditions ────────────────────────────────────────────────────────

  function answers() {
    const out = {};
    block.querySelectorAll('select[data-card-slug]').forEach((sel) => {
      const opt = sel.selectedOptions[0];
      out[sel.dataset.cardSlug] = opt?.dataset.slug && !sel.disabled ? [opt.dataset.slug] : [];
    });
    block.querySelectorAll('input[type="checkbox"][data-card-slug]').forEach((box) => {
      out[box.dataset.cardSlug] ??= [];
      if (box.checked && !box.disabled) out[box.dataset.cardSlug].push(box.dataset.slug);
    });
    return out;
  }

  function refresh() {
    if (!schema) return;

    // Two passes: an answer revealed by the first pass can reveal more.
    for (let pass = 0; pass < 2; pass++) {
      const a = answers();
      schema.fields.forEach((f) => {
        const node = block.querySelector(`[data-card-field="${f.id}"]`);
        if (!node) return;
        setShown(node, holds(f.visible_when, a));
        if (node.hidden) return;

        // Options that exist only for some answers (e.g. «Çoxqatlı modul»).
        f.options.forEach((o) => {
          if (!o.visible_when) return;
          const ok = holds(o.visible_when, a);
          const opt = node.querySelector(`option[value="${o.id}"]`);
          if (opt) {
            opt.disabled = !ok;
            opt.hidden = !ok;
            if (!ok && opt.selected) opt.parentElement.value = '';
          }
          const box = node.querySelector(`input[type="checkbox"][value="${o.id}"]`);
          if (box) {
            box.disabled = !ok;
            if (!ok) box.checked = false;
          }
        });

        // The text an option asks for («Digər» → exact name, «Bəli» → source).
        const textInput = node.querySelector('[data-card-text]');
        if (textInput) {
          const asking = [...node.querySelectorAll('option:checked, input[type="checkbox"]:checked')]
            .find((x) => x.dataset.prompt);
          textInput.hidden = !asking;
          textInput.style.display = asking ? '' : 'none';
          textInput.disabled = !asking;
          textInput.placeholder = asking?.dataset.prompt || '';
          textInput.required = !!asking;
        }

        // Mixed lengths ask for a min–max length.
        if (f.type === 'size') {
          const mixed = holds(f.rules?.mixed_when || { attr: '__never__', in: [] }, a);
          setShown(node.querySelector('[data-size-fixed]'), !mixed);
          setShown(node.querySelector('[data-size-mixed]'), mixed);
        }
      });
    }

    syncSale();
  }

  // Price and stock wording follow how this class sells; a custom-size stone has
  // no price at all.
  function syncSale() {
    const a = answers();
    const type = schema.sale?.type || 'area';
    const quote = holds(schema.sale?.quote_when || { attr: '__never__', in: [] }, a);
    const visible = (key) => schema.fields.some((f) => f.maps_to === key
      && !block.querySelector(`[data-card-field="${f.id}"]`)?.hidden);
    // A roll sold whole counts like a pack; a cut is sold by the running metre or,
    // for stretch-ceiling film, by the m² itself.
    const wholeRoll = type === 'area' && visible('roll_width') && visible('roll_length');
    const cut = type === 'area' && visible('roll_width') && !wholeRoll && !visible('package_size');
    const cutM2 = cut && schema.sale?.cut_unit === 'm2';

    setShown(quoteNote, quote);
    setShown(priceRow, !quote);
    form.querySelectorAll('[data-card-sale]').forEach((n) => setShown(n, !quote && n.dataset.cardSale === type));

    // A unit class offers only its own units — and, where the card ties a unit to
    // an answer (ready-made: ədəd, custom size: m²), only the ones that answer allows.
    const allowed = (schema.sale?.units || []).map(String);
    const unitWhen = schema.sale?.unit_when || {};
    form.querySelectorAll('[data-card-sale="unit"] option').forEach((o) => {
      if (!o.value) return;
      const ok = allowed.includes(o.value) && (!unitWhen[o.value] || holds(unitWhen[o.value], a));
      o.hidden = !ok;
      o.disabled = !ok;
      if (!ok && o.selected) o.parentElement.value = '';
    });

    const pick = (node, area) => (type === 'board' ? node.dataset.lBoard
      : type === 'unit' ? node.dataset.lUnit
        : cutM2 ? (node.dataset.lM2 || area)
          : cut ? node.dataset.lRoll : area);
    if (priceHint) priceHint.textContent = pick(priceHint, priceHint.dataset.lArea);
    if (stockHint) stockHint.textContent = pick(stockHint, stockHint.dataset.lPack);

    // Some cards (ceiling accessories) leave the country optional.
    const countryLabel = document.querySelector('#countryField .ui-label-b2b');
    if (countryLabel) {
      countryLabel.dataset.base ??= countryLabel.textContent.replace(/\s*\*$/, '');
      countryLabel.textContent = countryLabel.dataset.base + (schema.country_required === false ? '' : ' *');
    }

    syncAvailability(quote);
  }

  function syncAvailability(quote = false) {
    const v = availability?.value || 'in_stock';
    setShown(stockField, !quote && v === 'in_stock');
    setShown(leadField, v === 'on_order');
  }

  // ── Collections of the chosen brand (datalist suggestions) ───────────────

  let collectionsFor = null;
  async function loadCollections() {
    const brandId = form.querySelector('input[name="brand_id"]')?.value || '';
    if (!collectionList || !brandId || collectionsFor === brandId) return;
    collectionsFor = brandId;
    try {
      const res = await fetch(`${collectionList.dataset.endpoint}?brand_id=${encodeURIComponent(brandId)}`, { headers: { Accept: 'application/json' } });
      const list = res.ok ? await res.json() : [];
      collectionList.innerHTML = '';
      list.forEach((name) => collectionList.appendChild(new Option(name)));
    } catch { /* suggestions only */ }
  }
  collection?.addEventListener('focus', loadCollections);

  // ── Public API ─────────────────────────────────────────────────────────────

  function toggleChrome(on) {
    form.querySelectorAll('[data-card-only]').forEach((n) => setShown(n, on));
    form.querySelectorAll('[data-card-hide]').forEach((n) => setShown(n, !on));
    if (!on) {
      form.querySelectorAll('[data-card-sale]').forEach((n) => setShown(n, false));
      setShown(quoteNote, false);
      setShown(priceRow, true);
      setShown(stockField, true);
    }
  }

  function activate(def, useInitial = false) {
    schema = def;
    state.active = true;
    block.innerHTML = '';
    const grid = el('div', 'grid w-full grid-cols-2 items-start gap-4 max-[640px]:grid-cols-1');
    const values = useInitial ? initial : {};
    def.fields.forEach((f) => grid.appendChild(fieldNode(f, values[f.id])));
    block.appendChild(grid);
    setShown(block, true);
    toggleChrome(true);
    refresh();
  }

  function deactivate() {
    if (!block) return;
    schema = null;
    state.active = false;
    block.innerHTML = '';
    setShown(block, false);
    toggleChrome(false);
    if (missingBox) missingBox.hidden = true;
  }

  block?.addEventListener('change', refresh);
  availability?.addEventListener('change', () => syncAvailability(priceRow?.hidden));

  function clearErrors() {
    form.querySelectorAll('[data-card-err]').forEach((p) => { p.hidden = true; p.textContent = ''; });
    form.querySelectorAll('[data-err-for]').forEach((p) => { p.hidden = true; p.textContent = ''; });
  }

  // 422 → each message under its own field; returns the first one to scroll to.
  function handleFailure(data) {
    clearErrors();
    let first = null;
    Object.entries(data.errors || {}).forEach(([key, messages]) => {
      const msg = Array.isArray(messages) ? messages[0] : String(messages);
      const m = key.match(/^card\.(\d+)/);
      const slot = m
        ? form.querySelector(`[data-card-err="${m[1]}"]`)
        : [...form.querySelectorAll('[data-err-for]')].find((p) => p.dataset.errFor.split(' ').includes(key));
      if (!slot) return;
      slot.textContent = msg;
      slot.hidden = false;
      first ??= slot;
    });
    return first;
  }

  function handleSuccess(data) {
    clearErrors();
    const missing = data.missing || [];
    if (missingBox) {
      missingBox.innerHTML = '';
      if (missing.length) {
        missingBox.appendChild(el('p', 'font-semibold', missingBox.dataset.lTitle || ''));
        const ul = el('ul', 'mt-1 list-disc pl-5');
        missing.forEach((m) => ul.appendChild(el('li', '', m)));
        missingBox.appendChild(ul);
      }
      missingBox.hidden = !missing.length;
    }
    return missing;
  }

  return {
    get active() { return state.active; },
    activate,
    deactivate,
    handleFailure,
    handleSuccess,
    missingTitle: () => missingBox?.dataset.lTitle || '',
  };
}
