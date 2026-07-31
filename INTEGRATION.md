# Integrating ARCHI into a real Laravel application

How to lift this front end into an existing (or new) Laravel app that has controllers,
models, a database and authentication. Written for a senior developer doing it in one
sitting.

**Read [`ARCHITECTURE.md`](ARCHITECTURE.md) alongside this file.** It is the contract for
the code itself — component props, translation-key pattern, CSS tokens, JS conventions.
This document does not repeat any of it; it only explains how to move it.

---

## Table of contents

1. [What this project is](#1-what-this-project-is)
2. [What the target app must already have](#2-what-the-target-app-must-already-have)
3. [What to copy](#3-what-to-copy)
4. [What NOT to copy](#4-what-not-to-copy)
5. [Wiring it up](#5-wiring-it-up)
6. [Build setup — Vite, Tailwind, the two registries](#6-build-setup--vite-tailwind-the-two-registries)
7. [Migration path to a real backend](#7-migration-path-to-a-real-backend)
8. [i18n wiring](#8-i18n-wiring)
9. [Build & dev commands](#9-build--dev-commands)
10. [Tailwind 4 specifics](#10-tailwind-4-specifics)
11. [Gotchas](#11-gotchas)
12. [Integration checklist](#12-integration-checklist)

---

## 1. What this project is

A **presentation-only** Laravel 13 application. Its entire purpose is the markup.

| | |
|---|---|
| Framework | Laravel 13 (`laravel/framework ^13.8`, PHP `^8.3`) |
| PHP code | `app/` contains exactly one class: `App\Providers\AppServiceProvider`, empty. **No controllers, no models, no migrations, no auth, no database, no jobs, no policies, no tests.** |
| Routes | `routes/web.php` — 36 named GET routes, all closures, plus `/lang/{locale}` |
| Views | 36 page templates in `resources/views/pages/`, one per route |
| Components | 8 shell/content components + 16 `<x-ui.*>` design-system primitives + 7 `<x-cabinet.*>` shell components — all **anonymous** Blade components (no PHP classes) |
| Styles | Tailwind CSS 4 via `@tailwindcss/vite`. One entry `resources/css/app.css` (~950 lines: `@theme` tokens, `@layer base`, `@layer components`) which `@import`s 36 per-page files |
| JS | One entry `resources/js/app.js` (~90 lines) that statically imports 3 shared modules + 36 page modules and dispatches on `<body data-page>`. Single bundle, no code splitting. |
| i18n | `lang/{az,ru,en}/` — 39 files each: 36 page namespaces + `common.php`, `nav.php`, `footer.php`. Default locale `az`. |
| Assets | `public/assets/` — 82 files (66 at top level + `public/assets/fig/`), referenced as root-relative `/assets/...` |
| Build output | `public/build/` — one CSS (~350 KB unminified-ish) + one JS (~74 KB). Gitignored. |

Every page renders hardcoded demo content pulled from the translation files. Forms are
inert: they have no `action`, no `method`, no `@csrf`, and their JS calls
`e.preventDefault()`.

---

## 2. What the target app must already have

The front end assumes only three things beyond a stock Laravel install:

1. **A session on every page request.** The locale is stored in `session('locale')`. Any
   route that renders an ARCHI view must run inside the `web` middleware group.
2. **Vite** with `laravel-vite-plugin` (the standard Laravel 12/13 skeleton already has it).
3. **The site served from the domain root** (`https://example.com/`, not
   `https://example.com/shop/`) — every image path is root-relative. See §11.

Nothing else. No auth package is required to render the pages; the login/register/cabinet
screens are markup only.

---

## 3. What to copy

Copy these into the target app. Paths are identical on both sides unless noted.

```
resources/views/pages/            36 page templates
resources/views/components/       layout, navbar, footer, login-modal, section-head,
                                  pcard, scard, post, ui/ (16), cabinet/ (7)
resources/css/app.css             Tailwind entry: @theme tokens + base + shared components
resources/css/pages/              36 per-page stylesheets
resources/js/app.js               entry + page registry
resources/js/shared/              navbar.js, cursor.js, login-modal.js
resources/js/pages/               36 per-page modules
lang/az/  lang/ru/  lang/en/      39 files each
public/assets/                    82 image/icon files (incl. assets/fig/)
routes/web.php                    the 36 view routes + /lang/{locale}  — MERGE, see §5.1
vite.config.js                    the two plugins + input array          — MERGE, see §6
package.json                      devDependencies only                   — MERGE, see §6
```

On Windows PowerShell, from the target app's root:

```powershell
$src = 'C:/Users/mamed/ARCHI-laravel'
Copy-Item "$src/resources/views/pages"      resources/views/ -Recurse -Force
Copy-Item "$src/resources/views/components" resources/views/ -Recurse -Force
Copy-Item "$src/resources/css"              resources/      -Recurse -Force
Copy-Item "$src/resources/js"               resources/      -Recurse -Force
Copy-Item "$src/lang/az","$src/lang/ru","$src/lang/en" lang/ -Recurse -Force
Copy-Item "$src/public/assets"              public/         -Recurse -Force
```

> If the target app already owns `resources/css/app.css` or `resources/js/app.js`,
> **do not overwrite them** — see §6.3.

### 3.1 `package.json` devDependencies

Merge into the target's `devDependencies` (versions as shipped and verified here):

```json
"@tailwindcss/vite": "^4.0.0",
"laravel-vite-plugin": "^3.1",
"tailwindcss": "^4.0.0",
"vite": "^8.0.0",
"concurrently": "^9.0.1"
```

`concurrently` is only used by the `composer dev` script; drop it if the target has its
own dev runner. There are **no runtime npm dependencies** — no framework, no jQuery, no
Alpine. All JS is hand-written vanilla ES modules.

### 3.2 `composer.json`

Nothing to merge. This project adds no Composer packages beyond a stock skeleton
(`laravel/framework`, `laravel/tinker`, plus `pail`/`pint`/`collision` in dev).

---

## 4. What NOT to copy

Everything below is stock Laravel skeleton, or local state. Keep the target app's own.

| Do not copy | Why |
|---|---|
| `app/` | Only an empty `AppServiceProvider` |
| `bootstrap/`, `config/`, `artisan`, `public/index.php` | Untouched skeleton. **Exception:** check `config/app.php` locale keys, §8.1 |
| `composer.json` / `composer.lock` / `vendor/` | Nothing project-specific |
| `.env`, `.env.example` | Only the locale + session lines matter, §5.3 |
| `storage/`, `public/build/`, `node_modules/` | Generated |
| `routes/console.php` | Empty stub |
| `ARCHITECTURE.md`, `README.md` | Copy `ARCHITECTURE.md` if the team will keep editing pages — it is the working contract. `README.md` describes this repo, not the target. |

---

## 5. Wiring it up

### 5.1 Routes

`routes/web.php` here defines a file-local helper and 36 closures:

```php
if (! function_exists('archiView')) {
    // setLocale must run inside the request (here), not at file-load time:
    // the session is not available while the route file is being parsed.
    function archiView(string $view)
    {
        app()->setLocale(session('locale', config('app.locale')));

        return view($view);
    }
}

Route::get('/catalog', fn () => archiView('pages.catalog'))->name('catalog');
// … 35 more
```

Two ways to bring these over:

**(a) Fast path — paste as-is.** Append the route block to the target's `routes/web.php`
(or `require __DIR__.'/archi.php'` a copied file from inside the `web` group). Works
immediately. Costs you `php artisan route:cache` — see §11.

**(b) Recommended — drop `archiView` on day one.** The helper exists only because there
is no middleware in this project. In a real app, replace it with a locale middleware and
use plain `view()`:

```php
// app/Http/Middleware/SetLocale.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        app()->setLocale(session('locale', config('app.locale')));

        return $next($request);
    }
}
```

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->web(append: [\App\Http\Middleware\SetLocale::class]);
})
```

Then every route becomes `Route::view('/catalog', 'pages.catalog')->name('catalog')`, or a
controller action (§7.1). Delete `archiView` entirely — nothing else calls it.

### 5.2 The locale switch route

```php
Route::get('/lang/{locale}', function (string $locale) {
    if (in_array($locale, ['az', 'ru', 'en'], true)) {
        session(['locale' => $locale]);
    }

    return redirect()->back();
})->name('lang');
```

Keep the route **name** `lang` — `components/navbar.blade.php` links `route('lang', $code)`.
Two hardening changes worth making in a real app:

- `redirect()->back()` with no `Referer` lands on `/`. Be explicit:
  `back(fallback: route('home'))` (the helper's third argument is `$fallback`).
- A GET request that mutates session state is prefetch-unsafe. If you care, convert to
  `Route::post('/lang', ...)` and change the navbar's three `<a>` tags to a form. That is
  the only place in the codebase that links it.

### 5.3 Session & environment

The pages need `SESSION_DRIVER` set to anything that works in the target app. This project
ships `file` (`config/session.php` still defaults to `database`, the `.env` overrides it) so
a fresh clone runs with no migrations. **A real app will normally use `database` or `redis`
— that is fine and requires no change here**, as long as the sessions table exists.

Set the locale defaults in `.env`:

```
APP_LOCALE=az
APP_FALLBACK_LOCALE=az
```

(`config/app.php` falls back to `'en'` via `env(...)`, so without these lines the first
request before any `/lang/...` click renders English.)

---

## 6. Build setup — Vite, Tailwind, the two registries

### 6.1 `vite.config.js`

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
```

Three things matter:

- **`tailwindcss()` must be in `plugins`.** Tailwind 4 has no `tailwind.config.js` and no
  PostCSS step in this setup — the Vite plugin is the whole build. There is no
  `postcss.config.js` in this project; do not add one for Tailwind.
- **`input`** must include both entries. `<x-layout>` calls
  `@vite(['resources/css/app.css', 'resources/js/app.js'])`; if you rename either file,
  change both places.
- The `server.watch.ignored` line stops the dev server reloading in a loop on Blade's
  compiled-view cache. Keep it.

> The Laravel skeleton's `fonts: [bunny(...)]` option was deliberately removed — it hits
> the network at build time. Fonts are loaded from Google Fonts in `layout.blade.php`
> instead (Inter 400–700 + Manrope 400/500).

### 6.2 The single-bundle registry (important)

There is **no dynamic import and no per-page bundle**. `resources/js/app.js` imports all
36 page modules statically so Rollup emits one file, then dispatches:

```js
import catalog from './pages/catalog.js';
// … 35 more
const pages = { 'catalog': catalog, /* … */ };
pages[document.body.dataset.page]?.();
```

Each page module `export default`s an `init` function and never calls it itself.
Mirror-wise, `resources/css/app.css` `@import`s all 36 page stylesheets at the top of the
file (CSS requires `@import` before any other rule).

**Consequence: adding or removing a page is a three-file edit.** New page `foo` needs

1. `resources/views/pages/foo.blade.php`, `resources/css/pages/foo.css`,
   `resources/js/pages/foo.js`, `lang/{az,ru,en}/foo.php`
2. an `@import "./pages/foo.css";` line in `app.css`
3. an `import` + a `pages` map entry in `app.js`
4. a route whose view is `pages.foo` and whose `<x-layout page="foo">` slug matches

Miss (3) and the page renders styled but dead — `pages[...]?.()` fails silently by design.

If the target app is large enough that a 74 KB always-loaded bundle matters, switch the
registry to dynamic imports; nothing else in the codebase depends on the static form:

```js
const pages = {
    'catalog': () => import('./pages/catalog.js'),
    // …
};
pages[document.body.dataset.page]?.().then((m) => m.default());
```

### 6.3 If the target app already has `app.css` / `app.js`

Do not overwrite. Instead:

- **CSS** — keep the target's `app.css` as the Vite input and append
  `@import "./archi.css";` after its own `@import "tailwindcss";`, where `archi.css` is
  this project's `app.css` with its first `@import "tailwindcss";` line removed. Verify the
  `@source` lines still resolve: `app.css` declares `@source "../views";` and
  `@source "../js";` **relative to the file's own directory**, so a file moved out of
  `resources/css/` must have them adjusted.
- **JS** — keep the target's `app.js` and add `import './archi/app.js';` to it, having
  moved this project's `js/` tree under `resources/js/archi/`. The relative
  `./shared/...` and `./pages/...` imports move with it and keep working.
- **Blade component name collisions** (`ui.button`, `ui.card`, `layout`, …) are the real
  risk. If the target already owns those names, put the tree at
  `resources/views/archi/components/` and register a namespace instead of copying into
  `resources/views/components/`:

  ```php
  // AppServiceProvider::boot()
  Blade::anonymousComponentPath(resource_path('views/archi/components'), 'archi');
  ```

  Every tag then becomes `<x-archi::ui.button>`, `<x-archi::layout>`, `<x-archi::pcard>`.
  This is a mechanical find-and-replace across the 36 pages and the 7 cabinet components —
  do it once, up front, not halfway through.

---

## 7. Migration path to a real backend

Do this section **after** the pages render unchanged in the target app. Verify parity
first, then start replacing.

### 7.1 Closure routes → controllers, one section at a time

The 36 routes fall into six natural groups. Convert a whole group at a time so the
sidebars and cross-links stay consistent:

| Group | Routes | Suggested controller |
|---|---|---|
| Public catalog | `home` `catalog` `product` `search` | `CatalogController` |
| Specialists (public) | `specialists` `specialist` | `SpecialistController` |
| Content | `blog` `blog.article` `about` | `BlogController`, `PageController` |
| Auth & commerce | `login` `register` `cart` `sell` | your auth package + `CartController` |
| Business cabinet | `business.register`, `business.onboarding.step1–4`, `business.profile*` (7) | `Business\OnboardingController`, `Business\ProfileController` |
| Specialist cabinet | `specialist.owner` `specialist.onboarding` `specialist.cabinet*` (7) | `Specialist\CabinetController` |
| Utility | `lang` | keep as-is (§5.2) |

**Keep every route name identical.** The views and components resolve links through
`route('business.profile.products')` etc.; renaming a route breaks navbar, footer,
breadcrumbs and both cabinet sidebars at once. `ARCHITECTURE.md` §3 is the full route table.

```php
// before
Route::get('/product', fn () => archiView('pages.product'))->name('product');

// after
Route::get('/product/{product:slug}', [ProductController::class, 'show'])->name('product');
```

Note that `product`, `specialist`, `blog.article` and `business.profile.*` are currently
**parameterless** — they render one hardcoded example. Adding a route parameter is the
first real change, and it is a breaking one: every `route('product')` call site (the
`<x-pcard>` default `href`, the mega menu, the home and catalog grids, `data-url-product`
attributes read by JS) must start passing a model. Grep for `route('product')` before you
change the signature.

Route names likely to **collide** with an auth package (Breeze / Fortify / Jetstream):
`login`, `register`, `home`, `logout`. Laravel's `Authenticate` middleware redirects
unauthenticated users to `route('login')` — if you keep this project's static `login` view
under that name, that redirect lands on a form that does nothing. Wire the real auth
routes to these Blade views (§7.5) rather than keeping both.

### 7.2 Passing real data into the components

**Every component's props are documented in `ARCHITECTURE.md` §4** (`<x-ui.*>` §4.1–4.8,
`<x-cabinet.*>` §4.9, `<x-section-head>` §4.11, `<x-pcard>` §4.12, `<x-scard>` §4.13,
`<x-post>` §4.14). Do not duplicate that table anywhere — read it there.

The mechanical change per page is: a hardcoded component call becomes a loop over a
collection. E.g. `pages/catalog.blade.php` today:

```blade
<x-pcard :cat="__('catalog.grid.cat_tiles')" :name="__('catalog.grid.name_1')"
         now="23.90 ₼" old="45.99 ₼" off="-48%" rate="4.6"
         :reviews="__('catalog.grid.reviews_1')" img="/assets/prod-kafel.png" />
```

becomes

```blade
@foreach ($products as $product)
    <x-pcard
        :cat="$product->category->name"
        :name="$product->name"
        :now="$product->price_formatted"
        :old="$product->old_price_formatted"
        :off="$product->discount_label"
        :rate="$product->rating"
        :reviews="trans_choice('catalog.reviews', $product->reviews_count)"
        :img="$product->image_url"
        :href="route('product', $product)" />
```

Three rules that keep the design system intact while you do this:

1. **Props carry pre-formatted strings, not models.** `now="23.90 ₼"` is a string; the
   components do no formatting, no `number_format`, no currency logic. Put that in an
   accessor or a view model — not in the Blade template, and never inside the component.
2. **Geometry stays with the caller.** The components own tone/radius/state; sizes arrive
   through `$attributes->merge` (`class="h-11 px-3.5"`). Backend work should not touch
   class strings at all.
3. **Do not add props to a shared component.** If real data needs a variant that does not
   exist, that is a design-system change — make it once in
   `resources/views/components/` + `resources/css/app.css`, not a fork in one page.

### 7.3 Replacing the demo content

The demo content lives in **two** places, and they need opposite treatment:

- **`lang/*/{slug}.php`** — most visible strings. Split them: UI chrome (labels, buttons,
  headings, empty states, validation copy) **stays** in the lang files; content that
  becomes a database row (product names, specialist names, blog titles/excerpts, prices,
  review counts, showroom addresses) gets deleted from all three locales once the model
  feeds it. Deleting from only one locale is worse than leaving it — see §8.
- **Blade literals** — image paths (`/assets/prod-kafel.png`), star ratings, prices, counts
  and the demo arrays declared in `@php` blocks at the top of several pages. Grep for
  `@php` in `resources/views/pages/` to find them; they are the seams where a controller
  variable slots in with the least edit.

Also demo-only, in JS: the search-autocomplete dataset is declared in
`components/navbar.blade.php` (images) plus `lang/*/nav.php` (`nav.sd_demo_*` keys) and
consumed by `resources/js/shared/navbar.js`. Replace it with a real endpoint —
`shared/navbar.js` is the only consumer.

### 7.4 Query-string state

Three pages already read the request and are the model for the rest:

```php
// pages/search.blade.php
$q = request()->query('q');
$t = request()->query('tab');
// pages/register.blade.php
$r = request()->query('role');
```

When these become controller-backed, move the reads into the controller and pass `$q`,
`$tab`, `$role` in as view data. Keep the same variable names — the Blade further down
branches on them (`@if ($tab !== 'all' …)`).

### 7.5 Forms

Every form is currently inert. There are five:

| File | id / selector | Today |
|---|---|---|
| `pages/login.blade.php` | `#loginForm` | `resources/js/pages/login.js` calls `e.preventDefault()` |
| `pages/register.blade.php` | `#regForm` | same |
| `pages/sell.blade.php` | `#sellForm` | same, plus client-side step logic |
| `components/login-modal.blade.php` | `#lmForm` | `shared/login-modal.js` |
| `components/footer.blade.php` | `.form` (newsletter) | `onsubmit="return false"` inline |

To make one real:

1. Add `method="POST" action="{{ route('...') }}"` and **`@csrf`** — there is not a single
   `@csrf` in the codebase today, so this is easy to forget.
2. Remove the `e.preventDefault()` from the matching page module (or keep it and submit via
   `fetch` — the modal is the natural candidate).
3. Repopulate on validation failure: `<x-ui.input :value="old('email')">`. The inputs pass
   attributes through `$attributes->merge`, so `old()` and `name=` need no component change.
4. Show errors with the existing `<x-ui.alert tone="error" :on="$errors->any()">` — it is
   already in the design system (§4.8) and hidden until `data-on="true"`.
5. The cabinet save bar has a JS contract worth reusing rather than replacing:
   `.cab-save-bar` with `data-saved`, `[data-save]` / `[data-cancel]` buttons, and an
   `aria-live` message node. Point `[data-save]` at a real submit and keep the rest.

---

## 8. i18n wiring

### 8.1 Layout

```
lang/
├─ az/   ← default; the AZ value is the exact Figma/original text
├─ ru/
└─ en/
```

39 files per locale: `common.php`, `nav.php`, `footer.php`, and one `{slug}.php` per page,
where the file name equals the route's page slug. Keys are **always English**; only values
are localized. Nested arrays, addressed with dots:

```php
// lang/az/catalog.php
return [
    'title' => 'ARCHİ — Kataloq',
    'hero'  => ['tag' => 'Bütün məhsullar', 'title' => 'Kataloq'],
];
```

```blade
{{ __('catalog.hero.title') }}
```

Convention (full rules in `ARCHITECTURE.md` §5):

- `{slug}.{section}.{key}` for page text; `nav.*` / `footer.*` / `common.*` for shared text.
- `common.php` holds only strings used on **two or more** pages.
- **The key structure must be identical across all three locales.** A missing key makes
  Laravel print the key itself (`catalog.hero.title`) into the page — silently, in
  production. Verify after any edit:

  ```powershell
  php -r "$a=array_keys(include 'lang/az/catalog.php'); $b=array_keys(include 'lang/en/catalog.php'); var_dump(array_diff($a,$b), array_diff($b,$a));"
  ```

  (top level only — write a recursive check if you edit deep nodes often).
- The cabinet shell reads its chrome from the page's own namespace via the `ns` prop, and
  accepts two spellings of several keys (`{ns}.status.published` **or** `{ns}.status`) —
  that is deliberate, see `ARCHITECTURE.md` §4.9. Do not "normalize" it without touching
  all thirteen cabinet lang files.

### 8.2 Adding a locale

1. `cp -r lang/en lang/tr` and translate the values (keep every key).
2. `routes/web.php` — add the code to the whitelist:
   `in_array($locale, ['az', 'ru', 'en', 'tr'], true)`.
3. `resources/views/components/navbar.blade.php` — add to `$langLabels`:
   `['az' => 'AZ', 'ru' => 'RUS', 'en' => 'ENG', 'tr' => 'TR']`. The switcher renders from
   that array, so nothing else changes.
4. If the locale is RTL, budget real work: the CSS uses `left`/`right` and
   `margin-left: calc(50% - 50vw)` on the footer, not logical properties.

### 8.3 The `<html lang>` attribute

`layout.blade.php` emits `<html lang="{{ app()->getLocale() }}">`. That is the only place
the locale reaches the document, so getting §5.1 right (middleware runs before the view)
is what makes it correct.

---

## 9. Build & dev commands

```bash
composer install
npm install
cp .env.example .env      # then set APP_LOCALE=az, APP_FALLBACK_LOCALE=az
php artisan key:generate
```

Development — two processes:

```bash
npm run dev               # Vite dev server, HMR, writes public/hot
php artisan serve         # http://127.0.0.1:8000
```

or `composer dev`, which runs `artisan serve` + `artisan pail` + `npm run dev` under
`concurrently`.

Production:

```bash
npm run build             # → public/build/  (one CSS, one JS, plus manifest.json)
php artisan view:cache
php artisan config:cache
```

`public/build/` is **gitignored** — the deploy pipeline must run `npm run build`. If it
does not, `@vite(...)` throws `Unable to locate file in Vite manifest`.

> On this machine PHP is not on `PATH`:
> `$env:Path = "$HOME\.config\herd-lite\bin;$env:Path"`

---

## 10. Tailwind 4 specifics

**There is no `tailwind.config.js`.** Configuration is CSS. `resources/css/app.css` is the
single source of truth and has this shape, in this order (CSS demands `@import` first):

```css
@import "tailwindcss";
@import "./pages/home.css";        /* × 36 */
@source "../views";
@source "../js";

@theme { /* design tokens */ }
@layer base { /* preflight compensation */ }
@layer components { /* .wrap, navbar, footer, cards, .ui-*, .cab-* */ }
```

- **`@theme` is the design system.** Every color, radius and font family is a token; the
  components contain no hex literals. Changing `--color-yellow` restyles every primary
  button, progress fill and active chip site-wide. `ARCHITECTURE.md` §4.0 lists the tokens
  and §4.10 is a "I want to change X → edit exactly this" table. Use it before hand-editing
  any component.
- **`@source`** tells Tailwind where to scan for class names. Both paths are relative to
  `resources/css/`. If you relocate `app.css`, fix them or the build silently drops classes.
- **Utilities beat components.** Page markup passes `class="h-11 px-3.5 text-xs"` to
  design-system components and expects it to win; that works because utilities live in a
  later layer than `@layer components`. Do not move component CSS out of that layer.
- **Cascade order inside `@layer components`:** page CSS is imported *before* the shared
  rules, so at equal specificity the **shared** rule wins. A page overriding a shared class
  must raise specificity (`.sr .grid4`, not `.grid4`). This surprises people.
- **Preflight is active** (the predecessor static project had it disabled). The `@layer base`
  block compensates: `button{cursor:pointer}`, `font-size: revert` on headings,
  `revert` on input/select/textarea borders, `a{text-decoration:none;color:inherit}`,
  `img{display:block;max-width:100%}`. If the target app disables or replaces preflight,
  expect visual drift.
- Per-page CSS files contain **no** `@theme` and **no** `@import`, only
  `@layer components { … }` blocks using `@apply`.

---

## 11. Gotchas

**`/assets/...` is root-relative, everywhere.** 82 files, referenced from Blade `src=`,
CSS `url()` and a few JS strings. Serving the app from a subdirectory breaks all of them
at once. Either mount at the domain root, or do a project-wide replacement to
`{{ asset('assets/...') }}` (Blade) plus `url(...)` rewrites in the 36 page stylesheets —
budget an hour and re-screenshot every page. Assets are *not* Vite-processed: they are
static files under `public/`, not imported modules, so `npm run build` does not fingerprint
them and long-lived caching needs a CDN rule.

**`php artisan route:cache` breaks every page — and does it silently.** The command
*succeeds*: Laravel 13 serializes closure routes fine via `laravel-serializable-closure`.
But a cached route table means `routes/web.php` is never loaded, and `archiView()` is a
file-local helper defined in that file — so every request then dies with
`Call to undefined function archiView()`. All 36 routes return 500 after a completely
green build, which is the worst possible failure mode for a deploy pipeline. Fix it by
taking §5.1(b): a locale middleware plus `Route::view(...)` deletes `archiView` entirely,
and non-closure routes cache without complaint. Until then, keep `route:cache` out of the
deploy script. `view:cache`, `config:cache` and `event:cache` are all fine and recommended
in CI; `view:cache` in particular is worth running, since 36 pages × dozens of anonymous
components is a lot of first-request compilation. Note that `view:cache` requires the `lang/` files to be present at build time
only insofar as they are read at render time — it does not bake in translations, so a
locale change never needs a cache clear.

**`SESSION_DRIVER`.** `config/session.php` defaults to `database`; this project's `.env`
forces `file` so a fresh clone runs with zero migrations. Copy the `.env` value only if you
know why — a real app with a `sessions` table should stay on `database`.

**The `data-page` dispatch is silent.** `pages[document.body.dataset.page]?.()` — a typo in
`<x-layout page="...">`, or a page module missing from the registry in `app.js`, produces a
page with no JavaScript and **no console error**. The `page` prop must equal the slug,
which must equal the key in the `pages` map, which must equal the filename in
`resources/js/pages/`. When a page "stops working" after a merge, check that chain first.

**`[hidden]` loses to `flex`.** A flex element hidden via `el.hidden = true` in JS stays
visible, because Tailwind's `display:flex` outranks the user-agent `[hidden]{display:none}`
rule. The fix used throughout (`register`, `search`, `cart`) is to add
`[&[hidden]]:hidden` to the element — specificity 0,2,0. If you add a new toggled flex
container, add that utility or it will not hide.

**`leading-normal` is not `line-height: normal`.** Tailwind's `leading-normal` is 1.5. The
codebase uses `leading-[normal]` deliberately, ~everywhere. Do not "clean it up."

**Fonts come from Google Fonts over the network** (`fonts.googleapis.com`, in
`layout.blade.php`). A strict CSP, an air-gapped environment, or a privacy requirement means
self-hosting Inter + Manrope and editing that one `<link>`.

**No `@csrf` anywhere.** See §7.5. The moment a form becomes a real POST, Laravel will
419 until the token is added.

**`<x-navbar>` and `<x-footer>` are rendered by `<x-layout>`.** Never call them from a page.
The footer breaks out of its container with `margin-left: calc(50% - 50vw)` and must stay a
direct child of `<body>`.

**Never set a fixed `width: 1440px`.** It was a defect of the predecessor project and it
breaks the navbar. Use `w-full max-w-[1440px]` (`.wrap` / `.inner` already do).

---

## 12. Integration checklist

- [ ] Files copied per §3; component-name collisions resolved (§6.3)
- [ ] `vite.config.js` has `laravel({ input: [css, js] })` **and** `tailwindcss()`
- [ ] `npm install` picks up Tailwind 4 + Vite 8; no `postcss.config.js` added for Tailwind
- [ ] `npm run build` produces one CSS + one JS in `public/build/`
- [ ] `APP_LOCALE=az`, `APP_FALLBACK_LOCALE=az`; session driver works
- [ ] All 36 routes registered inside the `web` middleware group, names unchanged
- [ ] Locale middleware in place; `archiView` deleted
- [ ] `/lang/az`, `/lang/ru`, `/lang/en` switch the whole site and return to the same page
- [ ] `<html lang>` reflects the active locale
- [ ] Every page loads with no console error and its JS module runs (§11, `data-page`)
- [ ] `/assets/...` resolve (check a CSS `url()` background, not just an `<img>`)
- [ ] `route:cache` removed from the deploy script, or routes converted to controllers
- [ ] Auth route names (`login`, `register`, `home`) reconciled with the auth package
- [ ] Screenshot diff against this project on all 36 pages, in all 3 locales, at 1440 / 1200 / 900 / 640 px
