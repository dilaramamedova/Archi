<?php

/*
|--------------------------------------------------------------------------
| Elektrik, işıqlandırma və ağıllı ev — 43 cards in the workbook's 4 blocks
|--------------------------------------------------------------------------
|
| Source: Archi_AZ_Elektrik_Kataloq_AZ.xlsx (client, 23.11.2026). Format and decisions:
| docs/product-cards.md. Section «Elektrik, işıqlandırma, zəif cərəyan sistemləri və
| ağıllı ev»: the workbook's 4 blocks (E01 quraşdırma, E02 işıqlandırma, E03 internet /
| rabitə / təhlükəsizlik, E04 ağıllı ev) are the groups; their «Alt qrup» column stays
| inside the block. «Güc elektrik sistemləri» (shields, cable, breakers — «sonraya
| saxlanılıb» in the workbook) keeps its plain form.
|
| Repeated blocks («Nominal gərginlik», «Ağıllı ev uyğunluğu», «Rabitə və qoşulma»,
| «Əlavə idarəetmə cihazı», «Qidalanma», «PoE», «İşıq göstəriciləri» …) are one shared
| definition each. Option values are Azerbaijani only; attribute / class names az/ru/en.
| `kart-zemanet` / `kart-zemanet-muddeti` are the shared definitions of the other files.
|
*/

$n = fn (string $az, string $ru, string $en): array => compact('az', 'ru', 'en');
$o = fn (string $az, string $ru, string $en, array $extra = []): array => ['value' => compact('az', 'ru', 'en')] + $extra;
$a = fn (string $az, array $extra = []): array => ['value' => ['az' => $az]] + $extra;

$P_NAME = $n('Dəqiq adını yazın', 'Укажите точное название', 'Enter the exact name');
$P_CODE = $n('Rəngi və ya kodunu yazın', 'Укажите цвет или код', 'Enter the colour or its code');
$P_FREQ = $n('Tezliyi yazın', 'Укажите частоту', 'Enter the frequency');

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

// ── Shared ──────────────────────────────────────────────────────────────────
$warranty = ['slug' => 'kart-zemanet', 'name' => $n('Zəmanət', 'Гарантия', 'Warranty'), 'type' => 'dropdown', 'options' => [
    $o('Var', 'Есть', 'Provided', ['slug' => 'var']), $o('Verilmir', 'Не предоставляется', 'Not provided', ['slug' => 'verilmir']), $ns,
]];
$warrantyMonths = ['slug' => 'kart-zemanet-muddeti', 'name' => $n('Zəmanət müddəti', 'Срок гарантии', 'Warranty period'), 'type' => 'decimal', 'unit' => 'ay',
    'rules' => ['integer' => true], 'when' => ['attr' => 'kart-zemanet', 'in' => ['var']]];
$guarantee = [$warranty, $warrantyMonths];

