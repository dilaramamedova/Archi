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
| **Üzlüklər** | `calculator_finishes` | Döşəmə/divar/tavan üzlükləri: hər sətir = usta haqqı + material (Ekonom/Standart/Premium) + ehtiyat % + texnologiya |
| İş tarifləri | `calculator_work_rates` | ~55 iş: hazırlıq, mühəndis, demontaj, logistika, xidmət |
| Material qiymətləri | `calculator_material_fallbacks` | Üzlük olmayan materiallar: qrunt, suvaq, yapışqan, qapı, santexnika, işıq, kabel… |
| Əmsallar | `calculator_coefficients` | Yalnız tavan hündürlüyü (format/düzülüş əmsalları ləğv olunub) |
| Material normaları | `calculator_material_norms` | Sərfiyyat normaları (qrunt, şpaklyovka, suvaq, stəjka, yapışqan, dərz, hidroizolyasiya, boya) |
| Kalkulyator parametrləri | `calculator_settings` | Contingency %, ƏDV, sahə limitləri, kabel/boru normaları, çatdırılma normaları… |
| Şəhər əmsalları | `calculator_cities` | Şəhər üzrə əmək əmsalı; Bakı = 1.00 baza |
| **Otaq növləri** | `calculator_room_types` | «Sadə» rejim normaları: nöqtələr (baza + m²-ə pay), cihazlar, su xətti, hidroizolyasiya |

## TZ 19.11.2026 (TT №2) — işıqlandırma növü, trek, LED, balkon/qarderob, PDF

Mənbə: «Texniki tapşırıq №2 — Ətraflı kalkulyator» (bəndlər 39–47). İkinci qəbul obyekti «Leyla Loft»
`tests/Fixtures/calculator/leyla_loft.php`-dədir, qəbul testləri `tests/Feature/CalculatorTz2Test.php`.

- **İşıqlandırma növü** (`App\Enums\Calculator\LightingType`: SPOT / TRACK / CHANDELIER). Otaqda `lightingType`,
  obyektdə `object.lightingType` (+ `AUTO`). Effektiv növ (`GeometryEngine`): otağın öz növü → otaq açıq `AUTO`
  deyirsə tavan → obyektin növü → tavan (asma tavan → SPOT, rənglənmiş → CHANDELIER = TT №1 davranışı).
  JS-də obyektin seçimi `lightingTouched` olmayan otaqlara köçür (`ceilingHeightMm` ilə eyni məntiq).
  Cavabda `rooms[].lightingType` effektiv növdür.
- **«Sadə» normaları** (`RoomTypeNorm::points($area, LightingType, $lightNorms)`): SPOT — `ceil(sahə / SPOT_AREA_M2)`
  (otaq növünün öz spot normasından az deyil); TRACK — `lights_track = ceil(sahə / TRACK_LIGHT_AREA_M2)`,
  `track_rail_m = round(sahə × TRACK_RAIL_M_PER_M2, 1)`; çilçıraq/asma/bra normaları hər rejimdə otaq növündən.
  Hər iki rejimdə `track_feed = ceil(şina / TRACK_RAIL_FEED_M)`, trek olan otaqda ən azı 1 — törəmə nöqtədir,
  müştəri yazmır; elektrik nöqtələrinin cəminə (kabel, demontaj) daxildir. Parametrlər «Elektrik və santexnika
  normaları» bölməsindədir (1.4 / 0.33 / 3, Leyla Loft-a görə kalibr).
- **Yeni nöqtə açarları** (`RoomTypeNorm::POINT_KEYS`): `lights_track` (əd), `track_rail_m` (x.m, `LENGTH_KEYS` —
  0.1 dəqiqliklə, qalanları tam), `led_output` (`DETAILED_ONLY_KEYS` — «Sadə» rejimdə həmişə 0).
  Trek işığının öz elektrik nöqtəsi yoxdur (şinaya taxılır) — `ELEC_LIGHT_POINT` saymır.
