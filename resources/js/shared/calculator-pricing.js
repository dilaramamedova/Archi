/**
 * Shared helpers for the two screens that quote a quick renovation price: the
 * /calculator page and the homepage mini panel.
 *
 * There are deliberately NO rates in this file any more. The six prices, the area
 * limits and the defaults live in the database and reach the browser through the
 * page markup (CalculatorController), because an administrator has to be able to
 * change them without a release — TZ §30 states it as a critical requirement and
 * AC-14 tests it. What is shared here is only the arithmetic and the formatting, so
 * the mini panel and the full page can never round or validate a number differently.
 */

/** The localStorage key both screens use to hand a selection to the next one. */
export const HANDOFF_KEY = 'archi-quickcalc';

/** Technical value → URL slug, for the hand-over to the detailed calculator (TZ §47). */
export const SLUGS = {
  WHITE_BOX: 'white-box',
  FULL_RENOVATION: 'full-renovation',
  ECONOMY: 'economy',
  STANDARD: 'standard',
  PREMIUM: 'premium',
};

/** Thousands separators and no qəpik — both are explicit requirements (TZ §19). */
export function formatMoney(value) {
  return Math.round(value).toLocaleString('ru-RU');
}

export function readJson(raw, fallback = null) {
  try {
    return JSON.parse(raw || '');
  } catch {
    return fallback;
  }
}

/**
 * Validate an area and price it, in exactly the order the backend does.
 *
 * Returns `{ error }` rather than a number for every case where the honest answer is
 * not a price: an empty field, a non-positive area, an area outside the configured
 * bounds, or a package the admin has not priced yet. The caller renders the message —
 * what matters is that no branch here can produce 0 or NaN (TZ §29, §38).
 */
export function quote({ area, renovationType, segment, config }) {
  const parsed = parseFloat(area);

  if (area === '' || area === null || area === undefined || Number.isNaN(parsed)) {
    return { error: 'AREA_REQUIRED' };
  }

  if (parsed <= 0) return { error: 'AREA_MUST_BE_POSITIVE' };
  if (parsed < config.limits.minArea) return { error: 'AREA_BELOW_MINIMUM', area: parsed };
  if (parsed > config.limits.maxArea) return { error: 'AREA_ABOVE_MAXIMUM', area: parsed };

  const pricePerM2 = config.prices?.[renovationType]?.[segment] ?? null;

  if (pricePerM2 === null) return { error: 'PRICE_NOT_AVAILABLE', area: parsed };

  return { area: parsed, pricePerM2, total: parsed * pricePerM2 };
}

/**
 * Analytics events of TZ §45.
 *
 * Pushed to `window.dataLayer` when a tag manager is present and mirrored as a DOM
 * CustomEvent, so the calculator does not depend on any particular analytics vendor
 * being wired up — and works with none at all. No personal data is ever attached:
 * the quick calculator asks for none (§46).
 */
export function track(event, payload = {}) {
  const detail = { event, ...payload };

  try {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push(detail);
    window.dispatchEvent(new CustomEvent('archi:calculator', { detail }));
  } catch {
    // Analytics must never be able to break a price. Swallowing is the point.
  }
}

/** Query string that carries the three answers into the detailed calculator. */
export function handoffQuery({ area, renovationType, segment }) {
  return new URLSearchParams({
    area: String(area),
    type: SLUGS[renovationType] || renovationType,
    segment: SLUGS[segment] || segment,
  }).toString();
}
