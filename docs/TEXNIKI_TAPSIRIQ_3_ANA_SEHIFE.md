# ARCHI Marketplace — Texniki Tapşırıq

## Bölmə 3: Ana Səhifə, Bannerlər, Blog Modulu

> Bu sənəd backend development üçün yazılıb. Claude ilə implementasiya ediləcək.
> Tərcümə yanaşması: Bölmə 2, Bənd 0-a bax — bütün çoxdilli sahələr JSON formatında, `solution-forest/filament-translate-field` paketi ilə.

---

## 1. Ana Səhifə — Section Xəritəsi

Ana səhifə (`/`) aşağıdakı section-lardan ibarətdir (yuxarıdan aşağı):

| # | Section | Data mənbəyi | Admin idarəetmə |
|---|---------|-------------|-----------------|
| 1 | Hero (əsas banner + promo karusel + rol slider) | `banners` cədvəli + statik | Bannerlər modulu |
| 2 | Xidmət zolağı (trust badges) | `translations` cədvəli | Tərcümələr modulu |
| 3 | Kateqoriyalar | `categories` cədvəli | Kateqoriya modulu |
| 4 | SALE Marquee (sürüşən yazı) | `home_settings` | Admin tənzimləmələri |
| 5 | Kampaniya / Endirim (promo banner + endirimli məhsullar) | `promo_banners` + `products` (on_sale) | Promo banner modulu |
| 6 | Seçilmiş məhsullar (promo banner + featured products) | `promo_banners` + `products` (is_featured) | Promo banner modulu |
| 7 | Seçilmiş mütəxəssislər | `specialist_profiles` (is_featured) | Admin toggle |
| 8 | Blog | `blog_posts` (show_on_home) | Blog modulu |

---

## 2. Hero Bannerləri (3 ədəd)

Hero section-da **3 ayrı banner** var — hamısı eyni `banners` cədvəlindən idarə olunur:

```
┌──────────────────────────────────┬──────────────────────┐
│                                  │  2) Hero Promo       │
│  1) Əsas Hero Banner (sol)       │     Karusel (sağ üst)│
│     960×543px                    │     420×236px         │
│                                  ├──────────────────────┤
│                                  │  3) Rol Slider       │
│                                  │     (sağ alt)         │
│                                  │     420×303px         │
└──────────────────────────────────┴──────────────────────┘
```

### 2.1 Cədvəl: `banners`

| Sahə | Tip | Qeyd |
|------|-----|------|
| `id` | bigint (PK) | |
| `position` | enum | Banner yeri: `hero_main`, `hero_promo`, `hero_role` |
| `title` | json (nullable) | Tərcümə olunan başlıq: `{"az": "...", "ru": "...", "en": "..."}` |
| `subtitle` | json (nullable) | Tərcümə olunan alt başlıq |
| `description` | json (nullable) | Tərcümə olunan açıqlama mətni |
| `image` | string | Banner şəkli (fayl yolu) |
| `button_text` | json (nullable) | Düymə mətni: `{"az": "Ətraflı bax", ...}` |
| `button_url` | string (nullable) | Düymə linki |
| `url` | string (nullable) | Bannerin özünün link olduğu URL |
| `tag_text` | json (nullable) | Tag mətni (xətt + başlıq, məs. "TİKİNTİ VƏ TƏMİR MARKETİ") |
| `sort_order` | integer (default: 0) | Sıralama (karuseldə çoxlu slide ola bilər) |
| `is_active` | boolean (default: true) | Aktiv/deaktiv |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### 2.2 Banner yerləri (position)

**1) `hero_main` — Əsas hero banner (sol böyük panel, 960×543px)**
- Hazırda: statik şəkil (`hero-bricklayer.jpg`) + tag + h1 + subtitle + info link
- Admin dəyişə bilər: şəkil, tag mətni, başlıq, alt başlıq, info mətni
- Yalnız 1 ədəd (karusel deyil)

