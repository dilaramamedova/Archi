# ARCHI Marketplace — Texniki Tapşırıq

## Bölmə 2: Məhsullar

> Bu sənəd backend development üçün yazılıb. Claude ilə implementasiya ediləcək.

---

## 0. Tərcümə Yanaşması (Bütün Layihə Üçün Keçərli)

Layihədə bütün çoxdilli sahələr **JSON formatında** saxlanılır — ayrı-ayrı `_az`, `_ru`, `_en` sütunları **istifadə olunmur**.

**Paket:** `solution-forest/filament-translate-field` (^1.4) — Filament admin panelində JSON tərcümə sahələri üçün.

**DB sütunu:** `name` JSON tipində, dəyəri:
```json
{"az": "Kafel & metlax", "ru": "Плитка и метлах", "en": "Tiles & mosaic"}
```

**Laravel model-də:** `HasTranslations` trait (Spatie) və ya manual JSON cast + accessor ilə `$model->name` çağırıldıqda cari locale-a uyğun dəyər qaytarılır.

**Filament-də:** `TranslateField::make('name')` ilə hər dil üçün tab göstərilir.

**Bu qayda bütün tərcümə olunan sahələrə aiddir:** kateqoriya adları, məhsul adları, təsvirlər, xüsusiyyətlər, SEO sahələri və s.

---

## 1. Ümumi Baxış

Məhsul sistemi ARCHI marketplace-in əsasını təşkil edir. Satıcılar öz məhsullarını əlavə edir (maks. 5 ədəd), admin təsdiq edir, alıcılar kataloqda görür, səbətə atır, sifariş verir. Hər məhsul bir kateqoriyaya, bir alt kateqoriyaya və bir satıcıya aid olur.

---

## 2. Kateqoriya Sistemi

### 2.1 Kateqoriya Modeli (`Category`)

İki səviyyəli kateqoriya sistemi: **Kateqoriya → Alt kateqoriya**.

**Cədvəl: `categories`**

| Sahə | Tip | Qeyd |
|------|-----|------|
| `id` | bigint (PK) | |
| `parent_id` | bigint (nullable, FK → categories.id) | NULL = əsas kateqoriya, dolu = alt kateqoriya |
| `slug` | string (unique) | URL-friendly identifikator (məs. `kafel-metlax`) |
| `name` | json | Tərcümə olunan ad: `{"az": "Kafel & metlax", "ru": "Плитка", "en": "Tiles"}` |
| `icon` | string (nullable) | İkon faylı (SVG — navbar, sidebar, filter panellərdə istifadə olunur) |
| `image` | string (nullable) | Kateqoriya şəkli (ana səhifədə, kataloq başlığında göstərilən böyük thumbnail) |
| `sort_order` | integer (default: 0) | Sıralama |
| `is_active` | boolean (default: true) | Aktiv/deaktiv |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Əlaqələr:**
- `parent()` — BelongsTo(Category) — null olduqda əsas kateqoriyadır
- `children()` — HasMany(Category) — alt kateqoriyalar
- `products()` — HasMany(Product)

### 2.2 Başlanğıc kateqoriyalar (Seeder)

Frontend-dən müəyyən edilən kateqoriyalar:

| Əsas Kateqoriya | Slug | İkon | Frontend-dəki say |
|----------------|------|------|-------------------|
| Kafel & metlax | `kafel-metlax` | `icon-bricks.svg` | 860 |
| Boya & emal | `boya-emal` | `icon-floor-tiles.svg` | 412 |
| Laminant & parket | `laminant-parket` | `icon-floor-tiles.svg` | 340 |
| Santexnika | `santexnika` | `icon-faucet.svg` | 296 |
| Elektrik & işıqlandırma | `elektrik-isiq` | `icon-power-plug.svg` | 188 |
| İzolyasiya & istilik | `izolyasiya-istilik` | — | 154 |
| Dam örtüyü | `dam-ortugu` | — | 340 |
| Kərpic & daş | `kerpic-das` | — | 340 |
| Sement & qarışıqlar | `sement-qarisiq` | — | 340 |
| Tikinti materialları | `tikinti-materiallari` | — | — |
| Dekor və mebel | `dekor-mebel` | — | — |

**Alt kateqoriya nümunələri** (Onboarding step-3-dən):

*Kafel & metlax* altında: Divarlar, Döşemə, Tavan
*Ümumi:* Santexnika, Elektrik, İstilik sistemi, Havalandırma, Qapı və pəncərələr, Dam və fasad, Dizayn və dekor, Tikinti materialları, Landşaft

> **Qeyd:** Kateqoriyalar admin panelindən idarə olunmalıdır — əlavə, redaktə, silmə, sıralama. Seeder yalnız başlanğıc data üçündür.

---

## 3. Məhsul Modeli (`Product`)

### 3.1 Əsas cədvəl: `products`

