# Təmir kalkulyatoru — texniki icmal

İki kalkulyator var və onlar bir konfiqurasiyanı paylaşır:

| | Sürətli kalkulyator | Ətraflı kalkulyator |
|---|---|---|
| Səhifə | `/calculator` | `/calculator/detailed` |
| Sual sayı | 3 (sahə, təmir növü, qiymət kateqoriyası) | 6 addım (Obyekt → Otaqlar → İşlər → Materiallar → Səbət → Yekun) |
| Düstur | `Sahə × 1 m² qiyməti` | `Σ MAX(miqdar × tarif × Π əmsallar, minimum) + materiallar + logistika + ehtiyat` |
| Mənbə | TZ «Sürətli təmir kalkulyatoru» v1.0 | ARCHI Calculator Master Spec v1.0 |

Hər iki kalkulyatorda **heç bir qiymət kodda deyil**. Bütün tariflər, fallback qiymətlər,
əmsallar, sərfiyyat normaları və limitlər verilənlər bazasındadır və admin paneldən
deploy tələb etmədən dəyişdirilir (TZ §30/§34, Master Spec §21/§24).

## Admin panel — «Kalkulyator» bölməsi

| Ekran | Cədvəl | Nə idarə edir |
|---|---|---|
| Sürətli kalkulyator qiymətləri | `calculator_quick_rates` | Təsdiqlənmiş 6 qiymət (2 təmir növü × 3 kateqoriya) |
| İş tarifləri | `calculator_work_rates` | 125 iş: tarif, minimum məbləğ, dəqiqlik sinfi, ehtiyata daxil olub-olmaması |
| Material fallback qiymətləri | `calculator_material_fallbacks` | Məhsul seçilməyəndə istifadə olunan kateqoriya qiymətləri (Ekonom/Standart/Premium) |
| Əmsallar | `calculator_coefficients` | Tavan hündürlüyü, kafel formatı, düzülüş, HVAC mürəkkəbliyi… |
| Material normaları | `calculator_material_norms` | Sərfiyyat normaları və texnoloji ehtiyat (waste) faizləri |
| Kalkulyator parametrləri | `calculator_settings` | Contingency %, ƏDV rejimi, sahə limitləri, lift qaydası, defolt qat sayları… |
| Şəhər əmsalları | `calculator_cities` | Şəhər üzrə əmək əmsalı; Bakı = 1.00 baza |

Qeyd: **boş qiymət silinmiş qiymət demək deyil.** Tarif və ya fallback qiymət boş
qalarsa, smetada həmin sətir `PRICE_MISSING` statusu ilə «qiymət dəqiqləşdirilir»
kimi görünür — heç vaxt 0 AZN kimi yox (Master Spec §19).

## Hesablama motoru

`app/Services/Calculator/` — bütün hesablama burada, bir düsturla:

```
LineTotal = MAX(Quantity × UnitPrice × Π(Coefficients), MinimumCharge)
```

| Sinif | Məsuliyyət |
|---|---|
| `CalculatorConfig` | 7 konfiqurasiya cədvəlinə yeganə giriş nöqtəsi; versiyalı keş |
| `GeometryEngine` | Otaq həndəsəsi (§6) və aqreqasiya qatı (§6.3) |
| `DependencyEngine` | «Hazırkı vəziyyət → hədəf üzlük» texnoloji zənciri (§9) |
| `ModuleResolver` | Elektrik, santexnika, HVAC, qapı, işıqlandırma, demontaj, xidmətlər (§11) |
| `MaterialResolver` | Material tələbatı, waste, paket sayı, kataloq/fallback/istisna (§12–13) |
| `LogisticsResolver` | Çatdırılma reysləri, əl ilə qaldırma, xarici giriş (§14) |
| `EstimateBuilder` | Yeganə `Recalculate` giriş nöqtəsi (§18.1) |
| `QuickCalculator` | Sürətli kalkulyator: validasiya, matris, ötürmə linki |

