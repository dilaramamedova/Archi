<?php

/*
|--------------------------------------------------------------------------
| Döşəmələr — 22 product cards in 3 groups (D01–D22)
|--------------------------------------------------------------------------
|
| Source: D01–D12 Archi_AZ_Doseme_Kataloq_AZ.xlsx (client, 16.11.2026); D13–D22 the
| client's TZ proposal per class (attributes + sales unit, no workbook, 24.11.2026) —
| sold in the seller's unit (`sale: unit`). Imported by
| App\Services\ProductCard\CardImporter (`php artisan product-cards:import doseme`).
| Format: docs/product-cards.md. Group and class names are the prod admin's.
|
| Attribute slugs start with "kart-". One attribute may serve several classes
| (thickness, warranty, floor heating …) — its name, type and options must then be
| identical everywhere; what differs per class (required, unit, presets, hint,
| conditions) belongs to the class entry.
|
*/

$o = fn (string $az, string $ru, string $en, array $extra = []): array => ['value' => compact('az', 'ru', 'en')] + $extra;
$n = fn (string $az, string $ru, string $en): array => compact('az', 'ru', 'en');

$P_NAME = $n('Dəqiq adını yazın', 'Укажите точное название', 'Enter the exact name');
$P_SOURCE = $n('İstehsalçı mənbəyini yazın', 'Укажите источник производителя', 'Enter the manufacturer source');
$P_CLASS = $n('Pasportdakı sinfi yazın', 'Укажите класс по паспорту', 'Enter the class from the passport');

$yes = $o('Bəli', 'Да', 'Yes', ['slug' => 'beli']);
$no = $o('Xeyr', 'Нет', 'No', ['slug' => 'xeyr']);
$ns = $o('Göstərilməyib', 'Не указано', 'Not specified', ['slug' => 'gosterilmeyib']);
$nsX = $ns + ['exclusive' => true];
$other = fn (array $prompt, string $az = 'Digər', string $ru = 'Другое', string $en = 'Other', string $slug = 'diger'): array => $o($az, $ru, $en, ['slug' => $slug, 'prompt' => $prompt]);

$ynn = [$yes, $no, $ns];

// ── Shared attributes ───────────────────────────────────────────────────────
$heating = ['slug' => 'kart-isti-doseme', 'name' => $n('Döşəməaltı isitmə ilə uyğunluq', 'Совместимость с тёплым полом', 'Underfloor heating compatible'), 'type' => 'dropdown', 'options' => $ynn];
$warranty = ['slug' => 'kart-zemanet', 'name' => $n('Zəmanət', 'Гарантия', 'Warranty'), 'type' => 'dropdown', 'options' => [
    $o('Var', 'Есть', 'Provided', ['slug' => 'var']), $o('Verilmir', 'Не предоставляется', 'Not provided', ['slug' => 'verilmir']), $ns,
]];
$water = ['slug' => 'kart-su-qorunma', 'name' => $n('Suya qarşı qorunma', 'Влагозащита', 'Water protection'), 'type' => 'dropdown', 'options' => $ynn];
$underlay = ['slug' => 'kart-inteqre-altliq', 'name' => $n('İnteqrasiya olunmuş altlıq', 'Встроенная подложка', 'Integrated underlay'), 'type' => 'dropdown', 'options' => $ynn];
$slip = ['slug' => 'kart-surusme-sinagi', 'name' => $n('Sürüşmə sınağının nəticəsi var', 'Есть результат испытания на скольжение', 'Slip test result available'), 'type' => 'dropdown', 'options' => $ynn];
$frost = ['slug' => 'kart-saxtaya-davamli', 'name' => $n('Şaxtayadavamlılıq', 'Морозостойкость', 'Frost resistance'), 'type' => 'dropdown', 'options' => [
    $yes + ['prompt' => $P_SOURCE], $no, $ns,
]];
$rectified = ['slug' => 'kart-rektifikasiya', 'name' => $n('Rektifikasiya', 'Ректификация', 'Rectified'), 'type' => 'dropdown', 'options' => $ynn];
$glazed = ['slug' => 'kart-sirli-seth', 'name' => $n('Şirli səth', 'Глазурованная поверхность', 'Glazed surface'), 'type' => 'dropdown', 'filter' => true, 'options' => $ynn];
$floorUse = ['slug' => 'kart-dosemede-istifade', 'name' => $n('Döşəmədə istifadəyə uyğunluq', 'Пригодность для пола', 'Suitable for floors'), 'type' => 'dropdown', 'options' => $ynn,
    'required' => true, 'rules' => ['only' => ['beli']],
    'hint' => $n('Bu kateqoriyada yalnız istehsalçının təsdiqlədiyi «Bəli» məhsullar yerləşdirilir.', 'В этой категории размещаются только товары, пригодность которых для пола подтверждена производителем.', 'Only products the manufacturer confirms for floor use are listed in this category.')];
$shape = ['slug' => 'kart-forma', 'name' => $n('Forma', 'Форма', 'Shape'), 'type' => 'dropdown', 'required' => true, 'filter' => true, 'options' => [
    $o('Düzbucaqlı', 'Прямоугольная', 'Rectangular', ['slug' => 'duzbucaqli']),
    $o('Kvadrat', 'Квадратная', 'Square', ['slug' => 'kvadrat']),
    $other($n('Formanın dəqiq adını yazın', 'Укажите точное название формы', 'Enter the exact shape')),
]];
$surface = ['slug' => 'kart-seth', 'name' => $n('Səth', 'Поверхность', 'Surface'), 'type' => 'dropdown', 'filter' => true, 'options' => [
    $o('Mat', 'Матовая', 'Matt'), $o('Cilalanmış', 'Полированная', 'Polished', ['slug' => 'cilalanmis']),
    $o('Honlanmış', 'Хонингованная', 'Honed', ['slug' => 'honlanmis']), $o('Fakturalı', 'Фактурная', 'Textured', ['slug' => 'fakturali']),
    $other($P_NAME), $ns,
]];
$wood = ['slug' => 'kart-agac-novu', 'name' => $n('Ağac növü', 'Порода дерева', 'Wood species'), 'type' => 'dropdown', 'filter' => true,
    'hint' => $n('Görünən qatın ağacı.', 'Порода лицевого слоя.', 'Species of the visible layer.'), 'options' => [
        $o('Palıd', 'Дуб', 'Oak', ['slug' => 'palid']), $o('Göyrüş', 'Ясень', 'Ash', ['slug' => 'goyrus']),
        $o('Qoz', 'Орех', 'Walnut', ['slug' => 'qoz']), $o('Fıstıq', 'Бук', 'Beech', ['slug' => 'fistiq']),
        $o('Ağcaqayın', 'Клён', 'Maple', ['slug' => 'agcaqayin']), $other($P_NAME), $ns,
    ]];
$finish = ['slug' => 'kart-son-ortuk', 'name' => $n('Son örtük', 'Финишное покрытие', 'Finish'), 'type' => 'dropdown', 'filter' => true, 'options' => [
    $o('Lak', 'Лак', 'Lacquer'), $o('Yağ', 'Масло', 'Oil', ['slug' => 'yag']),
    $o('Sərt mumlu yağ', 'Твёрдое масло с воском', 'Hard wax oil', ['slug' => 'sert-mumlu-yag']),
    $o('Örtüksüz', 'Без покрытия', 'Unfinished', ['slug' => 'ortuksuz']), $other($P_NAME), $ns,
]];
$lengthFormat = ['slug' => 'kart-uzunluq-formati', 'name' => $n('Uzunluq formatı', 'Формат длины', 'Length format'), 'type' => 'dropdown', 'required' => true, 'options' => [
    $o('Sabit uzunluq', 'Фиксированная длина', 'Fixed length', ['slug' => 'sabit-uzunluq']),
    $o('Qarışıq uzunluqlar', 'Разные длины', 'Mixed lengths', ['slug' => 'qarisiq-uzunluqlar']),
]];
$woodJoint = ['slug' => 'kart-taxta-birlesme', 'name' => $n('Birləşmə növü', 'Тип соединения', 'Joint type'), 'type' => 'dropdown', 'options' => [
    $o('Kilidli', 'Замковое', 'Click lock', ['slug' => 'kilidli']), $o('Dil–yiv', 'Шип–паз', 'Tongue and groove', ['slug' => 'dil-yiv']),
    $o('Düz kənar', 'Прямая кромка', 'Square edge', ['slug' => 'duz-kenar']),
    $other($P_NAME, 'Digər təsdiqlənmiş növ', 'Другой подтверждённый тип', 'Other confirmed type'), $ns,
]];
$woodLaying = ['slug' => 'kart-taxta-dosenme', 'name' => $n('Döşənmə üsulu', 'Способ укладки', 'Installation method'), 'type' => 'multiselect',
    'hint' => $n('Yalnız istehsalçının icazə verdiyi üsullar.', 'Только способы, разрешённые производителем.', 'Only methods the manufacturer allows.'), 'options' => [
        $o('Üzən', 'Плавающий', 'Floating', ['slug' => 'uzen']), $o('Yapışdırma', 'Приклеивание', 'Glue-down', ['slug' => 'yapisdirma']),
        $o('Mexaniki bərkitmə', 'Механическое крепление', 'Mechanical fixing', ['slug' => 'mexaniki-berkitme']), $nsX,
    ]];