| Sahə | Tip | Qeyd |
|------|-----|------|
| `id` | bigint (PK) | |
| `seller_profile_id` | bigint (FK → seller_profiles.id) | Məhsulun sahibi olan satıcı |
| `category_id` | bigint (FK → categories.id) | Kateqoriya (alt kateqoriya da ola bilər) |
| `slug` | string (unique) | URL-friendly identifikator |
| `name` | json | Tərcümə olunan ad: `{"az": "Keramik kafel 60x60", "ru": "...", "en": "..."}` |
| `description` | json (nullable) | Tərcümə olunan təsvir: `{"az": "...", "ru": "...", "en": "..."}` |
| `brand` | string (nullable) | Brend adı (məs. "Marca Corona", "Vitra") |
| `sku` | string (nullable, unique) | Stok kodu |
| `price` | decimal(10,2) | Cari qiymət (AZN) |
| `old_price` | decimal(10,2) (nullable) | Köhnə qiymət — endirim hesablanması üçün |
| `discount_percent` | integer (nullable) | Hesablanmış endirim faizi (məs. 48). `old_price` dəyişdikdə avtomatik hesablanır |
| `currency` | string (default: 'AZN') | Valyuta |
| `unit` | enum | Qiymət vahidi: `m2`, `piece`, `box`, `linear_m`, `hour`, `kg`, `liter` |
| `unit_content` | json (nullable) | Tərcümə olunan vahid açıqlaması: `{"az": "qutuda 1.44 m² (4 ədəd)", ...}` |
| `condition` | enum (default: 'new') | `new`, `used` |
| `stock_qty` | integer (default: 0) | Stok miqdarı |
| `stock_unit` | json (nullable) | Tərcümə olunan stok vahidi: `{"az": "m²", "ru": "м²", "en": "m²"}` |
| `stock_status` | enum (default: 'in_stock') | `in_stock`, `low_stock`, `out_of_stock` |
| `is_visible` | boolean (default: true) | Satıcı tərəfindən görünürlük toggle |
| `is_approved` | boolean (default: false) | Admin təsdiqi |
| `is_featured` | boolean (default: false) | Admin tərəfindən seçilmiş məhsul — ana səhifədə "Seçilmiş məhsullar" section-unda göstərilir |
| `is_sale` | boolean (default: false) | "Böyük endirim SALE" məhsuludur? — ana səhifədə "Böyük endirim SALE" section-unda göstərilir, "Ətraflı bax" kliklə yalnız bu məhsullar siyahılanır |
| `free_delivery` | boolean (default: false) | Pulsuz çatdırılma (admin idarə edir) |
| `return_14_days` | boolean (default: false) | 14 gün qaytarma imkanı (admin idarə edir) |
| `specifications` | json (nullable) | Texniki xüsusiyyətlər (key-value, hər biri tərcümə olunmuş — formatı Bölmə 8-ə bax) |
| `features` | json (nullable) | Xüsusiyyət siyahısı (tərcümə olunmuş bullet-point-lər — formatı Bölmə 9-a bax) |
| `frequently_bought_together` | json (nullable) | Birlikdə alınan məhsullar (product ID-ləri massivi) |
| `accessories` | json (nullable) | Əlavə məhsullar / aksesuarlar (JSON formatında) |
| `meta_title` | json (nullable) | Tərcümə olunan SEO başlıq: `{"az": "...", "ru": "...", "en": "..."}` |
| `meta_description` | json (nullable) | Tərcümə olunan SEO təsvir: `{"az": "...", "ru": "...", "en": "..."}` |
| `sort_order` | integer (default: 0) | Satıcının öz sıralaması |
| `views_count` | integer (default: 0) | Baxış sayı |
| `sales_count` | integer (default: 0) | Satış sayı |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### 3.2 Əlaqələr

| Əlaqə | Tip | Model | Qeyd |
|--------|-----|-------|------|
| `sellerProfile()` | BelongsTo | SellerProfile | Məhsulun sahibi |
| `seller()` | HasOneThrough | User | SellerProfile vasitəsilə |
| `category()` | BelongsTo | Category | |
| `images()` | HasMany | ProductImage | Sıralanmış |
| `mainImage()` | HasOne | ProductImage | `is_main = true` |
| `reviews()` | MorphMany | Review | Polymorphic rəylər |
| `wishlistedBy()` | BelongsToMany | User | `wishlists` pivot cədvəli |
| `frequentlyBoughtWith()` | — | Product | JSON sahəsindən əlaqə (accessor) |

### 3.3 Hesablanmış atributlar (Accessors)

| Accessor | Qaytarılan dəyər |
|----------|-----------------|
| `discount_label` | `"-48%"` formatında endirim mətni, `old_price` varsa |
| `is_on_sale` | `old_price !== null && old_price > price` |
| `avg_rating` | Rəylər ortalaması (1.0–5.0) |
| `review_count` | Rəy sayı |
| `is_in_stock` | `stock_status !== 'out_of_stock'` |
| `price_formatted` | `"23.90 ₼"` formatında qiymət |
| `old_price_formatted` | `"45.99 ₼"` formatında köhnə qiymət |

### 3.4 Scope-lar (Query Scopes)

| Scope | Təsvir |
|-------|--------|
| `visible()` | `is_visible = true AND is_approved = true` |
| `approved()` | `is_approved = true` |
| `inStock()` | `stock_status != 'out_of_stock'` |
| `onSale()` | `old_price IS NOT NULL AND old_price > price` |
| `byCategory($id)` | Kateqoriyaya görə (əsas və ya alt) |
| `byBrand($brand)` | Brendə görə |
| `priceBetween($min, $max)` | Qiymət aralığı |
| `search($query)` | Ad, təsvir, brend, SKU üzrə full-text axtarış |
| `featured()` | `is_featured = true` — ana səhifədə "Seçilmiş məhsullar" section-u |
| `sale()` | `is_sale = true` — ana səhifədə "Böyük endirim SALE" section-u |
| `popular()` | `views_count + sales_count` üzrə sıralama |

