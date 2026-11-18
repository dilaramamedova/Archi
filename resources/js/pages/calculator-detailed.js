// Page module for "calculator-detailed" — the six-step estimate wizard.
//
// This file collects answers and renders what comes back. It does not price anything:
// every quantity, coefficient, package count and total is computed by the backend
// estimate engine (POST /api/calculator/estimate), which is the only place the work
// rates, material fallbacks and consumption norms exist. That split is the master
// specification's central rule — "ничего не хардкодить" — and it is what lets Archi
// add a work or change a rate in the admin panel without touching this module.
//
// The legacy `dc-*` class names and the .on/.done/.active/.exp/.off state classes are
// kept, because resources/css/pages/calculator-detailed.css is written against them.

import popup from '../shared/popup.js';
import { HANDOFF_KEY, formatMoney, readJson } from '../shared/calculator-pricing.js';

const CHK = '<svg><use href="#chk"/></svg>';

/** Utility class strings reused across templates; Tailwind scans ../js for them. */
const CLS = {
  editHint: 'text-[13px] font-medium text-black/50',
  colStack: 'flex flex-col gap-2.5',
  sumRow: 'flex items-baseline justify-between border-t border-black/10 pt-4',
  sumLabel: 'text-[15px] font-semibold',
  sumValue: 'text-[22px] font-bold whitespace-nowrap',
  sumUnit: 'text-sm text-black/50',
  cartRow: 'flex items-center justify-between gap-3 rounded-ds border border-black/10 px-5 py-4 max-[640px]:px-3.5',
  cartLabel: 'text-sm font-semibold',
  sideMeta: 'text-[13px] text-black/50',
  sideBanner: 'flex-col items-stretch gap-2.5',
  sideBannerRow: 'flex items-center justify-between',
  sideBannerHint: 'text-[13px] text-black/60',
  sideHint: 'mt-0.5 mb-1.5 text-[12px] leading-[1.4] text-black/45',
  addAll: 'p-4',
  note: 'text-[12px] leading-[1.5] text-black/45',
  warn: 'rounded-ds bg-amber-50 px-4 py-3 text-[13px] text-amber-900',
  trace: 'text-[12px] text-black/45',
  select: 'w-full rounded-ds border border-black/12 bg-white px-3 py-2 text-sm text-ink',
};

