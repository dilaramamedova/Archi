<?php

/*
|--------------------------------------------------------------------------
| Landşaft və abadlaşdırma — 48 cards (L01_1–L12_6) in 12 groups
|--------------------------------------------------------------------------
|
| Source: Archi_AZ_Landsaft_Kataloq_AZ.xlsx (client). Format and decisions:
| docs/product-cards.md. The workbook's «Kateqoriyalar» sheet names 12 «alt bölmə»
| (numbered 1–12) under 3 navigation blocks; as in santexnika, the 12 alt bölmə are the
| groups (the 3 blocks would hold 12 / 15 / 21 cards of unrelated goods — furniture,
| pools and barbecues in one menu item).
|
| Repeated blocks («Ölçülər — 3 rəqəm», «Rəng və səth», «Xarici mühitə davamlılıq»,
| «Qablaşdırma», «Uyğun model və ya sistem», «Qoşulma ölçüsü», «İş təzyiqi», «Elektrik
| təchizatı», «Yük həddi», mebel karkası / səthi / saxlama, the plant block, «Əlavə
| xidmətlər» …) are one shared `kart-ls-*` definition each. Per-card fields are
| `kart-l01-1-<key>` (no other file uses an L code). Option values are Azerbaijani only;
| attribute / class / group names az/ru/en.
|
| Sales units the engine has no unit for (kg, l, m³, ton) are left out: bags, sacks and
| cassettes sell as `pack`, pallets as `pack`; the net quantity goes in «Qablaşdırma».
|
*/

$n = fn (string $az, string $ru, string $en): array => compact('az', 'ru', 'en');
$o = fn (string $az, string $ru, string $en, array $extra = []): array => ['value' => compact('az', 'ru', 'en')] + $extra;
$a = fn (string $az, array $extra = []): array => ['value' => ['az' => $az]] + $extra;

$P_NAME = $n('Dəqiq adını yazın', 'Укажите точное название', 'Enter the exact name');
$P_CODE = $n('Rəngi və ya kodunu yazın', 'Укажите цвет или код', 'Enter the colour or its code');

$yes = $o('Bəli', 'Да', 'Yes', ['slug' => 'beli']);
$no = $o('Xeyr', 'Нет', 'No', ['slug' => 'xeyr']);
$ns = $o('Göstərilməyib', 'Не указано', 'Not specified', ['slug' => 'gosterilmeyib']);
$other = fn (?array $prompt = null, string $az = 'Digər', string $slug = 'diger'): array => $a($az, ['slug' => $slug, 'prompt' => $prompt ?? $P_NAME]);
$none = fn (string $az = 'Daxil deyil'): array => $a($az, ['slug' => 'daxil-deyil', 'exclusive' => true]);
$noneX = fn (string $az = 'Yoxdur', string $slug = 'yoxdur'): array => $a($az, ['slug' => $slug, 'exclusive' => true]);
$na = $a('Tətbiq edilmir', ['slug' => 'tetbiq-edilmir']);
$ynn = [$yes, $no, $ns];
$when = fn (string $attr, string ...$in): array => ['attr' => $attr, 'in' => $in];
$opts = fn (array $list): array => array_map(fn ($pair) => $a($pair[0], ['slug' => $pair[1]]), $list);
$hint = fn (string $az, string $ru, string $en): array => ['hint' => compact('az', 'ru', 'en')];

$kit = fn (string $code, array $items): array => [
    'slug' => 'kart-'.strtolower(str_replace('_', '-', $code)).'-komplekt', 'name' => $n('Komplektə daxildir', 'В комплекте', 'Included'), 'type' => 'multiselect',
    'hint' => $n('Yalnız qiymətə daxil olanlar.', 'Только то, что входит в цену.', 'Only what the price includes.'),
    'options' => [...$opts($items), $none()],
];
$f = fn (string $code, string $key, array $name, string $type, array $extra = []): array => ['slug' => 'kart-'.strtolower(str_replace('_', '-', $code)).'-'.$key, 'name' => $name, 'type' => $type] + $extra;
$ps = ['piece', 'set'];

// ── Shared ──────────────────────────────────────────────────────────────────

// «Əlavə xidmətlər — çatdırılma, montaj, əkin və hazırlıq işləri, hər biri: qiymətə
// daxildir / ayrıca ödəniş / təklif edilmir / tətbiq edilmir». On every card.
$serviceOptions = $opts([['Qiymətə daxildir', 'daxildir'], ['Ayrıca ödəniş', 'ayrica'], ['Təklif edilmir', 'teklif-edilmir'], ['Tətbiq edilmir', 'tetbiq-edilmir']]);
$services = [
    ['slug' => 'kart-ls-xidmet-catdirilma', 'name' => $n('Çatdırılma', 'Доставка', 'Delivery'), 'type' => 'dropdown', 'options' => $serviceOptions],
    ['slug' => 'kart-ls-xidmet-montaj', 'name' => $n('Montaj xidməti', 'Монтаж', 'Installation'), 'type' => 'dropdown', 'options' => $serviceOptions],
    ['slug' => 'kart-ls-xidmet-ekin', 'name' => $n('Əkin xidməti', 'Посадка', 'Planting'), 'type' => 'dropdown', 'options' => $serviceOptions],
    ['slug' => 'kart-ls-xidmet-hazirliq', 'name' => $n('Hazırlıq işləri', 'Подготовительные работы', 'Site preparation'), 'type' => 'dropdown', 'options' => $serviceOptions]
        + $hint('Torpaq işləri, əsasın və bünövrənin hazırlanması.', 'Земляные работы, подготовка основания и фундамента.', 'Earthworks, base and foundation preparation.'),
];
$card = fn (string $code, array $name, array $units, array $fields, array $extra = []): array => [
    'code' => $code, 'name' => $name, 'sale' => 'unit', 'units' => $units, 'fields' => [...$fields, ...$services],
] + $extra;

// «Ölçülər* — 3 rəqəm: uzunluq × en × hündürlük / qalınlıq, mm» (unit is per class).
$lengthField = ['slug' => 'kart-ls-uzunluq', 'name' => $n('Uzunluq', 'Длина', 'Length'), 'type' => 'decimal'];
$dims = fn (bool $req = true, string $unit = 'mm', ?array $h = null): array => [
    $lengthField + ['required' => $req, 'unit' => $unit]
        + ['hint' => $h ?? $n('Ölçülər: uzunluq × en × hündürlük / qalınlıq.', 'Размеры: длина × ширина × высота / толщина.', 'Size: length × width × height / thickness.')],
    ['slug' => 'kart-ls-en', 'name' => $n('En', 'Ширина', 'Width'), 'type' => 'decimal', 'required' => $req, 'unit' => $unit],
    ['slug' => 'kart-ls-hundurluk', 'name' => $n('Hündürlük / qalınlıq', 'Высота / толщина', 'Height / thickness'), 'type' => 'decimal', 'required' => $req, 'unit' => $unit],
];

// «Rəng və səth* — iki seçim: rəng kataloqdan; hamar / teksturalı / mat / parlaq / digər».
$color = ['slug' => 'kart-ls-reng', 'name' => $n('Rəng', 'Цвет', 'Colour'), 'type' => 'dropdown', 'required' => true, 'filter' => true, 'options' => [...$opts([
    ['Ağ', 'ag'], ['Qara', 'qara'], ['Boz', 'boz'], ['Antrasit', 'antrasit'], ['Bej', 'bej'], ['Qəhvəyi', 'qehveyi'], ['Qırmızı', 'qirmizi'],
    ['Terrakota', 'terrakota'], ['Yaşıl', 'yasil'], ['Təbii (ağac / daş rəngi)', 'tebii'], ['Çoxrəngli', 'coxrengli'],
]), $other($P_CODE)]];
$finish = ['slug' => 'kart-ls-seth', 'name' => $n('Səth', 'Поверхность', 'Finish'), 'type' => 'dropdown', 'required' => true, 'options' => [...$opts([
    ['Hamar', 'hamar'], ['Teksturalı', 'teksturali'], ['Mat', 'mat'], ['Parlaq', 'parlaq'],
]), $other()]];
$look = [$color, $finish];

// «Xarici mühitə davamlılıq — seçim və sənəd: UV, şaxta və korroziya; yalnız təsdiqlənmiş».
$outdoor = [
    ['slug' => 'kart-ls-davamliliq', 'name' => $n('Xarici mühitə davamlılıq', 'Стойкость к внешней среде', 'Weather resistance'), 'type' => 'multiselect', 'options' => [
        ...$opts([['UV şüalarına davamlı', 'uv'], ['Şaxtaya davamlı', 'saxta'], ['Korroziyaya davamlı', 'korroziya']]), $noneX('Göstərilməyib', 'gosterilmeyib'),
    ]] + $hint('Yalnız istehsalçının təsdiqlədiyi göstəricilər.', 'Только показатели, подтверждённые производителем.', 'Only what the maker confirms.'),
    ['slug' => 'kart-ls-davamliliq-sened', 'name' => $n('Davamlılıq sənədi', 'Документ о стойкости', 'Resistance document'), 'type' => 'document',
        'when' => ['attr' => 'kart-ls-davamliliq', 'in' => ['uv', 'saxta', 'korroziya']]]
        + $hint('İstehsalçının sənədi: PDF / şəkil faylı və ya link.', 'Документ производителя: файл PDF / изображение или ссылка.', 'The maker’s document: a PDF / image file or a link.'),
];

// «Texniki məlumat — fayl və ya keçid».
$techDoc = ['slug' => 'kart-ls-texniki', 'name' => $n('Texniki məlumat', 'Техническая информация', 'Technical data'), 'type' => 'document']
    + $hint('Ölçü sxemi, uyğunluq siyahısı və istehsalçı təlimatı: fayl və ya link.', 'Схема размеров, список совместимости и инструкция: файл или ссылка.', 'Dimension drawing, compatibility list and instructions: a file or a link.');
$techDocH = fn (string $az, string $ru, string $en): array => $hint($az, $ru, $en) + $techDoc;

// «Qablaşdırma* — rəqəm və vahid: ədəd sayı və ya xalis həcm / çəki; ədəd / l / kg / m / m²».
$packing = [
    ['slug' => 'kart-ls-qablasdirma', 'name' => $n('Qablaşdırmada miqdar', 'Количество в упаковке', 'Quantity per pack'), 'type' => 'decimal', 'required' => true]
        + $hint('Ədəd sayı və ya xalis həcm / çəki; tək satılan məhsulda 1 ədəd.', 'Число штук или нетто объём / вес; для штучного товара — 1 шт.', 'Piece count or net volume / weight; 1 piece for an item sold singly.'),
    ['slug' => 'kart-ls-qablasdirma-vahid', 'name' => $n('Qablaşdırmanın vahidi', 'Единица упаковки', 'Pack unit'), 'type' => 'dropdown', 'required' => true,
        'options' => $opts([['ədəd', 'eded'], ['l', 'l'], ['kg', 'kg'], ['m', 'm'], ['m²', 'm2']])],
];

// «Material* — metal / plastik / şüşə / ağac / keramika / digər» (the generic list).
$material = ['slug' => 'kart-ls-material', 'name' => $n('Material', 'Материал', 'Material'), 'type' => 'dropdown', 'required' => true, 'filter' => true, 'options' => [...$opts([
    ['Metal', 'metal'], ['Plastik', 'plastik'], ['Şüşə', 'suse'], ['Ağac', 'agac'], ['Keramika', 'keramika'],
]), $other()]];

// «Uyğun model və ya sistem* — axtarışlı siyahı və ya qısa mətn».
$compat = fn (bool $req = true, ?array $h = null): array => ['slug' => 'kart-ls-uygun', 'name' => $n('Uyğun model və ya sistem', 'Совместимая модель или система', 'Compatible model or system'), 'type' => 'text', 'required' => $req]
    + ['hint' => $h ?? $n('Brend + seriya / model və ya istehsalçının uyğunluq kodu. Universal olduqda ölçü və uyğunluq şərtləri.', 'Бренд + серия / модель или код совместимости. Для универсальных — размер и условия.', 'Brand + series / model or the maker’s compatibility code. For universal parts, the size and conditions.')];

// «Yük həddi — rəqəm, vahid və sənəd: istehsalçının yük göstəricisi və şərti».
$load = [
    ['slug' => 'kart-ls-yuk', 'name' => $n('Yük həddi', 'Допустимая нагрузка', 'Load limit'), 'type' => 'decimal']
        + $hint('İstehsalçının göstəricisi; sənədi «Texniki məlumat»a əlavə edin.', 'Показатель производителя; документ — в «Технической информации».', 'The maker’s figure; attach the document under «Technical data».'),
    ['slug' => 'kart-ls-yuk-vahid', 'name' => $n('Yük həddinin vahidi', 'Единица нагрузки', 'Load unit'), 'type' => 'dropdown',
        'options' => $opts([['kg', 'kg'], ['kg/m²', 'kg-m2'], ['kN', 'kn'], ['ton', 'ton']])],
    ['slug' => 'kart-ls-yuk-sert', 'name' => $n('Yükün şərti', 'Условие нагрузки', 'Load condition'), 'type' => 'text']
        + $hint('İstifadə / montaj şərti, məs. bərabər paylanmış, bir dayağa, laqa aralığı 400 mm.', 'Условие использования / монтажа, напр. равномерно распределённая.', 'Use / mounting condition, e.g. evenly distributed, per support.'),
];