- **Yeni işlər / materiallar** (`database/seeders/data/calculator_tz_2026_11_19.php`, DRAFT): `ELEC_TRACK_FEED_POINT`
  (WB, `TrackFeedPointCount`), `ELEC_LED_OUTPUT_POINT` (WB, `LedOutputPointCount`, materialı yoxdur),
  `LIGHT_TRACK_RAIL_INSTALL` → `MAT_TRACK_RAIL` (FINISH, x.m), `LIGHT_TRACK_INSTALL` → `MAT_LIGHT_TRACK` (FINISH, əd).
  `above` açarı: yeni trek qiyməti Archi-nin spot qiymətindən aşağı/bərabər düşərsə, spot × 1.5 yazılır.
- **BALCONY / WARDROBE**: enum + `calculator_room_types` sətirləri (rozetka 1, açar 1, spot 0 + 0.5/m² və
  0 + 0.6667/m²; 4.8 m² → 1/1/3, 2.4 m² → 1/1/2). `RoomType::defaultOpenings()` (qarderob — qapı, balkon — şüşələmə),
  `ObjectType::optionalRoomTypes()` — mənzil/evdə 2-ci addımda «+ Balkon / lociya», «+ Qarderob» düymələri.
  Seeder `after` açarı ilə sıralamada dəhlizdən sonra yerləşdirir.
- **PDF (§39)** — `App\Services\Calculator\EstimatePdf`. Əvvəl hər sorğuda dompdf-lə yenidən render olunurdu
  (Leyla Loft, 10 səhifə: soyuq worker-də 2.3 s, ≈ 100 MB, 1.3 MB fayl); səhifə URL-ə kor-koranə keçirdi.
  İndi: snapshot + dil üzrə **bir dəfə** render, `storage/app/private/calculator-pdf/`-da saxlanır; font subset
  (73 KB); render zamanı ikinci sorğu `202` + `Retry-After: 2`, render xətası `503` + `Retry-After`.
  Brauzer (`resources/js/shared/calculator-pdf.js`) faylı `fetch` ilə alır, düymədə «PDF hazırlanır…», 202/503/429/502/504
  və şəbəkə xətasında 2 s-dən bir, 15 s-ə qədər təkrar, sonra «PDF hazırlanmadı, yenidən cəhd edin».
  Sürətli kalkulyatorun «PDF yüklə»si artıq `window.print()` deyil: `GET /api/calculator/quick-pdf` (`pdf/calculator-quick`).
- **Bakı vaxtı (§40)**: `CalculatorConfig::TIMEZONE = 'Asia/Baku'`. Baza UTC-dir; PDF başlığı, fayl adı,
  «Kalkulyator müraciətləri» siyahısı/səhifəsi Bakı vaxtı ilə. `expires_at` = Bakı günü + N gün, günün sonu (UTC-də saxlanır).
- **Təkrar müraciət (§41)**: `calculator_requests.visitor_key` (`u:{id}` / `s:{session}`), `estimate_hash`
  (normallaşdırılmış giriş + `configVersion`), `events` [{type, at}]. Eyni ziyarətçi + eyni smeta 30 dəqiqə ərzində →
  yeni sətir/snapshot yox, hadisə əlavə olunur («PDF + WhatsApp»); «Açar təslim» mövcud müraciəti lead-ə çevirir.
- **3-cü addım (§42)**: ayrıca «Əlavə xidmətlər — Yekun təmizlik» açarı silindi; təmizlik «Əlavə xidmətlər» qrupunda
  iş sətri kimi qalır və orada söndürülür (köhnə `finalCleaning: false` → `works.LOG_FINAL_CLEANING.selected = false`).
- **Sürətli kalkulyator (§47)**: «Tam təmir» seçiləndə məbləğin altında `calculator.result.design_note` (AZ/RU/EN).
- **Versiyalar**: payload v4 (`Units::upgradePayload`: v3 → `object.lightingType = AUTO`), JS `STATE_VERSION = 5`
  (`upgradeTo5`), `CALCULATOR_VERSION = 2.2`. Mətnlər `calculator_translations.php` + miqrasiya `2026_11_19_110200`.
