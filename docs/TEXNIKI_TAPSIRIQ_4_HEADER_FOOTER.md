# ARCHI Marketplace — Texniki Tapşırıq

## Bölmə 4: Header (Navbar) və Footer

> Bu sənəd backend development üçün yazılıb. Claude ilə implementasiya ediləcək.
> Tərcümə yanaşması: Bölmə 2, Bənd 0-a bax — JSON format, `solution-forest/filament-translate-field`.
> Bütün statik mətnlər `translations` cədvəlindən (Bölmə 3, Bənd 5).

---

## 1. Ümumi Yanaşma

Header və footer-dəki bütün naviqasiya elementləri admin panelindən idarə olunmalıdır. Statik hardcoded data olmayacaq — bütün menyu elementləri, linklər, mega-dropdown məzmunu DB-dən gələcək.

---

# HEADER (NAVBAR)

## 2. Header Strukturu — Mövcud Frontend

Header 2 sətirdən və 3 mega-dropdown paneldən ibarətdir:

```
┌─────────────────────────────────────────────────────────────────────┐
│ Row 1: [Logo] [Axtarış] [Dil] [❤] [🛒 badge] [│] [Giriş] [+ Elan]│
├─────────────────────────────────────────────────────────────────────┤
│ Row 2: [☰ Kataloq ▼] [Mütəxəssislər ▼] [Bloq ▼] [Haqqımızda] [B2B]│  [🧮 Kalkulyator]
└─────────────────────────────────────────────────────────────────────┘
          ↓ dropdown          ↓ dropdown       ↓ dropdown
    ┌─────────────┐    ┌──────────────┐   ┌──────────────┐
    │ Mega Catalog │    │ Mega Spec    │   │ Mega Blog    │
    └─────────────┘    └──────────────┘   └──────────────┘
```

---

## 3. Naviqasiya Menyu Sistemi

### 3.1 Cədvəl: `menu_items`

Bütün header və footer menyuları bir cədvəldən idarə olunur.

| Sahə | Tip | Qeyd |
|------|-----|------|
| `id` | bigint (PK) | |
| `parent_id` | bigint (nullable, FK → menu_items.id) | NULL = əsas menyu, dolu = child element |
| `location` | enum | Menyu yeri: `header_main`, `header_mega_catalog`, `header_mega_specialists`, `header_mega_blog`, `footer` |
| `label` | json | Tərcümə olunan menyu mətni: `{"az": "Kataloq", "ru": "Каталог", "en": "Catalog"}` |
| `url` | string (nullable) | Link URL-i (məs. `/catalog`, `/specialists`) |
| `route_name` | string (nullable) | Laravel route adı (məs. `catalog`, `specialists`) — URL əvəzinə istifadə oluna bilər |
| `icon` | string (nullable) | İkon faylı (SVG adı, məs. `icon-menu.svg`) |
| `image` | string (nullable) | Şəkil (mega-dropdown kartları və blog kartları üçün) |
| `description` | json (nullable) | Tərcümə olunan açıqlama mətni (mega-dropdown kartlarında alt yazı) |
| `has_dropdown` | boolean (default: false) | Dropdown/mega-panel var? |
| `is_clickable` | boolean (default: true) | Klik oluna bilər? (footer parent-ləri üçün `false`) |
| `open_in_new_tab` | boolean (default: false) | Yeni tab-da açılsın? |
| `css_class` | string (nullable) | Əlavə CSS class (məs. `catalog`, `nav-calc`) |
| `badge_text` | json (nullable) | Badge mətni: `{"az": "Yeni", ...}` (opsional) |
| `sort_order` | integer (default: 0) | Sıralama |
| `is_active` | boolean (default: true) | Aktiv/deaktiv |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### 3.2 Əlaqələr

```php
class MenuItem extends Model
{
    use HasTranslations;

    public array $translatable = ['label', 'description', 'badge_text'];

    public function parent(): BelongsTo { return $this->belongsTo(MenuItem::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(MenuItem::class, 'parent_id')->ordered(); }

    public function scopeLocation($q, $loc) { return $q->where('location', $loc); }
    public function scopeRoots($q) { return $q->whereNull('parent_id'); }
    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeOrdered($q) { return $q->orderBy('sort_order'); }

    public function getResolvedUrlAttribute(): ?string
    {
        if ($this->route_name) return route($this->route_name);
        return $this->url;
    }
}
```