// «Qoşulma ölçüsü — seçim və ya rəqəm: yiv ölçüsü, düym; boru diametri, mm».
$connection = [
    ['slug' => 'kart-ls-qosulma', 'name' => $n('Qoşulma ölçüsü', 'Присоединительный размер', 'Connection size'), 'type' => 'dropdown', 'options' => [...$opts([
        ['1/2"', '1-2'], ['3/4"', '3-4'], ['1"', '1'], ['1 1/4"', '1-1-4'], ['1 1/2"', '1-1-2'], ['2"', '2'], ['Boru diametri, mm', 'boru'],
    ]), $other()]] + $hint('İstehsalçıya görə: yiv ölçüsü və ya boru diametri.', 'По производителю: резьба или диаметр трубы.', 'Per the maker: thread size or pipe diameter.'),
    ['slug' => 'kart-ls-qosulma-diametr', 'name' => $n('Borunun diametri', 'Диаметр трубы', 'Pipe diameter'), 'type' => 'decimal', 'required' => true, 'unit' => 'mm',
        'when' => ['attr' => 'kart-ls-qosulma', 'in' => ['boru']]],
];

// «İş təzyiqi — 2 rəqəm: minimum–maksimum, bar».
$pressure = [
    ['slug' => 'kart-ls-tezyiq-min', 'name' => $n('İş təzyiqi, min', 'Рабочее давление, мин.', 'Working pressure, min'), 'type' => 'decimal', 'unit' => 'bar',
        'rules' => ['lte' => 'kart-ls-tezyiq-maks']] + $hint('İstehsalçı məlumatına görə.', 'По данным производителя.', 'Per the maker.'),
    ['slug' => 'kart-ls-tezyiq-maks', 'name' => $n('İş təzyiqi, maks', 'Рабочее давление, макс.', 'Working pressure, max'), 'type' => 'decimal', 'unit' => 'bar'],
];

// «Elektrik təchizatı — şəbəkə / adapter / batareya / günəş paneli; V, AC/DC, W və IP».
$supplyAnswers = ['sebeke', 'adapter', 'batareya', 'gunes', 'diger'];
$voltage = ['slug' => 'kart-ls-gerginlik', 'name' => $n('Gərginlik', 'Напряжение', 'Voltage'), 'type' => 'decimal', 'unit' => 'V'];
$power = fn (?array $w = null): array => [
    ['slug' => 'kart-ls-elektrik', 'name' => $n('Elektrik təchizatı', 'Электропитание', 'Power supply'), 'type' => 'dropdown', 'options' => [...$opts([
        ['Şəbəkə', 'sebeke'], ['Adapter', 'adapter'], ['Batareya / akkumulyator', 'batareya'], ['Günəş paneli', 'gunes'],
    ]), $other()]] + ($w ? ['when' => $w] : []),
    $voltage + ['when' => ['attr' => 'kart-ls-elektrik', 'in' => $supplyAnswers]] + $hint('İstehsalçı sənədindən.', 'Из документации производителя.', 'From the maker’s documents.'),
    ['slug' => 'kart-ls-cereyan-novu', 'name' => $n('Cərəyan növü', 'Род тока', 'Current type'), 'type' => 'dropdown',
        'options' => $opts([['AC', 'ac'], ['DC', 'dc'], ['AC/DC', 'ac-dc']]), 'when' => ['attr' => 'kart-ls-elektrik', 'in' => $supplyAnswers]],
    ['slug' => 'kart-ls-guc', 'name' => $n('Güc', 'Мощность', 'Power'), 'type' => 'decimal', 'unit' => 'W', 'when' => ['attr' => 'kart-ls-elektrik', 'in' => $supplyAnswers]],
    ['slug' => 'kart-ls-ip', 'name' => $n('IP qorunma dərəcəsi', 'Степень защиты IP', 'IP rating'), 'type' => 'dropdown', 'options' => [...$opts([
        ['IP44', 'ip44'], ['IP54', 'ip54'], ['IP65', 'ip65'], ['IP66', 'ip66'], ['IP67', 'ip67'], ['IP68', 'ip68'],
    ]), $other(), $ns], 'when' => ['attr' => 'kart-ls-elektrik', 'in' => $supplyAnswers]],
];

// Garden furniture: frame, surface material, storage.
$frame = ['slug' => 'kart-ls-karkas', 'name' => $n('Karkas materialı', 'Материал каркаса', 'Frame material'), 'type' => 'dropdown', 'required' => true, 'filter' => true, 'options' => [...$opts([
    ['Ağac', 'agac'], ['Alüminium', 'aluminium'], ['Polad', 'polad'], ['Plastik', 'plastik'],
]), $other()]];
$surfaceMat = ['slug' => 'kart-ls-seth-material', 'name' => $n('Səth materialı', 'Материал поверхности', 'Surface material'), 'type' => 'dropdown', 'required' => true, 'options' => [...$opts([
    ['Ağac', 'agac'], ['Süni rattan', 'suni-rattan'], ['Tekstil', 'tekstil'], ['Metal', 'metal'], ['Plastik', 'plastik'],
]), $other()]];
$storage = ['slug' => 'kart-ls-saxlama', 'name' => $n('Saxlama', 'Хранение', 'Storage'), 'type' => 'dropdown', 'options' => [...$opts([
    ['Daimi açıq havada', 'aciq-hava'], ['Örtük altında', 'ortuk-altinda'], ['Qapalı yerdə', 'qapali'],
]), $ns]] + $hint('İstehsalçıya görə.', 'По данным производителя.', 'Per the maker.');
$furniture = [$frame, $surfaceMat, ...$dims(), ...$look, ...$outdoor, ...$load, $storage];
$folding = ['slug' => 'kart-ls-qatlanan', 'name' => $n('Qatlanan', 'Складной', 'Folding'), 'type' => 'dropdown', 'options' => $ynn];
$seats = ['slug' => 'kart-ls-oturacaq', 'name' => $n('Oturacaq sayı', 'Число мест', 'Seats'), 'type' => 'decimal', 'unit' => 'ədəd', 'rules' => ['integer' => true]];
$setList = ['slug' => 'kart-ls-dest-terkibi', 'name' => $n('Dəstin tərkibi', 'Состав комплекта', 'Set contents'), 'type' => 'textarea', 'required' => true]
    + $hint('Hər hissənin adı, sayı və ölçüsü.', 'Название, количество и размер каждой части.', 'Name, count and size of every piece.');

$mount = ['slug' => 'kart-ls-montaj', 'name' => $n('Montaj', 'Монтаж', 'Mounting'), 'type' => 'dropdown', 'options' => [...$opts([
    ['Sərbəst', 'serbest'], ['Bünövrəyə', 'bunovre'], ['Torpağa', 'torpaq'], ['Divara', 'divar'],
]), $other()]];

// «Hazırlanma variantı — standart / fərdi ölçü ilə sifariş»; fərdi → «Sifarişlə»,
// the price shows its sample size and kit.
$madeToOrder = [
    ['slug' => 'kart-ls-hazirlanma', 'name' => $n('Hazırlanma variantı', 'Вариант изготовления', 'Production'), 'type' => 'dropdown',
        'options' => $opts([['Standart', 'standart'], ['Fərdi ölçü ilə sifariş', 'ferdi']])],
    ['slug' => 'kart-ls-ferdi-numune', 'name' => $n('Qiymətin nümunə ölçüsü və komplekti', 'Образцовый размер и комплект цены', 'Sample size and kit of the price'), 'type' => 'text', 'required' => true,
        'when' => ['attr' => 'kart-ls-hazirlanma', 'in' => ['ferdi']]]
        + $hint('Fərdi sifarişdə qiymət hansı ölçü və komplekt üçündür.', 'Для какого размера и комплекта указана цена.', 'Which size and kit the price is for.'),
];
$onOrder = ['on_order_when' => ['attr' => 'kart-ls-hazirlanma', 'in' => ['ferdi']]];

$volume = ['slug' => 'kart-ls-hecm', 'name' => $n('Həcm', 'Объём', 'Volume'), 'type' => 'decimal', 'unit' => 'l'];
$weight = ['slug' => 'kart-ls-ceki', 'name' => $n('Çəki', 'Вес', 'Weight'), 'type' => 'decimal', 'unit' => 'kg'];
$waterVolume = ['slug' => 'kart-ls-su-tutumu', 'name' => $n('Su tutumu', 'Объём воды', 'Water capacity'), 'type' => 'decimal', 'unit' => 'l'];

// «Fraksiya — 2 rəqəm: minimum–maksimum, mm».
$fraction = fn (bool $req = true): array => [
    ['slug' => 'kart-ls-fraksiya-min', 'name' => $n('Fraksiya, min', 'Фракция, мин.', 'Grain size, min'), 'type' => 'decimal', 'required' => $req, 'unit' => 'mm',
        'rules' => ['lte' => 'kart-ls-fraksiya-maks']],
    ['slug' => 'kart-ls-fraksiya-maks', 'name' => $n('Fraksiya, maks', 'Фракция, макс.', 'Grain size, max'), 'type' => 'decimal', 'required' => $req, 'unit' => 'mm'],
];

// Lawns and plants.
$light = ['slug' => 'kart-ls-isiq-teleb', 'name' => $n('İşıq tələbi', 'Требование к свету', 'Light'), 'type' => 'dropdown', 'filter' => true,
    'options' => $opts([['Günəş', 'gunes'], ['Yarımkölgə', 'yarimkolge'], ['Kölgə', 'kolge']])];
$grass = [
    ['slug' => 'kart-ls-ot-novleri', 'name' => $n('Ot növləri', 'Виды трав', 'Grass species'), 'type' => 'multiselect', 'required' => true, 'options' => [...$opts([
        ['Çəmən qırtıcı (Poa pratensis)', 'poa-pratensis'], ['Çoxillik raygras (Lolium perenne)', 'lolium-perenne'],
        ['Qırmızı topal (Festuca rubra)', 'festuca-rubra'], ['Qamışvari topal (Festuca arundinacea)', 'festuca-arundinacea'],
        ['Bermud otu (Cynodon dactylon)', 'cynodon-dactylon'], ['Ağrostis (Agrostis)', 'agrostis'],
    ]), $other()]],
    ['slug' => 'kart-ls-ot-terkib', 'name' => $n('Tərkib, %', 'Состав, %', 'Mix, %'), 'type' => 'text', 'required' => true]
        + $hint('Hər növün payı, cəmi 100%: məs. 60% raygras, 40% qırmızı topal.', 'Доля каждого вида, всего 100%.', 'Each species’ share, 100% in total.'),
];
$rollSize = ['slug' => 'kart-ls-rulon-olcu', 'name' => $n('Rulon ölçüsü', 'Размер рулона', 'Roll size'), 'type' => 'size', 'required' => true, 'unit' => 'm']
    + $hint('Rulonun uzunluğu və eni, m.', 'Длина и ширина рулона, м.', 'Roll length and width, m.');
$plant = [
    ['slug' => 'kart-ls-bitki', 'name' => $n('Növ və sort', 'Вид и сорт', 'Species and cultivar'), 'type' => 'text', 'required' => true]
        + $hint('Azərbaycanca ad, botaniki ad və sort, məs. Qara şam — Pinus nigra, Austriaca.', 'Название, ботаническое название и сорт.', 'Common name, botanical name and cultivar.'),
    ['slug' => 'kart-ls-satis-hundurluk', 'name' => $n('Satış hündürlüyü', 'Высота при продаже', 'Height as sold'), 'type' => 'decimal', 'required' => true, 'unit' => 'cm']
        + $hint('Qab nəzərə alınmadan.', 'Без учёта горшка.', 'Without the pot.'),
    ['slug' => 'kart-ls-kok', 'name' => $n('Kök sistemi', 'Корневая система', 'Root system'), 'type' => 'dropdown', 'required' => true, 'filter' => true,
        'options' => $opts([['Konteyner', 'konteyner'], ['Torpaq topası', 'torpaq-topasi'], ['Açıq kök', 'aciq-kok']])],
    ['slug' => 'kart-ls-konteyner', 'name' => $n('Konteyner həcmi', 'Объём контейнера', 'Container size'), 'type' => 'decimal', 'unit' => 'l',
        'when' => ['attr' => 'kart-ls-kok', 'in' => ['konteyner']]],
    ['required' => true] + $light,
    ['slug' => 'kart-ls-su-teleb', 'name' => $n('Su tələbi', 'Потребность в воде', 'Water need'), 'type' => 'dropdown',
        'options' => $opts([['Az', 'az'], ['Orta', 'orta'], ['Yüksək', 'yuksek']])],
    ['slug' => 'kart-ls-iqlim', 'name' => $n('İqlim uyğunluğu', 'Климатическая пригодность', 'Climate suitability'), 'type' => 'text']
        + $hint('Şaxta, istilik, duzlu mühit üzrə tingliyin məlumatı.', 'Морозо-, жаростойкость, солёная среда — по данным питомника.', 'Frost, heat and salt tolerance per the nursery.'),
    ['slug' => 'kart-ls-yetkin', 'name' => $n('Yetkin ölçü', 'Размер взрослого растения', 'Mature size'), 'type' => 'text']
        + $hint('Hündürlük və tac eni, m; təxmini diapazon, məs. 3–4 × 2–3.', 'Высота и ширина кроны, м; примерно.', 'Height and spread, m; approximate.'),
];
$months = $opts([
    ['Yanvar', 'yanvar'], ['Fevral', 'fevral'], ['Mart', 'mart'], ['Aprel', 'aprel'], ['May', 'may'], ['İyun', 'iyun'],
    ['İyul', 'iyul'], ['Avqust', 'avqust'], ['Sentyabr', 'sentyabr'], ['Oktyabr', 'oktyabr'], ['Noyabr', 'noyabr'], ['Dekabr', 'dekabr'],
]);

