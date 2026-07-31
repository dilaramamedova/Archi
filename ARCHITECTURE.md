# ARCHI — Laravel architecture (contract for page agents)

> This document is **binding**. 23 page agents work in parallel; if everyone does not
> follow the same rules the result will not merge. When in doubt, look here — do not guess.

- **Working directory:** `C:/Users/mamed/ARCHI-laravel` (worktree, `laravel` branch)
- **Reference (READ ONLY):** `C:/Users/mamed/OneDrive/Desktop/ARCHI` — the old static
  project, verified 1:1 against Figma. **Never write there.**
- **Goal:** pixel parity + 3 languages (az / ru / en) + a clean Laravel structure.

---

## 0. GOLDEN RULES (do not break)

| # | Rule |
|---|---|
| 1 | **Change only YOUR files.** Do not step outside the "Your files" list below. |
| 2 | **Do NOT touch `resources/css/app.css`.** It holds the shared CSS; 23 agents writing there at once would conflict. |
| 3 | **Do NOT touch `resources/js/app.js`.** The registry already covers all 23 slugs. |
| 4 | **Do NOT touch `routes/web.php`.** Every route already exists. |
| 5 | **No global commands:** `npm run build`, `npm run dev`, `php artisan serve`, `git commit/push` — only the Gate agent runs those. |
| 6 | **No controllers, no custom PHP classes, no database, no migrations.** |
| 7 | **English everywhere** — file names, code, comments, translation keys, CSS class names, JS identifiers. Azerbaijani appears **only as translation values** inside `lang/az/`. Keep comments minimal and in English; no banner or ASCII-art comment blocks. |
| 8 | Every user-visible string goes through `__('...')`. The `az` value is the **exact** text from the old HTML. |
| 9 | Image paths are **always** `/assets/...` (root-relative). The old `assets/...` form is wrong. |

**Your files (slug = your page):**

```
resources/views/pages/{slug}.blade.php     ← replace the stub entirely
resources/css/pages/{slug}.css             ← the page's own CSS
resources/js/pages/{slug}.js               ← the page's own JS
lang/az/{slug}.php                         ← create it yourself
lang/ru/{slug}.php                         ← create it yourself
lang/en/{slug}.php                         ← create it yourself
```

---

## 1. Folder map

```
ARCHI-laravel/
├─ ARCHITECTURE.md                  ← this document
├─ routes/web.php                   ← ALL routes (closures, no controllers)
├─ lang/
│  ├─ az/  nav.php · footer.php · common.php · {slug}.php …
│  ├─ ru/  (same structure)
│  └─ en/  (same structure)
├─ public/assets/                   ← all images/icons (markup uses /assets/...)
├─ resources/
│  ├─ css/
│  │  ├─ app.css                    ← DO NOT TOUCH (theme + base + shared components)
│  │  └─ pages/{slug}.css           ← your file
│  ├─ js/
│  │  ├─ app.js                     ← DO NOT TOUCH (shared imports + page registry)
│  │  ├─ shared/navbar.js           ← shared (mega menu, search, language, cart)
│  │  ├─ shared/cursor.js           ← shared (round .pcard cursor)
│  │  └─ pages/{slug}.js            ← your file
│  └─ views/
│     ├─ components/                ← shared Blade components
│     │  layout · navbar · footer · pcard · scard · post · section-head
│     └─ pages/{slug}.blade.php     ← your file
└─ vite.config.js                   ← do not touch
```

---

## 2. Using the layout

Every page **must** be wrapped in `<x-layout>`:

```blade
<x-layout page="catalog" :title="__('catalog.meta_title')">

    <div class="wrap"><div class="inner">
        ... page markup ...
    </div></div>

</x-layout>
```

**Props**

| Prop | Type | Description |
|---|---|---|
| `page` | string | The `data-page` value. **Must equal the slug** — `app.js` picks the JS module from it. |
| `title` | string | `<title>` text. Defaults to `__('common.site_name')`. |
| `bodyClass` | string | Extra class on `<body>` (rare). |

For extra `<head>` content use the named slot:

```blade
<x-layout page="product" :title="...">
    <x-slot:head>
        <meta name="description" content="{{ __('product.meta_desc') }}">
    </x-slot:head>
    ...
</x-layout>
```

