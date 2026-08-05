# ARCHI Marketplace — Texniki Tapşırıq

## Bölmə 5: Haqqımızda (About) Səhifəsi

> Bu sənəd backend development üçün yazılıb. Claude ilə implementasiya ediləcək.
> Tərcümə yanaşması: Bölmə 2, Bənd 0-a bax — JSON format, `solution-forest/filament-translate-field`.
> Statik UI mətnləri `translations` cədvəlindən (Bölmə 3, Bənd 5).

---

## 1. Ümumi Yanaşma

Haqqımızda səhifəsi 4 əsas bölmədən ibarətdir. Təkrarlanan bloklar (stat kartlar, dəyər kartları) admin panelindən dinamik idarə olunur — hardcoded array olmayacaq. Admin istədiyi qədər element əlavə/silə/redaktə edə bilər.

**Mövcud frontend strukturu:**

```
┌────────────────────────────────────────────────────────────┐
│ HERO: breadcrumb → tag → h1 → subtitle → 4 stat kartı    │
├────────────────────────────────────────────────────────────┤
│ STORY: şəkil (sol) + hekayə mətni + müəllif (sağ)        │
├────────────────────────────────────────────────────────────┤
│ VALUES: tag → h2 → 4 dəyər kartı (ikon + title + text)   │
├────────────────────────────────────────────────────────────┤
│ CTA BAND: dark bg → başlıq + alt yazı + 2 düymə          │
└────────────────────────────────────────────────────────────┘
```

---

## 2. Statistika Kartları (Stat Tiles)

### 2.1 Cədvəl: `about_stats`

Hazırda Blade-da hardcoded 4 stat var (`catalog`, `masters`, `orders`, `since`). Backend-də bunlar ayrıca cədvəldə saxlanılacaq — admin "+" basıb yenisini əlavə edə biləcək.

| Sahə | Tip | Qeyd |
|------|-----|------|
| `id` | bigint (PK) | |
| `value` | json | Rəqəmsal dəyər: `{"az": "860+", "ru": "860+", "en": "860+"}` |
| `label` | json | Alt yazı: `{"az": "məhsul kataloqda", "ru": "товаров в каталоге", "en": "products in catalog"}` |
| `sort_order` | integer (default: 0) | Sıralama |
| `is_active` | boolean (default: true) | Aktiv/deaktiv |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### 2.2 Model: `AboutStat`

```php
class AboutStat extends Model
{
    use HasTranslations;

    public array $translatable = ['value', 'label'];

    protected $fillable = ['value', 'label', 'sort_order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeOrdered($q) { return $q->orderBy('sort_order'); }
}
```

### 2.3 Defolt seeder data-sı

| # | Value (AZ) | Label (AZ) | Label (RU) | Label (EN) |
|---|-----------|-----------|-----------|-----------|
| 1 | 860+ | məhsul kataloqda | товаров в каталоге | products in catalog |
| 2 | 248 | təsdiqlənmiş usta | подтверждённых мастеров | verified masters |
| 3 | 12 000+ | tamamlanmış sifariş | завершённых заказов | completed orders |
| 4 | 2021 | ildən bazardayıq | с нами на рынке | on the market since |

> **Qeyd:** `value` sahəsi də JSON-dur çünki bəzi dillərdə format fərqli ola bilər (məs. `12 000+` vs `12,000+`).

---

## 3. Dəyər Kartları (Values Grid)

### 3.1 Cədvəl: `about_values`

Hazırda Blade-da hardcoded 4 dəyər var (`trust`, `quality`, `transparency`, `support`). Backend-də cədvəldən idarə olunacaq.

| Sahə | Tip | Qeyd |
|------|-----|------|
| `id` | bigint (PK) | |
| `icon` | string | İkon faylı (SVG, məs. `icon-shield-check.svg`) |
| `title` | json | Başlıq: `{"az": "Etibar", "ru": "Доверие", "en": "Trust"}` |
| `description` | json | Açıqlama: `{"az": "Hər usta sənədlə yoxlanılır...", ...}` |
| `sort_order` | integer (default: 0) | Sıralama |
| `is_active` | boolean (default: true) | Aktiv/deaktiv |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### 3.2 Model: `AboutValue`

```php
class AboutValue extends Model
{
    use HasTranslations;

    public array $translatable = ['title', 'description'];

    protected $fillable = ['icon', 'title', 'description', 'sort_order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeOrdered($q) { return $q->orderBy('sort_order'); }
}
```

### 3.3 Defolt seeder data-sı

| # | İkon | Title (AZ) | Description (AZ) |
|---|------|-----------|------------------|
| 1 | `icon-shield-check.svg` | Etibar | Hər usta sənədlə yoxlanılır, hər satıcı təsdiqlənir. Reytinq və rəylər real sifarişlərə əsaslanır. |
| 2 | `icon-star-outline.svg` | Keyfiyyət | Kataloqa yalnız sertifikatlı materiallar əlavə olunur. Zəif reytinqli xidmətlər platformada qalmır. |
| 3 | `icon-eye.svg` | Şəffaflıq | Qiymətlər açıqdır, smeta kalkulyatorla əvvəlcədən hesablanır. Gizli xərc yoxdur. |
| 4 | `icon-speech-bubble.svg` | Dəstək | Pulsuz konsultasiya və 7/24 yardım mərkəzi — təmirin hər mərhələsində yanınızdayıq. |

