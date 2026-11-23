<?php

/*
|--------------------------------------------------------------------------
| Tekstil, pərdələr, günəşdən mühafizə və dekor — 39 cards in the workbook's 3 blocks
|--------------------------------------------------------------------------
|
| Source: Archi_AZ_Tekstil_Dekor_Kataloq_AZ.xlsx (client). Format and decisions:
| docs/product-cards.md. Section «Tekstil, pərdələr, günəşdən qorunma və interyer
| dekoru»: the workbook's 3 «Bölmə» (Pərdələr və günəşdən mühafizə T01–T07, Ev tekstili
| T08–T12, İnteryer dekoru T13–T15) are the groups; their «Alt qrup» column stays inside
| the block (the Kateqoriyalar sheet: «3 əsas blok»).
|
| Card codes are the workbook's (T01_1 …), but the ceiling file (tavan.php) already owns
| `kart-t01-…` slugs, so per-card attributes here are `kart-tx01-1-<key>` and shared
| ones `kart-tx-<key>`.
|
| Repeated blocks («Hazırlanma variantı», «Parça tərkibi», «İşıq süzmə tipi», «Rəng və
| naxış» / «Rəng və səth», «Qulluq», «Sistem ölçüsü», «Montaj», «İdarəetmə», «Elektrik
| təchizatı», «İdarəetmə uyğunluğu», «Dəstin tərkibi», «Əlavə xidmətlər» …) are one shared
| definition each. Option values are Azerbaijani only; attribute / class names az/ru/en.
|
*/

$n = fn (string $az, string $ru, string $en): array => compact('az', 'ru', 'en');
$o = fn (string $az, string $ru, string $en, array $extra = []): array => ['value' => compact('az', 'ru', 'en')] + $extra;
$a = fn (string $az, array $extra = []): array => ['value' => ['az' => $az]] + $extra;

$P_NAME = $n('Dəqiq adını yazın', 'Укажите точное название', 'Enter the exact name');
$P_CODE = $n('Rəngi və ya kodunu yazın', 'Укажите цвет или код', 'Enter the colour or its code');
$P_SMART = $n('Protokolu və sistemi yazın', 'Укажите протокол и систему', 'Enter the protocol and system');

$yes = $o('Bəli', 'Да', 'Yes', ['slug' => 'beli']);
$no = $o('Xeyr', 'Нет', 'No', ['slug' => 'xeyr']);
$ns = $o('Göstərilməyib', 'Не указано', 'Not specified', ['slug' => 'gosterilmeyib']);
$other = fn (?array $prompt = null, string $az = 'Digər', string $slug = 'diger'): array => $a($az, ['slug' => $slug, 'prompt' => $prompt ?? $P_NAME]);
$none = fn (string $az = 'Daxil deyil'): array => $a($az, ['slug' => 'daxil-deyil', 'exclusive' => true]);
$noneX = fn (string $az = 'Yoxdur', string $slug = 'yoxdur'): array => $a($az, ['slug' => $slug, 'exclusive' => true]);
$ynn = [$yes, $no, $ns];
$when = fn (string $attr, string ...$in): array => ['attr' => $attr, 'in' => $in];
$opts = fn (array $list): array => array_map(fn ($pair) => $a($pair[0], ['slug' => $pair[1]]), $list);
$hint = fn (string $az, string $ru, string $en): array => ['hint' => compact('az', 'ru', 'en')];

// Per-card slugs: T01_1 → kart-tx01-1-<key> (tavan.php owns kart-t01-…).
$pre = fn (string $code): string => 'kart-tx'.substr(strtolower(str_replace('_', '-', $code)), 1);
$s = fn (string $code, string $key): string => $pre($code).'-'.$key;
$f = fn (string $code, string $key, array $name, string $type, array $extra = []): array => ['slug' => $s($code, $key), 'name' => $name, 'type' => $type] + $extra;
$kit = fn (string $code, array $items): array => [
    'slug' => $s($code, 'komplekt'), 'name' => $n('Komplektə daxildir', 'В комплекте', 'Included'), 'type' => 'multiselect',
    'hint' => $n('Yalnız qiymətə daxil olanlar.', 'Только то, что входит в цену.', 'Only what the price includes.'),
    'options' => [...$opts($items), $none()],
];

// ── Shared ──────────────────────────────────────────────────────────────────

// «Əlavə xidmətlər — ölçmə, tikiş, çatdırılma və montaj, hər biri ayrıca» (every card).
$serviceOptions = $opts([['Qiymətə daxildir', 'daxildir'], ['Ayrıca ödəniş', 'ayrica'], ['Təklif edilmir', 'teklif-edilmir'], ['Tətbiq edilmir', 'tetbiq-edilmir']]);
$services = [
    ['slug' => 'kart-tx-xidmet-olcme', 'name' => $n('Ölçmə xidməti', 'Услуга замера', 'Measuring service'), 'type' => 'dropdown', 'options' => $serviceOptions]
        + $hint('Əlavə xidmətlər: qiymətə daxil olub-olmaması.', 'Дополнительные услуги: входят ли в цену.', 'Extra services: whether the price includes them.'),
    ['slug' => 'kart-tx-xidmet-tikis', 'name' => $n('Tikiş xidməti', 'Услуга пошива', 'Sewing service'), 'type' => 'dropdown', 'options' => $serviceOptions],
    ['slug' => 'kart-tx-xidmet-catdirilma', 'name' => $n('Çatdırılma', 'Доставка', 'Delivery'), 'type' => 'dropdown', 'options' => $serviceOptions],
    ['slug' => 'kart-tx-xidmet-montaj', 'name' => $n('Montaj xidməti', 'Услуга монтажа', 'Installation service'), 'type' => 'dropdown', 'options' => $serviceOptions],
];
$card = fn (string $code, array $name, array $units, array $fields, array $extra = []): array => ['code' => $code, 'name' => $name, 'sale' => 'unit', 'units' => $units, 'fields' => [...$fields, ...$services]] + $extra;
$ps = ['piece', 'set'];

// «Hazırlanma variantı* — Standart ölçü / fərdi ölçü ilə sifariş»: custom → «Sifarişlə».
$made = ['slug' => 'kart-tx-hazirlanma', 'name' => $n('Hazırlanma variantı', 'Вариант изготовления', 'Make'), 'type' => 'dropdown', 'required' => true, 'filter' => true,
    'options' => $opts([['Standart ölçü', 'standart'], ['Fərdi ölçü ilə sifariş', 'ferdi']])]
    + $hint('Fərdi sifarişdə mövcudluq «Sifarişlə»dir; qiymət nümunə ölçüsü və komplekt üçün başlanğıc qiymətdir.',
        'При индивидуальном заказе — «Под заказ»; цена — стартовая для образца размера и комплекта.',
        'Made to size is «On order»; the price is a starting price for the sample size and kit.');
$madeToOrder = ['on_order_when' => ['attr' => 'kart-tx-hazirlanma', 'in' => ['ferdi']]];

// «Parça tərkibi — liflər və hər lifin payı %, cəmi 100%»: fibres + the text with percentages.
$fibres = [...$opts([
    ['Pambıq', 'pambiq'], ['Kətan', 'ketan'], ['Polyester', 'polyester'], ['Viskoza', 'viskoza'], ['Yun', 'yun'], ['İpək', 'ipek'], ['Poliamid', 'poliamid'],
]), $other()];
$pctHint = $hint('Hər lifin payı, cəmi 100%: məs. 60% pambıq, 40% polyester. Qarışıq adı faizləri əvəz etmir.',
    'Доля каждого волокна, всего 100%: напр. 60% хлопок, 40% полиэстер.', 'Each fibre’s share, 100% in total: e.g. 60% cotton, 40% polyester.');
$composition = fn (bool $req = true): array => [
    ['slug' => 'kart-tx-parca-terkibi', 'name' => $n('Parça tərkibi', 'Состав ткани', 'Fabric composition'), 'type' => 'multiselect', 'required' => $req, 'filter' => true, 'options' => $fibres],
    ['slug' => 'kart-tx-terkib-faiz', 'name' => $n('Tərkib, %', 'Состав, %', 'Composition, %'), 'type' => 'text', 'required' => $req] + $pctHint,
];
// «Üz parçasının / üzlüyün / üst parçanın tərkibi — seçim və faizlər» (yorğan, yastıq, döşək qoruyucu).
$coverComposition = fn (array $h): array => [
    ['slug' => 'kart-tx-uz-parca-terkibi', 'name' => $n('Üz parçasının tərkibi', 'Состав чехла', 'Cover fabric composition'), 'type' => 'multiselect', 'options' => $fibres, 'hint' => $h],
    ['slug' => 'kart-tx-uz-terkib-faiz', 'name' => $n('Üz parçasının tərkibi, %', 'Состав чехла, %', 'Cover composition, %'), 'type' => 'text'] + $pctHint,
];

$lightFilter = ['slug' => 'kart-tx-isiq-suzme', 'name' => $n('İşıq süzmə tipi', 'Светопропускание', 'Light filtering'), 'type' => 'dropdown', 'required' => true, 'filter' => true,
    'options' => $opts([['Şəffaf', 'seffaf'], ['Yarımşəffaf', 'yarimseffaf'], ['İşıq süzən', 'isiq-suzen'], ['Dimout', 'dimout'], ['Blackout', 'blackout']])]
    + $hint('İstehsalçı təsnifatı. Blackout parçanın xüsusiyyətidir; kənar işıq sızması montajdan asılıdır.',
        'Классификация производителя. Blackout — свойство ткани; засвет по краям зависит от монтажа.',
        'The maker’s class. Blackout is a fabric property; edge light leaks depend on fitting.');

// «Rəng və naxış* — iki seçim» / «Rəng və səth* — iki seçim».
$color = ['slug' => 'kart-tx-reng', 'name' => $n('Rəng', 'Цвет', 'Colour'), 'type' => 'dropdown', 'required' => true, 'filter' => true, 'options' => [...$opts([
    ['Ağ', 'ag'], ['Krem', 'krem'], ['Bej', 'bej'], ['Boz', 'boz'], ['Qara', 'qara'], ['Qəhvəyi', 'qehveyi'], ['Mavi', 'mavi'], ['Göy', 'goy'],
    ['Yaşıl', 'yasil'], ['Qırmızı', 'qirmizi'], ['Bordo', 'bordo'], ['Sarı', 'sari'], ['Narıncı', 'narinci'], ['Çəhrayı', 'cehrayi'],
    ['Bənövşəyi', 'benovseyi'], ['Qızılı', 'qizili'], ['Gümüşü', 'gumusu'], ['Təbii', 'tebii'], ['Çoxrəngli', 'coxrengli'],
]), $other($P_CODE)]];
$pattern = ['slug' => 'kart-tx-naxis', 'name' => $n('Naxış', 'Узор', 'Pattern'), 'type' => 'dropdown', 'required' => true, 'filter' => true, 'options' => [...$opts([
    ['Tək rəng', 'tek-reng'], ['Həndəsi', 'hendesi'], ['Bitki', 'bitki'], ['Klassik', 'klassik'], ['Uşaq', 'usaq'],
]), $other()]];
$finish = ['slug' => 'kart-tx-seth', 'name' => $n('Səth', 'Поверхность', 'Finish'), 'type' => 'dropdown', 'required' => true, 'options' => [...$opts([
    ['Mat', 'mat'], ['Parlaq', 'parlaq'], ['Teksturalı', 'teksturali'],
]), $other()]];
$colorPattern = [$color, $pattern];
$colorFinish = [$color, $finish];