What the layout does itself:
`<!DOCTYPE html>` · `<html lang="{{ app()->getLocale() }}">` · meta tags · `<title>` ·
Google Fonts (**Inter 400–700 + Manrope 400/500**) · `@vite([...])` ·
`<body data-page="…" data-cur-product="…" data-cur-details="…">` ·
`<x-navbar/>` · `{{ $slot }}` · `<x-footer/>`.

> `<x-navbar/>` and `<x-footer/>` are already inside the layout — **do not call them again**
> in a page. `<footer>` breaks out of its container with `margin-left: calc(50% - 50vw)`,
> so it must remain a direct child of `<body>` (the layout already places it there).

---

## 3. Route table — old `.html` → new route

Convert every `href` with this table. **`href="catalog.html"` → `href="{{ route('catalog') }}"`**

| Old file | URL | Route name | View | data-page (slug) |
|---|---|---|---|---|
| `index.html` | `/` | `home` | `pages.home` | `home` |
| `catalog.html` | `/catalog` | `catalog` | `pages.catalog` | `catalog` |
| `product.html` | `/product` | `product` | `pages.product` | `product` |
| `search.html` | `/search` | `search` | `pages.search` | `search` |
| `specialists.html` | `/specialists` | `specialists` | `pages.specialists` | `specialists` |
| `specialist.html` | `/specialist` | `specialist` | `pages.specialist` | `specialist` |
| `blog.html` | `/blog` | `blog` | `pages.blog` | `blog` |
| `sell.html` | `/sell` | `sell` | `pages.sell` | `sell` |
| `login.html` | `/login` | `login` | `pages.login` | `login` |
| `register.html` | `/register` | `register` | `pages.register` | `register` |
| `cart.html` | `/cart` | `cart` | `pages.cart` | `cart` |
| `calculator.html` | `/calculator` | `calculator` | `pages.calculator` | `calculator` |
| `calculator-detailed.html` | `/calculator/detailed` | `calculator.detailed` | `pages.calculator-detailed` | `calculator-detailed` |
| `biznes-qeydiyyat.html` | `/business/register` | `business.register` | `pages.business-register` | `business-register` |
| `biznes-tamamlama-addim1.html` | `/business/onboarding/step-1` | `business.onboarding.step1` | `pages.business-onboarding-step1` | `business-onboarding-step1` |
| `biznes-tamamlama-addim3.html` | `/business/onboarding/step-3` | `business.onboarding.step3` | `pages.business-onboarding-step3` | `business-onboarding-step3` |
| `biznes-profil.html` | `/business/profile` | `business.profile` | `pages.business-profile` | `business-profile` |
| `biznes-profil-sirket.html` | `/business/profile/company` | `business.profile.company` | `pages.business-profile-company` | `business-profile-company` |
| `biznes-profil-elaqe.html` | `/business/profile/contact` | `business.profile.contact` | `pages.business-profile-contact` | `business-profile-contact` |
| `biznes-profil-mehsullar.html` | `/business/profile/products` | `business.profile.products` | `pages.business-profile-products` | `business-profile-products` |
| `biznes-profil-shourumlar.html` | `/business/profile/showrooms` | `business.profile.showrooms` | `pages.business-profile-showrooms` | `business-profile-showrooms` |
| `biznes-profil-bildirisler.html` | `/business/profile/notifications` | `business.profile.notifications` | `pages.business-profile-notifications` | `business-profile-notifications` |
| `biznes-profil-tehlukesizlik.html` | `/business/profile/security` | `business.profile.security` | `pages.business-profile-security` | `business-profile-security` |
| — | `/lang/{az\|ru\|en}` | `lang` | — | — |

**Links that never existed** (`haqqimizda.html`, `#`, empty `<a>`) → keep `href="#"`.
**Links with a query:** `search.html?tab=usta` → `{{ route('search', ['tab' => 'usta']) }}`.

If JS needs a route, **do not hardcode it** — pass it from Blade as a `data-*` attribute:

```blade
<div class="prod-grid" data-url-product="{{ route('product') }}">
```
```js
const url = el.dataset.urlProduct;
```

---

## 4. Blade components (API)

### 4.1 `<x-section-head>` — section head (`.sec-head`)

```blade
<x-section-head
    :tag="__('home.sec_bestsellers')"
    :title="__('home.sec_featured_products')"
    :more="route('search', ['tab' => 'prod'])" />
```

| Prop | Default | Description |
|---|---|---|
| `tag` | `null` | small label with the yellow rule |
| `title` | `null` | large heading |
| `more` | `null` | URL of the right-hand link. `null` → `<a>` without href (the old HTML has such cases). `false` → no link at all. |
| `moreLabel` | `__('common.view_more')` | link text |
| `icon` | `/assets/ic-arrow.svg` | arrow icon |