---

## 4. Məhsul Şəkilləri (`ProductImage`)

**Cədvəl: `product_images`**

| Sahə | Tip | Qeyd |
|------|-----|------|
| `id` | bigint (PK) | |
| `product_id` | bigint (FK → products.id) | |
| `path` | string | Faylın yolu (storage path) |
| `alt_text` | json (nullable) | Tərcümə olunan alternativ mətn: `{"az": "...", "ru": "...", "en": "..."}` |
| `is_main` | boolean (default: false) | Əsas şəkil (bir məhsulda yalnız 1) |
| `sort_order` | integer (default: 0) | Sıralama (thumbnail sırası) |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Tələblər:**
- Hər məhsul üçün **maks. 4 şəkil** (1 əsas + 3 əlavə — onboarding step-3 UI-dan)
- Dəstəklənən formatlar: JPEG, PNG
- Əsas şəkil (is_main) kataloqda və kartlarda göstərilir
- Thumbnail-lər məhsul səhifəsində sol paneldə göstərilir (84px)
- Əsas şəkil böyük ölçüdə göstərilir (556px hündürlük)

---

## 5. Məhsul Limiti — Satıcı Başına 5 Ədəd

### 5.1 Biznes qaydası

**Hər satıcı maksimum 5 məhsul əlavə edə bilər.** Bu limit server tərəfdə enforced olmalıdır.

### 5.2 İmplementasiya

```
ProductPolicy::create(User $user):
  - $user->isSeller() olmalıdır
  - $user->sellerProfile->products()->count() < 5 olmalıdır
  - Əks halda: "Maksimum 5 məhsul əlavə edə bilərsiniz" xətası
```

### 5.3 Frontend-dəki əks olunma

- Satıcı kabineti (`business-profile-products`): Başlıqda cari say göstərilir
- "Əlavə et" düyməsi limit dolduqda deaktiv olur
- Limit haqqında məlumat mesajı göstərilir

---

## 6. Məhsul Statusları və Təsdiq Axını

### 6.1 Axın

```
Satıcı məhsul yaradır
  → is_visible: true (satıcının niyyəti)
  → is_approved: false (admin hələ baxmayıb)
  → Kataloqda GÖRÜNMÜR

Admin baxır:
  ├─ Təsdiq edir → is_approved: true → Kataloqda GÖRÜNÜR
  ├─ Rədd edir → is_approved: false (qalır), satıcıya bildiriş
  └─ Geri çəkir → is_approved: false, satıcıya bildiriş

Satıcı is_visible-ı söndürür:
  → Kataloqda GÖRÜNMÜR (admin təsdiqi olsa belə)
```

### 6.2 Görünürlük qaydası

Məhsul kataloqda yalnız bu halda görünür:
```
is_visible = true AND is_approved = true
```

### 6.3 Admin panelindəki idarəetmə

Admin hər məhsul üçün bunları idarə edə bilər:

| Sahə | Admin İdarəetmə |
|------|-----------------|
| `is_approved` | Təsdiq / rədd |
| `free_delivery` | Pulsuz çatdırılma mövcuddur? |
| `return_14_days` | 14 gün qaytarma mövcuddur? |
| Məhsul detallarını görüntüləmə | Bütün sahələr read-only |
| Məhsulu silmə | Soft delete |

---

## 7. Qiymət və Endirim Sistemi

### 7.1 Qiymət sahələri

| Sahə | Təsvir | Nümunə |
|------|--------|--------|
| `price` | Cari satış qiyməti | 23.90 AZN |
| `old_price` | Köhnə qiymət (endirim üçün) | 45.99 AZN |
| `discount_percent` | Avtomatik hesablanır | 48 |
| `unit` | Qiymət vahidi | m², ədəd, qutu |

### 7.2 Endirim hesablanması

```
discount_percent = round((old_price - price) / old_price * 100)
```

- `old_price` boşdursa → endirim yoxdur
- `old_price <= price` → endirim yoxdur, `old_price` göstərilmir
- `old_price > price` → endirim badge-i göstərilir: `"-48%"` (qırmızı badge)

### 7.3 Frontend-dəki göstərilmə

- **Cari qiymət:** böyük, qalın (34px)
- **Köhnə qiymət:** kiçik, üstündən xətt çəkilmiş (strikethrough)
- **Endirim badge:** qırmızı fonda `"-48%"`
- **Vahid qeydi:** "qiymət 1 m² üçün · ƏDV daxil"

---

## 8. Specifications (Texniki Xüsusiyyətlər) — JSON

### 8.1 Format

`specifications` sahəsi JSON formatında saxlanılır. Hər key və value tərcümə olunmuşdur:

```json
[
  {
    "key": {"az": "Ölçü", "ru": "Размер", "en": "Size"},
    "value": {"az": "60 x 60 sm", "ru": "60 x 60 см", "en": "60 x 60 cm"}
  },
  {
    "key": {"az": "Qalınlıq", "ru": "Толщина", "en": "Thickness"},
    "value": {"az": "9 mm", "ru": "9 мм", "en": "9 mm"}
  },
  {
    "key": {"az": "Səth", "ru": "Поверхность", "en": "Surface"},
    "value": {"az": "Mat", "ru": "Матовая", "en": "Matte"}
  },
  {
    "key": {"az": "Material", "ru": "Материал", "en": "Material"},
    "value": {"az": "Keramoqranit", "ru": "Керамогранит", "en": "Porcelain stoneware"}
  }
]
```