**2) `hero_promo` — Hero promo karusel (sağ üst, 420×236px)**
- Hazırda: 3 slide (hər biri şəkil + "Ətraflı bax" linki)
- Admin istədiyi qədər slide əlavə edə bilər (`sort_order` ilə sıralama)
- Hər slide: şəkil + button_text + button_url
- Avtomatik fırlanma: 4000ms interval, dot naviqasiya

**3) `hero_role` — Rol slider (sağ alt, 420×303px)**
- Hazırda: 3 slide (Usta, Satıcı, Müştəri — hər biri şəkil + tag + başlıq + açıqlama + qeydiyyat linki)
- Admin dəyişə bilər: hər slide üçün şəkil, tag, başlıq, açıqlama, düymə mətni, düymə linki
- Avtomatik fırlanma: 4500ms interval, dot naviqasiya
- Defolt slide-lar seeder-dən gəlir (3 rol)

### 2.3 Filament Resource: `BannerResource`

**Siyahı:** Position-a görə qruplaşdırılmış, aktiv/deaktiv filteri
**Form sahələri:**
- Position (dropdown: hero_main, hero_promo, hero_role)
- Image (fayl upload)
- Tag text (TranslateField — yalnız hero_main və hero_role üçün)
- Title, Subtitle, Description (TranslateField)
- Button text (TranslateField), Button URL
- Sort order, Is active (toggle)

---

## 3. Reklam Kampaniya Bannerləri (Ayrı Modul)

Bu modul Hero banner-lərdən **tamamilə ayrıdır**. Aşağıda, SALE marquee-dən sonra gələn qara fonda promo bannerlədir. Pul ödəmiş şirkətlər üçün reklam yeridir.

### 3.1 Cədvəl: `promo_banners`

Kampaniya / reklam bannerləri — ARCHI60, ARCHI15 tipli və ya şirkət reklamları.

| Sahə | Tip | Qeyd |
|------|-----|------|
| `id` | bigint (PK) | |
| `section` | enum | Hansı section-da göstərilir: `campaign`, `products` |
| `badge_text` | json | Badge mətni: `{"az": "KAMPANIYA", "ru": "АКЦИЯ", "en": "PROMO"}` |
| `title` | json | Başlıq: `{"az": "Bütün sifarişlərə 60% endirim", ...}` |
| `description_before` | json | Kod-dan əvvəlki mətn: `{"az": "Səbətdə", ...}` |
| `promo_code` | string (nullable) | Promo kod (məs. "ARCHI60") — `promo_codes` cədvəlinə FK (nullable) |
| `description_after` | json | Kod-dan sonrakı mətn: `{"az": "promokodunu daxil edin — endirim avtomatik tətbiq olunsun", ...}` |
| `button_text` | json (nullable) | Əgər promo kod deyilsə — düymə mətni |
| `button_url` | string (nullable) | Düymə linki |
| `company_name` | string (nullable) | Reklam verən şirkət (admin üçün qeyd) |
| `image` | string (nullable) | Opsional banner şəkli |
| `sort_order` | integer (default: 0) | |
| `is_active` | boolean (default: true) | |
| `starts_at` | timestamp (nullable) | |
| `expires_at` | timestamp (nullable) | |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### 3.2 Frontend göstərilmə

**Campaign section-da** (`section = campaign`):
- Endirimli məhsullardan əvvəl promo banner göstərilir
- Promo kod varsa: kod + "Kopyala" düyməsi
- Promo kod yoxdursa: button_text + button_url ilə CTA düyməsi

**Products section-da** (`section = products`):
- Seçilmiş məhsullardan əvvəl promo banner göstərilir
- Eyni məntiq: ya promo kod, ya da CTA düyməsi

---

## 4. SALE Marquee (Sürüşən Yazı)

### 4.1 Hazırkı vəziyyət

