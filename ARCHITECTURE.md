# ARCHI — Laravel architecture (contract for page agents)

> This document is **binding**. Page agents work in parallel; if everyone does not
> follow the same rules the result will not merge. When in doubt, look here — do not guess.
> Wave 1 shipped 23 pages; wave 2 adds the 13 Figma-only pages of §3 (routes, stubs, CSS/JS
> placeholders and every cross-page link are already wired — see §3.1).

- **Working directory:** `C:/Users/mamed/ARCHI-laravel` (worktree, `laravel` branch)
- **Reference (READ ONLY):** `C:/Users/mamed/OneDrive/Desktop/ARCHI` — the old static
  project, verified 1:1 against Figma. **Never write there.**
- **Goal:** pixel parity + 3 languages (az / ru / en) + a clean Laravel structure.

---

## 0. GOLDEN RULES (do not break)

| # | Rule |
|---|---|
| 1 | **Change only YOUR files.** Do not step outside the "Your files" list below. |
| 2 | **Do NOT touch `resources/css/app.css` or `resources/views/components/`.** They hold the design system (§4); a dozen agents writing there at once would conflict. Need a variant the components do not have? Report it — do not fork it. |
| 3 | **Do NOT touch `resources/js/app.js`.** The registry already covers all 36 slugs. |
| 4 | **Do NOT touch `routes/web.php`.** Every route already exists (all 36 pages). |
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
│     ├─ components/                ← shared Blade components — DO NOT TOUCH
│     │  ├─ layout · navbar · footer · login-modal · section-head · pcard · scard · post
│     │  ├─ ui/                     ← design system primitives  <x-ui.*>   (§4.1–4.8)
│     │  └─ cabinet/                ← business cabinet shell   <x-cabinet.*> (§4.9)
│     └─ pages/{slug}.blade.php     ← your file
└─ vite.config.js                   ← do not touch
```

---

## 2. Using the layout

Every page **must** be wrapped in `<x-layout>`:

```blade
<x-layout page="catalog" :title="__('catalog.title')">

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
| `footer` | bool | Render `<x-footer/>`? Default `true`. **Only `business-register` passes `:footer="false"`** — its Figma frame is `Navbar 140 + auth-page 1160`, and `biznes-qeydiyyat.html` is the single reference page without a `data-archi="footer"` mount. Do not use it anywhere else. |

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

**Wave 2 — 13 pages that exist only in Figma** (no old `.html` reference; build them from
the node, not from a file):

| Figma node | URL | Route name | View | data-page (slug) |
|---|---|---|---|---|
| `831:7539` | `/about` | `about` | `pages.about` | `about` |
| `831:7823` | `/blog/article` | `blog.article` | `pages.blog-article` | `blog-article` |
| `831:11493` | `/specialist/owner` | `specialist.owner` | `pages.specialist-owner` | `specialist-owner` |
| `1054:9643` | `/specialist/onboarding` | `specialist.onboarding` | `pages.specialist-onboarding` | `specialist-onboarding` |
| `831:11186` | `/specialist/cabinet` | `specialist.cabinet` | `pages.specialist-cabinet` | `specialist-cabinet` |
| `831:11867` | `/specialist/cabinet/portfolio` | `specialist.cabinet.portfolio` | `pages.specialist-cabinet-portfolio` | `specialist-cabinet-portfolio` |
| `831:12139` | `/specialist/cabinet/services` | `specialist.cabinet.services` | `pages.specialist-cabinet-services` | `specialist-cabinet-services` |
| `831:12428` | `/specialist/cabinet/schedule` | `specialist.cabinet.schedule` | `pages.specialist-cabinet-schedule` | `specialist-cabinet-schedule` |
| `831:12727` | `/specialist/cabinet/reviews` | `specialist.cabinet.reviews` | `pages.specialist-cabinet-reviews` | `specialist-cabinet-reviews` |
| `831:12996` | `/specialist/cabinet/notifications` | `specialist.cabinet.notifications` | `pages.specialist-cabinet-notifications` | `specialist-cabinet-notifications` |
| `831:13282` | `/specialist/cabinet/security` | `specialist.cabinet.security` | `pages.specialist-cabinet-security` | `specialist-cabinet-security` |
| `1105:21043` | `/business/onboarding/step-2` | `business.onboarding.step2` | `pages.business-onboarding-step2` | `business-onboarding-step2` |
| `1105:21287` | `/business/onboarding/step-4` | `business.onboarding.step4` | `pages.business-onboarding-step4` | `business-onboarding-step4` |