> Hər bir dəyərin `title` və `description` sahəsi 3 dildə (az/ru/en) JSON formatında olacaq.

---

## 4. Hekayə Bölməsi (Story Section)

Hekayə bölməsi tək bir blokdur — cədvəl lazım deyil. `settings` cədvəlindən idarə olunur.

### 4.1 Settings key-ləri

| Key | Tip | Təsvir |
|-----|-----|--------|
| `about_story_image` | string | Hekayə şəkli (fayl yolu) |
| `about_story_image_alt` | json | Şəklin alt mətni: `{"az": "Təmir mərhələsində...", ...}` |
| `about_story_tag` | json | Eyebrow mətni: `{"az": "Bizim hekayəmiz", ...}` |
| `about_story_title` | json | Başlıq: `{"az": "Niyə ARCHİ yarandı?", ...}` |
| `about_story_paragraph_1` | json | 1-ci abzas (3 dildə) |
| `about_story_paragraph_2` | json | 2-ci abzas (3 dildə) |
| `about_story_author_initials` | string | Müəllif inisialları (məs. `AM`) |
| `about_story_author_name` | string | Müəllif adı (məs. `Lala Abdullayeva`) |
| `about_story_author_role` | json | Müəllif rolu: `{"az": "Təsisçi & CEO", ...}` |

---

## 5. Hero Bölməsi

### 5.1 Settings key-ləri

| Key | Tip | Təsvir |
|-----|-----|--------|
| `about_hero_tag` | json | Eyebrow mətni: `{"az": "Haqqımızda", ...}` |
| `about_hero_title` | json | H1 başlıq: `{"az": "Tikinti və təmir — bir platformada", ...}` |
| `about_hero_subtitle` | json | Alt yazı: `{"az": "ARCHİ — materialdan etibarlı ustaya...", ...}` |

---

## 6. CTA Band Bölməsi

### 6.1 Settings key-ləri

| Key | Tip | Təsvir |
|-----|-----|--------|
| `about_cta_title` | json | Başlıq: `{"az": "ARCHİ ailəsinə qoşul", ...}` |
| `about_cta_subtitle` | json | Alt yazı: `{"az": "İstər usta ol, istər məhsullarını sat...", ...}` |
| `about_cta_button1_text` | json | Sol düymə mətni: `{"az": "Usta ol", ...}` |
| `about_cta_button1_url` | string | Sol düymə linki (defolt: `/register`) |
| `about_cta_button2_text` | json | Sağ düymə mətni: `{"az": "Satıcı ol", ...}` |
| `about_cta_button2_url` | string | Sağ düymə linki (defolt: `/sell`) |

---

## 7. Controller: `AboutController`

```php
class AboutController extends Controller
{
    public function index()
    {
        return view('pages.about', [
            'stats'  => AboutStat::active()->ordered()->get(),
            'values' => AboutValue::active()->ordered()->get(),
        ]);
    }
}
```

### 7.1 Route dəyişikliyi

```php
// Əvvəlki:
Route::get('/about', fn () => archiView('pages.about'))->name('about');

// Yeni:
Route::get('/about', [AboutController::class, 'index'])->name('about');
```

### 7.2 Blade dəyişiklikləri

**Stat kartları** — hardcoded `$stats` array əvəzinə:

```blade
{{-- Əvvəlki: @foreach ($stats as $stat) --}}
@foreach ($stats as $stat)
  <div class="about-stat ...">
    <p class="...">{{ $stat->value }}</p>
    <p class="...">{{ $stat->label }}</p>
  </div>
@endforeach
```

**Dəyər kartları** — hardcoded `$values` array əvəzinə:

```blade
{{-- Əvvəlki: @foreach ($values as $value => $icon) --}}
@foreach ($values as $val)
  <x-ui.card class="about-value ...">
    <span class="...">
      <img class="size-6" src="/assets/{{ $val->icon }}" alt="">
    </span>
    <h3 class="...">{{ $val->title }}</h3>
    <p class="...">{{ $val->description }}</p>
  </x-ui.card>
@endforeach
```

**Hero, Story, CTA mətnləri** — `__('about.xxx')` əvəzinə `settings()` helper-dən:

```blade
{{-- Hero --}}
<h1>{{ setting('about_hero_title') }}</h1>
<p>{{ setting('about_hero_subtitle') }}</p>

{{-- Story --}}
<h2>{{ setting('about_story_title') }}</h2>
<p>{{ setting('about_story_paragraph_1') }}</p>
<p>{{ setting('about_story_paragraph_2') }}</p>

{{-- CTA --}}
<h2>{{ setting('about_cta_title') }}</h2>
```