- **Ölçülər (Archi-nin qiymətləri, 19.11)**: Sea Breeze WB 479.3 / tam 671.4 AZN/m² (dəyişməyib), «Sadə»–«Ətraflı» 0.4%.
  Leyla Loft WB 409.6 / 420.4, tam 820.3 / 828.4 AZN/m² («Sadə» / «Ətraflı», fərq 2.6% / 1.0%); 45 trek işığı, 20.0 x.m şina,
  9 qidalanma nöqtəsi; LED çıxışı yalnız «Ətraflı»da (9).

- **QA düzəlişləri (19.11)**: snapshot/müraciət səbətin sətirlərini itirirdi — düymə sorğusunda `basket` yoxdur,
  indi `build()` sessiyanın saxlanmış `basket.items`-ini götürür (xəta TT №2-dən əvvəl də var idi); sürətli PDF
  keşlənmir (`EstimatePdf::download(keep: false)`) — başlıqdakı vaxt yükləmə vaxtıdır; seçilməmiş «Yekun təmizlik»
  sətrində `LAST` → səviyyəyə görə WB / Bitirmə; otaqda «İsti döşəmə sahəsi, m²» (boş = bütün döşəmə; mühərrik
  `warmFloor.area`-nı həmişə oxuyurdu); balkon susmaya görə kafel (`RoomType::defaultFloorFamily()`); RU/EN-də
  vahidlər tərcümə olunur (`calculator-detailed.units.*`, JS `unitLabel`); desktopda yan panel ekrana sığır,
  «Geri / Növbəti» onun altına yapışıqdır. Mətnlər: miqrasiya `2026_11_19_110300`.

## TZ 17.11.2026 — mərhələlər, podratçı əlavəsi, normalar

Mənbə: «Texniki_tapsiriq_kalkulyator_Archi_AZ.docx» (L. Abdullayeva). Qəbul obyekti «Sea Breeze»
`tests/Fixtures/calculator/sea_breeze.php`-dədir, qəbul testləri `tests/Feature/CalculatorTzTest.php`.

- **Pul düsturu** (`EstimateBuilder`): Baza = işlər + Archi vasitəsilə alınan materiallar + logistika +
  xidmətlər; Əlavə = Baza × `CONTRACTOR_MARGIN_PERCENT` (15); Ehtiyat = (uyğun baza + onun əlavəsi) ×
  `CONTINGENCY_PERCENT`; Yekun = Baza + Əlavə + Ehtiyat (+ ƏDV). Sətirlər: `CONTRACTOR_MARGIN`, `CONTINGENCY`.
- **Mərhələ** hər sətirdə (`lines[].stage`): `WHITE_BOX` / `FINISH` / `null` (xidmətlər — dizayn).
  Mənbə: `calculator_work_rates.stage`, `calculator_finishes.labour_stage/material_stage`,
  `calculator_material_fallbacks.stage` (materialın öz mərhələsi işinkindən üstündür: rozetka nöqtəsi WB,
  üz hissəsi FINISH). `LAST` = logistika və yekun təmizlik → seçilmiş sonuncu mərhələ.
  `totals.stages.{WHITE_BOX,FINISH}` hər biri öz əlavəsi və ehtiyatı ilə; `totals.servicesBlock` — mərhələdən kənar.
- **Səviyyə** `object.level` = `FULL` | `WHITE_BOX`. White Box-da FINISH işləri və FINISH materialları
  susmaya görə düşür; `works[id].selected = true` ilə qayıdır.
- **Vahid sahə** `calcArea` = otaqların cəmi (`ModuleResolver::calcArea`): təmizlik, çatdırılma, qaldırma,
  dizayn, tullantı. 1-ci addımdakı sahə yalnız otaq yoxdursa işləyir; fərq → `AREA_MISMATCH`.