// «Qulluq* — yuma üsulu, maksimum temperatur °C, qurutma və ütüləmə (etiketdən)».
$care = [
    ['slug' => 'kart-tx-yuma', 'name' => $n('Yuma üsulu', 'Способ стирки', 'Washing'), 'type' => 'dropdown', 'required' => true, 'options' => $opts([
        ['Maşında yuma', 'masin'], ['Zərif rejimdə maşında', 'zerif'], ['Əllə yuma', 'elle'], ['Yalnız quru təmizləmə', 'quru-temizleme'], ['Yalnız səthi təmizləmə', 'sethi-temizleme'],
    ])] + $hint('İstehsalçının qulluq etiketinə görə.', 'По ярлыку ухода производителя.', 'From the maker’s care label.'),
    ['slug' => 'kart-tx-yuma-temp', 'name' => $n('Maksimum yuma temperaturu', 'Макс. температура стирки', 'Maximum washing temperature'), 'type' => 'decimal', 'unit' => '°C',
        'rules' => ['max' => 95], 'when' => ['attr' => 'kart-tx-yuma', 'in' => ['masin', 'zerif', 'elle']]],
    ['slug' => 'kart-tx-qurutma', 'name' => $n('Qurutma', 'Сушка', 'Drying'), 'type' => 'dropdown', 'options' => [...$opts([
        ['Barabanda qurutmaq olar', 'baraban'], ['Barabanda qurutmaq olmaz', 'baraban-olmaz'], ['Düz səthdə qurutma', 'duz'], ['Asaraq qurutma', 'asaraq'],
    ]), $ns]],
    ['slug' => 'kart-tx-utuleme', 'name' => $n('Ütüləmə', 'Глажение', 'Ironing'), 'type' => 'dropdown', 'options' => [...$opts([
        ['Ütülənmir', 'utulenmir'], ['Aşağı temperatur, 110 °C-dək', 'asagi'], ['Orta temperatur, 150 °C-dək', 'orta'], ['Yüksək temperatur, 200 °C-dək', 'yuksek'],
    ]), $ns]],
];

// «Dəstin tərkibi* — hər elementin adı, sayı və ölçüsü; dəst satıldıqda məcburidir».
$setContents = ['slug' => 'kart-tx-dest-terkibi', 'name' => $n('Dəstin tərkibi', 'Состав набора', 'Set contents'), 'type' => 'textarea']
    + $hint('Dəst kimi satıldıqda doldurun: hər elementin adı, sayı və ölçüsü.', 'Для набора: название, количество и размер каждого элемента.', 'For a set: each item’s name, count and size.');
$setQty = ['slug' => 'kart-tx-destde-miqdar', 'name' => $n('Dəstdə miqdar', 'Количество в наборе', 'Pieces per set'), 'type' => 'decimal', 'unit' => 'ədəd', 'rules' => ['integer' => true]];

// Sizes. Textiles in cm, systems in mm («Tekstildə cm, sistem ölçülərində mm»).
// Flat textiles «en × uzunluq»: the size widget asks «Uzunluq» and «En». Width × height
// sizes are two decimals instead (the widget's labels would not fit them).
$sizeLW = fn (bool $req = true, ?array $h = null): array => ['slug' => 'kart-tx-olcu', 'name' => $n('Ölçü', 'Размер', 'Size'), 'type' => 'size', 'required' => $req, 'unit' => 'sm']
    + ['hint' => $h ?? $n('Uzunluq və en. Dairəvi məhsulda hər ikisi diametrdir.', 'Длина и ширина. Для круглого изделия оба — диаметр.', 'Length and width. For a round item both are the diameter.')];
$width = fn (bool $req = true, ?array $h = null): array => ['slug' => 'kart-tx-en', 'name' => $n('En', 'Ширина', 'Width'), 'type' => 'decimal', 'required' => $req, 'unit' => 'sm'] + ($h ? ['hint' => $h] : []);
$depth = fn (bool $req = false): array => ['slug' => 'kart-tx-derinlik', 'name' => $n('Dərinlik', 'Глубина', 'Depth'), 'type' => 'decimal', 'required' => $req, 'unit' => 'sm'];
$height = fn (bool $req = true, ?array $h = null): array => ['slug' => 'kart-tx-hundurluk', 'name' => $n('Hündürlük', 'Высота', 'Height'), 'type' => 'decimal', 'required' => $req, 'unit' => 'sm'] + ($h ? ['hint' => $h] : []);
$diam = $n('Dairəvi məhsulda diametr.', 'Для круглого изделия — диаметр.', 'The diameter for a round item.');

// «Sistem ölçüsü* — en × hündürlük, mm; hazır məhsula və ya pəncərə boşluğuna aid olduğu ayrıca».
$systemSize = [
    ['slug' => 'kart-tx-sistem-eni', 'name' => $n('Sistemin eni', 'Ширина системы', 'System width'), 'type' => 'decimal', 'required' => true, 'unit' => 'mm']
        + $hint('Ümumi en. Fərdi sifarişdə qiymətin aid olduğu nümunə ölçüsü.', 'Общая ширина. Для заказа — размер образца, к которому относится цена.', 'Overall width. Made to size: the sample size the price is for.'),
    ['slug' => 'kart-tx-sistem-hundurlugu', 'name' => $n('Sistemin hündürlüyü', 'Высота системы', 'System height'), 'type' => 'decimal', 'required' => true, 'unit' => 'mm'],
    ['slug' => 'kart-tx-olcu-aidiyyeti', 'name' => $n('Ölçü aiddir', 'Размер относится к', 'Size refers to'), 'type' => 'dropdown', 'required' => true,
        'options' => $opts([['Hazır məhsula', 'hazir'], ['Pəncərə boşluğuna', 'bosluq']])],
];
$lamelWidth = ['slug' => 'kart-tx-lamel-eni', 'name' => $n('Lamel eni', 'Ширина ламели', 'Slat width'), 'type' => 'decimal', 'required' => true, 'unit' => 'mm'];
$density = ['slug' => 'kart-tx-seth-sixligi', 'name' => $n('Səth sıxlığı', 'Поверхностная плотность', 'Weight per m²'), 'type' => 'decimal', 'unit' => 'g/m²']
    + $hint('İstehsalçı məlumatına görə.', 'По данным производителя.', 'Per the maker.');

// Window systems: mounting, control, side.
$mount = ['slug' => 'kart-tx-montaj', 'name' => $n('Montaj', 'Монтаж', 'Mounting'), 'type' => 'dropdown', 'required' => true, 'options' => [...$opts([
    ['Divara', 'divar'], ['Tavana', 'tavan'], ['Pəncərə çərçivəsinə', 'cercive'], ['Boşluq daxilinə', 'bosluq'],
]), $other()]];
$control = ['slug' => 'kart-tx-idareetme', 'name' => $n('İdarəetmə', 'Управление', 'Operation'), 'type' => 'dropdown', 'required' => true, 'filter' => true, 'options' => $opts([
    ['Əl ilə', 'el'], ['Zəncirli', 'zencirli'], ['Yaylı', 'yayli'], ['Çubuqlu', 'cubuqlu'], ['Elektrik', 'elektrik'],
])];
$controlSide = ['slug' => 'kart-tx-idareetme-terefi', 'name' => $n('İdarəetmə tərəfi', 'Сторона управления', 'Control side'), 'type' => 'dropdown',
    'options' => $opts([['Sol', 'sol'], ['Sağ', 'sag'], ['Tətbiq edilmir', 'tetbiq-edilmir']])]
    + $hint('Qarşıdan baxdıqda.', 'Если смотреть спереди.', 'Seen from the front.');
// «Elektrikli variantda motor modeli, təchizat və idarəetmə uyğunluğu qeyd edilir».
$motorNote = ['slug' => 'kart-tx-motor-uygunluq', 'name' => $n('Motor, təchizat və idarəetmə', 'Мотор, питание и управление', 'Motor, power and control'), 'type' => 'text',
    'when' => ['attr' => 'kart-tx-idareetme', 'in' => ['elektrik']]]
    + $hint('Motorun modeli, təchizat və idarəetmə uyğunluğu (pult, tətbiq, ağıllı ev protokolu).', 'Модель мотора, питание и совместимость управления.', 'Motor model, power and control compatibility.');

// «Elektrik təchizatı — seçim və rəqəm: şəbəkə / adapter / akkumulyator / günəş paneli; V və adapter».
$supply = fn (?array $w = null): array => [
    ['slug' => 'kart-tx-techizat', 'name' => $n('Elektrik təchizatı', 'Электропитание', 'Power supply'), 'type' => 'dropdown',
        'options' => $opts([['Şəbəkə', 'sebeke'], ['Adapter', 'adapter'], ['Akkumulyator', 'akkumulyator'], ['Günəş paneli', 'gunes-paneli']])] + ($w ? ['when' => $w] : []),
    ['slug' => 'kart-tx-gerginlik', 'name' => $n('Gərginlik', 'Напряжение', 'Voltage'), 'type' => 'decimal', 'unit' => 'V',
        'when' => ['attr' => 'kart-tx-techizat', 'in' => ['sebeke', 'adapter', 'akkumulyator', 'gunes-paneli']]],
    ['slug' => 'kart-tx-adapter', 'name' => $n('Adapter', 'Адаптер', 'Adapter'), 'type' => 'dropdown',
        'options' => $opts([['Komplektə daxildir', 'daxildir'], ['Ayrıca alınır', 'ayrica']]), 'when' => ['attr' => 'kart-tx-techizat', 'in' => ['adapter']]],
];
// «İdarəetmə uyğunluğu — bir neçə seçim və uyğunluq kodu; dəqiq protokol və tələb olunan mərkəz / model».
$controlCompat = fn (?array $w = null): array => [
    ['slug' => 'kart-tx-idare-uygunluq', 'name' => $n('İdarəetmə uyğunluğu', 'Совместимость управления', 'Control compatibility'), 'type' => 'multiselect', 'options' => [
        $a('Pult', ['slug' => 'pult']), $a('Divar düyməsi', ['slug' => 'duyme']), $a('Tətbiq', ['slug' => 'tetbiq']), $a('Ağıllı ev', ['slug' => 'agilli-ev', 'prompt' => $P_SMART]),
    ]] + ($w ? ['when' => $w] : []),
    ['slug' => 'kart-tx-idare-model', 'name' => $n('Tələb olunan mərkəz / model', 'Требуемый хаб / модель', 'Required hub / model'), 'type' => 'text',
        'when' => ['attr' => 'kart-tx-idare-uygunluq', 'in' => ['pult', 'duyme', 'tetbiq', 'agilli-ev']]]
        + $hint('Dəqiq protokol, uyğunluq kodu və tələb olunan mərkəz.', 'Точный протокол, код совместимости и нужный хаб.', 'The exact protocol, compatibility code and hub needed.'),
];
$compat = ['slug' => 'kart-tx-uygun-model', 'name' => $n('Uyğun model və ya sistem', 'Совместимая модель или система', 'Compatible model or system'), 'type' => 'text', 'required' => true]
    + $hint('Brend + seriya / model və ya istehsalçının uyğunluq kodu. Universal olduqda ölçü və uyğunluq şərtləri.',
        'Бренд + серия / модель или код совместимости. Для универсальных — размеры и условия.',
        'Brand + series / model or the maker’s compatibility code. For universal parts, size and conditions.');