Qara fonda sürüşən ağ yazı: `"SALE • OUTLET • SALE • OUTLET •"` — 10 dəfə təkrarlanır, CSS animasiya ilə.

### 4.2 Admin idarəetməsi

`home_settings` cədvəli (və ya `settings` cədvəli) ilə idarə olunur:

| Key | Tip | Qeyd |
|-----|-----|------|
| `sale_marquee_text` | json | Tərcümə olunan sürüşən mətn: `{"az": "SALE • OUTLET •", ...}` |
| `sale_marquee_active` | boolean | Aktiv/deaktiv — `false` olduqda bütün marquee section gizlənir |

### 4.3 Filament idarəetməsi

Admin panelində "Ana Səhifə Tənzimləmələri" səhifəsindən idarə olunur:
- Marquee mətni (TranslateField)
- Marquee aktiv/deaktiv (toggle)

---

## 5. Tərcümələr Modulu (Admin-dən idarə olunan statik mətnlər)

### 5.1 Məqsəd

Ana səhifədə və digər səhifələrdə olan statik başlıq sözləri (section tag-ları, title-lar) hazırda `lang/az/home.php` fayllarında hardcoded-dir. Bunlar admin panelindən dəyişdirilə bilməlidir.

### 5.2 Cədvəl: `translations`

| Sahə | Tip | Qeyd |
|------|-----|------|
| `id` | bigint (PK) | |
| `group` | string | Qrup (məs. `home`, `catalog`, `common`) |
| `key` | string | Unikal açar (məs. `categories.tag`, `sale.title`) |
| `value` | json | Tərcümə: `{"az": "Ən çox baxılan", "ru": "Самые просматриваемые", "en": "Most viewed"}` |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Composite unique:** `(group, key)`

### 5.3 Hansı mətnlər idarə olunur

Ana səhifədəki bütün section başlıqları:

| Group | Key | Hazırkı AZ dəyəri |
|-------|-----|--------------------|
| `home` | `hero.tag` | Tikinti və təmir marketi |
| `home` | `hero.title` | ARCHI ilə tikintini asanlaşdır |
| `home` | `hero.subtitle` | Tikinti materialları, ustalar, alətlər — hamısı bir yerdə |
| `home` | `categories.tag` | Ən çox baxılan |
| `home` | `categories.title` | Kateqoriyalar |
| `home` | `sale.tag` | Endirimlə satılır |
| `home` | `sale.title` | Böyük endirim SALE |
| `home` | `sale.marquee` | SALE • OUTLET • |
| `home` | `products.tag` | Ən çox satıranlar |
| `home` | `products.title` | Seçilmiş məhsullar |
| `home` | `specialists.tag` | Ən çox müraciət olunan |
| `home` | `specialists.title` | Seçilmiş mütəxəssislər |
| `home` | `blog.tag` | Bloq |
| `home` | `blog.title` | Faydalı məqalələr |
| `home` | `services.masters_t1` | Sertifikatlı ustalar |
| `home` | `services.masters_t2` | reyting və rəylərlə |
| `home` | `services.delivery_t1` | Sürətli çatdırılma |
| `home` | `services.delivery_t2` | bütün bölgələrə |
| `home` | `services.payment_t1` | Etibarlı ödəniş |
| `home` | `services.payment_t2` | 3D Secure ilə |
| `home` | `services.consult_t1` | Pulsuz konsultasiya |
| `home` | `services.consult_t2` | mütəxəssislərdən |

### 5.4 İş prinsipi

1. İlk olaraq `lang/az/*.php` fayllarındakı mətnlər seeder ilə `translations` cədvəlinə yazılır.
2. Custom `TranslationLoader` Laravel-in translation sistemini override edir — əvvəlcə DB-dən yoxlayır, tapmazsa fayl-a fallback edir.
3. Admin panelindən istənilən mətni 3 dildə dəyişmək mümkündür.
4. Cache-ləmə: Tərcümələr cache-lənir, admin dəyişdikdə cache təmizlənir.

