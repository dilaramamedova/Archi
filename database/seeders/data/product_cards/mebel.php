<?php

/*
|--------------------------------------------------------------------------
| Mebel — block A of «Mebel və mebel furniturası», 36 cards M01_1–M15_2
|--------------------------------------------------------------------------
|
| Source: the client workbook «Mebel və mebel furniturası» (25.11.2026). Format and decisions:
| docs/product-cards.md. The workbook has two independent blocks («2 müstəqil blok. 27 alt
| bölmə»); this file is block A «Mebel» and its 15 numbered «alt bölmə» are the groups
| (sort 0–14). Block B (F01_1–F12_4) is `mebel_furnitura.php` in the same section (its groups
| sort 15–26). The two out-of-scope leftover groups of the old tree go last (27, 28).
|
| Attribute slugs: shared `kart-mb-*`, per-card `kart-m01-1-*` …; `kart-zemanet` /
| `kart-zemanet-muddeti` are the shared definitions of the other files. Nothing is shared
| with the furniture file (`kart-mf-*`).
|
| Kateqoriyalar rules followed here:
| - one main card per product; room and home / office / commercial use are extra filters
|   (`kart-mb-istifade`, `kart-mb-otaq`), not separate classes;
| - sets and modules give their contents, counts and overall size (`kart-mb-dest-terkibi`);
| - «Göstərilməyib» and «Tətbiq edilmir» are separate options;
| - the price is for the chosen variant and unit; a made-to-size price states its sample set
|   and basis (`kart-mb-qiymet-esasi`); delivery, assembly and (made to size) measuring are
|   chosen separately;
| - a load limit is asked only together with what it refers to (seat / shelf / part / whole
|   product); age and load figures come from the maker's data.
|
*/

$n = fn (string $az, string $ru, string $en): array => compact('az', 'ru', 'en');
$o = fn (string $az, string $ru, string $en, array $extra = []): array => ['value' => compact('az', 'ru', 'en')] + $extra;
$a = fn (string $az, array $extra = []): array => ['value' => ['az' => $az]] + $extra;

$P_NAME = $n('Dəqiq adını yazın', 'Укажите точное название', 'Enter the exact name');
$P_CODE = $n('Rəngi və ya kodunu yazın', 'Укажите цвет или код', 'Enter the colour or its code');

$ns = $o('Göstərilməyib', 'Не указано', 'Not specified', ['slug' => 'gosterilmeyib']);
$other = fn (?array $prompt = null, string $az = 'Digər', string $slug = 'diger'): array => $a($az, ['slug' => $slug, 'prompt' => $prompt ?? $P_NAME]);
$none = fn (string $az = 'Daxil deyil'): array => $a($az, ['slug' => 'daxil-deyil', 'exclusive' => true]);
$noneX = fn (string $az = 'Yoxdur', string $slug = 'yoxdur'): array => $a($az, ['slug' => $slug, 'exclusive' => true]);
$na = $a('Tətbiq edilmir', ['slug' => 'tetbiq-edilmir']);
$when = fn (string $attr, string ...$in): array => ['attr' => $attr, 'in' => $in];
$opts = fn (array $list): array => array_map(fn ($pair) => $a($pair[0], ['slug' => $pair[1]]), $list);
$hint = fn (string $az, string $ru, string $en): array => ['hint' => compact('az', 'ru', 'en')];
$req = fn (array $field, bool $required = true): array => ['required' => $required] + $field;
// «Bəli / Xeyr / Göstərilməyib».
$yesNo = [...$opts([['Bəli', 'beli'], ['Xeyr', 'xeyr']]), $ns];

// ── Shared ──────────────────────────────────────────────────────────────────
$warranty = ['slug' => 'kart-zemanet', 'name' => $n('Zəmanət', 'Гарантия', 'Warranty'), 'type' => 'dropdown', 'options' => [
    $o('Var', 'Есть', 'Provided', ['slug' => 'var']), $o('Verilmir', 'Не предоставляется', 'Not provided', ['slug' => 'verilmir']), $ns,
]];
$warrantyMonths = ['slug' => 'kart-zemanet-muddeti', 'name' => $n('Zəmanət müddəti', 'Срок гарантии', 'Warranty period'), 'type' => 'decimal', 'unit' => 'ay',
    'rules' => ['integer' => true], 'when' => ['attr' => 'kart-zemanet', 'in' => ['var']]];
$guarantee = [$warranty, $warrantyMonths];

// «İstehsal variantı* — Standart hazır model / fərdi ölçü ilə istehsal. Stok vəziyyəti ayrıca
// göstərilir» + «Fərdi sifarişdə nümunə komplekti və qiymətin hesablanma əsası göstərilir».
// Made to size is still priced (the sample set's price), so it is neither a quote nor «Sifarişlə».
$variant = [
    ['slug' => 'kart-mb-istehsal-varianti', 'name' => $n('İstehsal variantı', 'Вариант изготовления', 'Production'), 'type' => 'dropdown', 'required' => true,
        'options' => $opts([['Standart hazır model', 'hazir'], ['Fərdi ölçü ilə istehsal', 'ferdi']])]
        + $hint('Hazır model və fərdi ölçü ayrı kateqoriya deyil; mövcudluq (stokda / sifarişlə) ayrıca seçilir.', 'Готовая модель и изготовление по размеру — не разные категории; наличие выбирается отдельно.', 'Ready-made and made-to-size are not separate categories; availability is chosen separately.'),
    ['slug' => 'kart-mb-qiymet-esasi', 'name' => $n('Nümunə komplekti və qiymətin əsası', 'Образец комплектации и основа цены', 'Sample set and price basis'), 'type' => 'textarea',
        'required' => true, 'when' => $when('kart-mb-istehsal-varianti', 'ferdi')]
        + $hint('Qiymət hansı nümunə ölçüsü, material və tərkibə aiddir və necə hesablanır (məs. «2200×950 mm, parça kateqoriyası 2; hər əlavə 100 mm +5%»).', 'К какому образцу (размер, материал, состав) относится цена и как она считается.', 'Which sample size, material and contents the price is for, and how it is calculated.'),
];

// «Çatdırılma və montaj — hər biri ayrıca: qiymətə daxildir / ayrıca ödəniş / təklif edilmir.
// Fərdi sifarişdə ölçmə də ayrıca».
$serviceOpts = $opts([['Qiymətə daxildir', 'daxildir'], ['Ayrıca ödənişlə', 'ayrica'], ['Təklif edilmir', 'teklif-edilmir']]);
$services = [
    ['slug' => 'kart-mb-catdirilma', 'name' => $n('Çatdırılma', 'Доставка', 'Delivery'), 'type' => 'dropdown', 'options' => $serviceOpts],
    ['slug' => 'kart-mb-montaj-xidmeti', 'name' => $n('Yığma və montaj', 'Сборка и монтаж', 'Assembly and installation'), 'type' => 'dropdown', 'options' => $serviceOpts],
    ['slug' => 'kart-mb-olcme', 'name' => $n('Ölçmə', 'Замер', 'Measuring'), 'type' => 'dropdown', 'options' => $serviceOpts,
        'when' => $when('kart-mb-istehsal-varianti', 'ferdi')],
];

// «Qabarit ölçülər* — En × dərinlik × hündürlük, mm».
$dim = fn (string $key, array $name): array => ['slug' => 'kart-mb-'.$key, 'name' => $name, 'type' => 'decimal', 'unit' => 'mm', 'required' => true];
$width = $dim('en', $n('En', 'Ширина', 'Width'))
    + $hint('Qabarit ölçü. Qiymət seçilmiş ölçüyə aiddir.', 'Габаритный размер. Цена относится к выбранному размеру.', 'Overall size. The price is for the chosen size.');
$depth = $dim('derinlik', $n('Dərinlik', 'Глубина', 'Depth'));
$height = $dim('hundurluk', $n('Hündürlük', 'Высота', 'Height'));
$dims = fn (bool $widthFilter = false, ?array $h = null): array => [
    ($widthFilter ? ['filter' => 'secondary'] : []) + ($h ?? []) + $width, $depth, $height,
];

// «Karkas materialı*» / «Korpus materialı*» — the same list, two names on the cards.
$frameOpts = $opts([
    ['Massiv ağac', 'massiv-agac'], ['Faner', 'faner'], ['MDF', 'mdf'], ['Laminatlı yonqar plitəsi (LDSP)', 'ldsp'], ['Metal', 'metal'], ['Plastik', 'plastik'], ['Kombinə', 'kombine'],
]);
$frame = ['slug' => 'kart-mb-karkas', 'name' => $n('Karkas materialı', 'Материал каркаса', 'Frame material'), 'type' => 'multiselect', 'required' => true, 'filter' => true,
    'options' => $frameOpts];
$body = ['slug' => 'kart-mb-korpus', 'name' => $n('Korpus materialı', 'Материал корпуса', 'Carcass material'), 'type' => 'multiselect', 'required' => true, 'filter' => true,
    'options' => $frameOpts];

// «Fasad materialı və örtüyü — İki seçim».
$facade = [
    ['slug' => 'kart-mb-fasad', 'name' => $n('Fasad materialı', 'Материал фасада', 'Front material'), 'type' => 'dropdown', 'options' => [...$opts([
        ['MDF', 'mdf'], ['LDSP', 'ldsp'], ['Massiv', 'massiv'], ['Şüşə', 'suse'],
    ]), $other()]],
    ['slug' => 'kart-mb-fasad-ortuk', 'name' => $n('Fasad örtüyü', 'Покрытие фасада', 'Front finish'), 'type' => 'dropdown', 'options' => [...$opts([
        ['Boya', 'boya'], ['Laminat', 'laminat'], ['Şpon', 'spon'], ['Plyonka', 'plyonka'],
    ]), $other()]],
];

// «Üzlük* — Seçim və kod: parça / təbii dəri / süni dəri / üzlüksüz. Parçada kolleksiya və rəng kodu».
$upholstery = [
    ['slug' => 'kart-mb-uzluk', 'name' => $n('Üzlük', 'Обивка', 'Upholstery'), 'type' => 'dropdown', 'required' => true, 'filter' => true, 'options' => $opts([
        ['Parça', 'parca'], ['Təbii dəri', 'tebii-deri'], ['Süni dəri', 'suni-deri'], ['Üzlüksüz', 'uzluksuz'],
    ])],
    ['slug' => 'kart-mb-parca-kodu', 'name' => $n('Parçanın kolleksiyası və rəng kodu', 'Коллекция и код цвета ткани', 'Fabric collection and colour code'), 'type' => 'text',
        'required' => true, 'when' => $when('kart-mb-uzluk', 'parca')],
];

// «Yumşaq hissənin dolğusu — Bir neçə seçim».
$filling = ['slug' => 'kart-mb-dolgu', 'name' => $n('Yumşaq hissənin dolğusu', 'Наполнитель мягкой части', 'Cushion filling'), 'type' => 'multiselect', 'options' => [
    ...$opts([['Poliuretan köpük', 'pu-kopuk'], ['Lateks', 'lateks'], ['Yay bloku', 'yay-bloku'], ['Lələk-tük', 'lelek-tuk'], ['Sintetik lif', 'sintetik-lif']]),
    $other(), $noneX('Göstərilməyib', 'gosterilmeyib'),
]];