The output markup is **identical** to the old HTML:
`<div class="sec-head"><div><div class="sec-tag">…</div><div class="sec-title">…</div></div><a class="sec-more">…</a></div>`

### 4.2 `<x-pcard>` — product card (`.pcard`)

```blade
<x-pcard
    :cat="__('home.cat_tiles')"
    :name="__('home.prod_tile_matte')"
    now="23.90 ₼" old="45.99 ₼" off="-48%"
    rate="4.6" :reviews="__('home.reviews_1876')"
    img="/assets/prod-kafel.png"
    :href="route('product')" />
```

| Prop | Default | Description |
|---|---|---|
| `href` | `route('product')` | card link |
| `img` | `/assets/prod-kafel.png` | image |
| `cat` `name` | `null` | category · name |
| `now` `old` `off` | `null` | current price · old price · discount badge |
| `rate` `reviews` | `null` | rating · review count |
| `badges` | `[common.badge_new, common.badge_in_stock]` | array; each item is a string or `['label'=>'…','mine'=>true]` (yellow background) |
| `dots` | `3` | number of carousel dots, `0` → hidden |
| `dot` | `0` | index of the active dot |
| `cursor` | `__('common.go_to_product')` | text inside the round cursor |

For a user's own listing:
```blade
<x-pcard :badges="[['label' => __('common.your_listing'), 'mine' => true], ['label' => '4.6']]" … />
```
Extra utility classes are merged through `$attributes->merge`:
```blade
<x-pcard class="max-[1200px]:min-w-[260px]" … />
```

### 4.3 `<x-scard>` — specialist card (`.scard`)

```blade
<x-scard
    bg="#f5fbff"
    :role="__('home.role_tiler')"
    rate="4.9" :reviews="__('home.reviews_416')"
    :name="__('home.spec_name_1')"
    :exp="__('home.exp_12y')"
    :proj="__('home.proj_320')" />
```

| Prop | Default | Description |
|---|---|---|
| `href` | `null` | when given the card renders as `<a>`, otherwise `<div>` (old behaviour) |
| `bg` | `#f5fbff` | tint background of the `.top` block |
| `avatar` | `/assets/ic-person.svg` | avatar icon |
| `role` `rate` `reviews` `name` `exp` `proj` | `null` | text values |
| `badges` | `common.badge_top_master` (crown) + `common.badge_verified` (green) | item: `['label'=>'…','icon'=>'/assets/…','ok'=>bool]` |

> `.scard .name` and `.scard .meta` were **unstyled** in the old project too (no CSS rule
> existed). **Do not add CSS** for them — pixel parity depends on it.

### 4.4 `<x-post>` — blog card (`.post`)

```blade
<x-post
    :time="__('blog.read_6min')"
    :title="__('blog.post1_title')"
    :excerpt="__('blog.post1_excerpt')"
    :href="route('blog')"
    class="rounded-ds max-[1200px]:min-w-[260px]" />
```

| Prop | Default | Description |
|---|---|---|
| `href` | `null` | link (null → `<a>` without href) |
| `img` | `/assets/blog.png` | image |
| `time` `title` `excerpt` | `null` | text values |
| `read` | `__('common.read_arrow')` | bottom link text |

### 4.5 `<x-navbar>` / `<x-footer>`

The layout renders them — **you never call them**. Their text lives in `lang/*/nav.php`
and `lang/*/footer.php`. The navbar has the **language switcher** (`AZ · RU · EN`,
`/lang/{locale}` links, active locale on a yellow background).

**The active nav item is computed server-side** (`request()->routeIs(...)`) — nothing to do in JS.

---

## 5. i18n convention

- Default `az`, plus `ru` / `en`. Session key: `locale`.
- Switching: `GET /lang/{locale}` → writes the session → `redirect()->back()`.
- The locale is set **inside** the route closure (the `archiView()` helper), because the
  session is not available while the route file is being parsed.

### Key pattern

```
{slug}.{section}.{key}            → page-specific text   (lang/{locale}/{slug}.php)
nav.*  ·  footer.*  ·  common.*   → SHARED text          (already exists, do not change)
```

Example `lang/az/catalog.php`:

```php
<?php

return [
    'meta_title' => 'ARCHİ — Kataloq',

    'hero' => [
        'tag'   => 'Bütün məhsullar',
        'title' => 'Kataloq',
    ],
    'filter' => [
        'sort_popular' => 'Ən populyar',
        'sort_cheap'   => 'Əvvəlcə ucuz',
    ],
];
```
Usage: `{{ __('catalog.hero.title') }}`, `{{ __('catalog.filter.sort_cheap') }}`.
Keys are always English; only the values are localized.

### Rules

1. **The `az` value is character-for-character the old HTML text** — spacing, dash type
   (`—` vs `-`), the `&` sign and letter case included. `text-transform: uppercase` lives
   in CSS, so never write the text itself in capitals.
2. `ru` / `en` — quality translations. Ready-made ones live in the `I18N` dictionary of
   the old `archi.js` (`I18N.ru` / `I18N.en`) — look there first, translate what is missing.
3. **`common.php` only holds text used on at least two pages.** Page-specific text goes in
   the page file. If `common.php` looks like it needs a change, do not change it — repeat
   the string in `{slug}.php` instead (avoids parallel conflicts).
4. The **key structure must be identical** across all three locales (a missing key makes
   Laravel print the key itself, e.g. `catalog.hero.title`).
5. For text containing HTML use `{!! __('…') !!}`, but prefer splitting the string and
   using `{{ }}`.

---

## 6. CSS rules

### 6.1 File structure

`resources/css/pages/{slug}.css` — **only your page's** styles:

```css
/* Page CSS for "catalog". */
@layer components {
  .cat-chip     { @apply flex items-center gap-2 rounded-pill border border-black/10 px-4 py-2; }
  .cat-chip[data-on="true"] { @apply border-yellow bg-yellow; }
}
```

- **No `@import`** — `app.css` already imports you.
- **No `@theme`** — the tokens live in `app.css`.
- Write inside `@layer components { … }`.
- `@keyframes` may also live inside `@layer components` (verified, works).

### 6.2 Which approach to pick

Both approaches from HANDOFF.md §12 remain valid:

1. **Utilities in the markup** — for small pages with few repeated classes.
   `{slug}.css` stays empty. (login · register · cart · search · blog · sell ·
   business-register · business-onboarding-step1 · business-onboarding-step3 ·
   calculator were converted this way.)
2. **CSS moved to `@apply`, class names kept** — when dozens of classes repeat or the
   markup is built in JS. The old HTML's `<style type="text/tailwindcss">` block moves
   into `{slug}.css` almost verbatim. (calculator-detailed · specialist · specialists ·
   all business-profile-* are like this.)

The old page's `<style type="text/tailwindcss">` block **is already Tailwind** — you can
copy it directly, just keep the `@layer components { … }` wrapper.

### 6.3 Overriding a shared component

`app.css` comes **after** the page CSS, so at equal specificity the shared rule wins. To
override, **use higher specificity**:

```css
/* does NOT work — same specificity as .grid4, and app.css comes later */
.grid4 { @apply gap-3; }

/* works — 0,2,0 > 0,1,0 */
.sr .grid4 { @apply gap-3; }
```

### 6.4 Token table (old CSS variable → Tailwind)