$wind = fn (?array $h = null): array => ['slug' => 'kart-tx-kulek', 'name' => $n('Küləyə davamlılıq', 'Ветроустойчивость', 'Wind resistance'), 'type' => 'text']
    + ['hint' => $h ?? $n('İstehsalçının sinfi və ya həddi; ölçü və sınaq şərti ilə.', 'Класс или предел производителя; с размером и условием испытания.', 'The maker’s class or limit, with size and test condition.')];
$techDoc = ['slug' => 'kart-tx-texniki-melumat', 'name' => $n('Texniki məlumat', 'Техническая информация', 'Technical data'), 'type' => 'document']
    + $hint('Ölçü sxemi, uyğunluq siyahısı və ya təlimat: fayl və ya link.', 'Схема размеров, список совместимости или инструкция: файл или ссылка.', 'Size drawing, compatibility list or manual: a file or a link.');
$use = ['slug' => 'kart-tx-istifade-yeri', 'name' => $n('İstifadə', 'Применение', 'Use'), 'type' => 'dropdown', 'required' => true, 'filter' => true,
    'options' => $opts([['Daxili', 'daxili'], ['Açıq hava', 'aciq-hava'], ['Hər ikisi', 'her-ikisi']])]
    + $hint('İstehsalçıya görə.', 'По данным производителя.', 'Per the maker.');
$removableCover = ['slug' => 'kart-tx-cixarilan-uzluk', 'name' => $n('Çıxarılan üzlük', 'Съёмный чехол', 'Removable cover'), 'type' => 'dropdown', 'options' => $ynn];

$curtainKind = fn (string $code, array $items, bool $filter = true): array => $f($code, 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => $filter, 'options' => $items]);