// «Rəng və səth* — Rəng kataloqdan seçilir; səth: mat / parlaq / teksturalı / digər».
$look = [
    ['slug' => 'kart-mb-reng', 'name' => $n('Rəng', 'Цвет', 'Colour'), 'type' => 'dropdown', 'required' => true, 'filter' => true, 'options' => [...$opts([
        ['Ağ', 'ag'], ['Qara', 'qara'], ['Boz', 'boz'], ['Bej', 'bej'], ['Qəhvəyi', 'qehveyi'], ['Təbii ağac', 'tebii-agac'],
        ['Yaşıl', 'yasil'], ['Mavi', 'mavi'], ['Qırmızı', 'qirmizi'], ['Sarı', 'sari'], ['Çoxrəngli', 'coxrengli'],
    ]), $other($P_CODE)]] + $hint('Əsas görünən rəng; parçanın kodu üzlükdə yazılır.', 'Основной цвет; код ткани — в обивке.', 'The main visible colour; the fabric code goes with the upholstery.'),
    ['slug' => 'kart-mb-seth', 'name' => $n('Səth', 'Поверхность', 'Surface'), 'type' => 'dropdown', 'required' => true, 'options' => [...$opts([
        ['Mat', 'mat'], ['Parlaq', 'parlaq'], ['Teksturalı', 'teksturali'],
    ]), $other()]],
];

// «İcazə verilən yük — Rəqəm və seçim: kg; bir oturacaq, bir rəf və ya bütöv məhsul üçün olduğu
// ayrıca seçilir». The figure is asked only once it says what it refers to.
$load = [
    ['slug' => 'kart-mb-yuk-aid', 'name' => $n('İcazə verilən yük aiddir', 'Допустимая нагрузка относится к', 'Load limit applies to'), 'type' => 'dropdown', 'options' => [
        ...$opts([['Bir oturacaq', 'oturacaq'], ['Bir rəf', 'ref'], ['Bir hissə', 'hisse'], ['Tam məhsul', 'tam-mehsul']]), $ns,
    ]] + $hint('Yalnız istehsalçının məlumatı; yük uydurulmur.', 'Только данные производителя; нагрузку не выдумывать.', 'The maker’s data only; never estimated.'),
    ['slug' => 'kart-mb-yuk', 'name' => $n('İcazə verilən yük', 'Допустимая нагрузка', 'Load limit'), 'type' => 'decimal', 'unit' => 'kg', 'required' => true,
        'rules' => ['min' => 1], 'when' => $when('kart-mb-yuk-aid', 'oturacaq', 'ref', 'hisse', 'tam-mehsul')],
];

// «Təhvil forması — Yığılmış / sökülmüş / qismən yığılmış».
$delivered = ['slug' => 'kart-mb-tehvil', 'name' => $n('Təhvil forması', 'Состояние при поставке', 'Delivered as'), 'type' => 'dropdown', 'options' => $opts([
    ['Yığılmış', 'yigilmis'], ['Sökülmüş', 'sokulmus'], ['Qismən yığılmış', 'qismen'],
])];

// Room and use: «Otaq, ev/ofis/kommersiya istifadəsi əlavə filtrdir».
$use = ['slug' => 'kart-mb-istifade', 'name' => $n('İstifadə yeri', 'Место использования', 'Use'), 'type' => 'multiselect', 'filter' => 'secondary', 'options' => $opts([
    ['Ev', 'ev'], ['Ofis', 'ofis'], ['Kommersiya məkanı', 'kommersiya'], ['Açıq məkan', 'aciq-mekan'],
])] + $hint('Əlavə filtrdir: eyni məhsul bir kartda yaradılır və bir neçə istifadə yeri seçilir.', 'Дополнительный фильтр: товар создаётся один раз и отмечаются все места использования.', 'An extra filter: the product is created once and all uses are ticked.');
$room = ['slug' => 'kart-mb-otaq', 'name' => $n('Otaq', 'Помещение', 'Room'), 'type' => 'multiselect', 'filter' => 'secondary', 'options' => $opts([
    ['Qonaq otağı', 'qonaq'], ['Yataq otağı', 'yataq'], ['Yemək otağı', 'yemek'], ['Mətbəx', 'metbex'], ['Uşaq otağı', 'usaq'],
    ['Dəhliz', 'dehliz'], ['Hamam otağı', 'hamam'], ['İş otağı', 'is-otagi'], ['Terras və bağ', 'terras'],
])] + $hint('Əlavə filtrdir; uyğun bütün otaqları seçin.', 'Дополнительный фильтр; отметьте все подходящие помещения.', 'An extra filter; tick every suitable room.');

// «Dəstin tərkibi* — Hər elementin adı, sayı və ölçüsü; yalnız dəst satıldıqda».
$setContents = ['slug' => 'kart-mb-dest-terkibi', 'name' => $n('Dəstin tərkibi', 'Состав комплекта', 'Set contents'), 'type' => 'textarea']
    + $hint('Hər element ayrıca sətirdə: adı, sayı və ölçüsü, mm (məs. «Şkaf 2 qapılı — 1 ədəd — 900×600×2100»). Ümumi ölçü də göstərilir.', 'Каждый элемент с новой строки: название, количество, размер, мм. Укажите и общий размер.', 'One element per line: name, count and size, mm. Give the overall size too.');

// «Texniki məlumat — Fayl və ya keçid: ölçü sxemi və ya istehsalçı təlimatı».
$tech = ['slug' => 'kart-mb-texniki', 'name' => $n('Texniki məlumat', 'Техническая информация', 'Technical data'), 'type' => 'document']
    + $hint('Ölçü sxemi və ya istehsalçının təlimatı. Fayl və ya link.', 'Схема размеров или инструкция производителя. Файл или ссылка.', 'Dimension drawing or the maker’s manual. File or link.');

// Small fields shared by several cards.
$storage = ['slug' => 'kart-mb-saxlama-yeri', 'name' => $n('Saxlama yeri', 'Место для хранения', 'Storage'), 'type' => 'dropdown', 'options' => $yesNo];
$softClose = ['slug' => 'kart-mb-yumsaq-baglanma', 'name' => $n('Yumşaq bağlanma', 'Плавное закрывание', 'Soft close'), 'type' => 'dropdown', 'options' => [
    ...$opts([['Var', 'var'], ['Yoxdur', 'yoxdur']]), $na,
]];
$lighting = ['slug' => 'kart-mb-isiqlandirma', 'name' => $n('İşıqlandırma', 'Подсветка', 'Lighting'), 'type' => 'dropdown', 'options' => $yesNo];
$lock = ['slug' => 'kart-mb-kilid', 'name' => $n('Kilid', 'Замок', 'Lock'), 'type' => 'dropdown', 'options' => $yesNo];
$wallFix = ['slug' => 'kart-mb-divara-sabitleme', 'name' => $n('Divara sabitləmə', 'Крепление к стене', 'Wall anchoring'), 'type' => 'dropdown', 'options' => [
    ...$opts([['Tələb olunur', 'teleb-olunur'], ['Tələb olunmur', 'teleb-olunmur']]), $ns,
]];
$mount = ['slug' => 'kart-mb-montaj', 'name' => $n('Montaj', 'Монтаж', 'Mounting'), 'type' => 'dropdown', 'required' => true, 'filter' => true,
    'options' => $opts([['Döşəmə', 'doseme'], ['Asma', 'asma']])];
$doors = ['slug' => 'kart-mb-qapi-sayi', 'name' => $n('Qapı sayı', 'Количество дверей', 'Number of doors'), 'type' => 'decimal', 'unit' => 'ədəd', 'rules' => ['integer' => true]];
$drawers = ['slug' => 'kart-mb-cekmece-sayi', 'name' => $n('Çəkməcə sayı', 'Количество ящиков', 'Number of drawers'), 'type' => 'decimal', 'unit' => 'ədəd', 'rules' => ['integer' => true]];

// «Masa üstü materialı*» (dining and coffee tables).
$top = ['slug' => 'kart-mb-masaustu', 'name' => $n('Masa üstü materialı', 'Материал столешницы', 'Table top material'), 'type' => 'dropdown', 'required' => true, 'filter' => true,
    'options' => [...$opts([
        ['Ağac', 'agac'], ['MDF', 'mdf'], ['LDSP', 'ldsp'], ['Şüşə', 'suse'], ['Daş', 'das'], ['Keramika', 'keramika'], ['Metal', 'metal'],
    ]), $other()]];

// «Hündürlük tənzimlənməsi — Yoxdur / mexaniki / elektrik; varsa aralıq mm» (desks).
$adjusted = $when('kart-mb-hundurluk-tenzim', 'mexaniki', 'elektrik');
$heightAdjust = [
    ['slug' => 'kart-mb-hundurluk-tenzim', 'name' => $n('Hündürlük tənzimlənməsi', 'Регулировка высоты', 'Height adjustment'), 'type' => 'dropdown', 'options' => [
        ...$opts([['Yoxdur', 'yoxdur'], ['Mexaniki', 'mexaniki'], ['Elektrik', 'elektrik']]), $ns,
    ]],
    ['slug' => 'kart-mb-tenzim-min', 'name' => $n('Hündürlük, minimum', 'Высота, минимум', 'Height, minimum'), 'type' => 'decimal', 'unit' => 'mm', 'required' => true,
        'when' => $adjusted, 'rules' => ['lte' => 'kart-mb-tenzim-maks']],
    ['slug' => 'kart-mb-tenzim-maks', 'name' => $n('Hündürlük, maksimum', 'Высота, максимум', 'Height, maximum'), 'type' => 'decimal', 'unit' => 'mm', 'required' => true,
        'when' => $adjusted],
];

// «Oturacaq hündürlüyü — mm; tənzimlənəndə minimum–maksimum ayrıca».
$seatHeight = ['slug' => 'kart-mb-oturacaq-hundurluyu', 'name' => $n('Oturacaq hündürlüyü', 'Высота сиденья', 'Seat height'), 'type' => 'decimal', 'unit' => 'mm',
    'required' => true, 'rules' => ['lte' => 'kart-mb-oturacaq-hundurluyu-maks']];
$seatHeightMax = ['slug' => 'kart-mb-oturacaq-hundurluyu-maks', 'name' => $n('Oturacaq hündürlüyü, maksimum', 'Высота сиденья, максимум', 'Seat height, maximum'),
    'type' => 'decimal', 'unit' => 'mm'];

// «Uyğun döşək ölçüsü* — Uzunluq × en, mm» and «Yataq ölçüsü» of a sofa / chair bed.
$mattressSize = ['slug' => 'kart-mb-dosek-olcusu', 'name' => $n('Uyğun döşək ölçüsü (uzunluq × en)', 'Размер матраса (длина × ширина)', 'Mattress size (length × width)'),
    'type' => 'size', 'unit' => 'mm', 'required' => true];
$bedSize = ['slug' => 'kart-mb-yataq-olcusu', 'name' => $n('Yataq ölçüsü (uzunluq × en)', 'Спальное место (длина × ширина)', 'Sleeping area (length × width)'),
    'type' => 'size', 'unit' => 'mm'];

