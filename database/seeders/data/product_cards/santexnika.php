<?php

/*
|--------------------------------------------------------------------------
| Santexnika — 27 fixture cards (S01_1–S10_3), 6 accessory cards (S11_1–S11_6) and
| 13 plumbing-component cards (S12_1–S12_13)
|--------------------------------------------------------------------------
|
| Source: Archi_AZ_Santexnika_Kataloq_AZ.xlsx (client, 20.11.2026). Format and
| decisions: docs/product-cards.md. Section «Santexnika, su təchizatı və kanalizasiya»:
| the workbook's own 12 categories («Kateqoriyalar» sheet) are the 12 card groups; the
| accessory and component subgroups (column «Alt qrup») stay inside their category,
| which keeps the menu at the workbook's 12. The section's other groups (pipes, valves,
| pumps, water heaters, sewerage) are not in the workbook and keep their plain form.
|
| Every card sells in the seller's unit («Ədəd / dəst. Bir seçim»), priced and stocked
| in it. Option values are Azerbaijani only; attribute and class names carry ru / en.
| `kart-zemanet` is the shared definition of doseme.php / tavan.php.
|
*/

$n = fn (string $az, string $ru, string $en): array => compact('az', 'ru', 'en');
$o = fn (string $az, string $ru, string $en, array $extra = []): array => ['value' => compact('az', 'ru', 'en')] + $extra;
$a = fn (string $az, array $extra = []): array => ['value' => ['az' => $az]] + $extra;

$P_NAME = $n('Dəqiq adını yazın', 'Укажите точное название', 'Enter the exact name');
$P_CODE = $n('Rəngi və ya kodunu yazın', 'Укажите цвет или код', 'Enter the colour or its code');
$P_SIZE = $n('Ölçünü yazın', 'Укажите размер', 'Enter the size');

$yes = $o('Bəli', 'Да', 'Yes', ['slug' => 'beli']);
$no = $o('Xeyr', 'Нет', 'No', ['slug' => 'xeyr']);
$ns = $o('Göstərilməyib', 'Не указано', 'Not specified', ['slug' => 'gosterilmeyib']);
$other = fn (?array $prompt = null, string $az = 'Digər', string $slug = 'diger'): array => $a($az, ['slug' => $slug, 'prompt' => $prompt ?? $P_NAME]);
$none = fn (string $az = 'Daxil deyil'): array => $a($az, ['slug' => 'daxil-deyil', 'exclusive' => true]);
$na = $a('Tətbiq edilmir', ['slug' => 'tetbiq-edilmir']);
$ynn = [$yes, $no, $ns];
$when = fn (string $attr, string ...$in): array => ['attr' => $attr, 'in' => $in];
$opts = fn (array $list): array => array_map(fn ($pair) => $a($pair[0], ['slug' => $pair[1]]), $list);

// ── Shared ──────────────────────────────────────────────────────────────────
$warranty = ['slug' => 'kart-zemanet', 'name' => $n('Zəmanət', 'Гарантия', 'Warranty'), 'type' => 'dropdown', 'options' => [
    $o('Var', 'Есть', 'Provided', ['slug' => 'var']), $o('Verilmir', 'Не предоставляется', 'Not provided', ['slug' => 'verilmir']), $ns,
]];
$warrantyMonths = ['slug' => 'kart-zemanet-muddeti', 'name' => $n('Zəmanət müddəti', 'Срок гарантии', 'Warranty period'), 'type' => 'decimal', 'unit' => 'ay',
    'rules' => ['integer' => true], 'when' => ['attr' => 'kart-zemanet', 'in' => ['var']]];
$guarantee = [$warranty, $warrantyMonths];

$color = ['slug' => 'kart-san-reng', 'name' => $n('Rəng', 'Цвет', 'Colour'), 'type' => 'dropdown', 'required' => true, 'options' => [
    $a('Ağ', ['slug' => 'ag']), $a('Qara', ['slug' => 'qara']), $a('Xrom', ['slug' => 'xrom']), $a('Boz', ['slug' => 'boz']),
    $a('Qızılı', ['slug' => 'qizili']), $a('Bürünc', ['slug' => 'burunc']), $other($P_CODE),
]];
$finish = ['slug' => 'kart-san-seth', 'name' => $n('Səth', 'Поверхность', 'Finish'), 'type' => 'dropdown', 'required' => true, 'options' => [
    $a('Mat', ['slug' => 'mat']), $a('Parlaq', ['slug' => 'parlaq']), $a('Fırçalanmış', ['slug' => 'fircalanmis']), $other(),
]];
// «Rəng və səth* — iki seçim».
$look = [$color, $finish];

// «Qabarit ölçülər* — 3 rəqəm: en × dərinlik × hündürlük, mm».
$dims = fn (bool $req = true): array => [
    ['slug' => 'kart-san-en', 'name' => $n('En', 'Ширина', 'Width'), 'type' => 'decimal', 'required' => $req, 'unit' => 'mm',
        'hint' => $n('Qabarit ölçülər: en × dərinlik × hündürlük.', 'Габариты: ширина × глубина × высота.', 'Overall size: width × depth × height.')],
    ['slug' => 'kart-san-derinlik', 'name' => $n('Dərinlik', 'Глубина', 'Depth'), 'type' => 'decimal', 'required' => $req, 'unit' => 'mm'],
    ['slug' => 'kart-san-hundurluk', 'name' => $n('Hündürlük', 'Высота', 'Height'), 'type' => 'decimal', 'required' => $req, 'unit' => 'mm'],
];
// «2 rəqəm: uzunluq × en, mm».
$size = fn (bool $req = true, ?array $hint = null): array => ['slug' => 'kart-san-olcu', 'name' => $n('Ölçü', 'Размер', 'Size'), 'type' => 'size', 'required' => $req, 'unit' => 'mm',
    'hint' => $hint ?? $n('Uzunluq × en.', 'Длина × ширина.', 'Length × width.')];
// Glass panels and doors are measured width × height.
$sizeWH = fn (bool $req = true, ?array $hint = null): array => ['slug' => 'kart-san-en-hundurluk', 'name' => $n('Ölçü (en × hündürlük)', 'Размер (ширина × высота)', 'Size (width × height)'), 'type' => 'size', 'required' => $req, 'unit' => 'mm',
    'hint' => $hint ?? $n('Birinci rəqəm en, ikinci hündürlük.', 'Сначала ширина, затем высота.', 'Width first, then height.')];

$sanMaterial = ['slug' => 'kart-san-material-keramika', 'name' => $n('Material', 'Материал', 'Material'), 'type' => 'dropdown', 'options' => $opts([
    ['Sanitar keramika', 'sanitar-keramika'], ['Mineral kompozit', 'mineral-kompozit'], ['Paslanmayan polad', 'paslanmayan-polad'], ['Şüşə', 'suse'],
]) + [4 => $other()]];
$metalMaterial = ['slug' => 'kart-san-material-metal', 'name' => $n('Material', 'Материал', 'Material'), 'type' => 'dropdown', 'options' => $opts([
    ['Pirinç', 'pirinc'], ['Paslanmayan polad', 'paslanmayan-polad'], ['Metal ərintisi', 'metal-erintisi'], ['Plastik', 'plastik'],
]) + [4 => $other()]];
$mixMaterial = ['slug' => 'kart-san-material-qarisiq', 'name' => $n('Material', 'Материал', 'Material'), 'type' => 'dropdown', 'options' => $opts([
    ['Metal', 'metal'], ['Şüşə', 'suse'], ['Keramika', 'keramika'], ['Plastik', 'plastik'], ['Qarışıq', 'qarisiq'],
]) + [5 => $other()]];
$shMaterial = ['slug' => 'kart-san-material-dus', 'name' => $n('Material', 'Материал', 'Material'), 'type' => 'dropdown', 'options' => $opts([
    ['Plastik', 'plastik'], ['Metal', 'metal'], ['Qarışıq', 'qarisiq'],
])];

$floorWall = ['slug' => 'kart-san-montaj-dosemeustu-asma', 'name' => $n('Montaj növü', 'Тип монтажа', 'Mounting'), 'type' => 'dropdown', 'required' => true,
    'options' => $opts([['Döşəməüstü', 'dosemeustu'], ['Asma', 'asma']])];
$drainOutlet = ['slug' => 'kart-san-kanalizasiya-cixisi', 'name' => $n('Kanalizasiya çıxışı', 'Выпуск в канализацию', 'Waste outlet'), 'type' => 'dropdown', 'required' => true,
    'hint' => $n('İstehsalçıya görə.', 'По данным производителя.', 'Per the maker.'),
    'options' => $opts([['Üfüqi', 'ufuqi'], ['Şaquli', 'saquli'], ['Maili', 'maili'], ['Universal adapterli', 'universal']])];
$holes = ['slug' => 'kart-san-qarisdirici-desikleri', 'name' => $n('Qarışdırıcı deşiklərinin sayı', 'Отверстия под смеситель', 'Tap holes'), 'type' => 'decimal', 'unit' => 'ədəd',
    'rules' => ['integer' => true, 'min' => 0], 'hint' => $n('Deşik yoxdursa 0.', 'Если отверстий нет — 0.', '0 if there are none.')];
$overflow = ['slug' => 'kart-san-dasma-deliyi', 'name' => $n('Daşma dəliyi', 'Перелив', 'Overflow'), 'type' => 'dropdown', 'options' => $ynn];

// Mixers.
$control = ['slug' => 'kart-san-idareetme', 'name' => $n('İdarəetmə', 'Управление', 'Control'), 'type' => 'dropdown', 'required' => true, 'options' => $opts([
    ['Təkqollu', 'tekqollu'], ['İki ventilli', 'iki-ventilli'], ['Termostatik', 'termostatik'], ['Sensorlu', 'sensorlu'], ['Düyməli', 'duymeli'],
])];
$spoutHeight = ['slug' => 'kart-san-cixis-hundurluyu', 'name' => $n('Su çıxışının hündürlüyü', 'Высота излива', 'Spout height'), 'type' => 'decimal', 'unit' => 'mm'];
$spoutReach = ['slug' => 'kart-san-cixis-cixintisi', 'name' => $n('Su çıxışının irəli çıxıntısı', 'Вылет излива', 'Spout reach'), 'type' => 'decimal', 'unit' => 'mm'];
$concealed = [
    ['slug' => 'kart-san-gizli-govde', 'name' => $n('Gizli gövdə', 'Скрытая часть', 'Concealed body'), 'type' => 'dropdown', 'options' => $opts([
        ['Daxildir', 'daxildir'], ['Ayrıca alınır', 'ayrica'], ['Tələb olunmur', 'teleb-olunmur'],
    ])],
    ['slug' => 'kart-san-gizli-govde-kodu', 'name' => $n('Uyğun gizli gövdənin kodu', 'Код скрытой части', 'Concealed body code'), 'type' => 'text', 'required' => true,
        'when' => ['attr' => 'kart-san-gizli-govde', 'in' => ['ayrica']]],
];
$pressure = [
    ['slug' => 'kart-san-tezyiq-min', 'name' => $n('İş təzyiqi, min', 'Рабочее давление, мин.', 'Working pressure, min'), 'type' => 'decimal', 'unit' => 'bar',
        'rules' => ['lte' => 'kart-san-tezyiq-maks'], 'hint' => $n('İstehsalçı məlumatına görə.', 'По данным производителя.', 'Per the maker.')],
    ['slug' => 'kart-san-tezyiq-maks', 'name' => $n('İş təzyiqi, maks', 'Рабочее давление, макс.', 'Working pressure, max'), 'type' => 'decimal', 'unit' => 'bar'],
];
$flow = [
    ['slug' => 'kart-san-serfiyyat', 'name' => $n('Su sərfiyyatı', 'Расход воды', 'Flow rate'), 'type' => 'decimal', 'unit' => 'l/dəq'],
    ['slug' => 'kart-san-serfiyyat-tezyiqi', 'name' => $n('Ölçmədəki təzyiq', 'Давление при измерении', 'Pressure at measurement'), 'type' => 'decimal', 'unit' => 'bar'],
];
$flush = [
    ['slug' => 'kart-san-buraxma-tam', 'name' => $n('Su buraxma həcmi, tam', 'Объём слива, полный', 'Flush volume, full'), 'type' => 'decimal', 'unit' => 'l'],
    ['slug' => 'kart-san-buraxma-qenaetli', 'name' => $n('Su buraxma həcmi, qənaətli', 'Объём слива, экономный', 'Flush volume, economy'), 'type' => 'decimal', 'unit' => 'l',
        'rules' => ['lte' => 'kart-san-buraxma-tam']],
];
$connection = ['slug' => 'kart-san-qosulma-olcusu', 'name' => $n('Qoşulma ölçüsü', 'Присоединительный размер', 'Connection size'), 'type' => 'dropdown',
    'hint' => $n('İstehsalçıya görə: yiv ölçüsü, düym; boru diametri, mm.', 'По данным производителя: резьба в дюймах или диаметр трубы в мм.', 'Per the maker: thread in inches or pipe diameter in mm.'),
    'options' => [$a('1/2″', ['slug' => '1-2']), $a('3/8″', ['slug' => '3-8']), $a('3/4″', ['slug' => '3-4']), $a('M24×1', ['slug' => 'm24']), $other($P_SIZE, 'Digər ölçü')]];