### 8.2 Tələblər

- Admin və satıcı JSON key-value cütləri əlavə/redaktə edə bilər
- Filament-də `TranslateField` ilə hər dil üçün ayrı tab-da redaktə
- Frontend-də "Təsvir & xüsusiyyətlər" tab-ında cədvəl formatında göstərilir — cari locale-a uyğun key/value çəkilir

---

## 9. Features (Xüsusiyyət Siyahısı) — JSON

### 9.1 Format

`features` sahəsi — tərcümə olunmuş bullet-point siyahısıdır:

```json
[
  {
    "az": "Mat səth, sürüşməyə davamlı (R10)",
    "ru": "Матовая поверхность, нескользящая (R10)",
    "en": "Matte surface, anti-slip (R10)"
  },
  {
    "az": "Şaxtaya və nəmə davamlı — daxili/xarici üçün",
    "ru": "Морозо- и влагостойкая — для внутренних/наружных работ",
    "en": "Frost and moisture resistant — indoor/outdoor"
  },
  {
    "az": "60x60 sm · qutuda 1.44 m² (4 ədəd)",
    "ru": "60x60 см · в коробке 1.44 м² (4 шт)",
    "en": "60x60 cm · 1.44 m² per box (4 pcs)"
  }
]
```

### 9.2 Frontend-dəki göstərilmə

- Yaşıl checkmark ikonu ilə siyahı
- Məhsul məlumatları bölməsində, qiymət altında

---

## 10. Frequently Bought Together (Birlikdə Alınan Məhsullar) — JSON

### 10.1 Format

`frequently_bought_together` sahəsi — əlaqəli məhsulların ID massividir:

```json
[42, 87, 115]
```

### 10.2 Biznes məntiqi

- Admin və ya satıcı müəyyən edir hansı məhsullar birlikdə alınır
- Frontend-də "Birlikdə alınır" bölməsində göstərilir
- 3 məhsul kartı "+" işarəsi ilə
- Birləşdirilmiş qiymət hesablanır
- Qənaət məbləği göstərilir: `ayri_cem - birlesik_cem`
- "Seçilənləri səbətə at" düyməsi — bütün məhsulları birdən səbətə əlavə edir

### 10.3 Tələblər

- Yalnız aktiv və stokda olan məhsullar göstərilir
- Əgər əlaqəli məhsul silinib/deaktivdirsə → siyahıdan çıxarılır
- Birləşdirilmiş qiymətdə əlavə endirim olub-olmadığı admin tərəfindən idarə olunur

---

## 11. Accessories / Əlavə Məhsullar — JSON

### 11.1 Məqsəd

Məhsulun yanında mütləq lazım olan əlavə materiallar. Məsələn:
- Divar kağızı alırsan → kley də almalısan
- Kafel alırsan → fugə materialı da lazımdır
- Boya alırsan → rulon da lazımdır

### 11.2 Format

`accessories` sahəsi JSON formatında saxlanılır:

```json
[
  {
    "product_id": 45,
    "note": {
      "az": "Bu kafel üçün tövsiyə olunan fugə materialı",
      "ru": "Рекомендуемая затирка для этой плитки",
      "en": "Recommended grout for this tile"
    },
    "is_required": true
  },
  {
    "product_id": 78,
    "note": {
      "az": "Yapışdırıcı — hər m² üçün 3 kq lazımdır",
      "ru": "Клей — 3 кг на м²",
      "en": "Adhesive — 3 kg per m²"
    },
    "is_required": true
  },
  {
    "product_id": 92,
    "note": {
      "az": "Kəsmə üçün alət",
      "ru": "Инструмент для резки",
      "en": "Cutting tool"
    },
    "is_required": false
  }
]
```

### 11.3 Sahələr

| Sahə | Tip | Qeyd |
|------|-----|------|
| `product_id` | integer | Əlavə məhsulun ID-si |
| `note` | json (tərcümə olunan) | Niyə lazım olduğunu açıqlayan qeyd: `{"az": "...", "ru": "...", "en": "..."}` |
| `is_required` | boolean | Mütləq lazımdır? (true = xəbərdarlıq göstər) |

### 11.4 Frontend göstərilmə

- Məhsul səhifəsində ayrıca bölmə: "Bu məhsul üçün lazım olan materiallar"
- `is_required: true` olan əlavələr sarı xəbərdarlıq ilə göstərilir
- Hər əlavə məhsul: kart + qeyd + "Səbətə at" düyməsi
- Alıcı əsas məhsulu səbətə atdıqda, `is_required` əlavələr haqqında xəbərdarlıq göstərilir

### 11.5 Admin idarəetməsi

- Satıcı öz məhsullarına əlavə məhsullar bağlaya bilər
- Admin da əlavə edə bilər (cross-seller məhsulları da daxil)
- `product_id` yalnız aktiv, təsdiq olunmuş məhsullara istinad edə bilər

---

## 12. Badge-lər (Nişanlar)

### 12.1 Mövcud badge tipləri