// Garden furniture: «Material*» and «Xarici istifadə*».
$gardenMaterial = ['slug' => 'kart-mb-bag-material', 'name' => $n('Material', 'Материал', 'Material'), 'type' => 'dropdown', 'required' => true, 'filter' => true,
    'options' => [...$opts([['Alüminium', 'aluminium'], ['Polad', 'polad'], ['Ağac', 'agac'], ['Plastik', 'plastik'], ['Süni rattan', 'suni-rattan']]), $other()]];
$outdoor = ['slug' => 'kart-mb-xarici-istifade', 'name' => $n('Xarici istifadə', 'Наружное использование', 'Outdoor use'), 'type' => 'dropdown', 'required' => true, 'filter' => true,
    'options' => $opts([['Açıq havada', 'aciq-hava'], ['Örtülü terrasda', 'ortulu-terras']])]
    + $hint('İstehsalçının təyinatı üzrə.', 'По назначению производителя.', 'As the maker specifies.');

$cs = fn (string $code): string => 'kart-'.strtolower(str_replace('_', '-', $code));
$kit = fn (string $code, array $items): array => [
    'slug' => $cs($code).'-komplekt', 'name' => $n('Komplektə daxildir', 'В комплекте', 'Included'), 'type' => 'multiselect',
    'options' => [...$opts($items), $none()],
] + $hint('Yalnız qiymətə daxil olanlar.', 'Только то, что входит в цену.', 'Only what the price includes.');
$f = fn (string $code, string $key, array $name, string $type, array $extra = []): array => ['slug' => $cs($code).'-'.$key, 'name' => $name, 'type' => $type] + $extra;
$count = fn (string $code, string $key, array $name, array $extra = []): array => $extra + ['slug' => $cs($code).'-'.$key, 'name' => $name, 'type' => 'decimal', 'unit' => 'ədəd', 'rules' => ['integer' => true]];
$yn = fn (string $code, string $key, array $name, array $extra = []): array => ['slug' => $cs($code).'-'.$key, 'name' => $name, 'type' => 'dropdown', 'options' => $yesNo] + $extra;
$card = fn (string $code, array $name, array $units, array $fields, array $extra = []): array => ['code' => $code, 'name' => $name, 'sale' => 'unit', 'units' => $units, 'fields' => $fields] + $extra;
$type = fn (string $code, array $list, array $extra = [], string $key = 'tip', ?array $name = null): array => $extra + [
    'slug' => $cs($code).'-'.$key, 'name' => $name ?? $n('Tip', 'Тип', 'Type'), 'type' => 'dropdown', 'required' => true, 'filter' => true, 'options' => $opts($list),
];
$typeOther = fn (string $code, array $list, string $key = 'tip', ?array $name = null): array => [
    'slug' => $cs($code).'-'.$key, 'name' => $name ?? $n('Tip', 'Тип', 'Type'), 'type' => 'dropdown', 'required' => true, 'filter' => true, 'options' => [...$opts($list), $other()],
];

$ps = ['piece', 'set'];
$nofilter = ['filter' => false];

