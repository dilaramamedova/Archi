// Laminate card of the business product form (TZ «Ламинат»).
//
// Rendered from the class form's `laminate` schema when the seller picks the
// laminate class; every other class never touches this module. Its controls carry
// no `name`: serialize() writes the `laminate[...]` payload itself, so a dependant
// whose parent is not "yes" is simply never posted (the server answers a posted
// one with DEPENDENCY_CONFLICT). All copy comes from schema.t — nothing is
// hardcoded in any language.

const CTRL = 'h-[43px] w-full rounded border border-black/15 bg-white px-3.5 text-sm text-ink outline-none transition focus:border-black/40';
const CHIP = 'inline-flex cursor-pointer items-center gap-2 rounded border border-black/15 bg-white px-3.5 py-2 text-sm text-ink transition select-none hover:border-black/40 has-[:checked]:border-ink has-[:checked]:bg-ink has-[:checked]:text-white has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-black/25';
const SEG_WRAP = 'inline-flex max-w-full flex-wrap gap-0.5 rounded border border-black/15 bg-white p-0.5';
const SEG = 'cursor-pointer rounded-[3px] px-3.5 py-[7px] text-sm text-ink transition select-none hover:bg-gray-soft2 has-[:checked]:bg-ink has-[:checked]:text-white has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-black/25';
const BADGE = 'rounded bg-gray-soft2 px-1.5 py-0.5 text-[10px] font-semibold text-black/45';
const ERR = 'text-[13px] leading-[1.45] text-error';
const HINT = 'text-[13px] leading-[1.45] text-black/50';
const BTN = 'h-[38px] rounded border border-black/20 bg-white px-4 text-[13px] font-semibold text-ink transition hover:bg-gray-soft2';
const PANEL = 'flex w-full flex-col gap-4 rounded border border-black/10 bg-gray-soft2/60 p-4';
const GRID = 'grid w-full grid-cols-2 items-start gap-x-4 gap-y-5 max-[640px]:grid-cols-1';
const FULL = 'sm:col-span-2';

let uidSeq = 0;
const uid = (p) => `lam-${p}-${++uidSeq}`;

const el = (tag, cls, text) => {
  const n = document.createElement(tag);
  if (cls) n.className = cls;
  if (text != null) n.textContent = text;
  return n;
};

// style.display, not [hidden]: most of these nodes carry a flex/grid utility.
const toggle = (node, on) => { if (node) node.style.display = on ? '' : 'none'; };
const isBlank = (v) => v == null || String(v).trim() === '';

/** RFC 4122 v4. crypto.randomUUID only exists in secure contexts; archi.test is http. */
export function uuid() {
  try {
    if (typeof crypto.randomUUID === 'function') return crypto.randomUUID();
  } catch { /* insecure context */ }
  const b = crypto.getRandomValues(new Uint8Array(16));
  b[6] = (b[6] & 0x0f) | 0x40;
  b[8] = (b[8] & 0x3f) | 0x80;
  const h = [...b].map((x) => x.toString(16).padStart(2, '0')).join('');
  return `${h.slice(0, 8)}-${h.slice(8, 12)}-${h.slice(12, 16)}-${h.slice(16, 20)}-${h.slice(20)}`;
}

