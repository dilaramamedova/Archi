<?php

/*
|--------------------------------------------------------------------------
| Qapılar, pəncərələr və şüşələmə — 31 cards in the workbook's 4 blocks
|--------------------------------------------------------------------------
|
| Source: Archi_AZ_Qapi_Pencere_Kataloq_AZ.xlsx (client, 25.11.2026). Format and decisions:
| docs/product-cards.md. The workbook's 4 blocks («4 əsas blok. 12 alt bölmə») are the
| groups; the 12 numbered «alt bölmə» stay inside their block.
|
| The workbook's card codes (D01_1 … D12) repeat the Döşəmə file's codes, so they are kept
| only as `code`; every attribute slug carries the door/window prefix: shared `kart-qp-*`,
| per-card `kart-qd01-1-*`, `kart-qd10-3-*` … `kart-zemanet` / `kart-zemanet-muddeti` are the
| shared definitions of the other files.
|
| Repeated blocks (material, açılma üsulu / istiqaməti, ölçülər, rəng və örtük, şüşə və
| şüşəpaket, istilik / səs / təsdiqlənmiş siniflər, ölçmə və montaj …) are one shared
| definition each. Documented classes (EI, Sa, Rw, Uw, RC …) are asked only with a document.
| Option values are Azerbaijani only; attribute / class names az/ru/en.
|
*/

$n = fn (string $az, string $ru, string $en): array => compact('az', 'ru', 'en');
$o = fn (string $az, string $ru, string $en, array $extra = []): array => ['value' => compact('az', 'ru', 'en')] + $extra;
$a = fn (string $az, array $extra = []): array => ['value' => ['az' => $az]] + $extra;

$P_NAME = $n('Dəqiq adını yazın', 'Укажите точное название', 'Enter the exact name');
$P_CODE = $n('Rəngi və ya kodunu yazın', 'Укажите цвет или код', 'Enter the colour or its code');
$P_RAL = $n('RAL kodunu yazın', 'Укажите код RAL', 'Enter the RAL code');
$P_CLASS = $n('Sinfi sənəddəki kimi yazın', 'Укажите класс как в документе', 'Enter the class as in the document');

$ns = $o('Göstərilməyib', 'Не указано', 'Not specified', ['slug' => 'gosterilmeyib']);
$other = fn (?array $prompt = null, string $az = 'Digər', string $slug = 'diger'): array => $a($az, ['slug' => $slug, 'prompt' => $prompt ?? $P_NAME]);
$none = fn (string $az = 'Daxil deyil'): array => $a($az, ['slug' => 'daxil-deyil', 'exclusive' => true]);
$noneX = fn (string $az = 'Yoxdur', string $slug = 'yoxdur'): array => $a($az, ['slug' => $slug, 'exclusive' => true]);
$na = $a('Tətbiq edilmir', ['slug' => 'tetbiq-edilmir']);
$when = fn (string $attr, string ...$in): array => ['attr' => $attr, 'in' => $in];
$opts = fn (array $list): array => array_map(fn ($pair) => $a($pair[0], ['slug' => $pair[1]]), $list);
$hint = fn (string $az, string $ru, string $en): array => ['hint' => compact('az', 'ru', 'en')];
$req = fn (array $field, bool $required = true): array => ['required' => $required] + $field;
$varYox = $opts([['Var', 'var'], ['Yoxdur', 'yoxdur']]);

// ── Shared ──────────────────────────────────────────────────────────────────
$warranty = ['slug' => 'kart-zemanet', 'name' => $n('Zəmanət', 'Гарантия', 'Warranty'), 'type' => 'dropdown', 'options' => [
    $o('Var', 'Есть', 'Provided', ['slug' => 'var']), $o('Verilmir', 'Не предоставляется', 'Not provided', ['slug' => 'verilmir']), $ns,
]];
$warrantyMonths = ['slug' => 'kart-zemanet-muddeti', 'name' => $n('Zəmanət müddəti', 'Срок гарантии', 'Warranty period'), 'type' => 'decimal', 'unit' => 'ay',
    'rules' => ['integer' => true], 'when' => ['attr' => 'kart-zemanet', 'in' => ['var']]];
$guarantee = [$warranty, $warrantyMonths];

// «Ölçmə və montaj — İki seçim: Daxildir / ayrıca ödənilir / təqdim edilmir».
$serviceOpts = $opts([['Daxildir', 'daxildir'], ['Ayrıca ödənilir', 'ayrica'], ['Təqdim edilmir', 'teqdim-edilmir']]);
$service = [
    ['slug' => 'kart-qp-olcme', 'name' => $n('Ölçmə', 'Замер', 'Measuring'), 'type' => 'dropdown', 'options' => $serviceOpts],
    ['slug' => 'kart-qp-montaj-xidmeti', 'name' => $n('Montaj', 'Монтаж', 'Installation'), 'type' => 'dropdown', 'options' => $serviceOpts],
];

// «Polotno materialı*».
$leafMaterial = ['slug' => 'kart-qp-polotno-material', 'name' => $n('Polotno materialı', 'Материал полотна', 'Leaf material'), 'type' => 'dropdown',
    'required' => true, 'filter' => true, 'options' => [...$opts([
        ['Ağac', 'agac'], ['MDF əsaslı', 'mdf'], ['Polad', 'polad'], ['Alüminium', 'aluminium'], ['PVC', 'pvc'], ['Şüşə', 'suse'], ['Kombinə', 'kombine'],
    ]), $other()]];

// «Açılma üsulu*» — doors and door-window systems / windows.
$doorOpening = ['slug' => 'kart-qp-acilma-usulu', 'name' => $n('Açılma üsulu', 'Способ открывания', 'Opening type'), 'type' => 'dropdown',
    'required' => true, 'filter' => true, 'options' => [...$opts([
        ['Açılan', 'acilan'], ['Sürüşən', 'surusen'], ['Qatlanan', 'qatlanan'], ['Pivot', 'pivot'], ['Sabit', 'sabit'],
    ]), $other()]];
$windowOpening = ['slug' => 'kart-qp-pencere-acilma', 'name' => $n('Açılma üsulu', 'Способ открывания', 'Opening type'), 'type' => 'dropdown',
    'required' => true, 'filter' => true, 'options' => [...$opts([
        ['Sabit', 'sabit'], ['Dönən', 'donen'], ['Açılıb-qatlanan', 'acilib-qatlanan'], ['Üstə açılan', 'uste-acilan'], ['Sürüşən', 'surusen'],
    ]), $other()]];

// «Açılma istiqaməti* — Seçim və sxem»: the choice + the maker's drawing («Sxem»).
$direction = ['slug' => 'kart-qp-acilma-istiqameti', 'name' => $n('Açılma istiqaməti', 'Направление открывания', 'Opening direction'), 'type' => 'dropdown',
    'required' => true, 'options' => [...$opts([['Sol', 'sol'], ['Sağ', 'sag'], ['Universal', 'universal']]), $na]]
    + $hint('İçəri / xaricə açılma və baxış tərəfi «Sxem» sahəsində göstərilir.', 'Открывание внутрь / наружу и сторона обзора — в поле «Схема».', 'Inward / outward opening and the viewing side go in «Drawing».');
$scheme = fn (bool $required = false, ?array $h = null): array => [
    'slug' => 'kart-qp-sxem', 'name' => $n('Sxem', 'Схема', 'Drawing'), 'type' => 'document', 'required' => $required,
    'hint' => $h ?? $n('İstehsalçının sxemi: açılma tərəfi, qanadlar, ölçülər. Fayl və ya link.', 'Схема производителя: сторона открывания, створки, размеры. Файл или ссылка.', 'The maker’s drawing: opening side, leaves, sizes. File or link.'),
];

// «Qanadlar* — Rəqəmlər və sxem: qanad sayı; açılan və sabit hissələr ayrıca».
$leafCount = ['slug' => 'kart-qp-qanad-sayi', 'name' => $n('Qanad sayı', 'Количество створок', 'Number of leaves'), 'type' => 'decimal', 'required' => true,
    'unit' => 'ədəd', 'rules' => ['integer' => true, 'min' => 1]];
$leaves = [
    $leafCount,
    ['slug' => 'kart-qp-acilan-qanad', 'name' => $n('Açılan qanadlar', 'Открывающиеся створки', 'Opening leaves'), 'type' => 'decimal', 'unit' => 'ədəd',
        'rules' => ['integer' => true, 'lte' => 'kart-qp-qanad-sayi']],
    ['slug' => 'kart-qp-sabit-hisse', 'name' => $n('Sabit hissələr', 'Глухие части', 'Fixed parts'), 'type' => 'decimal', 'unit' => 'ədəd',
        'rules' => ['integer' => true]],
];

// «Ölçülər* — En × hündürlük, mm. Satılan blokun xarici ölçüsü; qanad/polotno ölçüsü ayrıca.
// Sifariş ölçüsü mümkündürsə qeyd edilir».
$size = ['slug' => 'kart-qp-olculer', 'name' => $n('Ölçülər (en × hündürlük)', 'Размеры (ширина × высота)', 'Size (width × height)'), 'type' => 'size',
    'required' => true, 'unit' => 'mm'];
$leafSize = ['slug' => 'kart-qp-qanad-olcusu', 'name' => $n('Qanad / polotno ölçüsü (en × hündürlük)', 'Размер створки / полотна (ширина × высота)', 'Leaf size (width × height)'),
    'type' => 'size', 'unit' => 'mm'];
$customSize = ['slug' => 'kart-qp-sifaris-olcusu', 'name' => $n('Sifariş ölçüsü', 'Изготовление по размеру', 'Made to size'), 'type' => 'dropdown', 'options' => [
    $a('Mümkündür', ['slug' => 'mumkundur']), $a('Yalnız göstərilən ölçü', ['slug' => 'yalniz-gosterilen']), $ns,
]];
$sizes = fn (bool $leaf = true): array => [$size, ...($leaf ? [$leafSize] : []), $customSize];

$leafThickness = ['slug' => 'kart-qp-polotno-qalinligi', 'name' => $n('Polotno qalınlığı', 'Толщина полотна', 'Leaf thickness'), 'type' => 'decimal', 'unit' => 'mm'];
// «Qapı qalınlığı — 2 rəqəm, minimum–maksimum mm» (hardware).
$doorThickness = fn (bool $required = false): array => [
    ['slug' => 'kart-qp-qapi-qalinligi', 'name' => $n('Qapı qalınlığı', 'Толщина двери', 'Door thickness'), 'type' => 'decimal', 'unit' => 'mm', 'required' => $required,
        'rules' => ['lte' => 'kart-qp-qapi-qalinligi-maks']]
        + $hint('Minimum; tək qiymətdə yalnız bu sahə.', 'Минимум; при одном значении — только это поле.', 'The minimum; for one value fill only this.'),
    ['slug' => 'kart-qp-qapi-qalinligi-maks', 'name' => $n('Qapı qalınlığı, maks', 'Толщина двери, макс.', 'Door thickness, max'), 'type' => 'decimal', 'unit' => 'mm'],
];
// «Divar qalınlığı» — one value (opening) or minimum–maximum (frames).
$wall = ['slug' => 'kart-qp-divar-qalinligi', 'name' => $n('Divar qalınlığı', 'Толщина стены', 'Wall thickness'), 'type' => 'decimal', 'unit' => 'mm'];
$wallMax = ['slug' => 'kart-qp-divar-qalinligi-maks', 'name' => $n('Divar qalınlığı, maks', 'Толщина стены, макс.', 'Wall thickness, max'), 'type' => 'decimal', 'unit' => 'mm'];

// «Tələb olunan açılış — Tikinti açılışının eni × hündürlüyü və divar qalınlığı, mm; istehsalçı sxemi».
$opening = [
    ['slug' => 'kart-qp-acilis', 'name' => $n('Tələb olunan açılış (en × hündürlük)', 'Требуемый проём (ширина × высота)', 'Required opening (width × height)'),
        'type' => 'size', 'unit' => 'mm']
        + $hint('Tikinti açılışının ölçüsü; istehsalçının sxemi «Sxem» sahəsində.', 'Размер строительного проёма; схема производителя — в поле «Схема».', 'Size of the structural opening; the maker’s drawing goes in «Drawing».'),
    $wall,
];

// Universal 3D dimensions, mm.
$dim = fn (string $key, array $name): array => ['slug' => 'kart-qp-'.$key, 'name' => $name, 'type' => 'decimal', 'unit' => 'mm', 'required' => true];
$length = $dim('uzunluq', $n('Uzunluq', 'Длина', 'Length'));
$width = $dim('en', $n('En', 'Ширина', 'Width'));
$depth = $dim('derinlik', $n('Dərinlik', 'Глубина', 'Depth'));
$height = $dim('hundurluk', $n('Hündürlük', 'Высота', 'Height'));
$thickness = $dim('qalinliq', $n('Qalınlıq', 'Толщина', 'Thickness'));