**Links that never existed** (`#`, empty `<a>`) → keep `href="#"`. `haqqimizda.html` is no
longer one of them: the About page now exists, so the navbar and footer link `route('about')`.

---

### 3.1 Wiring the wave-2 pages

The 13 pages are not islands. Where an existing page had a dead `href="#"` that Figma
shows pointing at one of them, it is **already rewired** — do not undo it:

| From | To |
|---|---|
| navbar "Haqqımızda" · footer "Haqqımızda" | `route('about')` |
| navbar mega-blog teaser cards | `route('blog.article')` |
| `blog` hero image · hero "Read more" button · every `<x-post>` | `route('blog.article')` |
| `home` blog section `<x-post>` ×4 | `route('blog.article')` |
| `business-onboarding-step1` "Yadda saxla və davam et" | `route('business.onboarding.step2')` |

**Reached only after login — deliberately not linked from any public page:**
`specialist-owner` · `specialist-onboarding` · the seven `specialist-cabinet-*` pages.
The public `specialist` page shows a visitor's view and has no owner affordance in Figma,
so nothing links to them from outside. They link to **each other**, and those links are the
page agent's job:

- `specialist-owner` — the yellow owner banner's "Profili redaktə et" and every section's
  "Redaktə et" → the matching cabinet tab (`specialist.cabinet`,
  `specialist.cabinet.portfolio`, `specialist.cabinet.services`, `specialist.cabinet.reviews`).
- `specialist-onboarding` — the "Profili tamamla — 4 addım" checklist rows are the hub:
  row 2 (İxtisas və şəhər) → `specialist.cabinet`, row 3 (Portfolio) →
  `specialist.cabinet.portfolio`, row 4 (İş qrafiki və qiymət) →
  `specialist.cabinet.schedule`. The banner button "Profili tamamla" stays on the page.
- every `specialist-cabinet-*` — `viewHref="{{ route('specialist.owner') }}"` on the shell
  (see §4.9.1); the sidebar handles the rest.

> Footer "Usta ol" keeps pointing at `route('register')` — a logged-out visitor registers
> first; onboarding is the post-registration screen. Intentional, do not "fix" it.

**The business onboarding funnel has THREE steps, not four.** Every frame's stepper reads
`Əsas məlumat → Əlaqə → İlk məhsul`. The route names are historical (they follow the old
`biznes-tamamlama-addimN.html` file names), so they do **not** line up with the step numbers:

| Route | Funnel step | Screen |
|---|---|---|
| `business.onboarding.step1` | 1 | Əsas məlumat / Şirkət məlumatları |
| `business.onboarding.step2` | 2 | Əlaqə — node `1105:21043` (h1 still reads "Şirkət məlumatları") |
| `business.onboarding.step3` | 3 | İlk məhsul — last step, its submit lands on `business.profile` |
| `business.onboarding.step4` | — | **not a step.** Node `1105:21287` is the step-3 screen with the category listbox open |

So the chain is `step1 → step2 → step3 → business.profile`. `step2`'s "next" must target
`business.onboarding.step3`. `step4` is scaffolded but has no place in the funnel — its two
deltas against step 3 (open category listbox; progress `75% → 100%` instead of the shipped
`67% → 100%`) belong in `business-onboarding-step3`, not in a duplicate page.

---
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

```
resources/views/components/
├─ layout · navbar · footer · login-modal      ← page shell (never call navbar/footer yourself)
├─ section-head · pcard · scard · post         ← content components
├─ ui/                                         ← DESIGN SYSTEM primitives  <x-ui.*>
│  button · eyebrow · breadcrumbs · badge · chip · toggle · field · input ·
│  textarea · select · checkbox · alert · card · progress · stars · modal
└─ cabinet/                                    ← business-cabinet shell  <x-cabinet.*>
   shell · header · sidebar · card · field · save-bar · row
```