---

## 4. Header Row 1 — Üst Sətir

### 4.1 Logo

- Şəkil: `/assets/logo-archi-black.png` (131.58×50px)
- Link: Ana səhifə (`/`)
- **Admin idarəetməsi:** `settings` cədvəlindən — `site_logo` (fayl yolu), `site_name` (SEO üçün)

### 4.2 Axtarış (Search)

**Mövcud frontend:** Autocomplete axtarış — minimum 2 simvol daxil edildikdə dropdown açılır.

**Dropdown bölmələri:**

| Bölmə | İkon | Göstərilən data | Maks. say |
|-------|------|----------------|-----------|
| Sürətli axtarış (suggest) | 🔍 | Axtarış təklifləri (matched text bold) | 4 |
| Məhsullar | — | Thumbnail + ad + kateqoriya + qiymət | 3 |
| Ustalar | — | Avatar (initials) + ad + ixtisas + reytinq | 2 |
| "Bütün nəticələrə bax (N)" | — | Axtarış səhifəsinə link | 1 |

**Backend API endpoint:**

| Metod | URL | Təsvir |
|-------|-----|--------|
| `GET` | `/api/search?q={query}` | Autocomplete axtarış |

**Cavab formatı:**
```json
{
  "suggests": ["kafel 60x60", "kafel yapışdırıcı", ...],
  "products": [
    {"id": 1, "name": "Keramik kafel", "cat": "Kafel", "price": "23.90 ₼", "img": "/storage/..."}
  ],
  "masters": [
    {"id": 5, "initials": "RM", "name": "Rəşad M.", "role": "Kafelçi", "rate": "4.9"}
  ],
  "total": 86
}
```

**Axtarış məntiqi:**
- `products`: ad, brend, kateqoriya üzrə `LIKE '%query%'` — yalnız `visible()` scope
- `specialists`: ad, ixtisas üzrə `LIKE '%query%'` — yalnız `Active` statuslu
- `suggests`: populyar axtarış sözləri / əvvəlki axtarışlar (gələcək — hələlik statik siyahı ola bilər)
- Azərbaycan diakritik normallaşdırma: ə→e, ö→o, ü→u, ç→c, ş→s, ğ→g, ı→i (frontend-də artıq var, backend-də də olmalıdır)

### 4.3 Dil dəyişdirici

- 3 dil: AZ, RUS, ENG
- Click ilə dropdown açılır
- Seçildikdə: `GET /lang/{locale}` — session-da saxlanılır, əvvəlki səhifəyə redirect
- **Statik qalır** — admin idarəetməsi lazım deyil (dillər sabitdir)

### 4.4 Seçilmişlər (Favorites) ikonu

- Ürək ikonu (♡)
- Click-də seçilmişlər səhifəsinə yönləndirir
- **Backend:** `GET /wishlist` (login olmuş istifadəçi üçün)

### 4.5 Səbət ikonu + badge

- Səbət ikonu (🛒) + say badge-i
- Badge: səbətdəki məhsul sayı göstərilir
- Hazırda localStorage-dan — backend-ə keçdikdə: `GET /api/cart/count`
- Click-də: `/cart` səhifəsinə yönləndirir

### 4.6 İstifadəçi sahəsi (Sign-in / User Menu)

**Login olmamış:**
- "Daxil ol" mətni → `/login` səhifəsinə link

**Login olmuş:**
- İstifadəçinin adı göstərilir (`first_name`)
- Rol-a görə ad klikləndikdə yönləndirmə:
  - Satıcı → `/business/profile`
  - Usta → `/specialist/cabinet`
  - Alıcı → klik edilə bilməz (span)
- "Çıxış" düyməsi → `POST /logout`

### 4.7 "Elan yerləşdir" düyməsi

- Qara fon, ağ mətn, "+" ikonu
- Link: `/sell` səhifəsinə
- Həmişə göstərilir (login olub-olmamağından asılı olmayaraq)
- **Admin:** `settings`-dən deaktiv edilə bilər (opsional)