// «Rəng və örtük* — Rəng/RAL; boya / şpon / laminat / plyonka / anodlaşdırma / örtüksüz / digər.
// İki tərəf fərqlidirsə ayrıca».
$colorOpts = [...$opts([
    ['Ağ', 'ag'], ['Qara', 'qara'], ['Boz', 'boz'], ['Antrasit', 'antrasit'], ['Qəhvəyi', 'qehveyi'], ['Bej', 'bej'], ['Ağac teksturası', 'agac-teksturasi'],
]), $a('RAL üzrə', ['slug' => 'ral', 'prompt' => $P_RAL]), $other($P_CODE)];
$coatOpts = [...$opts([
    ['Boya', 'boya'], ['Şpon', 'spon'], ['Laminat', 'laminat'], ['Plyonka', 'plyonka'], ['Anodlaşdırma', 'anodlasdirma'], ['Örtüksüz', 'ortuksuz'],
]), $other()];
$color = ['slug' => 'kart-qp-reng', 'name' => $n('Rəng', 'Цвет', 'Colour'), 'type' => 'dropdown', 'required' => true, 'filter' => true, 'options' => $colorOpts];
$coat = ['slug' => 'kart-qp-ortuk', 'name' => $n('Örtük', 'Покрытие', 'Finish'), 'type' => 'dropdown', 'required' => true, 'options' => $coatOpts];
$finish = fn (bool $twoSides = true): array => $twoSides ? [
    $color + $hint('Tərəflər fərqlidirsə — birinci tərəfin rəngi.', 'Если стороны различаются — цвет первой стороны.', 'If the sides differ, the first side’s colour.'),
    $coat,
    ['slug' => 'kart-qp-terefler', 'name' => $n('Tərəflər', 'Стороны', 'Sides'), 'type' => 'dropdown',
        'options' => $opts([['Hər iki tərəf eyni', 'eyni'], ['Tərəflər fərqli', 'ferqli']])],
    ['slug' => 'kart-qp-reng-2', 'name' => $n('Rəng, ikinci tərəf', 'Цвет второй стороны', 'Colour, second side'), 'type' => 'dropdown', 'required' => true,
        'when' => $when('kart-qp-terefler', 'ferqli'), 'options' => $colorOpts],
    ['slug' => 'kart-qp-ortuk-2', 'name' => $n('Örtük, ikinci tərəf', 'Покрытие второй стороны', 'Finish, second side'), 'type' => 'dropdown', 'required' => true,
        'when' => $when('kart-qp-terefler', 'ferqli'), 'options' => $coatOpts],
] : [$color, $coat];

// «Rəng və səth* — İki seçim» (hardware).
$hwColor = [
    ['slug' => 'kart-qp-furnitura-reng', 'name' => $n('Rəng', 'Цвет', 'Colour'), 'type' => 'dropdown', 'required' => true, 'filter' => true, 'options' => [...$opts([
        ['Ağ', 'ag'], ['Qara', 'qara'], ['Boz', 'boz'], ['Xrom', 'xrom'], ['Qızılı', 'qizili'],
    ]), $other($P_CODE)]],
    ['slug' => 'kart-qp-seth', 'name' => $n('Səth', 'Поверхность', 'Surface'), 'type' => 'dropdown', 'required' => true, 'options' => [...$opts([
        ['Mat', 'mat'], ['Parlaq', 'parlaq'], ['Fırçalanmış', 'fircalanmis'],
    ]), $other()]],
];

// «Şüşə və şüşəpaket — Şüşəsiz / tək şüşə / şüşəpaket. Şüşə qatları, ara boşluqlar və ümumi
// qalınlıq mm; temperli/laminə, şəffaf/mat/rəngli, Low-E seçimi».
$glazed = $when('kart-qp-suse', 'tek', 'susepaket');
$glassThickness = ['slug' => 'kart-qp-suse-qalinligi', 'name' => $n('Şüşənin ümumi qalınlığı', 'Общая толщина стекла', 'Total glass thickness'), 'type' => 'decimal', 'unit' => 'mm'];
$glassLook = ['slug' => 'kart-qp-suse-gorunus', 'name' => $n('Şüşənin görünüşü', 'Вид стекла', 'Glass appearance'), 'type' => 'dropdown', 'filter' => true, 'options' => [...$opts([
    ['Şəffaf', 'seffaf'], ['Mat', 'mat'], ['Rəngli', 'rengli'], ['Naxışlı', 'naxisli'],
]), $other()]];
$glass = fn (bool $lookRequired = false): array => [
    ['slug' => 'kart-qp-suse', 'name' => $n('Şüşələnmə', 'Остекление', 'Glazing'), 'type' => 'dropdown',
        'options' => $opts([['Şüşəsiz', 'susesiz'], ['Tək şüşə', 'tek'], ['Şüşəpaket', 'susepaket']])],
    ['slug' => 'kart-qp-susepaket-formulu', 'name' => $n('Şüşəpaketin formulu', 'Формула стеклопакета', 'Glazing unit build-up'), 'type' => 'text',
        'when' => $when('kart-qp-suse', 'susepaket')]
        + $hint('Şüşə qatları və ara boşluqlar, mm: məs. 4-16-4-16-4.', 'Стёкла и дистанции, мм: напр. 4-16-4-16-4.', 'Panes and spacers, mm: e.g. 4-16-4-16-4.'),
    ['slug' => 'kart-qp-susepaket-kamera', 'name' => $n('Şüşəpaketin kamera sayı', 'Количество камер стеклопакета', 'Glazing unit chambers'), 'type' => 'decimal',
        'unit' => 'ədəd', 'rules' => ['integer' => true], 'when' => $when('kart-qp-suse', 'susepaket')]
        + $hint('Profilin kameralarından ayrı göstəricidir.', 'Отдельно от камер профиля.', 'Not the profile’s chambers.'),
    $glassThickness + ['when' => $glazed],
    ['slug' => 'kart-qp-suse-emali', 'name' => $n('Şüşənin emalı', 'Обработка стекла', 'Glass treatment'), 'type' => 'multiselect', 'when' => $glazed, 'options' => [
        ...$opts([['Adi', 'adi'], ['Temperli', 'temperli'], ['Laminə', 'lamine']]), $noneX('Göstərilməyib', 'gosterilmeyib'),
    ]],
    $lookRequired ? ['required' => true, 'filter' => false] + $glassLook : ['when' => $glazed, 'filter' => false] + $glassLook,
    ['slug' => 'kart-qp-low-e', 'name' => $n('Low-E örtük', 'Низкоэмиссионное покрытие (Low-E)', 'Low-E coating'), 'type' => 'dropdown', 'when' => $glazed,
        'options' => [...$varYox, $ns]],
];

// «Çərçivə/profil* — Seçim və model: material + brend və sistem/seriya»;
// «Profil göstəriciləri — montaj dərinliyi; PVC-də kamera sayı, alüminiumda termik ayırma».
$profile = ['slug' => 'kart-qp-profil', 'name' => $n('Çərçivə / profil', 'Рама / профиль', 'Frame / profile'), 'type' => 'dropdown', 'required' => true, 'filter' => true,
    'options' => [...$opts([['PVC', 'pvc'], ['Alüminium', 'aluminium'], ['Ağac', 'agac'], ['Ağac-alüminium', 'agac-aluminium'], ['Polad', 'polad']]), $other()]];
$profileSystem = ['slug' => 'kart-qp-profil-sistem', 'name' => $n('Profil sistemi', 'Профильная система', 'Profile system'), 'type' => 'text', 'required' => true]
    + $hint('Profilin brendi və sistemi / seriyası.', 'Бренд и система / серия профиля.', 'The profile’s brand and system / series.');
$thermalBreak = ['slug' => 'kart-qp-termik-ayirma', 'name' => $n('Termik ayırma', 'Терморазрыв', 'Thermal break'), 'type' => 'dropdown', 'options' => [...$varYox, $ns]];
$profileBlock = [
    $profile,
    $profileSystem,
    ['slug' => 'kart-qp-montaj-derinliyi', 'name' => $n('Montaj dərinliyi', 'Монтажная глубина', 'Installation depth'), 'type' => 'decimal', 'unit' => 'mm'],
    ['slug' => 'kart-qp-profil-kamera', 'name' => $n('Profilin kamera sayı', 'Количество камер профиля', 'Profile chambers'), 'type' => 'decimal', 'unit' => 'ədəd',
        'rules' => ['integer' => true], 'when' => $when('kart-qp-profil', 'pvc')],
    $thermalBreak + ['when' => $when('kart-qp-profil', 'aluminium', 'agac-aluminium')],
];

// «İstilik keçiriciliyi — W/(m²·K); Uw / Ud; Ug ayrıca; nümunə ölçüsü və istehsalçı sənədi».
$thermal = [
    ['slug' => 'kart-qp-u', 'name' => $n('İstilik keçiriciliyi (Uw / Ud)', 'Теплопередача (Uw / Ud)', 'Thermal transmittance (Uw / Ud)'), 'type' => 'decimal', 'unit' => 'W/(m²·K)']
        + $hint('Bütöv pəncərə üçün Uw, qapı üçün Ud; istehsalçı sənədindəki nümunə ölçüsü üzrə.', 'Uw для окна целиком, Ud для двери; по образцу из документа производителя.', 'Uw for the whole window, Ud for a door; per the sample size in the maker’s document.'),
    ['slug' => 'kart-qp-ug', 'name' => $n('Şüşənin Ug göstəricisi', 'Ug стекла', 'Glass Ug'), 'type' => 'decimal', 'unit' => 'W/(m²·K)']
        + $hint('Uw, Ug və Uf bir-birini əvəz etmir.', 'Uw, Ug и Uf не заменяют друг друга.', 'Uw, Ug and Uf are not interchangeable.'),
    ['slug' => 'kart-qp-istilik-sened', 'name' => $n('İstilik göstəricisinin sənədi', 'Документ по теплопередаче', 'Thermal document'), 'type' => 'document']
        + $hint('İstehsalçının sənədi; nümunə ölçüsü göstərilir. Fayl və ya link.', 'Документ производителя с размером образца. Файл или ссылка.', 'The maker’s document with the sample size. File or link.'),
];

// «Səs izolyasiyası — Rw, dB; varsa C/Ctr. Sınaqdan keçmiş komplektə və ölçüyə aid sənəd».
$sound = [
    ['slug' => 'kart-qp-rw', 'name' => $n('Səs izolyasiyası Rw', 'Звукоизоляция Rw', 'Sound insulation Rw'), 'type' => 'decimal', 'unit' => 'dB']
        + $hint('Sınaqdan keçmiş komplekt və ölçü üzrə.', 'Для испытанного комплекта и размера.', 'For the tested set and size.'),
    ['slug' => 'kart-qp-c-ctr', 'name' => $n('Düzəlişlər C; Ctr', 'Поправки C; Ctr', 'Corrections C; Ctr'), 'type' => 'text']
        + $hint('Varsa, məs. -1; -4.', 'Если есть, напр. -1; -4.', 'If given, e.g. -1; -4.'),
    ['slug' => 'kart-qp-ses-sened', 'name' => $n('Səs izolyasiyasının sənədi', 'Документ по звукоизоляции', 'Sound test document'), 'type' => 'document'],
];

// A test report / certificate («Təsdiq sənədi», «Uyğunluq sənədi», «… və sənəd»).
$doc = ['slug' => 'kart-qp-sened', 'name' => $n('Təsdiq sənədi', 'Подтверждающий документ', 'Supporting document'), 'type' => 'document'];

// «Təsdiqlənmiş xüsusiyyətlər — hava keçirmə, su keçirməzlik, külək yükü və oğurluğa müqavimət:
// varsa sinif + sınaq standartı/sənəd. Məlum deyilsə Göstərilməyib».
$classText = fn (string $key, array $name, string $value, array $example): array => ['slug' => 'kart-qp-sinif-'.$key, 'name' => $name, 'type' => 'text', 'required' => true,
    'when' => $when('kart-qp-tesdiq', $value), 'hint' => $example];
$verified = [
    ['slug' => 'kart-qp-tesdiq', 'name' => $n('Təsdiqlənmiş xüsusiyyətlər', 'Подтверждённые характеристики', 'Certified properties'), 'type' => 'multiselect', 'options' => [
        ...$opts([['Hava keçirmə', 'hava'], ['Su keçirməzlik', 'su'], ['Külək yükü', 'kulek'], ['Oğurluğa müqavimət', 'ogurluq']]), $noneX('Göstərilməyib', 'gosterilmeyib'),
    ]] + $hint('Yalnız sınaq sənədi olan siniflər.', 'Только классы с протоколом испытаний.', 'Only classes backed by a test report.'),
    $classText('hava', $n('Hava keçirmə sinfi', 'Класс воздухопроницаемости', 'Air permeability class'), 'hava',
        $n('Sinif və standart, məs. 4, EN 12207.', 'Класс и стандарт, напр. 4, EN 12207.', 'Class and standard, e.g. 4, EN 12207.')),
    $classText('su', $n('Su keçirməzlik sinfi', 'Класс водонепроницаемости', 'Watertightness class'), 'su',
        $n('Sinif və standart, məs. 9A, EN 12208.', 'Класс и стандарт, напр. 9A, EN 12208.', 'Class and standard, e.g. 9A, EN 12208.')),
    $classText('kulek', $n('Külək yükünə müqavimət sinfi', 'Класс ветровой нагрузки', 'Wind load class'), 'kulek',
        $n('Sinif və standart, məs. C5, EN 12210.', 'Класс и стандарт, напр. C5, EN 12210.', 'Class and standard, e.g. C5, EN 12210.')),
    ['slug' => 'kart-qp-rc', 'name' => $n('Oğurluğa müqavimət sinfi (RC)', 'Класс взломостойкости (RC)', 'Burglar resistance class (RC)'), 'type' => 'dropdown', 'required' => true,
        'when' => $when('kart-qp-tesdiq', 'ogurluq'), 'options' => [...$opts([
            ['RC1N', 'rc1n'], ['RC2N', 'rc2n'], ['RC2', 'rc2'], ['RC3', 'rc3'], ['RC4', 'rc4'], ['RC5', 'rc5'], ['RC6', 'rc6'],
        ]), $other($P_CLASS)]] + $hint('EN 1627 üzrə, sənəddəki kimi.', 'По EN 1627, как в документе.', 'Per EN 1627, as in the document.'),
    $doc + ['required' => true, 'when' => $when('kart-qp-tesdiq', 'hava', 'su', 'kulek', 'ogurluq')],
];