### 5.5 Filament Resource: `TranslationResource`

**Siyahı:** Group-a görə qruplaşdırılmış, axtarış ilə
**Form:** Key (readonly), Value (TranslateField — az/ru/en tab-ları)
**Filter:** Group dropdown (home, catalog, common, product, blog...)

---

## 6. Kateqoriyalar Section-u

### 6.1 Frontend-dəki göstərilmə

- 7 kateqoriya thumbnail-i (flex row)
- Hər biri: şəkil, overlay, ad, məhsul sayı, ox ikonu
- İlk tile defolt olaraq expanded (`.open`)
- Hover-da digər tile-lar expand olur

### 6.2 Backend mənbəyi

`categories` cədvəlindən — yalnız `parent_id IS NULL` (əsas kateqoriyalar), `is_active = true`, `sort_order` ilə sıralanmış.

Hər kateqoriya üçün:
- `name` — JSON tərcümə ilə cari dildə
- `image` — kateqoriya şəkli (bu section üçün istifadə olunur)
- `products_count` — həmin kateqoriyadakı aktiv məhsul sayı (cached counter)

---

## 7. Endirimli Məhsullar (Campaign Section)

### 7.1 Frontend-dəki göstərilmə

- Promo banner (ARCHI60 tipli — Bölmə 3-dən gəlir)
- Section başlıq: "Endirimlə satılır" / "Böyük endirim SALE"
- 4 məhsul kartı (grid4)
- "Ətraflı bax" linki → endirimli məhsullar siyahısı səhifəsinə yönləndirir

### 7.2 Backend mənbəyi

`products` cədvəlindən — `is_sale = true`, `is_visible = true`, `is_approved = true`, sıralanmış, ilk 4.

**"Ətraflı bax" linki** → `/catalog?sale=1` — yalnız `is_sale = true` olan məhsullar siyahılanır.

---

## 8. Seçilmiş Məhsullar (Products Section)

### 8.1 `is_featured` sahəsi — Product modelinə əlavə

**Products cədvəlinə əlavə olunmalıdır:**

| Sahə | Tip | Qeyd |
|------|-----|------|
| `is_featured` | boolean (default: false) | Admin tərəfindən seçilmiş məhsul — ana səhifədə göstərilir |

### 8.2 Frontend-dəki göstərilmə

- Promo banner (ARCHI15 tipli — Bölmə 3-dən gəlir)
- Section başlıq: "Ən çox satıranlar" / "Seçilmiş məhsullar"
- 4 məhsul kartı (grid4)
- "Ətraflı bax" linki → `/catalog?featured=1` (seçilmiş məhsullar siyahısı)

### 8.3 Backend mənbəyi

`products` cədvəlindən — `is_featured = true`, `is_visible = true`, `is_approved = true`, `sort_order` ilə sıralanmış, ilk 4.

### 8.4 Admin idarəetməsi

- Filament-də Product siyahısında `is_featured` toggle sütunu
- Toplu əməliyyat: seçilib → "Seçilmiş et" / "Seçilmişdən çıxar"
- Product edit səhifəsində toggle

---

## 9. Seçilmiş Mütəxəssislər (Specialists Section)

### 9.1 Əlavə sahələr — SpecialistProfile modelinə

**specialist_profiles cədvəlinə əlavə olunmalıdır:**

| Sahə | Tip | Qeyd |
|------|-----|------|
| `is_featured` | boolean (default: false) | Admin tərəfindən seçilmiş — ana səhifədə göstərilir |
| `show_in_header` | boolean (default: false) | Saytın header/navbar-ında göstərilsin? (dropdown və ya mega-menü) |

### 9.2 Frontend-dəki göstərilmə