| Badge | Mənbə | Rəng | Qeyd |
|-------|-------|------|------|
| **Yeni məhsul** | Avtomatik (yaradılma tarixi < 30 gün) | Sarı | |
| **Stokda var** | `stock_status = 'in_stock'` | Yaşıl | |
| **Endirim (-48%)** | `old_price` varsa | Qırmızı | Avtomatik hesablanır |
| **Sənin elanın** | Baxan istifadəçi = satıcı | Sarı (mine) | Yalnız satıcının öz görüntüsü |
| **Təsdiqlənmiş** | Satıcı verified badge | Yaşıl | Satıcı profili üçün |

### 12.2 Admin tərəfindən idarə olunan badge-lər (gələcək)

- **Top Satıcı** — admin manual təyin edir
- **Kampaniya** — admin kampaniya yaratdıqda avtomatik
- **Son şans** — stok azaldıqda avtomatik

---

## 13. Çatdırılma və Qaytarma

### 13.1 Pulsuz Çatdırılma

| Sahə | `free_delivery` (boolean) |
|------|--------------------------|
| **true** | "Pulsuz çatdırılma" ikonu + "100 ₼-dən yuxarı sifarişlərə" mətni göstərilir |
| **false** | Çatdırılma ikonu göstərilmir |

- Admin tərəfindən hər məhsul üçün ayrıca idarə olunur
- Səbət cəmi ≥ 100 AZN olduqda çatdırılma pulsuz (bu qlobal qayda — `settings` cədvəlindən)
- Aşağı cəmlərdə çatdırılma haqqı 10 AZN (frontend-dəki statik dəyər)

### 13.2 14 Gün Qaytarma

| Sahə | `return_14_days` (boolean) |
|------|---------------------------|
| **true** | "14 gün qaytarma" ikonu + "Açılmamış qutular üçün" mətni göstərilir |
| **false** | Qaytarma ikonu göstərilmir |

- Admin tərəfindən hər məhsul üçün ayrıca idarə olunur

---

## 14. Rəylər (Reviews) — Polymorphic

### 14.1 Cədvəl: `reviews`

| Sahə | Tip | Qeyd |
|------|-----|------|
| `id` | bigint (PK) | |
| `user_id` | bigint (FK → users.id) | Rəy yazan alıcı |
| `reviewable_type` | string | Polymorphic tip (`Product`, `SpecialistProfile`, `SellerProfile`) |
| `reviewable_id` | bigint | Rəy yazılan entity-nin ID-si |
| `rating` | tinyint | 1–5 ulduz |
| `text` | text (nullable) | Rəy mətni |
| `is_verified_purchase` | boolean (default: false) | Təsdiqlənmiş alış (sifariş tarixçəsindən yoxlanılır) |
| `is_helpful_count` | integer (default: 0) | "Faydalı oldu" say |
| `reply_text` | text (nullable) | Satıcının / ustanın cavabı |
| `reply_at` | timestamp (nullable) | Cavab tarixi |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### 14.2 "Faydalı oldu" (Helpful) sistemi

**Cədvəl: `review_helpfuls`** (pivot)

| Sahə | Tip |
|------|-----|
| `review_id` | bigint (FK) |
| `user_id` | bigint (FK) |
| `created_at` | timestamp |

- Hər istifadəçi hər rəyə yalnız 1 dəfə "faydalı" verə bilər (toggle)

### 14.3 Frontend-dəki göstərilmə

**Məhsul səhifəsində:**
- Ümumi bal: 4.3 / 5.0
- Ulduz paylanması (5-ulduz-dan 1-ulduzə: histogram barları)
- Ümumi rəy sayı + yazılı rəy sayı
- Tövsiyə faizi: "92% alıcı tövsiyə edir"
- Filter düymələri: Ən faydalı, Ən yeni, Şəkilli, 5 ulduz
- Rəy kartları: avatar, ad, "Təsdiqlənmiş alış" badge, ulduz, tarix, mətn, "Faydalı oldu" düyməsi + say

**Mütəxəssis kabinetində:**
- Filter tab-ları: hamısı, cavabsız, 5-ulduz, aşağı reytinq
- Inline cavab yazma imkanı

### 14.4 Biznes qaydaları

- Yalnız `Active` statuslu istifadəçilər rəy yaza bilər
- Bir istifadəçi bir məhsula yalnız 1 rəy yaza bilər
- `is_verified_purchase`: əgər istifadəçi bu məhsulu sifariş edib və sifariş `delivered` statusundadırsa → true
- Satıcı öz məhsullarının rəylərinə cavab yaza bilər (`reply_text`)
- Usta öz profilinin rəylərinə cavab yaza bilər
- Rəy silmə: yalnız admin

---

## 15. Seçilmişlər (Wishlist)

### 15.1 Cədvəl: `wishlists`

| Sahə | Tip |
|------|-----|
| `user_id` | bigint (FK → users.id) |
| `product_id` | bigint (FK → products.id) |
| `created_at` | timestamp |

**Composite primary key:** `(user_id, product_id)`

### 15.2 API Endpoint-ləri

| Metod | URL | Təsvir |
|-------|-----|--------|
| `POST` | `/api/wishlist/{product}` | Seçilmişlərə əlavə et |
| `DELETE` | `/api/wishlist/{product}` | Seçilmişlərdən çıxar |
| `GET` | `/api/wishlist` | Seçilmişlər siyahısı |

