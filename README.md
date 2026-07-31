# ARCHI

Front end for the ARCHI construction-materials marketplace: 23 static pages built from the
Figma design, served by Laravel and Blade, styled with Tailwind, localised in Azerbaijani,
Russian and English.

This is a **presentation-only** project. There is no database, no authentication and no
business logic — every route renders a Blade view with demo content. The pages are the
deliverable; a backend can be attached later without touching the markup.

## Stack

| Layer     | Choice                                                       |
| --------- | ------------------------------------------------------------ |
| Framework | Laravel 13 (routes + Blade only — no controllers, no models)  |
| Build     | Vite 8 via `laravel-vite-plugin`                              |
| Styles    | Tailwind CSS 4 (`@tailwindcss/vite`), plus per-page CSS files |
| Templates | Blade anonymous components (`<x-layout>`, `<x-pcard>`, …)     |
| i18n      | Laravel translation files — `az` (default), `ru`, `en`        |

Locale is chosen by `GET /lang/{locale}`, which stores it in the session (file driver) and
redirects back. `routes/web.php` applies it with `app()->setLocale()` on every request.

## Running it

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Development — two processes, or `composer dev` to run both plus the log viewer:

```bash
npm run dev               # Vite dev server with hot reload
php artisan serve         # http://127.0.0.1:8000
```

Production build:

```bash
npm run build             # compiles to public/build/
php artisan serve
```

No migrations, no seeding, no `.sqlite` file — session, cache and queue use the `file` and
`sync` drivers, so a fresh clone runs immediately after `key:generate`.

## Folder structure

```
routes/web.php                 all 23 routes + the /lang/{locale} switch
resources/views/
  components/                  layout, navbar, footer, pcard, scard, post,
                               section-head, login-modal — the shared design system
  pages/                       one Blade file per page (home, catalog, product, …)
resources/css/
  app.css                      Tailwind entry, design tokens, shared component CSS
  pages/                       per-page stylesheet, loaded for that page only
resources/js/
  app.js                       entry; picks the page module from <body data-page>
  pages/                       per-page behaviour
  shared/                      navbar, login modal, cursor
lang/{az,ru,en}/               one file per page + common, nav, footer
public/assets/                 images and icons exported from Figma
```

App code is deliberately almost empty: `app/` holds only `AppServiceProvider`.

## Conventions

**Read [`ARCHITECTURE.md`](ARCHITECTURE.md) before changing anything.** It is the contract
for this codebase and covers the golden rules, the route table, the Blade component APIs,
the translation-key pattern, the CSS token table and the JS state conventions.

Porting this front end into a real backend application? See
[`INTEGRATION.md`](INTEGRATION.md) — what to copy, what to leave behind, and the migration
path from closure routes and demo content to controllers and models.

Dropping it into an existing Laravel app takes one command — `node install.mjs <target-app-path>`
copies the views, styles, scripts, translations and assets straight from this repo
(INTEGRATION.md §3).