---

## 5. Header Row 2 — Naviqasiya Sətri

### 5.1 Sol naviqasiya elementləri

`menu_items` cədvəlindən `location = 'header_main'`, `parent_id IS NULL`, sıralanmış.

**Defolt seeder data-sı:**

| # | Label | Route | İkon | Dropdown | CSS Class |
|---|-------|-------|------|----------|-----------|
| 1 | Kataloq | `catalog` | `icon-menu.svg` | ✅ `header_mega_catalog` | `catalog` |
| 2 | Mütəxəssislər | `specialists` | — (caret ▼) | ✅ `header_mega_specialists` | — |
| 3 | Bloq | `blog` | — (caret ▼) | ✅ `header_mega_blog` | — |
| 4 | Haqqımızda | `about` | — | ❌ | — |
| 5 | B2B | `business.register` | — | ❌ | — |

### 5.2 Sağ tərəf — Kalkulyator

| Label | Route | İkon | CSS Class |
|-------|-------|------|-----------|
| Təmir kalkulyatoru | `calculator` | `icon-calculator.svg` | `nav-calc` |

### 5.3 Dropdown/no-dropdown məntiqi

- `has_dropdown = true` → caret ikonu (▼) göstərilir, click-də mega-panel açılır
- `has_dropdown = false` → birbaşa linkdir, click-də URL-ə gedir
- Yalnız 1 dropdown eyni anda açıq ola bilər
- Kənarı click etmək və ya Escape basmaq dropdown-u bağlayır

### 5.4 Aktiv hal (active state)

Cari route əsasında menyu elementi aktiv görünür (alt xətt). Server-side hesablanır:
- `route_name` mövcuddursa → `request()->routeIs(route_name . '*')` ilə yoxlanılır

---

## 6. Mega Dropdown — Kataloq

### 6.1 Mövcud frontend strukturu

3-sütunlu grid. Hər element bir kart: ikon + başlıq + açıqlama mətni. Hamısı `/catalog`-a link edir.

### 6.2 Backend mənbəyi

`menu_items` cədvəlindən `location = 'header_mega_catalog'`, `parent_id IS NULL`.

**Hər kart üçün sahələr:**

| Sahə | İstifadə |
|------|---------|
| `icon` | Sol tərəfdəki SVG ikon |
| `label` | Kateqoriya başlığı (məs. "Tikinti materialları") |
| `description` | Alt açıqlama mətni (məs. "Kərpic, sement, beton qarışıqları") |
| `url` / `route_name` | Kateqoriya linki (məs. `/catalog?category=tikinti`) |

**Defolt seeder (6 kart):**

| # | İkon | Label (AZ) | Açıqlama |
|---|------|-----------|----------|
| 1 | `icon-bricks.svg` | Tikinti materialları | Kərpic, sement, beton qarışıqları |
| 2 | `icon-faucet.svg` | Santexnika | Kran, boru, duş kabinləri |
| 3 | `icon-power-plug.svg` | Elektrik | Kabel, rozetka, avtomatlar |
| 4 | `icon-floor-tiles.svg` | Döşemə & üzlük | Kafel, laminat, parket |
| 5 | `icon-pendant-lamp.svg` | İşıqlandırma | Lüstr, spot, LED panel |
| 6 | `icon-armchair.svg` | Dekor & mebel | İnteryer aksessuarları |

### 6.3 Admin idarəetməsi

- Admin kart əlavə/silə/redaktə edə bilər
- Hər kartın ikonu, adı, açıqlaması, linki dəyişdirilə bilər
- Sıralama drag-drop ilə

---

## 7. Mega Dropdown — Mütəxəssislər

### 7.1 Mövcud frontend strukturu

İki hissədən ibarətdir:
- **Sol:** 2-sütunlu grid — 4 ixtisas kartı (ikon + başlıq + açıqlama)
- **Sağ:** Promo bölməsi — şəkil + mətn + CTA düymə

### 7.2 Backend mənbəyi — İxtisas kartları

`menu_items` cədvəlindən `location = 'header_mega_specialists'`, `parent_id IS NULL`.