$thickness = ['slug' => 'kart-umumi-qalinliq', 'name' => $n('Ümumi qalınlıq', 'Общая толщина', 'Total thickness'), 'type' => 'decimal', 'required' => true, 'unit' => 'mm',
    'hint' => $n('Məhsulun etiketindəki dəqiq qalınlıq.', 'Точная толщина с этикетки.', 'Exact thickness from the label.')];
$topLayer = ['slug' => 'kart-ust-qat-qalinligi', 'name' => $n('Üst ağac qatının qalınlığı', 'Толщина верхнего слоя', 'Top layer thickness'), 'type' => 'decimal', 'unit' => 'mm',
    'presets' => [0.6, 1, 1.5, 2, 2.5, 3, 3.5, 4, 4.5, 5, 6], 'rules' => ['lt' => 'kart-umumi-qalinliq'],
    'hint' => $n('Məlumdursa. Ümumi qalınlıqdan kiçikdir.', 'Если известна. Меньше общей толщины.', 'If known. Less than the total thickness.')];
$packArea = ['slug' => 'kart-qablasdirma-sahesi', 'name' => $n('Qablaşdırmanın sahəsi', 'Площадь упаковки', 'Pack area'), 'type' => 'decimal', 'required' => true, 'unit' => 'm²',
    'maps_to' => 'package_size', 'hint' => $n('İstehsalçının etiketindəki dəqiq sahə.', 'Точная площадь с этикетки производителя.', 'Exact area from the manufacturer label.')];
$pieceSize = ['slug' => 'kart-memulat-olcusu', 'name' => $n('Məmulatın ölçüsü', 'Размер изделия', 'Piece size'), 'type' => 'size', 'required' => true, 'unit' => 'mm',
    'hint' => $n('Uzunluq × en, bir məmulatın ölçüsü.', 'Длина × ширина одного изделия.', 'Length × width of one piece.')];
$classes = ['slug' => 'kart-istismar-sinifleri', 'name' => $n('İstismar sinifləri', 'Классы применения', 'Usage classes'), 'type' => 'multiselect', 'filter' => true, 'options' => [
    $o('21', '21', '21'), $o('22', '22', '22'), $o('23', '23', '23'), $o('31', '31', '31'), $o('32', '32', '32'),
    $o('33', '33', '33'), $o('34', '34', '34'), $o('41', '41', '41'), $o('42', '42', '42'), $o('43', '43', '43'),
    $other($P_CLASS, 'Digər təsdiqlənmiş sinif', 'Другой подтверждённый класс', 'Other confirmed class'), $nsX,
]];
$rollWidth = ['slug' => 'kart-rulon-eni', 'name' => $n('Rulonun eni', 'Ширина рулона', 'Roll width'), 'type' => 'decimal', 'required' => true, 'unit' => 'm', 'maps_to' => 'roll_width'];
$soldArea = ['slug' => 'kart-satilan-sahe', 'name' => $n('Satılan vahidin sahəsi', 'Площадь единицы продажи', 'Area of the sold unit'), 'type' => 'decimal', 'required' => true, 'unit' => 'm²',
    'maps_to' => 'package_size'];

// Thickness presets per class.
$t = fn (array $presets): array => ['presets' => $presets] + $thickness;

// ── D13–D22 helpers (TZ proposal, no workbook): option values Azerbaijani only ──
$a = fn (string $az, array $extra = []): array => ['value' => ['az' => $az]] + $extra;
$opts = fn (array $list): array => array_map(fn ($pair) => $a($pair[0], ['slug' => $pair[1]]), $list);
$otherA = fn (?array $prompt = null): array => $a('Digər', ['slug' => 'diger', 'prompt' => $prompt ?? $P_NAME]);
$when = fn (string $attr, string ...$in): array => ['attr' => $attr, 'in' => $in];
$hint = fn (string $az, string $ru, string $en): array => ['hint' => compact('az', 'ru', 'en')];
$f = fn (string $code, string $key, array $name, string $type, array $extra = []): array => ['slug' => 'kart-'.strtolower($code).'-'.$key, 'name' => $name, 'type' => $type] + $extra;
$card = fn (string $code, array $name, array $units, array $fields, array $extra = []): array => ['code' => $code, 'name' => $name, 'sale' => 'unit', 'units' => $units] + $extra + ['fields' => $fields];
$yesNo = [$yes, $no];

$P_CODE = $n('Rəngi və ya kodunu yazın', 'Укажите цвет или код', 'Enter the colour or its code');

// Shared by mosaic, skirting and transition profiles.
$color = ['slug' => 'kart-dos-reng', 'name' => $n('Rəng', 'Цвет', 'Colour'), 'type' => 'dropdown', 'required' => true, 'filter' => true, 'options' => [...$opts([
    ['Ağ', 'ag'], ['Qara', 'qara'], ['Boz', 'boz'], ['Bej', 'bej'], ['Qəhvəyi', 'qehveyi'], ['Ağac dekoru', 'agac-dekoru'],
    ['Gümüşü', 'gumusu'], ['Qızılı', 'qizili'], ['Bürünc', 'burunc'], ['Çoxrəngli', 'coxrengli'],
]), $otherA($P_CODE)]];
// Skirting boards and transition profiles share one material list.
$profileMaterial = ['slug' => 'kart-dos-profil-material', 'name' => $n('Material', 'Материал', 'Material'), 'type' => 'dropdown', 'required' => true, 'filter' => true, 'options' => [...$opts([
    ['Alüminium', 'aluminium'], ['Paslanmayan polad', 'paslanmayan-polad'], ['Latun', 'latun'], ['PVC', 'pvc'], ['MDF', 'mdf'],
    ['Ağac', 'agac'], ['Poliuretan', 'poliuretan'], ['Polistirol', 'polistirol'],
]), $otherA()]];
$length = ['slug' => 'kart-dos-uzunluq', 'name' => $n('Uzunluq', 'Длина', 'Length'), 'type' => 'decimal', 'required' => true, 'unit' => 'mm'];

// D19: the sheet boards (everything but joists).
$boards = ['sement-yonqar', 'gips-lif', 'osb', 'yonqar', 'faner', 'diger'];