return [
    'section' => 'tekstil-perdeler-gunesden-qorunma-ve-interyer-dekoru',

    'groups' => [
        // ════ 1. Pərdələr və günəşdən mühafizə ═════════════════════════════
        [
            'slug' => 'perdeler-ve-gunesden-muhafize',
            'name' => $n('Pərdələr və günəşdən mühafizə', 'Шторы и солнцезащита', 'Curtains and sun protection'),
            'classes' => [
                'hazir-perdeler' => $card('T01_1', $n('Pərdələr və tüllər', 'Шторы и тюль', 'Curtains and sheers'), $ps, [
                    $made,
                    $curtainKind('T01_1', $opts([['Pərdə', 'perde'], ['Tül', 'tul'], ['Pərdə-tül dəsti', 'dest']])),
                    $width(true, $n('Hazır panelin eni; büzülməmiş parçanın eni yox. Fərdi sifarişdə nümunə ölçüsü.',
                        'Ширина готового полотна, не ширина ткани до сборки. Для заказа — размер образца.',
                        'Finished panel width, not the flat fabric width. Made to size: the sample size.')),
                    $height(true, $n('Hazır panelin hündürlüyü.', 'Высота готового полотна.', 'Finished panel height.')),
                    ...$composition(),
                    $lightFilter,
                    ...$colorPattern,
                    $f('T01_1', 'asilma', $n('Asılma üsulu', 'Способ подвеса', 'Heading'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Pərdə lenti', 'lent'], ['Halqa', 'halqa'], ['İlgək', 'ilgek'], ['Tunel', 'tunel'], ['Wave', 'wave'],
                    ]), $other()]]),
                    $f('T01_1', 'astar', $n('Astarlıq', 'Подкладка', 'Lining'), 'dropdown', ['options' => [...$opts([
                        ['Yoxdur', 'yoxdur'], ['Standart', 'standart'], ['Blackout', 'blackout'],
                    ]), $other()]]),
                    $f('T01_1', 'panel-sayi', $n('Panel sayı', 'Число полотен', 'Panels'), 'decimal', ['required' => true, 'unit' => 'ədəd', 'rules' => ['integer' => true]]
                        + $hint('Qiymətə daxil olan panellər.', 'Полотна, входящие в цену.', 'Panels the price includes.')),
                    ...$care,
                    $setContents,
                ], $madeToOrder),
                'perdelik-uzluk-parca' => $card('T02_1', $n('Pərdə parçaları', 'Портьерные ткани', 'Curtain fabrics'), ['lm', 'roll'], [
                    $f('T02_1', 'teyinat', $n('Təyinat', 'Назначение', 'Purpose'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Portyer', 'portyer'], ['Tül', 'tul'], ['Astarlıq', 'astar'], ['Roma pərdəsi', 'roma'],
                    ]), $other()]]),
                    ...$composition(),
                    $f('T02_1', 'rulon-eni', $n('Rulon eni', 'Ширина рулона', 'Roll width'), 'decimal', ['required' => true, 'unit' => 'sm']
                        + $hint('Faydalı parça eni. Pərdənin hündürlüyü kimi avtomatik qəbul edilmir.', 'Полезная ширина ткани; не равна высоте шторы автоматически.', 'Usable fabric width; not automatically the curtain height.')),
                    $density,
                    $lightFilter,
                    ...$colorPattern,
                    $f('T02_1', 'raport-saquli', $n('Naxış təkrarı, şaquli', 'Раппорт, вертикальный', 'Pattern repeat, vertical'), 'decimal', ['unit' => 'sm',
                        'when' => $when('kart-tx-naxis', 'hendesi', 'bitki', 'klassik', 'usaq', 'diger')]),
                    $f('T02_1', 'raport-ufuqi', $n('Naxış təkrarı, üfüqi', 'Раппорт, горизонтальный', 'Pattern repeat, horizontal'), 'decimal', ['unit' => 'sm',
                        'when' => $when('kart-tx-naxis', 'hendesi', 'bitki', 'klassik', 'usaq', 'diger')]),
                    $f('T02_1', 'istiqamet', $n('Parçanın istiqaməti', 'Направление ткани', 'Fabric direction'), 'dropdown', ['options' => [...$opts([
                        ['Standart', 'standart'], ['Eninə istifadə mümkündür', 'enine'],
                    ]), $ns]]),
                    ...$care,
                    $f('T02_1', 'min-sifaris', $n('Minimum sifariş', 'Минимальный заказ', 'Minimum order'), 'decimal', ['unit' => 'm']
                        + $hint('Poqon metr; satış addımı ayrıca.', 'Погонные метры; шаг продажи отдельно.', 'Running metres; the sales step separately.')),
                    $f('T02_1', 'rulon-uzunlugu', $n('Rulonun uzunluğu', 'Длина рулона', 'Roll length'), 'decimal', ['unit' => 'm']
                        + $hint('Rulonla satışda. 1 poqon metr rulonun tam eni üzrə 1 m-dir.', 'При продаже рулоном. 1 пог. м — 1 м по всей ширине.', 'When sold by the roll. 1 running metre = 1 m over the full width.')),
                    $techDoc,
                ]),
                'rulon-perde' => $card('T03_1', $n('Rulon pərdələr', 'Рулонные шторы', 'Roller blinds'), $ps, [
                    $made,
                    $curtainKind('T03_1', $opts([['Standart', 'standart'], ['Gün-gecə', 'gun-gece'], ['İki rulonlu', 'iki-rulonlu']]))
                        + $hint('Gün-gecə parçası blackout ilə eyni deyil.', 'Ткань «день-ночь» — не блэкаут.', 'Day-night fabric is not blackout.'),
                    ...$systemSize,
                    $f('T03_1', 'parca-eni', $n('Parça eni', 'Ширина ткани', 'Fabric width'), 'decimal', ['unit' => 'mm']
                        + $hint('Ümumi sistem enindən ayrıca.', 'Отдельно от общей ширины системы.', 'Separate from the overall system width.')),
                    ...$composition(),
                    $lightFilter,
                    $f('T03_1', 'konstruksiya', $n('Konstruksiya', 'Конструкция', 'Construction'), 'dropdown', ['required' => true, 'options' => $opts([['Açıq rulon', 'aciq'], ['Kasetli', 'kasetli']])]),
                    $f('T03_1', 'yonlendirici', $n('Yan yönləndiricilər', 'Боковые направляющие', 'Side guides'), 'dropdown', ['options' => $ynn]),
                    $mount,
                    $control,
                    $controlSide,
                    $motorNote,
                    ...$colorPattern,
                    $kit('T03_1', [['Bərkidicilər', 'berkidiciler'], ['İdarəetmə', 'idareetme'], ['Təhlükəsizlik gərginləşdiricisi', 'tehlukesizlik'], ['Motor', 'motor']]),
                ], $madeToOrder),
                'roma-perdesi' => $card('T03_2', $n('Roma pərdələri', 'Римские шторы', 'Roman blinds'), $ps, [
                    $made,
                    ...$systemSize,
                    ...$composition(),
                    $lightFilter,
                    ...$colorPattern,
                    $f('T03_2', 'konstruksiya', $n('Konstruksiya', 'Конструкция', 'Construction'), 'dropdown', ['required' => true, 'options' => $opts([['Çubuqlu', 'cubuqlu'], ['Çubuqsuz', 'cubuqsuz']])]),
                    $f('T03_2', 'astar', $n('Astarlıq', 'Подкладка', 'Lining'), 'dropdown', ['options' => $opts([['Var', 'var'], ['Yoxdur', 'yoxdur']])]),
                    $mount,
                    $control,
                    $controlSide,
                    $motorNote,
                    $kit('T03_2', [['Mexanizm', 'mexanizm'], ['Parça', 'parca'], ['Çubuqlar', 'cubuqlar'], ['Bərkidicilər', 'berkidiciler'], ['Motor', 'motor']]),
                    ...$care,
                ], $madeToOrder),
                'plisse' => $card('T03_3', $n('Plisse pərdələr', 'Шторы плиссе', 'Pleated blinds'), $ps, [
                    $made,
                    $f('T03_3', 'qurulus', $n('Quruluş', 'Структура', 'Structure'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([['Birqat plisse', 'birqat'], ['Hüceyrəli', 'huceyreli']])]),
                    ...$systemSize,
                    $f('T03_3', 'forma', $n('Forma', 'Форма', 'Shape'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Düzbucaqlı', 'duzbucaqli'], ['Üçbucaq', 'ucbucaq'], ['Trapesiya', 'trapesiya'],
                    ]), $other()]]),
                    $f('T03_3', 'hereket', $n('Hərəkət', 'Движение', 'Movement'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Yuxarıdan aşağı', 'yuxaridan'], ['Aşağıdan yuxarı', 'asagidan'], ['Hər iki istiqamət', 'her-iki'],
                    ])]),
                    $lightFilter,
                    ...$colorPattern,
                    $mount,
                    $control,
                    $motorNote,
                    $f('T03_3', 'pencere', $n('Pəncərə tipi', 'Тип окна', 'Window type'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Şaquli', 'saquli'], ['Maili mansard', 'mansard'], ['Üfüqi', 'ufuqi'],
                    ])] + $hint('Modelin təyinatı.', 'Назначение модели.', 'What the model is made for.')),
                    $kit('T03_3', [['Bərkidicilər', 'berkidiciler'], ['Tutacaq', 'tutacaq'], ['Dartı sistemi', 'darti'], ['Motor', 'motor']]),
                    $techDoc,
                ], $madeToOrder),
                'jaluzler-ufuqi-saquli' => $card('T04_1', $n('Üfüqi jalüzlər', 'Горизонтальные жалюзи', 'Horizontal blinds'), $ps, [
                    $made,
                    ...$systemSize,
                    $f('T04_1', 'lamel-material', $n('Lamel materialı', 'Материал ламелей', 'Slat material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Alüminium', 'aluminium'], ['Ağac', 'agac'], ['Bambuk', 'bambuk'], ['PVC', 'pvc'],
                    ]), $other()]]),
                    $lamelWidth,
                    ...$colorFinish,
                    $mount,
                    $control,
                    $controlSide,
                    $motorNote,
                    $f('T04_1', 'beledci', $n('Bələdçi', 'Направляющие', 'Guides'), 'dropdown', ['options' => $opts([['Yoxdur', 'yoxdur'], ['Tros', 'tros'], ['Yan rels', 'rels']])]),
                    $kit('T04_1', [['Bərkidicilər', 'berkidiciler'], ['İdarəetmə', 'idareetme'], ['Təhlükəsizlik elementi', 'tehlukesizlik'], ['Motor', 'motor']]),
                    $techDoc,
                ], $madeToOrder),
                'saquli-jaluzler' => $card('T04_2', $n('Şaquli jalüzlər', 'Вертикальные жалюзи', 'Vertical blinds'), $ps, [
                    $made,
                    ...$systemSize,
                    $f('T04_2', 'lamel-material', $n('Lamel materialı', 'Материал ламелей', 'Slat material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Parça', 'parca'], ['PVC', 'pvc'], ['Alüminium', 'aluminium'],
                    ]), $other()]]),
                    $lamelWidth,
                    $lightFilter,
                    ...$colorPattern,
                    $f('T04_2', 'yigilma', $n('Yığılma istiqaməti', 'Направление сборки', 'Stacking'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Sola', 'sola'], ['Sağa', 'saga'], ['Mərkəzdən iki tərəfə', 'merkezden'],
                    ]), $other()]]),
                    $mount,
                    $control,
                    $controlSide,
                    $motorNote,
                    $kit('T04_2', [['Karniz', 'karniz'], ['Lamellər', 'lameller'], ['Çəkilər', 'cekiler'], ['Zəncir', 'zencir'], ['Bərkidicilər', 'berkidiciler']]),
                    $techDoc,
                ], $madeToOrder),
                'karnizler-o-cumleden-motorlasdirilmis' => $card('T05_1', $n('Mexaniki pərdə karnizləri', 'Механические карнизы', 'Manual curtain rails and rods'), ['piece', 'lm', 'set'], [
                    $curtainKind('T05_1', $opts([['Boru', 'boru'], ['Profil', 'profil'], ['Sim', 'sim']])),
                    $f('T05_1', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Alüminium', 'aluminium'], ['Polad', 'polad'], ['Ağac', 'agac'], ['Plastik', 'plastik'],
                    ]), $other()]]),
                    $f('T05_1', 'uzunluq', $n('Uzunluq', 'Длина', 'Length'), 'decimal', ['required' => true, 'unit' => 'mm', 'rules' => ['lte' => $s('T05_1', 'uzunluq-maks')]]
                        + $hint('Teleskopik karnizdə minimum; maksimum ayrıca.', 'Для телескопического — минимум; максимум отдельно.', 'For a telescopic rod, the minimum; the maximum separately.')),
                    $f('T05_1', 'uzunluq-maks', $n('Uzunluq, maks (teleskopik)', 'Длина, макс. (телескопический)', 'Length, max (telescopic)'), 'decimal', ['unit' => 'mm']),
                    $f('T05_1', 'sira', $n('Sıra sayı', 'Число рядов', 'Tracks'), 'decimal', ['required' => true, 'unit' => 'ədəd', 'rules' => ['integer' => true]]),
                    $f('T05_1', 'forma', $n('Forma', 'Форма', 'Shape'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Düz', 'duz'], ['Künc', 'kunc'], ['Əyri', 'eyri'], ['Elastik', 'elastik'],
                    ])]),
                    $mount,
                    $f('T05_1', 'profil-olcu', $n('Profil və ya boru ölçüsü', 'Размер профиля или трубы', 'Profile or rod size'), 'text',
                        $hint('Kəsik ölçüsü və ya diametr, mm.', 'Сечение или диаметр, мм.', 'Section or diameter, mm.')),
                    $f('T05_1', 'yuk', $n('Yük həddi', 'Допустимая нагрузка', 'Load limit'), 'decimal', ['unit' => 'kq']),
                    $f('T05_1', 'yuk-sert', $n('Yük həddinin şərti', 'Условие нагрузки', 'Load condition'), 'text',
                        $hint('Karnizin uzunluğu və kronşteyn addımı — hansı üçün həddin verildiyi.', 'Длина карниза и шаг кронштейнов, для которых дан предел.', 'The rod length and bracket spacing the limit is for.')),
                    ...$colorFinish,
                    $kit('T05_1', [['Kronşteyn', 'kronsteyn'], ['Halqa', 'halqa'], ['Qaçışçı', 'qacisci'], ['Sonluq', 'sonluq'], ['Bərkidici', 'berkidici']]),
                    $techDoc,
                ]),
                'elektrik-karniz-sistemleri' => $card('T05_2', $n('Elektrik karniz sistemləri', 'Электрокарнизы', 'Motorised curtain tracks'), ['set', 'lm'], [
                    $f('T05_2', 'uzunluq', $n('Karniz uzunluğu', 'Длина карниза', 'Track length'), 'decimal', ['required' => true, 'unit' => 'mm']
                        + $hint('Seçilən komplekt üçün. Metr qiymətində komplekt təsvirdə göstərilir.', 'Для выбранного комплекта. При цене за метр комплект — в описании.', 'For the chosen kit. When priced per metre, state the kit in the description.')),
                    $f('T05_2', 'forma', $n('Forma', 'Форма', 'Shape'), 'dropdown', ['required' => true, 'options' => $opts([['Düz', 'duz'], ['Künc', 'kunc'], ['Əyri', 'eyri']])]),
                    $f('T05_2', 'acilma', $n('Açılma', 'Открывание', 'Opening'), 'dropdown', ['required' => true, 'options' => $opts([['Bir tərəfə', 'bir-terefe'], ['Mərkəzdən iki tərəfə', 'merkezden']])]),
                    $f('T05_2', 'ceki', $n('Pərdə çəkisi həddi', 'Макс. вес штор', 'Curtain weight limit'), 'decimal', ['required' => true, 'unit' => 'kq']
                        + $hint('Konkret uzunluq və forma üçün; motorun həddi relsin uzunluğu və əyrilikləri ilə birlikdə yoxlanılır.', 'Для конкретной длины и формы; лимит мотора проверяется с длиной и изгибами.', 'For this length and shape; the motor limit depends on length and bends.')),
                    $f('T05_2', 'ceki-cedveli', $n('İstehsalçının çəki cədvəli', 'Таблица нагрузок производителя', 'Maker’s load table'), 'document'),
                    $mount,
                    ...$supply(),
                    ...$controlCompat(),
                    $f('T05_2', 'elle', $n('Əllə hərəkətlə işə düşmə', 'Запуск рывком руки', 'Touch-and-go start'), 'dropdown', ['options' => $ynn]),
                    ...$colorFinish,
                    $kit('T05_2', [['Karniz', 'karniz'], ['Motor', 'motor'], ['Adapter', 'adapter'], ['Pult', 'pult'], ['Qaçışçılar', 'qaciscilar'], ['Bərkidicilər', 'berkidiciler']]),
                    $compat,
                    $techDoc,
                ]),
                'karnizler-ucun-kronsteynler-ve-berkidiciler' => $card('T06_1', $n('Pərdə asılma və montaj hissələri', 'Фурнитура для подвеса и монтажа штор', 'Curtain hanging and fitting parts'), ['piece', 'pack', 'lm', 'set'], [
                    $f('T06_1', 'hisse', $n('Hissə', 'Деталь', 'Part'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Kronşteyn', 'kronsteyn'], ['Qaçışçı', 'qacisci'], ['Qarmaq', 'qarmaq'], ['Halqa', 'halqa'], ['Sonluq', 'sonluq'], ['Birləşdirici', 'birlesdirici'], ['Pərdə lenti', 'lent'],
                    ]), $other()]] + $hint('Lüvers və halqalar «Halqa»dadır.', 'Люверсы и кольца — «Кольцо».', 'Eyelets and rings are «Ring».')),
                    $f('T06_1', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Metal', 'metal'], ['Plastik', 'plastik'], ['Ağac', 'agac'], ['Tekstil', 'tekstil'],
                    ]), $other()]]),
                    $f('T06_1', 'olculer', $n('Ölçülər', 'Размеры', 'Dimensions'), 'text', ['required' => true]
                        + $hint('Diametr, kəsik, uzunluq və ya lent eni, mm.', 'Диаметр, сечение, длина или ширина ленты, мм.', 'Diameter, section, length or tape width, mm.')),
                    $compat,
                    ...$colorFinish,
                    $f('T06_1', 'paket-miqdar', $n('Paketdə miqdar', 'Количество в упаковке', 'Quantity per pack'), 'decimal'),
                    $f('T06_1', 'paket-vahid', $n('Miqdarın vahidi', 'Единица количества', 'Quantity unit'), 'dropdown', ['options' => $opts([['ədəd', 'eded'], ['m', 'm']])]),
                    $techDoc,
                ]),
                'perde-tutacaqlari-ve-dekor-aksesuarlari' => $card('T06_2', $n('Pərdə tutacaqları və dekor aksesuarları', 'Подхваты и декор для штор', 'Tie-backs and curtain accessories'), $ps, [
                    $curtainKind('T06_2', [...$opts([['Maqnit tutacaq', 'maqnit'], ['Parça bağ', 'bag'], ['Divar qarmağı', 'qarmaq'], ['Qotaz', 'qotaz']]), $other()]),
                    $f('T06_2', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Tekstil', 'tekstil'], ['Metal', 'metal'], ['Plastik', 'plastik'], ['Ağac', 'agac'],
                    ]), $other()]]),
                    $f('T06_2', 'olculer', $n('Ölçülər', 'Размеры', 'Dimensions'), 'text', ['required' => true]
                        + $hint('Uzunluq və ya qabarit ölçülər, cm.', 'Длина или габариты, см.', 'Length or overall size, cm.')),
                    ...$colorPattern,
                    $f('T06_2', 'montaj', $n('Montaj', 'Крепление', 'Fixing'), 'dropdown', ['options' => [...$opts([['Divara', 'divar'], ['Pərdəyə', 'perde'], ['Maqnit', 'maqnit']]), $other()]]),
                    $setQty,
                    $kit('T06_2', [['Bərkidicilər', 'berkidiciler']]),
                ]),
                'jaluz-ve-perde-mexanizmleri' => $card('T06_3', $n('Jalüz və pərdə mexanizmləri', 'Механизмы для жалюзи и штор', 'Blind and curtain mechanisms'), ['piece', 'lm', 'set'], [
                    $f('T06_3', 'hisse', $n('Hissə', 'Деталь', 'Part'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Zəncir mexanizmi', 'zencir-mexanizmi'], ['Zəncir', 'zencir'], ['Yay mexanizmi', 'yay'], ['Boru', 'boru'], ['Lamel', 'lamel'], ['Çəki', 'ceki'],
                        ['Motor', 'motor'], ['Təhlükəsizlik gərginləşdiricisi', 'tehlukesizlik'],
                    ]), $other()]] + $hint('Sadə ehtiyat hissəsində elektrik sahələri açılmır.', 'Для простой запчасти электрические поля не открываются.', 'Electrical fields open only for a motor.')),
                    $compat,
                    $f('T06_3', 'olcu', $n('Ölçü', 'Размер', 'Size'), 'text', ['required' => true]
                        + $hint('Uyğun boru diametri, lamel eni və ya hissə ölçüsü, mm.', 'Диаметр трубы, ширина ламели или размер детали, мм.', 'Tube diameter, slat width or part size, mm.')),
                    $controlSide,
                    $f('T06_3', 'moment', $n('Fırlanma momenti', 'Крутящий момент', 'Torque'), 'decimal', ['unit' => 'Nm', 'when' => $when($s('T06_3', 'hisse'), 'motor')]),
                    $f('T06_3', 'suret', $n('Sürət', 'Скорость', 'Speed'), 'decimal', ['unit' => 'dövr/dəq', 'when' => $when($s('T06_3', 'hisse'), 'motor')]),
                    ...$supply($when($s('T06_3', 'hisse'), 'motor')),
                    ...$controlCompat($when($s('T06_3', 'hisse'), 'motor')),
                    ...$colorFinish,
                    $kit('T06_3', [['Adapterlər', 'adapterler'], ['Bərkidicilər', 'berkidiciler'], ['Pult', 'pult']]),
                    $techDoc,
                ]),
                'markizler' => $card('T07_1', $n('Markizlər', 'Маркизы', 'Awnings'), ['piece', 'set', 'm2'], [
                    $made,
                    $curtainKind('T07_1', [...$opts([['Açıq', 'aciq'], ['Yarımkaset', 'yarimkaset'], ['Tam kaset', 'tam-kaset'], ['Səbət tipli', 'sebet']]), $other()]),
                    $f('T07_1', 'en', $n('Ümumi en', 'Общая ширина', 'Overall width'), 'decimal', ['required' => true, 'unit' => 'mm']
                        + $hint('Fərdi sifarişdə qiymətin aid olduğu nümunə ölçüsü.', 'Для заказа — размер образца, к которому относится цена.', 'Made to size: the sample size the price is for.')),
                    $f('T07_1', 'cixinti', $n('Açılmış çıxıntı', 'Вылет в раскрытом виде', 'Opened projection'), 'decimal', ['required' => true, 'unit' => 'mm']),
                    $f('T07_1', 'parca', $n('Parça', 'Ткань', 'Fabric'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Akril', 'akril'], ['Örtüklü polyester', 'polyester'], ['PVC', 'pvc'],
                    ]), $other()]]),
                    ...$colorPattern,
                    $f('T07_1', 'karkas', $n('Karkas', 'Каркас', 'Frame'), 'dropdown', ['required' => true, 'options' => $opts([['Alüminium', 'aluminium'], ['Polad', 'polad'], ['Kombinə', 'kombine']])]),
                    $f('T07_1', 'montaj', $n('Montaj', 'Монтаж', 'Mounting'), 'dropdown', ['required' => true, 'options' => $opts([['Divara', 'divar'], ['Tavana', 'tavan'], ['Konstruksiyaya', 'konstruksiya']])]),
                    $control,
                    ...$supply($when('kart-tx-idareetme', 'elektrik')),
                    $wind($n('İstehsalçının sinfi və ya həddi; ölçü və sınaq şərti ilə. Markiz yağış örtüyü deyil.', 'Класс или предел производителя. Маркиза — не защита от дождя.', 'The maker’s class or limit. An awning is not a rain cover.')),
                    $kit('T07_1', [['Motor', 'motor'], ['Pult', 'pult'], ['Külək sensoru', 'kulek-sensoru'], ['Kronşteynlər', 'kronsteynler']]),
                    $techDoc,
                ], $madeToOrder),
                'xarici-ekran-perdeleri' => $card('T07_2', $n('Xarici ekran pərdələri', 'Наружные screen-шторы', 'Exterior screen blinds'), $ps, [
                    $made,
                    $f('T07_2', 'sistem', $n('Sistem', 'Система', 'System'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['ZIP', 'zip'], ['Yan rels', 'rels'], ['Troslu', 'troslu'], ['Sərbəst', 'serbest'],
                    ])]),
                    ...$systemSize,
                    $f('T07_2', 'parca', $n('Parça materialı', 'Материал ткани', 'Fabric'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['PVC örtüklü polyester', 'pvc-polyester'], ['PVC örtüklü şüşə lif', 'pvc-suse-lif'],
                    ]), $other()]]),
                    $f('T07_2', 'aciqliq', $n('Açıqlıq əmsalı', 'Коэффициент открытости', 'Openness factor'), 'decimal', ['unit' => '%', 'rules' => ['max' => 100]]
                        + $hint('Screen parçanın toxuma açıqlığı; işıqkeçirmə və UV faizi deyil.', 'Открытость плетения; не светопропускание и не UV.', 'Weave openness; not light or UV transmission.')),
                    $lightFilter,
                    ...$colorPattern,
                    $control,
                    ...$supply($when('kart-tx-idareetme', 'elektrik')),
                    $wind($n('İstehsalçı sinfi / həddi və uyğun ölçü.', 'Класс / предел производителя и размер.', 'The maker’s class / limit and size.')),
                    $kit('T07_2', [['Kaset', 'kaset'], ['Yönləndirici', 'yonlendirici'], ['Motor', 'motor'], ['Pult', 'pult'], ['Sensor', 'sensor'], ['Montaj dəsti', 'montaj']]),
                    $techDoc,
                ], $madeToOrder),
                'xarici-jaluzler-rafstorlar' => $card('T07_3', $n('Xarici jalüzlər — rafştorlar', 'Наружные жалюзи — раффшторы', 'Exterior venetian blinds'), $ps, [
                    $made,
                    ...$systemSize,
                    $f('T07_3', 'profil', $n('Lamel profili', 'Профиль ламели', 'Slat profile'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['C', 'c'], ['Z', 'z'], ['S', 's'], ['Yastı', 'yasti'],
                    ]), $other()]]),
                    $lamelWidth,
                    $f('T07_3', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'options' => [$a('Alüminium', ['slug' => 'aluminium']), $other()]]),
                    ...$colorFinish,
                    $f('T07_3', 'yonlendirici', $n('Yan yönləndirici', 'Боковая направляющая', 'Side guide'), 'dropdown', ['required' => true, 'options' => $opts([['Rels', 'rels'], ['Tros', 'tros']])]),
                    $control,
                    ...$supply($when('kart-tx-idareetme', 'elektrik')),
                    $wind($n('İstehsalçı sinfi / həddi, məhsulun ölçüsü və montajına görə.', 'Класс / предел производителя по размеру и монтажу.', 'The maker’s class / limit for this size and mounting.')),
                    $kit('T07_3', [['Üst qutu', 'qutu'], ['Motor', 'motor'], ['Pult', 'pult'], ['Sensor', 'sensor'], ['Yönləndiricilər', 'yonlendiriciler']]),
                    $techDoc,
                ], $madeToOrder),
                'gunesden-qoruyucu-yelkenler' => $card('T07_4', $n('Günəşdən qoruyucu yelkənlər', 'Теневые паруса', 'Shade sails'), $ps, [
                    $made,
                    $f('T07_4', 'forma', $n('Forma', 'Форма', 'Shape'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Üçbucaq', 'ucbucaq'], ['Düzbucaqlı', 'duzbucaqli'], ['Kvadrat', 'kvadrat'],
                    ]), $other()]]),
                    $f('T07_4', 'olculer', $n('Ölçülər', 'Размеры', 'Dimensions'), 'text', ['required' => true]
                        + $hint('Hər tərəfin uzunluğu, cm; hazır məhsul. Məs. 360 × 360 × 360.', 'Длина каждой стороны, см; готовое изделие.', 'Each side’s length, cm; finished item.')),
                    $f('T07_4', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'options' => [...$opts([['HDPE', 'hdpe'], ['Polyester', 'polyester']]), $other()]]),
                    $density,
                    $f('T07_4', 'su', $n('Su davranışı', 'Водопроницаемость', 'Water behaviour'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Sukeçirən', 'sukeciren'], ['Su itələyən', 'su-iteleyen'], ['Su keçirməyən', 'su-kecirmeyen'],
                    ])] + $hint('İstehsalçı məlumatı.', 'По данным производителя.', 'Per the maker.')),
                    ...$colorPattern,
                    $f('T07_4', 'uv', $n('UV göstəricisi', 'Показатель UV', 'UV rating'), 'text',
                        $hint('Faiz və ya sinif, ölçmə üsulu ilə; istehsalçı məlumatı.', 'Процент или класс с методом измерения.', 'A percentage or class with the test method.')),
                    $hint('Yalnız qiymətə daxil olanlar. Dayaq və bərkitmə daxil deyilsə ayrıca göstərilir.', 'Только то, что входит в цену. Если опоры и крепёж не входят — укажите отдельно.', 'Only what the price includes. If posts and fixings are not included, say so.')
                        + $kit('T07_4', [['İplər', 'ipler'], ['Dartıcılar', 'darticilar'], ['Bərkidicilər', 'berkidiciler'], ['Dayaqlar', 'dayaqlar']]),
                    $techDoc,
                ], $madeToOrder),
            ],
        ],

        // ════ 2. Ev tekstili ═══════════════════════════════════════════════
        [
            'slug' => 'ev-tekstili',
            'name' => $n('Ev tekstili', 'Домашний текстиль', 'Home textiles'),
            'classes' => [
                'xalca-dekorativ-ededli' => $card('T08_1', $n('Xalçalar və xalça yolları', 'Ковры и ковровые дорожки', 'Rugs and runners'), ['piece', 'm2', 'lm'], [
                    $made,
                    $curtainKind('T08_1', $opts([['Xalça', 'xalca'], ['Xalça yolu', 'yol'], ['Kilim', 'kilim']]))
                        + $hint('Divardan divara kovrolin «Döşəmə örtükləri» bölməsindədir.', 'Ковролин от стены до стены — в разделе «Напольные покрытия».', 'Wall-to-wall carpet is under «Floor coverings».'),
                    $f('T08_1', 'forma', $n('Forma', 'Форма', 'Shape'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Düzbucaqlı', 'duzbucaqli'], ['Dairəvi', 'dairevi'], ['Oval', 'oval'], ['Sərbəst', 'serbest'],
                    ])]),
                    $sizeLW(true, $n('En × uzunluq; dairəvidə hər ikisi diametrdir. Metrlə satılan yolda en sabitdir, uzunluq — rulonun uzunluğu.',
                        'Ширина × длина; для круглого — диаметр. Для дорожки на метраж ширина постоянна.', 'Width × length; round: the diameter. A runner sold by the metre has a fixed width.')),
                    $f('T08_1', 'xov-material', $n('Xov materialı', 'Материал ворса', 'Pile material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Yun', 'yun'], ['Pambıq', 'pambiq'], ['Viskoza', 'viskoza'], ['Polipropilen', 'polipropilen'], ['Polyester', 'polyester'], ['Qarışıq', 'qarisiq'],
                    ]), $other()]]),
                    $f('T08_1', 'istehsal', $n('İstehsal üsulu', 'Способ изготовления', 'Construction'), 'dropdown', ['options' => [...$opts([
                        ['Maşın toxuması', 'masin'], ['Əl toxuması', 'el'], ['Tuftinq', 'tuftinq'], ['Yastı toxuma', 'yasti'],
                    ]), $other()]]),
                    $f('T08_1', 'xov-hundurluk', $n('Xov hündürlüyü', 'Высота ворса', 'Pile height'), 'decimal', ['unit' => 'mm']
                        + $hint('Xovsuz məhsulda 0.', 'Для безворсового — 0.', '0 for a flat weave.')),
                    $f('T08_1', 'alt-esas', $n('Alt əsas', 'Основа', 'Backing'), 'dropdown', ['options' => [...$opts([
                        ['Jüt', 'jut'], ['Tekstil', 'tekstil'], ['Lateks', 'lateks'], ['Rezin', 'rezin'],
                    ]), $other()]]),
                    ...$colorPattern,
                    $use,
                    ...$care,
                ], $madeToOrder),
                'yataq-ortukleri-ve-pledler' => $card('T09_1', $n('Yataq örtükləri və pledlər', 'Покрывала и пледы', 'Bedspreads and throws'), $ps, [
                    $curtainKind('T09_1', $opts([['Yataq örtüyü', 'ortuk'], ['Pled', 'pled'], ['Dekorativ örtük', 'dekorativ']])),
                    $sizeLW(),
                    ...$composition(),
                    $f('T09_1', 'qurulus', $n('Quruluş', 'Структура', 'Construction'), 'dropdown', ['options' => [...$opts([
                        ['Təkqat', 'tekqat'], ['Sırıqlı', 'siriqli'], ['Trikotaj', 'trikotaj'],
                    ]), $other()]]),
                    $f('T09_1', 'dolgu', $n('Dolğu', 'Наполнитель', 'Filling'), 'dropdown', ['options' => [...$opts([
                        ['Yoxdur', 'yoxdur'], ['Polyester', 'polyester'], ['Pambıq', 'pambiq'],
                    ]), $other()]]),
                    ...$colorPattern,
                    ...$care,
                    $setContents,
                ]),
                'dekorativ-yastiq-interyer' => $card('T10_1', $n('Dekorativ yastıqlar', 'Декоративные подушки', 'Decorative cushions'), $ps, [
                    $f('T10_1', 'forma', $n('Forma', 'Форма', 'Shape'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Kvadrat', 'kvadrat'], ['Düzbucaqlı', 'duzbucaqli'], ['Dairəvi', 'dairevi'], ['Valik', 'valik'],
                    ]), $other()]]),
                    $sizeLW(),
                    ...$composition(),
                    $f('T10_1', 'dolgu', $n('Dolğu', 'Наполнитель', 'Filling'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Polyester', 'polyester'], ['Köpük', 'kopuk'], ['Lələk-tük', 'lelek-tuk'],
                    ]), $other()]]),
                    $removableCover,
                    ...$colorPattern,
                    ...$care,
                    $setQty,
                ]),
                'dekorativ-yastiq-uzlukleri' => $card('T10_2', $n('Dekorativ yastıq üzlükləri', 'Наволочки для декоративных подушек', 'Cushion covers'), $ps, [
                    $sizeLW(),
                    ...$composition(),
                    $f('T10_2', 'baglanma', $n('Bağlanma', 'Застёжка', 'Closure'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Fermuar', 'fermuar'], ['Düymə', 'duyme'], ['Üst-üstə keçən', 'ust-uste'],
                    ]), $other()]] + $hint('Yastıq içliyi daxil deyil; ayrıca göstərilir.', 'Внутренняя подушка не входит; указывается отдельно.', 'The insert is not included; list it separately.')),
                    ...$colorPattern,
                    ...$care,
                    $setQty,
                ]),
                'mebel-uzlukleri' => $card('T10_3', $n('Mebel üzlükləri', 'Чехлы для мебели', 'Furniture covers'), $ps, [
                    $f('T10_3', 'teyinat', $n('Təyinat', 'Назначение', 'For'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Divan', 'divan'], ['Kreslo', 'kreslo'], ['Stul', 'stul'],
                    ]), $other()]]),
                    $f('T10_3', 'uygun-olcu', $n('Uyğun ölçü', 'Подходящий размер', 'Fits'), 'text', ['required' => true]
                        + $hint('Mebelin eni, dərinliyi, hündürlüyü və tələb olunan model ölçüləri, cm. Mebel və üzlük ayrıca məhsullardır.',
                            'Ширина, глубина, высота мебели и нужные размеры модели, см. Мебель и чехол — разные товары.',
                            'Furniture width, depth, height and model measurements, cm. The furniture is a separate product.')),
                    $f('T10_3', 'hazirlanma', $n('Hazırlanma', 'Изготовление', 'Make'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Standart', 'standart'], ['Elastik universal', 'elastik'], ['Ölçüyə sifariş', 'olcuye'],
                    ])] + $hint('Ölçüyə sifarişdə mövcudluq «Sifarişlə»dir.', 'Под размер — «Под заказ».', 'Made to measure is «On order».')),
                    ...$composition(),
                    $f('T10_3', 'baglanma', $n('Bağlanma', 'Застёжка', 'Closure'), 'dropdown', ['options' => $opts([
                        ['Rezin', 'rezin'], ['Bağ', 'bag'], ['Fermuar', 'fermuar'], ['Yapışqan lent', 'yapisqan'], ['Yoxdur', 'yoxdur'],
                    ])]),
                    ...$colorPattern,
                    ...$care,
                    $setContents,
                ], ['on_order_when' => ['attr' => $s('T10_3', 'hazirlanma'), 'in' => ['olcuye']]]),
                'yataq-desti-ve-yataq-otagi-tekstili' => $card('T11_1', $n('Yataq dəstləri, mələfələr və üzlüklər', 'Постельное бельё', 'Bed linen'), $ps, [
                    $f('T11_1', 'mehsul', $n('Məhsul', 'Изделие', 'Item'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Yataq dəsti', 'dest'], ['Mələfə', 'melefe'], ['Yorğan üzü', 'yorgan-uzu'], ['Yastıq üzü', 'yastiq-uzu'],
                    ])]),
                    $f('T11_1', 'olculer', $n('Ölçülər', 'Размеры', 'Dimensions'), 'textarea', ['required' => true]
                        + $hint('Hər elementin en × uzunluğu, cm. «Birnəfərlik / ikinəfərlik» adı ölçüləri əvəz etmir.', 'Ширина × длина каждого элемента, см. «Полуторка / двуспальный» не заменяет размеры.', 'Width × length of each item, cm. «Single / double» does not replace sizes.')),
                    ...$composition(),
                    $f('T11_1', 'toxuma', $n('Toxuma', 'Переплетение', 'Weave'), 'dropdown', ['options' => [...$opts([
                        ['Satin', 'satin'], ['Perkal', 'perkal'], ['Ranfors', 'ranfors'], ['Trikotaj', 'trikotaj'],
                    ]), $other()]]),
                    $f('T11_1', 'melefe', $n('Mələfə tipi', 'Тип простыни', 'Sheet type'), 'dropdown', ['options' => $opts([
                        ['Standart', 'standart'], ['Rezinli', 'rezinli'], ['Daxil deyil', 'daxil-deyil'],
                    ])]),
                    $f('T11_1', 'dosek-hundurluk', $n('Uyğun döşək hündürlüyü', 'Высота матраса', 'Mattress depth'), 'decimal', ['unit' => 'sm',
                        'when' => $when($s('T11_1', 'melefe'), 'rezinli')]),
                    ...$colorPattern,
                    ...$care,
                    $setContents,
                ]),
                'yorgan' => $card('T11_2', $n('Yorğanlar', 'Одеяла', 'Duvets'), $ps, [
                    $sizeLW(),
                    $f('T11_2', 'dolgu', $n('Dolğu', 'Наполнитель', 'Filling'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Polyester', 'polyester'], ['Yun', 'yun'], ['Pambıq', 'pambiq'], ['İpək', 'ipek'], ['Lələk-tük', 'lelek-tuk'],
                    ]), $other()]]),
                    ...$coverComposition($n('Üz parçasının lifləri.', 'Волокна чехла.', 'The cover’s fibres.')),
                    $f('T11_2', 'istilik', $n('İstilik səviyyəsi', 'Степень теплоты', 'Warmth'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Yay', 'yay'], ['Bütün mövsüm', 'butun-movsum'], ['Qış', 'qis'], ['Birləşdirilən', 'birlesdirilen'],
                    ])] + $hint('İstehsalçı təsnifatı.', 'Классификация производителя.', 'The maker’s class.')),
                    $f('T11_2', 'dolgu-sixligi', $n('Dolğu sıxlığı', 'Плотность наполнителя', 'Filling weight'), 'decimal', ['unit' => 'g/m²']
                        + $hint('Ümumi məhsul sıxlığı ilə qarışdırılmır.', 'Не путать с плотностью всего изделия.', 'Not the whole product’s weight.')),
                    ...$care,
                    $setQty,
                ]),
                'yastiq-yatmaq-ucun' => $card('T11_3', $n('Yuxu yastıqları', 'Подушки для сна', 'Bed pillows'), $ps, [
                    $f('T11_3', 'forma', $n('Forma', 'Форма', 'Shape'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Standart', 'standart'], ['Anatomik formalı', 'anatomik'], ['Valik', 'valik'],
                    ]), $other()]] + $hint('Anatomik forma tibbi təsir iddiası deyil.', 'Анатомическая форма — не медицинское заявление.', 'An anatomical shape is not a medical claim.')),
                    $sizeLW(),
                    $height(),
                    $f('T11_3', 'dolgu', $n('Dolğu', 'Наполнитель', 'Filling'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Polyester', 'polyester'], ['Lələk-tük', 'lelek-tuk'], ['Lateks', 'lateks'], ['Yaddaş köpüyü', 'yaddas-kopuyu'],
                    ]), $other()]]),
                    $f('T11_3', 'sertlik', $n('Sərtlik', 'Жёсткость', 'Firmness'), 'dropdown', ['options' => $opts([['Yumşaq', 'yumsaq'], ['Orta', 'orta'], ['Sərt', 'sert']])]
                        + $hint('İstehsalçı şkalası.', 'Шкала производителя.', 'The maker’s scale.')),
                    ...$coverComposition($n('Üzlüyün lifləri.', 'Волокна чехла.', 'The cover’s fibres.')),
                    $f('T11_3', 'tenzim', $n('Hündürlük tənzimlənir', 'Регулируемая высота', 'Adjustable height'), 'dropdown', ['options' => $ynn]),
                    $removableCover,
                    ...$care,
                ]),
                'dosek-qoruyucusu-qoruyucu-uzluk' => $card('T11_4', $n('Döşək qoruyucuları və nazik üst döşəklər', 'Наматрасники и топперы', 'Mattress protectors and toppers'), $ps, [
                    $curtainKind('T11_4', $opts([['Qoruyucu üzlük', 'qoruyucu'], ['Su keçirməyən qoruyucu', 'su-kecirmeyen'], ['Nazik üst döşək — topper', 'topper']]))
                        + $hint('Əsas döşəklər «Mebel» bölməsindədir.', 'Основные матрасы — в разделе «Мебель».', 'Mattresses are in «Furniture».'),
                    $sizeLW(),
                    $f('T11_4', 'qalinliq', $n('Qalınlıq', 'Толщина', 'Thickness'), 'decimal', ['unit' => 'sm', 'when' => $when($s('T11_4', 'tip'), 'topper')]),
                    $f('T11_4', 'dosek-min', $n('Uyğun döşək hündürlüyü, min', 'Высота матраса, мин.', 'Mattress depth, min'), 'decimal', ['unit' => 'sm',
                        'rules' => ['lte' => $s('T11_4', 'dosek-maks')]] + $hint('Ətəkli qoruyucuda.', 'Для наматрасника с юбкой.', 'For a skirted protector.')),
                    $f('T11_4', 'dosek-maks', $n('Uyğun döşək hündürlüyü, maks', 'Высота матраса, макс.', 'Mattress depth, max'), 'decimal', ['unit' => 'sm']),
                    $f('T11_4', 'berkitme', $n('Bərkitmə', 'Крепление', 'Fixing'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Künc rezinləri', 'kunc-rezin'], ['Rezinli ətək', 'etek'], ['Fermuar', 'fermuar'], ['Yoxdur', 'yoxdur'],
                    ])]),
                    $f('T11_4', 'qatlar', $n('Dolğu və qatlar', 'Наполнитель и слои', 'Filling and layers'), 'multiselect', ['options' => [
                        ...$opts([['Köpük', 'kopuk'], ['Lateks', 'lateks'], ['Lif', 'lif'], ['Membran', 'membran']]), $noneX('Dolğusuz', 'dolgusuz'), $other(),
                    ]]),
                    ...$coverComposition($n('Üst parçanın lifləri.', 'Волокна верхней ткани.', 'The top fabric’s fibres.')),
                    ...$care,
                ]),
                'sufre-ve-metbex-tekstili' => $card('T12_1', $n('Süfrələr və masa tekstili', 'Скатерти и столовый текстиль', 'Tablecloths and table linen'), $ps, [
                    $curtainKind('T12_1', $opts([['Süfrə', 'sufre'], ['Masa yolu', 'masa-yolu'], ['Salfet', 'salfet'], ['Altlıq', 'altliq']])),
                    $f('T12_1', 'forma', $n('Forma', 'Форма', 'Shape'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Düzbucaqlı', 'duzbucaqli'], ['Dairəvi', 'dairevi'], ['Oval', 'oval'], ['Kvadrat', 'kvadrat'],
                    ]), $other()]]),
                    $sizeLW(),
                    ...$composition(),
                    ...$colorPattern,
                    $f('T12_1', 'leke', $n('Ləkə itələyən emal', 'Грязеотталкивающая пропитка', 'Stain-repellent finish'), 'dropdown', ['options' => $ynn]),
                    ...$care,
                    $setContents,
                ]),
                'desmal' => $card('T12_2', $n('Dəsmallar', 'Полотенца', 'Towels'), $ps, [
                    $f('T12_2', 'teyinat', $n('Təyinat', 'Назначение', 'For'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Əl', 'el'], ['Üz', 'uz'], ['Hamam', 'hamam'], ['Mətbəx', 'metbex'], ['Dəst', 'dest'],
                    ])]),
                    $sizeLW(),
                    ...$composition(),
                    $density,
                    $f('T12_2', 'toxuma', $n('Toxuma', 'Плетение', 'Weave'), 'dropdown', ['options' => [...$opts([
                        ['Xovlu', 'xovlu'], ['Vafli', 'vafli'], ['Yastı', 'yasti'],
                    ]), $other()]]),
                    ...$colorPattern,
                    ...$care,
                    $setContents,
                ]),
                'vanna-otagi-ucun-ayaqalti' => $card('T12_3', $n('Hamam xalçaları', 'Коврики для ванной', 'Bath mats'), $ps, [
                    $sizeLW(),
                    $f('T12_3', 'ust-material', $n('Üst material', 'Материал верха', 'Top material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Pambıq', 'pambiq'], ['Mikrofiber', 'mikrofiber'], ['Polyester', 'polyester'], ['Bambuk', 'bambuk'], ['Diatomit', 'diatomit'],
                    ]), $other()]]),
                    $f('T12_3', 'alt-esas', $n('Alt əsas', 'Основа', 'Backing'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Rezin', 'rezin'], ['Lateks', 'lateks'], ['TPR', 'tpr'], ['Tekstil', 'tekstil'],
                    ]), $other()]]),
                    $f('T12_3', 'surusme', $n('Sürüşməyə qarşı altlıq', 'Противоскользящая основа', 'Non-slip backing'), 'dropdown', ['options' => $ynn]),
                    $f('T12_3', 'qalinliq', $n('Qalınlıq', 'Толщина', 'Thickness'), 'decimal', ['unit' => 'mm']),
                    ...$colorPattern,
                    ...$care,
                    $setContents,
                ]),
                'hamam-perdeleri' => $card('T12_4', $n('Hamam pərdələri', 'Шторы для ванной', 'Shower curtains'), $ps, [
                    $width(),
                    $height(),
                    $f('T12_4', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Polyester', 'polyester'], ['PEVA', 'peva'], ['EVA', 'eva'], ['PVC', 'pvc'],
                    ]), $other()]]),
                    $f('T12_4', 'su', $n('Suya qarşı xüsusiyyət', 'Водостойкость', 'Water resistance'), 'dropdown', ['options' => [...$opts([
                        ['Su itələyən', 'su-iteleyen'], ['Su keçirməyən', 'su-kecirmeyen'],
                    ]), $ns]]),
                    $f('T12_4', 'asilma', $n('Asılma', 'Подвес', 'Hanging'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Halqa', 'halqa'], ['Qarmaq', 'qarmaq'], ['İlgək', 'ilgek'],
                    ]), $other()]]),
                    ...$colorPattern,
                    $kit('T12_4', [['Halqalar', 'halqalar'], ['Çubuq', 'cubuq']]),
                    ...$care,
                ]),
            ],
            // No card in the workbook — plain form.
            'plain' => ['hamam-xalati'],
        ],

        // ════ 3. İnteryer dekoru ═══════════════════════════════════════════
        [
            'slug' => 'interyer-dekoru',
            'name' => $n('İnteryer dekoru', 'Интерьерный декор', 'Interior decor'),
            'classes' => [
                'dekorativ-guzgu-interyer' => $card('T13_1', $n('Dekorativ güzgülər', 'Декоративные зеркала', 'Decorative mirrors'), $ps, [
                    $curtainKind('T13_1', $opts([['Divar', 'divar'], ['Döşəmə', 'doseme'], ['Masaüstü', 'masaustu']]))
                        + $hint('Güzgülü dolab «Mebel» bölməsindədir.', 'Шкаф с зеркалом — в разделе «Мебель».', 'Mirrored cabinets are in «Furniture».'),
                    $f('T13_1', 'forma', $n('Forma', 'Форма', 'Shape'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Düzbucaqlı', 'duzbucaqli'], ['Dairəvi', 'dairevi'], ['Oval', 'oval'], ['Sərbəst', 'serbest'],
                    ])]),
                    $width(true, $diam),
                    $height(),
                    $depth(),
                    $f('T13_1', 'cercive', $n('Çərçivə materialı', 'Материал рамы', 'Frame material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Çərçivəsiz', 'cercivesiz'], ['Ağac', 'agac'], ['Metal', 'metal'], ['Plastik', 'plastik'],
                    ]), $other()]]),
                    ...$colorFinish,
                    $f('T13_1', 'montaj', $n('Montaj', 'Установка', 'Mounting'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Asma', 'asma'], ['Dayaqla', 'dayaqla'], ['Masaüstü', 'masaustu'],
                    ])]),
                    $f('T13_1', 'isiq', $n('İşıqlandırma', 'Подсветка', 'Lighting'), 'dropdown', ['options' => $ynn]),
                    ...$supply($when($s('T13_1', 'isiq'), 'beli')),
                    $f('T13_1', 'isiq-rengi', $n('İşıq rəngi', 'Цветовая температура', 'Light colour'), 'decimal', ['unit' => 'K', 'when' => $when($s('T13_1', 'isiq'), 'beli')]),
                    $f('T13_1', 'ip', $n('IP qorunma (nəm məkan)', 'Степень IP (влажные помещения)', 'IP rating (damp rooms)'), 'text', ['when' => $when($s('T13_1', 'isiq'), 'beli')]
                        + $hint('Yalnız sənədə görə.', 'Только по документам.', 'Only as documented.')),
                    $f('T13_1', 'ceki', $n('Çəki', 'Вес', 'Weight'), 'decimal', ['unit' => 'kq']),
                    $kit('T13_1', [['Bərkidicilər', 'berkidiciler'], ['Dayaq', 'dayaq'], ['Adapter', 'adapter']]),
                    $techDoc,
                ]),
                'resm-poster-divar-pannosu' => $card('T14_1', $n('Rəsmlər, posterlər və dekorativ pannolar', 'Картины, постеры и панно', 'Paintings, posters and wall panels'), $ps, [
                    $curtainKind('T14_1', $opts([['Orijinal rəsm', 'orijinal'], ['Reproduksiya', 'reproduksiya'], ['Poster', 'poster'], ['Dekorativ panno', 'panno']]))
                        + $hint('Divarın üzlənməsi üçün panellər «Divar örtükləri»ndədir.', 'Отделочные стеновые панели — в «Стенах».', 'Wall cladding panels are under «Walls».'),
                    $f('T14_1', 'esas', $n('Əsas', 'Основа', 'Base'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Kətan', 'ketan'], ['Kağız', 'kagiz'], ['Ağac', 'agac'], ['Metal', 'metal'], ['Tekstil', 'tekstil'],
                    ]), $other()]]),
                    $f('T14_1', 'texnika', $n('Texnika', 'Техника', 'Technique'), 'dropdown', ['options' => [...$opts([
                        ['Yağlı boya', 'yagli-boya'], ['Akril', 'akril'], ['Akvarel', 'akvarel'], ['Çap', 'cap'], ['Əl işi', 'el-isi'],
                    ]), $other()]]),
                    $width(true, $n('Çərçivə daxildirsə — çərçivə ilə.', 'Если рама входит — с рамой.', 'With the frame, if included.')),
                    $height(),
                    $depth(),
                    $f('T14_1', 'cercive', $n('Çərçivə', 'Рама', 'Frame'), 'dropdown', ['required' => true, 'options' => $opts([['Daxildir', 'daxildir'], ['Daxil deyil', 'daxil-deyil']])]),
                    $f('T14_1', 'movzu', $n('Mövzu', 'Тема', 'Subject'), 'dropdown', ['filter' => true, 'options' => [...$opts([
                        ['Abstrakt', 'abstrakt'], ['Təbiət', 'tebiet'], ['Memarlıq', 'memarliq'], ['Portret', 'portret'], ['Tipoqrafiya', 'tipoqrafiya'],
                    ]), $other()]]),
                    ...$colorPattern,
                    $f('T14_1', 'muellif', $n('Müəllif', 'Автор', 'Artist'), 'text', $hint('Məlumdursa.', 'Если известен.', 'If known.')),
                    $setContents,
                    $kit('T14_1', [['Asqı', 'asqi'], ['Çərçivə', 'cercive'], ['Qoruyucu şüşə', 'suse']]),
                ]),
                'foto-ve-resm-cerciveleri' => $card('T14_2', $n('Foto və rəsm çərçivələri', 'Рамки для фото и картин', 'Photo and picture frames'), $ps, [
                    $f('T14_2', 'yerlesme', $n('Yerləşmə', 'Размещение', 'Placement'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Divar', 'divar'], ['Masa', 'masa'], ['Hər ikisi', 'her-ikisi'],
                    ])]),
                    $f('T14_2', 'daxili-en', $n('Daxili ölçü: en', 'Внутренний размер: ширина', 'Picture width'), 'decimal', ['required' => true, 'unit' => 'sm', 'filter' => true]
                        + $hint('Şəkil üçün.', 'Под изображение.', 'Of the picture.')),
                    $f('T14_2', 'daxili-hundurluk', $n('Daxili ölçü: hündürlük', 'Внутренний размер: высота', 'Picture height'), 'decimal', ['required' => true, 'unit' => 'sm', 'filter' => true]),
                    $width(false, $n('Xarici ölçü.', 'Внешний размер.', 'Outside size.')),
                    $height(false),
                    $depth(),
                    $f('T14_2', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Ağac', 'agac'], ['MDF', 'mdf'], ['Metal', 'metal'], ['Plastik', 'plastik'],
                    ]), $other()]]),
                    $f('T14_2', 'on-qat', $n('Qoruyucu ön qat', 'Защитное стекло', 'Glazing'), 'dropdown', ['options' => $opts([['Şüşə', 'suse'], ['Akril', 'akril'], ['Yoxdur', 'yoxdur']])]),
                    $f('T14_2', 'paspartu', $n('Paspartu', 'Паспарту', 'Mount'), 'dropdown', ['options' => $ynn]),
                    ...$colorFinish,
                    $setQty,
                ]),
                'divar-saati' => $card('T14_3', $n('Divar saatları', 'Настенные часы', 'Wall clocks'), $ps, [
                    $curtainKind('T14_3', $opts([['Analoq', 'analoq'], ['Rəqəmsal', 'reqemsal']])),
                    $width(true, $n('Dairəvi saatda diametr.', 'Для круглых — диаметр.', 'The diameter for a round clock.')),
                    $height(false),
                    $depth(),
                    $f('T14_3', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Metal', 'metal'], ['Ağac', 'agac'], ['Plastik', 'plastik'], ['Şüşə', 'suse'],
                    ]), $other()]]),
                    $f('T14_3', 'mexanizm', $n('Mexanizm', 'Механизм', 'Movement'), 'dropdown', ['options' => $opts([['Kvars', 'kvars'], ['Mexaniki', 'mexaniki'], ['Elektron', 'elektron']])]),
                    $f('T14_3', 'enerji', $n('Enerji', 'Питание', 'Power'), 'dropdown', ['required' => true, 'options' => $opts([['Batareya', 'batareya'], ['Adapter', 'adapter'], ['Mexaniki', 'mexaniki']])]),
                    $f('T14_3', 'sessiz', $n('Səssiz gediş', 'Бесшумный ход', 'Silent movement'), 'dropdown', ['options' => $ynn]),
                    ...$colorFinish,
                    $kit('T14_3', [['Batareya', 'batareya'], ['Adapter', 'adapter'], ['Asqı', 'asqi']]),
                ]),
                'dekorativ-vaza' => $card('T15_1', $n('Vazalar', 'Вазы', 'Vases'), $ps, [
                    $curtainKind('T15_1', $opts([['Masaüstü', 'masaustu'], ['Döşəmə', 'doseme'], ['Asma', 'asma']])),
                    $f('T15_1', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Şüşə', 'suse'], ['Keramika', 'keramika'], ['Metal', 'metal'], ['Daş', 'das'], ['Plastik', 'plastik'],
                    ]), $other()]]),
                    $width(true, $diam),
                    $height(),
                    $f('T15_1', 'istifade', $n('İstifadə', 'Применение', 'Use'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Su ilə canlı gül', 'su-ile'], ['Yalnız quru dekor', 'quru'],
                    ])] + $hint('İstehsalçıya görə.', 'По данным производителя.', 'Per the maker.')),
                    ...$colorFinish,
                    $setContents,
                ]),
                'heykelcikler-ve-suvenirler' => $card('T15_2', $n('Dekorativ fiqurlar və əşyalar', 'Декоративные фигурки и предметы', 'Decorative figurines and objects'), $ps, [
                    $curtainKind('T15_2', [...$opts([['Heykəlcik', 'heykelcik'], ['Dekorativ obyekt', 'dekorativ-obyekt'], ['Dekorativ qutu', 'qutu']]), $other()]),
                    $f('T15_2', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Keramika', 'keramika'], ['Metal', 'metal'], ['Ağac', 'agac'], ['Şüşə', 'suse'], ['Daş', 'das'], ['Polimer', 'polimer'],
                    ]), $other()]]),
                    $width(),
                    $depth(true),
                    $height(),
                    ...$colorFinish,
                    $f('T15_2', 'yerlesme', $n('Yerləşmə', 'Размещение', 'Placement'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Masaüstü', 'masaustu'], ['Döşəmə', 'doseme'], ['Divar', 'divar'],
                    ])]),
                    $setContents,
                ]),
                'samlar-ve-samdanlar' => $card('T15_3', $n('Şamdanlar', 'Подсвечники', 'Candle holders'), $ps, [
                    $curtainKind('T15_3', $opts([['Bir şamlıq', 'bir'], ['Çoxşamlı', 'cox'], ['Fənər tipli', 'fener']])),
                    $f('T15_3', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Metal', 'metal'], ['Şüşə', 'suse'], ['Keramika', 'keramika'], ['Daş', 'das'],
                    ]), $other()]]),
                    $width(),
                    $depth(true),
                    $height(),
                    $f('T15_3', 'sam', $n('Uyğun şam', 'Подходящая свеча', 'Candle type'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Nazik', 'nazik'], ['Silindrik', 'silindrik'], ['Çay şamı', 'cay'], ['LED', 'led'],
                    ])]),
                    $f('T15_3', 'sam-diametr', $n('Şamın diametri', 'Диаметр свечи', 'Candle diameter'), 'decimal', ['unit' => 'sm']),
                    ...$colorFinish,
                    $kit('T15_3', [['Şam', 'sam'], ['Şüşə qoruyucu', 'suse']]),
                    $setContents,
                ]),
                'dekorativ-siniler' => $card('T15_4', $n('Dekorativ sinilər', 'Декоративные подносы', 'Decorative trays'), $ps, [
                    $f('T15_4', 'forma', $n('Forma', 'Форма', 'Shape'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Dairəvi', 'dairevi'], ['Düzbucaqlı', 'duzbucaqli'], ['Oval', 'oval'],
                    ]), $other()]]),
                    $f('T15_4', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Metal', 'metal'], ['Ağac', 'agac'], ['Şüşə', 'suse'], ['Keramika', 'keramika'], ['Daş', 'das'], ['Plastik', 'plastik'],
                    ]), $other()]]),
                    $sizeLW(),
                    $height(false),
                    ...$colorFinish,
                    $f('T15_4', 'qida', $n('Qida ilə təmas', 'Контакт с пищей', 'Food contact'), 'dropdown', ['options' => [...$opts([
                        ['Təsdiqlənib', 'tesdiqlenib'], ['Yalnız dekor', 'dekor'],
                    ]), $ns]]),
                    $setContents,
                ]),
                'dekorativ-dibcek-qablari' => $card('T15_5', $n('Dekorativ dibçək qabları', 'Кашпо', 'Decorative planters'), $ps, [
                    $f('T15_5', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Keramika', 'keramika'], ['Metal', 'metal'], ['Plastik', 'plastik'], ['Beton', 'beton'], ['Ağac', 'agac'],
                    ]), $other()]] + $hint('Bağ və landşaftda istifadə edilən eyni məhsul üçün təkrar kart yaradılmır.', 'Тот же товар для сада повторно не заводится.', 'Do not list the same item again for the garden.')),
                    $width(true, $n('Xarici ölçü; dairəvidə diametr.', 'Внешний размер; для круглого — диаметр.', 'Outside size; the diameter if round.')),
                    $depth(),
                    $height(),
                    $f('T15_5', 'daxili-en', $n('Daxili en / diametr', 'Внутренняя ширина / диаметр', 'Inner width / diameter'), 'decimal', ['unit' => 'sm']),
                    $f('T15_5', 'daxili-derinlik', $n('Daxili dərinlik', 'Внутренняя глубина', 'Inner depth'), 'decimal', ['unit' => 'sm']),
                    $f('T15_5', 'drenaj', $n('Drenaj dəliyi', 'Дренажное отверстие', 'Drainage hole'), 'dropdown', ['options' => $ynn]),
                    $use,
                    ...$colorFinish,
                    $kit('T15_5', [['Altlıq', 'altliq'], ['Dayaq', 'dayaq'], ['Daxili qab', 'daxili-qab']]),
                    $setContents,
                ]),
                'suni-bitkiler-ve-guller' => $card('T15_6', $n('Süni bitkilər və güllər', 'Искусственные растения и цветы', 'Artificial plants and flowers'), $ps, [
                    $curtainKind('T15_6', [...$opts([['Tək budaq', 'budaq'], ['Buket', 'buket'], ['Dibçəkli bitki', 'dibcekli'], ['Süni ağac', 'agac']]), $other()]),
                    $f('T15_6', 'bitki', $n('Bitki görünüşü', 'Вид растения', 'Plant'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Qızılgül', 'qizilgul'], ['Pion', 'pion'], ['Orxideya', 'orxideya'], ['Lalə', 'lale'], ['Hortenziya', 'hortenziya'], ['Lavanda', 'lavanda'],
                        ['Evkalipt', 'evkalipt'], ['Monstera', 'monstera'], ['Fikus', 'fikus'], ['Palma', 'palma'], ['Zeytun ağacı', 'zeytun'],
                        ['Sukkulent və kaktus', 'sukkulent'], ['Qamış və dekorativ ot', 'qamis'],
                    ]), $other()]] + $hint('Bitki və ya gül növü.', 'Вид растения или цветка.', 'The plant or flower kind.')),
                    $f('T15_6', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Tekstil', 'tekstil'], ['Plastik', 'plastik'], ['Lateks', 'lateks'], ['Təbii gövdə ilə kombinə', 'kombine'],
                    ]), $other()]]),
                    $height(),
                    $width(true, $n('Maksimum en.', 'Максимальная ширина.', 'The maximum width.')),
                    ...$colorPattern,
                    $hint('UV uyğunluğu istehsalçıya görə.', 'Стойкость к UV — по данным производителя.', 'UV resistance per the maker.') + $use,
                    $kit('T15_6', [['Dibçək', 'dibcek'], ['Kaşpo', 'kaspo'], ['Dayaq', 'dayaq']]),
                    $setContents,
                ]),
            ],
        ],
    ],

    // Old classes the cards replace: the moved products get the old class as «Hissə» / «Tip».
    'merge' => [
        'perdeler-ucun-luversler-ve-halqalar' => ['into' => 'karnizler-ucun-kronsteynler-ve-berkidiciler', 'set' => ['kart-tx06-1-hisse' => 'halqa']],
        'interyer-dekoru-panno-guzguler-vazalar-isiq-dekoru' => ['into' => 'heykelcikler-ve-suvenirler', 'set' => ['kart-tx15-2-tip' => 'dekorativ-obyekt']],
    ],
    // Lamps belong to the Elektrik section (torserler, masa-lampalari, divar işıqlandırıcıları).
    'retire_classes' => ['dekorativ-isiqlandirma-cihazi-masa-lampasidoseme-lampasidivar-lampasi'],
    'retire_groups' => [
        'perdelik-ve-uzluk-parcalar', 'jaluzler-rulon-ve-roma-sistemleri', 'karnizler-ve-motorlasdirma', 'xalcalar',
        'yataq-levazimatlari', 'karnizler-ve-berkidiciler', 'hamam-ve-metbex-tekstili',
    ],
];