- **«Sadə» rejim** (`pointsTouched` = false): nöqtələr `RoomTypeNorm::points()` — `ceil(baza + pay × sahə)`,
  spot = `ceil(sahə / SPOT_AREA_M2)` yalnız SUSPENDED tavanda, işıqsız otağa 1 çilçıraq; su/kanalizasiya
  cihazlardan (`RoomTypeNorm::FIXTURE_POINTS`). `pointsTouched` olmayan köhnə payload: `points` göndərilibsə
  «Ətraflı» sayılır; köhnə `lights` → `lights_chandelier`. Cavabda `rooms[].points/fixtures` effektiv saylardır.
- **Hidroizolyasiya** yalnız `needs_waterproofing` (BATHROOM, WC): bütün döşəmə + bütün divarlar, üzlükdən asılı deyil.
- **Gipskarton tavan** (`top_coat_finish` = CEILING_PAINT): CEIL_PUTTY, CEIL_SANDING, CEIL_PRIMER_BASE + tavan boyası
  (iş və litrlə material), K_HEIGHT ilə.
- **Yeni miqdarlar**: WINDOW_SLOPES = Σ(en + 2h), WINDOW_SILL = Σ en, DOOR_OPENING_PREP = otaqlararası qapılar
  (`isEntrance` sayılmır), PASSAGE divardan və plintusdan çıxılır, `kitchenFurnitureMm` plintusdan çıxılır,
  tullantı həmişə: sahə × `CONSTRUCTION_WASTE_M3_PER_M2` (+ sökülmə), kondisioner çıxışları
  `AC_ROUTE_M_PER_UNIT` / `AC_DRAIN_M_PER_UNIT` (WB), blok FINISH; işıqlar növə görə (spot/asma/çilçıraq/bra).
- **Materiallar**: bir işə bir neçə material (`MaterialResolver::ITEMS`), boru/şit/qofro/tor-mayak/altlıq,
  santexnika və işıqlar növə görə; boya `paint_norm` → litr = sahə × qat / örtmə, qiymət litr üzrə.
  «Özüm alacağam» → `meta.estimatedTotal`, `totals.excludedMaterials`, yekuna daxil deyil.
- **Kəsim ehtiyatı tək mənbə**: üzlüklər üçün yalnız `calculator_finishes.waste_percent`; norm cədvəlindəki
  WASTE_* sətirlərindən yalnız `WASTE_SKIRTING` işləyir (admin paneldə yazılıb).
- **Data**: `database/seeders/data/calculator_tz_2026_11.php` + `CalculatorTzSeeder`. Prod miqrasiyası
  `2026_11_18_100100` `apply(fresh: false)` — mövcud qiymət/parametr/otaq növünü yazmır, yalnız çatışmayanı
  əlavə edir, mərhələ/bayraq qoyur, sətirləri yandırır/söndürür; boyanı m² → litrə bir dəfə çevirir
  (`paint_norm`). Yeni qiymətlər DRAFT-dır — admin paneldə «Təsdiq» filtri/toplu «Təsdiqlə».
- Sea Breeze (Archi-nin 17.11 qiymətləri ilə): White Box ≈ 479, tam ≈ 671 AZN/m² (qəbul: 430–480 / 620–680);
  «Sadə» ilə «Ətraflı» fərqi < 1%.

## Sadələşdirilmiş model (2026-11)

Kataloqda sinif sahələri söndürüldükdən sonra (`CATALOG_CLASSIFIER_FIELDS`) kalkulyator
da öz kod səviyyəli klassifikasiyalarından azad edildi. Məntiq:

- **Üzlük = admin sətri.** `FinishCategory` enum-u yoxdur. Hər üzlük (`LAMINATE`, `PAINT`,
  `WALL_TILE_LARGE`…) `calculator_finishes`-də bir sətirdir və öz usta haqqını, material
  qiymətini, ehtiyat faizini daşıyır. Yeni üzlük («Mikrosement») = admin paneldə yeni sətir,
  deploy lazım deyil. Üzlüyün iş sətrinin ID-si üzlüyün ID-sidir; material sətri `{ID}_MATERIAL`.