Miqdarın mənbəyi həmişə birdir: həndəsə, nöqtə sayı, ədəd, uzunluq, sahə, seçilmiş
məhsul, əl ilə daxiletmə və ya sabit xidmət. Sahə **heç vaxt** elektrik və ya
santexnikanın miqdar mənbəyi deyil (§11.1).

## API

| Metod | Ünvan | Təyinat |
|---|---|---|
| `GET` | `/api/calculator/quick-rates` | 6 qiymət + limitlər + defoltlar (brauzer bir dəfə alır) |
| `POST` | `/api/calculator/quick-estimate` | Serverdə sürətli hesablama |
| `GET` | `/api/calculator/config` | İş kataloqu, klassifikatorlar, limitlər (tariflər **daxil deyil**) |
| `POST` | `/api/calculator/estimate` | Tam smeta — vəziyyət saxlanmır |
| `POST` | `/api/calculator/sessions` | Sessiyanı saxla |
| `PUT` | `/api/calculator/sessions/{uuid}` | Sessiyanı yenilə |
| `POST` | `/api/calculator/sessions/{uuid}/snapshot` | Smetanı dondur |
| `GET` | `/api/calculator/snapshots/{uuid}` | Dondurulmuş versiyanı oxu (yenidən hesablanmır) |

Snapshot = giriş məlumatları + hesablanmış kəmiyyətlər + sətirlər + istifadə olunmuş
qiymətlər + konfiqurasiya versiyası. Tarif sonradan dəyişsə belə snapshot dəyişmir —
PDF, WhatsApp və sifariş eyni məbləği göstərir (§22).

## Sənədlərdəki açıq suallar və qərarlar

| Məsələ | Qərar |
|---|---|
| ƏDV (audit §2.5 — sənədlərdə cavabsız) | `VAT_MODE` parametri: defolt `INCLUDED` (tariflər ƏDV-ni daxil edir, ayrıca sətir yoxdur). Archi `ADDED` seçsə, ayrıca sətir kimi hesablanır. |
| Səth vəziyyəti obyekt yoxsa zona səviyyəsində (§2.1) | Zona səviyyəsində. Obyektin vəziyyəti yalnız defolt verir. |
| Asma platforma (§2.2) | Obyekt addımından çıxarılıb, «İşlər → Logistika» blokuna keçib. |
| Qapı ölçülərinin təkrarı (§2.6) | `installNewDoor` bayrağı montaj sətrini avtomatik yaradır. |
| Divar kağızı rulon hesabı (§2.9) | MVP-də m² üzrə; rulon hesabı backlog-da. |
| Şəhər əmsalları (§2.10) | `calculator_cities` cədvəli; naməlum şəhər = 1.00, bloklama yoxdur. |
| Yaş zona divarları | Hidroizolyasiya defoltu yalnız hamam/sanitar qovşaq üçün; mətbəxdə yalnız döşəmə. İstifadəçi zonanı əl ilə yaş işarələyə bilər. |
| Qaba materialların (qrunt, şpaklyovka, yapışqan…) qiyməti | Müştərinin matrisində yox idi; motor miqdarı hesabladığına görə DRAFT fallback qiymətlərlə seed edilib və admin paneldə təsdiqlənməlidir. |

## Admin panel niyə belə yazılıb

`app/Filament/Resources/Concerns/ExplainsCalculator.php` — hər kalkulyator ekranının
başındakı izah paneli. Üç suala cavab verir: bu səhifə nədir, saytda harada görünür,
dəyişiklik nə vaxt işə düşür.

Bunun texniki səbəbi var: qiymətlərin admin paneldən dəyişdirilə bilməsi TZ-nin əsas
tələbidir (§30, §34), amma `WALL_PUTTY` / `NetWallAreaSelected` görən menecer həmin
sətrə toxunmur. Ona görə:

- **Texniki ID** cədvəldə gizlədilib (istəyən sütunu açır), əsas sütun işin adıdır.
- **`quantity_source`** hərfi mənada tərcümə olunur: `NetWallAreaSelected` →
  «Qapı və pəncərə çıxılmaqla divar sahəsi (m²)». Siyahı
  `CalculatorWorkRateResource::QUANTITY_SOURCES`-dədir; yeni iş növü əlavə edəndə
  ora da bir sətir yazılmalıdır.
- **Parametrlər** səhifəsində hər açarın nə etdiyi bir cümlə ilə yazılıb
  (`CalculatorSettingResource::EXPLANATIONS`).
- **Əmsallar** səhifəsində hər əmsalın nə vaxt işə düşdüyü göstərilir
  (`CalculatorCoefficientResource::WHEN_APPLIED`) və dəyər «+15% bahalaşır» kimi
  oxunaqlı formaya çevrilir.

Diqqət: `->helperText()` həmişə `->translatable()`-dan **əvvəl** yazılmalıdır —
tərcümə plagini sahəni `Tabs`-a bükür və sonrakı metodlar həmin bükümə düşür.

## Səbətlə iş (vacib qaydalar)

Səbət iki yerdə yaşayır: qonaq üçün brauzerin yaddaşında (`localStorage` →
`archi-cart`), qeydiyyatlı istifadəçi üçün həm də serverdə. Kalkulyator ikisini
birləşdirir.

- Brauzerdən **yalnız məhsulun ID-si və sayı** göndərilir; qiymət həmişə kataloqdan
  yenidən oxunur. Yəni `localStorage`-da qiyməti dəyişməklə smetanı aldatmaq olmur.
- Xarab sətir (kəsr say, mətn ID, ID-siz köhnə format) **bütün hesablamanı dayandırmır** —
  o sətir sadəcə atılır. Əvvəllər bu, 422 xətası verib «Hesablama alınmadı» yazdırırdı.
- Bir məhsul həm material kimi seçilibsə, həm də səbətdədirsə, **iki dəfə hesablanmır**:
  kalkulyatorun hesabladığı miqdar əsas götürülür, səbət sətrində
  «Material kimi hesablanıb» yazılır.

## Analitika (TZ §45)

Sürətli kalkulyator `window.dataLayer`-ə və `archi:calculator` CustomEvent-inə
aşağıdakı hadisələri göndərir: `quick_calculator_open`,
`quick_calculator_area_changed`, `quick_calculator_renovation_type_changed`,
`quick_calculator_segment_changed`, `quick_calculator_result_viewed`,
`quick_calculator_cta_clicked`. Hər hadisə ilə `area`, `renovationType`, `segment`,
`pricePerM2`, `estimatedTotal` ötürülür — şəxsi məlumat heç vaxt (§46).

Tag manager qoşulmayıbsa heç nə pozulmur: hadisələr sadəcə heç yerə getmir.

## Testlər

- `tests/Feature/QuickCalculatorTest.php` — TZ §50 qəbul meyarları (AC-01…AC-16) və §51 nəzarət cədvəli
- `tests/Feature/EstimateEngineTest.php` — Master Spec §23 ssenariləri A–J + waste/contingency ayrılığı, snapshot sabitliyi
- `tests/Feature/AdminCalculatorResourcesTest.php` — admin ekranları və qiymət dəyişikliyinin storefront-a çatması
- `tests/Feature/CalculatorPagesTest.php` — səhifələrdə qiymətin hardcode olunmaması

## Seed

```bash
php artisan db:seed --class=CalculatorSeeder
```

Təkrar işlətmək təhlükəsizdir: hər sətir texniki ID üzrə `updateOrCreate` edilir.
Qiymətlər müştərinin doldurduğu `02_Archi_Calculator_Price_Input_Matrix` faylından
götürülüb; sürətli kalkulyatorun 6 qiyməti **APPROVED**, qalanları **DRAFT**-dır və
Archi tərəfindən təsdiqlənməlidir.