const esc = (s) =>
  String(s == null ? '' : s).replace(
    /[&<>"']/g,
    (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c],
  );

/** Laravel-style :placeholder substitution on the raw templates from Blade. */
const tr = (tpl, vars) =>
  Object.keys(vars || {}).reduce((s, k) => s.split(`:${k}`).join(vars[k]), tpl || '');

/**
 * Numbers with a thousands separator and qəpik only where there are any (TZ §19).
 *
 * The line amount and the trace under it have to agree to the qəpik: a row reading
 * "474 ₼" above "59.29 m² × 8 ₼/m² = 474.34 ₼" looks like the calculator made a
 * mistake, even though neither figure is wrong.
 */
const num = (n) => {
  const value = Number(n) || 0;
  const whole = Math.abs(value - Math.round(value)) < 0.005;
  const [int, frac] = (whole ? String(Math.round(value)) : value.toFixed(2)).split('.');

  // Group only the integer part — "1 234.56", never "1 234.5 6".
  return int.replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + (frac ? `.${frac}` : '');
};

/** Quantities: same rule, no currency. */
const qty = (n) => num(n);

/** Two decimals — a sum of room areas must not read "67.19999999". */
const round2 = (n) => Math.round((Number(n) || 0) * 100) / 100;

/**
 * Line types that carry the stage structure's own rows (margin, contingency, VAT)
 * rather than a work or a material. From 2026-11-18 those figures have their own
 * place at the bottom of the estimate, so listing them again among the category
 * groups would count them twice in the reader's head.
 */
const ITEM_LINE_TYPES = ['WORK', 'MATERIAL', 'LOGISTICS', 'SERVICE'];
const isTotalsLine = (line) => ! ITEM_LINE_TYPES.includes(line.lineType) || line.category === 'RESERVE';

/** "Materialı özüm alacağam" — a shopping-list line, never part of the total. */
const isOwnMaterial = (line) => line.lineType === 'MATERIAL' && line.meta?.mode === 'EXCLUDED';

export default function init() {
  const page = document.getElementById('dcPage');
  if (! page) return;

  const T = readJson(page.dataset.i18n, null);
  const CONFIG = readJson(page.dataset.config, null);
  if (! T || ! CONFIG) return;

  const mainEl = document.getElementById('dcMain');
  const sideEl = document.getElementById('dcSide');
  const stepEl = document.getElementById('dcStepper');
  if (! mainEl || ! sideEl || ! stepEl) return;

  const prefill = readJson(page.dataset.prefill, {}) || {};
  const catalogUrl = page.dataset.urlCatalog || '#';

  /**
   * The visitor's cart, from BOTH places it lives. The storefront cart is
   * localStorage "archi-cart" — the only cart a guest has — while a logged-in
   * account also carries a server-side one (handed over by Blade). Merged by
   * product id, server first. Display uses these rows; the estimate itself sends
   * only ids and quantities, and the server re-prices everything from the catalog.
   */
  const mergeCart = (server) => {
    const seen = new Set(server.map((p) => p.id));
    const local = (readJson(localStorage.getItem('archi-cart'), []) || [])
      // A row without a usable product id is from an older storefront build and
      // cannot be priced — the server would have nothing to look up.
      .filter((c) => c && Number.isFinite(+c.id) && +c.id > 0 && ! seen.has(+c.id))
      .map((c) => ({
        id: Math.trunc(+c.id),
        name: c.name || '',
        price: +c.price || 0,
        unit: null,
        // The storefront saves the thumbnail with the cart row, so a guest's basket
        // shows the same picture here as it does on /cart.
        image: c.img || null,
        packageSize: null,
        quantity: Math.max(1, Math.ceil(+c.qty || 1)),
      }));

    // /api/cart/items prices a category-card product by the pack it is sold in; the
    // estimate works in the catalogue unit (m²) and the pack size.
    const base = server.map((p) => (p.basePrice !== undefined ? { ...p, price: p.basePrice, unit: p.baseUnit } : p));

    return [...base, ...local];
  };

  let cartProducts = mergeCart(readJson(page.dataset.cart, []) || []);
  // Line and group amounts keep their qəpik so they match the trace beneath them.
  // Only the headline totals — the grand total and the side panel — are rounded to
  // whole manats, because the estimate as a whole is an approximation.
  const money = (n) => tr(T.money, { amount: num(n) });

  const STEPS = [T.steps.object, T.steps.rooms, T.steps.works, T.steps.materials, T.steps.cart, T.steps.summary];
  const STEP_CAPTIONS = [T.object.k, T.rooms.k, T.works.k, T.materials.k, T.cart.k, T.summary.k];

  const enums = CONFIG.enums;
  const settings = CONFIG.settings || {};
  const labelOf = (list, value) => (list.find((x) => x.value === value) || {}).label || value;
  const roomTypeOf = (value) => enums.roomTypes.find((r) => r.value === value) || {};

  /**
   * The ceiling every new room starts from (TZ 17.11.2026, «Tavan hündürlüyü»). The
   * older config called the same setting defaultRoomHeightMm; both are read so this
   * page works on either side of the backend change.
   */
  const DEFAULT_CEILING_MM = settings.defaultCeilingHeightMm ?? settings.defaultRoomHeightMm ?? 2800;

  /** Renovation level — the quick calculator's White Box / Tam təmir, one screen later. */
  const levels = enums.levels?.length
    ? enums.levels
    : [
        { value: 'FULL', label: T.levels?.full || 'FULL' },
        { value: 'WHITE_BOX', label: T.levels?.white_box || 'WHITE_BOX' },
      ];

  /** Room types that carry a kitchen run — its length comes off the skirting. */
  const KITCHEN_TYPES = ['KITCHEN', 'KITCHEN_LIVING'];

  /**
   * Which counters a room type has. `pointKeys` / `fixtureKeys` arrive with the
   * admin-editable room types; the older config only had the default maps, whose keys
   * were the same list.
   */
  const pointKeysOf = (type) => type.pointKeys || Object.keys(type.defaultPoints || {});
  const fixtureKeysOf = (type) => type.fixtureKeys || Object.keys(type.defaultFixtures || {});

  /** Lighting is billed per kind of fitting but read as one number: "6 işıq". */
  const LIGHT_KEYS = ['lights_spot', 'lights_pendant', 'lights_chandelier', 'lights'];

  /**
   * The finishes are admin rows (calculator_finishes), not a fixed list: whatever
   * the panel holds for a surface is what the chips offer.
   */
  const finishes = CONFIG.finishes || [];
  const finishesFor = (surface) => finishes
    .filter((f) => f.surface === surface)
    .map((f) => ({ value: f.id, label: f.name }));

  /**
   * The finish a new room starts with: a tile-type finish on a wet room's floor and
   * walls, otherwise the row the admin marked as the default for that surface.
   */
  const defaultFinish = (surface, wet) => {
    const rows = finishes.filter((f) => f.surface === surface);
    const pick = (wet && surface !== 'CEILING' && rows.find((f) => f.family === 'TILE'))
      || rows.find((f) => f.isDefault)
      || rows[0];

    return pick ? pick.id : null;
  };

  /**
   * Finishes of a surface that belong to the finishing stage (`labourStage`), i.e.
   * the ones a White Box estimate leaves out. Empty on a backend that does not know
   * stages yet, which simply means nothing gets marked.
   */
  const finishStageFinishes = (surface) => finishes.filter((f) => f.surface === surface && f.labourStage === 'FINISH');

  const conditionOf = (value) => enums.conditions.find((c) => c.value === value) || enums.conditions[0] || {};

  const state = {
    step: 0,
    object: {
      objectType: enums.objectTypes[0]?.value || 'APARTMENT',
      area: CONFIG.quick.defaults.area,
      cityKey: (CONFIG.cities.find((c) => c.isDefault) || CONFIG.cities[0] || {}).key,
      floor: 1,
      condition: 'SHELL',
      hasFreightElevator: false,
      demolitionRequired: false,
      rewireElectrics: true,
      designProject: false,
      segment: CONFIG.quick.defaults.segment,
      // FULL | WHITE_BOX. White Box makes the server switch every FINISH-stage work
      // off by default; the visitor can still tick any of them back on step 3.
      level: 'FULL',
      // Rooms whose height the visitor never touched follow this value.
      ceilingHeightMm: DEFAULT_CEILING_MM,
    },
    rooms: [],
    works: {},
    materials: {},
    // Electrics, plumbing, air conditioning, fittings, doors, demolition and
    // delivery are all derived from the rooms; only the cleaning switch is asked.
    modules: {
      services: { finalCleaning: true },
    },
    basket: { useCart: cartProducts.length > 0, excluded: [] },
    estimate: null,
    loading: false,
    error: null,
    editRoom: 0,
    summaryView: 'category',
    roomsTouched: false,
    demolitionTouched: false,
    rewireTouched: false,
  };

  /**
   * Rooms whose «Ətraflı» point editor is open although nothing in it was changed
   * yet. Screen state only: a reload falls back to «Sadə» unless the visitor edited
   * a counter, which is recorded on the room itself (`pointsTouched`).
   */
  const pointsOpen = new Set();

  /**
   * The condition seeds the two object switches until the visitor sets them
   * (master spec §7) — an old flat is stripped out and rewired, a prepared base
   * is neither.
   */
  function applyConditionDefaults() {
    const condition = conditionOf(state.object.condition);

    if (! state.demolitionTouched) state.object.demolitionRequired = !! condition.defaultDemolition;
    if (! state.rewireTouched) state.object.rewireElectrics = !! condition.defaultRewiring;
  }

  /**
   * Continue the quick calculator: the URL query wins (the CTA carries it), then
   * whatever the mini panel left in storage. TZ §47 — the visitor never retypes.
   */
  function hydrateFromQuick() {
    const saved = readJson(localStorage.getItem(HANDOFF_KEY), {}) || {};
    const area = prefill.area ?? saved.area;
    const segment = prefill.segment ?? saved.segment;
    const type = prefill.renovationType ?? saved.renovationType;

    if (Number.isFinite(parseFloat(area))) state.object.area = parseFloat(area);
    if (enums.segments.some((s) => s.value === segment)) state.object.segment = segment;

    // White Box is a shell-and-core finish: the object arrives bare, and the visitor
    // said so one screen ago. Full renovation is normally an occupied flat.
    if (type === 'WHITE_BOX') state.object.condition = 'SHELL';
    else if (type === 'FULL_RENOVATION') state.object.condition = 'OLD_RENOVATION';

    applyLevelFromQuick(type);
    applyConditionDefaults();
  }

  /** The quick calculator's renovation type is this page's level, one to one. */
  function applyLevelFromQuick(type) {
    if (type === 'WHITE_BOX') state.object.level = 'WHITE_BOX';
    else if (type === 'FULL_RENOVATION') state.object.level = 'FULL';
  }

  /**
   * A starting set of rooms so step 2 is never an empty screen. Which rooms belong
   * to which kind of object is the server's answer (ObjectType::defaultRoomTypes) —
   * a commercial fit-out must not open with a bedroom.
   */
  function seedRooms() {
    const area = state.object.area || 80;
    const type = enums.objectTypes.find((t) => t.value === state.object.objectType);
    const roomTypes = type?.defaultRoomTypes?.length ? type.defaultRoomTypes : ['OTHER'];

    // Weighted so the living space dominates and the wet rooms stay small; the
    // visitor corrects every dimension on the next screen anyway.
    const weights = roomTypes.map((roomType) =>
      roomType === 'BATHROOM' || roomType === 'WC' ? 0.5 : roomType === 'CORRIDOR' ? 0.8 : 1.5,
    );
    const totalWeight = weights.reduce((sum, w) => sum + w, 0) || 1;

    state.rooms = roomTypes.map((roomType, index) =>
      newRoom(roomType, (area * weights[index]) / totalWeight, index),
    );
  }

  /**
   * Every dimension on a room is MILLIMETRES — `lengthMm`, `widthMm`, `heightMm` and
   * an opening's `widthMm` / `heightMm` — because that is how this market specifies a
   * room, and because the key says the unit so a metre-era saved state is detectable
   * rather than silently a thousand times too small. Areas stay m²: `object.area`,
   * `warmFloor.area`. The server divides by 1000 exactly once
   * (app/Services/Calculator/Units.php).
   */
  const MM_STEP = 10;

  /** Nearest 10 mm — the step of the fields, so a seeded value lands on it too. */
  const mm = (metres) => Math.round((metres * 1000) / MM_STEP) * MM_STEP;

  /** The same snap for a value that is already in millimetres. */
  const snapMm = (value) => Math.round((Number(value) || 0) / MM_STEP) * MM_STEP;

  /**
   * A fresh opening of the given type. DOOR / WINDOW / PASSAGE (a doorless
   * opening: it comes off the wall area and the skirting, but no door is fitted).
   * A passage takes the door's default size — it is usually the hole a door would
   * have hung in.
   */
  function newOpening(type, qtyValue = 1) {
    const size = type === 'WINDOW' ? settings.defaultWindow : settings.defaultDoor;
    const opening = { type, widthMm: size?.widthMm ?? 0, heightMm: size?.heightMm ?? 0, qty: qtyValue };

    // An entrance door is not an interior door: no installation and no opening
    // preparation are billed for it. Every door starts as an interior one.
    if (type === 'DOOR') Object.assign(opening, { installNewDoor: true, isEntrance: false });

    return opening;
  }

  /**
   * Older saved rooms and rooms built by older code have no zone lists, and a room
   * must always offer at least one door row and one window row to type into (qty 0
   * means "none" and is dropped from the payload).
   */
  function normaliseRoom(room) {
    room.openings = Array.isArray(room.openings) ? room.openings : [];
    if (! room.openings.some((o) => o.type === 'DOOR')) room.openings.unshift(newOpening('DOOR', 0));
    if (! room.openings.some((o) => o.type === 'WINDOW')) room.openings.push(newOpening('WINDOW', 0));
    room.openings.forEach((o) => {
      if (o.type === 'DOOR') o.isEntrance = !! o.isEntrance;
    });
    room.floorZones = Array.isArray(room.floorZones) ? room.floorZones : [];
    room.wallZones = Array.isArray(room.wallZones) ? room.wallZones : [];
    room.points = room.points || {};
    room.fixtures = room.fixtures || {};
    room.pointsTouched = !! room.pointsTouched;
    room.heightTouched = !! room.heightTouched;
    if (KITCHEN_TYPES.includes(room.roomType)) room.kitchenFurnitureMm ??= 0;

    return room;
  }

  function newRoom(roomType, roomArea, index) {
    // A plausible rectangle for the share of the area this room takes: the visitor
    // corrects the two dimensions, and every wall quantity follows from them.
    const side = Math.max(1.5, Math.sqrt(Math.max(roomArea, 4)));
    const type = roomTypeOf(roomType);

    return normaliseRoom({
      id: `room-${index + 1}-${Math.random().toString(36).slice(2, 7)}`,
      name: labelOf(enums.roomTypes, roomType),
      roomType,
      lengthMm: mm(side * 1.2),
      widthMm: mm(side / 1.2),
      // Every room starts at the object's ceiling and follows it until the visitor
      // types this room's own height (`heightTouched`). Before this, a 3 200 mm flat
      // meant correcting five rooms one by one.
      heightMm: state.object.ceilingHeightMm || DEFAULT_CEILING_MM,
      heightTouched: false,
      currentState: null, // null → the object condition seeds it on the server
      ...roomFinishes(type),
      openings: [
        newOpening('DOOR', 1),
        newOpening('WINDOW', roomType === 'BATHROOM' || roomType === 'WC' || roomType === 'CORRIDOR' ? 0 : 1),
      ],
      // Extra finishes on part of a surface, e.g. 2 m² of porcelain at the door of a
      // laminate kitchen-living room; the main finish covers whatever is left.
      floorZones: [],
      wallZones: [],
      // Bathrooms and WCs get a warm floor by default; a kitchen is wet underfoot
      // but rarely heated.
      warmFloor: { type: type.hasWetWalls ? 'ELECTRIC' : 'NONE', area: null },
      // «Sadə» mode: the server fills the points and fixtures from its norms (room
      // type + area + ceiling) and ignores these maps until `pointsTouched`.
      points: {},
      fixtures: {},
      pointsTouched: false,
    });
  }

  /** Σ of the rooms' floor areas as typed — what the estimate is calculated on. */
  const roomsArea = () => round2(state.rooms.reduce((sum, r) => sum + ((r.lengthMm || 0) * (r.widthMm || 0)) / 1e6, 0));

  /** How far, in %, the rooms' sum is from the area stated on step 1. */
  function areaDeviation() {
    const objectArea = +state.object.area || 0;

    return objectArea > 0 ? (Math.abs(roomsArea() - objectArea) / objectArea) * 100 : 0;
  }

  const areaMismatch = () => areaDeviation() > (settings.areaTolerancePercent ?? 5);

  /**
   * Scale every room so their sum matches a new object area. Both sides get the
   * square root of the ratio, so the rooms keep their proportions; the result is
   * snapped to the 10 mm step of the fields.
   */
  function scaleRoomsTo(area) {
    const current = roomsArea();
    if (! (current > 0) || ! (area > 0)) return;

    const k = Math.sqrt(area / current);
    state.rooms.forEach((room) => {
      room.lengthMm = snapMm(room.lengthMm * k);
      room.widthMm = snapMm(room.widthMm * k);
    });
  }

  /** The three finishes a room of this type starts with. */
  function roomFinishes(type) {
    return {
      floorFinish: defaultFinish('FLOOR', !! type.isWet),
      wallFinish: defaultFinish('WALL', !! type.hasWetWalls),
      ceilingFinish: defaultFinish('CEILING', false),
    };
  }

  // ---------- the engine ----------------------------------------------------------

  function payload() {
    return {
      // Shape version: from 2 on, room and opening dimensions are millimetres.
      v: CONFIG.settings.payloadVersion,
      object: state.object,
      rooms: state.rooms.map(({ kitchenFurnitureMm, ...room }) => ({
        ...room,
        // The kitchen run is only meaningful for the two kitchen types; a room that
        // was a kitchen a minute ago must not keep shortening its skirting.
        ...(KITCHEN_TYPES.includes(room.roomType) ? { kitchenFurnitureMm: Math.max(0, +kitchenFurnitureMm || 0) } : {}),
        openings: room.openings.filter((o) => o.qty > 0),
        floorZones: (room.floorZones || []).filter((z) => z.finish && z.areaM2 > 0),
        wallZones: (room.wallZones || []).filter((z) => z.finish && z.areaM2 > 0),
        // «Sadə»: the server derives the counts from its norms and ignores the maps;
        // sending them empty keeps that true on a server that predates the flag.
        points: room.pointsTouched ? room.points : {},
        fixtures: room.pointsTouched ? room.fixtures : {},
        warmFloor: room.warmFloor.type === 'NONE' ? { type: 'NONE' } : room.warmFloor,
      })),
      works: state.works,
      materials: state.materials,
      modules: state.modules,
      basket: {
        ...state.basket,
        // Ids and quantities only — the server re-reads every price from the
        // catalog, so nothing in localStorage can change an estimate.
        items: cartProducts.map((p) => ({ productId: p.id, quantity: p.quantity })),
      },
    };
  }

  // ---------- persistence ---------------------------------------------------------

  /** Everything the visitor answered, so leaving the page loses nothing. */
  const STATE_KEY = 'archi-detailed-calc';

  /**
   * Shape version of the saved answers.
   *
   *   1 — room and opening dimensions in METRES
   *   2 — in MILLIMETRES (`lengthMm`, `widthMm`, `heightMm`, openings `widthMm` /
   *       `heightMm`)
   *   3 — three surface states and three object conditions; materials and works
   *       keyed by finish id; the engineering modules derived from the rooms
   *   4 — renovation level and object ceiling height; per-room `pointsTouched`
   *       («Sadə» / «Ətraflı») and `heightTouched`; lighting split by kind
   *       (`lights` → `lights_chandelier`); floor / wall zones; PASSAGE openings
   *
   * This marker exists for exactly this moment: reading an old blob as if it were
   * current would turn a visitor's 4.5 m room into 4.5 mm, or their "old tile" into
   * no state at all. upgradeSaved() converts it instead of discarding their answers —
   * the same steps the server takes in Units::upgradePayload().
   */
  const STATE_VERSION = 4;

  function persist() {
    try {
      localStorage.setItem(STATE_KEY, JSON.stringify({
        v: STATE_VERSION,
        step: state.step,
        object: state.object,
        rooms: state.rooms,
        works: state.works,
        materials: state.materials,
        modules: state.modules,
        basket: { useCart: state.basket.useCart, excluded: state.basket.excluded },
        roomsTouched: state.roomsTouched,
        demolitionTouched: state.demolitionTouched,
        rewireTouched: state.rewireTouched,
      }));
    } catch {
      // Storage blocked (private window) — the calculator still works, the
      // answers simply do not survive a page change.
    }
  }

  /** v2 → v3 value maps; the finish ids themselves did not change. */
  const LEGACY = {
    states: { SHELL: 'BARE', ROUGH_FLOOR: 'BARE', PLASTER_READY: 'PREPARED', PAINT_READY: 'PREPARED', OLD_FINISH: 'OLD', OLD_TILE: 'OLD' },
    conditions: { PARTIAL: 'PREPARED', READY_BASE: 'PREPARED' },
    materials: {
      MAT_FLOOR_LAMINATE: 'LAMINATE', MAT_FLOOR_SPC: 'SPC', MAT_FLOOR_PARQUET: 'PARQUET', MAT_FLOOR_CARPET: 'CARPET',
      MAT_FLOOR_TILE: 'FLOOR_TILE', MAT_FLOOR_STONE: 'FLOOR_STONE', MAT_WALL_PAINT: 'PAINT', MAT_WALL_WALLPAPER: 'WALLPAPER',
      MAT_WALL_DECOR_PLASTER: 'DECOR_PLASTER', MAT_WALL_TILE: 'WALL_TILE', MAT_WALL_PANEL: 'WALL_PANEL', MAT_WALL_STONE: 'WALL_STONE',
      MAT_CEIL_PAINT: 'CEILING_PAINT', MAT_CEIL_GYPSUM: 'GYPSUM_FLAT', MAT_CEIL_STRETCH: 'STRETCH_CEILING',
    },
    works: {
      FLOOR_LAMINATE_INSTALL: 'LAMINATE', FLOOR_SPC_INSTALL: 'SPC', FLOOR_PARQUET_INSTALL: 'PARQUET', FLOOR_TILE_INSTALL: 'FLOOR_TILE',
      FLOOR_STONE_INSTALL: 'FLOOR_STONE', TILE_INSTALL_STANDARD: 'WALL_TILE', TILE_INSTALL_LARGE: 'WALL_TILE_LARGE', TILE_INSTALL_MOSAIC: 'MOSAIC',
      WALL_PAINT: 'PAINT', WALL_WALLPAPER: 'WALLPAPER', WALL_DECOR_PLASTER: 'DECOR_PLASTER', WALL_PANEL_INSTALL: 'WALL_PANEL',
      WALL_STONE_INSTALL: 'WALL_STONE', CEIL_PAINT: 'CEILING_PAINT', CEIL_GYPSUM_FLAT: 'GYPSUM_FLAT', CEIL_GYPSUM_COMPLEX: 'GYPSUM_COMPLEX',
      CEIL_STRETCH: 'STRETCH_CEILING',
    },
  };

  const rekey = (map, legacy) => Object.fromEntries(Object.entries(map || {}).map(([k, v]) => [legacy[k] || k, v]));

  /**
   * Bring a saved blob up to STATE_VERSION, one version at a time. Anything older or
   * unversioned is discarded, because guessing the unit from the magnitude of a
   * number is how a 4.5 becomes a 4 500 mm room one visitor and a 4.5 mm room the next.
   */
  function upgradeSaved(saved) {
    if (saved.v === STATE_VERSION) return saved;
    if (! [1, 2, 3].includes(saved.v)) return null;

    // The steps are chained: a v1 blob goes 1 → 2 → 3 → 4, a v3 one only the last step.
    if (saved.v === 1 || saved.v === 2) upgradeTo3(saved);
    upgradeTo4(saved);

    return saved;
  }

  /** v1 / v2 → v3: metres to millimetres, then the 2026-11 simplification. */
  function upgradeTo3(saved) {
    if (saved.v === 1) {
      const toMm = (node, from, to) => {
        if (Number.isFinite(+node[from]) && node[to] === undefined) node[to] = Math.round(+node[from] * 1000);
        delete node[from];
      };

      (saved.rooms || []).forEach((room) => {
        toMm(room, 'length', 'lengthMm');
        toMm(room, 'width', 'widthMm');
        toMm(room, 'height', 'heightMm');
        (room.openings || []).forEach((opening) => {
          toMm(opening, 'width', 'widthMm');
          toMm(opening, 'height', 'heightMm');
        });
      });
    }

    const object = saved.object || {};
    object.condition = LEGACY.conditions[object.condition] || object.condition
      || (object.marketType === 'NEW_BUILD' ? 'SHELL' : 'OLD_RENOVATION');
    delete object.marketType;
    delete object.totalFloors;
    saved.object = object;

    (saved.rooms || []).forEach((room) => {
      if (room.currentState) room.currentState = LEGACY.states[room.currentState] || room.currentState;
      (room.openings || []).forEach((opening) => { delete opening.subtype; });
      room.fixtures = room.fixtures || {};
    });

    saved.materials = rekey(saved.materials, LEGACY.materials);
    saved.works = rekey(saved.works, LEGACY.works);
    saved.modules = { services: { finalCleaning: saved.modules?.services?.finalCleaning ?? true } };
    saved.v = 3;
  }

  /**
   * v3 → v4 (TZ 17.11.2026). A room whose counts the visitor had edited keeps them
   * as «Ətraflı»; every other room becomes «Sadə» and is priced from the norms. The
   * single ceiling-light count becomes chandeliers — the server maps the old key
   * the same way. The object ceiling is taken from the first room, and a room that
   * differs from it is treated as one whose height was typed by hand.
   */
  function upgradeTo4(saved) {
    const object = saved.object || {};
    const rooms = saved.rooms || [];

    object.level = object.level || 'FULL';
    object.ceilingHeightMm = object.ceilingHeightMm || +rooms[0]?.heightMm || DEFAULT_CEILING_MM;
    saved.object = object;

    rooms.forEach((room) => {
      const points = room.points || {};

      if (points.lights !== undefined) {
        if (points.lights_chandelier === undefined) points.lights_chandelier = points.lights;
        delete points.lights;
      }

      room.points = points;
      room.fixtures = room.fixtures || {};
      room.pointsTouched = Object.keys(room.points).length > 0 || Object.keys(room.fixtures).length > 0;
      room.heightTouched = +room.heightMm !== +object.ceilingHeightMm;
      room.floorZones = room.floorZones || [];
      room.wallZones = room.wallZones || [];
    });

    saved.v = 4;
  }

  /** @returns {boolean} true when a previous session was restored */
  function restore() {
    const stored = readJson(localStorage.getItem(STATE_KEY), null);
    const saved = stored && Array.isArray(stored.rooms) && stored.rooms.length ? upgradeSaved(stored) : null;

    if (! saved) {
      return false;
    }

    state.object = { ...state.object, ...saved.object };
    state.rooms = saved.rooms;
    state.works = saved.works || {};
    state.materials = saved.materials || {};
    state.modules = { ...state.modules, ...saved.modules };
    state.basket.useCart = saved.basket?.useCart ?? state.basket.useCart;
    state.basket.excluded = Array.isArray(saved.basket?.excluded) ? saved.basket.excluded : [];
    state.step = Math.min(5, Math.max(0, +saved.step || 0));
    state.roomsTouched = !! saved.roomsTouched;
    state.demolitionTouched = !! saved.demolitionTouched;
    state.rewireTouched = !! saved.rewireTouched;
    state.editRoom = 0;

    // A finish the admin has since switched off cannot stay selected: fall back to
    // the room type's default rather than sending an id the server will ignore.
    const known = new Set(finishes.map((f) => f.id));
    state.rooms.forEach((room) => {
      const type = roomTypeOf(room.roomType);
      const defaults = roomFinishes(type);

      ['floorFinish', 'wallFinish', 'ceilingFinish'].forEach((key) => {
        if (room[key] && ! known.has(room[key])) room[key] = defaults[key];
      });
      normaliseRoom(room);
      // Same rule for the extra zones: a switched-off finish is dropped, not sent.
      room.floorZones = room.floorZones.filter((z) => known.has(z.finish));
      room.wallZones = room.wallZones.filter((z) => known.has(z.finish));
    });

    return true;
  }

  function resetAll() {
    popup.confirm(T.alerts.reset_confirm, {
      confirmText: T.actions.reset,
      danger: true,
      onConfirm: () => {
        try {
          localStorage.removeItem(STATE_KEY);
        } catch {
          // nothing stored — the reload below still restarts the wizard
        }
        // A clean restart, with the URL query dropped so old hand-over
        // parameters do not resurrect the answers that were just discarded.
        window.location.href = window.location.pathname;
      },
    });
  }

  let pending = 0;
  let inflight = null;

  /**
   * Recalculate. Debounced because a room dimension is typed digit by digit, and
   * every keystroke would otherwise be its own round trip; the previous request is
   * abandoned so a slow one can never overwrite a newer result.
   */
  function recalculate({ immediate = false } = {}) {
    // Every state mutation funnels through here, so this is also the save point.
    persist();

    clearTimeout(pending);

    pending = setTimeout(async () => {
      inflight?.abort();
      const controller = new AbortController();
      inflight = controller;

      state.loading = true;
      state.error = null;
      renderSide();

      try {
        const response = await fetch(page.dataset.urlEstimate, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': page.dataset.csrf || '',
          },
          body: JSON.stringify(payload()),
          signal: controller.signal,
        });

        if (! response.ok) throw new Error(`HTTP ${response.status}`);

        state.estimate = await response.json();
      } catch (error) {
        if (error.name === 'AbortError') return;
        // The specification is explicit: when the engine cannot be reached the
        // calculator says so rather than showing a stale or invented figure.
        state.error = T.error?.failed || '';
      } finally {
        if (inflight === controller) {
          state.loading = false;
          inflight = null;
          // The one automatic re-render: it must not snatch the field the visitor
          // is still typing into. See render().
          render({ deferWhileTyping: true });
        }
      }
    }, immediate ? 0 : 300);
  }

  /**
   * Re-read both carts and redraw. Needed when the basket changes without this
   * page being re-initialised: the visitor removes a product on /cart in another
   * tab (storage event fires here), or comes back to this page through the
   * browser's back/forward cache (pageshow with persisted=true) — in both cases
   * the list built at load time would keep showing the deleted product.
   */
  let refreshingCart = false;

  async function refreshCartProducts() {
    if (refreshingCart) return;
    refreshingCart = true;

    let server = [];
    if (page.dataset.authenticated === '1' && page.dataset.urlCartItems) {
      try {
        const response = await fetch(page.dataset.urlCartItems, { headers: { Accept: 'application/json' } });
        if (response.ok) server = (await response.json()).items || [];
      } catch {
        // Offline — merge the browser cart alone rather than keep a stale list.
      }
    }

    const next = mergeCart(server);
    const key = (list) => JSON.stringify(list.map((p) => [p.id, p.quantity]));
    refreshingCart = false;

    if (key(next) === key(cartProducts)) return;

    cartProducts = next;
    const ids = new Set(cartProducts.map((p) => p.id));
    state.basket.excluded = state.basket.excluded.filter((id) => ids.has(id));
    if (! cartProducts.length) state.basket.useCart = false;
    render();
    recalculate({ immediate: true });
  }

  window.addEventListener('pageshow', (event) => {
    if (event.persisted) refreshCartProducts();
  });
  window.addEventListener('storage', (event) => {
    if (event.key === 'archi-cart') refreshCartProducts();
  });

  const totals = () => state.estimate?.totals || null;
  const lines = () => state.estimate?.lines || [];
  const roomsDerived = () => state.estimate?.rooms || [];
  const derivedRoom = (id) => roomsDerived().find((r) => r.id === id);

  /**
   * The ONE area of the estimate: Σ of the room floor areas. Header, side panel and
   * every per-m² line use it; step 1's area is only the starting figure.
   */
  const calcArea = () => state.estimate?.calcArea ?? state.estimate?.derived?.TOTAL_FLOOR_AREA ?? 0;

  // ---------- shared markup -------------------------------------------------------

  /**
   * @param {Set<string>|null} flagged values drawn as "not in White Box" (dashed);
   *   the explanation sits under the chip row, see notInWhiteBox().
   */
  function chips(groupKey, options, current, extra = '', flagged = null) {
    return (
      `<div class="dc-chips" ${extra} data-group="${groupKey}">` +
      options
        .map((o) => `<button type="button" class="dc-chip ${current === o.value ? 'on' : ''} ${flagged?.has(o.value) ? 'nwb' : ''}" data-v="${esc(o.value)}">${esc(o.label)}</button>`)
        .join('') +
      '</div>'
    );
  }

  function select(name, options, current, attrs = '') {
    return (
      `<select class="${CLS.select}" ${attrs} data-select="${name}">` +
      options
        .map((o) => `<option value="${esc(o.value)}" ${o.value === current ? 'selected' : ''}>${esc(o.label)}</option>`)
        .join('') +
      '</select>'
    );
  }

  function field(label, inner) {
    return `<div class="dc-fb"><span>${esc(label)}</span><div class="ip">${inner}</div></div>`;
  }

  function numberInput(attrs, value, unit = '') {
    return `<input type="number" inputmode="decimal" min="0" ${attrs} value="${esc(value)}">${unit ? `<span class="u">${esc(unit)}</span>` : ''}`;
  }

  /**
   * A dimension field. Millimetres, so whole numbers on a 10 mm step and a numeric
   * keypad with no decimal point — nobody types 4 500.5 mm.
   */
  function mmInput(attrs, value) {
    return `<input type="number" inputmode="numeric" min="0" step="${MM_STEP}" ${attrs} value="${esc(value)}"><span class="u">${esc(T.units.mm)}</span>`;
  }

  // ---------- steps ---------------------------------------------------------------

  function stepObject() {
    const t = T.object;
    const o = state.object;

    const levelHint = o.level === 'WHITE_BOX' ? t.level_hint_white_box : t.level_hint_full;

    return `<div class="dc-sh"><div class="k">${esc(t.k)}</div><h2>${esc(t.h)}</h2></div>
  <div class="dc-group"><label>${esc(t.type)}</label>${chips('objectType', enums.objectTypes, o.objectType)}</div>
  <div class="dc-group"><label>${esc(t.level || '')}</label>${chips('level', levels, o.level)}${levelHint ? `<p class="${CLS.note}">${esc(levelHint)}</p>` : ''}</div>
  <div class="dc-fields">
    ${field(t.area, numberInput('data-o="area"', o.area, T.units.m2))}
    ${field(t.ceiling_height || T.rooms.height, mmInput('data-o="ceilingHeightMm"', o.ceilingHeightMm))}
    <div class="dc-fb"><span>${esc(t.city)}</span><div class="ip">${select('cityKey', CONFIG.cities.map((c) => ({ value: c.key, label: c.name })), o.cityKey)}</div></div>
    ${field(t.floor, numberInput('data-o="floor" step="1"', o.floor))}
  </div>
  ${t.area_hint ? `<p class="${CLS.note}">${esc(t.area_hint)}</p>` : ''}
  <div class="dc-group"><label>${esc(t.condition)}</label>${chips('condition', enums.conditions, o.condition)}</div>
  <div class="dc-group"><label>${esc(t.segment)}</label>${chips('segment', enums.segments, o.segment)}</div>
  <div class="dc-group"><label>${esc(t.extra)}</label>
    <div class="dc-toggles">
      <div class="dc-tg ${o.demolitionRequired ? 'on' : ''}" data-flag="demolitionRequired"><span>${esc(t.demolition)}</span><div class="dc-sw"></div></div>
      ${t.demolition_hint ? `<p class="${CLS.note}">${esc(t.demolition_hint)}</p>` : ''}
      <div class="dc-tg ${o.rewireElectrics ? 'on' : ''}" data-flag="rewireElectrics"><span>${esc(t.rewire || '')}</span><div class="dc-sw"></div></div>
      ${t.rewire_hint ? `<p class="${CLS.note}">${esc(t.rewire_hint)}</p>` : ''}
      <div class="dc-tg ${o.hasFreightElevator ? 'on' : ''}" data-flag="hasFreightElevator"><span>${esc(t.elevator)}</span><div class="dc-sw"></div></div>
      <div class="dc-tg ${o.designProject ? 'on' : ''}" data-flag="designProject"><span>${esc(t.design_needed)}</span><div class="dc-sw"></div></div>
    </div>
  </div>`;
  }

  /**
   * The counts a room is priced on, restricted to the keys its type has.
   *
   * «Sadə» (`pointsTouched` false): what the server derived from its norms — the
   * estimate's `rooms[].points / fixtures` — falling back to the type's defaults
   * until the first estimate arrives. «Ətraflı»: the visitor's own numbers first,
   * and the server's for any key they have not set.
   */
  function effectiveCounts(room, measured = derivedRoom(room.id)) {
    const type = roomTypeOf(room.roomType);
    const pick = (keys, ...sources) => Object.fromEntries(keys.map((key) => {
      const source = sources.find((s) => s && s[key] !== undefined && s[key] !== null);

      return [key, Math.max(0, Math.trunc(+(source?.[key] ?? 0)) || 0)];
    }));
    const own = room.pointsTouched;

    return {
      points: pick(pointKeysOf(type), own ? room.points : null, measured?.points, type.defaultPoints),
      fixtures: pick(fixtureKeysOf(type), own ? room.fixtures : null, measured?.fixtures, type.defaultFixtures),
    };
  }

  /**
   * «Elektrik: 8 rozetka, 2 açar, 6 işıq · Santexnika: unitaz, lavabo (orta norma)».
   *
   * The whole point of «Sadə» is that the visitor never has to count ~90 points
   * from drawings they do not have — but they still see what the estimate assumed.
   * Lighting is one figure here (spots + pendants + chandeliers); water and sewer
   * points follow from the fixtures and are only named when there are no fixtures.
   */
  function normSummary(room, counts, byNorm) {
    const P = T.points_short || {};
    const F = T.fixtures_short || {};
    const type = roomTypeOf(room.roomType);
    const electric = [];
    let lightsDone = false;

    pointKeysOf(type).forEach((key) => {
      if (key === 'water_points' || key === 'sewer_points') return;

      if (LIGHT_KEYS.includes(key)) {
        if (lightsDone) return;
        lightsDone = true;
        const lights = LIGHT_KEYS.reduce((sum, k) => sum + (counts.points[k] || 0), 0);
        if (lights > 0) electric.push(tr(P.lights || ':n', { n: lights }));

        return;
      }

      const n = counts.points[key] || 0;
      if (n > 0) electric.push(tr(P[key] || `:n ${key}`, { n }));
    });

    const plumbing = fixtureKeysOf(type)
      .filter((key) => counts.fixtures[key] > 0)
      .map((key) => `${F[key] || T.fixtures?.[key] || key}${counts.fixtures[key] > 1 ? ` ×${counts.fixtures[key]}` : ''}`);

    if (! plumbing.length && counts.points.water_points > 0) {
      plumbing.push(tr(P.water_points || ':n', { n: counts.points.water_points }));
    }

    const parts = [
      electric.length ? `${T.category?.electrical || ''}: ${electric.join(', ')}` : '',
      plumbing.length ? `${T.category?.plumbing || ''}: ${plumbing.join(', ')}` : '',
    ].filter(Boolean);

    if (! parts.length) return T.rooms.points_none || '';

    return parts.join(' · ') + (byNorm && T.rooms.by_norm ? ` ${T.rooms.by_norm}` : '');
  }

  /**
   * Rozetka, açar, unitaz… — the room's electrical points and sanitary fixtures.
   *
   * By default a room is in «Sadə» mode: one summary line built from the counts the
   * server derived from its norms, and a «Ətraflı / Dəqiqləşdir» button. The button
   * opens every counter of this room type, PREFILLED with those same counts, so the
   * visitor corrects only what they actually know. The first edit turns the room
   * «Ətraflı» (`pointsTouched`) and from then on the server uses the visitor's full
   * maps; «Orta normaya qaytar» hands the room back to the norms.
   */
  function pointsSection(room, index, measured) {
    const t = T.rooms;
    const type = roomTypeOf(room.roomType);
    const pointKeys = pointKeysOf(type);
    const fixtureKeys = fixtureKeysOf(type);

    if (! pointKeys.length && ! fixtureKeys.length) return '';

    const counts = effectiveCounts(room, measured);

    if (! room.pointsTouched && ! pointsOpen.has(room.id)) {
      const byNorm = measured?.pointsByNorm ?? true;

      return `<div class="dc-group"><label>${esc(t.points_label || '')}</label>
      <div class="dc-norm">
        <div class="dc-norm-t">${esc(normSummary(room, counts, byNorm))}</div>
        <button type="button" class="dc-link" data-pts-open="${index}">${esc(t.points_detail || '')}</button>
      </div></div>`;
    }

    const P = T.points || {};
    const F = T.fixtures || {};

    const pointInputs = pointKeys
      // An air-conditioner point is where a split unit hangs — the engine prices the
      // unit's installation from this count, so the label names the unit.
      .map((key) => field((key === 'ac_power' ? (P.ac || P[key]) : P[key]) || key,
        numberInput(`data-pt="${index}" data-pk="${esc(key)}" step="1"`, counts.points[key])))
      .join('');

    const fixtureInputs = fixtureKeys
      .map((key) => field(F[key] || key, numberInput(`data-fx="${index}" data-fk="${esc(key)}" step="1"`, counts.fixtures[key])))
      .join('');

    return `<div class="dc-group"><label>${esc(t.points_label || '')}</label>
      ${room.pointsTouched ? '' : (t.points_hint ? `<p class="${CLS.note}">${esc(t.points_hint)}</p>` : '')}
      ${pointInputs ? `<div class="dc-fields">${pointInputs}</div>` : ''}
    </div>
    ${fixtureInputs ? `<div class="dc-group"><label>${esc(t.fixtures_label || '')}</label><div class="dc-fields">${fixtureInputs}</div></div>` : ''}
    <div><button type="button" class="dc-link" data-pts-reset="${index}">${esc(t.points_reset || '')}</button></div>`;
  }

  /**
   * Door, window and doorless-passage rows. Each row is its own size and count, so a
   * kitchen with two windows of different sizes is two rows. A room always keeps at
   * least one door row and one window row (qty 0 = none); passages are optional.
   */
  function openingsEditor(room, index) {
    const t = T.rooms;
    const o = T.openings || {};
    const ofType = (type) => room.openings.filter((x) => x.type === type).length;
    const titles = { DOOR: o.door || t.doors, WINDOW: o.window || t.windows, PASSAGE: o.passage || 'PASSAGE' };

    const rows = room.openings
      .map((op, oi) => {
        const attrs = (key) => `data-op="${index}" data-oi="${oi}" data-of="${key}"`;
        const removable = op.type === 'PASSAGE' || ofType(op.type) > 1;

        // An entrance door is neither installed nor prepared as an interior door, so
        // its "new door" switch is not offered at all.
        const doorToggles = op.type === 'DOOR'
          ? `<div class="dc-toggles">
          <div class="dc-tg ${op.isEntrance ? 'on' : ''}" data-otg="${index}:${oi}:isEntrance"><span>${esc(o.entrance || '')}</span><div class="dc-sw"></div></div>
          ${op.isEntrance ? '' : `<div class="dc-tg ${op.installNewDoor ? 'on' : ''}" data-otg="${index}:${oi}:installNewDoor"><span>${esc(t.install_door)}</span><div class="dc-sw"></div></div>`}
        </div>`
          : '';

        return `<div class="dc-op">
        <div class="dc-op-h"><b>${esc(titles[op.type] || op.type)}</b>${removable ? `<button type="button" class="rm" data-orm="${index}:${oi}">${esc(t.remove)}</button>` : ''}</div>
        <div class="dc-fields">
          <div class="dc-fb dc-fb-sm"><span>${esc(o.qty || '')}</span><div class="ip">${numberInput(`${attrs('qty')} step="1"`, op.qty ?? 0)}</div></div>
          <div class="dc-fb dc-fb-sm"><span>${esc(o.width || '')}</span><div class="ip">${mmInput(attrs('widthMm'), op.widthMm ?? 0)}</div></div>
          <div class="dc-fb dc-fb-sm"><span>${esc(o.height || '')}</span><div class="ip">${mmInput(attrs('heightMm'), op.heightMm ?? 0)}</div></div>
        </div>
        ${doorToggles}
      </div>`;
      })
      .join('');

    const adds = ['DOOR', 'WINDOW', 'PASSAGE']
      .map((type) => `<button type="button" class="dc-op-add" data-oadd="${index}:${type}">${esc(o[`add_${type.toLowerCase()}`] || `+ ${titles[type]}`)}</button>`)
      .join('');

    return `<div class="dc-group"><label>${esc(t.openings || '')}</label>
      <div class="${CLS.colStack}">${rows}</div>
      <div class="dc-op-adds">${adds}</div>
    </div>`;
  }

  /**
   * «White Box-a daxil deyil: Laminat, Parket…» under a finish chip row. Shown only
   * on a White Box estimate, and only once the backend reports `labourStage`; the
   * chips stay selectable — the visitor may well want a finish on top of White Box.
   */
  function notInWhiteBox(surface) {
    if (state.object.level !== 'WHITE_BOX') return '';

    const names = finishStageFinishes(surface).map((f) => f.name);

    return names.length && T.rooms.not_in_white_box
      ? `<p class="dc-nwb-note">${esc(T.rooms.not_in_white_box)}: ${esc(names.join(', '))}</p>`
      : '';
  }

  const flaggedFinishes = (surface) => (state.object.level === 'WHITE_BOX'
    ? new Set(finishStageFinishes(surface).map((f) => f.id))
    : null);

  /**
   * Extra finishes on part of a surface — `floorZones` / `wallZones`, each
   * `{finish, areaM2}`. The room's main finish covers whatever is left, and the line
   * under the zones says how much that is, from the server's own floor / net wall
   * area so it can never disagree with the estimate.
   */
  function zonesEditor(room, index, surface, measured) {
    const key = surface === 'FLOOR' ? 'floorZones' : 'wallZones';
    const zones = room[key] || [];
    const options = finishesFor(surface);
    const t = T.rooms;

    const rows = zones
      .map((zone, zi) => `<div class="dc-zone">
        ${select(`zone:${index}:${key}:${zi}`, options, zone.finish, `aria-label="${esc(t.zone_finish || '')}"`)}
        <div class="dc-fb dc-zone-a"><div class="ip">${numberInput(`data-zone="${index}:${key}:${zi}" step="0.1" aria-label="${esc(t.zone_area || '')}"`, zone.areaM2, T.units.m2)}</div></div>
        <button type="button" class="dc-zone-rm" data-zrm="${index}:${key}:${zi}" aria-label="${esc(t.remove)}">×</button>
      </div>`)
      .join('');

    const surfaceArea = surface === 'FLOOR' ? measured?.floorArea : measured?.netWallArea;
    const used = zones.reduce((sum, z) => sum + (+z.areaM2 || 0), 0);
    const rest = zones.length && Number.isFinite(surfaceArea) && t.zone_rest
      ? `<p class="${CLS.note}">${esc(tr(t.zone_rest, { area: qty(round2(Math.max(0, surfaceArea - used))) }))}</p>`
      : '';

    return `${rows ? `<div class="${CLS.colStack}">${rows}</div>` : ''}${rest}
      <div><button type="button" class="dc-link" data-zadd="${index}:${key}">${esc(t.zones_add || '')}</button></div>`;
  }

  /**
   * «Otaqlar: 67.20 / 69 m²» above the rooms, and the yellow warning when the two
   * disagree by more than the admin's tolerance. Rebuilt in place while the visitor
   * types a dimension — it holds no field, so updating it interrupts nothing.
   */
  function areaCounter() {
    const mismatch = areaMismatch();
    const counter = tr(T.rooms.area_counter || ':rooms / :object m²', {
      rooms: qty(roomsArea()),
      object: qty(+state.object.area || 0),
    });

    return `<div class="dc-areacount ${mismatch ? 'warn' : ''}">${esc(counter)}</div>`
      + (mismatch ? `<div class="${CLS.warn}">${esc(tr(T.notice.area_mismatch, { deviation: num(Math.round(areaDeviation() * 10) / 10) }))}</div>` : '');
  }

  /** The room's own state, or — until the visitor picks one — the object's. */
  const roomState = (room) => room.currentState || conditionOf(state.object.condition).defaultSurfaceState;

  function stepRooms() {
    const t = T.rooms;
    const derived = roomsDerived();

    const rooms = state.rooms
      .map((room, index) => {
        const measured = derived.find((r) => r.id === room.id);

        if (index !== state.editRoom) {
          // Name the figures. "Hamam otağı · 22.80 m²" leaves the reader guessing
          // whether that is the floor, the walls or the room's share of the flat.
          // The dimensions are millimetres, the area is m² — 1 000 000 mm² to the m².
          const floorArea = measured
            ? measured.floorArea
            : ((room.lengthMm || 0) * (room.widthMm || 0)) / 1e6;
          // The separators are literal text, not margins: the row is read aloud by
          // screen readers and copied into WhatsApp, where CSS spacing does not exist.
          const summary = [
            '',
            `${qty(room.lengthMm)} × ${qty(room.widthMm)} ${T.units.mm}`,
            `${t.floor_short} ${qty(floorArea)} ${T.units.m2}`,
          ].join(' · ');

          return `<div class="dc-collapsed" data-open="${index}"><div><b>${esc(room.name)}</b><span class="a">${esc(summary)}</span></div><span class="${CLS.editHint}">${esc(t.edit)}</span></div>`;
        }

        const removable = state.rooms.length > 1 ? `data-rm="${index}"` : 'disabled';

        // Every derived quantity is shown back to the visitor: the specification asks
        // for real opening sizes precisely because NetWallArea cannot be guessed from
        // a door count, and seeing the result is what makes that worth typing.
        const derivedLine = measured
          ? `<div class="${CLS.trace}">${esc(tr(t.derived, {
              floor: qty(measured.floorArea),
              wall: qty(measured.netWallArea),
              skirting: qty(measured.skirtingLength),
            }))}</div>`
          : '';

        // The kitchen run comes off the skirting (TZ 17.11.2026 §P2): only asked
        // where there is a kitchen.
        const kitchenField = KITCHEN_TYPES.includes(room.roomType)
          ? field(t.kitchen_furniture || '', mmInput(`data-r="${index}" data-f="kitchenFurnitureMm"`, room.kitchenFurnitureMm ?? 0))
          : '';

        return `<div class="dc-room exp">
      <div class="dc-room-h"><b>${esc(room.name)}</b><button class="rm" ${removable}>${esc(t.remove)}</button></div>
      <div class="dc-group"><label>${esc(t.type)}</label>${chips('roomType', enums.roomTypes.map((r) => ({ value: r.value, label: r.label })), room.roomType, `data-ri="${index}"`)}</div>
      <div class="dc-fields">
        ${field(t.length, mmInput(`data-r="${index}" data-f="lengthMm"`, room.lengthMm))}
        ${field(t.width, mmInput(`data-r="${index}" data-f="widthMm"`, room.widthMm))}
        ${field(t.height, mmInput(`data-r="${index}" data-f="heightMm"`, room.heightMm))}
        ${kitchenField}
      </div>
      ${kitchenField && t.kitchen_furniture_hint ? `<p class="${CLS.note}">${esc(t.kitchen_furniture_hint)}</p>` : ''}
      ${openingsEditor(room, index)}
      ${derivedLine}
      <div class="dc-group"><label>${esc(t.floor_cover)}</label>${chips('floorFinish', finishesFor('FLOOR'), room.floorFinish, `data-ri="${index}"`, flaggedFinishes('FLOOR'))}${notInWhiteBox('FLOOR')}${zonesEditor(room, index, 'FLOOR', measured)}</div>
      <div class="dc-group"><label>${esc(t.wall_cover)}</label>${chips('wallFinish', finishesFor('WALL'), room.wallFinish, `data-ri="${index}"`, flaggedFinishes('WALL'))}${notInWhiteBox('WALL')}${zonesEditor(room, index, 'WALL', measured)}</div>
      <div class="dc-group"><label>${esc(t.ceiling_cover)}</label>${chips('ceilingFinish', finishesFor('CEILING'), room.ceilingFinish, `data-ri="${index}"`, flaggedFinishes('CEILING'))}${notInWhiteBox('CEILING')}</div>
      <div class="dc-group"><label>${esc(t.state)}</label>${chips('currentState', enums.surfaceStates, roomState(room), `data-ri="${index}"`)}${t.state_hint ? `<p class="${CLS.note}">${esc(t.state_hint)}</p>` : ''}</div>
      ${pointsSection(room, index, measured)}
      <div class="dc-tg ${room.warmFloor.type !== 'NONE' ? 'on' : ''}" data-warm="${index}"><span>${esc(t.heating)}</span><div class="dc-sw"></div></div>
      </div>`;
      })
      .join('');

    return `<div class="dc-sh"><div class="k">${esc(t.k)}</div><h2>${esc(t.h)}</h2><div class="d">${esc(t.d)}</div></div>
  <div class="dc-areabox" id="dcAreaCount">${areaCounter()}</div>
  ${rooms}<button class="dc-add" id="dcAddRoom">${esc(t.add)}</button>`;
  }

  /**
   * The works step. The dependency engine has already proposed the technological
   * chain for every surface; the visitor edits that list. No price is shown here —
   * the specification is explicit about it, and the side panel carries the money.
   */
  function stepWorks() {
    const t = T.works;
    const suggested = state.estimate?.suggestedWorks || [];
    // A finish's own installation is a work too, named by the finish id; it sits in
    // the block of its surface, or with the tiling when it is tile-type.
    const finishWorks = finishes.map((f) => ({
      workId: f.id,
      name: f.name,
      unit: f.unit,
      stage: f.labourStage,
      category: f.family === 'TILE' ? 'TILING' : ({ FLOOR: 'FLOOR', WALL: 'WALLS', CEILING: 'CEILING' })[f.surface],
    }));
    const byId = new Map([...CONFIG.works, ...finishWorks].map((w) => [w.workId, w]));
    const active = new Set(lines().filter((l) => l.lineType !== 'MATERIAL').map((l) => l.itemId));

    // «202.8 m²» instead of a bare «m²» (TZ 17.11.2026 §P2): a work's volume is the
    // sum of its lines over every room. The stage comes from the line when the
    // engine has priced it, otherwise from the work's own row.
    const volume = new Map();
    lines().forEach((line) => {
      if (line.lineType === 'MATERIAL') return;
      const entry = volume.get(line.itemId) || { quantity: 0, unit: line.unit, stage: line.stage };
      entry.quantity += +line.quantity || 0;
      entry.stage = entry.stage || line.stage;
      volume.set(line.itemId, entry);
    });

    const stageBadge = (stage) => (stage
      ? `<span class="dc-stage ${stage === 'FINISH' ? 'fin' : 'wb'}">${esc(T.stages_short?.[stage.toLowerCase()] || stage)}</span>`
      : '');

    const groups = {};

    suggested.forEach((workId) => {
      const work = byId.get(workId);
      if (! work) return;

      (groups[work.category] ??= []).push(work);
    });

    const cards = Object.entries(groups)
      .map(([category, works]) => {
        const rows = works
          .map((work) => {
            const on = state.works[work.workId]?.selected !== false && active.has(work.workId);
            const measured = on ? volume.get(work.workId) : null;
            const amount = measured ? `${qty(round2(measured.quantity))} ${measured.unit || work.unit}` : work.unit;

            return `<div class="dc-wr ${on ? 'on' : ''}" data-w="${esc(work.workId)}"><div class="lft"><div class="dc-cb">${CHK}</div><div class="nm">${esc(work.name)}</div></div><div class="pr">${esc(amount)}${stageBadge(measured?.stage || work.stage)}</div></div>`;
          })
          .join('');

        return `<div class="dc-group"><label>${esc(T.category?.[category.toLowerCase()] || category)}</label><div class="${CLS.colStack}">${rows}</div></div>`;
      })
      .join('');

    const removedSuggestion = Object.values(state.works).some((w) => w.selected === false);

    return `<div class="dc-sh"><div class="k">${esc(t.k)}</div><h2>${esc(t.h)}</h2><div class="d">${esc(t.d)}</div></div>
  <p class="${CLS.note}">${esc(t.auto_hint || '')} ${esc(t.no_prices)}</p>
  ${state.object.level === 'WHITE_BOX' && t.white_box_hint ? `<div class="${CLS.warn}">${esc(t.white_box_hint)}</div>` : ''}
  ${removedSuggestion ? `<div class="${CLS.warn}">${esc(t.removed_warning)}</div>` : ''}
  ${cards}
  ${modulesBlock()}`;
  }

  /**
   * Air conditioning, light fittings, pipes and cable are counted from the rooms'
   * points now, so the only thing left to ask here is the cleaning.
   */
  function modulesBlock() {
    const m = state.modules;

    return `<div class="dc-group"><label>${esc(T.modules.services)}</label>
    <div class="dc-toggles">
      <div class="dc-tg ${m.services.finalCleaning ? 'on' : ''}" data-mflag="services.finalCleaning"><span>${esc(T.modules.final_cleaning)}</span><div class="dc-sw"></div></div>
    </div>
  </div>`;
  }

  /**
   * Materials. Every requirement the engine derived is listed with its quantity and
   * the three ways it can be priced (master spec §12): a real catalog product, the
   * finish's or category's average price for the chosen segment, or "I will buy it
   * myself". `meta.materialId` is the selection key — a finish id for the finish
   * material, a category id for everything else.
   */
  function stepMaterials() {
    const t = T.materials;

    const rows = lines()
      .filter((line) => line.lineType === 'MATERIAL' && line.meta?.materialId)
      .map((line) => {
        const categoryId = line.meta.materialId;
        const selection = state.materials[categoryId] || { mode: 'FALLBACK_SEGMENT' };
        const options = [
          { value: 'FALLBACK_SEGMENT', label: t.mode.fallback },
          ...cartProducts.map((p) => ({ value: `PRODUCT:${p.id}`, label: `${p.name} — ${money(p.price)}` })),
          { value: 'EXCLUDED', label: t.mode.excluded },
        ];
        const current = selection.mode === 'CATALOG_PRODUCT' ? `PRODUCT:${selection.productId}` : selection.mode;
        const packages = line.meta.packageQty ? ` · ${tr(t.packages, { qty: line.meta.packageQty })}` : '';

        // A material the visitor buys themselves is worth 0 ₼ in the estimate, and a
        // bare "0 ₼" reads like a free material. What they will roughly spend is
        // shown instead, marked as outside the total.
        const price = isOwnMaterial(line)
          ? (Number.isFinite(+line.meta.estimatedTotal) && line.meta.estimatedTotal !== null
            ? tr(t.excluded_estimate || '≈ :amount', { amount: money(line.meta.estimatedTotal) })
            : '')
          : (line.unitPrice === null ? T.notice.price_missing : money(line.lineTotal));

        return `<div class="dc-mr">
      <div class="ml"><b>${esc(line.name)}</b><span>${esc(tr(t.required, { qty: qty(line.quantity), unit: line.unit }))}${esc(packages)}</span></div>
      <div class="mr-r">
        <span class="price ${isOwnMaterial(line) ? 'own' : ''}">${esc(price)}</span>
        ${select(`material:${categoryId}`, options, current)}
      </div>
    </div>`;
      })
      .join('');

    // "Səbətinizdəki 0 məhsul…" is not information, it is noise — the banner and
    // its toggle only appear when there actually is a basket to include.
    const cartBanner = cartProducts.length
      ? `<div class="dc-banner"><b>${esc(tr(t.banner, { count: cartProducts.length }))}</b><div class="dc-tg grn ${state.basket.useCart ? 'on' : ''}" data-basket="useCart"><span>${esc(T.side.use_in_calc)}</span><div class="dc-sw"></div></div></div>`
      : '';

    return `<div class="dc-sh"><div class="k">${esc(t.k)}</div><h2>${esc(t.h)}</h2><div class="d">${esc(t.d)}</div></div>
  ${cartBanner}
  ${rows || `<p class="${CLS.note}">${esc(t.not_selected)}</p>`}
  <a class="dc-pick" href="${esc(catalogUrl)}">${esc(t.pick)}</a>`;
  }

  function stepCart() {
    const t = T.cart;

    if (! cartProducts.length) {
      return `<div class="dc-sh"><div class="k">${esc(t.k)}</div><h2>${esc(t.h)}</h2></div>
    <p class="${CLS.note}">${esc(T.materials.d)}</p>
    <a class="dc-pick" href="${esc(catalogUrl)}">${esc(T.materials.pick)}</a>`;
    }

    const rows = cartProducts
      .map((product) => {
        const excluded = state.basket.excluded.includes(product.id);
        const line = lines().find((l) => l.itemId === `CART_${product.id}`);
        // The same product may already be priced as the material for a surface, in
        // which case it is deliberately absent from the cart lines. Saying so is the
        // difference between "the calculator handled it" and "the calculator lost it".
        const asMaterial = ! line && ! excluded
          ? lines().find((l) => l.meta?.productId === product.id)
          : null;

        const right = asMaterial
          ? `<div class="p">${esc(money(asMaterial.lineTotal))}</div>`
            + `<span class="dc-badge">${esc(t.counted_as_material)}</span>`
          : `<div class="p">${esc(money(line ? line.lineTotal : product.price * product.quantity))}</div>`;

        const detail = asMaterial
          ? `${qty(asMaterial.quantity)} ${asMaterial.unit} · ${asMaterial.name}`
          : `${qty(line ? line.quantity : product.quantity)} × ${money(line?.unitPrice ?? product.price)}`;

        const thumb = product.image
          ? `<img class="dc-thumb" src="${esc(product.image)}" alt="" loading="lazy">`
          : '<div class="dc-thumb"></div>';

        return `<div class="dc-cart-row ${excluded ? 'off' : ''}" data-c="${product.id}">
      <div class="cbx">${CHK}</div>${thumb}
      <div class="info"><b>${esc(line?.name || product.name)}</b><div class="calc">${esc(detail)}</div></div>
      <div class="rr">${right}</div>
    </div>`;
      })
      .join('');

    const included = cartProducts.length - state.basket.excluded.length;
    // Only the rows actually billed as cart items; a product counted as a material
    // belongs to the materials subtotal, not this one.
    const cartTotal = lines()
      .filter((l) => l.meta?.fromCart)
      .reduce((sum, l) => sum + l.lineTotal, 0);

    return `<div class="dc-sh"><div class="k">${esc(t.k)}</div><h2>${esc(t.h)}</h2><div class="d">${esc(t.d)}</div></div>
  ${rows}
  <div class="${CLS.cartRow}"><b class="${CLS.cartLabel}">${esc(tr(t.summary, { count: included }))}</b><span class="${CLS.sumValue}">${formatMoney(cartTotal)} <span class="${CLS.sumUnit}">${esc(T.currency)}</span></span></div>`;
  }

  // ---------- stage structure (TZ 17.11.2026 §1–2) ---------------------------------

  /** The two stages in the order the estimate reads: White Box first. */
  const STAGES = ['WHITE_BOX', 'FINISH'];
  const stageTitle = (key) => (key === 'WHITE_BOX' ? T.summary.white_box : T.summary.finish_stage) || key;

  /** Stages that have anything in them — a White Box estimate has no finishing block. */
  const stagesWithContent = (total) => STAGES.filter((key) => (+total.stages?.[key]?.base || 0) > 0);

  const marginLabel = (total) => tr(T.side.margin || ':percent%', {
    percent: num(total.marginPercent ?? settings.contractorMarginPercent ?? 0),
  });
  const contingencyLabel = (total) => tr(T.side.contingency, { percent: num(total.contingencyPercent) });
  const vatLabel = () => tr(T.side.vat, { percent: num(settings.vatPercent) });

  /**
   * The estimate as the client reads it, top to bottom:
   *   White Box (works, materials, subtotal) → Bitirmə (works, materials, subtotal)
   *   → Logistika və xidmətlər → Podratçı əlavəsi → Ehtiyat → (ƏDV) → Yekun.
   *
   * Every money row is ADDITIVE: the subtotals, the logistics-and-services row and
   * the three percentage rows sum to the grand total underneath them. The stage's
   * `other` (logistics + final cleaning) is not in its subtotal — it is collected in
   * the logistics-and-services row with the services that belong to no stage.
   *
   * @returns {Array<{head?: string, label?: string, value?: number, kind?: string, hint?: string}>}
   */
  function structureRows(total) {
    const rows = [];

    stagesWithContent(total).forEach((key) => {
      const stage = total.stages[key];

      // «White Box» is an English term: uppercased under lang="az" it reads «WHİTE BOX».
      rows.push({ head: stageTitle(key), lang: key === 'WHITE_BOX' ? 'en' : null });
      rows.push({ label: T.summary.works, value: +stage.works || 0, kind: 'sub' });
      rows.push({ label: T.summary.materials, value: +stage.materials || 0, kind: 'sub' });
      rows.push({ label: T.summary.subtotal, value: round2((+stage.works || 0) + (+stage.materials || 0)), kind: 'subtotal' });
    });

    // Whatever of the base is not a stage's works or materials: the stages' `other`
    // (logistics, final cleaning) plus the services outside both stages. Taken as
    // the remainder of `baseSubtotal` so the rows add up whatever the server files
    // under `services` — it also counts the final cleaning there, which already sits
    // in a stage's `other`, and adding the two would bill the cleaning twice.
    const stageItems = STAGES.reduce((sum, key) => sum + (+total.stages?.[key]?.works || 0) + (+total.stages?.[key]?.materials || 0), 0);
    const other = Number.isFinite(+total.baseSubtotal) && total.baseSubtotal !== null
      ? round2(+total.baseSubtotal - stageItems)
      : round2(STAGES.reduce((sum, key) => sum + (+total.stages?.[key]?.other || 0), 0) + (+total.servicesBlock?.base || 0));

    rows.push({
      label: T.summary.logistics_services,
      value: other,
      // Delivery and lifting are a norm per m² of the object, and the panel says so.
      hint: total.logistics > 0 ? T.side.delivery_note : null,
    });
    rows.push({ label: marginLabel(total), value: +total.margin || 0 });
    rows.push({ label: contingencyLabel(total), value: +total.contingency || 0 });
    rows.push({ label: vatLabel(), value: +total.vat || 0 });

    // A zero row says nothing; a stage subtotal stays even at 0 so the block closes.
    return rows.filter((row) => row.head || row.kind === 'subtotal' || row.value > 0);
  }

  /** The three totals: each stage with its own margin and contingency, and the whole. */
  function stageTotals(total) {
    return [
      ...stagesWithContent(total).map((key) => ({ label: stageTitle(key), value: +total.stages[key].total || 0 })),
      { label: T.side.grand_total, value: total.grandTotalRounded, grand: true },
    ];
  }

  /** «Özünüz almalı olduğunuz materiallar» — never inside the estimate rows. */
  function ownMaterialsBlock() {
    const own = lines().filter(isOwnMaterial);
    if (! own.length) return '';

    const t = T.summary;
    const sum = own.reduce((acc, line) => acc + (+line.meta.estimatedTotal || 0), 0);

    const rows = own
      .map((line) => {
        const room = line.roomName ? `<span class="dc-lroom"> · ${esc(line.roomName)}</span>` : '';
        const amount = Number.isFinite(+line.meta.estimatedTotal) && line.meta.estimatedTotal !== null
          ? `≈ ${money(line.meta.estimatedTotal)}`
          : '';

        return `<div class="row"><div class="top"><span class="l">${esc(line.name)}${room}</span><span class="v">${esc(amount)}</span></div><div class="${CLS.trace}">${esc(`${qty(line.quantity)} ${line.unit}`)}</div></div>`;
      })
      .join('');

    return `<div class="dc-own">
      <div class="dc-own-h"><b>${esc(t.own_materials || '')}</b>${sum > 0 ? `<span>≈ ${esc(money(round2(sum)))}</span>` : ''}</div>
      ${t.own_materials_hint ? `<p class="${CLS.note}">${esc(t.own_materials_hint)}</p>` : ''}
      <div class="dc-bd">${rows}</div>
    </div>`;
  }

  const normNotice = () => (state.estimate?.pointsByNorm && T.notice.points_by_norm
    ? `<div class="dc-notice">${esc(T.notice.points_by_norm)}</div>`
    : '');

  const validityNote = () => (settings.estimateValidDays && T.notice.valid_days
    ? tr(T.notice.valid_days, { days: settings.estimateValidDays })
    : '');

  /**
   * The final estimate. Every group opens down to the individual lines, each with the
   * short trace the engine produced — "184 m² × 8 AZN = 1 472 AZN" — because a client
   * who cannot see where a number came from does not believe it (master spec §16.1).
   */
  function stepSummary() {
    const t = T.summary;
    const e = state.estimate;

    if (! e) return `<div class="dc-sh"><h2>${esc(T.side.calculating)}</h2></div>`;

    const staged = !! e.totals?.stages;

    const grouped = state.summaryView === 'room'
      ? groupByRoom()
      : groupByCategory();

    const max = Math.max(1, ...grouped.map((g) => g.total));

    const blocks = grouped
      .map((group) => {
        const rows = group.lines
          // In the category view the same work repeats once per room ("Divarların
          // suvanması" × 4) and reads like a duplicate; the room name says why.
          .map((line) => {
            // Only rendered when there is a room to name — an empty span would
            // still be a styled element in the layout.
            const room = state.summaryView === 'category' && line.roomName
              ? `<span class="dc-lroom"> · ${esc(line.roomName)}</span>`
              : '';

            return `<div class="row"><div class="top"><span class="l">${esc(line.name)}${line.precisionClass === 'ESTIMATED' ? ' *' : ''}${room}</span><span class="v">${esc(line.unitPrice === null ? T.notice.price_missing : money(line.lineTotal))}</span></div><div class="${CLS.trace}">${esc(line.trace)}</div></div>`;
          })
          .join('');

        return `<div class="dc-bd">
      <div class="row"><div class="top"><span class="l"><b>${esc(group.label)}</b></span><span class="v"><b>${esc(money(group.total))}</b></span></div>
      <div class="dc-bar ${group.total === max ? 'hi' : ''}"><i style="width:${Math.max(4, Math.round((group.total / max) * 100))}%"></i></div></div>
      ${rows}
    </div>`;
      })
      .join('');

    const warnings = (e.warnings || [])
      .map((w) => {
        if (w.code === 'PRICE_MISSING') return `${T.notice.price_missing}: ${w.name}`;
        if (w.code === 'LOGISTICS_INCOMPLETE') return T.notice.logistics_incomplete;
        if (w.code === 'AREA_MISMATCH') return tr(T.notice.area_mismatch, { deviation: w.deviationPercent });
        return null;
      })
      .filter(Boolean);

    // Three totals at a glance — White Box, finishing, the whole — before the detail.
    const tiles = staged
      ? `<div class="dc-tiles">${stageTotals(e.totals)
        .map((tile) => `<div class="dc-tile ${tile.grand ? 'grand' : ''}"><span>${esc(tile.label)}</span><b>${esc(tile.grand ? `${num(tile.value)} ${T.currency_code}` : money(tile.value))}</b></div>`)
        .join('')}</div>`
      : '';

    const structure = staged
      ? `<div class="dc-struct">${structureRows(e.totals)
        .map((row) => (row.head
          ? `<div class="h"${row.lang ? ` lang="${row.lang}"` : ''}>${esc(row.head)}</div>`
          : `<div class="r ${row.kind || ''}"><span>${esc(row.label)}</span><b>${esc(money(row.value))}</b></div>`
            + (row.hint ? `<div class="${CLS.sideHint}">${esc(row.hint)}</div>` : '')))
        .join('')}</div>`
      : '';

    const validity = validityNote();

    return `<div class="dc-sh"><div class="k">${esc(t.k)}</div><h2>${esc(tr(t.h, { area: qty(calcArea()) }))}</h2><div class="d">${esc(t.d)}</div></div>
  ${warnings.length ? `<div class="${CLS.warn}">${warnings.map(esc).join('<br>')}</div>` : ''}
  ${normNotice()}
  ${tiles}
  <div class="dc-chips" data-group="summaryView">
    <button type="button" class="dc-chip ${state.summaryView === 'category' ? 'on' : ''}" data-v="category">${esc(t.by_category)}</button>
    <button type="button" class="dc-chip ${state.summaryView === 'room' ? 'on' : ''}" data-v="room">${esc(t.by_room)}</button>
  </div>
  ${blocks}
  ${e.hasEstimatedLines ? `<p class="${CLS.note}">* ${esc(T.notice.estimated)}</p>` : ''}
  ${ownMaterialsBlock()}
  ${structure}
  <div class="dc-total"><b>${esc(t.total)}</b><div class="v"><b>${num(e.totals.grandTotalRounded)}</b><span> ${esc(T.currency_code)}</span></div></div>
  ${validity ? `<p class="${CLS.note}">${esc(validity)}</p>` : ''}`;
  }

  /**
   * The lines the category / room tabs break down. A material the visitor buys
   * themselves has its own block; and once the backend reports the stage structure,
   * the margin, contingency and VAT rows live in that structure, not among the groups.
   */
  function detailLines() {
    const staged = !! totals()?.stages;

    return lines().filter((line) => ! isOwnMaterial(line) && ! (staged && isTotalsLine(line)));
  }

  function groupByCategory() {
    const groups = new Map();

    detailLines().forEach((line) => {
      const key = line.category;
      const group = groups.get(key) || { label: T.category?.[key.toLowerCase()] || key, total: 0, lines: [] };

      group.total = Math.round((group.total + line.lineTotal) * 100) / 100;
      group.lines.push(line);
      groups.set(key, group);
    });

    return [...groups.values()].sort((a, b) => b.total - a.total);
  }

  function groupByRoom() {
    const groups = new Map();

    detailLines().forEach((line) => {
      const key = line.roomId || '_object';
      const group = groups.get(key) || { label: line.roomName || T.steps.object, total: 0, lines: [] };

      group.total = Math.round((group.total + line.lineTotal) * 100) / 100;
      group.lines.push(line);
      groups.set(key, group);
    });

    return [...groups.values()].sort((a, b) => b.total - a.total);
  }

  // ---------- side panel ----------------------------------------------------------

  /**
   * The running estimate. Every row that carries money is listed and the rows add up
   * to the figure above them — a panel whose parts do not sum to its own total is
   * the fastest way to lose a client's trust. Rows worth 0 ₼ are not shown.
   *
   * With the stage structure the panel is the same structure as the final estimate,
   * on every step; without it (an older backend) it is the flat list it always was.
   */
  function sideSummary(total) {
    const rows = total.stages
      ? structureRows(total)
        .map((row) => (row.head
          ? `<div class="r head"${row.lang ? ` lang="${row.lang}"` : ''}><span>${esc(row.head)}</span></div>`
          : `<div class="r ${row.kind || ''}"><span>${esc(row.label)}</span><b>${esc(money(row.value))}</b></div>`
            + (row.hint ? `<div class="${CLS.sideHint}">${esc(row.hint)}</div>` : '')))
        .join('')
      : [
          { label: T.side.works, value: total.works },
          { label: T.side.materials, value: total.materials },
          { label: T.side.services, value: total.services },
          {
            label: T.side.logistics,
            value: total.logistics,
            // The catalog rarely knows what a product weighs, so delivery and lifting
            // are a norm per m² of the object — and the panel says so.
            hint: total.logistics > 0 ? T.side.delivery_note : null,
          },
          { label: marginLabel(total), value: total.margin },
          { label: contingencyLabel(total), value: total.contingency },
          { label: vatLabel(), value: total.vat },
        ]
          .filter((row) => (row.show ?? row.value > 0))
          .map((row) => `<div class="r"><span>${esc(row.label)}</span><b>${esc(money(row.value))}</b></div>`
            + (row.hint ? `<div class="${CLS.sideHint}">${esc(row.hint)}</div>` : ''))
          .join('');

    // The stage totals under the grand total, each with its own margin and reserve.
    const stageLine = total.stages
      ? stagesWithContent(total)
        .map((key) => `<div class="r stage"><span>${esc(stageTitle(key))}</span><b>${esc(money(total.stages[key].total))}</b></div>`)
        .join('')
      : '';

    return `<div class="big"><b>${esc(tr(T.approx, { amount: num(total.grandTotalRounded) }))}</b><span>${esc(T.currency_code)}</span></div>
     <div class="${CLS.sideMeta}">${esc(tr(T.side.meta, { area: qty(calcArea()), rooms: state.rooms.length }))}</div>
     <div class="rows">
       ${rows}
       <div class="r total"><span>${esc(T.side.grand_total)}</span><b>${esc(money(total.grandTotal))}</b></div>
       ${stageLine ? `<div class="stages">${stageLine}</div>` : ''}
     </div>
     ${state.estimate?.pointsByNorm && T.notice.points_by_norm ? `<div class="${CLS.sideMeta}">${esc(T.notice.points_by_norm)}</div>` : ''}
     ${hasOnlyFallbackMaterials() ? `<div class="${CLS.sideMeta}">${esc(T.side.estimate_only)}</div>` : ''}`;
  }

  /**
   * The WhatsApp message, with the figures exactly as the summary shows them: the
   * grand total, each stage's total, the margin and the reserve that those totals
   * include, VAT when it is added, how long the prices hold, and the norm notice
   * when the points were not counted by the visitor.
   */
  function whatsappText(total) {
    const out = [tr(T.alerts.whatsapp, { total: num(total.grandTotalRounded) })];
    const included = T.alerts.whatsapp_included ? ` (${T.alerts.whatsapp_included})` : '';

    if (total.stages) {
      stagesWithContent(total).forEach((key) => out.push(`${stageTitle(key)}: ${money(total.stages[key].total)}`));
      if (total.margin > 0) out.push(`${marginLabel(total)}: ${money(total.margin)}${included}`);
    }

    if (total.contingency > 0) out.push(`${contingencyLabel(total)}: ${money(total.contingency)}${included}`);
    if (total.vat > 0) out.push(`${vatLabel()}: ${money(total.vat)}${included}`);

    const validity = validityNote();
    if (validity) out.push(validity);
    if (state.estimate?.pointsByNorm && T.notice.points_by_norm) out.push(T.notice.points_by_norm);

    return out.join('\n');
  }

  function renderSide() {
    if (state.step === 5) return renderSummaryActions();

    const s = T.side;
    const total = totals();

    const body = state.error
      ? `<div class="${CLS.warn}">${esc(state.error)}</div>`
      : ! total || state.loading
        ? `<div class="${CLS.sideMeta}">${esc(s.calculating)}</div>`
        : sideSummary(total);

    sideEl.innerHTML = `
  <div class="dc-sum">
    <div class="lbl">${esc(s.label)}</div>
    ${body}
  </div>
  ${
    state.step >= 3 && cartProducts.length
      ? `<div class="dc-banner ${CLS.sideBanner}"><b>${esc(tr(s.cart_banner, { count: cartProducts.length }))}</b><div class="${CLS.sideBannerRow}"><span class="${CLS.sideBannerHint}">${esc(s.use_in_calc)}</span><div class="dc-tg grn bare ${state.basket.useCart ? 'on' : ''}" data-basket="useCart"><div class="dc-sw"></div></div></div></div>`
      : ''
  }
  <div class="dc-nav">
    ${state.step > 0 ? `<button class="dc-back" id="dcBack">${esc(s.back)}</button>` : ''}
    <button class="dc-next" id="dcNextBtn">${esc(state.step === 4 ? s.calculate : s.next)}  →</button>
  </div>`;

    sideEl.querySelector('#dcBack')?.addEventListener('click', () => goToStep(state.step - 1));
    sideEl.querySelector('#dcNextBtn')?.addEventListener('click', () => goToStep(Math.min(state.step + 1, 5)));
    bindBasketToggles(sideEl);
  }

  const hasOnlyFallbackMaterials = () =>
    lines().some((l) => l.source === 'FALLBACK_CATEGORY_PRICE') &&
    ! lines().some((l) => l.source === 'CATALOG_PRICE');

  /**
   * "Bütün materialları səbətə at" is switched off for now at Archi's request: the
   * quantities it would push are only as good as the packaging data on the products,
   * and most of the catalog has none yet. Set ADD_ALL_TO_CART_ENABLED to true to
   * bring it back — addAllToCart() below is intact.
   */
  const ADD_ALL_TO_CART_ENABLED = false;

  function renderSummaryActions() {
    const a = T.actions;

    sideEl.innerHTML = `
  <div class="dc-act">
    ${ADD_ALL_TO_CART_ENABLED ? `<button class="dc-next ${CLS.addAll}" id="dcAddAll">${esc(a.add_all)}</button>` : ''}
    <div class="dc-sec"><button id="dcPdf">${esc(a.pdf)}</button><button id="dcWa">${esc(a.whatsapp)}</button></div>
    <button class="dc-outline" id="dcTurnkey">${esc(a.turnkey)}</button>
  </div>`;

    // The PDF is generated server-side from a frozen snapshot, so the figures in
    // the file can never differ from the ones on this screen — even if an admin
    // edits a rate a second later. The click waits for the snapshot, then starts
    // the download.
    sideEl.querySelector('#dcPdf').addEventListener('click', async (event) => {
      const button = event.currentTarget;
      if (button.disabled) return;
      button.disabled = true;

      const result = await submitRequest('PDF');
      button.disabled = false;

      if (result?.snapshotUuid && page.dataset.urlSnapshotPdf) {
        window.location.href = page.dataset.urlSnapshotPdf.replace('__UUID__', result.snapshotUuid);
      } else {
        popup.info(T.error?.failed || '');
      }
    });

    sideEl.querySelector('#dcAddAll')?.addEventListener('click', addAllToCart);
    sideEl.querySelector('#dcTurnkey').addEventListener('click', requestTurnkey);

    sideEl.querySelector('#dcWa').addEventListener('click', () => {
      const total = totals();
      if (! total) return;

      submitRequest('WHATSAPP');
      window.open(`https://wa.me/?text=${encodeURIComponent(whatsappText(total))}`, '_blank');
    });
  }

  /**
   * Freeze the estimate server-side. PDF, WhatsApp and a turnkey order all have to
   * quote the same sum even if a rate is edited a minute later, and only a stored
   * snapshot can guarantee that (master spec §22).
   */
  const jsonHeaders = () => ({
    'Content-Type': 'application/json',
    Accept: 'application/json',
    'X-CSRF-TOKEN': page.dataset.csrf || '',
  });

  /**
   * Store the answers and freeze the estimate, then raise a request of the given
   * type so it appears in the admin panel.
   *
   * @returns {Promise<object|null>} the created request, or null when it failed
   */
  async function submitRequest(type, extra = {}) {
    if (! state.estimate) return null;

    try {
      const created = await fetch(page.dataset.urlSessions, {
        method: 'POST',
        headers: jsonHeaders(),
        body: JSON.stringify({ ...payload(), currentStep: 'SUMMARY' }),
      });

      if (! created.ok) throw new Error(`HTTP ${created.status}`);

      const { uuid } = await created.json();

      const raised = await fetch(`${page.dataset.urlSessions}/${uuid}/request`, {
        method: 'POST',
        headers: jsonHeaders(),
        body: JSON.stringify({ type, ...extra }),
      });

      if (! raised.ok) throw new Error(`HTTP ${raised.status}`);

      return await raised.json();
    } catch {
      return null;
    }
  }

  /**
   * The turnkey request is the one action that is a real lead, so it asks for a name
   * and a phone number first — everything else on this page deliberately collects
   * nothing (TZ §46).
   */
  async function requestTurnkey() {
    const name = window.prompt(T.alerts.turnkey_name);
    if (name === null) return;

    const phone = window.prompt(T.alerts.turnkey_phone);
    if (phone === null) return;

    if (! String(phone).trim()) {
      popup.info(T.alerts.turnkey_phone_required);

      return;
    }

    const result = await submitRequest('TURNKEY', { name: String(name).trim(), phone: String(phone).trim() });

    if (result) popup.success(T.alerts.turnkey);
    else popup.info(T.error?.failed || '');
  }

  /** Add every priced material line to the cart, in the quantity the estimate needs. */
  async function addAllToCart() {
    // The cart belongs to an account; a guest is told so rather than watching six
    // silent 401s go by.
    if (page.dataset.authenticated !== '1') {
      popup.info(T.modules.login_required, {
        buttons: [{ text: T.actions.add_all, variant: 'primary', href: page.dataset.urlLogin }],
      });

      return;
    }

    const productLines = lines().filter((l) => l.meta?.productId && ! l.meta.fromCart);

    for (const line of productLines) {
      // Sequential on purpose: the cart endpoint is an upsert per product, and a
      // parallel burst of six writes on the same row is how duplicates appear.
      // eslint-disable-next-line no-await-in-loop
      await fetch(page.dataset.urlCart, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': page.dataset.csrf || '',
        },
        body: JSON.stringify({ product_id: line.meta.productId, quantity: Math.max(1, Math.round(line.quantity)) }),
      }).catch(() => null);
    }

    // The server cart just changed; the cart step should show the new rows without
    // waiting for a reload.
    refreshCartProducts();

    const gotoCart = page.dataset.gotoCart;
    const cont = page.dataset.continue;

    popup.success(
      T.alerts.added,
      gotoCart && cont
        ? { buttons: [{ text: gotoCart, variant: 'primary', href: '/cart' }, { text: cont, variant: 'outline' }] }
        : undefined,
    );
  }

  // ---------- stepper & wiring ----------------------------------------------------

  function renderStepper() {
    const strip = STEPS.map((name, i) => {
      const cls = i < state.step ? 'done' : i === state.step ? 'active' : '';
      const inner = `<div class="dc-step ${cls}" data-i="${i}"><div class="c">${i < state.step ? '✓' : i + 1}</div><div class="l">${esc(name)}</div></div>`;
      return inner + (i < STEPS.length - 1 ? '<div class="dc-conn"></div>' : '');
    }).join('');

    stepEl.innerHTML = `<div class="dc-strip">${strip}</div><div class="dc-stepcap">${esc(STEP_CAPTIONS[state.step] || '')}</div>`
      + `<button type="button" class="dc-reset" id="dcReset" title="${esc(T.alerts.reset_confirm)}">${esc(T.actions.reset)}</button>`;

    stepEl.querySelectorAll('.dc-step').forEach((el) =>
      el.addEventListener('click', () => {
        const i = +el.dataset.i;
        if (i <= state.step) goToStep(i);
      }),
    );

    stepEl.querySelector('#dcReset').addEventListener('click', resetAll);
  }

  /**
   * @param {{force?: boolean}} options `force` skips the area check below — used by
   *   the dialog's own "fix" and "continue" buttons.
   */
  function goToStep(i, { force = false } = {}) {
    if (i === state.step) return;

    // Leaving the rooms with a sum that disagrees with step 1 (the TZ's test case:
    // 55 m² stated, 99.21 m² in rooms) asks once — but never blocks.
    if (! force && state.step === 1 && i > 1 && areaMismatch()) {
      confirmAreaMismatch(i);

      return;
    }

    state.step = i;
    persist();
    render();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  /**
   * «Obyektin sahəsini X olaraq düzəlt» / «Otaqlara qayıt» / «Davam et». The
   * estimate is calculated on the rooms either way; fixing the stated area only makes
   * step 1 agree with them.
   */
  function confirmAreaMismatch(target) {
    const a = T.alerts;
    const rooms = roomsArea();

    popup.show({
      type: 'warning',
      title: a.area_title || '',
      message: tr(a.area_message || T.notice.area_mismatch, {
        rooms: qty(rooms),
        object: qty(+state.object.area || 0),
        deviation: num(Math.round(areaDeviation() * 10) / 10),
      }),
      buttons: [
        {
          text: tr(a.area_fix || ':area', { area: qty(rooms) }),
          variant: 'primary',
          onClick: () => {
            state.object.area = rooms;
            recalculate({ immediate: true });
            goToStep(target, { force: true });
          },
        },
        { text: a.area_back || '', variant: 'outline', value: false },
        { text: a.area_continue || '', variant: 'outline', onClick: () => goToStep(target, { force: true }) },
      ],
    });

    // Three long labels do not fit side by side in the popup's one button row; this
    // page's stylesheet stacks them (see .dc-pop-stack in calculator-detailed.css).
    document.getElementById('archiPopup')?.classList.add('dc-pop-stack');
  }

  /**
   * Area changed on step 1 while the rooms are the visitor's own: offer to scale
   * them to the new area instead of silently keeping (or silently re-seeding) them.
   * Asked on `change`, i.e. once the visitor has finished typing the number.
   */
  function offerRoomRescale(previousArea) {
    const area = +state.object.area || 0;
    const current = roomsArea();

    if (! state.roomsTouched || ! (area > 0) || ! (current > 0) || area === previousArea) return;
    // The rooms already add up to the new figure — nothing to ask.
    if (Math.abs(current - area) / area < 0.005) return;

    popup.confirm(T.alerts.rescale_rooms, {
      confirmText: T.alerts.rescale_yes,
      cancelText: T.alerts.rescale_no,
      onConfirm: () => {
        scaleRoomsTo(area);
        recalculate({ immediate: true });
        render();
      },
    });
  }

  /**
   * Is the visitor typing into a field of the current step?
   *
   * Only fields inside `mainEl` count. The side panel holds buttons, and the stepper
   * holds none, so rebuilding either one interrupts nothing.
   */
  function editingMainField() {
    const el = document.activeElement;

    return !! el
      && mainEl.contains(el)
      && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.tagName === 'SELECT');
  }

  // Set when an automatic re-render was skipped because a field had focus, so the
  // step knows it still owes itself a rebuild once the visitor leaves that field.
  let mainRenderPending = false;

  /**
   * @param {{deferWhileTyping?: boolean}} options
   *   `deferWhileTyping` is passed only by recalculate(): every other caller is a
   *   deliberate click that changes what the step must show — a new room, a removed
   *   line, a step change — and those must always rebuild.
   */
  function render({ deferWhileTyping = false } = {}) {
    renderStepper();

    // recalculate() lands ~300ms after the last keystroke and rebuilt the whole step
    // through innerHTML, which replaces the very <input> being typed into. The field
    // lost focus mid-word; worse, on `type="number"` a half-typed decimal reads back
    // as "" (the browser rejects "4." as an incomplete number), so the rebuild wiped
    // the entry as well. Nothing in the step is worth that: the results panel keeps
    // updating live, and the step's own markup waits for the field to be released.
    if (deferWhileTyping && editingMainField()) {
      mainRenderPending = true;
      renderSide();

      return;
    }

    mainEl.innerHTML = [stepObject, stepRooms, stepWorks, stepMaterials, stepCart, stepSummary][state.step]();
    bindMain();
    mainRenderPending = false;
    renderSide();
  }

  // `mainEl` outlives every render, so this is bound once here rather than inside
  // bindMain(). The timeout lets focus settle first: tabbing from one field to the
  // next fires focusout before focusin, and rebuilding in between would destroy the
  // field the visitor has just reached.
  mainEl.addEventListener('focusout', () => {
    setTimeout(() => {
      if (mainRenderPending && ! editingMainField()) render();
    }, 0);
  });

  function bindBasketToggles(root) {
    root.querySelectorAll('[data-basket]').forEach((el) =>
      el.addEventListener('click', () => {
        state.basket.useCart = ! state.basket.useCart;
        recalculate({ immediate: true });
        render();
      }),
    );
  }

  /** "2:5:isEntrance" → [2, 5, 'isEntrance'] — the composite data attributes below. */
  const parts = (value) => String(value || '').split(':').map((p) => (/^\d+$/.test(p) ? +p : p));

  /**
   * First edit of a «Sadə» room's counter: the room becomes «Ətraflı», and the FULL
   * maps it was priced on are written to it, so the one number the visitor changed
   * is the only one that moves.
   */
  function touchPoints(room) {
    if (room.pointsTouched) return;

    const counts = effectiveCounts(room);
    room.points = counts.points;
    room.fixtures = counts.fixtures;
    room.pointsTouched = true;
  }

  function bindMain() {
    // chip groups — object fields, room fields and the summary view switch
    mainEl.querySelectorAll('.dc-chips[data-group]').forEach((group) =>
      group.addEventListener('click', (event) => {
        const button = event.target.closest('.dc-chip');
        if (! button) return;

        const key = group.dataset.group;
        const value = button.dataset.v;
        const roomIndex = group.dataset.ri;

        if (key === 'summaryView') {
          state.summaryView = value;
          render();

          return;
        }

        if (roomIndex !== undefined) {
          const room = state.rooms[+roomIndex];
          room[key] = value;
          state.roomsTouched = true;

          if (key === 'roomType') {
            const type = roomTypeOf(value);

            room.name = labelOf(enums.roomTypes, value);
            // The counts belong to the old room type; keeping them would give the
            // new bathroom a living room's eight sockets and no toilet. The
            // finishes follow too, while the visitor has not picked their own.
            // The room goes back to «Sadə»: the norms of its NEW type apply.
            room.points = {};
            room.fixtures = {};
            room.pointsTouched = false;
            pointsOpen.delete(room.id);
            Object.assign(room, roomFinishes(type));
            room.floorZones = [];
            room.wallZones = [];
            room.warmFloor = { type: type.hasWetWalls ? 'ELECTRIC' : 'NONE', area: null };
            if (KITCHEN_TYPES.includes(value)) room.kitchenFurnitureMm ??= 0;
          }
        } else {
          state.object[key] = value;

          // A commercial fit-out should not keep the flat's bedroom, but only while
          // the visitor has not started editing the rooms themselves.
          if (key === 'objectType' && ! state.roomsTouched) seedRooms();

          // An old flat is stripped out and rewired, a prepared base is neither —
          // the switches follow the condition until the visitor sets them.
          if (key === 'condition') applyConditionDefaults();
        }

        recalculate({ immediate: true });
        render();
      }),
    );

    mainEl.querySelectorAll('select[data-select]').forEach((el) =>
      el.addEventListener('change', () => {
        const [kind, ...rest] = el.dataset.select.split(':');
        const id = rest.join(':');

        if (kind === 'material') {
          if (el.value.startsWith('PRODUCT:')) {
            state.materials[id] = { mode: 'CATALOG_PRODUCT', productId: +el.value.split(':')[1] };
          } else {
            state.materials[id] = { mode: el.value };
          }
        } else if (kind === 'cityKey') {
          state.object.cityKey = el.value;
        } else if (kind === 'zone') {
          const [roomIndex, key, zoneIndex] = parts(id);
          const zone = state.rooms[roomIndex]?.[key]?.[zoneIndex];
          if (zone) zone.finish = el.value;
          state.roomsTouched = true;
        }

        recalculate({ immediate: true });
      }),
    );

    mainEl.querySelectorAll('input[data-o]').forEach((el) =>
      el.addEventListener('input', () => {
        state.object[el.dataset.o] = +el.value || 0;

        // The seeded rooms exist to match the stated area. While the visitor has
        // not edited them, a new area re-seeds them — otherwise typing 80 over the
        // default 120 leaves 120 m² worth of rooms behind the visitor's back, and
        // the whole estimate is silently 50% too big.
        if (el.dataset.o === 'area' && ! state.roomsTouched && state.object.area > 0) {
          seedRooms();
        }

        // The object ceiling carries every room whose height was never typed.
        if (el.dataset.o === 'ceilingHeightMm' && state.object.ceilingHeightMm > 0) {
          state.rooms.forEach((room) => {
            if (! room.heightTouched) room.heightMm = state.object.ceilingHeightMm;
          });
        }

        recalculate();
      }),
    );

    // Edited rooms are not re-seeded on every keystroke of the area; once the number
    // is finished (`change`), the visitor is asked whether to scale them.
    const areaEl = mainEl.querySelector('input[data-o="area"]');
    if (areaEl) {
      let areaBefore = state.object.area;
      areaEl.addEventListener('focus', () => { areaBefore = state.object.area; });
      areaEl.addEventListener('change', () => {
        offerRoomRescale(areaBefore);
        areaBefore = state.object.area;
      });
    }

    mainEl.querySelectorAll('input[data-r]').forEach((el) =>
      el.addEventListener('input', () => {
        const room = state.rooms[+el.dataset.r];
        room[el.dataset.f] = +el.value || 0;
        state.roomsTouched = true;

        // From now on this room keeps its own height when the object ceiling moves.
        if (el.dataset.f === 'heightMm') room.heightTouched = true;

        // The counter above the rooms holds no field, so it can follow every digit.
        const counter = mainEl.querySelector('#dcAreaCount');
        if (counter) counter.innerHTML = areaCounter();

        recalculate();
      }),
    );

    mainEl.querySelectorAll('input[data-pt]').forEach((el) =>
      el.addEventListener('input', () => {
        const room = state.rooms[+el.dataset.pt];
        if (! room) return;

        touchPoints(room);
        room.points = { ...room.points, [el.dataset.pk]: Math.max(0, Math.trunc(+el.value || 0)) };
        state.roomsTouched = true;
        recalculate();
      }),
    );

    mainEl.querySelectorAll('input[data-fx]').forEach((el) =>
      el.addEventListener('input', () => {
        const room = state.rooms[+el.dataset.fx];
        if (! room) return;

        touchPoints(room);
        room.fixtures = { ...room.fixtures, [el.dataset.fk]: Math.max(0, Math.trunc(+el.value || 0)) };
        state.roomsTouched = true;
        recalculate();
      }),
    );

    // «Ətraflı / Dəqiqləşdir» — opens the counters, prefilled; nothing changes yet.
    mainEl.querySelectorAll('[data-pts-open]').forEach((el) =>
      el.addEventListener('click', () => {
        const room = state.rooms[+el.dataset.ptsOpen];
        if (! room) return;

        pointsOpen.add(room.id);
        render();
      }),
    );

    // «Orta normaya qaytar» — the room is priced from the norms again.
    mainEl.querySelectorAll('[data-pts-reset]').forEach((el) =>
      el.addEventListener('click', () => {
        const room = state.rooms[+el.dataset.ptsReset];
        if (! room) return;

        room.points = {};
        room.fixtures = {};
        room.pointsTouched = false;
        pointsOpen.delete(room.id);
        recalculate({ immediate: true });
        render();
      }),
    );

    mainEl.querySelectorAll('input[data-op]').forEach((el) =>
      el.addEventListener('input', () => {
        const opening = state.rooms[+el.dataset.op]?.openings[+el.dataset.oi];
        if (! opening) return;

        state.roomsTouched = true;
        opening[el.dataset.of] = +el.value || 0;
        recalculate();
      }),
    );

    // Door switches: «Giriş qapısı» and «Yeni qapı quraşdırılsın».
    mainEl.querySelectorAll('[data-otg]').forEach((el) =>
      el.addEventListener('click', () => {
        const [roomIndex, openingIndex, key] = parts(el.dataset.otg);
        const opening = state.rooms[roomIndex]?.openings[openingIndex];
        if (! opening) return;

        opening[key] = ! opening[key];
        state.roomsTouched = true;
        recalculate({ immediate: true });
        render();
      }),
    );

    // A new row goes after the last row of its type, so doors stay with doors.
    mainEl.querySelectorAll('[data-oadd]').forEach((el) =>
      el.addEventListener('click', () => {
        const [roomIndex, type] = parts(el.dataset.oadd);
        const room = state.rooms[roomIndex];
        if (! room) return;

        const last = room.openings.map((o) => o.type).lastIndexOf(type);
        room.openings.splice(last === -1 ? room.openings.length : last + 1, 0, newOpening(type, 1));
        state.roomsTouched = true;
        recalculate({ immediate: true });
        render();
      }),
    );

    mainEl.querySelectorAll('[data-orm]').forEach((el) =>
      el.addEventListener('click', () => {
        const [roomIndex, openingIndex] = parts(el.dataset.orm);
        const room = state.rooms[roomIndex];
        if (! room) return;

        room.openings.splice(openingIndex, 1);
        normaliseRoom(room); // never below one door row and one window row
        state.roomsTouched = true;
        recalculate({ immediate: true });
        render();
      }),
    );

    // Extra finish zones: add, change the area, remove. The finish itself is a
    // <select>, handled with the other selects above.
    mainEl.querySelectorAll('[data-zadd]').forEach((el) =>
      el.addEventListener('click', () => {
        const [roomIndex, key] = parts(el.dataset.zadd);
        const room = state.rooms[roomIndex];
        if (! room) return;

        const surface = key === 'floorZones' ? 'FLOOR' : 'WALL';
        const main = surface === 'FLOOR' ? room.floorFinish : room.wallFinish;
        const options = finishesFor(surface);
        // The point of a zone is a DIFFERENT finish, so it starts on one.
        const finish = (options.find((o) => o.value !== main) || options[0])?.value;
        if (! finish) return;

        room[key] = [...(room[key] || []), { finish, areaM2: 1 }];
        state.roomsTouched = true;
        recalculate({ immediate: true });
        render();
      }),
    );

    mainEl.querySelectorAll('input[data-zone]').forEach((el) =>
      el.addEventListener('input', () => {
        const [roomIndex, key, zoneIndex] = parts(el.dataset.zone);
        const zone = state.rooms[roomIndex]?.[key]?.[zoneIndex];
        if (! zone) return;

        zone.areaM2 = Math.max(0, +el.value || 0);
        state.roomsTouched = true;
        recalculate();
      }),
    );

    mainEl.querySelectorAll('[data-zrm]').forEach((el) =>
      el.addEventListener('click', () => {
        const [roomIndex, key, zoneIndex] = parts(el.dataset.zrm);
        const room = state.rooms[roomIndex];
        if (! room?.[key]) return;

        room[key].splice(zoneIndex, 1);
        recalculate({ immediate: true });
        render();
      }),
    );

    mainEl.querySelectorAll('[data-flag]').forEach((el) =>
      el.addEventListener('click', () => {
        state.object[el.dataset.flag] = ! state.object[el.dataset.flag];

        // Once the visitor has an opinion about a switch, the condition chips stop
        // overwriting it.
        if (el.dataset.flag === 'demolitionRequired') state.demolitionTouched = true;
        if (el.dataset.flag === 'rewireElectrics') state.rewireTouched = true;

        recalculate({ immediate: true });
        render();
      }),
    );

    mainEl.querySelectorAll('[data-mflag]').forEach((el) =>
      el.addEventListener('click', () => {
        const [group, key] = el.dataset.mflag.split('.');
        state.modules[group][key] = ! state.modules[group][key];
        recalculate({ immediate: true });
        render();
      }),
    );

    mainEl.querySelectorAll('[data-warm]').forEach((el) =>
      el.addEventListener('click', () => {
        const room = state.rooms[+el.dataset.warm];
        room.warmFloor = { type: room.warmFloor.type === 'NONE' ? 'ELECTRIC' : 'NONE', area: null };
        recalculate({ immediate: true });
        render();
      }),
    );

    // A suggested work can be removed; the warning above the list is the consequence.
    // The row's own state decides the toggle, not the stored override: on a White Box
    // estimate the server switches FINISH works off with no override stored, and a
    // click must send `{selected: true}` to bring one back.
    mainEl.querySelectorAll('.dc-wr[data-w]').forEach((el) =>
      el.addEventListener('click', () => {
        const workId = el.dataset.w;
        const on = el.classList.contains('on');

        state.works[workId] = { selected: ! on };
        recalculate({ immediate: true });
        render();
      }),
    );

    mainEl.querySelectorAll('.dc-cart-row[data-c]').forEach((el) =>
      el.querySelector('.cbx').addEventListener('click', () => {
        const id = +el.dataset.c;
        const excluded = state.basket.excluded;

        state.basket.excluded = excluded.includes(id)
          ? excluded.filter((x) => x !== id)
          : [...excluded, id];

        recalculate({ immediate: true });
        render();
      }),
    );

    mainEl.querySelectorAll('[data-open]').forEach((el) =>
      el.addEventListener('click', () => {
        state.editRoom = +el.dataset.open;
        render();
      }),
    );

    mainEl.querySelectorAll('[data-rm]').forEach((el) =>
      el.addEventListener('click', (event) => {
        event.stopPropagation();
        state.rooms.splice(+el.dataset.rm, 1);
        state.editRoom = 0;
        recalculate({ immediate: true });
        render();
      }),
    );

    mainEl.querySelector('#dcAddRoom')?.addEventListener('click', () => {
      state.rooms.push(newRoom('OTHER', 12, state.rooms.length));
      state.editRoom = state.rooms.length - 1;
      recalculate({ immediate: true });
      render();
    });

    bindBasketToggles(mainEl);
  }

  // A returning visitor continues where they left off; only a fresh session is
  // seeded from the quick calculator's hand-over and the default room set. Explicit
  // URL parameters (the quick calculator's CTA) still win over a restored area,
  // segment and level — the visitor set them a moment ago, on purpose.
  const restored = restore();

  if (! restored) {
    hydrateFromQuick();
    seedRooms();
  } else {
    if (prefill.area !== undefined) state.object.area = prefill.area;
    if (prefill.segment !== undefined) state.object.segment = prefill.segment;
    if (prefill.renovationType !== undefined) applyLevelFromQuick(prefill.renovationType);
  }

  // ?step=1..6 opens the wizard on a given step. Used for deep links from support
  // material and for the admin panel's screenshots, which are captured from the real
  // page so they can never drift from what the visitor sees.
  const requestedStep = parseInt(new URLSearchParams(window.location.search).get('step'), 10);

  if (Number.isFinite(requestedStep)) {
    state.step = Math.min(STEPS.length - 1, Math.max(0, requestedStep - 1));
  }

  render();
  recalculate({ immediate: true });
}