**The design system is the single source of truth.** A component owns *tone, radius,
state and hover*; the **caller** supplies *geometry and typography* through
`$attributes->merge`. That split exists because the same yellow button is 42 px tall in
the cabinet and 54 px on the auth pages — one size scale would be a lie. Tailwind
utilities sit in a later layer than `@layer components`, so `class="h-11 px-3.5 text-xs"`
always beats the component defaults.

All of the CSS lives in **`resources/css/app.css`**, in two blocks:
`DESIGN SYSTEM — UI PRIMITIVES` and `BUSINESS CABINET SHELL`. No hex literal appears in
either — every color and radius comes from `@theme` (§4.0).

---

### 4.0 Tokens the components are built from

| Token | Value | Utilities | Drives |
|---|---|---|---|
| `--color-yellow` | `#fdfe00` | `bg-yellow` | primary button · progress fill · active chip rule |
| `--color-yellow-line` | `#ffe600` | `border-yellow-line` | selected chip / nav border · link underline |
| `--color-sel-bg` | `#fffde0` | `bg-sel-bg` | selected chip / active sidebar row |
| `--color-ink` | `#111111` | `text-ink` `bg-ink` | dark button · save bar · checkbox tick box |
| `--color-ink-alt` | `#141414` | `text-ink-alt` | editorial type (`.post h3`, `.sec-more p`, `.sr-more`) |
| `--color-ok` | `#229653` | `text-ok` `bg-ok` | switch ON · "published" chip · verified badge |
| `--color-ok-soft` | `#e9f6ed` | `bg-ok-soft` | background of those chips |
| `--color-warn` / `-soft` | `#c88200` / `#fff4db` | `text-warn` `bg-warn-soft` | low-stock badge |
| `--color-danger` | `#d33c32` | `text-danger` `border-danger/80` `bg-danger/8` | delete · logout · danger zone |
| `--color-success` / `-soft` | `#0a7a14` / `#eafce9` | `text-success` `bg-success-soft` | success message box |
| `--color-error` / `-soft` | `#b4322c` / `#fdeaea` | `text-error` `bg-error-soft` | error message box |
| `--color-neutral-soft` | `#f0f1f3` | `bg-neutral-soft` | "hidden" badge |
| `--color-green` | `#00a613` | `text-green` `bg-green/12` | saved dot · verified tint |
| `--radius-ds-sm` / `-ds` / `-ds-md` / `-pill` | 3 / 4 / 8 / 100 px | `rounded-ds-sm` … | progress bar · every DS box · logo box · switch |
| `--font-sans` / `--font-b2b` | Inter / Manrope | `font-sans` `font-b2b` | site / business pages |

**Never write a hex that a token already covers.** If you find one in an old page,
replace it with the utility and say so in your report.

---

### 4.1 `<x-ui.button>`

```blade
<x-ui.button variant="primary" class="cab-btn-save">{{ __('…save') }}</x-ui.button>
<x-ui.button variant="outline" class="cab-btn-view" :href="route('business.profile')">…</x-ui.button>
<x-ui.button variant="primary" type="submit" class="h-[54px] rounded-none text-lg font-semibold hover:brightness-[.93]">…</x-ui.button>
```

| Prop | Default | Description |
|---|---|---|
| `variant` | `primary` | `primary` (yellow) · `dark` (ink) · `outline` (white + black/20) · `ghost` (borderless, danger text) · `danger` (red outline) · `on-ink` (for the dark save bar) |
| `href` | `null` | renders an `<a>` instead of a `<button>` |
| `type` | `button` | `<button type>`; ignored when `href` is set |
| `hover` | `true` | `false` → no transition and no hover (`business-profile-company` is the only page that needs it) |
| `icon` / `iconClass` | `null` / `size-5` | icon rendered before the label |

> The base is **square-cornered at `rounded-ds` (4 px)**. Pages whose button is a sharp
> rectangle (login · register · sell) must pass `rounded-none`.

Repeating cabinet geometries live in app.css and compose with the tone:
`.cab-btn-view` `.cab-btn-add` `.cab-btn-edit` `.cab-btn-del` `.cab-btn-save` `.cab-btn-cancel`.

### 4.2 `<x-ui.eyebrow>` — the yellow rule + uppercase label

```blade
<x-ui.eyebrow :label="__('login.head.tag')" />
<x-ui.eyebrow variant="b2b" :label="__('business-register.head.tag')" />
```