- **Texnologiya ailəsi** (`FinishFamily`: COAT, TILE, PANEL, FLOATING, SUSPENDED) hazırlıq
  zəncirini müəyyən edir — kodda qalan yeganə klassifikasiya. Boya tipli → suvaq/şpaklyovka/finiş
  qrunt; kafel tipli → kafel altı hazırlıq, yaş zonada hidroizolyasiya, dərz, yapışqan; üzən döşəmə
  → plintus; asma tavan → hamarlama yoxdur.
- **3 vəziyyət** (`SurfaceState`: BARE / PREPARED / OLD) və **3 obyekt vəziyyəti**
  (`ObjectCondition`: SHELL / PREPARED / OLD_RENOVATION). Qaba səth suvaqlanır/stəjka olunur,
  köhnə səth əvvəlcə sökülür (yaş otaqda kafel tarifi, quru otaqda yumşaq örtük tarifi).
- **Modullar otaqlardan çıxarılır**, ayrıca forma yoxdur: kondisioner = otaqdakı kondisioner
  nöqtəsi sayı; işıqlandırıcı = işıq nöqtələri; boru = su/kanalizasiya nöqtəsi × `PIPE_M_PER_POINT`;
  «Elektrik tam yenilənsin» açıqdırsa şit + nöqtə × `CABLE_M_PER_POINT` kabel; santexnika
  avadanlığı otaq növündən defolt (`RoomType::defaultFixtures()`), redaktə olunur.
- **Demontaj** yalnız köhnə təmirli obyektdə köhnə qapı/santexnika/nöqtə sayır (yeni saylar = köhnə
  saylar); tullantı obyekt sahəsindən.
- **Logistika** sahə normasıdır (`DELIVERY_AREA_PER_TRIP`, `MATERIAL_KG_PER_M2`) — kataloqda çəki
  olmadığı üçün əvvəlki «hesablana bilmir, 0 ₼» yerinə real təxmin.
- Payload versiyası **3**; v1/v2 sessiyalar `Units::upgradePayload()` və JS `upgradeSaved()`
  ilə çevrilir (köhnə vəziyyətlər, material/iş açarları). Miqrasiya
  `2026_11_04_100000_simplify_calculator_into_finish_rows` köhnə iş/fallback qiymətlərini
  üzlük sətirlərinə köçürür, istifadəsiz sətirləri deaktiv edir.

Qeyd: **boş qiymət silinmiş qiymət demək deyil.** Tarif və ya fallback qiymət boş
qalarsa, smetada həmin sətir `PRICE_MISSING` statusu ilə «qiymət dəqiqləşdirilir»
kimi görünür — heç vaxt 0 AZN kimi yox (Master Spec §19).

## Ölçü vahidləri — millimetr / metr sərhədi

**Qayda:** istifadəçi **millimetr** yazır, motor **metr**lə hesablayır. Çevirmə
bütün sistemdə **bir yerdə** baş verir: `app/Services/Calculator/Units.php`.
Kodda 1000-ə bölən başqa sətir yoxdur.

Səbəb: `calculator_work_rates`-dəki bütün tariflər **m²** və **p.m** üzrədir,
material normaları da m² üzrə. Motoru mm²-ə keçirmək adminin əl ilə yazdığı hər
qiyməti mənasız edərdi. Ona görə yalnız **giriş sərhədi** dəyişir.

| Nə | Vahid | Açar |
|---|---|---|
| Otağın uzunluğu / eni / hündürlüyü | **mm** | `lengthMm`, `widthMm`, `heightMm` |
| Qapı / pəncərə eni və hündürlüyü | **mm** | `openings[].widthMm`, `.heightMm` |
| Əl ilə həndəsə — perimetr | **mm** | `perimeterMm` |
| Obyektin sahəsi | m² | `object.area` |
| Əl ilə həndəsə — sahələr | m² | `floorArea`, `netWallArea` |
| İsti döşəmə sahəsi | m² | `warmFloor.area` |
| Kondisionerlənən sahə | m² | `hvac.conditionedArea` |
| Trassa, drenaj, kabel, boru uzunluqları | **m** | `routeMeters`, `drainMeters`, `cableMeters`… |

