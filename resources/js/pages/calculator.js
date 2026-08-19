// Page module for "calculator": chip groups (data-on state), the area input and the
// three price tiers, which are rendered here because their count/order is data driven.
// All texts come from Blade through #qcCalc[data-labels].

import popup from '../shared/popup.js';
// The rates and the rounding rule are shared with the homepage mini panel — see the
// header of shared/calculator-pricing.js for why they may not live in a page module.
import { TIERS, clampArea, priceFor as computePrice } from '../shared/calculator-pricing.js';

// Tailwind classes of a tier card (the card is built in JS, so they live here).
// Below 640px the three stacked cards turn into the frame's one-line rows — name on the
// left, price on the right — through the grid areas in resources/css/pages/calculator.css.
// Areas rather than a reorder, so the DOM order and the desktop column are untouched.
const TIER_CLASS =
  'qc-tier flex min-w-0 flex-1 cursor-pointer flex-col gap-1 rounded-ds border border-black/10 bg-[#f9f9fb] p-4 transition-all duration-150 hover:border-black/30 data-[on=true]:border-2 data-[on=true]:border-yellow-line data-[on=true]:bg-sel-bg data-[on=true]:p-[15px] max-[640px]:grid max-[640px]:items-center max-[640px]:gap-x-3 max-[640px]:gap-y-0.5 max-[640px]:px-4 max-[640px]:py-3.5 max-[640px]:data-[on=true]:px-[15px] max-[640px]:data-[on=true]:py-[13px]';

export default function init() {
  const root = document.getElementById('qcCalc');
  if (!root) return;

  const labels = JSON.parse(root.dataset.labels || '{}');
  const areaEl = document.getElementById('qcArea');
  const resultLabelEl = document.getElementById('qcRlbl');
  const tiersEl = document.getElementById('qcTiers');

  const state = { obj: 1, type: 1, rooms: 1.03, level: 'standard', area: 80 };

  /**
   * Continue whatever the homepage mini panel was showing.
   *
   * That panel's whole job is to funnel here, and it used to hand over nothing: a
   * visitor who set 150 m² / Premium arrived back at 80 m² / Standart and had to enter
   * it all again. The key and shape are the ones this page already wrote for the
   * detailed calculator, so nothing new is invented — it is simply read now as well.
   */
  function hydrateFromMiniPanel() {
    let saved;

    try {
      saved = JSON.parse(localStorage.getItem('archi-quickcalc') || 'null');
    } catch {
      return; // unparseable or storage blocked — the defaults stand
    }

    if (! saved) return;

    if (Number.isFinite(parseFloat(saved.area))) {
      state.area = clampArea(saved.area);
      areaEl.value = String(state.area);
    }

    // The chips are the source of truth for the multipliers (they carry data-m), so a
    // handed-over key is applied by activating its chip rather than by trusting a
    // number from storage.
    for (const [group, value] of [['type', saved.type], ['level', saved.level]]) {
      if (! value) continue;

      const chip = document.querySelector(`[data-key="${group}"] [data-v="${value}"]`);
      if (! chip) continue;

      chip.closest('[data-key]').querySelectorAll('[data-v]').forEach((b) => (b.dataset.on = 'false'));
      chip.dataset.on = 'true';

      if (group === 'level') state.level = value;
      else state[group] = parseFloat(chip.dataset.m) || 1;
    }
  }

  const fmt = (n) => Math.round(n).toLocaleString('ru-RU');

  // the chips carry their multipliers as numbers in data-m, so the state already holds
  // factors rather than keys — only the arithmetic is shared
  const priceFor = (rate) =>
    computePrice({ area: state.area, rate, obj: state.obj, type: state.type, rooms: state.rooms });

  function render() {
    resultLabelEl.textContent = (labels.resultLabel || '').replace('{area}', String(state.area || 0));

    tiersEl.innerHTML = TIERS.map((t) => {
      const on = t.key === state.level;
      return `<div class="${TIER_CLASS}" data-v="${t.key}" data-on="${on}">
        <div class="qc-tier-n text-[13px] font-semibold text-ink">${labels.tiers[t.key]}</div>
        <div class="qc-tier-p flex items-baseline gap-[3px] max-[640px]:justify-end"><b class="text-[19px] font-bold text-ink">${fmt(priceFor(t.rate))}</b><span class="text-[11px] font-medium text-black/50">${labels.currency}</span></div>
        ${on ? `<div class="qc-tier-r text-[11px] font-medium text-[#8c7f0d]">${labels.recommended}</div>` : ''}
      </div>`;
    }).join('');

    // keep the choices for the detailed calculator
    try {
      localStorage.setItem('archi-quickcalc', JSON.stringify({ area: state.area, level: state.level }));
    } catch (e) {}
  }

  // chip groups
  document.querySelectorAll('[data-key]').forEach((group) => {
    group.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-v]');
      if (!btn) return;
      group.querySelectorAll('[data-v]').forEach((b) => (b.dataset.on = 'false'));
      btn.dataset.on = 'true';
      const key = group.dataset.key;
      if (key === 'level') state.level = btn.dataset.v;
      else state[key] = parseFloat(btn.dataset.m) || 1;
      render();
    });
  });

  // The min="1" of the input is not enforced by itself (there is no form), so the
  // value is clamped here: an empty or below-minimum field would otherwise price at 0
  // and hand that 0 over to the detailed calculator.
  areaEl.addEventListener('input', () => {
    state.area = clampArea(areaEl.value);
    render();
  });

  // write the clamped value back once the field is left, so input and prices agree
  areaEl.addEventListener('change', () => {
    if (parseFloat(areaEl.value) !== state.area) areaEl.value = String(state.area);
  });

  // a tier card also selects the material level
  tiersEl.addEventListener('click', (e) => {
    const card = e.target.closest('[data-v]');
    if (!card) return;
    state.level = card.dataset.v;
    document
      .querySelectorAll('[data-key="level"] [data-v]')
      .forEach((b) => (b.dataset.on = String(b.dataset.v === state.level)));
    render();
  });

  document.getElementById('qcSave').addEventListener('click', () => {
    try {
      localStorage.setItem('archi-quickcalc-saved', JSON.stringify({ ...state, ts: Date.now() }));
    } catch (e) {}
    popup.success(labels.saved);
  });

  document.getElementById('qcWa').addEventListener('click', () => {
    const tier = TIERS.find((t) => t.key === state.level);
    const msg = (labels.whatsapp || '')
      .replace('{area}', String(state.area))
      .replace('{level}', labels.tiers[state.level])
      .replace('{price}', fmt(priceFor(tier.rate)));
    window.open('https://wa.me/?text=' + encodeURIComponent(msg), '_blank');
  });

  document.getElementById('qcPdf').addEventListener('click', () => window.print());

  // Before the first render, so the page paints the handed-over selection rather than
  // flashing the defaults and correcting itself.
  hydrateFromMiniPanel();
  render();
}