// «Astana — standart / alçaq / döşəmə ilə eyni səviyyədə; hündürlük mm».
$threshold = [
    ['slug' => 'kart-qp-astana', 'name' => $n('Astana', 'Порог', 'Threshold'), 'type' => 'dropdown', 'options' => [...$opts([
        ['Standart', 'standart'], ['Alçaq', 'alcaq'], ['Döşəmə ilə eyni səviyyədə', 'doseme-seviyyesi'],
    ]), $other()]],
    ['slug' => 'kart-qp-astana-hundurluyu', 'name' => $n('Astananın hündürlüyü', 'Высота порога', 'Threshold height'), 'type' => 'decimal', 'unit' => 'mm'],
];

// Roof windows and their flashings: «Dam maililiyi — minimum–maksimum, dərəcə», «Dam örtüyü».
$pitch = [
    ['slug' => 'kart-qp-maillik', 'name' => $n('Dam maililiyi, minimum', 'Уклон кровли, минимум', 'Roof pitch, minimum'), 'type' => 'decimal', 'unit' => '°', 'required' => true,
        'rules' => ['lte' => 'kart-qp-maillik-maks', 'max' => 90]],
    ['slug' => 'kart-qp-maillik-maks', 'name' => $n('Dam maililiyi, maksimum', 'Уклон кровли, максимум', 'Roof pitch, maximum'), 'type' => 'decimal', 'unit' => '°', 'required' => true,
        'rules' => ['max' => 90]],
];
$roof = [
    ['slug' => 'kart-qp-dam-ortuyu', 'name' => $n('Dam örtüyü', 'Кровельное покрытие', 'Roofing'), 'type' => 'dropdown', 'required' => true, 'filter' => true,
        'options' => $opts([['Düz', 'duz'], ['Profilli', 'profilli']])],
    ['slug' => 'kart-qp-dam-ortuk-materiali', 'name' => $n('Örtük materialı', 'Материал кровли', 'Roofing material'), 'type' => 'text']
        + $hint('Məs. kirəmit, metal kirəmit, şifer.', 'Напр. черепица, металлочерепица, шифер.', 'E.g. tiles, metal tiles, slate.'),
    ['slug' => 'kart-qp-profil-hundurluyu', 'name' => $n('Örtük profilinin maksimum hündürlüyü', 'Макс. высота профиля кровли', 'Max roofing profile height'), 'type' => 'decimal',
        'unit' => 'mm', 'when' => $when('kart-qp-dam-ortuyu', 'profilli')],
];

// «Uyğun model və ya sistem* — Axtarışlı siyahı və ya qısa mətn».
$model = ['slug' => 'kart-qp-uygun-model', 'name' => $n('Uyğun model və ya sistem', 'Совместимая модель или система', 'Compatible model or system'), 'type' => 'text', 'required' => true];

// «Yük həddi* — kg», «Qanad hədləri — maksimum çəki kg».
$load = ['slug' => 'kart-qp-yuk-heddi', 'name' => $n('Yük həddi', 'Допустимая нагрузка', 'Load limit'), 'type' => 'decimal', 'unit' => 'kg', 'required' => true];

// Materials shared by several hardware cards.
$hwMaterial = ['slug' => 'kart-qp-furnitura-material', 'name' => $n('Material', 'Материал', 'Material'), 'type' => 'dropdown', 'required' => true, 'filter' => true,
    'options' => [...$opts([['Pirinç', 'pirinc'], ['Paslanmayan polad', 'paslanmayan-polad'], ['Metal ərintisi', 'metal-erintisi'], ['Plastik', 'plastik']]), $other()]];
$mpkMaterial = ['slug' => 'kart-qp-material-mpk', 'name' => $n('Material', 'Материал', 'Material'), 'type' => 'dropdown', 'required' => true, 'filter' => true,
    'options' => [...$opts([['Metal', 'metal'], ['Plastik', 'plastik'], ['Kombinə', 'kombine']]), $other()]];

// Default hints of the shared fields. They are added in the final pass (bottom of the
// file) to every field that has no hint of its own, so `$field + $hint(...)` overrides them.
$defaultHints = [
    'kart-qp-olculer' => $n('Satılan blokun xarici ölçüsü. Qiymət bu ölçüyə aiddir.', 'Наружный размер продаваемого блока. Цена относится к этому размеру.', 'Outer size of the unit sold. The price is for this size.'),
    'kart-qp-sened' => $n('Sınaq protokolu və ya sertifikat: model, ölçü və komplekt göstərilir. Fayl və ya link.', 'Протокол испытаний или сертификат: модель, размер и комплект. Файл или ссылка.', 'Test report or certificate covering model, size and set. File or link.'),
    'kart-qp-uygun-model' => $n('Brend + seriya / model və ya istehsalçının uyğunluq kodu. Universal olduqda ölçü və uyğunluq şərtləri.', 'Бренд + серия / модель или код совместимости. Для универсальных — размеры и условия.', 'Brand + series / model or the maker’s compatibility code. For universal parts, sizes and conditions.'),
    // Every «Komplektə daxildir» (slug ending in -komplekt).
    'komplekt' => $n('Yalnız qiymətə daxil olanlar.', 'Только то, что входит в цену.', 'Only what the price includes.'),
];

// «Komplektə daxildir — yalnız qiymətə daxil olanlar». The door list is shared.
$doorKit = ['slug' => 'kart-qp-komplekt', 'name' => $n('Komplektə daxildir', 'В комплекте', 'Included'), 'type' => 'multiselect',
    'options' => [...$opts([
        ['Qanad', 'qanad'], ['Çərçivə', 'cercive'], ['Şüşə', 'suse'], ['Dəstək', 'destek'], ['Kilid', 'kilid'], ['Menteşə', 'mentese'], ['Astana', 'astana'], ['Bərkitmə', 'berkitme'],
    ]), $none()]];

$cs = fn (string $code): string => 'kart-q'.strtolower(str_replace('_', '-', $code));
$kit = fn (string $code, array $items): array => [
    'slug' => $cs($code).'-komplekt', 'name' => $n('Komplektə daxildir', 'В комплекте', 'Included'), 'type' => 'multiselect',
    'options' => [...$opts($items), $none()],
];
$f = fn (string $code, string $key, array $name, string $type, array $extra = []): array => ['slug' => $cs($code).'-'.$key, 'name' => $name, 'type' => $type] + $extra;
$card = fn (string $code, array $name, array $units, array $fields, array $extra = []): array => ['code' => $code, 'name' => $name, 'sale' => 'unit', 'units' => $units, 'fields' => $fields] + $extra;
$count = fn (string $code, string $key, array $name, array $extra = []): array => ['slug' => $cs($code).'-'.$key, 'name' => $name, 'type' => 'decimal', 'unit' => 'ədəd', 'rules' => ['integer' => true]] + $extra;

$ps = ['piece', 'set'];