**Ana səhifədə:**
- Section başlıq: "Ən çox müraciət olunan" / "Seçilmiş mütəxəssislər"
- 4 mütəxəssis kartı (grid4)
- Hər kart: fon rəngi, ixtisas, reytinq, rəy sayı, ad, təcrübə, layihə sayı
- "Ətraflı bax" linki → `/specialists?featured=1` (seçilmiş mütəxəssislər siyahısı)

**Header-da:**
- `show_in_header = true` olan mütəxəssislər navbar-ın mütəxəssislər dropdown/mega-menüsündə göstərilir

### 9.3 Backend mənbəyi

- **Ana səhifə:** `specialist_profiles` → `is_featured = true`, istifadəçisi `Active` statusda, ilk 4
- **Header:** `specialist_profiles` → `show_in_header = true`, istifadəçisi `Active` statusda

### 9.4 Admin idarəetməsi

- Filament-də SpecialistProfile siyahısında `is_featured` və `show_in_header` toggle sütunları
- Specialist edit səhifəsində hər iki toggle

---

## 10. Blog Modulu

### 10.1 Cədvəl: `blog_posts`

| Sahə | Tip | Qeyd |
|------|-----|------|
| `id` | bigint (PK) | |
| `slug` | string (unique) | URL-friendly identifikator |
| `title` | json | Tərcümə olunan başlıq: `{"az": "...", "ru": "...", "en": "..."}` |
| `excerpt` | json (nullable) | Qısa xülasə (kart və siyahıda göstərilən) |
| `body` | json | Tam məqalə mətni (rich text / HTML) |
| `cover_image` | string | Üz qabığı şəkli |
| `category` | string | Kateqoriya slug-ı (filter üçün) |
| `tags` | json (nullable) | Etiketlər massivi: `["repair", "budget", "planning"]` |
| `author_name` | string | Müəllif adı |
| `author_avatar` | string (nullable) | Müəllif avatarı |
| `author_bio` | json (nullable) | Müəllif haqqında: `{"az": "...", ...}` |
| `read_time` | string (nullable) | Oxuma müddəti (məs. "5 dəq") |
| `show_on_home` | boolean (default: false) | Ana səhifədə göstərilsin? |
| `show_in_header` | boolean (default: false) | Saytın header/navbar-ında göstərilsin? (dropdown və ya mega-menü) |
| `is_featured` | boolean (default: false) | Blog səhifəsində featured (böyük) olaraq göstərilsin? |
| `is_published` | boolean (default: false) | Dərc edilib? |
| `published_at` | timestamp (nullable) | Dərc tarixi |
| `views_count` | integer (default: 0) | Baxış sayı |
| `sort_order` | integer (default: 0) | Sıralama |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### 10.2 Blog kateqoriyaları

Frontend-dən müəyyən edilən filter tab-ları:

| Slug | AZ | RU | EN |
|------|----|----|----|
| `all` | Hamısı | Все | All |
| `repair` | Təmir | Ремонт | Repair |
| `materials` | Materiallar | Материалы | Materials |
| `budget` | Büdcə | Бюджет | Budget |
| `design` | Dizayn | Дизайн | Design |
| `masters` | Ustalar | Мастера | Masters |
| `plumbing` | Santexnika | Сантехника | Plumbing |
| `insulation` | İzolyasiya | Изоляция | Insulation |

Bu kateqoriyalar `translations` cədvəlindən idarə olunur (Bölmə 5).

### 10.3 Ana səhifədə blog göstərilməsi

- `show_on_home = true` olan blog yazıları ana səhifədə göstərilir
- Section başlıq: "Bloq" / "Faydalı məqalələr"
- 4 blog kartı (blog-grid)
- Hər kart: şəkil, vaxt, başlıq, xülasə, "Oxu" linki

### 10.4 Blog siyahı səhifəsi (`/blog`)

**Mövcud frontend strukturu:**

