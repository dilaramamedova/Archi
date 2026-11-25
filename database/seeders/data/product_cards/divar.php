<?php

/*
|--------------------------------------------------------------------------
| Divar üzlükləri — 21 wall-finish cards (W01_1–W12_2), 12 accessory cards (WK01–WK08; WK01 = the five
| former adhesive cards) and 4 partition cards (W13_1–W13_4)
|--------------------------------------------------------------------------
|
| Source: Archi_AZ_Divar_Kataloq_AZ.xlsx (client, 18.11.2026). Format and decisions:
| docs/product-cards.md. Section «Divarlar və arakəsmələr».
|
| 24.11.2026 (TZ «Divarlar və arakəsmələr»): the client cut the tree to 7 groups in the
| prod admin; groups, names and order here mirror prod (the importer writes them from
| this file). The five adhesive cards are one class «Yapışqanlar» (WK01, «Təyinat»),
| the four partition classes got cards W13_1–W13_4, and some classes sell by m² / lm.
|
| Option values are Azerbaijani only (the site falls back to az); attribute and
| class names carry ru / en. Wall attributes are `kart-divar-*` (shared inside this
| file) or `kart-w…` (one card); none is shared with doseme.php / tavan.php.
|
| Workbook rules carried over:
| - «Rəqəm aralığı» → two numbers, min ≤ max (`lte`).
| - «Seçim və ya 2 rəqəm» → an open list (`rules.open`): a listed size or «Başqa ölçü».
| - «Hazır məhsul: ədəd; fərdi ölçü: m²» → `sale: unit` with `unit_when`; a custom
|   size may instead be priced on request (`kart-divar-ferdi-qiymet` → quote).
| - «Seçim və qısa mətn» (colour) → the colour list + the maker's colour code.
|
*/

$n = fn (string $az, string $ru, string $en): array => compact('az', 'ru', 'en');
$o = fn (string $az, string $ru, string $en, array $extra = []): array => ['value' => compact('az', 'ru', 'en')] + $extra;
// An Azerbaijani-only option; the slug comes from the text unless given.
$a = fn (string $az, array $extra = []): array => ['value' => ['az' => $az]] + $extra;

$P_NAME = $n('Dəqiq adını yazın', 'Укажите точное название', 'Enter the exact name');
$P_CODE = $n('Rəngi və ya kodunu yazın', 'Укажите цвет или код', 'Enter the colour or its code');

$yes = $o('Bəli', 'Да', 'Yes', ['slug' => 'beli']);
$no = $o('Xeyr', 'Нет', 'No', ['slug' => 'xeyr']);
$ns = $o('Göstərilməyib', 'Не указано', 'Not specified', ['slug' => 'gosterilmeyib']);
$nsX = $ns + ['exclusive' => true];
$other = fn (?array $prompt = null, string $az = 'Digər', string $slug = 'diger'): array => $a($az, ['slug' => $slug, 'prompt' => $prompt ?? $P_NAME]);
$ynn = [$yes, $no, $ns];
$when = fn (string $attr, string ...$in): array => ['attr' => $attr, 'in' => $in];

// ── Shared wall attributes ──────────────────────────────────────────────────
$color = ['slug' => 'kart-divar-reng', 'name' => $n('Rəng', 'Цвет', 'Colour'), 'type' => 'dropdown', 'required' => true, 'filter' => true, 'options' => [
    $a('Ağ', ['slug' => 'ag']), $a('Bej', ['slug' => 'bej']), $a('Boz', ['slug' => 'boz']), $a('Qara', ['slug' => 'qara']),
    $a('Qəhvəyi', ['slug' => 'qehveyi']), $a('Qırmızı', ['slug' => 'qirmizi']), $a('Çəhrayı', ['slug' => 'cehrayi']),
    $a('Narıncı', ['slug' => 'narinci']), $a('Sarı', ['slug' => 'sari']), $a('Yaşıl', ['slug' => 'yasil']),
    $a('Mavi', ['slug' => 'mavi']), $a('Bənövşəyi', ['slug' => 'benovseyi']), $a('Metal (gümüşü, qızılı)', ['slug' => 'metal']),
    $a('Ağac dekoru', ['slug' => 'agac-dekoru']), $a('Şəffaf', ['slug' => 'seffaf']), $a('Çoxrəngli', ['slug' => 'coxrengli']),
    $other($P_CODE),
]];
$colorCode = ['slug' => 'kart-divar-reng-kodu', 'name' => $n('İstehsalçının rəng kodu', 'Код цвета производителя', 'Maker’s colour code'), 'type' => 'text',
    'hint' => $n('Varsa: kataloq kodu, RAL, NCS.', 'Если есть: код каталога, RAL, NCS.', 'If any: catalogue code, RAL, NCS.')];
$surface = ['slug' => 'kart-divar-seth', 'name' => $n('Səth', 'Поверхность', 'Surface'), 'type' => 'dropdown', 'filter' => true, 'options' => [
    $a('Mat', ['slug' => 'mat']), $a('Yarımmat', ['slug' => 'yarimmat']), $a('Parlaq', ['slug' => 'parlaq']),
    $a('Fakturalı', ['slug' => 'fakturali']), $other(), $ns,
]];
$wet = ['slug' => 'kart-divar-rutubetli-otaq', 'name' => $n('Rütubətli otağa uyğunluq', 'Для влажных помещений', 'Suitable for damp rooms'), 'type' => 'dropdown', 'options' => $ynn,
    'hint' => $n('İstehsalçı məlumatına görə; birbaşa su təması ayrıca yoxlanılır.', 'По данным производителя; прямой контакт с водой проверяется отдельно.', 'Per the maker; direct water contact is checked separately.')];
$mount = ['slug' => 'kart-divar-montaj-usulu', 'name' => $n('Montaj üsulu', 'Способ монтажа', 'Installation method'), 'type' => 'multiselect', 'options' => [
    $a('Yapışqan', ['slug' => 'yapisqan']), $a('Vint', ['slug' => 'vint']), $a('Klips', ['slug' => 'klips']),
    $a('Asqı sistemi', ['slug' => 'asqi']), $a('Özüyapışan', ['slug' => 'ozuyapisan']), $other(),
],
    'hint' => $n('Yalnız istehsalçının təsdiqlədiyi üsullar.', 'Только способы, подтверждённые производителем.', 'Only the methods the maker confirms.')];
$paintable = ['slug' => 'kart-divar-boyana-bilir', 'name' => $n('Boyana bilir', 'Можно окрашивать', 'Paintable'), 'type' => 'dropdown', 'options' => $ynn];
$thick = ['slug' => 'kart-divar-qalinliq', 'name' => $n('Qalınlıq', 'Толщина', 'Thickness'), 'type' => 'decimal', 'required' => true, 'unit' => 'mm',
    'hint' => $n('Məhsulun dəqiq qalınlığı.', 'Точная толщина изделия.', 'The product’s exact thickness.')];
$size = fn (array $presets = [], bool $req = true): array => [
    'slug' => 'kart-divar-olcu', 'name' => $n('Ölçü', 'Размер', 'Size'), 'type' => 'size', 'required' => $req, 'unit' => 'mm',
    'hint' => $presets
        ? $n('Uzunluq × en. Hazır ölçünü seçin və ya «Başqa ölçü» ilə yazın.', 'Длина × ширина. Выберите размер или укажите «Другой размер».', 'Length × width. Pick a listed size or type «Another size».')
        : $n('Uzunluq × en.', 'Длина × ширина.', 'Length × width.'),
] + ($presets ? ['presets' => $presets, 'rules' => ['open' => true]] : []);
// Glass, mirror and photo murals are measured width × height.
$sizeWH = ['slug' => 'kart-divar-en-hundurluk', 'name' => $n('Ölçü (en × hündürlük)', 'Размер (ширина × высота)', 'Size (width × height)'), 'type' => 'size', 'unit' => 'mm',
    'hint' => $n('Birinci rəqəm en, ikinci hündürlük.', 'Первое число — ширина, второе — высота.', 'Width first, then height.')];
$packArea = ['slug' => 'kart-divar-qablasdirma-sahesi', 'name' => $n('Qablaşdırmanın sahəsi', 'Площадь упаковки', 'Pack area'), 'type' => 'decimal', 'required' => true, 'unit' => 'm²',
    'maps_to' => 'package_size',
    'hint' => $n('Etiketə əsasən, kəsim itkisi əlavə edilmədən.', 'По этикетке, без запаса на подрезку.', 'From the label, without cutting waste.')];
$packCount = ['slug' => 'kart-divar-qablasdirmada-say', 'name' => $n('Qablaşdırmada miqdar', 'Количество в упаковке', 'Pieces per pack'), 'type' => 'decimal', 'required' => true, 'unit' => 'ədəd',
    'rules' => ['integer' => true],
    'hint' => $n('Tək satılan məhsul üçün 1.', 'Для товара поштучно — 1.', '1 for an item sold singly.')];
$packQty = ['slug' => 'kart-divar-qablasdirma-miqdari', 'name' => $n('Qablaşdırmanın miqdarı', 'Объём / вес упаковки', 'Pack quantity'), 'type' => 'decimal', 'required' => true];
$packQtyUnit = ['slug' => 'kart-divar-qablasdirma-vahidi', 'name' => $n('Miqdarın vahidi', 'Единица количества', 'Quantity unit'), 'type' => 'dropdown', 'required' => true, 'options' => [
    $a('q', ['slug' => 'q']), $a('kq', ['slug' => 'kq']), $a('ml', ['slug' => 'ml']), $a('l', ['slug' => 'l']),
]];
$packWeight = ['slug' => 'kart-divar-qablasdirma-cekisi', 'name' => $n('Qablaşdırmanın çəkisi', 'Вес упаковки', 'Pack weight'), 'type' => 'decimal', 'required' => true, 'unit' => 'kq'];
$shelf = ['slug' => 'kart-divar-saxlama-muddeti', 'name' => $n('Saxlama müddəti', 'Срок хранения', 'Shelf life'), 'type' => 'decimal', 'unit' => 'ay',
    'hint' => $n('Açılmamış qablaşdırma üçün.', 'Для невскрытой упаковки.', 'Unopened pack.')];
$substrates = ['slug' => 'kart-divar-alt-sethler', 'name' => $n('Uyğun alt səthlər', 'Подходящие основания', 'Suitable substrates'), 'type' => 'multiselect', 'options' => [
    $a('Suvaq', ['slug' => 'suvaq']), $a('Beton', ['slug' => 'beton']), $a('Alçıpan', ['slug' => 'alcipan']),
    $a('Ağac', ['slug' => 'agac']), $a('Mövcud plitə', ['slug' => 'movcud-plite']), $other(),
],
    'hint' => $n('İstehsalçıya görə.', 'По данным производителя.', 'Per the maker.')];
$zone = ['slug' => 'kart-divar-istifade-zonasi', 'name' => $n('İstifadə zonası', 'Зона применения', 'Area of use'), 'type' => 'multiselect', 'options' => [
    $a('Quru zona', ['slug' => 'quru']), $a('Rütubətli otaq', ['slug' => 'rutubetli']), $a('Duş', ['slug' => 'dus']), $a('Hovuz', ['slug' => 'hovuz']),
],
    'hint' => $n('Yalnız istehsalçının təsdiqlədiyi tətbiqlər.', 'Только применения, подтверждённые производителем.', 'Only uses the maker confirms.')];
$compatMat = ['slug' => 'kart-divar-uygun-materiallar', 'name' => $n('Uyğun materiallar', 'Совместимые материалы', 'Compatible materials'), 'type' => 'multiselect', 'required' => true, 'options' => [
    $a('Keramik plitə', ['slug' => 'keramik']), $a('Keramoqranit', ['slug' => 'keramoqranit']), $a('Mozaika', ['slug' => 'mozaika']),
    $a('Təbii daş', ['slug' => 'tebii-das']), $a('Dekorativ kərpic və süni daş', ['slug' => 'suni-das']), $a('Dekorativ suvaq', ['slug' => 'suvaq']),
    $a('Mikrosement', ['slug' => 'mikrosement']), $a('Ağac', ['slug' => 'agac']), $a('Şüşə', ['slug' => 'suse']), $a('Güzgü', ['slug' => 'guzgu']), $other(),
],
    'hint' => $n('İstehsalçının təsdiqlədiyi örtüklər.', 'Покрытия, подтверждённые производителем.', 'Finishes the maker confirms.')];
$panelSystem = fn (bool $req = false): array => ['slug' => 'kart-divar-uygun-panel', 'name' => $n('Uyğun panel sistemi', 'Совместимая панельная система', 'Compatible panel system'), 'type' => 'multiselect', 'required' => $req, 'options' => [
    $a('Taxta və şponlu panel', ['slug' => 'taxta']), $a('MDF panel', ['slug' => 'mdf']), $a('Plastik və polimer panel', ['slug' => 'plastik']),
    $a('Gips panel', ['slug' => 'gips']), $a('Dekorativ reyka', ['slug' => 'reyka']), $a('Mantar örtük', ['slug' => 'mantar']),
    $a('Yumşaq panel', ['slug' => 'yumsaq']), $a('Akustik panel', ['slug' => 'akustik']), $a('Şüşə panel', ['slug' => 'suse']), $other(),
]];
$panelSeries = fn (bool $req = false): array => ['slug' => 'kart-divar-panel-seriyasi', 'name' => $n('Panel istehsalçısı / seriyası / kodu', 'Производитель / серия / код панели', 'Panel maker / series / code'), 'type' => 'text', 'required' => $req];
// «Rəqəm aralığı»: the maker's min and max.
$range = fn (string $slug, array $name, string $unit, ?array $hint = null): array => [
    ['slug' => "{$slug}-min", 'name' => $n($name['az'].', min', $name['ru'].', мин.', $name['en'].', min'), 'type' => 'decimal', 'unit' => $unit,
        'rules' => ['lte' => "{$slug}-maks"]] + ($hint ? ['hint' => $hint] : []),
    ['slug' => "{$slug}-maks", 'name' => $n($name['az'].', maks', $name['ru'].', макс.', $name['en'].', max'), 'type' => 'decimal', 'unit' => $unit],
];
$coverage = [
    ...$range('kart-divar-ortme', $n('Örtmə sahəsi', 'Укрывистость', 'Coverage'), '',
        $n('1 qat üçün istehsalçının aralığı; vahidi aşağıda.', 'Диапазон производителя на 1 слой; единица ниже.', 'The maker’s range for 1 coat; unit below.')),
    ['slug' => 'kart-divar-ortme-vahidi', 'name' => $n('Örtmə sahəsinin vahidi', 'Единица укрывистости', 'Coverage unit'), 'type' => 'dropdown', 'options' => [
        $a('m²/l', ['slug' => 'm2-l']), $a('m²/kq', ['slug' => 'm2-kq']),
    ]],
];
$consumption = fn (?array $hint = null): array => $range('kart-divar-serfiyyat', $n('Sərfiyyat', 'Расход', 'Consumption'), 'kq/m²', $hint);
$packCoverage = $range('kart-divar-qab-ortme', $n('Bir qablaşdırmanın örtmə sahəsi', 'Площадь покрытия одной упаковки', 'Coverage of one pack'), 'm²',
    $n('İstehsalçı aralığı.', 'Диапазон производителя.', 'The maker’s range.'));
