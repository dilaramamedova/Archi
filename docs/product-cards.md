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

## Santexnika ağacı (20.11.2026)

`Archi_AZ_Santexnika_Kataloq_AZ.xlsx`: 46 kart, fayl `santexnika.php`.
- 27 santexnika kartı (S01_1–S10_3), 6 aksesuar kartı (S11_1–S11_6) və 13 komplektləşdirici kartı (S12_1–S12_13).
- Bölmə «Santexnika, su təchizatı və kanalizasiya».
- Qruplar Excel-in öz «Kateqoriyalar» vərəqindəki 12 kateqoriyadır. S11 və S12-nin «Alt qrup» sütunu ayrıca qrup olmur: aksesuarlar bir qrupda (6), komplektləşdiricilər bir qrupda (13). Menyu Excel-dəki kimi 12 bənd olaraq qalır.
- Excel-ə daxil olmayan qruplar sadə formla qalır: borular, armatur, nasos, su qızdırıcıları, kanalizasiya. Su sayğacı «Bağlayıcı və tənzimləyici armatur»a keçib.
- Köhnə siniflər slug-ları saxlanılaraq kartlara çevrilib:

  | Köhnə slug | Kart |
  |---|---|
  | `unitazlar` | S01_1 |
  | `canaqlar-eluzyuyanlar` | S02 |
  | `qarisdiricilar` | S04_1 |
  | `vannalar` | S07 |
  | `dus-kabinesi-dus-sistemi` | S08_1 |
  | `unitaz-ucun-instalyasiya` | S09_1 |
  | `traplar-ve-drenajlar` | S10_1 |
  | `elastik-slanqlar-qosulma-slanqlari` | S12_4 |

- Köhnə «Qarışdırıcılar və duş sistemləri», «Sanitar keramika» və «Santexnika aksesuarları» qrupları bağlanıb.

Qərarlar:
- Satış: hamısı `sale: unit`. Əksəriyyəti ədəd / dəst; S05_4, S10_3 yalnız ədəd; S11_6, S12_8 yalnız dəst.
- «Qabarit ölçülər — 3 rəqəm»: en, dərinlik, hündürlük üç ayrı rəqəm sahəsidir (`kart-san-en/derinlik/hundurluk`).
- «Rəng və səth — iki seçim»: iki ayrı seçim (`kart-san-reng`, `kart-san-seth`).
- «Zəmanət — seçim və rəqəm»: ortaq `kart-zemanet` + «Var» olanda `kart-zemanet-muddeti` (ay).
- «Komplektə daxildir»: hər kartın öz siyahısı, «Daxil deyil» başqa seçimlə birlikdə olmur.
- «Seçim və kod» (gizli gövdə): «Ayrıca alınır» seçiləndə kod məcburidir. «Seçim və rəqəmlər» (elektrik): «Tələb olunur» seçiləndə V və W sahələri açılır.
- «Rəqəm aralığı» (təzyiq, montaj eni, dərinlik, bərkitmə məsafəsi): min / maks (`lte`). Qənaətli su buraxma həcmi tam həcmdən böyük ola bilməz.
- «Axtarışlı siyahı və ya kod» (uyğun model / sistem): mətn sahəsi. Hər fərqli ad öz slug-unu alır.

## Elektrik ağacı (23.11.2026)

`Archi_AZ_Elektrik_Kataloq_AZ.xlsx`: 43 kart, fayl `elektrik.php`.
- Bölmə «Elektrik, işıqlandırma, zəif cərəyan sistemləri və ağıllı ev».
- Qruplar Excel-in 4 blokudur:
  - «Elektrik quraşdırma məhsulları» (E01_01–E01_06);
  - «İşıqlandırma» (E02_01–E02_14);
  - «İnternet, rabitə və təhlükəsizlik» (E03_01–E03_16);
  - «Ağıllı ev və avtomatlaşdırma» (E04_01–E04_07).
- «Alt qrup» sütunu ayrıca qrup olmur.
- «Güc elektrik sistemləri» qrupu Excel-də yoxdur. Onun 9 sinfi (avtomat, lövhə, kabel …) sadə formla, sonuncu qrup kimi qalır.
- Köhnə siniflər slug-ları saxlanılaraq kartlara çevrilib:

  | Köhnə slug | Kart |
  |---|---|
  | `rozetkalar-ve-acarlar` | E01_01 Elektrik rozetkaları |
  | `isiqlandirma-cihazi-led-…` | E02_02 Tavan işıqlandırıcıları |
  | `videomusahide-cctv` | E03_06 Müşahidə kameraları |
  | `domofon-sistemi-sks-siqnalizasiya` | E03_09 Domofonlar |
  | `agilli-ev-sistemi` | E04_01 Ağıllı ev kontrollerləri |

- Köhnə «Rozetkalar və açarlar» və «Zəif cərəyan sistemləri və ağıllı ev» qrupları bağlanıb.