1. **Hero:** Breadcrumb, başlıq ("Təmir bələdçisi"), alt başlıq
2. **Filter tab-ları:** 8 kateqoriya çipi — JS ilə kart-ları filtrləyir (`data-cat` atributu)
3. **Featured məqalə:** Böyük 2-sütunlu layout — şəkil (680×516px), etiketlər, başlıq, xülasə, müəllif, oxuma müddəti, tarix, "Daha ətraflı" düyməsi
4. **Məqalə grid-i:** 4 məqalə kartı, hər biri `data-cat` ilə filtrə uyğun gizlənir/göstərilir
5. **Boş hal:** "Seçilmiş filtrdə məqalə tapılmadı"

### 10.5 Blog məqalə səhifəsi (`/blog/{slug}`)

**Mövcud frontend strukturu:**

1. **Başlıq hissəsi:**
   - Breadcrumb: Ana səhifə → Bloq → məqalə adı
   - Etiket badge
   - H1 başlıq (44px)
   - Müəllif sətri: avatar, ad, meta
   - Paylaşma paneli (aşağıda Bölmə 10.8-ə bax)

2. **Məqalə gövdəsi:**
   - Cover şəkil (1000×480px)
   - Rich text məzmun: paraqraflar, H2 başlıqlar, bullet siyahılar, tip qutusu, blockquote, CTA banner
   - Tip qutusu: sarı fon, nida ikonu, məsləhət mətni
   - Blockquote: sarı sol border, sitat + müəllif
   - CTA banner: qara fon, başlıq, açıqlama, sarı düymə (kalkulyatora link)

3. **Etiketlər + müəllif:**
   - Etiket linkləri (bloq siyahısına)
   - Müəllif kartı: avatar, ad, bio, "Bütün yazılar" düyməsi

4. **Əlaqəli məqalələr:**
   - Section başlıq + 4 məqalə kartı (eyni kateqoriyadan və ya eyni tag-ları olan digər yazılar)

### 10.8 Paylaşma Funksionallığı (Share)

Blog məqaləsinin başlıq hissəsində "Paylaş:" paneli var. Frontend-də artıq implementasiya olunub (`data-share-bar`).

**Paylaşma kanalları:**

| Kanal | `data-share` dəyəri | Backend tələbi |
|-------|---------------------|----------------|
| **Facebook** | `facebook` | URL-i Facebook share dialog-a yönləndirmə: `https://www.facebook.com/sharer/sharer.php?u={url}` |
| **Instagram** | `native` | Browser Native Share API (`navigator.share`) — mobil cihazlarda IG daxil bütün paylaşma seçimləri açılır |
| **WhatsApp** | `whatsapp` | URL-i WhatsApp-a yönləndirmə: `https://wa.me/?text={title}+{url}` |
| **Link kopyala** | `copy` | URL-i clipboard-a kopyalama (`navigator.clipboard.writeText`), "Kopyalandı" status mətni göstərilir |

**Backend tələbi:**
- Paylaşma tamamilə **client-side-dır** — backend-dən əlavə endpoint lazım deyil
- Blog post URL-i: `/blog/{slug}` — slug unikal olduğu üçün paylaşılan link birbaşa məqaləyə aparır
- OG meta tag-lar (Open Graph) hər blog məqaləsi üçün layout-da render olunmalıdır:
  ```html
  <meta property="og:title" content="{{ $post->title }}">
  <meta property="og:description" content="{{ $post->excerpt }}">
  <meta property="og:image" content="{{ asset($post->cover_image) }}">
  <meta property="og:url" content="{{ url('/blog/' . $post->slug) }}">
  <meta property="og:type" content="article">
  ```

### 10.9 Blog etiketləri (Tags)

- `tags` sahəsi JSON massivdir: `["repair", "budget", "planning", "beginners"]`
- Məqalə səhifəsinin altında etiket linkləri göstərilir — hər biri `/blog?tag=repair` şəklində bloq siyahısına yönləndirir
- Tag-lar admin tərəfindən Filament-də tag input ilə daxil edilir
- Blog siyahı səhifəsində tag filteri əlavə oluna bilər (kateqoriya filterinə əlavə olaraq)

