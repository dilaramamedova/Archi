# Kateqoriya kartları (məhsul parametrləri)

Müştəri hər bölmə üçün Excel göndərir: bölmənin alt kateqoriyaları və hər birinin
**məhsul kartı** — məhsul əlavə edilərkən hansı parametrlərin soruşulduğu, hansının
məcburi olduğu, seçimlər və qiymət/stok qaydası. Bu sənəd həmin kartların saytda necə
qurulduğunun xəritəsidir. İlk bölmə: **Döşəmə örtükləri** (D01–D12,
`Archi_AZ_Doseme_Kataloq_AZ.xlsx`, 16.11.2026).

## Necə işləyir

- Kart **məlumatdır, kod deyil.** Hər bölmənin kartları bir PHP faylındadır:
  `database/seeders/data/product_cards/<bölmə>.php`. Fayl
  `php artisan product-cards:import <bölmə>` ilə bazaya yüklənir (təkrar işlətmək
  təhlükəsizdir — faylı dəyişib yenidən işlədin).
- Parametrlər mövcud `attributes` / `attribute_options` / `attribute_sub_category`
  cədvəllərindədir; kartın davranışı pivot və option sütunlarına yazılır.
- `sub_categories.card_enabled = true` olan sinifdə məhsul **həm saytda (satıcı forması),
  həm admin paneldə** həmin kartla əlavə olunur. Qalan siniflər köhnə sadə formla qalır.
  Köhnə klassifikator açarı (`CATALOG_CLASSIFIER_FIELDS`) kartlara təsir etmir.

## Döşəmə ağacı

`Döşəmələr` → **`Döşəmə örtükləri`** (yeni qrup) → 12 kart sinfi. Köhnə siniflər
slug-ları saxlanılaraq köçürülüb və adları dəyişib:

| Kod | Sinif | Slug | Satış |
|---|---|---|---|
| D01 | Laminat | `laminat` | qablaşdırma |
| D02 | Parket lövhəsi | `parket-parket-lovhesi` | qablaşdırma |
| D03 | Mühəndis parketi | `muhendislik-lovhesi` | qablaşdırma |
| D04 | Parket və massiv taxta | `parket-ve-massiv-taxta` (yeni) | qablaşdırma |
| D05 | Vinil və kvars vinil örtüklər | `spc-lvt-kvars-vinil` | qablaşdırma |
| D06 | Linoleum | `linoleum` | rulon, poqon metr |
| D07 | Keramoqranit | `keramoqranit` | qablaşdırma |
| D08 | Döşəmə plitəsi | `keramik-plite-kafel` | qablaşdırma |
| D09 | Döşəmə üçün təbii daş | `tebii-das-mermer-qranit-sist-ve-s` | ədəd/qablaşdırma; fərdi ölçü → sorğu |
| D10 | Kovrolin və xalça plitələri | `kovrolin` (+ `xalca-plitesi` birləşdirildi) | rulon və ya qablaşdırma |
| D11 | Teras taxtası | `terras-lovhesi-dpk` | bütöv taxta, qiymət bazası seçilir |
| D12 | Səki plitələri və tamet | `seki-pliteleri-ve-tamet` (yeni) | ədəd/dəst/palet |

Excel-də olmayan Mozaika, Mantar, Özüyayılan döşəmə və İdman örtüyü də bu qrupa keçib,
sadə formla qalır. Boşalan köhnə qruplar (Universal, Taxta, Polimer, Xalça) bağlanıb.
«Texniki döşəmə» və «Altlıqlar» qruplarına toxunulmayıb.

> `CatalogClassifierSeeder` ağacı yenidən qurur və pivotları silir. Ondan sonra
> həmişə `ProductCardSeeder` işləməlidir (DatabaseSeeder-də ardıcıllıq belədir).

## Kart faylının formatı

```php
'classes' => [
    'laminat' => [                       // sub_category slug (yoxdursa yaradılır)
        'code' => 'D01',
        'name' => ['az' => …, 'ru' => …, 'en' => …],
        'sale' => 'area',                // area | board
        'quote_when' => [...],           // (ixtiyari) bu cavabda qiymət yox, sorğu
        'fields' => [ <sahə>, … ],
    ],
],
'plain' => [...],                        // qrupa köçən, kartsız siniflər
'merge' => ['köhnə' => 'yeni'],          // məhsullar köçür, köhnə sinif bağlanır
'retire_groups' => [...],                // boş qalanda bağlanan qruplar
```

Sahə: `slug`, `name`, `type` (`dropdown`, `multiselect`, `decimal`, `size`, `text`,
`textarea`), `options` — atributun özünə aiddir və eyni slug bir neçə sinifdə
işlədilirsə **eyni olmalıdır** (importer fərqi görəndə dayanır). Sinfə aid olanlar:

| Açar | Mənası |
|---|---|
| `required` | Dərc üçün məcburidir (qaralama boş saxlanıla bilər) |
| `unit`, `hint` | Vahid və izah |
| `presets` | Seçim siyahısı: rəqəmlər və ya `"600×600"`. Varsa sahə **select**-dir və yalnız siyahıdakı dəyər qəbul olunur (server də yoxlayır); ölçüdə bir «L × W» seçimi (`card[id][pair]`), qarışıq uzunluqda min/maks uzunluq və en siyahının hissələrindən seçilir. Admin: Məhsul sinifləri → Atributlar → «Seçim siyahısı». Ölçülən dəyərlərə (qablaşdırmanın sahəsi) siyahı qoyulmur |
| `when` | `['attr' => <sahə slug>, 'in' => [<seçim slug>…]]` — sahə yalnız bu cavabda görünür |
| `rules.lt` / `rules.lte` | Başqa rəqəm sahəsindən kiçik / böyük deyil |
| `rules.only` | Yalnız bu seçimlərlə dərc olunur («Döşəmədə istifadə» → Bəli) |
| `rules.mixed_when` | Ölçüdə min–maks uzunluq (qarışıq uzunluqlar) |
| `rules.square_when` | Kvadrat formada uzunluq = en |
| `maps_to` | Məhsul hesabına gedir: `package_size`, `package_unit`, `roll_width`, `length_mm`, `width_mm` |

Seçim: `value` (az/ru/en), `slug`, `prompt` (seçiləndə mətn soruşulur: «Digər» →
dəqiq ad, «Bəli» → mənbə), `when` (seçim yalnız bu cavabda var), `exclusive`
(«Göstərilməyib» başqa seçimlə birlikdə olmur).

## Kod

| Sinif | Məsuliyyət |
|---|---|
| `Services\ProductCard\CardSchema` | Sinfin kartını massiv kimi qaytarır; şərtlərin yoxlanması |
| `Services\ProductCard\CardInput` | Kart cavablarını yoxlayır → `product_attribute_values` sətirləri |
| `Services\ProductCard\CardProduct` | Ümumi sahələr (brend, ölkə, kolleksiya, mövcudluq), satış vahidinin hesabı, saxlama |
| `Services\ProductCard\CardPresenter` | Məhsul səhifəsi: parametrlər, paket qiyməti, stokun m²-si |
| `Services\ProductCard\CardImporter` | Kart faylını bazaya yükləyir |
| `Filament\Resources\ProductResource\CardForm` | Admin formunda eyni kart |
| `resources/js/pages/business-product-card.js` | Satıcı formunda kartın göstərilməsi, canlı şərtlər |

## Qiymət və satış vahidi

Qiymət satıcının yazdığı kimi saxlanılır (AZN/m², teras taxtasında seçilmiş baza).
Bir **satılan vahidin** qiyməti `price × sale_factor`-dur (`Product::salePrice()`),
səbət və sifariş onu istifadə edir:

- **Qablaşdırma** (`package_size` var): `sale_factor` = qablaşdırmanın m²-si.
  Stok — qablaşdırma sayı; məhsul səhifəsi stokun m²-sini də göstərir.
- **Rulon** (`roll_width` var, qablaşdırma yox): `sale_factor` = rulonun eni, satış
  poqon metrlə; stok metrlə.
- **Taxta** (`board`): baza `piece` → 1, `lm` → uzunluq (m), `m2` → uzunluq × en.
- **Sorğu** (`quote_when`): qiymət yoxdur, səbətə əlavə olunmur; məhsul səhifəsində
  «Hesablama tələb et» forması `consultation_requests`-ə (`source = product`) yazır.

`availability`: `in_stock` (stok sayı ilə), `on_order` (stok məhdudlaşdırmır,
`lead_time_days` göstərilir), `unavailable` (alınmır).

## Növbəti bölmə üçün

1. Excel-i oxu (`xlsx` → PHP DOM ilə, kitabxana yoxdur), alt kateqoriyaları mövcud
   siniflərlə tutuşdur və istifadəçiyə xülasə ver.
2. `database/seeders/data/product_cards/<bölmə>.php` yaz; ortaq atributları
   (`kart-zemanet`, `kart-isti-doseme` …) yenidən istifadə et.
3. Fayla miqrasiya yaz (`2026_11_16_100100_import_doseme_product_cards.php` nümunədir)
   və `ProductCardSeeder::FILES`-a əlavə et.
4. `tests/Feature/ProductCardTest.php` nümunəsi ilə yeni qaydaları testlə.
