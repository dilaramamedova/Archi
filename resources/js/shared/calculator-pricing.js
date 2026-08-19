/**
 * The renovation price model — the ONLY place the numbers live.
 *
 * Two screens now quote a price from it: the full /calculator page
 * (resources/js/pages/calculator.js) and the homepage mini panel
 * (resources/js/shared/mini-calculator.js). Both used to be able to own a copy of the
 * table; the first edit to one of them would have silently made the homepage quote a
 * different number than the page it links to, which is the one thing a price teaser
 * must never do. Anything price-shaped therefore belongs here, not in a page module.
 *
 * The chip markup of the full page still carries its multipliers in `data-m`, because
 * that page's chips are authored in Blade; the mini panel builds its chips FROM these
 * maps precisely so it cannot drift.
 */

/** mirrors the min="1" of the area inputs — an empty field would otherwise price at 0 */
export const MIN_AREA = 1;

/** ₼ per m² of the three material levels, in the order the tier cards render them */
export const TIERS = [
  { key: 'economy', rate: 600 },
  { key: 'standard', rate: 900 },
  { key: 'premium', rate: 1500 },
];

/** same rates keyed by level, for callers that quote a single tier */
export const RATES = Object.fromEntries(TIERS.map((t) => [t.key, t.rate]));

export const OBJECT_MULTIPLIERS = { apartment: 1, house: 1.1, office: 0.95, commercial: 1.05 };
export const TYPE_MULTIPLIERS = { shell: 0.55, cosmetic: 0.7, major: 1, turnkey: 1.25 };
/** '4' is the "4+" chip — the key is the chip's data-v, not a room count */
export const ROOMS_MULTIPLIERS = { studio: 0.95, 1: 1, 2: 1.03, 3: 1.06, 4: 1.1 };

/** the selection both screens start from, so a visitor who only changes the area
 *  sees the same number here and on /calculator */
export const DEFAULTS = { obj: 'apartment', area: 80, type: 'major', rooms: '2', level: 'standard' };

/** the largest object worth quoting — see the note in clampArea() */
export const MAX_AREA = 100000;

/**
 * Clamp a raw input value to a usable area. Neither screen wraps its number field in a
 * <form>, so the browser never enforces min/max for us — the attributes are there for
 * the spinner and the mobile keyboard, nothing more.
 *
 * The upper bound matters as much as the lower one: 999999 m² priced at 1 931 248 000 ₼,
 * which a visitor reads as a broken calculator rather than as a very large job.
 */
export function clampArea(value) {
  const n = parseFloat(value);

  if (! Number.isFinite(n)) return MIN_AREA;

  return Math.min(MAX_AREA, Math.max(MIN_AREA, n));
}

/**
 * Estimated price, rounded to the nearest 1000 ₼ — the estimate is an order of
 * magnitude, and a quote like "72 384 ₼" would suggest a precision it does not have.
 *
 * The multipliers default to 1 so a caller may pass only the factors it exposes.
 *
 * The floor is not cosmetic. Rounding to the nearest thousand sends anything under
 * ~900 ₼ to zero, so a 1 m² shell renovation at the economy tier quoted "0 ₼" — a
 * marketplace advertising free work. Both screens went through this function, so both
 * showed it. Below one thousand the honest answer is "about a thousand", not nothing.
 */
export function priceFor({ area, rate, obj = 1, type = 1, rooms = 1 }) {
  const raw = area * rate * obj * type * rooms;

  if (raw <= 0) {
    return 0;
  }

  return Math.max(1000, Math.round(raw / 1000) * 1000);
}