$floorCards = [
    // ── D01 ──────────────────────────────────────────────────────────────
    'laminat' => [
        'code' => 'D01',
        'name' => $n('Laminat', 'Ламинат', 'Laminate'),
        'sale' => 'area',
        'fields' => [
            ['slug' => 'kart-laminat-istismar-sinfi', 'name' => $n('İstismar sinfi', 'Класс применения', 'Usage class'), 'type' => 'dropdown', 'required' => true, 'filter' => true, 'options' => [
                $o('31', '31', '31'), $o('32', '32', '32'), $o('33', '33', '33'), $o('34', '34', '34'),
                $other($P_CLASS, 'Pasporta əsasən digər', 'Другой по паспорту', 'Other per passport'), $ns,
            ]],
            ['slug' => 'kart-asinma-sinfi', 'name' => $n('Aşınma sinfi', 'Класс износостойкости', 'Abrasion class'), 'type' => 'dropdown', 'filter' => true, 'options' => [
                $o('AC3', 'AC3', 'AC3', ['slug' => 'ac3']), $o('AC4', 'AC4', 'AC4', ['slug' => 'ac4']),
                $o('AC5', 'AC5', 'AC5', ['slug' => 'ac5']), $o('AC6', 'AC6', 'AC6', ['slug' => 'ac6']),
                $other($P_CLASS, 'Pasporta əsasən digər', 'Другой по паспорту', 'Other per passport'), $ns,
            ]],
            ['slug' => 'kart-panel-qalinligi', 'name' => $n('Panelin qalınlığı', 'Толщина панели', 'Panel thickness'), 'type' => 'decimal', 'required' => true, 'unit' => 'mm', 'filter' => true,
                'presets' => [6, 7, 8, 9, 10, 11, 12, 14], 'hint' => $n('Etiketdəki dəqiq qalınlıq.', 'Точная толщина с этикетки.', 'Exact thickness from the label.')],
            ['slug' => 'kart-panel-olcusu', 'name' => $n('Panelin ölçüsü', 'Размер панели', 'Panel size'), 'type' => 'size', 'required' => true, 'unit' => 'mm',
                'presets' => ['1200×190', '1218×198', '1285×192', '1286×192', '1288×195', '1292×194', '1380×157', '1380×193', '1380×244', '1845×244', '2050×244'],
                'hint' => $n('Uzunluq × en, bir panelin ölçüsü.', 'Длина × ширина одной панели.', 'Length × width of one panel.')],
            ['filter' => true] + $water,
            ['filter' => true] + $heating,
            $underlay,
            ['slug' => 'kart-laminat-birlesme', 'name' => $n('Birləşmə növü', 'Тип соединения', 'Joint type'), 'type' => 'dropdown', 'options' => [
                $o('Kilidli', 'Замковое', 'Click lock', ['slug' => 'kilidli']),
                $o('Yapışqanla birləşdirilən dil–yiv', 'Шип–паз на клей', 'Glued tongue and groove', ['slug' => 'yapisqanla-dil-yiv']),
                $other($P_NAME), $ns,
            ]],
            $packArea,
            $warranty,
        ],
    ],

    // ── D02 ──────────────────────────────────────────────────────────────
    'parket-parket-lovhesi' => [
        'code' => 'D02',
        'name' => $n('Parket lövhəsi', 'Паркетная доска', 'Parquet board'),
        'sale' => 'area',
        'fields' => [
            ['slug' => 'kart-parket-konstruksiya', 'name' => $n('Konstruksiya', 'Конструкция', 'Construction'), 'type' => 'dropdown', 'required' => true, 'filter' => true, 'options' => [
                $o('Üçqatlı', 'Трёхслойная', 'Three-layer', ['slug' => 'ucqatli']),
                $o('Çoxqatlı ağac', 'Многослойная древесина', 'Multi-layer wood', ['slug' => 'coxqatli-agac']),
            ]],
            $wood,
            ['filter' => true] + $t([10, 13, 14, 15, 20, 21]),
            $topLayer,
            ['presets' => ['1200×190', '1800×190', '1900×190', '2000×190', '2200×190', '2200×207', '2266×188', '2400×220'], 'rules' => ['mixed_when' => ['attr' => 'kart-uzunluq-formati', 'in' => ['qarisiq-uzunluqlar']]]] + $pieceSize,
            $lengthFormat,
            $finish,
            $woodJoint,
            $woodLaying,
            ['filter' => true] + $heating,
            $packArea,
            $warranty,
        ],
    ],

    // ── D03 ──────────────────────────────────────────────────────────────
    'muhendislik-lovhesi' => [
        'code' => 'D03',
        'name' => $n('Mühəndis parketi', 'Инженерная доска', 'Engineered wood'),
        'sale' => 'area',
        'fields' => [
            ['slug' => 'kart-muhendis-esas', 'name' => $n('Əsas materialı', 'Материал основы', 'Core material'), 'type' => 'dropdown', 'required' => true, 'options' => [
                $o('Faner', 'Фанера', 'Plywood', ['slug' => 'faner']),
                $other($n('Konstruksiyanı və mənbəni yazın', 'Укажите конструкцию и источник', 'Describe the construction and its source')),
            ]],
            $wood,
            ['filter' => true] + $t([10, 12, 14, 15, 16, 20, 21]),
            $topLayer,
            ['presets' => ['1200×150', '1500×190', '1800×180', '1900×190', '2200×190', '2200×220', '2400×240'], 'rules' => ['mixed_when' => ['attr' => 'kart-uzunluq-formati', 'in' => ['qarisiq-uzunluqlar']]]] + $pieceSize,
            $lengthFormat,
            $finish,
            $woodJoint,
            $woodLaying,
            ['filter' => true] + $heating,
            $packArea,
            $warranty,
        ],
    ],

    // ── D04 ──────────────────────────────────────────────────────────────
    'parket-ve-massiv-taxta' => [
        'code' => 'D04',
        'name' => $n('Parket və massiv taxta', 'Паркет и массивная доска', 'Parquet and solid wood'),
        'sale' => 'area',
        'fields' => [
            ['slug' => 'kart-parket-mehsul-novu', 'name' => $n('Məhsul növü', 'Тип товара', 'Product type'), 'type' => 'dropdown', 'required' => true, 'filter' => true, 'options' => [
                $o('Parça parket', 'Штучный паркет', 'Strip parquet', ['slug' => 'parca-parket']),
                $o('Modul parket', 'Модульный паркет', 'Modular parquet', ['slug' => 'modul-parket']),
                $o('Massiv taxta', 'Массивная доска', 'Solid wood board', ['slug' => 'massiv-taxta']),
            ]],
            ['slug' => 'kart-parket-massiv-konstruksiya', 'name' => $n('Konstruksiya', 'Конструкция', 'Construction'), 'type' => 'dropdown', 'required' => true,
                'hint' => $n('Parça parket və massiv taxta üçün yalnız massiv.', 'Для штучного паркета и массивной доски — только массив.', 'Strip parquet and solid boards are solid only.'), 'options' => [
                    $o('Massiv', 'Массив', 'Solid', ['slug' => 'massiv']),
                    $o('Çoxqatlı modul', 'Многослойный модуль', 'Multi-layer module', ['slug' => 'coxqatli-modul', 'when' => ['attr' => 'kart-parket-mehsul-novu', 'in' => ['modul-parket']]]),
                ]],
            $wood,
            ['filter' => true] + $t([14, 15, 16, 18, 19, 20, 22]),
            ['presets' => ['300×60', '400×70', '490×70', '500×75', '600×90', '400×400', '450×450', '1200×120', '1800×140'], 'rules' => ['mixed_when' => ['attr' => 'kart-uzunluq-formati', 'in' => ['qarisiq-uzunluqlar']]]] + $pieceSize,
            $lengthFormat,
            $finish,
            $woodJoint,
            $woodLaying,
            ['filter' => true] + $heating,
            $packArea,
            $warranty,
        ],
    ],

    // ── D05 ──────────────────────────────────────────────────────────────
    'spc-lvt-kvars-vinil' => [
        'code' => 'D05',
        'name' => $n('Vinil və kvars vinil örtüklər', 'Виниловые и кварцвиниловые покрытия', 'Vinyl and stone-plastic flooring'),
        'sale' => 'area',
        'fields' => [
            ['slug' => 'kart-vinil-novu', 'name' => $n('Məhsul növü', 'Тип товара', 'Product type'), 'type' => 'dropdown', 'required' => true, 'filter' => true,
                'hint' => $n('İstehsalçı konstruksiyasına əsasən.', 'По конструкции производителя.', 'Per the manufacturer construction.'), 'options' => [
                    $o('SPC', 'SPC', 'SPC', ['slug' => 'spc']), $o('LVT', 'LVT', 'LVT', ['slug' => 'lvt']),
                    $o('WPC', 'WPC', 'WPC', ['slug' => 'wpc']), $other($P_NAME),
                ]],
            ['filter' => true] + $t([2, 2.5, 3, 3.5, 4, 4.5, 5, 5.5, 6, 6.5, 7, 8]),
            ['presets' => ['1220×180', '1220×228', '1235×180', '1500×230', '1800×225', '914×152', '610×305', '457×457', '600×600']] + $pieceSize,
            ['slug' => 'kart-qoruyucu-qat', 'name' => $n('Qoruyucu qatın qalınlığı', 'Толщина защитного слоя', 'Wear layer thickness'), 'type' => 'decimal', 'unit' => 'mm', 'filter' => true,
                'presets' => [0.1, 0.2, 0.3, 0.4, 0.5, 0.55, 0.7, 1], 'rules' => ['lte' => 'kart-umumi-qalinliq'],
                'hint' => $n('Ümumi qalınlıqdan böyük deyil.', 'Не больше общей толщины.', 'Not more than the total thickness.')],
            $classes,
            ['slug' => 'kart-vinil-dosenme', 'name' => $n('Döşənmə üsulu', 'Способ укладки', 'Installation method'), 'type' => 'multiselect', 'filter' => true, 'options' => [
                $o('Kilidli', 'Замковый', 'Click lock', ['slug' => 'kilidli']), $o('Yapışdırma', 'Приклеивание', 'Glue-down', ['slug' => 'yapisdirma']),
                $o('Sərbəst döşəmə', 'Свободная укладка', 'Loose lay', ['slug' => 'serbest-doseme']),
                $o('Özüyapışan', 'Самоклеящийся', 'Self-adhesive', ['slug' => 'ozuyapisan']), $nsX,
            ]],
            $underlay,
            $water,
            $heating,
            $packArea,
            $warranty,
        ],
    ],

    // ── D06 ──────────────────────────────────────────────────────────────
    'linoleum' => [
        'code' => 'D06',
        'name' => $n('Linoleum', 'Линолеум', 'Linoleum'),
        'sale' => 'area',
        'fields' => [
            ['slug' => 'kart-linoleum-material', 'name' => $n('Material', 'Материал', 'Material'), 'type' => 'dropdown', 'required' => true, 'filter' => true, 'options' => [
                $o('Heterogen PVC', 'Гетерогенный ПВХ', 'Heterogeneous PVC', ['slug' => 'heterogen-pvc']),
                $o('Homogen PVC', 'Гомогенный ПВХ', 'Homogeneous PVC', ['slug' => 'homogen-pvc']),
                $o('Təbii linoleum', 'Натуральный линолеум', 'Natural linoleum', ['slug' => 'tebii-linoleum']),
            ]],
            ['presets' => [1.5, 2, 2.5, 3, 3.5, 4, 4.5, 5], 'filter' => true] + $rollWidth,
            ['filter' => true] + $t([1.5, 1.8, 2, 2.2, 2.5, 2.8, 3, 3.5, 4, 4.5, 5]),
            ['slug' => 'kart-linoleum-esas', 'name' => $n('Əsas', 'Основа', 'Backing'), 'type' => 'dropdown', 'filter' => true, 'options' => [
                $o('Köpük', 'Вспененная', 'Foam', ['slug' => 'kopuk']), $o('Keçə', 'Войлок', 'Felt', ['slug' => 'kece']),
                $o('Tekstil', 'Текстиль', 'Textile', ['slug' => 'tekstil']), $o('Cut', 'Джут', 'Jute', ['slug' => 'cut']),
                $o('Əsassız', 'Без основы', 'No backing', ['slug' => 'esassiz']), $other($P_NAME), $ns,
            ]],
            $classes,
            ['slug' => 'kart-linoleum-dosenme', 'name' => $n('Döşənmə üsulu', 'Способ укладки', 'Installation method'), 'type' => 'multiselect', 'options' => [
                $o('Yapışdırma', 'Приклеивание', 'Glue-down', ['slug' => 'yapisdirma']),
                $o('Sərbəst döşəmə', 'Свободная укладка', 'Loose lay', ['slug' => 'serbest-doseme']),
                $o('Lentlə', 'На ленту', 'Tape', ['slug' => 'lentle']),
                $other($P_NAME, 'Digər təsdiqlənmiş üsul', 'Другой подтверждённый способ', 'Other confirmed method'), $nsX,
            ]],
            $water,
            $heating,
            $warranty,
        ],
    ],

    // ── D07 ──────────────────────────────────────────────────────────────
    'keramoqranit' => [
        'code' => 'D07',
        'name' => $n('Keramoqranit', 'Керамогранит', 'Porcelain stoneware'),
        'sale' => 'area',
        'fields' => [
            $floorUse,
            $shape,
            ['presets' => ['300×300', '300×600', '400×400', '450×450', '600×600', '600×1200', '800×800', '800×1600', '1000×1000', '1200×1200', '1200×2400', '150×900', '200×1000', '200×1200', '200×1600'],
                'rules' => ['square_when' => ['attr' => 'kart-forma', 'in' => ['kvadrat']]]] + $pieceSize,
            $t([6, 7, 8, 8.5, 9, 9.5, 10, 11, 12, 20, 30]),
            $surface,
            $glazed,
            ['filter' => true] + $rectified,
            ['filter' => true] + $slip,
            ['filter' => true] + $frost,
            $heating,
            $packArea,
            $warranty,
        ],
    ],

    // ── D08 ──────────────────────────────────────────────────────────────
    'keramik-plite-kafel' => [
        'code' => 'D08',
        'name' => $n('Döşəmə plitəsi', 'Напольная плитка', 'Floor tiles'),
        'sale' => 'area',
        'fields' => [
            ['slug' => 'kart-plite-material', 'name' => $n('Material növü', 'Тип материала', 'Material type'), 'type' => 'dropdown', 'required' => true, 'filter' => true,
                'hint' => $n('Keramoqranit ayrıca kateqoriyadır.', 'Керамогранит — отдельная категория.', 'Porcelain stoneware is a separate category.'), 'options' => [
                    $o('Keramika', 'Керамика', 'Ceramic', ['slug' => 'keramika']), $o('Klinker', 'Клинкер', 'Clinker', ['slug' => 'klinker']),
                ]],
            $floorUse,
            $shape,
            ['presets' => ['200×200', '245×245', '250×400', '300×300', '330×330', '333×333', '400×400', '420×420', '450×450', '300×600', '600×600'],
                'rules' => ['square_when' => ['attr' => 'kart-forma', 'in' => ['kvadrat']]]] + $pieceSize,
            $t([6, 7, 7.5, 8, 8.5, 9, 10, 12]),
            $surface,
            $glazed,
            $rectified,
            ['filter' => true] + $slip,
            ['filter' => true] + $frost,
            $heating,
            $packArea,
            $warranty,
        ],
    ],

    // ── D09 ──────────────────────────────────────────────────────────────
    'tebii-das-mermer-qranit-sist-ve-s' => [
        'code' => 'D09',
        'name' => $n('Döşəmə üçün təbii daş', 'Натуральный камень для пола', 'Natural stone for floors'),
        'sale' => 'area',
        // Custom size: no automatic price — the buyer asks for an estimate.
        'quote_when' => ['attr' => 'kart-das-format', 'in' => ['ferdi-olcu']],
        'fields' => [
            ['slug' => 'kart-das-novu', 'name' => $n('Daş növü', 'Вид камня', 'Stone type'), 'type' => 'dropdown', 'required' => true, 'filter' => true, 'options' => [
                $o('Mərmər', 'Мрамор', 'Marble', ['slug' => 'mermer']), $o('Qranit', 'Гранит', 'Granite', ['slug' => 'qranit']),
                $o('Travertin', 'Травертин', 'Travertine', ['slug' => 'travertin']), $o('Əhəngdaşı', 'Известняк', 'Limestone', ['slug' => 'ehengdasi']),
                $o('Şist', 'Сланец', 'Slate', ['slug' => 'sist']), $other($P_NAME),
            ]],
            ['slug' => 'kart-das-format', 'name' => $n('Format', 'Формат', 'Format'), 'type' => 'dropdown', 'required' => true, 'filter' => true, 'options' => [
                $o('Hazır plitə', 'Готовая плитка', 'Ready tile', ['slug' => 'hazir-plite']),
                $o('Hazır sləb', 'Готовый слэб', 'Ready slab', ['slug' => 'hazir-sleb']),
                $o('Fərdi ölçü', 'Индивидуальный размер', 'Custom size', ['slug' => 'ferdi-olcu']),
            ]],
            $floorUse,
            ['filter' => true] + $t([10, 12, 15, 18, 20, 25, 30, 40, 50]),
            ['slug' => 'kart-hazir-memulat-olcusu', 'name' => $n('Hazır məmulatın ölçüsü', 'Размер готового изделия', 'Ready piece size'), 'type' => 'size', 'required' => true, 'unit' => 'mm',
                'presets' => ['300×300', '305×305', '400×400', '457×457', '600×300', '600×400', '600×600', '610×305', '610×610', '800×400', '1200×600'],
                'when' => ['attr' => 'kart-das-format', 'in' => ['hazir-plite', 'hazir-sleb']],
                'hint' => $n('Uzunluq × en. Fərdi ölçü razılaşdırılır.', 'Длина × ширина. Индивидуальный размер согласовывается.', 'Length × width. Custom sizes are agreed separately.')],
            $surface,
            ['slug' => 'kart-hopdurucu-emal', 'name' => $n('Hopdurucu qoruyucu emal', 'Защитная пропитка', 'Protective impregnation'), 'type' => 'dropdown', 'options' => $ynn],
            ['slug' => 'kart-mesame-doldurma', 'name' => $n('Məsamələrin doldurulması', 'Заполнение пор', 'Filled pores'), 'type' => 'dropdown', 'options' => $ynn],
            $slip,
            ['filter' => true] + $frost,
            $heating,
            ['slug' => 'kart-das-satis-vahidi', 'name' => $n('Satış vahidi', 'Единица продажи', 'Sales unit'), 'type' => 'dropdown', 'required' => true,
                'maps_to' => 'package_unit', 'when' => ['attr' => 'kart-das-format', 'in' => ['hazir-plite', 'hazir-sleb']], 'options' => [
                    $o('Ədəd', 'Штука', 'Piece', ['slug' => 'eded']), $o('Qablaşdırma', 'Упаковка', 'Pack', ['slug' => 'qablasdirma']),
                ]],
            ['when' => ['attr' => 'kart-das-format', 'in' => ['hazir-plite', 'hazir-sleb']],
                'hint' => $n('Ədədin və ya qablaşdırmanın dəqiq sahəsi.', 'Точная площадь штуки или упаковки.', 'Exact area of one piece or pack.')] + $soldArea,
            $warranty,
        ],
    ],

    // ── D10 ──────────────────────────────────────────────────────────────
    'kovrolin' => [
        'code' => 'D10',
        'name' => $n('Kovrolin və xalça plitələri', 'Ковролин и ковровая плитка', 'Carpet and carpet tiles'),
        'sale' => 'area',
        'fields' => [
            ['slug' => 'kart-xalca-format', 'name' => $n('Format', 'Формат', 'Format'), 'type' => 'dropdown', 'required' => true, 'filter' => true, 'options' => [
                $o('Rulon', 'Рулон', 'Roll', ['slug' => 'rulon']),
                $o('Xalça plitəsi', 'Ковровая плитка', 'Carpet tile', ['slug' => 'xalca-plitesi']),
                $o('Uzunsov modul', 'Удлинённый модуль', 'Plank module', ['slug' => 'uzunsov-modul']),
            ]],
            ['slug' => 'kart-lif-novu', 'name' => $n('Lif növü', 'Тип волокна', 'Fibre'), 'type' => 'dropdown', 'filter' => true, 'options' => [
                $o('Poliamid', 'Полиамид', 'Polyamide', ['slug' => 'poliamid']), $o('Polipropilen', 'Полипропилен', 'Polypropylene', ['slug' => 'polipropilen']),
                $o('Polyester', 'Полиэстер', 'Polyester', ['slug' => 'polyester']), $o('Yun', 'Шерсть', 'Wool', ['slug' => 'yun']),
                $o('Qarışıq', 'Смешанное', 'Blend', ['slug' => 'qarisiq']), $other($P_NAME), $ns,
            ]],
            ['slug' => 'kart-xov-novu', 'name' => $n('Xov növü', 'Тип ворса', 'Pile type'), 'type' => 'dropdown', 'filter' => true, 'options' => [
                $o('İlgəkli', 'Петлевой', 'Loop', ['slug' => 'ilgekli']), $o('Kəsilmiş', 'Разрезной', 'Cut pile', ['slug' => 'kesilmis']),
                $o('Qarışıq', 'Комбинированный', 'Cut and loop', ['slug' => 'qarisiq']),
                $o('İynə ilə bərkidilmiş', 'Иглопробивной', 'Needle-punched', ['slug' => 'iyne-ile-berkidilmis']),
                $other($P_NAME, 'Digər təsdiqlənmiş növ', 'Другой подтверждённый тип', 'Other confirmed type'), $ns,
            ]],
            ['filter' => true] + $t([3, 4, 5, 6, 7, 8, 9, 10, 12, 15]),
            ['presets' => [2, 3, 3.66, 4, 5], 'when' => ['attr' => 'kart-xalca-format', 'in' => ['rulon']]] + $rollWidth,
            ['slug' => 'kart-plite-olcusu', 'name' => $n('Plitənin və ya modulun ölçüsü', 'Размер плитки или модуля', 'Tile or module size'), 'type' => 'size', 'required' => true, 'unit' => 'mm',
                'presets' => ['500×500', '600×600', '250×1000', '500×1000', '1000×1000'], 'when' => ['attr' => 'kart-xalca-format', 'in' => ['xalca-plitesi', 'uzunsov-modul']]],
            ['slug' => 'kart-xalca-esas', 'name' => $n('Əsas', 'Основа', 'Backing'), 'type' => 'dropdown', 'options' => [
                $o('Bitum', 'Битум', 'Bitumen', ['slug' => 'bitum']), $o('PVC', 'ПВХ', 'PVC', ['slug' => 'pvc']),
                $o('Keçə', 'Войлок', 'Felt', ['slug' => 'kece']), $o('Lateks', 'Латекс', 'Latex', ['slug' => 'lateks']), $other($P_NAME), $ns,
            ]],
            ['slug' => 'kart-istismar-sinifleri-tekstil', 'name' => $n('İstismar sinifləri', 'Классы применения', 'Usage classes'), 'type' => 'multiselect', 'filter' => true, 'options' => [
                $o('21', '21', '21'), $o('22', '22', '22'), $o('23', '23', '23'), $o('31', '31', '31'), $o('32', '32', '32'), $o('33', '33', '33'),
                $other($P_CLASS, 'Digər təsdiqlənmiş sinif', 'Другой подтверждённый класс', 'Other confirmed class'), $nsX,
            ]],
            $heating,
            ['slug' => 'kart-tekerli-kreslo', 'name' => $n('Təkərli kresloya uyğunluq', 'Совместимость с офисным креслом', 'Castor chair compatible'), 'type' => 'dropdown', 'options' => $ynn],
            ['when' => ['attr' => 'kart-xalca-format', 'in' => ['xalca-plitesi', 'uzunsov-modul']]] + $packArea,
            $warranty,
        ],
    ],

    // ── D11 ──────────────────────────────────────────────────────────────
    'terras-lovhesi-dpk' => [
        'code' => 'D11',
        'name' => $n('Teras taxtası', 'Террасная доска', 'Decking board'),
        'sale' => 'board',
        'fields' => [
            ['slug' => 'kart-teras-material', 'name' => $n('Material', 'Материал', 'Material'), 'type' => 'dropdown', 'required' => true, 'filter' => true, 'options' => [
                $o('Ağac', 'Дерево', 'Wood', ['slug' => 'agac']), $o('WPC', 'ДПК (WPC)', 'WPC', ['slug' => 'wpc']),
            ]],
            ['slug' => 'kart-teras-profil', 'name' => $n('Profil', 'Профиль', 'Profile'), 'type' => 'dropdown', 'required' => true, 'filter' => true,
                'hint' => $n('Adi ağac taxtada içiboş seçilmir.', 'У обычной деревянной доски пустотелый профиль не выбирается.', 'A plain wooden board cannot be hollow.'), 'options' => [
                    $o('Bütöv', 'Полнотелый', 'Solid', ['slug' => 'butov']),
                    $o('İçiboş', 'Пустотелый', 'Hollow', ['slug' => 'icibos', 'when' => ['attr' => 'kart-teras-material', 'in' => ['wpc']]]),
                    $other($n('Xüsusi konstruksiyanı izah edin', 'Опишите особую конструкцию', 'Describe the special construction')),
                ]],
            ['slug' => 'kart-taxta-uzunlugu', 'name' => $n('Taxtanın uzunluğu', 'Длина доски', 'Board length'), 'type' => 'decimal', 'required' => true, 'unit' => 'mm', 'filter' => true,
                'presets' => [2000, 2200, 2400, 2500, 2800, 2900, 3000, 3600, 4000, 4500, 6000], 'maps_to' => 'length_mm',
                'hint' => $n('Hər zavod uzunluğu ayrıca məhsul variantıdır.', 'Каждая заводская длина — отдельный вариант товара.', 'Each factory length is a separate product variant.')],
            ['slug' => 'kart-taxta-eni', 'name' => $n('Taxtanın eni', 'Ширина доски', 'Board width'), 'type' => 'decimal', 'required' => true, 'unit' => 'mm',
                'presets' => [90, 95, 120, 125, 140, 142, 145, 146, 150, 160], 'maps_to' => 'width_mm',
                'hint' => $n('Tam qabarit en.', 'Полная габаритная ширина.', 'Full overall width.')],
            ['filter' => true] + $t([19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 30, 32, 35]),
            ['slug' => 'kart-teras-kenar', 'name' => $n('Kənar', 'Кромка', 'Edge'), 'type' => 'dropdown', 'options' => [
                $o('Yivli', 'С пазом', 'Grooved', ['slug' => 'yivli']), $o('Düz', 'Прямая', 'Square', ['slug' => 'duz']), $other($P_NAME), $ns,
            ]],
            ['slug' => 'kart-teras-seth', 'name' => $n('Səth', 'Поверхность', 'Surface'), 'type' => 'dropdown', 'filter' => true, 'options' => [
                $o('Hamar', 'Гладкая', 'Smooth', ['slug' => 'hamar']), $o('Yivli', 'Рифлёная', 'Ribbed', ['slug' => 'yivli']),
                $o('Fırçalanmış', 'Брашированная', 'Brushed', ['slug' => 'fircalanmis']), $other($P_NAME), $ns,
            ]],
            ['slug' => 'kart-teras-berkitme', 'name' => $n('Bərkitmə üsulu', 'Способ крепления', 'Fixing method'), 'type' => 'multiselect',
                'hint' => $n('Təlimata əsasən.', 'По инструкции.', 'Per the instructions.'), 'options' => [
                    $o('Klips', 'Клипсы', 'Clips', ['slug' => 'klips']), $o('Vint', 'Шурупы', 'Screws', ['slug' => 'vint']), $other($P_NAME), $nsX,
                ]],
            $slip,
            $warranty,
        ],
    ],

    // ── D12 ──────────────────────────────────────────────────────────────
    'seki-pliteleri-ve-tamet' => [
        'code' => 'D12',
        'name' => $n('Səki plitələri və tamet', 'Тротуарная плитка и брусчатка', 'Paving slabs and pavers'),
        'sale' => 'area',
        'fields' => [
            ['slug' => 'kart-seki-material', 'name' => $n('Material', 'Материал', 'Material'), 'type' => 'dropdown', 'required' => true, 'filter' => true, 'options' => [
                $o('Beton', 'Бетон', 'Concrete', ['slug' => 'beton']), $o('Klinker', 'Клинкер', 'Clinker', ['slug' => 'klinker']),
                $o('Təbii daş', 'Натуральный камень', 'Natural stone', ['slug' => 'tebii-das']), $other($P_NAME),
            ]],
            ['slug' => 'kart-seki-format', 'name' => $n('Format', 'Формат', 'Format'), 'type' => 'dropdown', 'required' => true, 'filter' => true, 'options' => [
                $o('Tək element', 'Отдельный элемент', 'Single element', ['slug' => 'tek-element']),
                $o('Dəst', 'Набор', 'Set', ['slug' => 'dest']),
                $o('Fiqurlu', 'Фигурная', 'Shaped', ['slug' => 'fiqurlu']),
            ]],
            ['filter' => true] + $t([30, 40, 45, 50, 60, 70, 80, 100, 120]),
            ['slug' => 'kart-istifade-yuku', 'name' => $n('İstifadə yükü', 'Нагрузка', 'Load'), 'type' => 'multiselect', 'filter' => true,
                'hint' => $n('Yalnız təsdiqlənmiş tətbiqlər.', 'Только подтверждённые применения.', 'Only confirmed uses.'), 'options' => [
                    $o('Piyada', 'Пешеходная', 'Pedestrian', ['slug' => 'piyada']),
                    $o('Minik avtomobili', 'Легковой автомобиль', 'Passenger cars', ['slug' => 'minik-avtomobili']),
                    $o('Ağır nəqliyyat', 'Тяжёлый транспорт', 'Heavy vehicles', ['slug' => 'agir-neqliyyat']), $nsX,
                ]],
            ['slug' => 'kart-element-olcusu', 'name' => $n('Elementin ölçüsü', 'Размер элемента', 'Element size'), 'type' => 'size', 'required' => true, 'unit' => 'mm',
                'presets' => ['100×100', '200×100', '200×200', '210×140', '250×250', '300×300', '400×400', '500×500', '600×300', '600×600', '800×400']],
            ['slug' => 'kart-dest-terkibi', 'name' => $n('Dəstin tərkibi', 'Состав набора', 'Set composition'), 'type' => 'textarea', 'required' => true,
                'when' => ['attr' => 'kart-seki-format', 'in' => ['dest']],
                'hint' => $n('Hər elementin ölçüsü və sayı.', 'Размер и количество каждого элемента.', 'Size and count of every element.')],
            $frost,
            $slip,
            ['slug' => 'kart-su-keciren', 'name' => $n('Su keçirən sistemə uyğunluq', 'Совместимость с водопроницаемой системой', 'Permeable system compatible'), 'type' => 'dropdown', 'filter' => true, 'options' => $ynn],
            ['slug' => 'kart-seki-satis-vahidi', 'name' => $n('Satış vahidi', 'Единица продажи', 'Sales unit'), 'type' => 'dropdown', 'required' => true, 'maps_to' => 'package_unit',
                'hint' => $n('Yalnız tam vahidlər.', 'Только целые единицы.', 'Whole units only.'), 'options' => [
                    $o('Ədəd', 'Штука', 'Piece', ['slug' => 'eded']), $o('Dəst', 'Набор', 'Set', ['slug' => 'dest']), $o('Palet', 'Паллета', 'Pallet', ['slug' => 'palet']),
                ]],
            ['hint' => $n('Seçilmiş ədədin, dəstin və ya paletin sahəsi.', 'Площадь выбранной штуки, набора или паллеты.', 'Area of the chosen piece, set or pallet.')] + $soldArea,
            ['slug' => 'kart-sahe-esasi', 'name' => $n('Sahənin əsası', 'Основа площади', 'Area basis'), 'type' => 'dropdown', 'required' => true, 'options' => [
                $o('Məmulatın sahəsi', 'Площадь изделия', 'Product area', ['slug' => 'memulatin-sahesi']),
                $o('Tikişlər nəzərə alınmış döşənmə sahəsi', 'Площадь укладки с учётом швов', 'Laid area including joints', ['slug' => 'dosenme-sahesi']),
            ]],
            $warranty,
        ],
    ],
];

