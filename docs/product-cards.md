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

## Tavanlar ağacı (17.11.2026)

`Archi_AZ_Tavan_Kataloq_AZ.xlsx`: 9 tavan kartı (T01–T09) və 26 komplektləşdirici kartı
(K1_1–K7_4). Excel-in 4-cü səviyyəsi (Komplektləşdiricilər → alt qrup → kart) 3
səviyyəli ağaca belə yığılıb: `Tavanlar` → **`Tavan növləri`** (9 kart + sadə formlu
Kesson və boya/lambir sinfi) və hər komplektləşdirici alt qrupu üçün bir qrup (7 qrup;
plintus/qaltel «Divarboyu və dekorativ elementlər»də sadə formla). Köhnə 4 qrup bağlanıb.

Qərarlar (müştəri ilə razılaşdırılıb):
- «Siyahı / Əlaqəli siyahı» (profil kodu, uyğun model) üçün lüğət yoxdur — mətn sahəsi;
  «Uyğunluq» = tavan növlərindən çoxlu seçim + «istehsalçı / seriya / model» mətni.
- «İki / üç rəqəm», «minimum və maksimum» — ayrı rəqəm sahələri (`lte` qaydası ilə).
- Siyahı (select) yalnız standart ölçülərə: alçıpan, Armstrong, kaset modulu, Grilyato,
  dartma tavan eni. Komplektləşdirici ölçüləri sərbəst rəqəmdir.
- Dartma tavan: rulon → tam rulon (m² × en × uzunluq), kəsim → m², layihə ölçüsü və ya
  «materiallar və montaj» → sorğu.
- Komplektləşdiricilərdə istehsal ölkəsi məcburi deyil (`country_required: false`).

## Divar ağacı (18.11.2026)

`Archi_AZ_Divar_Kataloq_AZ.xlsx`: 21 üzlük kartı (W01_1–W12_2) və 16 komplektləşdirici
kartı (WK01_1–WK08), fayl `divar.php`. `Divarlar və arakəsmələr` bölməsində Excel-in hər
kateqoriyası (12) və hər komplektləşdirici alt qrupu (7) ayrıca qrupdur; «Arakəsmə
sistemləri» qalır və Şüşə bloklar ora keçib (sadə form). Köhnə siniflər slug-ları
saxlanılaraq kartlara çevrilib (`divar-kagizlari` → W01_1, `interyer-boyasi` → W02,
`dekorativ-suvaq` → W03_1, `dekorativ-kerpic-klinker-interyer-ucun` → W08,
`taxta-lambir-uzluk` → W09_1, `dekorativ-paneller-mdf…` → W09_2, `akustik-paneller` → W11,
`moldinqler-bagetler-…` → WK06_1); ümumi «Keramoqranit / Keramik plitə / …» sinfi W04-ə,
«3D panellər» W09_4-ə birləşdirilib; «Divar və tavan üzlük örtükləri» qrupu bağlanıb.

Qərarlar:
- «Seçim və ya 2 rəqəm» → **açıq siyahı** (`rules.open`): hazır ölçü seçilir və ya
  «Başqa ölçü» ilə yazılır. Siyahı plitə, kərpic və panel ölçülərinə qoyulub.
- «Hazır məhsul: ədəd; fərdi ölçü: m²» (W01_2, W07, W10, W12_1, W12_2) → `sale: unit`,
  `units: [piece, m2]` və `unit_when` (vahid formatdan asılıdır). Fərdi ölçüdə satıcı
  «AZN/m² ilə» və ya «Qiymət sorğu ilə» seçir; sorğu → `quote_when`, vahid m² yazılır.
- Rulon divar kağızı AZN/rulon (`units: [roll]`); boya, suvaq, yapışqan — tam qablaşdırma.
- Plitə, keramoqranit, mozaika, mantar — AZN/m², qablaşdırmanın sahəsi ilə (`area`).
- «Rəqəm aralığı» (örtmə sahəsi, sərfiyyat, tikiş eni, panel qalınlığı) — min / maks (`lte`).
- «Səsudma göstəricisi» — αw / NRC seçimi + əmsal (≤ 1,5); «Göstərilməyib»də əmsal yoxdur.
- Excel-in «eyni məhsul həm divar, həm döşəmə üçün → bir qeyd, uyğun kateqoriyalarda
  göstərilir» qaydası hələ yoxdur: məhsulun bir sinfi var. Bu, kataloq işidir (növbəti mərhələ).

## Agent yoxlaması (18.11.2026)

Üç Excel üzrə 6 agent (3 admin, 3 satıcı+alıcı) lokal saytda məhsul əlavə etdi. Düzəlişlər
(miqrasiya `2026_11_18_120000_apply_card_test_fixes`):
- Səbət səhifəsi kataloq qiymətini (m²) yox, satılan vahidin qiymətini (`salePrice`) göstərir,
  vahidin adı ilə («36.96 ₼ / paket»); «Sifarişlə» məhsulun sayı stokla məhdudlaşmır.