**Defolt seeder (4 kart):**

| # | İkon | Label (AZ) | Açıqlama |
|---|------|-----------|----------|
| 1 | `icon-blueprint.svg` | Arxitektorlar | Layihə, plan çəkmə |
| 2 | `icon-interior-design.svg` | İnteryer dizaynerlər | Daxili dizayn həlləri |
| 3 | `icon-hammer-wrench.svg` | Ustalar | Kafelçi, boyaçı, elektrik |
| 4 | `icon-tower-crane.svg` | Tikinti şirkətləri | Tam təmir xidmətləri |

### 7.3 Backend mənbəyi — Promo bölməsi

`settings` cədvəlindən:

| Key | Tip | Qeyd |
|-----|-----|------|
| `mega_spec_promo_image` | string | Promo şəkli |
| `mega_spec_promo_text` | json | Promo mətni: `{"az": "Hardan başlayacağınızı bilmirsiniz?...", ...}` |
| `mega_spec_promo_button_text` | json | CTA düymə mətni: `{"az": "Pulsuz konsultasiya", ...}` |
| `mega_spec_promo_button_url` | string | CTA linki |

---

## 8. Mega Dropdown — Bloq

### 8.1 Mövcud frontend strukturu

Horizontal sıra — 3 blog kartı. Hər kart: şəkil (208×192px) + başlıq + açıqlama + "Daha ətraflı" linki.

### 8.2 Backend mənbəyi

Bu dropdown `menu_items`-dan **deyil** — birbaşa `blog_posts` cədvəlindən gəlir:

```
BlogPost::published()
    ->showInHeader()     // show_in_header = true
    ->latest('published_at')
    ->take(3)
    ->get()
```

**Hər kart göstərir:**

| Sahə | Mənbə |
|------|-------|
| Şəkil | `blog_posts.cover_image` |
| Başlıq | `blog_posts.title` (cari dildə) |
| Açıqlama | `blog_posts.excerpt` (cari dildə) |
| Link | `/blog/{slug}` |

### 8.3 Admin idarəetməsi

- Admin blog yazısı yaratdıqda `show_in_header` toggle-ını aktiv edir
- Maks. 3 blog yazısı header-da göstərilir (frontend limit)
- 3-dən çox `show_in_header = true` varsa — ən son dərc olunan 3-ü göstərilir

---

## 9. Header Elementlərinin Tam Siyahısı

### 9.1 Row 1 elementləri (admin idarəetmə)

| Element | Mənbə | Admin dəyişdirə bilər? |
|---------|-------|----------------------|
| Logo | `settings.site_logo` | ✅ Logo şəkli |
| Axtarış placeholder | `translations` (`nav.search_placeholder`) | ✅ Mətn |
| Dil seçici | Statik (az/ru/en) | ❌ |
| Favorites ikonu | Statik | ❌ |
| Səbət ikonu | Statik | ❌ |
| "Daxil ol" / user adı | Dinamik (auth state) | ❌ |
| "Elan yerləşdir" mətni | `translations` (`nav.post_product`) | ✅ Mətn |
| "Elan yerləşdir" linki | Statik (`/sell`) | ❌ |

### 9.2 Row 2 elementləri (admin idarəetmə)

| Element | Mənbə | Admin dəyişdirə bilər? |
|---------|-------|----------------------|
| Nav elementləri | `menu_items` (location=header_main) | ✅ Tam CRUD |
| Kalkulyator linki | `menu_items` (location=header_main) | ✅ |
| Mega Catalog | `menu_items` (location=header_mega_catalog) | ✅ Tam CRUD |
| Mega Specialists | `menu_items` (location=header_mega_specialists) + `settings` | ✅ Kartlar + promo |
| Mega Blog | `blog_posts` (show_in_header=true) | ✅ Blog idarəetməsindən |

---

# FOOTER

## 10. Footer Strukturu — Mövcud Frontend

