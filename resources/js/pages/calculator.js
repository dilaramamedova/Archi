// Page module for "calculator" — the quick renovation calculator of the TZ.
//
// The arithmetic is one line: area × the price per m² of the selected package. What
// this module is really responsible for is that the visitor never sees a wrong or
// meaningless number: no "0 AZN" while a field is empty, no NaN, no price for a
// combination the admin has not approved, and no separate "Hesabla" button (TZ §25).
//
// Every price and limit comes from #qcCalc[data-quick], which the controller filled
// from the database. Nothing price-shaped may be written in this file — that is the
// hard requirement of TZ §30, and it is what lets Archi change a rate in the admin
// panel without a release.

import popup from '../shared/popup.js';
// The validation, the rounding and the hand-off shape are shared with the homepage
// mini panel so the two screens can never quote different numbers.
import {
  HANDOFF_KEY,
  formatMoney as format,
  handoffQuery,
  quote,
  readJson,
  track,
} from '../shared/calculator-pricing.js';

export default function init() {
  const root = document.getElementById('qcCalc');
  if (! root) return;

  const config = readJson(root.dataset.quick, null);
  const labels = readJson(root.dataset.labels, {});
  if (! config) return;

  const areaEl = document.getElementById('qcArea');
  const areaBox = document.getElementById('qcAreaBox');
  const errorEl = document.getElementById('qcAreaErr');
  const labelEl = document.getElementById('qcRlbl');
  const priceEl = document.getElementById('qcPrice');
  const currencyEl = document.getElementById('qcCurrency');
  const detailsEl = document.getElementById('qcDetails');
  const ctaEl = document.getElementById('qcNext');

  const state = {
    area: config.defaults.area,
    renovationType: config.defaults.renovationType,
    segment: config.defaults.segment,
  };

  /**
   * Continue whatever the homepage mini panel was showing. A visitor who set
   * 150 m² / Premium there must not arrive here at the defaults and enter it again.
   */
  function hydrate() {
    const saved = readJson(localStorage.getItem(HANDOFF_KEY), null);
    if (! saved) return;

    if (Number.isFinite(parseFloat(saved.area))) {
      state.area = parseFloat(saved.area);
      areaEl.value = String(state.area);
    }

    if (config.prices[saved.renovationType]) state.renovationType = saved.renovationType;
    if (config.prices[state.renovationType]?.[saved.segment] !== undefined) state.segment = saved.segment;
  }

  const priceFor = (type, segment) => config.prices?.[type]?.[segment] ?? null;

  const evaluate = () => quote({
    area: areaEl.value,
    renovationType: state.renovationType,
    segment: state.segment,
    config,
  });

  function renderSegments() {
    root.querySelectorAll('[data-key] [data-v]').forEach((button) => {
      const group = button.closest('[data-key]').dataset.key;
      const selected = group === 'type'
        ? button.dataset.v === state.renovationType
        : button.dataset.v === state.segment;

      button.dataset.on = String(selected);
      button.setAttribute('aria-checked', String(selected));

      // A package with no approved price cannot be chosen — it would only lead to
      // "qiymət dəqiqləşdirilir" (TZ §28 "Disabled").
      if (group === 'segment') {
        button.disabled = priceFor(state.renovationType, button.dataset.v) === null;
      } else {
        button.disabled = ! config.prices?.[button.dataset.v];
      }
    });
  }

  function renderResult(result) {
    const failed = Boolean(result.error);

    errorEl.textContent = failed ? (labels.errors?.[result.error] || '') : '';
    errorEl.classList.toggle('hidden', ! failed);
    areaBox.classList.toggle('border-red-500', failed && result.error !== 'PRICE_NOT_AVAILABLE');

    labelEl.textContent = (labels.resultLabel || '').replace(
      '{area}',
      format(result.area ?? state.area ?? 0),
    );

    if (failed) {
      // An em dash, never 0 AZN or NaN — the estimate has to look unanswered, not free.
      priceEl.textContent = '—';
      currencyEl.textContent = '';
      detailsEl.classList.add('hidden');
      detailsEl.classList.remove('grid');
      if (ctaEl) ctaEl.href = config.detailedCalculatorUrl;

      return;
    }

    priceEl.textContent = format(result.total);
    currencyEl.textContent = config.showFromPrefix ? labels.fromSuffix : labels.currency;

    detailsEl.classList.remove('hidden');
    detailsEl.classList.add('grid');
    detailsEl.innerHTML = [
      [labels.details?.area, `${format(result.area)} m²`],
      [labels.details?.type, labels.types?.[state.renovationType]],
      [labels.details?.segment, labels.segments?.[state.segment]],
      [labels.details?.pricePerM2, `${format(result.pricePerM2)} ${labels.currency}`],
      [labels.details?.total, `${format(result.total)} ${labels.currency}`],
    ]
      .map(([term, value]) => `<dt>${term ?? ''}</dt><dd class="text-black/70">${value ?? ''}</dd>`)
      .join('');

    // Hand the three answers to the detailed calculator (TZ §47).
    if (ctaEl) {
      ctaEl.href = `${config.detailedCalculatorUrl}?${handoffQuery({
        area: result.area,
        renovationType: state.renovationType,
        segment: state.segment,
      })}`;
    }
  }

  let last = {};

  /** The five fields TZ §45 asks to be sent with a calculator event. */
  const eventPayload = () => ({
    area: last.area ?? null,
    renovationType: state.renovationType,
    segment: state.segment,
    pricePerM2: last.pricePerM2 ?? null,
    estimatedTotal: last.total ?? null,
  });

  function render() {
    renderSegments();
    const previous = last;
    last = evaluate();
    renderResult(last);

    // "result_viewed" fires when a real number appears or changes — not on every
    // keystroke that leaves it invalid.
    if (! last.error && last.total !== previous.total) {
      track('quick_calculator_result_viewed', eventPayload());
    }

    try {
      localStorage.setItem(HANDOFF_KEY, JSON.stringify({
        area: state.area,
        renovationType: state.renovationType,
        segment: state.segment,
      }));
    } catch {
      // Storage blocked (private window) — the hand-over is a convenience, not a
      // requirement; the CTA still carries the parameters in its query string.
    }
  }

  root.querySelectorAll('[data-key]').forEach((group) => {
    group.addEventListener('click', (event) => {
      const button = event.target.closest('[data-v]');
      if (! button || button.disabled) return;

      if (group.dataset.key === 'type') {
        state.renovationType = button.dataset.v;

        // Changing one parameter must not reset the others (TZ §26); the segment is
        // only moved if this renovation type has no price for it at all.
        if (priceFor(state.renovationType, state.segment) === null) {
          const available = Object.keys(config.prices[state.renovationType] || {})
            .find((key) => priceFor(state.renovationType, key) !== null);
          if (available) state.segment = available;
        }
      } else {
        state.segment = button.dataset.v;
      }

      render();
      track(
        group.dataset.key === 'type'
          ? 'quick_calculator_renovation_type_changed'
          : 'quick_calculator_segment_changed',
        eventPayload(),
      );
    });
  });

  areaEl.addEventListener('input', () => {
    const parsed = parseFloat(areaEl.value);
    state.area = Number.isFinite(parsed) ? parsed : state.area;
    render();
  });

  // One event per finished edit rather than one per keystroke.
  areaEl.addEventListener('change', () => track('quick_calculator_area_changed', eventPayload()));

  ctaEl?.addEventListener('click', () => track('quick_calculator_cta_clicked', eventPayload()));

  document.getElementById('qcSave')?.addEventListener('click', () => {
    try {
      localStorage.setItem('archi-quickcalc-saved', JSON.stringify({ ...state, ...last }));
    } catch {
      // nothing to persist; the message below is still honest about the calculation
    }
    popup.success(labels.saved);
  });

  document.getElementById('qcWa')?.addEventListener('click', () => {
    if (last.error) return;

    const message = (labels.whatsapp || '')
      .replace('{area}', format(last.area))
      .replace('{level}', labels.segments?.[state.segment] || '')
      .replace('{price}', format(last.total));

    window.open(`https://wa.me/?text=${encodeURIComponent(message)}`, '_blank');
  });

  document.getElementById('qcPdf')?.addEventListener('click', () => window.print());

  hydrate();
  render();
  track('quick_calculator_open', eventPayload());
}