| `variant` | Rule | Label | Used by |
|---|---|---|---|
| *(default)* | `h-1 w-8` rounded 2 px, yellow | 13 px / 1.4 px tracking / black-55 | login · register · sell · login modal |
| `lg` | same | 14 px | blog |
| `flat` | `h-0.5 w-8` square | 13 px / .5 px / black | search · specialists · specialist · catalog |
| `b2b` | `h-1 w-8` rounded-ds, yellow-line | 12 px semibold / 1 px / black-50 | business-register · calculator |
| `kicker` | `h-0.5 w-8` square | 12 px medium / 1 px / black-55 | business-profile |

Never type the label in CAPS — `uppercase` is in the CSS.

### 4.3 `<x-ui.breadcrumbs>`

```blade
<x-ui.breadcrumbs :items="[
    ['label' => __('common.home'), 'href' => route('home')],
    ['label' => __('common.catalog'), 'href' => '#'],
    ['label' => __('catalog.crumb_current')],
]" />
```

| Prop | Default | Description |
|---|---|---|
| `items` | `[]` | `['label' => …, 'href' => …]`; the **last** item is the current page (`.cur`) and is never a link |
| `sep` | `/` | separator glyph (the cabinet passes a translated one) |

Canonical: `gap-2`, 13 px, `leading-[normal]`, links `black/50`, current `font-medium black/90`.

### 4.4 `<x-ui.badge>` — status chip (non-interactive)

```blade
<x-ui.badge tone="ok" size="md" dot>{{ __('…status.published') }}</x-ui.badge>
```

| Prop | Values |
|---|---|
| `tone` | `ok` · `warn` · `muted` · `green` |
| `size` | `md` (14/8 px pad · 13 px) · `sm` (10/4 · 11 px) · `xs` (8/2 · 10 px) |
| `dot` | `false` — the 8 px dot inherits the tone color via `bg-current` |

### 4.5 `<x-ui.chip>` — selectable chip with a checkbox box

```blade
<x-ui.chip :label="__('…languages.az')" :on="true" size="sm" tick="svg" />
```

| Prop | Default | Description |
|---|---|---|
| `label` | `null` | also written to `data-label`, which reserves the **semibold** width so toggling never reflows the row |
| `on` | `false` | selected state (`data-on`) |
| `size` | `md` | `sm` (13 px, 9/14/12 pad) · `md` (14 px, 10/16/14 pad) |
| `box` | `true` | render the 18 px checkbox square |
| `tick` | `css` | `css` = rotated-border tick · `svg` = borderless box + inlined check glyph |
| `role` | `checkbox` | pass `radio` for single-select groups |

### 4.6 `<x-ui.toggle>` — switch

```blade
<x-ui.toggle :on="$row['on']" size="sm" :aria-label="__('…toggle')" />
```

| Prop | Default | Description |
|---|---|---|
| `on` | `false` | ON state |
| `size` | `md` | `md` 44×24 (knob 20 @2) · `sm` 40×22 (knob 16 @3) |
| `tone` | `ok` | `ok` → `--color-ok` track · `yellow` → `--color-yellow` |
| `hover` | `true` | brightness hover |

### 4.7 Form: `<x-ui.field>` · `<x-ui.input>` · `<x-ui.textarea>` · `<x-ui.select>` · `<x-ui.checkbox>`

Two families, chosen with `variant`:

| | *(default)* — consumer | `b2b` — business |
|---|---|---|
| label | 14 px medium black/70 | 13 px semibold ink |
| control | square, `border-black/20`, `px-4 py-3.5`, 16 px, `focus:border-black` | `rounded-ds`, `border-black/14`, `px-4 py-[13px]`, 14 px ink, `focus:border-ink` |
| used by | login · register · sell · cart | business-register · onboarding · cabinet |

```blade
<x-ui.field :label="__('login.form.identifier_label')" for="loginIdentifier">
  <x-ui.input id="loginIdentifier" :placeholder="__('…')" required />
</x-ui.field>

<x-ui.field variant="b2b" :label="__('…legal_name_label')">
  <x-ui.input variant="b2b" :value="__('…legal_name_value')" />
</x-ui.field>

<x-ui.select :placeholder="__('register.form.select_placeholder')" :options="__('register.cities')" />
<x-ui.checkbox required>{{ __('login.form.remember') }}</x-ui.checkbox>
```