$applyThick = ['slug' => 'kart-divar-tetbiq-qalinligi', 'name' => $n('Tətbiq qalınlığı', 'Толщина нанесения', 'Application thickness'), 'type' => 'decimal', 'unit' => 'mm'];
$length = ['slug' => 'kart-divar-uzunluq', 'name' => $n('Uzunluq', 'Длина', 'Length'), 'type' => 'decimal', 'required' => true, 'unit' => 'mm'];
$profileFor = fn (bool $req, ?array $hint = null): array => ['slug' => 'kart-divar-uygun-profil', 'name' => $n('Uyğun profil (brend, seriya, kod)', 'Совместимый профиль (бренд, серия, код)', 'Compatible profile (brand, series, code)'), 'type' => 'text', 'required' => $req]
    + ($hint ? ['hint' => $hint] : []);
$profileMaterial = ['slug' => 'kart-divar-profil-materiali', 'name' => $n('Material', 'Материал', 'Material'), 'type' => 'dropdown', 'required' => true, 'filter' => true, 'options' => [
    $a('Alüminium', ['slug' => 'aluminium']), $a('Paslanmayan polad', ['slug' => 'paslanmayan-polad']), $a('PVC', ['slug' => 'pvc']),
    $a('Pirinç', ['slug' => 'pirinc']), $other(),
]];
$sectionW = ['slug' => 'kart-divar-kesik-eni', 'name' => $n('Kəsiyin eni', 'Ширина сечения', 'Section width'), 'type' => 'decimal', 'required' => true, 'unit' => 'mm'];
$sectionD = ['slug' => 'kart-divar-kesik-derinliyi', 'name' => $n('Kəsiyin dərinliyi (çıxıntı)', 'Глубина сечения (вынос)', 'Section depth (projection)'), 'type' => 'decimal', 'required' => true, 'unit' => 'mm'];
$makerClass = ['slug' => 'kart-divar-istehsalci-sinfi', 'name' => $n('İstehsalçı sinfi', 'Класс по производителю', 'Maker’s class'), 'type' => 'text',
    'hint' => $n('Texniki vərəqdəki dəqiq sinif, varsa.', 'Точный класс из техлиста, если есть.', 'The exact class from the data sheet, if any.')];

// Ready-made: ədəd; custom size: m² — or no price at all, on request.
$customPrice = fn (string $formatAttr): array => [
    'slug' => 'kart-divar-ferdi-qiymet', 'name' => $n('Fərdi ölçünün qiyməti', 'Цена индивидуального размера', 'Custom-size price'), 'type' => 'dropdown', 'required' => true,
    'options' => [$a('AZN/m² ilə', ['slug' => 'm2']), $a('Qiymət sorğu ilə', ['slug' => 'sorgu'])],
    'when' => ['attr' => $formatAttr, 'in' => ['ferdi']],
    'hint' => $n('Sorğu ilə: alıcı «Hesablama tələb et» göndərir.', 'По запросу: покупатель отправляет «Запросить расчёт».', 'On request: the buyer sends «Request an estimate».'),
];
$madeToSize = fn (string $formatAttr, array $readyIn): array => [
    'sale' => 'unit', 'units' => ['piece', 'm2'],
    'unit_when' => ['piece' => ['attr' => $formatAttr, 'in' => $readyIn], 'm2' => ['attr' => $formatAttr, 'in' => ['ferdi']]],
    'quote_when' => ['attr' => 'kart-divar-ferdi-qiymet', 'in' => ['sorgu']],
    // «Fərdi istehsalda mövcudluq “Sifarişlə”».
    'on_order_when' => ['attr' => $formatAttr, 'in' => ['ferdi']],
];

// «Yapışqanlar» (WK01): a field / option shown only for these adhesive kinds.
$adh = fn (string ...$kinds): array => $when('kart-wk01-teyinat', ...$kinds);

// ── Partition systems (W13) ─────────────────────────────────────────────────
$rw = ['slug' => 'kart-divar-ses-izolyasiyasi', 'name' => $n('Səs izolyasiyası, Rw', 'Звукоизоляция, Rw', 'Sound insulation, Rw'), 'type' => 'decimal', 'filter' => true, 'unit' => 'dB',
    'hint' => $n('Bütün sistemin göstəricisi, sınaq sənədinə görə.', 'Показатель всей системы, по протоколу испытаний.', 'For the whole system, per the test report.')];
$maxHeight = ['slug' => 'kart-divar-maks-hundurluk', 'name' => $n('Maksimal hündürlük', 'Максимальная высота', 'Maximum height'), 'type' => 'decimal', 'unit' => 'mm'];
// «m² və ya qiymət sorğu ilə»: a ready system is priced per m², a project only on request.
$offer = ['slug' => 'kart-divar-teklif-novu', 'name' => $n('Təklif növü', 'Тип предложения', 'Offer type'), 'type' => 'dropdown', 'required' => true,
    'options' => [$a('Hazır sistem, AZN/m²', ['slug' => 'm2']), $a('Layihə üzrə, qiymət sorğu ilə', ['slug' => 'layihe'])],
    'hint' => $n('Layihə üzrə: qiymət yoxdur, alıcı «Hesablama tələb et» göndərir.', 'По проекту: цены нет, покупатель отправляет «Запросить расчёт».', 'Per project: no price, the buyer sends «Request an estimate».')];
$byOffer = ['sale' => 'unit', 'units' => ['m2'], 'quote_when' => ['attr' => 'kart-divar-teklif-novu', 'in' => ['layihe']]];

// Sold whole in the seller's unit.
$pack = ['sale' => 'unit', 'units' => ['pack']];
$piece = ['sale' => 'unit', 'units' => ['piece']];