Son sətir qəsdən belədir: bunlar həndəsə deyil, **tarifin öz vahidində miqdar**dır
(p.m üzrə qiymətlənir) və birbaşa tarifə vurulur. Onları mm-ə keçirmək ikinci
çevirmə nöqtəsi yaradardı. Açarın adı vahidi deyir: **`Mm` ilə bitən hər açar
millimetrdir**, qalanı öz tarif vahidindədir.

Cavabda (`estimate.rooms[]`) **millimetr yalnız istifadəçinin yazdığı ölçülərdir**
(`openings[].widthMm`). `floorArea`, `netWallArea`, `perimeter`, `skirtingLength`
— hamısı m² / m, çünki tarif onları belə qiymətləndirir. Daxil edilmiş
uzunluq/en/hündürlük cavabda **təkrarlanmır**: eyni ekranda həm 4500 mm, həm 4.5 m
görmək oxucunu yanlış rəqəmə inandırır.

### Köhnə məlumatların miqrasiyası

Payload-ın **versiya nişanı** var: `v`. `v: 1` = metr, `v: 2` = millimetr.

- `calculator_sessions.inputs` — `CalculatorController::build()` hər hesablamadan
  əvvəl `Units::upgradePayload()` çağırır. Bir yol var, ona görə həm brauzerdən
  gələn sorğu, həm aylar əvvəl saxlanmış sessiya eyni yerdən keçir. Çevirmə
  **rəqəmin böyüklüyünə görə yox, versiya nişanına görə** olur.
- Brauzer gələn sorğuda **müştərinin elan etdiyi** `v`-ni oxuyur (nişan yoxdursa 1):
  keşlənmiş köhnə JS bundle hələ metr göndərirsə, o da düzgün çevrilir.
- `localStorage` (`archi-detailed-calc`) — eyni nişan, `STATE_VERSION = 2`.
  v1 blob atılmır, çevrilir (`upgradeSaved()`), istifadəçinin cavabları itmir.
- **Snapshot-lar toxunulmur.** Onlar dondurulmuş sənəddir: saxlanmış `lines` və
  `totals`-dan render olunur, heç vaxt yenidən hesablanmır (§22). Müştərinin
  əlindəki PDF-dəki rəqəm vahid dəyişikliyindən tərpənmir.

Admin paneldəki beş həndəsə defoltu da millimetrdədir:
`DEFAULT_ROOM_HEIGHT_MM` (2800), `DEFAULT_DOOR_WIDTH_MM` (900),
`DEFAULT_DOOR_HEIGHT_MM` (2100), `DEFAULT_WINDOW_WIDTH_MM` (1500),
`DEFAULT_WINDOW_HEIGHT_MM` (1400). Miqrasiya adminin yazdığı dəyəri saxlayır,
yalnız vahidi çevirir — heç bir qiymət dəyişmir.

## Hesablama motoru

`app/Services/Calculator/` — bütün hesablama burada, bir düsturla:

```
LineTotal = MAX(Quantity × UnitPrice × Π(Coefficients), MinimumCharge)
```

`MinimumCharge` **sifariş üzrə**dir, otaq üzrə yox. Bir iş bir neçə otaqda
görünürsə, minimum həmin işin bütün sətirləri arasında onların dəyərinə mütənasib
bölünür: mətbəxdə 9 m², hamamda 4 m² döşəmə kafeli bir sifarişdir — 13 m² × 15 ₼ =
195 ₼, iki dəfə 150 ₼ = 300 ₼ yox. Sətirdə `meta.minimumChargeOrder` sifarişin
tam minimumunu saxlayır.