$power = [
    ['slug' => 'kart-san-elektrik', 'name' => $n('Elektrik qidalanması', 'Электропитание', 'Power supply'), 'type' => 'dropdown', 'options' => $opts([
        ['Tələb olunur', 'teleb-olunur'], ['Tələb olunmur', 'teleb-olunmur'],
    ])],
    ['slug' => 'kart-san-gerginlik', 'name' => $n('Gərginlik', 'Напряжение', 'Voltage'), 'type' => 'decimal', 'unit' => 'V', 'when' => ['attr' => 'kart-san-elektrik', 'in' => ['teleb-olunur']]],
    ['slug' => 'kart-san-guc', 'name' => $n('Güc', 'Мощность', 'Power'), 'type' => 'decimal', 'unit' => 'W', 'when' => ['attr' => 'kart-san-elektrik', 'in' => ['teleb-olunur']]],
];
// One slug per wording: an attribute keeps one name everywhere (CardImporter checks).
$compat = fn (bool $req = true, ?array $name = null, ?array $hint = null, string $key = 'uygun-model'): array => [
    'slug' => 'kart-san-'.$key, 'name' => $name ?? $n('Uyğun model və ya sistem', 'Совместимая модель или система', 'Compatible model or system'), 'type' => 'text', 'required' => $req,
    'hint' => $hint ?? $n('Brend + seriya/model və ya istehsalçının uyğunluq kodu. Universal olduqda ölçü və uyğunluq şərtləri.', 'Бренд + серия/модель или код совместимости. Для универсальных — размеры и условия.', 'Brand + series/model or the maker’s compatibility code. For universal parts, the size and conditions.'),
];
$packCount = ['slug' => 'kart-san-paketde-say', 'name' => $n('Paketdə sayı', 'Количество в упаковке', 'Pieces per pack'), 'type' => 'decimal', 'unit' => 'ədəd',
    'rules' => ['integer' => true], 'hint' => $n('Tək məhsulda 1.', 'Для одиночного товара — 1.', '1 for a single item.')];

// Glass.
$glassThick = fn (bool $req = false): array => ['slug' => 'kart-san-suse-qalinligi', 'name' => $n('Şüşə qalınlığı', 'Толщина стекла', 'Glass thickness'), 'type' => 'decimal', 'required' => $req, 'unit' => 'mm'];
$tempered = ['slug' => 'kart-san-temperli', 'name' => $n('Temperli şüşə', 'Закалённое стекло', 'Tempered glass'), 'type' => 'dropdown', 'options' => $ynn];
$glassLook = ['slug' => 'kart-san-suse-gorunusu', 'name' => $n('Şüşə görünüşü', 'Вид стекла', 'Glass look'), 'type' => 'dropdown', 'options' => $opts([
    ['Şəffaf', 'seffaf'], ['Mat', 'mat'], ['Rəngli', 'rengli'], ['Naxışlı', 'naxisli'],
])];
$hand = ['slug' => 'kart-san-istiqamet', 'name' => $n('İstiqamət', 'Сторона', 'Hand'), 'type' => 'dropdown', 'options' => $opts([
    ['Sol', 'sol'], ['Sağ', 'sag'], ['Çevrilə bilən', 'cevrile-bilen'],
])];
$widthRange = fn (bool $req = false): array => [
    ['slug' => 'kart-san-montaj-eni-min', 'name' => $n('Montaj eni, min', 'Монтажная ширина, мин.', 'Fitting width, min'), 'type' => 'decimal', 'required' => $req, 'unit' => 'mm',
        'rules' => ['lte' => 'kart-san-montaj-eni-maks']],
    ['slug' => 'kart-san-montaj-eni-maks', 'name' => $n('Montaj eni, maks', 'Монтажная ширина, макс.', 'Fitting width, max'), 'type' => 'decimal', 'required' => $req, 'unit' => 'mm'],
];

// Accessories.
$accMount = ['slug' => 'kart-san-aksesuar-montaj', 'name' => $n('Montaj', 'Монтаж', 'Mounting'), 'type' => 'dropdown', 'required' => true, 'options' => $opts([
    ['Divara vintlə', 'vintle'], ['Yapışdırılan', 'yapisdirilan'], ['Səthüstü', 'sethustu'], ['Sərbəst dayanan', 'serbest'],
])];
$fixingKit = ['slug' => 'kart-san-komplekt-berkitme', 'name' => $n('Komplektə daxildir', 'В комплекте', 'Included'), 'type' => 'multiselect',
    'hint' => $n('Yalnız qiymətə daxil olanlar.', 'Только то, что входит в цену.', 'Only what the price includes.'),
    'options' => [$a('Bərkitmə dəsti', ['slug' => 'berkitme']), $none()]];

// «Komplektə daxildir» — one list per card; «Daxil deyil» stands alone.
$kit = fn (string $code, array $items): array => [
    'slug' => 'kart-'.strtolower(str_replace('_', '-', $code)).'-komplekt', 'name' => $n('Komplektə daxildir', 'В комплекте', 'Included'), 'type' => 'multiselect',
    'hint' => $n('Yalnız qiymətə daxil olanlar.', 'Только то, что входит в цену.', 'Only what the price includes.'),
    'options' => [...$opts($items), $none()],
];
$f = fn (string $code, string $key, array $name, string $type, array $extra = []): array => ['slug' => 'kart-'.strtolower(str_replace('_', '-', $code)).'-'.$key, 'name' => $name, 'type' => $type] + $extra;

// Units («Satış vahidi»).
$pieceOrSet = ['sale' => 'unit', 'units' => ['piece', 'set']];
$pieceOnly = ['sale' => 'unit', 'units' => ['piece']];
$setOnly = ['sale' => 'unit', 'units' => ['set']];
$card = fn (string $code, array $name, array $sale, array $fields): array => ['code' => $code, 'name' => $name, ...$sale, 'fields' => $fields];