### 15.3 Qeydiyyatsız istifadəçi

- localStorage-da `archi-wishlist` key ilə saxlanılır
- Login olduqda → server-ə sinxronizasiya (merge — dublikatlar aradan qaldırılır)

---

## 16. Səbət (Cart) — Backend

### 16.1 Cədvəl: `cart_items`

| Sahə | Tip | Qeyd |
|------|-----|------|
| `id` | bigint (PK) | |
| `user_id` | bigint (FK → users.id) | |
| `product_id` | bigint (FK → products.id) | |
| `quantity` | integer (default: 1) | Miqdar |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### 16.2 API Endpoint-ləri

| Metod | URL | Təsvir |
|-------|-----|--------|
| `POST` | `/api/cart` | Məhsul əlavə et (`product_id`, `quantity`) |
| `PUT` | `/api/cart/{item}` | Miqdarı dəyiş |
| `DELETE` | `/api/cart/{item}` | Məhsulu sil |
| `GET` | `/api/cart` | Səbət məzmunu |
| `POST` | `/api/cart/sync` | localStorage-dan sinxronizasiya |
| `POST` | `/api/cart/promo` | Promo kod tətbiqi |

### 16.3 Promo Kod Sistemi

**Cədvəl: `promo_codes`**

| Sahə | Tip | Qeyd |
|------|-----|------|
| `id` | bigint (PK) | |
| `code` | string (unique) | Kod (məs. "ARCHI15") |
| `label` | json | Tərcümə olunan ad: `{"az": "15% endirim", "ru": "скидка 15%", "en": "15% off"}` |
| `type` | enum | `percent`, `fixed` |
| `value` | decimal(10,2) | Endirim dəyəri (15 = 15% və ya 15 AZN) |
| `min_order` | decimal(10,2) (nullable) | Minimum sifariş məbləği (məs. 200 AZN) |
| `max_uses` | integer (nullable) | Maksimum istifadə sayı |
| `used_count` | integer (default: 0) | İstifadə olunmuş say |
| `starts_at` | timestamp (nullable) | Başlanğıc tarixi |
| `expires_at` | timestamp (nullable) | Bitmə tarixi |
| `is_active` | boolean (default: true) | |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Frontend-dəki mövcud kodlar (admin seeder ilə):**

| Kod | Tip | Dəyər | Min sifariş |
|-----|-----|-------|-------------|
| `ARCHI15` | percent | 15% | — |
| `YENI10` | percent | 10% | — |
| `QIS20` | percent | 20% | 200 AZN |

### 16.4 Səbət hesablama məntiqi

```
alt_cem = SUM(item.price * item.quantity)
endirim = promo_code tətbiq edildikdə hesablanır
catdirilma = alt_cem >= 100 ? 0 : 10.00
yekun = alt_cem - endirim + catdirilma
```

---

## 17. Sifariş Sistemi

### 17.1 Cədvəl: `orders`

| Sahə | Tip | Qeyd |
|------|-----|------|
| `id` | bigint (PK) | |
| `order_number` | string (unique) | Sifariş nömrəsi (məs. "ARCHI-20260804-001") |
| `user_id` | bigint (FK → users.id) | Alıcı |
| `status` | enum | Sifariş statusu (aşağıda) |
| `subtotal` | decimal(10,2) | Ara cəm |
| `discount` | decimal(10,2) (default: 0) | Endirim məbləği |
| `promo_code` | string (nullable) | İstifadə olunan promo kod |
| `delivery_fee` | decimal(10,2) (default: 0) | Çatdırılma haqqı |
| `total` | decimal(10,2) | Yekun məbləğ |
| `delivery_address` | text (nullable) | Çatdırılma ünvanı |
| `notes` | text (nullable) | Alıcının qeydləri |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### 17.2 Cədvəl: `order_items`

| Sahə | Tip | Qeyd |
|------|-----|------|
| `id` | bigint (PK) | |
| `order_id` | bigint (FK → orders.id) | |
| `product_id` | bigint (FK → products.id) | |
| `seller_profile_id` | bigint (FK) | Satıcı (snapshot — məhsul silinə bilər) |
| `product_name` | string | Snapshot — sifariş zamanındakı ad |
| `product_price` | decimal(10,2) | Snapshot — sifariş zamanındakı qiymət |
| `quantity` | integer | |
| `total` | decimal(10,2) | `product_price * quantity` |
| `created_at` | timestamp | |

### 17.3 Sifariş statusları

| Status | Dəyər | Təsvir |
|--------|-------|--------|
| **Gözləmədə** | `pending` | Sifariş yaradılıb, ödəniş gözlənilir |
| **Təsdiqlənib** | `confirmed` | Satıcı/admin təsdiq edib |
| **Göndərilib** | `shipped` | Çatdırılmaya verilib |
| **Çatdırılıb** | `delivered` | Alıcıya çatdırılıb |
| **Ləğv edilib** | `cancelled` | Ləğv edilib (alıcı və ya admin tərəfindən) |

### 17.4 Sifariş axını

```
Alıcı "Sifarişi rəsmiləşdir" basır
  → Stok yoxlanılır (hər məhsul üçün)
  → Sifariş yaradılır (status: pending)
  → Stok azaldılır
  → Səbət təmizlənir
  → Satıcıya/satıcılara bildiriş göndərilir
  → Alıcıya təsdiq emaili

Satıcı/Admin:
  → confirmed → shipped → delivered

Ləğv:
  → Yalnız pending/confirmed statusda mümkün
  → Stok geri qaytarılır
```