return [
    'section' => 'dosemeler',

    // The workbook says «Hazır ölçü və ya rəqəmlər»: a listed size or a typed one («Başqa ölçü»).
    'open_size_lists' => true,

    // Names are the ones the client set in the prod admin (the importer writes them back).
    'groups' => [
        // ════ Döşəmə örtükləri: D01–D12 (workbook) + D13–D16 (TZ proposal) ═════════
        [
            'slug' => 'doseme-ortukleri',
            'name' => $n('Döşəmə örtükləri', 'Напольные покрытия', 'Floor coverings'),
            'classes' => $floorCards + [
                // ── D13 ─────────────────────────────────────────────────────
                'mozaika' => $card('D13', $n('Mozaika', 'Мозаика', 'Mosaic'), ['m2', 'sheet'], [
                    $f('D13', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Şüşə', 'suse'], ['Keramika', 'keramika'], ['Təbii daş', 'tebii-das'], ['Metal', 'metal'], ['Qarışıq', 'qarisiq'],
                    ]), $otherA()]]),
                    $f('D13', 'cip-olcusu', $n('Çipin ölçüsü', 'Размер чипа', 'Chip size'), 'size', ['required' => true, 'unit' => 'mm',
                        'presets' => ['10×10', '15×15', '20×20', '23×23', '25×25', '30×30', '48×48', '50×50'],
                    ] + $hint('Bir elementin uzunluğu × eni.', 'Длина × ширина одного элемента.', 'Length × width of one chip.')),
                    $f('D13', 'vereq-olcusu', $n('Vərəqin (torun) ölçüsü', 'Размер листа (сетки)', 'Sheet (mesh) size'), 'size', ['required' => true, 'unit' => 'mm',
                        'presets' => ['300×300', '305×305', '316×316', '318×318', '327×327'],
                    ] + $hint('Tor üzərində bir vərəqin ölçüsü.', 'Размер одного листа на сетке.', 'Size of one sheet on its mesh.')),
                    ['filter' => true] + $t([4, 5, 6, 8, 10]),
                    $color,
                ]),

                // ── D14 ─────────────────────────────────────────────────────
                'mantar-dosemeler' => $card('D14', $n('Mantar döşəmələr', 'Пробковые полы', 'Cork flooring'), ['m2'], [
                    $f('D14', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Yapışdırılan', 'yapisdirilan'], ['Qıfıllı', 'qifilli'],
                    ])]),
                    ['presets' => ['300×300', '450×150', '600×300', '600×450', '900×150', '905×295', '1220×185']] + $pieceSize,
                    ['filter' => true] + $t([4, 6, 8, 10, 10.5, 11, 12]),
                    $finish,
                    $packArea,
                    $warranty,
                ]),

                // ── D15 ─────────────────────────────────────────────────────
                'ozuyayilan-dosemeler-dekorativ' => $card('D15', $n('Özüyayılan döşəmələr (dekorativ)', 'Наливные полы (декоративные)', 'Self-levelling floors (decorative)'), ['kg', 'pack'], [
                    $f('D15', 'esas', $n('Əsas', 'Основа', 'Base'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Epoksid', 'epoksid'], ['Poliuretan', 'poliuretan'], ['Sement', 'sement'],
                    ]), $otherA()]]),
                    $f('D15', 'qat-qalinligi', $n('Qatın qalınlığı', 'Толщина слоя', 'Layer thickness'), 'decimal', ['required' => true, 'unit' => 'mm', 'filter' => true,
                        'presets' => [1, 1.5, 2, 3, 4, 5, 10]] + $hint('İstehsalçının tövsiyə etdiyi qalınlıq.', 'Толщина, рекомендованная производителем.', 'The thickness the maker recommends.')),
                    $f('D15', 'serfiyyat', $n('Sərfiyyat', 'Расход', 'Consumption'), 'decimal', ['required' => true, 'unit' => 'kq/m²']
                        + $hint('İstehsalçıya görə, 1 mm qat üçün.', 'По данным производителя, на слой 1 мм.', 'Per the maker, for a 1 mm layer.')),
                    $f('D15', 'qablasdirma-novu', $n('Qablaşdırma növü', 'Тип упаковки', 'Package type'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Kisə', 'kise'], ['Vedrə', 'vedre'], ['Kanistr', 'kanistr'],
                    ]), $otherA()]]),
                    $f('D15', 'qablasdirma-cekisi', $n('Qablaşdırmanın çəkisi', 'Вес упаковки', 'Package weight'), 'decimal', ['required' => true, 'unit' => 'kq']
                        + $hint('Bir kisə, vedrə və ya kanistrin xalis çəkisi.', 'Масса нетто одного мешка, ведра или канистры.', 'Net weight of one bag, bucket or canister.')),
                    ['required' => false] + $color,
                ]),

                // ── D16 ─────────────────────────────────────────────────────
                'idman-ortuyu' => $card('D16', $n('İdman örtüyü', 'Спортивное покрытие', 'Sports flooring'), ['m2', 'roll'], [
                    $f('D16', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['PVC', 'pvc'], ['Rezin', 'rezin'], ['Taxta', 'taxta'],
                    ]), $otherA()]]),
                    ['filter' => true] + $t([2, 3, 4, 5, 6, 7, 8, 10, 15, 20, 22]),
                    ['required' => false, 'presets' => [1.5, 1.8, 2, 2.5, 3], 'when' => $when('kart-d16-material', 'pvc', 'rezin', 'diger')]
                        + $hint('Rulonla satılan örtük üçün.', 'Для покрытия в рулонах.', 'For flooring sold in rolls.') + $rollWidth,
                    $f('D16', 'idman-novu', $n('İdman növü', 'Вид спорта', 'Sport'), 'multiselect', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Universal', 'universal'], ['Basketbol', 'basketbol'], ['Voleybol', 'voleybol'], ['Futbol', 'futbol'], ['Həndbol', 'hendbol'],
                        ['Tennis', 'tennis'], ['Badminton', 'badminton'], ['Fitnes və trenajor zalı', 'fitnes'], ['Rəqs və aerobika', 'reqs'],
                    ]), $otherA()]]),
                    $warranty,
                ], ['unit_when' => ['roll' => $when('kart-d16-material', 'pvc', 'rezin', 'diger')]]),
            ],
        ],

        // ════ Texniki döşəmə və alt sistemlər: D17, D19 ═════════════════════════
        [
            'slug' => 'texniki-doseme-ve-alt-sistemler',
            'name' => $n('Texniki döşəmə və alt sistemlər', 'Технические полы и подсистемы', 'Technical floors and subsystems'),
            'classes' => [
                // ── D17 ─────────────────────────────────────────────────────
                'qaldirilmis-doseme' => $card('D17', $n('Qaldırılmış döşəmə', 'Фальшпол', 'Raised floor'), ['m2', 'piece'], [
                    ['slug' => 'kart-panel-olcusu', 'name' => $n('Panelin ölçüsü', 'Размер панели', 'Panel size'), 'type' => 'size', 'required' => true, 'unit' => 'mm',
                        'presets' => ['500×500', '600×600', '610×610'],
                        'hint' => $n('Uzunluq × en, bir panelin ölçüsü.', 'Длина × ширина одной панели.', 'Length × width of one panel.')],
                    $f('D17', 'panel-material', $n('Panelin materialı', 'Материал панели', 'Panel material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Kalsium sulfat', 'kalsium-sulfat'], ['Yonqar lövhə', 'yonqar-lovhe'], ['Sement doldurulmuş polad', 'polad-sement'], ['Alüminium', 'aluminium'],
                    ]), $otherA()]]),
                    $f('D17', 'hundurluk-min', $n('Hündürlük, min', 'Высота, мин.', 'Height, min'), 'decimal', ['required' => true, 'unit' => 'mm', 'filter' => true,
                        'rules' => ['lte' => 'kart-d17-hundurluk-maks']] + $hint('Dayaqların tənzimlənmə aralığının minimumu.', 'Минимум диапазона регулировки опор.', 'Lowest setting of the pedestals.')),
                    $f('D17', 'hundurluk-maks', $n('Hündürlük, maks', 'Высота, макс.', 'Height, max'), 'decimal', ['required' => true, 'unit' => 'mm']
                        + $hint('Dayaqların tənzimlənmə aralığının maksimumu.', 'Максимум диапазона регулировки опор.', 'Highest setting of the pedestals.')),
                    $f('D17', 'yuk', $n('Yükgötürmə qabiliyyəti', 'Несущая способность', 'Load capacity'), 'decimal', ['required' => true, 'unit' => 'kN/m²', 'filter' => true]
                        + $hint('Paylanmış yük, istehsalçının məlumatına görə.', 'Распределённая нагрузка по данным производителя.', 'Distributed load, per the maker.')),
                    $warranty,
                ]),

                // ── D19 ─────────────────────────────────────────────────────
                // Joists (laqa) are not sheets: they have a section and a length and sell by the piece.
                'csp-gvl-osp-dsp-faner-keramzit-laqalar-dosemenin-qara-bazasi' => $card('D19',
                    $n('Döşəmə üçün qara baza: sement-yonqar lövhə, gips-lif lövhə, OSB, yonqar lövhə, faner, laqalar', 'Черновое основание пола: ЦСП, ГВЛ, OSB, ДСП, фанера, лаги', 'Subfloor boards: cement-bonded board, gypsum fibre board, OSB, chipboard, plywood, joists'),
                    ['sheet', 'm2', 'piece'], [
                        $f('D19', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                            ['Sement-yonqar lövhə', 'sement-yonqar'], ['Gips-lif lövhə', 'gips-lif'], ['OSB', 'osb'], ['Yonqar lövhə', 'yonqar'],
                            ['Faner', 'faner'], ['Laqa', 'laqa'],
                        ]), $otherA()]]),
                        $f('D19', 'vereq-olcusu', $n('Vərəqin ölçüsü', 'Размер листа', 'Sheet size'), 'size', ['required' => true, 'unit' => 'mm',
                            'presets' => ['1200×600', '1500×1200', '2440×1220', '2500×1200', '2500×1250', '2700×1200', '2800×2070', '3200×1250'],
                            'when' => $when('kart-d19-material', ...$boards)] + $hint('Uzunluq × en.', 'Длина × ширина.', 'Length × width.')),
                        ['filter' => true, 'when' => $when('kart-d19-material', ...$boards)] + $t([6, 8, 9, 10, 12, 15, 16, 18, 20, 22, 24]),
                        $f('D19', 'laqa-kesiyi', $n('Laqanın kəsiyi', 'Сечение лаги', 'Joist section'), 'size', ['required' => true, 'unit' => 'mm',
                            'presets' => ['40×50', '50×50', '40×100', '50×100', '50×150'], 'when' => $when('kart-d19-material', 'laqa')]
                            + $hint('En × hündürlük.', 'Ширина × высота.', 'Width × height.')),
                        ['presets' => [2000, 3000, 4000, 6000], 'when' => $when('kart-d19-material', 'laqa')] + $length,
                    ], ['unit_when' => [
                        'sheet' => $when('kart-d19-material', ...$boards),
                        'm2' => $when('kart-d19-material', ...$boards),
                        'piece' => $when('kart-d19-material', 'laqa'),
                    ]]),
            ],
        ],

        // ════ Plintus, profillər və altlıqlar: D18, D20, D21, D22 ════════════════
        [
            'slug' => 'altliqlar-ve-birlesdirici-elementler',
            'name' => $n('Plintus, profillər və altlıqlar', 'Плинтусы, профили и подложки', 'Skirting boards, profiles and underlays'),
            'classes' => [
                // ── D18 ─────────────────────────────────────────────────────
                'plintus-ve-doseme-profilleri' => $card('D18', $n('Plintus və döşəmə profilləri', 'Плинтусы и напольные профили', 'Skirting boards and floor profiles'), ['piece', 'lm'], [
                    $profileMaterial,
                    $f('D18', 'hundurluk', $n('Hündürlük', 'Высота', 'Height'), 'decimal', ['required' => true, 'unit' => 'mm', 'filter' => true,
                        'presets' => [40, 50, 55, 60, 70, 80, 100, 120, 150]]),
                    ['presets' => [2000, 2200, 2400, 2500, 2700, 3000]] + $hint('Bir plankanın uzunluğu.', 'Длина одной планки.', 'Length of one piece.') + $length,
                    $f('D18', 'kabel-kanali', $n('Kabel kanalı', 'Кабель-канал', 'Cable channel'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $yesNo]),
                    $color,
                ]),

                // ── D20 ─────────────────────────────────────────────────────
                'doseme-ortukleri-ucun-altliq' => $card('D20', $n('Döşəmə örtükləri üçün altlıq', 'Подложка для напольных покрытий', 'Underlay for floor coverings'), ['m2', 'roll', 'pack'], [
                    $f('D20', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Polietilen köpük', 'polietilen'], ['Ekstruziya polistirol (XPS)', 'xps'], ['Mantar', 'mantar'], ['Ağac lifi', 'agac-lifi'],
                        ['Poliuretan', 'poliuretan'], ['Rezin', 'rezin'],
                    ]), $otherA()]]),
                    ['filter' => true] + $t([1, 1.5, 2, 3, 4, 5, 6, 7, 10]),
                    $hint('Bir rulonda və ya paketdə neçə m² var.', 'Сколько м² в одном рулоне или упаковке.', 'How many m² one roll or pack holds.') + $soldArea,
                    $f('D20', 'buxar-izolyasiyasi', $n('Buxar izolyasiyası', 'Пароизоляция', 'Vapour barrier'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $yesNo]),
                ]),

                // ── D21 ─────────────────────────────────────────────────────
                'kecid-ve-birlesdirici-profiller' => $card('D21', $n('Keçid və birləşdirici profillər', 'Переходные и соединительные профили', 'Transition and joining profiles'), ['piece', 'lm'], [
                    $f('D21', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Keçid', 'kecid'], ['Səviyyə fərqi', 'seviyye'], ['Bitmə', 'bitme'], ['Pillə', 'pille'],
                    ]), $otherA()]]),
                    $profileMaterial,
                    ['presets' => [900, 1000, 1800, 2000, 2700, 3000]] + $hint('Bir profilin uzunluğu.', 'Длина одного профиля.', 'Length of one profile.') + $length,
                    $color,
                ]),

                // ── D22 ─────────────────────────────────────────────────────
                'dempfer-lenti-sap-ucun' => $card('D22', $n('Dempfer lenti (şap üçün)', 'Демпферная лента (для стяжки)', 'Edge insulation strip (for screed)'), ['roll', 'lm'], [
                    $f('D22', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Polietilen köpük', 'polietilen'], ['Poliuretan köpük', 'poliuretan'], ['Mineral yun', 'mineral-yun'],
                    ]), $otherA()]]),
                    $f('D22', 'en', $n('En', 'Ширина', 'Width'), 'decimal', ['required' => true, 'unit' => 'mm', 'filter' => true,
                        'presets' => [50, 80, 100, 120, 150, 200]]),
                    ['filter' => true] + $t([3, 5, 8, 10, 12]),
                    $f('D22', 'rulon-uzunlugu', $n('Rulonun uzunluğu', 'Длина рулона', 'Roll length'), 'decimal', ['required' => true, 'unit' => 'm',
                        'presets' => [10, 20, 25, 30, 50]]),
                ]),
            ],
        ],
    ],

    // Classes folded into a card class: products move over, the old class closes.
    'merge' => ['xalca-plitesi' => 'kovrolin'],

    // Groups emptied by the move above; closed once nothing points at them.
    'retire_groups' => [
        'universal-ortukler-plite-ve-das-kateqoriyalararasi-sinifler',
        'taxta-ortukler',
        'polimer-ve-rulon-ortukler',
        'xalca-ortukleri',
    ],

    // Class 255 duplicates Landşaft's paving card: the admin switched it off; it keeps
    // its card and products, and a re-import leaves it off.
    'hide_classes' => ['seki-pliteleri-ve-tamet'],
];