```
┌─────────────────────────────────────────────────────────────────────┐
│ [Logo]                                          [Məhsullara keç →] │
├─────────────────────────────────────────────────────────────────────┤
│ Məhsullar    │ Mütəxəssislər │ ARCHI-yə qoşul │ Şirkət & dəstək  │
│ · Kafel      │ · Usta tap    │ · Satıcı ol    │ · Haqqımızda     │
│ · Boya       │ · Top ustalar │ · Usta ol      │ · Konsultasiya   │
│ · Santexnika │ · Hamısına bax│ · Partnyor     │ · Məqalələr      │
│ · İzolyasiya │               │ · Əməkdaşlıq   │ · Yardım mərkəzi │
│ · Hamısına bax│              │                │ · Əlaqə          │
├─────────────────────────────────────────────────────────────────────┤
│ [✉] Yeniliklərdən xəbərdar ol          [email input] [Abunə ol]   │
├─────────────────────────────────────────────────────────────────────┤
│ İstifadə şərtləri | Gizlilik | Çatdırılma | Cookie | Sitemap      │
│ ©2026 ARCHI                                              [IG]      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 11. Footer Menyu Sistemi

### 11.1 Mənbə

`menu_items` cədvəlindən `location = 'footer'`.

### 11.2 İki səviyyəli struktur

**Parent elementlər** — sütun başlıqları:
- `is_clickable = false` — klik edilə bilməz, yalnız başlıq rolunda
- `parent_id = NULL`
- `label` — sütun başlığı mətni

**Child elementlər** — sütun içindəki linklər:
- `is_clickable = true` — klik oluna bilər
- `parent_id` = parent-in ID-si
- `label` — link mətni
- `url` / `route_name` — link hədəfi
- `sort_order` — sıralama (parent daxilində)

### 11.3 Defolt seeder data-sı

**Parent 1: Məhsullar** (`is_clickable: false`)
| # | Child label (AZ) | Route / URL |
|---|-------------------|-------------|
| 1 | Kafel & metlax | `/catalog?category=kafel-metlax` |
| 2 | Boya & emal | `/catalog?category=boya-emal` |
| 3 | Santexnika | `/catalog?category=santexnika` |
| 4 | İzolyasiya & istilik | `/catalog?category=izolyasiya-istilik` |
| 5 | Bütün kateqoriyalar | `/catalog` |

**Parent 2: Mütəxəssislər** (`is_clickable: false`)
| # | Child label (AZ) | Route / URL |
|---|-------------------|-------------|
| 1 | Usta tap | `/specialists` |
| 2 | Top ustalar | `/specialists?featured=1` |
| 3 | Hamısına bax | `/specialists` |

**Parent 3: ARCHI-yə qoşul** (`is_clickable: false`)
| # | Child label (AZ) | Route / URL |
|---|-------------------|-------------|
| 1 | Satıcı ol | `/sell` |
| 2 | Usta ol | `/register?role=master` |
| 3 | Partnyor proqramı | `/business/register` |
| 4 | Biznes əməkdaşlığı | `/business/register` |

**Parent 4: Şirkət & dəstək** (`is_clickable: false`)
| # | Child label (AZ) | Route / URL |
|---|-------------------|-------------|
| 1 | Haqqımızda | `/about` |
| 2 | Pulsuz konsultasiya | `/specialists` |
| 3 | Məqalələr | `/blog` |
| 4 | Yardım mərkəzi | `#` (placeholder) |
| 5 | Əlaqə | `#` (placeholder) |

### 11.4 Frontend rendering məntiqi

```php
$footerMenus = MenuItem::where('location', 'footer')
    ->whereNull('parent_id')
    ->active()
    ->ordered()
    ->with(['children' => fn($q) => $q->active()->ordered()])
    ->get();

// Blade-da:
@foreach($footerMenus as $column)
  <div class="foot-col">
    <h5>{{ $column->label }}</h5>  {{-- Parent: klik olunmaz --}}
    @foreach($column->children as $link)
      <a href="{{ $link->resolved_url }}">{{ $link->label }}</a>
    @endforeach
  </div>
@endforeach
```

---

## 12. Footer — Digər Elementlər

### 12.1 Logo + "Məhsullara keç" linki

| Element | Mənbə |
|---------|-------|
| Footer logo | `settings.site_logo_white` (ağ versiya) |
| "Məhsullara keç" mətni | `translations` (`footer.go_products`) |
| Link | `/catalog` |

