# Hardcoded strings → DB translations report

Migration of remaining hardcoded user-facing strings to the DB-backed `t()` system.
28 new translation rows inserted via `App\Models\Translation::updateOrCreate` (table `translations`, count 2482 → 2510).

Format: `group.key | az | ru | en | file:line where used`

## Blade / UI (navbar, 404, specialist, orders, portfolio, security)

| key | az | ru | en | used in |
|---|---|---|---|---|
| nav.menu_aria | Menyu | Меню | Menu | resources/views/components/navbar.blade.php:119 |
| nav.mobile_nav_aria | Mobil naviqasiya | Мобильная навигация | Mobile navigation | resources/views/components/navbar.blade.php:126 |
| nav.close_menu_aria | Menyunu bağla | Закрыть меню | Close menu | resources/views/components/navbar.blade.php:129 |
| errors.404_tag_1 | Kafel & metlax | Плитка и метлах | Tile & mosaic | resources/views/errors/404.blade.php:21 |
| errors.404_tag_2 | Boya & emal | Краска и эмаль | Paint & enamel | resources/views/errors/404.blade.php:21 |
| errors.404_tag_3 | Usta tap | Найти мастера | Find a specialist | resources/views/errors/404.blade.php:21 |
| errors.404_tag_4 | Təmir kalkulyatoru | Калькулятор ремонта | Repair calculator | resources/views/errors/404.blade.php:21 |
| errors.404_tag_5 | Sifarişlərim | Мои заказы | My orders | resources/views/errors/404.blade.php:21 |
| specialist.contact | Əlaqə saxla | Связаться | Get in touch | resources/views/pages/specialist.blade.php:108 |
| business-orders.card.unit_short | əd | шт | pcs | resources/views/pages/business-orders.blade.php:49 |
| business-order-detail.items.shelf | Anbar: :shelf rəf | Склад: полка :shelf | Warehouse: shelf :shelf | resources/views/pages/business-order-detail.blade.php:108 |
| specialist-cabinet-portfolio.tile.status_pending | Yoxlanılır | На проверке | Under review | resources/views/pages/specialist-cabinet-portfolio.blade.php:72 |

## JS strings exposed via data-* attributes rendered with t()

| key | az | ru | en | data attr (blade) → JS reader |
|---|---|---|---|---|
| common.error_generic | Xəta baş verdi | Произошла ошибка | An error occurred | layout.blade.php body `data-err-generic` → `document.body.dataset.errGeneric` (business-product-edit.js:96, business-inventory.js:51, business-orders.js:21, business-profile-products.js:22, product.js:240, home.js:167 fallback) |
| common.error_network | Şəbəkə xətası. | Ошибка сети. | Network error. | layout.blade.php body `data-err-network` → `document.body.dataset.errNetwork` (product.js:245) |
| home.lead.error | Sorğu göndərilmədi. Yenidən cəhd edin. | Не удалось отправить запрос. Попробуйте снова. | Request could not be sent. Please try again. | home.blade.php:328 `data-l-error` → home.js:167 `form.dataset.lError` |
| specialist.contact_no_phone | Telefon nömrəsi əlavə edilməyib | Номер телефона не указан | Phone number not provided | specialist.blade.php:108 `data-no-phone` → specialist.js:27 `btn.dataset.noPhone` |
| specialist.book.mail_subject | ARCHI: Mesaj - | ARCHI: Сообщение - | ARCHI: Message - | specialist.blade.php:215 `data-l-subject` → specialist.js (mailto) |
| specialist.book.mail_name | Ad: | Имя: | Name: | specialist.blade.php:215 `data-l-name` → specialist.js (mailto) |
| specialist.book.mail_phone | Telefon: | Телефон: | Phone: | specialist.blade.php:215 `data-l-phone` → specialist.js (mailto) |
| specialist.book.mail_spec_id | Mütəxəssis ID: | ID специалиста: | Specialist ID: | specialist.blade.php:215 `data-l-spec-id` → specialist.js (mailto) |
| specialist-cabinet-security.sessions.load_error | Sessiyalar yüklənmədi. | Не удалось загрузить сессии. | Failed to load sessions. | specialist-cabinet-security.blade.php `data-load-error` → specialist-cabinet-security.js:52 |

Note: `specialist-cabinet-security.sessions.this_device` and `sessions.logout` already existed in the DB; the blade now exposes them via `data-this-device` / `data-logout-text` spans, and the JS reads those instead of its old hardcoded language-ternary fallbacks (`This device` / `Sign out` / `Bu cihaz` / etc. removed from specialist-cabinet-security.js).

## Controllers (JSON messages returned to JS)

| key | az | ru | en | used in |
|---|---|---|---|---|
| product.review_exists | Bu məhsul üçün artıq rəy yazmısınız. | Вы уже оставили отзыв для этого товара. | You have already reviewed this product. | app/Http/Controllers/ReviewController.php:39 |
| product.review_submitted | Rəyiniz göndərildi və təsdiq gözləyir. | Ваш отзыв отправлен и ожидает подтверждения. | Your review has been submitted and is awaiting approval. | app/Http/Controllers/ReviewController.php:53 |
| specialist-cabinet-portfolio.msg.image_missing | Yeni portfolio şəkli tapılmadı. | Новое изображение портфолио не найдено. | New portfolio image not found. | app/Http/Controllers/Cabinet/SpecialistCabinetController.php:105 |
| specialist-cabinet-portfolio.msg.saved | Portfolio yadda saxlanıldı. | Портфолио сохранено. | Portfolio saved. | app/Http/Controllers/Cabinet/SpecialistCabinetController.php:115 |
| specialist-cabinet-services.msg.saved | Xidmətlər yadda saxlanıldı. | Услуги сохранены. | Services saved. | app/Http/Controllers/Cabinet/SpecialistCabinetController.php:148 |
| specialist-cabinet-schedule.msg.end_after_start | İş günlərində bitmə vaxtı başlama vaxtından sonra olmalıdır. | В рабочие дни время окончания должно быть позже времени начала. | On working days, the end time must be after the start time. | app/Http/Controllers/Cabinet/SpecialistCabinetController.php:165 |
| specialist-cabinet-schedule.msg.saved | İş qrafiki yadda saxlanıldı. | Рабочий график сохранён. | Work schedule saved. | app/Http/Controllers/Cabinet/SpecialistCabinetController.php:186 |

## Intentionally left (with reason)

- `resources/js/pages/sell.js:102` `d.lCondUsed || 'İşlənmiş'` and `:174` `d.lServerError || 'Xəta baş verdi. Yenidən cəhd edin.'` — primary path already reads translated values from `sell.blade.php` data attributes (`data-l-cond-used`, `data-l-server-error` rendered via `t()`); the literals are defensive fallbacks only.
- `resources/views/pages/specialist.blade.php` `t('specialist.book.*', [], 'literal')` and `sell.blade.php:37` `t('sell.form.server_error', [], 'literal')` — already wrapped in `t()` (pre-existing migration pattern; keys resolve from DB). Not bare literals.
- Emoji/glyph icons (📦 ✓ ✕ ▾ 🎉 📷 etc.), price/currency formatting (`₼`), tile sizes (`30×30`), and brand `alt="ARCHI"` / example placeholders (`ARCHI15`, `email@example.com`) — not translatable UI copy.
- `lang/*/validation.php` — framework validation, left untouched per instructions.