---

## 18. Kataloq / Siyahı Səhifəsi — Backend Tələbləri

### 18.1 URL və Route

- **Kataloq:** `GET /catalog?category=&brand=&surface=&size=&min_price=&max_price=&in_stock=&sort=&page=`
- **Axtarış:** `GET /search?q=&tab=`

### 18.2 Sıralama seçimləri

| Sort dəyəri | Təsvir | SQL |
|-------------|--------|-----|
| `popular` | Ən populyar (defolt) | `views_count + sales_count DESC` |
| `cheap` | Əvvəlcə ucuz | `price ASC` |
| `expensive` | Əvvəlcə bahalı | `price DESC` |
| `rating` | Reytinqə görə | `avg_rating DESC` |
| `newest` | Yeni əlavələr | `created_at DESC` |

### 18.3 Filter parametrləri

| Parametr | Tip | Təsvir |
|----------|-----|--------|
| `category` | string (slug) | Kateqoriya slug-ı |
| `brand` | string[] | Brend adları (çoxlu seçim) |
| `surface` | string[] | Səth tipi: mat, parlaq, struktur |
| `size` | string[] | Ölçü: "30x30", "60x60" və s. |
| `min_price` | decimal | Minimum qiymət |
| `max_price` | decimal | Maksimum qiymət |
| `in_stock` | boolean | Yalnız stokda olanlar |
| `sort` | string | Sıralama |
| `page` | integer | Səhifə nömrəsi |

### 18.4 Cavab formatı

Pagination ilə məhsul siyahısı. Hər məhsulda: id, slug, name, price, old_price, discount_percent, main_image, category, brand, avg_rating, review_count, stock_status, badges, is_wishlisted (auth user üçün).

---

## 19. Məhsul Detal Səhifəsi — Backend Tələbləri

### 19.1 URL

`GET /product/{slug}`

### 19.2 Qaytarılan data

| Bölmə | Sahələr |
|-------|---------|
| **Əsas** | id, name, slug, description, brand, sku, price, old_price, discount_percent, unit, unit_content, condition, stock_qty, stock_unit, stock_status |
| **Şəkillər** | images[] (path, is_main, sort_order) |
| **Kateqoriya** | category.name, category.parent.name (breadcrumb üçün) |
| **Features** | features[] (bullet-point siyahısı) |
| **Specifications** | specifications[] (key-value cütləri) |
| **Çatdırılma** | free_delivery, return_14_days |
| **Satıcı** | seller: name, logo, rating, product_count, response_time, is_verified |
| **FBT** | frequently_bought_together → products[] (id, name, price, old_price, image) |
| **Accessories** | accessories[] (product summary + note + is_required) |
| **Rəylər** | reviews (paginated): user, rating, text, date, is_verified_purchase, helpful_count, reply |
| **Review Stats** | avg_rating, total_ratings, written_reviews, rating_distribution[5..1], recommendation_percent |
| **Oxşar** | similar_products[] (eyni kateqoriyadakı digər məhsullar) |
| **Mütəxəssislər** | related_specialists[] (bu kateqoriya ilə əlaqəli ustalar) |
| **Wishlist** | is_wishlisted (cari istifadəçi üçün) |
| **Badges** | computed badges array |

### 19.3 Baxış sayğacı

Hər unikal baxışda `views_count` artırılır (session-based deduplication — eyni sessiyada eyni məhsula təkrar baxışlar sayılmır).

---

## 20. Satıcı Kabineti — Məhsul CRUD API

### 20.1 Endpoint-lər

| Metod | URL | Təsvir |
|-------|-----|--------|
| `GET` | `/business/profile/products` | Satıcının məhsul siyahısı (blade view, paginated) |
| `GET` | `/business/products/create` | Məhsul yaratma forması |
| `POST` | `/business/products` | Məhsul yarat |
| `GET` | `/business/products/{id}/edit` | Redaktə forması |
| `PUT` | `/business/products/{id}` | Məhsulu yenilə |
| `DELETE` | `/business/products/{id}` | Məhsulu sil (soft delete) |
| `PATCH` | `/business/products/{id}/toggle` | Görünürlük toggle |

### 20.2 Yaratma/redaktə sahələri

| Sahə | Tələb | Validasiya |
|------|-------|-----------|
| `name` | required | string, max:255 |
| `category_id` | required | exists:categories,id |
| `brand` | optional | string, max:255 |
| `price` | required | numeric, min:0.01 |
| `old_price` | optional | numeric, gt:price (əgər doldurulubsa) |
| `unit` | required | in:m2,piece,box,linear_m,hour,kg,liter |
| `description` | optional | string, max:5000 |
| `condition` | required | in:new,used |
| `stock_qty` | required | integer, min:0 |
| `stock_unit` | optional | string, max:50 |
| `specifications` | optional | json array of {key, value} |
| `features` | optional | json array of strings |
| `frequently_bought_together` | optional | json array of product IDs |
| `accessories` | optional | json array of {product_id, note, is_required} |
| `images[]` | required (min 1) | image, mimes:jpeg,png, max:5120 (5MB) |
| `main_image_index` | optional | integer (hansı şəkil əsasdır) |

### 20.3 Authorization