return [
    'section' => 'santexnika-su-techizati-ve-kanalizasiya',

    'groups' => [
        // ════ 1. Unitazlar və bidelər ══════════════════════════════════════
        [
            'slug' => 'unitazlar-ve-bideler',
            'name' => $n('Unitazlar və bidelər', 'Унитазы и биде', 'Toilets and bidets'),
            'classes' => [
                'unitazlar' => $card('S01_1', $n('Unitazlar', 'Унитазы', 'Toilets'), $pieceOrSet, [
                    $floorWall,
                    $f('S01_1', 'konstruksiya', $n('Konstruksiya', 'Конструкция', 'Design'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Kompakt, açıq çənli', 'kompakt'], ['Monoblok', 'monoblok'], ['Gizli çən üçün çanaq', 'gizli-cen'],
                    ])]),
                    ['required' => true] + $sanMaterial,
                    ...$dims(),
                    ...$look,
                    $drainOutlet,
                    $f('S01_1', 'rimless', $n('Kənarsız çanaq (Rimless)', 'Безободковый (Rimless)', 'Rimless bowl'), 'dropdown', ['options' => $ynn]),
                    ...$flush,
                    $kit('S01_1', [['Çən', 'cen'], ['Oturacaq-qapaq', 'oturacaq'], ['Bərkitmə', 'berkitme'], ['Qoşulma dəsti', 'qosulma']]),
                    $f('S01_1', 'yumsaq', $n('Oturacağın yumşaq bağlanması', 'Микролифт сиденья', 'Soft-close seat'), 'dropdown', ['options' => [
                        $yes, $no, $a('Oturacaq daxil deyil', ['slug' => 'oturacaq-yoxdur']), $ns,
                    ]]),
                    ...$guarantee,
                ]),
                'bideler' => $card('S01_2', $n('Bidelər', 'Биде', 'Bidets'), $pieceOrSet, [
                    $floorWall,
                    ['required' => true] + $sanMaterial,
                    ...$dims(),
                    ...$look,
                    $holes,
                    $overflow,
                    $kit('S01_2', [['Qarışdırıcı', 'qarisdirici'], ['Sifon', 'sifon'], ['Dib klapanı', 'dib-klapani'], ['Bərkitmə', 'berkitme']]),
                    ...$guarantee,
                ]),
                'bide-funksiyali-unitazlar' => $card('S01_3', $n('Bide funksiyalı unitazlar', 'Унитазы с функцией биде', 'Toilets with bidet function'), $pieceOrSet, [
                    $floorWall,
                    $f('S01_3', 'idareetme', $n('İdarəetmə', 'Управление', 'Control'), 'dropdown', ['required' => true, 'options' => $opts([['Mexaniki', 'mexaniki'], ['Elektron', 'elektron']])]),
                    ...$dims(),
                    ...$look,
                    $drainOutlet,
                    $f('S01_3', 'funksiyalar', $n('Funksiyalar', 'Функции', 'Functions'), 'multiselect', ['options' => [...$opts([
                        ['Yuma', 'yuma'], ['Qurulama', 'qurulama'], ['Suyun qızdırılması', 'su-qizdirma'], ['Oturacaq isidilməsi', 'oturacaq-isidilmesi'], ['Avtomatik qapaq', 'avtomatik-qapaq'],
                    ]), $other()]]),
                    ['required' => true] + $power[0], $power[1], $power[2],
                    $f('S01_3', 'su', $n('Su təchizatı', 'Подвод воды', 'Water supply'), 'dropdown', ['required' => true, 'options' => $opts([['Yalnız soyuq', 'soyuq'], ['İsti və soyuq', 'isti-soyuq']])]),
                    $kit('S01_3', [['Çən', 'cen'], ['Oturacaq', 'oturacaq'], ['Pult', 'pult'], ['Filtr', 'filtr'], ['Bərkitmə', 'berkitme'], ['Qoşulma dəsti', 'qosulma']]),
                    ...$guarantee,
                ]),
            ],
        ],

        // ════ 2. Əlüzyuyanlar ══════════════════════════════════════════════
        [
            'slug' => 'santex-eluzyuyanlar',
            'name' => $n('Əlüzyuyanlar', 'Раковины', 'Washbasins'),
            'classes' => [
                'canaqlar-eluzyuyanlar' => $card('S02', $n('Əlüzyuyanlar', 'Раковины', 'Washbasins'), $pieceOrSet, [
                    $f('S02', 'montaj', $n('Montaj növü', 'Тип монтажа', 'Mounting'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Tezgahüstü', 'tezgahustu'], ['Tezgaha gömülən', 'gomulen'], ['Tezgahaltı', 'tezgahalti'], ['Divardan asılan', 'divardan-asilan'],
                        ['Postamentli', 'postamentli'], ['Sərbəst dayanan', 'serbest'], ['Mebelüstü', 'mebelustu'],
                    ]), 'hint' => $n('Mebelüstü modeldə uyğun mebel seriyasını təsvirdə göstərin.', 'Для модели на мебель укажите серию мебели в описании.', 'For a furniture basin, name the matching furniture series in the description.')]),
                    ['required' => true] + $sanMaterial,
                    ...$dims(),
                    ...$look,
                    $f('S02', 'canaq-sayi', $n('Çanaq sayı', 'Число чаш', 'Bowls'), 'dropdown', ['options' => [$a('1', ['slug' => '1']), $a('2', ['slug' => '2']), $other()]]),
                    $holes,
                    $overflow,
                    $f('S02', 'forma', $n('Forma', 'Форма', 'Shape'), 'dropdown', ['options' => [...$opts([
                        ['Düzbucaqlı', 'duzbucaqli'], ['Oval', 'oval'], ['Dairəvi', 'dairevi'], ['Kvadrat', 'kvadrat'], ['Künc', 'kunc'],
                    ]), $other()]]),
                    $kit('S02', [['Postament', 'postament'], ['Bərkitmə', 'berkitme'], ['Dib klapanı', 'dib-klapani'], ['Sifon', 'sifon']]),
                    ...$guarantee,
                ]),
            ],
        ],

        // ════ 3. Mətbəx çanaqları ══════════════════════════════════════════
        [
            'slug' => 'santex-metbex-canaqlari',
            'name' => $n('Mətbəx çanaqları', 'Кухонные мойки', 'Kitchen sinks'),
            'classes' => [
                'metbex-canaqlari' => $card('S03', $n('Mətbəx çanaqları', 'Кухонные мойки', 'Kitchen sinks'), $pieceOrSet, [
                    $f('S03', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Paslanmayan polad', 'paslanmayan-polad'], ['Qranit kompozit', 'qranit-kompozit'], ['Keramika', 'keramika'], ['Mineral kompozit', 'mineral-kompozit'],
                    ]), $other()]]),
                    $f('S03', 'montaj', $n('Montaj', 'Монтаж', 'Mounting'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Üstdən oturdulan', 'ustden'], ['Tezgahaltı', 'tezgahalti'], ['Tezgahla eyni səviyyədə', 'eyni-seviyye'], ['Tumbaüstü', 'tumbaustu'],
                    ])]),
                    $size(),
                    $f('S03', 'derinlik', $n('Çanağın dərinliyi', 'Глубина чаши', 'Bowl depth'), 'decimal', ['required' => true, 'unit' => 'mm',
                        'hint' => $n('İki çanaqda dərin olanınki.', 'Для двух чаш — более глубокой.', 'For two bowls, the deeper one.')]),
                    $f('S03', 'tumba', $n('Minimum tumba eni', 'Минимальная ширина тумбы', 'Minimum cabinet width'), 'decimal', ['required' => true, 'unit' => 'mm',
                        'hint' => $n('İstehsalçı tələbi.', 'Требование производителя.', 'The maker’s requirement.')]),
                    $f('S03', 'canaq-sayi', $n('Çanaqların sayı', 'Число чаш', 'Bowls'), 'dropdown', ['options' => [
                        $a('1', ['slug' => '1']), $a('1,5', ['slug' => '1-5']), $a('2', ['slug' => '2']), $other(),
                    ]]),
                    $f('S03', 'qanad', $n('Qurutma qanadı', 'Крыло для сушки', 'Drainer'), 'dropdown', ['options' => $opts([
                        ['Yoxdur', 'yoxdur'], ['Solda', 'solda'], ['Sağda', 'sagda'], ['Çevrilə bilən', 'cevrile-bilen'],
                    ])]),
                    ...$look,
                    $holes,
                    $kit('S03', [['Sifon', 'sifon'], ['Səbətli klapan', 'sebetli-klapan'], ['Bərkitmə', 'berkitme'], ['Kəsmə şablonu', 'sablon'], ['Digər aksesuar', 'aksesuar']]),
                    ...$guarantee,
                ]),
            ],
        ],

        // ════ 4. Su qarışdırıcıları ════════════════════════════════════════
        [
            'slug' => 'su-qarisdiricilari',
            'name' => $n('Su qarışdırıcıları', 'Смесители', 'Mixer taps'),
            'classes' => [
                'qarisdiricilar' => $card('S04_1', $n('Əlüzyuyan qarışdırıcıları', 'Смесители для раковины', 'Basin mixers'), $pieceOrSet, [
                    $control,
                    $f('S04', 'montaj', $n('Montaj növü', 'Тип монтажа', 'Mounting'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Səth üzərinə', 'seth'], ['Açıq divar montajı', 'aciq-divar'], ['Gizli divar montajı', 'gizli-divar'], ['Döşəməüstü', 'dosemeustu'],
                    ])]),
                    ['required' => true] + $metalMaterial,
                    ...$look,
                    $spoutHeight,
                    $spoutReach,
                    $f('S04_1', 'desik', $n('Montaj deşiklərinin sayı', 'Монтажные отверстия', 'Mounting holes'), 'decimal', ['unit' => 'ədəd', 'rules' => ['integer' => true]]),
                    ...$concealed,
                    ...$pressure,
                    ...$flow,
                    $kit('S04_1', [['Dib klapanı', 'dib-klapani'], ['Elastik bağlantılar', 'elastik'], ['Bərkitmə', 'berkitme']]),
                    ...$guarantee,
                ]),
                'metbex-qarisdiricilari' => $card('S04_2', $n('Mətbəx qarışdırıcıları', 'Кухонные смесители', 'Kitchen mixers'), $pieceOrSet, [
                    $control,
                    $f('S04_2', 'montaj', $n('Montaj növü', 'Тип монтажа', 'Mounting'), 'dropdown', ['required' => true, 'options' => $opts([['Çanaq/tezgah üzərinə', 'tezgah'], ['Divara', 'divar']])]),
                    ['required' => true] + $metalMaterial,
                    ...$look,
                    $spoutHeight,
                    $spoutReach,
                    $f('S04_2', 'cixarilan', $n('Çıxarılan duş başlığı', 'Выдвижная лейка', 'Pull-out spray'), 'dropdown', ['options' => $ynn]),
                    $f('S04_2', 'donen', $n('Dönən su çıxışı', 'Поворотный излив', 'Swivel spout'), 'dropdown', ['options' => $ynn]),
                    $f('S04_2', 'filtr', $n('Filtrli su üçün ayrıca kanal', 'Отдельный канал для фильтрованной воды', 'Separate filtered-water channel'), 'dropdown', ['options' => $ynn]),
                    ...$pressure,
                    $kit('S04_2', [['Elastik bağlantılar', 'elastik'], ['Bərkitmə', 'berkitme'], ['Filtr sistemi', 'filtr']]),
                    ...$guarantee,
                ]),
                'vanna-qarisdiricilari' => $card('S04_3', $n('Vanna qarışdırıcıları', 'Смесители для ванны', 'Bath mixers'), $pieceOrSet, [
                    $control,
                    $f('S04', 'montaj', $n('Montaj növü', 'Тип монтажа', 'Mounting'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Səth üzərinə', 'seth'], ['Açıq divar montajı', 'aciq-divar'], ['Gizli divar montajı', 'gizli-divar'], ['Döşəməüstü', 'dosemeustu'],
                    ])]),
                    ['required' => true] + $metalMaterial,
                    ...$look,
                    $f('S04_3', 'yonlendirici', $n('Duşa yönləndirici', 'Переключатель на душ', 'Shower diverter'), 'dropdown', ['options' => $opts([['Var', 'var'], ['Yoxdur', 'yoxdur']])]),
                    $spoutReach,
                    $f('S04_3', 'montaj-olcusu', $n('Montaj ölçüsü', 'Монтажный размер', 'Mounting dimension'), 'text', [
                        'hint' => $n('Açıq divar montajında mərkəzlərarası məsafə, mm; səth montajında deşik sayı.', 'Для открытого настенного — межосевое расстояние, мм; для монтажа на борт — число отверстий.', 'Exposed wall: centre distance, mm; deck mount: number of holes.')]),
                    ...$concealed,
                    ...$pressure,
                    $kit('S04_3', [['Əl duşu', 'el-dusu'], ['Şlanq', 'slanq'], ['Tutacaq', 'tutacaq'], ['Bərkitmə', 'berkitme']]),
                    ...$guarantee,
                ]),
                'dus-qarisdiricilari' => $card('S04_4', $n('Duş qarışdırıcıları', 'Смесители для душа', 'Shower mixers'), $pieceOrSet, [
                    $control,
                    $f('S04_4', 'montaj', $n('Montaj növü', 'Тип монтажа', 'Mounting'), 'dropdown', ['required' => true, 'options' => $opts([['Açıq divar montajı', 'aciq-divar'], ['Gizli divar montajı', 'gizli-divar']])]),
                    ['required' => true] + $metalMaterial,
                    ...$look,
                    $f('S04_4', 'cixislar', $n('İdarə olunan su çıxışları', 'Управляемые выходы', 'Controlled outlets'), 'decimal', ['unit' => 'ədəd', 'rules' => ['integer' => true]]),
                    $f('S04_4', 'merkez', $n('Mərkəzlərarası məsafə', 'Межосевое расстояние', 'Centre distance'), 'decimal', ['unit' => 'mm',
                        'when' => $when('kart-s04-4-montaj', 'aciq-divar')]),
                    ...$concealed,
                    ...$pressure,
                    $kit('S04_4', [['Bərkitmə', 'berkitme'], ['Dekorativ örtük', 'ortuk'], ['Yönləndirici', 'yonlendirici']]),
                    ...$guarantee,
                ]),
                'bide-qarisdiricilari' => $card('S04_5', $n('Bide qarışdırıcıları', 'Смесители для биде', 'Bidet mixers'), $pieceOrSet, [
                    $control,
                    $f('S04_5', 'montaj', $n('Montaj növü', 'Тип монтажа', 'Mounting'), 'dropdown', ['required' => true, 'options' => $opts([['Bide üzərinə', 'bide'], ['Divara', 'divar']])]),
                    ['required' => true] + $metalMaterial,
                    ...$look,
                    $spoutHeight,
                    $spoutReach,
                    $f('S04_5', 'aerator', $n('Hərəkətli aerator', 'Подвижный аэратор', 'Swivel aerator'), 'dropdown', ['options' => $ynn]),
                    ...$concealed,
                    ...$pressure,
                    $kit('S04_5', [['Dib klapanı', 'dib-klapani'], ['Elastik bağlantılar', 'elastik'], ['Bərkitmə', 'berkitme']]),
                    ...$guarantee,
                ]),
            ],
        ],

        // ════ 5. Duş sistemləri və dəstləri ════════════════════════════════
        [
            'slug' => 'dus-sistemleri-ve-destleri',
            'name' => $n('Duş sistemləri və dəstləri', 'Душевые системы и гарнитуры', 'Shower systems and sets'),
            'classes' => [
                'dus-sistemleri' => $card('S05_1', $n('Duş sistemləri', 'Душевые системы', 'Shower systems'), $pieceOrSet, [
                    $f('S05_1', 'montaj', $n('Montaj növü', 'Тип монтажа', 'Mounting'), 'dropdown', ['required' => true, 'options' => $opts([['Açıq divar montajı', 'aciq-divar'], ['Gizli sistem', 'gizli']])]),
                    $f('S05_1', 'qarisdirici', $n('Qarışdırıcı', 'Смеситель', 'Mixer'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Adi daxildir', 'adi'], ['Termostatik daxildir', 'termostatik'], ['Daxil deyil', 'daxil-deyil'],
                    ])]),
                    ['required' => true] + $metalMaterial,
                    ...$look,
                    $f('S05_1', 'direk', $n('Dirəyin hündürlüyü', 'Высота штанги', 'Rail height'), 'decimal', ['unit' => 'mm', 'when' => $when('kart-s05-1-montaj', 'aciq-divar')]),
                    $f('S05_1', 'ust-dus', $n('Üst duşun ölçüsü', 'Размер верхнего душа', 'Overhead shower size'), 'text', [
                        'hint' => $n('Diametr və ya uzunluq × en, mm.', 'Диаметр или длина × ширина, мм.', 'Diameter or length × width, mm.')]),
                    $f('S05_1', 'rejim', $n('Əl duşunda rejim sayı', 'Режимы ручного душа', 'Hand-shower modes'), 'decimal', ['unit' => 'ədəd', 'rules' => ['integer' => true]]),
                    ...$pressure,
                    ...$concealed,
                    $kit('S05_1', [['Üst duş', 'ust-dus'], ['Əl duşu', 'el-dusu'], ['Şlanq', 'slanq'], ['Dirək', 'direk'], ['Duş qolu', 'dus-qolu'], ['Divar çıxışı', 'divar-cixisi']]),
                    ...$guarantee,
                ]),
                'el-dusu-destleri' => $card('S05_2', $n('Əl duşu dəstləri', 'Душевые гарнитуры', 'Hand-shower sets'), $pieceOrSet, [
                    $f('S05_2', 'nov', $n('Dəst növü', 'Тип гарнитура', 'Set type'), 'dropdown', ['required' => true, 'options' => $opts([['Sürüşən dirəkli', 'direkli'], ['Sabit tutacaqlı', 'tutacaqli']])]),
                    $shMaterial,
                    ...$look,
                    $f('S05_2', 'direk', $n('Dirəyin uzunluğu', 'Длина штанги', 'Rail length'), 'decimal', ['unit' => 'mm', 'when' => $when('kart-s05-2-nov', 'direkli')]),
                    $f('S05_2', 'slanq', $n('Şlanqın uzunluğu', 'Длина шланга', 'Hose length'), 'decimal', ['unit' => 'mm']),
                    $f('S05_2', 'rejim', $n('Duş rejimlərinin sayı', 'Число режимов', 'Spray modes'), 'decimal', ['unit' => 'ədəd', 'rules' => ['integer' => true]]),
                    $connection,
                    $kit('S05_2', [['Əl duşu', 'el-dusu'], ['Şlanq', 'slanq'], ['Dirək', 'direk'], ['Tutacaq', 'tutacaq'], ['Sabunqabı', 'sabunqabi']]),
                    ...$guarantee,
                ]),
                'ust-dus-basliqlari' => $card('S05_3', $n('Üst duş başlıqları', 'Верхние души', 'Overhead showers'), $pieceOrSet, [
                    $f('S05_3', 'montaj', $n('Montaj', 'Монтаж', 'Mounting'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Divar qolu ilə', 'divar-qolu'], ['Tavan qolu ilə', 'tavan-qolu'], ['Tavana gömülən', 'tavana-gomulen'],
                    ])]),
                    $f('S05_3', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['options' => [...$opts([
                        ['Paslanmayan polad', 'paslanmayan-polad'], ['Pirinç', 'pirinc'], ['Plastik', 'plastik'],
                    ]), $other()]]),
                    $f('S05_3', 'forma', $n('Forma', 'Форма', 'Shape'), 'dropdown', ['options' => [...$opts([['Dairəvi', 'dairevi'], ['Kvadrat', 'kvadrat'], ['Düzbucaqlı', 'duzbucaqli']]), $other()]]),
                    $f('S05_3', 'olcu', $n('Ölçü', 'Размер', 'Size'), 'text', ['required' => true,
                        'hint' => $n('Diametr və ya uzunluq × en, mm.', 'Диаметр или длина × ширина, мм.', 'Diameter or length × width, mm.')]),
                    ...$look,
                    $f('S05_3', 'rejim', $n('Rejim sayı', 'Число режимов', 'Spray modes'), 'decimal', ['unit' => 'ədəd', 'rules' => ['integer' => true]]),
                    $connection,
                    ...$flow,
                    $kit('S05_3', [['Duş qolu', 'dus-qolu'], ['Montaj hissələri', 'montaj']]),
                    ...$guarantee,
                ]),
                'el-dusu-basliqlari' => $card('S05_4', $n('Əl duşu başlıqları', 'Ручные лейки', 'Hand showers'), $pieceOnly, [
                    $shMaterial,
                    ...$look,
                    $f('S05_4', 'olcu', $n('Başlığın ölçüsü', 'Размер лейки', 'Head size'), 'decimal', ['unit' => 'mm',
                        'hint' => $n('Diametr və ya maksimum en.', 'Диаметр или наибольшая ширина.', 'Diameter or the largest width.')]),
                    $f('S05_4', 'rejim', $n('Rejim sayı', 'Число режимов', 'Spray modes'), 'decimal', ['unit' => 'ədəd', 'rules' => ['integer' => true]]),
                    $connection,
                    ...$flow,
                    $f('S05_4', 'ehenge-qarsi', $n('Əhəngə qarşı təmizlənən ucluqlar', 'Сопла с защитой от известкового налёта', 'Anti-limescale nozzles'), 'dropdown', ['options' => $ynn]),
                    ...$guarantee,
                ]),
            ],
        ],

        // ════ 6. Gigiyenik duşlar ══════════════════════════════════════════
        [
            'slug' => 'santex-gigiyenik-duslar',
            'name' => $n('Gigiyenik duşlar', 'Гигиенические души', 'Bidet showers'),
            'classes' => [
                'gigiyenik-duslar' => $card('S06', $n('Gigiyenik duşlar', 'Гигиенические души', 'Bidet showers'), $pieceOrSet, [
                    $f('S06', 'techizat', $n('Təchizat növü', 'Комплектация', 'Supply'), 'dropdown', ['required' => true, 'options' => $opts([['Tam dəst', 'tam-dest'], ['Yalnız başlıq', 'baslig']])]),
                    $f('S06', 'qarisdirici', $n('Qarışdırıcı', 'Смеситель', 'Mixer'), 'dropdown', ['options' => $opts([
                        ['Adi daxildir', 'adi'], ['Termostatik daxildir', 'termostatik'], ['Daxil deyil', 'daxil-deyil'],
                    ])]),
                    $f('S06', 'montaj', $n('Montaj növü', 'Тип монтажа', 'Mounting'), 'dropdown', ['options' => [...$opts([
                        ['Açıq divar montajı', 'aciq-divar'], ['Gizli montaj', 'gizli'], ['Əlüzyuyan qarışdırıcısına qoşulan', 'qarisdiriciya'],
                    ]), $na]]),
                    ['required' => true] + $metalMaterial,
                    ...$look,
                    $f('S06', 'slanq', $n('Şlanqın uzunluğu', 'Длина шланга', 'Hose length'), 'decimal', ['unit' => 'mm']),
                    $connection,
                    ...$concealed,
                    $kit('S06', [['Şlanq', 'slanq'], ['Tutacaq', 'tutacaq'], ['Divar çıxışı', 'divar-cixisi'], ['Bağlayıcı kran', 'kran']]),
                    ...$guarantee,
                ]),
            ],
        ],

        // ════ 7. Vannalar ══════════════════════════════════════════════════
        [
            'slug' => 'santex-vannalar',
            'name' => $n('Vannalar', 'Ванны', 'Bathtubs'),
            'classes' => [
                'vannalar' => $card('S07', $n('Vannalar', 'Ванны', 'Bathtubs'), $pieceOrSet, [
                    $f('S07', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Akril', 'akril'], ['Emallı polad', 'emalli-polad'], ['Çuqun', 'cuqun'], ['Mineral kompozit', 'mineral-kompozit'],
                    ]), $other()]]),
                    $f('S07', 'montaj', $n('Montaj növü', 'Тип монтажа', 'Mounting'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Sərbəst dayanan', 'serbest'], ['Divara bitişik', 'divara'], ['Gömülən', 'gomulen'], ['Künc', 'kunc'],
                    ])]),
                    ...$dims(),
                    $f('S07', 'forma', $n('Forma', 'Форма', 'Shape'), 'dropdown', ['options' => [...$opts([
                        ['Düzbucaqlı', 'duzbucaqli'], ['Oval', 'oval'], ['Dairəvi', 'dairevi'], ['Asimmetrik', 'asimmetrik'],
                    ]), $other()]]),
                    ...$look,
                    $f('S07', 'istiqamet', $n('İstiqamət', 'Сторона', 'Hand'), 'dropdown', ['options' => $opts([['Simmetrik', 'simmetrik'], ['Sol', 'sol'], ['Sağ', 'sag']])]),
                    $f('S07', 'cixis', $n('Suyun çıxış yeri', 'Расположение слива', 'Drain position'), 'dropdown', ['options' => [...$opts([['Mərkəzdə', 'merkezde'], ['Ayaq tərəfində', 'ayaq']]), $other()]]),
                    $f('S07', 'hecm', $n('Həcm', 'Объём', 'Capacity'), 'decimal', ['unit' => 'l', 'hint' => $n('İstehsalçı məlumatına görə.', 'По данным производителя.', 'Per the maker.')]),
                    $f('S07', 'hidromasaj', $n('Hidromasaj', 'Гидромассаж', 'Whirlpool'), 'dropdown', ['options' => $ynn]),
                    $kit('S07', [['Ayaqlar', 'ayaqlar'], ['Karkas', 'karkas'], ['Ön panel', 'on-panel'], ['Yan panel', 'yan-panel'], ['Sifon', 'sifon'], ['Daşma dəsti', 'dasma']]),
                    ...$guarantee,
                ]),
            ],
        ],

        // ════ 8. Duş kabinələri, arakəsmələri və altlıqları ════════════════
        [
            'slug' => 'dus-kabineleri-arakesmeleri-ve-altliqlari',
            'name' => $n('Duş kabinələri, arakəsmələri və altlıqları', 'Душевые кабины, ограждения и поддоны', 'Shower enclosures, screens and trays'),
            'classes' => [
                'dus-kabinesi-dus-sistemi' => $card('S08_1', $n('Duş kabinələri', 'Душевые кабины', 'Shower cabins'), $pieceOrSet, [
                    $f('S08_1', 'forma', $n('Forma', 'Форма', 'Shape'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Kvadrat', 'kvadrat'], ['Düzbucaqlı', 'duzbucaqli'], ['Dairəvi künc', 'dairevi-kunc'],
                    ]), $other()]]),
                    ...$dims(),
                    $f('S08_1', 'qapi', $n('Qapı mexanizmi', 'Механизм двери', 'Door mechanism'), 'dropdown', ['options' => $opts([['Sürüşən', 'surusen'], ['Açılan', 'acilan'], ['Qatlanan', 'qatlanan']])]),
                    $glassLook,
                    $glassThick(),
                    $f('S08_1', 'altliq', $n('Altlıq', 'Поддон', 'Tray'), 'dropdown', ['options' => $opts([['Aşağı', 'asagi'], ['Dərin', 'derin'], ['Daxil deyil', 'daxil-deyil']])]),
                    ...$look,
                    ...$power,
                    $f('S08_1', 'funksiyalar', $n('Əlavə funksiyalar', 'Дополнительные функции', 'Extra functions'), 'multiselect', ['options' => [...$opts([
                        ['Hidromasaj', 'hidromasaj'], ['Buxar', 'buxar'], ['İşıq', 'isiq'], ['Ventilyator', 'ventilyator'],
                    ]), $a('Yoxdur', ['slug' => 'yoxdur', 'exclusive' => true])]]),
                    $kit('S08_1', [['Qarışdırıcı', 'qarisdirici'], ['Üst duş', 'ust-dus'], ['Əl duşu', 'el-dusu'], ['Sifon', 'sifon'], ['Arxa divarlar', 'arxa-divarlar'], ['Dam', 'dam']]),
                    ...$guarantee,
                ]),
                'dus-arakesmeleri' => $card('S08_2', $n('Duş arakəsmələri', 'Душевые ограждения', 'Shower screens'), $pieceOrSet, [
                    $f('S08_2', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Sabit Walk-in', 'walk-in'], ['Künc dəsti', 'kunc'], ['Yan panel', 'yan-panel'],
                    ]), $other()]]),
                    $sizeWH(true, $n('En × hündürlük. Künc dəstində hər tərəfin enini təsvirdə yazın.', 'Ширина × высота. Для углового комплекта — ширина каждой стороны в описании.', 'Width × height. For a corner set, give each side in the description.')),
                    ...$widthRange(),
                    $glassThick(true),
                    $tempered,
                    $glassLook,
                    ...$look,
                    $hand,
                    $f('S08_2', 'ortuk', $n('Asan təmizlənən örtük', 'Лёгкая очистка', 'Easy-clean coating'), 'dropdown', ['options' => $ynn]),
                    $kit('S08_2', [['Divar profili', 'profil'], ['Sabitləşdirici qol', 'qol'], ['Bərkitmə', 'berkitme']]),
                    ...$guarantee,
                ]),
                'dus-qapilari' => $card('S08_3', $n('Duş qapıları', 'Душевые двери', 'Shower doors'), $pieceOrSet, [
                    $f('S08_3', 'acilma', $n('Açılma növü', 'Тип открывания', 'Opening'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Açılan', 'acilan'], ['Sürüşən', 'surusen'], ['Qatlanan', 'qatlanan'], ['Pivot', 'pivot'],
                    ])]),
                    $sizeWH(),
                    ...$widthRange(true),
                    $glassThick(),
                    $tempered,
                    $glassLook,
                    ...$look,
                    $hand,
                    $kit('S08_3', [['Profil', 'profil'], ['Tutacaq', 'tutacaq'], ['Menteşə və ya çarx', 'mentese'], ['Sızdırmazlıq lentləri', 'lent']]),
                    ...$guarantee,
                ]),
                'dus-altliqlari' => $card('S08_4', $n('Duş altlıqları', 'Душевые поддоны', 'Shower trays'), $pieceOrSet, [
                    $f('S08_4', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Akril', 'akril'], ['Emallı polad', 'emalli-polad'], ['Keramika', 'keramika'], ['Mineral kompozit', 'mineral-kompozit'],
                    ]), $other()]]),
                    ...$dims(),
                    $f('S08_4', 'forma', $n('Forma', 'Форма', 'Shape'), 'dropdown', ['options' => [...$opts([
                        ['Kvadrat', 'kvadrat'], ['Düzbucaqlı', 'duzbucaqli'], ['Dairəvi künc', 'dairevi-kunc'],
                    ]), $other()]]),
                    ...$look,
                    $f('S08_4', 'montaj', $n('Montaj', 'Монтаж', 'Mounting'), 'dropdown', ['options' => $opts([
                        ['Döşəmə səviyyəsində', 'seviyyede'], ['Döşəmə üzərində', 'uzerinde'], ['Ayaqlı', 'ayaqli'],
                    ])]),
                    $f('S08_4', 'sifon-diametri', $n('Sifon dəliyinin diametri', 'Диаметр сливного отверстия', 'Waste hole diameter'), 'decimal', ['required' => true, 'unit' => 'mm']),
                    $f('S08_4', 'sifon-yeri', $n('Sifonun yeri', 'Расположение слива', 'Waste position'), 'dropdown', ['options' => $opts([
                        ['Küncdə', 'kuncde'], ['Mərkəzdə', 'merkezde'], ['Kənarda', 'kenarda'],
                    ])]),
                    $f('S08_4', 'surusme', $n('Sürüşməyə qarşı səth', 'Противоскользящая поверхность', 'Anti-slip surface'), 'dropdown', ['options' => $ynn]),
                    $kit('S08_4', [['Sifon', 'sifon'], ['Ayaqlar', 'ayaqlar'], ['Karkas', 'karkas'], ['Panel', 'panel']]),
                    ...$guarantee,
                ]),
                'vanna-ucun-suse-arakesmeler' => $card('S08_5', $n('Vanna üçün şüşə arakəsmələr', 'Стеклянные шторки для ванны', 'Bath screens'), $pieceOrSet, [
                    $f('S08_5', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Sabit', 'sabit'], ['Açılan', 'acilan'], ['Qatlanan', 'qatlanan'], ['Sürüşən', 'surusen'],
                    ])]),
                    $sizeWH(),
                    $glassThick(),
                    $tempered,
                    $glassLook,
                    ...$look,
                    $hand,
                    $kit('S08_5', [['Profil', 'profil'], ['Menteşə', 'mentese'], ['Alt sızdırmazlıq lenti', 'lent'], ['Bərkitmə', 'berkitme']]),
                    ...$guarantee,
                ]),
            ],
        ],

        // ════ 9. İnstallyasiyalar və su buraxma düymələri ══════════════════
        [
            'slug' => 'installyasiyalar-ve-su-buraxma-duymeleri',
            'name' => $n('İnstallyasiyalar və su buraxma düymələri', 'Инсталляции и клавиши смыва', 'Installation frames and flush plates'),
            'classes' => [
                'unitaz-ucun-instalyasiya' => $card('S09_1', $n('İnstallyasiya çərçivələri', 'Инсталляции', 'Installation frames'), $pieceOrSet, [
                    $f('S09_1', 'teyinat', $n('Təyinat', 'Назначение', 'For'), 'dropdown', ['required' => true, 'options' => $opts([['Asma unitaz', 'unitaz'], ['Asma bide', 'bide']])]),
                    $f('S09_1', 'sistem', $n('Montaj sistemi', 'Система монтажа', 'Mounting system'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Karkas arakəsmə', 'karkas'], ['Möhkəm divar', 'mohkem-divar'], ['Universal', 'universal'],
                    ]), 'hint' => $n('İstehsalçıya görə.', 'По данным производителя.', 'Per the maker.')]),
                    ...$dims(),
                    $f('S09_1', 'derinlik-min', $n('Dərinlik tənzimlənməsi, min', 'Регулировка глубины, мин.', 'Depth adjustment, min'), 'decimal', ['unit' => 'mm',
                        'rules' => ['lte' => 'kart-s09-1-derinlik-maks']]),
                    $f('S09_1', 'derinlik-maks', $n('Dərinlik tənzimlənməsi, maks', 'Регулировка глубины, макс.', 'Depth adjustment, max'), 'decimal', ['unit' => 'mm']),
                    $f('S09_1', 'berkitme', $n('Sanitar cihazın bərkitmə məsafəsi', 'Межосевое расстояние крепления', 'Fixing centres'), 'decimal', ['required' => true, 'unit' => 'mm',
                        'hint' => $n('Mərkəzlərarası; adətən 180 və ya 230.', 'Межосевое; обычно 180 или 230.', 'Centre to centre; usually 180 or 230.')]),
                    $compat(false, $n('Çən sistemi (seriya / model)', 'Бачок (серия / модель)', 'Cistern (series / model)'),
                        $n('Unitaz üçün; bide çərçivəsində boş qalır.', 'Для унитаза; для биде оставьте пустым.', 'For a toilet frame; leave empty for a bidet frame.'), 'cen-sistemi'),
                    ...$flush,
                    $kit('S09_1', [['Çən', 'cen'], ['Düymə', 'duyme'], ['Divar bərkitməsi', 'divar-berkitmesi'], ['Cihaz bərkitməsi', 'cihaz-berkitmesi'], ['Qoşulma dəsti', 'qosulma']]),
                    ...$guarantee,
                ]),
                'gizli-su-cenleri' => $card('S09_2', $n('Gizli su çənləri', 'Скрытые бачки', 'Concealed cisterns'), $pieceOrSet, [
                    $compat(true, $n('Sistem və model', 'Система и модель', 'System and model'), $n('İstehsalçının çən seriyası və model kodu.', 'Серия и код модели бачка.', 'The maker’s cistern series and model code.'), 'sistem-ve-model'),
                    ...$dims(),
                    $f('S09_2', 'montaj', $n('Montaj', 'Монтаж', 'Mounting'), 'dropdown', ['required' => true, 'options' => $opts([['Möhkəm divarda', 'mohkem-divar'], ['Uyğun çərçivədə', 'cercive']])]),
                    $f('S09_2', 'idareetme', $n('İdarəetmə tərəfi', 'Сторона управления', 'Actuation side'), 'dropdown', ['options' => $opts([['Öndən', 'onden'], ['Yuxarıdan', 'yuxaridan'], ['Uzaqdan', 'uzaqdan']])]),
                    ...$flush,
                    $f('S09_2', 'giris', $n('Su girişinin tərəfi', 'Сторона подвода воды', 'Water inlet side'), 'dropdown', ['options' => [...$opts([['Yan', 'yan'], ['Üst', 'ust'], ['Arxa', 'arxa']]), $other()]]),
                    $kit('S09_2', [['Düymə', 'duyme'], ['Doldurma mexanizmi', 'doldurma'], ['Su buraxma mexanizmi', 'buraxma'], ['Qoşulma dəsti', 'qosulma']]),
                    ...$guarantee,
                ]),
                'su-buraxma-duymeleri' => $card('S09_3', $n('Su buraxma düymələri', 'Клавиши смыва', 'Flush plates'), $pieceOrSet, [
                    $compat(true, null, $n('Brend + seriya/model. Yalnız eyni brend olması uyğunluğu təsdiqləmir — çən sistemi və modeli də uyğun gəlməlidir.', 'Бренд + серия/модель. Один бренд ещё не гарантирует совместимость — нужны совместимые система бачка и модель.', 'Brand + series/model. The same brand alone does not prove a fit — the cistern system and model must match.')),
                    $f('S09_3', 'idareetme', $n('İdarəetmə', 'Управление', 'Actuation'), 'dropdown', ['required' => true, 'options' => $opts([['Mexaniki', 'mexaniki'], ['Pnevmatik', 'pnevmatik'], ['Elektron', 'elektron']])]),
                    $f('S09_3', 'rejim', $n('Rejim', 'Режим', 'Mode'), 'dropdown', ['options' => $opts([['Bir həcm', 'bir'], ['İki həcm', 'iki'], ['Start-stop', 'start-stop']])]),
                    ['required' => true] + $metalMaterial,
                    ...$look,
                    $size(),
                    $f('S09_3', 'montaj', $n('Montaj tərəfi', 'Сторона монтажа', 'Mounting side'), 'dropdown', ['options' => $opts([['Ön', 'on'], ['Üst', 'ust'], ['Uzaqdan', 'uzaqdan']])]),
                    $kit('S09_3', [['Montaj çərçivəsi', 'cercive'], ['İtələyici çubuqlar', 'cubuqlar'], ['Pnevmatik şlanq', 'slanq'], ['Elektron modul', 'modul']]),
                    ...$guarantee,
                ]),
            ],
        ],

        // ════ 10. Duş trapları və drenaj kanalları ═════════════════════════
        [
            'slug' => 'dus-traplari-ve-drenaj-kanallari',
            'name' => $n('Duş trapları və drenaj kanalları', 'Душевые трапы и лотки', 'Shower drains and channels'),
            'classes' => [
                'traplar-ve-drenajlar' => $card('S10_1', $n('Nöqtəvi duş trapları', 'Точечные трапы', 'Point drains'), $pieceOrSet, [
                    $f('S10_1', 'techizat', $n('Təchizat', 'Комплектация', 'Supply'), 'dropdown', ['required' => true, 'options' => $opts([['Tam trap', 'tam'], ['Yalnız gövdə', 'govde']])]),
                    $f('S10_1', 'material', $n('Gövdə materialı', 'Материал корпуса', 'Body material'), 'dropdown', ['options' => [...$opts([['Plastik', 'plastik'], ['Paslanmayan polad', 'paslanmayan-polad']]), $other()]]),
                    $size(),
                    $f('S10_1', 'hundurluk', $n('Minimum montaj hündürlüyü', 'Минимальная монтажная высота', 'Minimum installation height'), 'decimal', ['required' => true, 'unit' => 'mm',
                        'hint' => $n('İstehsalçı sxeminə görə.', 'По схеме производителя.', 'From the maker’s drawing.')]),
                    $f('S10_1', 'cixis', $n('Çıxış diametri', 'Диаметр выпуска', 'Outlet diameter'), 'decimal', ['required' => true, 'unit' => 'mm']),
                    $f('S10_1', 'istiqamet', $n('Çıxış istiqaməti', 'Направление выпуска', 'Outlet direction'), 'dropdown', ['options' => $opts([['Üfüqi', 'ufuqi'], ['Şaquli', 'saquli'], ['Tənzimlənən', 'tenzimlenen']])]),
                    $f('S10', 'qoxu', $n('Qoxu bağlayıcısı', 'Запах-затвор', 'Odour trap'), 'dropdown', ['options' => $opts([
                        ['Su kilidi', 'su-kilidi'], ['Quru', 'quru'], ['Kombinə edilmiş', 'kombine'], ['Daxil deyil', 'daxil-deyil'],
                    ])]),
                    $f('S10', 'suoturme', $n('Suötürmə qabiliyyəti', 'Пропускная способность', 'Flow capacity'), 'decimal', ['unit' => 'l/dəq',
                        'hint' => $n('İstehsalçının göstərdiyi sınaq şəraitində.', 'В условиях испытаний производителя.', 'Under the maker’s test conditions.')]),
                    ...$look,
                    $kit('S10_1', [['Barmaqlıq', 'barmaqliq'], ['Sifon', 'sifon'], ['Saç tutucu', 'sac-tutucu'], ['Hidroizolyasiya manjeti', 'manjet']]),
                    ...$guarantee,
                ]),
                'xetti-dus-kanallari' => $card('S10_2', $n('Xətti duş kanalları', 'Линейные душевые лотки', 'Linear shower channels'), $pieceOrSet, [
                    $f('S10_2', 'techizat', $n('Təchizat', 'Комплектация', 'Supply'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Tam dəst', 'tam'], ['Montaj gövdəsi', 'govde'], ['Finiş dəsti', 'finis'],
                    ])]),
                    $f('S10_2', 'yer', $n('Montaj yeri', 'Место установки', 'Position'), 'dropdown', ['options' => $opts([
                        ['Divar dibi', 'divar-dibi'], ['Döşəmənin içi', 'doseme'], ['Divara inteqrasiya', 'divara'],
                    ])]),
                    $f('S10_2', 'uzunluq', $n('Kanalın uzunluğu', 'Длина лотка', 'Channel length'), 'decimal', ['required' => true, 'unit' => 'mm']),
                    $f('S10_2', 'en', $n('Kanalın eni', 'Ширина лотка', 'Channel width'), 'decimal', ['unit' => 'mm']),
                    $f('S10_2', 'hundurluk', $n('Minimum montaj hündürlüyü', 'Минимальная монтажная высота', 'Minimum installation height'), 'decimal', ['unit' => 'mm',
                        'hint' => $n('Tam dəstdə məcburidir.', 'Обязательно для полного комплекта.', 'Required for a complete set.')]),
                    $f('S10_2', 'cixis', $n('Çıxış diametri', 'Диаметр выпуска', 'Outlet diameter'), 'decimal', ['unit' => 'mm']),
                    $f('S10_2', 'istiqamet', $n('Çıxış istiqaməti', 'Направление выпуска', 'Outlet direction'), 'dropdown', ['options' => $opts([['Üfüqi', 'ufuqi'], ['Şaquli', 'saquli']])]),
                    $f('S10', 'qoxu', $n('Qoxu bağlayıcısı', 'Запах-затвор', 'Odour trap'), 'dropdown', ['options' => $opts([
                        ['Su kilidi', 'su-kilidi'], ['Quru', 'quru'], ['Kombinə edilmiş', 'kombine'], ['Daxil deyil', 'daxil-deyil'],
                    ])]),
                    $f('S10', 'suoturme', $n('Suötürmə qabiliyyəti', 'Пропускная способность', 'Flow capacity'), 'decimal', ['unit' => 'l/dəq',
                        'hint' => $n('Sınaq şəraiti istehsalçıya görə.', 'Условия испытаний — по данным производителя.', 'Test conditions per the maker.')]),
                    ...$look,
                    $kit('S10_2', [['Gövdə', 'govde'], ['Örtük', 'ortuk'], ['Sifon', 'sifon'], ['Manjet', 'manjet'], ['Ayaqlar', 'ayaqlar']]),
                    ['required' => true, 'when' => $when('kart-s10-2-techizat', 'govde', 'finis')] + $compat(true, $n('Uyğun sistem (brend, model / kod)', 'Совместимая система (бренд, модель / код)', 'Compatible system (brand, model / code)'),
                        $n('Ayrı satılan gövdə və finiş dəstində model/kod məcburidir.', 'Для отдельно продаваемых корпуса и финишного комплекта код обязателен.', 'Required when the body and the finish set are sold separately.'), 'uygun-sistem'),
                ]),
                'trap-ve-kanal-ortukleri' => $card('S10_3', $n('Trap və kanal örtükləri', 'Решётки для трапов и лотков', 'Drain and channel covers'), $pieceOnly, [
                    $compat(),
                    $f('S10_3', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Nöqtəvi barmaqlıq', 'noqtevi'], ['Xətti barmaqlıq', 'xetti'], ['Plitə yerləşdirilən örtük', 'plite'],
                    ])]),
                    $f('S10_3', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['options' => [...$opts([
                        ['Paslanmayan polad', 'paslanmayan-polad'], ['Şüşə', 'suse'], ['Plastik', 'plastik'],
                    ]), $other()]]),
                    $size(),
                    ...$look,
                    $f('S10_3', 'plite', $n('Uyğun plitə qalınlığı', 'Толщина плитки', 'Tile thickness'), 'decimal', ['unit' => 'mm', 'when' => $when('kart-s10-3-tip', 'plite')]),
                    $kit('S10_3', [['Çərçivə', 'cercive'], ['Bərkitmə', 'berkitme']]),
                ]),
            ],
        ],

        // ════ 11. Hamam və tualet aksesuarları ═════════════════════════════
        [
            'slug' => 'hamam-ve-tualet-aksesuarlari',
            'name' => $n('Hamam və tualet aksesuarları', 'Аксессуары для ванной и туалета', 'Bathroom and toilet accessories'),
            'classes' => [
                'desmal-tutacaqlari' => $card('S11_1', $n('Dəsmal tutacaqları', 'Держатели для полотенец', 'Towel holders'), $pieceOrSet, [
                    $f('S11_1', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['required' => true, 'options' => $opts([['Çubuq', 'cubuq'], ['Halqa', 'halqa'], ['Çoxqollu', 'coxqollu'], ['Rəfli', 'refli']])]),
                    ['required' => true] + $metalMaterial,
                    ...$look,
                    ...$dims(),
                    $accMount,
                    $fixingKit,
                ]),
                'tualet-kagizi-tutacaqlari' => $card('S11_2', $n('Tualet kağızı tutacaqları', 'Держатели туалетной бумаги', 'Toilet roll holders'), $pieceOrSet, [
                    $f('S11_2', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Qapaqlı', 'qapaqli'], ['Qapaqsız', 'qapaqsiz'], ['Ehtiyat rulon üçün', 'ehtiyat'], ['Kombinə edilmiş', 'kombine'],
                    ])]),
                    ['required' => true] + $metalMaterial,
                    ...$look,
                    ...$dims(),
                    $accMount,
                    $fixingKit,
                ]),
                'hamam-qarmaqlari-ve-refleri' => $card('S11_3', $n('Hamam qarmaqları və rəfləri', 'Крючки и полки для ванной', 'Hooks and shelves'), $pieceOrSet, [
                    $f('S11_3', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Tək qarmaq', 'tek-qarmaq'], ['Çoxlu qarmaq', 'coxlu-qarmaq'], ['Düz rəf', 'duz-ref'], ['Künc rəfi', 'kunc-ref'], ['Duş səbəti', 'dus-sebeti'],
                    ])]),
                    $mixMaterial,
                    ...$look,
                    ...$dims(),
                    $accMount,
                    $packCount,
                    $fixingKit,
                ]),
                'sabunqabi-ve-dozatorlar' => $card('S11_4', $n('Sabunqabı və dozatorlar', 'Мыльницы и дозаторы', 'Soap dishes and dispensers'), $pieceOrSet, [
                    $f('S11_4', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['required' => true, 'options' => $opts([['Sabunqabı', 'sabunqabi'], ['Maye sabun dozatoru', 'dozator']])]),
                    $mixMaterial,
                    ...$look,
                    ...$dims(),
                    $accMount,
                    $f('S11_4', 'tutum', $n('Tutum', 'Объём', 'Capacity'), 'decimal', ['unit' => 'ml', 'when' => $when('kart-s11-4-nov', 'dozator')]),
                    $f('S11_4', 'idareetme', $n('İdarəetmə', 'Управление', 'Operation'), 'dropdown', ['options' => [...$opts([['Mexaniki', 'mexaniki'], ['Sensorlu', 'sensorlu']]), $na]]),
                    $kit('S11_4', [['Divar tutacağı', 'divar-tutacagi'], ['Bərkitmə', 'berkitme'], ['Batareya', 'batareya']]),
                ]),
                'tualet-fircasi-destleri' => $card('S11_5', $n('Tualet fırçası dəstləri', 'Ёршики для туалета', 'Toilet brush sets'), $pieceOrSet, [
                    $f('S11_5', 'montaj', $n('Montaj', 'Монтаж', 'Mounting'), 'dropdown', ['required' => true, 'options' => $opts([['Divardan asılan', 'divar'], ['Döşəməüstü', 'dosemeustu']])]),
                    $mixMaterial,
                    ...$look,
                    ...$dims(),
                    $f('S11_5', 'basliq', $n('Dəyişdirilən fırça başlığı', 'Сменная щётка', 'Replaceable brush head'), 'dropdown', ['options' => $ynn]),
                    $kit('S11_5', [['Fırça', 'firca'], ['Qab', 'qab'], ['Divar bərkitməsi', 'berkitme']]),
                ]),
                'hamam-aksesuar-destleri' => $card('S11_6', $n('Hamam aksesuar dəstləri', 'Наборы аксессуаров для ванной', 'Bathroom accessory sets'), $setOnly, [
                    $f('S11_6', 'say', $n('Dəstdə məhsul sayı', 'Предметов в наборе', 'Items in the set'), 'decimal', ['required' => true, 'unit' => 'ədəd', 'rules' => ['integer' => true],
                        'hint' => $n('Bərkitmə vintləri sayılmır.', 'Крепёжные винты не считаются.', 'Fixing screws do not count.')]),
                    $f('S11_6', 'terkib', $n('Dəstin tərkibi', 'Состав набора', 'Set contents'), 'multiselect', ['required' => true, 'options' => [...$opts([
                        ['Dəsmal tutacağı', 'desmal'], ['Kağız tutacağı', 'kagiz'], ['Qarmaq', 'qarmaq'], ['Rəf', 'ref'], ['Sabunqabı', 'sabunqabi'], ['Dozator', 'dozator'], ['Fırça dəsti', 'firca'],
                    ]), $other()]]),
                    $mixMaterial,
                    ...$look,
                    $accMount,
                    $f('S11_6', 'olculer', $n('Elementlərin ölçüləri', 'Размеры предметов', 'Item sizes'), 'text', [
                        'hint' => $n('Hər əsas element üçün mm.', 'Для каждого основного предмета, мм.', 'For each main item, mm.')]),
                    $fixingKit,
                ]),
            ],
        ],

        // ════ 12. Santexnika komplektləşdiriciləri ═════════════════════════
        [
            'slug' => 'santexnika-komplektlesdiricileri',
            'name' => $n('Santexnika komplektləşdiriciləri', 'Комплектующие для сантехники', 'Plumbing components'),
            'classes' => [
                // Sifonlar və boşaltma hissələri
                'sifonlar' => $card('S12_1', $n('Sifonlar', 'Сифоны', 'Traps'), $pieceOrSet, [
                    $f('S12_1', 'teyinat', $n('Təyinat', 'Назначение', 'For'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Əlüzyuyan', 'eluzyuyan'], ['Mətbəx çanağı', 'metbex'], ['Duş altlığı', 'dus-altligi'], ['Bide', 'bide'],
                    ])]),
                    $f('S12_1', 'konstruksiya', $n('Konstruksiya', 'Конструкция', 'Design'), 'dropdown', ['options' => [...$opts([
                        ['Şüşəvari', 'suseveri'], ['Boru tipli', 'boru'], ['Yastı', 'yasti'], ['Çevik', 'cevik'],
                    ]), $other()]]),
                    $f('S12_1', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['options' => [...$opts([
                        ['Plastik', 'plastik'], ['Pirinç', 'pirinc'], ['Paslanmayan polad', 'paslanmayan-polad'],
                    ]), $other()]]),
                    ...$look,
                    $f('S12_1', 'giris', $n('Giriş ölçüsü', 'Входной размер', 'Inlet size'), 'text', ['required' => true,
                        'hint' => $n('Yiv ölçüsü, düym və ya dəlik diametri, mm.', 'Резьба в дюймах или диаметр отверстия, мм.', 'Thread in inches or hole diameter, mm.')]),
                    $f('S12_1', 'cixis', $n('Kanalizasiya çıxış diametri', 'Диаметр выпуска в канализацию', 'Waste outlet diameter'), 'decimal', ['required' => true, 'unit' => 'mm']),
                    $f('S12_1', 'hundurluk', $n('Montaj hündürlüyü', 'Монтажная высота', 'Installation height'), 'decimal', ['unit' => 'mm',
                        'hint' => $n('Minimum tələb olunan yer.', 'Минимально необходимое место.', 'The minimum space needed.')]),
                    $f('S12_1', 'cihaz', $n('Əlavə cihaz bağlantısı', 'Отвод для техники', 'Appliance connections'), 'decimal', ['unit' => 'ədəd', 'rules' => ['integer' => true, 'min' => 0],
                        'hint' => $n('Paltaryuyan / qabyuyan üçün; yoxdursa 0.', 'Для стиральной / посудомоечной машины; если нет — 0.', 'For a washing machine or dishwasher; 0 if none.')]),
                    $kit('S12_1', [['Çıxış borusu', 'boru'], ['Daşma bağlantısı', 'dasma'], ['Klapan', 'klapan'], ['Contalar', 'contalar']]),
                ]),
                'dib-klapanlari' => $card('S12_2', $n('Dib klapanları', 'Донные клапаны', 'Basin wastes'), $pieceOrSet, [
                    $f('S12_2', 'teyinat', $n('Təyinat', 'Назначение', 'For'), 'dropdown', ['required' => true, 'options' => $opts([['Əlüzyuyan', 'eluzyuyan'], ['Bide', 'bide'], ['Mətbəx çanağı', 'metbex']])]),
                    $f('S12_2', 'mexanizm', $n('Mexanizm', 'Механизм', 'Mechanism'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Klik-klak', 'klik-klak'], ['Qollu', 'qollu'], ['Açıq', 'aciq'], ['Səbətli', 'sebetli'],
                    ])]),
                    $f('S12_2', 'dasma', $n('Daşma dəliyinə uyğunluq', 'Совместимость с переливом', 'Overflow compatibility'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Daşmalı çanaq üçün', 'dasmali'], ['Daşmasız üçün', 'dasmasiz'], ['Universal', 'universal'],
                    ])]),
                    ['required' => true] + $metalMaterial,
                    ...$look,
                    $f('S12_2', 'montaj-olcusu', $n('Montaj ölçüsü', 'Монтажный размер', 'Mounting size'), 'text', ['required' => true,
                        'hint' => $n('Yiv ölçüsü və uyğun çanaq dəliyi; istehsalçıya görə.', 'Резьба и подходящее отверстие чаши; по данным производителя.', 'Thread and the matching bowl hole; per the maker.')]),
                    $f('S12_2', 'qapaq', $n('Qapaq diametri', 'Диаметр крышки', 'Cap diameter'), 'decimal', ['unit' => 'mm']),
                    $kit('S12_2', [['Contalar', 'contalar'], ['İdarəetmə qolu', 'qol']]),
                ]),
                'vanna-sifonlari-ve-dasma-destleri' => $card('S12_3', $n('Vanna sifonları və daşma dəstləri', 'Сифоны для ванны с переливом', 'Bath wastes and overflows'), $pieceOrSet, [
                    $f('S12_3', 'idareetme', $n('İdarəetmə', 'Управление', 'Operation'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Tıxaclı', 'tixacli'], ['Kabel ilə', 'kabel'], ['Klik-klak', 'klik-klak'],
                    ]), $other()]]),
                    $shMaterial,
                    ...$look,
                    $f('S12_3', 'olculer', $n('Uyğun vanna ölçüləri', 'Подходящие размеры ванны', 'Matching bath dimensions'), 'text', [
                        'hint' => $n('Dib dəliyi, daşma dəliyi və aralarındakı məsafə, mm.', 'Донное и переливное отверстия и расстояние между ними, мм.', 'Waste hole, overflow hole and the distance between them, mm.')]),
                    $f('S12_3', 'cixis', $n('Çıxış diametri', 'Диаметр выпуска', 'Outlet diameter'), 'decimal', ['required' => true, 'unit' => 'mm']),
                    $f('S12_3', 'hundurluk', $n('Minimum montaj hündürlüyü', 'Минимальная монтажная высота', 'Minimum installation height'), 'decimal', ['unit' => 'mm']),
                    $f('S12_3', 'doldurma', $n('Daşma yerindən su doldurma', 'Наполнение через перелив', 'Filling through the overflow'), 'dropdown', ['options' => $ynn]),
                    $kit('S12_3', [['Sifon', 'sifon'], ['Daşma borusu', 'dasma'], ['Örtüklər', 'ortukler'], ['Contalar', 'contalar']]),
                ]),
                // Cihaz bağlantıları
                'elastik-slanqlar-qosulma-slanqlari' => $card('S12_4', $n('Elastik su bağlantıları', 'Гибкие подводки', 'Flexible connectors'), $pieceOrSet, [
                    $f('S12_4', 'teyinat', $n('Təyinat', 'Назначение', 'For'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Qarışdırıcı', 'qarisdirici'], ['Unitaz çəni', 'cen'], ['Digər sanitar cihaz', 'diger-cihaz'],
                    ])]),
                    $f('S12_4', 'uzunluq', $n('Uzunluq', 'Длина', 'Length'), 'decimal', ['required' => true, 'unit' => 'mm']),
                    $f('S12_4', 'uc1', $n('Birinci uc', 'Первый конец', 'First end'), 'text', ['required' => true,
                        'hint' => $n('Yiv ölçüsü və daxili/xarici yiv, məs. 1/2″ daxili.', 'Резьба и внутр./наружн., напр. 1/2″ внутр.', 'Thread size and female/male, e.g. 1/2″ female.')]),
                    $f('S12_4', 'uc2', $n('İkinci uc', 'Второй конец', 'Second end'), 'text', ['required' => true,
                        'hint' => $n('Yiv ölçüsü və daxili/xarici yiv, məs. M10 xarici.', 'Резьба и внутр./наружн., напр. M10 наружн.', 'Thread size and female/male, e.g. M10 male.')]),
                    $f('S12_4', 'konstruksiya', $n('Konstruksiya', 'Конструкция', 'Design'), 'dropdown', ['options' => [...$opts([['Örgülü', 'orgulu'], ['Körüklü', 'koruklu']]), $other()]]),
                    $f('S12_4', 'su', $n('Uyğun su', 'Вода', 'Water'), 'dropdown', ['options' => $opts([['Soyuq', 'soyuq'], ['İsti və soyuq', 'isti-soyuq']])]),
                    $f('S12_4', 'tezyiq', $n('Maksimum iş təzyiqi', 'Максимальное рабочее давление', 'Maximum working pressure'), 'decimal', ['unit' => 'bar']),
                    $packCount,
                ]),
                'bucaq-kranlari' => $card('S12_5', $n('Bucaq kranları', 'Угловые краны', 'Angle valves'), $pieceOrSet, [
                    $f('S12_5', 'cixis-sayi', $n('Çıxış sayı', 'Число выходов', 'Outlets'), 'dropdown', ['options' => [$a('1', ['slug' => '1']), $a('2', ['slug' => '2']), $other()]]),
                    $f('S12_5', 'giris', $n('Giriş yivi', 'Входная резьба', 'Inlet thread'), 'text', ['required' => true,
                        'hint' => $n('Ölçü və daxili/xarici yiv.', 'Размер и внутр./наружн.', 'Size and female/male.')]),
                    $f('S12_5', 'cixislar', $n('Çıxış yivləri', 'Выходная резьба', 'Outlet threads'), 'text', ['required' => true,
                        'hint' => $n('Hər çıxışın ölçüsü və daxili/xarici yivi.', 'Размер и тип резьбы каждого выхода.', 'Size and female/male for each outlet.')]),
                    ['required' => true] + $metalMaterial,
                    ...$look,
                    $f('S12_5', 'filtr', $n('İnteqrasiya edilmiş filtr', 'Встроенный фильтр', 'Built-in filter'), 'dropdown', ['options' => $ynn]),
                    $f('S12_5', 'idareetme', $n('İdarəetmə', 'Управление', 'Operation'), 'dropdown', ['options' => [...$opts([['Döndərilən', 'donderilen'], ['Dörddəbir dövrə', 'dorddebir']]), $other()]]),
                    $kit('S12_5', [['Dekorativ rozet', 'rozet'], ['Birləşmə hissələri', 'birlesme']]),
                ]),
                'unitaz-qosulma-hisseleri' => $card('S12_6', $n('Unitaz qoşulma hissələri', 'Подключение унитаза', 'Toilet connectors'), $pieceOrSet, [
                    $f('S12_6', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Düz çıxış', 'duz'], ['Dirsək', 'dirsek'], ['Eksantrik', 'eksantrik'], ['Çevik bağlantı', 'cevik'], ['İnstallyasiya qoşulma dəsti', 'installyasiya'],
                    ])]),
                    $f('S12_6', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['options' => $opts([['Plastik', 'plastik'], ['Elastomer', 'elastomer'], ['Qarışıq', 'qarisiq']])]),
                    $f('S12_6', 'giris', $n('Giriş diametri', 'Диаметр входа', 'Inlet diameter'), 'decimal', ['required' => true, 'unit' => 'mm']),
                    $f('S12_6', 'cixis', $n('Çıxış diametri', 'Диаметр выхода', 'Outlet diameter'), 'decimal', ['required' => true, 'unit' => 'mm']),
                    $f('S12_6', 'uzunluq-min', $n('Uzunluq, min', 'Длина, мин.', 'Length, min'), 'decimal', ['unit' => 'mm', 'rules' => ['lte' => 'kart-s12-6-uzunluq-maks']]),
                    $f('S12_6', 'uzunluq-maks', $n('Uzunluq, maks', 'Длина, макс.', 'Length, max'), 'decimal', ['unit' => 'mm']),
                    $f('S12_6', 'bucaq', $n('Bucaq', 'Угол', 'Angle'), 'decimal', ['unit' => '°', 'when' => $when('kart-s12-6-nov', 'dirsek')]),
                    $f('S12_6', 'surusme', $n('Sürüşmə', 'Смещение', 'Offset'), 'decimal', ['unit' => 'mm', 'when' => $when('kart-s12-6-nov', 'eksantrik')]),
                    ['when' => $when('kart-s12-6-nov', 'installyasiya')] + $compat(false, $n('Uyğun sistem (brend, model / kod)', 'Совместимая система (бренд, модель / код)', 'Compatible system (brand, model / code)'), null, 'uygun-sistem'),
                    $kit('S12_6', [['Contalar', 'contalar'], ['Dekorativ örtüklər', 'ortukler']]),
                ]),
                // Oturacaqlar və bərkitmələr
                'unitaz-oturacaqlari-ve-qapaqlari' => $card('S12_7', $n('Unitaz oturacaqları və qapaqları', 'Сиденья и крышки для унитаза', 'Toilet seats and lids'), $pieceOrSet, [
                    $compat(),
                    $f('S12_7', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['options' => [...$opts([['Duroplast', 'duroplast'], ['Termoplast', 'termoplast'], ['MDF', 'mdf']]), $other()]]),
                    $f('S12_7', 'forma', $n('Forma', 'Форма', 'Shape'), 'dropdown', ['options' => [...$opts([['Oval', 'oval'], ['D-formalı', 'd-formali'], ['Düzbucaqlı', 'duzbucaqli']]), $other()]]),
                    $size(),
                    $f('S12_7', 'berkitme-min', $n('Bərkitmə məsafəsi, min', 'Расстояние крепления, мин.', 'Fixing distance, min'), 'decimal', ['required' => true, 'unit' => 'mm',
                        'rules' => ['lte' => 'kart-s12-7-berkitme-maks']]),
                    $f('S12_7', 'berkitme-maks', $n('Bərkitmə məsafəsi, maks', 'Расстояние крепления, макс.', 'Fixing distance, max'), 'decimal', ['required' => true, 'unit' => 'mm']),
                    ...$look,
                    $f('S12_7', 'yumsaq', $n('Yumşaq bağlanma', 'Микролифт', 'Soft close'), 'dropdown', ['options' => $ynn]),
                    $f('S12_7', 'cixarilma', $n('Tez çıxarılma', 'Быстросъёмное', 'Quick release'), 'dropdown', ['options' => $ynn]),
                    $kit('S12_7', [['Menteşə', 'mentese'], ['Bərkitmə dəsti', 'berkitme']]),
                ]),
                'santexnika-berkitme-destleri' => $card('S12_8', $n('Santexnika bərkitmə dəstləri', 'Крепёж для сантехники', 'Sanitary fixing kits'), $setOnly, [
                    $f('S12_8', 'teyinat', $n('Təyinat', 'Назначение', 'For'), 'dropdown', ['required' => true, 'options' => [...$opts([['Əlüzyuyan', 'eluzyuyan'], ['Unitaz', 'unitaz'], ['Bide', 'bide']]), $other()]]),
                    $f('S12_8', 'seth', $n('Montaj səthi', 'Основание', 'Base'), 'dropdown', ['options' => [...$opts([['Beton', 'beton'], ['Dolu kərpic', 'kerpic'], ['Uyğun karkas', 'karkas']]), $other()],
                        'hint' => $n('İstehsalçıya görə.', 'По данным производителя.', 'Per the maker.')]),
                    $f('S12_8', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['options' => $opts([
                        ['Sinklənmiş polad', 'sinkli'], ['Paslanmayan polad', 'paslanmayan'], ['Plastik', 'plastik'], ['Qarışıq', 'qarisiq'],
                    ])]),
                    $f('S12_8', 'diametr', $n('Vint / şpilka diametri', 'Диаметр винта / шпильки', 'Screw / stud diameter'), 'decimal', ['required' => true, 'unit' => 'mm']),
                    $f('S12_8', 'uzunluq', $n('Vint / şpilka uzunluğu', 'Длина винта / шпильки', 'Screw / stud length'), 'decimal', ['required' => true, 'unit' => 'mm']),
                    $f('S12_8', 'terkib', $n('Dəstin tərkibi', 'Состав комплекта', 'Kit contents'), 'text', ['required' => true,
                        'hint' => $n('Hissələrin adı və sayı.', 'Названия и количество деталей.', 'Part names and counts.')]),
                    $compat(false, $n('Uyğun model', 'Совместимая модель', 'Compatible model'), $n('Xüsusi bərkitmədə məhsul seriyası / kodu.', 'Для специального крепежа — серия / код изделия.', 'For a special fixing, the product series / code.'), 'xususi-model'),
                ]),
                // Qarışdırıcı hissələri
                'gizli-qarisdirici-govdeleri' => $card('S12_9', $n('Gizli qarışdırıcı gövdələri', 'Скрытые части смесителей', 'Concealed mixer bodies'), $pieceOrSet, [
                    $compat(),
                    $f('S12_9', 'teyinat', $n('Təyinat', 'Назначение', 'For'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Əlüzyuyan', 'eluzyuyan'], ['Duş', 'dus'], ['Vanna', 'vanna'], ['Bide', 'bide'], ['Gigiyenik duş', 'gigiyenik'],
                    ]), 'hint' => $n('Görünən idarəetmə və dekorativ hissə ayrıca satılırsa dəstə daxil göstərilmir.', 'Если видимая часть продаётся отдельно, в комплект её не включайте.', 'If the visible trim is sold separately, do not list it as included.')]),
                    $f('S12_9', 'sistem', $n('İdarəetmə sistemi', 'Система управления', 'Control system'), 'dropdown', ['options' => $opts([['Adi', 'adi'], ['Termostatik', 'termostatik'], ['Elektron', 'elektron']])]),
                    $f('S12_9', 'cixis', $n('Çıxış sayı', 'Число выходов', 'Outlets'), 'decimal', ['unit' => 'ədəd', 'rules' => ['integer' => true]]),
                    $f('S12_9', 'derinlik-min', $n('Montaj dərinliyi, min', 'Глубина монтажа, мин.', 'Installation depth, min'), 'decimal', ['required' => true, 'unit' => 'mm',
                        'rules' => ['lte' => 'kart-s12-9-derinlik-maks']]),
                    $f('S12_9', 'derinlik-maks', $n('Montaj dərinliyi, maks', 'Глубина монтажа, макс.', 'Installation depth, max'), 'decimal', ['required' => true, 'unit' => 'mm']),
                    $connection,
                    ...$pressure,
                    $kit('S12_9', [['Qoruyucu örtük', 'ortuk'], ['Sızdırmazlıq manjeti', 'manjet'], ['Montaj hissələri', 'montaj']]),
                ]),
                'qarisdirici-ehtiyat-hisseleri' => $card('S12_10', $n('Qarışdırıcı ehtiyat hissələri', 'Запчасти для смесителей', 'Mixer spare parts'), $pieceOrSet, [
                    $f('S12_10', 'nov', $n('Hissə növü', 'Тип детали', 'Part type'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Kartric', 'kartric'], ['Termostatik element', 'termostatik'], ['Aerator', 'aerator'], ['Yönləndirici', 'yonlendirici'],
                        ['Tutacaq', 'tutacaq'], ['Çıxış ucu', 'cixis-ucu'], ['Conta', 'conta'],
                    ])]),
                    $compat(),
                    $f('S12_10', 'olcu', $n('Ölçü və birləşmə', 'Размер и присоединение', 'Size and connection'), 'text', ['required' => true,
                        'hint' => $n('Hissəyə görə diametr, hündürlük, yiv və ya qoşulma ölçüsü.', 'Диаметр, высота, резьба или присоединительный размер.', 'Diameter, height, thread or connection size, as the part needs.')]),
                    $f('S12_10', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['options' => $opts([
                        ['Keramika', 'keramika'], ['Metal', 'metal'], ['Plastik', 'plastik'], ['Elastomer', 'elastomer'], ['Qarışıq', 'qarisiq'],
                    ])]),
                    ['required' => false] + $color,
                    $packCount,
                ]),
                // Çən hissələri
                'su-ceni-mexanizmleri' => $card('S12_11', $n('Su çəni mexanizmləri', 'Арматура для бачка', 'Cistern mechanisms'), $pieceOrSet, [
                    $f('S12_11', 'nov', $n('Hissə növü', 'Тип детали', 'Part type'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Doldurma klapanı', 'doldurma'], ['Su buraxma mexanizmi', 'buraxma'], ['Tam təmir dəsti', 'tam-dest'], ['Conta', 'conta'],
                    ])]),
                    $compat(),
                    $f('S12_11', 'giris', $n('Giriş yeri', 'Подвод', 'Inlet'), 'dropdown', ['options' => [...$opts([['Alt', 'alt'], ['Yan', 'yan']]), $na]]),
                    $connection,
                    $f('S12_11', 'hundurluk-min', $n('Hündürlük aralığı, min', 'Диапазон высоты, мин.', 'Height range, min'), 'decimal', ['unit' => 'mm',
                        'rules' => ['lte' => 'kart-s12-11-hundurluk-maks']]),
                    $f('S12_11', 'hundurluk-maks', $n('Hündürlük aralığı, maks', 'Диапазон высоты, макс.', 'Height range, max'), 'decimal', ['unit' => 'mm']),
                    $f('S12_11', 'rejim', $n('Su buraxma rejimi', 'Режим слива', 'Flush mode'), 'dropdown', ['options' => [...$opts([
                        ['Bir həcm', 'bir'], ['İki həcm', 'iki'], ['Start-stop', 'start-stop'],
                    ]), $na]]),
                    $kit('S12_11', [['Bərkitmə', 'berkitme'], ['Contalar', 'contalar'], ['Düymə', 'duyme']]),
                ]),
                // Duş hissələri
                'dus-baglantilari-ve-tutacaqlari' => $card('S12_12', $n('Duş bağlantıları və tutacaqları', 'Шланги и держатели для душа', 'Shower hoses and holders'), $pieceOrSet, [
                    $f('S12_12', 'nov', $n('Hissə növü', 'Тип детали', 'Part type'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Duş şlanqı', 'slanq'], ['Əl duşu tutacağı', 'tutacaq'], ['Duş qolu', 'dus-qolu'], ['Divar su çıxışı', 'divar-cixisi'], ['Sürüşən tutacaq', 'surusen'],
                    ])]),
                    ['required' => true] + $metalMaterial,
                    ...$look,
                    $f('S12_12', 'olcu', $n('Ölçü', 'Размер', 'Size'), 'decimal', ['required' => true, 'unit' => 'mm',
                        'hint' => $n('Şlanq / qol üçün uzunluq; tutacaq üçün uyğun dirək diametri.', 'Для шланга / кронштейна — длина; для держателя — диаметр штанги.', 'Length for a hose or arm; rail diameter for a holder.')]),
                    $connection,
                    $compat(false, $n('Uyğun model', 'Совместимая модель', 'Compatible model'), $n('Xüsusi hissədə brend və seriya / kod.', 'Для специальной детали — бренд и серия / код.', 'For a special part, the brand and series / code.'), 'xususi-model'),
                    $kit('S12_12', [['Bərkitmə', 'berkitme'], ['Contalar', 'contalar'], ['Dekorativ rozet', 'rozet']]),
                ]),
                'dus-kabinesi-ve-arakesme-hisseleri' => $card('S12_13', $n('Duş kabinəsi və arakəsmə hissələri', 'Комплектующие для душевых кабин и ограждений', 'Shower enclosure parts'), $pieceOrSet, [
                    $f('S12_13', 'nov', $n('Hissə növü', 'Тип детали', 'Part type'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Çarx', 'carx'], ['Menteşə', 'mentese'], ['Tutacaq', 'tutacaq'], ['Maqnitli lent', 'maqnit'], ['Sızdırmazlıq lenti', 'lent'], ['Profil', 'profil'],
                    ]), $other()]]),
                    $compat(),
                    ['hint' => $n('Şüşəyə bərkidilən hissədə.', 'Для детали, крепящейся к стеклу.', 'For a part fixed to the glass.')] + $glassThick(),
                    $f('S12_13', 'olcu', $n('Ölçü', 'Размер', 'Size'), 'text', ['required' => true,
                        'hint' => $n('Növə görə uzunluq, diametr və ya deşiklərarası məsafə, mm.', 'Длина, диаметр или межосевое расстояние, мм.', 'Length, diameter or hole spacing, mm.')]),
                    $f('S12_13', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['options' => $opts([['Metal', 'metal'], ['Plastik', 'plastik'], ['Elastomer', 'elastomer'], ['Qarışıq', 'qarisiq']])]),
                    ...$look,
                    $packCount,
                ]),
            ],
        ],

        // ════ Not in the workbook — the section's other groups, plain form ════
        [
            'slug' => 'borular-ve-fitinqler',
            'name' => $n('Borular və fitinqlər', 'Трубы и фитинги', 'Pipes and fittings'),
            'plain' => ['polipropilen-borular', 'metal-plastik-mis-polad-borular', 'fitinqler'],
        ],
        [
            'slug' => 'baglayici-ve-tenzimleyici-armatur',
            'name' => $n('Bağlayıcı və tənzimləyici armatur', 'Запорная и регулирующая арматура', 'Shut-off and control valves'),
            'plain' => ['kurevi-kranlar', 'manjetler-ve-sizdirmazlasdiricilar', 'kobud-temizleme-filtrleri', 'ince-temizleme-filtrleri', 'su-saygaci'],
        ],
        [
            'slug' => 'nasos-avadanligi',
            'name' => $n('Nasos avadanlığı', 'Насосное оборудование', 'Pumps'),
            'plain' => ['nasoslar-sirkulyasiya-drenaj-fekal'],
        ],
        [
            'slug' => 'su-qizdiricilari',
            'name' => $n('Su qızdırıcıları', 'Водонагреватели', 'Water heaters'),
            // «Dəsmal quruducu» moved to the İqlim cards (H03, iqlim.php merges it).
            'plain' => ['su-qizdiricisi-boyler'],
        ],
        [
            'slug' => 'kanalizasiya-borulari-ve-avadanligi',
            'name' => $n('Kanalizasiya boruları və avadanlığı', 'Канализационные трубы и оборудование', 'Sewer pipes and equipment'),
            'plain' => ['kanalizasiya-borulari-ve-fitinqleri-pvx', 'septik-lokal-temizleme-stansiyasi'],
        ],
    ],

    // The old catch-all groups are emptied by the cards above.
    'retire_groups' => ['qarisdiricilar-ve-dus-sistemleri', 'sanitar-keramika', 'santexnika-aksesuarlari'],
];
