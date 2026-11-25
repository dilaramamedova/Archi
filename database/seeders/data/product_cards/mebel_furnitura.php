<?php

/*
|--------------------------------------------------------------------------
| Mebel furniturası və komplektləşdiriciləri — 28 cards (F01_1 – F12_4)
|--------------------------------------------------------------------------
|
| Source: «Mebel və mebel furniturası» workbook (client, 25.11.2026), block B. Block A
| (M01_1 – M15_2) is `mebel.php`; both files write the same section. Format and decisions:
| docs/product-cards.md. Groups = the 12 numbered «alt bölmə» of block B (Kateqoriyalar
| sheet), placed after block A's 15 groups (`sort_order` 15 … 26).
|
| Attribute slugs: shared `kart-mf-*`, per-card `kart-f01-1-*` … (block A uses `kart-mb-*`).
|
| Kateqoriyalar rules kept here:
| - «Menteşə ədəd, rels cüt və ya dəst, profil metr və ya ədəd» — the sales unit is chosen
|   exactly: single rail / pair / set is an answer that unlocks its unit (`unit_when`),
|   rails and profiles get `lm` only for the parts sold by length.
| - The cabinet's nominal outer width and the minimum inner mounting size are separate
|   fields (`kart-mf-dolab-eni` vs `kart-mf-daxili-*`; inner ≤ nominal).
| - Compatibility = maker + series / code (`kart-mf-uygun-model`) + mounting drawing
|   (`kart-mf-texniki-melumat`).
| - A load limit is never written without what it applies to: «Yük həddi nəyə aiddir»
|   (hissə / cüt / dəst / tam məhsul) comes first, the kg field opens with it and is then
|   required. Per-door / per-wheel limits are their own fields.
|
*/

$n = fn (string $az, string $ru, string $en): array => compact('az', 'ru', 'en');
$o = fn (string $az, string $ru, string $en, array $extra = []): array => ['value' => compact('az', 'ru', 'en')] + $extra;
$a = fn (string $az, array $extra = []): array => ['value' => ['az' => $az]] + $extra;

$P_NAME = $n('Dəqiq adını yazın', 'Укажите точное название', 'Enter the exact name');
$P_CODE = $n('Rəngi və ya kodunu yazın', 'Укажите цвет или код', 'Enter the colour or its code');
$P_RAL = $n('RAL kodunu yazın', 'Укажите код RAL', 'Enter the RAL code');

$ns = $o('Göstərilməyib', 'Не указано', 'Not specified', ['slug' => 'gosterilmeyib']);
$other = fn (?array $prompt = null, string $az = 'Digər', string $slug = 'diger'): array => $a($az, ['slug' => $slug, 'prompt' => $prompt ?? $P_NAME]);
$none = fn (string $az = 'Daxil deyil'): array => $a($az, ['slug' => 'daxil-deyil', 'exclusive' => true]);
$noneX = fn (string $az = 'Yoxdur', string $slug = 'yoxdur'): array => $a($az, ['slug' => $slug, 'exclusive' => true]);
$when = fn (string $attr, string ...$in): array => ['attr' => $attr, 'in' => $in];
$opts = fn (array $list): array => array_map(fn ($pair) => $a($pair[0], ['slug' => $pair[1]]), $list);
$hint = fn (string $az, string $ru, string $en): array => ['hint' => compact('az', 'ru', 'en')];
$yesNo = $opts([['Bəli', 'beli'], ['Xeyr', 'xeyr']]);

$cs = fn (string $code): string => 'kart-'.strtolower(str_replace('_', '-', $code));
$f = fn (string $code, string $key, array $name, string $type, array $extra = []): array => ['slug' => $cs($code).'-'.$key, 'name' => $name, 'type' => $type] + $extra;
$card = fn (string $code, array $name, array $units, array $fields, array $extra = []): array => ['code' => $code, 'name' => $name, 'sale' => 'unit', 'units' => $units, 'fields' => $fields] + $extra;
$count = fn (string $code, string $key, array $name, array $extra = []): array => $extra + ['slug' => $cs($code).'-'.$key, 'name' => $name, 'type' => 'decimal', 'unit' => 'ədəd', 'rules' => ['integer' => true]];
$mm = fn (string $code, string $key, array $name, array $extra = []): array => ['slug' => $cs($code).'-'.$key, 'name' => $name, 'type' => 'decimal', 'unit' => 'mm'] + $extra;
// «Tip* — Bir seçim»: the card's first field, required and a filter.
$type = fn (string $code, array $list, bool $withOther = false, array $extra = []): array => [
    'slug' => $cs($code).'-tip', 'name' => $n('Tip', 'Тип', 'Type'), 'type' => 'dropdown', 'required' => true, 'filter' => true,
    'options' => [...$opts($list), ...($withOther ? [$other()] : [])],
] + $extra;
// «Komplektə daxildir — … / daxil deyil. Yalnız qiymətə daxil olanlar».
$kit = fn (string $code, array $items): array => [
    'slug' => $cs($code).'-komplekt', 'name' => $n('Komplektə daxildir', 'В комплекте', 'Included'), 'type' => 'multiselect',
    'options' => [...$opts($items), $none()],
];

// ── Shared ──────────────────────────────────────────────────────────────────

// «Material* — Polad / paslanmayan polad / alüminium / sink ərintisi / plastik / digər».
$material = ['slug' => 'kart-mf-material', 'name' => $n('Material', 'Материал', 'Material'), 'type' => 'dropdown', 'required' => true, 'filter' => true,
    'options' => [...$opts([
        ['Polad', 'polad'], ['Paslanmayan polad', 'paslanmayan-polad'], ['Alüminium', 'aluminium'], ['Sink ərintisi', 'sink-erintisi'], ['Plastik', 'plastik'],
    ]), $other()]];

// «Rəng və səth* — İki seçim: rəng kataloqdan; səth: mat / parlaq / teksturalı / digər».
$look = [
    ['slug' => 'kart-mf-reng', 'name' => $n('Rəng', 'Цвет', 'Colour'), 'type' => 'dropdown', 'required' => true, 'filter' => true, 'options' => [...$opts([
        ['Ağ', 'ag'], ['Qara', 'qara'], ['Boz', 'boz'], ['Antrasit', 'antrasit'], ['Bej', 'bej'], ['Qəhvəyi', 'qehveyi'], ['Xrom', 'xrom'], ['Nikel', 'nikel'],
        ['Qızılı', 'qizili'], ['Bürünc', 'burunc'], ['Metal rəngi', 'metal'], ['Ağac teksturası', 'agac-teksturasi'], ['Daş teksturası', 'das-teksturasi'], ['Şəffaf', 'seffaf'],
    ]), $a('RAL üzrə', ['slug' => 'ral', 'prompt' => $P_RAL]), $other($P_CODE)]],
    ['slug' => 'kart-mf-seth', 'name' => $n('Səth', 'Поверхность', 'Surface'), 'type' => 'dropdown', 'required' => true, 'options' => [
        ...$opts([['Mat', 'mat'], ['Parlaq', 'parlaq'], ['Teksturalı', 'teksturali']]), $other(),
    ]],
];

// Dimensions, mm. «Ölçülər* — Uzunluq × en × hündürlük; dairəvi hissədə diametr ayrıca».
$dim = fn (string $key, array $name, bool $required = true): array => ['slug' => 'kart-mf-'.$key, 'name' => $name, 'type' => 'decimal', 'unit' => 'mm', 'required' => $required];
$length = fn (bool $r = true): array => $dim('uzunluq', $n('Uzunluq', 'Длина', 'Length'), $r);
$width = fn (bool $r = true): array => $dim('en', $n('En', 'Ширина', 'Width'), $r);
$height = fn (bool $r = true): array => $dim('hundurluk', $n('Hündürlük', 'Высота', 'Height'), $r);
$depth = fn (bool $r = true): array => $dim('derinlik', $n('Dərinlik', 'Глубина', 'Depth'), $r);
$thick = fn (bool $r = true): array => $dim('qalinliq', $n('Qalınlıq', 'Толщина', 'Thickness'), $r);
$diameter = fn (bool $r = false): array => $dim('diametr', $n('Diametr', 'Диаметр', 'Diameter'), $r);
$dims = [
    $length(), $width(), $height(),
    $diameter() + $hint('Dairəvi hissə (düymə, rolik) üçün; uzunluq və en sahələrinə xarici ölçü yazılır.', 'Для круглой части (кнопка, ролик); в длину и ширину — наружный размер.', 'For a round part (knob, roller); length and width take the outer size.'),
];

// «Uyğun fasad qalınlığı — 2 rəqəm, minimum–maksimum mm».
$facade = [
    ['slug' => 'kart-mf-fasad-qalinligi', 'name' => $n('Uyğun fasad qalınlığı, minimum', 'Толщина фасада, минимум', 'Front thickness, minimum'), 'type' => 'decimal', 'unit' => 'mm',
        'rules' => ['lte' => 'kart-mf-fasad-qalinligi-maks']]
        + $hint('Tək qiymətdə yalnız bu sahə.', 'При одном значении — только это поле.', 'For one value fill only this.'),
    ['slug' => 'kart-mf-fasad-qalinligi-maks', 'name' => $n('Uyğun fasad qalınlığı, maksimum', 'Толщина фасада, максимум', 'Front thickness, maximum'), 'type' => 'decimal', 'unit' => 'mm'],
];

// «Uyğun model və ya sistem* — Brend + seriya/model və ya istehsalçının uyğunluq kodu».
$model = ['slug' => 'kart-mf-uygun-model', 'name' => $n('Uyğun model və ya sistem', 'Совместимая модель или система', 'Compatible model or system'), 'type' => 'text', 'required' => true];
// «Texniki məlumat — Fayl və ya keçid: ölçü sxemi və ya istehsalçı təlimatı».
$tech = ['slug' => 'kart-mf-texniki-melumat', 'name' => $n('Texniki məlumat', 'Техническая информация', 'Technical data'), 'type' => 'document'];