`<x-ui.field>` also takes `tag="p"` for controls that cannot be labelled (the onboarding
city combobox). The custom, JS-driven dropdowns are **not** `<x-ui.select>` — they are
listbox widgets and stay page-local.

### 4.8 Small pieces

| Component | Props | Notes |
|---|---|---|
| `<x-ui.alert>` | `tone` (`ok` · `error`) · `on` | hidden until `data-on="true"` |
| `<x-ui.card>` | — | `rounded-ds` + `border-black/10` + white; padding/gap from the caller |
| `<x-ui.progress>` | `fill` (`w-[184px]`) | 6 px bar, `rounded-ds-sm`, yellow fill |
| `<x-ui.stars>` | `count` (5) · `icon` | replaces the `@for` star loops |
| `<x-ui.modal>` | `dialog` · `closeLabel` · `on` | overlay + close button only; the dialog box is the caller's |

---

### 4.9 Business cabinet — `<x-cabinet.*>`

The six cabinet pages (`business-profile-company` · `-contact` · `-showrooms` ·
`-products` · `-notifications` · `-security`) shipped ~90 % identical CSS under six
prefixes. One shell renders all of it:

```blade
<x-layout page="business-profile-products" :title="__('business-profile-products.title')" bodyClass="bg-gray-soft2">
  <x-cabinet.shell ns="business-profile-products" active="products" class="bpp-page">

    <x-cabinet.card layout="row" gap="gap-3.5"
        :title="__('business-profile-products.list.title')"
        :desc="__('business-profile-products.list.summary')">
      <x-slot:action>
        <x-ui.button variant="primary" class="cab-btn-add">{{ __('…list.add') }}</x-ui.button>
      </x-slot:action>
      …rows…
    </x-cabinet.card>

    <x-cabinet.save-bar ns="business-profile-products" />

  </x-cabinet.shell>
</x-layout>
```

| Component | Props |
|---|---|
| `<x-cabinet.shell>` | `ns` · `active` · `navItems` · `heading` · `viewHref` · `strong` · `hover` · `progressFill` |
| `<x-cabinet.header>` | `ns` · `heading` · `viewHref` · `hover` |
| `<x-cabinet.sidebar>` | `ns` · `active` · `strong` · `fill` |
| `<x-cabinet.card>` | `title` · `desc` · `layout` (`stack`/`row`) · `tag` · `gap` · `pad` + `<x-slot:action>` |
| `<x-cabinet.field>` | `label` · `for` · `full` · `tag` + `<x-slot:badge>` |
| `<x-cabinet.save-bar>` | `ns` · `saved` · `hover` |
| `<x-cabinet.row>` | — (the gray list row) |

**`ns` is the page's own translation namespace** — no shared cabinet lang file exists and
none is needed. The shell reads:

```
{ns}.crumbs.panel · {ns}.crumbs.current · {ns}.crumbs.sep | {ns}.crumbs.separator
{ns}.heading
{ns}.status.published | {ns}.status          {ns}.status.view_profile | {ns}.view_profile
{ns}.nav.company … {ns}.nav.security · {ns}.nav.showrooms_count · {ns}.nav.products_count
{ns}.progress.label · {ns}.progress.value · {ns}.progress.note | {ns}.progress.hint
{ns}.save.unsaved · {ns}.save.saved | {ns}.save.saved_alert · {ns}.save.cancel · {ns}.save.save
```

Both key spellings are accepted (the six lang files were written independently), so **no
lang file changes** when a page adopts the shell.

**JS contract of the save bar:** `.cab-save-bar` (`data-saved` flips the dot to green) ·
`.cab-save-bar .msg` (`aria-live`) · `data-saved-message` · `[data-save]` / `[data-cancel]`.

#### 4.9.1 The specialist cabinet reuses this shell — it is not a second one

The seven `specialist-cabinet-*` frames are the business cabinet with different rows: same
breadcrumbs + 34 px title, same green "Dərc olunub" badge + outline "Profilə bax ↗" button,
same 264 px sidebar with a "Profil tamlığı" progress block, same cards, same dark save bar.
**Build them with `<x-cabinet.*>`. Do not fork the shell and do not add a `spec-*` prefix.**