$data = [
    'section' => 'mebel-komplektlesdiriciler-furnitura-ve-mexanizmler',

    'groups' => [
        // ════ 1. Divanlar (M01_1) ══════════════════════════════════════════
        [
            'slug' => 'divanlar',
            'name' => $n('Divanlar', 'Диваны', 'Sofas'),
            'classes' => [
                'divan-yumsaq-mebel' => $card('M01_1', $n('Divanlar', 'Диваны', 'Sofas'), $ps, [
                    ...$variant,
                    $typeOther('M01_1', [['Düz', 'duz'], ['Künc', 'kunc'], ['U formalı', 'u-formali']], 'forma', $n('Forma', 'Форма', 'Shape')),
                    $type('M01_1', [['Bütöv', 'butov'], ['Modullu', 'modullu']], ['filter' => false], 'qurulus', $n('Quruluş', 'Конструкция', 'Build')),
                    $f('M01_1', 'modullar', $n('Qiymətə daxil olan modullar', 'Модули, входящие в цену', 'Modules in the price'), 'textarea', ['required' => true,
                        'when' => $when('kart-m01-1-qurulus', 'modullu')] + $hint('Hər modul: adı, sayı və ölçüsü; ümumi ölçü yuxarıdakı qabaritdir.', 'Каждый модуль: название, количество, размер; общий размер — габариты выше.', 'Each module: name, count and size; the overall size is above.')),
                    ...$dims(),
                    $count('M01_1', 'oturacaq-sayi', $n('Oturacaq sayı', 'Количество мест', 'Seats'), ['unit' => 'nəfər']
                        + $hint('İstehsalçının göstərdiyi say.', 'По данным производителя.', 'As the maker states.')),
                    $f('M01_1', 'kunc-istiqameti', $n('Künc istiqaməti', 'Направление угла', 'Corner side'), 'dropdown', ['options' => [
                        ...$opts([['Sol', 'sol'], ['Sağ', 'sag'], ['Dəyişdirilə bilən', 'deyisdirile-bilen']]), $na,
                    ]] + $hint('Qarşıdan baxdıqda.', 'Если смотреть спереди.', 'Seen from the front.')),
                    $nofilter + $frame,
                    ...$upholstery,
                    $filling,
                    $f('M01_1', 'yataga-acilma', $n('Yatağa açılma', 'Раскладывается в кровать', 'Sofa bed'), 'dropdown', ['required' => true, 'filter' => true,
                        'options' => $opts([['Yoxdur', 'yoxdur'], ['Var', 'var']])]),
                    $f('M01_1', 'mexanizm', $n('Açılma mexanizmi', 'Механизм трансформации', 'Bed mechanism'), 'dropdown', ['required' => true,
                        'when' => $when('kart-m01-1-yataga-acilma', 'var'), 'options' => [...$opts([
                            ['Kitab', 'kitab'], ['Avrokitab', 'avrokitab'], ['Klik-klak', 'klik-klak'], ['Akkordeon', 'akkordeon'], ['Delfin', 'delfin'],
                            ['İrəli çəkilən', 'ireli-cekilen'], ['Fransız qatlama', 'fransiz'], ['Puma', 'puma'],
                        ]), $other()]]),
                    $bedSize + ['when' => $when('kart-m01-1-yataga-acilma', 'var')],
                    $storage,
                    ...$look,
                    $use,
                    $room,
                    ...$services,
                ]),
            ],
        ],

        // ════ 2. Kreslolar, puflar və banketkalar (M02_1–M02_2) ═════════════
        [
            'slug' => 'kreslolar-puflar-ve-banketkalar',
            'name' => $n('Kreslolar, puflar və banketkalar', 'Кресла, пуфы и банкетки', 'Armchairs, poufs and benches'),
            'classes' => [
                'kreslo' => $card('M02_1', $n('İnteryer kresloları və reklaynerlər', 'Интерьерные кресла и реклайнеры', 'Armchairs and recliners'), $ps, [
                    ...$variant,
                    $type('M02_1', [['Standart', 'standart'], ['Reklayner', 'reklayner'], ['Yellənən', 'yellenen'], ['Fırlanan', 'firlanan'], ['Kreslo-çarpayı', 'kreslo-carpayi']]),
                    ...$dims(),
                    $nofilter + $frame,
                    ...$upholstery,
                    $filling,
                    $f('M02_1', 'idareetme', $n('İdarəetmə', 'Управление', 'Control'), 'dropdown', ['options' => [...$opts([['Mexaniki', 'mexaniki'], ['Elektrik', 'elektrik']]), $na]]),
                    // «Elektrikli modeldə enerji təchizatı, adapterin komplektə daxil olması və açılmış ölçü».
                    $f('M02_1', 'enerji', $n('Enerji təchizatı', 'Электропитание', 'Power supply'), 'dropdown', ['required' => true,
                        'when' => $when('kart-m02-1-idareetme', 'elektrik'), 'options' => [...$opts([
                            ['Şəbəkədən', 'sebeke'], ['Akkumulyator', 'akkumulyator'], ['Şəbəkə və akkumulyator', 'kombine'],
                        ]), $other()]]),
                    $f('M02_1', 'adapter', $n('Adapter komplektdə', 'Адаптер в комплекте', 'Adapter included'), 'dropdown', [
                        'when' => $when('kart-m02-1-idareetme', 'elektrik'), 'options' => $yesNo]),
                    $f('M02_1', 'acilmis-olcu', $n('Açılmış ölçü (dərinlik × hündürlük)', 'Размер в разложенном виде (глубина × высота)', 'Reclined size (depth × height)'), 'size', ['unit' => 'mm',
                        'when' => $when('kart-m02-1-idareetme', 'mexaniki', 'elektrik')]),
                    $bedSize + ['when' => $when('kart-m02-1-tip', 'kreslo-carpayi')],
                    ...$look,
                    ...$load,
                    $use,
                    $room,
                    ...$services,
                ]),
                'puflar-ve-banketkalar' => $card('M02_2', $n('Puflar və banketkalar', 'Пуфы и банкетки', 'Poufs and benches'), $ps, [
                    ...$variant,
                    $type('M02_2', [['Puf', 'puf'], ['Banketka', 'banketka'], ['Ayaqaltı', 'ayaqalti']]),
                    ...$dims(),
                    $nofilter + $frame,
                    ...$upholstery,
                    $storage,
                    ...$look,
                    ...$load,
                    $use,
                    $room,
                    ...$services,
                ]),
            ],
        ],

        // ════ 3. Stullar və taburetlər (M03_1) ══════════════════════════════
        [
            'slug' => 'stullar-ve-taburetler',
            'name' => $n('Stullar və taburetlər', 'Стулья и табуреты', 'Chairs and stools'),
            'classes' => [
                'stul' => $card('M03_1', $n('Stullar və taburetlər', 'Стулья и табуреты', 'Chairs and stools'), $ps, [
                    ...$variant,
                    $type('M03_1', [['Yemək stulu', 'yemek-stulu'], ['Bar stulu', 'bar-stulu'], ['Yarımbar stulu', 'yarimbar-stulu'], ['Taburet', 'taburet']]),
                    ...$dims(),
                    $seatHeight + $hint('Tənzimlənəndə — minimum.', 'При регулировке — минимум.', 'If adjustable, the minimum.'),
                    $seatHeightMax + ['when' => $when('kart-m03-1-funksiyalar', 'hundurluk')],
                    $frame,
                    ...$upholstery,
                    $yn('M03_1', 'qoltuqalti', $n('Qoltuqaltı', 'Подлокотники', 'Armrests')),
                    $f('M03_1', 'funksiyalar', $n('Funksiyalar', 'Функции', 'Features'), 'multiselect', ['options' => [
                        ...$opts([['Yığılan', 'yigilan'], ['Üst-üstə yığılan', 'ust-uste'], ['Fırlanan', 'firlanan'], ['Hündürlük tənzimlənməsi', 'hundurluk']]), $noneX(),
                    ]]),
                    ...$look,
                    ...$load,
                    $use,
                    $room,
                    ...$services,
                ]),
            ],
        ],

        // ════ 4. Masalar (M04_1–M04_3) ══════════════════════════════════════
        [
            'slug' => 'masalar',
            'name' => $n('Masalar', 'Столы', 'Tables'),
            'classes' => [
                'yemek-masasi' => $card('M04_1', $n('Yemək masaları', 'Обеденные столы', 'Dining tables'), $ps, [
                    ...$variant,
                    $typeOther('M04_1', [['Düzbucaqlı', 'duzbucaqli'], ['Dairəvi', 'dairevi'], ['Oval', 'oval'], ['Kvadrat', 'kvadrat']], 'forma', $n('Forma', 'Форма', 'Shape')),
                    ...$dims(),
                    $top,
                    $f('M04_1', 'dayaq', $n('Dayaq materialı', 'Материал опоры', 'Base material'), 'dropdown', ['options' => [...$opts([['Ağac', 'agac'], ['Metal', 'metal']]), $other()]]),
                    $type('M04_1', [['Sabit', 'sabit'], ['Uzanan', 'uzanan'], ['Qatlanan', 'qatlanan']], [], 'acilma', $n('Açılma', 'Раскладка', 'Extension')),
                    $f('M04_1', 'acilmis-uzunluq', $n('Açılmış uzunluq', 'Длина в разложенном виде', 'Extended length'), 'decimal', ['unit' => 'mm', 'required' => true,
                        'when' => $when('kart-m04-1-acilma', 'uzanan', 'qatlanan')]),
                    $f('M04_1', 'acilmis-en', $n('Açılmış en', 'Ширина в разложенном виде', 'Extended width'), 'decimal', ['unit' => 'mm',
                        'when' => $when('kart-m04-1-acilma', 'uzanan', 'qatlanan')]),
                    $f('M04_1', 'acilmis-hundurluk', $n('Açılmış hündürlük', 'Высота в разложенном виде', 'Extended height'), 'decimal', ['unit' => 'mm',
                        'when' => $when('kart-m04-1-acilma', 'uzanan', 'qatlanan')]),
                    $count('M04_1', 'tutum', $n('Oturacaq tutumu', 'Вместимость', 'Seats'), ['unit' => 'nəfər', 'rules' => ['integer' => true, 'lte' => 'kart-m04-1-tutum-acilmis']]
                        + $hint('Bağlı vəziyyətdə.', 'В сложенном виде.', 'Closed.')),
                    $count('M04_1', 'tutum-acilmis', $n('Oturacaq tutumu, açılmış', 'Вместимость в разложенном виде', 'Seats, extended'), ['unit' => 'nəfər',
                        'when' => $when('kart-m04-1-acilma', 'uzanan', 'qatlanan')]),
                    ...$look,
                    $delivered,
                    $use,
                    $room,
                    ...$services,
                ]),
                'jurnal-qehve-masasi' => $card('M04_2', $n('Jurnal, yan və konsol masaları', 'Журнальные, приставные и консольные столы', 'Coffee, side and console tables'), $ps, [
                    ...$variant,
                    $type('M04_2', [['Jurnal', 'jurnal'], ['Yan', 'yan'], ['Konsol', 'konsol'], ['İç-içə masa dəsti', 'ic-ice']]),
                    ...$dims(),
                    $top,
                    $nofilter + $frame,
                    ...$look,
                    $f('M04_2', 'funksiyalar', $n('Funksiyalar', 'Функции', 'Features'), 'multiselect', ['options' => [
                        ...$opts([['Rəf', 'ref'], ['Çəkməcə', 'cekmece'], ['Qaldırılan üst', 'qaldirilan-ust'], ['Təkər', 'teker']]), $noneX(),
                    ]]),
                    $setContents + ['required' => true, 'when' => $when('kart-m04-2-tip', 'ic-ice')],
                    $delivered,
                    $use,
                    $room,
                    ...$services,
                ]),
                'yazi-ve-kosmetik-masalar' => $card('M04_3', $n('Yazı və kosmetik masalar', 'Письменные и туалетные столы', 'Desks and dressing tables'), $ps, [
                    ...$variant,
                    $type('M04_3', [['Yazı masası', 'yazi'], ['Kompüter masası', 'komputer'], ['Kosmetik masa', 'kosmetik']]),
                    ...$dims(),
                    $body,
                    ...$facade,
                    ...$look,
                    ...$heightAdjust,
                    $f('M04_3', 'daxili', $n('Daxili elementlər', 'Встроенные элементы', 'Built-in elements'), 'multiselect', ['options' => [
                        ...$opts([['Çəkməcə', 'cekmece'], ['Dolab', 'dolab'], ['Kabel keçidi', 'kabel']]), $noneX(),
                    ]]),
                    $kit('M04_3', [['Güzgü', 'guzgu'], ['İşıq', 'isiq'], ['Stul', 'stul'], ['Tumba', 'tumba']]),
                    $delivered,
                    $use,
                    $room,
                    ...$services,
                ]),
            ],
        ],

        // ════ 5. Çarpayılar və döşəklər (M05_1–M05_4) ═══════════════════════
        [
            'slug' => 'dosekler-ve-yataq-levazimatlari',
            'name' => $n('Çarpayılar və döşəklər', 'Кровати и матрасы', 'Beds and mattresses'),
            'classes' => [
                'carpayilar' => $card('M05_1', $n('Çarpayılar', 'Кровати', 'Beds'), $ps, [
                    ...$variant,
                    $typeOther('M05_1', [['Birnəfərlik', 'birneferlik'], ['İkinəfərlik', 'ikineferlik'], ['İkimərtəbəli', 'ikimertebeli'], ['Qatlanan', 'qatlanan']]),
                    ...$dims(),
                    $mattressSize,
                    $frame,
                    ...$upholstery,
                    $f('M05_1', 'saxlama', $n('Saxlama yeri', 'Место для хранения', 'Storage'), 'dropdown', ['filter' => 'secondary', 'options' => $opts([
                        ['Yoxdur', 'yoxdur'], ['Çəkməcə', 'cekmece'], ['Qaldırılan baza', 'qaldirilan-baza'],
                    ])]),
                    $kit('M05_1', [['Döşək', 'dosek'], ['Lamelli baza', 'lamelli-baza'], ['Başlıq', 'basliq'], ['Qaldırıcı mexanizm', 'qaldirici']]),
                    ...$look,
                    ...$load,
                    $delivered,
                    $use,
                    ...$services,
                ]),
                'dosek' => $card('M05_2', $n('Döşəklər', 'Матрасы', 'Mattresses'), ['piece'], [
                    ...$variant,
                    $f('M05_2', 'uzunluq', $n('Uzunluq', 'Длина', 'Length'), 'decimal', ['unit' => 'mm', 'required' => true, 'filter' => 'secondary']),
                    $f('M05_2', 'en', $n('En', 'Ширина', 'Width'), 'decimal', ['unit' => 'mm', 'required' => true, 'filter' => true]),
                    $f('M05_2', 'hundurluk', $n('Hündürlük', 'Высота', 'Height'), 'decimal', ['unit' => 'mm', 'required' => true, 'filter' => 'secondary']),
                    $type('M05_2', [['Müstəqil yay', 'musteqil-yay'], ['Əlaqəli yay', 'elaqeli-yay'], ['Yaysız', 'yaysiz'], ['Hibrid', 'hibrid']], [], 'qurulus', $n('Quruluş', 'Конструкция', 'Construction')),
                    $f('M05_2', 'dolgu', $n('Dolğu qatları', 'Слои наполнителя', 'Comfort layers'), 'multiselect', ['required' => true, 'options' => [
                        ...$opts([['Poliuretan köpük', 'pu-kopuk'], ['Yaddaş köpüyü', 'yaddas-kopuyu'], ['Lateks', 'lateks'], ['Kokos', 'kokos']]), $other(),
                    ]]),
                    $type('M05_2', [['Yumşaq', 'yumsaq'], ['Orta', 'orta'], ['Sərt', 'sert'], ['İki tərəfi fərqli', 'iki-terefli']], [], 'sertlik', $n('Sərtlik', 'Жёсткость', 'Firmness'))
                        + $hint('İstehsalçının şkalası üzrə; sərtlik istehsalçılar arasında eyni standart deyil. Tibbi / ortopedik təsir sənədsiz yazılmır.', 'По шкале производителя; жёсткость у производителей не стандартизирована. Медицинский эффект без документа не указывается.', 'On the maker’s scale; firmness is not a common standard. No medical claims without a document.'),
                    $f('M05_2', 'yuk', $n('Bir yataq yerinə yük', 'Нагрузка на одно спальное место', 'Load per sleeping place'), 'decimal', ['unit' => 'kg']
                        + $hint('İstehsalçının tövsiyəsi.', 'Рекомендация производителя.', 'The maker’s recommendation.')),
                    $f('M05_2', 'uzluk', $n('Üzlük materialı', 'Материал чехла', 'Cover material'), 'text')
                        + $hint('Tərkib faizlə, istehsalçıya görə (məs. «60% pambıq, 40% polyester»).', 'Состав в процентах по данным производителя.', 'Composition in %, per the maker.'),
                    $yn('M05_2', 'cixarilan-uzluk', $n('Çıxarılan üzlük', 'Съёмный чехол', 'Removable cover')),
                    $yn('M05_2', 'rulon', $n('Rulon qablaşdırma', 'Упаковка в рулон', 'Roll-packed')),
                    ...$guarantee,
                    $use,
                    ...$services,
                ]),
                'carpayi-basliqlari' => $card('M05_3', $n('Çarpayı başlıqları', 'Изголовья кроватей', 'Headboards'), $ps, [
                    ...$variant,
                    ...$dims(),
                    $nofilter + $frame,
                    ...$upholstery,
                    $type('M05_3', [['Divara', 'divara'], ['Çarpayıya', 'carpayiya'], ['Sərbəst dayanan', 'serbest']], [], 'berkitme', $n('Bərkitmə', 'Крепление', 'Fixing')),
                    $f('M05_3', 'carpayi-eni', $n('Uyğun çarpayı eni', 'Подходящая ширина кровати', 'Bed width'), 'decimal', ['unit' => 'mm', 'required' => true, 'filter' => 'secondary',
                        'rules' => ['lte' => 'kart-m05-3-carpayi-eni-maks']] + $hint('Standart ölçü və ya minimum.', 'Стандартный размер или минимум.', 'The standard size or the minimum.')),
                    $f('M05_3', 'carpayi-eni-maks', $n('Uyğun çarpayı eni, maksimum', 'Ширина кровати, максимум', 'Bed width, maximum'), 'decimal', ['unit' => 'mm']),
                    $kit('M05_3', [['Bərkidicilər', 'berkidiciler'], ['Yan panellər', 'yan-paneller']]),
                    ...$look,
                    ...$services,
                ]),
                'carpayi-esasi-ortopedik' => $card('M05_4', $n('Çarpayı bazaları', 'Основания кроватей', 'Bed bases'), $ps, [
                    ...$variant,
                    $type('M05_4', [['Lamelli', 'lamelli'], ['Bütöv', 'butov'], ['Tənzimlənən', 'tenzimlenen']]),
                    ...$dims(),
                    $mattressSize,
                    $frame,
                    $f('M05_4', 'tenzimleme', $n('Tənzimləmə', 'Регулировка', 'Adjustment'), 'dropdown', ['options' => $opts([
                        ['Yoxdur', 'yoxdur'], ['Mexaniki', 'mexaniki'], ['Elektrik', 'elektrik'],
                    ])]),
                    $count('M05_4', 'lamel-sayi', $n('Lamellərin sayı', 'Количество ламелей', 'Number of slats'), ['when' => $when('kart-m05-4-tip', 'lamelli')]),
                    $kit('M05_4', [['Ayaqlar', 'ayaqlar'], ['Qaldırıcı', 'qaldirici'], ['İdarəetmə pultu', 'pult']]),
                    $tech,
                    ...$load,
                    ...$services,
                ]),
            ],
        ],

        // ════ 6. Şkaflar və qarderob sistemləri (M06_1–M06_2) ═══════════════
        [
            'slug' => 'skaflar-ve-qarderob-sistemleri',
            'name' => $n('Şkaflar və qarderob sistemləri', 'Шкафы и гардеробные системы', 'Wardrobes and closet systems'),
            'classes' => [
                'skaflar' => $card('M06_1', $n('Şkaflar', 'Шкафы', 'Wardrobes'), $ps, [
                    ...$variant,
                    $type('M06_1', [['Sərbəst dayanan', 'serbest'], ['Daxilə quraşdırılan', 'daxile']], [], 'qurasdirma', $n('Quraşdırma', 'Установка', 'Installation')),
                    $type('M06_1', [['Açılan', 'acilan'], ['Sürüşən', 'surusen'], ['Qatlanan', 'qatlanan'], ['Qapısız', 'qapisiz']], [], 'qapi-sistemi', $n('Qapı sistemi', 'Система дверей', 'Door system')),
                    ...$dims(),
                    $body,
                    ...$facade,
                    $doors,
                    $f('M06_1', 'daxili', $n('Daxili quruluş', 'Внутреннее наполнение', 'Interior layout'), 'multiselect', ['options' => [
                        ...$opts([['Rəflər', 'refler'], ['Asılqan borusu', 'asilqan-borusu'], ['Çəkməcələr', 'cekmeceler'], ['Səbətlər', 'sebetler']]), $other(),
                    ]]),
                    $softClose,
                    ...$look,
                    $delivered,
                    $use,
                    $room,
                    ...$services,
                ]),
                'qarderob-sistemi-saxlama-sistemi' => $card('M06_2', $n('Qarderob sistemləri', 'Гардеробные системы', 'Closet systems'), $ps, [
                    ...$variant,
                    $type('M06_2', [['Korpuslu', 'korpuslu'], ['Dirəkli', 'direkli'], ['Relsli', 'relsli'], ['Modullu', 'modullu']], [], 'qurulus', $n('Quruluş', 'Конструкция', 'Build')),
                    ...$dims(false, $hint('Ümumi ölçü; künc modelində tərəflər aşağıda ayrıca.', 'Общий размер; у угловой модели стороны — ниже.', 'Overall size; corner sides go below.')),
                    $f('M06_2', 'kunc-terefler', $n('Künc modelində tərəflərin ölçüləri', 'Размеры сторон угловой модели', 'Corner model side sizes'), 'text')
                        + $hint('Hər tərəfin eni, mm (məs. «2400 + 1800»).', 'Ширина каждой стороны, мм.', 'Each side’s width, mm.'),
                    $body,
                    $f('M06_2', 'montaj', $n('Montaj', 'Монтаж', 'Mounting'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Divara', 'divara'], ['Döşəmə-tavan', 'doseme-tavan'], ['Sərbəst dayanan', 'serbest'], ['Kombinə', 'kombine'],
                    ])]),
                    $f('M06_2', 'daxili', $n('Daxili quruluş', 'Внутреннее наполнение', 'Interior layout'), 'multiselect', ['required' => true, 'options' => [
                        ...$opts([['Rəflər', 'refler'], ['Ştanqlar', 'stanqlar'], ['Çəkməcələr', 'cekmeceler'], ['Səbətlər', 'sebetler'], ['Ayaqqabı bölməsi', 'ayaqqabi']]), $other(),
                    ]]),
                    $setContents + ['required' => true],
                    $tech,
                    ...$look,
                    ...$load,
                    $use,
                    ...$services,
                ]),
            ],
        ],

        // ════ 7. Komodlar, tumbalar və bufetlər (M07_1–M07_2) ═══════════════
        [
            'slug' => 'komodlar-tumbalar-ve-bufetler',
            'name' => $n('Komodlar, tumbalar və bufetlər', 'Комоды, тумбы и буфеты', 'Chests, cabinets and sideboards'),
            'classes' => [
                'komod' => $card('M07_1', $n('Komodlar və tumbalar', 'Комоды и тумбы', 'Chests of drawers and cabinets'), $ps, [
                    ...$variant,
                    $typeOther('M07_1', [['Komod', 'komod'], ['Yataqyanı tumba', 'yataqyani-tumba'], ['TV tumbası', 'tv-tumbasi']]),
                    ...$dims(),
                    $body,
                    ...$facade,
                    $mount,
                    $drawers,
                    $doors,
                    $softClose,
                    ...$look,
                    $delivered,
                    $use,
                    $room,
                    ...$services,
                ]),
                'bufetler-ve-vitrinalar' => $card('M07_2', $n('Bufetlər və vitrinalar', 'Буфеты и витрины', 'Sideboards and display cabinets'), $ps, [
                    ...$variant,
                    $type('M07_2', [['Bufet', 'bufet'], ['Servant', 'servant'], ['Vitrin', 'vitrin']]),
                    ...$dims(),
                    $body,
                    ...$facade,
                    $f('M07_2', 'qurulus', $n('Quruluş', 'Конструкция', 'Layout'), 'multiselect', ['options' => $opts([
                        ['Şüşəli bölmə', 'suseli'], ['Qapalı bölmə', 'qapali'], ['Çəkməcə', 'cekmece'], ['Açıq rəf', 'aciq-ref'],
                    ])]),
                    $lighting,
                    $softClose,
                    ...$look,
                    $delivered,
                    $use,
                    $room,
                    ...$services,
                ]),
            ],
        ],

        // ════ 8. Stellajlar və rəflər (M08_1–M08_2) ═════════════════════════
        [
            'slug' => 'stellajlar-ve-refler',
            'name' => $n('Stellajlar və rəflər', 'Стеллажи и полки', 'Shelving units and shelves'),
            'classes' => [
                'stellaj-kitab-skafi' => $card('M08_1', $n('Stellajlar', 'Стеллажи', 'Shelving units'), $ps, [
                    ...$variant,
                    ...$dims(),
                    $body,
                    $type('M08_1', [['Açıq', 'aciq'], ['Qismən qapalı', 'qismen-qapali'], ['Modullu', 'modullu']], [], 'qurulus', $n('Quruluş', 'Конструкция', 'Build')),
                    $count('M08_1', 'ref-sayi', $n('Rəf sayı', 'Количество полок', 'Number of shelves')),
                    $yn('M08_1', 'ref-hundurluyu', $n('Rəf hündürlüyü dəyişir', 'Полки переставляются', 'Adjustable shelves')),
                    $f('M08_1', 'ref-yuku', $n('Bir rəfin yük həddi', 'Нагрузка на одну полку', 'Load per shelf'), 'decimal', ['unit' => 'kg']
                        + $hint('İstehsalçı məlumatı.', 'Данные производителя.', 'The maker’s data.')),
                    $wallFix,
                    ...$look,
                    $delivered,
                    $use,
                    $room,
                    ...$services,
                ]),
                'divar-refleri' => $card('M08_2', $n('Divar rəfləri', 'Настенные полки', 'Wall shelves'), $ps, [
                    ...$variant,
                    $typeOther('M08_2', [['Düz', 'duz'], ['Künc', 'kunc'], ['Dekorativ', 'dekorativ']], 'forma', $n('Forma', 'Форма', 'Shape')),
                    ...$dims(),
                    $typeOther('M08_2', [['Ağac', 'agac'], ['MDF', 'mdf'], ['LDSP', 'ldsp'], ['Metal', 'metal'], ['Şüşə', 'suse']], 'material', $n('Material', 'Материал', 'Material')),
                    $nofilter + $typeOther('M08_2', [['Gizli', 'gizli'], ['Kronşteyn', 'kronsteyn'], ['Asma', 'asma']], 'berkitme', $n('Bərkitmə', 'Крепление', 'Fixing')),
                    $kit('M08_2', [['Kronşteyn', 'kronsteyn'], ['Bərkidici dəst', 'berkidici-dest']]),
                    $tech,
                    ...$look,
                    ...$load,
                    $f('M08_2', 'montaj-serti', $n('Montaj şərti', 'Условие монтажа', 'Mounting condition'), 'text', [
                        'when' => $when('kart-mb-yuk-aid', 'oturacaq', 'ref', 'hisse', 'tam-mehsul')]
                        + $hint('Yük həddi rəf, bərkidici və divar əsasından asılıdır: hansı divar və bərkidici ilə (məs. «beton / kərpic, dübel 8 mm»).', 'Нагрузка зависит от полки, крепежа и основания стены: при каком основании и крепеже.', 'The load depends on shelf, fixings and wall: for which wall and fixings.')),
                    $use,
                    $room,
                    ...$services,
                ]),
            ],
        ],

        // ════ 9. Mətbəx mebeli (M09_1–M09_3) ════════════════════════════════
        [
            'slug' => 'hazir-metbexler-ve-saxlama-sistemleri',
            'name' => $n('Mətbəx mebeli', 'Кухонная мебель', 'Kitchen furniture'),
            'classes' => [
                'metbex-mebel-desti-hazir-hell' => $card('M09_1', $n('Mətbəx dəstləri və sifariş mətbəxləri', 'Кухонные гарнитуры и кухни на заказ', 'Kitchen sets and made-to-order kitchens'), ['set', 'lm'], [
                    ...$variant,
                    $typeOther('M09_1', [['Düz', 'duz'], ['L', 'l'], ['U', 'u'], ['Paralel', 'paralel'], ['Adalı', 'adali']], 'plan', $n('Plan forması', 'Планировка', 'Layout')),
                    $f('M09_1', 'xett-uzunluqlari', $n('Xətlərin uzunluğu', 'Длина линий', 'Run lengths'), 'text', ['required' => true]
                        + $hint('Hər xəttin uzunluğu, mm (məs. «3000 + 1800»).', 'Длина каждой линии, мм.', 'Each run’s length, mm.')),
                    $f('M09_1', 'alt-derinlik', $n('Alt modulların dərinliyi', 'Глубина нижних модулей', 'Base unit depth'), 'decimal', ['unit' => 'mm', 'required' => true]),
                    $f('M09_1', 'alt-hundurluk', $n('Alt modulların hündürlüyü', 'Высота нижних модулей', 'Base unit height'), 'decimal', ['unit' => 'mm', 'required' => true]),
                    $f('M09_1', 'ust-derinlik', $n('Üst modulların dərinliyi', 'Глубина верхних модулей', 'Wall unit depth'), 'decimal', ['unit' => 'mm']),
                    $f('M09_1', 'ust-hundurluk', $n('Üst modulların hündürlüyü', 'Высота верхних модулей', 'Wall unit height'), 'decimal', ['unit' => 'mm']),
                    $body,
                    ...$facade,
                    $typeOther('M09_1', [
                        ['Daxil deyil', 'daxil-deyil'], ['Laminat', 'laminat'], ['Kompakt HPL', 'hpl'], ['Akril daş', 'akril'], ['Kvars kompozit', 'kvars'], ['Təbii daş', 'tebii-das'], ['Keramika', 'keramika'],
                    ], 'tezgah', $n('Tezgah', 'Столешница', 'Worktop')),
                    $f('M09_1', 'furnitura', $n('Furnitura', 'Фурнитура', 'Hardware'), 'text', ['required' => true]
                        + $hint('Brend və seriya; menteşə və çəkməcə sistemi ayrıca (məs. «Blum Clip top; Tandembox»).', 'Бренд и серия; петли и система ящиков отдельно.', 'Brand and series; hinges and drawer system separately.')),
                    $softClose,
                    $kit('M09_1', [
                        ['Tezgah', 'tezgah'], ['Plintus', 'plintus'], ['İşıq', 'isiq'], ['Çanaq', 'canaq'], ['Qarışdırıcı', 'qarisdirici'], ['Texnika', 'texnika'], ['Montaj', 'montaj'],
                    ]),
                    ['required' => true] + $hint('Qiymətə daxil olan modullar: adı, sayı və ölçüsü, mm. Poqon metr qiymətində nümunə komplektin modulları. Mətbəx texnikası ayrıca məhsul kimi bu bölməyə daxil edilmir.', 'Модули в цене: название, количество, размер, мм. Для цены за погонный метр — модули образца. Техника сюда не входит.', 'Modules in the price: name, count and size, mm. For a per-metre price, the sample set’s modules. Appliances are not listed here.') + $setContents,
                    ...$look,
                    $use,
                    ...$services,
                ]),
                'metbex-modullari' => $card('M09_2', $n('Mətbəx modulları', 'Кухонные модули', 'Kitchen units'), $ps, [
                    ...$variant,
                    $type('M09_2', [['Alt', 'alt'], ['Üst', 'ust'], ['Hündür', 'hundur'], ['Künc', 'kunc'], ['Texnika üçün', 'texnika']], [], 'modul', $n('Modul', 'Модуль', 'Unit')),
                    ...$dims(true),
                    $body,
                    ...$facade,
                    $f('M09_2', 'on-hisse', $n('Ön hissə', 'Фронт', 'Front'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Qapı', 'qapi'], ['Çəkməcə', 'cekmece'], ['Açıq', 'aciq'], ['Qarışıq', 'qarisiq'],
                    ])]),
                    $softClose,
                    $f('M09_2', 'yuva-en', $n('Texnika yuvası: en', 'Ниша для техники: ширина', 'Appliance niche: width'), 'decimal', ['unit' => 'mm',
                        'when' => $when('kart-m09-2-modul', 'hundur', 'texnika')]),
                    $f('M09_2', 'yuva-derinlik', $n('Texnika yuvası: dərinlik', 'Ниша для техники: глубина', 'Appliance niche: depth'), 'decimal', ['unit' => 'mm',
                        'when' => $when('kart-m09-2-modul', 'hundur', 'texnika')]),
                    $f('M09_2', 'yuva-hundurluk', $n('Texnika yuvası: hündürlük', 'Ниша для техники: высота', 'Appliance niche: height'), 'decimal', ['unit' => 'mm',
                        'when' => $when('kart-m09-2-modul', 'hundur', 'texnika')]),
                    $kit('M09_2', [['Fasad', 'fasad'], ['Ayaq', 'ayaq'], ['Asqı', 'asqi'], ['Tezgah', 'tezgah'], ['Furnitura', 'furnitura']]),
                    ...$look,
                    $use,
                    ...$services,
                ]),
                'metbex-adasi-bar-stendi' => $card('M09_3', $n('Mətbəx adaları', 'Кухонные острова', 'Kitchen islands'), $ps, [
                    ...$variant,
                    ...$dims(),
                    $body,
                    ...$facade,
                    $typeOther('M09_3', [
                        ['Laminat', 'laminat'], ['Ağac', 'agac'], ['Kompakt HPL', 'hpl'], ['Akril daş', 'akril'], ['Kvars kompozit', 'kvars'], ['Təbii daş', 'tebii-das'], ['Keramika', 'keramika'],
                    ], 'tezgah', $n('Tezgah materialı', 'Материал столешницы', 'Worktop material')),
                    $type('M09_3', [['Sabit', 'sabit'], ['Təkərli', 'tekerli']], [], 'qurulus', $n('Quruluş', 'Конструкция', 'Build')),
                    $yn('M09_3', 'oturma', $n('Oturma hissəsi', 'Зона для сидения', 'Seating overhang')),
                    $f('M09_3', 'daxili', $n('Daxili bölmələr', 'Внутренние секции', 'Compartments'), 'multiselect', ['options' => [
                        ...$opts([['Çəkməcə', 'cekmece'], ['Dolab', 'dolab'], ['Açıq rəf', 'aciq-ref'], ['Texnika yeri', 'texnika']]), $noneX(),
                    ]]),
                    $kit('M09_3', [['Tezgah', 'tezgah'], ['Çanaq', 'canaq'], ['Texnika', 'texnika'], ['Montaj', 'montaj']]),
                    ...$look,
                    $use,
                    ...$services,
                ]),
            ],
        ],

        // ════ 10. Dəhliz mebeli (M10_1–M10_3) ═══════════════════════════════
        [
            'slug' => 'dehliz-ve-qarderob-otaqlari',
            'name' => $n('Dəhliz mebeli', 'Мебель для прихожей', 'Hallway furniture'),
            'classes' => [
                'ayaqqabi-tumbasi-dehliz-pufu' => $card('M10_1', $n('Ayaqqabı dolabları', 'Обувные шкафы', 'Shoe cabinets'), $ps, [
                    ...$variant,
                    ...$dims(),
                    $body,
                    ...$facade,
                    $type('M10_1', [['Açılan qapı', 'acilan-qapi'], ['Maili bölmə', 'maili'], ['Açıq', 'aciq'], ['Qarışıq', 'qarisiq']], [], 'acilma', $n('Açılma', 'Открывание', 'Opening')),
                    $count('M10_1', 'tutum', $n('Tutum', 'Вместимость', 'Capacity'), ['unit' => 'cüt', 'filter' => 'secondary']
                        + $hint('Cüt ayaqqabı; istehsalçının təxmini tutumu.', 'Пар обуви; ориентировочно по данным производителя.', 'Pairs of shoes, as the maker estimates.')),
                    $yn('M10_1', 'oturacaq', $n('Oturacaq', 'Сиденье', 'Seat')),
                    $mount,
                    ...$look,
                    $delivered,
                    $use,
                    ...$services,
                ]),
                'dehliz-asilqanlari' => $card('M10_2', $n('Dəhliz asılqanları', 'Вешалки для прихожей', 'Hallway coat racks'), $ps, [
                    ...$variant,
                    $type('M10_2', [['Divar', 'divar'], ['Döşəmə', 'doseme'], ['Panel', 'panel']]),
                    ...$dims(),
                    $typeOther('M10_2', [['Ağac', 'agac'], ['Metal', 'metal'], ['MDF', 'mdf'], ['LDSP', 'ldsp']], 'material', $n('Material', 'Материал', 'Material')),
                    $count('M10_2', 'qarmaq-sayi', $n('Qarmaq sayı', 'Количество крючков', 'Number of hooks')),
                    $kit('M10_2', [['Rəf', 'ref'], ['Oturacaq', 'oturacaq'], ['Güzgü', 'guzgu']]),
                    ...$look,
                    ...$load,
                    $delivered,
                    $use,
                    ...$services,
                ]),
                'dehliz-mebel-destleri' => $card('M10_3', $n('Dəhliz mebel dəstləri', 'Комплекты мебели для прихожей', 'Hallway furniture sets'), $ps, [
                    ...$variant,
                    ...$dims(false, $hint('Dəstin ümumi ölçüsü.', 'Общий размер комплекта.', 'The set’s overall size.')),
                    $body,
                    ...$facade,
                    $setContents + ['required' => true],
                    $kit('M10_3', [['Şkaf', 'skaf'], ['Ayaqqabı dolabı', 'ayaqqabi-dolabi'], ['Asılqan', 'asilqan'], ['Güzgü', 'guzgu'], ['Banketka', 'banketka']]),
                    ...$look,
                    $delivered,
                    $use,
                    ...$services,
                ]),
            ],
        ],

        // ════ 11. Hamam mebeli (M11_1–M11_2) ════════════════════════════════
        [
            'slug' => 'vanna-otagi-mebeli',
            'name' => $n('Hamam mebeli', 'Мебель для ванной', 'Bathroom furniture'),
            'classes' => [
                'eluzyuyan-alti-tumba' => $card('M11_1', $n('Əlüzyuyan altı tumbalar', 'Тумбы под раковину', 'Vanity units'), $ps, [
                    ...$variant,
                    ...$dims(true),
                    $body,
                    ...$facade,
                    $type('M11_1', [['Asma', 'asma'], ['Ayaqlı', 'ayaqli'], ['Sokollu', 'sokollu']], [], 'montaj', $n('Montaj', 'Монтаж', 'Mounting')),
                    $type('M11_1', [['Tumbaüstü', 'tumbaustu'], ['Tezgahüstü', 'tezgahustu'], ['Tezgahaltı', 'tezgahalti'], ['İnteqrə olunmuş', 'inteqre']], [], 'eluzyuyan', $n('Əlüzyuyan yerləşməsi', 'Расположение раковины', 'Basin position')),
                    $f('M11_1', 'uygun-eluzyuyan', $n('Uyğun əlüzyuyan', 'Подходящая раковина', 'Matching basin'), 'text')
                        + $hint('Çanaq daxil deyilsə: model və ya tezgah kəsiyi. Ayrıca satılan əlüzyuyan və qarışdırıcı santexnika bölməsindədir.', 'Если раковина не входит: модель или вырез столешницы. Раковины и смесители — в сантехнике.', 'If no basin is included: model or worktop cut-out. Basins and taps are in plumbing.'),
                    $softClose,
                    $kit('M11_1', [['Əlüzyuyan', 'eluzyuyan'], ['Tezgah', 'tezgah'], ['Sifon', 'sifon'], ['Qarışdırıcı', 'qarisdirici'], ['Asqı', 'asqi']]),
                    ...$look,
                    $use,
                    ...$services,
                ]),
                'guzgu-vanna-otagi-ucun-guzgulu-skaf' => $card('M11_2', $n('Hamam dolabları və güzgülü dolablar', 'Шкафы и зеркальные шкафы для ванной', 'Bathroom cabinets and mirror cabinets'), $ps, [
                    ...$variant,
                    $type('M11_2', [['Penal', 'penal'], ['Asma dolab', 'asma-dolab'], ['Güzgülü dolab', 'guzgulu-dolab']]),
                    ...$dims(),
                    $body,
                    ...$facade,
                    $mount,
                    $softClose,
                    $lighting,
                    $f('M11_2', 'ip', $n('Elektrik hissəsinin qorunması (IP)', 'Защита электрической части (IP)', 'Electrical protection (IP)'), 'text', [
                        'when' => $when('kart-mb-isiqlandirma', 'beli')] + $hint('IP kodu, istehsalçı göstərirsə (məs. IP44).', 'Код IP, если указан производителем.', 'The IP code, if the maker states it.')),
                    ...$look,
                    $delivered,
                    $use,
                    ...$services,
                ]),
            ],
        ],

        // ════ 12. Uşaq mebeli (M12_1–M12_3) ═════════════════════════════════
        [
            'slug' => 'usaq-mebeli',
            'name' => $n('Uşaq mebeli', 'Детская мебель', 'Children’s furniture'),
            'classes' => [
                'usaq-carpayisi' => $card('M12_1', $n('Uşaq çarpayıları', 'Детские кровати', 'Children’s beds'), $ps, [
                    ...$variant,
                    $type('M12_1', [
                        ['Körpə çarpayısı', 'korpe'], ['Uşaq çarpayısı', 'usaq'], ['İkimərtəbəli', 'ikimertebeli'], ['Çardaq tipli', 'cardaq'], ['Transformasiya olunan', 'transformasiya'],
                    ]),
                    ...$dims(),
                    $mattressSize + $hint('Döşək ölçüsü.', 'Размер матраса.', 'Mattress size.'),
                    $frame,
                    $f('M12_1', 'yas-min', $n('İstehsalçının yaş aralığı: minimum', 'Возраст по производителю: от', 'Maker’s age range: from'), 'decimal', [
                        'rules' => ['lte' => 'kart-m12-1-yas-maks']] + $hint('Yalnız istehsalçı təlimatından; vahid aşağıda.', 'Только из инструкции производителя; единица ниже.', 'From the maker’s manual only; unit below.')),
                    $f('M12_1', 'yas-maks', $n('İstehsalçının yaş aralığı: maksimum', 'Возраст по производителю: до', 'Maker’s age range: to'), 'decimal'),
                    $f('M12_1', 'yas-vahidi', $n('Yaş vahidi', 'Единица возраста', 'Age unit'), 'dropdown', ['options' => $opts([['Ay', 'ay'], ['İl', 'il']])]),
                    $f('M12_1', 'yanliq', $n('Qoruyucu yanlıq', 'Защитный бортик', 'Safety rail'), 'dropdown', ['options' => [
                        ...$opts([['Yoxdur', 'yoxdur'], ['Çıxarılan', 'cixarilan'], ['Sabit', 'sabit']]), $ns,
                    ]]),
                    $f('M12_1', 'dosek-qalinligi', $n('Maksimum döşək qalınlığı', 'Максимальная толщина матраса', 'Max mattress thickness'), 'decimal', ['unit' => 'mm']
                        + $hint('İstehsalçının göstərişi.', 'По указанию производителя.', 'As the maker specifies.')),
                    $kit('M12_1', [['Döşək', 'dosek'], ['Baza', 'baza'], ['Nərdivan', 'nerdivan'], ['Yanlıq', 'yanliq'], ['Çəkməcə', 'cekmece']]),
                    $tech,
                    ...$look,
                    ...$load,
                    $f('M12_1', 'ust-yuk', $n('Üst yatağın yük həddi', 'Нагрузка на верхний ярус', 'Upper bunk load limit'), 'decimal', ['unit' => 'kg',
                        'when' => $when('kart-m12-1-tip', 'ikimertebeli', 'cardaq')] + $hint('İstehsalçı təlimatından; üst yataq ayrıca yoxlanılır.', 'Из инструкции производителя; верхний ярус отдельно.', 'From the maker’s manual; the upper bunk separately.')),
                    ...$services,
                ]),
                'usaq-masalari-ve-stullari' => $card('M12_2', $n('Uşaq masaları və stulları', 'Детские столы и стулья', 'Children’s tables and chairs'), $ps, [
                    ...$variant,
                    $type('M12_2', [['Masa', 'masa'], ['Stul', 'stul'], ['Masa-stul dəsti', 'dest']], [], 'mehsul', $n('Məhsul', 'Изделие', 'Item')),
                    ...$dims(),
                    $f('M12_2', 'masa-min', $n('Masa üstünün hündürlüyü: minimum', 'Высота столешницы: минимум', 'Table top height: minimum'), 'decimal', ['unit' => 'mm',
                        'when' => $when('kart-m12-2-mehsul', 'masa', 'dest'), 'rules' => ['lte' => 'kart-m12-2-masa-maks']]),
                    $f('M12_2', 'masa-maks', $n('Masa üstünün hündürlüyü: maksimum', 'Высота столешницы: максимум', 'Table top height: maximum'), 'decimal', ['unit' => 'mm',
                        'when' => $when('kart-m12-2-mehsul', 'masa', 'dest')]),
                    $f('M12_2', 'oturacaq-min', $n('Oturacaq hündürlüyü: minimum', 'Высота сиденья: минимум', 'Seat height: minimum'), 'decimal', ['unit' => 'mm',
                        'when' => $when('kart-m12-2-mehsul', 'stul', 'dest'), 'rules' => ['lte' => 'kart-m12-2-oturacaq-maks']]),
                    $f('M12_2', 'oturacaq-maks', $n('Oturacaq hündürlüyü: maksimum', 'Высота сиденья: максимум', 'Seat height: maximum'), 'decimal', ['unit' => 'mm',
                        'when' => $when('kart-m12-2-mehsul', 'stul', 'dest')]),
                    $f('M12_2', 'boy-min', $n('İstehsalçının boy tövsiyəsi: minimum', 'Рост по производителю: от', 'Maker’s height range: from'), 'decimal', ['unit' => 'sm',
                        'rules' => ['lte' => 'kart-m12-2-boy-maks']]),
                    $f('M12_2', 'boy-maks', $n('İstehsalçının boy tövsiyəsi: maksimum', 'Рост по производителю: до', 'Maker’s height range: to'), 'decimal', ['unit' => 'sm']),
                    $yn('M12_2', 'maillik', $n('Masa üstünün maililik tənzimlənməsi', 'Регулировка наклона столешницы', 'Tilting table top'), [
                        'when' => $when('kart-m12-2-mehsul', 'masa', 'dest')]),
                    $setContents + ['required' => true, 'when' => $when('kart-m12-2-mehsul', 'dest')],
                    $frame,
                    ...$look,
                    ...$load,
                    ...$services,
                ]),
                'usaq-skafi-penal' => $card('M12_3', $n('Uşaq saxlama mebeli və dəstləri', 'Детская мебель для хранения и комплекты', 'Children’s storage furniture and sets'), $ps, [
                    ...$variant,
                    $type('M12_3', [['Şkaf', 'skaf'], ['Komod', 'komod'], ['Oyuncaq stellajı', 'oyuncaq-stellaji'], ['Otaq dəsti', 'otaq-desti']]),
                    ...$dims(),
                    $body,
                    ...$facade,
                    $setContents + ['required' => true, 'when' => $when('kart-m12-3-tip', 'otaq-desti')],
                    $softClose,
                    $wallFix,
                    ...$look,
                    $delivered,
                    ...$services,
                ]),
            ],
        ],

        // ════ 13. Ofis mebeli (M13_1–M13_3) ═════════════════════════════════
        [
            'slug' => 'ofis-mebeli',
            'name' => $n('Ofis mebeli', 'Офисная мебель', 'Office furniture'),
            'classes' => [
                'ofis-masasi' => $card('M13_1', $n('Ofis iş masaları və iş stansiyaları', 'Офисные столы и рабочие станции', 'Office desks and workstations'), $ps, [
                    ...$variant,
                    $type('M13_1', [['Tək iş masası', 'tek'], ['Çoxyerli iş stansiyası', 'coxyerli'], ['Rəhbər masası', 'rehber'], ['İclas masası', 'iclas']])
                        + $hint('Həm ev, həm ofis üçün olan yazı masası bir kartda — «Yazı və kosmetik masalar»da yaradılır.', 'Письменный стол для дома и офиса создаётся один раз — в «Письменных столах».', 'A desk for home and office is created once, under «Desks».'),
                    $count('M13_1', 'yer-sayi', $n('İş və ya oturacaq sayı', 'Количество рабочих / посадочных мест', 'Workplaces or seats'), ['unit' => 'nəfər', 'filter' => 'secondary']),
                    ...$dims(),
                    $body,
                    ...$heightAdjust,
                    $f('M13_1', 'elementler', $n('Elementlər', 'Элементы', 'Elements'), 'multiselect', ['options' => [
                        ...$opts([['Kabel kanalı', 'kabel-kanali'], ['Masaüstü arakəsmə', 'arakesme'], ['Tumba', 'tumba']]), $noneX(),
                    ]]),
                    $setContents + ['required' => true, 'when' => $when('kart-m13-1-tip', 'coxyerli')],
                    ...$look,
                    $delivered,
                    $use,
                    ...$services,
                ]),
                'ofis-kreslosu' => $card('M13_2', $n('Ofis kresloları', 'Офисные кресла', 'Office chairs'), $ps, [
                    ...$variant,
                    $type('M13_2', [['İşçi', 'isci'], ['Rəhbər', 'rehber'], ['Ziyarətçi', 'ziyaretci'], ['İclas', 'iclas']]),
                    ...$dims(),
                    $seatHeight,
                    $req($seatHeightMax),
                    $type('M13_2', [['Tor', 'tor'], ['Yumşaq üzlüklü', 'yumsaq'], ['Sərt', 'sert']], [], 'arxaliq', $n('Arxalıq', 'Спинка', 'Backrest')),
                    ...$upholstery,
                    $f('M13_2', 'tenzimlemeler', $n('Tənzimləmələr', 'Регулировки', 'Adjustments'), 'multiselect', ['options' => [
                        ...$opts([
                            ['Oturacaq hündürlüyü', 'hundurluk'], ['Oturacaq dərinliyi', 'derinlik'], ['Bel dəstəyi', 'bel'], ['Qoltuqaltı', 'qoltuqalti'], ['Başlıq', 'basliq'], ['Arxalıq bucağı', 'bucaq'],
                        ]), $noneX(),
                    ]]),
                    $f('M13_2', 'mexanizm', $n('Mexanizm', 'Механизм', 'Mechanism'), 'dropdown', ['options' => [
                        ...$opts([['Sabit', 'sabit'], ['Yellənmə', 'yellenme'], ['Sinxron', 'sinxron']]), $other(),
                    ]]),
                    $type('M13_2', [['Təkərli', 'tekerli'], ['Ayaqlı', 'ayaqli'], ['Xizək tipli', 'xizek']], [], 'baza', $n('Baza', 'Основание', 'Base')),
                    ...$look,
                    ...$load,
                    $use,
                    ...$services,
                ]),
                'ofis-skafi-stellaj-tumba' => $card('M13_3', $n('Ofis saxlama mebeli', 'Офисная мебель для хранения', 'Office storage furniture'), $ps, [
                    ...$variant,
                    $type('M13_3', [['Sənəd dolabı', 'sened-dolabi'], ['Mobil tumba', 'mobil-tumba'], ['Şəxsi saxlama dolabı', 'sexsi-dolab'], ['Arxiv stellajı', 'arxiv-stellaji']]),
                    ...$dims(),
                    $body,
                    $count('M13_3', 'bolme-sayi', $n('Bölmə və ya çəkməcə sayı', 'Количество секций или ящиков', 'Compartments or drawers')),
                    $lock,
                    ...$look,
                    ...$load,
                    $delivered,
                    $use,
                    ...$services,
                ]),
            ],
        ],

        // ════ 14. Kommersiya məkanları üçün mebel (M14_1–M14_3) ═════════════
        [
            'slug' => 'kommersiya-mekanlari-ucun-mebel',
            'name' => $n('Kommersiya məkanları üçün mebel', 'Мебель для коммерческих помещений', 'Commercial furniture'),
            'classes' => [
                'resepsion-stendi' => $card('M14_1', $n('Resepşn və bar piştaxtaları', 'Стойки ресепшн и барные стойки', 'Reception and bar counters'), $ps, [
                    ...$variant,
                    $type('M14_1', [['Resepşn', 'resepsn'], ['Bar', 'bar'], ['Xidmət piştaxtası', 'xidmet']], [], 'teyinat', $n('Təyinat', 'Назначение', 'Purpose')),
                    $typeOther('M14_1', [['Düz', 'duz'], ['Künc', 'kunc'], ['U formalı', 'u-formali'], ['Əyri', 'eyri']], 'forma', $n('Forma', 'Форма', 'Shape')),
                    ...$dims(),
                    $body,
                    $typeOther('M14_1', [
                        ['Laminat', 'laminat'], ['Ağac', 'agac'], ['Akril daş', 'akril'], ['Kvars kompozit', 'kvars'], ['Təbii daş', 'tebii-das'], ['Metal', 'metal'],
                    ], 'is-sethi', $n('İş səthi materialı', 'Материал рабочей поверхности', 'Counter top material')),
                    ...$facade,
                    $kit('M14_1', [['İşıq', 'isiq'], ['Kabel kanalı', 'kabel-kanali'], ['Daxili dolab', 'daxili-dolab'], ['Tezgah', 'tezgah']]),
                    $hint('Dəst (modullu piştaxta) satıldıqda məcburidir: hər modulun adı, sayı və ölçüsü, mm.', 'Обязательно при продаже комплектом: каждый модуль, количество, размер, мм.', 'Required when sold as a set: each module’s name, count and size, mm.') + $setContents,
                    ...$look,
                    ...$services,
                ]),
                'ticaret-vitrinleri-ve-pistaxtalari' => $card('M14_2', $n('Ticarət vitrinləri və piştaxtaları', 'Торговые витрины и прилавки', 'Retail display cases and counters'), $ps, [
                    ...$variant,
                    $type('M14_2', [['Şüşəli vitrin', 'suseli-vitrin'], ['Satış piştaxtası', 'pistaxta'], ['Nümayiş stendi', 'stend']])
                        + $hint('Soyuducu vitrin və texnoloji avadanlıq bu karta daxil deyil.', 'Холодильные витрины и технологическое оборудование сюда не входят.', 'Refrigerated cases and equipment are not covered here.'),
                    ...$dims(),
                    $body,
                    $type('M14_2', [['Adi', 'adi'], ['Temperli', 'temperli'], ['Laminə', 'lamine'], ['Şüşəsiz', 'susesiz']], [], 'suse', $n('Şüşə', 'Стекло', 'Glass')),
                    $lock,
                    $lighting,
                    ...$look,
                    ...$load,
                    $delivered,
                    ...$services,
                ]),
                'kommersiya-mebeli' => $card('M14_3', $n('Kommersiya məkanları üçün xüsusi mebel', 'Специальная мебель для коммерческих помещений', 'Special commercial furniture'), $ps, [
                    ...$variant,
                    $typeOther('M14_3', [['Kafe-restoran', 'kafe-restoran'], ['Otel', 'otel']], 'teyinat', $n('Təyinat', 'Назначение', 'Purpose')),
                    $typeOther('M14_3', [
                        ['Sabit yumşaq oturacaq', 'sabit-oturacaq'], ['Restoran kabinəsi', 'kabine'], ['Baqaj altlığı', 'baqaj-altligi'], ['Otel mebel komplekti', 'otel-komplekti'],
                    ], 'mehsul-tipi', $n('Məhsul tipi', 'Тип изделия', 'Product type'))
                        + $hint('Yalnız xüsusi quruluşlu məhsullar və komplektlər; adi masa, stul və çarpayılar öz kartındadır.', 'Только специальные изделия и комплекты; обычные столы, стулья и кровати — в своих карточках.', 'Special items and sets only; ordinary tables, chairs and beds use their own cards.'),
                    ...$dims(),
                    $body,
                    ...$upholstery,
                    $setContents + ['required' => true, 'when' => $when('kart-m14-3-mehsul-tipi', 'otel-komplekti')],
                    $f('M14_3', 'kommersiya-uygun', $n('Kommersiya istifadəsinə uyğunluq', 'Пригодность для коммерческого использования', 'Rated for commercial use'), 'dropdown', ['required' => true,
                        'options' => [$a('İstehsalçı tərəfindən uyğun göstərilib', ['slug' => 'gosterilib']), $ns]]),
                    $tech,
                    ...$look,
                    ...$services,
                ]),
            ],
        ],

        // ════ 15. Bağ və açıq məkan mebeli (M15_1–M15_2) ════════════════════
        [
            'slug' => 'bag-ve-aciq-mekan-mebeli',
            'name' => $n('Bağ və açıq məkan mebeli', 'Садовая и уличная мебель', 'Garden and outdoor furniture'),
            'classes' => [
                'bag-masalari-ve-oturacaqlari' => $card('M15_1', $n('Bağ masaları və oturacaqları', 'Садовые столы и сиденья', 'Garden tables and seating'), $ps, [
                    ...$variant,
                    $type('M15_1', [['Masa', 'masa'], ['Stul', 'stul'], ['Kreslo', 'kreslo'], ['Divan', 'divan'], ['Skamya', 'skamya']]),
                    ...$dims(),
                    $gardenMaterial + $hint('Karkasın materialı.', 'Материал каркаса.', 'Frame material.'),
                    $outdoor,
                    $f('M15_1', 'yastiq', $n('Üzlük və yastıq', 'Обивка и подушки', 'Upholstery and cushions'), 'dropdown', ['options' => $opts([['Yoxdur', 'yoxdur'], ['Var', 'var']])]),
                    $f('M15_1', 'yastiq-melumat', $n('Parça və dolğu', 'Ткань и наполнитель', 'Fabric and filling'), 'text', ['when' => $when('kart-m15-1-yastiq', 'var')]),
                    $f('M15_1', 'funksiyalar', $n('Funksiyalar', 'Функции', 'Features'), 'multiselect', ['options' => [
                        ...$opts([['Qatlanan', 'qatlanan'], ['Üst-üstə yığılan', 'ust-uste'], ['Modullu', 'modullu']]), $noneX(),
                    ]]),
                    $kit('M15_1', [['Yastıq', 'yastiq'], ['Qoruyucu örtük', 'ortuk']]),
                    ...$look,
                    ...$load,
                    ...$services,
                ]),
                'sezlonqlar-ve-bag-mebel-destleri' => $card('M15_2', $n('Şezlonqlar və bağ mebel dəstləri', 'Шезлонги и комплекты садовой мебели', 'Sun loungers and garden furniture sets'), $ps, [
                    ...$variant,
                    $type('M15_2', [['Şezlonq', 'sezlonq'], ['Bağ mebel dəsti', 'dest']]),
                    ...$dims(),
                    $gardenMaterial,
                    $outdoor,
                    $yn('M15_2', 'arxaliq', $n('Arxalıq tənzimlənir', 'Регулируемая спинка', 'Adjustable backrest')),
                    $setContents + ['required' => true, 'when' => $when('kart-m15-2-tip', 'dest')],
                    $kit('M15_2', [['Yastıq', 'yastiq'], ['Döşək', 'dosek'], ['Qoruyucu örtük', 'ortuk']]),
                    ...$look,
                    ...$load,
                    ...$services,
                ]),
            ],
        ],

        // ════ Not in the workbook — kept on the site with the plain form ════
        // «Mətbəx texnikası ayrıca məhsul kimi bu bölməyə daxil edilmir»; sinks are santexnika's,
        // the backsplash panel is a wall finish. After the furniture file's groups (15–26).
        [
            'slug' => 'metbexler-ve-iceriqurasdirilan-texnika',
            'name' => $n('Mətbəx texnikası və moykalar', 'Кухонная техника и мойки', 'Kitchen appliances and sinks'),
            'sort_order' => 27,
            'plain' => [
                'bisirme-paneli', 'iceriqurasdirilan-soba', 'metbex-aspiratoru', 'iceriqurasdirilan-qabyuyan-masin', 'iceriqurasdirilan-soyuducu',
                'moykalar-ve-iceriqurasdirilan-metbex-texnikasi', 'metbex-onluyu-divar-paneli',
            ],
        ],
        // «Xam MDF, LDSP, faner, parça və dolğu materialları bu kataloqa daxil deyil».
        [
            'slug' => 'mebel-pliteleri-ve-materiallari',
            'name' => $n('Mebel plitələri və materialları', 'Мебельные плиты и материалы', 'Furniture boards and materials'),
            'sort_order' => 28,
            'plain' => ['mebel-pliteleri-dspmdffanermebel-qalxani', 'mebel-kenar-lenti', 'mebel-sponu'],
        ],
    ],

    // Near-duplicates of a card class: the old class closes, its links redirect, a moved
    // product gets the old class as the card's type answer.
    'merge' => [
        'carpayilar-skaflar-masalar-stullar-korpus-mebeli' => 'carpayilar',
        'bar-stulu' => ['into' => 'stul', 'set' => ['kart-m03-1-tip' => 'bar-stulu']],
        'carpayiyani-tumba' => ['into' => 'komod', 'set' => ['kart-m07-1-tip' => 'yataqyani-tumba']],
        'tv-tumbasi-media-sistemi' => ['into' => 'komod', 'set' => ['kart-m07-1-tip' => 'tv-tumbasi']],
        'divar-mebeli-modul-saxlama-sistemi-qonaq-otagi' => ['into' => 'bufetler-ve-vitrinalar', 'set' => ['kart-m07-2-tip' => 'servant']],
        'surusen-qapili-skaf' => ['into' => 'skaflar', 'set' => ['kart-m06-1-qapi-sistemi' => 'surusen']],
        'vanna-otagi-ucun-penal' => ['into' => 'guzgu-vanna-otagi-ucun-guzgulu-skaf', 'set' => ['kart-m11-2-tip' => 'penal']],
    ],

    // Emptied by the moves. 71 and 73 are emptied by mebel_furnitura.php (imported first);
    // 74's only class (moykalar) joins the appliances group above.
    'retire_groups' => [
        'mebel-pliteleri-ve-fasadlari', 'korpus-ve-yumsaq-mebel', 'mebel-furniturasi-ve-mexanizmleri', 'metbex-texnikasi-ve-moykalar',
        'qonaq-otagi', 'yataq-otagi', 'yemek-qrupu',
    ],
];

return $data;