$color = ['slug' => 'kart-el-reng', 'name' => $n('Rəng', 'Цвет', 'Colour'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [
    $a('Ağ', ['slug' => 'ag']), $a('Qara', ['slug' => 'qara']), $a('Boz', ['slug' => 'boz']), $a('Xrom', ['slug' => 'xrom']),
    $a('Qızılı', ['slug' => 'qizili']), $other($P_CODE),
]];
$finish = ['slug' => 'kart-el-seth', 'name' => $n('Səth', 'Поверхность', 'Finish'), 'type' => 'dropdown', 'required' => true, 'options' => [
    $a('Mat', ['slug' => 'mat']), $a('Parlaq', ['slug' => 'parlaq']), $a('Fırçalanmış', ['slug' => 'fircalanmis']), $other(),
]];
$look = [$color, $finish];
$lookOptional = [['required' => false] + $color, ['required' => false] + $finish];

$material = ['slug' => 'kart-el-material', 'name' => $n('Material', 'Материал', 'Material'), 'type' => 'dropdown', 'filter' => true, 'options' => [...$opts([
    ['Metal', 'metal'], ['Plastik', 'plastik'], ['Şüşə', 'suse'], ['Ağac', 'agac'], ['Keramika', 'keramika'],
]), $other()]];
$lightMaterial = ['slug' => 'kart-el-material-isiq', 'name' => $n('Material', 'Материал', 'Material'), 'type' => 'dropdown', 'options' => [...$opts([
    ['Metal', 'metal'], ['Plastik', 'plastik'], ['Şüşə', 'suse'], ['Ağac', 'agac'],
]), $other()]];
// «Material və görünüş»: material + rəng + səth, not required.
$lightLook = [$lightMaterial, ...$lookOptional];

$ip = ['slug' => 'kart-el-ip', 'name' => $n('IP qorunma dərəcəsi', 'Степень защиты IP', 'IP rating'), 'type' => 'dropdown', 'filter' => true, 'options' => [...$opts([
    ['IP20', 'ip20'], ['IP44', 'ip44'], ['IP54', 'ip54'], ['IP65', 'ip65'], ['IP66', 'ip66'], ['IP67', 'ip67'],
]), $other(), $ns]] + $hint('İstehsalçı məlumatına görə.', 'По данным производителя.', 'Per the maker.');

$series = ['slug' => 'kart-el-seriya', 'name' => $n('Seriya uyğunluğu', 'Совместимая серия', 'Matching series'), 'type' => 'text', 'required' => true]
    + $hint('Mexanizm, üzlük və çərçivə üçün istehsalçının uyğun seriyası.', 'Серия производителя для механизма, накладки и рамки.', 'The maker’s series for mechanism, cover plate and frame.');
$compatModel = fn (bool $req = false): array => ['slug' => 'kart-el-uygun-seriya', 'name' => $n('Uyğun seriya və model', 'Совместимая серия и модель', 'Compatible series and model'), 'type' => 'text', 'required' => $req]
    + $hint('İstehsalçının seriyası / modeli və uyğunluq kodu.', 'Серия / модель производителя и код совместимости.', 'The maker’s series / model and compatibility code.');
$compatSystem = fn (bool $req = false, ?array $hintText = null): array => ['slug' => 'kart-el-uygun-sistem', 'name' => $n('Uyğun sistem', 'Совместимая система', 'Compatible system'), 'type' => 'text', 'required' => $req]
    + ['hint' => $hintText ?? $n('Mərkəzi blok / seriya / model və ya qoşulma interfeysi.', 'Центральный блок / серия / модель или интерфейс.', 'Control panel / series / model or interface.')];

$wiringMount = ['slug' => 'kart-el-montaj-qurasdirma', 'name' => $n('Montaj', 'Монтаж', 'Mounting'), 'type' => 'dropdown', 'filter' => true, 'required' => true, 'options' => [...$opts([
    ['Gizli', 'gizli'], ['Səthüstü', 'sethustu'], ['Mebelə quraşdırılan', 'mebel'], ['Daşınan adapter', 'adapter'],
]), $other()]];

// «Nominal gərginlik — V və AC/DC. Aralıq varsa minimum və maksimum».
$voltage = fn (bool $req = true): array => [
    ['slug' => 'kart-el-gerginlik', 'name' => $n('Nominal gərginlik', 'Номинальное напряжение', 'Rated voltage'), 'type' => 'decimal', 'required' => $req, 'unit' => 'V',
        'rules' => ['lte' => 'kart-el-gerginlik-maks']]
        + $hint('Aralıq varsa minimum; tək qiymətdə yalnız bu sahə.', 'Если диапазон — минимум; одно значение — только это поле.', 'The minimum of a range; for one value fill only this.'),
    ['slug' => 'kart-el-gerginlik-maks', 'name' => $n('Nominal gərginlik, maks', 'Номинальное напряжение, макс.', 'Rated voltage, max'), 'type' => 'decimal', 'unit' => 'V'],
    ['slug' => 'kart-el-cereyan-novu', 'name' => $n('Cərəyan növü', 'Род тока', 'Current type'), 'type' => 'dropdown', 'required' => $req,
        'options' => $opts([['AC', 'ac'], ['DC', 'dc'], ['AC/DC', 'ac-dc']])],
];
$current = fn (bool $req = false, ?array $h = null): array => ['slug' => 'kart-el-cereyan', 'name' => $n('Nominal cərəyan', 'Номинальный ток', 'Rated current'), 'type' => 'decimal', 'required' => $req, 'unit' => 'A']
    + ['hint' => $h ?? $n('İstehsalçı üzrə.', 'По данным производителя.', 'Per the maker.')];
$neutral = fn (bool $req = false): array => ['slug' => 'kart-el-neytral', 'name' => $n('Neytral naqil', 'Нейтральный провод', 'Neutral wire'), 'type' => 'dropdown', 'required' => $req,
    'options' => [...$opts([['Tələb olunur', 'teleb-olunur'], ['Tələb olunmur', 'teleb-olunmur'], ['İstəyə bağlı', 'isteye-bagli']]), $na]];

// «Ağıllı ev uyğunluğu — Yoxdur / Var / Göstərilməyib; varsa sistem, Matter, birbaşa / körpü».
$smart = [
    ['slug' => 'kart-el-agilli', 'name' => $n('Ağıllı ev uyğunluğu', 'Совместимость с умным домом', 'Smart-home compatible'), 'type' => 'dropdown',
        'options' => [$a('Yoxdur', ['slug' => 'yoxdur']), $a('Var', ['slug' => 'var']), $ns]],
    ['slug' => 'kart-el-agilli-sistem', 'name' => $n('Ağıllı ev sistemi', 'Система умного дома', 'Smart-home system'), 'type' => 'text', 'required' => true,
        'when' => ['attr' => 'kart-el-agilli', 'in' => ['var']]]
        + $hint('Sistemin adı, məs. Apple Home, Google Home, Yandex, Tuya.', 'Название системы, напр. Apple Home, Google Home, Яндекс, Tuya.', 'The system, e.g. Apple Home, Google Home, Tuya.'),
    ['slug' => 'kart-el-matter', 'name' => $n('Matter dəstəyi', 'Поддержка Matter', 'Matter support'), 'type' => 'dropdown', 'options' => [$yes, $no, $ns],
        'when' => ['attr' => 'kart-el-agilli', 'in' => ['var']]],
    ['slug' => 'kart-el-qosulma-yolu', 'name' => $n('Qoşulma yolu', 'Способ подключения', 'Connection path'), 'type' => 'dropdown',
        'options' => $opts([['Birbaşa', 'birbasa'], ['Körpü vasitəsilə', 'korpu']]), 'when' => ['attr' => 'kart-el-agilli', 'in' => ['var']]],
];
// «Rabitə və qoşulma — Wi-Fi və radioda tezlik göstərilir».
$comm = ['slug' => 'kart-el-rabite', 'name' => $n('Rabitə və qoşulma', 'Связь и подключение', 'Connectivity'), 'type' => 'multiselect', 'options' => [
    $noneX(), $a('Wi-Fi', ['slug' => 'wi-fi', 'prompt' => $P_FREQ]), $a('Ethernet', ['slug' => 'ethernet']), $a('Zigbee', ['slug' => 'zigbee']),
    $a('Thread', ['slug' => 'thread']), $a('Bluetooth', ['slug' => 'bluetooth']), $a('KNX TP', ['slug' => 'knx-tp']), $a('KNX IP', ['slug' => 'knx-ip']),
    $a('Radio', ['slug' => 'radio', 'prompt' => $P_FREQ]), $other(),
]];
// «Əlavə idarəetmə cihazı — varsa uyğun model və komplektə daxil olub-olmaması».
$extraDevice = [
    ['slug' => 'kart-el-elave-cihaz', 'name' => $n('Əlavə idarəetmə cihazı', 'Дополнительное устройство управления', 'Extra control device'), 'type' => 'multiselect', 'options' => [
        $noneX('Tələb olunmur', 'teleb-olunmur'), $a('Kontroller', ['slug' => 'kontroller']), $a('Şlüz', ['slug' => 'sluz']),
        $a('Thread Border Router', ['slug' => 'tbr']), $other(),
    ]],
    ['slug' => 'kart-el-elave-cihaz-model', 'name' => $n('Uyğun idarəetmə cihazının modeli', 'Модель устройства управления', 'Control device model'), 'type' => 'text',
        'when' => ['attr' => 'kart-el-elave-cihaz', 'in' => ['kontroller', 'sluz', 'tbr', 'diger']]],
    ['slug' => 'kart-el-elave-cihaz-komplekt', 'name' => $n('İdarəetmə cihazı komplektdədir', 'Устройство в комплекте', 'Device included'), 'type' => 'dropdown',
        'options' => $opts([['Komplektə daxildir', 'daxildir'], ['Ayrıca alınır', 'ayrica']]),
        'when' => ['attr' => 'kart-el-elave-cihaz', 'in' => ['kontroller', 'sluz', 'tbr', 'diger']]],
];
// Smart block of the lights and devices: compatibility + connectivity + extra device.
$smartBlock = [...$smart, $comm, ...$extraDevice];

// «Qidalanma* — Şəbəkə / adapter / PoE / batareya / akkumulyator / digər; V, AC/DC; batareyada tip və say».
$supply = [
    ['slug' => 'kart-el-qidalanma', 'name' => $n('Qidalanma', 'Питание', 'Power supply'), 'type' => 'dropdown', 'required' => true, 'filter' => true, 'options' => [...$opts([
        ['Şəbəkə', 'sebeke'], ['Adapter', 'adapter'], ['PoE', 'poe'], ['Batareya', 'batareya'], ['Akkumulyator', 'akkumulyator'],
    ]), $other()]],
    ['slug' => 'kart-el-qida-gerginlik', 'name' => $n('Qidalanma gərginliyi', 'Напряжение питания', 'Supply voltage'), 'type' => 'decimal', 'unit' => 'V',
        'when' => ['attr' => 'kart-el-qidalanma', 'in' => ['sebeke', 'adapter', 'poe', 'diger']]]
        + $hint('AC/DC-ni cərəyan növündə göstərin, varsa.', 'Род тока укажите отдельно, если есть.', 'Give AC/DC separately, if known.'),
    ['slug' => 'kart-el-batareya', 'name' => $n('Batareyanın tipi və sayı', 'Тип и число батарей', 'Battery type and count'), 'type' => 'text', 'required' => true,
        'when' => ['attr' => 'kart-el-qidalanma', 'in' => ['batareya', 'akkumulyator']]]
        + $hint('Məs. 2 × AA, CR2032, Li-ion 2500 mAh.', 'Напр. 2 × AA, CR2032, Li-ion 2500 мА·ч.', 'E.g. 2 × AA, CR2032, Li-ion 2500 mAh.'),
];
$poe = [
    ['slug' => 'kart-el-poe', 'name' => $n('PoE', 'PoE', 'PoE'), 'type' => 'dropdown', 'filter' => true, 'options' => [...$opts([
        ['Yoxdur', 'yoxdur'], ['IEEE 802.3af', 'af'], ['IEEE 802.3at', 'at'], ['IEEE 802.3bt', 'bt'], ['Passiv', 'passiv'],
    ])]],
    ['slug' => 'kart-el-poe-gerginlik', 'name' => $n('Passiv PoE gərginliyi', 'Напряжение пассивного PoE', 'Passive PoE voltage'), 'type' => 'decimal', 'unit' => 'V',
        'when' => ['attr' => 'kart-el-poe', 'in' => ['passiv']]],
];
$ports = ['slug' => 'kart-el-portlar', 'name' => $n('Portlar', 'Порты', 'Ports'), 'type' => 'text', 'required' => true]
    + $hint('Növ, say və sürət: məs. 4 × RJ45 1 Gbit/s, 1 × SFP, 1 × USB.', 'Тип, число и скорость: напр. 4 × RJ45 1 Гбит/с, 1 × SFP.', 'Type, count and speed: e.g. 4 × RJ45 1 Gbit/s, 1 × SFP.');
$wifi = [
    ['slug' => 'kart-el-wifi', 'name' => $n('Wi-Fi', 'Wi-Fi', 'Wi-Fi'), 'type' => 'dropdown', 'options' => $opts([['Yoxdur', 'yoxdur'], ['Var', 'var']])],
    ['slug' => 'kart-el-wifi-standart', 'name' => $n('Wi-Fi standartı', 'Стандарт Wi-Fi', 'Wi-Fi standard'), 'type' => 'dropdown', 'filter' => true, 'when' => ['attr' => 'kart-el-wifi', 'in' => ['var']],
        'options' => [...$opts([['Wi-Fi 4 (802.11n)', 'wifi4'], ['Wi-Fi 5 (802.11ac)', 'wifi5'], ['Wi-Fi 6 (802.11ax)', 'wifi6'], ['Wi-Fi 6E', 'wifi6e'], ['Wi-Fi 7 (802.11be)', 'wifi7']]), $other()]],
    ['slug' => 'kart-el-wifi-zolaq', 'name' => $n('Tezlik zolaqları', 'Частотные диапазоны', 'Frequency bands'), 'type' => 'multiselect', 'when' => ['attr' => 'kart-el-wifi', 'in' => ['var']],
        'options' => $opts([['2,4 GHz', '2-4'], ['5 GHz', '5'], ['6 GHz', '6']])],
];
$dims = ['slug' => 'kart-el-olculer', 'name' => $n('Ölçülər', 'Размеры', 'Dimensions'), 'type' => 'text']
    + $hint('Uzunluq × en × hündürlük və ya diametr × hündürlük, mm. Məs. 120 × 80 × 40 və ya Ø90 × 60.', 'Длина × ширина × высота или диаметр × высота, мм.', 'Length × width × height or diameter × height, mm.');

// Lighting.
$source = [
    ['slug' => 'kart-el-isiq-menbeyi', 'name' => $n('İşıq mənbəyi', 'Источник света', 'Light source'), 'type' => 'dropdown', 'required' => true,
        'options' => $opts([['Daxili LED', 'daxili-led'], ['Dəyişən lampa', 'lampa']])],
    ['slug' => 'kart-el-soket', 'name' => $n('Soket', 'Цоколь', 'Socket'), 'type' => 'dropdown', 'required' => true, 'when' => ['attr' => 'kart-el-isiq-menbeyi', 'in' => ['lampa']],
        'options' => [...$opts([['E27', 'e27'], ['E14', 'e14'], ['GU10', 'gu10'], ['GU5.3', 'gu5-3'], ['G9', 'g9'], ['G4', 'g4'], ['GX53', 'gx53']]), $other()]],
    ['slug' => 'kart-el-lampa-sayi', 'name' => $n('Lampa sayı', 'Число ламп', 'Number of lamps'), 'type' => 'decimal', 'required' => true, 'unit' => 'ədəd',
        'rules' => ['integer' => true], 'when' => ['attr' => 'kart-el-isiq-menbeyi', 'in' => ['lampa']]],
];
$wattage = fn (?array $h = null, bool $req = true): array => ['slug' => 'kart-el-guc', 'name' => $n('Güc', 'Мощность', 'Power'), 'type' => 'decimal', 'filter' => true, 'required' => $req, 'unit' => 'W']
    + ['hint' => $h ?? $n('Daxili LED üçün ümumi W; dəyişən lampada bir soket üçün maksimum W.', 'Для встроенного LED — общая мощность; для сменной лампы — максимум на патрон.', 'Built-in LED: total W; replaceable lamp: maximum W per socket.')];
$kelvin = [
    ['slug' => 'kart-el-k-min', 'name' => $n('Rəng temperaturu', 'Цветовая температура', 'Colour temperature'), 'type' => 'decimal', 'unit' => 'K', 'filter' => true,
        'rules' => ['lte' => 'kart-el-k-maks']] + $hint('Ağ işıq üçün K; aralıqda minimum.', 'Для белого света, К; для диапазона — минимум.', 'For white light, K; the minimum of a range.'),
    ['slug' => 'kart-el-k-maks', 'name' => $n('Rəng temperaturu, maks', 'Цветовая температура, макс.', 'Colour temperature, max'), 'type' => 'decimal', 'unit' => 'K'],
];
$cri = ['slug' => 'kart-el-cri', 'name' => $n('Rəngötürmə indeksi (CRI)', 'Индекс цветопередачи (CRI)', 'Colour rendering (CRI)'), 'type' => 'decimal', 'unit' => 'Ra', 'rules' => ['max' => 100]];
// «İşıq göstəriciləri — lm; K və ya K aralığı; RGB / RGBW; CRI».
$lightMetrics = [
    ['slug' => 'kart-el-isiq-axini', 'name' => $n('İşıq axını', 'Световой поток', 'Luminous flux'), 'type' => 'decimal', 'unit' => 'lm']
        + $hint('Yalnız daxili və ya komplektdəki işıq mənbəyi üzrə.', 'Только для встроенного или входящего в комплект источника.', 'Only for a built-in or included light source.'),
    ...$kelvin,
    ['slug' => 'kart-el-rgb', 'name' => $n('Rəngli işıq', 'Цветной свет', 'Colour light'), 'type' => 'dropdown', 'options' => $opts([['Yoxdur', 'yoxdur'], ['RGB', 'rgb'], ['RGBW', 'rgbw']])],
    $cri,
];
$lightColor = ['slug' => 'kart-el-isiq-rengi', 'name' => $n('İşıq rəngi', 'Цвет света', 'Light colour'), 'type' => 'dropdown', 'filter' => true, 'options' => [...$opts([
    ['Sabit ağ', 'sabit-ag'], ['Tənzimlənən ağ', 'tenzimlenen-ag'], ['RGB', 'rgb'], ['RGBW', 'rgbw'], ['RGB+CCT', 'rgb-cct'],
]), $other()]];
$dimming = ['slug' => 'kart-el-dimm', 'name' => $n('Parlaqlığın tənzimlənməsi', 'Регулировка яркости', 'Dimming'), 'type' => 'multiselect', 'options' => [
    $noneX(), ...$opts([['Faza kəsimi', 'faza'], ['DALI', 'dali'], ['0–10 V', '0-10'], ['1–10 V', '1-10'], ['PWM', 'pwm'], ['Tətbiq', 'tetbiq']]), $other(),
]];
$beam = ['slug' => 'kart-el-sua-bucagi', 'name' => $n('İşıq şüasının bucağı', 'Угол луча', 'Beam angle'), 'type' => 'decimal', 'unit' => '°'];
$cutout = ['slug' => 'kart-el-montaj-boslugu', 'name' => $n('Montaj boşluğu', 'Монтажное отверстие', 'Cut-out'), 'type' => 'text']
    + $hint('Kəsik ölçüsü və tələb olunan dərinlik, mm; gömülən modeldə.', 'Размер выреза и необходимая глубина, мм; для встраиваемых.', 'Cut-out size and depth needed, mm; for recessed models.');
// The light cards' common core.
$lightCore = fn (array $before = []): array => [...$before, ...$source, $wattage(), ...$voltage(), $dims, ...$lightLook, $ip, ...$lightMetrics, $dimming, ...$smartBlock];

$packCount = ['slug' => 'kart-el-paketde-say', 'name' => $n('Paketdə say', 'Количество в упаковке', 'Pieces per pack'), 'type' => 'decimal', 'unit' => 'ədəd', 'rules' => ['integer' => true]];
$systems = ['slug' => 'kart-el-sistemler', 'name' => $n('Dəstəklənən sistemlər', 'Поддерживаемые системы', 'Supported systems'), 'type' => 'multiselect', 'filter' => true, 'required' => true,
    'options' => [...$opts([['Matter', 'matter'], ['KNX', 'knx'], ['İstehsalçının sistemi', 'istehsalci']]), $other()]]
    + $hint('Yalnız təsdiqlənmiş dəstək. Ekosistemi və uyğun modelləri təsvirdə yazın.', 'Только подтверждённая поддержка. Экосистему и модели укажите в описании.', 'Confirmed support only. Name the ecosystem and models in the description.');

$kit = fn (string $code, array $items): array => [
    'slug' => 'kart-'.strtolower(str_replace('_', '-', $code)).'-komplekt', 'name' => $n('Komplektə daxildir', 'В комплекте', 'Included'), 'type' => 'multiselect',
    'hint' => $n('Yalnız qiymətə daxil olanlar.', 'Только то, что входит в цену.', 'Only what the price includes.'),
    'options' => [...$opts($items), $none()],
];
$f = fn (string $code, string $key, array $name, string $type, array $extra = []): array => ['slug' => 'kart-'.strtolower(str_replace('_', '-', $code)).'-'.$key, 'name' => $name, 'type' => $type] + $extra;
$card = fn (string $code, array $name, array $units, array $fields): array => ['code' => $code, 'name' => $name, 'sale' => 'unit', 'units' => $units, 'fields' => $fields];
$installKit = [['Mexanizm', 'mexanizm'], ['Üzlük', 'uzluk'], ['Çərçivə', 'cercive']];
$ps = ['piece', 'set'];

return [
    'section' => 'elektrik-isiqlandirma-zeif-cereyan-sistemleri-ve-agilli-ev',

    'groups' => [
        // ════ 1. Elektrik quraşdırma məhsulları ════════════════════════════
        [
            'slug' => 'elektrik-qurasdirma-mehsullari',
            'name' => $n('Elektrik quraşdırma məhsulları', 'Электроустановочные изделия', 'Wiring devices'),
            'classes' => [
                'rozetkalar-ve-acarlar' => $card('E01_01', $n('Elektrik rozetkaları', 'Электрические розетки', 'Power sockets'), $ps, [
                    $f('E01_01', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['filter' => true, 'required' => true, 'options' => [...$opts([
                        ['Adi', 'adi'], ['USB şarjlı', 'usb'], ['Adi və USB', 'adi-usb'], ['Ağıllı adapter', 'agilli-adapter'],
                    ]), $other()]] + $hint('Ağıllı rozetka da bu kartda yerləşdirilir.', 'Умная розетка тоже размещается здесь.', 'Smart sockets are listed here too.')),
                    $wiringMount,
                    $f('E01_01', 'standart', $n('Rozetka standartı', 'Стандарт розетки', 'Socket standard'), 'dropdown', ['filter' => true, 'required' => true, 'options' => [...$opts([
                        ['Schuko (F)', 'schuko'], ['Tip C', 'c'], ['Tip E', 'e'], ['Tip G (Britaniya)', 'g'],
                    ]), $other()]]),
                    $f('E01_01', 'yuva', $n('Yuva sayı', 'Число гнёзд', 'Outlets'), 'decimal', ['required' => true, 'unit' => 'ədəd', 'rules' => ['integer' => true]]),
                    $f('E01_01', 'torpaqlama', $n('Torpaqlama', 'Заземление', 'Earthing'), 'dropdown', ['required' => true, 'options' => $opts([['Var', 'var'], ['Yoxdur', 'yoxdur']])]),
                    ...$voltage(),
                    $current(true),
                    $f('E01_01', 'usb', $n('USB çıxışı', 'USB-выход', 'USB output'), 'multiselect', ['filter' => true, 'options' => [$noneX(), ...$opts([['USB-A', 'usb-a'], ['USB-C', 'usb-c']])]]),
                    $f('E01_01', 'usb-port', $n('USB port sayı', 'Число USB-портов', 'USB ports'), 'decimal', ['unit' => 'ədəd', 'rules' => ['integer' => true],
                        'when' => $when('kart-e01-01-usb', 'usb-a', 'usb-c')]),
                    $f('E01_01', 'usb-guc', $n('USB maksimum ümumi güc', 'Макс. общая мощность USB', 'USB maximum total power'), 'decimal', ['unit' => 'W',
                        'when' => $when('kart-e01-01-usb', 'usb-a', 'usb-c')]),
                    $f('E01_01', 'usb-protokol', $n('Şarj protokolu', 'Протокол зарядки', 'Charging protocol'), 'text', ['when' => $when('kart-e01-01-usb', 'usb-a', 'usb-c')]
                        + $hint('Məs. USB PD, QC 3.0.', 'Напр. USB PD, QC 3.0.', 'E.g. USB PD, QC 3.0.')),
                    ...$look,
                    $ip,
                    $series,
                    ...$smartBlock,
                    $kit('E01_01', $installKit),
                ]),
                'internet-tv-ve-telefon-rozetkalari' => $card('E01_02', $n('İnternet, TV və telefon rozetkaları', 'Розетки интернет, ТВ и телефона', 'Data, TV and phone sockets'), $ps, [
                    $f('E01_02', 'teyinat', $n('Təyinat', 'Назначение', 'Purpose'), 'dropdown', ['filter' => true, 'required' => true, 'options' => $opts([
                        ['RJ45 internet', 'rj45'], ['TV', 'tv'], ['SAT', 'sat'], ['Radio', 'radio'], ['Telefon', 'telefon'], ['Qarışıq', 'qarisiq'],
                    ])]),
                    $f('E01_02', 'port', $n('Port sayı', 'Число портов', 'Ports'), 'decimal', ['required' => true, 'unit' => 'ədəd', 'rules' => ['integer' => true]]
                        + $hint('Qarışıq modeldə hər növ üçün təsvirdə yazın.', 'Для смешанной модели — по каждому типу в описании.', 'For a mixed model, give each type in the description.')),
                    $f('E01_02', 'konnektor', $n('Konnektor', 'Разъём', 'Connector'), 'multiselect', ['filter' => true, 'required' => true, 'options' => [...$opts([
                        ['RJ45', 'rj45'], ['RJ11', 'rj11'], ['IEC', 'iec'], ['F', 'f'],
                    ]), $other()]]),
                    $f('E01_02', 'kateqoriya', $n('RJ45 kateqoriyası', 'Категория RJ45', 'RJ45 category'), 'dropdown', ['when' => $when('kart-e01-02-konnektor', 'rj45'), 'options' => [...$opts([
                        ['Cat5e', 'cat5e'], ['Cat6', 'cat6'], ['Cat6A', 'cat6a'],
                    ]), $other()]]),
                    $f('E01_02', 'ekranlama', $n('Ekranlama', 'Экранирование', 'Shielding'), 'dropdown', ['options' => [...$opts([['Ekranlı', 'ekranli'], ['Ekransız', 'ekransiz']]), $na]]),
                    $wiringMount,
                    ...$look,
                    $series,
                    $kit('E01_02', $installKit),
                ]),
                'isiq-acarlari' => $card('E01_03', $n('İşıq açarları', 'Выключатели', 'Light switches'), $ps, [
                    $f('E01_03', 'funksiya', $n('Funksiya', 'Функция', 'Function'), 'dropdown', ['filter' => true, 'required' => true, 'options' => [...$opts([
                        ['Adi', 'adi'], ['Keçid', 'kecid'], ['Çarpaz', 'carpaz'], ['İmpuls düyməsi', 'impuls'], ['Jalüz idarəsi', 'jaluz'],
                    ]), $other()]]),
                    $f('E01_03', 'duyme', $n('Düymə sayı', 'Число клавиш', 'Gangs'), 'decimal', ['filter' => true, 'required' => true, 'unit' => 'ədəd', 'rules' => ['integer' => true]]),
                    $f('E01_03', 'idareetme', $n('İdarəetmə', 'Управление', 'Operation'), 'dropdown', ['filter' => true, 'options' => [...$opts([['Mexaniki', 'mexaniki'], ['Toxunma', 'toxunma'], ['Sensorlu', 'sensorlu']]), $other()]]),
                    $wiringMount,
                    ...$voltage(),
                    $current(false, $n('Yük növünə görə.', 'По типу нагрузки.', 'By load type.')),
                    $neutral(),
                    ...$look,
                    $series,
                    ...$smartBlock,
                    $kit('E01_03', $installKit),
                ]),
                'dimmerler' => $card('E01_04', $n('Dimmerlər', 'Диммеры', 'Dimmers'), $ps, [
                    $f('E01_04', 'idareetme', $n('İdarəetmə', 'Управление', 'Operation'), 'dropdown', ['filter' => true, 'required' => true, 'options' => [...$opts([
                        ['Döndərmə', 'dondurme'], ['Düymə', 'duyme'], ['Toxunma', 'toxunma'],
                    ]), $other()]]),
                    $f('E01_04', 'yuk', $n('Uyğun yük növü', 'Тип нагрузки', 'Load types'), 'multiselect', ['filter' => true, 'required' => true, 'options' => [...$opts([
                        ['Dimm olunan LED', 'led'], ['Közərmə', 'kozerme'], ['Halogen', 'halogen'], ['Uyğun transformator', 'transformator'],
                    ]), $other()]] + $hint('İstehsalçı məlumatına əsasən; LED və digər yüklərin limitləri eyni deyil.', 'По данным производителя; лимиты для LED и других нагрузок различаются.', 'Per the maker; LED and other load limits differ.')),
                    $f('E01_04', 'usul', $n('Dimm üsulu', 'Способ диммирования', 'Dimming method'), 'dropdown', ['filter' => true, 'required' => true, 'options' => [...$opts([
                        ['Faza kəsimi, ön cəbhə', 'on-cebhe'], ['Faza kəsimi, arxa cəbhə', 'arxa-cebhe'], ['DALI', 'dali'], ['0–10 V', '0-10'], ['1–10 V', '1-10'],
                    ]), $other()]]),
                    $f('E01_04', 'yuk-araligi', $n('Yük aralığı', 'Диапазон нагрузки', 'Load range'), 'text', ['required' => true]
                        + $hint('Hər yük növü üçün min–maks W və ya VA, məs. LED 3–100 W, közərmə 10–300 W.', 'Для каждого типа мин.–макс. Вт или ВА.', 'Min–max W or VA per load type.')),
                    ...$voltage(),
                    $neutral(true),
                    $wiringMount,
                    ...$look,
                    $series,
                    ...$smartBlock,
                    $kit('E01_04', [...$installKit, ['Bypass modulu', 'bypass']]),
                ]),
                'rozetka-ve-acar-cerciveleri' => $card('E01_05', $n('Rozetka və açar çərçivələri', 'Рамки для розеток и выключателей', 'Switch and socket frames'), ['piece'], [
                    $series,
                    $f('E01_05', 'post', $n('Post sayı', 'Число постов', 'Gangs'), 'decimal', ['filter' => true, 'required' => true, 'unit' => 'ədəd', 'rules' => ['integer' => true]]),
                    $f('E01_05', 'istiqamet', $n('İstiqamət', 'Ориентация', 'Orientation'), 'dropdown', ['filter' => true, 'options' => $opts([['Üfüqi', 'ufuqi'], ['Şaquli', 'saquli'], ['Universal', 'universal']])]),
                    ['required' => true] + $material,
                    ...$look,
                    $dims,
                    $ip,
                    $kit('E01_05', [['Çərçivə', 'cercive'], ['Ara hissə', 'ara'], ['Bərkitmə', 'berkitme']]),
                ]),
                'kabel-cixislari-ve-dekorativ-qapaqlar' => $card('E01_06', $n('Kabel çıxışları və dekorativ qapaqlar', 'Выводы кабеля и заглушки', 'Cable outlets and blank plates'), ['piece'], [
                    $f('E01_06', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['filter' => true, 'required' => true, 'options' => $opts([['Kabel çıxışı', 'kabel'], ['Boşluq qapağı', 'qapaq'], ['Dekorativ örtük', 'ortuk']])]),
                    $series,
                    $wiringMount,
                    ['required' => true] + $material,
                    ...$look,
                    $dims,
                    $f('E01_06', 'kecid', $n('Kabel keçidi', 'Кабельный проход', 'Cable passage'), 'text', $hint('Dəlik diametri və ya en × hündürlük, mm.', 'Диаметр отверстия или ширина × высота, мм.', 'Hole diameter or width × height, mm.')),
                    $kit('E01_06', $installKit),
                ]),
            ],
        ],

        // ════ 2. İşıqlandırma ══════════════════════════════════════════════
        [
            'slug' => 'isiqlandirma',
            'name' => $n('İşıqlandırma', 'Освещение', 'Lighting'),
            'classes' => [
                'cilciraqlar-ve-asma-isiqlandiricilar' => $card('E02_01', $n('Çilçıraqlar və asma işıqlandırıcılar', 'Люстры и подвесные светильники', 'Chandeliers and pendants'), $ps, [
                    ...$lightCore([$f('E02_01', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['filter' => true, 'required' => true, 'options' => $opts([
                        ['Çilçıraq', 'cilciraq'], ['Tək asma', 'tek'], ['Çoxlu asma', 'coxlu'], ['Xətti asma', 'xetti'],
                    ])])]),
                    $f('E02_01', 'asqi-min', $n('Asqı hündürlüyü, min', 'Высота подвеса, мин.', 'Suspension height, min'), 'decimal', ['unit' => 'mm', 'rules' => ['lte' => 'kart-e02-01-asqi-maks']]),
                    $f('E02_01', 'asqi-maks', $n('Asqı hündürlüyü, maks', 'Высота подвеса, макс.', 'Suspension height, max'), 'decimal', ['unit' => 'mm']),
                    $kit('E02_01', [['Lampa', 'lampa'], ['LED modul', 'led'], ['Drayver', 'drayver'], ['Asqı', 'asqi'], ['Bərkitmə', 'berkitme']]),
                ]),
                'isiqlandirma-cihazi-led-iceriqurasdirilanustequrasdirilantrek' => $card('E02_02', $n('Tavan işıqlandırıcıları', 'Потолочные светильники', 'Ceiling lights'), $ps, [
                    ...$lightCore([
                        $f('E02_02', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['filter' => true, 'required' => true, 'options' => [...$opts([['Plafon', 'plafon'], ['LED panel', 'panel'], ['Xətti', 'xetti']]), $other()]]),
                        $f('E02_02', 'montaj', $n('Montaj', 'Монтаж', 'Mounting'), 'dropdown', ['filter' => true, 'required' => true, 'options' => $opts([['Səthüstü', 'sethustu'], ['Gömülən', 'gomulen'], ['Asma', 'asma']])]),
                        ['when' => $when('kart-e02-02-montaj', 'gomulen')] + $cutout,
                    ]),
                    $kit('E02_02', [['Lampa', 'lampa'], ['LED modul', 'led'], ['Drayver', 'drayver'], ['Bərkitmə', 'berkitme']]),
                ]),
                'divar-isiqlandiricilari-ve-bralar' => $card('E02_03', $n('Divar işıqlandırıcıları və bralar', 'Настенные светильники и бра', 'Wall lights and sconces'), $ps, [
                    ...$lightCore([$f('E02_03', 'teyinat', $n('Təyinat', 'Назначение', 'Purpose'), 'dropdown', ['filter' => true, 'options' => [...$opts([
                        ['Ümumi', 'umumi'], ['Oxu', 'oxu'], ['Güzgü', 'guzgu'], ['Dekorativ', 'dekorativ'],
                    ]), $other()]])]),
                    $f('E02_03', 'istiqamet', $n('İşıq istiqaməti tənzimlənir', 'Регулируемое направление света', 'Adjustable direction'), 'dropdown', ['options' => $ynn]),
                    $kit('E02_03', [['Lampa', 'lampa'], ['LED modul', 'led'], ['Drayver', 'drayver'], ['Bərkitmə', 'berkitme']]),
                ]),
                'spot-isiqlandiricilar' => $card('E02_04', $n('Spot işıqlandırıcılar', 'Точечные светильники', 'Spotlights'), $ps, [
                    ...$lightCore([$f('E02_04', 'montaj', $n('Montaj', 'Монтаж', 'Mounting'), 'dropdown', ['filter' => true, 'required' => true, 'options' => $opts([['Gömülən', 'gomulen'], ['Səthüstü', 'sethustu']])]),
                        ['when' => $when('kart-e02-04-montaj', 'gomulen')] + $cutout,
                    ]),
                    $beam,
                    $f('E02_04', 'istiqamet', $n('İstiqaməti tənzimlənir', 'Поворотный', 'Adjustable'), 'dropdown', ['options' => $ynn]),
                    $kit('E02_04', [['Lampa', 'lampa'], ['LED modul', 'led'], ['Drayver', 'drayver'], ['Bərkitmə', 'berkitme']]),
                ]),
                'trek-isiqlandiricilari' => $card('E02_05', $n('Trek işıqlandırıcıları', 'Трековые светильники', 'Track lights'), $ps, [
                    ...$lightCore([
                        $f('E02_05', 'sistem', $n('Trek sistemi', 'Трековая система', 'Track system'), 'dropdown', ['filter' => true, 'required' => true, 'options' => [...$opts([
                            ['Birfazalı', 'birfazali'], ['Üçfazalı', 'ucfazali'], ['Maqnitli', 'maqnitli'],
                        ]), $other()]] + $hint('Eyni gərginlik trek sistemlərinin uyğunluğunu təsdiqləmir.', 'Одинаковое напряжение не гарантирует совместимость треков.', 'The same voltage alone does not make tracks compatible.')),
                        $compatModel(true),
                        $f('E02_05', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['filter' => true, 'options' => [...$opts([['Spot', 'spot'], ['Xətti', 'xetti'], ['Asma', 'asma']]), $other()]]),
                    ]),
                    $beam,
                    $kit('E02_05', [['Trek adapteri', 'adapter'], ['Lampa', 'lampa'], ['LED modul', 'led'], ['Drayver', 'drayver']]),
                ]),
                'trek-relsleri' => $card('E02_06', $n('Trek relsləri', 'Трековые шинопроводы', 'Track rails'), ['piece', 'lm'], [
                    $f('E02_05', 'sistem', $n('Trek sistemi', 'Трековая система', 'Track system'), 'dropdown', ['filter' => true, 'required' => true, 'options' => [...$opts([
                        ['Birfazalı', 'birfazali'], ['Üçfazalı', 'ucfazali'], ['Maqnitli', 'maqnitli'],
                    ]), $other()]]),
                    $compatModel(),
                    ...$voltage(),
                    $f('E02_06', 'montaj', $n('Montaj', 'Монтаж', 'Mounting'), 'dropdown', ['filter' => true, 'required' => true, 'options' => $opts([['Səthüstü', 'sethustu'], ['Gömülən', 'gomulen'], ['Asma', 'asma']])]),
                    $f('E02_06', 'uzunluq', $n('Uzunluq', 'Длина', 'Length'), 'decimal', ['filter' => true, 'required' => true, 'unit' => 'm']),
                    $f('E02_06', 'en', $n('En', 'Ширина', 'Width'), 'decimal', ['unit' => 'mm']),
                    $f('E02_06', 'hundurluk', $n('Hündürlük', 'Высота', 'Height'), 'decimal', ['unit' => 'mm']),
                    ...$look,
                    $f('E02_06', 'cereyan', $n('Maksimum cərəyan', 'Максимальный ток', 'Maximum current'), 'decimal', ['unit' => 'A']),
                    $kit('E02_06', [['Qidalanma girişi', 'giris'], ['Birləşdirici', 'birlesdirici'], ['Sonluq', 'sonluq'], ['Asqı', 'asqi'], ['Bərkitmə', 'berkitme']]),
                ]),
                'torserler' => $card('E02_07', $n('Torşerlər', 'Торшеры', 'Floor lamps'), ['piece'], [
                    ...$lightCore(),
                    $f('E02_07', 'acar', $n('Açarın yeri', 'Расположение выключателя', 'Switch position'), 'dropdown', ['options' => [...$opts([['Korpusda', 'korpus'], ['Naqildə', 'naqil'], ['Pultda', 'pult']]), $other()]]),
                    $kit('E02_07', [['Lampa', 'lampa'], ['LED modul', 'led'], ['Adapter', 'adapter'], ['Pult', 'pult']]),
                ]),
                'masa-lampalari' => $card('E02_08', $n('Masa lampaları', 'Настольные лампы', 'Table lamps'), ['piece'], [
                    ...$lightCore(),
                    $f('E02_08', 'qida', $n('Qidalanma variantı', 'Вариант питания', 'Power option'), 'dropdown', ['filter' => true, 'options' => $opts([
                        ['Şəbəkə', 'sebeke'], ['USB', 'usb'], ['Akkumulyator', 'akkumulyator'], ['Batareya', 'batareya'],
                    ])]),
                    $f('E02_08', 'qida-qeyd', $n('Port və ya batareya tipi', 'Порт или тип батареи', 'Port or battery type'), 'text', ['when' => $when('kart-e02-08-qida', 'usb', 'akkumulyator', 'batareya')]),
                    $kit('E02_08', [['Lampa', 'lampa'], ['LED modul', 'led'], ['Adapter', 'adapter'], ['Şarj kabeli', 'kabel']]),
                ]),
                'led-lentler' => $card('E02_09', $n('LED lentlər', 'LED-ленты', 'LED strips'), ['lm', 'roll', 'set'], [
                    $f('E02_09', 'nov', $n('LED növü', 'Тип LED', 'LED type'), 'dropdown', ['filter' => true, 'options' => [...$opts([['SMD', 'smd'], ['COB', 'cob']]), $other()]]),
                    ...$voltage(),
                    $wattage($n('W/m.', 'Вт/м.', 'W/m.')),
                    $f('E02_09', 'uzunluq', $n('Satış uzunluğu', 'Длина в продаже', 'Length sold'), 'decimal', ['required' => true, 'unit' => 'm']),
                    $f('E02_09', 'en', $n('En', 'Ширина', 'Width'), 'decimal', ['required' => true, 'unit' => 'mm']),
                    $f('E02_09', 'qalinliq', $n('Qalınlıq', 'Толщина', 'Thickness'), 'decimal', ['required' => true, 'unit' => 'mm']),
                    $lightColor,
                    ...$kelvin,
                    $f('E02_09', 'axin', $n('İşıq axını', 'Световой поток', 'Luminous flux'), 'decimal', ['unit' => 'lm/m']),
                    $cri,
                    $ip,
                    $f('E02_09', 'kesim', $n('Kəsim addımı', 'Шаг резки', 'Cut step'), 'decimal', ['unit' => 'mm']),
                    $f('E02_09', 'maks-uzunluq', $n('Bir qidalanma nöqtəsindən maksimum uzunluq', 'Макс. длина от одной точки питания', 'Maximum run from one feed'), 'decimal', ['unit' => 'm']),
                    $f('E02_09', 'idareetme', $n('İdarəetmə və qoşulma', 'Управление и подключение', 'Control and connection'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Tək rəng', 'tek'], ['CCT', 'cct'], ['RGB', 'rgb'], ['RGBW', 'rgbw'], ['RGB+CCT', 'rgb-cct'], ['Ünvanlanan', 'unvanlanan'],
                    ])]),
                    $f('E02_09', 'kontakt', $n('Kontakt sayı və protokol', 'Число контактов и протокол', 'Pins and protocol'), 'text',
                        $hint('Kontakt sayı; ünvanlananda çip / protokol (məs. WS2812B).', 'Число контактов; для адресной — чип / протокол.', 'Pin count; for addressable, the chip / protocol.')),
                    $kit('E02_09', [['Lent', 'lent'], ['Qida bloku', 'qida'], ['Kontroller', 'kontroller'], ['Konnektor', 'konnektor']]),
                ]),
                'lampalar' => $card('E02_10', $n('Lampalar', 'Лампы', 'Light bulbs'), ['piece', 'pack'], [
                    $f('E02_10', 'texnologiya', $n('Texnologiya', 'Технология', 'Technology'), 'dropdown', ['filter' => true, 'required' => true, 'options' => [...$opts([
                        ['LED', 'led'], ['Halogen', 'halogen'], ['Közərmə', 'kozerme'],
                    ]), $other()]] + $hint('Ağıllı lampa da bu kartda yerləşdirilir.', 'Умная лампа тоже размещается здесь.', 'Smart bulbs are listed here too.')),
                    ['required' => true, 'when' => null, 'filter' => true] + $source[1],
                    $wattage($n('Faktiki elektrik gücü.', 'Фактическая потребляемая мощность.', 'Actual power draw.')),
                    ...$voltage(),
                    $f('E02_10', 'axin', $n('İşıq axını', 'Световой поток', 'Luminous flux'), 'decimal', ['unit' => 'lm']),
                    $lightColor,
                    ...$kelvin,
                    $cri,
                    $f('E02_10', 'forma', $n('Forma', 'Форма', 'Shape'), 'dropdown', ['filter' => true, 'options' => [...$opts([['Şar', 'sar'], ['Şam', 'sam'], ['Reflektor', 'reflektor'], ['Boru', 'boru']]), $other()]]),
                    $f('E02_10', 'olcu', $n('Diametr × uzunluq', 'Диаметр × длина', 'Diameter × length'), 'text', $hint('mm, məs. 60 × 108.', 'мм, напр. 60 × 108.', 'mm, e.g. 60 × 108.')),
                    $dimming,
                    ...$smartBlock,
                    $packCount,
                ]),
                'led-profilleri-ve-diffuzorlar' => $card('E02_11', $n('LED profilləri və diffuzorlar', 'Профили и рассеиватели для LED', 'LED profiles and diffusers'), ['lm', 'piece', 'set'], [
                    $f('E02_11', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['filter' => true, 'required' => true, 'options' => $opts([['Profil', 'profil'], ['Diffuzor', 'diffuzor'], ['Profil dəsti', 'dest']])]),
                    $f('E02_11', 'montaj', $n('Montaj', 'Монтаж', 'Mounting'), 'dropdown', ['filter' => true, 'options' => [...$opts([
                        ['Səthüstü', 'sethustu'], ['Gömülən', 'gomulen'], ['Künc', 'kunc'], ['Asma', 'asma'],
                    ]), $other()]]),
                    ['required' => true] + $material,
                    ...$look,
                    $f('E02_11', 'uzunluq', $n('Uzunluq', 'Длина', 'Length'), 'decimal', ['required' => true, 'unit' => 'm']),
                    $f('E02_11', 'en', $n('En', 'Ширина', 'Width'), 'decimal', ['required' => true, 'unit' => 'mm']),
                    $f('E02_11', 'hundurluk', $n('Hündürlük', 'Высота', 'Height'), 'decimal', ['required' => true, 'unit' => 'mm']),
                    $f('E02_11', 'lent-eni', $n('Uyğun LED lent eni, maks', 'Макс. ширина LED-ленты', 'Maximum LED strip width'), 'decimal', ['required' => true, 'unit' => 'mm']),
                    $f('E02_11', 'diffuzor', $n('Diffuzor', 'Рассеиватель', 'Diffuser'), 'dropdown', ['filter' => true, 'options' => $opts([['Şəffaf', 'seffaf'], ['Mat', 'mat'], ['Opal', 'opal'], ['Daxil deyil', 'daxil-deyil']])]),
                    $compatModel(),
                    $kit('E02_11', [['Profil', 'profil'], ['Diffuzor', 'diffuzor'], ['Sonluq', 'sonluq'], ['Bərkitmə', 'berkitme']]),
                ]),
                'led-qida-bloklari-ve-drayverler' => $card('E02_12', $n('LED qida blokları və drayverlər', 'Блоки питания и драйверы для LED', 'LED power supplies and drivers'), ['piece'], [
                    $f('E02_12', 'cixis-novu', $n('Çıxış növü', 'Тип выхода', 'Output type'), 'dropdown', ['filter' => true, 'required' => true, 'options' => $opts([
                        ['Sabit gərginlik (CV)', 'cv'], ['Sabit cərəyan (CC)', 'cc'],
                    ])]),
                    $f('E02_12', 'giris', $n('Giriş', 'Вход', 'Input'), 'text', ['required' => true] + $hint('Gərginlik V və AC/DC; AC üçün tezlik Hz. Məs. 220–240 V AC, 50/60 Hz.', 'Напряжение и AC/DC; для AC — частота.', 'Voltage and AC/DC; for AC the frequency.')),
                    $f('E02_12', 'cixis', $n('Çıxış', 'Выход', 'Output'), 'text', ['required' => true] + $hint('CV: V və maksimum A. CC: mA və V aralığı.', 'CV: В и макс. А. CC: мА и диапазон В.', 'CV: V and max A. CC: mA and the V range.')),
                    $wattage($n('Nominal güc.', 'Номинальная мощность.', 'Rated power.')),
                    ['filter' => true] + $dimming,
                    $ip,
                    $dims,
                    $f('E02_12', 'montaj', $n('Montaj', 'Монтаж', 'Mounting'), 'dropdown', ['filter' => true, 'options' => [...$opts([['Səthüstü', 'sethustu'], ['Gizli yerləşdirmə', 'gizli'], ['Trekə uyğun', 'trek']]), $other()]]),
                    $compatModel(),
                    $kit('E02_12', [['Drayver', 'drayver'], ['Qoşulma kabeli', 'kabel'], ['Bərkitmə', 'berkitme']]),
                ]),
                'isiqlandirma-komplektlesdiricileri' => $card('E02_13', $n('İşıqlandırma komplektləşdiriciləri', 'Комплектующие для освещения', 'Lighting accessories'), ['piece', 'set', 'pack'], [
                    $f('E02_13', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['filter' => true, 'required' => true, 'options' => [...$opts([
                        ['Trek birləşdiricisi', 'trek-birlesdirici'], ['Trek qidalanma girişi', 'trek-giris'], ['Sonluq', 'sonluq'], ['Asqı', 'asqi'],
                        ['LED konnektoru', 'led-konnektor'], ['Lampa patronu', 'patron'],
                    ]), $other()]]),
                    $compatModel(),
                    $f('E02_13', 'olcu', $n('Birləşmə ölçüsü', 'Присоединительный размер', 'Connection size'), 'text', ['required' => true]
                        + $hint('Ölçü, kontakt sayı və ya soket; hissənin növünə görə.', 'Размер, число контактов или цоколь.', 'Size, pin count or socket, as the part needs.')),
                    $f('E02_13', 'elektrik', $n('Elektrik göstəriciləri', 'Электрические параметры', 'Electrical ratings'), 'text',
                        $hint('Gərginlik V, AC/DC və cərəyan A; elektrik keçirən hissələr üçün.', 'Напряжение, AC/DC и ток; для токоведущих деталей.', 'Voltage, AC/DC and current; for live parts.')),
                    ['required' => true] + $material,
                    ...$look,
                    $packCount,
                    $kit('E02_13', [['Hissə', 'hisse'], ['Bərkitmə', 'berkitme'], ['Kabel', 'kabel']]),
                ]),
                'led-lent-kontrollerleri' => $card('E02_14', $n('LED lent kontrollerləri', 'Контроллеры LED-лент', 'LED strip controllers'), $ps, [
                    $f('E02_14', 'lent', $n('İdarə olunan lent', 'Тип ленты', 'Strip type'), 'dropdown', ['filter' => true, 'required' => true, 'options' => $opts([
                        ['Tək rəng', 'tek'], ['CCT', 'cct'], ['RGB', 'rgb'], ['RGBW', 'rgbw'], ['RGB+CCT', 'rgb-cct'], ['Ünvanlanan', 'unvanlanan'],
                    ])]),
                    ...$voltage(),
                    $f('E02_14', 'yuk', $n('Çıxış yükü', 'Выходная нагрузка', 'Output load'), 'text', ['required' => true]
                        + $hint('Hər kanal və ümumi maksimum A; gərginliyə görə W.', 'Макс. А на канал и общий; Вт по напряжению.', 'Max A per channel and total; W by voltage.')),
                    $f('E02_14', 'kanal', $n('Kanal sayı', 'Число каналов', 'Channels'), 'decimal', ['required' => true, 'unit' => 'ədəd', 'rules' => ['integer' => true]]),
                    $f('E02_14', 'uygunluq', $n('Lent uyğunluğu', 'Совместимость с лентой', 'Strip compatibility'), 'text', $hint('Kontakt sayı; ünvanlanan lentdə çip / protokol.', 'Число контактов; для адресной — чип / протокол.', 'Pin count; for addressable, the chip / protocol.')),
                    $f('E02_14', 'idareetme', $n('İdarəetmə', 'Управление', 'Control'), 'multiselect', ['filter' => true, 'options' => $opts([['Düymə', 'duyme'], ['Pult', 'pult'], ['Tətbiq', 'tetbiq'], ['Avtomatika', 'avtomatika']])]),
                    ...$smartBlock,
                    $ip,
                    $dims,
                    $kit('E02_14', [['Kontroller', 'kontroller'], ['Pult', 'pult'], ['Qida bloku', 'qida']]),
                ]),
            ],
        ],

        // ════ 3. İnternet, rabitə və təhlükəsizlik ═════════════════════════
        [
            'slug' => 'internet-rabite-ve-tehlukesizlik',
            'name' => $n('İnternet, rabitə və təhlükəsizlik', 'Интернет, связь и безопасность', 'Internet, communications and security'),
            'classes' => [
                'routerler-ve-mesh-sistemleri' => $card('E03_01', $n('Routerlər və Mesh sistemləri', 'Роутеры и Mesh-системы', 'Routers and mesh systems'), $ps, [
                    $f('E03_01', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['filter' => true, 'required' => true, 'options' => $opts([['Router', 'router'], ['Mobil internet routeri', 'mobil'], ['Mesh sistemi', 'mesh']])]),
                    $f('E03_01', 'giris', $n('İnternet girişi', 'Вход интернета', 'Internet input'), 'multiselect', ['filter' => true, 'required' => true, 'options' => [...$opts([
                        ['Ethernet WAN', 'wan'], ['SIM', 'sim'], ['DSL', 'dsl'], ['Optik', 'optik'],
                    ]), $other()]]),
                    ...$wifi,
                    $ports,
                    $f('E03_01', 'sinif', $n('Wi-Fi sürət sinfi', 'Класс скорости Wi-Fi', 'Wi-Fi speed class'), 'text', $hint('İstehsalçının göstərdiyi sinif, məs. AX3000; internet sürəti deyil.', 'Класс производителя, напр. AX3000; это не скорость интернета.', 'The maker’s class, e.g. AX3000; not the internet speed.')),
                    $f('E03_01', 'mesh', $n('Mesh uyğunluğu', 'Поддержка Mesh', 'Mesh support'), 'dropdown', ['filter' => true, 'options' => $opts([['Yoxdur', 'yoxdur'], ['Var', 'var']])]),
                    $f('E03_01', 'mesh-sistem', $n('Mesh sistemi və modelləri', 'Mesh-система и модели', 'Mesh system and models'), 'text', ['when' => $when('kart-e03-01-mesh', 'var')]),
                    $f('E03_01', 'say', $n('Dəstdə cihaz sayı', 'Устройств в комплекте', 'Units in the set'), 'decimal', ['unit' => 'ədəd', 'rules' => ['integer' => true]]),
                    ...$supply,
                    $kit('E03_01', [['Router', 'router'], ['Mesh modulu', 'mesh'], ['Adapter', 'adapter'], ['Kabel', 'kabel']]),
                    ...$guarantee,
                ]),
                'wi-fi-giris-noqteleri' => $card('E03_02', $n('Wi-Fi giriş nöqtələri', 'Точки доступа Wi-Fi', 'Wi-Fi access points'), $ps, [
                    ...$wifi,
                    $ports,
                    $f('E03_02', 'montaj', $n('Montaj', 'Монтаж', 'Mounting'), 'dropdown', ['filter' => true, 'required' => true, 'options' => $opts([['Tavan', 'tavan'], ['Divar', 'divar'], ['Masaüstü', 'masaustu'], ['Xarici mühit', 'xarici']])]),
                    ...$poe,
                    ...$supply,
                    $f('E03_02', 'kontroller', $n('Kontroller tələbi', 'Требуется контроллер', 'Controller needed'), 'dropdown', ['options' => $opts([
                        ['Müstəqil işləyir', 'musteqil'], ['Kontroller tələb edir', 'teleb'], ['Hər iki rejim', 'her-iki'],
                    ])]),
                    $f('E03_02', 'kontroller-sistem', $n('Uyğun kontroller sistemi', 'Совместимая система контроллера', 'Compatible controller system'), 'text', ['when' => $when('kart-e03-02-kontroller', 'teleb', 'her-iki')]),
                    $ip,
                    $dims,
                    $kit('E03_02', [['Giriş nöqtəsi', 'cihaz'], ['Adapter', 'adapter'], ['PoE injektoru', 'injektor'], ['Bərkitmə', 'berkitme']]),
                    ...$guarantee,
                ]),
                'sebeke-kommutatorlari' => $card('E03_03', $n('Şəbəkə kommutatorları', 'Сетевые коммутаторы', 'Network switches'), $ps, [
                    $ports,
                    $f('E03_03', 'idareetme', $n('İdarəetmə', 'Управление', 'Management'), 'dropdown', ['filter' => true, 'required' => true, 'options' => $opts([
                        ['İdarə olunmayan', 'idare-olunmayan'], ['Sadə idarə olunan', 'sade'], ['İdarə olunan', 'idare-olunan'],
                    ])]),
                    ...$poe,
                    $f('E03_03', 'poe-port', $n('PoE port sayı', 'Портов PoE', 'PoE ports'), 'decimal', ['unit' => 'ədəd', 'rules' => ['integer' => true], 'when' => $when('kart-el-poe', 'af', 'at', 'bt', 'passiv')]),
                    $f('E03_03', 'poe-budce', $n('PoE büdcəsi, ümumi', 'Бюджет PoE, общий', 'PoE budget, total'), 'decimal', ['unit' => 'W', 'when' => $when('kart-el-poe', 'af', 'at', 'bt', 'passiv')]),
                    $f('E03_03', 'poe-port-maks', $n('PoE, bir port üçün maks', 'PoE, макс. на порт', 'PoE, max per port'), 'decimal', ['unit' => 'W', 'when' => $when('kart-el-poe', 'af', 'at', 'bt', 'passiv')]),
                    $f('E03_03', 'montaj', $n('Montaj', 'Монтаж', 'Mounting'), 'dropdown', ['filter' => true, 'options' => $opts([['Masaüstü', 'masaustu'], ['Divar', 'divar'], ['Rack', 'rack']])]),
                    ...$supply,
                    $dims,
                    $kit('E03_03', [['Kommutator', 'kommutator'], ['Adapter', 'adapter'], ['Rack bərkitməsi', 'rack']]),
                    ...$guarantee,
                ]),
                'tv-antenalari-ve-qebulediciler' => $card('E03_04', $n('TV antenaları və qəbuledicilər', 'ТВ-антенны и ресиверы', 'TV aerials and receivers'), $ps, [
                    $f('E03_04', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['filter' => true, 'required' => true, 'options' => $opts([
                        ['Yerüstü TV antenası', 'yerustu'], ['Peyk antenası', 'peyk'], ['LNB', 'lnb'], ['TV qəbuledicisi', 'qebuledici'],
                    ])]),
                    $f('E03_04', 'standart', $n('Siqnal standartı', 'Стандарт сигнала', 'Signal standard'), 'multiselect', ['filter' => true, 'required' => true, 'options' => [...$opts([
                        ['DVB-T/T2', 'dvb-t'], ['DVB-S/S2', 'dvb-s'], ['DVB-C', 'dvb-c'],
                    ]), $other()]]),
                    $f('E03_04', 'tezlik', $n('Tezlik diapazonu', 'Частотный диапазон', 'Frequency range'), 'text', $hint('MHz və ya GHz; cihazın növünə görə.', 'МГц или ГГц.', 'MHz or GHz.')),
                    $f('E03_04', 'giris-cixis', $n('Giriş və çıxışlar', 'Входы и выходы', 'Inputs and outputs'), 'text', $hint('RF / F / HDMI / USB və sayı.', 'RF / F / HDMI / USB и количество.', 'RF / F / HDMI / USB and count.')),
                    $f('E03_04', 'olcu', $n('Ölçü', 'Размер', 'Size'), 'text', $hint('Antenada diametr / ölçü; qəbuledicidə qabarit, mm.', 'Для антенны — диаметр; для ресивера — габариты, мм.', 'Aerial diameter; receiver size, mm.')),
                    $f('E03_04', 'qida', $n('Qidalanma', 'Питание', 'Power'), 'dropdown', ['filter' => true, 'options' => $opts([['Passiv', 'passiv'], ['Şəbəkə', 'sebeke'], ['Adapter', 'adapter'], ['Kabeldən', 'kabel']])]),
                    $f('E03_04', 'qida-v', $n('Tələb olunan gərginlik', 'Требуемое напряжение', 'Voltage required'), 'decimal', ['unit' => 'V', 'when' => $when('kart-e03-04-qida', 'sebeke', 'adapter', 'kabel')]),
                    $kit('E03_04', [['Antena', 'antena'], ['Qəbuledici', 'qebuledici'], ['LNB', 'lnb'], ['Pult', 'pult'], ['Adapter', 'adapter'], ['Bərkitmə', 'berkitme']]),
                    ...$guarantee,
                ]),
                'tv-siqnal-paylayicilari-ve-guclendiricileri' => $card('E03_05', $n('TV siqnal paylayıcıları və gücləndiriciləri', 'ТВ-сплиттеры и усилители', 'TV splitters and amplifiers'), $ps, [
                    $f('E03_05', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['filter' => true, 'required' => true, 'options' => $opts([['Splitter', 'splitter'], ['Tap', 'tap'], ['Multiswitch', 'multiswitch'], ['Gücləndirici', 'guclendirici']])]),
                    $f('E03_05', 'giris', $n('Giriş sayı', 'Число входов', 'Inputs'), 'decimal', ['required' => true, 'unit' => 'ədəd', 'rules' => ['integer' => true]]),
                    $f('E03_05', 'cixis', $n('Çıxış sayı', 'Число выходов', 'Outputs'), 'decimal', ['filter' => true, 'required' => true, 'unit' => 'ədəd', 'rules' => ['integer' => true]]),
                    $f('E03_05', 'tezlik-min', $n('Tezlik diapazonu, min', 'Частота, мин.', 'Frequency, min'), 'decimal', ['required' => true, 'unit' => 'MHz', 'rules' => ['lte' => 'kart-e03-05-tezlik-maks']]),
                    $f('E03_05', 'tezlik-maks', $n('Tezlik diapazonu, maks', 'Частота, макс.', 'Frequency, max'), 'decimal', ['required' => true, 'unit' => 'MHz']),
                    $f('E03_05', 'konnektor', $n('Konnektor', 'Разъём', 'Connector'), 'dropdown', ['filter' => true, 'options' => [...$opts([['F', 'f'], ['IEC', 'iec']]), $other()]]),
                    $f('E03_05', 'siqnal', $n('Siqnal göstəricisi', 'Затухание / усиление', 'Loss / gain'), 'text', $hint('Zəifləmə və ya gücləndirmə, dB; port və diapazon üzrə.', 'Затухание или усиление, дБ.', 'Loss or gain, dB, by port and band.')),
                    $f('E03_05', 'qida', $n('Qidalanma', 'Питание', 'Power'), 'dropdown', ['options' => $opts([['Passiv', 'passiv'], ['Adapter', 'adapter'], ['Kabeldən', 'kabel']])]),
                    $f('E03_05', 'qida-v', $n('Tələb olunan gərginlik', 'Требуемое напряжение', 'Voltage required'), 'decimal', ['unit' => 'V', 'when' => $when('kart-e03-05-qida', 'adapter', 'kabel')]),
                    $f('E03_05', 'dc', $n('DC keçidi', 'Пропуск DC', 'DC pass'), 'dropdown', ['options' => $ynn]),
                    $kit('E03_05', [['Paylayıcı', 'paylayici'], ['Gücləndirici', 'guclendirici'], ['Adapter', 'adapter']]),
                ]),
                'videomusahide-cctv' => $card('E03_06', $n('Müşahidə kameraları', 'Камеры видеонаблюдения', 'Security cameras'), $ps, [
                    $f('E03_06', 'sistem', $n('Sistem', 'Система', 'System'), 'dropdown', ['filter' => true, 'required' => true, 'options' => $opts([['IP', 'ip'], ['Analoq', 'analoq']])]),
                    $f('E03_06', 'format', $n('Analoq format', 'Аналоговый формат', 'Analogue format'), 'text', ['when' => $when('kart-e03-06-sistem', 'analoq')] + $hint('Məs. AHD, TVI, CVI.', 'Напр. AHD, TVI, CVI.', 'E.g. AHD, TVI, CVI.')),
                    $f('E03_06', 'korpus', $n('Korpus və montaj', 'Корпус и монтаж', 'Housing'), 'dropdown', ['filter' => true, 'options' => [...$opts([['Dome', 'dome'], ['Bullet', 'bullet'], ['PTZ', 'ptz'], ['Masaüstü', 'masaustu']]), $other()]]),
                    $f('E03_06', 'mp', $n('Görüntü ölçüsü', 'Разрешение', 'Resolution'), 'decimal', ['filter' => true, 'required' => true, 'unit' => 'MP']),
                    $f('E03_06', 'obyektiv', $n('Obyektiv', 'Объектив', 'Lens'), 'dropdown', ['options' => $opts([['Sabit', 'sabit'], ['Varifokal', 'varifokal']])]),
                    $f('E03_06', 'fokus', $n('Fokus məsafəsi', 'Фокусное расстояние', 'Focal length'), 'text', $hint('mm; varifokalda aralıq, məs. 2,8–12.', 'мм; для варифокального — диапазон.', 'mm; a range for varifocal.')),
                    $f('E03_06', 'bucaq', $n('Baxış bucağı', 'Угол обзора', 'Field of view'), 'decimal', ['unit' => '°']),
                    $f('E03_06', 'gece', $n('Gecə görüntüsü', 'Ночная съёмка', 'Night vision'), 'dropdown', ['options' => $opts([['IR', 'ir'], ['Rəngli', 'rengli'], ['Hər ikisi', 'her-iki'], ['Yoxdur', 'yoxdur']])]),
                    $f('E03_06', 'gece-mesafe', $n('Gecə görüntüsü məsafəsi', 'Дальность ночной съёмки', 'Night vision range'), 'decimal', ['unit' => 'm', 'when' => $when('kart-e03-06-gece', 'ir', 'rengli', 'her-iki')]),
                    $comm,
                    ...$supply,
                    ...$poe,
                    $ip,
                    $f('E03_06', 'yaddas', $n('Yaddaş', 'Хранение', 'Storage'), 'multiselect', ['options' => [$noneX(), ...$opts([['microSD', 'microsd'], ['Registrator', 'registrator'], ['Bulud', 'bulud']])]]),
                    $f('E03_06', 'kart', $n('Maksimum kart həcmi', 'Макс. объём карты', 'Maximum card size'), 'decimal', ['unit' => 'GB', 'when' => $when('kart-e03-06-yaddas', 'microsd')]),
                    $compatSystem(false, $n('Uyğun registrator / protokol / profil və tətbiq; bulud abunəsi tələb olunursa yazın.', 'Регистратор / протокол / профиль и приложение; укажите подписку, если нужна.', 'Recorder / protocol / profile and app; note any cloud subscription.')),
                    $f('E03_06', 'ses', $n('Səs', 'Звук', 'Audio'), 'dropdown', ['options' => $opts([['Yoxdur', 'yoxdur'], ['Mikrofon', 'mikrofon'], ['İkitərəfli səs', 'ikiterefli']])]),
                    ...$smart,
                    $kit('E03_06', [['Kamera', 'kamera'], ['Adapter', 'adapter'], ['Bərkitmə', 'berkitme']]),
                    ...$guarantee,
                ]),
                'videoregistratorlar' => $card('E03_07', $n('Videoregistratorlar', 'Видеорегистраторы', 'Video recorders'), $ps, [
                    $f('E03_07', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['filter' => true, 'required' => true, 'options' => $opts([['NVR', 'nvr'], ['DVR', 'dvr'], ['Hibrid', 'hibrid']])]),
                    $f('E03_07', 'kanal', $n('Kamera kanalları', 'Каналы камер', 'Camera channels'), 'decimal', ['filter' => true, 'required' => true, 'unit' => 'ədəd', 'rules' => ['integer' => true]]
                        + $hint('Hibriddə analoq və IP-ni təsvirdə ayrıca yazın.', 'Для гибрида — аналог и IP отдельно в описании.', 'For a hybrid, give analogue and IP separately in the description.')),
                    $f('E03_07', 'format', $n('Uyğun kamera formatı', 'Совместимый формат камер', 'Compatible camera format'), 'text', ['required' => true] + $hint('Protokol / profil / siqnal formatı, məs. ONVIF, AHD.', 'Протокол / профиль / формат сигнала.', 'Protocol / profile / signal format.')),
                    $f('E03_07', 'qeyd', $n('Qeyd imkanları', 'Запись', 'Recording'), 'text', ['required' => true] + $hint('Maksimum MP və həmin ölçüdə kadr/s; giriş bant genişliyi Mbit/s.', 'Макс. МП и кадр/с; входящая полоса Мбит/с.', 'Max MP and fps at it; incoming bandwidth Mbit/s.')),
                    $f('E03_07', 'disk-say', $n('Disk yuvaları', 'Отсеки для дисков', 'Drive bays'), 'decimal', ['unit' => 'ədəd', 'rules' => ['integer' => true]]),
                    $f('E03_07', 'disk-tb', $n('Bir disk üçün maks', 'Макс. на один диск', 'Maximum per drive'), 'decimal', ['unit' => 'TB']),
                    $f('E03_07', 'disk-daxil', $n('Disk daxildir', 'Диск в комплекте', 'Drive included'), 'dropdown', ['options' => $ynn]),
                    $ports,
                    ...$poe,
                    $f('E03_07', 'poe-port', $n('PoE çıxış portları', 'Порты PoE', 'PoE output ports'), 'decimal', ['unit' => 'ədəd', 'rules' => ['integer' => true], 'when' => $when('kart-el-poe', 'af', 'at', 'bt', 'passiv')]),
                    $f('E03_07', 'poe-budce', $n('PoE büdcəsi', 'Бюджет PoE', 'PoE budget'), 'decimal', ['unit' => 'W', 'when' => $when('kart-el-poe', 'af', 'at', 'bt', 'passiv')]),
                    $f('E03_07', 'goruntu', $n('Görüntü çıxışı', 'Видеовыход', 'Video output'), 'multiselect', ['options' => [...$opts([['HDMI', 'hdmi'], ['VGA', 'vga']]), $other()]]),
                    ...$supply,
                    $kit('E03_07', [['Registrator', 'registrator'], ['Disk', 'disk'], ['Adapter', 'adapter'], ['Siçan', 'sican']]),
                    ...$guarantee,
                ]),
                'videomusahide-destleri' => $card('E03_08', $n('Videomüşahidə dəstləri', 'Комплекты видеонаблюдения', 'Video surveillance kits'), ['set'], [
                    $f('E03_08', 'sistem', $n('Sistem', 'Система', 'System'), 'dropdown', ['filter' => true, 'required' => true, 'options' => $opts([['IP', 'ip'], ['Analoq', 'analoq'], ['Simsiz', 'simsiz']])]),
                    $f('E03_08', 'kameralar', $n('Kamera tərkibi', 'Состав камер', 'Cameras'), 'text', ['required' => true] + $hint('Say, model və hər modelin MP göstəricisi.', 'Число, модель и МП каждой.', 'Count, model and MP of each.')),
                    $f('E03_08', 'registrator', $n('Registrator', 'Регистратор', 'Recorder'), 'text', ['required' => true] + $hint('Model və kanal sayı; yoxdursa «daxil deyil».', 'Модель и число каналов; если нет — «нет».', 'Model and channels; «not included» if none.')),
                    $f('E03_08', 'yaddas', $n('Yaddaş', 'Хранение', 'Storage'), 'dropdown', ['filter' => true, 'required' => true, 'options' => $opts([['Disk / kart daxil deyil', 'daxil-deyil'], ['Daxildir', 'daxildir']])]),
                    $f('E03_08', 'yaddas-hecm', $n('Yaddaş həcmi', 'Объём хранения', 'Storage size'), 'text', ['required' => true, 'when' => $when('kart-e03-08-yaddas', 'daxildir')] + $hint('GB və ya TB.', 'ГБ или ТБ.', 'GB or TB.')),
                    $f('E03_08', 'qosulma', $n('Qoşulma və qidalanma', 'Подключение и питание', 'Connection and power'), 'text', $hint('Ethernet / Wi-Fi / koaksial; PoE və ya adapter, gərginlik.', 'Ethernet / Wi-Fi / коаксиал; PoE или адаптер.', 'Ethernet / Wi-Fi / coax; PoE or adapter, voltage.')),
                    $ip,
                    $f('E03_08', 'tetbiq', $n('Tətbiq və abunə', 'Приложение и подписка', 'App and subscription'), 'text', $hint('Tətbiqin adı; bulud abunəsi varsa yazın.', 'Название приложения; облачную подписку, если есть.', 'App name; note any cloud subscription.')),
                    $kit('E03_08', [['Kameralar', 'kameralar'], ['Registrator', 'registrator'], ['Disk', 'disk'], ['Adapterlər', 'adapterler'], ['Kabellər', 'kabeller'], ['Bərkitmə', 'berkitme']]),
                    ...$guarantee,
                ]),
                'domofon-sistemi-sks-siqnalizasiya' => $card('E03_09', $n('Domofonlar', 'Домофоны', 'Intercoms'), $ps, [
                    $f('E03_09', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['filter' => true, 'required' => true, 'options' => $opts([
                        ['Audio, daxili blok', 'audio-blok'], ['Audio, hazır dəst', 'audio-dest'], ['Video, daxili blok', 'video-blok'], ['Video, hazır dəst', 'video-dest'],
                    ])]),
                    $f('E03_09', 'sistem', $n('Sistem', 'Система', 'System'), 'dropdown', ['filter' => true, 'required' => true, 'options' => [...$opts([['IP', 'ip'], ['2 naqilli', '2-naqil'], ['4 naqilli', '4-naqil']]), $other()]]),
                    $compatModel(),
                    $f('E03_09', 'ekran', $n('Ekran', 'Экран', 'Screen'), 'dropdown', ['filter' => true, 'options' => $opts([['Yoxdur', 'yoxdur'], ['Var', 'var']])]),
                    $f('E03_09', 'diaqonal', $n('Ekranın diaqonalı', 'Диагональ экрана', 'Screen size'), 'decimal', ['unit' => '″', 'when' => $when('kart-e03-09-ekran', 'var')]),
                    $f('E03_09', 'panel-say', $n('Xarici panellərin maksimum sayı', 'Макс. вызывных панелей', 'Maximum door stations'), 'decimal', ['unit' => 'ədəd', 'rules' => ['integer' => true]]),
                    $f('E03_09', 'blok-say', $n('Daxili blokların maksimum sayı', 'Макс. мониторов', 'Maximum indoor units'), 'decimal', ['unit' => 'ədəd', 'rules' => ['integer' => true]]),
                    $comm,
                    ...$supply,
                    ...$smart,
                    $f('E03_09', 'kilid', $n('Kilid idarəsi', 'Управление замком', 'Lock control'), 'dropdown', ['options' => $opts([['Var', 'var'], ['Yoxdur', 'yoxdur']])]),
                    $f('E03_09', 'kilid-cixis', $n('Uyğun kilid və çıxış növü', 'Совместимый замок и тип выхода', 'Compatible lock and output'), 'text', ['when' => $when('kart-e03-09-kilid', 'var')]),
                    ...$look,
                    $kit('E03_09', [['Daxili blok', 'blok'], ['Çağırış paneli', 'panel'], ['Adapter', 'adapter'], ['Bərkitmə', 'berkitme']]),
                    ...$guarantee,
                ]),
                'domofon-cagiris-panelleri' => $card('E03_10', $n('Domofon çağırış panelləri', 'Вызывные панели домофона', 'Intercom door stations'), $ps, [
                    $f('E03_10', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['filter' => true, 'required' => true, 'options' => $opts([['Audio', 'audio'], ['Video', 'video']])]),
                    $f('E03_09', 'sistem', $n('Sistem', 'Система', 'System'), 'dropdown', ['filter' => true, 'required' => true, 'options' => [...$opts([['IP', 'ip'], ['2 naqilli', '2-naqil'], ['4 naqilli', '4-naqil']]), $other()]]),
                    $compatModel(true),
                    $f('E03_10', 'abuneci', $n('Abunəçi düymələrinin sayı', 'Кнопок абонентов', 'Call buttons'), 'decimal', ['unit' => 'ədəd', 'rules' => ['integer' => true]]),
                    $f('E03_10', 'kamera', $n('Kamera', 'Камера', 'Camera'), 'dropdown', ['filter' => true, 'options' => $opts([['Yoxdur', 'yoxdur'], ['Var', 'var']])]),
                    $f('E03_10', 'kamera-qeyd', $n('Çözünürlük və baxış bucağı', 'Разрешение и угол обзора', 'Resolution and field of view'), 'text', ['when' => $when('kart-e03-10-kamera', 'var')]),
                    $f('E03_10', 'giris', $n('Giriş üsulları', 'Способы доступа', 'Access methods'), 'multiselect', ['options' => [...$opts([
                        ['Çağırış', 'cagiris'], ['Kart', 'kart'], ['Kod', 'kod'], ['Üz', 'uz'], ['Tətbiq', 'tetbiq'],
                    ]), $other()]]),
                    ...$supply,
                    $ip,
                    $f('E03_10', 'montaj', $n('Montaj', 'Монтаж', 'Mounting'), 'dropdown', ['options' => $opts([['Gömülən', 'gomulen'], ['Səthüstü', 'sethustu']])]),
                    $dims,
                    ...$look,
                    $kit('E03_10', [['Panel', 'panel'], ['Montaj qutusu', 'qutu'], ['Adapter', 'adapter'], ['Örtük', 'ortuk']]),
                    ...$guarantee,
                ]),
                'elektron-qapi-kilidleri' => $card('E03_11', $n('Elektron qapı kilidləri', 'Электронные дверные замки', 'Electronic door locks'), $ps, [
                    $f('E03_11', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['filter' => true, 'required' => true, 'options' => $opts([
                        ['Elektromexaniki', 'elektromexaniki'], ['Elektromaqnit', 'elektromaqnit'], ['Elektrik qarşılıq', 'qarsiliq'], ['Ağıllı kilid', 'agilli'],
                    ])]),
                    $f('E03_11', 'qapi', $n('Qapı uyğunluğu', 'Совместимость с дверью', 'Door compatibility'), 'text', ['required' => true]
                        + $hint('Material, qalınlıq aralığı mm, açılma istiqaməti və montaj ölçüləri.', 'Материал, толщина, направление открывания и монтажные размеры.', 'Material, thickness range, opening and fitting sizes.')),
                    $f('E03_11', 'acilma', $n('Açılma üsulları', 'Способы открывания', 'Unlock methods'), 'multiselect', ['filter' => true, 'options' => [...$opts([
                        ['Kart', 'kart'], ['Kod', 'kod'], ['Barmaq izi', 'barmaq'], ['Üz', 'uz'], ['Telefon', 'telefon'], ['Mexaniki açar', 'acar'],
                    ]), $other()]]),
                    ...$supply,
                    $f('E03_11', 'enerji', $n('Enerji kəsiləndə davranış', 'При отключении питания', 'On power loss'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Kilidli qalır', 'kilidli'], ['Açılır', 'acilir'], ['Batareya ilə işləyir', 'batareya'],
                    ])] + $hint('İstehsalçıya görə.', 'По данным производителя.', 'Per the maker.')),
                    ...$smartBlock,
                    $ip,
                    ...$look,
                    $kit('E03_11', [['Kilid', 'kilid'], ['Qarşılıq', 'qarsiliq'], ['Silindr', 'silindr'], ['Açar', 'acar'], ['Kart', 'kart'], ['Batareya', 'batareya'], ['Şlüz', 'sluz']]),
                    ...$guarantee,
                ]),
                'giris-karti-oxuyuculari' => $card('E03_12', $n('Giriş kartı oxuyucuları və idarəetmə cihazları', 'Считыватели и контроллеры доступа', 'Access readers and controllers'), $ps, [
                    $f('E03_12', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['filter' => true, 'required' => true, 'options' => $opts([
                        ['Oxuyucu', 'oxuyucu'], ['Klaviatura', 'klaviatura'], ['Biometrik terminal', 'biometrik'], ['Giriş kontrolleri', 'kontroller'],
                    ])]),
                    $f('E03_12', 'tanima', $n('Tanıma üsulu', 'Способ идентификации', 'Identification'), 'multiselect', ['filter' => true, 'options' => $opts([
                        ['Kart', 'kart'], ['Kod', 'kod'], ['Barmaq izi', 'barmaq'], ['Üz', 'uz'], ['Mobil cihaz', 'mobil'],
                    ])]),
                    $f('E03_12', 'interfeys', $n('Kart və interfeys uyğunluğu', 'Карты и интерфейс', 'Cards and interface'), 'text', ['required' => true]
                        + $hint('Kart texnologiyası / tezliyi; Wiegand / OSDP / RS-485 / Ethernet.', 'Технология / частота карт; Wiegand / OSDP / RS-485 / Ethernet.', 'Card technology / frequency; Wiegand / OSDP / RS-485 / Ethernet.')),
                    $f('E03_12', 'istifadeci', $n('İstifadəçi tutumu', 'Пользователей', 'User capacity'), 'decimal', ['unit' => 'ədəd', 'rules' => ['integer' => true]]),
                    $f('E03_12', 'qapi', $n('İdarə olunan qapı sayı', 'Дверей', 'Doors'), 'decimal', ['unit' => 'ədəd', 'rules' => ['integer' => true]]),
                    ...$supply,
                    $ip,
                    $compatModel(),
                    $f('E03_12', 'kilid', $n('Kilid çıxışı', 'Выход на замок', 'Lock output'), 'dropdown', ['options' => $opts([['Yoxdur', 'yoxdur'], ['Rele', 'rele'], ['Gərginlik çıxışı', 'gerginlik']])]),
                    $f('E03_12', 'kilid-limit', $n('Kontakt limiti', 'Предел контакта', 'Contact rating'), 'text', ['when' => $when('kart-e03-12-kilid', 'rele', 'gerginlik')] + $hint('A və V.', 'А и В.', 'A and V.')),
                    $kit('E03_12', [['Cihaz', 'cihaz'], ['Adapter', 'adapter'], ['Bərkitmə', 'berkitme']]),
                    ...$guarantee,
                ]),
                'muhafize-siqnalizasiya-destleri' => $card('E03_13', $n('Mühafizə siqnalizasiya dəstləri', 'Комплекты охранной сигнализации', 'Alarm kits'), ['set'], [
                    $f('E03_13', 'sistem', $n('Sistem', 'Система', 'System'), 'dropdown', ['filter' => true, 'required' => true, 'options' => $opts([['Simli', 'simli'], ['Simsiz', 'simsiz'], ['Hibrid', 'hibrid']])]),
                    $f('E03_13', 'blok', $n('Mərkəzi blok', 'Центральный блок', 'Control panel'), 'text', ['required' => true] + $hint('Model və uyğun cihaz seriyası.', 'Модель и серия устройств.', 'Model and the matching device series.')),
                    $f('E03_13', 'zona', $n('Zona sayı, maks', 'Зон, макс.', 'Zones, max'), 'decimal', ['unit' => 'ədəd', 'rules' => ['integer' => true]]),
                    $f('E03_13', 'cihaz', $n('Cihaz sayı, maks', 'Устройств, макс.', 'Devices, max'), 'decimal', ['unit' => 'ədəd', 'rules' => ['integer' => true]]),
                    $f('E03_13', 'bildiris', $n('Bildiriş rabitəsi', 'Канал оповещения', 'Alert channel'), 'multiselect', ['filter' => true, 'options' => [...$opts([['Ethernet', 'ethernet'], ['Wi-Fi', 'wi-fi'], ['Mobil şəbəkə', 'mobil']]), $other()]]),
                    ...$supply,
                    $f('E03_13', 'ehtiyat', $n('Ehtiyat enerji', 'Резервное питание', 'Backup power'), 'dropdown', ['options' => $opts([['Yoxdur', 'yoxdur'], ['Batareya daxildir', 'daxildir'], ['Ayrıca alınır', 'ayrica']])]),
                    $f('E03_13', 'ehtiyat-qeyd', $n('Ehtiyat batareyanın tipi və tutumu', 'Тип и ёмкость резервной батареи', 'Backup battery type and capacity'), 'text', ['when' => $when('kart-e03-13-ehtiyat', 'daxildir', 'ayrica')]),
                    ...$smart,
                    $f('E03_13', 'terkib', $n('Dəstin tərkibi', 'Состав комплекта', 'Kit contents'), 'textarea', ['required' => true] + $hint('Hər cihazın növü, modeli və sayı.', 'Тип, модель и количество каждого устройства.', 'Type, model and count of each device.')),
                    $f('E03_13', 'abune', $n('Abunə tələbi', 'Требуется подписка', 'Subscription needed'), 'dropdown', ['options' => $opts([['Yoxdur', 'yoxdur'], ['Var', 'var']])]),
                    $f('E03_13', 'abune-qeyd', $n('Abunə növü', 'Тип подписки', 'Subscription type'), 'text', ['when' => $when('kart-e03-13-abune', 'var')] + $hint('Tətbiq, bulud və ya mühafizə xidməti.', 'Приложение, облако или охранная служба.', 'App, cloud or monitoring service.')),
                    ...$guarantee,
                ]),
                'muhafize-sensorlari' => $card('E03_14', $n('Mühafizə sensorları', 'Охранные датчики', 'Security sensors'), $ps, [
                    $f('E03_14', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['filter' => true, 'required' => true, 'options' => [...$opts([
                        ['Hərəkət', 'hereket'], ['Qapı-pəncərə açılması', 'acilma'], ['Şüşə qırılması', 'suse'], ['Vibrasiya', 'vibrasiya'],
                    ]), $other()]] + $hint('Yalnız avtomatika üçün sensor «Avtomatlaşdırma sensorları» kartındadır.', 'Датчик только для автоматики — в карточке «Датчики автоматизации».', 'Automation-only sensors belong to «Automation sensors».')),
                    $f('E03_14', 'usul', $n('Aşkarlama üsulu', 'Принцип обнаружения', 'Detection method'), 'dropdown', ['filter' => true, 'options' => [...$opts([
                        ['PIR', 'pir'], ['Mikrodalğa', 'mikrodalga'], ['Kombinə', 'kombine'], ['Maqnit', 'maqnit'], ['Akustik', 'akustik'],
                    ]), $other()]]),
                    $compatSystem(true, $n('Mərkəzi blok / seriya və ya kontakt çıxışı.', 'Центральный блок / серия или контактный выход.', 'Control panel / series or contact output.')),
                    $comm,
                    ...$supply,
                    $f('E03_14', 'mesafe', $n('Aşkarlama məsafəsi', 'Дальность обнаружения', 'Detection range'), 'decimal', ['unit' => 'm']),
                    $f('E03_14', 'bucaq', $n('Aşkarlama bucağı', 'Угол обнаружения', 'Detection angle'), 'decimal', ['unit' => '°']),
                    $f('E03_14', 'heyvan', $n('Heyvanlara həssaslıq', 'Защита от животных', 'Pet immunity'), 'dropdown', ['filter' => true, 'options' => $opts([['Var', 'var'], ['Xüsusiyyət yoxdur', 'yoxdur'], ['Göstərilməyib', 'gosterilmeyib']])]),
                    $f('E03_14', 'heyvan-kq', $n('Heyvan çəkisi həddi', 'Порог веса животного', 'Pet weight limit'), 'decimal', ['unit' => 'kq', 'when' => $when('kart-e03-14-heyvan', 'var')]),
                    $ip,
                    ...$smart,
                    $kit('E03_14', [['Sensor', 'sensor'], ['Batareya', 'batareya'], ['Bərkitmə', 'berkitme']]),
                    ...$guarantee,
                ]),
                'tustu-istilik-ve-qaz-xebervericileri' => $card('E03_15', $n('Tüstü, istilik və qaz xəbərvericiləri', 'Датчики дыма, тепла и газа', 'Smoke, heat and gas detectors'), $ps, [
                    $f('E03_15', 'tehluke', $n('Aşkarlanan təhlükə', 'Обнаруживает', 'Detects'), 'multiselect', ['filter' => true, 'required' => true, 'options' => $opts([
                        ['Tüstü', 'tustu'], ['İstilik', 'istilik'], ['CO', 'co'], ['Metan', 'metan'], ['LPG', 'lpg'],
                    ])] + $hint('Yalnız cihazın aşkarladığı təhlükələr; CO, metan və LPG eyni deyil.', 'Только то, что прибор обнаруживает; CO, метан и LPG различаются.', 'Only what the device detects; CO, methane and LPG differ.')),
                    $f('E03_15', 'rejim', $n('İş rejimi', 'Режим работы', 'Operating mode'), 'dropdown', ['filter' => true, 'required' => true, 'options' => $opts([
                        ['Müstəqil', 'musteqil'], ['Mərkəzi sistemə qoşulan', 'merkezi'], ['Hər ikisi', 'her-iki'],
                    ])]),
                    $compatSystem(false, $n('Model / seriya, naqilli interfeys və ya simsiz protokol.', 'Модель / серия, проводной интерфейс или беспроводной протокол.', 'Model / series, wired interface or wireless protocol.')),
                    ...$supply,
                    $f('E03_15', 'xeberdarliq', $n('Xəbərdarlıq', 'Оповещение', 'Alert'), 'multiselect', ['options' => $opts([['Səs', 'ses'], ['İşıq', 'isiq'], ['Tətbiq', 'tetbiq'], ['Mərkəzi panel', 'panel']])]),
                    $f('E03_15', 'resurs', $n('Sensor resursu', 'Ресурс датчика', 'Sensor life'), 'decimal', ['unit' => 'il']),
                    $f('E03_15', 'sened', $n('Uyğunluq sənədi', 'Документ о соответствии', 'Conformity document'), 'document',
                        $hint('Məhsulun təyinatına aid standart və sertifikat: PDF / şəkil faylı və ya link.', 'Стандарт и сертификат: файл PDF / изображение или ссылка.', 'The applicable standard and certificate: a PDF / image file or a link.')),
                    ...$smart,
                    $kit('E03_15', [['Cihaz', 'cihaz'], ['Batareya', 'batareya'], ['Bərkitmə', 'berkitme']]),
                    ...$guarantee,
                ]),
                'sirenler-ve-xeberdarliq-cihazlari' => $card('E03_16', $n('Sirenlər və xəbərdarlıq cihazları', 'Сирены и оповещатели', 'Sirens and sounders'), $ps, [
                    $f('E03_16', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['filter' => true, 'required' => true, 'options' => $opts([['Səsli', 'sesli'], ['İşıqlı', 'isiqli'], ['Səsli-işıqlı', 'sesli-isiqli']])]),
                    $f('E03_16', 'muhit', $n('Mühit', 'Среда', 'Environment'), 'dropdown', ['filter' => true, 'required' => true, 'options' => $opts([['Daxili', 'daxili'], ['Xarici', 'xarici']])]),
                    $compatSystem(true),
                    $comm,
                    ...$supply,
                    $f('E03_16', 'db', $n('Səs səviyyəsi', 'Громкость', 'Sound level'), 'decimal', ['unit' => 'dB']),
                    $f('E03_16', 'db-mesafe', $n('Ölçmə məsafəsi', 'Расстояние измерения', 'Measured at'), 'decimal', ['unit' => 'm']),
                    $ip,
                    $dims,
                    $f('E03_16', 'batareya', $n('Ehtiyat batareya', 'Резервная батарея', 'Backup battery'), 'dropdown', ['options' => $opts([['Daxildir', 'daxildir'], ['Ayrıca alınır', 'ayrica'], ['Dəstəklənmir', 'desteklenmir']])]),
                    $kit('E03_16', [['Siren', 'siren'], ['Batareya', 'batareya'], ['Bərkitmə', 'berkitme']]),
                    ...$guarantee,
                ]),
            ],
        ],

        // ════ 4. Ağıllı ev və avtomatlaşdırma ══════════════════════════════
        [
            'slug' => 'agilli-ev-ve-avtomatlasdirma',
            'name' => $n('Ağıllı ev və avtomatlaşdırma', 'Умный дом и автоматизация', 'Smart home and automation'),
            'classes' => [
                'agilli-ev-sistemi' => $card('E04_01', $n('Ağıllı ev kontrollerləri və şlüzləri', 'Контроллеры и шлюзы умного дома', 'Smart-home hubs and gateways'), $ps, [
                    $f('E04_01', 'funksiya', $n('Funksiya', 'Функция', 'Function'), 'multiselect', ['filter' => true, 'required' => true, 'options' => $opts([
                        ['Mərkəzi kontroller', 'kontroller'], ['Şlüz', 'sluz'], ['Körpü', 'korpu'], ['Thread Border Router', 'tbr'],
                    ])] + $hint('Matter kontrolleri və Thread Border Router eyni funksiya deyil.', 'Контроллер Matter и Thread Border Router — разные функции.', 'A Matter controller and a Thread Border Router are different functions.')),
                    $systems,
                    ['filter' => true] + $comm,
                    $f('E04_01', 'tutum', $n('Tutum', 'Ёмкость', 'Capacity'), 'decimal', ['unit' => 'cihaz', 'rules' => ['integer' => true]] + $hint('İstehsalçı üzrə maksimum cihaz sayı.', 'Макс. число устройств по данным производителя.', 'Maximum number of devices per the maker.')),
                    $f('E04_01', 'idareetme', $n('İdarəetmə', 'Управление', 'Control'), 'dropdown', ['options' => $opts([['Lokal', 'lokal'], ['Bulud', 'bulud'], ['Hər ikisi', 'her-iki']])]),
                    $f('E04_01', 'lokal', $n('İnternetsiz işləyən funksiyalar', 'Функции без интернета', 'Offline functions'), 'text'),
                    $f('E04_01', 'tetbiq', $n('Tətbiq və abunə', 'Приложение и подписка', 'App and subscription'), 'text', $hint('Tətbiq adı; məcburi və ya əlavə abunə varsa.', 'Название; обязательная или доп. подписка, если есть.', 'App name; any required or optional subscription.')),
                    ...$supply,
                    $dims,
                    $kit('E04_01', [['Kontroller', 'kontroller'], ['Şlüz', 'sluz'], ['Adapter', 'adapter'], ['Kabel', 'kabel']]),
                    ...$guarantee,
                ]),
                'agilli-ev-idareetme-panelleri' => $card('E04_02', $n('Ağıllı ev idarəetmə panelləri', 'Панели управления умным домом', 'Smart-home control panels'), $ps, [
                    $f('E04_02', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['filter' => true, 'required' => true, 'options' => $opts([['Sensor ekranlı', 'ekran'], ['Düyməli', 'duymeli'], ['Kombinə', 'kombine']])]),
                    $systems,
                    ['filter' => true] + $comm,
                    ...$extraDevice,
                    $f('E04_02', 'ekran', $n('Ekran', 'Экран', 'Screen'), 'text', $hint('Diaqonal düym və çözünürlük; ekran yoxdursa boş qalır.', 'Диагональ и разрешение; без экрана — пусто.', 'Diagonal and resolution; leave empty without a screen.')),
                    $f('E04_02', 'montaj', $n('Montaj', 'Монтаж', 'Mounting'), 'dropdown', ['filter' => true, 'options' => $opts([['Divara gömülən', 'gomulen'], ['Divarüstü', 'divarustu'], ['Masaüstü', 'masaustu']])]),
                    ...$supply,
                    ...$look,
                    $f('E04_02', 'daxili', $n('Daxili funksiyalar', 'Встроенные функции', 'Built-in functions'), 'multiselect', ['options' => [$noneX(), ...$opts([
                        ['Temperatur sensoru', 'temperatur'], ['Rele', 'rele'], ['Mikrofon', 'mikrofon'],
                    ]), $other()]]),
                    $kit('E04_02', [['Panel', 'panel'], ['Adapter', 'adapter'], ['Montaj hissələri', 'montaj']]),
                    ...$guarantee,
                ]),
                'ssenari-duymeleri-ve-pultlar' => $card('E04_03', $n('Ssenari düymələri və pultlar', 'Сценарные кнопки и пульты', 'Scene buttons and remotes'), $ps, [
                    $f('E04_03', 'nov', $n('Növ', 'Тип', 'Type'), 'dropdown', ['filter' => true, 'required' => true, 'options' => $opts([['Divar düyməsi', 'divar'], ['Daşınan pult', 'pult']])]
                        + $hint('Yükü birbaşa açıb-bağlayan açar «İşıq açarları» kartındadır.', 'Выключатель, коммутирующий нагрузку, — в «Выключателях».', 'A switch that switches the load itself belongs to «Light switches».')),
                    $f('E04_03', 'duyme', $n('Düymə sayı', 'Число кнопок', 'Buttons'), 'decimal', ['required' => true, 'unit' => 'ədəd', 'rules' => ['integer' => true]]),
                    $f('E04_03', 'emrler', $n('Əmrlər', 'Команды', 'Actions'), 'multiselect', ['options' => [...$opts([
                        ['Bir basma', 'bir'], ['İki basma', 'iki'], ['Uzun basma', 'uzun'], ['Fırlatma', 'firlatma'],
                    ]), $other()]]),
                    $systems,
                    ['filter' => true] + $comm,
                    ...$extraDevice,
                    ...$supply,
                    ...$look,
                    $f('E04_03', 'montaj', $n('Montaj', 'Монтаж', 'Mounting'), 'dropdown', ['options' => $opts([['Divara', 'divar'], ['Masaüstü', 'masaustu'], ['Daşınan', 'dasinan']])]),
                    $kit('E04_03', [['Düymə və ya pult', 'cihaz'], ['Batareya', 'batareya'], ['Tutacaq', 'tutacaq']]),
                    ...$guarantee,
                ]),
                'agilli-releler-ve-idareetme-modullari' => $card('E04_04', $n('Ağıllı relelər və idarəetmə modulları', 'Умные реле и модули', 'Smart relays and modules'), $ps, [
                    $f('E04_04', 'funksiya', $n('Funksiya', 'Функция', 'Function'), 'dropdown', ['filter' => true, 'required' => true, 'options' => [...$opts([
                        ['Açıb-bağlama relesi', 'rele'], ['Dimmer modulu', 'dimmer'], ['Jalüz modulu', 'jaluz'], ['Giriş modulu', 'giris'],
                    ]), $other()]] + $hint('LED lent kontrolleri ayrıca kartdadır.', 'Контроллер LED-ленты — в отдельной карточке.', 'LED strip controllers have their own card.')),
                    $f('E04_04', 'kanal', $n('Kanal sayı', 'Число каналов', 'Channels'), 'decimal', ['filter' => true, 'required' => true, 'unit' => 'ədəd', 'rules' => ['integer' => true]]),
                    $f('E04_04', 'cixis', $n('Çıxış növü', 'Тип выхода', 'Output type'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Quru kontakt', 'quru'], ['Gərginlik çıxışı', 'gerginlik'], ['Dimm çıxışı', 'dimm'], ['Yalnız giriş', 'giris'],
                    ])]),
                    $f('E04_04', 'yuk', $n('Yük limiti', 'Нагрузка', 'Load rating'), 'text', ['required' => true, 'when' => $when('kart-e04-04-cixis', 'quru', 'gerginlik', 'dimm')]
                        + $hint('Yük növünə görə A və W; motor limiti ayrıca.', 'А и Вт по типу нагрузки; лимит мотора отдельно.', 'A and W by load type; the motor limit separately.')),
                    ...$voltage(),
                    $neutral(true),
                    $f('E04_04', 'montaj', $n('Montaj', 'Монтаж', 'Mounting'), 'dropdown', ['filter' => true, 'options' => $opts([['Montaj qutusu daxilinə', 'qutu'], ['DIN-rels', 'din'], ['Ayrıca korpus', 'korpus']])]),
                    $systems,
                    ['filter' => true] + $comm,
                    ...$extraDevice,
                    $f('E04_04', 'enerji', $n('Enerji sərfiyyatını ölçür', 'Измеряет потребление', 'Energy metering'), 'dropdown', ['options' => $ynn]),
                    $dims,
                    $kit('E04_04', [['Modul', 'modul'], ['Bypass', 'bypass'], ['Montaj hissələri', 'montaj']]),
                    ...$guarantee,
                ]),
                'avtomatlasdirma-sensorlari' => $card('E04_05', $n('Avtomatlaşdırma sensorları', 'Датчики автоматизации', 'Automation sensors'), $ps, [
                    $f('E04_05', 'gosterici', $n('Ölçülən göstərici', 'Измеряемые параметры', 'Measures'), 'multiselect', ['filter' => true, 'required' => true, 'options' => [...$opts([
                        ['Temperatur', 'temperatur'], ['Rütubət', 'rutubet'], ['İşıqlanma', 'isiqlanma'], ['Mövcudluq', 'movcudluq'], ['Su sızması', 'su'],
                    ]), $other()]] + $hint('Mühafizə sensorları və tüstü / qaz xəbərvericiləri ayrıca kartlardadır.', 'Охранные датчики и датчики дыма / газа — в своих карточках.', 'Security sensors and smoke / gas detectors have their own cards.')),
                    $f('E04_05', 'texnologiya', $n('Sensor texnologiyası', 'Технология датчика', 'Sensor technology'), 'dropdown', ['options' => [...$opts([
                        ['PIR', 'pir'], ['Radar', 'radar'], ['Kontakt', 'kontakt'], ['Ölçmə sensoru', 'olcme'],
                    ]), $other()]]),
                    $f('E04_05', 'aralig', $n('Ölçmə və aşkarlama aralığı', 'Диапазон измерения', 'Measuring range'), 'text', $hint('Göstərici üzrə aralıq və vahid: °C, %RH, lx, m, dərəcə.', 'Диапазон и единица: °C, %RH, лк, м, градусы.', 'Range and unit: °C, %RH, lx, m, degrees.')),
                    $systems,
                    ['filter' => true] + $comm,
                    ...$extraDevice,
                    ...$supply,
                    $ip,
                    $f('E04_05', 'montaj', $n('Montaj', 'Монтаж', 'Mounting'), 'dropdown', ['options' => [...$opts([['Divar', 'divar'], ['Tavan', 'tavan'], ['Masa', 'masa'], ['Döşəmə', 'doseme']]), $other()]]),
                    $kit('E04_05', [['Sensor', 'sensor'], ['Zond', 'zond'], ['Batareya', 'batareya'], ['Bərkitmə', 'berkitme']]),
                    ...$guarantee,
                ]),
                'perde-ve-jaluz-muherrikleri' => $card('E04_06', $n('Pərdə və jalüz mühərrikləri', 'Моторы для штор и жалюзи', 'Curtain and blind motors'), $ps, [
                    $f('E04_06', 'teyinat', $n('Təyinat', 'Назначение', 'For'), 'dropdown', ['filter' => true, 'required' => true, 'options' => [...$opts([
                        ['Sürüşən pərdə', 'surusen'], ['Rulon pərdə', 'rulon'], ['Jalüz', 'jaluz'],
                    ]), $other()]] + $hint('Parça və dekorativ pərdə bu bölmədə deyil.', 'Ткань и декоративные шторы — не в этом разделе.', 'Fabric and decorative curtains are not in this section.')),
                    $compatModel(),
                    $f('E04_06', 'yuk', $n('Yük göstəricisi', 'Нагрузка', 'Load rating'), 'text', ['required' => true]
                        + $hint('Sürüşən pərdədə maks kq; boru mühərrikində Nm və uyğun boru diametri mm.', 'Для штор — макс. кг; для трубчатого мотора — Нм и диаметр трубы.', 'Curtains: max kg; tubular motor: Nm and tube diameter.')),
                    $f('E04_06', 'suret', $n('Sürət', 'Скорость', 'Speed'), 'text', $hint('cm/s və ya dövr/dəq; növə görə.', 'см/с или об/мин.', 'cm/s or rpm.')),
                    ...$supply,
                    $f('E04_06', 'idareetme', $n('İdarəetmə', 'Управление', 'Control'), 'multiselect', ['filter' => true, 'required' => true, 'options' => $opts([
                        ['Naqilli açar', 'acar'], ['Radio pult', 'pult'], ['Tətbiq', 'tetbiq'], ['Şin sistemi', 'sin'],
                    ])]),
                    ...$smartBlock,
                    $f('E04_06', 'elle', $n('Əllə idarəetmə dəstəyi', 'Ручное управление', 'Manual operation'), 'dropdown', ['options' => $ynn]),
                    $kit('E04_06', [['Mühərrik', 'muherrik'], ['Adapter', 'adapter'], ['Pult', 'pult'], ['Bərkitmə', 'berkitme']]),
                    ...$guarantee,
                ]),
                'perde-avtomatikasi-destleri' => $card('E04_07', $n('Pərdə avtomatikası dəstləri', 'Комплекты автоматики штор', 'Curtain automation kits'), ['set'], [
                    $f('E04_07', 'teyinat', $n('Təyinat', 'Назначение', 'For'), 'dropdown', ['filter' => true, 'required' => true, 'options' => $opts([
                        ['Sürüşən pərdə', 'surusen'], ['Rulon pərdə', 'rulon'], ['Jalüz', 'jaluz'],
                    ])]),
                    $f('E04_07', 'olcu', $n('Ölçü', 'Размер', 'Size'), 'text', ['required' => true] + $hint('Rels uzunluğu və ya uyğun pərdə eni / hündürlüyü, mm.', 'Длина карниза или ширина / высота шторы, мм.', 'Rail length or curtain width / height, mm.')),
                    $f('E04_07', 'istiqamet', $n('Açılma istiqaməti', 'Направление открывания', 'Opening direction'), 'dropdown', ['filter' => true, 'options' => $opts([
                        ['Sola', 'sola'], ['Sağa', 'saga'], ['Mərkəzdən', 'merkezden'], ['Yuxarı-aşağı', 'yuxari-asagi'],
                    ])]),
                    $f('E04_07', 'muherrik', $n('Mühərrik və yük', 'Мотор и нагрузка', 'Motor and load'), 'text', ['required' => true] + $hint('Model; maks kq və ya Nm, sistemə görə.', 'Модель; макс. кг или Нм.', 'Model; max kg or Nm.')),
                    ...$supply,
                    ...$smartBlock,
                    $f('E04_07', 'terkib', $n('Dəstin tərkibi', 'Состав комплекта', 'Kit contents'), 'textarea', ['required' => true]
                        + $hint('Mühərrik / rels / ötürmə hissələri / pult / adapter / bərkitmə və hər hissənin sayı. Parça daxil deyilsə yazılmır.', 'Мотор / карниз / привод / пульт / адаптер / крепёж и количество. Ткань — только если входит.', 'Motor / rail / drive parts / remote / adapter / fixings and counts. Fabric only if included.')),
                    ...$guarantee,
                ]),
            ],
        ],

        // ════ Not in the workbook — «sonraya saxlanılıb» ═══════════════════
        [
            'slug' => 'guc-elektrik-sistemleri',
            'name' => $n('Güc elektrik sistemləri', 'Силовые электросистемы', 'Power distribution'),
            'plain' => [
                'avtomatik-acar', 'elektrik-lovhesi', 'kabel-ve-naqil', 'montaj-komponentleri-podrozetnikler-klemmnikler-gofra-krablar',
                'uzo-diferensial-avtomat', 'gerginlik-stabilizatoru', 'generator-ehtiyat-enerji-menbeyi', 'ibp-fasilesiz-enerji-techizati-menbeyi', 'kabel-kanallar-ve-lotoklar',
            ],
        ],
    ],

    'retire_groups' => ['rozetkalar-ve-acarlar', 'zeif-cereyan-sistemleri-ve-agilli-ev'],
];