Only two things were business-specific, and both are now props:

- **the sidebar rows** — `<x-cabinet.sidebar>` took a hardcoded six-row array. It now
  accepts `items`, and `<x-cabinet.shell>` forwards it as `navItems`. The business default
  is unchanged, so the six business pages pass nothing.
- **the "view profile" target** — `<x-cabinet.header>` defaults to `route('business.profile')`.
  A specialist page **must** pass `viewHref`.

```blade
@php
    $specNav = [
        ['key' => 'main',          'route' => 'specialist.cabinet'],
        ['key' => 'portfolio',     'route' => 'specialist.cabinet.portfolio',     'count' => 'portfolio_count'],
        ['key' => 'services',      'route' => 'specialist.cabinet.services',      'count' => 'services_count'],
        ['key' => 'schedule',      'route' => 'specialist.cabinet.schedule'],
        ['key' => 'reviews',       'route' => 'specialist.cabinet.reviews',       'count' => 'reviews_count'],
        ['key' => 'notifications', 'route' => 'specialist.cabinet.notifications'],
        ['key' => 'security',      'route' => 'specialist.cabinet.security'],
    ];
@endphp
<x-layout page="specialist-cabinet" :title="__('specialist-cabinet.title')" bodyClass="bg-gray-soft2">
  <x-cabinet.shell ns="specialist-cabinet" active="main" :nav-items="$specNav"
                   :view-href="route('specialist.owner')">
    …cards…
    <x-cabinet.save-bar ns="specialist-cabinet" />
  </x-cabinet.shell>
</x-layout>
```

> **All seven agents must copy that `$specNav` array verbatim** — same seven keys, same
> order, only `active` differs. The keys double as lang keys, so a typo silently prints the
> key. Because `ns` is the page's own namespace, each of the seven lang files repeats the
> seven `nav.*` labels, the three `nav.*_count` values, `crumbs.*`, `heading`, `status.*`
> and `progress.*` — that duplication is deliberate (it is what keeps the six business
> pages conflict-free in parallel).

`progressFill` is a width utility, so the 78 % bar is `progressFill="w-[184px]"`-style —
measure it in Figma rather than reusing the business value.

**Legacy deltas.** Where the six pages disagreed, the shared class took the majority value
and the minority page keeps a one-line, higher-specificity override in its page CSS,
marked `/* legacy delta */`. They are listed per page in the refactor map; the largest set
belongs to `business-profile-company`, which was ported without hover states and without
`leading-[normal]`.

---

### 4.10 How to change X site-wide

| I want to change… | Edit exactly this |
|---|---|
| the radius of **every** DS box | `@theme --radius-ds` in `app.css` |
| the button radius only | `.ui-btn { … rounded-ds }` |
| the primary (yellow) button color | `@theme --color-yellow` |
| the switch ON color | `@theme --color-ok` |
| the status-chip background | `@theme --color-ok-soft` |
| every destructive color | `@theme --color-danger` |
| the input border / focus color | `.ui-control` and `.ui-control-b2b` |
| the label size of a b2b form | `.ui-label-b2b` |
| the breadcrumb separator color | `.ui-crumbs .sep` |
| the cabinet sidebar width | `.cab-snav { … w-[264px] }` |
| the cabinet sticky offset under the navbar | `.cab-snav { … top-[158px] }` |
| the cabinet page gutter | `.cab-page { … px-7 }` |
| the default cabinet card padding | the `pad` default in `components/cabinet/card.blade.php` |
| the save-bar look | `.cab-save-bar` |
| the cabinet nav order / a new tab | `components/cabinet/sidebar.blade.php` (`$items`) + one key per lang file |
| the chip tick glyph | `.ui-chip[data-on="true"] .cbox::after` |

---

### 4.11 `<x-section-head>` — section head (`.sec-head`)

```blade
<x-section-head
    :tag="__('home.products.tag')"
    :title="__('home.products.title')"
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

### 4.12 `<x-pcard>` — product card (`.pcard`)

```blade
<x-pcard
    :cat="__('home.sale.cat_tiles')"
    :name="__('home.sale.name_tile_matte')"
    now="23.90 ₼" old="45.99 ₼" off="-48%"
    rate="4.6" :reviews="__('home.sale.reviews_1876')"
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