$data = [
    'section' => 'qapilar-pencereler-ve-suseleme',

    'groups' => [
        // ════ 1. Qapılar (D01_1–D03_2) ═════════════════════════════════════
        [
            'slug' => 'qapilar',
            'name' => $n('Qapılar', 'Двери', 'Doors'),
            'classes' => [
                'otaqlararasi-qapi' => $card('D01_1', $n('Daxili qapılar', 'Межкомнатные двери', 'Interior doors'), $ps, [
                    $leafMaterial,
                    $f('D01_1', 'konstruksiya', $n('Konstruksiya', 'Конструкция', 'Construction'), 'dropdown', ['options' => [...$opts([
                        ['Massiv', 'massiv'], ['Karkas', 'karkas'], ['Pətək dolğu', 'petek'], ['Borulu dolğu', 'borulu'], ['Bütöv dolğu', 'butov'],
                    ]), $other()]]),
                    $doorOpening,
                    $direction,
                    ...$leaves,
                    ...$sizes(),
                    $req($leafThickness),
                    $f('D01_1', 'qutu-novu', $n('Qutu növü', 'Тип коробки', 'Frame type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Adi', 'adi'], ['Gizli', 'gizli'], ['Teleskopik', 'teleskopik'], ['Qutusuz sistem', 'qutusuz'],
                    ])]),
                    ...$finish(),
                    ...$glass(),
                    ...$opening,
                    $scheme(),
                    $doorKit + $hint('Yalnız qiymətə daxil olanlar. Polotno ilə satılırsa «Daxil deyil».', 'Только то, что входит в цену. Для полотна — «Не входит».', 'Only what the price includes. For a leaf alone — «Not included».'),
                    ...$guarantee,
                ]),
                'butov-suse-daxili-qapilar' => $card('D01_2', $n('Bütöv şüşə daxili qapılar', 'Цельностеклянные межкомнатные двери', 'All-glass interior doors'), $ps, [
                    $doorOpening,
                    $direction,
                    ...$leaves,
                    ...$sizes(),
                    $f('D01_2', 'suse-novu', $n('Şüşə növü', 'Тип стекла', 'Glass type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Temperli', 'temperli'], ['Laminə', 'lamine'], ['Temperli-laminə', 'temperli-lamine'],
                    ])]),
                    $req($glassThickness),
                    $req($glassLook),
                    $f('D01_2', 'cercive', $n('Çərçivə', 'Рама', 'Frame'), 'dropdown', ['options' => [...$opts([
                        ['Çərçivəsiz', 'cercivesiz'], ['Alüminium', 'aluminium'], ['Polad', 'polad'],
                    ]), $other()]]),
                    $model,
                    $scheme(true, $n('Emal sxemi: dəlik və kəsik ölçüləri, uyğun menteşə / kilid modeli. Fayl və ya link.', 'Схема обработки: отверстия и вырезы, модель петель / замка. Файл или ссылка.', 'Machining drawing: holes and cut-outs, matching hinge / lock model. File or link.')),
                    $kit('D01_2', [['Şüşə polotno', 'polotno'], ['Menteşə', 'mentese'], ['Kilid', 'kilid'], ['Dəstək', 'destek'], ['Çərçivə', 'cercive'], ['Mexanizm', 'mexanizm']]),
                    ...$service,
                    ...$guarantee,
                ]),
                'giris-qapisi' => $card('D02', $n('Giriş qapıları', 'Входные двери', 'Entrance doors'), $ps, [
                    $f('D02', 'istifade', $n('İstifadə yeri', 'Место установки', 'Use'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Mənzil girişi', 'menzil'], ['Küçədən evə giriş', 'kuce'], ['Kommersiya girişi', 'kommersiya'],
                    ])] + $hint('Küçə qapısı ilə pilləkən meydançasına açılan mənzil qapısı fərqlidir.', 'Уличная дверь и квартирная дверь на лестничную площадку различаются.', 'A street door differs from a flat door onto a stairwell.')),
                    $leafMaterial,
                    $doorOpening,
                    $direction,
                    ...$leaves,
                    ...$sizes(),
                    $leafThickness,
                    ...$finish(),
                    $f('D02', 'dolgu', $n('İstilik izolyasiyası dolğusu', 'Утеплитель', 'Insulation fill'), 'text')
                        + $hint('Dolğu növü, məs. mineral yun, poliuretan köpük.', 'Тип наполнителя, напр. минвата, пенополиуретан.', 'Fill type, e.g. mineral wool, PU foam.'),
                    $thermalBreak,
                    ...$thermal,
                    ...$sound,
                    ...$verified,
                    ...$opening,
                    $scheme(),
                    $doorKit,
                    ...$service,
                    ...$guarantee,
                ]),
                'texniki-ve-xususi-teyinatli-qapilar' => $card('D03_1', $n('Texniki və xüsusi təyinatlı qapılar', 'Технические двери и двери специального назначения', 'Technical and special-purpose doors'), $ps, [
                    $f('D03_1', 'teyinat', $n('Təyinat', 'Назначение', 'Purpose'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Texniki otaq', 'texniki'], ['Anbar', 'anbar'], ['Akustik', 'akustik'], ['Gigiyenik', 'gigiyenik'],
                    ]), $other()]] + $hint('Yanğın və tüstü qorunması olan modellər «Yanğın və tüstü qoruyucu qapılar»dadır.', 'Модели с огне- и дымозащитой — в «Противопожарных и дымозащитных дверях».', 'Fire and smoke doors go under «Fire and smoke doors».')),
                    $leafMaterial,
                    $doorOpening,
                    $direction,
                    ...$leaves,
                    ...$sizes(),
                    ...$finish(),
                    ...$opening,
                    ...$sound,
                    $f('D03_1', 'xususi-gosterici', $n('Xüsusi göstərici', 'Специальный показатель', 'Special rating'), 'text')
                        + $hint('Təyinata uyğun göstərici; yalnız sənədlə, təsdiqsiz iddia yazılmır.', 'Показатель по назначению; только с документом.', 'The rating for the purpose; only with a document.'),
                    $doc,
                    $scheme(),
                    $doorKit,
                    ...$service,
                    ...$guarantee,
                ]),
                'yangina-davamli-qapi' => $card('D03_2', $n('Yanğın və tüstü qoruyucu qapılar', 'Противопожарные и дымозащитные двери', 'Fire and smoke doors'), ['set'], [
                    $f('D03_2', 'teyinat', $n('Təyinat', 'Назначение', 'Purpose'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Yanğın', 'yangin'], ['Tüstü', 'tustu'], ['Yanğın və tüstü', 'yangin-tustu'],
                    ])]),
                    $f('D03_2', 'istifade', $n('İstifadə', 'Применение', 'Use'), 'dropdown', ['options' => [...$opts([['Daxili', 'daxili'], ['Xarici', 'xarici']]), $ns]]),
                    $leafMaterial,
                    $direction,
                    ...$leaves,
                    ...$sizes(),
                    ['slug' => 'kart-qp-yangin-sinfi', 'name' => $n('Yanğına davamlılıq sinfi', 'Класс огнестойкости', 'Fire resistance class'), 'type' => 'dropdown', 'required' => true,
                        'filter' => true, 'when' => $when('kart-qd03-2-teyinat', 'yangin', 'yangin-tustu'), 'options' => [...$opts([
                            ['EI 30', 'ei30'], ['EI 45', 'ei45'], ['EI 60', 'ei60'], ['EI 90', 'ei90'], ['EI 120', 'ei120'], ['E 30', 'e30'], ['EW 30', 'ew30'], ['EW 60', 'ew60'],
                        ]), $other($P_CLASS)]],
                    ['slug' => 'kart-qp-tustu-sinfi', 'name' => $n('Tüstü keçirməzlik sinfi', 'Класс дымонепроницаемости', 'Smoke control class'), 'type' => 'dropdown', 'required' => true,
                        'when' => $when('kart-qd03-2-teyinat', 'tustu', 'yangin-tustu'), 'options' => [...$opts([['Sa', 'sa'], ['S200', 's200']]), $other($P_CLASS)]],
                    ['slug' => 'kart-qp-tam-sinif', 'name' => $n('Tam sinif və standart', 'Полный класс и стандарт', 'Full class and standard'), 'type' => 'text', 'required' => true]
                        + $hint('Sənəddəki kimi, məs. EI2 60-C5 Sa, EN 13501-2. Yalnız dəqiq sertifikatlaşdırılmış variant.', 'Как в документе, напр. EI2 60-C5 Sa, EN 13501-2. Только сертифицированный вариант.', 'As in the document, e.g. EI2 60-C5 Sa, EN 13501-2. The exact certified variant only.'),
                    $req($doc) + $hint('Sınaq / təsnifat sənədi: model, ölçü və komplekt əhatəsi. Sinif sınaqdan keçmiş bütöv sistemə aiddir.', 'Протокол / классификация: модель, размер и комплект. Класс относится ко всей испытанной системе.', 'Test / classification report: model, size and set. The class applies to the whole tested system.'),
                    $f('D03_2', 'divar-novu', $n('Montaj əsası', 'Основание для монтажа', 'Mounting wall'), 'multiselect', ['required' => true, 'options' => [...$opts([
                        ['Kərpic', 'kerpic'], ['Beton', 'beton'], ['Qazobeton', 'qazobeton'], ['Gips-karton arakəsmə', 'gips-karton'],
                    ]), $other()]] + $hint('İstehsalçının təsdiqlədiyi divar növləri.', 'Типы стен, подтверждённые производителем.', 'Wall types approved by the maker.')),
                    $req($wall) + $hint('İstehsalçının təsdiqlədiyi minimum qalınlıq.', 'Минимальная толщина, подтверждённая производителем.', 'The minimum approved by the maker.'),
                    ...$finish(),
                    $f('D03_2', 'suselenme', $n('Şüşələnmə', 'Остекление', 'Glazing'), 'dropdown', ['options' => $opts([['Yoxdur', 'yoxdur'], ['Var', 'var']])]),
                    $f('D03_2', 'suse-melumat', $n('Təsdiqlənmiş şüşə', 'Подтверждённое стекло', 'Approved glazing'), 'text', ['required' => true, 'when' => $when('kart-qd03-2-suselenme', 'var')]
                        + $hint('Şüşənin növü və ölçüsü, sənədə görə.', 'Тип и размер стекла по документу.', 'Glass type and size per the document.')),
                    $f('D03_2', 'oz-ozune', $n('Öz-özünə bağlanma', 'Самозакрывание', 'Self-closing'), 'dropdown', ['options' => [...$varYox, $ns]]),
                    $f('D03_2', 'ardicilliq', $n('Bağlanma ardıcıllığı', 'Координатор закрывания', 'Closing sequence'), 'dropdown', ['options' => [...$varYox, $na]]
                        + $hint('İkiqanadlı qapıda.', 'Для двустворчатой двери.', 'For a double door.')),
                    $f('D03_2', 'antipanik', $n('Antipanik avadanlığın modeli', 'Модель антипаники', 'Panic hardware model'), 'text'),
                    $scheme(),
                    $kit('D03_2', [
                        ['Polotno', 'polotno'], ['Qutu', 'qutu'], ['Menteşə', 'mentese'], ['Kilid', 'kilid'], ['Bağlayıcı', 'baglayici'],
                        ['Qoruyucu kipləşdirici', 'kiplesdirici'], ['Şüşə', 'suse'], ['Antipanik mexanizm', 'antipanik'],
                    ]),
                    ...$service,
                    ...$guarantee,
                ]),
            ],
        ],

        // ════ 2. Pəncərələr və xarici qapı-pəncərə sistemləri (D04–D07) ═══
        [
            'slug' => 'pencere-bloklari',
            'name' => $n('Pəncərələr və xarici qapı-pəncərə sistemləri', 'Окна и наружные дверно-оконные системы', 'Windows and exterior door-window systems'),
            'classes' => [
                'pencere-bloku' => $card('D04', $n('Pəncərələr', 'Окна', 'Windows'), ['piece', 'm2'], [
                    ...$profileBlock,
                    $windowOpening,
                    $direction,
                    ...$leaves,
                    ...$sizes(),
                    ...$glass(),
                    ...$finish(),
                    ...$thermal,
                    ...$sound,
                    ...$verified,
                    $scheme(),
                    $kit('D04', [
                        ['Çərçivə', 'cercive'], ['Qanad', 'qanad'], ['Şüşəpaket', 'susepaket'], ['Furnitura', 'furnitura'], ['Dəstək', 'destek'],
                        ['Tor', 'tor'], ['Altlıq', 'altliq'], ['Su axıdıcı', 'su-axidici'],
                    ]) + $hint('Yalnız qiymətə daxil olanlar. m² qiymətində nümunə ölçüsü «Ölçülər»də göstərilir.', 'Только то, что входит в цену. Для цены за м² образец указан в «Размерах».', 'Only what the price includes. For a per-m² price, the sample size goes in «Size».'),
                    ...$service,
                    ...$guarantee,
                ]),
                'balkon-qapilari-ve-bloklari' => $card('D05', $n('Balkon qapıları və blokları', 'Балконные двери и блоки', 'Balcony doors and units'), ['piece', 'set', 'm2'], [
                    $f('D05', 'nov', $n('Məhsul növü', 'Тип изделия', 'Product type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Ayrıca balkon qapısı', 'qapi'], ['Qapı-pəncərə bloku', 'blok'],
                    ])]),
                    ...$profileBlock,
                    $doorOpening,
                    $direction,
                    ...$leaves,
                    ...$sizes(),
                    ...$glass(),
                    $f('D05', 'alt-hisse', $n('Alt hissə', 'Нижняя часть', 'Lower part'), 'dropdown', ['options' => $opts([['Şüşəli', 'suseli'], ['Panel', 'panel']])]),
                    ...$threshold,
                    ...$finish(),
                    ...$thermal,
                    ...$verified,
                    $scheme(false, $n('Blokda qapı və pəncərə ölçüləri ayrıca sxemdə. Fayl və ya link.', 'Размеры двери и окна в блоке — на схеме. Файл или ссылка.', 'Door and window sizes of the unit on a drawing. File or link.')),
                    $doorKit,
                    ...$service,
                    ...$guarantee,
                ]),
                'panoram-ve-surusme-sistemleri' => $card('D06', $n('Panoram və sürüşmə sistemləri', 'Панорамные и раздвижные системы', 'Panoramic and sliding systems'), ['set', 'm2'], [
                    $f('D06', 'sistem', $n('Sistem növü', 'Тип системы', 'System type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Qaldırılıb-sürüşən', 'qaldirilib-surusen'], ['Paralel sürüşən', 'paralel-surusen'], ['Qatlanan', 'qatlanan'], ['Sabit panoram', 'sabit-panoram'],
                    ]), $other()]] + $hint('Binanın xarici örtüyü üçün. Mövcud balkon / terrasın bağlanması «Balkon və terras şüşələnməsi»ndədir.', 'Для наружной оболочки здания. Остекление балкона / террасы — в отдельном разделе.', 'For the building envelope. Glazing an existing balcony / terrace is a separate class.')),
                    ...$profileBlock,
                    ...$leaves,
                    ...$sizes(),
                    $scheme(true, $n('Açılma sxemi: qanadların hərəkət istiqaməti və sabit hissələr. Fayl və ya link.', 'Схема: направление движения створок и глухие части. Файл или ссылка.', 'Opening drawing: leaf movement and fixed parts. File or link.')),
                    ...$glass(),
                    ...$finish(),
                    ...$threshold,
                    ...$thermal,
                    ...$verified,
                    $kit('D06', [
                        ['Profil', 'profil'], ['Şüşə', 'suse'], ['Rels', 'rels'], ['Mexanizm', 'mexanizm'], ['Dəstək', 'destek'], ['Kilid', 'kilid'], ['Astana', 'astana'],
                    ]),
                    ...$service,
                    ...$guarantee,
                ]),
                'mansard-pencereleri' => $card('D07', $n('Mansard pəncərələri', 'Мансардные окна', 'Roof windows'), $ps, [
                    $profile,
                    $profileSystem,
                    $f('D07', 'acilma', $n('Açılma növü', 'Тип открывания', 'Opening type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Mərkəzdən dönən', 'merkezden'], ['Yuxarıdan açılan', 'yuxaridan'], ['Kombinə', 'kombine'],
                    ]), $other()]]),
                    ...$sizes(),
                    ...$pitch,
                    ...$roof,
                    $f('D07', 'hasiye-modeli', $n('Uyğun montaj haşiyəsinin modeli', 'Модель оклада', 'Matching flashing model'), 'text', ['required' => true]),
                    ...$glass(),
                    ...$finish(),
                    ...$thermal,
                    $f('D07', 'idareetme', $n('İdarəetmə', 'Управление', 'Operation'), 'dropdown', ['filter' => true, 'options' => $opts([
                        ['Əl ilə', 'el'], ['Elektrik', 'elektrik'], ['Günəş enerjili', 'gunes'],
                    ])]),
                    $f('D07', 'gerginlik', $n('Gərginlik', 'Напряжение', 'Voltage'), 'decimal', ['unit' => 'V', 'when' => $when('kart-qd07-idareetme', 'elektrik', 'gunes')]),
                    $f('D07', 'protokol', $n('İdarəetmə protokolu və uyğun modul', 'Протокол управления и модуль', 'Control protocol and module'), 'text', [
                        'when' => $when('kart-qd07-idareetme', 'elektrik', 'gunes'),
                    ] + $hint('Məs. io-homecontrol, KNX modulu.', 'Напр. io-homecontrol, модуль KNX.', 'E.g. io-homecontrol, a KNX module.')),
                    $kit('D07', [['Pəncərə', 'pencere'], ['Montaj haşiyəsi', 'hasiye'], ['İzolyasiya dəsti', 'izolyasiya'], ['İdarəetmə', 'idareetme'], ['Tor', 'tor']])
                        + $hint('Yalnız qiymətə daxil olanlar; montaj haşiyəsinin daxil olub-olmaması göstərilir.', 'Только то, что входит в цену; входит ли оклад.', 'Only what the price includes; say whether the flashing is.'),
                    ...$service,
                    ...$guarantee,
                ]),
            ],
        ],

        // ════ 3. Şüşələmə və arakəsmələr (D08–D09) ═══════════════════════════
        [
            'slug' => 'suseleme',
            'name' => $n('Şüşələmə və arakəsmələr', 'Остекление и перегородки', 'Glazing and partitions'),
            'classes' => [
                'balkon-ve-terras-suselenmesi' => $card('D08', $n('Balkon və terras şüşələnməsi', 'Остекление балконов и террас', 'Balcony and terrace glazing'), ['set', 'm2'], [
                    $f('D08', 'sistem', $n('Sistem', 'Система', 'System'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Çərçivəli', 'cerciveli'], ['Çərçivəsiz', 'cercivesiz'],
                    ])]),
                    $f('D08', 'acilma', $n('Açılma', 'Открывание', 'Opening'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Sabit', 'sabit'], ['Sürüşən', 'surusen'], ['Qatlanan', 'qatlanan'], ['Sürüşüb-yığılan', 'surusub-yigilan'],
                    ]), $other()]]),
                    $f('D08', 'istilik', $n('İstilik həlli', 'Тепловое решение', 'Thermal solution'), 'dropdown', ['options' => $opts([
                        ['Termik ayırmalı', 'termik'], ['Termik ayırmasız', 'termiksiz'], ['Çərçivəsiz', 'cercivesiz'],
                    ])] + $hint('Çərçivəsiz sistem avtomatik isti sistem sayılmır; göstəricilər yalnız sənədlə.', 'Безрамная система не считается тёплой; показатели — только по документу.', 'A frameless system is not a warm system; ratings only with a document.')),
                    ...$sizes(),
                    ...$leaves,
                    ...$glass(),
                    $req($profile, false),
                    $req($profileSystem, false),
                    $count('D08', 'rels-sayi', $n('Rels sayı', 'Количество направляющих', 'Number of tracks')),
                    ...$finish(),
                    $scheme(true, $n('Yığılma sxemi: qanadların yığılma tərəfi və açılış sxemi. Fayl və ya link.', 'Схема сборки створок и открывания. Файл или ссылка.', 'Stacking drawing: stacking side and opening layout. File or link.')),
                    $f('D08', 'alt-rels', $n('Alt rels', 'Нижняя направляющая', 'Bottom track'), 'dropdown', ['options' => $opts([
                        ['Səthüstü', 'sethustu'], ['Döşəməyə inteqrasiya', 'inteqrasiya'],
                    ])]),
                    $f('D08', 'alt-rels-hundurluyu', $n('Alt relsin hündürlüyü', 'Высота нижней направляющей', 'Bottom track height'), 'decimal', ['unit' => 'mm']),
                    ...$verified,
                    $kit('D08', [
                        ['Şüşə', 'suse'], ['Profil', 'profil'], ['Rels', 'rels'], ['Diyircək', 'diyircek'], ['Kilidləmə', 'kilidleme'], ['Kipləşdirici', 'kiplesdirici'],
                    ]),
                    ...$service,
                    ...$guarantee,
                ]),
                'interyer-suse-arakesmeleri' => $card('D09', $n('İnteryer şüşə arakəsmələri', 'Интерьерные стеклянные перегородки', 'Interior glass partitions'), ['set', 'm2'], [
                    $f('D09', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Sabit', 'sabit'], ['Sürüşən', 'surusen'], ['Qatlanan', 'qatlanan'], ['Kombinə', 'kombine'],
                    ])] + $hint('Duş arakəsmələri santexnika bölməsindədir.', 'Душевые перегородки — в разделе сантехники.', 'Shower screens are in the plumbing section.')),
                    $f('D09', 'konstruksiya', $n('Konstruksiya', 'Конструкция', 'Construction'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Çərçivəli', 'cerciveli'], ['Çərçivəsiz', 'cercivesiz'],
                    ])]),
                    ['required' => true, 'when' => $when('kart-qd09-konstruksiya', 'cerciveli')] + $profile,
                    $req($profileSystem, false),
                    ...$sizes(false),
                    $count('D09', 'modul-sayi', $n('Modul sayı', 'Количество модулей', 'Number of modules')),
                    $f('D09', 'modul-enleri', $n('Modulların eni', 'Ширина модулей', 'Module widths'), 'text')
                        + $hint('mm; qapının yeri «Sxem»də.', 'мм; место двери — на «Схеме».', 'mm; the door position goes on «Drawing».'),
                    ...$glass(true),
                    ...$finish(),
                    $f('D09', 'qapi', $n('Qapı', 'Дверь', 'Door'), 'dropdown', ['filter' => true, 'options' => $opts([['Yoxdur', 'yoxdur'], ['Var', 'var']])]),
                    $count('D09', 'qapi-sayi', $n('Qapı sayı', 'Количество дверей', 'Number of doors'), ['when' => $when('kart-qd09-qapi', 'var')]),
                    ['when' => $when('kart-qd09-qapi', 'var'), 'filter' => false] + $doorOpening,
                    ['when' => $when('kart-qd09-qapi', 'var')] + $leafSize,
                    ...$sound,
                    $f('D09', 'berkitme', $n('Bərkitmə', 'Крепление', 'Fixing'), 'multiselect', ['options' => $opts([['Döşəmə', 'doseme'], ['Tavan', 'tavan'], ['Divar', 'divar']])]),
                    $scheme(false, $n('Modullar, qapının yeri və montaj sxemi. Fayl və ya link.', 'Модули, место двери и схема монтажа. Файл или ссылка.', 'Modules, door position and fixing drawing. File or link.')),
                    $kit('D09', [['Şüşə', 'suse'], ['Profil', 'profil'], ['Qapı', 'qapi'], ['Furnitura', 'furnitura'], ['Kipləşdirici', 'kiplesdirici']])
                        + $hint('Yalnız qiymətə daxil olanlar; qiymətin qapını əhatə edib-etmədiyi göstərilir.', 'Только то, что входит в цену; входит ли дверь.', 'Only what the price includes; say whether the door is.'),
                    ...$service,
                    ...$guarantee,
                ]),
            ],
        ],

        // ════ 4. Komplektləşdiricilər və furnitura (D10_1–D12) ═══════════════
        [
            'slug' => 'komplektlesdiriciler-ve-furnitura',
            'name' => $n('Komplektləşdiricilər və furnitura', 'Комплектующие и фурнитура', 'Components and hardware'),
            'classes' => [
                'qapi-destekleri' => $card('D10_1', $n('Qapı dəstəkləri', 'Дверные ручки', 'Door handles'), ['piece', 'pair', 'set'], [
                    $f('D10_1', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Basmalı', 'basmali'], ['Sabit tutacaq', 'sabit-tutacaq'], ['Topuz', 'topuz'], ['İçəri gömülən', 'gomulen'],
                    ])] + $hint('Mebel dəstəkləri mebel bölməsindədir.', 'Мебельные ручки — в разделе мебели.', 'Furniture handles are in the furniture section.')),
                    $hwMaterial,
                    ...$hwColor,
                    $f('D10_1', 'uygun-qapi', $n('Uyğun qapı', 'Для двери', 'Door type'), 'multiselect', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Daxili', 'daxili'], ['Giriş', 'giris'], ['Şüşə', 'suse'],
                    ])]),
                    ...$doorThickness(true),
                    $f('D10_1', 'mil', $n('Kvadrat mil ölçüsü', 'Размер квадрата', 'Spindle size'), 'decimal', ['unit' => 'mm', 'required' => true,
                        'when' => $when('kart-qd10-1-nov', 'basmali', 'topuz')]),
                    $f('D10_1', 'oxlararasi', $n('Oxlararası məsafə', 'Межосевое расстояние', 'Centre distance'), 'decimal', ['unit' => 'mm', 'required' => true,
                        'when' => $when('kart-qd10-1-nov', 'sabit-tutacaq')]),
                    $f('D10_1', 'vint-arasi', $n('Vintlərarası məsafə', 'Расстояние между винтами', 'Screw spacing'), 'decimal', ['unit' => 'mm']),
                    $f('D10_1', 'rozet', $n('Rozet və örtük', 'Розетка и накладка', 'Rose and plate'), 'dropdown', ['options' => $opts([
                        ['Ayrı rozet', 'rozet'], ['Uzun lövhə', 'uzun-lovhe'], ['Yoxdur', 'yoxdur'],
                    ])]),
                    $f('D10_1', 'rozet-olcu', $n('Rozet / lövhə ölçüsü', 'Размер розетки / планки', 'Rose / plate size'), 'text', ['when' => $when('kart-qd10-1-rozet', 'rozet', 'uzun-lovhe')]
                        + $hint('mm, məs. Ø52 və ya 240 × 45.', 'мм, напр. Ø52 или 240 × 45.', 'mm, e.g. Ø52 or 240 × 45.')),
                    $f('D10_1', 'kilid-deliyi', $n('Kilid dəliyi', 'Отверстие под замок', 'Keyhole'), 'dropdown', ['options' => $opts([
                        ['Silindr', 'silindr'], ['Adi açar', 'adi-acar'], ['WC', 'wc'], ['Yoxdur', 'yoxdur'],
                    ])]),
                    $f('D10_1', 'merkezlerarasi', $n('Mərkəzlərarası məsafə', 'Межцентровое расстояние', 'Handle-to-keyhole distance'), 'decimal', ['unit' => 'mm',
                        'when' => ['all' => [$when('kart-qd10-1-rozet', 'uzun-lovhe'), $when('kart-qd10-1-kilid-deliyi', 'silindr', 'adi-acar', 'wc')]]]),
                    $direction,
                    $scheme(),
                    $kit('D10_1', [['Dəstək', 'destek'], ['Qarşı dəstək', 'qarsi-destek'], ['Mil', 'mil'], ['Rozet', 'rozet'], ['Bərkidici', 'berkidici']])
                        + $hint('Yalnız qiymətə daxil olanlar. Tək və cüt dəstək satış vahidi ilə fərqlənir.', 'Только то, что входит в цену. Одна ручка и пара — разные единицы продажи.', 'Only what the price includes. Single vs pair is the sales unit.'),
                ]),
                'qapi-menteseleri' => $card('D10_2', $n('Qapı menteşələri', 'Дверные петли', 'Door hinges'), ['piece', 'pair', 'set'], [
                    $f('D10_2', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Adi', 'adi'], ['Gizli', 'gizli'], ['Pivot', 'pivot'], ['Şüşə üçün', 'suse'],
                    ]), $other()]]),
                    $hwMaterial,
                    ...$hwColor,
                    $model,
                    $f('D10_2', 'qapi-materiali', $n('Qapının materialı', 'Материал двери', 'Door material'), 'multiselect', ['required' => true, 'options' => [...$opts([
                        ['Ağac / MDF', 'agac-mdf'], ['Metal', 'metal'], ['Alüminium', 'aluminium'], ['PVC', 'pvc'], ['Şüşə', 'suse'],
                    ]), $other()]]),
                    ...$doorThickness(true),
                    $f('D10_2', 'oturma', $n('Oturma forması', 'Тип притвора', 'Rebate type'), 'text', ['required' => true]
                        + $hint('Məs. falslı, falssız, üst-üstə.', 'Напр. с притвором, без притвора, накладная.', 'E.g. rebated, flush, overlay.')),
                    $load + $hint('kg; istehsalçının menteşə sayı, yerləşmə və qapı ölçüsü şərtləri ilə. Avtomatik bir menteşəyə aid sayılmır.', 'кг; при числе петель, расположении и размере двери по производителю.', 'kg; with the maker’s hinge count, positions and door size.'),
                    $f('D10_2', 'yuk-serti', $n('Yük şərtləri', 'Условия нагрузки', 'Load conditions'), 'text')
                        + $hint('Menteşə sayı, yerləşmə və qapının ölçüsü.', 'Число петель, расположение и размер двери.', 'Number of hinges, positions and door size.'),
                    $f('D10_2', 'olculer', $n('Menteşənin ölçüləri', 'Размеры петли', 'Hinge dimensions'), 'text', ['required' => true]
                        + $hint('mm; frezləmə / dəlik sxemi «Sxem»də.', 'мм; схема фрезеровки — в «Схеме».', 'mm; the routing drawing goes in «Drawing».')),
                    $scheme(),
                    $direction,
                    $f('D10_2', 'tenzimleme', $n('Tənzimləmə', 'Регулировка', 'Adjustment'), 'dropdown', ['filter' => true, 'options' => $opts([['Yoxdur', 'yoxdur'], ['2D', '2d'], ['3D', '3d']])]),
                    $f('D10_2', 'bucaq', $n('Açılma bucağı', 'Угол открывания', 'Opening angle'), 'decimal', ['unit' => '°', 'rules' => ['max' => 360]]),
                    $kit('D10_2', [['Menteşə', 'mentese'], ['Örtük', 'ortuk'], ['Vint', 'vint'], ['Montaj hissələri', 'montaj']]),
                ]),
                'qapi-kilidleri-ve-cefteler' => $card('D10_3', $n('Qapı kilidləri və cəftələr', 'Дверные замки и защёлки', 'Door locks and latches'), $ps, [
                    $f('D10_3', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Mexaniki', 'mexaniki'], ['Maqnit cəftə', 'maqnit'], ['Çoxnöqtəli', 'coxnoqteli'], ['Elektromexaniki', 'elektromexaniki'], ['Ağıllı', 'agilli'],
                    ])] + $hint('Yalnız silindr «Kilid silindrləri»ndədir.', 'Отдельный цилиндр — в «Цилиндрах».', 'A cylinder alone goes under «Lock cylinders».')),
                    $f('D10_3', 'montaj', $n('Montaj', 'Монтаж', 'Mounting'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Gömülən', 'gomulen'], ['Səthüstü', 'sethustu'], ['Şüşə üçün', 'suse'],
                    ])]),
                    $model,
                    $f('D10_3', 'funksiya', $n('Funksiya', 'Функция', 'Function'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Açar', 'acar'], ['Silindr', 'silindr'], ['WC', 'wc'], ['Sadə cəftə', 'cefte'], ['Elektron giriş', 'elektron'],
                    ])]),
                    $f('D10_3', 'backset', $n('Backset', 'Бэксет', 'Backset'), 'decimal', ['unit' => 'mm', 'required' => true]),
                    $f('D10_3', 'merkezlerarasi', $n('Mərkəzlərarası məsafə', 'Межцентровое расстояние', 'Centre distance'), 'decimal', ['unit' => 'mm']),
                    $f('D10_3', 'govde', $n('Gövdə və ön lövhə ölçüsü', 'Размер корпуса и лицевой планки', 'Case and faceplate size'), 'text', ['required' => true]
                        + $hint('mm, məs. gövdə 85 × 150, lövhə 235 × 24.', 'мм, напр. корпус 85 × 150, планка 235 × 24.', 'mm, e.g. case 85 × 150, plate 235 × 24.')),
                    $scheme(),
                    $direction,
                    ...$doorThickness(),
                    ...$hwColor,
                    $f('D10_3', 'gerginlik', $n('Gərginlik', 'Напряжение', 'Voltage'), 'decimal', ['unit' => 'V', 'when' => $when('kart-qd10-3-nov', 'elektromexaniki', 'agilli')]),
                    $f('D10_3', 'qidalanma', $n('Qidalanma', 'Питание', 'Power supply'), 'dropdown', ['when' => $when('kart-qd10-3-nov', 'elektromexaniki', 'agilli'), 'options' => [...$opts([
                        ['Şəbəkə', 'sebeke'], ['Batareya', 'batareya'], ['Akkumulyator', 'akkumulyator'],
                    ]), $other()]]),
                    $f('D10_3', 'giris', $n('Giriş üsulları', 'Способы доступа', 'Access methods'), 'multiselect', ['when' => $when('kart-qd10-3-nov', 'elektromexaniki', 'agilli'), 'options' => [...$opts([
                        ['Açar', 'acar'], ['Kod', 'kod'], ['Kart / brelok', 'kart'], ['Barmaq izi', 'barmaq-izi'], ['Tətbiq', 'tetbiq'],
                    ]), $other()]]),
                    $f('D10_3', 'protokol', $n('Əlaqə protokolu', 'Протокол связи', 'Connectivity'), 'text', ['when' => $when('kart-qd10-3-nov', 'elektromexaniki', 'agilli')]
                        + $hint('Məs. Wi-Fi, Bluetooth, Zigbee.', 'Напр. Wi-Fi, Bluetooth, Zigbee.', 'E.g. Wi-Fi, Bluetooth, Zigbee.')),
                    $f('D10_3', 'sluz', $n('Şlüz tələb olunur', 'Нужен шлюз', 'Gateway needed'), 'dropdown', ['when' => $when('kart-qd10-3-nov', 'elektromexaniki', 'agilli'),
                        'options' => [...$opts([['Bəli', 'beli'], ['Xeyr', 'xeyr']]), $ns]]),
                    $kit('D10_3', [['Kilid', 'kilid'], ['Qarşılıq', 'qarsiliq'], ['Silindr', 'silindr'], ['Açar', 'acar'], ['Dəstək', 'destek'], ['Batareya', 'batareya']]),
                    ...$guarantee,
                ]),
                'kilid-silindrleri' => $card('D10_4', $n('Kilid silindrləri', 'Цилиндры замков', 'Lock cylinders'), $ps, [
                    $f('D10_4', 'profil', $n('Profil', 'Профиль', 'Profile'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Avroprofil', 'avroprofil'], ['Oval', 'oval'], ['Dairəvi', 'dairevi'],
                    ]), $other()]]),
                    $f('D10_4', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['İkitərəfli açar', 'ikiterefli'], ['Açar-düymə', 'acar-duyme'], ['Yarımsilindr', 'yarim'],
                    ])]),
                    $f('D10_4', 'a', $n('A ölçüsü', 'Размер A', 'Size A'), 'decimal', ['unit' => 'mm', 'required' => true]
                        + $hint('Bərkitmə vintinin mərkəzindən A tərəfinə qədər.', 'От центра крепёжного винта до стороны A.', 'From the fixing screw centre to side A.')),
                    $f('D10_4', 'b', $n('B ölçüsü', 'Размер B', 'Size B'), 'decimal', ['unit' => 'mm', 'required' => true, 'when' => $when('kart-qd10-4-nov', 'ikiterefli', 'acar-duyme')]),
                    $scheme(false, $n('A / B istiqamət sxemi. Fayl və ya link.', 'Схема сторон A / B. Файл или ссылка.', 'A / B side drawing. File or link.')),
                    $model,
                    ...$hwColor,
                    $f('D10_4', 'acar-sistemi', $n('Açar sistemi', 'Система ключей', 'Key system'), 'dropdown', ['options' => $opts([
                        ['Fərdi', 'ferdi'], ['Eyni açarlı', 'eyni-acarli'], ['Master sistem', 'master'],
                    ])]),
                    $count('D10_4', 'acar-sayi', $n('Açar sayı', 'Количество ключей', 'Number of keys')),
                    $f('D10_4', 'qoruma', $n('Təsdiqlənmiş qoruma', 'Подтверждённая защита', 'Certified protection'), 'multiselect', ['options' => [...$opts([
                        ['Qazmaya qarşı', 'qazma'], ['Qırmaya qarşı', 'qirma'], ['Bampinqə qarşı', 'bamping'], ['Açar surətinin qorunması', 'acar-sureti'],
                    ]), $other(), $noneX('Göstərilməyib', 'gosterilmeyib')]] + $hint('Yalnız sənədə görə.', 'Только по документу.', 'Only per a document.')),
                    $f('D10_4', 'sinif', $n('Qoruma sinfi', 'Класс защиты', 'Security class'), 'text', ['when' => $when('kart-qd10-4-qoruma', 'qazma', 'qirma', 'bamping', 'acar-sureti', 'diger')]
                        + $hint('Sənəddəki kimi, məs. EN 1303 grade 6.', 'Как в документе, напр. EN 1303 grade 6.', 'As in the document, e.g. EN 1303 grade 6.')),
                    $doc + ['required' => true, 'when' => $when('kart-qd10-4-qoruma', 'qazma', 'qirma', 'bamping', 'acar-sureti', 'diger')],
                    $kit('D10_4', [['Silindr', 'silindr'], ['Açarlar', 'acarlar'], ['Vint', 'vint'], ['Təhlükəsizlik kartı', 'kart']]),
                ]),
                'qapi-baglayicilari' => $card('D10_5', $n('Qapı bağlayıcıları', 'Дверные доводчики', 'Door closers'), $ps, [
                    $f('D10_5', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Səthüstü', 'sethustu'], ['Gizli', 'gizli'], ['Döşəmə tipli', 'doseme'],
                    ])]),
                    $f('D10_5', 'qol', $n('Qol növü', 'Тип тяги', 'Arm type'), 'dropdown', ['options' => [...$opts([['Dirsəkli', 'dirsekli'], ['Sürüşmə relsli', 'rels']]), $na]]),
                    $f('D10_5', 'guc', $n('Bağlama gücü', 'Усилие закрывания', 'Closing force'), 'text', ['required' => true, 'filter' => true]
                        + $hint('İstehsalçının EN ölçüsü və ya güc sinfi, məs. EN 2–4.', 'Размер EN или класс усилия, напр. EN 2–4.', 'EN size or force class, e.g. EN 2–4.')),
                    $f('D10_5', 'guc-tenzim', $n('Güc', 'Усилие', 'Force'), 'dropdown', ['options' => $opts([['Sabit', 'sabit'], ['Tənzimlənən', 'tenzimlenen']])]),
                    $f('D10_5', 'max-en', $n('Qapının maksimum eni', 'Макс. ширина двери', 'Max door width'), 'decimal', ['unit' => 'mm', 'required' => true]),
                    $load + $hint('Qapının maksimum çəkisi, kg; seçilmiş montaj sxemi ilə.', 'Макс. масса двери, кг; для выбранной схемы монтажа.', 'Max door weight, kg; for the chosen mounting.'),
                    $direction,
                    $model,
                    $f('D10_5', 'montaj-olculeri', $n('Montaj ölçüləri', 'Монтажные размеры', 'Mounting dimensions'), 'text')
                        + $hint('Gövdə və montaj dəlikləri, mm; sxem «Sxem»də.', 'Корпус и монтажные отверстия, мм; схема — в «Схеме».', 'Body and fixing holes, mm; drawing in «Drawing».'),
                    $scheme(),
                    $f('D10_5', 'funksiyalar', $n('Funksiyalar', 'Функции', 'Functions'), 'multiselect', ['options' => $opts([
                        ['Sürət tənzimləmə', 'suret'], ['Son bağlama', 'son-baglama'], ['Açılma əyləci', 'eylec'], ['Gecikmə', 'gecikme'], ['Açıq saxlama', 'aciq-saxlama'],
                    ])] + $hint('Yanğın / tüstü qapısında uyğunluq ayrıca sənədlə.', 'Для противопожарной двери — отдельный документ.', 'For a fire / smoke door, a separate document.')),
                    ...$hwColor,
                    $kit('D10_5', [['Bağlayıcı', 'baglayici'], ['Qol və ya rels', 'qol-rels'], ['Montaj lövhəsi', 'lovhe'], ['Örtük', 'ortuk']]),
                    ...$guarantee,
                ]),
                'qapi-stoporlari-ve-saxlayicilari' => $card('D10_6', $n('Qapı stoporları və saxlayıcıları', 'Дверные стопоры и фиксаторы', 'Door stops and holders'), $ps, [
                    $f('D10_6', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Stopor', 'stopor'], ['Maqnit saxlayıcı', 'maqnit'], ['Mexaniki saxlayıcı', 'mexaniki'],
                    ])]),
                    $f('D10_6', 'montaj', $n('Montaj', 'Монтаж', 'Mounting'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Döşəmə', 'doseme'], ['Divar', 'divar'], ['Qapı', 'qapi'], ['Gizli', 'gizli'],
                    ])]),
                    $f('D10_6', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Metal', 'metal'], ['Plastik', 'plastik'], ['Rezin', 'rezin'], ['Kombinə', 'kombine'],
                    ]), $other()]]),
                    ...$hwColor,
                    $width,
                    $depth,
                    $height,
                    $f('D10_6', 'berkitme', $n('Bərkitmə', 'Крепление', 'Fixing'), 'dropdown', ['options' => [...$opts([['Vint', 'vint'], ['Yapışdırma', 'yapisdirma']]), $other()]]),
                    $model,
                    $kit('D10_6', [['Stopor', 'stopor'], ['Qarşılıq', 'qarsiliq'], ['Vint', 'vint'], ['Dübel', 'dubel']]),
                ]),
                'qapi-qutulari' => $card('D10_7', $n('Qapı qutuları', 'Дверные коробки', 'Door frames'), ['set', 'piece', 'lm'], [
                    $f('D10_7', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Adi', 'adi'], ['Gizli', 'gizli'], ['Teleskopik', 'teleskopik'], ['Şüşə qapı üçün', 'suse'],
                    ])]),
                    $f('D10_7', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Ağac', 'agac'], ['MDF', 'mdf'], ['Alüminium', 'aluminium'], ['Polad', 'polad'],
                    ]), $other()]]),
                    $model,
                    $req($leafSize) + $hint('Uyğun polotnonun eni × hündürlüyü.', 'Ширина × высота подходящего полотна.', 'Width × height of the matching leaf.'),
                    $req($leafThickness),
                    $req($wall) + ['rules' => ['lte' => 'kart-qp-divar-qalinligi-maks']] + $hint('Minimum.', 'Минимум.', 'Minimum.'),
                    $req($wallMax),
                    $direction,
                    ...$finish(),
                    $f('D10_7', 'profil-olcusu', $n('Profil ölçüsü', 'Размер профиля', 'Profile size'), 'text')
                        + $hint('Kəsik və element uzunluğu, mm.', 'Сечение и длина элемента, мм.', 'Section and element length, mm.'),
                    $kit('D10_7', [['Qutu', 'qutu'], ['Kipləşdirici', 'kiplesdirici'], ['Menteşə yuvası', 'mentese-yuvasi'], ['Qarşılıq', 'qarsiliq'], ['Montaj hissələri', 'montaj']])
                        + $hint('Yalnız qiymətə daxil olanlar. Qiymət bir profilə, metrə və ya tam qutu dəstinə aiddir.', 'Только то, что входит в цену. Цена — за профиль, метр или полный комплект.', 'Only what the price includes. The price is per profile, metre or full frame set.'),
                ]),
                'qapi-hasiyeleri-ve-elave-paneller' => $card('D10_8', $n('Qapı haşiyələri və əlavə panellər', 'Дверные наличники и доборы', 'Door architraves and extensions'), ['piece', 'lm', 'set'], [
                    $f('D10_8', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Haşiyə', 'hasiye'], ['Qutu genişləndirici panel', 'genislendirici'], ['Dekorativ tamamlayıcı', 'dekorativ'],
                    ])]),
                    $f('D10_8', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Ağac', 'agac'], ['MDF', 'mdf'], ['PVC', 'pvc'], ['Alüminium', 'aluminium'], ['Polad', 'polad'],
                    ]), $other()]]),
                    ...$finish(false),
                    $length,
                    $width,
                    $thickness,
                    $f('D10_8', 'montaj', $n('Montaj', 'Монтаж', 'Mounting'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Teleskopik', 'teleskopik'], ['Səthüstü', 'sethustu'], ['Yivli', 'yivli'],
                    ]), $other()]]),
                    $model,
                    $count('D10_8', 'destde-say', $n('Dəstdə say', 'Количество в комплекте', 'Pieces per set')),
                    $f('D10_8', 'teref', $n('Dəst neçə tərəf üçündür', 'Комплект на сколько сторон', 'Set covers'), 'dropdown', ['options' => $opts([
                        ['Bir tərəf', 'bir'], ['İki tərəf', 'iki'],
                    ])] + $hint('Dəstlə satılanda.', 'При продаже комплектом.', 'When sold as a set.')),
                    $kit('D10_8', [['Haşiyə', 'hasiye'], ['Panel', 'panel'], ['Birləşdirici', 'birlesdirici']]),
                ]),
                'surusen-ve-qatlanan-qapi-mexanizmleri' => $card('D10_9', $n('Sürüşən və qatlanan qapı mexanizmləri', 'Механизмы раздвижных и складных дверей', 'Sliding and folding door gear'), ['set'], [
                    $f('D10_9', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Səthüstü sürüşmə', 'sethustu'], ['Divariçi kaset', 'kaset'], ['Qatlanan', 'qatlanan'],
                    ]), $other()]]),
                    $model,
                    $f('D10_9', 'qanad-material', $n('Qanad materialı', 'Материал створки', 'Leaf material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Ağac-MDF', 'agac-mdf'], ['Şüşə', 'suse'], ['Metal çərçivəli', 'metal'],
                    ]), $other()]]),
                    $load + $hint('Bir qanadın maksimum çəkisi, kg.', 'Макс. масса одной створки, кг.', 'Max weight of one leaf, kg.'),
                    $req($leafSize) + $hint('Bir qanadın maksimum eni × hündürlüyü.', 'Макс. ширина × высота одной створки.', 'Max width × height of one leaf.'),
                    ...$doorThickness(true),
                    $leafCount,
                    $f('D10_9', 'rels-uzunlugu', $n('Rels uzunluğu', 'Длина направляющей', 'Track length'), 'decimal', ['unit' => 'mm', 'required' => true]),
                    $f('D10_9', 'montaj', $n('Montaj', 'Монтаж', 'Mounting'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Divar', 'divar'], ['Tavan', 'tavan'], ['Döşəmə dayaqlı', 'doseme'], ['Divariçi', 'divarici'],
                    ])]),
                    $f('D10_9', 'funksiyalar', $n('Funksiyalar', 'Функции', 'Functions'), 'multiselect', ['options' => $opts([
                        ['Yumşaq bağlanma', 'yumsaq'], ['Sinxron hərəkət', 'sinxron'], ['Teleskopik', 'teleskopik'], ['Sabitləmə', 'sabitleme'],
                    ])]),
                    ...$hwColor,
                    $kit('D10_9', [
                        ['Rels', 'rels'], ['Diyircəklər', 'diyircekler'], ['Bələdçi', 'beledci'], ['Stopor', 'stopor'], ['Yumşaq bağlayıcı', 'yumsaq'],
                        ['Kaset', 'kaset'], ['Qapı polotnosu', 'polotno'],
                    ]) + $hint('Yalnız qiymətə daxil olanlar; polotno yalnız seçilibsə daxildir.', 'Только то, что входит в цену; полотно — только если отмечено.', 'Only what the price includes; the leaf only if ticked.'),
                    ...$guarantee,
                ]),
                'qapi-astanalari-ve-kiplesdiricileri' => $card('D10_10', $n('Qapı astanaları və kipləşdiriciləri', 'Дверные пороги и уплотнители', 'Door thresholds and seals'), ['piece', 'lm', 'roll', 'set'], [
                    $f('D10_10', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Astana', 'astana'], ['Avtomatik enən kipləşdirici', 'avtomatik'], ['Perimetr kipləşdiricisi', 'perimetr'],
                    ])] + $hint('Pəncərə kipləşdiricisi «Pəncərə kipləşdiriciləri»ndədir.', 'Оконный уплотнитель — в «Оконных уплотнителях».', 'Window seals are a separate class.')),
                    $f('D10_10', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Alüminium', 'aluminium'], ['Polad', 'polad'], ['EPDM', 'epdm'], ['TPE', 'tpe'], ['Silikon', 'silikon'], ['Fırça', 'firca'], ['Kombinə', 'kombine'],
                    ]), $other()]]),
                    ...$hwColor,
                    $length,
                    $f('D10_10', 'kesik', $n('Kəsik və iş boşluğu', 'Сечение и рабочий зазор', 'Section and working gap'), 'text', ['required' => true]
                        + $hint('mm.', 'мм.', 'mm.')),
                    $model,
                    $f('D10_10', 'montaj', $n('Montaj', 'Монтаж', 'Mounting'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Yivə', 'yive'], ['Səthə', 'sethe'], ['Yapışqanlı', 'yapisqanli'], ['Gömülən', 'gomulen'],
                    ])]),
                    $f('D10_10', 'xususi', $n('Xüsusi uyğunluq', 'Специальное соответствие', 'Special rating'), 'multiselect', ['options' => [...$opts([
                        ['Akustik', 'akustik'], ['Tüstü', 'tustu'], ['Yanğın', 'yangin'],
                    ]), $noneX('Göstərilməyib', 'gosterilmeyib')]] + $hint('Yalnız təsdiqlənmiş model və sənədə görə.', 'Только по подтверждённой модели и документу.', 'Only per an approved model and document.')),
                    $doc + ['required' => true, 'when' => $when('kart-qd10-10-xususi', 'akustik', 'tustu', 'yangin')],
                    $kit('D10_10', [['Profil', 'profil'], ['Kipləşdirici', 'kiplesdirici'], ['Ucluq', 'ucluq'], ['Bərkitmə', 'berkitme']]),
                ]),
                'qapi-gozlukleri' => $card('D10_11', $n('Qapı gözlükləri', 'Дверные глазки', 'Door viewers'), $ps, [
                    $f('D10_11', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([['Optik', 'optik'], ['Rəqəmsal', 'reqemsal']])]
                        + $hint('Domofon və ayrıca kamera zəif cərəyan sistemlərindədir.', 'Домофоны и отдельные камеры — в слаботочных системах.', 'Intercoms and cameras are in low-voltage systems.')),
                    ...$doorThickness(true),
                    $f('D10_11', 'desik', $n('Montaj dəliyinin diametri', 'Диаметр монтажного отверстия', 'Mounting hole diameter'), 'decimal', ['unit' => 'mm', 'required' => true]),
                    $f('D10_11', 'bucaq', $n('Baxış bucağı', 'Угол обзора', 'Viewing angle'), 'decimal', ['unit' => '°', 'rules' => ['max' => 360]]),
                    $mpkMaterial,
                    ...$hwColor,
                    $f('D10_11', 'ekran', $n('Ekran', 'Экран', 'Screen'), 'decimal', ['unit' => '″', 'when' => $when('kart-qd10-11-nov', 'reqemsal')]),
                    $f('D10_11', 'kamera', $n('Kamera', 'Камера', 'Camera'), 'text', ['when' => $when('kart-qd10-11-nov', 'reqemsal')]
                        + $hint('Çözümlülük və bucaq, məs. 1080p, 120°.', 'Разрешение и угол, напр. 1080p, 120°.', 'Resolution and angle, e.g. 1080p, 120°.')),
                    $f('D10_11', 'yaddas', $n('Yaddaş', 'Память', 'Storage'), 'text', ['when' => $when('kart-qd10-11-nov', 'reqemsal')]
                        + $hint('Məs. microSD 32 GB-a qədər.', 'Напр. microSD до 32 ГБ.', 'E.g. microSD up to 32 GB.')),
                    $f('D10_11', 'qidalanma', $n('Qidalanma', 'Питание', 'Power supply'), 'dropdown', ['when' => $when('kart-qd10-11-nov', 'reqemsal'), 'options' => [...$opts([
                        ['Batareya', 'batareya'], ['Akkumulyator', 'akkumulyator'], ['Şəbəkə', 'sebeke'],
                    ]), $other()]]),
                    $kit('D10_11', [['Gözlük', 'gozluk'], ['Ekran', 'ekran'], ['Qidalanma', 'qidalanma'], ['Montaj hissələri', 'montaj']]),
                    ...$guarantee,
                ]),
                'antipanik-cixis-mexanizmleri' => $card('D10_12', $n('Antipanik çıxış mexanizmləri', 'Антипаника', 'Panic exit devices'), $ps, [
                    $f('D10_12', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Basmalı bar', 'basmali'], ['Toxunma barı', 'toxunma'], ['Təcili çıxış dəstəyi', 'destek'],
                    ])]),
                    $model,
                    $f('D10_12', 'qurulus', $n('Qapı quruluşu', 'Конструкция двери', 'Door layout'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Birqanadlı', 'bir'], ['İkiqanadlı — aktiv qanad', 'iki-aktiv'], ['İkiqanadlı — passiv qanad', 'iki-passiv'],
                    ])]),
                    $req($leafSize) + $hint('Qanadın maksimum eni × hündürlüyü, istehsalçı üzrə.', 'Макс. ширина × высота створки по производителю.', 'Max leaf width × height per the maker.'),
                    ...$doorThickness(true),
                    $load + $hint('Qanadın maksimum çəkisi, kg.', 'Макс. масса створки, кг.', 'Max leaf weight, kg.'),
                    $direction,
                    $f('D10_12', 'kilidleme', $n('Kilidləmə', 'Запирание', 'Locking'), 'dropdown', ['options' => $opts([
                        ['Yan', 'yan'], ['Yuxarı-aşağı', 'yuxari-asagi'], ['Çoxnöqtəli', 'coxnoqteli'],
                    ])]),
                    $req($doc) + $hint('Uyğunluq sənədi: model üzrə standart və təsdiq; yanğın / tüstü qapısında ayrıca uyğunluq. Fayl və ya link.', 'Документ о соответствии: стандарт и подтверждение по модели. Файл или ссылка.', 'Conformity document: standard and approval for the model. File or link.'),
                    ...$hwColor,
                    $kit('D10_12', [['Bar', 'bar'], ['Kilid', 'kilid'], ['Çubuqlar', 'cubuqlar'], ['Qarşılıqlar', 'qarsiliqlar'], ['Xarici dəstək', 'xarici-destek']]),
                    ...$guarantee,
                ]),
                'pencere-destekleri' => $card('D11_1', $n('Pəncərə dəstəkləri', 'Оконные ручки', 'Window handles'), $ps, [
                    $f('D11_1', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Adi', 'adi'], ['Açarlı', 'acarli'], ['Düyməli', 'duymeli'], ['Çıxarılan', 'cixarilan'],
                    ])]),
                    $f('D11_1', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Alüminium', 'aluminium'], ['Sink ərintisi', 'sink'], ['Polad', 'polad'], ['Plastik', 'plastik'],
                    ]), $other()]]),
                    ...$hwColor,
                    $f('D11_1', 'mil', $n('Kvadrat mil ölçüsü', 'Размер квадрата', 'Spindle size'), 'decimal', ['unit' => 'mm', 'required' => true]),
                    $f('D11_1', 'mil-uzunluq', $n('Milin çıxıntı uzunluğu', 'Длина штифта', 'Spindle length'), 'decimal', ['unit' => 'mm', 'required' => true]),
                    $f('D11_1', 'vint-arasi', $n('Vintlərarası məsafə', 'Расстояние между винтами', 'Screw spacing'), 'decimal', ['unit' => 'mm', 'required' => true]),
                    $f('D11_1', 'vint', $n('Vint ölçüsü', 'Размер винта', 'Screw size'), 'text', ['required' => true] + $hint('Məs. M5 × 40.', 'Напр. M5 × 40.', 'E.g. M5 × 40.')),
                    $model,
                    $direction,
                    $kit('D11_1', [['Dəstək', 'destek'], ['Vint', 'vint'], ['Açar', 'acar']]),
                ]),
                'pencere-acilma-mexanizmleri-ve-menteseleri' => $card('D11_2', $n('Pəncərə açılma mexanizmləri və menteşələri', 'Оконные механизмы и петли', 'Window gear and hinges'), $ps, [
                    $f('D11_2', 'hisse', $n('Hissə növü', 'Тип детали', 'Part type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Tam furnitura dəsti', 'tam-dest'], ['Menteşə', 'mentese'], ['Qayçı', 'qayci'], ['Ötürücü', 'oturucu'], ['Qarşılıq', 'qarsiliq'],
                    ]), $other()]]),
                    $windowOpening,
                    $model,
                    $f('D11_2', 'profil', $n('Profil uyğunluğu', 'Совместимый профиль', 'Profile compatibility'), 'multiselect', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['PVC', 'pvc'], ['Ağac', 'agac'], ['Alüminium', 'aluminium'],
                    ])]),
                    $f('D11_2', 'yiv', $n('Profil yivi və sistem', 'Паз профиля и система', 'Profile groove and system'), 'text', ['required' => true]
                        + $hint('Məs. yiv 12/20-9, sistem adı.', 'Напр. паз 12/20-9, система.', 'E.g. groove 12/20-9, system name.')),
                    $f('D11_2', 'en-min', $n('Qanadın eni, minimum', 'Ширина створки, мин.', 'Sash width, min'), 'decimal', ['unit' => 'mm', 'required' => true, 'rules' => ['lte' => 'kart-qd11-2-en-maks']]),
                    $f('D11_2', 'en-maks', $n('Qanadın eni, maksimum', 'Ширина створки, макс.', 'Sash width, max'), 'decimal', ['unit' => 'mm', 'required' => true]),
                    $f('D11_2', 'hundurluk-min', $n('Qanadın hündürlüyü, minimum', 'Высота створки, мин.', 'Sash height, min'), 'decimal', ['unit' => 'mm', 'required' => true,
                        'rules' => ['lte' => 'kart-qd11-2-hundurluk-maks']]),
                    $f('D11_2', 'hundurluk-maks', $n('Qanadın hündürlüyü, maksimum', 'Высота створки, макс.', 'Sash height, max'), 'decimal', ['unit' => 'mm', 'required' => true]),
                    $load + $hint('kg; istehsalçının komplekt və ölçü şərtləri ilə.', 'кг; при условиях производителя.', 'kg; with the maker’s set and size conditions.'),
                    $direction,
                    $f('D11_2', 'kod', $n('Hissə kodu', 'Код детали', 'Part code'), 'text'),
                    $scheme(false, $n('Montaj sxemi: ölçülər. Fayl və ya link.', 'Монтажная схема с размерами. Файл или ссылка.', 'Mounting drawing with sizes. File or link.')),
                    ...$hwColor,
                    $kit('D11_2', [
                        ['Mexanizm', 'mexanizm'], ['Menteşə', 'mentese'], ['Qayçı', 'qayci'], ['Ötürücü', 'oturucu'], ['Qarşılıq', 'qarsiliq'], ['Bərkidici', 'berkidici'],
                    ]),
                ]),
                'pencere-mehdudlasdiricilari-ve-usaq-kilidleri' => $card('D11_3', $n('Pəncərə məhdudlaşdırıcıları və uşaq kilidləri', 'Оконные ограничители и детские замки', 'Window restrictors and child locks'), $ps, [
                    $f('D11_3', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Açılma məhdudlaşdırıcısı', 'mehdudlasdirici'], ['Troslu kilid', 'troslu'], ['Əlavə kilid', 'elave-kilid'],
                    ]), $other()]] + $hint('Uşaq təhlükəsizliyi yalnız istehsalçının təsdiq etdiyi təyinat üzrə.', 'Детская безопасность — только по назначению от производителя.', 'Child safety only as the maker states.')),
                    $model,
                    $f('D11_3', 'montaj', $n('Montaj', 'Монтаж', 'Mounting'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Səthüstü', 'sethustu'], ['Gizli', 'gizli'], ['Profilə', 'profile'],
                    ])]),
                    $f('D11_3', 'hedd-novu', $n('Açılma həddi', 'Ограничение открывания', 'Opening limit'), 'dropdown', ['options' => $opts([
                        ['Məsafə, mm', 'mm'], ['Bucaq, dərəcə', 'derece'],
                    ])]),
                    $f('D11_3', 'hedd-mm', $n('Açılma həddi, məsafə', 'Ограничение, расстояние', 'Opening limit, distance'), 'decimal', ['unit' => 'mm', 'when' => $when('kart-qd11-3-hedd-novu', 'mm')]),
                    $f('D11_3', 'hedd-derece', $n('Açılma həddi, bucaq', 'Ограничение, угол', 'Opening limit, angle'), 'decimal', ['unit' => '°', 'rules' => ['max' => 180],
                        'when' => $when('kart-qd11-3-hedd-novu', 'derece')]),
                    $f('D11_3', 'acma', $n('Açma üsulu', 'Способ разблокировки', 'Release'), 'dropdown', ['options' => $opts([
                        ['Açar', 'acar'], ['Düymə', 'duyme'], ['Mexaniki', 'mexaniki'],
                    ])]),
                    $mpkMaterial,
                    ...$hwColor,
                    $f('D11_3', 'olcu', $n('Ölçü və bərkitmə', 'Размер и крепление', 'Size and fixing'), 'text') + $hint('mm; montaj sxemi «Sxem»də.', 'мм; схема — в «Схеме».', 'mm; drawing in «Drawing».'),
                    $scheme(),
                    $kit('D11_3', [['Mexanizm', 'mexanizm'], ['Qarşılıq', 'qarsiliq'], ['Açar', 'acar'], ['Vint', 'vint']]),
                ]),
                'pencere-kiplesdiricileri' => $card('D11_4', $n('Pəncərə kipləşdiriciləri', 'Оконные уплотнители', 'Window seals'), ['lm', 'roll', 'set'], [
                    $f('D11_4', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['EPDM', 'epdm'], ['TPE', 'tpe'], ['Silikon', 'silikon'], ['Fırça', 'firca'],
                    ]), $other()]]),
                    $f('D11_4', 'teyinat', $n('Təyinat', 'Назначение', 'Purpose'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Qanad', 'qanad'], ['Çərçivə', 'cercive'], ['Şüşə', 'suse'], ['Sürüşmə sistemi', 'surusme'],
                    ])]),
                    $model + $hint('Uyğun profil kodu əsas seçim meyarıdır.', 'Код подходящего профиля — главный критерий.', 'The matching profile code is the key criterion.'),
                    $f('D11_4', 'kesik', $n('Kəsik və ölçü', 'Сечение и размер', 'Section and size'), 'text', ['required' => true]
                        + $hint('Profil kəsiyi, yiv ölçüsü və iş boşluğu, mm.', 'Сечение, размер паза и рабочий зазор, мм.', 'Section, groove size and working gap, mm.')),
                    $scheme(),
                    $f('D11_4', 'montaj', $n('Montaj', 'Монтаж', 'Mounting'), 'dropdown', ['required' => true, 'options' => [...$opts([['Yivə', 'yive'], ['Yapışqanlı', 'yapisqanli']]), $other()]]),
                    ...$hwColor,
                    $f('D11_4', 'qablasdirma', $n('Qablaşdırma uzunluğu', 'Длина в упаковке', 'Length per pack'), 'decimal', ['unit' => 'm']),
                    $kit('D11_4', [['Kipləşdirici', 'kiplesdirici'], ['Birləşdirici', 'birlesdirici']]),
                ]),
                'pencerealti' => $card('D11_5', $n('Pəncərə altlıqları', 'Подоконники', 'Window sills'), ['piece', 'lm', 'm2'], [
                    $f('D11_5', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['PVC', 'pvc'], ['Ağac', 'agac'], ['MDF', 'mdf'], ['Təbii daş', 'tebii-das'], ['Süni daş', 'suni-das'], ['Metal', 'metal'],
                    ]), $other()]]),
                    $length,
                    ['filter' => 'secondary'] + $depth + $hint('Dərinlik və qalınlıq olmadan metr qiyməti müqayisə edilmir.', 'Без глубины и толщины цену за метр не сравнить.', 'Per-metre prices compare only with depth and thickness.'),
                    $thickness,
                    ...$finish(false),
                    $f('D11_5', 'kenar', $n('Kənar forması', 'Форма кромки', 'Edge profile'), 'dropdown', ['options' => [...$opts([['Düz', 'duz'], ['Yumru', 'yumru'], ['Profilli', 'profilli']]), $other()]]),
                    $f('D11_5', 'hazirlanma', $n('Hazırlanma', 'Изготовление', 'Production'), 'dropdown', ['options' => $opts([['Hazır ölçü', 'hazir'], ['Sifariş ölçüsü', 'sifaris']])]
                        + $hint('Sifariş ölçüsü yalnız «Sifarişlə» satılır.', 'Изготовление по размеру — только «Под заказ».', 'Made to size sells «On order» only.')),
                    $kit('D11_5', [['Altlıq', 'altliq'], ['Ucluq', 'ucluq'], ['Birləşdirici', 'birlesdirici'], ['Bərkidici', 'berkidici']]),
                    ...$service,
                ], ['on_order_when' => $when('kart-qd11-5-hazirlanma', 'sifaris')]),
                'pencere-su-axidicilari' => $card('D11_6', $n('Pəncərə su axıdıcıları', 'Оконные отливы', 'Window drip sills'), ['piece', 'lm'], [
                    $f('D11_6', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Alüminium', 'aluminium'], ['Sinklənmiş polad', 'sinklenmis-polad'], ['Daş', 'das'],
                    ]), $other()]] + $hint('Xarici pəncərə su axıdıcısı üçün.', 'Для наружного отлива окна.', 'For an exterior window drip.')),
                    $length,
                    ['filter' => 'secondary'] + $depth,
                    $thickness,
                    ...$hwColor,
                    $scheme(false, $n('Profil forması: damcı kənarı, arxa qaldırma və yan ucluq. Fayl və ya link.', 'Профиль: капельник, задний подъём и торцевые заглушки. Файл или ссылка.', 'Profile: drip edge, back upstand and end caps. File or link.')),
                    $model,
                    $kit('D11_6', [['Su axıdıcı', 'su-axidici'], ['Ucluq', 'ucluq'], ['Birləşdirici', 'birlesdirici'], ['Səsazaldıcı altlıq', 'sesazaldici'], ['Bərkidici', 'berkidici']]),
                ]),
                'mansard-pencere-montaj-hasiyeleri' => $card('D11_7', $n('Mansard pəncərəsi montaj haşiyələri', 'Оклады для мансардных окон', 'Roof window flashings'), ['set'], [
                    $model + $hint('Pəncərənin özü «Mansard pəncərələri»ndədir. Uyğun pəncərə modeli / seriyası.', 'Само окно — в «Мансардных окнах». Совместимая модель / серия.', 'The window itself is under «Roof windows». Matching window model / series.'),
                    $f('D11_7', 'olcu-kodu', $n('Pəncərənin ölçü kodu', 'Код размера окна', 'Window size code'), 'text', ['required' => true, 'filter' => true]),
                    $size + $hint('Pəncərənin en × hündürlüyü.', 'Ширина × высота окна.', 'Window width × height.'),
                    ...$roof,
                    ...$pitch,
                    $f('D11_7', 'yerlesme', $n('Yerləşmə', 'Расположение', 'Layout'), 'dropdown', ['required' => true, 'options' => $opts([['Tək pəncərə', 'tek'], ['Kombinə', 'kombine']])]),
                    $f('D11_7', 'araliq', $n('Pəncərələr arası aralıq', 'Расстояние между окнами', 'Gap between windows'), 'decimal', ['unit' => 'mm', 'required' => true,
                        'when' => $when('kart-qd11-7-yerlesme', 'kombine')]),
                    $f('D11_7', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Alüminium', 'aluminium'], ['Mis', 'mis'], ['Sink', 'sink'],
                    ]), $other()]]),
                    ...$hwColor,
                    $kit('D11_7', [['Haşiyə', 'hasiye'], ['İzolyasiya yaxalığı', 'yaxaliq'], ['Drenaj hissəsi', 'drenaj'], ['Bərkitmə', 'berkitme']]),
                ]),
                'agcaqanad-toru' => $card('D12', $n('Ağcaqanad torları', 'Москитные сетки', 'Insect screens'), ['piece', 'm2'], [
                    $f('D12', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Çərçivəli', 'cerciveli'], ['Rulon', 'rulon'], ['Sürüşən', 'surusen'], ['Plisse', 'plisse'], ['Menteşəli', 'menteseli'],
                    ])] + $hint('Jalüz və günəşdən qoruyan torlar tekstil / günəşdən mühafizə bölməsindədir.', 'Жалюзи и солнцезащитные сетки — в текстиле / солнцезащите.', 'Blinds and sun screens are in textiles / sun protection.')),
                    $f('D12', 'teyinat', $n('Təyinat', 'Назначение', 'For'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Pəncərə', 'pencere'], ['Balkon qapısı', 'balkon-qapisi'], ['Terras qapısı', 'terras-qapisi'], ['Mansard pəncərəsi', 'mansard'],
                    ])]),
                    $size + $hint('Açılışın və ya hazır torun ölçüsü — aşağıda seçilir.', 'Размер проёма или готовой сетки — выберите ниже.', 'Opening or finished screen size — choose below.'),
                    $f('D12', 'olcu-aid', $n('Ölçü aiddir', 'Размер относится к', 'Size refers to'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Açılışa', 'acilis'], ['Hazır tora', 'hazir-tor'],
                    ])]),
                    $f('D12', 'cerceve-material', $n('Çərçivə materialı', 'Материал рамки', 'Frame material'), 'dropdown', ['options' => [...$opts([
                        ['Alüminium', 'aluminium'], ['PVC', 'pvc'], ['Çərçivəsiz', 'cercivesiz'],
                    ]), $other()]]),
                    $f('D12', 'tor-material', $n('Tor materialı', 'Материал сетки', 'Mesh material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Şüşə lifli', 'suse-lifli'], ['Polyester', 'polyester'], ['Metal', 'metal'],
                    ]), $other()]]),
                    $f('D12', 'tor-xususiyyet', $n('Tor xüsusiyyəti', 'Свойство сетки', 'Mesh feature'), 'dropdown', ['options' => [...$opts([
                        ['Standart', 'standart'], ['İncə gözlü', 'ince'], ['Heyvana davamlı', 'heyvan'],
                    ]), $other()]]),
                    $color + $hint('Çərçivənin rəngi.', 'Цвет рамки.', 'Frame colour.'),
                    $f('D12', 'tor-reng', $n('Torun rəngi', 'Цвет сетки', 'Mesh colour'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Qara', 'qara'], ['Boz', 'boz'], ['Ağ', 'ag'],
                    ]), $other()]]),
                    $f('D12', 'montaj', $n('Montaj', 'Монтаж', 'Mounting'), 'multiselect', ['required' => true, 'options' => $opts([
                        ['Daxili', 'daxili'], ['Xarici', 'xarici'], ['Açılış daxilində', 'acilis-daxilinde'],
                    ])] + $hint('Profilə uyğun bərkitmə.', 'Крепление под профиль.', 'Fixing to suit the profile.')),
                    $f('D12', 'hereket', $n('Hərəkət istiqaməti', 'Направление движения', 'Movement'), 'dropdown', ['options' => [...$opts([
                        ['Şaquli', 'saquli'], ['Üfüqi', 'ufuqi'], ['Sabit', 'sabit'],
                    ]), $other()]]),
                    $model,
                    $kit('D12', [['Tor', 'tor'], ['Çərçivə', 'cercive'], ['Rels', 'rels'], ['Dəstək', 'destek'], ['Bərkitmə', 'berkitme']]),
                    ...$service,
                ]),
            ],
        ],
    ],

    // Duplicates of a card class: products move over, the old class closes, its links redirect.
    'merge' => [
        'gizli-qapi-invisible' => ['into' => 'otaqlararasi-qapi', 'set' => ['kart-qd01-1-qutu-novu' => 'gizli']],
        'qapi-furniturasi' => 'qapi-destekleri',
        'susepaket-suseleme' => 'balkon-ve-terras-suselenmesi',
    ],

    // Jalüz / rollet / rulon pərdə: «günəşdən mühafizə bölməsinə aiddir».
    'retire_classes' => ['pencere-aksesuarlari-rolletler-jaluzler-rulon-perdeler'],

    'retire_groups' => [
        'giris-qapilari', 'otaqlararasi-qapilar', 'qapi-furniturasi', 'pencere-furniturasi-ve-aksesuarlari', 'xususi-qapi-novleri', 'pencereler-ucun-elave-aksesuarlar',
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