// «Yük həddi — Rəqəm və seçim: kg; bir ədəd, cüt və ya dəst üçün olduğu ayrıca göstərilir».
$loadScope = $when('kart-mf-yuk-aiddir', 'hisse', 'cut', 'dest', 'tam');
$load = [
    ['slug' => 'kart-mf-yuk-aiddir', 'name' => $n('Yük həddi nəyə aiddir', 'Нагрузка указана на', 'Load limit applies to'), 'type' => 'dropdown', 'options' => [
        ...$opts([['Bir hissəyə', 'hisse'], ['Cütə', 'cut'], ['Dəstə', 'dest'], ['Tam məhsula', 'tam']]), $ns,
    ]] + $hint('Yük həddi yalnız aid olduğu vahidlə yazılır; istehsalçı göstərməyibsə «Göstərilməyib».', 'Нагрузка пишется только с тем, к чему относится; если производитель не указал — «Не указано».', 'A load limit is only given with what it applies to; if the maker gives none, «Not specified».'),
    ['slug' => 'kart-mf-yuk-heddi', 'name' => $n('Yük həddi', 'Допустимая нагрузка', 'Load limit'), 'type' => 'decimal', 'unit' => 'kg', 'required' => true, 'when' => $loadScope]
        + $hint('İstehsalçının məlumatına görə.', 'По данным производителя.', 'Per the maker’s data.'),
];
// F03: «Yük göstəricisinin çəkməcənin öz çəkisini daxil edib-etmədiyi … göstərilir».
$ownWeight = ['slug' => 'kart-mf-yuk-oz-cekisi', 'name' => $n('Çəkməcənin öz çəkisi yükə daxildir', 'Вес ящика входит в нагрузку', 'Drawer’s own weight included in the load'),
    'type' => 'dropdown', 'when' => $loadScope, 'options' => [...$opts([['Daxildir', 'daxildir'], ['Daxil deyil', 'daxil-deyil']]), $ns]];

// «Hərəkət funksiyası — Standart / yumşaq bağlanma / basmaqla açılma / hər ikisi».
$motion = ['slug' => 'kart-mf-hereket-funksiyasi', 'name' => $n('Hərəkət funksiyası', 'Функция движения', 'Motion function'), 'type' => 'dropdown', 'options' => $opts([
    ['Standart', 'standart'], ['Yumşaq bağlanma', 'yumsaq-baglanma'], ['Basmaqla açılma', 'basmaqla-acilma'], ['Hər ikisi', 'her-ikisi'],
])] + $hint('Yalnız uyğun komplektdə olan funksiya.', 'Только функция, входящая в комплект.', 'Only a function the set actually provides.');

// «Yumşaq bağlanma — Var / Yoxdur / Tətbiq edilmir».
$softClose = ['slug' => 'kart-mf-yumsaq-baglanma', 'name' => $n('Yumşaq bağlanma', 'Плавное закрывание', 'Soft close'), 'type' => 'dropdown', 'options' => $opts([
    ['Var', 'var'], ['Yoxdur', 'yoxdur'], ['Tətbiq edilmir', 'tetbiq-edilmir'],
])];

// «Açılma bucağı — Rəqəm, dərəcə».
$angle = ['slug' => 'kart-mf-acilma-bucagi', 'name' => $n('Açılma bucağı', 'Угол открывания', 'Opening angle'), 'type' => 'decimal', 'unit' => '°', 'rules' => ['max' => 360]];

// «Tərəf — Sol / sağ / universal».
$side = ['slug' => 'kart-mf-teref', 'name' => $n('Tərəf', 'Сторона', 'Side'), 'type' => 'dropdown', 'options' => $opts([['Sol', 'sol'], ['Sağ', 'sag'], ['Universal', 'universal']])];

// «Uyğun dolab ölçüsü* — Nominal xarici en və tələb olunan minimum daxili en × dərinlik × hündürlük, mm».
$innerWidth = ['slug' => 'kart-mf-daxili-en', 'name' => $n('Minimum daxili en', 'Минимальная внутренняя ширина', 'Minimum inner width'), 'type' => 'decimal', 'unit' => 'mm', 'required' => true];
$innerDepth = ['slug' => 'kart-mf-daxili-derinlik', 'name' => $n('Minimum daxili dərinlik', 'Минимальная внутренняя глубина', 'Minimum inner depth'), 'type' => 'decimal', 'unit' => 'mm', 'required' => true];
$innerHeight = ['slug' => 'kart-mf-daxili-hundurluk', 'name' => $n('Minimum daxili hündürlük', 'Минимальная внутренняя высота', 'Minimum inner height'), 'type' => 'decimal', 'unit' => 'mm', 'required' => true];
$cabinet = [
    ['slug' => 'kart-mf-dolab-eni', 'name' => $n('Dolabın nominal eni', 'Номинальная ширина шкафа', 'Nominal cabinet width'), 'type' => 'decimal', 'unit' => 'mm', 'required' => true, 'filter' => true]
        + $hint('Dolabın nominal xarici eni, məs. 300, 450, 600. Daxili montaj ölçüsü ayrıca yazılır.', 'Номинальная наружная ширина шкафа, напр. 300, 450, 600. Внутренний монтажный размер — отдельно.', 'The cabinet’s nominal outer width, e.g. 300, 450, 600. The inner mounting size is separate.'),
    $innerWidth + ['rules' => ['lte' => 'kart-mf-dolab-eni']]
        + $hint('Məhsulun quraşdırılması üçün tələb olunan minimum daxili ölçü.', 'Минимальный внутренний размер для установки.', 'The minimum inner size needed to install it.'),
    $innerDepth,
    $innerHeight,
];

// «Çatdırılma və montaj — hər biri ayrıca: qiymətə daxildir / ayrıca ödəniş / təklif edilmir.
// Fərdi sifarişdə ölçmə də ayrıca».
$serviceOpts = $opts([['Qiymətə daxildir', 'daxildir'], ['Ayrıca ödənilir', 'ayrica'], ['Təklif edilmir', 'teklif-edilmir']]);
$delivery = ['slug' => 'kart-mf-catdirilma', 'name' => $n('Çatdırılma', 'Доставка', 'Delivery'), 'type' => 'dropdown', 'options' => $serviceOpts];
$install = ['slug' => 'kart-mf-montaj-xidmeti', 'name' => $n('Montaj', 'Монтаж', 'Installation'), 'type' => 'dropdown', 'options' => $serviceOpts];
$service = [$delivery, $install];
$measure = ['slug' => 'kart-mf-olcme', 'name' => $n('Ölçmə', 'Замер', 'Measuring'), 'type' => 'dropdown', 'options' => $serviceOpts,
    'when' => $when('kart-mf-hazirlanma', 'sifaris')];

// «Hazırlanma* — Standart ölçü / ölçüyə sifariş» (fronts, worktops, shelves).
$made = ['slug' => 'kart-mf-hazirlanma', 'name' => $n('Hazırlanma', 'Изготовление', 'Manufacture'), 'type' => 'dropdown', 'required' => true, 'filter' => true,
    'options' => $opts([['Standart ölçü', 'standart'], ['Ölçüyə sifariş', 'sifaris']])];
$madeToOrder = ['on_order_when' => $when('kart-mf-hazirlanma', 'sifaris')];

// «Bərkitmə ölçüsü — Yiv kodu və ya lövhə / deşik ölçüləri» (legs, castors).
$fixSize = ['slug' => 'kart-mf-berkitme-olcusu', 'name' => $n('Bərkitmə ölçüsü', 'Размер крепления', 'Fixing size'), 'type' => 'text']
    + $hint('Yiv kodu (məs. M8, M10) və ya lövhənin ölçüsü və deşik aralığı, mm.', 'Резьба (напр. M8, M10) или размер пластины и межцентровое расстояние отверстий, мм.', 'Thread (e.g. M8, M10) or plate size and hole spacing, mm.');

// «Uyğun material» and «Uyğun panel qalınlığı» (fasteners, shelf supports).
$forMaterial = ['slug' => 'kart-mf-uygun-material', 'name' => $n('Uyğun material', 'Подходящий материал', 'Suitable material'), 'type' => 'dropdown', 'filter' => true,
    'options' => [...$opts([['LDSP / MDF', 'ldsp-mdf'], ['Ağac', 'agac'], ['Metal', 'metal'], ['Şüşə', 'suse']]), $other()]];
$panel = [
    ['slug' => 'kart-mf-panel-qalinligi', 'name' => $n('Uyğun panel qalınlığı, minimum', 'Толщина панели, минимум', 'Panel thickness, minimum'), 'type' => 'decimal', 'unit' => 'mm',
        'rules' => ['lte' => 'kart-mf-panel-qalinligi-maks']]
        + $hint('Tək qiymətdə yalnız bu sahə.', 'При одном значении — только это поле.', 'For one value fill only this.'),
    ['slug' => 'kart-mf-panel-qalinligi-maks', 'name' => $n('Uyğun panel qalınlığı, maksimum', 'Толщина панели, максимум', 'Panel thickness, maximum'), 'type' => 'decimal', 'unit' => 'mm'],
];

// «Qüvvə* — N; kg ilə əvəz edilmir» (gas struts; bed lifts with a gas strut).
$force = ['slug' => 'kart-mf-quvve', 'name' => $n('Qüvvə', 'Усилие', 'Force'), 'type' => 'decimal', 'unit' => 'N']
    + $hint('Nyutonla; kq ilə əvəz edilmir.', 'В ньютонах; не заменяется килограммами.', 'In newtons; not to be replaced by kg.');

// Default hints of the shared fields, added in the final pass where a class gave none.
$defaultHints = [
    'kart-mf-uygun-model' => $n('Brend + seriya / model və ya istehsalçının uyğunluq kodu. Universal olduqda ölçü və uyğunluq şərtləri.', 'Бренд + серия / модель или код совместимости производителя. Для универсальных — размеры и условия.', 'Brand + series / model or the maker’s compatibility code. For universal parts, sizes and conditions.'),
    'kart-mf-texniki-melumat' => $n('Ölçü və montaj sxemi və ya istehsalçının təlimatı. Fayl və ya link.', 'Схема размеров и монтажа или инструкция производителя. Файл или ссылка.', 'Dimension and mounting drawing or the maker’s instructions. File or link.'),
    'komplekt' => $n('Yalnız qiymətə daxil olanlar; dəstdə hər hissənin adı və sayı.', 'Только то, что входит в цену; в наборе — название и количество каждой части.', 'Only what the price includes; for a set, each part and its count.'),
];

$ps = ['piece', 'set'];