| Sinif | Məsuliyyət |
|---|---|
| `Units` | **Yeganə mm → m çevirmə nöqtəsi** və payload versiyası |
| `CalculatorConfig` | 7 konfiqurasiya cədvəlinə yeganə giriş nöqtəsi; versiyalı keş |
| `GeometryEngine` | Otaq həndəsəsi (§6) və aqreqasiya qatı (§6.3); **vahid sərhədi** |
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
| `GET` | `/api/calculator/snapshots/{uuid}/pdf` | Smetanı PDF kimi yüklə (dompdf, snapshot-dan bir dəfə render olunur, sonra diskdən; render gedirsə `202` + `Retry-After`) |
| `GET` | `/api/calculator/quick-pdf?area&renovationType&segment` | Sürətli kalkulyatorun PDF-i (TT №2 §39) |

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
| Köhnə döşəmə örtüyünün sökülməsi | Döşəmə zonasının vəziyyəti `OLD_FINISH` → `DEM_FLOOR_LAMINATE` (yumşaq örtük tarifi), `OLD_TILE` → `DEM_FLOOR_TILE`. `existingCover` opsiyası dəqiq örtüyü deyirsə, o üstün gəlir. |
| Köhnə qapıların sökülməsi | `OLD_RENOVATION` obyektdə `installNewDoor` işarəli hər qapı yeri üçün `DEM_DOOR` avtomatik təklif olunur (istifadəçi çıxara bilər); SHELL-də heç vaxt. |
| Silikon (`TILE_SILICONE`) | **Otaq üzrə bir dəfə**, otağın perimetri qədər. Əvvəl hər kafelli zona üçün ayrıca yaranırdı: divarı da, döşəməsi də kafelli hamamda perimetr iki dəfə hesablanırdı. Birləşmə yeri səthin yox, otağın xassəsidir. |
| İriformatlı kafel | Divarda format **tarif sətrini** seçir (`TILE_INSTALL_LARGE`, 18 ₼/m²), əmsal yox — fərq onsuz da formatın əlavəsidir. Döşəmədə isə bir tarif sətri var, ona görə format orada **əmsaldan** gəlir (`K_TILE_LARGE`). Düzülüş əmsalı (`K_TILE_DIAGONAL`) hər formata tətbiq olunur. |
| Səthdəki köhnə üzlüyün sökülməsi | Yalnız **texnoloji zəncir** (zonanın `currentState`-i) qərar verir. Demontaj bloku `wallTileArea` / `ceilingGypsumArea` / `ceilingStretchArea` qəbul etmir — zona sətri otaq səviyyəsində, modul sətri obyekt səviyyəsində olduğu üçün ikisi birləşmirdi və eyni kafel iki dəfə sökülürdü. |
| Obyektin sahəsi | Bir mənbə: yazılmış `object.area`, yoxdursa otaqların cəmi. Həm yekun təmizlik, həm tullantı həcmi eyni rəqəmdən oxuyur. |
| Nöqtə sayları | Otaq addımında redaktə olunur (rozetka, açar, işıq, su, kanalizasiya…). Defolt `RoomType::defaultPoints()`-dən, otaq növü dəyişəndə sıfırlanır. |
| Sahə dəyişəndə otaqlar | İstifadəçi otaqlara toxunmayıbsa, obyekt sahəsi dəyişəndə otaqlar yenidən seed olunur — əks halda 120 m²-lik defolt otaqlar 80 yazan müştərinin smetasını sakitcə 50% şişirdirdi. |
| Qaba materialların (qrunt, şpaklyovka, yapışqan…) qiyməti | Müştərinin matrisində yox idi; motor miqdarı hesabladığına görə DRAFT fallback qiymətlərlə seed edilib və admin paneldə təsdiqlənməlidir. |

## Admin paneldəki skrinşotlar

Hər kalkulyator ekranının başında bir cümlə və saytın həmin hissəsinin şəkli var.
Şəkillər `public/assets/admin/calculator/*.png` altındadır və **canlı səhifədən**
çəkilir — köhnəldikdə yenidən çəkmək lazımdır:

```bash
C="/c/Program Files/Google/Chrome/Application/chrome.exe"
B='C:\Users\User\Herd\Archi\public\assets\admin\calculator'
"$C" --headless=new --disable-gpu --hide-scrollbars --window-size=1000,1050 \
     --virtual-time-budget=7000 --screenshot="$B\work-rates.png" \
     "http://archi.test/calculator/detailed?step=3"
```

`?step=1..6` ətraflı kalkulyatoru istənilən addımda açır. Fayl adları resursun
`explanationParts()` metodundakı `shot` açarına uyğun olmalıdır.

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

### Axtarış

- Bütün kalkulyator resursları admin panelin **yuxarı qlobal axtarışına** qoşulub
  (`$recordTitleAttribute` + `getGloballySearchableAttributes()`).
- Tərcüməli sütunlar JSON-da `\u`-escape saxlandığı üçün adi `LIKE` Azərbaycan
  hərflərini tapmır — həm qlobal, həm cədvəl axtarışı **`name->az` / `name->ru` /
  `name->en`** yollarını ayrıca gəzir. Yeni tərcüməli sütun axtarışa qoşulanda eyni
  qayda ilə yazılmalıdır.
- Saytdakı klassifikator etiketləri («Mayak / qara divar», «Laminat», «Gərilmə
  tavan»…) onları işə salan iş qiymətlərinin `search_keywords`-una yazılıb —
  seed faylında və `2026_08_28_110000_append_site_labels…` miqrasiyasında.
  Kalkulyatora yeni görünən ad əlavə edəndə onu da müvafiq işin açar sözlərinə yaz.

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
- **İki səbət heç vaxt ayrılmır.** `/cart` səhifəsində silmə və say dəyişikliyi
  qeydiyyatlı istifadəçi üçün serverə də yazılır (`DELETE /api/cart/product/{id}`,
  `POST /api/cart` upsert), server səbətindəki əlavə sətirlər isə `/cart` açılanda
  localStorage-a birləşdirilir. Kalkulyatorun 5-ci addımı səhifə yenidən yüklənmədən
  də təzələnir: başqa tabdakı dəyişiklik `storage` hadisəsi ilə, geri düyməsi ilə
  qayıdış (bfcache) `pageshow` ilə tutulur və siyahı `GET /api/cart/items` +
  localStorage-dan yenidən qurulur.

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
- `tests/Feature/CalculatorMillimetreTest.php` — mm → m sərhədi, payload versiyası və köhnə (metr) sessiyanın miqrasiyası
- `tests/Feature/EstimateEngineTest.php` — Master Spec §23 ssenariləri A–J + waste/contingency ayrılığı, snapshot sabitliyi, silikon/minimum sifariş/kafel əmsalı düzəlişləri
- `tests/Feature/AdminCalculatorResourcesTest.php` — admin ekranları və qiymət dəyişikliyinin storefront-a çatması
- `tests/Feature/CalculatorPagesTest.php` — səhifələrdə qiymətin hardcode olunmaması
- `tests/Feature/CalculatorTzTest.php` — TT №1 (17.11.2026), «Sea Breeze»
- `tests/Feature/CalculatorTz2Test.php` — TT №2 (19.11.2026), «Leyla Loft»: trek, LED, balkon/qarderob, PDF, Bakı vaxtı, təkrar müraciət

## Seed

```bash
php artisan db:seed --class=CalculatorSeeder
```

Təkrar işlətmək təhlükəsizdir: hər sətir texniki ID üzrə `updateOrCreate` edilir.
Qiymətlər müştərinin doldurduğu `02_Archi_Calculator_Price_Input_Matrix` faylından
götürülüb; sürətli kalkulyatorun 6 qiyməti **APPROVED**, qalanları **DRAFT**-dır və
Archi tərəfindən təsdiqlənməlidir.