Qərarlar:
- **Satış.** Hamısı `sale: unit`:
  - əksəriyyəti ədəd / dəst;
  - E01_05, E01_06, E02_07, E02_08, E02_12 yalnız ədəd;
  - trek rels: ədəd / xətti metr;
  - LED lent: xətti metr / rulon / dəst;
  - lampalar: ədəd / paket;
  - profillər: xətti metr / ədəd / dəst;
  - komplektləşdiricilər: ədəd / dəst / paket;
  - E03_08, E03_13, E04_07 yalnız dəst.
- **Brend və ölkə** bütün kartlarda məcburidir (mühərrikin default davranışı).
- **Ortaq bloklar.** Hər biri bir dəfə yazılıb, bütün kartlarda eyni slug-la işlənir:
  - «Nominal gərginlik»: `kart-el-gerginlik` (min) ≤ `kart-el-gerginlik-maks` + `kart-el-cereyan-novu`;
  - «Ağıllı ev uyğunluğu»: «Var» seçiləndə sistemin adı məcburi olur, Matter və qoşulma yolu açılır;
  - «Rabitə və qoşulma»: «Yoxdur» başqa seçimlə birlikdə olmur; Wi-Fi və radio seçiləndə tezlik yazılır;
  - «Əlavə idarəetmə cihazı»: model və komplektdə olub-olmaması;
  - «Qidalanma»: batareya / akkumulyator seçiləndə tip və say məcburidir;
  - «PoE», «IP qorunma», «Rəng» + «Səth».
- **İşıq kartlarının nüvəsi.** «İşıq mənbəyi» (daxili LED / dəyişən lampa), güc, gərginlik, ölçü, material, IP, lm / K / CRI, dimm və ağıllı ev bloku. «Dəyişən lampa» seçiləndə soket və lampa sayı məcburi olur. Gömülən spot və tavan işığında «Montaj boşluğu» açılır.
- **Zəmanət.** E03 və E04 kartlarında ortaq `kart-zemanet` + `kart-zemanet-muddeti` var.
- **E03_15.** «Uyğunluq sənədi» `document` sahəsidir: fayl və ya link.
- **Mətn sahələri.** Çox hissəli dəyərlər mətn sahəsində yazılır: portlar, giriş / çıxış, yük limiti, dəstin tərkibi.

## Tekstil və Landşaft (23.11.2026)

**Tekstil** (`Archi_AZ_Tekstil_Dekor_Kataloq_AZ.xlsx`, faylı `tekstil.php`) — 39 kart, kodları T01_1–T15_6.
- **Slug-lar:** atribut slug-ları `kart-tx…` (ortaq) və `kart-txNN-N-…` (karta aid). Tavan faylı T01–T11 kodlarını tutur, ona görə prefiks ayrıdır.
- **Qruplar** Excel-in 3 blokudur:
  - Pərdələr və günəşdən mühafizə (16);
  - Ev tekstili (13 kart və sadə formlu «Hamam xalatı»);
  - İnteryer dekoru (10).
- **Köhnə siniflər:** 23 köhnə sinif slug-ı saxlanaraq karta çevrilib, 2 sinif birləşdirilib. «Dekorativ işıqlandırma cihazı» bağlanıb: lampalar Elektrik bölməsindədir.
- **Parça tərkibi:** liflərin çoxlu seçimi + «Tərkib, %» mətn sahəsi. 100%-in avtomatik yoxlaması yoxdur.
- **Fərdi ölçü:** «Fərdi ölçü» seçiləndə mövcudluq yalnız «Sifarişlə» olur (`on_order_when`).

**Landşaft** (`Archi_AZ_Landsaft_Kataloq_AZ.xlsx`, faylı `landsaft.php`) — 48 kart, kodları L01_1–L12_6.
- **Slug-lar:** `kart-ls…` / `kart-lNN-N-…`.
- **Qruplar:** Excel-in 12 alt bölməsidir, 3 naviqasiya bloku deyil. Bunun ardınca 4 sadə qrup gəlir: uşaq/idman, bağ tikililəri, landşaft işıqlandırması, drenaj.
- **Köhnə qruplar:** təkrarlanan köhnə qruplar (drenaj, MAF, hovuz) bağlanıb.
- **Vahidlər:** kq / l / m³ / ton mühərrikdə yoxdur. Torpaq, malç, toxum və çınqıl paketlə satılır, xalis miqdar «Qablaşdırma»da yazılır.
- **Dəstin tərkibi:** satış vahidinə görə şərt qoymaq olmur. Bu sahə «Tip = dəst» olanda məcburidir.

**Hər iki faylda:**
- Hər kartda «Əlavə xidmətlər» (çatdırılma, montaj …) seçimləri var. Bunlar ayrıca `kart-tx-xidmet-*` və `kart-ls-xidmet-*` atributlarıdır.
- Eyni məhsulun bir neçə bölmədə göstərilməsi (bağ mebeli ↔ mebel, terras ↔ döşəmə) hələ qurulmayıb.

## Tavanlar 38 → 20 (23.11.2026)

Mənbə: müştərinin `Tavanlar_kartlar_38-20_AZ.xlsx` və `Tavanlar_audit_AZ.docx` faylları. Faylda ID-lər prod-dakı ID-lərdir.