- `ProductPolicy::create` — isSeller + count < 5
- `ProductPolicy::update` — isSeller + owns the product
- `ProductPolicy::delete` — isSeller + owns the product
- `ProductPolicy::toggleVisibility` — isSeller + owns the product

---

## 21. Admin Panel — Məhsul İdarəetməsi (Filament)

### 21.1 Filament Resource: `ProductResource`

**Siyahı səhifəsi (ListProducts):**
- Sütunlar: ID, şəkil, ad, satıcı, kateqoriya, qiymət, stok, status (approved/pending), görünürlük
- Filterlər: kateqoriya, satıcı, status, stokda var/yox
- Toplu əməliyyatlar: təsdiq et, rədd et

**Baxış/redaktə səhifəsi:**
- Bütün məhsul sahələri (read-only/editable)
- `is_approved` toggle
- `free_delivery` toggle
- `return_14_days` toggle
- Şəkillər galereyası
- Rəylər siyahısı

### 21.2 Filament Resource: `CategoryResource`

- Kateqoriya CRUD (əlavə, redaktə, silmə, sıralama)
- Parent/child əlaqəsi (tree view)
- Aktiv/deaktiv toggle

### 21.3 Filament Resource: `PromoCodeResource`

- Promo kod CRUD
- İstifadə statistikası

---

## 22. Sell Səhifəsi — Mövcud Frontend Axını

### 22.1 Mövcud vəziyyət

`/sell` səhifəsi hazırda **localStorage-a** əsaslanır (backend yoxdur). Qeydiyyatsız istifadəçilər belə məhsul "yerləşdirə bilir" — data yalnız brauzerdə saxlanılır.

### 22.2 Backend-ə keçid planı

Bu səhifə **satıcı kabinetindəki "Əlavə et" düyməsinə** yönləndirilməlidir:
- Qeydiyyatsız istifadəçi → qeydiyyat səhifəsinə yönləndirmə (role=seller)
- Alıcı rolunda → "Satıcı olmaq üçün profil yeniləyin" mesajı
- Satıcı rolunda → `/business/products/create` səhifəsinə yönləndirmə
- Limit dolubsa → "Maksimum 5 məhsul" mesajı

---

## 23. Texniki Qeydlər — Claude İmplementasiya üçün

### 23.1 Migration sırası

1. `create_categories_table` — kateqoriya + alt kateqoriya
2. `create_products_table` — əsas məhsul cədvəli
3. `create_product_images_table` — şəkillər
4. `create_reviews_table` — polymorphic rəylər
5. `create_review_helpfuls_table` — faydalı sayğac pivot
6. `create_wishlists_table` — seçilmişlər
7. `create_cart_items_table` — səbət
8. `create_promo_codes_table` — promo kodlar
9. `create_orders_table` — sifarişlər
10. `create_order_items_table` — sifariş sətirləri

### 23.2 Model yaratma sırası

1. `Category` (self-referencing parent/child)
2. `Product` (+ ProductImage)
3. `Review` (polymorphic)
4. `Wishlist` (pivot)
5. `CartItem`
6. `PromoCode`
7. `Order` (+ OrderItem)

### 23.3 Seeder-lər

1. `CategorySeeder` — 11 əsas kateqoriya + alt kateqoriyalar
2. `PromoCodeSeeder` — ARCHI15, YENI10, QIS20

### 23.4 Filament Resource-lar

1. `ProductResource` — məhsul idarəetməsi
2. `CategoryResource` — kateqoriya idarəetməsi
3. `PromoCodeResource` — promo kod idarəetməsi
4. `OrderResource` — sifariş izləmə
5. `ReviewResource` — rəy moderasiyası

### 23.5 Controller-lər

1. `ProductController` — kataloq, detal, axtarış (public)
2. `Business\ProductController` — satıcı CRUD (auth)
3. `CartController` — səbət API
4. `WishlistController` — seçilmişlər API
5. `OrderController` — sifariş yaratma
6. `ReviewController` — rəy yazma, faydalı toggle

### 23.6 JSON sahələrinin cast-ları

```php
// Product model — Spatie HasTranslations trait istifadə edir
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasTranslations;

    // JSON tərcümə olunan sahələr — $model->name cari locale-a uyğun dəyər qaytarır
    public array $translatable = [
        'name', 'description', 'unit_content', 'stock_unit',
        'meta_title', 'meta_description',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'old_price' => 'decimal:2',
        'specifications' => 'array',      // JSON: [{key: {az,ru,en}, value: {az,ru,en}}, ...]
        'features' => 'array',            // JSON: [{az,ru,en}, ...]
        'frequently_bought_together' => 'array',  // JSON: [product_id, ...]
        'accessories' => 'array',          // JSON: [{product_id, note: {az,ru,en}, is_required}, ...]
        'free_delivery' => 'boolean',
        'return_14_days' => 'boolean',
        'is_visible' => 'boolean',
        'is_approved' => 'boolean',
        'condition' => ProductCondition::class,
        'unit' => ProductUnit::class,
        'stock_status' => StockStatus::class,
    ];
}

// Category model
class Category extends Model
{
    use HasTranslations;

    public array $translatable = ['name'];
}

// Filament-də istifadə (filament-translate-field paketi):
// TranslateField::make('name')
//     ->locales(['az', 'ru', 'en'])
//     ->required()
```