### 12.2 Newsletter (Xəbər bülleteni)

**Mövcud frontend:**
- Başlıq, alt başlıq, email input, "Abunə ol" düyməsi
- Hazırda `onsubmit="return false"` — heç bir backend yoxdur

**Backend tələbi:**

**Cədvəl: `newsletter_subscribers`**

| Sahə | Tip | Qeyd |
|------|-----|------|
| `id` | bigint (PK) | |
| `email` | string (unique) | Abunəçi emaili |
| `is_active` | boolean (default: true) | Abunəlik aktiv? |
| `subscribed_at` | timestamp | |
| `unsubscribed_at` | timestamp (nullable) | |
| `created_at` | timestamp | |

**API endpoint:**

| Metod | URL | Təsvir |
|-------|-----|--------|
| `POST` | `/api/newsletter/subscribe` | Email ilə abunə ol |

**Validasiya:** `email` — required, email, unique:newsletter_subscribers
**Cavab:** `{ success: true, message: "Uğurla abunə oldunuz" }`
**Dublikat:** Əgər email artıq var və aktiv-dirsə → "Artıq abunəsiniz" mesajı

**Mətnlər `translations`-dan:**

| Key | Hazırkı AZ dəyəri |
|-----|--------------------|
| `footer.news_title` | Yeniliklərdən xəbərdar ol |
| `footer.news_sub` | Endirimlər, yeni məhsullar, faydalı məqalələr |
| `footer.news_email` | E-poçt ünvanınız |
| `footer.news_submit` | Abunə ol |

### 12.3 Hüquqi linklər (Legal)

`menu_items` cədvəlindən — ayrıca `location = 'footer_legal'` ilə, `parent_id = NULL`, hamısı `is_clickable = true`.

**Defolt seeder:**

| # | Label (AZ) | URL |
|---|-----------|-----|
| 1 | İstifadə şərtləri | `/terms` (placeholder) |
| 2 | Gizlilik siyasəti | `/privacy` (placeholder) |
| 3 | Çatdırılma & qaytarma | `/delivery` (placeholder) |
| 4 | Cookie siyasəti | `/cookies` (placeholder) |
| 5 | Sayt xəritəsi | `/sitemap` (placeholder) |

### 12.4 Copyright mətni

`translations` cədvəlindən:

| Key | Hazırkı dəyər |
|-----|---------------|
| `footer.copy` | ©2026 ARCHI — Bütün hüquqlar qorunur. |

### 12.5 Sosial şəbəkə linkləri

**Cədvəl: `social_links`**

| Sahə | Tip | Qeyd |
|------|-----|------|
| `id` | bigint (PK) | |
| `platform` | string | Platforma adı (instagram, facebook, whatsapp, youtube, tiktok, linkedin) |
| `url` | string | Profil linki |
| `icon` | string | İkon faylı (məs. `icon-instagram-white.svg`) |
| `sort_order` | integer (default: 0) | |
| `is_active` | boolean (default: true) | |

**Defolt seeder:** Yalnız Instagram (hazırda frontend-də 1 ikon var). Admin əlavə platformalar əlavə edə bilər.

---

## 13. Admin Panel — Filament

### 13.1 Filament Resource: `MenuItemResource`

**Siyahı:** Location-a görə qruplaşdırılmış, tree view (parent → children)
**Filterlər:** Location dropdown, is_active

**Form sahələri:**
- Location (dropdown: header_main, header_mega_catalog, header_mega_specialists, header_mega_blog, footer, footer_legal)
- Parent (dropdown — eyni location-dakı parent-lər, nullable)
- Label (TranslateField — az/ru/en)
- URL (text) və ya Route name (text) — biri doldurulmalıdır
- Icon (fayl upload, nullable)
- Image (fayl upload, nullable — mega kartlar üçün)
- Description (TranslateField, nullable — mega kartlar üçün)
- Has dropdown (toggle)
- Is clickable (toggle — defolt: true)
- Open in new tab (toggle)
- CSS class (text, nullable)
- Badge text (TranslateField, nullable)
- Sort order (number)
- Is active (toggle)