**Qruplar (6):**
- Tavan növləri (10 sinif);
- Karkas (110-un yeni adı): Profillər, Birləşdiricilər;
- Asqılar və bərkidicilər: Asqılar, Ankerlər, Vintlər, Klipslər;
- Kənar profilləri (113-ün yeni adı): Kənar və künc profilləri;
- Montaj hissələri (114-ün yeni adı): Montaj hissələri, Reviziya qapaqları;
- yeni «Tavan dekoru»: Tavan plintusu və qaltel.

Bağlananlar:
- 112, 115, 116 qrupları;
- 42-ci sinif (divar kağızı / boya). Onun məhsulları «Tətbiq sahəsi: Tavan» ilə göstərilməlidir, bu hələ qurulmayıb.

**Birləşmə.** Əsas sinif ID-sini və slug-ını saxlayır, adı dəyişir:

| Əsas slug | Yeni ad | İçinə birləşənlər |
|---|---|---|
| `dartma-tavan-ucun-profiller` | Profillər | 261–263 |
| `bir-seviyyeli-birlesdiriciler` | Birləşdiricilər | 269, 271, 272 |
| `birbasa-asqilar` | Asqılar | 265, 282–285 |
| `divarboyu-kunc-profilleri` | Kənar və künc profilləri | 274–276 |
| `montaj-platformalari` | Montaj hissələri | 278, 279 |
| `boyana-bilen-reviziya-qapaqlari` | Reviziya qapaqları | 281 |

**Kartların yığılması.** Köhnə K-kartları faylda `` olaraq olduğu kimi qalır. `` onları bir sinfə yığır:
- **«Növ».** Birinci sahə «Növ»-dür: məcburi və filtrdir, seçimləri keçmiş siniflərdir.
- **Görünmə.** Hər sahə yalnız gəldiyi növ(lər)də görünür.
- **Öz şərti.** Sahənin öz şərti varsa (məs. «Forma»), «Növ» ilə AND olur.
- **Məcburilik.** Sahə yalnız onu göstərən bütün növlərdə məcburi olubsa məcburidir. Məsələn, Montaj hissələrində «Material» yalnız platformada məcburi idi, indi ixtiyaridir.

**Dublikatlar bir atributa birləşir** (`move_values` cavabları köçürür):
- Material: `kart-k1/k2/k3/k4/k5-material`;
- Qoruyucu örtük;
- Birləşdirilən profil 1 / 2;
- Bərkitmə / birləşmə üsulu;
- Təchizat forması. Seçimləri «Növ»-ə görədir: tros seçimləri yalnız «Asqı trosu»nda;
- Montaj üsulu (kənar);
- Çərçivə materialı.

«Tavan növü» (786, 820) və «Uyğun sistem» (760) əvəzinə ortaq `kart-tavan-uygun-tavan` gəlir. O, bütün komplektləşdiricilərdə birinci sahədir, ankerlərdə / vintlərdə / klipslərdə isə ixtiyaridir.

**Vahidlər** (`unit_when` ilə növə bağlıdır):
- Asqılar: lm və rulon yalnız tros üçün;
- Kənar: rulon yalnız örtücü lent üçün, cüt yalnız uc qapağı üçün;
- Tavan növlərinə (akustikdən başqa) m² əlavə olunub;
- Kesson: ədəd / m²;
- Plintus: ədəd / xətti metr.

**Yeni sahələr:**
- Yanğın təhlükəsizliyi sinfi (`kart-tavan-yangin`, Euroclass / KM) bütün tavan növlərində var;
- RH %, NRC və αw Armstrong və akustik kartlardadır;
- Kesson (T10) və plintus (T11) kartları;
- uyğunluq sənədi (T02 / T08 / T09) artıq məcburi deyil (sahibkarın qərarı).

**Filtr.** «Saytda filtr = bəli» olan sahələr `filter => true` ilə gəlir. Kataloqda kart parametrləri üzrə filtr hələ ayrıca mərhələdir.

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
// və ya 'köhnə' => ['into' => 'yeni', 'set' => [<atribut> => <seçim>]] — köçən məhsula
// cavab yazılır («Növ» = köhnə sinif); köhnə ?class= / ?group= linkləri 301 ilə yeni
// sinfə gedir (card_settings.merged_into → CatalogController::mergedTarget).
'move_values' => ['köhnə-atribut' => 'yeni-atribut'], // dublikatların cavabları: seçim eyni
                                         // mətnli seçimə, rəqəm / mətn olduğu kimi
'retire_classes' => [...],               // məhsulu yoxdursa bağlanan siniflər
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
| `when` | `['attr' => <sahə slug>, 'in' => [<seçim slug>…]]` — sahə yalnız bu cavabda görünür. Şərtlər siyahısı = hər hansı biri (OR); `['all' => [şərt, şərt]]` = hamısı (AND), məs. «Növ = platforma» **və** «Forma = dairəvi» |
| `filter` | Saytda filtr (`is_filterable`) |
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