### 10.6 Filament Resource: `BlogPostResource`

> **Qeyd:** Blog yazılarını yalnız admin yazır. İstifadəçilər blog yaza bilmir.

**Siyahı səhifəsi:**
- Sütunlar: cover şəkil (thumbnail), başlıq, kateqoriya, dərc tarixi, show_on_home, show_in_header, is_featured, is_published, views
- Filterlər: kateqoriya, dərc statusu, ana səhifədə göstərilir?, header-da göstərilir?
- Toplu əməliyyat: dərc et / geri çək, ana səhifəyə əlavə et / çıxar

**Yaratma/redaktə forması:**
- Title (TranslateField — az/ru/en)
- Slug (auto-generated from title)
- Excerpt (TranslateField — az/ru/en, textarea)
- Body (TranslateField — az/ru/en, RichEditor)
- Cover image (fayl upload)
- Category (dropdown — blog kateqoriyaları)
- Tags (tag input — çoxlu etiket)
- Author name, Author avatar, Author bio (TranslateField)
- Read time
- **Show on home** (toggle) — ana səhifədə göstərilsin?
- **Show in header** (toggle) — saytın header/navbar-ında göstərilsin?
- Is featured (toggle) — blog səhifəsində böyük olaraq göstərilsin?
- Is published (toggle)
- Published at (date-time picker)

### 10.7 Əlaqələr və scope-lar

```php
class BlogPost extends Model
{
    use HasTranslations;

    public array $translatable = [
        'title', 'excerpt', 'body', 'author_bio',
    ];

    // Scope-lar
    public function scopePublished($q) { return $q->where('is_published', true); }
    public function scopeShowOnHome($q) { return $q->where('show_on_home', true); }
    public function scopeShowInHeader($q) { return $q->where('show_in_header', true); }
    public function scopeFeatured($q) { return $q->where('is_featured', true); }
    public function scopeByCategory($q, $cat) { return $q->where('category', $cat); }
}
```

---

## 11. Hero Section — Rol Slider

### 11.1 Hazırkı vəziyyət

3 slide horizontal olaraq sürüşür (4500ms interval):

| Slide | Rol | Şəkil | Qeydiyyat linki |
|-------|-----|-------|-----------------|
| 1 | Usta / Mütəxəssis | `hero-tiler-at-work.jpg` | `/register?role=master` |
| 2 | Satıcı | `hero-seller-with-tile.png` | `/register?role=seller` |
| 3 | Müştəri / Alıcı | `hero-customer-in-tile-store.jpg` | `/register?role=buyer` |

### 11.2 Backend tələbi

Bu bölmə **statik qala bilər** — 3 rol sabitdir. Mətnlər `translations` cədvəlindən idarə olunur:

| Key | Məzmun |
|-----|--------|
| `home.roles.master_tag` | Usta ol |
| `home.roles.master_title` | İşini genişləndir |
| `home.roles.master_line1` / `line2` | Açıqlama mətnləri |
| `home.roles.seller_tag` | Satıcı ol |
| `home.roles.seller_title` | Məhsullarını paylaş |
| `home.roles.customer_tag` | Müştəri ol |
| `home.roles.customer_title` | Hər şey bir yerdə |

---

## 12. Xidmət Zolağı (Service Strip / Trust Badges)

### 12.1 Hazırkı vəziyyət

4 trust badge — hər birində ikon + 2 sətir mətn:

| İkon | Başlıq | Alt mətn |
|------|--------|----------|
| icon-user-grey.svg | Sertifikatlı ustalar | reyting və rəylərlə |
| icon-truck-grey.svg | Sürətli çatdırılma | bütün bölgələrə |
| icon-shield-grey.svg | Etibarlı ödəniş | 3D Secure ilə |
| icon-chat-grey.svg | Pulsuz konsultasiya | mütəxəssislərdən |