- Qiymət, endirim və stok nişanı bir yerdən: `Product::priceLabel()` («28.00 ₼ / m²»,
  «Qiymət sorğu ilə»), `discountLabel()` (iki qiymətdən hesablanır — köhnə `discount_percent`
  sütunu yox), `stockBadge()` («Sifarişlə», «Sorğu ilə»).
- Döşəmə və Tavan ölçüləri də açıq siyahıdır (Excel: «Hazır ölçü və ya rəqəmlər»).
- Fərdi ölçü (W01_2, W07, W10, W12_1, W12_2) yalnız «Sifarişlə» (`on_order_when`).
- Admin: yeni məhsulda çoxseçim boş siyahı ilə başlayır (əvvəl bir qutu hamısını işarələyirdi);
  sorğu ilə məhsula qiymət tələb olunmur; stok tam rəqəmdir; gizlənmiş sahənin cavabı
  asılı sahələri açıq saxlamır (server də); siyahısız ölçünün izahı görünür.
- Satıcı: «Qaralama saxla» brauzer yoxlamasız göndərilir; tək mümkün satış vahidi özü seçilir;
  kartlı sinifdə «Brend *».

Açıq qalanlar (növbəti mərhələ): kataloq filtrləri kart parametrlərinə görə deyil (brend
siyahısı və «Səth / Ölçü» filtrləri ümumidir); bir məhsulun bir neçə kateqoriyada göstərilməsi;
admin formasında şəkil məcburi deyil; kəsr stok (m² kəsim) üçün stok sütunu tam rəqəmdir.

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
// Bir bölmədə bir neçə qrup: 'groups' => [[slug, name, classes, plain], …]
// Sinif ayarları: sale (area | board | unit), units (unit üçün icazəli vahidlər:
// piece, lm, pack, set, pair, roll, m2), unit_when (vahid → şərt: həmin vahid yalnız
// bu cavabda seçilə bilər), on_order_when (bu cavabda mövcudluq yalnız «Sifarişlə»),
// cut_unit (rulondan kəsim: lm | m2),
// Fayl səviyyəsində: open_size_lists => true — fayldakı bütün siyahılı ölçülər açıqdır.
// quote_when (bir şərt və ya şərtlər siyahısı — hər hansı biri), country_required.
'merge' => ['köhnə' => 'yeni'],          // məhsullar köçür, köhnə sinif bağlanır
'retire_groups' => [...],                // boş qalanda bağlanan qruplar
```

Sahə: `slug`, `name`, `type` (`dropdown`, `multiselect`, `decimal`, `size`, `text`,
`textarea`, `document` — fayl (public disk, `product-docs/`, PDF/şəkil ≤ 10 MB) və ya
`https://` link), `options` — atributun özünə aiddir və eyni slug bir neçə sinifdə
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
| `rules.integer` | Tam rəqəm (boru sayı, asqı nöqtəsi sayı) |
| `rules.open` | Ölçü siyahısı açıqdır: siyahıdan seçim və ya «Başqa ölçü» (`__other__`) ilə yazılmış uzunluq × en |
| `rules.min` / `rules.max` | Rəqəmin sərhədləri (səsudma əmsalı ≤ 1,5) |
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
- **Rulon tam** (`roll_width` + `roll_length`): `sale_factor` = en × uzunluq, vahid rulon.
  `cut_unit: m2` olan sinifdə kəsim m² ilə satılır (dartma tavan).
- **Satıcının vahidi** (`unit`): satıcı icazəli vahidlərdən birini seçir (`price_basis`
  sahəsi), qiymət və stok həmin vahidlədir; «Örtmə sahəsi» (`package_size`) yazılsa,
  səhifədə ≈ AZN/m² də göstərilir. `unit_when` olan sinifdə formada yalnız cavablara
  uyğun vahidlər qalır, server də yoxlayır; sorğu ilə qiymətdə vahid avtomatik seçilir.
- **Sorğu** (`quote_when`): qiymət yoxdur, səbətə əlavə olunmur; məhsul səhifəsində
  «Hesablama tələb et» forması `consultation_requests`-ə (`source = product`) yazır.

`availability`: `in_stock` (stok sayı ilə), `on_order` (stok məhdudlaşdırmır,
`lead_time_days` göstərilir), `unavailable` (alınmır).

## Növbəti bölmə üçün

1. Excel-i oxu (`xlsx` → PHP DOM ilə, kitabxana yoxdur), alt kateqoriyaları mövcud
   siniflərlə tutuşdur və istifadəçiyə xülasə ver.
2. `database/seeders/data/product_cards/<bölmə>.php` yaz; ortaq atributları
   (`kart-zemanet`, `kart-isti-doseme` …) yenidən istifadə et.
3. Fayla miqrasiya yaz (`2026_11_18_110000_import_divar_product_cards.php` nümunədir —
   tərcümələri `firstOrCreate` ilə əlavə edir, admin redaktələri qalır) və
   `ProductCardSeeder::FILES`-a əlavə et. Slug toqquşmasını əvvəlcə yoxla: importer
   eyni slug-lu sinfi başqa bölmədən köçürər.
4. `tests/Feature/ProductCardTest.php` nümunəsi ilə yeni qaydaları testlə.