### 4.13 `<x-scard>` — specialist card (`.scard`)

```blade
<x-scard
    bg="#f5fbff"
    :role="__('home.specialists.role_tiler')"
    rate="4.9" :reviews="__('home.specialists.reviews_416')"
    :name="__('home.specialists.name_1')"
    :exp="__('home.specialists.exp_12')"
    :proj="__('home.specialists.proj_320')" />
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

### 4.14 `<x-post>` — blog card (`.post`)

```blade
<x-post
    :time="__('blog.posts.time_1')"
    :title="__('blog.posts.title_1')"
    :excerpt="__('blog.posts.excerpt_1')"
    :href="route('blog')"
    class="rounded-ds max-[1200px]:min-w-[260px]" />
```

| Prop | Default | Description |
|---|---|---|
| `href` | `null` | link (null → `<a>` without href) |
| `img` | `/assets/blog.png` | image |
| `time` `title` `excerpt` | `null` | text values |
| `read` | `__('common.read_arrow')` | bottom link text |

### 4.15 `<x-navbar>` / `<x-footer>`

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
    'title' => 'ARCHİ — Kataloq',

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

The **semantic status tokens** (`--color-ok` `--color-ok-soft` `--color-warn` `--color-danger`
`--color-success` `--color-error` `--color-neutral-soft` `--color-ink-alt` `--radius-ds-sm`)
are listed in **§4.0** — use those instead of the hex literals `#229653` `#e9f6ed` `#d33c32`
`#0a7a14` `#eafce9` `#b4322c` `#fdeaea` `#f0f1f3` `#141414` `#fff4db` `#c88200`.

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

`resources/js/pages/{slug}.js` is the page module. It must `export default` an `init`
function and **must not call it itself** — `app.js` imports every page module statically
(one single Vite bundle) and calls only the `init` matching `<body data-page="{slug}">`.

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
```

- The bundle loads after `<body>` exists (`@vite` scripts are `defer`) — no need to wait
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
| shell | `.wrap` (max 1440, px-28) · `.inner` (max 1384) · `.wrap-narrow` (max 1240, px-20 — cart / calculator-detailed) |
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
| filter sidebar + sort dropdown (catalog · specialists) | `.fside` `.fside-scroll` `.fside-apply-sep` `.fside-apply` `.fside-box` (checked state comes from the row: `[data-on="true"] > .fside-box`) `.fsort` `.fsort-menu` — the skeleton only; per-page sizes/tints stay in the page CSS |
| **design system** (§4.1–4.8) | `.ui-btn` + 6 tones · `.ui-eyebrow` · `.ui-crumbs` · `.ui-badge` · `.ui-chip` · `.ui-toggle` · `.ui-progress` · `.ui-field` `.ui-label` `.ui-control` `.ui-label-b2b` `.ui-control-b2b` `.ui-check` `.ui-check-row` · `.ui-alert` · `.ui-card` · `.ui-modal-overlay` `.ui-modal-close` |
| **business cabinet** (§4.9) | `.cab-page` `.cab-head` `.cab-head-left/right` `.cab-title` `.cab-body` `.cab-main` · `.cab-snav` `.cab-snav-item` `.cab-snav-prog` · `.cab-card` `.cab-card-head` `.cab-card-head-row` `.cab-card-head-txt` `.cab-card-title` `.cab-card-desc` `.cab-card-sub` · `.cab-field` `.cab-field-row` `.cab-field-full` · `.cab-row` · `.cab-btn-view/add/edit/del/save/cancel` · `.cab-save-bar` |
| animation | `.reveal` `.reveal.in` `.reveal.d1–d3` |
| responsive | 1200 / 900 / 640 px + `hover:none` + `prefers-reduced-motion` (navbar/footer/cards only) |

All of this is **ready** — use the same class names in your page and write no CSS for them.
Page-specific responsive rules (hero, calculator, catalog gallery, …) go into your
`{slug}.css`.

> The home hero is deliberately **not** wrapped in `.wrap`: `<div class="hero"><div class="inner hero-grid">`
> matches the old `index.html` 1:1, so below 1384px the hero is full-bleed while the sections
> below it keep their 28px gutter. Intended — do not "fix" it into `.wrap`.

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