return [
    'section' => 'divarlar-ve-arakesmeler',

    'groups' => [
        // ════ 1. Divar kağızları ═══════════════════════════════════════════
        [
            'slug' => 'divar-kagizi-novleri',
            'name' => $n('Divar kağızları', 'Обои', 'Wallpapers'),
            'classes' => [
                // ── W01_1 ───────────────────────────────────────────────────
                'divar-kagizlari' => [
                    'code' => 'W01_1',
                    'name' => $n('Rulon divar kağızları', 'Рулонные обои', 'Roll wallpapers'),
                    'sale' => 'unit', 'units' => ['roll'],
                    'fields' => [
                        ['slug' => 'kart-w01-ust-qat', 'name' => $n('Üst qatın materialı', 'Материал верхнего слоя', 'Top layer material'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Kağız', ['slug' => 'kagiz']), $a('Vinil', ['slug' => 'vinil']), $a('Flizelin', ['slug' => 'flizelin']),
                            $a('Tekstil', ['slug' => 'tekstil']), $a('Şüşə lif', ['slug' => 'suse-lif']), $other(),
                        ]],
                        ['slug' => 'kart-w01-esas', 'name' => $n('Əsas materialı', 'Материал основы', 'Backing material'), 'type' => 'dropdown', 'filter' => true, 'options' => [
                            $a('Kağız', ['slug' => 'kagiz']), $a('Flizelin', ['slug' => 'flizelin']), $a('Parça', ['slug' => 'parca']),
                            $a('Şüşə lif', ['slug' => 'suse-lif']), $other(), $ns,
                        ]],
                        ['slug' => 'kart-w01-rulon-eni', 'name' => $n('Rulonun eni', 'Ширина рулона', 'Roll width'), 'type' => 'decimal', 'filter' => true, 'required' => true, 'unit' => 'm',
                            'hint' => $n('Məsələn 0,53 və ya 1,06.', 'Например 0,53 или 1,06.', 'E.g. 0.53 or 1.06.')],
                        ['slug' => 'kart-w01-rulon-uzunlugu', 'name' => $n('Rulonun uzunluğu', 'Длина рулона', 'Roll length'), 'type' => 'decimal', 'required' => true, 'unit' => 'm',
                            'hint' => $n('Məsələn 10,05.', 'Например 10,05.', 'E.g. 10.05.')],
                        $color,
                        $colorCode,
                        ['slug' => 'kart-w01-naxis', 'name' => $n('Naxış', 'Рисунок', 'Pattern'), 'type' => 'dropdown', 'filter' => true, 'options' => [
                            $a('Sadə', ['slug' => 'sade']), $a('Həndəsi', ['slug' => 'hendesi']), $a('Bitki', ['slug' => 'bitki']),
                            $a('Klassik', ['slug' => 'klassik']), $a('Uşaq', ['slug' => 'usaq']), $a('Material imitasiyası', ['slug' => 'imitasiya']), $other(),
                        ]],
                        ['slug' => 'kart-w01-uygunlasdirma', 'name' => $n('Naxış uyğunlaşdırılması', 'Подгонка рисунка', 'Pattern match'), 'type' => 'dropdown', 'options' => [
                            $a('Sərbəst', ['slug' => 'serbest']), $a('Düz uyğunlaşdırma', ['slug' => 'duz']),
                            $a('Sürüşdürülmüş uyğunlaşdırma', ['slug' => 'surusdurulmus']), $ns,
                        ]],
                        ['slug' => 'kart-w01-rapport', 'name' => $n('Rapport (naxışın təkrar addımı)', 'Раппорт (шаг рисунка)', 'Pattern repeat'), 'type' => 'decimal', 'unit' => 'sm',
                            'when' => $when('kart-w01-uygunlasdirma', 'duz', 'surusdurulmus')],
                        ['slug' => 'kart-w01-surusme', 'name' => $n('Sürüşmə', 'Смещение', 'Offset'), 'type' => 'decimal', 'unit' => 'sm',
                            'when' => $when('kart-w01-uygunlasdirma', 'surusdurulmus')],
                        ['slug' => 'kart-w01-temizlenme', 'name' => $n('Təmizlənmə', 'Уход', 'Cleaning'), 'type' => 'dropdown', 'filter' => true, 'options' => [
                            $a('Quru', ['slug' => 'quru']), $a('Nəm silmə', ['slug' => 'nem']), $a('Yuyulan', ['slug' => 'yuyulan']),
                            $a('Fırça ilə təmizlənən', ['slug' => 'firca']), $ns,
                        ],
                            'hint' => $n('Etiketə görə.', 'По этикетке.', 'Per the label.')],
                        $paintable,
                        ['slug' => 'kart-w01-yapisdirma', 'name' => $n('Yapışdırma üsulu', 'Способ поклейки', 'Hanging method'), 'type' => 'dropdown', 'options' => [
                            $a('Divara yapışqan', ['slug' => 'divara']), $a('Kağıza yapışqan', ['slug' => 'kagiza']),
                            $a('Əvvəlcədən yapışqanlı', ['slug' => 'yapisqanli']), $a('Özüyapışan', ['slug' => 'ozuyapisan']), $ns,
                        ]],
                    ],
                ],

                // ── W01_2 ───────────────────────────────────────────────────
                'foto-divar-kagizlari' => [
                    'code' => 'W01_2',
                    'name' => $n('Foto divar kağızları', 'Фотообои', 'Photo murals'),
                    ...$madeToSize('kart-w01-2-format', ['hazir']),
                    'fields' => [
                        ['slug' => 'kart-w01-2-material', 'name' => $n('Material', 'Материал', 'Material'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Kağız', ['slug' => 'kagiz']), $a('Flizelin', ['slug' => 'flizelin']), $a('Vinil', ['slug' => 'vinil']),
                            $a('Tekstil', ['slug' => 'tekstil']), $other(),
                        ]],
                        ['slug' => 'kart-w01-2-format', 'name' => $n('Format', 'Формат', 'Format'), 'type' => 'dropdown', 'filter' => true, 'required' => true,
                            'hint' => $n('Hazır dəst ədədlə, fərdi ölçü m² ilə satılır.', 'Готовый комплект — поштучно, индивидуальный размер — за м².', 'A ready set sells by the piece, a custom size by the m².'),
                            'options' => [$a('Hazır dəst', ['slug' => 'hazir']), $a('Fərdi ölçü', ['slug' => 'ferdi'])]],
                        $customPrice('kart-w01-2-format'),
                        ['required' => true, 'name' => $n('Tam təsvirin ölçüsü (en × hündürlük)', 'Размер всего изображения (ширина × высота)', 'Full image size (width × height)'),
                            'slug' => 'kart-w01-2-olcu', 'when' => $when('kart-w01-2-format', 'hazir')] + $sizeWH,
                        ['slug' => 'kart-w01-2-zolaq', 'name' => $n('Zolaqların sayı', 'Количество полос', 'Number of strips'), 'type' => 'decimal', 'unit' => 'ədəd',
                            'rules' => ['integer' => true], 'when' => $when('kart-w01-2-format', 'hazir')],
                        ['slug' => 'kart-w01-2-movzu', 'name' => $n('Mövzu', 'Тема', 'Theme'), 'type' => 'dropdown', 'filter' => true, 'options' => [
                            $a('Təbiət', ['slug' => 'tebiet']), $a('Şəhər', ['slug' => 'seher']), $a('Abstrakt', ['slug' => 'abstrakt']),
                            $a('Uşaq', ['slug' => 'usaq']), $a('Həndəsi', ['slug' => 'hendesi']), $other(),
                        ]],
                        $color,
                        $colorCode,
                        ['slug' => 'kart-w01-2-temizlenme', 'name' => $n('Təmizlənmə', 'Уход', 'Cleaning'), 'type' => 'dropdown', 'options' => [
                            $a('Quru', ['slug' => 'quru']), $a('Nəm silmə', ['slug' => 'nem']), $a('Yuyulan', ['slug' => 'yuyulan']), $ns,
                        ]],
                        ['slug' => 'kart-w01-2-yapisdirma', 'name' => $n('Yapışdırma üsulu', 'Способ поклейки', 'Hanging method'), 'type' => 'dropdown', 'options' => [
                            $a('Divara yapışqan', ['slug' => 'divara']), $a('Kağıza yapışqan', ['slug' => 'kagiza']), $a('Özüyapışan', ['slug' => 'ozuyapisan']), $ns,
                        ]],
                    ],
                ],
            ],
        ],

        // ════ 2. Boyalar və dekorativ örtüklər ═════════════════════════════════
        [
            'slug' => 'dekorativ-suvaqlar-ve-ortukler',
            'name' => $n('Boyalar və dekorativ örtüklər', 'Краски и декоративные покрытия', 'Paints and decorative coatings'),
            'classes' => [
                // ── W02 ─────────────────────────────────────────────────────
                'interyer-boyasi' => [
                    'code' => 'W02',
                    'name' => $n('Divar boyaları', 'Краски для стен', 'Wall paints'),
                    ...$pack,
                    'fields' => [
                        ['slug' => 'kart-w02-novu', 'name' => $n('Boya növü', 'Тип краски', 'Paint type'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Akril', ['slug' => 'akril']), $a('Vinil', ['slug' => 'vinil']), $a('Silikat', ['slug' => 'silikat']),
                            $a('Silikon', ['slug' => 'silikon']), $a('Əhəng', ['slug' => 'eheng']), $other(),
                        ],
                            'hint' => $n('İstehsalçı təsnifatına görə.', 'По классификации производителя.', 'Per the maker’s classification.')],
                        $surface,
                        $color,
                        $colorCode,
                        ['slug' => 'kart-w02-rengleme', 'name' => $n('Rəngləmə imkanı', 'Возможность колеровки', 'Tintable'), 'type' => 'dropdown', 'options' => $ynn],
                        ['slug' => 'kart-w02-hecm', 'name' => $n('Qablaşdırmanın həcmi və ya çəkisi', 'Объём или вес упаковки', 'Pack volume or weight'), 'type' => 'decimal', 'required' => true],
                        ['slug' => 'kart-w02-hecm-vahidi', 'name' => $n('Həcmin / çəkinin vahidi', 'Единица объёма / веса', 'Volume / weight unit'), 'type' => 'dropdown', 'required' => true, 'options' => [
                            $a('l', ['slug' => 'l']), $a('kq', ['slug' => 'kq']),
                        ]],
                        ...$coverage,
                        ['slug' => 'kart-w02-yuyulma', 'name' => $n('Yuyulmaya davamlılıq', 'Стойкость к мытью', 'Scrub resistance'), 'type' => 'dropdown', 'filter' => true, 'options' => [
                            $a('Yuyulan', ['slug' => 'yuyulan']), $a('Yalnız nəm silmə', ['slug' => 'nem']), $a('Yuyulmayan', ['slug' => 'yuyulmayan']), $ns,
                        ]],
                        ['filter' => true] + $wet,
                        ['slug' => 'kart-w02-qat-sayi', 'name' => $n('Tövsiyə olunan qat sayı', 'Рекомендуемое число слоёв', 'Recommended coats'), 'type' => 'decimal', 'unit' => 'ədəd',
                            'rules' => ['integer' => true]],
                        ['slug' => 'kart-w02-gozleme', 'name' => $n('Qatlararası gözləmə', 'Межслойная выдержка', 'Recoat time'), 'type' => 'decimal', 'unit' => 'saat',
                            'hint' => $n('İstehsalçının göstərdiyi şəraitdə.', 'При условиях, указанных производителем.', 'Under the maker’s stated conditions.')],
                    ],
                ],

                // ── W03_1 ───────────────────────────────────────────────────
                'dekorativ-suvaq' => [
                    'code' => 'W03_1',
                    'name' => $n('Dekorativ suvaqlar', 'Декоративные штукатурки', 'Decorative plasters'),
                    ...$pack,
                    'fields' => [
                        ['slug' => 'kart-w03-1-novu', 'name' => $n('Növ', 'Тип', 'Type'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Fakturalı', ['slug' => 'fakturali']), $a('Venesiya', ['slug' => 'venesiya']), $a('Mineral', ['slug' => 'mineral']),
                            $other(null, 'Digər dekorativ suvaq'),
                        ]],
                        ['slug' => 'kart-w03-1-effekt', 'name' => $n('Görünüş effekti', 'Визуальный эффект', 'Visual effect'), 'type' => 'dropdown', 'filter' => true, 'options' => [
                            $a('Beton', ['slug' => 'beton']), $a('Daş', ['slug' => 'das']), $a('Mərmər', ['slug' => 'mermer']),
                            $a('Qum', ['slug' => 'qum']), $a('İpək', ['slug' => 'ipek']), $other(),
                        ]],
                        ['slug' => 'kart-w03-1-terkib', 'name' => $n('Tərkib', 'Состав', 'Composition'), 'type' => 'dropdown', 'filter' => true, 'options' => [
                            $a('Akril', ['slug' => 'akril']), $a('Əhəng', ['slug' => 'eheng']), $a('Sement', ['slug' => 'sement']),
                            $a('Silikat', ['slug' => 'silikat']), $a('Silikon', ['slug' => 'silikon']), $other(), $ns,
                        ]],
                        ['slug' => 'kart-w03-1-techizat', 'name' => $n('Təchizat forması', 'Форма поставки', 'Supply form'), 'type' => 'dropdown', 'required' => true, 'options' => [
                            $a('Quru qarışıq', ['slug' => 'quru']), $a('İstifadəyə hazır', ['slug' => 'hazir']),
                        ]],
                        $color,
                        $colorCode,
                        $packWeight,
                        ...$consumption($n('Göstərilən qat qalınlığında istehsalçı aralığı.', 'Диапазон производителя при указанной толщине слоя.', 'The maker’s range at the stated layer thickness.')),
                        ['slug' => 'kart-w03-1-dene', 'name' => $n('Dənə ölçüsü', 'Размер зерна', 'Grain size'), 'type' => 'decimal', 'unit' => 'mm',
                            'hint' => $n('Dənəli məhsullarda.', 'Для зернистых составов.', 'For grained products.')],
                        $wet,
                        ['slug' => 'kart-w03-1-son-qat', 'name' => $n('Qoruyucu son qat tələb olunur', 'Требуется защитный финишный слой', 'Protective top coat required'), 'type' => 'dropdown', 'options' => $ynn],
                    ],
                ],

                // ── W03_2 ───────────────────────────────────────────────────
                'mikrosement' => [
                    'code' => 'W03_2',
                    'name' => $n('Mikrosement', 'Микроцемент', 'Microcement'),
                    ...$pack,
                    'fields' => [
                        ['slug' => 'kart-w03-2-forma', 'name' => $n('Məhsul forması', 'Форма продукта', 'Product form'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Təkkomponentli', ['slug' => 'tek']), $a('İkikomponentli', ['slug' => 'iki']), $a('İstifadəyə hazır', ['slug' => 'hazir']),
                        ]],
                        ['slug' => 'kart-w03-2-terkib', 'name' => $n('Satılan tərkib', 'Что продаётся', 'What is sold'), 'type' => 'dropdown', 'filter' => true, 'required' => true,
                            'hint' => $n('Lak və digər komponentlər qiymətə yalnız satılan dəstə daxil olduqda daxildir.', 'Лак и прочие компоненты входят в цену, только если входят в продаваемый комплект.', 'Varnish and other components are in the price only when the kit includes them.'),
                            'options' => [$a('Yalnız finiş materialı', ['slug' => 'finis']), $a('Tam örtük sistemi', ['slug' => 'sistem'])]],
                        ['slug' => 'kart-w03-2-dest', 'name' => $n('Dəstin tərkibi', 'Состав комплекта', 'Kit contents'), 'type' => 'text', 'required' => true,
                            'when' => $when('kart-w03-2-terkib', 'sistem'),
                            'hint' => $n('Komponentlərin adları və miqdarları.', 'Названия и количество компонентов.', 'Component names and quantities.')],
                        $color,
                        $colorCode,
                        $surface,
                        ['slug' => 'kart-w03-2-ceki', 'name' => $n('Qablaşdırmanın ümumi çəkisi', 'Общий вес упаковки', 'Total pack weight'), 'type' => 'decimal', 'required' => true, 'unit' => 'kq',
                            'hint' => $n('Bütün komponentlər birlikdə.', 'Все компоненты вместе.', 'All components together.')],
                        ...$consumption($n('Satılan material və qat qalınlığı üçün.', 'Для продаваемого материала и толщины слоя.', 'For the material sold and the layer thickness.')),
                        ['hint' => $n('Tövsiyə olunan ümumi qalınlıq.', 'Рекомендуемая общая толщина.', 'Recommended total thickness.')] + $applyThick,
                        ['slug' => 'kart-w03-2-zona', 'name' => $n('İstifadə zonası', 'Зона применения', 'Area of use'), 'type' => 'multiselect', 'filter' => true, 'options' => [
                            $a('Quru otaq', ['slug' => 'quru']), $a('Rütubətli otaq', ['slug' => 'rutubetli']), $a('Birbaşa su təması', ['slug' => 'su']),
                        ],
                            'hint' => $n('Yalnız təsdiqlənmiş sistem üçün; uyğunluq bütün sistemə aiddir.', 'Только для подтверждённой системы; относится ко всей системе.', 'Only for a confirmed system; it applies to the whole system.')],
                        ['slug' => 'kart-w03-2-lak', 'name' => $n('Qoruyucu lak tələb olunur', 'Требуется защитный лак', 'Protective varnish required'), 'type' => 'dropdown', 'options' => $ynn],
                    ],
                ],

                // ── W03_3 ───────────────────────────────────────────────────
                'maye-divar-kagizlari' => [
                    'code' => 'W03_3',
                    'name' => $n('Maye divar kağızları', 'Жидкие обои', 'Liquid wallpaper'),
                    ...$pack,
                    'fields' => [
                        ['slug' => 'kart-w03-3-lif', 'name' => $n('Lif tərkibi', 'Состав волокон', 'Fibre content'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Sellüloza', ['slug' => 'selluloza']), $a('Pambıq', ['slug' => 'pambiq']), $a('İpək lif', ['slug' => 'ipek']),
                            $a('Qarışıq', ['slug' => 'qarisiq']), $other(),
                        ]],
                        $color,
                        $colorCode,
                        ['slug' => 'kart-w03-3-tekstura', 'name' => $n('Tekstura', 'Текстура', 'Texture'), 'type' => 'dropdown', 'filter' => true, 'options' => [
                            $a('İncə', ['slug' => 'ince']), $a('Orta', ['slug' => 'orta']), $a('İri', ['slug' => 'iri']), $other(),
                        ]],
                        $packWeight,
                        ...$packCoverage,
                        $applyThick,
                        ['slug' => 'kart-w03-3-parilti', 'name' => $n('Parıltılı əlavələr', 'Блёстки', 'Glitter'), 'type' => 'dropdown', 'filter' => true, 'options' => $ynn],
                        ['slug' => 'kart-w03-3-temizlenme', 'name' => $n('Təmizlənmə', 'Уход', 'Cleaning'), 'type' => 'dropdown', 'options' => [
                            $a('Quru', ['slug' => 'quru']), $a('Qoruyucu qatla nəm silmə', ['slug' => 'nem']), $other(), $ns,
                        ]],
                    ],
                ],

                // ── WK07 ────────────────────────────────────────────────────
                'finis-qoruyucu-laklar-mumlar-hopdurucular' => [
                    'code' => 'WK07',
                    'name' => $n('Finiş qoruyucu laklar, mumlar və hopdurucular', 'Защитные лаки, воски и пропитки', 'Protective varnishes, waxes and sealers'),
                    ...$pack,
                    'fields' => [
                        ['slug' => 'kart-wk07-novu', 'name' => $n('Növ', 'Тип', 'Type'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Lak', ['slug' => 'lak']), $a('Mum', ['slug' => 'mum']), $a('Hopdurucu', ['slug' => 'hopdurucu']),
                        ]],
                        ['filter' => true] + $compatMat,
                        ['slug' => 'kart-wk07-terkib', 'name' => $n('Tərkib', 'Состав', 'Composition'), 'type' => 'dropdown', 'filter' => true, 'options' => [
                            $a('Su əsaslı', ['slug' => 'su']), $a('Həlledici əsaslı', ['slug' => 'helledici']), $other(), $ns,
                        ]],
                        ['slug' => 'kart-wk07-komponent', 'name' => $n('Komponent sayı', 'Число компонентов', 'Components'), 'type' => 'dropdown', 'options' => [
                            $a('Bir', ['slug' => 'bir']), $a('İki', ['slug' => 'iki']),
                        ]],
                        $surface,
                        ['slug' => 'kart-wk07-reng', 'name' => $n('Rəng', 'Цвет', 'Colour'), 'type' => 'dropdown', 'options' => [
                            $a('Rəngsiz', ['slug' => 'rengsiz']), $a('Rəngli', ['slug' => 'rengli']),
                        ]],
                        $packQty,
                        $packQtyUnit,
                        ...$coverage,
                        ['filter' => true] + $wet,
                        $shelf,
                    ],
                ],
            ],
        ],

        // ════ 3. Plitə və daş ══════════════════════════════════════════════════
        [
            'slug' => 'keramik-divar-pliteleri',
            'name' => $n('Plitə və daş', 'Плитка и камень', 'Tiles and stone'),
            'classes' => [
                // ── W04 ─────────────────────────────────────────────────────
                'divar-keramik-plitesi' => [
                    'code' => 'W04',
                    'name' => $n('Keramik divar plitələri', 'Настенная керамическая плитка', 'Ceramic wall tiles'),
                    'sale' => 'area',
                    'fields' => $tileFields = [
                        ['slug' => 'kart-divar-plite-forma', 'name' => $n('Forma', 'Форма', 'Shape'), 'type' => 'dropdown', 'filter' => true, 'options' => [
                            $a('Düzbucaqlı', ['slug' => 'duzbucaqli']), $a('Kvadrat', ['slug' => 'kvadrat']), $a('Altıbucaqlı', ['slug' => 'altibucaqli']), $other(),
                        ]],
                        $size(['100×100', '100×200', '100×300', '150×150', '200×200', '200×250', '200×300', '200×400', '200×500', '200×600',
                            '250×330', '250×400', '250×500', '250×600', '250×750', '300×300', '300×600', '300×900', '400×800', '400×1200', '600×600', '600×1200']),
                        $thick,
                        $color,
                        $colorCode,
                        $surface,
                        ['slug' => 'kart-divar-plite-dekor', 'name' => $n('Dekor', 'Декор', 'Decor'), 'type' => 'dropdown', 'filter' => true, 'options' => [
                            $a('Sadə', ['slug' => 'sade']), $a('Daş', ['slug' => 'das']), $a('Mərmər', ['slug' => 'mermer']), $a('Ağac', ['slug' => 'agac']),
                            $a('Beton', ['slug' => 'beton']), $a('Naxışlı', ['slug' => 'naxisli']), $other(),
                        ]],
                        ['slug' => 'kart-divar-rektifikasiya', 'name' => $n('Rektifikasiya', 'Ректификация', 'Rectified'), 'type' => 'dropdown', 'filter' => true, 'options' => $ynn],
                        ['slug' => 'kart-divar-sirli', 'name' => $n('Şirli səth', 'Глазурованная поверхность', 'Glazed surface'), 'type' => 'dropdown', 'options' => $ynn],
                        $packArea,
                    ],
                ],

                // ── W05 ─────────────────────────────────────────────────────
                'divar-ucun-keramoqranit' => [
                    'code' => 'W05',
                    'name' => $n('Divar üçün keramoqranit', 'Керамогранит для стен', 'Porcelain stoneware for walls'),
                    'sale' => 'area',
                    // The same card as W04; only the size list is a porcelain one.
                    'fields' => array_replace($tileFields, [1 => $size(['100×300', '150×900', '200×1200', '300×300', '300×600', '600×600',
                        '600×1200', '800×800', '800×1600', '1000×1000', '1200×1200', '1200×2400', '1200×2600', '1600×3200'])]),
                ],

                // ── W06 ─────────────────────────────────────────────────────
                'divar-mozaikasi' => [
                    'code' => 'W06',
                    'name' => $n('Divar mozaikası', 'Настенная мозаика', 'Wall mosaic'),
                    'sale' => 'area',
                    'fields' => [
                        ['slug' => 'kart-w06-material', 'name' => $n('Material', 'Материал', 'Material'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Şüşə', ['slug' => 'suse']), $a('Keramika', ['slug' => 'keramika']), $a('Təbii daş', ['slug' => 'tebii-das']),
                            $a('Metal', ['slug' => 'metal']), $a('Qarışıq', ['slug' => 'qarisiq']), $other(),
                        ]],
                        ['slug' => 'kart-w06-techizat', 'name' => $n('Təchizat forması', 'Форма поставки', 'Supply form'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Tor üzərində', ['slug' => 'tor']), $a('Kağız üzərində', ['slug' => 'kagiz']),
                            $a('Plyonka üzərində', ['slug' => 'plyonka']), $a('Ayrı elementlər', ['slug' => 'ayri']),
                        ]],
                        ['slug' => 'kart-w06-modul', 'name' => $n('Modulun ölçüsü', 'Размер модуля', 'Sheet size'), 'type' => 'size', 'required' => true, 'unit' => 'mm',
                            'hint' => $n('Uzunluq × en. Ayrı elementlərdə həmin elementin ölçüsü.', 'Длина × ширина. Для отдельных элементов — размер элемента.', 'Length × width. For loose pieces, the piece size.')],
                        ['slug' => 'kart-w06-element', 'name' => $n('Kiçik elementin ölçüsü', 'Размер чипа', 'Chip size'), 'type' => 'size', 'unit' => 'mm',
                            'hint' => $n('Uzunluq × en.', 'Длина × ширина.', 'Length × width.')],
                        $thick,
                        $color,
                        $colorCode,
                        $surface,
                        $packArea,
                        ['filter' => true, 'hint' => $n('Yalnız istehsalçı təsdiqi ilə.', 'Только с подтверждением производителя.', 'Only with the maker’s confirmation.')] + $zone,
                    ],
                ],

                // ── W07 ─────────────────────────────────────────────────────
                'divar-ucun-tebii-das' => [
                    'code' => 'W07',
                    'name' => $n('Divar üçün təbii daş', 'Натуральный камень для стен', 'Natural stone for walls'),
                    ...$madeToSize('kart-w07-format', ['hazir-plite', 'sleb']),
                    'fields' => [
                        ['slug' => 'kart-w07-das', 'name' => $n('Daş növü', 'Вид камня', 'Stone type'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Mərmər', ['slug' => 'mermer']), $a('Travertin', ['slug' => 'travertin']), $a('Qranit', ['slug' => 'qranit']),
                            $a('Əhəngdaşı', ['slug' => 'ehengdasi']), $a('Şist', ['slug' => 'sist']), $a('Oniks', ['slug' => 'oniks']), $other(),
                        ]],
                        ['slug' => 'kart-w07-format', 'name' => $n('Format', 'Формат', 'Format'), 'type' => 'dropdown', 'filter' => true, 'required' => true,
                            'hint' => $n('Hazır məhsul ədədlə, fərdi ölçü m² ilə satılır.', 'Готовое изделие — поштучно, индивидуальный размер — за м².', 'Ready-made sells by the piece, a custom size by the m².'),
                            'options' => [$a('Hazır plitə', ['slug' => 'hazir-plite']), $a('Sləb', ['slug' => 'sleb']), $a('Fərdi ölçü', ['slug' => 'ferdi'])]],
                        $customPrice('kart-w07-format'),
                        ['when' => $when('kart-w07-format', 'hazir-plite', 'sleb'),
                            'hint' => $n('Uzunluq × en. Hazır məhsulda məcburidir.', 'Длина × ширина. Обязательно для готового изделия.', 'Length × width. Required for ready-made.')] + $size(),
                        ['filter' => true] + $thick,
                        $color,
                        $colorCode,
                        $surface,
                        ['slug' => 'kart-w07-hopdurucu', 'name' => $n('Qoruyucu hopdurucu ilə işlənib', 'Обработан защитной пропиткой', 'Sealed with impregnator'), 'type' => 'dropdown', 'options' => $ynn],
                        ['slug' => 'kart-w07-mesame', 'name' => $n('Məsamələr doldurulub', 'Поры заполнены', 'Pores filled'), 'type' => 'dropdown', 'options' => $ynn],
                        ['slug' => 'kart-w07-montaj', 'name' => $n('Uyğun montaj üsulu', 'Подходящий способ монтажа', 'Suitable installation'), 'type' => 'multiselect', 'options' => [
                            $a('Yapışdırma', ['slug' => 'yapisdirma']), $a('Mexaniki bərkitmə', ['slug' => 'mexaniki']),
                            $a('Kombinə edilmiş', ['slug' => 'kombine']), $nsX,
                        ]],
                    ],
                ],

                // ── W08 ─────────────────────────────────────────────────────
                'dekorativ-kerpic-klinker-interyer-ucun' => [
                    'code' => 'W08',
                    'name' => $n('Dekorativ kərpic və süni daş', 'Декоративный кирпич и искусственный камень', 'Decorative brick and faux stone'),
                    // TZ 24.11: + m² — flat pieces only (corners are counted in running metres).
                    'sale' => 'unit', 'units' => ['pack', 'm2'], 'unit_when' => ['m2' => $when('kart-w08-element', 'duz')],
                    'fields' => [
                        ['slug' => 'kart-w08-novu', 'name' => $n('Növ', 'Тип', 'Type'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Dekorativ kərpic', ['slug' => 'kerpic']), $a('Süni daş', ['slug' => 'suni-das']),
                        ]],
                        ['slug' => 'kart-w08-material', 'name' => $n('Material', 'Материал', 'Material'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Gips', ['slug' => 'gips']), $a('Beton', ['slug' => 'beton']), $a('Klinker', ['slug' => 'klinker']),
                            $a('Polimer-mineral', ['slug' => 'polimer-mineral']), $other(),
                        ]],
                        ['slug' => 'kart-w08-element', 'name' => $n('Element', 'Элемент', 'Element'), 'type' => 'dropdown', 'filter' => true, 'required' => true,
                            'hint' => $n('Düz element m², künc elementi poqon metrlə qablaşdırılır.', 'Плоский элемент — в м², угловой — в пог. м.', 'Flat pieces are packed by m², corners by the running metre.'),
                            'options' => [$a('Düz', ['slug' => 'duz']), $a('Künc', ['slug' => 'kunc'])]],
                        $size(['210×50', '210×65', '240×52', '240×65', '240×71', '250×60', '250×65', '290×52', '300×100', '400×100']),
                        $thick,
                        $color,
                        $colorCode,
                        ['filter' => true] + $wet,
                        ['slug' => 'kart-w08-qab-sahe', 'name' => $n('Qablaşdırmada miqdar', 'Количество в упаковке', 'Quantity per pack'), 'type' => 'decimal', 'required' => true, 'unit' => 'm²',
                            'maps_to' => 'package_size', 'when' => $when('kart-w08-element', 'duz')],
                        ['slug' => 'kart-w08-qab-uzunluq', 'name' => $n('Qablaşdırmada miqdar (künc)', 'Количество в упаковке (угол)', 'Quantity per pack (corner)'), 'type' => 'decimal', 'required' => true, 'unit' => 'p.m.',
                            'when' => $when('kart-w08-element', 'kunc')],
                        ['slug' => 'kart-w08-sahe-hesabi', 'name' => $n('Sahənin hesablanması', 'Расчёт площади', 'How the area is counted'), 'type' => 'dropdown',
                            'when' => $when('kart-w08-element', 'duz'), 'options' => [
                                $a('Tikişsiz material sahəsi', ['slug' => 'tikissiz']), $a('Tikiş nəzərə alınmış örtmə sahəsi', ['slug' => 'tikisli']),
                            ]],
                        ['slug' => 'kart-w08-tikis', 'name' => $n('Tövsiyə olunan tikiş eni', 'Рекомендуемая ширина шва', 'Recommended joint width'), 'type' => 'decimal', 'unit' => 'mm',
                            'hint' => $n('Tikişli döşənmə üçün.', 'Для укладки со швом.', 'For laying with joints.')],
                    ],
                ],
            ],
        ],

        // ════ 4. Divar panelləri və reykalar ═══════════════════════════════════
        [
            'slug' => 'divar-panelleri-ve-reykalar',
            'name' => $n('Divar panelləri və reykalar', 'Стеновые панели и рейки', 'Wall panels and slats'),
            'classes' => [
                // ── W09_1 ───────────────────────────────────────────────────
                'taxta-lambir-uzluk' => [
                    'code' => 'W09_1',
                    'name' => $n('Taxta və şponlu divar panelləri', 'Деревянные и шпонированные стеновые панели', 'Wood and veneered wall panels'),
                    'sale' => 'unit', 'units' => ['piece', 'pack', 'm2'], // + m² (TZ 24.11)
                    'fields' => [
                        ['slug' => 'kart-w09-1-konstruksiya', 'name' => $n('Konstruksiya', 'Конструкция', 'Construction'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Massiv ağac', ['slug' => 'massiv']), $a('Şponlu faner', ['slug' => 'sponlu-faner']), $a('Şponlu MDF', ['slug' => 'sponlu-mdf']), $other(),
                        ]],
                        ['slug' => 'kart-w09-1-agac', 'name' => $n('Görünən ağac növü', 'Порода дерева (лицевая)', 'Visible wood species'), 'type' => 'dropdown', 'filter' => true, 'options' => [
                            $a('Palıd', ['slug' => 'palid']), $a('Qoz', ['slug' => 'qoz']), $a('Göyrüş', ['slug' => 'goyrus']), $a('Şam', ['slug' => 'sam']), $other(), $ns,
                        ]],
                        $size(['2400×600', '2700×600', '2800×600', '3000×600', '2440×1220', '2800×1220', '3050×1220']),
                        $thick,
                        $color,
                        $colorCode,
                        ['slug' => 'kart-w09-1-son-ortuk', 'name' => $n('Son örtük', 'Финишное покрытие', 'Finish'), 'type' => 'dropdown', 'filter' => true, 'options' => [
                            $a('Lak', ['slug' => 'lak']), $a('Yağ', ['slug' => 'yag']), $a('Mum', ['slug' => 'mum']), $a('Boya', ['slug' => 'boya']),
                            $a('Örtüksüz', ['slug' => 'ortuksuz']), $other(),
                        ]],
                        ['filter' => true] + $mount,
                        $wet,
                        $packCount,
                    ],
                ],

                // ── W09_2 ───────────────────────────────────────────────────
                'dekorativ-paneller-mdfplastikagacmetalakril' => [
                    'code' => 'W09_2',
                    'name' => $n('MDF divar panelləri', 'Стеновые панели МДФ', 'MDF wall panels'),
                    'sale' => 'unit', 'units' => ['piece', 'pack', 'm2'], // + m² (TZ 24.11)
                    'fields' => [
                        ['slug' => 'kart-w09-2-ust-ortuk', 'name' => $n('Üst örtük', 'Лицевое покрытие', 'Face finish'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Laminasiya', ['slug' => 'laminasiya']), $a('PVC plyonka', ['slug' => 'pvc']), $a('Boya', ['slug' => 'boya']),
                            $a('Örtüksüz', ['slug' => 'ortuksuz']), $other(),
                        ],
                            'hint' => $n('Şponlu məhsul «Taxta və şponlu divar panelləri» sinfinə aiddir.', 'Шпонированные изделия — в классе «Деревянные и шпонированные панели».', 'Veneered products belong to «Wood and veneered wall panels».')],
                        $size(['2600×153', '2600×200', '2600×250', '2700×200', '2700×250', '2800×1220', '2800×2070', '2440×1220']),
                        $thick,
                        $color,
                        $colorCode,
                        $surface,
                        ['slug' => 'kart-divar-panel-gorunus', 'name' => $n('Görünüş', 'Рельеф', 'Relief'), 'type' => 'dropdown', 'filter' => true, 'options' => [
                            $a('Hamar', ['slug' => 'hamar']), $a('Relyefli', ['slug' => 'relyefli']), $a('Yivli', ['slug' => 'yivli']), $other(),
                        ]],
                        $mount,
                        ['filter' => true] + $wet,
                        $packCount,
                    ],
                ],

                // ── W09_3 ───────────────────────────────────────────────────
                'plastik-ve-polimer-divar-panelleri' => [
                    'code' => 'W09_3',
                    'name' => $n('Plastik və polimer divar panelləri', 'Пластиковые и полимерные стеновые панели', 'Plastic and polymer wall panels'),
                    'sale' => 'unit', 'units' => ['piece', 'pack', 'm2'], // + m² (TZ 24.11)
                    'fields' => [
                        ['slug' => 'kart-w09-3-material', 'name' => $n('Material', 'Материал', 'Material'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('PVC', ['slug' => 'pvc']), $a('Polistirol', ['slug' => 'polistirol']), $a('Poliuretan', ['slug' => 'poliuretan']),
                            $a('WPC', ['slug' => 'wpc']), $a('SPC', ['slug' => 'spc']), $other(),
                        ]],
                        $size(['2700×250', '3000×250', '2600×200', '3000×100', '3000×160', '2440×1220', '2800×1220', '3000×1220']),
                        $thick,
                        $color,
                        $colorCode,
                        ['slug' => 'kart-w09-3-dekor', 'name' => $n('Dekor', 'Декор', 'Decor'), 'type' => 'dropdown', 'filter' => true, 'options' => [
                            $a('Sadə', ['slug' => 'sade']), $a('Mərmər', ['slug' => 'mermer']), $a('Daş', ['slug' => 'das']),
                            $a('Ağac', ['slug' => 'agac']), $a('Beton', ['slug' => 'beton']), $other(),
                        ]],
                        ['slug' => 'kart-w09-3-seth-formasi', 'name' => $n('Səth forması', 'Форма поверхности', 'Surface form'), 'type' => 'dropdown', 'filter' => true, 'options' => [
                            $a('Hamar', ['slug' => 'hamar']), $a('Relyefli', ['slug' => 'relyefli']), $a('Yivli', ['slug' => 'yivli']), $other(),
                        ]],
                        $mount,
                        ['filter' => true] + $wet,
                        $paintable,
                        $packCount,
                    ],
                ],

                // ── W09_4 ───────────────────────────────────────────────────
                'gips-divar-panelleri' => [
                    'code' => 'W09_4',
                    'name' => $n('Gips divar panelləri', 'Гипсовые стеновые панели', 'Gypsum wall panels'),
                    'sale' => 'unit', 'units' => ['piece', 'pack', 'm2'], // + m² (TZ 24.11)
                    'fields' => [
                        ['slug' => 'kart-w09-4-novu', 'name' => $n('Panel növü', 'Тип панели', 'Panel type'), 'type' => 'dropdown', 'filter' => true, 'required' => true,
                            'hint' => $n('Dekorativ finiş panelləri. Adi alçıpan lövhələr qaralama materiallar bölməsinə aiddir.', 'Декоративные финишные панели. Обычный гипсокартон — в разделе черновых материалов.', 'Decorative finish panels. Plain plasterboard belongs to rough materials.'),
                            'options' => [$a('Hamar dekorativ', ['slug' => 'hamar']), $a('3D relyefli', ['slug' => '3d']), $a('Liflə möhkəmləndirilmiş', ['slug' => 'lifli'])]],
                        $size(['500×500', '600×600', '600×300', '1000×500', '1200×600', '2400×600']),
                        $thick,
                        ['slug' => 'kart-w09-4-ceki', 'name' => $n('Bir panelin çəkisi', 'Вес одной панели', 'Weight of one panel'), 'type' => 'decimal', 'unit' => 'kq'],
                        $color,
                        $colorCode,
                        ['slug' => 'kart-w09-4-techizat', 'name' => $n('Təchizat səthi', 'Поверхность при поставке', 'Supplied surface'), 'type' => 'dropdown', 'filter' => true, 'options' => [
                            $a('Boyasız', ['slug' => 'boyasiz']), $a('Astarlanmış', ['slug' => 'astarli']), $a('Boyalı', ['slug' => 'boyali']),
                        ]],
                        ['filter' => true] + $mount,
                        $packCount,
                    ],
                ],

                // ── W09_5 ───────────────────────────────────────────────────
                'dekorativ-divar-reykalari' => [
                    'code' => 'W09_5',
                    'name' => $n('Dekorativ divar reykaları', 'Декоративные стеновые рейки', 'Decorative wall slats'),
                    'sale' => 'unit', 'units' => ['piece', 'pack', 'lm'], // + xətti metr (TZ 24.11)
                    'fields' => [
                        ['slug' => 'kart-w09-5-material', 'name' => $n('Material', 'Материал', 'Material'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Massiv ağac', ['slug' => 'massiv']), $a('Şponlu MDF', ['slug' => 'sponlu-mdf']), $a('Örtüklü MDF', ['slug' => 'ortuklu-mdf']),
                            $a('WPC', ['slug' => 'wpc']), $a('Polimer', ['slug' => 'polimer']), $a('Metal', ['slug' => 'metal']), $other(),
                        ]],
                        $length,
                        ['name' => $n('Kəsiyin eni', 'Ширина сечения', 'Section width')] + $sectionW,
                        $sectionD,
                        $color,
                        $colorCode,
                        $surface,
                        ['filter' => true] + $mount,
                        $wet,
                        $packCount,
                    ],
                ],

                // ── W09_6 ───────────────────────────────────────────────────
                'mantar-divar-ortukleri' => [
                    'code' => 'W09_6',
                    'name' => $n('Mantar divar örtükləri', 'Пробковые настенные покрытия', 'Cork wall coverings'),
                    'sale' => 'area',
                    'fields' => [
                        ['slug' => 'kart-w09-6-format', 'name' => $n('Format', 'Формат', 'Format'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Plitə', ['slug' => 'plite']), $a('Rulon', ['slug' => 'rulon']), $a('Panel', ['slug' => 'panel']),
                        ]],
                        ['when' => $when('kart-w09-6-format', 'plite', 'panel')] + $size(),
                        ['slug' => 'kart-w09-6-rulon-eni', 'name' => $n('Rulonun eni', 'Ширина рулона', 'Roll width'), 'type' => 'decimal', 'required' => true, 'unit' => 'm',
                            'when' => $when('kart-w09-6-format', 'rulon')],
                        ['slug' => 'kart-w09-6-rulon-uzunlugu', 'name' => $n('Rulonun uzunluğu', 'Длина рулона', 'Roll length'), 'type' => 'decimal', 'required' => true, 'unit' => 'm',
                            'when' => $when('kart-w09-6-format', 'rulon')],
                        ['filter' => true] + $thick,
                        $color,
                        $colorCode,
                        ['slug' => 'kart-w09-6-son-ortuk', 'name' => $n('Son örtük', 'Финишное покрытие', 'Finish'), 'type' => 'dropdown', 'filter' => true, 'options' => [
                            $a('Mum', ['slug' => 'mum']), $a('Lak', ['slug' => 'lak']), $a('Örtüksüz', ['slug' => 'ortuksuz']), $other(),
                        ]],
                        ['slug' => 'kart-w09-6-ozuyapisan', 'name' => $n('Özüyapışan', 'Самоклеящееся', 'Self-adhesive'), 'type' => 'dropdown', 'filter' => true, 'options' => $ynn],
                        $wet,
                        ['hint' => $n('Etiketə əsasən, kəsim itkisi əlavə edilmədən. Rulonda rulonun sahəsi.', 'По этикетке, без запаса на подрезку. Для рулона — площадь рулона.', 'From the label, without cutting waste. For a roll, the roll area.')] + $packArea,
                    ],
                ],

                // ── W10 ─────────────────────────────────────────────────────
                'yumsaq-divar-panelleri' => [
                    'code' => 'W10',
                    'name' => $n('Yumşaq divar panelləri', 'Мягкие стеновые панели', 'Soft wall panels'),
                    ...$madeToSize('kart-w10-format', ['hazir']),
                    'fields' => [
                        ['slug' => 'kart-w10-uzluk', 'name' => $n('Üzlük materialı', 'Материал обивки', 'Upholstery'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Parça', ['slug' => 'parca']), $a('Təbii dəri', ['slug' => 'deri']), $a('Süni dəri', ['slug' => 'suni-deri']), $other(),
                        ]],
                        ['slug' => 'kart-w10-format', 'name' => $n('Format', 'Формат', 'Format'), 'type' => 'dropdown', 'filter' => true, 'required' => true,
                            'hint' => $n('Hazır modul ədədlə, fərdi ölçü m² ilə satılır.', 'Готовый модуль — поштучно, индивидуальный размер — за м².', 'A ready module sells by the piece, a custom size by the m².'),
                            'options' => [$a('Hazır modul', ['slug' => 'hazir']), $a('Fərdi ölçü', ['slug' => 'ferdi'])]],
                        $customPrice('kart-w10-format'),
                        ['when' => $when('kart-w10-format', 'hazir'),
                            'hint' => $n('Uzunluq × en. Hazır modulda məcburidir.', 'Длина × ширина. Обязательно для готового модуля.', 'Length × width. Required for a ready module.')] + $size(),
                        $thick,
                        $color,
                        $colorCode,
                        ['slug' => 'kart-w10-esas', 'name' => $n('Əsas', 'Основа', 'Base'), 'type' => 'dropdown', 'options' => [
                            $a('MDF', ['slug' => 'mdf']), $a('Faner', ['slug' => 'faner']), $a('Ağac', ['slug' => 'agac']), $other(), $ns,
                        ]],
                        ['slug' => 'kart-w10-dolgu', 'name' => $n('Dolğu', 'Наполнитель', 'Filling'), 'type' => 'dropdown', 'filter' => true, 'options' => [
                            $a('Poliuretan köpük', ['slug' => 'poliuretan']), $a('Lifli dolğu', ['slug' => 'lifli']), $a('Qarışıq', ['slug' => 'qarisiq']), $other(), $ns,
                        ]],
                        ['slug' => 'kart-w10-temizlenme', 'name' => $n('Təmizlənmə', 'Уход', 'Cleaning'), 'type' => 'dropdown', 'options' => [
                            $a('Quru', ['slug' => 'quru']), $a('Nəm silmə', ['slug' => 'nem']), $a('Çıxarılan yuyulan üzlük', ['slug' => 'cixarilan']), $ns,
                        ]],
                        $mount,
                    ],
                ],

                // ── W11 ─────────────────────────────────────────────────────
                'akustik-paneller' => [
                    'code' => 'W11',
                    'name' => $n('Akustik divar panelləri', 'Акустические стеновые панели', 'Acoustic wall panels'),
                    'sale' => 'unit', 'units' => ['piece', 'pack', 'm2'], // + m² (TZ 24.11)
                    'fields' => [
                        ['slug' => 'kart-w11-novu', 'name' => $n('Növ', 'Тип', 'Type'), 'type' => 'dropdown', 'filter' => true, 'required' => true,
                            'hint' => $n('Səsudma təsdiqi olmayan dekorativ reykalar «Dekorativ divar reykaları» sinfinə aiddir.', 'Декоративные рейки без подтверждённого звукопоглощения — в классе «Декоративные рейки».', 'Decorative slats without proven absorption belong to «Decorative wall slats».'),
                            'options' => [
                                $a('PET keçə', ['slug' => 'pet']), $a('Keçə üzərində reykalı', ['slug' => 'reykali']), $a('Parça üzlü', ['slug' => 'parca']),
                                $a('Ağac lifli', ['slug' => 'agac-lifli']), $a('Perforasiyalı', ['slug' => 'perforasiyali']), $other(),
                            ]],
                        $size(['2400×600', '2700×600', '3000×600', '600×600', '1200×600', '1200×1200']),
                        ['filter' => true] + $thick,
                        $color,
                        $colorCode,
                        ['slug' => 'kart-w11-esas', 'name' => $n('Əsas material', 'Материал основы', 'Core material'), 'type' => 'dropdown', 'filter' => true, 'options' => [
                            $a('PET', ['slug' => 'pet']), $a('MDF', ['slug' => 'mdf']), $a('Ağac lifi', ['slug' => 'agac-lifi']),
                            $a('Mineral lif', ['slug' => 'mineral-lif']), $a('Şüşə lifi', ['slug' => 'suse-lifi']), $other(), $ns,
                        ]],
                        ['filter' => true] + $mount,
                        ['slug' => 'kart-w11-gosterici', 'name' => $n('Səsudma göstəricisi', 'Показатель звукопоглощения', 'Sound absorption rating'), 'type' => 'dropdown', 'options' => [
                            $a('αw', ['slug' => 'aw']), $a('NRC', ['slug' => 'nrc']), $ns,
                        ],
                            'hint' => $n('Səsudma göstəricisi səsizolyasiya göstəricisi deyil.', 'Звукопоглощение — не звукоизоляция.', 'Sound absorption is not sound insulation.')],
                        ['slug' => 'kart-w11-emsal', 'name' => $n('Səsudma əmsalı', 'Коэффициент звукопоглощения', 'Absorption coefficient'), 'type' => 'decimal', 'required' => true,
                            'when' => $when('kart-w11-gosterici', 'aw', 'nrc'), 'rules' => ['max' => 1.5],
                            'hint' => $n('İstehsalçının sınaq sənədindən, məsələn 0,85.', 'Из протокола испытаний производителя, например 0,85.', 'From the maker’s test report, e.g. 0.85.')],
                        ['slug' => 'kart-w11-sinaq', 'name' => $n('Sınaq montajı', 'Монтаж при испытании', 'Test mounting'), 'type' => 'dropdown',
                            'when' => $when('kart-w11-gosterici', 'aw', 'nrc'), 'options' => [
                                $a('Birbaşa divara', ['slug' => 'birbasa']), $a('Hava boşluğu ilə', ['slug' => 'bosluq']), $a('Əlavə izolyasiya ilə', ['slug' => 'izolyasiya']),
                            ]],
                        ['slug' => 'kart-w11-sinaq-qeyd', 'name' => $n('Qat və boşluq ölçüsü', 'Слои и размер зазора', 'Layers and gap size'), 'type' => 'text',
                            'when' => $when('kart-w11-sinaq', 'bosluq', 'izolyasiya'),
                            'hint' => $n('Varsa, sınaq sənədinə görə.', 'Если есть, по протоколу испытаний.', 'If any, per the test report.')],
                        $packCount,
                    ],
                ],

                // ── W12_1 ───────────────────────────────────────────────────
                'suse-divar-panelleri' => [
                    'code' => 'W12_1',
                    'name' => $n('Şüşə divar panelləri', 'Стеклянные стеновые панели', 'Glass wall panels'),
                    ...$madeToSize('kart-w12-1-format', ['hazir']),
                    'fields' => [
                        ['slug' => 'kart-w12-1-suse', 'name' => $n('Şüşə növü', 'Тип стекла', 'Glass type'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Adi', ['slug' => 'adi']), $a('Temperli', ['slug' => 'temperli']), $a('Laminasiyalı', ['slug' => 'laminasiyali']), $other(),
                        ]],
                        ['slug' => 'kart-w12-1-format', 'name' => $n('Format', 'Формат', 'Format'), 'type' => 'dropdown', 'filter' => true, 'required' => true,
                            'hint' => $n('Hazır panel ədədlə, fərdi ölçü m² ilə satılır.', 'Готовая панель — поштучно, индивидуальный размер — за м².', 'A ready panel sells by the piece, a custom size by the m².'),
                            'options' => [$a('Hazır panel', ['slug' => 'hazir']), $a('Fərdi ölçü', ['slug' => 'ferdi'])]],
                        $customPrice('kart-w12-1-format'),
                        ['required' => true, 'when' => $when('kart-w12-1-format', 'hazir')] + $sizeWH,
                        $thick,
                        $color,
                        $colorCode,
                        ['slug' => 'kart-w12-1-dekor', 'name' => $n('Dekor', 'Декор', 'Decor'), 'type' => 'dropdown', 'filter' => true, 'options' => [
                            $a('Şəffaf', ['slug' => 'seffaf']), $a('Mat', ['slug' => 'mat']), $a('Arxadan boyalı', ['slug' => 'arxadan-boyali']),
                            $a('Foto çap', ['slug' => 'foto-cap']), $other(),
                        ]],
                        ['slug' => 'kart-divar-kenar-emali', 'name' => $n('Kənar emalı', 'Обработка кромки', 'Edge finish'), 'type' => 'dropdown', 'filter' => true, 'options' => [
                            $a('Cilalanmış', ['slug' => 'cilalanmis']), $a('Fasetli', ['slug' => 'fasetli']), $other(), $ns,
                        ]],
                        ['slug' => 'kart-w12-1-desik', 'name' => $n('Deşik və kəsik sifarişi', 'Отверстия и вырезы под заказ', 'Holes and cut-outs to order'), 'type' => 'dropdown', 'options' => $ynn],
                        $mount,
                    ],
                ],

                // ── W12_2 ───────────────────────────────────────────────────
                'guzgu-divar-ortukleri' => [
                    'code' => 'W12_2',
                    'name' => $n('Güzgü divar örtükləri', 'Зеркальные настенные покрытия', 'Mirror wall coverings'),
                    ...$madeToSize('kart-w12-2-format', ['plite', 'hazir']),
                    'fields' => [
                        ['slug' => 'kart-w12-2-format', 'name' => $n('Format', 'Формат', 'Format'), 'type' => 'dropdown', 'filter' => true, 'required' => true,
                            'hint' => $n('Hazır məhsul ədədlə, fərdi ölçü m² ilə satılır.', 'Готовое изделие — поштучно, индивидуальный размер — за м².', 'Ready-made sells by the piece, a custom size by the m².'),
                            'options' => [$a('Güzgü plitəsi', ['slug' => 'plite']), $a('Hazır panel', ['slug' => 'hazir']), $a('Fərdi ölçü', ['slug' => 'ferdi'])]],
                        $customPrice('kart-w12-2-format'),
                        ['required' => true, 'when' => $when('kart-w12-2-format', 'plite', 'hazir')] + $sizeWH,
                        ['filter' => true] + $thick,
                        ['slug' => 'kart-w12-2-reng', 'name' => $n('Güzgünün rəngi', 'Цвет зеркала', 'Mirror tint'), 'type' => 'dropdown', 'filter' => true, 'options' => [
                            $a('Gümüşü', ['slug' => 'gumusu']), $a('Bürünc', ['slug' => 'burunc']), $a('Boz', ['slug' => 'boz']), $other(),
                        ]],
                        ['slug' => 'kart-divar-kenar-emali', 'name' => $n('Kənar emalı', 'Обработка кромки', 'Edge finish'), 'type' => 'dropdown', 'filter' => true, 'options' => [
                            $a('Cilalanmış', ['slug' => 'cilalanmis']), $a('Fasetli', ['slug' => 'fasetli']), $other(), $ns,
                        ]],
                        ['slug' => 'kart-w12-2-faset', 'name' => $n('Faset eni', 'Ширина фацета', 'Bevel width'), 'type' => 'decimal', 'unit' => 'mm',
                            'when' => $when('kart-divar-kenar-emali', 'fasetli')],
                        ['slug' => 'kart-w12-2-plyonka', 'name' => $n('Arxa qoruyucu plyonka', 'Защитная плёнка на тыльной стороне', 'Safety backing film'), 'type' => 'dropdown', 'options' => $ynn],
                        ['filter' => true] + $wet,
                        $mount,
                    ],
                ],
            ],
        ],

        // ════ 5. Montaj materialları ═══════════════════════════════════════════
        [
            'slug' => 'divar-uzluk-yapisqanlari',
            'name' => $n('Montaj materialları', 'Монтажные материалы', 'Installation materials'),
            'classes' => [
                // ── WK01: the five adhesive cards (WK01_1–WK01_5) in one class ───
                // TZ 24.11 item 3: they differ only by purpose. «Təyinat» picks the kind;
                // each kind still asks what its own card asked (`when`), shared fields once.
                'divar-kagizi-yapisqani' => [
                    'code' => 'WK01',
                    'name' => $n('Yapışqanlar', 'Клеи', 'Adhesives'),
                    ...$pack,
                    'fields' => [
                        ['slug' => 'kart-wk01-teyinat', 'name' => $n('Təyinat', 'Назначение', 'Intended for'), 'type' => 'dropdown', 'filter' => true, 'required' => true,
                            'hint' => $n('Yapışqanın növü; aşağıdakı sahələr ona görə dəyişir.', 'Вид клея; поля ниже зависят от него.', 'The adhesive kind; the fields below follow it.'),
                            'options' => [
                                $a('Divar kağızı yapışqanı', ['slug' => 'divar-kagizi']),
                                $a('Plitə, mozaika və daş yapışqanı', ['slug' => 'plite']),
                                $a('Divar paneli montaj yapışqanı', ['slug' => 'panel']),
                                $a('Şüşə və güzgü yapışqanı', ['slug' => 'suse-guzgu']),
                                $a('Moldinq və dekor yapışqanı', ['slug' => 'moldinq']),
                            ]],
                        // WK01_1 / WK01_2 had their own «Tərkib»; folded here (move_values), each kind sees its own list.
                        ['slug' => 'kart-divar-yap-terkib', 'name' => $n('Tərkib', 'Состав', 'Composition'), 'type' => 'dropdown', 'filter' => true, 'options' => [
                            $a('Dispersiya', ['slug' => 'dispersiya']),
                            $a('Poliuretan', ['slug' => 'poliuretan', 'when' => $adh('panel', 'suse-guzgu', 'moldinq')]),
                            $a('MS-polimer', ['slug' => 'ms-polimer', 'when' => $adh('panel', 'suse-guzgu', 'moldinq')]),
                            $a('Neytral silikon', ['slug' => 'silikon', 'when' => $adh('panel', 'suse-guzgu', 'moldinq')]),
                            $a('Gips', ['slug' => 'gips', 'when' => $adh('panel', 'suse-guzgu', 'moldinq')]),
                            $a('Sellüloza', ['slug' => 'selluloza', 'when' => $adh('divar-kagizi')]),
                            $a('Nişasta', ['slug' => 'nisasta', 'when' => $adh('divar-kagizi')]),
                            $a('Qarışıq', ['slug' => 'qarisiq', 'when' => $adh('divar-kagizi')]),
                            $a('Sement', ['slug' => 'sement', 'when' => $adh('plite')]),
                            $a('Epoksi', ['slug' => 'epoksi', 'when' => $adh('plite')]),
                            $other(),
                        ]],
                        ['slug' => 'kart-divar-yap-techizat', 'name' => $n('Təchizat forması', 'Форма поставки', 'Supply form'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Quru qarışıq', ['slug' => 'quru']), $a('İstifadəyə hazır', ['slug' => 'hazir']), $a('Hazır pasta', ['slug' => 'pasta']),
                            $a('Kartric', ['slug' => 'kartric']), $a('İkikomponentli dəst', ['slug' => 'iki']), $other(),
                        ]],
                        $substrates,
                        $packQty,
                        $packQtyUnit,

                        // WK01_1 — wallpaper
                        ['slug' => 'kart-wk01-1-teyinat', 'name' => $n('Divar kağızının növü', 'Тип обоев', 'Wallpaper type'), 'type' => 'multiselect', 'required' => true,
                            'when' => $adh('divar-kagizi'), 'options' => [
                                $a('Kağız', ['slug' => 'kagiz']), $a('Vinil', ['slug' => 'vinil']), $a('Flizelin', ['slug' => 'flizelin']),
                                $a('Tekstil', ['slug' => 'tekstil']), $a('Şüşə lif', ['slug' => 'suse-lif']),
                            ]],
                        ...array_map(fn (array $f): array => $f + ['when' => $adh('divar-kagizi')],
                            $range('kart-divar-qab-ortme', $n('Bir qablaşdırmanın örtmə sahəsi', 'Площадь покрытия одной упаковки', 'Coverage of one pack'), 'm²',
                                $n('Kağızın növünə görə istehsalçı aralığı.', 'Диапазон производителя по типу обоев.', 'The maker’s range by wallpaper type.'))),

                        // WK01_2 — tile, mosaic, stone
                        ['slug' => 'kart-wk01-2-teyinat', 'name' => $n('Üzlük materialı', 'Облицовочный материал', 'Cladding material'), 'type' => 'multiselect', 'required' => true,
                            'when' => $adh('plite'), 'options' => [
                                $a('Keramik plitə', ['slug' => 'keramik']), $a('Keramoqranit', ['slug' => 'keramoqranit']), $a('Mozaika', ['slug' => 'mozaika']),
                                $a('Təbii daş', ['slug' => 'tebii-das']), $a('Dekorativ daş', ['slug' => 'dekorativ-das']),
                            ]],
                        // WK01_2 / WK01_4 colour, folded (move_values).
                        ['slug' => 'kart-divar-yap-reng', 'name' => $n('Yapışqanın rəngi', 'Цвет клея', 'Adhesive colour'), 'type' => 'dropdown',
                            'when' => $adh('plite', 'suse-guzgu'), 'options' => [
                                $a('Ağ', ['slug' => 'ag']), $a('Boz', ['slug' => 'boz', 'when' => $adh('plite')]),
                                $a('Şəffaf', ['slug' => 'seffaf', 'when' => $adh('suse-guzgu')]), $other(),
                            ]],
                        ['when' => $adh('plite')] + $makerClass,
                        ['when' => $adh('plite')] + $zone,
                        ...array_map(fn (array $f): array => $f + ['when' => $adh('plite')],
                            $consumption($n('Tətbiq qalınlığı və ya dişli malanın ölçüsü ilə birlikdə.', 'Вместе с толщиной нанесения или размером зубчатого шпателя.', 'Together with the layer thickness or the notched trowel size.'))),
                        ['slug' => 'kart-wk01-2-serfiyyat-serti', 'name' => $n('Sərfiyyatın şərti', 'Условие расхода', 'Consumption basis'), 'type' => 'text',
                            'when' => $adh('plite'),
                            'hint' => $n('Məsələn: «dişli mala 8 mm» və ya «qat 5 mm».', 'Например: «зубчатый шпатель 8 мм» или «слой 5 мм».', 'E.g. «8 mm notched trowel» or «5 mm layer».')],

                        // WK01_3 — wall panels
                        ['slug' => 'kart-wk01-3-teyinat', 'name' => $n('Panelin materialı', 'Материал панели', 'Panel material'), 'type' => 'multiselect', 'required' => true,
                            'when' => $adh('panel'), 'options' => [
                                $a('Taxta', ['slug' => 'taxta']), $a('MDF', ['slug' => 'mdf']), $a('PVC', ['slug' => 'pvc']), $a('Polimer', ['slug' => 'polimer']),
                                $a('Gips', ['slug' => 'gips']), $a('Mantar', ['slug' => 'mantar']), $a('Akustik panel', ['slug' => 'akustik']),
                            ]],
                        ['slug' => 'kart-wk01-3-serfiyyat', 'name' => $n('Sərfiyyat', 'Расход', 'Consumption'), 'type' => 'decimal', 'when' => $adh('panel')],
                        ['slug' => 'kart-wk01-3-serfiyyat-vahidi', 'name' => $n('Sərfiyyatın vahidi', 'Единица расхода', 'Consumption unit'), 'type' => 'dropdown',
                            'when' => $adh('panel'), 'options' => [
                                $a('q/m²', ['slug' => 'q-m2']), $a('ml/m²', ['slug' => 'ml-m2']),
                            ]],
                        ['when' => $adh('panel', 'suse-guzgu')] + $wet,

                        // WK01_4 — glass and mirror
                        ['slug' => 'kart-wk01-4-teyinat', 'name' => $n('Yapışdırılan material', 'Склеиваемый материал', 'Material bonded'), 'type' => 'multiselect', 'required' => true,
                            'when' => $adh('suse-guzgu'), 'options' => [
                                $a('Şüşə', ['slug' => 'suse']), $a('Güzgü', ['slug' => 'guzgu']),
                            ],
                            'hint' => $n('Güzgünün arxa örtüyünə uyğunluq təsdiqlənməlidir.', 'Совместимость с тыльным покрытием зеркала должна быть подтверждена.', 'Compatibility with the mirror backing must be confirmed.')],

                        // WK01_5 — mouldings and decor
                        ['slug' => 'kart-wk01-5-teyinat', 'name' => $n('Tətbiq', 'Применение', 'Application'), 'type' => 'multiselect', 'required' => true,
                            'when' => $adh('moldinq'), 'options' => [
                                $a('Montaj', ['slug' => 'montaj']), $a('Elementlərin birləşmə yeri', ['slug' => 'birlesme']),
                            ]],
                        ['slug' => 'kart-wk01-5-dekor', 'name' => $n('Uyğun dekor materialı', 'Подходящий материал декора', 'Suitable decor material'), 'type' => 'multiselect',
                            'when' => $adh('moldinq'), 'options' => [
                                $a('Polistirol', ['slug' => 'polistirol']), $a('Poliuretan', ['slug' => 'poliuretan']), $a('Gips', ['slug' => 'gips']),
                                $a('Ağac', ['slug' => 'agac']), $other(),
                            ]],
                        ['when' => $adh('moldinq')] + $paintable,

                        $shelf,
                    ],
                ],

                // ── WK02 ────────────────────────────────────────────────────
                'tikis-doldurucular' => [
                    'code' => 'WK02',
                    'name' => $n('Tikiş doldurucuları', 'Затирки', 'Grouts'),
                    ...$pack,
                    'fields' => [
                        ['slug' => 'kart-wk02-terkib', 'name' => $n('Tərkib', 'Состав', 'Composition'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Sement', ['slug' => 'sement']), $a('Epoksi', ['slug' => 'epoksi']), $other(),
                        ]],
                        ['filter' => true] + $compatMat,
                        $color,
                        $colorCode,
                        $packQty,
                        $packQtyUnit,
                        ['slug' => 'kart-wk02-tikis-min', 'name' => $n('Tikiş eni, min', 'Ширина шва, мин.', 'Joint width, min'), 'type' => 'decimal', 'required' => true, 'unit' => 'mm',
                            'rules' => ['lte' => 'kart-wk02-tikis-maks']],
                        ['slug' => 'kart-wk02-tikis-maks', 'name' => $n('Tikiş eni, maks', 'Ширина шва, макс.', 'Joint width, max'), 'type' => 'decimal', 'required' => true, 'unit' => 'mm'],
                        ['filter' => true, 'hint' => $n('İstehsalçıya görə.', 'По данным производителя.', 'Per the maker.')] + $zone,
                        ['hint' => $n('Texniki vərəqdəki sinif, varsa.', 'Класс из техлиста, если есть.', 'The data-sheet class, if any.')] + $makerClass,
                        $shelf,
                    ],
                ],

                // ── WK03 ────────────────────────────────────────────────────
                'hermetikler' => [
                    'code' => 'WK03',
                    'name' => $n('Hermetiklər', 'Герметики', 'Sealants'),
                    ...$pack,
                    'fields' => [
                        ['slug' => 'kart-wk03-terkib', 'name' => $n('Tərkib', 'Состав', 'Composition'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Silikon', ['slug' => 'silikon']), $a('Akril', ['slug' => 'akril']), $a('Poliuretan', ['slug' => 'poliuretan']),
                            $a('MS-polimer', ['slug' => 'ms-polimer']), $other(),
                        ]],
                        ['slug' => 'kart-wk03-teyinat', 'name' => $n('Təyinat', 'Назначение', 'Intended for'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Sanitar tikiş', ['slug' => 'sanitar']), $a('Divar birləşməsi', ['slug' => 'divar']), $a('Təbii daş', ['slug' => 'tebii-das']),
                            $a('Şüşə', ['slug' => 'suse']), $other(),
                        ]],
                        ['filter' => true] + $compatMat,
                        $color,
                        $colorCode,
                        $packQty,
                        $packQtyUnit,
                        $paintable,
                        ['slug' => 'kart-wk03-kif', 'name' => $n('Kifə qarşı əlavələr', 'Противогрибковые добавки', 'Anti-mould additives'), 'type' => 'dropdown', 'filter' => true, 'options' => $ynn],
                        $shelf,
                    ],
                ],

                // ── WK04_1 ──────────────────────────────────────────────────
                'plite-kenar-ve-kunc-profilleri' => [
                    'code' => 'WK04_1',
                    'name' => $n('Plitə üçün kənar və künc profilləri', 'Торцевые и угловые профили для плитки', 'Tile edge and corner profiles'),
                    ...$piece,
                    'fields' => [
                        ['slug' => 'kart-wk04-1-novu', 'name' => $n('Profil növü', 'Тип профиля', 'Profile type'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Xarici künc', ['slug' => 'xarici']), $a('Daxili künc', ['slug' => 'daxili']), $a('Sonlandırıcı', ['slug' => 'sonlandirici']),
                            $a('Dekorativ ara profil', ['slug' => 'dekorativ']), $a('Deformasiya profili', ['slug' => 'deformasiya']),
                        ]],
                        $profileMaterial,
                        $length,
                        ['slug' => 'kart-wk04-1-plite-qalinligi', 'name' => $n('Uyğun plitə qalınlığı', 'Подходящая толщина плитки', 'Matching tile thickness'), 'type' => 'decimal', 'filter' => true, 'required' => true, 'unit' => 'mm',
                            'hint' => $n('Profilin işçi hündürlüyü.', 'Рабочая высота профиля.', 'The profile’s working height.')],
                        ['slug' => 'kart-wk04-1-gorunen-en', 'name' => $n('Görünən en', 'Видимая ширина', 'Visible width'), 'type' => 'decimal', 'unit' => 'mm'],
                        $color,
                        $colorCode,
                        $surface,
                    ],
                ],

                // ── WK04_2 ──────────────────────────────────────────────────
                'divar-paneli-profilleri' => [
                    'code' => 'WK04_2',
                    'name' => $n('Divar panelləri üçün profillər', 'Профили для стеновых панелей', 'Wall panel profiles'),
                    ...$piece,
                    'fields' => [
                        ['slug' => 'kart-wk04-2-novu', 'name' => $n('Profil növü', 'Тип профиля', 'Profile type'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Başlanğıc', ['slug' => 'baslangic']), $a('Sonlandırıcı', ['slug' => 'sonlandirici']), $a('H-birləşdirici', ['slug' => 'h']),
                            $a('Daxili künc', ['slug' => 'daxili']), $a('Xarici künc', ['slug' => 'xarici']), $a('Kölgə profili', ['slug' => 'kolge']), $other(),
                        ]],
                        $profileMaterial,
                        $length,
                        ['slug' => 'kart-wk04-2-panel-min', 'name' => $n('Uyğun panel qalınlığı, min', 'Толщина панели, мин.', 'Panel thickness, min'), 'type' => 'decimal', 'required' => true, 'unit' => 'mm',
                            'rules' => ['lte' => 'kart-wk04-2-panel-maks'],
                            'hint' => $n('Bir ölçü üçün yalnız bu sahəni doldurun.', 'Для одного размера заполните только это поле.', 'For a single size fill in only this one.')],
                        ['slug' => 'kart-wk04-2-panel-maks', 'name' => $n('Uyğun panel qalınlığı, maks', 'Толщина панели, макс.', 'Panel thickness, max'), 'type' => 'decimal', 'unit' => 'mm'],
                        $color,
                        $colorCode,
                        $surface,
                        ['filter' => true] + $panelSystem(),
                        $panelSeries(),
                    ],
                ],

                // ── WK04_3 ──────────────────────────────────────────────────
                'profil-birlesdiricileri-ve-uc-qapaqlari' => [
                    'code' => 'WK04_3',
                    'name' => $n('Profil birləşdiriciləri və uc qapaqları', 'Соединители и заглушки профилей', 'Profile connectors and end caps'),
                    ...$pack,
                    'fields' => [
                        ['slug' => 'kart-wk04-3-novu', 'name' => $n('Element növü', 'Тип элемента', 'Element type'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Düz birləşdirici', ['slug' => 'duz']), $a('Künc birləşdirici', ['slug' => 'kunc']), $a('Uc qapağı', ['slug' => 'uc-qapagi']), $other(),
                        ]],
                        $profileMaterial,
                        $profileFor(true),
                        $size(),
                        $color,
                        $colorCode,
                        $packCount,
                    ],
                ],

                // ── WK05_1 ──────────────────────────────────────────────────
                'panel-klipsleri-ve-gizli-berkidiciler' => [
                    'code' => 'WK05_1',
                    'name' => $n('Panel klipsləri və gizli bərkidicilər', 'Кляммеры и скрытый крепёж для панелей', 'Panel clips and concealed fixings'),
                    ...$pack,
                    'fields' => [
                        ['slug' => 'kart-wk05-1-novu', 'name' => $n('Növ', 'Тип', 'Type'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Klips', ['slug' => 'klips']), $a('Klyaymer', ['slug' => 'klyaymer']), $a('Asqı lövhəsi', ['slug' => 'asqi']),
                            $a('Maqnitli bərkidici', ['slug' => 'maqnit']), $other(),
                        ]],
                        ['slug' => 'kart-wk05-1-material', 'name' => $n('Material', 'Материал', 'Material'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Polad', ['slug' => 'polad']), $a('Alüminium', ['slug' => 'aluminium']), $a('Plastik', ['slug' => 'plastik']), $other(),
                        ]],
                        ['filter' => true] + $panelSystem(true),
                        $panelSeries(),
                        ['slug' => 'kart-wk05-1-olcu', 'name' => $n('Uyğun qalınlıq və ya yiv', 'Подходящая толщина или паз', 'Matching thickness or groove'), 'type' => 'decimal', 'unit' => 'mm',
                            'hint' => $n('Bərkidicinin tələb etdiyi ölçü.', 'Размер, который требует крепёж.', 'The size the fixing needs.')],
                        $packCount,
                        ['slug' => 'kart-wk05-1-dest', 'name' => $n('Dəst tərkibi', 'Состав комплекта', 'Kit contents'), 'type' => 'text',
                            'hint' => $n('Klips, vint və digər elementlər və sayı.', 'Кляммеры, саморезы и прочее с количеством.', 'Clips, screws and other parts with counts.')],
                    ],
                ],

                // ── WK05_2 ──────────────────────────────────────────────────
                'uzluk-montaji-ucun-vint-ve-dubeller' => [
                    'code' => 'WK05_2',
                    'name' => $n('Üzlük montajı üçün vint və dübellər', 'Саморезы и дюбели для облицовки', 'Screws and plugs for cladding'),
                    ...$pack,
                    'fields' => [
                        ['slug' => 'kart-wk05-2-novu', 'name' => $n('Növ', 'Тип', 'Type'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Vint', ['slug' => 'vint']), $a('Dübel', ['slug' => 'dubel']), $a('Vint-dübel dəsti', ['slug' => 'dest']), $a('Anker', ['slug' => 'anker']),
                        ]],
                        ['slug' => 'kart-wk05-2-esas', 'name' => $n('Əsas material', 'Материал основания', 'Base material'), 'type' => 'multiselect', 'filter' => true, 'required' => true, 'options' => [
                            $a('Beton', ['slug' => 'beton']), $a('Dolu kərpic', ['slug' => 'dolu-kerpic']), $a('Boşluqlu kərpic', ['slug' => 'bosluqlu-kerpic']),
                            $a('Məsaməli blok', ['slug' => 'mesameli-blok']), $a('Alçıpan', ['slug' => 'alcipan']), $a('Ağac', ['slug' => 'agac']),
                        ]],
                        ['slug' => 'kart-wk05-2-diametr', 'name' => $n('Diametr', 'Диаметр', 'Diameter'), 'type' => 'decimal', 'filter' => true, 'required' => true, 'unit' => 'mm'],
                        ['slug' => 'kart-wk05-2-uzunluq', 'name' => $n('Uzunluq', 'Длина', 'Length'), 'type' => 'decimal', 'filter' => true, 'required' => true, 'unit' => 'mm'],
                        ['slug' => 'kart-wk05-2-dest-olcu', 'name' => $n('Dəstdə vint və dübelin ölçüləri', 'Размеры самореза и дюбеля в комплекте', 'Screw and plug sizes in the set'), 'type' => 'text', 'required' => true,
                            'when' => $when('kart-wk05-2-novu', 'dest'),
                            'hint' => $n('Məsələn: dübel 8×40, vint 5×50.', 'Например: дюбель 8×40, саморез 5×50.', 'E.g. plug 8×40, screw 5×50.')],
                        ['slug' => 'kart-wk05-2-material', 'name' => $n('Material və örtük', 'Материал и покрытие', 'Material and coating'), 'type' => 'dropdown', 'options' => [
                            $a('Sinklənmiş polad', ['slug' => 'sinkli']), $a('Paslanmayan polad', ['slug' => 'paslanmayan']), $a('Neylon', ['slug' => 'neylon']), $other(),
                        ]],
                        ['slug' => 'kart-wk05-2-basliq', 'name' => $n('Başlıq', 'Головка', 'Head'), 'type' => 'dropdown', 'options' => [
                            $a('Gizli', ['slug' => 'gizli']), $a('Yarımdairəvi', ['slug' => 'yarimdairevi']), $a('Altıbucaqlı', ['slug' => 'altibucaqli']),
                            $other(), $a('Tətbiq edilmir', ['slug' => 'yoxdur']),
                        ]],
                        $packCount,
                    ],
                ],

                // ── WK08 ────────────────────────────────────────────────────
                'plite-ara-mesafe-ve-seviyyeleme-elementleri' => [
                    'code' => 'WK08',
                    'name' => $n('Plitə ara məsafə və səviyyələmə elementləri', 'Крестики и элементы выравнивания плитки', 'Tile spacers and levelling parts'),
                    ...$pack,
                    'fields' => [
                        ['slug' => 'kart-wk08-novu', 'name' => $n('Növ', 'Тип', 'Type'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Xaç', ['slug' => 'xac']), $a('T-ara məsafə', ['slug' => 't']), $a('Səviyyələmə klipsi', ['slug' => 'klips']),
                            $a('Paz', ['slug' => 'paz']), $a('Qapaq', ['slug' => 'qapaq']), $a('Dəst', ['slug' => 'dest']),
                        ]],
                        ['slug' => 'kart-wk08-tikis', 'name' => $n('Tikiş eni', 'Ширина шва', 'Joint width'), 'type' => 'decimal', 'filter' => true, 'required' => true, 'unit' => 'mm',
                            'when' => $when('kart-wk08-novu', 'xac', 't', 'klips', 'dest'),
                            'hint' => $n('Tikişi müəyyən edən elementdə.', 'Для элемента, задающего шов.', 'For the part that sets the joint.')],
                        ['slug' => 'kart-wk08-plite-min', 'name' => $n('Uyğun plitə qalınlığı, min', 'Толщина плитки, мин.', 'Tile thickness, min'), 'type' => 'decimal', 'unit' => 'mm',
                            'rules' => ['lte' => 'kart-wk08-plite-maks'], 'when' => $when('kart-wk08-novu', 'klips', 'paz', 'qapaq', 'dest'),
                            'hint' => $n('Səviyyələmə sistemində.', 'Для системы выравнивания.', 'For a levelling system.')],
                        ['slug' => 'kart-wk08-plite-maks', 'name' => $n('Uyğun plitə qalınlığı, maks', 'Толщина плитки, макс.', 'Tile thickness, max'), 'type' => 'decimal', 'unit' => 'mm',
                            'when' => $when('kart-wk08-novu', 'klips', 'paz', 'qapaq', 'dest')],
                        ['slug' => 'kart-wk08-sistem', 'name' => $n('Sistem uyğunluğu', 'Совместимость с системой', 'System compatibility'), 'type' => 'text',
                            'hint' => $n('Seriya və ya uyğun klips / paz / qapaq.', 'Серия или подходящий клипс / клин / колпачок.', 'Series or matching clip / wedge / cap.')],
                        $packCount,
                        ['slug' => 'kart-wk08-tekrar', 'name' => $n('Təkrar istifadə olunur', 'Многоразовые', 'Reusable'), 'type' => 'dropdown', 'filter' => true, 'options' => $ynn],
                    ],
                ],
            ],
        ],

        // ════ 6. Dekorativ elementlər ══════════════════════════════════════════
        [
            'slug' => 'dekorativ-elementler',
            'name' => $n('Dekorativ elementlər', 'Декоративные элементы', 'Decorative elements'),
            'classes' => [
                // ── WK06_1 ──────────────────────────────────────────────────
                'moldinqler-bagetler-karnizler-interyer-ucun' => [
                    'code' => 'WK06_1',
                    'name' => $n('Divar moldinqləri və bordürləri', 'Настенные молдинги и бордюры', 'Wall mouldings and borders'),
                    'sale' => 'unit', 'units' => ['piece', 'lm'], // + xətti metr (TZ 24.11)
                    'fields' => [
                        ['slug' => 'kart-wk06-1-novu', 'name' => $n('Növ', 'Тип', 'Type'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Moldinq', ['slug' => 'moldinq']), $a('Bordür', ['slug' => 'bordur']), $a('Çərçivə profili', ['slug' => 'cercive']),
                        ]],
                        ['slug' => 'kart-wk06-1-material', 'name' => $n('Material', 'Материал', 'Material'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Poliuretan', ['slug' => 'poliuretan']), $a('Polistirol', ['slug' => 'polistirol']), $a('Gips', ['slug' => 'gips']),
                            $a('Ağac', ['slug' => 'agac']), $a('MDF', ['slug' => 'mdf']), $other(),
                        ]],
                        $length,
                        $sectionW,
                        $sectionD,
                        $color,
                        $colorCode,
                        ['filter' => true] + $paintable,
                        ['slug' => 'kart-wk06-1-elastik', 'name' => $n('Elastikdir', 'Гибкий', 'Flexible'), 'type' => 'dropdown', 'filter' => true, 'options' => $ynn],
                        $mount,
                    ],
                ],

                // ── WK06_2 ──────────────────────────────────────────────────
                'dekorativ-kunc-ve-birlesme-elementleri' => [
                    'code' => 'WK06_2',
                    'name' => $n('Dekorativ künc və birləşmə elementləri', 'Декоративные уголки и соединительные элементы', 'Decorative corners and joints'),
                    ...$piece,
                    'fields' => [
                        ['slug' => 'kart-wk06-2-novu', 'name' => $n('Növ', 'Тип', 'Type'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Çərçivə küncü', ['slug' => 'cercive-kuncu']), $a('Dekorativ birləşdirici', ['slug' => 'birlesdirici']),
                            $a('Ornament', ['slug' => 'ornament']), $other(),
                        ]],
                        ['slug' => 'kart-wk06-2-material', 'name' => $n('Material', 'Материал', 'Material'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Poliuretan', ['slug' => 'poliuretan']), $a('Polistirol', ['slug' => 'polistirol']), $a('Gips', ['slug' => 'gips']),
                            $a('Ağac', ['slug' => 'agac']), $other(),
                        ]],
                        $size(),
                        $thick,
                        $color,
                        $colorCode,
                        $profileFor(false, $n('Profilə aid elementdə məcburidir: seriya və ya kod.', 'Обязательно для элемента к профилю: серия или код.', 'Required for an element made for a profile: series or code.')),
                        ['filter' => true] + $paintable,
                    ],
                ],
            ],
        ],

        // ════ 7. Arakəsmə sistemləri (TZ 24.11 item 1: W13_1–W13_4) ════════
        [
            'slug' => 'arakesme-sistemleri',
            'name' => $n('Arakəsmə sistemləri', 'Системы перегородок', 'Partition systems'),
            'classes' => [
                // ── W13_1 ───────────────────────────────────────────────────
                'karkas-arakesme-metal-karkas-gkl' => [
                    'code' => 'W13_1',
                    'name' => $n('Karkas arakəsmə (metal karkas + gipskarton)', 'Каркасная перегородка (металлокаркас + гипсокартон)', 'Framed partition (metal frame + plasterboard)'),
                    ...$byOffer,
                    'fields' => [
                        $offer,
                        ['slug' => 'kart-w13-1-profil-eni', 'name' => $n('Profilin eni', 'Ширина профиля', 'Stud width'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('50 mm', ['slug' => '50']), $a('75 mm', ['slug' => '75']), $a('100 mm', ['slug' => '100']), $other(),
                        ]],
                        ['slug' => 'kart-w13-1-gipskarton', 'name' => $n('Gipskartonun növü', 'Тип гипсокартона', 'Plasterboard type'), 'type' => 'multiselect', 'filter' => true, 'required' => true, 'options' => [
                            $a('Adi', ['slug' => 'adi']), $a('Nəmə davamlı', ['slug' => 'neme-davamli']), $a('Odadavamlı', ['slug' => 'odadavamli']), $other(),
                        ]],
                        ['slug' => 'kart-w13-1-qat-sayi', 'name' => $n('Gipskarton qatlarının sayı', 'Число слоёв гипсокартона', 'Plasterboard layers'), 'type' => 'decimal', 'required' => true, 'unit' => 'ədəd',
                            'rules' => ['integer' => true],
                            'hint' => $n('Hər tərəfdə.', 'С каждой стороны.', 'On each side.')],
                        ['slug' => 'kart-w13-1-qalinliq', 'name' => $n('Ümumi qalınlıq', 'Общая толщина', 'Total thickness'), 'type' => 'decimal', 'filter' => true, 'required' => true, 'unit' => 'mm',
                            'hint' => $n('Karkas və bütün gipskarton qatları ilə.', 'С каркасом и всеми слоями гипсокартона.', 'Frame plus all plasterboard layers.')],
                        $rw,
                        ['slug' => 'kart-w13-1-ei', 'name' => $n('Odadavamlılıq (EI sinfi)', 'Огнестойкость (класс EI)', 'Fire resistance (EI class)'), 'type' => 'dropdown', 'filter' => true, 'options' => [
                            $a('EI 30', ['slug' => 'ei-30']), $a('EI 45', ['slug' => 'ei-45']), $a('EI 60', ['slug' => 'ei-60']),
                            $a('EI 90', ['slug' => 'ei-90']), $a('EI 120', ['slug' => 'ei-120']), $ns,
                        ],
                            'hint' => $n('Sistemin sınaq sənədinə görə.', 'По протоколу испытаний системы.', 'Per the system’s test report.')],
                    ],
                ],

                // ── W13_2 ───────────────────────────────────────────────────
                'suse-arakesme' => [
                    'code' => 'W13_2',
                    'name' => $n('Şüşə arakəsmə', 'Стеклянная перегородка', 'Glass partition'),
                    ...$byOffer,
                    'fields' => [
                        $offer,
                        ['slug' => 'kart-w13-2-suse', 'name' => $n('Şüşənin növü', 'Тип стекла', 'Glass type'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Temperli', ['slug' => 'temperli']), $a('Tripleks', ['slug' => 'tripleks']), $other(),
                        ]],
                        ['slug' => 'kart-w13-2-qalinliq', 'name' => $n('Şüşənin qalınlığı', 'Толщина стекла', 'Glass thickness'), 'type' => 'decimal', 'filter' => true, 'required' => true, 'unit' => 'mm'],
                        ['slug' => 'kart-w13-2-profil', 'name' => $n('Profil', 'Профиль', 'Frame'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Çərçivəsiz', ['slug' => 'cercivesiz']), $a('Alüminium profil', ['slug' => 'aluminium']), $other(),
                        ]],
                        ['slug' => 'kart-w13-2-qapi', 'name' => $n('Qapı daxildir', 'Дверь в комплекте', 'Door included'), 'type' => 'dropdown', 'filter' => true, 'required' => true,
                            'options' => [$yes, $no]],
                        $maxHeight,
                    ],
                ],

                // ── W13_3 ───────────────────────────────────────────────────
                'mobil-surusen-arakesme' => [
                    'code' => 'W13_3',
                    'name' => $n('Mobil / sürüşən arakəsmə', 'Мобильная / раздвижная перегородка', 'Mobile / sliding partition'),
                    'sale' => 'unit', 'units' => ['m2', 'set'],
                    'fields' => [
                        ['slug' => 'kart-w13-3-mexanizm', 'name' => $n('Mexanizm', 'Механизм', 'Mechanism'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Sürüşən', ['slug' => 'surusen']), $a('Qatlanan', ['slug' => 'qatlanan']), $a('Transformasiya olunan', ['slug' => 'transformasiya']), $other(),
                        ]],
                        ['slug' => 'kart-w13-3-material', 'name' => $n('Panelin materialı', 'Материал панели', 'Panel material'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
                            $a('Şüşə', ['slug' => 'suse']), $a('MDF', ['slug' => 'mdf']), $a('LDSP', ['slug' => 'ldsp']), $a('Ağac', ['slug' => 'agac']),
                            $a('Alüminium', ['slug' => 'aluminium']), $a('Parça üzlü (akustik)', ['slug' => 'parca']), $other(),
                        ]],
                        $maxHeight,
                        $rw,
                    ],
                ],

                // ── W13_4 ───────────────────────────────────────────────────
                'suse-bloklar' => [
                    'code' => 'W13_4',
                    'name' => $n('Şüşə bloklar', 'Стеклоблоки', 'Glass blocks'),
                    'sale' => 'unit', 'units' => ['piece', 'm2'],
                    'fields' => [
                        $size(['190×190', '240×240', '190×90']),
                        ['filter' => true] + $thick,
                        ['slug' => 'kart-w13-4-seth', 'name' => $n('Səth', 'Поверхность', 'Surface'), 'type' => 'dropdown', 'filter' => true, 'options' => [
                            $a('Hamar', ['slug' => 'hamar']), $a('Dalğalı', ['slug' => 'dalgali']), $a('Rəngli', ['slug' => 'rengli']), $other(),
                        ]],
                        $color,
                        $colorCode,
                    ],
                ],
            ],
        ],
    ],

    // Old classes already folded earlier: W04–W07 replaced the catch-all tile class, W09_4 the 3D panels.
    // TZ 24.11 item 3: four adhesive classes fold into «Yapışqanlar» (kept slug divar-kagizi-yapisqani);
    // their products get «Təyinat» = the old class and the old URLs answer with a 301.
    'merge' => [
        'keramoqranit-keramik-plite-tebii-das-mozaika' => 'divar-keramik-plitesi',
        '3d-paneller' => 'gips-divar-panelleri',
        'plite-mozaika-ve-das-yapisqani' => ['into' => 'divar-kagizi-yapisqani', 'set' => ['kart-wk01-teyinat' => 'plite']],
        'divar-paneli-montaj-yapisqani' => ['into' => 'divar-kagizi-yapisqani', 'set' => ['kart-wk01-teyinat' => 'panel']],
        'suse-ve-guzgu-yapisqani' => ['into' => 'divar-kagizi-yapisqani', 'set' => ['kart-wk01-teyinat' => 'suse-guzgu']],
        'moldinq-ve-dekor-yapisqani' => ['into' => 'divar-kagizi-yapisqani', 'set' => ['kart-wk01-teyinat' => 'moldinq']],
    ],

    // The adhesive cards' own duplicates, folded into the shared attribute.
    'move_values' => [
        'kart-wk01-1-terkib' => 'kart-divar-yap-terkib',
        'kart-wk01-2-terkib' => 'kart-divar-yap-terkib',
        'kart-wk01-2-reng' => 'kart-divar-yap-reng',
        'kart-wk01-4-reng' => 'kart-divar-yap-reng',
    ],

    // 24.11: the client cut 21 groups to 7 in the admin; every group switched off there.
    'retire_groups' => [
        'divar-ve-tavan-uzluk-ortukleri', 'divar-boyalari', 'divar-keramoqranit', 'divar-mozaika', 'divar-tebii-das',
        'dekorativ-kerpic-ve-suni-das', 'yumsaq-paneller', 'akustik-divar-panelleri', 'suse-ve-guzgu-ortukleri',
        'tikis-doldurucular-ve-hermetikler', 'divar-uzluk-profilleri-ve-kuncler', 'divar-uzluk-berkidicileri',
        'qoruyucu-finis-mehsullari', 'plite-araliqlari-ve-seviyyeleme',
    ],
];