$data = [
    'section' => 'mebel-komplektlesdiriciler-furnitura-ve-mexanizmler',

    'groups' => [
        // ════ 1. Mebel tutacaqları (F01_1, F01_2) ═══════════════════════════
        [
            'slug' => 'mebel-tutacaqlari',
            'sort_order' => 15,
            'name' => $n('Mebel tutacaqları', 'Мебельные ручки', 'Furniture handles'),
            'classes' => [
                'mebel-tutacaqlari' => $card('F01_1', $n('Mebel tutacaqları', 'Мебельные ручки', 'Furniture handles'), $ps, [
                    $type('F01_1', [['Skob', 'skob'], ['Düymə', 'duyme'], ['Oyuğa quraşdırılan', 'oyuga'], ['Kənar tutacaq', 'kenar']])
                        + $hint('Fasad boyu uzanan profil «Profil tutacaqları»ndadır.', 'Профиль во всю длину фасада — в «Профильных ручках».', 'A full-length profile goes under «Profile handles».'),
                    $material,
                    ...$dims,
                    $f('F01_1', 'deliklerarasi', $n('Bərkitmə dəlikləri arası məsafə', 'Межцентровое расстояние', 'Hole centre distance'), 'decimal', ['unit' => 'mm', 'filter' => true]
                        + $hint('İki və daha çox bərkitmə nöqtəsi olduqda, məs. 96, 128, 160.', 'При двух и более точках крепления, напр. 96, 128, 160.', 'With two or more fixing points, e.g. 96, 128, 160.')),
                    ...$look,
                    $f('F01_1', 'montaj', $n('Montaj', 'Монтаж', 'Mounting'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Vintlə', 'vintle'], ['Oyuğa', 'oyuga'], ['Kənara', 'kenara'],
                    ])]),
                    ...$facade,
                    $kit('F01_1', [['Vintlər', 'vintler'], ['Araqat', 'araqat']]),
                    ...$service,
                ]),
                'profil-tutacaqlari' => $card('F01_2', $n('Profil tutacaqları', 'Профильные ручки', 'Profile handles'), ['lm', 'piece', 'set'], [
                    $f('F01_2', 'profil', $n('Profil', 'Профиль', 'Profile'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Gola', 'gola'], ['Kənar', 'kenar'], ['Oyuğa', 'oyuga'],
                    ]), $other()]]),
                    $material,
                    $length() + $hint('Ədədlə satılan profilin uzunluğu; metrlə satılırsa çubuğun uzunluğu.', 'Длина профиля при продаже поштучно; при продаже метрами — длина хлыста.', 'Length of a profile sold by the piece; if sold by the metre, the bar length.'),
                    $f('F01_2', 'kesik', $n('Kəsik ölçüsü (en × hündürlük)', 'Сечение (ширина × высота)', 'Cross-section (width × height)'), 'size', ['unit' => 'mm', 'required' => true]
                        + $hint('Profilin sxemi «Texniki məlumat» sahəsində.', 'Схема профиля — в поле «Техническая информация».', 'The profile drawing goes in «Technical data».')),
                    ...$look,
                    ...$facade,
                    $model,
                    $kit('F01_2', [['Qapaqlar', 'qapaqlar'], ['Künclər', 'kuncler'], ['Bərkidicilər', 'berkidiciler']]),
                    $tech,
                    ...$service,
                ]),
            ],
        ],

        // ════ 2. Mebel menteşələri (F02_1) ══════════════════════════════════
        [
            'slug' => 'mebel-menteseleri',
            'sort_order' => 16,
            'name' => $n('Mebel menteşələri', 'Мебельные петли', 'Furniture hinges'),
            'classes' => [
                'mebel-menteseleri' => $card('F02_1', $n('Mebel menteşələri', 'Мебельные петли', 'Furniture hinges'), $ps, [
                    $f('F02_1', 'yerlesme', $n('Fasad yerləşməsi', 'Положение фасада', 'Front overlay'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Tam örtən', 'tam-orten'], ['Yarım örtən', 'yarim-orten'], ['İçəridə yerləşən', 'iceride'], ['Xüsusi bucaq', 'xususi-bucaq'],
                    ])]),
                    $f('F02_1', 'fasad-materiali', $n('Fasad materialı', 'Материал фасада', 'Front material'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Taxta və plitə', 'taxta-plite'], ['Şüşə', 'suse'], ['Alüminium çərçivə', 'aluminium-cercive'],
                    ]), $other()]]),
                    $angle + ['required' => true, 'filter' => true]
                        + $hint('Fasadın açılma bucağı, məs. 110°. Dolabın künc bucağı fərqli parametrdir.', 'Угол открывания фасада, напр. 110°. Угол углового шкафа — другой параметр.', 'The front’s opening angle, e.g. 110°. The cabinet’s corner angle is a different parameter.'),
                    $mm('F02_1', 'fincan-diametri', $n('Fincan diametri', 'Диаметр чашки', 'Cup diameter'))
                        + $hint('Fincanlı menteşədə, məs. 35.', 'Для чашечной петли, напр. 35.', 'For a cup hinge, e.g. 35.'),
                    $mm('F02_1', 'fincan-derinliyi', $n('Fincan dərinliyi', 'Глубина чашки', 'Cup depth')),
                    ...$facade,
                    $softClose + ['filter' => true],
                    $f('F02_1', 'montaj', $n('Montaj', 'Монтаж', 'Mounting'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Vintli', 'vintli'], ['Basılaraq', 'basilaraq'], ['Klipsli', 'klipsli'],
                    ])]),
                    $f('F02_1', 'lovhe', $n('Montaj lövhəsi', 'Монтажная планка', 'Mounting plate'), 'dropdown', ['options' => $opts([
                        ['Daxildir', 'daxildir'], ['Ayrıca alınır', 'ayrica'],
                    ])]),
                    $f('F02_1', 'lovhe-kodu', $n('Montaj lövhəsinin kodu', 'Код монтажной планки', 'Mounting plate code'), 'text', ['required' => true, 'when' => $when('kart-f02-1-lovhe', 'ayrica')]),
                    $mm('F02_1', 'lovhe-hundurlugu', $n('Montaj lövhəsinin hündürlüyü', 'Высота монтажной планки', 'Mounting plate height'), ['when' => $when('kart-f02-1-lovhe', 'ayrica')]),
                    ...$look,
                    $model,
                    $tech + $hint('Ölçü sxemi və ya istehsalçı təlimatı. Menteşə sayı fasadın ölçüsü və çəkisinə görə seçilir.', 'Схема размеров или инструкция. Число петель подбирается по размеру и весу фасада.', 'Dimension drawing or instructions. The hinge count follows the front’s size and weight.'),
                    ...$service,
                ]),
            ],
        ],

        // ════ 3. Çəkməcə relsləri və sistemləri (F03_1, F03_2) ═══════════════
        [
            'slug' => 'cekmece-relsleri-ve-sistemleri',
            'sort_order' => 17,
            'name' => $n('Çəkməcə relsləri və sistemləri', 'Направляющие и системы ящиков', 'Drawer runners and systems'),
            'classes' => [
                'cekmece-relsleri' => $card('F03_1', $n('Çəkməcə relsləri', 'Направляющие для ящиков', 'Drawer runners'), ['pair', 'set', 'piece'], [
                    $type('F03_1', [['Rolikli', 'rolikli'], ['Kürəcikli', 'kurecikli'], ['Gizli', 'gizli']]),
                    $f('F03_1', 'satis', $n('Satış forması', 'Форма продажи', 'Sold as'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Cüt (sol və sağ)', 'cut'], ['Dəst', 'dest'], ['Tək rels', 'tek'],
                    ])] + $hint('Satış vahidini təyin edir: cüt, dəst və ya ədəd.', 'Определяет единицу продажи: пара, комплект или штука.', 'Sets the sales unit: pair, set or piece.')),
                    $side + ['required' => true, 'when' => $when('kart-f03-1-satis', 'tek')],
                    ['slug' => 'kart-mf-nominal-uzunluq', 'name' => $n('Nominal uzunluq', 'Номинальная длина', 'Nominal length'), 'type' => 'decimal', 'unit' => 'mm',
                        'required' => true, 'filter' => true],
                    $f('F03_1', 'acilma', $n('Açılma', 'Выдвижение', 'Extension'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Qismən', 'qismen'], ['Tam', 'tam'], ['Artıq çıxışlı', 'artiq'],
                    ])]),
                    ['required' => false] + $innerDepth
                        + $hint('Minimum korpus dərinliyi: bu rels üçün tələb olunan daxili dərinlik.', 'Минимальная глубина корпуса для этой направляющей.', 'Minimum carcass depth for this runner.'),
                    ...$load,
                    $ownWeight,
                    $motion + ['filter' => true],
                    $f('F03_1', 'montaj-yeri', $n('Montaj yeri', 'Место установки', 'Mounting position'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Yan', 'yan'], ['Alt', 'alt'],
                    ]), $other()]]),
                    ['slug' => 'kart-mf-yan-divar', 'name' => $n('Yan divar qalınlığı', 'Толщина боковой стенки', 'Side wall thickness'), 'type' => 'decimal', 'unit' => 'mm']
                        + $hint('Uyğun çəkməcənin yan divarı.', 'Боковина подходящего ящика.', 'The side of a matching drawer.'),
                    $mm('F03_1', 'montaj-boslugu', $n('Montaj boşluğu', 'Монтажный зазор', 'Mounting clearance'))
                        + $hint('Çəkməcə ilə korpus arasında hər tərəfdən.', 'Между ящиком и корпусом с каждой стороны.', 'Between drawer and carcass on each side.'),
                    $model,
                    $kit('F03_1', [['Sol-sağ rels', 'sol-sag-rels'], ['Kilidlər', 'kilidler'], ['Sinxronlaşdırıcı', 'sinxronlasdirici']]),
                    $tech,
                    ...$service,
                ], ['unit_when' => [
                    'pair' => $when('kart-f03-1-satis', 'cut'),
                    'set' => $when('kart-f03-1-satis', 'dest'),
                    'piece' => $when('kart-f03-1-satis', 'tek'),
                ]]),
                'cekmece-sistemleri' => $card('F03_2', $n('Çəkməcə sistemləri', 'Системы ящиков', 'Drawer systems'), ['set'], [
                    ['slug' => 'kart-mf-nominal-uzunluq', 'name' => $n('Nominal uzunluq', 'Номинальная длина', 'Nominal length'), 'type' => 'decimal', 'unit' => 'mm',
                        'required' => true, 'filter' => true],
                    $mm('F03_2', 'yan-hundurluk', $n('Yan divar hündürlüyü', 'Высота боковины', 'Side height'), ['required' => true, 'filter' => true]),
                    $f('F03_2', 'qurulus', $n('Quruluş', 'Конструкция', 'Construction'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Xarici fasadlı', 'xarici-fasadli'], ['Daxili çəkməcə', 'daxili'],
                    ])]),
                    $f('F03_2', 'acilma', $n('Açılma', 'Выдвижение', 'Extension'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Qismən', 'qismen'], ['Tam', 'tam'],
                    ])]),
                    ...$load,
                    $ownWeight,
                    $motion + ['filter' => true],
                    ...$look,
                    $mm('F03_2', 'korpus-eni', $n('Uyğun korpus eni, minimum', 'Ширина корпуса, минимум', 'Carcass width, minimum'), ['rules' => ['lte' => 'kart-f03-2-korpus-eni-maks']]),
                    $mm('F03_2', 'korpus-eni-maks', $n('Uyğun korpus eni, maksimum', 'Ширина корпуса, максимум', 'Carcass width, maximum')),
                    ['slug' => 'kart-mf-yan-divar', 'name' => $n('Yan divar qalınlığı', 'Толщина боковой стенки', 'Side wall thickness'), 'type' => 'decimal', 'unit' => 'mm']
                        + $hint('Korpusun yan divarı.', 'Боковая стенка корпуса.', 'The carcass side wall.'),
                    $kit('F03_2', [
                        ['Relslər', 'relsler'], ['Yanlar', 'yanlar'], ['Ön bərkidici', 'on-berkidici'], ['Arxa bərkidici', 'arxa-berkidici'], ['Reylinq', 'reylinq'],
                        ['Altlıq', 'altliq'], ['Arxa panel', 'arxa-panel'], ['Fasad', 'fasad'],
                    ]) + $hint('Yalnız qiymətə daxil olanlar. Altlıq, arxa panel və fasad çox vaxt ayrıca hazırlanır.', 'Только то, что входит в цену. Дно, задняя стенка и фасад часто изготавливаются отдельно.', 'Only what the price includes. Bottom, back panel and front are often made separately.'),
                    $model,
                    $tech,
                    ...$service,
                ]),
            ],
        ],

        // ════ 4. Qaldırıcı mexanizmlər (F04_1, F04_2) ═══════════════════════
        [
            'slug' => 'qaldirici-mexanizmler',
            'sort_order' => 18,
            'name' => $n('Qaldırıcı mexanizmlər', 'Подъёмные механизмы', 'Lift mechanisms'),
            'classes' => [
                'fasad-qaldirici-mexanizmleri' => $card('F04_1', $n('Fasad qaldırıcı mexanizmləri', 'Подъёмные механизмы фасадов', 'Flap lift mechanisms'), ['set', 'piece'], [
                    $f('F04_1', 'hereket', $n('Hərəkət', 'Тип движения', 'Movement'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Yuxarı qalxan', 'yuxari'], ['İkiqat qatlanan', 'ikiqat'], ['Paralel qalxan', 'paralel'], ['Dönərək qalxan', 'donerek'], ['Aşağı açılan', 'asagi'],
                    ])]),
                    $mm('F04_1', 'fasad-hundurluyu', $n('Fasadın hündürlüyü, minimum', 'Высота фасада, минимум', 'Front height, minimum'), ['required' => true, 'rules' => ['lte' => 'kart-f04-1-fasad-hundurluyu-maks']]),
                    $mm('F04_1', 'fasad-hundurluyu-maks', $n('Fasadın hündürlüyü, maksimum', 'Высота фасада, максимум', 'Front height, maximum'), ['required' => true]),
                    $mm('F04_1', 'fasad-eni', $n('Fasadın eni, minimum', 'Ширина фасада, минимум', 'Front width, minimum'), ['rules' => ['lte' => 'kart-f04-1-fasad-eni-maks']]),
                    $mm('F04_1', 'fasad-eni-maks', $n('Fasadın eni, maksimum', 'Ширина фасада, максимум', 'Front width, maximum')),
                    $f('F04_1', 'secim', $n('Seçim göstəricisi', 'Параметр подбора', 'Selection basis'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Güc əmsalı', 'emsal'], ['Çəki / ölçü cədvəli', 'cedvel'],
                    ])] + $hint('Mexanizm yalnız fasadın çəkisinə görə seçilmir: ölçü, tutacağın çəkisi və sistemin cədvəli də nəzərə alınır.', 'Механизм подбирается не только по весу фасада: учитываются размер, вес ручки и таблица системы.', 'Not chosen by front weight alone: size, handle weight and the system’s table count too.')),
                    $f('F04_1', 'guc-emsali', $n('Güc əmsalı', 'Коэффициент мощности', 'Power factor'), 'text', ['required' => true, 'when' => $when('kart-f04-1-secim', 'emsal')]
                        + $hint('İstehsalçının göstəricisi və aralığı, məs. 750–1800.', 'Показатель производителя и диапазон, напр. 750–1800.', 'The maker’s figure and range, e.g. 750–1800.')),
                    $f('F04_1', 'secim-cedveli', $n('Seçim cədvəli', 'Таблица подбора', 'Selection table'), 'document', ['required' => true, 'when' => $when('kart-f04-1-secim', 'cedvel')]
                        + $hint('İstehsalçının çəki / ölçü cədvəli. Fayl və ya link.', 'Таблица веса / размеров производителя. Файл или ссылка.', 'The maker’s weight / size table. File or link.')),
                    $f('F04_1', 'idareetme', $n('İdarəetmə', 'Управление', 'Operation'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Tutacaq', 'tutacaq'], ['Basmaqla', 'basmaqla'], ['Elektrik', 'elektrik'],
                    ])]),
                    $softClose + ['filter' => true],
                    $angle + $hint('Modelə tətbiq edilirsə.', 'Если применимо к модели.', 'If it applies to the model.'),
                    $kit('F04_1', [
                        ['Sol-sağ mexanizm', 'sol-sag-mexanizm'], ['Qollar', 'qollar'], ['Bərkidicilər', 'berkidiciler'], ['Qapaqlar', 'qapaqlar'], ['Elektrik ötürücü', 'elektrik-oturucu'],
                    ]),
                    $model,
                    $tech,
                    ...$service,
                ]),
                'mebel-qaz-amortizatorlari' => $card('F04_2', $n('Mebel qaz amortizatorları', 'Мебельные газлифты', 'Furniture gas struts'), $ps, [
                    $hint('Nyutonla, kq ilə əvəz edilmir. Qapının çəkisi, ölçüsü, montaj nöqtələri və amortizator sayına görə seçilir.', 'В ньютонах, не в кг. Подбирается по весу и размеру двери, точкам крепления и числу газлифтов.', 'In newtons, not kg. Chosen by door weight and size, fixing points and number of struts.')
                        + $force + ['required' => true, 'filter' => true],
                    $mm('F04_2', 'aciq-uzunluq', $n('Açıq uzunluq', 'Длина в открытом положении', 'Extended length'), ['required' => true, 'filter' => true]
                        + $hint('Bərkitmə mərkəzləri arasında.', 'Между центрами крепления.', 'Between fixing centres.')),
                    $mm('F04_2', 'gedis', $n('Gediş', 'Ход', 'Stroke')),
                    $f('F04_2', 'hereket', $n('Hərəkət', 'Тип движения', 'Movement'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Qaldırma', 'qaldirma'], ['Aşağı açılmanı dəstəkləmə', 'asagi'],
                    ]), $other()]]),
                    $f('F04_2', 'ucluq', $n('Ucluq və bərkitmə', 'Наконечник и крепление', 'End fitting and mounting'), 'text', ['required' => true]
                        + $hint('İstehsalçının ucluq tipi və montaj ölçüsü.', 'Тип наконечника производителя и монтажный размер.', 'The maker’s end-fitting type and mounting size.')),
                    $model,
                    $kit('F04_2', [['Kronşteynlər', 'kronsteynler'], ['Vintlər', 'vintler']]),
                    $tech,
                    ...$service,
                ]),
            ],
        ],

        // ════ 5. Sürüşmə və qatlanan qapı sistemləri (F05_1, F05_2) ══════════
        [
            'slug' => 'surusme-ve-qatlanan-qapi-sistemleri',
            'sort_order' => 19,
            'name' => $n('Sürüşmə və qatlanan qapı sistemləri', 'Раздвижные и складные системы дверей', 'Sliding and folding door systems'),
            'classes' => [
                'surusme-ve-qatlanan-mebel-qapi-mexanizmleri' => $card('F05_1', $n('Sürüşmə və qatlanan mebel qapı mexanizmləri', 'Механизмы раздвижных и складных мебельных дверей', 'Sliding and folding furniture door mechanisms'), ['set'], [
                    $f('F05_1', 'sistem', $n('Sistem', 'Система', 'System'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Sürüşmə', 'surusme'], ['Qatlanan', 'qatlanan'], ['Korpus daxilinə yığılan', 'korpus-daxili'],
                    ])]),
                    $f('F05_1', 'dasima', $n('Daşıma', 'Несущая схема', 'Load bearing'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Alt dayaq', 'alt-dayaq'], ['Üst asqı', 'ust-asqi'],
                    ]), $other()]]),
                    $count('F05_1', 'qapi-sayi', $n('Qapı sayı', 'Количество дверей', 'Number of doors'), ['required' => true, 'rules' => ['integer' => true, 'min' => 1]]),
                    $mm('F05_1', 'qapi-eni', $n('Bir qapının eni, minimum', 'Ширина двери, минимум', 'Door width, minimum'), ['required' => true, 'rules' => ['lte' => 'kart-f05-1-qapi-eni-maks']]),
                    $mm('F05_1', 'qapi-eni-maks', $n('Bir qapının eni, maksimum', 'Ширина двери, максимум', 'Door width, maximum'), ['required' => true]),
                    $mm('F05_1', 'qapi-hundurluyu', $n('Bir qapının hündürlüyü, minimum', 'Высота двери, минимум', 'Door height, minimum'), ['required' => true, 'rules' => ['lte' => 'kart-f05-1-qapi-hundurluyu-maks']]),
                    $mm('F05_1', 'qapi-hundurluyu-maks', $n('Bir qapının hündürlüyü, maksimum', 'Высота двери, максимум', 'Door height, maximum'), ['required' => true]),
                    ...$facade,
                    $f('F05_1', 'qapi-yuku', $n('Bir qapının yük həddi', 'Нагрузка на одну дверь', 'Load per door'), 'decimal', ['unit' => 'kg', 'filter' => true]
                        + $hint('Bir qapıya, istehsalçının məlumatına görə.', 'На одну дверь, по данным производителя.', 'Per door, per the maker’s data.')),
                    $f('F05_1', 'qapi-materiali', $n('Qapı materialı', 'Материал двери', 'Door material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Plitə', 'plite'], ['Şüşə', 'suse'], ['Çərçivəli', 'cerciveli'],
                    ]), $other()]]),
                    $softClose,
                    $kit('F05_1', [
                        ['Arabacıqlar', 'arabaciqlar'], ['Relslər', 'relsler'], ['Bərkidicilər', 'berkidiciler'], ['Stoporlar', 'stoporlar'], ['Dovodçiklər', 'dovodciklar'],
                    ]),
                    $model,
                    $tech,
                    ...$service,
                ]),
                'mebel-qapisi-relsleri-ve-rolikleri' => $card('F05_2', $n('Mebel qapısı relsləri və rolikləri', 'Направляющие и ролики мебельных дверей', 'Furniture door tracks and rollers'), ['piece', 'lm', 'pair', 'set'], [
                    $f('F05_2', 'hisse', $n('Hissə', 'Деталь', 'Part'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Üst rels', 'ust-rels'], ['Alt rels', 'alt-rels'], ['Rolik', 'rolik'], ['Arabacıq', 'arabaciq'], ['Yönləndirici', 'yonlendirici'], ['Stopor', 'stopor'],
                        ['Qatlanma hissəsi', 'qatlanma'],
                    ])] + $hint('Metrlə yalnız relslər satılır.', 'Метрами продаются только направляющие.', 'Only tracks are sold by the metre.')),
                    $material,
                    ...$dims,
                    $f('F05_2', 'yerlesme', $n('Yerləşmə', 'Расположение', 'Position'), 'dropdown', ['filter' => true, 'options' => $opts([
                        ['Üst', 'ust'], ['Alt', 'alt'], ['Sol', 'sol'], ['Sağ', 'sag'], ['Universal', 'universal'],
                    ])]),
                    $model,
                    $kit('F05_2', [['Bərkidicilər', 'berkidiciler'], ['Sonluqlar', 'sonluqlar']]),
                    $tech,
                    ...$service,
                ], ['unit_when' => ['lm' => $when('kart-f05-2-hisse', 'ust-rels', 'alt-rels')]]),
            ],
        ],

        // ════ 6. Yumşaq bağlanma, itələyici və kilid mexanizmləri (F06_1, F06_2)
        [
            'slug' => 'yumsaq-baglanma-iteleyici-ve-kilid-mexanizmleri',
            'sort_order' => 20,
            'name' => $n('Yumşaq bağlanma, itələyici və kilid mexanizmləri', 'Доводчики, толкатели и замки', 'Soft-close, push-to-open and lock mechanisms'),
            'classes' => [
                'yumsaq-baglayicilar-ve-iteleyiciler' => $card('F06_1', $n('Yumşaq bağlayıcılar və itələyicilər', 'Доводчики и толкатели', 'Soft-close dampers and push-to-open devices'), $ps, [
                    $type('F06_1', [
                        ['Yumşaq bağlayıcı', 'yumsaq-baglayici'], ['Basmaqla açılan itələyici', 'iteleyici'], ['Maqnit tutucu', 'maqnit'], ['Qapı məhdudlaşdırıcısı', 'mehdudlasdirici'],
                    ]),
                    $f('F06_1', 'tetbiq', $n('Tətbiq', 'Применение', 'Application'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Qapı', 'qapi'], ['Çəkməcə', 'cekmece'], ['Sürüşən fasad', 'surusen-fasad'],
                    ]), $other()]]),
                    $f('F06_1', 'montaj', $n('Montaj', 'Монтаж', 'Mounting'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Səthə', 'sethe'], ['Oyuğa', 'oyuga'], ['Sistemə taxılan', 'sisteme'],
                    ])]),
                    ...$dims,
                    $f('F06_1', 'uygun-mexanizm', $n('Uyğun mexanizm', 'Совместимый механизм', 'Compatible mechanism'), 'dropdown', ['options' => $opts([
                        ['Yaylı menteşə', 'yayli-mentese'], ['Yaysız menteşə', 'yaysiz-mentese'], ['Konkret rels', 'rels'], ['Universal', 'universal'],
                    ])] + $hint('Model təlimatına görə; uyğunluq kodu «Uyğun model və ya sistem» sahəsində.', 'По инструкции модели; код совместимости — в «Совместимой модели или системе».', 'Per the model’s instructions; the compatibility code goes in «Compatible model or system».')),
                    $mm('F06_1', 'gedis', $n('İtələmə gedişi', 'Ход толкателя', 'Push stroke'), ['when' => $when('kart-f06-1-tip', 'iteleyici')]),
                    $mm('F06_1', 'bosluq', $n('Tələb olunan fasad boşluğu', 'Требуемый зазор фасада', 'Required front gap'), ['when' => $when('kart-f06-1-tip', 'iteleyici')]),
                    $model,
                    $kit('F06_1', [['Qarşılıq', 'qarsiliq'], ['Adapter', 'adapter'], ['Vintlər', 'vintler']]),
                    $tech,
                    ...$service,
                ]),
                'mebel-kilidleri' => $card('F06_2', $n('Mebel kilidləri', 'Мебельные замки', 'Furniture locks'), $ps, [
                    $type('F06_2', [['Açarlı', 'acarli'], ['Kodlu', 'kodlu'], ['Elektron', 'elektron'], ['Mərkəzi kilid', 'merkezi']]),
                    $f('F06_2', 'tetbiq', $n('Tətbiq', 'Применение', 'Application'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Şkaf', 'skaf'], ['Çəkməcə', 'cekmece'], ['Şüşə qapı', 'suse-qapi'], ['Fərdi dolab', 'ferdi-dolab'],
                    ])]),
                    $f('F06_2', 'montaj', $n('Montaj', 'Монтаж', 'Mounting'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Oyuğa', 'oyuga'], ['Səthə', 'sethe'], ['Şüşəyə sıxılma', 'suseye-sixilma'],
                    ])]),
                    ...$dims,
                    ...$facade,
                    $f('F06_2', 'idareetme', $n('İdarəetmə', 'Способ открывания', 'Access method'), 'multiselect', ['options' => [...$opts([
                        ['Açar', 'acar'], ['Mexaniki kod', 'mexaniki-kod'], ['Kart', 'kart'], ['Elektron kod', 'elektron-kod'], ['Barmaq izi', 'barmaq-izi'],
                    ]), $other()]]),
                    $f('F06_2', 'enerji', $n('Enerji təchizatı', 'Питание', 'Power supply'), 'dropdown', ['options' => $opts([
                        ['Batareya', 'batareya'], ['Şəbəkə', 'sebeke'], ['Tələb olunmur', 'teleb-olunmur'],
                    ])]),
                    ...$look,
                    $kit('F06_2', [['Açarlar', 'acarlar'], ['Qarşılıq', 'qarsiliq'], ['Batareya', 'batareya'], ['Bərkidicilər', 'berkidiciler']]),
                    $tech,
                    ...$service,
                ]),
            ],
        ],

        // ════ 7. Ayaqlar, dayaqlar və təkərlər (F07_1, F07_2) ═════════════════
        [
            'slug' => 'ayaqlar-dayaqlar-ve-tekerler',
            'sort_order' => 21,
            'name' => $n('Ayaqlar, dayaqlar və təkərlər', 'Ножки, опоры и колёса', 'Legs, supports and castors'),
            'classes' => [
                'mebel-dayaqlari-ve-berkitme-elementleri' => $card('F07_1', $n('Mebel ayaqları və dayaqları', 'Мебельные ножки и опоры', 'Furniture legs and supports'), $ps, [
                    $type('F07_1', [['Dekorativ ayaq', 'dekorativ'], ['Tənzimlənən ayaq', 'tenzimlenen'], ['Sokol ayağı', 'sokol'], ['Masa altlığı', 'masa-altligi']]),
                    $material,
                    $height() + ['filter' => true],
                    $f('F07_1', 'kesik', $n('Dayaq kəsiyi', 'Сечение опоры', 'Support cross-section'), 'text', ['required' => true]
                        + $hint('mm, məs. Ø60 və ya 40 × 40.', 'мм, напр. Ø60 или 40 × 40.', 'mm, e.g. Ø60 or 40 × 40.')),
                    $mm('F07_1', 'tenzimleme', $n('Tənzimləmə hündürlüyü, minimum', 'Регулировка высоты, минимум', 'Adjustable height, minimum'), ['rules' => ['lte' => 'kart-f07-1-tenzimleme-maks']]),
                    $mm('F07_1', 'tenzimleme-maks', $n('Tənzimləmə hündürlüyü, maksimum', 'Регулировка высоты, максимум', 'Adjustable height, maximum')),
                    $f('F07_1', 'berkitme', $n('Bərkitmə', 'Крепление', 'Fixing'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Lövhə ilə', 'lovhe'], ['Yivli ştift', 'yivli-stift'], ['Boruya taxılan', 'boruya'],
                    ]), $other()]]),
                    $fixSize,
                    ...$load,
                    ...$look,
                    $kit('F07_1', [['Bərkitmə lövhəsi', 'berkitme-lovhesi'], ['Vintlər', 'vintler'], ['Altlıq', 'altliq']]),
                    $tech,
                    ...$service,
                ]),
                'mebel-tekerleri' => $card('F07_2', $n('Mebel təkərləri', 'Мебельные колёса', 'Furniture castors'), $ps, [
                    $diameter(true) + ['filter' => true] + $hint('Təkərin diametri.', 'Диаметр колеса.', 'Wheel diameter.'),
                    $height(false) + $hint('Ümumi hündürlük.', 'Общая высота.', 'Overall height.'),
                    $type('F07_2', [['Fırlanan', 'firlanan'], ['Sabit', 'sabit']]),
                    $f('F07_2', 'eylec', $n('Əyləc', 'Тормоз', 'Brake'), 'dropdown', ['filter' => true, 'options' => [...$yesNo, $ns]]),
                    $f('F07_2', 'material', $n('Təkər materialı', 'Материал колеса', 'Wheel material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Poliuretan', 'poliuretan'], ['Rezin', 'rezin'], ['Poliamid', 'poliamid'],
                    ]), $other()]]),
                    $f('F07_2', 'berkitme', $n('Bərkitmə', 'Крепление', 'Fixing'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Lövhəli', 'lovheli'], ['Yivli', 'yivli'], ['Ştiftli', 'stiftli'],
                    ])]),
                    ['required' => true] + $fixSize,
                    $f('F07_2', 'teker-yuku', $n('Bir təkərin yük həddi', 'Нагрузка на одно колесо', 'Load per castor'), 'decimal', ['unit' => 'kg']
                        + $hint('Bir təkərə, istehsalçının məlumatı. Mebelin ümumi yükü təkərlərin yükünü toplamaqla təyin edilmir.', 'На одно колесо, по данным производителя. Общая нагрузка мебели не равна сумме нагрузок колёс.', 'Per castor, per the maker. The furniture’s total load is not the sum of the castors.')),
                    $f('F07_2', 'doseme', $n('Döşəmə uyğunluğu', 'Тип пола', 'Floor type'), 'dropdown', ['options' => $opts([
                        ['Sərt örtük', 'sert'], ['Xalça', 'xalca'], ['Hər ikisi', 'her-ikisi'],
                    ])]),
                    $tech,
                    ...$service,
                ]),
            ],
        ],

        // ════ 8. Bərkidicilər və birləşdiricilər (F08_1, F08_2) ══════════════
        [
            'slug' => 'berkidiciler-ve-birlesdiriciler',
            'sort_order' => 22,
            'name' => $n('Bərkidicilər və birləşdiricilər', 'Крепёж и соединители', 'Fasteners and connectors'),
            'classes' => [
                'mebel-berkidicileri-ve-birlesdiricileri' => $card('F08_1', $n('Mebel bərkidiciləri və birləşdiriciləri', 'Мебельный крепёж и соединители', 'Furniture fasteners and connectors'), ['piece', 'pack', 'set'], [
                    $type('F08_1', [
                        ['Konfirmat', 'konfirmat'], ['Eksantrik birləşdirici', 'eksantrik'], ['Şkant', 'skant'], ['Vint', 'vint'], ['Bucaq', 'bucaq'], ['Birləşdirici bolt', 'bolt'],
                    ], true),
                    $material,
                    $f('F08_1', 'olculer', $n('Ölçülər', 'Размеры', 'Dimensions'), 'text', ['required' => true]
                        + $hint('Tipə görə: diametr × uzunluq və ya kəsik, mm; məs. 7 × 50, 40 × 40 × 20.', 'По типу: диаметр × длина или сечение, мм; напр. 7 × 50, 40 × 40 × 20.', 'By type: diameter × length or section, mm; e.g. 7 × 50, 40 × 40 × 20.')),
                    $diameter(),
                    $length(false),
                    $forMaterial + ['required' => true],
                    ...$panel,
                    $mm('F08_1', 'deliyin-diametri', $n('Montaj dəliyinin diametri', 'Диаметр монтажного отверстия', 'Mounting hole diameter')),
                    $mm('F08_1', 'deliyin-derinliyi', $n('Montaj dəliyinin dərinliyi', 'Глубина монтажного отверстия', 'Mounting hole depth')),
                    ['slug' => 'kart-mf-paketde-say', 'name' => $n('Paketdə miqdar', 'Количество в упаковке', 'Pieces per pack'), 'type' => 'decimal', 'unit' => 'ədəd', 'rules' => ['integer' => true]]
                        + $hint('Paketlə və ya dəstlə satılanda.', 'При продаже упаковкой или набором.', 'When sold by the pack or set.'),
                    $tech,
                    ...$service,
                ]),
                'ref-tutacaqlari' => $card('F08_2', $n('Mebel asqıları və rəf tutacaqları', 'Мебельные навесы и полкодержатели', 'Cabinet hangers and shelf supports'), ['piece', 'pair', 'lm', 'set'], [
                    $type('F08_2', [
                        ['Dolab asqısı', 'dolab-asqisi'], ['Montaj relsi', 'montaj-relsi'], ['Rəf pini', 'ref-pini'], ['Gizli rəf tutacağı', 'gizli'], ['Şüşə rəf tutacağı', 'suse'],
                    ]) + $hint('Metrlə yalnız montaj relsi satılır.', 'Метрами продаётся только монтажная шина.', 'Only the mounting rail is sold by the metre.'),
                    $material,
                    ...$dims,
                    $forMaterial + $hint('Uyğun rəf və korpusun materialı.', 'Материал подходящей полки и корпуса.', 'Material of the matching shelf and carcass.'),
                    ...$panel,
                    $f('F08_2', 'montaj', $n('Montaj', 'Монтаж', 'Mounting'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Vintli', 'vintli'], ['Deşiyə', 'desiye'], ['Sıxma', 'sixma'], ['Relsə', 'relse'],
                    ])]),
                    $load[0],
                    $hint('İstehsalçının məlumatına görə. Asqının yükü bütün montajın yükü deyil; divar ankeri əsas materiala görə ayrıca seçilir.', 'По данным производителя. Нагрузка навеса — не нагрузка всего монтажа; анкер подбирается по основанию отдельно.', 'Per the maker. A hanger’s load is not the whole installation’s; the wall anchor is chosen separately by the substrate.') + $load[1],
                    $model,
                    $kit('F08_2', [['Sol-sağ asqı', 'sol-sag-asqi'], ['Rels', 'rels'], ['Qapaq', 'qapaq'], ['Vint', 'vint']]),
                    $tech,
                    ...$service,
                ], ['unit_when' => ['lm' => $when('kart-f08-2-tip', 'montaj-relsi')]]),
            ],
        ],

        // ════ 9. Mətbəx dolablarının daxili aksesuarları (F09_1 – F09_4) ═════
        [
            'slug' => 'metbex-dolablarinin-daxili-aksesuarlari',
            'sort_order' => 23,
            'name' => $n('Mətbəx dolablarının daxili aksesuarları', 'Внутренние аксессуары кухонных шкафов', 'Kitchen cabinet interior fittings'),
            'classes' => [
                'metbex-cixarilan-sebetleri-ve-butulka-refleri' => $card('F09_1', $n('Mətbəx çıxarılan səbətləri və butulka rəfləri', 'Выдвижные корзины и бутылочницы', 'Kitchen pull-out baskets and bottle racks'), $ps, [
                    $type('F09_1', [['Butulka rəfi', 'butulka-refi'], ['Səbət', 'sebet'], ['Hündür dolab sistemi', 'hundur-dolab']]),
                    ...$cabinet,
                    $count('F09_1', 'mertebe', $n('Mərtəbə sayı', 'Количество уровней', 'Number of tiers')),
                    $f('F09_1', 'berkitme', $n('Bərkitmə', 'Крепление', 'Fixing'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Alt', 'alt'], ['Yan', 'yan'], ['Fasada', 'fasada'],
                    ]), $other()]]),
                    $side,
                    $motion + ['filter' => true],
                    ...$load,
                    ...$look,
                    $kit('F09_1', [['Relslər', 'relsler'], ['Səbətlər', 'sebetler'], ['Fasad bərkidicisi', 'fasad-berkidicisi']]),
                    $model,
                    $tech,
                    ...$service,
                ]),
                'metbex-kunc-sistemleri' => $card('F09_2', $n('Mətbəx künc sistemləri', 'Угловые кухонные системы', 'Kitchen corner systems'), $ps, [
                    $type('F09_2', [['Fırlanan rəf', 'firlanan'], ['Çıxarılan rəf', 'cixarilan'], ['Səbət sistemi', 'sebet-sistemi']], true),
                    ...$cabinet,
                    $side + ['required' => true, 'filter' => true],
                    $mm('F09_2', 'qapi-acilisi', $n('Minimum qapı açılışı', 'Минимальный проём двери', 'Minimum door opening'), ['required' => true]),
                    $count('F09_2', 'ref-sayi', $n('Rəf sayı', 'Количество полок', 'Number of shelves')),
                    $motion,
                    ...$load,
                    ...$look,
                    $kit('F09_2', [['Rəflər', 'refler'], ['Relslər', 'relsler'], ['Mexanizm', 'mexanizm'], ['Fasad bərkidicisi', 'fasad-berkidicisi']]),
                    $model,
                    $tech,
                    ...$service,
                ]),
                'qab-quruduculari-ve-cekmece-teskilatcilari' => $card('F09_3', $n('Qab quruducuları və çəkməcə təşkilatçıları', 'Сушилки для посуды и органайзеры для ящиков', 'Dish racks and drawer organisers'), $ps, [
                    $type('F09_3', [
                        ['Dolab üçün qab quruducu', 'qab-quruducu'], ['Çəkməcə qab rəfi', 'cekmece-qab-refi'], ['Qaşıq-çəngəl altlığı', 'qasiq-cengel'], ['Bölücü', 'bolucu'],
                    ]),
                    ...$dims,
                    $innerWidth + ['filter' => true] + $hint('Uyğun yer: dolabın və ya çəkməcənin daxili eni.', 'Подходящее место: внутренняя ширина шкафа или ящика.', 'Fitting space: the cabinet’s or drawer’s inner width.'),
                    $innerDepth,
                    $innerHeight,
                    $f('F09_3', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Paslanmayan polad', 'paslanmayan-polad'], ['Örtüklü polad', 'ortuklu-polad'], ['Alüminium', 'aluminium'], ['Plastik', 'plastik'], ['Ağac', 'agac'],
                    ]), $other()]]),
                    $f('F09_3', 'kesile-bilir', $n('Ölçüyə kəsilə bilir', 'Можно подрезать по размеру', 'Can be cut to size'), 'dropdown', ['options' => [...$yesNo, $ns]]),
                    ...$look,
                    $kit('F09_3', [['Su altlığı', 'su-altligi'], ['Bərkidicilər', 'berkidiciler'], ['Bölücülər', 'boluculer']]),
                    $model,
                    ...$service,
                ]),
                'metbex-dolabi-ucun-tullanti-qablari' => $card('F09_4', $n('Mətbəx dolabı üçün tullantı qabları', 'Мусорные системы для кухонного шкафа', 'Kitchen cabinet waste bins'), $ps, [
                    $type('F09_4', [['Çıxarılan', 'cixarilan'], ['Qapıya bağlı', 'qapiya-bagli'], ['Korpus daxilində sabit', 'sabit']]),
                    ...$cabinet,
                    $count('F09_4', 'qab-sayi', $n('Qab sayı', 'Количество вёдер', 'Number of bins'), ['required' => true, 'rules' => ['integer' => true, 'min' => 1]]),
                    $f('F09_4', 'hecmler', $n('Qabların həcmi', 'Объём вёдер', 'Bin volumes'), 'text', ['required' => true]
                        + $hint('Hər qab ayrıca, litr: məs. 2 × 15 + 1 × 8.', 'Каждое ведро отдельно, л: напр. 2 × 15 + 1 × 8.', 'Each bin separately, litres: e.g. 2 × 15 + 1 × 8.')),
                    $f('F09_4', 'umumi-hecm', $n('Ümumi həcm', 'Общий объём', 'Total volume'), 'decimal', ['unit' => 'l', 'filter' => true]),
                    $f('F09_4', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Plastik', 'plastik'], ['Metal', 'metal'], ['Kombinə', 'kombine'],
                    ])]),
                    $motion,
                    $kit('F09_4', [['Qablar', 'qablar'], ['Qapaqlar', 'qapaqlar'], ['Relslər', 'relsler'], ['Bərkidicilər', 'berkidiciler']]),
                    $model,
                    $tech,
                    ...$service,
                ]),
            ],
        ],

        // ════ 10. Şkaf və qarderob daxili aksesuarları (F10_1, F10_2) ═════════
        [
            'slug' => 'skaf-ve-qarderob-daxili-aksesuarlari',
            'sort_order' => 24,
            'name' => $n('Şkaf və qarderob daxili aksesuarları', 'Внутренние аксессуары шкафов и гардеробных', 'Wardrobe interior fittings'),
            'classes' => [
                'qarderob-stanqlari-ve-pantoqraflari' => $card('F10_1', $n('Qarderob ştanqları və pantoqrafları', 'Гардеробные штанги и пантографы', 'Wardrobe rails and pull-down rails'), $ps, [
                    $type('F10_1', [['Sabit ştanq', 'sabit'], ['Teleskopik ştanq', 'teleskopik'], ['Pantoqraf', 'pantoqraf']]),
                    $mm('F10_1', 'en', $n('En aralığı, minimum', 'Диапазон ширины, минимум', 'Width range, minimum'), ['required' => true, 'rules' => ['lte' => 'kart-f10-1-en-maks']]),
                    $mm('F10_1', 'en-maks', $n('En aralığı, maksimum', 'Диапазон ширины, максимум', 'Width range, maximum'), ['required' => true]),
                    $f('F10_1', 'profil', $n('Profil', 'Профиль', 'Profile'), 'dropdown', ['options' => $opts([['Dairəvi', 'dairevi'], ['Oval', 'oval']])]),
                    $f('F10_1', 'kesik', $n('Kəsik ölçüsü', 'Размер сечения', 'Section size'), 'text', ['when' => $when('kart-f10-1-profil', 'dairevi', 'oval')]
                        + $hint('mm, məs. Ø25 və ya 30 × 15.', 'мм, напр. Ø25 или 30 × 15.', 'mm, e.g. Ø25 or 30 × 15.')),
                    $f('F10_1', 'montaj', $n('Montaj', 'Монтаж', 'Mounting'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Yan', 'yan'], ['Üst', 'ust'], ['Sistemə uyğun', 'sisteme'],
                    ])]),
                    $f('F10_1', 'idareetme', $n('İdarəetmə', 'Управление', 'Operation'), 'dropdown', ['options' => $opts([
                        ['Əl ilə', 'el-ile'], ['Elektrik', 'elektrik'], ['Tətbiq edilmir', 'tetbiq-edilmir'],
                    ])]),
                    ...$load,
                    ...$look,
                    $kit('F10_1', [['Tutacaqlar', 'tutacaqlar'], ['Mexanizm', 'mexanizm'], ['Bərkidicilər', 'berkidiciler']]),
                    $model,
                    $tech,
                    ...$service,
                ]),
                'qarderobun-cixarilan-daxili-aksesuarlari' => $card('F10_2', $n('Qarderobun çıxarılan daxili aksesuarları', 'Выдвижные аксессуары для гардероба', 'Wardrobe pull-out accessories'), $ps, [
                    $type('F10_2', [['Şalvar asılqanı', 'salvar-asilqani'], ['Səbət', 'sebet'], ['Ayaqqabı rəfi', 'ayaqqabi-refi'], ['Aksesuar təşkilatçısı', 'teskilatci']], true),
                    ...$cabinet,
                    $side,
                    $motion,
                    $material,
                    ...$look,
                    ...$load,
                    $kit('F10_2', [['Relslər', 'relsler'], ['Bərkidicilər', 'berkidiciler'], ['Bölücülər', 'boluculer']]),
                    $model,
                    $tech,
                    ...$service,
                ]),
            ],
        ],

        // ════ 11. Mebelin transformasiya mexanizmləri (F11_1 – F11_3) ═══════
        [
            'slug' => 'mebelin-transformasiya-mexanizmleri',
            'sort_order' => 25,
            'name' => $n('Mebelin transformasiya mexanizmləri', 'Механизмы трансформации мебели', 'Furniture transformation mechanisms'),
            'classes' => [
                'divan-transformasiya-mexanizmleri' => $card('F11_1', $n('Divan transformasiya mexanizmləri', 'Механизмы трансформации диванов', 'Sofa bed mechanisms'), $ps, [
                    $f('F11_1', 'hereket', $n('Hərəkət tipi', 'Тип механизма', 'Mechanism type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Kitab', 'kitab'], ['Klik-klak', 'klik-klak'], ['İrəli çıxan', 'ireli-cixan'], ['Qatlanan', 'qatlanan'], ['Delfin', 'delfin'],
                    ]), $other()]] + $hint('İstehsalçının kodu «Uyğun model və ya sistem» sahəsində.', 'Код производителя — в «Совместимой модели или системе».', 'The maker’s code goes in «Compatible model or system».')),
                    $f('F11_1', 'yataq', $n('Uyğun yataq ölçüsü (uzunluq × en)', 'Размер спального места (длина × ширина)', 'Sleeping area (length × width)'), 'size', ['unit' => 'mm', 'required' => true]),
                    ...$dims,
                    $f('F11_1', 'karkas', $n('Montaj ölçüləri', 'Монтажные размеры', 'Mounting dimensions'), 'text', ['required' => true]
                        + $hint('Karkasın ölçüsü, mm; bərkitmə sxemi «Texniki məlumat» sahəsində.', 'Размер каркаса, мм; схема крепления — в «Технической информации».', 'Frame size, mm; the fixing drawing goes in «Technical data».')),
                    ...$load,
                    $f('F11_1', 'teref', $n('Tərəf', 'Сторона', 'Side'), 'dropdown', ['options' => $opts([
                        ['Sol', 'sol'], ['Sağ', 'sag'], ['Cüt', 'cut'], ['Bütöv sistem', 'butov'],
                    ])]),
                    $kit('F11_1', [['Sol-sağ hissə', 'sol-sag-hisse'], ['Baza', 'baza'], ['Ayaqlar', 'ayaqlar'], ['Bərkidicilər', 'berkidiciler']]),
                    $model,
                    $tech,
                    ...$service,
                ]),
                'carpayi-qaldirma-ve-qatlama-mexanizmleri' => $card('F11_2', $n('Çarpayı qaldırma və qatlama mexanizmləri', 'Подъёмные и откидные механизмы кроватей', 'Bed lift and fold-away mechanisms'), $ps, [
                    $type('F11_2', [['Saxlama bazası üçün qaldırıcı', 'qaldirici'], ['Divara qatlanan çarpayı', 'divara-qatlanan']]),
                    $f('F11_2', 'istiqamet', $n('Açılma istiqaməti', 'Направление открывания', 'Opening direction'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Uzun tərəfdən', 'uzun-terefden'], ['Qısa tərəfdən', 'qisa-terefden'], ['Şaquli qatlama', 'saquli'], ['Üfüqi qatlama', 'ufuqi'],
                    ])]),
                    $f('F11_2', 'baza', $n('Uyğun baza ölçüsü (uzunluq × en)', 'Размер основания (длина × ширина)', 'Base size (length × width)'), 'size', ['unit' => 'mm', 'required' => true]),
                    $f('F11_2', 'ceki', $n('Hərəkətli hissənin çəkisi, minimum', 'Вес подвижной части, минимум', 'Moving part weight, minimum'), 'decimal', ['unit' => 'kg', 'required' => true,
                        'rules' => ['lte' => 'kart-f11-2-ceki-maks']]
                        + $hint('Döşək daxil olmaqla, sistemin seçim cədvəlinə görə.', 'Включая матрас, по таблице подбора системы.', 'Including the mattress, per the system’s selection table.')),
                    $f('F11_2', 'ceki-maks', $n('Hərəkətli hissənin çəkisi, maksimum', 'Вес подвижной части, максимум', 'Moving part weight, maximum'), 'decimal', ['unit' => 'kg', 'required' => true]),
                    $f('F11_2', 'guc', $n('Güc elementi', 'Силовой элемент', 'Power element'), 'dropdown', ['filter' => true, 'options' => $opts([
                        ['Qaz amortizatoru', 'qaz'], ['Yay', 'yay'], ['Elektrik', 'elektrik'],
                    ])]),
                    $force + ['when' => $when('kart-f11-2-guc', 'qaz')],
                    $kit('F11_2', [['Sol-sağ mexanizm', 'sol-sag-mexanizm'], ['Amortizator', 'amortizator'], ['Baza', 'baza'], ['Bərkidicilər', 'berkidiciler']]),
                    $model,
                    $tech + $hint('Seçim cədvəli və montaj sxemi. Divara qatlanan sistemdə korpus və divar bərkitməsi ayrıca nəzərə alınır.', 'Таблица подбора и схема монтажа. Для откидной кровати корпус и крепление к стене учитываются отдельно.', 'Selection table and mounting drawing. For a wall bed, the carcass and wall fixing are considered separately.'),
                    ...$service,
                ]),
                'masa-ve-oturacaq-transformasiya-mexanizmleri' => $card('F11_3', $n('Masa və oturacaq transformasiya mexanizmləri', 'Механизмы трансформации столов и сидений', 'Table and seat mechanisms'), $ps, [
                    $f('F11_3', 'teyinat', $n('Təyinat', 'Назначение', 'Purpose'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Masanı uzatma', 'uzatma'], ['Masa üstünü qaldırma', 'qaldirma'], ['Qatlama', 'qatlama'], ['Reklayner', 'reklayner'],
                    ])]),
                    $f('F11_3', 'idareetme', $n('İdarəetmə', 'Управление', 'Operation'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Mexaniki', 'mexaniki'], ['Elektrik', 'elektrik'],
                    ])]),
                    $mm('F11_3', 'hereket', $n('Hərəkət ölçüsü, minimum', 'Ход, минимум', 'Travel, minimum'), ['required' => true, 'rules' => ['lte' => 'kart-f11-3-hereket-maks']]
                        + $hint('Uzama və ya qaldırma aralığı; tək qiymətdə yalnız bu sahə.', 'Диапазон выдвижения или подъёма; при одном значении — только это поле.', 'Extension or lift range; for one value fill only this.')),
                    $mm('F11_3', 'hereket-maks', $n('Hərəkət ölçüsü, maksimum', 'Ход, максимум', 'Travel, maximum')),
                    ...$dims,
                    ...$load,
                    $f('F11_3', 'mebel-olcusu', $n('Uyğun mebel ölçüsü', 'Подходящий размер мебели', 'Suitable furniture size'), 'text')
                        + $hint('İstehsalçıya görə ölçü aralığı, mm; montaj sxemi «Texniki məlumat» sahəsində.', 'Диапазон размеров по производителю, мм; схема монтажа — в «Технической информации».', 'Size range per the maker, mm; the mounting drawing goes in «Technical data».'),
                    $kit('F11_3', [
                        ['Relslər', 'relsler'], ['Qollar', 'qollar'], ['Elektrik ötürücü', 'elektrik-oturucu'], ['Adapter', 'adapter'], ['Pult', 'pult'], ['Bərkidicilər', 'berkidiciler'],
                    ]),
                    $model,
                    $tech,
                    ...$service,
                ]),
            ],
        ],

        // ════ 12. Hazır mebel hissələri (F12_1 – F12_4) ═════════════════════
        [
            'slug' => 'hazir-mebel-hisseleri',
            'sort_order' => 26,
            'name' => $n('Hazır mebel hissələri', 'Готовые мебельные детали', 'Ready-made furniture parts'),
            'classes' => [
                'metbex-ve-mebel-fasadlari' => $card('F12_1', $n('Hazır mebel fasadları', 'Готовые мебельные фасады', 'Ready-made furniture fronts'), ['piece', 'm2'], [
                    $f('F12_1', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['MDF', 'mdf'], ['LDSP', 'ldsp'], ['Massiv ağac', 'massiv-agac'], ['Alüminium-şüşə', 'aluminium-suse'],
                    ]), $other()]] + $hint('Xam MDF və LDSP lövhələri bu kataloqa daxil deyil.', 'Необработанные плиты МДФ и ЛДСП в этот каталог не входят.', 'Raw MDF and chipboard panels are not in this catalogue.')),
                    $f('F12_1', 'ortuk', $n('Örtük', 'Покрытие', 'Finish'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Boya', 'boya'], ['Laminat', 'laminat'], ['Şpon', 'spon'], ['Plyonka', 'plyonka'], ['Akril', 'akril'], ['Örtüksüz', 'ortuksuz'],
                    ]), $other()]]),
                    $width(),
                    $height(),
                    $thick(),
                    $made,
                    $f('F12_1', 'forma', $n('Forma', 'Форма', 'Shape'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Düz', 'duz'], ['Frezli', 'frezli'], ['Çərçivəli', 'cerciveli'], ['Radiuslu', 'radiuslu'],
                    ])]),
                    ...$look,
                    $f('F12_1', 'emal', $n('Emal', 'Обработка', 'Machining'), 'multiselect', ['options' => [...$opts([
                        ['Menteşə dəliyi', 'mentese-deliyi'], ['Tutacaq dəliyi', 'tutacaq-deliyi'], ['Kənar tutacaq', 'kenar-tutacaq'],
                    ]), $noneX('Emalsız', 'emalsiz')]]),
                    $model,
                    $tech,
                    ...$service,
                    $measure,
                ], $madeToOrder),
                'masaustu-pliteler' => $card('F12_2', $n('Mebel tezgahları', 'Столешницы', 'Worktops'), ['piece', 'lm', 'm2'], [
                    $f('F12_2', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Laminatlı plitə', 'laminatli-plite'], ['Kompakt HPL', 'kompakt-hpl'], ['Massiv ağac', 'massiv-agac'], ['Akril daş', 'akril-das'],
                        ['Kvars kompozit', 'kvars-kompozit'], ['Təbii daş', 'tebii-das'], ['Keramika', 'keramika'], ['Paslanmayan polad', 'paslanmayan-polad'],
                    ]), $other()]] + $hint('Yalnız hazır tezgah; xam daş və plitə burada deyil.', 'Только готовая столешница; необработанный камень и плиты — не здесь.', 'Finished worktops only; raw stone and slabs go elsewhere.')),
                    $length() + $hint('Metrlə satılırsa — plitənin maksimum uzunluğu.', 'При продаже метрами — максимальная длина плиты.', 'If sold by the metre, the maximum slab length.'),
                    $depth() + ['filter' => true],
                    $thick() + ['filter' => true],
                    $made,
                    ...$look,
                    $f('F12_2', 'kenar', $n('Kənar emalı', 'Обработка кромки', 'Edge profile'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Düz', 'duz'], ['Yumru', 'yumru'], ['Maili', 'maili'],
                    ]), $other()]]),
                    $f('F12_2', 'kesikler', $n('Kəsiklər', 'Вырезы', 'Cut-outs'), 'multiselect', ['options' => [
                        $noneX(), ...$opts([['Çanaq', 'canaq'], ['Bişirmə paneli', 'bisirme-paneli']]), $other(),
                    ]]),
                    $kit('F12_2', [['Kəsim', 'kesim'], ['Kənar emalı', 'kenar-emali'], ['Kəsiklər', 'kesikler'], ['Birləşmə', 'birlesme'], ['Montaj', 'montaj']])
                        + $hint('Yalnız qiymətə daxil olanlar. Poqon metr qiymətində daxil olan emal burada seçilir.', 'Только то, что входит в цену. Для цены за погонный метр — какая обработка включена.', 'Only what the price includes. For a per-metre price, the machining it covers.'),
                    $tech,
                    ...$service,
                    $measure,
                ], $madeToOrder),
                'hazir-mebel-refleri' => $card('F12_3', $n('Hazır mebel rəfləri', 'Готовые мебельные полки', 'Ready-made furniture shelves'), ['piece'], [
                    $f('F12_3', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['LDSP', 'ldsp'], ['MDF', 'mdf'], ['Ağac', 'agac'], ['Şüşə', 'suse'], ['Metal', 'metal'],
                    ]), $other()]] + $hint('Şkafın daxili rəfi. Divara ayrıca quraşdırılan rəf mebelin «Rəflər» bölməsindədir.', 'Внутренняя полка шкафа. Настенная полка — в разделе мебели «Полки».', 'A cabinet’s inner shelf. A wall shelf goes under furniture «Shelves».')),
                    $width(),
                    $depth(),
                    $thick(),
                    $made,
                    ...$look,
                    $f('F12_3', 'kenar', $n('Kənar emalı', 'Обработка кромки', 'Edge finish'), 'dropdown', ['options' => $opts([['Hazır', 'hazir'], ['Emalsız', 'emalsiz']])]),
                    $model,
                    $kit('F12_3', [['Rəf tutacaqları', 'ref-tutacaqlari']]),
                    ...$service,
                    $measure,
                ], $madeToOrder),
                'mebel-sokollari-ve-tamamlayici-profilleri' => $card('F12_4', $n('Mebel sokolları və tamamlayıcı profilləri', 'Мебельные цоколи и доборные профили', 'Furniture plinths and trim profiles'), ['piece', 'lm', 'set'], [
                    $type('F12_4', [
                        ['Mətbəx sokolu', 'sokol'], ['Tezgah sonluğu', 'tezgah-sonlugu'], ['Tezgah birləşdiricisi', 'tezgah-birlesdiricisi'], ['Divar-tezgah profili', 'divar-tezgah-profili'],
                    ], true, $hint('Metrlə yalnız sokol və profil satılır.', 'Метрами продаются только цоколь и профиль.', 'Only plinths and profiles are sold by the metre.')),
                    $f('F12_4', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['PVC', 'pvc'], ['Alüminium', 'aluminium'], ['MDF', 'mdf'], ['LDSP', 'ldsp'],
                    ]), $other()]]),
                    $length(),
                    $height(),
                    $f('F12_4', 'kesik', $n('Kəsik ölçüsü', 'Размер сечения', 'Section size'), 'text')
                        + $hint('mm, məs. 100 × 18.', 'мм, напр. 100 × 18.', 'mm, e.g. 100 × 18.'),
                    ...$look,
                    $model,
                    $kit('F12_4', [['Klipslər', 'klipsler'], ['Sonluqlar', 'sonluqlar'], ['Künclər', 'kuncler'], ['Sızdırmazlıq lenti', 'sizdirmazliq-lenti']]),
                    $tech,
                    ...$service,
                ], ['unit_when' => ['lm' => $when('kart-f12-4-tip', 'sokol', 'divar-tezgah-profili')]]),
            ],
        ],
    ],

    // The old generic «furnitura və mexanizmlər» class (hinges, runners, lifts, cargo, corner units):
    // its products move to the hinge card, the old class closes and its links redirect.
    'merge' => [
        'mebel-furniturasi-ve-mexanizmleri-menteseler-istiqametlendiriciler-qaldiricilar-kargo-butulka-refleri-kunc-mexanizmleri' => 'mebel-menteseleri',
    ],
];

// Final pass: the default hints of the shared fields where a class gave none.
foreach ($data['groups'] as $g => $group) {
    foreach ($group['classes'] as $slug => $class) {
        foreach ($class['fields'] as $i => $field) {
            $default = $defaultHints[$field['slug']] ?? (str_ends_with($field['slug'], '-komplekt') ? $defaultHints['komplekt'] : null);
            if ($default !== null && ! isset($field['hint'])) {
                $data['groups'][$g]['classes'][$slug]['fields'][$i]['hint'] = $default;
            }
        }
    }
}

return $data;