export function createLaminateForm({ form, imageCount }) {
  const root = document.getElementById('laminateBlock');
  const missingBox = document.getElementById('laminateMissing');
  const brandNote = document.getElementById('brandPendingNote');
  const brandInput = document.getElementById('pBrand');
  const brandId = form.querySelector('input[name="brand_id"]');
  const newBrand = form.querySelector('input[name="new_brand"]');
  const unitSel = document.getElementById('pUnit');
  const isEdit = form.dataset.method === 'PUT';
  let initial = null;
  try { initial = JSON.parse(document.getElementById('laminateInitial')?.textContent || 'null'); } catch { initial = null; }

  let S = null;            // the schema
  let T = {};              // schema.t
  let active = false;
  let version = initial?.version ?? null;
  let hideSnap = [];
  let unitSnap = null;
  let unitForced = null;
  let f = {};              // field controls by key
  let hosts = new Map();   // error path -> [slot]
  let docs = [];           // {id?, kind, name, href?, isLink, file?, url?, scope, existing, li, err}
  let docRows = {};        // kind -> {row, list, actions, scopeSel, err}
  let warrantyIndex = [];  // posted warranties[i] -> scope block
  let docIndex = [];       // posted laminate_documents[i] -> doc
  let lookupTimer = null;
  let lookupKey = '';
  let lookupBox = null;
  let dynErrs = [];

  const t = (key, rep = {}) => {
    let s = T[key] ?? key;
    Object.entries(rep).forEach(([k, v]) => { s = s.split(`:${k}`).join(String(v)); });
    return s;
  };
  const enumOf = (name) => S?.enums?.[name] || [];
  const enumLabel = (name, value) => enumOf(name).find((o) => o.value === value)?.label ?? value;
  const publishRequired = (path) => (S?.required_for_publish || []).includes(path);

  // ── Error slots ───────────────────────────────────────────────────────────
  function errSlot(paths) {
    const p = el('p', ERR);
    p.hidden = true;
    [].concat(paths).forEach((path) => {
      if (!hosts.has(path)) hosts.set(path, []);
      hosts.get(path).push(p);
    });
    return p;
  }

  // The first slot whose surroundings are on screen (e.g. the size field shows
  // length/width errors under the custom inputs when "Digər ölçü" is open, under
  // the chips otherwise).
  const shown = (slot) => slot.parentElement && slot.parentElement.getClientRects().length > 0;

  function hostFor(path) {
    const m = /^laminate\.warranties\.(\d+)\./.exec(path);
    if (m && warrantyIndex[+m[1]]) return warrantyIndex[+m[1]].err;
    const d = /^laminate_documents\.(\d+)\./.exec(path);
    if (d && docIndex[+d[1]]) return docIndex[+d[1]].err;

    const own = (hosts.get(path) || []);
    const visible = own.find(shown) || own[0];
    if (visible) return visible;

    const page = [...form.querySelectorAll('[data-err-for]')].find((n) => n.dataset.errFor.split(/\s+/).includes(path));
    if (page) return page;

    // Any other field: put the message right under the control that posted it.
    const name = path.split('.').map((s, i) => (i ? `[${s}]` : s)).join('');
    const ctrl = form.querySelector(`[name="${CSS.escape(name)}"], [name="${CSS.escape(name)}[]"]`);
    if (ctrl) {
      const slot = el('p', ERR + ' mt-1.5');
      (ctrl.closest('.cab-field') || ctrl.parentElement).appendChild(slot);
      dynErrs.push(slot);
      return slot;
    }
    return hosts.get('*')?.[0] || null;
  }

  function clearErrors() {
    hosts.forEach((list) => list.forEach((p) => { p.hidden = true; p.textContent = ''; }));
    form.querySelectorAll('[data-err-for]').forEach((p) => { p.hidden = true; p.textContent = ''; });
    docs.forEach((d) => { if (d.err) { d.err.hidden = true; d.err.textContent = ''; } });
    dynErrs.forEach((p) => p.remove());
    dynErrs = [];
  }

  /** Show every message under its own field; returns the first slot used. */
  function showErrors(errors) {
    clearErrors();
    let first = null;
    Object.entries(errors || {}).forEach(([path, msgs]) => {
      const slot = hostFor(path);
      if (!slot) return;
      const text = [].concat(msgs).join(' ');
      slot.textContent = slot.textContent ? `${slot.textContent} ${text}` : text;
      slot.hidden = false;
      first ??= slot;
    });
    return first;
  }

  // ── Building blocks ───────────────────────────────────────────────────────
  function labelRow(text, { publish = false } = {}) {
    const row = el('div', 'flex flex-wrap items-center gap-1.5');
    row.appendChild(el('span', 'ui-label-b2b', text));
    if (publish) row.appendChild(el('span', BADGE, t('ui.required_for_publish')));
    return row;
  }

  function fieldNode(label, control, { publish = false, hint = null, errPaths = [], full = false } = {}) {
    const node = el('div', 'flex min-w-0 flex-col items-start gap-2' + (full ? ` ${FULL}` : ''));
    node.appendChild(labelRow(label, { publish }));
    node.appendChild(control);
    if (hint) node.appendChild(el('p', HINT, hint));
    if (errPaths.length) node.appendChild(errSlot(errPaths));
    return node;
  }

  function textInput({ max = 255, mode = null, placeholder = '' } = {}) {
    const i = el('input', CTRL);
    i.type = 'text';
    i.autocomplete = 'off';
    if (max) i.maxLength = max;
    if (mode) i.inputMode = mode;
    if (placeholder) i.placeholder = placeholder;
    return i;
  }

  // Decimal inputs are text + inputmode=decimal: a seller types "1,835" and a
  // number input would reject the comma. The raw text is posted; the server
  // normalises it and owns every check.
  function unitInput(unit, { mode = 'decimal', placeholder = '' } = {}) {
    const input = textInput({ max: 40, mode, placeholder });
    if (!unit) return { wrap: input, input };
    const wrap = el('div', 'relative w-full min-w-0 max-w-[240px]');
    input.classList.add('pr-14');
    wrap.append(input, el('span', 'pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-sm text-black/40', unit));
    return { wrap, input };
  }

  /** Radio (single) or checkbox (multi) chips. */
  function chips(items, { multi = false, seg = false, onChange = null } = {}) {
    const name = uid('g');
    const wrap = el('div', seg ? SEG_WRAP : 'flex w-full flex-wrap gap-2');
    const inputs = items.map((it) => {
      const label = el('label', seg ? SEG : CHIP);
      const box = el('input', 'sr-only');
      box.type = multi ? 'checkbox' : 'radio';
      // The name only groups the radios; serialize() drops every `lam-*` key.
      box.name = name;
      box.value = String(it.value);
      label.append(box, el('span', '', it.label));
      wrap.appendChild(label);
      box.addEventListener('change', () => onChange?.(api.get(), true));
      return box;
    });
    const api = {
      wrap,
      inputs,
      get: () => (multi ? inputs.filter((i) => i.checked).map((i) => i.value) : (inputs.find((i) => i.checked)?.value ?? '')),
      set(v) {
        const want = multi ? [].concat(v || []).map(String) : [String(v ?? '')];
        inputs.forEach((i) => { i.checked = want.includes(i.value); });
        onChange?.(api.get(), false);
      },
    };
    return api;
  }

  /** Quick picks + "Digər" (free input) + optional "Göstərilməyib". */
  function pickField({ picks, unit = '', mode = 'decimal', notStated = null, onChange = null }) {
    const items = picks.map((p) => ({ value: `p:${p}`, label: unit ? `${p} ${unit}` : String(p) }));
    items.push({ value: 'other', label: t('ui.other') });
    if (notStated) items.push({ value: 'none', label: t(notStated) });
    const box = el('div', 'flex w-full flex-col gap-2');
    const other = unitInput(unit, { mode });
    toggle(other.wrap, false);
    const group = chips(items, {
      onChange: (v, user) => {
        toggle(other.wrap, v === 'other');
        if (user && v === 'other') other.input.focus();
        onChange?.();
      },
    });
    box.append(group.wrap, other.wrap);
    other.input.addEventListener('input', () => onChange?.());
    const api = {
      wrap: box,
      input: other.input,
      get() {
        const v = group.get();
        if (v.startsWith('p:')) return v.slice(2);
        if (v === 'other') return other.input.value;
        return '';
      },
      set(v) {
        other.input.value = '';
        if (isBlank(v)) { group.set(notStated ? 'none' : ''); return; }
        const s = String(v);
        const hit = picks.find((p) => String(p) === s || Number(p) === Number(s.replace(',', '.')));
        if (hit !== undefined) { group.set(`p:${hit}`); return; }
        group.set('other');
        other.input.value = s;
      },
    };
    return api;
  }

  /** Dictionary answer: listed options + "Digər" (proposal) + "Göstərilməyib". */
  function optionField(field) {
    const dict = S.dictionaries?.[field] || [];
    const items = dict.map((o) => ({ value: String(o.id), label: o.label }));
    items.push({ value: 'other', label: t('ui.other') }, { value: 'none', label: t('ui.not_stated_m') });
    const box = el('div', 'flex w-full flex-col gap-2');
    const input = textInput({ max: 100, placeholder: t('ui.proposed_placeholder') });
    input.classList.add('max-w-[360px]');
    toggle(input, false);
    const pending = el('p', 'text-[13px] leading-[1.45] text-warn');
    toggle(pending, false);
    const group = chips(items, {
      onChange: (v, user) => {
        toggle(input, v === 'other');
        if (user) toggle(pending, false);
        if (user && v === 'other') input.focus();
      },
    });
    input.addEventListener('input', () => toggle(pending, false));
    box.append(group.wrap, input, pending);
    return {
      wrap: box,
      get() {
        const v = group.get();
        return {
          id: v && v !== 'other' && v !== 'none' ? v : '',
          proposed: v === 'other' ? input.value : '',
        };
      },
      set(id, proposed) {
        input.value = '';
        toggle(pending, false);
        if (!isBlank(proposed)) {
          group.set('other');
          input.value = proposed;
          pending.textContent = t('ui.proposed_pending', { value: proposed });
          toggle(pending, true);
        } else if (!isBlank(id) && dict.some((o) => String(o.id) === String(id))) {
          group.set(String(id));
        } else {
          group.set('none');
        }
      },
    };
  }

  /** yes / no / unknown — a segmented control, defaulting to unknown. */
  function triField(enumName, onChange) {
    const group = chips(enumOf(enumName).map((o) => ({ value: o.value, label: o.label })), { seg: true, onChange });
    return { wrap: group.wrap, get: () => group.get() || 'unknown', set: (v) => group.set(v || 'unknown') };
  }

  // ── Sections ──────────────────────────────────────────────────────────────
  function sectionHead(text) {
    return el('p', 'w-full border-t border-black/10 pt-4 text-[15px] font-semibold text-ink', text);
  }

  function buildIdentity(wrap) {
    wrap.appendChild(sectionHead(t('ui.section_identity')));
    const g = el('div', GRID);
    f.model_name = textInput({ max: 200 });
    f.collection_name = textInput({ max: 150 });
    f.manufacturer_sku = textInput({ max: 100 });
    g.append(
      fieldNode(t('field.model_name'), f.model_name, { publish: publishRequired('laminate.model_name'), errPaths: ['laminate.model_name'] }),
      fieldNode(t('field.collection_name'), f.collection_name, { errPaths: ['laminate.collection_name'] }),
      fieldNode(t('field.manufacturer_sku'), f.manufacturer_sku, { publish: publishRequired('laminate.manufacturer_sku'), errPaths: ['laminate.manufacturer_sku'] }),
    );
    lookupBox = el('div', `${FULL} flex w-full flex-wrap items-center gap-3 rounded border border-yellow-line bg-sel-bg px-4 py-3`);
    toggle(lookupBox, false);
    g.appendChild(lookupBox);
    wrap.appendChild(g);
    f.manufacturer_sku.addEventListener('input', scheduleLookup);
  }

  function buildMain(wrap) {
    wrap.appendChild(sectionHead(t('ui.section_main')));
    wrap.appendChild(el('p', HINT, t('ui.decimal_hint')));
    const g = el('div', GRID);
    const u = S.units || {};
    const picks = S.picks || {};

    // 2 · country
    const country = el('select', CTRL + ' appearance-none pr-8');
    country.appendChild(new Option(t('ui.not_stated_f'), ''));
    (S.countries || []).forEach((c) => country.appendChild(new Option(c.name, c.code)));
    const cWrap = el('div', 'relative w-full min-w-0');
    cWrap.append(country, el('span', 'pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-xs text-black/40', '▾'));
    f.country_code = { get: () => country.value, set: (v) => { country.value = v || ''; if (country.value !== (v || '')) country.value = ''; } };
    g.appendChild(fieldNode(t('field.country_code'), cWrap, { errPaths: ['laminate.country_code'] }));

    // 3 · usage class, 4 · AC
    f.usage_class = optionField('usage_class');
    g.appendChild(fieldNode(t('field.usage_class_option_id'), f.usage_class.wrap, { errPaths: ['laminate.usage_class_option_id', 'laminate.usage_class_proposed'] }));
    f.abrasion = optionField('abrasion');
    g.appendChild(fieldNode(t('field.abrasion_option_id'), f.abrasion.wrap, { errPaths: ['laminate.abrasion_option_id', 'laminate.abrasion_proposed'] }));

    // 5 · thickness
    f.thickness_mm = pickField({ picks: picks.thickness_mm || [], unit: u.mm, onChange: refreshMissing });
    g.appendChild(fieldNode(t('field.thickness_mm'), f.thickness_mm.wrap, { publish: publishRequired('laminate.thickness_mm'), errPaths: ['laminate.thickness_mm'] }));

    // 6 · size — ready pairs + "Digər ölçü" (two inputs)
    const sizeBox = el('div', 'flex w-full flex-col gap-2');
    const pairs = picks.sizes || [];
    const custom = el('div', 'flex w-full flex-wrap items-start gap-3');
    const len = unitInput(u.mm);
    const wid = unitInput(u.mm);
    const lenNode = fieldNode(t('field.length_mm'), len.wrap, { errPaths: ['laminate.length_mm'] });
    const widNode = fieldNode(t('field.width_mm'), wid.wrap, { errPaths: ['laminate.width_mm'] });
    lenNode.classList.add('flex-1', 'min-w-[160px]');
    widNode.classList.add('flex-1', 'min-w-[160px]');
    custom.append(lenNode, widNode);
    toggle(custom, false);
    const sizeGroup = chips(
      [...pairs.map(([l, w]) => ({ value: `p:${l}x${w}`, label: `${l} × ${w}` })), { value: 'other', label: t('ui.other_size') }],
      {
        onChange: (v, user) => {
          toggle(custom, v === 'other');
          if (user && v === 'other') len.input.focus();
          refreshMissing();
        },
      },
    );
    [len.input, wid.input].forEach((i) => i.addEventListener('input', refreshMissing));
    sizeBox.append(sizeGroup.wrap, custom);
    f.size = {
      get() {
        const v = sizeGroup.get();
        if (v.startsWith('p:')) return v.slice(2).split('x');
        if (v === 'other') return [len.input.value, wid.input.value];
        return ['', ''];
      },
      set(l, w) {
        len.input.value = '';
        wid.input.value = '';
        if (isBlank(l) && isBlank(w)) { sizeGroup.set(''); return; }
        const hit = pairs.find(([pl, pw]) => Number(pl) === Number(l) && Number(pw) === Number(w));
        if (hit) { sizeGroup.set(`p:${hit[0]}x${hit[1]}`); return; }
        sizeGroup.set('other');
        len.input.value = l ?? '';
        wid.input.value = w ?? '';
      },
    };
    g.appendChild(fieldNode(t('field.size'), sizeBox, {
      full: true,
      publish: publishRequired('laminate.length_mm') || publishRequired('laminate.width_mm'),
      errPaths: ['laminate.length_mm', 'laminate.width_mm'],
    }));

    // 7 · water protection
    const waterPanel = el('div', PANEL);
    f.water_claim = chips(enumOf('water_claim'), {});
    f.water_hours = pickField({ picks: picks.water_hours || [], unit: u.hours, mode: 'numeric', notStated: 'ui.not_stated' });
    waterPanel.append(
      fieldNode(t('field.water_claim'), f.water_claim.wrap, { errPaths: ['laminate.water_claim'] }),
      fieldNode(t('field.water_hours'), f.water_hours.wrap, { errPaths: ['laminate.water_hours'] }),
    );
    const resetWater = () => { f.water_claim.set('unknown'); f.water_hours.set(null); dropDocs((d) => d.kind === 'water_conditions'); };
    f.water_protection = triField('tri', (v, user) => parentChanged(v, user, waterPanel, resetWater));
    g.appendChild(parentNode('field.water_protection', f.water_protection, waterPanel, 'laminate.water_protection'));

    // 8 · underfloor heating
    const heatPanel = el('div', PANEL);
    const types = chips(enumOf('heating_types'), { multi: true });
    const typesNode = fieldNode(t('field.heating_types'), types.wrap, { errPaths: ['laminate.heating_types'] });
    f.heating_types = types;
    f.heating_types_known = chips(
      [{ value: '1', label: t('ui.heating_known_yes') }, { value: '0', label: t('ui.heating_known_no') }],
      {
        seg: true,
        onChange: (v, user) => {
          toggle(typesNode, v === '1');
          // "Not stated" posts no type; coming back does not restore old ticks.
          if (user && v !== '1') types.set([]);
        },
      },
    );
    f.max_surface_temp_c = pickField({ picks: picks.max_surface_temp_c || [], unit: u.celsius, notStated: 'ui.not_stated' });
    const res = unitInput(u.resistance);
    f.thermal_resistance_m2k_w = { get: () => res.input.value, set: (v) => { res.input.value = v ?? ''; } };
    heatPanel.append(
      fieldNode(t('field.heating_types_known'), f.heating_types_known.wrap, { errPaths: ['laminate.heating_types_known'] }),
      typesNode,
      fieldNode(t('field.max_surface_temp_c'), f.max_surface_temp_c.wrap, { errPaths: ['laminate.max_surface_temp_c'] }),
      fieldNode(t('field.thermal_resistance_m2k_w'), res.wrap, { errPaths: ['laminate.thermal_resistance_m2k_w'] }),
    );
    const resetHeat = () => {
      f.heating_types_known.set('0');
      types.set([]);
      f.max_surface_temp_c.set(null);
      f.thermal_resistance_m2k_w.set('');
    };
    f.floor_heating = triField('tri', (v, user) => parentChanged(v, user, heatPanel, resetHeat));
    g.appendChild(parentNode('field.floor_heating', f.floor_heating, heatPanel, 'laminate.floor_heating'));

    // 9 · integrated underlay
    const underPanel = el('div', PANEL);
    f.underlay_thickness_mm = pickField({ picks: picks.underlay_thickness_mm || [], unit: u.mm, notStated: 'ui.not_stated' });
    f.thickness_includes_underlay = triField('tri');
    underPanel.append(
      fieldNode(t('field.underlay_thickness_mm'), f.underlay_thickness_mm.wrap, { errPaths: ['laminate.underlay_thickness_mm'] }),
      fieldNode(t('field.thickness_includes_underlay'), f.thickness_includes_underlay.wrap, { errPaths: ['laminate.thickness_includes_underlay'] }),
    );
    const resetUnder = () => { f.underlay_thickness_mm.set(null); f.thickness_includes_underlay.set('unknown'); };
    f.integrated_underlay = triField('tri', (v, user) => parentChanged(v, user, underPanel, resetUnder));
    g.appendChild(parentNode('field.integrated_underlay', f.integrated_underlay, underPanel, 'laminate.integrated_underlay'));

    // 10 · joint type, 11 · pack area
    f.connection = optionField('connection');
    g.appendChild(fieldNode(t('field.connection_option_id'), f.connection.wrap, { errPaths: ['laminate.connection_option_id', 'laminate.connection_proposed'] }));
    const pack = unitInput(u.m2, { placeholder: '1,835' });
    pack.input.addEventListener('input', refreshMissing);
    f.pack_area_m2 = { get: () => pack.input.value, set: (v) => { pack.input.value = v ?? ''; } };
    g.appendChild(fieldNode(t('field.pack_area_m2'), pack.wrap, {
      publish: publishRequired('laminate.pack_area_m2'),
      hint: t('ui.pack_area_hint'),
      errPaths: ['laminate.pack_area_m2'],
    }));

    // 12 · warranty
    const warrPanel = el('div', PANEL);
    buildWarranties(warrPanel);
    const resetWarr = () => { f.warranties.set([]); };
    f.warranty = triField('warranty_tri', (v, user) => parentChanged(v, user, warrPanel, resetWarr));
    g.appendChild(parentNode('field.warranty', f.warranty, warrPanel, 'laminate.warranty'));

    wrap.appendChild(g);
  }

  function parentNode(labelKey, tri, panel, errPath) {
    const node = el('div', `flex min-w-0 flex-col items-start gap-2 ${FULL}`);
    node.append(labelRow(t(labelKey)), tri.wrap, errSlot([errPath]), panel);
    toggle(panel, false);
    return node;
  }

  // A dependant exists only while its parent is "yes" (TZ §4). Leaving "yes"
  // empties the dependants, so coming back never restores old answers silently.
  function parentChanged(v, user, panel, reset) {
    toggle(panel, v === 'yes');
    if (user && v !== 'yes') reset();
    if (user) syncDocKinds();
  }

  function buildWarranties(panel) {
    const scopes = enumOf('warranty_scopes');
    const blocks = {};
    const years = S.picks?.warranty_years || [];
    const yearsUnit = S.units?.years || '';
    const scopeGroup = chips(scopes, {
      multi: true,
      onChange: (list, user) => {
        scopes.forEach((s) => {
          const on = list.includes(s.value);
          toggle(blocks[s.value].wrap, on);
          if (user && !on) {
            blocks[s.value].reset();
            dropDocs((d) => d.kind === 'warranty_conditions' && d.scope === s.value);
          }
        });
        if (user) syncDocKinds();
      },
    });
    panel.append(fieldNode(t('field.warranties'), scopeGroup.wrap, { errPaths: ['laminate.warranties'] }));

    scopes.forEach((s) => {
      const wrap = el('div', 'flex w-full flex-col gap-3 rounded border border-black/10 bg-white p-4');
      wrap.appendChild(el('p', 'text-sm font-semibold text-ink', s.label));
      const other = unitInput(yearsUnit, { mode: 'numeric' });
      toggle(other.wrap, false);
      const term = chips([
        ...years.map((y) => ({ value: `y:${y}`, label: `${y} ${yearsUnit}` })),
        { value: 'other', label: t('ui.other') },
        { value: 'lifetime', label: enumLabel('warranty_terms', 'lifetime') },
        { value: 'unknown', label: enumLabel('warranty_terms', 'unknown') },
      ], {
        onChange: (v, user) => {
          toggle(other.wrap, v === 'other');
          if (user && v === 'other') other.input.focus();
        },
      });
      const termBox = el('div', 'flex w-full flex-col gap-2');
      termBox.append(term.wrap, other.wrap);
      const cond = el('textarea', 'min-h-[80px] w-full resize-y rounded border border-black/15 bg-white px-3.5 py-2.5 text-sm text-ink outline-none transition focus:border-black/40');
      cond.maxLength = 2000;
      cond.placeholder = t('ui.warranty_conditions_placeholder');
      const err = el('p', ERR);
      err.hidden = true;
      wrap.append(
        fieldNode(t('field.warranty_term_kind'), termBox),
        fieldNode(t('field.warranty_conditions_text'), cond),
        err,
      );
      toggle(wrap, false);
      panel.appendChild(wrap);
      blocks[s.value] = {
        wrap,
        err,
        get() {
          const v = term.get();
          if (v.startsWith('y:')) return { term_kind: 'years', years: v.slice(2) };
          if (v === 'other') return { term_kind: 'years', years: other.input.value };
          if (v === 'lifetime') return { term_kind: 'lifetime' };
          return { term_kind: 'unknown' };
        },
        set(w) {
          other.input.value = '';
          cond.value = w?.conditions_text ?? '';
          if (w?.term_kind === 'years') {
            const hit = years.find((y) => Number(y) === Number(w.years));
            if (hit !== undefined) term.set(`y:${hit}`);
            else { term.set('other'); other.input.value = w.years ?? ''; }
          } else {
            term.set(w?.term_kind === 'lifetime' ? 'lifetime' : 'unknown');
          }
        },
        reset() { this.set(null); },
        cond,
      };
    });

    f.warranties = {
      scopes: () => scopeGroup.get(),
      blocks,
      get: () => scopeGroup.get().map((scope) => ({ scope, ...blocks[scope].get(), conditions_text: blocks[scope].cond.value })),
      set(list) {
        const rows = [].concat(list || []);
        scopes.forEach((s) => blocks[s.value].set(rows.find((r) => r.scope === s.value) || null));
        scopeGroup.set(rows.map((r) => r.scope));
      },
    };
  }

  // ── Documents ─────────────────────────────────────────────────────────────
  function buildDocuments(wrap) {
    wrap.appendChild(sectionHead(t('ui.section_documents')));
    wrap.appendChild(el('p', HINT, t('ui.documents_hint')));
    const list = el('div', 'flex w-full flex-col gap-4');
    enumOf('document_kinds').forEach((k) => list.appendChild(docRow(k.value, k.label)));
    wrap.append(list, errSlot(['laminate_documents']));
  }

  function docRow(kind, label) {
    const row = el('div', 'flex w-full flex-col items-start gap-2');
    row.appendChild(el('span', 'ui-label-b2b', label));
    const ul = el('ul', 'flex w-full flex-col gap-2');
    const actions = el('div', 'flex w-full flex-wrap items-center gap-2');

    let scopeSel = null;
    if (kind === 'warranty_conditions') {
      scopeSel = el('select', 'h-[38px] rounded border border-black/15 bg-white px-3 text-[13px] text-ink outline-none focus:border-black/40');
      actions.appendChild(scopeSel);
    }

    const fileLabel = el('label', `${BTN} inline-flex cursor-pointer items-center`);
    const fileInput = el('input', 'hidden');
    fileInput.type = 'file';
    fileInput.accept = S.documents?.accept || '';
    fileLabel.append(el('span', '', t('ui.add_file')), fileInput);

    const linkBtn = el('button', BTN, t('ui.add_link'));
    linkBtn.type = 'button';
    const linkRow = el('div', 'flex w-full max-w-[560px] items-center gap-2');
    const linkInput = textInput({ max: 2048, mode: 'url', placeholder: 'https://' });
    const linkOk = el('button', `${BTN} shrink-0 border-ink bg-ink text-white hover:bg-ink/90`, t('ui.add_link'));
    linkOk.type = 'button';
    linkRow.append(linkInput, linkOk);
    toggle(linkRow, false);
    actions.append(fileLabel, linkBtn);

    const err = el('p', ERR);
    err.hidden = true;
    const fail = (code) => { err.textContent = t(`errors.${code}`); err.hidden = false; };

    fileInput.addEventListener('change', () => {
      const file = fileInput.files?.[0];
      fileInput.value = '';
      err.hidden = true;
      if (!file) return;
      const ext = (file.name.split('.').pop() || '').toLowerCase();
      if (!['pdf', 'doc', 'docx'].includes(ext)) return fail('INVALID_FILE');
      if (file.size > (S.documents?.max_kb || 20480) * 1024) return fail('FILE_TOO_LARGE');
      addDoc({ kind, name: file.name, file, scope: scopeSel?.value || null });
    });

    linkBtn.addEventListener('click', () => {
      const open = linkRow.style.display === 'none';
      toggle(linkRow, open);
      if (open) linkInput.focus();
    });

    const addLink = () => {
      const url = linkInput.value.trim();
      err.hidden = true;
      if (!/^https:\/\/[^\s/]+\.[^\s]+/i.test(url)) return fail('INVALID_URL');
      let host = url;
      try { host = new URL(url).host; } catch { /* keep the raw text */ }
      addDoc({ kind, name: host, url, isLink: true, scope: scopeSel?.value || null });
      linkInput.value = '';
      toggle(linkRow, false);
    };
    linkOk.addEventListener('click', addLink);
    linkInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); addLink(); } });

    row.append(ul, actions, linkRow, err);
    docRows[kind] = { row, ul, actions, scopeSel, linkRow };
    return row;
  }

  function addDoc(d, { silent = false } = {}) {
    docs.push(d);
    renderDocs();
    if (!silent) refreshMissing();
  }

  function dropDocs(pred) {
    docs = docs.filter((d) => !pred(d));
    renderDocs();
  }

  function renderDocs() {
    const max = S?.documents?.max || 10;
    Object.entries(docRows).forEach(([kind, r]) => {
      r.ul.innerHTML = '';
      docs.filter((d) => d.kind === kind).forEach((d) => {
        const li = el('li', 'flex w-full flex-col gap-1');
        const line = el('div', 'flex w-full items-center gap-3 rounded border border-black/10 bg-white px-3.5 py-2 text-sm text-ink');
        const name = d.href
          ? Object.assign(el('a', 'min-w-0 flex-1 truncate underline decoration-black/25 underline-offset-2 hover:decoration-black', d.name), { href: d.href, target: '_blank', rel: 'noopener' })
          : el('span', 'min-w-0 flex-1 truncate', d.name);
        line.appendChild(el('span', 'shrink-0 text-xs font-semibold text-black/40', d.isLink ? '↗' : '▤'));
        line.appendChild(name);
        if (d.scope) line.appendChild(el('span', BADGE, enumLabel('warranty_scopes', d.scope)));
        const rm = el('button', 'flex size-7 shrink-0 items-center justify-center rounded border border-black/15 text-black/40 transition hover:border-error hover:text-error', '✕');
        rm.type = 'button';
        rm.setAttribute('aria-label', t('ui.remove'));
        rm.title = t('ui.remove');
        rm.addEventListener('click', () => { docs = docs.filter((x) => x !== d); renderDocs(); });
        line.appendChild(rm);
        d.err ??= Object.assign(el('p', ERR), { hidden: true });
        li.append(line, d.err);
        r.ul.appendChild(li);
      });
      toggle(r.actions, docs.length < max);
    });
    syncDocKinds();
  }

  // water_conditions only while water protection is yes; warranty_conditions only
  // while warranty is yes, each bound to one of the active scopes.
  function syncDocKinds() {
    if (!f.water_protection) return;
    const water = f.water_protection.get() === 'yes';
    const warranty = f.warranty.get() === 'yes';
    const scopes = warranty ? f.warranties.scopes() : [];
    if (docRows.water_conditions) toggle(docRows.water_conditions.row, water);
    const w = docRows.warranty_conditions;
    if (w) {
      toggle(w.row, warranty && scopes.length > 0);
      const keep = w.scopeSel.value;
      w.scopeSel.innerHTML = '';
      scopes.forEach((s) => w.scopeSel.appendChild(new Option(enumLabel('warranty_scopes', s), s)));
      if (scopes.includes(keep)) w.scopeSel.value = keep;
    }
  }

  // ── Lookup by brand + manufacturer article ────────────────────────────────
  function scheduleLookup() {
    clearTimeout(lookupTimer);
    lookupTimer = setTimeout(runLookup, 500);
  }

  async function runLookup() {
    if (!active || !lookupBox) return;
    const b = brandId?.value || '';
    const sku = f.manufacturer_sku.value.trim();
    const key = `${b}|${sku}`;
    if (!b || !sku) { lookupKey = ''; toggle(lookupBox, false); return; }
    if (key === lookupKey) return;
    lookupKey = key;
    try {
      const url = `${root.dataset.lookup}?brand_id=${encodeURIComponent(b)}&sku=${encodeURIComponent(sku)}`;
      const res = await fetch(url, { headers: { Accept: 'application/json' } });
      if (!res.ok) return;
      const data = await res.json();
      if (!active || key !== lookupKey) return;
      const match = data.matches?.[0];
      lookupBox.innerHTML = '';
      if (!match) { toggle(lookupBox, false); return; }
      const apply = el('button', 'h-[34px] shrink-0 rounded bg-ink px-4 text-[13px] font-semibold text-white transition hover:bg-ink/90', t('ui.lookup_apply'));
      apply.type = 'button';
      apply.addEventListener('click', () => {
        // TZ §6: an existing variant replaces the form's values whole, not mixed.
        fill(match.values, { keepDocs: true });
        toggle(lookupBox, false);
        refreshMissing();
      });
      lookupBox.append(el('p', 'min-w-0 flex-1 text-sm text-ink', t('ui.lookup_found', { name: match.name })), apply);
      toggle(lookupBox, true);
    } catch { /* lookup is a convenience; ignore network trouble */ }
  }

  // ── Fill / serialize ──────────────────────────────────────────────────────
  function fill(v, { keepDocs = false } = {}) {
    v = v || {};
    f.model_name.value = v.model_name ?? '';
    f.collection_name.value = v.collection_name ?? '';
    f.manufacturer_sku.value = v.manufacturer_sku ?? '';
    f.country_code.set(v.country_code ?? '');
    f.usage_class.set(v.usage_class_option_id, v.usage_class_proposed);
    f.abrasion.set(v.abrasion_option_id, v.abrasion_proposed);
    f.connection.set(v.connection_option_id, v.connection_proposed);
    f.thickness_mm.set(v.thickness_mm);
    f.size.set(v.length_mm, v.width_mm);
    f.pack_area_m2.set(v.pack_area_m2);

    f.water_protection.set(v.water_protection);
    f.water_claim.set(v.water_protection === 'yes' ? (v.water_claim || 'unknown') : 'unknown');
    f.water_hours.set(v.water_protection === 'yes' ? v.water_hours : null);

    const heat = v.floor_heating === 'yes';
    f.floor_heating.set(v.floor_heating);
    f.heating_types_known.set(heat && v.heating_types_known ? '1' : '0');
    f.heating_types.set(heat && v.heating_types_known ? v.heating_types : []);
    f.max_surface_temp_c.set(heat ? v.max_surface_temp_c : null);
    f.thermal_resistance_m2k_w.set(heat ? v.thermal_resistance_m2k_w : '');

    const under = v.integrated_underlay === 'yes';
    f.integrated_underlay.set(v.integrated_underlay);
    f.underlay_thickness_mm.set(under ? v.underlay_thickness_mm : null);
    f.thickness_includes_underlay.set(under ? v.thickness_includes_underlay : 'unknown');

    f.warranty.set(v.warranty);
    f.warranties.set(v.warranty === 'yes' ? v.warranties : []);

    if (!keepDocs) {
      docs = (v.documents || []).map((d) => ({
        id: d.id, kind: d.kind, name: d.name, href: d.url, isLink: !!d.is_link, scope: d.warranty_scope || null, existing: true,
      }));
    } else {
      // Documents stay the seller's own; only those the new answers still allow remain.
      if (v.water_protection !== 'yes') docs = docs.filter((d) => d.kind !== 'water_conditions');
      const scopes = v.warranty === 'yes' ? (v.warranties || []).map((w) => w.scope) : [];
      docs = docs.filter((d) => d.kind !== 'warranty_conditions' || scopes.includes(d.scope));
    }
    renderDocs();
  }

  function serialize(fd) {
    if (!active) return;
    // The chips' radio/checkbox groups are UI only — never part of the payload.
    [...fd.keys()].filter((k) => k.startsWith('lam-')).forEach((k) => fd.delete(k));

    const L = (k, v) => fd.append(`laminate[${k}]`, v == null ? '' : String(v));
    L('model_name', f.model_name.value);
    L('collection_name', f.collection_name.value);
    L('manufacturer_sku', f.manufacturer_sku.value);
    L('country_code', f.country_code.get());

    // "Digər" posts an empty id plus the proposal; anything else posts the id (or
    // '' for "not stated") and an empty proposal, which withdraws a pending one.
    [['usage_class', f.usage_class], ['abrasion', f.abrasion], ['connection', f.connection]].forEach(([k, c]) => {
      const { id, proposed } = c.get();
      L(`${k}_option_id`, id);
      L(`${k}_proposed`, proposed);
    });

    L('thickness_mm', f.thickness_mm.get());
    const [l, w] = f.size.get();
    L('length_mm', l);
    L('width_mm', w);
    L('pack_area_m2', f.pack_area_m2.get());

    const water = f.water_protection.get();
    L('water_protection', water);
    if (water === 'yes') {
      L('water_claim', f.water_claim.get() || 'unknown');
      L('water_hours', f.water_hours.get());
    }

    const heat = f.floor_heating.get();
    L('floor_heating', heat);
    if (heat === 'yes') {
      const known = f.heating_types_known.get() === '1';
      L('heating_types_known', known ? '1' : '0');
      if (known) {
        // Empty marker: "known, none ticked" must reach the server as an empty set
        // (HEATING_TYPE_REQUIRED), not as "key absent = keep the stored set".
        fd.append('laminate[heating_types][]', '');
        f.heating_types.get().forEach((c) => fd.append('laminate[heating_types][]', c));
      }
      L('max_surface_temp_c', f.max_surface_temp_c.get());
      L('thermal_resistance_m2k_w', f.thermal_resistance_m2k_w.get());
    }

    const under = f.integrated_underlay.get();
    L('integrated_underlay', under);
    if (under === 'yes') {
      L('underlay_thickness_mm', f.underlay_thickness_mm.get());
      L('thickness_includes_underlay', f.thickness_includes_underlay.get());
    }

    const warranty = f.warranty.get();
    L('warranty', warranty);
    warrantyIndex = [];
    if (warranty === 'yes') {
      const rows = f.warranties.get();
      if (!rows.length) L('warranties', ''); // empty set → WARRANTY_SCOPE_REQUIRED
      rows.forEach((r, i) => {
        const W = (k, v) => fd.append(`laminate[warranties][${i}][${k}]`, v == null ? '' : String(v));
        W('scope', r.scope);
        W('term_kind', r.term_kind);
        // Lifetime / not stated carry no year count at all (WARRANTY_YEARS_FORBIDDEN).
        if (r.term_kind === 'years') W('years', r.years);
        W('conditions_text', r.conditions_text);
        warrantyIndex[i] = f.warranties.blocks[r.scope];
      });
    }

    if (isEdit && version != null) L('expected_version', version);

    if (isEdit) {
      // Always sent on edit: an absent list means "keep everything".
      fd.append('laminate_documents_kept[]', '');
      docs.filter((d) => d.existing).forEach((d) => fd.append('laminate_documents_kept[]', String(d.id)));
    }
    docIndex = [];
    docs.filter((d) => !d.existing).forEach((d, i) => {
      fd.append(`laminate_documents[${i}][kind]`, d.kind);
      if (d.file) fd.append(`laminate_documents[${i}][file]`, d.file, d.file.name);
      else fd.append(`laminate_documents[${i}][url]`, d.url);
      if (d.kind === 'warranty_conditions') fd.append(`laminate_documents[${i}][warranty_scope]`, d.scope || '');
      docIndex[i] = d;
    });
  }

  // ── Publish requirements ──────────────────────────────────────────────────
  function fieldLabel(path) {
    if (path === 'brand_id' || path === 'images') return t(`field.${path}`);
    const key = path.replace(/^laminate\./, '');
    return T[`field.${key}`] ?? key;
  }

  function liveMissing() {
    const out = [];
    const value = {
      'laminate.model_name': () => f.model_name.value,
      'laminate.manufacturer_sku': () => f.manufacturer_sku.value,
      'laminate.thickness_mm': () => f.thickness_mm.get(),
      'laminate.length_mm': () => f.size.get()[0],
      'laminate.width_mm': () => f.size.get()[1],
      'laminate.pack_area_m2': () => f.pack_area_m2.get(),
    };
    (S.required_for_publish || []).forEach((p) => {
      if (p === 'brand_id') { if (!brandId?.value) out.push(p); }
      else if (p === 'images') { if (imageCount() < 1) out.push(p); }
      else if (value[p] && isBlank(value[p]())) out.push(p);
    });
    return out;
  }

  function renderMissing(list) {
    if (!missingBox) return;
    missingBox.innerHTML = '';
    if (!active || !list?.length) { missingBox.hidden = true; return; }
    missingBox.appendChild(el('p', 'font-semibold', t('ui.missing_for_publish')));
    const ul = el('ul', 'mt-1.5 list-disc pl-5 text-black/70');
    list.forEach((p) => ul.appendChild(el('li', '', fieldLabel(p))));
    missingBox.appendChild(ul);
    missingBox.hidden = false;
  }

  function refreshMissing() {
    if (active && S) renderMissing(liveMissing());
  }

  function updateBrandNote() {
    if (!brandNote) return;
    const name = newBrand?.value.trim() || '';
    const pending = active && !brandId?.value && name !== '';
    brandNote.textContent = pending ? t('ui.brand_pending', { value: name }) : '';
    brandNote.hidden = !pending;
  }

  // ── Generic blocks + unit select ─────────────────────────────────────────
  function hideGeneric() {
    const hide = S.hide_generic || [];
    hideSnap = [];
    form.querySelectorAll('[data-laminate-hide]').forEach((node) => {
      const keys = node.dataset.laminateHide.split(/\s+/).filter(Boolean);
      if (!keys.length || !keys.every((k) => hide.includes(k))) return;
      const controls = [...node.querySelectorAll('input, select, textarea, button')];
      hideSnap.push({ node, display: node.style.display, controls: controls.map((c) => [c, c.disabled]) });
      node.style.display = 'none';
      controls.forEach((c) => { c.disabled = true; });
    });
  }

  function restoreGeneric() {
    hideSnap.forEach(({ node, display, controls }) => {
      node.style.display = display;
      controls.forEach(([c, d]) => { c.disabled = d; });
    });
    hideSnap = [];
  }

  function restrictUnits() {
    if (!unitSel) return;
    const allowed = (S.price_units || []).map((u) => u.value);
    unitSnap = [...unitSel.options].map((o) => ({ o, text: o.text, hidden: o.hidden, disabled: o.disabled }));
    [...unitSel.options].forEach((o) => {
      const u = (S.price_units || []).find((x) => x.value === o.value);
      if (u) o.text = u.label;
      else { o.hidden = true; o.disabled = true; }
    });
    unitForced = null;
    if (!allowed.includes(unitSel.value)) {
      const to = allowed.includes('m2') ? 'm2' : (allowed[0] || '');
      unitForced = { from: unitSel.value, to };
      unitSel.value = to;
    }
  }

  function restoreUnits() {
    (unitSnap || []).forEach(({ o, text, hidden, disabled }) => { o.text = text; o.hidden = hidden; o.disabled = disabled; });
    // Only undo the default laminate picked; a unit the seller chose stays.
    if (unitForced && unitSel?.value === unitForced.to) unitSel.value = unitForced.from;
    unitSnap = null;
    unitForced = null;
  }

  function badges(on) {
    form.querySelectorAll('[data-lam-publish-badge]').forEach((b) => {
      const show = on && publishRequired(b.dataset.lamPublishBadge);
      b.textContent = show ? t('ui.required_for_publish') : '';
      b.hidden = !show;
    });
  }

  // ── Lifecycle ─────────────────────────────────────────────────────────────
  function activate(schema, useInitial) {
    if (active) deactivate();
    S = schema;
    T = schema.t || {};
    active = true;
    f = {};
    hosts = new Map();
    docs = [];
    docRows = {};

    root.innerHTML = '';
    const wrap = el('div', 'flex w-full flex-col gap-4');
    wrap.appendChild(el('p', HINT, t('ui.intro')));
    wrap.appendChild(errSlot(['laminate.expected_version', 'idempotency', '*']));
    buildIdentity(wrap);
    buildMain(wrap);
    buildDocuments(wrap);
    root.appendChild(wrap);
    root.hidden = false;

    fill(useInitial ? initial : null);
    hideGeneric();
    restrictUnits();
    badges(true);
    updateBrandNote();
    refreshMissing();
  }

  function deactivate() {
    if (!active) return;
    clearErrors();
    active = false;
    clearTimeout(lookupTimer);
    lookupKey = '';
    root.innerHTML = '';
    root.hidden = true;
    restoreGeneric();
    restoreUnits();
    badges(false);
    updateBrandNote();
    renderMissing([]);
  }

  /** After a successful save: the new card version, and what still blocks publishing. */
  function handleSuccess(data) {
    if (!active) return [];
    if (data.laminate_version != null) version = data.laminate_version;
    clearErrors();
    const missing = data.missing_fields || [];
    renderMissing(missing);
    return missing.map(fieldLabel);
  }

  function handleFailure(data) {
    if (!active) return null;
    const first = showErrors(data.errors);
    if (Array.isArray(data.missing_fields)) renderMissing(data.missing_fields);
    return first;
  }

  // Enter in a laminate text input must not submit the whole product form.
  root?.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && e.target instanceof HTMLInputElement) e.preventDefault();
  });

  ['input', 'change'].forEach((ev) => form.addEventListener(ev, () => { if (active) setTimeout(refreshMissing, 0); }));
  const slots = document.getElementById('imageSlots');
  if (slots) new MutationObserver(() => refreshMissing()).observe(slots, { childList: true });

  unitSel?.addEventListener('change', () => { unitForced = null; });
  brandInput?.addEventListener('input', () => { if (brandNote) brandNote.hidden = true; });
  // Runs after the combobox's own blur handler has settled brand_id / new_brand.
  brandInput?.addEventListener('blur', () => setTimeout(() => { updateBrandNote(); scheduleLookup(); refreshMissing(); }, 0));

  return {
    get active() { return active; },
    activate,
    deactivate,
    serialize,
    handleSuccess,
    handleFailure,
    missingTitle: () => t('ui.missing_for_publish'),
    onBrandChange() { if (!active) return; updateBrandNote(); scheduleLookup(); refreshMissing(); },
  };
}