> **Qeyd:** `setting()` helper JSON sahələr üçün avtomatik cari dili qaytarmalıdır. Bu Bölmə 3-dəki `settings` modulundan gəlir. Əgər `settings` cədvəlindəki `value` JSON-dursa, `app()->getLocale()` ilə cari dildəki dəyər çıxarılır.

---

## 8. Cache Strategiyası

| Key | TTL | Invalidation |
|-----|-----|-------------|
| `about_stats` | 60 dəq | `AboutStat` observer |
| `about_values` | 60 dəq | `AboutValue` observer |

`settings` cədvəlindəki about-related key-lər artıq Bölmə 3-dəki ümumi settings cache sisteminə daxildir.

---

## 9. Admin Panel — Filament

### 9.1 Filament Resource: `AboutStatResource`

**Siyahı görünüşü:**

| Sütun | Tip |
|-------|-----|
| Sort | Drag-drop handle |
| Value | Text (cari dildə) |
| Label | Text (cari dildə) |
| Aktiv | Toggle |

**Form sahələri:**
- Value — `TranslateField` (az/ru/en) — rəqəmsal dəyər (məs. "860+")
- Label — `TranslateField` (az/ru/en) — alt açıqlama mətni
- Sort order — number input
- Is active — toggle (defolt: true)

**Toplu əməliyyat:** Aktiv et / Deaktiv et
**Drag-drop:** sort_order avtomatik yenilənir
**"+" düyməsi:** Yeni stat kartı əlavə et

### 9.2 Filament Resource: `AboutValueResource`

**Siyahı görünüşü:**

| Sütun | Tip |
|-------|-----|
| Sort | Drag-drop handle |
| İkon | Image preview (kiçik) |
| Title | Text (cari dildə) |
| Aktiv | Toggle |

**Form sahələri:**
- Icon — File upload (SVG) — ikon faylı
- Title — `TranslateField` (az/ru/en) — dəyər başlığı
- Description — `TranslateField` (az/ru/en) — açıqlama mətni (textarea)
- Sort order — number input
- Is active — toggle (defolt: true)

**Toplu əməliyyat:** Aktiv et / Deaktiv et
**Drag-drop:** sort_order avtomatik yenilənir
**"+" düyməsi:** Yeni dəyər kartı əlavə et

### 9.3 Filament Settings Səhifəsi: "Haqqımızda Səhifəsi"

Mövcud Settings Filament səhifəsinə yeni tab əlavə olunur (və ya ayrıca `AboutSettingsPage`). Bölmələr:

**Hero bölməsi:**
- `about_hero_tag` — TranslateField (eyebrow mətni)
- `about_hero_title` — TranslateField (H1 başlıq)
- `about_hero_subtitle` — TranslateField (alt yazı, textarea)

**Story bölməsi:**
- `about_story_image` — File upload (şəkil)
- `about_story_image_alt` — TranslateField
- `about_story_tag` — TranslateField (eyebrow)
- `about_story_title` — TranslateField (H2 başlıq)
- `about_story_paragraph_1` — TranslateField (textarea)
- `about_story_paragraph_2` — TranslateField (textarea)
- `about_story_author_initials` — Text input
- `about_story_author_name` — Text input
- `about_story_author_role` — TranslateField

**CTA bölməsi:**
- `about_cta_title` — TranslateField
- `about_cta_subtitle` — TranslateField
- `about_cta_button1_text` — TranslateField (sol düymə)
- `about_cta_button1_url` — Text input (URL)
- `about_cta_button2_text` — TranslateField (sağ düymə)
- `about_cta_button2_url` — Text input (URL)

---

## 10. Migration Sırası

1. `create_about_stats_table`
2. `create_about_values_table`
3. `SettingSeeder`-ə əlavə — hero, story, CTA key-ləri

### 10.1 Seeder-lər

1. `AboutStatSeeder` — 4 defolt stat (Bənd 2.3-dəki datalar, 3 dildə)
2. `AboutValueSeeder` — 4 defolt dəyər (Bənd 3.3-dəki datalar, 3 dildə)
3. `SettingSeeder`-ə əlavə — bütün `about_*` key-ləri (Bənd 4.1, 5.1, 6.1)

### 10.2 Model-lər

1. `AboutStat`
2. `AboutValue`

### 10.3 Controller-lər

1. `AboutController` — `index()` metodu

### 10.4 Filament Resource-lar

1. `AboutStatResource` — stat kartları CRUD + drag-drop
2. `AboutValueResource` — dəyər kartları CRUD + drag-drop
3. `AboutSettingsPage` (və ya mövcud Settings-ə tab) — hero, story, CTA tənzimləmələri

---

## 11. Breadcrumb

Breadcrumb strukturu:

```
Ana səhifə  →  Haqqımızda
```

`translations` cədvəlindən:
- `common.home` — "Ana səhifə"
- `about.crumb_current` — "Haqqımızda"

Breadcrumb komponenti (`<x-ui.breadcrumbs>`) artıq mövcuddur, dəyişiklik lazım deyil.