### 12.2 Backend tələbi

Mətnlər `translations` cədvəlindən idarə olunur (Bölmə 5-dəki `home.services.*` key-ləri). İkon-lar statik qalır.

---

## 13. Ana Səhifə Tənzimləmələri — Settings

### 13.1 Cədvəl: `settings` (key-value)

| Key | Tip | Qeyd |
|-----|-----|------|
| `sale_marquee_text` | json | Marquee mətni: `{"az": "SALE • OUTLET •", ...}` |
| `sale_marquee_active` | boolean | Marquee aktiv/deaktiv |
| `free_delivery_threshold` | decimal | Pulsuz çatdırılma həddi (hazırda 100 AZN) |
| `default_delivery_fee` | decimal | Standart çatdırılma haqqı (hazırda 10 AZN) |
| `featured_products_count` | integer | Ana səhifədə göstərilən seçilmiş məhsul sayı (defolt: 4) |
| `featured_specialists_count` | integer | Ana səhifədə göstərilən seçilmiş mütəxəssis sayı (defolt: 4) |
| `home_blog_count` | integer | Ana səhifədə göstərilən blog yazısı sayı (defolt: 4) |

### 13.2 Filament səhifəsi: `HomeSettingsPage`

Admin panelindəki tənzimləmələr səhifəsi (Filament Settings Page):
- Marquee mətni (TranslateField) + aktiv toggle
- Çatdırılma tənzimləmələri
- Section sayları

---

## 14. Ana Səhifə Controller-i

### 14.1 `HomeController@index`

```
Route: GET / → HomeController@index

Data:
  - hero_banner: Banner::where('position', 'hero_main')->active()->first()
  - promo_slides: Banner::where('position', 'hero_promo')->active()->ordered()->get()
  - categories: Category::roots()->active()->withCount('products')->ordered()->get()
  - campaign_banner: PromoBanner::where('section', 'campaign')->active()->first()
  - sale_products: Product::visible()->onSale()->orderByDesc('discount_percent')->take(4)->get()
  - products_banner: PromoBanner::where('section', 'products')->active()->first()
  - featured_products: Product::visible()->featured()->ordered()->take(4)->get()
  - featured_specialists: SpecialistProfile::featured()->with('user')->take(4)->get()
  - blog_posts: BlogPost::published()->showOnHome()->latest('published_at')->take(4)->get()
  - marquee_text: Setting::get('sale_marquee_text')
  - marquee_active: Setting::get('sale_marquee_active')
```

---

## 15. Migration Sırası (Bu bölmə üçün)

1. `create_banners_table`
2. `create_promo_banners_table`
3. `create_translations_table`
4. `create_settings_table`
5. `create_blog_posts_table`
6. `add_is_featured_to_products_table` — `products` cədvəlinə `is_featured` boolean əlavə
7. `add_is_featured_to_specialist_profiles_table` — `specialist_profiles` cədvəlinə `is_featured` boolean əlavə

### 15.1 Seeder-lər

1. `TranslationSeeder` — `lang/az/*.php`, `lang/ru/*.php`, `lang/en/*.php` fayllarından bütün key-value-ları `translations` cədvəlinə yazır
2. `SettingSeeder` — defolt tənzimləmələr (marquee text, çatdırılma həddi və s.)
3. `BlogCategorySeeder` — blog kateqoriyaları (əgər ayrıca cədvəl olsa)

### 15.2 Filament Resource-lar

1. `BannerResource` — hero və reklam bannerləri
2. `PromoBannerResource` — promo kod kampaniya bannerləri
3. `TranslationResource` — statik mətn tərcümələri
4. `BlogPostResource` — blog yazıları
5. `HomeSettingsPage` — ana səhifə tənzimləmələri (Filament Settings Page)

### 15.3 Controller-lər

1. `HomeController` — ana səhifə data toplama
2. `BlogController` — blog siyahı + məqalə detal