**Toplu əməliyyat:** Aktiv et / deaktiv et
**Drag-drop sıralama:** sort_order avtomatik yenilənir

### 13.2 Filament Resource: `SocialLinkResource`

**Siyahı:** Platform, URL, aktiv toggle
**Form:** Platform (dropdown), URL, Icon (fayl upload), Sort order, Is active

### 13.3 Filament Settings: Header/Footer Tənzimləmələri

`settings` cədvəlindəki əlaqəli key-lər:

| Key | Tip | Təsvir |
|-----|-----|--------|
| `site_logo` | string | Əsas logo (header) |
| `site_logo_white` | string | Ağ logo (footer) |
| `site_name` | json | Sayt adı (SEO) |
| `mega_spec_promo_image` | string | Mütəxəssis mega-dropdown promo şəkli |
| `mega_spec_promo_text` | json | Promo mətni |
| `mega_spec_promo_button_text` | json | Promo CTA düymə mətni |
| `mega_spec_promo_button_url` | string | Promo CTA linki |

---

## 14. Shared Layout — Data Yükləmə

Header və footer hər səhifədə göstərildiyi üçün data layout component-indən yüklənir.

### 14.1 ViewComposer / Middleware ilə data paylaşma

```php
// AppServiceProvider::boot() və ya ViewComposer

View::composer('components.layout', function ($view) {
    $view->with([
        // Header Row 2 — əsas naviqasiya
        'headerMenu' => MenuItem::location('header_main')
            ->roots()->active()->ordered()
            ->get(),

        // Mega Catalog dropdown
        'megaCatalog' => MenuItem::location('header_mega_catalog')
            ->roots()->active()->ordered()
            ->get(),

        // Mega Specialists dropdown
        'megaSpecialists' => MenuItem::location('header_mega_specialists')
            ->roots()->active()->ordered()
            ->get(),

        // Mega Blog dropdown
        'megaBlog' => BlogPost::published()
            ->showInHeader()
            ->latest('published_at')
            ->take(3)
            ->get(),

        // Footer sütunları (parent + children eager loaded)
        'footerMenu' => MenuItem::location('footer')
            ->roots()->active()->ordered()
            ->with(['children' => fn($q) => $q->active()->ordered()])
            ->get(),

        // Footer legal linklər
        'footerLegal' => MenuItem::location('footer_legal')
            ->roots()->active()->ordered()
            ->get(),

        // Sosial linklər
        'socialLinks' => SocialLink::active()->ordered()->get(),

        // Səbət sayı (auth user)
        'cartCount' => auth()->check()
            ? CartItem::where('user_id', auth()->id())->count()
            : 0,
    ]);
});
```

### 14.2 Cache-ləmə

Menyu data-sı hər request-də DB-dən çəkilməməlidir:

```php
// Cache key-ləri:
'menu_header_main'           // 60 dəq TTL
'menu_mega_catalog'          // 60 dəq TTL
'menu_mega_specialists'      // 60 dəq TTL
'menu_footer'                // 60 dəq TTL
'menu_footer_legal'          // 60 dəq TTL
'social_links'               // 60 dəq TTL

// Admin dəyişiklik etdikdə cache təmizlənir:
// MenuItem observer → Cache::forget('menu_*')
// SocialLink observer → Cache::forget('social_links')
```

---

## 15. Migration Sırası (Bu bölmə üçün)

1. `create_menu_items_table`
2. `create_social_links_table`
3. `create_newsletter_subscribers_table`

### 15.1 Seeder-lər

1. `MenuItemSeeder` — bütün header və footer menyu elementləri (Bölmə 6, 7, 11, 12.3-dəki defolt datalar)
2. `SocialLinkSeeder` — Instagram (defolt)
3. `SettingSeeder`-ə əlavə — logo, mega-spec promo tənzimləmələri

### 15.2 Controller-lər

1. `SearchController` — autocomplete API
2. `NewsletterController` — abunə API

### 15.3 Filament Resource-lar

1. `MenuItemResource` — menyu idarəetməsi (tree view)
2. `SocialLinkResource` — sosial linklər
3. Settings səhifəsinə əlavə — logo, mega-spec promo