return [
    'section' => 'landsaft-ve-abadlasdirma',

    'groups' => [
        // ════ 1. Bağ yolları və meydançalar üçün örtüklər ══════════════════
        [
            'slug' => 'seki-salinmasi-ve-ortukler',
            'name' => $n('Bağ yolları və meydançalar üçün örtüklər', 'Покрытия для садовых дорожек и площадок', 'Garden path and yard surfacing'),
            'classes' => [
                'seki-dasi-seki-plitesi' => $card('L01_1', $n('Tamet və səki plitələri', 'Брусчатка и тротуарная плитка', 'Pavers and paving slabs'), ['m2', 'piece', 'pack'], [
                    $f('L01_1', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Beton', 'beton'], ['Klinker', 'klinker'], ['Təbii daş', 'tebii-das'], ['Xarici istifadə üçün keramika', 'keramika'],
                    ])]),
                    ...$dims(),
                    $f('L01_1', 'teyinat', $n('Təyinat', 'Назначение', 'Use'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Piyada yolu', 'piyada'], ['Avtomobil sahəsi', 'avtomobil'],
                    ])] + $hint('İstehsalçıya görə. Daşıma qabiliyyəti yalnız qalınlıqdan çıxarılmır: əsas qat və montaj da nəzərə alınır.', 'По данным производителя; несущая способность зависит и от основания и укладки.', 'Per the maker; load capacity also depends on the base and laying.')),
                    $f('L01_1', 'forma', $n('Forma', 'Форма', 'Shape'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Düzbucaqlı', 'duzbucaqli'], ['Kvadrat', 'kvadrat'], ['Fiqurlu', 'fiqurlu'], ['Qarışıq dəst', 'qarisiq-dest'],
                    ])]),
                    ...$look,
                    $f('L01_1', 'surusme', $n('Sürüşmə göstəricisi', 'Показатель скольжения', 'Slip resistance'), 'text',
                        $hint('Sınaq üsulu və nəticəsi, məlumdursa.', 'Метод испытания и результат, если известны.', 'Test method and result, if known.')),
                    ...$outdoor,
                    $f('L01_1', 'palet-sahe', $n('Paletdə sahə', 'Площадь на паллете', 'Area per pallet'), 'decimal', ['unit' => 'm²']),
                    $techDoc,
                ]),
                'addim-daslari-ve-modul-bag-yollari' => $card('L01_2', $n('Addım daşları və modul bağ yolları', 'Камни-ступени и модульные садовые дорожки', 'Stepping stones and modular garden paths'), ['piece', 'set', 'm2'], [
                    $f('L01_2', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Təbii daş', 'tebii-das'], ['Beton', 'beton'], ['Ağac', 'agac'], ['Plastik', 'plastik'],
                    ]), $other()]]),
                    $f('L01_2', 'forma', $n('Forma', 'Форма', 'Shape'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Dairəvi', 'dairevi'], ['Düzbucaqlı', 'duzbucaqli'], ['Sərbəst', 'serbest'], ['Modul', 'modul'],
                    ])]),
                    ...$dims(),
                    ...$look,
                    $f('L01_2', 'doseme', $n('Döşəmə üsulu', 'Способ укладки', 'Laying method'), 'dropdown', ['options' => $opts([
                        ['Torpağa', 'torpaq'], ['Çınqıla', 'cinqil'], ['Quma', 'qum'], ['Yapışdırma', 'yapisdirma'], ['Birləşən modul', 'modul'],
                    ])]),
                    ...$outdoor,
                    ...$packing,
                ]),
                'rezin-ortuk-usaqidman-meydancalari-ucun' => $card('L01_3', $n('Rezin meydança örtükləri', 'Резиновые покрытия для площадок', 'Rubber playground and sports surfacing'), ['m2', 'roll', 'piece'], [
                    $f('L01_3', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Plitə', 'plite'], ['Rulon', 'rulon'], ['Modul', 'modul'],
                    ])]),
                    $f('L01_3', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['SBR', 'sbr'], ['EPDM', 'epdm'], ['Kombinə', 'kombine'],
                    ]), $other()]]),
                    ...$dims(),
                    ...$look,
                    $f('L01_3', 'birlesme', $n('Birləşmə', 'Соединение', 'Joining'), 'dropdown', ['options' => $opts([
                        ['Yapışdırma', 'yapisdirma'], ['Kilid', 'kilid'], ['Sərbəst', 'serbest'],
                    ])]),
                    $f('L01_3', 'teyinat', $n('Təyinat', 'Назначение', 'Use'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Bağ yolu', 'bag-yolu'], ['İdman', 'idman'], ['Oyun meydançası', 'oyun'],
                    ])]),
                    $f('L01_3', 'dusme', $n('Kritik düşmə hündürlüyü', 'Критическая высота падения', 'Critical fall height'), 'decimal', ['unit' => 'm']
                        + $hint('Zərbə udma sınağına görə. Qalınlıq təkbaşına oyun meydançasına uyğunluğu təsdiq etmir.', 'По испытанию на амортизацию; толщина сама по себе не подтверждает пригодность.', 'Per the impact test; thickness alone does not prove suitability.')),
                    $f('L01_3', 'zerbe-sened', $n('Zərbə udma sınaq sənədi', 'Протокол испытания на амортизацию', 'Impact test report'), 'document',
                        $hint('Oyun sahəsi üçün: fayl və ya link.', 'Для игровых площадок: файл или ссылка.', 'For playgrounds: a file or a link.')),
                    ...$outdoor,
                    ...$packing,
                    $techDoc,
                ]),
            ],
            // Grass pavers: no card in the workbook.
            'plain' => ['qazon-sebekesi'],
        ],

        // ════ 2. Terras örtükləri və komplektləşdiricilər ══════════════════
        [
            'slug' => 'terras-ortukleri-ve-komplektlesdiriciler',
            'name' => $n('Terras örtükləri və komplektləşdiricilər', 'Террасные покрытия и комплектующие', 'Decking and accessories'),
            'classes' => [
                // The old «Terras lövhəsi (Bölmə 2 — Döşəmələrə baxın)» class becomes the card.
                'terras-lovhesi-bolme-2-dosemelere-baxin' => $card('L02_1', $n('Terras lövhələri', 'Террасная доска', 'Decking boards'), ['m2', 'lm', 'piece'], [
                    $f('L02_1', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Ağac', 'agac'], ['Termoağac', 'termoagac'], ['WPC (ağac-polimer kompozit)', 'wpc'],
                    ])]),
                    $f('L02_1', 'agac', $n('Ağac növü', 'Порода древесины', 'Wood species'), 'dropdown', ['when' => $when('kart-l02-1-material', 'agac', 'termoagac'), 'options' => [...$opts([
                        ['Şam', 'sam'], ['Larç', 'larc'], ['Sidr', 'sidr'], ['Tik', 'tik'], ['İpe', 'ipe'], ['Merbau', 'merbau'],
                        ['Kumaru', 'kumaru'], ['Palıd', 'palid'], ['Ağcaqayın', 'agcaqayin'],
                    ]), $other()]]),
                    ...$dims(),
                    $f('L02_1', 'profil', $n('Profil', 'Профиль', 'Profile'), 'dropdown', ['required' => true, 'options' => $opts([['Bütöv', 'butov'], ['Boşluqlu', 'bosluqlu']])]),
                    ...$look,
                    $f('L02_1', 'berkitme', $n('Bərkitmə', 'Крепление', 'Fixing'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Gizli klips', 'gizli-klips'], ['Açıq vint', 'aciq-vint'],
                    ]), $other()]]),
                    $f('L02_1', 'laqa-araligi', $n('Laqa aralığı', 'Шаг лаг', 'Joist spacing'), 'decimal', ['unit' => 'mm']
                        + $hint('İstehsalçının konkret yük və montaj üçün həddi; təlimata uyğun seçilir.', 'Предел производителя для конкретной нагрузки и монтажа.', 'The maker’s limit for the given load and installation.')),
                    ...$outdoor,
                    ...$packing,
                    $techDoc,
                ]),
                'terras-laqalari-ve-tenzimlenen-dayaqlar' => $card('L02_2', $n('Terras laqaları və tənzimlənən dayaqlar', 'Лаги и регулируемые опоры для террас', 'Decking joists and adjustable pedestals'), $ps, [
                    $f('L02_2', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Laqa', 'laqa'], ['Tənzimlənən dayaq', 'dayaq'], ['Dayaq altlığı', 'altliq'],
                    ])]),
                    $material,
                    ...$dims(),
                    $f('L02_2', 'hundurluk-min', $n('Hündürlük diapazonu, min', 'Диапазон высоты, мин.', 'Height range, min'), 'decimal', ['unit' => 'mm',
                        'rules' => ['lte' => 'kart-l02-2-hundurluk-maks'], 'when' => $when('kart-l02-2-tip', 'dayaq')]),
                    $f('L02_2', 'hundurluk-maks', $n('Hündürlük diapazonu, maks', 'Диапазон высоты, макс.', 'Height range, max'), 'decimal', ['unit' => 'mm',
                        'when' => $when('kart-l02-2-tip', 'dayaq')]),
                    ...$load,
                    $compat(),
                    ...$packing,
                    $techDoc,
                ]),
                'terras-berkidicileri-ve-tamamlayici-profiller' => $card('L02_3', $n('Terras bərkidiciləri və tamamlayıcı profillər', 'Крепёж и доборные профили для террас', 'Decking fixings and trims'), ['piece', 'pack', 'lm', 'set'], [
                    $f('L02_3', 'hisse', $n('Hissə', 'Деталь', 'Part'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Başlanğıc klips', 'baslangic-klips'], ['Ara klips', 'ara-klips'], ['Vint', 'vint'], ['Künc profili', 'kunc-profili'], ['Sonluq', 'sonluq'],
                    ]), $other()]]),
                    $compat(),
                    ...$dims(),
                    $material,
                    ...$look,
                    ...$packing,
                    $kit('L02_3', [['Vint', 'vint'], ['Klips', 'klips'], ['Sonluq', 'sonluq']]),
                    $techDoc,
                ]),
            ],
        ],

        // ════ 3. Bordürlər və bağ ayırıcıları ══════════════════════════════
        [
            'slug' => 'bordurler-ve-bag-ayiricilari',
            'name' => $n('Bordürlər və bağ ayırıcıları', 'Бордюры и садовые ограничители', 'Kerbs and garden edging'),
            'classes' => [
                'bordurler' => $card('L03_1', $n('Bağ və səki bordürləri', 'Садовые и тротуарные бордюры', 'Garden and pavement kerbs'), ['piece', 'lm', 'pack'], [
                    $f('L03_1', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Beton', 'beton'], ['Təbii daş', 'tebii-das'], ['Metal', 'metal'], ['Plastik', 'plastik'], ['Ağac', 'agac'],
                    ])]),
                    ...$dims(),
                    $f('L03_1', 'forma', $n('Forma', 'Форма', 'Shape'), 'dropdown', ['required' => true, 'options' => $opts([['Düz', 'duz'], ['Əyri', 'eyri'], ['Künc', 'kunc']])]),
                    ...$look,
                    $f('L03_1', 'teyinat', $n('Təyinat', 'Назначение', 'Use'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Səki', 'seki'], ['Qazon', 'qazon'], ['Çiçəklik', 'ciceklik'],
                    ])]),
                    ...$outdoor,
                    ...$packing,
                ]),
                'elastik-bag-ayiricilari' => $card('L03_2', $n('Elastik bağ ayırıcıları', 'Гибкие садовые бордюрные ленты', 'Flexible garden edging'), ['roll', 'lm', 'set'], [
                    $f('L03_2', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Plastik', 'plastik'], ['Polad', 'polad'], ['Alüminium', 'aluminium'],
                    ]), $other()]]),
                    $lengthField + ['required' => true, 'unit' => 'm'],
                    $f('L03_2', 'hundurluk', $n('Hündürlük', 'Высота', 'Height'), 'decimal', ['required' => true, 'unit' => 'mm']),
                    $f('L03_2', 'qalinliq', $n('Qalınlıq', 'Толщина', 'Thickness'), 'decimal', ['unit' => 'mm']),
                    ...$look,
                    $f('L03_2', 'qurulus', $n('Quruluş', 'Исполнение', 'Format'), 'dropdown', ['required' => true, 'options' => $opts([['Rulon', 'rulon'], ['Modul', 'modul']])]),
                    $kit('L03_2', [['Bərkitmə mıxları', 'mixlar'], ['Birləşdiricilər', 'birlesdiriciler']]),
                    ...$packing,
                ]),
            ],
        ],

        // ════ 4. Hasarlar, darvazalar və bağ qapıları ══════════════════════
        [
            'slug' => 'hasarlar-darvazalar-ve-avtomatika',
            'name' => $n('Hasarlar, darvazalar və bağ qapıları', 'Заборы, ворота и калитки', 'Fences, gates and garden doors'),
            'classes' => [
                'hasarlar-ve-darvazalar' => $card('L04_1', $n('Hasar panelləri və torları', 'Заборные панели и сетки', 'Fence panels and mesh'), ['piece', 'lm', 'm2', 'roll'], [
                    $f('L04_1', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Panel', 'panel'], ['Qaynaqlı tor', 'qaynaqli-tor'], ['Hörmə tor', 'horme-tor'], ['Dekorativ bölmə', 'dekorativ'],
                    ])]),
                    $f('L04_1', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Polad', 'polad'], ['Alüminium', 'aluminium'], ['Ağac', 'agac'], ['WPC', 'wpc'], ['PVC', 'pvc'],
                    ])]),
                    ...$dims(),
                    $f('L04_1', 'goz', $n('Göz ölçüsü', 'Размер ячейки', 'Mesh size'), 'size', ['unit' => 'mm', 'when' => $when('kart-l04-1-tip', 'qaynaqli-tor', 'horme-tor')]
                        + $hint('Gözün eni və hündürlüyü.', 'Ширина и высота ячейки.', 'Mesh opening width and height.')),
                    $f('L04_1', 'tel', $n('Tel və ya profil qalınlığı', 'Толщина проволоки или профиля', 'Wire or profile thickness'), 'decimal', ['unit' => 'mm']),
                    ...$look,
                    $f('L04_1', 'ortuk', $n('Qoruyucu örtük', 'Защитное покрытие', 'Protective coating'), 'dropdown', ['options' => [...$opts([
                        ['Sink', 'sink'], ['Boya', 'boya'], ['Polimer', 'polimer'], ['Hopdurma', 'hopdurma'],
                    ]), $other()]]),
                    $kit('L04_1', [['Dirəklər', 'direkler'], ['Bərkidicilər', 'berkidiciler']]),
                    ...$outdoor,
                    $techDoc,
                ]),
                'darvazalar-ve-bag-qapilari' => $card('L04_2', $n('Darvazalar və bağ qapıları', 'Ворота и садовые калитки', 'Gates and garden doors'), $ps, [
                    $f('L04_2', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([['Darvaza', 'darvaza'], ['Bağ qapısı', 'bag-qapisi']])]),
                    $f('L04_2', 'acilma', $n('Açılma', 'Открывание', 'Opening'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Qanadlı', 'qanadli'], ['Sürüşən', 'surusen'], ['Qatlanan', 'qatlanan'],
                    ])]),
                    $f('L04_2', 'kecid', $n('Keçid ölçüsü', 'Размер проёма', 'Clear opening'), 'size', ['required' => true, 'unit' => 'mm']
                        + $hint('Təmiz en × hündürlük.', 'Чистая ширина × высота.', 'Clear width × height.')),
                    $f('L04_2', 'xarici', $n('Xarici ölçü', 'Габаритный размер', 'Overall size'), 'size', ['unit' => 'mm']
                        + $hint('Dirəklərlə birlikdə en × hündürlük.', 'Ширина × высота со столбами.', 'Width × height with the posts.')),
                    $material,
                    ...$look,
                    ...$madeToOrder,
                    $f('L04_2', 'idareetme', $n('İdarəetmə', 'Управление', 'Operation'), 'dropdown', ['required' => true, 'options' => $opts([['Əl ilə', 'el-ile'], ['Avtomatik', 'avtomatik']])]),
                    $kit('L04_2', [['Dirək', 'direk'], ['Kilid', 'kilid'], ['Menteşə', 'mentese'], ['Motor', 'motor'], ['Pult', 'pult']]),
                    $techDoc,
                ], $onOrder),
                'hasar-ve-darvaza-furniturasi' => $card('L04_3', $n('Hasar və darvaza furniturası', 'Фурнитура для заборов и ворот', 'Fence and gate hardware'), ['piece', 'pack', 'lm', 'set'], [
                    $f('L04_3', 'hisse', $n('Hissə', 'Деталь', 'Part'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Dirək', 'direk'], ['Başlıq', 'basliq'], ['Menteşə', 'mentese'], ['Kilid', 'kilid'], ['Təkər', 'teker'], ['Rels', 'rels'],
                        ['Tutacaq', 'tutacaq'], ['Bərkidici', 'berkidici'],
                    ]), $other()]]),
                    $compat(),
                    ...$dims(),
                    $material,
                    ...$look,
                    ...$packing,
                    ...$load,
                    $techDoc,
                ]),
                'darvazalar-ucun-avtomatika-oturucu-slaqbaum' => $card('L04_4', $n('Darvaza avtomatikası', 'Автоматика для ворот', 'Gate automation'), $ps, [
                    $f('L04_4', 'darvaza', $n('Darvaza tipi', 'Тип ворот', 'Gate type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Qanadlı', 'qanadli'], ['Sürüşən', 'surusen'],
                    ]), $other()]]),
                    $compat(),
                    $f('L04_4', 'qanad-olcu', $n('Uyğun qanad ölçüsü', 'Допустимый размер створки', 'Leaf size supported'), 'decimal', ['required' => true, 'unit' => 'm']
                        + $hint('Maksimum en / uzunluq, istehsalçıya görə.', 'Максимальная ширина / длина по производителю.', 'Maximum width / length per the maker.')),
                    $f('L04_4', 'qanad-ceki', $n('Uyğun qanad çəkisi', 'Допустимый вес створки', 'Leaf weight supported'), 'decimal', ['required' => true, 'unit' => 'kg']
                        + $hint('Ölçü ilə birlikdə istehsalçı cədvəlindən.', 'По таблице производителя вместе с размером.', 'From the maker’s table, with the size.')),
                    ...$power(),
                    $f('L04_4', 'idareetme', $n('İdarəetmə', 'Управление', 'Control'), 'multiselect', ['options' => $opts([
                        ['Pult', 'pult'], ['Düymə', 'duyme'], ['Tətbiq', 'tetbiq'], ['Ağıllı ev', 'agilli-ev'],
                    ])]),
                    $f('L04_4', 'protokol', $n('Protokol və tələb olunan modul', 'Протокол и необходимый модуль', 'Protocol and module needed'), 'text',
                        ['when' => $when('kart-l04-4-idareetme', 'tetbiq', 'agilli-ev')] + $hint('Məs. Wi-Fi, Zigbee; ayrıca alınan modulun modeli.', 'Напр. Wi-Fi, Zigbee; модель отдельного модуля.', 'E.g. Wi-Fi, Zigbee; the model of a separate module.')),
                    $kit('L04_4', [['Motor', 'motor'], ['İdarəetmə bloku', 'blok'], ['Fotoelement', 'fotoelement'], ['Siqnal lampası', 'lampa'], ['Pult', 'pult'], ['Bərkidici', 'berkidici']]),
                    $techDoc,
                ]),
            ],
        ],

        // ════ 5. Qazonlar və bitkilər ══════════════════════════════════════
        [
            'slug' => 'yasillasdirma-ve-suvarma',
            'name' => $n('Qazonlar və bitkilər', 'Газоны и растения', 'Lawns and plants'),
            'classes' => [
                'rulon-qazon' => $card('L05_1', $n('Rulon qazon', 'Рулонный газон', 'Turf rolls'), ['m2', 'roll'], [
                    ...$grass,
                    $f('L05_1', 'teyinat', $n('Təyinat', 'Назначение', 'Use'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Dekorativ', 'dekorativ'], ['İntensiv istifadə', 'intensiv'], ['Kölgəyə uyğun', 'kolge'],
                    ]), $other()]] + $hint('Yetişdiriciyə görə.', 'По данным производителя.', 'Per the grower.')),
                    $rollSize,
                    $f('L05_1', 'qalinliq', $n('Qalınlıq', 'Толщина', 'Thickness'), 'decimal', ['unit' => 'mm'] + $hint('Torpaq qatı ilə.', 'С учётом слоя грунта.', 'Including the soil layer.')),
                    $f('L05_1', 'rulon-sahe', $n('Rulonun sahəsi', 'Площадь рулона', 'Roll area'), 'decimal', ['required' => true, 'unit' => 'm²']),
                    $light,
                    $f('L05_1', 'kesim', $n('Kəsim və çatdırılma', 'Срез и доставка', 'Cutting and delivery'), 'text',
                        $hint('Kəsim tarixi və çatdırılma şərtləri.', 'Дата среза и условия доставки.', 'Cutting date and delivery terms.')),
                ]),
                'qazon-toxumlari' => $card('L05_2', $n('Qazon toxumları', 'Семена газонных трав', 'Lawn seed'), ['pack'], [
                    ...$grass,
                    $f('L05_2', 'teyinat', $n('Təyinat', 'Назначение', 'Use'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Dekorativ', 'dekorativ'], ['İdman', 'idman'], ['Bərpa', 'berpa'],
                    ]), $other()]]),
                    $light,
                    $f('L05_2', 'norma', $n('Səpin norması (yeni səpin)', 'Норма высева (новый газон)', 'Sowing rate (new lawn)'), 'decimal', ['unit' => 'g/m²']),
                    $f('L05_2', 'norma-berpa', $n('Səpin norması (bərpa)', 'Норма высева (подсев)', 'Sowing rate (overseeding)'), 'decimal', ['unit' => 'g/m²']),
                    ...$packing,
                    $f('L05_2', 'partiya', $n('Partiya və yararlılıq', 'Партия и срок годности', 'Batch and best-before'), 'text',
                        $hint('Partiya kodu və son istifadə tarixi.', 'Код партии и дата окончания срока годности.', 'Batch code and use-by date.')),
                    $f('L05_2', 'movsum', $n('Səpin mövsümü', 'Сезон посева', 'Sowing season'), 'multiselect', ['options' => $months]
                        + $hint('İstehsalçının regiona uyğun göstərişi.', 'По рекомендации производителя для региона.', 'Per the maker’s advice for the region.')),
                ]),
                'suni-qazon' => $card('L05_3', $n('Süni qazon', 'Искусственный газон', 'Artificial grass'), ['m2', 'roll'], [
                    $f('L05_3', 'teyinat', $n('Təyinat', 'Назначение', 'Use'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Landşaft', 'landsaft'], ['İdman', 'idman'], ['Dekor', 'dekor'],
                    ])]),
                    $f('L05_3', 'idman', $n('İdman növü və sistem tələbləri', 'Вид спорта и требования к системе', 'Sport and system requirements'), 'text',
                        ['required' => true, 'when' => $when('kart-l05-3-teyinat', 'idman')]),
                    $f('L05_3', 'lif', $n('Lif materialı', 'Материал волокна', 'Fibre'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['PE', 'pe'], ['PP', 'pp'], ['PA', 'pa'], ['Qarışıq', 'qarisiq'],
                    ])]),
                    $f('L05_3', 'xov', $n('Xov hündürlüyü', 'Высота ворса', 'Pile height'), 'decimal', ['required' => true, 'unit' => 'mm']),
                    $rollSize,
                    $f('L05_3', 'alt-esas', $n('Alt əsas', 'Основа', 'Backing'), 'dropdown', ['options' => [...$opts([['Lateks', 'lateks'], ['PU', 'pu']]), $other()]]),
                    $f('L05_3', 'su-kecirme', $n('Su keçirmə', 'Водопроницаемость', 'Drainage holes'), 'dropdown', ['options' => $ynn]),
                    ...$look,
                    ...$outdoor,
                    $kit('L05_3', [['Birləşmə lenti', 'lent'], ['Yapışqan', 'yapisqan'], ['Bərkidici', 'berkidici']]),
                    $techDoc,
                ]),
                'bitkiler-ve-qazon' => $card('L05_4', $n('Ağaclar və kollar', 'Деревья и кустарники', 'Trees and shrubs'), ['piece'], [
                    ...$plant,
                    $f('L05_4', 'qrup', $n('Bitki qrupu', 'Группа растений', 'Plant group'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Ağac', 'agac'], ['Kol', 'kol'], ['Sarmaşıq', 'sarmasiq'],
                    ])]),
                    $f('L05_4', 'yarpaq', $n('Yarpaq tipi', 'Тип листвы', 'Foliage'), 'dropdown', ['filter' => true, 'options' => $opts([
                        ['Həmişəyaşıl', 'hemiseyasil'], ['Yarpaqtökən', 'yarpaqtoken'],
                    ])]),
                    $f('L05_4', 'govde', $n('Gövdə çevrəsi', 'Обхват ствола', 'Trunk girth'), 'decimal', ['unit' => 'cm']),
                    $f('L05_4', 'govde-hundurluk', $n('Çevrənin ölçüldüyü hündürlük', 'Высота измерения обхвата', 'Girth measured at'), 'decimal', ['unit' => 'cm']),
                ]),
                'guller-ve-ortucu-bitkiler' => $card('L05_5', $n('Güllər və örtücü bitkilər', 'Цветы и почвопокровные растения', 'Flowers and ground-cover plants'), ['piece', 'pack'], [
                    ...$plant,
                    $f('L05_5', 'heyat', $n('Həyat dövrü', 'Жизненный цикл', 'Life cycle'), 'dropdown', ['filter' => true, 'options' => $opts([
                        ['Birillik', 'birillik'], ['İkiillik', 'ikiillik'], ['Çoxillik', 'coxillik'],
                    ])]),
                    $f('L05_5', 'ciceklenme', $n('Çiçəklənmə ayları', 'Месяцы цветения', 'Flowering months'), 'multiselect', ['options' => $months]),
                    $f('L05_5', 'cicek-rengi', $n('Çiçək rəngi', 'Цвет цветков', 'Flower colour'), 'text'),
                    $f('L05_5', 'kaset', $n('Kasetdə say', 'Количество в кассете', 'Plants per cassette'), 'decimal', ['unit' => 'ədəd', 'rules' => ['integer' => true]]
                        + $hint('Kaset satışında (vahid «Qablaşdırma»).', 'При продаже кассетами (единица «Упаковка»).', 'When sold by the cassette (unit «Pack»).')),
                ]),
            ],
        ],

        // ════ 6. Əkin və landşaft dekorasiyası üçün materiallar ═══════════
        [
            'slug' => 'ekin-ve-landsaft-dekorasiyasi-materiallari',
            'name' => $n('Əkin və landşaft dekorasiyası üçün materiallar', 'Материалы для посадки и ландшафтного декора', 'Planting and landscape decor materials'),
            'classes' => [
                'munbit-torpaq-ve-malc' => $card('L06_1', $n('Torpaqlar və substratlar', 'Грунты и субстраты', 'Soils and substrates'), ['pack'], [
                    $f('L06_1', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Bağ torpağı', 'bag-torpagi'], ['Universal substrat', 'universal'], ['Torf', 'torf'], ['Kompost', 'kompost'], ['Xüsusi qarışıq', 'xususi'],
                    ])]),
                    $f('L06_1', 'teyinat', $n('Təyinat', 'Назначение', 'Use'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Qazon', 'qazon'], ['Ağac və kol', 'agac-kol'], ['Gül', 'gul'], ['Turşusevər bitki', 'tursusever'],
                    ]), $other()]]),
                    $f('L06_1', 'terkib', $n('Tərkib', 'Состав', 'Composition'), 'multiselect', ['options' => [...$opts([
                        ['Torf', 'torf'], ['Kompost', 'kompost'], ['Bağ torpağı', 'bag-torpagi'], ['Qum', 'qum'], ['Perlit', 'perlit'],
                        ['Vermikulit', 'vermikulit'], ['Kokos lifi', 'kokos'], ['Biohumus', 'biohumus'],
                    ]), $other()]]),
                    $f('L06_1', 'terkib-faiz', $n('Tərkib, %', 'Состав, %', 'Composition, %'), 'text',
                        $hint('Məlumdursa nisbətlər, məs. 70% torf, 20% kompost, 10% perlit.', 'Доли, если известны.', 'Proportions, if known.')),
                    $f('L06_1', 'ph-min', $n('pH, min', 'pH, мин.', 'pH, min'), 'decimal', ['rules' => ['lte' => 'kart-l06-1-ph-maks', 'max' => 14]]
                        + $hint('İstehsalçıya görə.', 'По данным производителя.', 'Per the maker.')),
                    $f('L06_1', 'ph-maks', $n('pH, maks', 'pH, макс.', 'pH, max'), 'decimal', ['rules' => ['max' => 14]]),
                    ...$packing,
                    $f('L06_1', 'gubre', $n('Gübrə əlavə edilib', 'С удобрением', 'Fertiliser added'), 'dropdown', ['options' => $ynn]),
                ]),
                'malc-ve-dekorativ-qabiq' => $card('L06_2', $n('Malç və dekorativ qabıq', 'Мульча и декоративная кора', 'Mulch and decorative bark'), ['pack'], [
                    $f('L06_2', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Ağac qabığı', 'qabiq'], ['Yonqar', 'yonqar'], ['Kokos', 'kokos'],
                    ]), $other()]]),
                    ...$fraction(),
                    ...$look,
                    ...$packing,
                    $f('L06_2', 'emal', $n('Emal', 'Обработка', 'Treatment'), 'dropdown', ['options' => [...$opts([['Təbii', 'tebii'], ['Rənglənmiş', 'renglenmis']]), $other()]]),
                    $f('L06_2', 'norma', $n('Örtmə norması', 'Норма расхода', 'Coverage rate'), 'decimal', ['unit' => 'l/m²']
                        + $hint('Aşağıdakı qat qalınlığında; istehsalçıya görə.', 'При указанной ниже толщине слоя.', 'At the layer thickness below; per the maker.')),
                    $f('L06_2', 'qat', $n('Qat qalınlığı', 'Толщина слоя', 'Layer thickness'), 'decimal', ['unit' => 'mm']),
                ]),
                'dekorativ-cinqil-ve-daslar' => $card('L06_3', $n('Dekorativ çınqıl və daşlar', 'Декоративный гравий и камни', 'Decorative gravel and stones'), ['pack', 'piece'], [
                    $f('L06_3', 'das', $n('Daş növü', 'Вид камня', 'Stone'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Çay daşı', 'cay-dasi'], ['Mərmər', 'mermer'], ['Qranit', 'qranit'], ['Bazalt', 'bazalt'],
                    ]), $other()]] + $hint('Tikinti üçün doldurucu çınqıl ümumi tikinti materialları bölməsindədir.', 'Строительный щебень — в разделе общих стройматериалов.', 'Construction aggregate belongs to general building materials.')),
                    $f('L06_3', 'forma', $n('Forma', 'Форма', 'Shape'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Yumru', 'yumru'], ['Qırma', 'qirma'], ['İri dekorativ daş', 'iri'],
                    ])]),
                    ...$fraction(),
                    ...$look,
                    ...$packing,
                    $f('L06_3', 'emal', $n('Emal', 'Обработка', 'Treatment'), 'dropdown', ['options' => $opts([
                        ['Yuyulmuş', 'yuyulmus'], ['Cilalanmış', 'cilalanmis'], ['Rənglənmiş', 'renglenmis'], ['Təbii', 'tebii'],
                    ])]),
                ]),
                'ekin-ucun-komekci-materiallar' => $card('L06_4', $n('Əkin üçün köməkçi materiallar', 'Вспомогательные материалы для посадки', 'Planting aids'), ['pack'], [
                    $f('L06_4', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Perlit', 'perlit'], ['Vermikulit', 'vermikulit'], ['Keramzit', 'keramzit'], ['Kokos lifi', 'kokos'],
                    ]), $other()]]),
                    $f('L06_4', 'teyinat', $n('Təyinat', 'Назначение', 'Use'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Torpağı yumşaltma', 'yumsaltma'], ['Drenaj', 'drenaj'], ['Nəm saxlama', 'nem'],
                    ]), $other()]]),
                    ...$fraction(false),
                    ...$packing,
                    $f('L06_4', 'qayda', $n('İstifadə qaydası', 'Способ применения', 'How to use'), 'text',
                        $hint('İstehsalçının qarışdırma və tətbiq göstərişi.', 'Рекомендации производителя по смешиванию и применению.', 'The maker’s mixing and use advice.')),
                ]),
            ],
        ],

        // ════ 7. Suvarma sistemləri və ləvazimatları ═══════════════════════
        [
            'slug' => 'suvarma-sistemleri-ve-levazimatlari',
            'name' => $n('Suvarma sistemləri və ləvazimatları', 'Системы и принадлежности для полива', 'Irrigation systems and supplies'),
            'classes' => [
                'suvarma-slanqlari' => $card('L07_1', $n('Suvarma şlanqları', 'Поливочные шланги', 'Garden hoses'), ['piece', 'lm', 'roll'], [
                    $lengthField + ['required' => true, 'unit' => 'm'],
                    $f('L07_1', 'diametr', $n('Daxili diametr', 'Внутренний диаметр', 'Inner diameter'), 'decimal', ['required' => true, 'unit' => 'mm', 'filter' => true]),
                    $f('L07_1', 'duym', $n('Düym ölçüsü', 'Размер в дюймах', 'Size in inches'), 'dropdown', ['options' => $opts([
                        ['1/2"', '1-2'], ['5/8"', '5-8'], ['3/4"', '3-4'], ['1"', '1'],
                    ])]),
                    $f('L07_1', 'material', $n('Material', 'Материал', 'Material'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['PVC', 'pvc'], ['Rezin', 'rezin'], ['TPE', 'tpe'],
                    ]), $other()]]),
                    ...$pressure,
                    $f('L07_1', 'qurulus', $n('Quruluş', 'Конструкция', 'Construction'), 'dropdown', ['options' => $opts([
                        ['Armaturlu', 'armaturlu'], ['Elastik uzanan', 'uzanan'], ['Standart', 'standart'],
                    ])]),
                    ...$outdoor,
                    $kit('L07_1', [['Birləşdiricilər', 'birlesdiriciler'], ['Püskürdücü', 'puskurducu']]),
                    $techDoc,
                ]),
                'slanq-makaralari-ve-arabalari' => $card('L07_2', $n('Şlanq makaraları və arabaları', 'Катушки и тележки для шлангов', 'Hose reels and carts'), $ps, [
                    $f('L07_2', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Divar makarası', 'divar'], ['Avtomatik makara', 'avtomatik'], ['Araba', 'araba'], ['Tutacaq', 'tutacaq'],
                    ])]),
                    $f('L07_2', 'tutum', $n('Şlanq tutumu', 'Вместимость шланга', 'Hose capacity'), 'decimal', ['required' => true, 'unit' => 'm']),
                    $f('L07_2', 'diametr', $n('Uyğun şlanq diametri', 'Подходящий диаметр шланга', 'Hose diameter'), 'decimal', ['required' => true, 'unit' => 'mm']),
                    $f('L07_2', 'slanq', $n('Şlanq daxildir', 'Шланг в комплекте', 'Hose included'), 'dropdown', ['options' => $ynn]),
                    $f('L07_2', 'slanq-uzunluq', $n('Daxil olan şlanqın uzunluğu', 'Длина шланга в комплекте', 'Included hose length'), 'decimal', ['unit' => 'm',
                        'when' => $when('kart-l07-2-slanq', 'beli')]),
                    $material,
                    $kit('L07_2', [['Bərkidici', 'berkidici'], ['Şlanq', 'slanq'], ['Birləşdirici', 'birlesdirici']]),
                    $techDoc,
                ]),
                'yagmurlayicilar-ve-suvarma-tapancalari' => $card('L07_3', $n('Yağmurlayıcılar və suvarma tapançaları', 'Дождеватели и поливочные пистолеты', 'Sprinklers and spray guns'), $ps, [
                    $f('L07_3', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Stasionar', 'stasionar'], ['Fırlanan', 'firlanan'], ['Osillyasiyalı', 'osillyasiyali'], ['Çıxan başlıq', 'cixan'], ['Əl tapançası', 'tapanca'],
                    ])]),
                    ...$connection,
                    ...$pressure,
                    $f('L07_3', 'radius', $n('Əhatə radiusu', 'Радиус полива', 'Throw radius'), 'decimal', ['unit' => 'm']),
                    $f('L07_3', 'sahe', $n('Əhatə sahəsi', 'Площадь полива', 'Coverage area'), 'decimal', ['unit' => 'm²']),
                    $f('L07_3', 'sinaq', $n('Əhatənin sınaq təzyiqi', 'Давление при замере', 'Test pressure'), 'decimal', ['unit' => 'bar']
                        + $hint('Radius / sahə hansı təzyiqdə ölçülüb.', 'При каком давлении измерены радиус / площадь.', 'The pressure the radius / area were measured at.')),
                    $f('L07_3', 'serf', $n('Su sərfiyyatı', 'Расход воды', 'Flow rate'), 'decimal', ['unit' => 'l/dəq']),
                    $f('L07_3', 'serf-tezyiq', $n('Sərfiyyatın ölçüldüyü təzyiq', 'Давление при замере расхода', 'Flow measured at'), 'decimal', ['unit' => 'bar']),
                    $f('L07_3', 'rejim', $n('Rejimlər', 'Режимы', 'Spray patterns'), 'multiselect', ['options' => [...$opts([
                        ['Şırnaq', 'sirnaq'], ['Duman', 'duman'], ['Duş', 'dus'], ['Sektor tənzimlənməsi', 'sektor'],
                    ]), $other()]]),
                    $kit('L07_3', [['Ucluq', 'ucluq'], ['Dayaq', 'dayaq'], ['Birləşdirici', 'birlesdirici']]),
                    $techDoc,
                ]),
                'damci-suvarma' => $card('L07_4', $n('Damcı suvarma', 'Капельный полив', 'Drip irrigation'), ['roll', 'lm', 'piece', 'set'], [
                    $f('L07_4', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Damcı borusu', 'boru'], ['Lent', 'lent'], ['Ayrıca damcılayıcı', 'damcilayici'], ['Hazır dəst', 'dest'],
                    ])]),
                    $lengthField + ['unit' => 'm', 'when' => $when('kart-l07-4-tip', 'boru', 'lent')],
                    $f('L07_4', 'diametr', $n('Diametr', 'Диаметр', 'Diameter'), 'decimal', ['unit' => 'mm', 'when' => $when('kart-l07-4-tip', 'boru', 'lent')]),
                    $f('L07_4', 'addim', $n('Damcılayıcı addımı', 'Шаг капельниц', 'Emitter spacing'), 'decimal', ['unit' => 'cm']),
                    $f('L07_4', 'serf', $n('Bir damcılayıcının sərfi', 'Расход капельницы', 'Emitter flow'), 'decimal', ['required' => true, 'unit' => 'l/saat']
                        + $hint('Nominal təzyiqdə.', 'При номинальном давлении.', 'At nominal pressure.')),
                    ...$pressure,
                    $f('L07_4', 'kompensasiya', $n('Təzyiq kompensasiyası', 'Компенсация давления', 'Pressure compensating'), 'dropdown', ['options' => $ynn]),
                    $kit('L07_4', [['Boru', 'boru'], ['Filtr', 'filtr'], ['Reduktor', 'reduktor'], ['Birləşdirici', 'birlesdirici'], ['Taymer', 'taymer']]),
                    $techDoc,
                ]),
                'avtomatik-suvarma' => $card('L07_5', $n('Suvarma taymerləri və kontrollerləri', 'Таймеры и контроллеры полива', 'Irrigation timers and controllers'), $ps, [
                    $f('L07_5', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Kran taymeri', 'kran-taymeri'], ['Çoxzonalı kontroller', 'kontroller'], ['Sensor', 'sensor'],
                    ])]),
                    $f('L07_5', 'zona', $n('Zona sayı', 'Число зон', 'Zones'), 'decimal', ['unit' => 'ədəd', 'rules' => ['integer' => true], 'when' => $when('kart-l07-5-tip', 'kontroller')]),
                    ...$connection,
                    ...$power(),
                    $f('L07_5', 'proqram', $n('Proqramlaşdırma', 'Программирование', 'Scheduling'), 'multiselect', ['options' => [...$opts([
                        ['Həftənin günləri', 'gunler'], ['İnterval (hər N gündən bir)', 'interval'], ['Sensorla (yağış / nəm)', 'sensor'],
                    ]), $other()]]),
                    $f('L07_5', 'muddet', $n('Maksimum suvarma müddəti', 'Макс. длительность полива', 'Maximum run time'), 'decimal', ['unit' => 'dəq']),
                    $f('L07_5', 'dovr', $n('Gündə dövr sayı', 'Циклов в день', 'Cycles per day'), 'decimal', ['unit' => 'ədəd', 'rules' => ['integer' => true]]),
                    $f('L07_5', 'idareetme', $n('İdarəetmə', 'Управление', 'Control'), 'dropdown', ['options' => [...$opts([
                        ['Mexaniki', 'mexaniki'], ['Elektron', 'elektron'], ['Wi-Fi', 'wi-fi'], ['Bluetooth', 'bluetooth'],
                    ]), $other()]]),
                    $f('L07_5', 'modul', $n('Uyğun modul', 'Совместимый модуль', 'Compatible module'), 'text', ['when' => $when('kart-l07-5-idareetme', 'wi-fi', 'bluetooth', 'diger')]
                        + $hint('Tələb olunan şlüz / modul, varsa.', 'Необходимый шлюз / модуль, если нужен.', 'The gateway / module needed, if any.')),
                    $compat(),
                    $kit('L07_5', [['Sensor', 'sensor'], ['Adapter', 'adapter'], ['Klapan', 'klapan']]),
                    $techDoc,
                ]),
                'suvarma-birlesdiricileri-klapanlari-ve-filtrleri' => $card('L07_6', $n('Suvarma birləşdiriciləri, klapanları və filtrləri', 'Соединители, клапаны и фильтры для полива', 'Irrigation fittings, valves and filters'), ['piece', 'pack', 'set'], [
                    $f('L07_6', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Birləşdirici', 'birlesdirici'], ['Adapter', 'adapter'], ['Paylayıcı', 'paylayici'], ['Klapan', 'klapan'], ['Filtr', 'filtr'], ['Təzyiq reduktoru', 'reduktor'],
                    ])] + $hint('Magistral su boruları ümumi tikinti materiallarındadır.', 'Магистральные трубы — в общих стройматериалах.', 'Mains water pipes belong to general building materials.')),
                    ...$connection,
                    $material,
                    ...$pressure,
                    $compat(),
                    $f('L07_6', 'filtr', $n('Filtr göstəricisi', 'Тонкость фильтрации', 'Filtration rating'), 'decimal', ['when' => $when('kart-l07-6-tip', 'filtr')]),
                    $f('L07_6', 'filtr-vahid', $n('Filtr göstəricisinin vahidi', 'Единица фильтрации', 'Filtration unit'), 'dropdown', ['required' => true,
                        'options' => $opts([['Mikron', 'mikron'], ['Mesh', 'mesh']]), 'when' => $when('kart-l07-6-tip', 'filtr')]),
                    $f('L07_6', 'reduktor', $n('Reduktorun çıxış təzyiqi', 'Давление на выходе редуктора', 'Reducer outlet pressure'), 'decimal', ['unit' => 'bar',
                        'when' => $when('kart-l07-6-tip', 'reduktor')]),
                    ...$packing,
                    $techDoc,
                ]),
            ],
        ],

        // ════ 8. Bağ mebeli ════════════════════════════════════════════════
        [
            'slug' => 'bag-mebeli',
            'name' => $n('Bağ mebeli', 'Садовая мебель', 'Garden furniture'),
            'classes' => [
                'bag-masalari-ve-stullari' => $card('L08_1', $n('Bağ masaları və stulları', 'Садовые столы и стулья', 'Garden tables and chairs'), $ps, [
                    $f('L08_1', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Masa', 'masa'], ['Stul', 'stul'], ['Yemək dəsti', 'dest'],
                    ])]),
                    ...$furniture,
                    $folding,
                    $seats + ['when' => $when('kart-l08-1-tip', 'masa', 'dest')],
                    $setList + ['when' => $when('kart-l08-1-tip', 'dest')],
                ]),
                // The old «Küçə mebeli» class.
                'kuce-mebeli' => $card('L08_2', $n('Bağ divanları, kresloları və skamyaları', 'Садовые диваны, кресла и скамейки', 'Garden sofas, armchairs and benches'), $ps, [
                    $f('L08_2', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Divan', 'divan'], ['Kreslo', 'kreslo'], ['Skamya', 'skamya'], ['İstirahət dəsti', 'dest'],
                    ])]),
                    ...$furniture,
                    ['required' => true] + $seats,
                    $f('L08_2', 'yastiq', $n('Yastıqlar daxildir', 'Подушки в комплекте', 'Cushions included'), 'dropdown', ['options' => $ynn]),
                    $setList + ['when' => $when('kart-l08-2-tip', 'dest')],
                ]),
                'sezlonqlar' => $card('L08_3', $n('Şezlonqlar', 'Шезлонги', 'Sun loungers'), $ps, [
                    ...$furniture,
                    $f('L08_3', 'arxaliq', $n('Arxalıq tənzimlənir', 'Регулируемая спинка', 'Adjustable back'), 'dropdown', ['options' => $ynn]),
                    $folding,
                    $f('L08_3', 'teker', $n('Təkərlər', 'Колёса', 'Wheels'), 'dropdown', ['options' => $ynn]),
                    $kit('L08_3', [['Döşəkçə', 'dosekce'], ['Yastıq', 'yastiq'], ['Günəşlik', 'guneslik']]),
                ]),
                // The old «Bağ yelləncəyi (patio)» class.
                'bag-yellenceyi-patio' => $card('L08_4', $n('Hamaklar və bağ yelləncəkləri', 'Гамаки и садовые качели', 'Hammocks and garden swings'), $ps, [
                    $f('L08_4', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Hamak', 'hamak'], ['Asma kreslo', 'asma-kreslo'], ['Bağ yelləncəyi', 'yellencek'],
                    ])]),
                    ...$furniture,
                    $f('L08_4', 'yer', $n('Yer sayı', 'Число мест', 'Places'), 'decimal', ['required' => true, 'unit' => 'ədəd', 'rules' => ['integer' => true]]),
                    $f('L08_4', 'asilma', $n('Asılma', 'Подвес', 'Hanging'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Öz dayağı', 'oz-dayagi'], ['Tavana', 'tavana'], ['Ağaca', 'agaca'],
                    ]), $other()]]),
                    $kit('L08_4', [['Dayaq', 'dayaq'], ['Asqı', 'asqi'], ['Tavan örtüyü', 'tavan'], ['Yastıq', 'yastiq']]),
                    $techDoc,
                ]),
                'bag-mebeli-ortukleri-ve-yastiqlari' => $card('L08_5', $n('Bağ mebeli örtükləri və yastıqları', 'Чехлы и подушки для садовой мебели', 'Garden furniture covers and cushions'), $ps, [
                    $f('L08_5', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Qoruyucu örtük', 'ortuk'], ['Oturacaq yastığı', 'yastiq'], ['Şezlonq döşəkçəsi', 'dosekce'],
                    ])]),
                    $compat(),
                    ...$dims(),
                    $f('L08_5', 'parca', $n('Parça', 'Ткань', 'Fabric'), 'multiselect', ['required' => true, 'options' => [...$opts([
                        ['Polyester', 'polyester'], ['Akril', 'akril'], ['Olefin', 'olefin'], ['Pambıq', 'pambiq'], ['PVC örtüklü parça', 'pvc'],
                    ]), $other()]] + $hint('Parçanın tərkibi.', 'Состав ткани.', 'Fabric composition.')),
                    $f('L08_5', 'dolgu', $n('Dolğu', 'Наполнитель', 'Filling'), 'dropdown', ['required' => true, 'when' => $when('kart-l08-5-tip', 'yastiq', 'dosekce'), 'options' => [...$opts([
                        ['Penopoliuretan', 'ppu'], ['Holofayber', 'holofayber'], ['Sintepon', 'sintepon'],
                    ]), $other()]]),
                    $f('L08_5', 'su', $n('Suya davamlılıq', 'Водостойкость', 'Water resistance'), 'dropdown', ['options' => [...$opts([
                        ['Su itələyən', 'su-iteleyen'], ['Su keçirməyən', 'su-kecirmeyen'],
                    ]), $ns]]),
                    $f('L08_5', 'qulluq', $n('Qulluq', 'Уход', 'Care'), 'multiselect', ['options' => [...$opts([
                        ['Maşında yuma', 'masin'], ['Əllə yuma', 'el'], ['Nəm parça ilə silmə', 'silme'], ['Yalnız quru təmizləmə', 'quru'],
                    ]), $other()]] + $hint('İstehsalçı etiketinə görə.', 'По ярлыку производителя.', 'Per the care label.')),
                    ...$look,
                ]),
            ],
        ],

        // ════ 9. Çardaqlar, perqolalar və bağ çətirləri ════════════════════
        [
            'slug' => 'maf-bag-koskleri-ve-aciq-hava-metbexi',
            'name' => $n('Çardaqlar, perqolalar və bağ çətirləri', 'Беседки, перголы и садовые зонты', 'Gazebos, pergolas and parasols'),
            'classes' => [
                'perqolalar-ve-maf-kicik-memarliq-formalari' => $card('L09_1', $n('Çardaqlar və perqolalar', 'Беседки и перголы', 'Gazebos and pergolas'), $ps, [
                    $f('L09_1', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([['Çardaq', 'cardaq'], ['Perqola', 'perqola']])]),
                    $frame,
                    ...$dims(),
                    $f('L09_1', 'dam', $n('Dam', 'Крыша', 'Roof'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Açıq', 'aciq'], ['Sabit örtük', 'sabit'], ['Hərəkətli lamel', 'lamel'], ['Yığılan örtük', 'yigilan'],
                    ])]),
                    $f('L09_1', 'ortuk', $n('Örtük materialı', 'Материал покрытия', 'Cover material'), 'dropdown', ['options' => [...$opts([
                        ['Parça', 'parca'], ['Polikarbonat', 'polikarbonat'], ['Metal', 'metal'], ['Ağac', 'agac'],
                    ]), $other()]]),
                    ...$madeToOrder,
                    ...$look,
                    $mount,
                    $f('L09_1', 'idareetme', $n('İdarəetmə', 'Управление', 'Operation'), 'dropdown', ['options' => [...$opts([['Əl ilə', 'el-ile'], ['Elektrik', 'elektrik']]), $na]]),
                    $kit('L09_1', [['Dayaqlar', 'dayaqlar'], ['Dam', 'dam'], ['Yan örtüklər', 'yan'], ['Motor', 'motor'], ['Bərkidicilər', 'berkidiciler']]),
                    $techDocH('Külək və qar yükü konkret model sənədindən. Bünövrə, montaj və daşıyıcı hesab ayrıca dəqiqləşdirilir.',
                        'Ветровая и снеговая нагрузка — из документа модели; фундамент и расчёт уточняются отдельно.',
                        'Wind and snow loads come from the model’s documents; foundation and structural design are agreed separately.'),
                ], $onOrder),
                'bag-cetirleri' => $card('L09_2', $n('Bağ çətirləri', 'Садовые зонты', 'Garden parasols'), $ps, [
                    $f('L09_2', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([['Mərkəzi dirək', 'merkezi'], ['Yan dirək', 'yan']])]),
                    $f('L09_2', 'forma', $n('Çətirin forması', 'Форма купола', 'Canopy shape'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Dairəvi', 'dairevi'], ['Düzbucaqlı / kvadrat', 'duzbucaqli'],
                    ])]),
                    $f('L09_2', 'diametr', $n('Çətirin diametri', 'Диаметр купола', 'Canopy diameter'), 'decimal', ['required' => true, 'unit' => 'cm', 'when' => $when('kart-l09-2-forma', 'dairevi')]),
                    $f('L09_2', 'olcu', $n('Çətirin ölçüsü', 'Размер купола', 'Canopy size'), 'size', ['required' => true, 'unit' => 'cm', 'when' => $when('kart-l09-2-forma', 'duzbucaqli')]
                        + $hint('Uzunluq × en.', 'Длина × ширина.', 'Length × width.')),
                    $f('L09_2', 'hundurluk', $n('Hündürlük', 'Высота', 'Height'), 'decimal', ['unit' => 'cm']),
                    $frame,
                    $f('L09_2', 'parca', $n('Parça', 'Ткань', 'Fabric'), 'dropdown', ['required' => true, 'options' => [...$opts([['Polyester', 'polyester'], ['Akril', 'akril']]), $other()]]),
                    ...$look,
                    $f('L09_2', 'acilma', $n('Açılma', 'Открывание', 'Opening'), 'dropdown', ['options' => $opts([['Əl ilə', 'el-ile'], ['Mexanizmli', 'mexanizmli']])]),
                    $f('L09_2', 'eyilme', $n('Əyilmə və dönmə', 'Наклон и поворот', 'Tilt and swivel'), 'dropdown', ['options' => $ynn]),
                    $kit('L09_2', [['Altlıq', 'altliq'], ['Ballast', 'ballast'], ['Qoruyucu örtük', 'ortuk']]),
                    $techDoc,
                ]),
                'cetir-altliqlari-ve-konstruksiya-hisseleri' => $card('L09_3', $n('Çətir altlıqları və konstruksiya hissələri', 'Основания для зонтов и комплектующие', 'Parasol bases and parts'), $ps, [
                    $f('L09_3', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Çətir altlığı', 'altliq'], ['Ballast', 'ballast'], ['Bərkidici', 'berkidici'], ['Əvəzləyici örtük', 'ortuk'],
                    ]), $other()]]),
                    $compat(),
                    ...$dims(),
                    $weight + $hint('Altlıqda boş çəki.', 'Для основания — вес пустого.', 'For a base, the empty weight.'),
                    $f('L09_3', 'ceki-dolu', $n('Doldurulmuş çəki', 'Вес с наполнителем', 'Filled weight'), 'decimal', ['unit' => 'kg', 'when' => $when('kart-l09-3-tip', 'altliq', 'ballast')]),
                    $material,
                    ...$look,
                    $kit('L09_3', [['Bərkidicilər', 'berkidiciler']]),
                    $techDoc,
                ]),
            ],
        ],

        // ════ 10. Manqallar, qrillər və açıq hava ocaqları ═════════════════
        [
            'slug' => 'manqallar-qriller-ve-aciq-hava-ocaqlari',
            'name' => $n('Manqallar, qrillər və açıq hava ocaqları', 'Мангалы, грили и уличные очаги', 'Barbecues, grills and fire pits'),
            'classes' => [
                // The old «Barbekü zonası / açıq hava mətbəxi» class.
                'barbeku-zonasi-aciq-hava-metbexi' => $card('L10_1', $n('Manqallar və qrillər', 'Мангалы и грили', 'Barbecues and grills'), $ps, [
                    $f('L10_1', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Manqal', 'manqal'], ['Qril', 'qril'], ['Barbekü', 'barbeku'],
                    ])]),
                    $f('L10_1', 'yanacaq', $n('Yanacaq', 'Топливо', 'Fuel'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Kömür', 'komur'], ['Odun', 'odun'], ['Qaz', 'qaz'], ['Elektrik', 'elektrik'],
                    ])]),
                    $material,
                    ...$dims(),
                    $f('L10_1', 'bisirme', $n('Bişirmə sahəsi', 'Рабочая поверхность', 'Cooking area'), 'size', ['required' => true, 'unit' => 'cm']
                        + $hint('En × dərinlik.', 'Ширина × глубина.', 'Width × depth.')),
                    $f('L10_1', 'brulor', $n('Brülör sayı', 'Число горелок', 'Burners'), 'decimal', ['unit' => 'ədəd', 'rules' => ['integer' => true], 'when' => $when('kart-l10-1-yanacaq', 'qaz')]),
                    $f('L10_1', 'guc', $n('Güc', 'Мощность', 'Power'), 'decimal', ['unit' => 'kW', 'when' => $when('kart-l10-1-yanacaq', 'qaz', 'elektrik')]),
                    $f('L10_1', 'qaz', $n('Qaz növü', 'Вид газа', 'Gas type'), 'dropdown', ['when' => $when('kart-l10-1-yanacaq', 'qaz'), 'options' => $opts([
                        ['Propan-butan (balon)', 'propan'], ['Təbii qaz', 'tebii'],
                    ])]),
                    $f('L10_1', 'qaz-tezyiq', $n('Qazın təzyiqi', 'Давление газа', 'Gas pressure'), 'decimal', ['unit' => 'mbar', 'when' => $when('kart-l10-1-yanacaq', 'qaz')]),
                    $voltage + ['when' => $when('kart-l10-1-yanacaq', 'elektrik')],
                    $f('L10_1', 'qapaq', $n('Qapaq', 'Крышка', 'Lid'), 'dropdown', ['options' => $ynn]),
                    $kit('L10_1', [['Barmaqlıq', 'barmaqliq'], ['Şiş', 'sis'], ['Reduktor', 'reduktor'], ['Şlanq', 'slanq'], ['Termometr', 'termometr']]),
                    $techDoc,
                ]),
                'aciq-hava-ocaqlari' => $card('L10_2', $n('Açıq hava ocaqları', 'Уличные очаги', 'Outdoor fire pits'), $ps, [
                    $f('L10_2', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Ocaq kasası', 'kasa'], ['Açıq hava kamini', 'kamin'], ['Od masası', 'od-masasi'],
                    ])]),
                    $f('L10_2', 'yanacaq', $n('Yanacaq', 'Топливо', 'Fuel'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Odun', 'odun'], ['Qaz', 'qaz'], ['Bioetanol', 'bioetanol'],
                    ]), $other()]]),
                    $material,
                    ...$dims(),
                    ...$look,
                    $f('L10_2', 'yerlesme', $n('Yerləşmə', 'Установка', 'Placement'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Sərbəst', 'serbest'], ['Sabit quraşdırılan', 'sabit'],
                    ])]),
                    $kit('L10_2', [['Qapaq', 'qapaq'], ['Qığılcım toru', 'tor'], ['Barmaqlıq', 'barmaqliq'], ['Qaz hissələri', 'qaz']]),
                    $techDocH('İstifadə məsafələri və yerləşmə şərtləri model təlimatında.', 'Безопасные расстояния и условия установки — в инструкции модели.', 'Safe distances and placement rules are in the model’s instructions.'),
                ]),
                'manqal-ve-qril-aksesuarlari' => $card('L10_3', $n('Manqal və qril aksesuarları', 'Аксессуары для мангалов и грилей', 'BBQ and grill accessories'), $ps, [
                    $f('L10_3', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Şiş', 'sis'], ['Barmaqlıq', 'barmaqliq'], ['Alət dəsti', 'alet-desti'], ['Termometr', 'termometr'], ['Qoruyucu örtük', 'ortuk'], ['Təmizləmə fırçası', 'firca'],
                    ])]),
                    $compat(),
                    ...$dims(),
                    $material,
                    $f('L10_3', 'say', $n('Dəstdə say', 'Количество в наборе', 'Pieces in set'), 'decimal', ['unit' => 'ədəd', 'rules' => ['integer' => true]]),
                    $f('L10_3', 'qulluq', $n('Qulluq', 'Уход', 'Care'), 'text', $hint('İstehsalçıya görə yuma və təmizləmə qaydası.', 'Мойка и чистка по производителю.', 'Washing and cleaning per the maker.')),
                ]),
            ],
        ],

        // ════ 11. Bağ dekoru və avadanlıqları ══════════════════════════════
        [
            'slug' => 'bag-dekoru-ve-avadanliqlari',
            'name' => $n('Bağ dekoru və avadanlıqları', 'Садовый декор и инвентарь', 'Garden decor and equipment'),
            'classes' => [
                'bag-dibcekleri-ve-kaspolar' => $card('L11_1', $n('Bağ dibçəkləri və kaşpolar', 'Садовые горшки и кашпо', 'Garden pots and planters'), $ps, [
                    $material,
                    ...$dims(),
                    $volume + $hint('Daxili həcm.', 'Внутренний объём.', 'Inner volume.'),
                    $f('L11_1', 'drenaj', $n('Drenaj dəliyi', 'Дренажное отверстие', 'Drainage hole'), 'dropdown', ['options' => $ynn]),
                    ...$look,
                    ...$outdoor,
                    $kit('L11_1', [['Altlıq', 'altliq'], ['Daxili qab', 'daxili-qab'], ['Dayaq', 'dayaq']]),
                ]),
                'bag-heykelleri-ve-dekorativ-fiqurlar' => $card('L11_2', $n('Bağ heykəlləri və dekorativ fiqurlar', 'Садовые скульптуры и декоративные фигуры', 'Garden statues and figures'), $ps, [
                    $f('L11_2', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Heykəl', 'heykel'], ['Fiqur', 'fiqur'], ['Dekorativ obyekt', 'obyekt'],
                    ])]),
                    $material,
                    ...$dims(),
                    $weight,
                    ...$look,
                    $mount,
                    ...$outdoor,
                ]),
                'bitki-dayaqlari-taglar-ve-spalerler' => $card('L11_3', $n('Bitki dayaqları, tağlar və şpalerlər', 'Опоры для растений, арки и шпалеры', 'Plant supports, arches and trellises'), $ps, [
                    $f('L11_3', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Tağ', 'tag'], ['Şpaler', 'spaler'], ['Bitki dayağı', 'dayaq'], ['Dekorativ qəfəs', 'qefes'],
                    ])]),
                    $material,
                    ...$dims(),
                    ...$look,
                    $mount,
                    $kit('L11_3', [['Torpaq bərkidicisi', 'torpaq-berkidicisi'], ['Birləşdirici', 'birlesdirici']]),
                    ...$outdoor,
                ]),
                'bag-urnalari-ve-saxlama-qutulari' => $card('L11_4', $n('Bağ urnaları və saxlama qutuları', 'Садовые урны и ящики для хранения', 'Garden bins and storage boxes'), $ps, [
                    $f('L11_4', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Urna', 'urna'], ['Saxlama qutusu', 'qutu'], ['Alət dolabı', 'dolab'],
                    ])]),
                    $material,
                    ...$dims(),
                    $volume,
                    ...$look,
                    $f('L11_4', 'qifil', $n('Qıfıllanır', 'Запирается', 'Lockable'), 'dropdown', ['options' => $ynn]),
                    $f('L11_4', 'su', $n('Suya davamlılıq', 'Водостойкость', 'Water resistance'), 'text',
                        $hint('İstehsalçı məlumatı; qapaq və birləşmələr daxil.', 'По данным производителя, включая крышку и швы.', 'Per the maker, lid and joints included.')),
                    $kit('L11_4', [['Daxili qab', 'daxili-qab'], ['Kilid', 'kilid'], ['Montaj dəsti', 'montaj']]),
                ]),
            ],
        ],

        // ════ 12. Hovuzlar, fəvvarələr və dekorativ su hövzələri ═══════════
        [
            'slug' => 'su-obyektleri',
            'name' => $n('Hovuzlar, fəvvarələr və dekorativ su hövzələri', 'Бассейны, фонтаны и декоративные водоёмы', 'Pools, fountains and water features'),
            'classes' => [
                // The old «Hovuz kasası» class (group «Hovuzlar (konstruksiya)»).
                'hovuz-kasasi' => $card('L12_1', $n('Hazır hovuzlar', 'Готовые бассейны', 'Ready-made pools'), $ps, [
                    $f('L12_1', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Şişmə', 'sisme'], ['Karkaslı', 'karkasli'], ['Sərt hazır kasa', 'kasa'],
                    ])]),
                    $f('L12_1', 'forma', $n('Forma', 'Форма', 'Shape'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Dairəvi', 'dairevi'], ['Oval', 'oval'], ['Düzbucaqlı', 'duzbucaqli'],
                    ]), $other()]]),
                    $material,
                    ...$dims(true, 'cm', $n('Xarici ölçülər: uzunluq × en × hündürlük.', 'Внешние размеры: длина × ширина × высота.', 'Outer size: length × width × height.')),
                    $f('L12_1', 'derinlik', $n('İşçi su dərinliyi', 'Рабочая глубина', 'Water depth'), 'decimal', ['required' => true, 'unit' => 'cm']),
                    ['required' => true] + $waterVolume + $hint('İstehsalçının doldurma səviyyəsində.', 'При уровне заполнения производителя.', 'At the maker’s fill level.'),
                    $f('L12_1', 'yerlesme', $n('Yerləşmə', 'Установка', 'Placement'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Yerüstü', 'yerustu'], ['Torpağa yerləşdirilən', 'torpaga'],
                    ])]),
                    $kit('L12_1', [['Nasos', 'nasos'], ['Filtr', 'filtr'], ['Nərdivan', 'nerdivan'], ['Örtük', 'ortuk'], ['Altlıq', 'altliq']]),
                    $techDoc,
                ]),
                'dekorativ-fevvare' => $card('L12_2', $n('Fəvvarələr və dekorativ su kasaları', 'Фонтаны и декоративные чаши', 'Fountains and water bowls'), $ps, [
                    $f('L12_2', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Hazır fəvvarə', 'fevvare'], ['Dekorativ su kasası', 'kasa'], ['Hazır gölməçə kasası', 'golmece'],
                    ])]),
                    $material,
                    ...$dims(),
                    $waterVolume,
                    ...$look,
                    ...$power(),
                    $kit('L12_2', [['Nasos', 'nasos'], ['Su anbarı', 'anbar'], ['İşıqlandırma', 'isiqlandirma'], ['Ucluq', 'ucluq']]),
                    $techDoc,
                ]),
                'hovuz-ve-fevvare-nasoslari' => $card('L12_3', $n('Hovuz və fəvvarə nasosları', 'Насосы для бассейнов и фонтанов', 'Pool and fountain pumps'), $ps, [
                    $f('L12_3', 'teyinat', $n('Təyinat', 'Назначение', 'Use'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Hovuz dövriyyəsi', 'hovuz'], ['Fəvvarə', 'fevvare'], ['Gölməçə', 'golmece'],
                    ])]),
                    $f('L12_3', 'qurasdirma', $n('Quraşdırma', 'Установка', 'Installation'), 'dropdown', ['required' => true, 'options' => $opts([['Sualtı', 'sualti'], ['Xarici', 'xarici']])]),
                    $f('L12_3', 'serf', $n('İş nöqtəsi: sərf', 'Рабочая точка: расход', 'Duty point: flow'), 'decimal', ['required' => true, 'unit' => 'm³/saat',
                        'rules' => ['lte' => 'kart-l12-3-maks-serf']] + $hint('İstehsalçı əyrisindən; əyrini «Texniki məlumat»a əlavə edin.', 'По кривой производителя; кривую приложите в «Технической информации».', 'From the maker’s curve; attach it under «Technical data».')),
                    $f('L12_3', 'basqi', $n('İş nöqtəsi: basqı', 'Рабочая точка: напор', 'Duty point: head'), 'decimal', ['required' => true, 'unit' => 'm',
                        'rules' => ['lte' => 'kart-l12-3-maks-basqi']] + $hint('Həmin sərfdəki basqı.', 'Напор при этом расходе.', 'The head at that flow.')),
                    $f('L12_3', 'maks-serf', $n('Maksimum sərf', 'Максимальный расход', 'Maximum flow'), 'decimal', ['unit' => 'm³/saat']),
                    $f('L12_3', 'maks-basqi', $n('Maksimum basqı', 'Максимальный напор', 'Maximum head'), 'decimal', ['unit' => 'm']
                        + $hint('Maksimum sərflə eyni iş nöqtəsi deyil.', 'Не та же точка, что максимальный расход.', 'Not the same point as the maximum flow.')),
                    ...$connection,
                    ...$power(),
                    $compat(),
                    $techDoc,
                ]),
                'hovuz-ve-golmece-filtrleri' => $card('L12_4', $n('Hovuz və gölməçə filtrləri', 'Фильтры для бассейнов и прудов', 'Pool and pond filters'), $ps, [
                    $f('L12_4', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Qum', 'qum'], ['Kartuş', 'kartus'], ['Bioloji', 'bioloji'],
                    ]), $other()]]),
                    $f('L12_4', 'axin', $n('Nominal axın', 'Номинальный расход', 'Rated flow'), 'decimal', ['required' => true, 'unit' => 'm³/saat']
                        + $hint('İstehsalçı şərti ilə.', 'При условиях производителя.', 'Under the maker’s conditions.')),
                    $f('L12_4', 'hecm', $n('Uyğun su həcmi', 'Подходящий объём воды', 'Water volume served'), 'decimal', ['unit' => 'm³']),
                    ...$connection,
                    $f('L12_4', 'filtr-material', $n('Filtr materialı', 'Фильтрующий материал', 'Filter media'), 'dropdown', ['options' => $opts([
                        ['Qum', 'qum'], ['Şüşə', 'suse'], ['Kartuş', 'kartus'], ['Süngər', 'sunger'], ['Bioloji material', 'bioloji'],
                    ])]),
                    $f('L12_4', 'miqdar', $n('Materialın miqdarı', 'Количество материала', 'Media quantity'), 'decimal'),
                    $f('L12_4', 'miqdar-vahid', $n('Miqdarın vahidi', 'Единица количества', 'Quantity unit'), 'dropdown', ['options' => $opts([['kg', 'kg'], ['ədəd', 'eded']])]),
                    $compat(),
                    $kit('L12_4', [['Klapan', 'klapan'], ['Filtr materialı', 'filtr-materiali'], ['Nasos', 'nasos']]),
                    $techDoc,
                ]),
                'hovuz-ortukleri-nerdivanlari-ve-aksesuarlari' => $card('L12_5', $n('Hovuz örtükləri, nərdivanları və aksesuarları', 'Покрытия, лестницы и аксессуары для бассейнов', 'Pool covers, ladders and accessories'), $ps, [
                    $f('L12_5', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Örtük', 'ortuk'], ['Nərdivan', 'nerdivan'], ['Təmizləmə toru', 'tor'], ['Fırça', 'firca'], ['Skimmer', 'skimmer'], ['Ucluq', 'ucluq'], ['Birləşdirici', 'birlesdirici'],
                    ])]),
                    $compat(true, $n('Brend + model və ya uyğunluq kodu. Nərdivanda uyğun hovuz hündürlüyü, örtükdə uyğun hovuz ölçüsü.', 'Бренд + модель или код. Для лестницы — высота бассейна, для покрытия — его размер.', 'Brand + model or code. For a ladder, the pool height; for a cover, the pool size.')),
                    ...$dims(),
                    $material,
                    ...$load,
                    ...$packing,
                    $techDoc,
                ]),
                'hovuz-temizleyicileri-ve-su-testleri' => $card('L12_6', $n('Hovuz təmizləyiciləri və su testləri', 'Очистители бассейнов и тесты воды', 'Pool cleaners and water tests'), $ps, [
                    $f('L12_6', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Robot', 'robot'], ['Əl tozsoranı', 'tozsoran'], ['Test zolağı', 'zolaq'], ['Damcı test dəsti', 'damci-test'],
                    ])]),
                    $compat(),
                    $f('L12_6', 'sahe', $n('İş sahəsi', 'Зона очистки', 'Cleans'), 'multiselect', ['when' => $when('kart-l12-6-tip', 'robot', 'tozsoran'), 'options' => $opts([
                        ['Dib', 'dib'], ['Divar', 'divar'], ['Su xətti', 'su-xetti'],
                    ])]),
                    ...$power($when('kart-l12-6-tip', 'robot', 'tozsoran')),
                    $f('L12_6', 'parametr', $n('Test parametrləri', 'Параметры теста', 'Test parameters'), 'multiselect', ['when' => $when('kart-l12-6-tip', 'zolaq', 'damci-test'), 'options' => [...$opts([
                        ['pH', 'ph'], ['Sərbəst xlor', 'serbest-xlor'], ['Ümumi xlor', 'umumi-xlor'], ['Qələvilik', 'qelevilik'],
                    ]), $other()]]),
                    $f('L12_6', 'diapazon', $n('Ölçmə diapazonu', 'Диапазон измерения', 'Measuring range'), 'text', ['when' => $when('kart-l12-6-tip', 'zolaq', 'damci-test')]
                        + $hint('Hər göstəricinin diapazonu və vahidi, məs. pH 6,2–8,4; xlor 0–5 mg/l.', 'Диапазон и единица по каждому показателю.', 'Range and unit for each parameter.')),
                    ...$packing,
                    $techDoc,
                ]),
            ],
        ],

        // ════ Not in the workbook — plain form ═════════════════════════════
        [
            'slug' => 'usaq-ve-idman-meydancalari',
            'name' => $n('Uşaq və idman meydançaları', 'Детские и спортивные площадки', 'Playgrounds and outdoor fitness'),
            'plain' => ['usaq-meydancasi-aciq-hava-oyun-avadanligi', 'aciq-hava-trenajorlari'],
        ],
        [
            'slug' => 'bag-tikilileri',
            'name' => $n('Bağ tikililəri', 'Садовые постройки', 'Garden buildings'),
            'plain' => ['istixana', 'bag-kompost-qabi'],
        ],
        // «İşıqlandırma Elektrik bölməsində qalır», but Elektrik has no outdoor card yet.
        [
            'slug' => 'landsaft-isiqlandirmasi',
            'name' => $n('Landşaft işıqlandırması', 'Ландшафтное освещение', 'Landscape lighting'),
            'plain' => ['kuce-isiqlandirma-cihazi-direk-park-torpaq-ici', 'landsaft-projektoru-memarliq-isiqlandirmasi'],
        ],
        // «Drenaj şəbəkələri ümumi tikinti materialları bölməsinə aiddir» — kept here
        // until that section has a home for them; the two old drainage groups become one.
        [
            'slug' => 'drenaj-suoturme-ve-geosintetika',
            'name' => $n('Drenaj, suötürmə və geosintetika', 'Дренаж, водоотвод и геосинтетика', 'Drainage and geosynthetics'),
            'plain' => ['geotekstil-geotor', 'drenaj-sistemleri', 'suoturme-lotoklari-yagis-kanalizasiyasi', 'drenaj-borusu'],
        ],
    ],

    // Duplicates of a card class: products move over, the old class closes.
    'merge' => [
        'seki-ucun-tebii-das-yasti-das' => ['into' => 'seki-dasi-seki-plitesi', 'set' => ['kart-l01-1-material' => 'tebii-das']],
        'bag-kosku' => ['into' => 'perqolalar-ve-maf-kicik-memarliq-formalari', 'set' => ['kart-l09-1-tip' => 'cardaq']],
        'hovuzlar-ve-su-obyektleri-ucun-avadanliq' => 'hovuz-ortukleri-nerdivanlari-ve-aksesuarlari',
        'golmece-ve-su-obyektleri-plyonka-avadanliq' => ['into' => 'dekorativ-fevvare', 'set' => ['kart-l12-2-tip' => 'golmece']],
    ],

    'retire_groups' => ['drenaj-ve-geosintetika', 'maf-hasarlar-ve-su-obyektleri', 'hovuzlar-konstruksiya'],
];