| Old | Tailwind |
|---|---|
| `--yellow` `--yellow-line` `--sel-bg` | `bg-yellow` `bg-yellow-line` `bg-sel-bg` |
| `--black` (**#111111**, NOT #000) | `text-ink` / `bg-ink` |
| `--off-white` #efefef | `text-off-white` / `bg-off-white` |
| `--green` `--red` `--star` | `text-green` `text-red` `text-star` |
| `--gray-soft` `--gray-soft2` | `bg-gray-soft` `bg-gray-soft2` |
| `--radius` 4 / `--radius-md` 8 / `--radius-pill` | `rounded-ds` / `rounded-ds-md` / `rounded-pill` |
| `--text-primary/secondary/muted/faint` | `text-black/90` `/70` `/50` `/40` |
| `--border` / `--border-strong` / `--border-hover` | `border-black/10` `/14` `/35` |
| `'Inter'` | `font-sans` |
| `'Manrope'` (business pages) | `font-b2b` |

The spacing scale (4/8/12/16/20/24/28/32/40/48) matches Tailwind's default — `p-1`…`p-12`.
Translucent colors are not separate tokens; use the opacity modifier.

### 6.5 Traps

| Trap | Correct form |
|---|---|
| `line-height: normal` | **`leading-[normal]`** — `leading-normal` is 1.5, which is wrong |
| a **flex** element hidden via `el.hidden` in JS | `flex` beats the UA `[hidden]{display:none}` rule → add **`[&[hidden]]:hidden`** to the element (specificity 0,2,0) |
| `filter: brightness(.92)` | `hover:brightness-[.92]` |
| `transition: transform .25s ease` | `transition-transform duration-[250ms]` (if you need the exact easing, write plain CSS — it can be mixed with `@apply`) |
| fixed `width: 1440px` / `min-width: 1440px` | **never** — it breaks the navbar width. Use `w-full max-w-[1440px]`. |
| page shell built with `position: absolute` | move it to normal flow (a defect of the old `biznes-*` pages) |

---

## 7. JS rules

`resources/js/pages/{slug}.js` is the page module. `app.js` imports it dynamically **only**
when `<body data-page="{slug}">` is rendered (its own Vite chunk).

```js
// Page module for "catalog".
export default function init() {
  document.querySelectorAll('.cat-chip').forEach((c) =>
    c.addEventListener('click', (e) => {
      e.preventDefault();
      document.querySelectorAll('.cat-chip').forEach((x) => (x.dataset.on = 'false'));
      c.dataset.on = 'true';
    })
  );
}
init();
```

- The module loads after `<body>` exists (`@vite` scripts are `defer`) — no need to wait
  for `DOMContentLoaded`.
- **Do not duplicate shared code**: navbar/mega/search/language/cart → `shared/navbar.js`,
  the round cursor → `shared/cursor.js`.
- Never hardcode routes or text — pass them from Blade via `data-*`.

### 7.1 State pattern: `data-on` / `data-sel`

State classes like `.on` / `.active` / `.sel` / `.show` cannot be driven by Tailwind
utilities. Move them to a **`data-*` attribute**:

```html
<button class="fchip" data-on="true">{{ __('catalog.filter.all') }}</button>
<button class="fchip" data-on="false">{{ __('catalog.filter.repair') }}</button>
```
```css
.fchip                   { @apply border border-black/10 px-4 py-2; }
.fchip[data-on="true"]   { @apply border-yellow bg-yellow; }
```
```js
chips.forEach((x) => (x.dataset.on = 'false'));
chip.dataset.on = 'true';
```

For state driven from a parent use `group` + `group-data-[sel=true]:`.

> Exception: the **shared** components deliberately keep the old classes
> (`.lang.open`, `.mega-panel.open`, `.search-dropdown.on`, `.pcard .dots i.on`,
> `.reveal.in`, `.nav-item.active`) — leave them alone.

---

## 8. Images / assets

- Everything lives under `public/assets/` (64 files + `public/assets/fig/`).
- Markup **always** uses `/assets/...`:
  - `src="assets/logo.png"` → `src="/assets/logo.png"`
  - `url(assets/hero.jpg)` → `url(/assets/hero.jpg)`
- Do not add new images — everything is already copied. If a file is missing, copy it from
  the old project into `public/assets/` and mention it in your report.

---

## 9. What the shared CSS already provides (do not rewrite it)

`resources/css/app.css` → `@layer components`:

| Group | Classes |
|---|---|
| shell | `.wrap` (max 1440, px-28) · `.inner` (max 1384) |
| navbar row 1 | `.topbar` `.nav-row1` `.logo` `.search` + autocomplete (`.search-dropdown`, `.sd-*`) `.nav-menu` `.nav-icons` `.lang` `.lang-menu` `.nav-cart` `.cart-badge` `.signin` `.divider` `.btn-post` |
| navbar row 2 | `.nav-row2` `.nav-left` `.nav-item` (+`.catalog`, `.active`, `.mega-active`) `.nav-calc` |
| mega menu | `.mega-panel` `.mega-inner` `.mega-cats` `.mcat` `.mega-spec` `.mega-blog` `.mblog` |
| footer | `footer` `.foot-top` `.foot-logo` `.foot-products` `.foot-line` `.foot-cols` `.foot-col` `.foot-news` `.foot-bottom` `.foot-legal` `.foot-social` |
| grids | `.grid4` `.blog-grid` |
| section head | `.section` `.sec-head` `.sec-tag` `.sec-title` `.sec-more` |
| product card | `.pcard` + children |
| cursor | `.prod-cursor` `.hascur` `.cursing` |
| specialist card | `.scard` + children |
| blog card | `.post` + children |
| animation | `.reveal` `.reveal.in` `.reveal.d1–d3` |
| responsive | 1200 / 900 / 640 px + `hover:none` + `prefers-reduced-motion` (navbar/footer/cards only) |

All of this is **ready** — use the same class names in your page and write no CSS for them.
Page-specific responsive rules (hero, calculator, catalog gallery, …) go into your
`{slug}.css`.

### 9.1 About preflight (important difference)

The old project did **not** load Tailwind preflight. The project now uses the full
`@import "tailwindcss"`, so preflight **is active**. The `@layer base` block of `app.css`
compensates for the differences: `button{cursor:pointer}` · UA heading sizes
(`font-size: revert`) · UA border/background/radius of `input/select/textarea` (`revert`) ·
`a{text-decoration:none;color:inherit}` · `img{display:block;max-width:100%}`.

Even so, **compare before and after**: an element that had no CSS in the old page may look
different now. If you spot a difference, fix it explicitly in the page CSS, do not touch
`app.css`, and note it in your report.

---

## 10. Self-check (without a global build)

```powershell
# PHP is not on PATH:
$env:Path = "$HOME\.config\herd-lite\bin;$env:Path"

# 1) syntax
php -l resources/views/pages/{slug}.blade.php   # limited value for Blade
php -l lang/az/{slug}.php
php -l lang/ru/{slug}.php
php -l lang/en/{slug}.php

# 2) do all three locales share the same keys
php -r "$a=array_keys(include 'lang/az/{slug}.php'); $b=array_keys(include 'lang/ru/{slug}.php'); var_dump(array_diff($a,$b), array_diff($b,$a));"

# 3) leftovers from the old project (all must come back EMPTY)
Select-String -Path resources/views/pages/{slug}.blade.php -Pattern '\.html|src="assets/|href="assets/|data-archi'
```

Additional checklist:

- [ ] `<x-layout page="{slug}">` — `page` equals the slug
- [ ] every `href` uses `route()`, no `.html` left
- [ ] every `src`/`url()` starts with `/assets/...`
- [ ] every visible string goes through `__('…')`, the `az` value matches the old text
- [ ] the three lang files share the same key structure
- [ ] no `leading-normal` (the correct one is `leading-[normal]`)
- [ ] no fixed `width:1440px` / `min-width:1440px`
- [ ] `app.css` / `app.js` / `web.php` / `components/` **unchanged**
- [ ] file names, code, comments and keys are in English; Azerbaijani only in `lang/az/`
- [ ] no `npm run build` / `git commit`

---

## 11. What changed compared to the old project (deliberate)

| Old | New | Reason |
|---|---|---|
| `archi.js` navbar/footer injection (`data-archi="nav"`) | `<x-navbar/>` `<x-footer/>` Blade components | server-side render, SEO, works without JS |
| `archi.js` TreeWalker i18n (client) | Laravel `__()` (server) | 3 locales, correct `<html lang>`, dictionary split into files |
| navbar width differing per page | **one component = one width** | fixes the old defect (caused by per-page `width:1440px`) |
| "Sign in" → JS popup (`.lm-*`) | the `/login` page | the popup CSS was dead and a login page already existed |
| `archi-tw.js` browser build (CDN/local) | Vite + `@tailwindcss/vite` build | a real build step, preflight active |
| preflight disabled | preflight **active** + `@layer base` compensation | modern baseline; differences in §9.1 |
| `.lead` `.fcalc-*` `.lm-*` CSS | **removed** | unused on every page (dead code) |
| `vite.config.js` `bunny('Instrument Sans')` | **removed** | unused and required network access at build time |
| Azerbaijani file slugs and comments | English slugs and comments | one convention across the codebase; Azerbaijani only as `lang/az` values |
| `.sd-usta` class + hardcoded AZ demo data in `archi.js` | `.sd-master` class + `nav.sd_demo_*` translation keys | English class names; the search dropdown is now localized in all three locales |

---

## 12. Useful references

- Old project documentation: `C:/Users/mamed/OneDrive/Desktop/ARCHI/HANDOFF.md`
  (especially **§12 Tailwind migration** — token table, traps, Figma node IDs)
- Old shared files (reference): `archi.css` · `archi-tw.js` · `archi.js`
- Figma: fileKey `1VQNQO1hPMNJ657B1UH8SF` (archi-2). Node IDs are in HANDOFF.
- PHP is not on PATH: `$env:Path = "$HOME\.config\herd-lite\bin;$env:Path"`
