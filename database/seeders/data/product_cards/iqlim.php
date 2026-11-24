<?php

/*
|--------------------------------------------------------------------------
| İsitmə, havalandırma və kondisionerləşdirmə — 37 cards in the workbook's 4 blocks
|--------------------------------------------------------------------------
|
| Source: Archi_AZ_Iqlim_Kataloq_AZ.xlsx (client, 24.11.2026). Format and decisions:
| docs/product-cards.md. Section «İstilik, ventilyasiya və kondisionerləşdirmə»: the
| workbook's «Kateqoriyalar» sheet says «4 əsas blok. 15 alt bölmə» (like the textile
| workbook), so the 4 blocks are the groups and the 15 numbered sub-sections stay inside
| them. Pipes, fittings, expansion tanks, collectors («ayrı borular və kollektorlar
| sonraya saxlanılır») and the industrial chiller / humidifier keep their plain form.
|
| Repeated blocks («Ölçülər», «Rəng və səth», «Elektrik qidalanması», «Ağıllı ev və
| tətbiq uyğunluğu», «Rabitə və əlavə modul», «Səs göstəricisi», «Soyutma və isitmə
| gücü», «Hava sərfi», «Su bağlantısı», «İş hədləri» …) are one shared `kart-iq-*`
| definition each. Option values are Azerbaijani only; attribute / class names az/ru/en.
| `kart-zemanet` / `kart-zemanet-muddeti` are the shared definitions of the other files.
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
$noneX = fn (string $az = 'Yoxdur', string $slug = 'yoxdur'): array => $a($az, ['slug' => $slug, 'exclusive' => true]);
$ynn = [$yes, $no, $ns];
$when = fn (string $attr, string ...$in): array => ['attr' => $attr, 'in' => $in];
$opts = fn (array $list): array => array_map(fn ($pair) => $a($pair[0], ['slug' => $pair[1]]), $list);
$hint = fn (string $az, string $ru, string $en): array => ['hint' => compact('az', 'ru', 'en')];
$req = ['required' => true];

// ── Shared ──────────────────────────────────────────────────────────────────
$warranty = ['slug' => 'kart-zemanet', 'name' => $n('Zəmanət', 'Гарантия', 'Warranty'), 'type' => 'dropdown', 'options' => [
    $o('Var', 'Есть', 'Provided', ['slug' => 'var']), $o('Verilmir', 'Не предоставляется', 'Not provided', ['slug' => 'verilmir']), $ns,
]];
$warrantyMonths = ['slug' => 'kart-zemanet-muddeti', 'name' => $n('Zəmanət müddəti', 'Срок гарантии', 'Warranty period'), 'type' => 'decimal', 'unit' => 'ay',
    'rules' => ['integer' => true], 'when' => ['attr' => 'kart-zemanet', 'in' => ['var']]];
$guarantee = [$warranty, $warrantyMonths];

// «Rəng və səth* — iki seçim».
$color = ['slug' => 'kart-iq-reng', 'name' => $n('Rəng', 'Цвет', 'Colour'), 'type' => 'dropdown', 'required' => true, 'filter' => true, 'options' => [
    $a('Ağ', ['slug' => 'ag']), $a('Qara', ['slug' => 'qara']), $a('Boz', ['slug' => 'boz']), $a('Xrom', ['slug' => 'xrom']),
    $a('Qızılı', ['slug' => 'qizili']), $other($P_CODE),
]];
$finish = ['slug' => 'kart-iq-seth', 'name' => $n('Səth', 'Поверхность', 'Finish'), 'type' => 'dropdown', 'required' => true, 'options' => [
    $a('Mat', ['slug' => 'mat']), $a('Parlaq', ['slug' => 'parlaq']), $a('Fırçalanmış', ['slug' => 'fircalanmis']), $other(),
]];
$look = [$color, $finish];

// «Ölçülər* — En × dərinlik × hündürlük, mm; hər blok üçün ayrıca».
$dims = fn (?array $h = null): array => [
    ['slug' => 'kart-iq-en', 'name' => $n('En', 'Ширина', 'Width'), 'type' => 'decimal', 'required' => true, 'unit' => 'mm',
        'hint' => $h ?? $n('Qabarit ölçülər: en × dərinlik × hündürlük.', 'Габариты: ширина × глубина × высота.', 'Overall size: width × depth × height.')],
    ['slug' => 'kart-iq-derinlik', 'name' => $n('Dərinlik', 'Глубина', 'Depth'), 'type' => 'decimal', 'required' => true, 'unit' => 'mm'],
    ['slug' => 'kart-iq-hundurluk', 'name' => $n('Hündürlük', 'Высота', 'Height'), 'type' => 'decimal', 'required' => true, 'unit' => 'mm'],
];
$indoorHint = $n('Daxili blok: en × dərinlik × hündürlük. Xarici blok aşağıda ayrıca.', 'Внутренний блок: ширина × глубина × высота. Наружный — ниже отдельно.', 'Indoor unit: width × depth × height. The outdoor unit is given separately below.');
$outdoorDims = ['slug' => 'kart-iq-xarici-blok-olculeri', 'name' => $n('Xarici blokun ölçüləri', 'Размеры наружного блока', 'Outdoor unit size'), 'type' => 'text']
    + $hint('En × dərinlik × hündürlük, mm. Məs. 800 × 290 × 550.', 'Ширина × глубина × высота, мм.', 'Width × depth × height, mm.');
// One-word measures whose unit differs per card (m for warm-floor mats, mm for filters).
$length = ['slug' => 'kart-iq-uzunluq', 'name' => $n('Uzunluq', 'Длина', 'Length'), 'type' => 'decimal'];
$width = ['slug' => 'kart-iq-en', 'name' => $n('En', 'Ширина', 'Width'), 'type' => 'decimal'];
$thickness = ['slug' => 'kart-iq-qalinliq', 'name' => $n('Qalınlıq', 'Толщина', 'Thickness'), 'type' => 'decimal', 'unit' => 'mm'];

// «İstilik gücü* — W (su ilə isitmə cihazları) və ya kW (qazan, istilik nasosu)»; the unit is per card.
$heat = ['slug' => 'kart-iq-istilik-gucu', 'name' => $n('İstilik gücü', 'Тепловая мощность', 'Heat output'), 'type' => 'decimal', 'filter' => true];
$tempMode = ['slug' => 'kart-iq-temperatur-rejimi', 'name' => $n('Temperatur rejimi', 'Температурный режим', 'Temperature regime'), 'type' => 'text']
    + $hint('Gediş / qayıdış / otaq °C (məs. 75/65/20) və ya istehsalçının ΔT göstəricisi (məs. ΔT 50).', 'Подача / обратка / помещение °C (напр. 75/65/20) или ΔT производителя.', 'Flow / return / room °C (e.g. 75/65/20) or the maker’s ΔT.');
// «Elektrik gücü / Nominal güc / Elektrik giriş gücü» — W or kW, per card.
$elPower = ['slug' => 'kart-iq-elektrik-gucu', 'name' => $n('Elektrik gücü', 'Электрическая мощность', 'Electrical power'), 'type' => 'decimal'];
$heaterPower = ['slug' => 'kart-iq-qizdirici-gucu', 'name' => $n('Qızdırıcının gücü', 'Мощность нагревателя', 'Heater power'), 'type' => 'decimal', 'unit' => 'W']
    + $hint('Elektrik qızdırıcısı; ventilyatordan ayrı.', 'Электронагреватель; отдельно от вентилятора.', 'Electric heater; separate from the fan.');

// «Su bağlantısı* — Alt / yan / digər; yiv ölçüsü və mərkəzlərarası məsafə mm».
$thread = ['slug' => 'kart-iq-yiv', 'name' => $n('Yiv ölçüsü', 'Размер резьбы', 'Thread size'), 'type' => 'dropdown', 'options' => [
    $a('3/8″', ['slug' => '3-8']), $a('1/2″', ['slug' => '1-2']), $a('3/4″', ['slug' => '3-4']), $a('1″', ['slug' => '1']), $a('M24×1,5', ['slug' => 'm24']),
    $other($P_SIZE, 'Digər ölçü'),
]];
$centres = ['slug' => 'kart-iq-merkezlerarasi', 'name' => $n('Mərkəzlərarası məsafə', 'Межосевое расстояние', 'Centre distance'), 'type' => 'decimal', 'unit' => 'mm'];
$waterConn = fn (bool $req = true, ?array $w = null): array => array_map(fn ($f) => $w ? ['when' => $w] + $f : $f, [
    ['slug' => 'kart-iq-su-baglantisi', 'name' => $n('Su bağlantısı', 'Подключение воды', 'Water connection'), 'type' => 'dropdown', 'required' => $req,
        'options' => [...$opts([['Alt', 'alt'], ['Yan', 'yan']]), $other()]],
    ['required' => $req] + $thread,
    $centres,
]);
// «İş hədləri — maksimum iş təzyiqi bar və su temperaturu °C».
$maxWaterTemp = ['slug' => 'kart-iq-maks-su-temperaturu', 'name' => $n('Maksimum su temperaturu', 'Макс. температура воды', 'Maximum water temperature'), 'type' => 'decimal', 'unit' => '°C'];
$limits = fn (?array $w = null): array => array_map(fn ($f) => $w ? ['when' => $w] + $f : $f, [
    ['slug' => 'kart-iq-maks-tezyiq', 'name' => $n('Maksimum iş təzyiqi', 'Макс. рабочее давление', 'Maximum working pressure'), 'type' => 'decimal', 'unit' => 'bar'],
    $maxWaterTemp,
]);

// «Elektrik qidalanması* — Gərginlik V, AC/DC; AC üçün faza sayı və tezlik Hz. Batareyada tip və say».
$supply = fn (bool $req = true, ?array $w = null): array => [
    ['slug' => 'kart-iq-gerginlik', 'name' => $n('Gərginlik', 'Напряжение', 'Voltage'), 'type' => 'decimal', 'required' => $req, 'unit' => 'V'] + ($w ? ['when' => $w] : []),
    ['slug' => 'kart-iq-cereyan-novu', 'name' => $n('Cərəyan növü', 'Род тока', 'Current type'), 'type' => 'dropdown', 'required' => $req,
        'options' => $opts([['AC', 'ac'], ['DC', 'dc'], ['AC/DC', 'ac-dc']])] + ($w ? ['when' => $w] : []),
    ['slug' => 'kart-iq-faza', 'name' => $n('Faza sayı', 'Число фаз', 'Phases'), 'type' => 'dropdown', 'required' => $req,
        'options' => $opts([['1 faza', '1'], ['3 faza', '3']]), 'when' => ['attr' => 'kart-iq-cereyan-novu', 'in' => ['ac', 'ac-dc']]],
    ['slug' => 'kart-iq-tezlik', 'name' => $n('Tezlik', 'Частота', 'Frequency'), 'type' => 'decimal', 'unit' => 'Hz',
        'when' => ['attr' => 'kart-iq-cereyan-novu', 'in' => ['ac', 'ac-dc']]],
    ['slug' => 'kart-iq-batareya', 'name' => $n('Batareyanın tipi və sayı', 'Тип и число батарей', 'Battery type and count'), 'type' => 'text',
        'when' => ['attr' => 'kart-iq-cereyan-novu', 'in' => ['dc']]]
        + $hint('Batareya ilə işləyirsə, məs. 2 × AA.', 'Если от батарей, напр. 2 × AA.', 'If battery powered, e.g. 2 × AA.'),
];

$ip = ['slug' => 'kart-iq-ip', 'name' => $n('IP qorunma dərəcəsi', 'Степень защиты IP', 'IP rating'), 'type' => 'dropdown', 'options' => [...$opts([
    ['IP20', 'ip20'], ['IP21', 'ip21'], ['IP24', 'ip24'], ['IP44', 'ip44'], ['IP54', 'ip54'], ['IP65', 'ip65'],
]), $other(), $ns]] + $hint('İstehsalçıya görə.', 'По данным производителя.', 'Per the maker.');

$control = ['slug' => 'kart-iq-idareetme', 'name' => $n('İdarəetmə', 'Управление', 'Control'), 'type' => 'multiselect', 'options' => [...$opts([
    ['Mexaniki', 'mexaniki'], ['Elektron', 'elektron'], ['Sensor ekran', 'sensor-ekran'], ['Naqilli pult', 'naqilli-pult'], ['Simsiz pult', 'simsiz-pult'], ['Tətbiq', 'tetbiq'],
]), $other()]];

// «Ağıllı ev və tətbiq uyğunluğu — Yoxdur / Var / Göstərilməyib; varsa tətbiq və sistemlər».
$smart = [
    ['slug' => 'kart-iq-agilli', 'name' => $n('Ağıllı ev və tətbiq uyğunluğu', 'Совместимость с умным домом и приложением', 'Smart-home and app compatible'), 'type' => 'dropdown',
        'options' => [$a('Yoxdur', ['slug' => 'yoxdur']), $a('Var', ['slug' => 'var']), $ns]],
    ['slug' => 'kart-iq-agilli-sistem', 'name' => $n('Tətbiq və dəstəklənən sistemlər', 'Приложение и поддерживаемые системы', 'App and supported systems'), 'type' => 'text', 'required' => true,
        'when' => ['attr' => 'kart-iq-agilli', 'in' => ['var']]]
        + $hint('Yalnız istehsalçının təsdiqlədiyi uyğunluq: tətbiqin adı və sistemlər, məs. Google Home, Apple Home, Tuya.', 'Только подтверждённая производителем: приложение и системы.', 'Maker-confirmed only: the app and the systems.'),
];
// «Rabitə və əlavə modul — Wi-Fi / Ethernet / … / yoxdur; şlüz / adapter: model və komplektdə olub-olmaması».
$comm = [
    ['slug' => 'kart-iq-rabite', 'name' => $n('Rabitə', 'Связь', 'Connectivity'), 'type' => 'multiselect', 'options' => [
        $noneX(), ...$opts([['Wi-Fi', 'wi-fi'], ['Ethernet', 'ethernet'], ['Zigbee', 'zigbee'], ['Thread', 'thread'], ['Radio', 'radio'], ['Naqilli şin', 'naqilli-sin']]), $other(),
    ]],
    ['slug' => 'kart-iq-modul', 'name' => $n('Şlüz / adapter', 'Шлюз / адаптер', 'Gateway / adapter'), 'type' => 'dropdown',
        'options' => $opts([['Tələb olunmur', 'teleb-olunmur'], ['Tələb olunur', 'teleb-olunur']])],
    ['slug' => 'kart-iq-modul-model', 'name' => $n('Şlüzün / adapterin modeli', 'Модель шлюза / адаптера', 'Gateway / adapter model'), 'type' => 'text', 'required' => true,
        'when' => ['attr' => 'kart-iq-modul', 'in' => ['teleb-olunur']]],
    ['slug' => 'kart-iq-modul-komplekt', 'name' => $n('Şlüz / adapter komplektdədir', 'Шлюз / адаптер в комплекте', 'Gateway / adapter included'), 'type' => 'dropdown', 'required' => true,
        'options' => $opts([['Komplektə daxildir', 'daxildir'], ['Ayrıca alınır', 'ayrica']]), 'when' => ['attr' => 'kart-iq-modul', 'in' => ['teleb-olunur']]],
];
$smartBlock = [...$smart, ...$comm];

// «Səs göstəricisi — dB(A); səs təzyiqi və ya səs gücü; rejim / sürət; təzyiqdə ölçmə məsafəsi m».
$noiseLevel = ['slug' => 'kart-iq-ses', 'name' => $n('Səs səviyyəsi', 'Уровень шума', 'Noise level'), 'type' => 'decimal', 'unit' => 'dB(A)'];
$noise = [
    ['slug' => 'kart-iq-ses-novu', 'name' => $n('Səs göstəricisi', 'Шумовая характеристика', 'Noise rating'), 'type' => 'dropdown',
        'options' => $opts([['Səs təzyiqi', 'tezyiq'], ['Səs gücü', 'guc']])],
    ['when' => ['attr' => 'kart-iq-ses-novu', 'in' => ['tezyiq', 'guc']]] + $noiseLevel,
    ['slug' => 'kart-iq-ses-rejim', 'name' => $n('Ölçmə rejimi / sürəti', 'Режим / скорость измерения', 'Mode / speed measured'), 'type' => 'text',
        'when' => ['attr' => 'kart-iq-ses-novu', 'in' => ['tezyiq', 'guc']]],
    ['slug' => 'kart-iq-ses-mesafe', 'name' => $n('Ölçmə məsafəsi', 'Расстояние измерения', 'Measuring distance'), 'type' => 'decimal', 'unit' => 'm',
        'when' => ['attr' => 'kart-iq-ses-novu', 'in' => ['tezyiq']]],
];

$refrigerant = ['slug' => 'kart-iq-soyuducu-agent', 'name' => $n('Soyuducu agent', 'Хладагент', 'Refrigerant'), 'type' => 'dropdown', 'required' => true, 'filter' => true,
    'options' => [...$opts([['R32', 'r32'], ['R410A', 'r410a'], ['R290', 'r290']]), $other()]] + $hint('İstehsalçıya görə.', 'По данным производителя.', 'Per the maker.');

// «Soyutma və isitmə gücü* — hər rejim üzrə nominal kW; BTU/saat istəyə görə; yalnız soyutmada isitmə tətbiq edilmir».
$capacity = fn (bool $btu = true): array => array_values(array_filter([
    ['slug' => 'kart-iq-soyutma-gucu', 'name' => $n('Soyutma gücü', 'Холодопроизводительность', 'Cooling capacity'), 'type' => 'decimal', 'required' => true, 'unit' => 'kW', 'filter' => true]
        + $hint('Nominal.', 'Номинальная.', 'Rated.'),
    ['slug' => 'kart-iq-isitme-rejimi', 'name' => $n('İsitmə rejimi', 'Режим обогрева', 'Heating mode'), 'type' => 'dropdown', 'required' => true,
        'options' => $opts([['Var', 'var'], ['Yoxdur, yalnız soyutma', 'yoxdur']])],
    ['slug' => 'kart-iq-isitme-gucu', 'name' => $n('İsitmə gücü', 'Теплопроизводительность', 'Heating capacity'), 'type' => 'decimal', 'required' => true, 'unit' => 'kW',
        'when' => ['attr' => 'kart-iq-isitme-rejimi', 'in' => ['var']]] + $hint('Nominal.', 'Номинальная.', 'Rated.'),
    $btu ? ['slug' => 'kart-iq-btu', 'name' => $n('Soyutma gücü, BTU/saat', 'Холодопроизводительность, BTU/ч', 'Cooling capacity, BTU/h'), 'type' => 'decimal', 'unit' => 'BTU/saat'] : null,
]));
// «Elektrik sərfiyyatı* — soyutma və isitmə rejimində nominal giriş gücü kW».
$consumption = [
    ['slug' => 'kart-iq-giris-soyutma', 'name' => $n('Elektrik sərfiyyatı, soyutma', 'Потребляемая мощность, охлаждение', 'Power input, cooling'), 'type' => 'decimal', 'required' => true, 'unit' => 'kW'],
    ['slug' => 'kart-iq-giris-isitme', 'name' => $n('Elektrik sərfiyyatı, isitmə', 'Потребляемая мощность, обогрев', 'Power input, heating'), 'type' => 'decimal', 'required' => true, 'unit' => 'kW',
        'when' => ['attr' => 'kart-iq-isitme-rejimi', 'in' => ['var']]],
];
$compressor = ['slug' => 'kart-iq-kompressor', 'name' => $n('Kompressor', 'Компрессор', 'Compressor'), 'type' => 'dropdown', 'filter' => true,
    'options' => $opts([['İnverter', 'inverter'], ['Sabit sürətli', 'sabit']])];
// «Enerji səmərəliliyi — etiketdəki sinif və standart; SEER / SCOP; isitmə iqlim zonası ilə».
$scop = ['slug' => 'kart-iq-scop', 'name' => $n('SCOP', 'SCOP', 'SCOP'), 'type' => 'decimal'];
$climateZone = ['slug' => 'kart-iq-iqlim-zonasi', 'name' => $n('SCOP iqlim zonası', 'Климатическая зона SCOP', 'SCOP climate zone'), 'type' => 'dropdown',
    'options' => $opts([['Orta', 'orta'], ['İsti', 'isti'], ['Soyuq', 'soyuq']])];
$energy = [
    ['slug' => 'kart-iq-enerji-sinfi', 'name' => $n('Enerji səmərəliliyi sinfi', 'Класс энергоэффективности', 'Energy class'), 'type' => 'dropdown', 'filter' => true, 'options' => [...$opts([
        ['A+++', 'a3'], ['A++', 'a2'], ['A+', 'a1'], ['A', 'a'], ['B', 'b'], ['C', 'c'], ['D', 'd'],
    ]), $ns]] + $hint('İstehsalçının etiketindəki sinif.', 'Класс с этикетки производителя.', 'The class on the maker’s label.'),
    ['slug' => 'kart-iq-enerji-standart', 'name' => $n('Etiketin standartı', 'Стандарт этикетки', 'Label standard'), 'type' => 'text']
        + $hint('Məs. EU 626/2011.', 'Напр. EU 626/2011.', 'E.g. EU 626/2011.'),
    ['slug' => 'kart-iq-seer', 'name' => $n('SEER', 'SEER', 'SEER'), 'type' => 'decimal'],
    $scop,
    $climateZone,
];
// «Xarici temperatur aralığı — soyutma və isitmə üçün minimum–maksimum °C».
// Text, not two numbers: the engine's decimals are > 0 only, and these minimums are below zero.
$outdoorTemp = [
    ['slug' => 'kart-iq-xarici-temp-soyutma', 'name' => $n('Xarici temperatur aralığı, soyutma', 'Диапазон наружной температуры, охлаждение', 'Outdoor temperature range, cooling'), 'type' => 'text']
        + $hint('Minimum–maksimum °C, istehsalçıya görə, məs. −15…+48.', 'Мин.–макс. °C по данным производителя, напр. −15…+48.', 'Min–max °C per the maker, e.g. −15…+48.'),
    ['slug' => 'kart-iq-xarici-temp-isitme', 'name' => $n('Xarici temperatur aralığı, isitmə', 'Диапазон наружной температуры, обогрев', 'Outdoor temperature range, heating'), 'type' => 'text']
        + $hint('Minimum–maksimum °C, istehsalçıya görə, məs. −25…+24.', 'Мин.–макс. °C по данным производителя, напр. −25…+24.', 'Min–max °C per the maker, e.g. −25…+24.'),
];
$indoorModel = ['slug' => 'kart-iq-daxili-model', 'name' => $n('Daxili blokun modeli', 'Модель внутреннего блока', 'Indoor unit model'), 'type' => 'text', 'required' => true];
$outdoorModel = ['slug' => 'kart-iq-xarici-model', 'name' => $n('Xarici blokun modeli', 'Модель наружного блока', 'Outdoor unit model'), 'type' => 'text', 'required' => true];
$system = ['slug' => 'kart-iq-sistem', 'name' => $n('Sistem', 'Система', 'System'), 'type' => 'dropdown', 'required' => true, 'filter' => true,
    'options' => $opts([['Multisplit', 'multisplit'], ['VRF', 'vrf']])];

// «Hava sərfi* — m³/saat; həmin rejimdə xarici statik təzyiq Pa; sərbəst çıxış ayrıca».
$airRate = ['slug' => 'kart-iq-hava-serfi', 'name' => $n('Hava sərfi', 'Расход воздуха', 'Airflow'), 'type' => 'decimal', 'unit' => 'm³/saat', 'filter' => true];
$staticP = ['slug' => 'kart-iq-statik-tezyiq', 'name' => $n('Xarici statik təzyiq', 'Внешнее статическое давление', 'External static pressure'), 'type' => 'decimal', 'unit' => 'Pa'];
$airMode = ['slug' => 'kart-iq-hava-serfi-rejim', 'name' => $n('Hava sərfinin rejimi', 'Режим расхода воздуха', 'Airflow mode'), 'type' => 'text']
    + $hint('Sürət / rejim. Sərbəst çıxış göstəricisi ayrıca qeyd edilir.', 'Скорость / режим. Значение на свободном выходе — отдельно.', 'Speed / mode. A free-blowing figure is noted separately.');
$airflow = fn (bool $req = true): array => [['required' => $req] + $airRate, $staticP, $airMode];

// «Filtrlər — sinif / standart və uyğun dəyişmə modeli».
$filterClass = ['slug' => 'kart-iq-filtr-sinfi', 'name' => $n('Filtr sinfi / standartı', 'Класс / стандарт фильтра', 'Filter class / standard'), 'type' => 'text']
    + $hint('Mərhələlər, sinif və standart, məs. G4 + F7 (EN 779) və ya ISO ePM1 55%. Fərqli standartlar eyniləşdirilmir.', 'Ступени, класс и стандарт, напр. G4 + F7 (EN 779).', 'Stages, class and standard, e.g. G4 + F7 (EN 779).');
$filterModel = ['slug' => 'kart-iq-filtr-model', 'name' => $n('Dəyişmə filtrinin modeli', 'Модель сменного фильтра', 'Replacement filter model'), 'type' => 'text'];

// Warm floors.
$heatArea = ['slug' => 'kart-iq-isitme-sahesi', 'name' => $n('İsitmə sahəsi', 'Площадь обогрева', 'Heated area'), 'type' => 'decimal', 'required' => true, 'unit' => 'm²', 'filter' => true];
$totalPower = ['slug' => 'kart-iq-umumi-guc', 'name' => $n('Ümumi güc', 'Общая мощность', 'Total power'), 'type' => 'decimal', 'unit' => 'W'];
$specPower = ['slug' => 'kart-iq-xususi-guc', 'name' => $n('Xüsusi güc', 'Удельная мощность', 'Specific power'), 'type' => 'decimal', 'required' => true];
$floor = [
    ['slug' => 'kart-iq-doseme-montaj', 'name' => $n('Montaj üsulu', 'Способ монтажа', 'Installation'), 'type' => 'multiselect', 'required' => true,
        'options' => [...$opts([['Plitə yapışdırıcısı', 'plite-yapisdiricisi'], ['Şap', 'sap'], ['Quru montaj', 'quru']]), $other()]],
    ['slug' => 'kart-iq-uygun-ortuk', 'name' => $n('Uyğun döşəmə örtükləri', 'Подходящие покрытия пола', 'Suitable floor coverings'), 'type' => 'multiselect', 'options' => [...$opts([
        ['Plitə və keramoqranit', 'plite'], ['Təbii daş', 'tebii-das'], ['Laminat', 'laminat'], ['Parket', 'parket'], ['Vinil / SPC', 'vinil'], ['Linoleum', 'linoleum'], ['Kovrolin', 'kovrolin'],
    ]), $other()]] + $hint('Yalnız istehsalçının icazə verdiyi örtüklər.', 'Только допущенные производителем покрытия.', 'Only the coverings the maker allows.'),
];
$coldLead = ['slug' => 'kart-iq-soyuq-kabel', 'name' => $n('Soyuq kabelin uzunluğu', 'Длина холодного провода', 'Cold lead length'), 'type' => 'decimal', 'unit' => 'm'];
$thermostat = [
    ['slug' => 'kart-iq-termostat', 'name' => $n('Termostat və sensor', 'Терморегулятор и датчик', 'Thermostat and sensor'), 'type' => 'dropdown',
        'options' => $opts([['Komplektə daxildir', 'daxildir'], ['Ayrıca alınır', 'ayrica']])],
    ['slug' => 'kart-iq-termostat-model', 'name' => $n('Uyğun termostat və sensor', 'Подходящий терморегулятор и датчик', 'Compatible thermostat and sensor'), 'type' => 'text',
        'when' => ['attr' => 'kart-iq-termostat', 'in' => ['daxildir', 'ayrica']]] + $hint('Model və sensorun tipi.', 'Модель и тип датчика.', 'Model and sensor type.'),
];

// Ducts: «Kanal ölçüsü / bağlantısı* — dairəvi diametr və ya en × hündürlük, mm».
$duct = [
    ['slug' => 'kart-iq-kanal-forma', 'name' => $n('Kanal bağlantısı', 'Присоединение к воздуховоду', 'Duct connection'), 'type' => 'dropdown', 'required' => true,
        'options' => $opts([['Dairəvi', 'dairevi'], ['Düzbucaqlı', 'duzbucaqli']])],
    ['slug' => 'kart-iq-kanal-diametr', 'name' => $n('Kanal diametri', 'Диаметр воздуховода', 'Duct diameter'), 'type' => 'decimal', 'required' => true, 'unit' => 'mm', 'filter' => true,
        'when' => ['attr' => 'kart-iq-kanal-forma', 'in' => ['dairevi']]],
    ['slug' => 'kart-iq-kanal-olcu', 'name' => $n('Kanal ölçüsü (en × hündürlük)', 'Размер воздуховода (ширина × высота)', 'Duct size (width × height)'), 'type' => 'size', 'required' => true, 'unit' => 'mm',
        'when' => ['attr' => 'kart-iq-kanal-forma', 'in' => ['duzbucaqli']]] + $hint('Birinci rəqəm en, ikinci hündürlük.', 'Сначала ширина, затем высота.', 'Width first, then height.'),
];
// «Montaj ölçüləri — divar dəliyi və divar qalınlığı aralığı, mm».
$wallFit = [
    ['slug' => 'kart-iq-divar-desiyi', 'name' => $n('Divar dəliyi', 'Отверстие в стене', 'Wall hole'), 'type' => 'text', 'required' => true]
        + $hint('Diametr və ya en × hündürlük, mm.', 'Диаметр или ширина × высота, мм.', 'Diameter or width × height, mm.'),
    ['slug' => 'kart-iq-divar-min', 'name' => $n('Divar qalınlığı, min', 'Толщина стены, мин.', 'Wall thickness, min'), 'type' => 'decimal', 'required' => true, 'unit' => 'mm',
        'rules' => ['lte' => 'kart-iq-divar-maks']],
    ['slug' => 'kart-iq-divar-maks', 'name' => $n('Divar qalınlığı, maks', 'Толщина стены, макс.', 'Wall thickness, max'), 'type' => 'decimal', 'required' => true, 'unit' => 'mm'],
];

// «Temperatur aralığı — minimum–maksimum °C» (termostatlar).
$setRange = [
    ['slug' => 'kart-iq-temp-min', 'name' => $n('Tənzimləmə aralığı, min', 'Диапазон настройки, мин.', 'Set range, min'), 'type' => 'decimal', 'unit' => '°C', 'rules' => ['lte' => 'kart-iq-temp-maks']],
    ['slug' => 'kart-iq-temp-maks', 'name' => $n('Tənzimləmə aralığı, maks', 'Диапазон настройки, макс.', 'Set range, max'), 'type' => 'decimal', 'unit' => '°C'],
];

// «Uyğun model və ya sistem* — brend, seriya və model / kod; universal hissədə ölçü və şərtlər».
$compat = fn (bool $r = true): array => ['slug' => 'kart-iq-uygun-model', 'name' => $n('Uyğun model və ya sistem', 'Совместимая модель или система', 'Compatible model or system'), 'type' => 'text', 'required' => $r]
    + $hint('Brend, seriya və model / kod. Universal hissədə uyğunluq ölçüləri və şərtləri.', 'Бренд, серия и модель / код. Для универсальных — размеры и условия.', 'Brand, series and model / code. For universal parts, the size and conditions.');
$packCount = ['slug' => 'kart-iq-paketde-say', 'name' => $n('Paketdə say', 'Количество в упаковке', 'Pieces per pack'), 'type' => 'decimal', 'unit' => 'ədəd', 'rules' => ['integer' => true]];
$setCount = ['slug' => 'kart-iq-destde-say', 'name' => $n('Dəstdə say', 'Количество в комплекте', 'Pieces per set'), 'type' => 'decimal', 'unit' => 'ədəd', 'rules' => ['integer' => true]];
// «Yük həddi — kq; bir hissəyə və ya dəstə aid».
$load = fn (bool $r = false): array => [
    ['slug' => 'kart-iq-yuk-heddi', 'name' => $n('Yük həddi', 'Допустимая нагрузка', 'Load limit'), 'type' => 'decimal', 'required' => $r, 'unit' => 'kq'],
    ['slug' => 'kart-iq-yuk-aidiyyeti', 'name' => $n('Yük həddi aiddir', 'Нагрузка указана для', 'Load limit applies to'), 'type' => 'dropdown', 'required' => $r,
        'options' => $opts([['Bir hissəyə', 'hisse'], ['Dəstə', 'dest']])],
];

$kit = fn (string $code, array $items): array => [
    'slug' => 'kart-'.strtolower(str_replace('_', '-', $code)).'-komplekt', 'name' => $n('Komplektə daxildir', 'В комплекте', 'Included'), 'type' => 'multiselect',
    'hint' => $n('Yalnız qiymətə daxil olanlar.', 'Только то, что входит в цену.', 'Only what the price includes.'),
    'options' => [...$opts($items), $none()],
];
$f = fn (string $code, string $key, array $name, string $type, array $extra = []): array => ['slug' => 'kart-'.strtolower(str_replace('_', '-', $code)).'-'.$key, 'name' => $name, 'type' => $type] + $extra;
$card = fn (string $code, array $name, array $units, array $fields, array $extra = []): array => ['code' => $code, 'name' => $name, 'sale' => 'unit', 'units' => $units] + $extra + ['fields' => $fields];
$ps = ['piece', 'set'];
$typeLabel = $n('Növ', 'Тип', 'Type');
$materialLabel = $n('Material', 'Материал', 'Material');
$mountLabel = $n('Montaj', 'Монтаж', 'Mounting');
$incSep = [['Daxildir', 'daxildir'], ['Ayrıca alınır', 'ayrica']];

return [
    'section' => 'istilik-ventilyasiya-ve-kondisionerlesdirme',

    'groups' => [
        // ════ 1. İsitmə (alt bölmələr 1–6) ══════════════════════════════════
        [
            'slug' => 'radiatorlar-konvektorlar-ve-isti-doseme',
            'name' => $n('İsitmə', 'Отопление', 'Heating'),
            'classes' => [
                'istilik-radiatoru' => $card('H01', $n('İsitmə radiatorları', 'Радиаторы отопления', 'Heating radiators'), ['piece'], [
                    $f('H01', 'konstruksiya', $n('Konstruksiya', 'Конструкция', 'Design'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Panel', 'panel'], ['Seksiyalı', 'seksiyali'], ['Borulu', 'borulu'],
                    ]), $other()]]),
                    $f('H01', 'dizayner', $n('Dizayner modeli', 'Дизайнерская модель', 'Designer model'), 'dropdown', ['options' => [$yes, $no]]),
                    $f('H01', 'material', $materialLabel, 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Polad', 'polad'], ['Alüminium', 'aluminium'], ['Bimetal', 'bimetal'], ['Çuqun', 'cuqun'],
                    ]), $other()]]),
                    ...$dims(),
                    $f('H01', 'panel-tipi', $n('Panel tipi', 'Тип панели', 'Panel type'), 'text', ['when' => $when('kart-h01-konstruksiya', 'panel')]
                        + $hint('Tip kodu, məs. 11, 22, 33.', 'Код типа, напр. 11, 22, 33.', 'Type code, e.g. 11, 22, 33.')),
                    $f('H01', 'seksiya', $n('Seksiya sayı', 'Число секций', 'Sections'), 'decimal', ['unit' => 'ədəd', 'rules' => ['integer' => true],
                        'when' => $when('kart-h01-konstruksiya', 'seksiyali')]),
                    $f('H01', 'satis', $n('Qiymət aiddir', 'Цена указана за', 'Price is for'), 'dropdown', ['required' => true, 'options' => [
                        $a('Bütöv radiator', ['slug' => 'butov']),
                        $a('Bir seksiya', ['slug' => 'seksiya', 'when' => ['attr' => 'kart-h01-konstruksiya', 'in' => ['seksiyali']]]),
                    ]] + $hint('Seksiya ilə satışda güc və qiymət bir seksiyaya, bütöv satışda bütün radiatora aiddir.', 'При продаже секциями мощность и цена — за одну секцию.', 'Sold by the section: power and price are per section.')),
                    ['required' => true, 'unit' => 'W'] + $heat,
                    ['required' => true] + $tempMode,
                    ...$waterConn(),
                    ...$limits(),
                    ...$look,
                    $kit('H01', [['Radiator', 'radiator'], ['Kronşteyn', 'kronsteyn'], ['Tıxac', 'tixac'], ['Havaçıxaran', 'havacixaran'], ['Klapan', 'klapan']]),
                    ...$guarantee,
                ]),
                'su-ile-isleyen-konvektorlar' => $card('H02', $n('Su ilə işləyən konvektorlar', 'Водяные конвекторы', 'Water convectors'), $ps, [
                    $f('H02', 'montaj', $mountLabel, 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Divar', 'divar'], ['Döşəməüstü', 'dosemeustu'], ['Döşəmədaxili', 'dosemedaxili'],
                    ])]),
                    $f('H02', 'konveksiya', $n('Konveksiya', 'Конвекция', 'Convection'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Təbii', 'tebii'], ['Ventilyatorlu', 'ventilyatorlu'],
                    ])]),
                    ...$dims(),
                    ['required' => true, 'unit' => 'W'] + $heat,
                    ['required' => true] + $tempMode,
                    $f('H02', 'deyisdirici', $n('İstilik dəyişdirici', 'Теплообменник', 'Heat exchanger'), 'dropdown', ['options' => [...$opts([
                        ['Mis-alüminium', 'mis-aluminium'], ['Polad', 'polad'],
                    ]), $other()]]),
                    ...$waterConn(),
                    ...$limits(),
                    $f('H02', 'elektrik', $n('Elektrik qidalanması', 'Электропитание', 'Power supply'), 'dropdown', ['options' => $opts([
                        ['Tələb olunmur', 'teleb-olunmur'], ['Tələb olunur', 'teleb-olunur'],
                    ])]),
                    ...$supply(true, $when('kart-h02-elektrik', 'teleb-olunur')),
                    $f('H02', 'ventilyator-gucu', $n('Ventilyatorun gücü', 'Мощность вентилятора', 'Fan power'), 'decimal', ['unit' => 'W',
                        'when' => $when('kart-h02-elektrik', 'teleb-olunur')]),
                    ...$look,
                    $f('H02', 'barmaqliq', $n('Barmaqlıq', 'Решётка', 'Grille'), 'dropdown', ['options' => $opts($incSep)]),
                    $f('H02', 'barmaqliq-qeyd', $n('Barmaqlığın materialı və rəngi', 'Материал и цвет решётки', 'Grille material and colour'), 'text',
                        ['when' => $when('kart-h02-barmaqliq', 'daxildir', 'ayrica')]),
                    $kit('H02', [['Konvektor', 'konvektor'], ['Korpus', 'korpus'], ['Barmaqlıq', 'barmaqliq'], ['İdarəetmə', 'idareetme'], ['Bərkitmə', 'berkitme']]),
                    ...$guarantee,
                ]),
                'desmalqurudanlar' => $card('H03', $n('Dəsmalqurudanlar', 'Полотенцесушители', 'Towel warmers'), $ps, [
                    $f('H03', 'nov', $typeLabel, 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Su ilə', 'su'], ['Elektrik', 'elektrik'], ['Kombinə', 'kombine'],
                    ])]),
                    $f('H03', 'material', $materialLabel, 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Polad', 'polad'], ['Paslanmayan polad', 'paslanmayan-polad'], ['Alüminium', 'aluminium'],
                    ]), $other()]]),
                    ...$dims(),
                    ...$look,
                    ['required' => true, 'unit' => 'W', 'when' => $when('kart-h03-nov', 'su', 'kombine')] + $heat
                        + $hint('Su rejimində.', 'В водяном режиме.', 'In water mode.'),
                    ['required' => true, 'when' => $when('kart-h03-nov', 'su', 'kombine')] + $tempMode,
                    ['required' => true, 'unit' => 'W', 'when' => $when('kart-h03-nov', 'elektrik', 'kombine')] + $elPower
                        + $hint('Elektrik rejimində.', 'В электрическом режиме.', 'In electric mode.'),
                    ...$waterConn(true, $when('kart-h03-nov', 'su', 'kombine')),
                    ...$limits($when('kart-h03-nov', 'su', 'kombine')),
                    ['required' => true, 'unit' => 'V', 'when' => $when('kart-h03-nov', 'elektrik', 'kombine')] + $supply()[0],
                    $f('H03', 'elektrik-qosulma', $n('Elektrik bağlantısı', 'Электроподключение', 'Electrical connection'), 'dropdown', ['options' => $opts([
                        ['Kabel və fiş', 'kabel-fis'], ['Gizli bağlantı', 'gizli'],
                    ]), 'when' => $when('kart-h03-nov', 'elektrik', 'kombine')]),
                    $ip,
                    $control,
                    ...$smartBlock,
                    $kit('H03', [['Qızdırıcı element', 'qizdirici'], ['Termostat', 'termostat'], ['Klapan', 'klapan'], ['Kronşteyn', 'kronsteyn']]),
                    ...$guarantee,
                ]),
                'elektrik-qizdiricilari' => $card('H04', $n('Elektrik qızdırıcıları', 'Электрические обогреватели', 'Electric heaters'), $ps, [
                    $f('H04', 'nov', $typeLabel, 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Elektrik konvektoru', 'konvektor'], ['Yağ radiatoru', 'yag-radiatoru'], ['İnfraqırmızı', 'infraqirmizi'], ['Ventilyatorlu', 'ventilyatorlu'],
                    ]), $other()]]),
                    ['required' => true, 'unit' => 'W', 'filter' => true] + $elPower
                        + $hint('Maksimum elektrik gücü. Təxmini isitmə sahəsi gücdən avtomatik hesablanmır.', 'Максимальная мощность. Площадь обогрева не рассчитывается автоматически.', 'Maximum power. The heated area is not derived from it.'),
                    ...$supply(),
                    $f('H04', 'montaj', $mountLabel, 'dropdown', ['required' => true, 'options' => $opts([
                        ['Divar', 'divar'], ['Döşəmə', 'doseme'], ['Tavan', 'tavan'], ['Daşınan', 'dasinan'],
                    ])]),
                    ...$dims(),
                    ...$look,
                    $control,
                    $ip,
                    $f('H04', 'funksiyalar', $n('Funksiyalar', 'Функции', 'Features'), 'multiselect', ['options' => [...$opts([
                        ['Termostat', 'termostat'], ['Taymer', 'taymer'], ['Aşmadan qorunma', 'asmadan'], ['Həddən artıq qızmadan qorunma', 'qizmadan'],
                    ]), $other()]]),
                    ...$smartBlock,
                    $kit('H04', [['Ayaqlar', 'ayaqlar'], ['Təkərlər', 'tekerler'], ['Kronşteyn', 'kronsteyn'], ['Pult', 'pult']]),
                    ...$guarantee,
                ]),
                'qazan' => $card('H05_1', $n('Qaz isitmə qazanları', 'Газовые котлы отопления', 'Gas boilers'), $ps, [
                    $f('H05_1', 'texnologiya', $n('Texnologiya', 'Технология', 'Technology'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Kondensasiyalı', 'kondensasiyali'], ['Ənənəvi', 'eneneevi'],
                    ])]),
                    $f('H05_1', 'dovre', $n('Dövrə', 'Контуры', 'Circuits'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Təkdövrəli', 'tek'], ['İkidövrəli', 'iki'],
                    ])]),
                    $f('H05_1', 'yanacaq', $n('Yanacaq', 'Топливо', 'Fuel'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Təbii qaz', 'tebii-qaz'], ['LPG', 'lpg'], ['Hər ikisinə uyğun', 'her-ikisi'],
                    ])]),
                    $f('H05_1', 'yanacaq-qeyd', $n('Zavod sazlaması və çevirmə dəsti', 'Заводская настройка и комплект перевода', 'Factory setting and conversion kit'), 'text',
                        $hint('Məs. «Təbii qaz üçün sazlanıb; LPG çevirmə dəsti ayrıca».', 'Напр. «Настроен на природный газ; комплект LPG отдельно».', 'E.g. «Set for natural gas; LPG kit sold separately».')),
                    $f('H05_1', 'guc-min', $n('İstilik gücü, min', 'Тепловая мощность, мин.', 'Heat output, min'), 'decimal', ['unit' => 'kW', 'rules' => ['lte' => 'kart-iq-istilik-gucu']]
                        + $hint('Faydalı güc.', 'Полезная мощность.', 'Useful output.')),
                    ['required' => true, 'unit' => 'kW'] + $heat + $hint('Maksimum faydalı güc.', 'Максимальная полезная мощность.', 'Maximum useful output.'),
                    ['required' => true] + $tempMode,
                    $f('H05_1', 'isti-su', $n('İsti su məhsuldarlığı', 'Производительность ГВС', 'Hot water output'), 'decimal', ['unit' => 'l/dəq', 'when' => $when('kart-h05-1-dovre', 'iki')]),
                    $f('H05_1', 'isti-su-dt', $n('İsti su üçün ΔT', 'ΔT для ГВС', 'Hot water ΔT'), 'decimal', ['unit' => '°C', 'when' => $when('kart-h05-1-dovre', 'iki')]),
                    $f('H05_1', 'montaj', $mountLabel, 'dropdown', ['options' => $opts([['Divar', 'divar'], ['Döşəmə', 'doseme']])]),
                    $f('H05_1', 'kamera', $n('Yanma kamerası', 'Камера сгорания', 'Combustion chamber'), 'dropdown', ['options' => $opts([['Açıq', 'aciq'], ['Qapalı', 'qapali']])]),
                    $f('H05_1', 'baca', $n('Tüstü çıxışı', 'Дымоход', 'Flue'), 'text', $hint('Uyğun baca sistemi, diametr və ya model kodu.', 'Подходящая система дымохода, диаметр или код.', 'Matching flue system, diameter or model code.')),
                    ...$supply(),
                    ...$dims(),
                    ...$limits(),
                    ...$smartBlock,
                    $kit('H05_1', [['Nasos', 'nasos'], ['Genişlənmə çəni', 'genislenme-ceni'], ['İdarəetmə', 'idareetme'], ['Baca dəsti', 'baca']]),
                    ...$guarantee,
                ]),
                'elektrik-isitme-qazanlari' => $card('H05_2', $n('Elektrik isitmə qazanları', 'Электрические котлы отопления', 'Electric boilers'), $ps, [
                    ['required' => true, 'unit' => 'kW'] + $heat + $hint('Faydalı nominal güc.', 'Полезная номинальная мощность.', 'Rated useful output.'),
                    ['required' => true, 'unit' => 'kW'] + $elPower + $hint('Maksimum giriş gücü.', 'Максимальная потребляемая мощность.', 'Maximum input power.'),
                    ...$supply(),
                    $f('H05_2', 'dovre', $n('Dövrə', 'Контуры', 'Circuits'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Yalnız isitmə', 'isitme'], ['Xarici isti su çəni ilə işləyə bilən', 'xarici-cen'],
                    ]), $other()]]),
                    $f('H05_2', 'montaj', $mountLabel, 'dropdown', ['options' => $opts([['Divar', 'divar'], ['Döşəmə', 'doseme']])]),
                    ...$dims(),
                    ...$limits(),
                    $f('H05_2', 'qosulma', $n('Qoşulma ölçüsü', 'Присоединительный размер', 'Connection size'), 'text',
                        $hint('İstehsalçıya görə: yiv ölçüsü, düym, və ya boru diametri, mm.', 'По данным производителя: резьба в дюймах или диаметр трубы в мм.', 'Per the maker: thread in inches or pipe diameter in mm.')),
                    $control,
                    ...$smartBlock,
                    $kit('H05_2', [['Nasos', 'nasos'], ['Genişlənmə çəni', 'genislenme-ceni'], ['İdarəetmə', 'idareetme']]),
                    ...$guarantee,
                ]),
                'istilik-nasosu' => $card('H05_3', $n('İsitmə üçün istilik nasosları', 'Тепловые насосы для отопления', 'Heat pumps for heating'), $ps, [
                    $f('H05_3', 'nov', $typeLabel, 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Hava-su', 'hava-su'], ['Torpaq-su', 'torpaq-su'], ['Su-su', 'su-su'],
                    ]), $other()]]),
                    $f('H05_3', 'konstruksiya', $n('Konstruksiya', 'Конструкция', 'Design'), 'dropdown', ['required' => true, 'options' => $opts([['Monoblok', 'monoblok'], ['Split', 'split']])]),
                    $f('H05_3', 'satis', $n('Satış forması', 'Форма продажи', 'Sold as'), 'dropdown', ['required' => true, 'options' => $opts([['Tam sistem', 'tam'], ['Ayrıca blok', 'blok']])]),
                    $f('H05_3', 'bloklar', $n('Uyğun bloklar', 'Совместимые блоки', 'Matching units'), 'text', ['required' => true]
                        + $hint('Daxili və xarici blokun model kodları; ayrıca satılan hissələr göstərilir.', 'Коды моделей внутреннего и наружного блоков; отдельно продаваемые части.', 'Indoor and outdoor model codes; parts sold separately.')),
                    ['required' => true, 'unit' => 'kW'] + $heat,
                    ['required' => true] + $hint('Sınaq şərti: mənbə temperaturu / suyun çıxış temperaturu, °C, məs. A7/W35.', 'Условия испытания: температура источника / воды на выходе, напр. A7/W35.', 'Test condition: source / leaving water °C, e.g. A7/W35.') + $tempMode,
                    $f('H05_3', 'cop', $n('COP', 'COP', 'COP'), 'decimal'),
                    $f('H05_3', 'cop-sert', $n('COP sınaq şərti', 'Условия испытания COP', 'COP test condition'), 'text', $hint('Məs. A7/W35.', 'Напр. A7/W35.', 'E.g. A7/W35.')),
                    $scop,
                    $climateZone,
                    $f('H05_3', 'scop-su', $n('SCOP üçün su temperaturu', 'Температура воды для SCOP', 'Water temperature for SCOP'), 'decimal', ['unit' => '°C']),
                    $maxWaterTemp,
                    ...$supply(),
                    $refrigerant,
                    ...$outdoorTemp,
                    ...$dims($indoorHint),
                    ['required' => true, 'when' => $when('kart-h05-3-konstruksiya', 'split')] + $outdoorDims,
                    ...$noise,
                    $f('H05_3', 'rejimler', $n('Rejimlər', 'Режимы', 'Modes'), 'multiselect', ['options' => $opts([
                        ['İsitmə', 'isitme'], ['Soyutma', 'soyutma'], ['İsti su hazırlama', 'isti-su'],
                    ])]),
                    ...$smartBlock,
                    $kit('H05_3', [['Xarici blok', 'xarici-blok'], ['Hidromodul', 'hidromodul'], ['İdarəetmə', 'idareetme'], ['Ehtiyat qızdırıcı', 'ehtiyat-qizdirici'], ['Çən', 'cen']]),
                    ...$guarantee,
                ]),
                'isti-doseme' => $card('H06_1', $n('Elektrik isti döşəmə matları', 'Электрические нагревательные маты', 'Electric heating mats'), ['set', 'piece'], [
                    $heatArea + $hint('Matın faktiki sahəsi; otağın ümumi sahəsi deyil.', 'Фактическая площадь мата, не площадь комнаты.', 'The mat’s actual area, not the room’s.'),
                    ['required' => true] + $totalPower,
                    ['unit' => 'W/m²'] + $specPower,
                    ...$supply(),
                    ['required' => true, 'unit' => 'm'] + $length,
                    ['required' => true, 'unit' => 'm'] + $width,
                    ['required' => true] + $thickness,
                    ...$floor,
                    $coldLead,
                    ...$thermostat,
                    $kit('H06_1', [['Mat', 'mat'], ['Termostat', 'termostat'], ['Döşəmə sensoru', 'sensor'], ['Montaj hissələri', 'montaj']]),
                    ...$guarantee,
                ]),
                'isti-doseme-isitme-kabelleri' => $card('H06_2', $n('İsti döşəmə isitmə kabelləri', 'Нагревательные кабели для тёплого пола', 'Underfloor heating cables'), ['set', 'lm'], [
                    $f('H06_2', 'satis', $n('Satış forması', 'Форма продажи', 'Sold as'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Sabit uzunluqlu dəst', 'dest'], ['Metrlə', 'metr'],
                    ])] + $hint('Sabit uzunluqlu kabel metrlə satılan kabeldən ayrıca variantdır.', 'Кабель фиксированной длины — отдельный вариант от метражного.', 'A fixed-length cable is a separate listing from cable sold by the metre.')),
                    $f('H06_2', 'nov', $n('Kabel növü', 'Тип кабеля', 'Cable type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Təkdamarlı', 'tekdamarli'], ['İkidamarlı', 'ikidamarli'], ['Özünütənzimləyən', 'ozunutenzimleyen'],
                    ]), $other()]]),
                    ['required' => true, 'unit' => 'm', 'when' => $when('kart-h06-2-satis', 'dest')] + $length
                        + $hint('İsitmə kabelinin uzunluğu.', 'Длина нагревательного кабеля.', 'Heating cable length.'),
                    ['required' => true, 'when' => $when('kart-h06-2-satis', 'dest')] + $totalPower,
                    ['unit' => 'W/m'] + $specPower,
                    ...$supply(),
                    $f('H06_2', 'diametr', $n('Kabel diametri', 'Диаметр кабеля', 'Cable diameter'), 'decimal', ['unit' => 'mm']),
                    ...$floor,
                    $coldLead,
                    ...$thermostat,
                    $kit('H06_2', [['Kabel', 'kabel'], ['Termostat', 'termostat'], ['Sensor', 'sensor'], ['Montaj lenti', 'lent']]),
                    ...$guarantee,
                ], ['unit_when' => ['set' => $when('kart-h06-2-satis', 'dest'), 'lm' => $when('kart-h06-2-satis', 'metr')]]),
                'isti-doseme-isitme-plyonkalari' => $card('H06_3', $n('İsti döşəmə isitmə plyonkaları', 'Плёночный тёплый пол', 'Underfloor heating films'), ['m2', 'roll', 'set'], [
                    $heatArea,
                    ['required' => true, 'unit' => 'm'] + $width,
                    ['required' => true, 'unit' => 'm'] + $length,
                    ['unit' => 'W/m²'] + $specPower,
                    ...$supply(),
                    $thickness,
                    $f('H06_3', 'kesim', $n('Kəsim addımı', 'Шаг резки', 'Cut step'), 'decimal', ['unit' => 'mm']),
                    ...$floor,
                    $f('H06_3', 'maks-temp', $n('Maksimum iş temperaturu', 'Макс. рабочая температура', 'Maximum working temperature'), 'decimal', ['unit' => '°C']),
                    $f('H06_3', 'ortuk-limit', $n('Örtük üçün icazə verilən temperatur', 'Допустимая температура для покрытия', 'Temperature limit for the covering'), 'decimal', ['unit' => '°C']),
                    ...$thermostat,
                    $kit('H06_3', [['Plyonka', 'plyonka'], ['Kontaktlar', 'kontaktlar'], ['İzolyasiya', 'izolyasiya'], ['Termostat', 'termostat'], ['Sensor', 'sensor']]),
                    ...$guarantee,
                ]),
                'su-ile-isleyen-isti-doseme-destleri' => $card('H06_4', $n('Su ilə işləyən isti döşəmə dəstləri', 'Комплекты водяного тёплого пола', 'Water underfloor heating kits'), ['set'], [
                    $heatArea + $hint('Dəstin nəzərdə tutulan sahəsi, istehsalçının boru addımı ilə.', 'Расчётная площадь комплекта при шаге трубы производителя.', 'The kit’s design area at the maker’s pipe spacing.'),
                    $f('H06_4', 'addim', $n('Boru addımı', 'Шаг укладки трубы', 'Pipe spacing'), 'decimal', ['unit' => 'mm']),
                    $f('H06_4', 'boru-material', $n('Borunun materialı', 'Материал трубы', 'Pipe material'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['PE-RT', 'pe-rt'], ['PEX', 'pex'], ['Metal-plastik', 'metal-plastik'], ['Mis', 'mis'],
                    ]), $other()]]),
                    $f('H06_4', 'boru-diametr', $n('Borunun xarici diametri', 'Наружный диаметр трубы', 'Pipe outer diameter'), 'decimal', ['required' => true, 'unit' => 'mm']),
                    $f('H06_4', 'boru-divar', $n('Borunun divar qalınlığı', 'Толщина стенки трубы', 'Pipe wall thickness'), 'decimal', ['required' => true, 'unit' => 'mm']),
                    $f('H06_4', 'boru-uzunluq', $n('Borunun ümumi uzunluğu', 'Общая длина трубы', 'Total pipe length'), 'decimal', ['required' => true, 'unit' => 'm']),
                    $f('H06_4', 'kontur', $n('Kontur sayı', 'Число контуров', 'Loops'), 'decimal', ['required' => true, 'unit' => 'ədəd', 'rules' => ['integer' => true]]),
                    $f('H06_4', 'montaj', $n('Montaj sistemi', 'Система монтажа', 'Installation system'), 'dropdown', ['options' => [...$opts([
                        ['Yaş şap', 'yas-sap'], ['Quru sistem', 'quru'],
                    ]), $other()]]),
                    $f('H06_4', 'kollektor', $n('Kollektor və qarışdırma qrupu', 'Коллектор и смесительный узел', 'Manifold and mixing unit'), 'dropdown', ['options' => $opts($incSep)]),
                    $f('H06_4', 'kollektor-model', $n('Kollektorun modeli', 'Модель коллектора', 'Manifold model'), 'text', ['when' => $when('kart-h06-4-kollektor', 'daxildir', 'ayrica')]),
                    $f('H06_4', 'cixis', $n('Kollektorun çıxış sayı', 'Число выходов коллектора', 'Manifold outlets'), 'decimal', ['unit' => 'ədəd', 'rules' => ['integer' => true],
                        'when' => $when('kart-h06-4-kollektor', 'daxildir', 'ayrica')]),
                    $f('H06_4', 'idareetme', $n('İdarəetmə', 'Управление', 'Controls'), 'dropdown', ['options' => $opts($incSep)]),
                    $f('H06_4', 'idareetme-model', $n('Termostat və aktuator modelləri', 'Модели термостата и актуатора', 'Thermostat and actuator models'), 'text',
                        ['when' => $when('kart-h06-4-idareetme', 'daxildir', 'ayrica')]),
                    $f('H06_4', 'terkib', $n('Dəstin tərkibi', 'Состав комплекта', 'Kit contents'), 'textarea', ['required' => true]
                        + $hint('Hər hissənin adı, modeli və miqdarı.', 'Название, модель и количество каждой части.', 'Each part’s name, model and quantity.')),
                    ...$guarantee,
                ]),
            ],
        ],

        // ════ 2. Kondisionerləşdirmə (alt bölmələr 7–9) ═════════════════════
        [
            'slug' => 'kondisionerlesdirme',
            'name' => $n('Kondisionerləşdirmə', 'Кондиционирование', 'Air conditioning'),
            'classes' => [
                'kondisioner-split-sistem-multi-split-vrf' => $card('H07_1', $n('Divar tipli split kondisionerlər', 'Настенные сплит-системы', 'Wall split air conditioners'), ['set'], [
                    $indoorModel,
                    $outdoorModel,
                    ...$capacity(),
                    ...$consumption,
                    ['required' => true] + $compressor,
                    ...$energy,
                    $refrigerant,
                    ...$outdoorTemp,
                    ...$supply(),
                    ...$dims($indoorHint),
                    ['required' => true] + $outdoorDims,
                    ...$noise,
                    $control,
                    ...$smartBlock,
                    $kit('H07_1', [['Daxili blok', 'daxili-blok'], ['Xarici blok', 'xarici-blok'], ['Pult', 'pult'], ['Montaj lövhəsi', 'montaj-lovhesi']]),
                    ...$guarantee,
                ]),
                'mobil-kondisionerler' => $card('H07_2', $n('Mobil kondisionerlər', 'Мобильные кондиционеры', 'Portable air conditioners'), $ps, [
                    $f('H07_2', 'konstruksiya', $n('Konstruksiya', 'Конструкция', 'Design'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Tək hava borulu', 'tek'], ['İki hava borulu', 'iki'],
                    ]), $other()]] + $hint('Evaporativ hava soyuducusu kondisioner kimi yerləşdirilmir.', 'Испарительный охладитель не размещается как кондиционер.', 'Evaporative coolers are not listed as air conditioners.')),
                    ...$capacity(),
                    ...$consumption,
                    ...$supply(),
                    ...$energy,
                    $refrigerant,
                    ...$dims(),
                    ...$noise,
                    $f('H07_2', 'kondensat', $n('Kondensatın çıxarılması', 'Отвод конденсата', 'Condensate removal'), 'dropdown', ['options' => $opts([
                        ['Çən', 'cen'], ['Drenaj', 'drenaj'], ['Buxarlandırma', 'buxarlandirma'], ['Kombinə', 'kombine'],
                    ])]),
                    $control,
                    ...$smartBlock,
                    $kit('H07_2', [['İsti hava borusu', 'boru'], ['Pəncərə dəsti', 'pencere'], ['Drenaj şlanqı', 'slanq'], ['Pult', 'pult']]),
                    ...$guarantee,
                ]),
                'kaset-kanal-ve-doseme-tavan-kondisionerleri' => $card('H07_3', $n('Kaset, kanal və döşəmə-tavan kondisionerləri', 'Кассетные, канальные и напольно-потолочные кондиционеры', 'Cassette, ducted and floor-ceiling air conditioners'), ['set'], [
                    $f('H07_3', 'tip', $n('Daxili blok tipi', 'Тип внутреннего блока', 'Indoor unit type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Kaset', 'kaset'], ['Kanal', 'kanal'], ['Döşəmə-tavan', 'doseme-tavan'],
                    ])] + $hint('Tam mono-split dəsti; ayrıca daxili blok H08_2 kartındadır.', 'Полный моносплит; отдельный внутренний блок — карточка H08_2.', 'A full mono-split set; a separate indoor unit is card H08_2.')),
                    $indoorModel,
                    $outdoorModel,
                    ...$capacity(),
                    ...$consumption,
                    $compressor,
                    ...$energy,
                    $refrigerant,
                    ...$supply(),
                    ...$dims($indoorHint),
                    ['required' => true] + $outdoorDims,
                    ...$noise,
                    ['when' => $when('kart-h07-3-tip', 'kanal')] + $staticP + $hint('Kanal blokunun statik təzyiqi.', 'Статическое давление канального блока.', 'The ducted unit’s static pressure.'),
                    $control,
                    ...$smartBlock,
                    $kit('H07_3', [['Daxili blok', 'daxili-blok'], ['Xarici blok', 'xarici-blok'], ['Dekorativ panel', 'panel'], ['Drenaj pompası', 'pompa'], ['Pult', 'pult']]),
                    ...$guarantee,
                ]),
                'vrfvrv-sistemi-xarici-blok' => $card('H08_1', $n('Multisplit və VRF xarici blokları', 'Наружные блоки мультисплит и VRF', 'Multi-split and VRF outdoor units'), ['piece'], [
                    $system,
                    $hint('Brend, seriya və model / kod. Uyğunluq istehsalçının kombinasiya cədvəli ilə müəyyən edilir.', 'Бренд, серия и модель. Совместимость — по таблице комбинаций производителя.', 'Brand, series and model. Compatibility follows the maker’s combination table.') + $compat(),
                    ...$capacity(),
                    ...$consumption,
                    $f('H08_1', 'blok-sayi', $n('Maksimum daxili blok sayı', 'Макс. число внутренних блоков', 'Maximum indoor units'), 'decimal', ['required' => true, 'unit' => 'ədəd', 'rules' => ['integer' => true]]),
                    $f('H08_1', 'qosulma', $n('İcazə verilən qoşulma gücü / nisbəti', 'Допустимая мощность / коэффициент подключения', 'Allowed connected capacity / ratio'), 'text', ['required' => true]
                        + $hint('Məs. 50–130% və ya maks. 14 kW.', 'Напр. 50–130% или макс. 14 кВт.', 'E.g. 50–130% or max 14 kW.')),
                    $f('H08_1', 'rejim', $n('Rejim', 'Режим', 'Operation'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Bütün bloklar üçün ümumi rejim', 'umumi'], ['Eyni vaxtda fərqli rejimlər', 'ferqli'],
                    ])]),
                    $refrigerant,
                    ...$supply(),
                    ...$outdoorTemp,
                    ...$dims(),
                    ...$noise,
                    $f('H08_1', 'boru-umumi', $n('Boru xəttinin ümumi uzunluğu', 'Общая длина трассы', 'Total piping length'), 'decimal', ['unit' => 'm']),
                    $f('H08_1', 'boru-en-uzun', $n('Ən uzun xətt', 'Самая длинная трасса', 'Longest run'), 'decimal', ['unit' => 'm']),
                    $f('H08_1', 'hundurluk-ferqi', $n('Hündürlük fərqi', 'Перепад высот', 'Height difference'), 'decimal', ['unit' => 'm']),
                    $kit('H08_1', [['Xarici blok', 'xarici-blok'], ['Bağlantı hissələri', 'baglanti']]),
                    ...$guarantee,
                ]),
                'multisplit-ve-vrf-daxili-bloklari' => $card('H08_2', $n('Multisplit və VRF daxili blokları', 'Внутренние блоки мультисплит и VRF', 'Multi-split and VRF indoor units'), ['piece'], [
                    $system,
                    $f('H08_2', 'tip', $n('Blok tipi', 'Тип блока', 'Unit type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Divar', 'divar'], ['Kaset', 'kaset'], ['Kanal', 'kanal'], ['Döşəmə', 'doseme'], ['Tavan', 'tavan'],
                    ]), $other()]]),
                    $compat(),
                    ...$capacity(),
                    ...$supply(),
                    ...$dims(),
                    ...$noise,
                    $airRate,
                    $airMode,
                    ['when' => $when('kart-h08-2-tip', 'kanal')] + $staticP,
                    $control,
                    ...$smartBlock,
                    $kit('H08_2', [['Daxili blok', 'daxili-blok'], ['Dekorativ panel', 'panel'], ['Drenaj pompası', 'pompa'], ['Pult', 'pult']]),
                    ...$guarantee,
                ]),
                'multisplit-ve-vrf-destleri' => $card('H08_3', $n('Multisplit və VRF dəstləri', 'Комплекты мультисплит и VRF', 'Multi-split and VRF sets'), ['set'], [
                    $system,
                    $f('H08_3', 'xarici', $n('Xarici bloklar', 'Наружные блоки', 'Outdoor units'), 'textarea', ['required' => true]
                        + $hint('Hər modelin kodu və sayı.', 'Код и количество каждой модели.', 'Each model’s code and count.')),
                    $f('H08_3', 'daxili', $n('Daxili bloklar', 'Внутренние блоки', 'Indoor units'), 'textarea', ['required' => true]
                        + $hint('Hər modelin tipi, kodu və sayı.', 'Тип, код и количество каждой модели.', 'Each model’s type, code and count.')),
                    ...array_map(fn ($fd) => $fd['slug'] === 'kart-iq-soyutma-gucu'
                        ? $hint('Dəstin gücü; daxili blokların nominal güclərinin sadə cəmi deyil.', 'Мощность комплекта, не сумма мощностей внутренних блоков.', 'The set’s capacity, not the sum of the indoor units.') + $fd
                        : $fd, $capacity()),
                    $f('H08_3', 'kombinasiya', $n('Kombinasiya təsdiqi', 'Подтверждение комбинации', 'Combination approval'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Kombinasiya kodu', 'kod'], ['Cədvəl / fayl', 'fayl'],
                    ])] + $hint('İstehsalçının uyğun kombinasiya kodu və ya cədvəli.', 'Код или таблица комбинаций производителя.', 'The maker’s combination code or table.')),
                    $f('H08_3', 'kombinasiya-kodu', $n('Kombinasiya kodu', 'Код комбинации', 'Combination code'), 'text', ['required' => true, 'when' => $when('kart-h08-3-kombinasiya', 'kod')]),
                    $f('H08_3', 'kombinasiya-senedi', $n('Kombinasiya cədvəli', 'Таблица комбинаций', 'Combination table'), 'document', ['required' => true, 'when' => $when('kart-h08-3-kombinasiya', 'fayl')]),
                    ...$supply(),
                    ...$energy,
                    $refrigerant,
                    $control,
                    ...$smartBlock,
                    $f('H08_3', 'terkib', $n('Dəstin tərkibi', 'Состав комплекта', 'Kit contents'), 'textarea', ['required' => true]
                        + $hint('Bloklar, pultlar, panellər və bölüşdürücülər; yalnız qiymətə daxil olanlar.', 'Блоки, пульты, панели и разветвители; только входящие в цену.', 'Units, remotes, panels and branch joints; only what the price includes.')),
                    ...$guarantee,
                ]),
                'fankoyl' => $card('H09', $n('Fankoyllar', 'Фанкойлы', 'Fan coil units'), $ps, [
                    $f('H09', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Divar', 'divar'], ['Kaset', 'kaset'], ['Kanal', 'kanal'], ['Döşəmə-tavan', 'doseme-tavan'],
                    ])] + $hint('Su ilə işləyir; soyuducu agentli daxili blok deyil.', 'Работает на воде; не фреоновый внутренний блок.', 'Water-based; not a refrigerant indoor unit.')),
                    $f('H09', 'su-sistemi', $n('Su sistemi', 'Водяная система', 'Pipe system'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['2 borulu', '2-borulu'], ['4 borulu', '4-borulu'],
                    ])]),
                    ...array_map(fn ($fd) => $fd['slug'] === 'kart-iq-soyutma-gucu'
                        ? $hint('Soyutmada ümumi güc.', 'Общая мощность охлаждения.', 'Total cooling capacity.') + $fd
                        : $fd, $capacity(false)),
                    $f('H09', 'hiss-olunan', $n('Hiss olunan soyutma gücü', 'Явная холодопроизводительность', 'Sensible cooling capacity'), 'decimal', ['unit' => 'kW',
                        'rules' => ['lte' => 'kart-iq-soyutma-gucu']]),
                    $f('H09', 'sinaq', $n('Sınaq şərti', 'Условия испытания', 'Test condition'), 'text',
                        $hint('Su / hava temperaturu və fan sürəti.', 'Температура воды / воздуха и скорость вентилятора.', 'Water / air temperature and fan speed.')),
                    $airRate,
                    $airMode,
                    ['when' => $when('kart-h09-tip', 'kanal')] + $staticP,
                    ...$supply(),
                    ['unit' => 'W'] + $elPower + $hint('Fan və elektronika.', 'Вентилятор и электроника.', 'Fan and electronics.'),
                    ...$dims(),
                    ...$noise,
                    $thread,
                    $f('H09', 'teref', $n('Bağlantı tərəfi', 'Сторона подключения', 'Connection side'), 'dropdown', ['options' => $opts([['Sağ', 'sag'], ['Sol', 'sol']])]),
                    $control,
                    ...$smartBlock,
                    $kit('H09', [['Fankoyl', 'fankoyl'], ['Dekorativ panel', 'panel'], ['Klapan qrupu', 'klapan'], ['Drenaj pompası', 'pompa'], ['Pult', 'pult']]),
                    ...$guarantee,
                ]),
            ],
        ],

        // ════ 3. Havalandırma (alt bölmələr 10–12) ═════════════════════════
        [
            'slug' => 'ventilyasiya-avadanligi',
            'name' => $n('Havalandırma', 'Вентиляция', 'Ventilation'),
            'classes' => [
                'ventilyatorlar-ve-ventilyasiya-qurgulari' => $card('H10', $n('Sorucu ventilyatorlar', 'Вытяжные вентиляторы', 'Extract fans'), $ps, [
                    $f('H10', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Divar / tavan üçün', 'divar-tavan'], ['Kanal üçün', 'kanal'],
                    ])]),
                    $f('H10', 'konstruksiya', $n('Konstruksiya', 'Конструкция', 'Design'), 'dropdown', ['options' => $opts([
                        ['Oxlu', 'oxlu'], ['Mərkəzdənqaçma', 'merkezdenqacma'], ['Qarışıq axınlı', 'qarisiq'],
                    ])]),
                    ...$duct,
                    ...$airflow(),
                    ...$supply(),
                    ['unit' => 'W'] + $elPower,
                    ...$noise,
                    $ip,
                    ...$dims(),
                    $f('H10', 'funksiyalar', $n('İdarəetmə və funksiyalar', 'Управление и функции', 'Controls and features'), 'multiselect', ['options' => $opts([
                        ['Açar', 'acar'], ['Taymer', 'taymer'], ['Rütubət sensoru', 'rutubet'], ['Hərəkət sensoru', 'hereket'], ['Sürət idarəsi', 'suret'], ['Geri axın klapanı', 'geri-axin'],
                    ])]),
                    $kit('H10', [['Ventilyator', 'ventilyator'], ['Geri axın klapanı', 'geri-axin'], ['Bərkitmə', 'berkitme']]),
                    ...$guarantee,
                ]),
                'temiz-hava-klapanlari' => $card('H11_1', $n('Təmiz hava klapanları', 'Приточные клапаны', 'Fresh-air inlet valves'), $ps, [
                    $f('H11_1', 'montaj', $mountLabel, 'dropdown', ['required' => true, 'options' => $opts([['Divar', 'divar'], ['Pəncərə', 'pencere']])]
                        + $hint('Passiv hava girişi; ventilyatorlu cihazlar ayrıca kartlardadır.', 'Пассивный приток; вентиляторные приборы — в своих карточках.', 'Passive air inlet; fan units have their own cards.')),
                    ['required' => true] + $airRate,
                    $f('H11_1', 'tezyiq-ferqi', $n('Ölçmədə təzyiq fərqi', 'Перепад давления при измерении', 'Pressure difference measured'), 'decimal', ['required' => true, 'unit' => 'Pa']),
                    ...$wallFit,
                    $f('H11_1', 'tenzimleme', $n('Tənzimləmə', 'Регулирование', 'Regulation'), 'dropdown', ['options' => [...$opts([
                        ['Əl ilə', 'el-ile'], ['Rütubətə görə', 'rutubet'], ['Temperatura görə', 'temperatur'],
                    ]), $other()]]),
                    $f('H11_1', 'filtr', $n('Filtr', 'Фильтр', 'Filter'), 'dropdown', ['options' => $opts([['Yoxdur', 'yoxdur'], ['Var', 'var']])]),
                    ['when' => $when('kart-h11-1-filtr', 'var')] + $filterClass,
                    ['when' => $when('kart-h11-1-filtr', 'var')] + $filterModel,
                    $f('H11_1', 'ses-izolyasiyasi', $n('Səs izolyasiyası', 'Звукоизоляция', 'Sound insulation'), 'decimal', ['unit' => 'dB']),
                    $f('H11_1', 'ses-gosterici', $n('Səs izolyasiyası göstəricisinin adı', 'Название показателя звукоизоляции', 'Sound insulation index'), 'text',
                        $hint('Məs. Dn,e,w.', 'Напр. Dn,e,w.', 'E.g. Dn,e,w.')),
                    ...$look,
                    $kit('H11_1', [['Daxili klapan', 'klapan'], ['Xarici örtük', 'ortuk'], ['Divar gilizi', 'giliz'], ['Filtr', 'filtr']]),
                    ...$guarantee,
                ]),
                'divar-tipli-temiz-hava-cihazlari' => $card('H11_2', $n('Divar tipli təmiz hava cihazları', 'Настенные приточные устройства', 'Wall-mounted fresh-air units'), $ps, [
                    $f('H11_2', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Mexaniki hava təchizatı', 'mexaniki'], ['Brizer', 'brizer'],
                    ])] + $hint('Yalnız hava təchizatı; rekuperasiyalı cihaz H11_4 kartındadır.', 'Только приток; с рекуперацией — карточка H11_4.', 'Supply only; heat-recovery units are card H11_4.')),
                    ...$airflow(),
                    ...$supply(),
                    $f('H11_2', 'qizdirici', $n('Qızdırıcı', 'Нагреватель', 'Heater'), 'dropdown', ['options' => $opts([['Yoxdur', 'yoxdur'], ['Var', 'var']])]),
                    ['when' => $when('kart-h11-2-qizdirici', 'var')] + $heaterPower,
                    ['unit' => 'W'] + $elPower + $hint('Ventilyator; qızdırıcıdan ayrı.', 'Вентилятор; без нагревателя.', 'Fan only, without the heater.'),
                    ['required' => true] + $filterClass,
                    ['required' => true] + $filterModel,
                    ...$noise,
                    ...$wallFit,
                    ...$dims(),
                    $control,
                    ...$smartBlock,
                    $kit('H11_2', [['Cihaz', 'cihaz'], ['Filtr', 'filtr'], ['Divar kanalı', 'divar-kanali'], ['Xarici örtük', 'ortuk'], ['Pult', 'pult']]),
                    ...$guarantee,
                ]),
                'hava-techizati-qurgulari' => $card('H11_3', $n('Hava təchizatı qurğuları', 'Приточные установки', 'Supply air units'), $ps, [
                    $f('H11_3', 'montaj', $mountLabel, 'dropdown', ['required' => true, 'options' => $opts([
                        ['Asma tavan arxası', 'asma-tavan'], ['Divar', 'divar'], ['Döşəmə', 'doseme'], ['Xarici', 'xarici'],
                    ])]),
                    ...$airflow(),
                    ...$supply(),
                    ['unit' => 'W'] + $elPower + $hint('Ventilyator.', 'Вентилятор.', 'Fan.'),
                    $f('H11_3', 'qizdirici', $n('Qızdırıcı tipi', 'Тип нагревателя', 'Heater type'), 'dropdown', ['options' => $opts([
                        ['Yoxdur', 'yoxdur'], ['Elektrik', 'elektrik'], ['Su', 'su'],
                    ])] + $hint('Su qızdırıcısının gücü və temperatur rejimi istehsalçı məlumatında göstərilir.', 'Мощность и режим водяного нагревателя — по данным производителя.', 'A water heater’s output and regime follow the maker’s data.')),
                    ['when' => $when('kart-h11-3-qizdirici', 'elektrik')] + $heaterPower,
                    ...$duct,
                    $filterClass,
                    $filterModel,
                    ...$dims(),
                    ...$noise,
                    $control,
                    ...$smartBlock,
                    $kit('H11_3', [['Qurğu', 'qurgu'], ['Filtr', 'filtr'], ['İdarəetmə', 'idareetme'], ['Qızdırıcı', 'qizdirici']]),
                    ...$guarantee,
                ]),
                'hava-techizati-ve-sorucu-qurgular' => $card('H11_4', $n('Hava təchizatı və sorucu qurğular', 'Приточно-вытяжные установки', 'Supply and extract units'), $ps, [
                    $f('H11_4', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Mərkəzi kanal sistemi', 'merkezi'], ['Otaq üçün divar cihazı', 'divar'],
                    ])]),
                    $f('H11_4', 'prinsip', $n('İş prinsipi', 'Принцип работы', 'Operating principle'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Eyni vaxtda təchizat və sorulma', 'eyni-vaxtda'], ['Növbəli axın', 'novbeli'],
                    ])] + $hint('Növbəli cihazda qiymət bir cihaza (ədəd) və ya işlək cütə (dəst) aiddir.', 'Для реверсивных: цена за один прибор (шт.) или рабочую пару (комплект).', 'Alternating units: price per device (piece) or working pair (set).')),
                    ...$airflow(),
                    $f('H11_4', 'berpa', $n('İstilik bərpası', 'Рекуперация тепла', 'Heat recovery'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Yoxdur', 'yoxdur'], ['Rekuperator', 'rekuperator'], ['Regenerator', 'regenerator'],
                    ])]),
                    $f('H11_4', 'deyisdirici', $n('İstilik dəyişdirici', 'Теплообменник', 'Heat exchanger'), 'dropdown', ['options' => [...$opts([
                        ['Lövhəli', 'lovheli'], ['Rotor', 'rotor'], ['Entalpiya', 'entalpiya'],
                    ]), $other()], 'when' => $when('kart-h11-4-berpa', 'rekuperator', 'regenerator')]),
                    $f('H11_4', 'semerelilik', $n('Bərpa səmərəliliyi', 'Эффективность рекуперации', 'Recovery efficiency'), 'decimal', ['unit' => '%', 'rules' => ['max' => 100],
                        'when' => $when('kart-h11-4-berpa', 'rekuperator', 'regenerator')]),
                    $f('H11_4', 'semerelilik-sert', $n('Səmərəliliyin sınaq şərti', 'Условия испытания эффективности', 'Efficiency test condition'), 'text', ['when' => $when('kart-h11-4-berpa', 'rekuperator', 'regenerator')]
                        + $hint('Hava sərfi və sınaq şərti, istehsalçıya görə.', 'Расход воздуха и условия испытания.', 'Airflow and test condition, per the maker.')),
                    ...$supply(),
                    ['unit' => 'W'] + $elPower + $hint('Ventilyatorların ümumi gücü.', 'Общая мощность вентиляторов.', 'Total fan power.'),
                    $heaterPower,
                    $hint('Təchizat və sorucu tərəfdə sinif / standart.', 'Класс / стандарт на притоке и вытяжке.', 'Class / standard on supply and extract.') + $filterClass,
                    $filterModel,
                    $f('H11_4', 'montaj', $n('Montaj yeri', 'Место монтажа', 'Mounting place'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Asma tavan arxası', 'asma-tavan'], ['Divar', 'divar'], ['Döşəmə', 'doseme'],
                    ]), $other()]]),
                    $f('H11_4', 'baglanti', $n('Kanal / deşik ölçüsü', 'Размер воздуховода / отверстия', 'Duct / hole size'), 'text', ['required' => true]
                        + $hint('Diametr və ya en × hündürlük, mm.', 'Диаметр или ширина × высота, мм.', 'Diameter or width × height, mm.')),
                    $f('H11_4', 'cihaz-sayi', $n('Tələb olunan cihaz sayı', 'Требуемое число приборов', 'Devices required'), 'decimal', ['unit' => 'ədəd', 'rules' => ['integer' => true],
                        'when' => $when('kart-h11-4-prinsip', 'novbeli')]),
                    ...$dims(),
                    ...$noise,
                    $control,
                    ...$smartBlock,
                    $kit('H11_4', [['Qurğu', 'qurgu'], ['Filtrlər', 'filtrler'], ['İdarəetmə', 'idareetme'], ['Divar dəsti', 'divar-desti'], ['Qızdırıcı', 'qizdirici']]),
                    ...$guarantee,
                ]),
                'barmaqliqlar-diffuzorlar-hava-qebuledicileri' => $card('H12_1', $n('Havalandırma barmaqlıqları', 'Вентиляционные решётки', 'Ventilation grilles'), ['piece'], [
                    $f('H12_1', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Təchizat', 'techizat'], ['Sorucu', 'sorucu'], ['Keçid', 'kecid'], ['Xarici fasad', 'fasad'],
                    ]), $other()]]),
                    $f('H12_1', 'forma', $n('Forma', 'Форма', 'Shape'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Düzbucaqlı', 'duzbucaqli'], ['Dairəvi', 'dairevi'], ['Xətti', 'xetti'],
                    ]), $other()]]),
                    $f('H12_1', 'umumi-olcu', $n('Ümumi ölçü', 'Габаритный размер', 'Overall size'), 'text', ['required' => true]
                        + $hint('En × hündürlük və ya diametr, mm.', 'Ширина × высота или диаметр, мм.', 'Width × height or diameter, mm.')),
                    $f('H12_1', 'bosluq', $n('Montaj boşluğunun ölçüsü', 'Размер монтажного проёма', 'Opening size'), 'text', ['required' => true]
                        + $hint('En × hündürlük və ya diametr, mm.', 'Ширина × высота или диаметр, мм.', 'Width × height or diameter, mm.')),
                    $f('H12_1', 'material', $materialLabel, 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Alüminium', 'aluminium'], ['Polad', 'polad'], ['Plastik', 'plastik'], ['Ağac', 'agac'],
                    ]), $other()]]),
                    ...$look,
                    $f('H12_1', 'jaluz', $n('Jalüzlər', 'Жалюзи', 'Louvres'), 'dropdown', ['options' => $opts([
                        ['Sabit', 'sabit'], ['Tənzimlənən', 'tenzimlenen'], ['Yoxdur', 'yoxdur'],
                    ])]),
                    $f('H12_1', 'xususiyyetler', $n('Komplekt xüsusiyyətləri', 'Комплектация', 'Features'), 'multiselect', ['options' => [...$opts([
                        ['Tor', 'tor'], ['Hava tənzimləyici', 'tenzimleyici'], ['Geri axın klapanı', 'geri-axin'], ['Çıxarılan çərçivə', 'cercive'],
                    ]), $other()]]),
                    $kit('H12_1', [['Barmaqlıq', 'barmaqliq'], ['Çərçivə', 'cercive'], ['Bərkitmə', 'berkitme']]),
                ]),
                'anemostatlar' => $card('H12_2', $n('Diffuzorlar və anemostatlar', 'Диффузоры и анемостаты', 'Diffusers and air valves'), $ps, [
                    $f('H12_2', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Tavan diffuzoru', 'tavan'], ['Yarıqlı diffuzor', 'yariqli'], ['Burulğanlı diffuzor', 'burulganli'], ['Anemostat', 'anemostat'],
                    ])]),
                    $f('H12_2', 'teyinat', $n('Təyinat', 'Назначение', 'Purpose'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Hava təchizatı', 'techizat'], ['Hava sorulması', 'sorulma'], ['Hər ikisi', 'her-ikisi'],
                    ])]),
                    $f('H12_2', 'olculer', $n('Ümumi və qoşulma ölçüləri', 'Габаритные и присоединительные размеры', 'Overall and connection size'), 'text', ['required' => true]
                        + $hint('mm; məs. 595 × 595, qoşulma Ø200.', 'мм; напр. 595 × 595, присоединение Ø200.', 'mm; e.g. 595 × 595, connection Ø200.')),
                    $f('H12_2', 'uzunluq', $n('Uzunluq', 'Длина', 'Length'), 'decimal', ['unit' => 'mm', 'when' => $when('kart-h12-2-tip', 'yariqli')]),
                    $f('H12_2', 'yariq', $n('Yarıq sayı', 'Число щелей', 'Slots'), 'decimal', ['unit' => 'ədəd', 'rules' => ['integer' => true], 'when' => $when('kart-h12-2-tip', 'yariqli')]),
                    $f('H12_2', 'material', $materialLabel, 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Alüminium', 'aluminium'], ['Polad', 'polad'], ['Plastik', 'plastik'],
                    ]), $other()]]),
                    ...$look,
                    $f('H12_2', 'axin', $n('Axın tənzimlənməsi', 'Регулировка потока', 'Flow adjustment'), 'dropdown', ['options' => $opts([['Var', 'var'], ['Yoxdur', 'yoxdur']])]
                        + $hint('İstiqamət və ya həcm üzrə.', 'По направлению или объёму.', 'Direction or volume.')),
                    $f('H12_2', 'plenum', $n('Uyğun plenum', 'Подходящий пленум', 'Matching plenum'), 'dropdown', ['options' => $opts([
                        ['Daxildir', 'daxildir'], ['Ayrıca alınır', 'ayrica'], ['Tələb olunmur', 'teleb-olunmur'],
                    ])]),
                    $f('H12_2', 'plenum-model', $n('Plenumun modeli və ölçüsü', 'Модель и размер пленума', 'Plenum model and size'), 'text', ['when' => $when('kart-h12-2-plenum', 'daxildir', 'ayrica')]),
                    $airRate + $hint('İş göstəriciləri istehsalçıya görə.', 'Рабочие показатели — по данным производителя.', 'Performance per the maker.'),
                    $f('H12_2', 'tezyiq-itkisi', $n('Təzyiq itkisi', 'Потеря давления', 'Pressure drop'), 'decimal', ['unit' => 'Pa']),
                    $noiseLevel,
                    $airMode,
                    $kit('H12_2', [['Diffuzor', 'diffuzor'], ['Plenum', 'plenum'], ['Damper', 'damper'], ['Bərkitmə', 'berkitme']]),
                ]),
            ],
            // Duct network and dampers: «hava kanalı şəbəkəsi bu kataloqa daxil deyil»; air purifiers — no card.
            'plain' => ['hava-kanallari-ve-komponentler', 'ventilyasiya-damperleri-ve-klapanlari', 'hava-filtrleri-ve-temizleyicileri'],
        ],

        // ════ 4. İdarəetmə və komplektləşdiricilər (alt bölmələr 13–15) ═════
        [
            'slug' => 'iqlim-idareetme-ve-komplektlesdiriciler',
            'name' => $n('İdarəetmə və komplektləşdiricilər', 'Управление и комплектующие', 'Controls and accessories'),
            'classes' => [
                'termorequlyatorlar-ve-kontrollerler' => $card('H13_1', $n('Termostatlar və temperatur tənzimləyiciləri', 'Термостаты и терморегуляторы', 'Thermostats and temperature controllers'), $ps, [
                    $f('H13_1', 'teyinat', $n('Təyinat', 'Назначение', 'Purpose'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Otaq isitmə', 'otaq'], ['Elektrik isti döşəmə', 'elektrik-doseme'], ['Su isti döşəmə', 'su-doseme'], ['Fankoyl', 'fankoyl'],
                    ]), $other()]] + $hint('Ağıllı termostat da burada yerləşdirilir.', 'Умный термостат тоже размещается здесь.', 'Smart thermostats are listed here too.')),
                    $f('H13_1', 'uygunluq', $n('İdarə olunan cihazla uyğunluq', 'Совместимость с управляемым прибором', 'Controlled-device interface'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Quru kontakt', 'quru-kontakt'], ['Gərginlik çıxışı', 'gerginlik'], ['0–10 V', '0-10'], ['OpenTherm', 'opentherm'], ['İstehsalçı şini', 'sin'],
                    ]), $other()]]),
                    $compat(false),
                    $f('H13_1', 'cereyan', $n('Çıxış yükü, maks cərəyan', 'Нагрузка выхода, макс. ток', 'Output load, max current'), 'decimal', ['required' => true, 'unit' => 'A',
                        'when' => $when('kart-h13-1-uygunluq', 'quru-kontakt', 'gerginlik', '0-10', 'opentherm', 'diger')]
                        + $hint('Şin idarəsində tətbiq edilmir.', 'Не применяется при управлении по шине.', 'Not applicable with bus control.')),
                    $f('H13_1', 'guc', $n('Çıxış yükü, maks güc', 'Нагрузка выхода, макс. мощность', 'Output load, max power'), 'decimal', ['unit' => 'W',
                        'when' => $when('kart-h13-1-uygunluq', 'quru-kontakt', 'gerginlik', '0-10', 'opentherm', 'diger')]),
                    $f('H13_1', 'yuk-novu', $n('Çıxış gərginliyi və yük növü', 'Напряжение выхода и тип нагрузки', 'Output voltage and load type'), 'text', [
                        'when' => $when('kart-h13-1-uygunluq', 'quru-kontakt', 'gerginlik', '0-10', 'opentherm', 'diger')]),
                    ...$supply(),
                    ...$setRange,
                    $f('H13_1', 'sensor', $n('Sensor', 'Датчик', 'Sensor'), 'dropdown', ['required' => true, 'options' => $opts([
                        ['Daxili hava', 'hava'], ['Döşəmə', 'doseme'], ['Xarici', 'xarici'], ['Kombinə', 'kombine'],
                    ])]),
                    $f('H13_1', 'sensor-tip', $n('Xarici sensorun tipi və müqaviməti', 'Тип и сопротивление выносного датчика', 'Remote sensor type and resistance'), 'text', [
                        'when' => $when('kart-h13-1-sensor', 'doseme', 'xarici', 'kombine')] + $hint('Məs. NTC 10 kΩ.', 'Напр. NTC 10 кОм.', 'E.g. NTC 10 kΩ.')),
                    $f('H13_1', 'montaj', $mountLabel, 'dropdown', ['options' => $opts([['Gömülən', 'gomulen'], ['Səthüstü', 'sethustu'], ['Masaüstü', 'masaustu']])]),
                    $control,
                    ...$smartBlock,
                    ...$look,
                    $kit('H13_1', [['Termostat', 'termostat'], ['Sensor', 'sensor'], ['Qəbuledici', 'qebuledici'], ['Bərkitmə', 'berkitme']]),
                    ...$guarantee,
                ]),
                'radiator-termostatik-basliqlari' => $card('H13_2', $n('Radiator termostatik başlıqları', 'Термостатические головки для радиаторов', 'Radiator thermostatic heads'), $ps, [
                    $f('H13_2', 'nov', $typeLabel, 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Mexaniki', 'mexaniki'], ['Elektron', 'elektron'], ['Ağıllı', 'agilli'],
                    ])] + $hint('Başlıq klapan gövdəsindən ayrıdır; birlikdə satılırsa komplektdə göstərilir.', 'Головка отдельно от корпуса клапана; если вместе — в комплекте.', 'The head is separate from the valve body; if sold together, tick it in the kit.')),
                    $f('H13_2', 'baglanti', $n('Klapan bağlantısı', 'Присоединение к клапану', 'Valve connection'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['M30×1,5', 'm30'], ['M28×1,5', 'm28'], ['Danfoss RA', 'danfoss-ra'],
                    ]), $other()]]),
                    $f('H13_2', 'seriya', $n('Uyğun klapan seriyası', 'Совместимая серия клапанов', 'Compatible valve series'), 'text'),
                    ...$setRange,
                    $f('H13_2', 'sensor', $n('Temperatur sensoru', 'Датчик температуры', 'Temperature sensor'), 'dropdown', ['options' => $opts([
                        ['Daxili', 'daxili'], ['Uzaqdan', 'uzaqdan'], ['Xarici sensor dəstəyi', 'xarici'],
                    ])]),
                    $f('H13_2', 'qidalanma', $n('Qidalanma', 'Питание', 'Power'), 'dropdown', ['options' => $opts([
                        ['Mexaniki, tələb olunmur', 'mexaniki'], ['Batareya', 'batareya'], ['Adapter', 'adapter'],
                    ])]),
                    $f('H13_2', 'qida-qeyd', $n('Qidalanmanın tipi və gərginliyi', 'Тип и напряжение питания', 'Power type and voltage'), 'text', ['when' => $when('kart-h13-2-qidalanma', 'batareya', 'adapter')]
                        + $hint('Məs. 2 × AA və ya 5 V USB.', 'Напр. 2 × AA или 5 В USB.', 'E.g. 2 × AA or 5 V USB.')),
                    ...$smartBlock,
                    ...$dims(),
                    ...$look,
                    $kit('H13_2', [['Başlıq', 'basliq'], ['Adapterlər', 'adapterler'], ['Batareya', 'batareya']]),
                    ...$guarantee,
                ]),
                'iqlim-pultlari-ve-idareetme-panelleri' => $card('H13_3', $n('İqlim pultları və idarəetmə panelləri', 'Пульты и панели управления климатом', 'Climate remotes and control panels'), $ps, [
                    $f('H13_3', 'teyinat', $n('Təyinat', 'Назначение', 'For'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Kondisioner', 'kondisioner'], ['VRF', 'vrf'], ['Fankoyl', 'fankoyl'], ['Havalandırma', 'havalandirma'], ['Qazan', 'qazan'], ['İstilik nasosu', 'istilik-nasosu'],
                    ])] + $hint('Yalnız iqlim avadanlığı üçün; ümumi ağıllı ev panelləri Elektrik bölməsindədir.', 'Только для климатического оборудования; панели умного дома — в разделе «Электрика».', 'Climate equipment only; general smart-home panels are in the Electrical section.')),
                    $f('H13_3', 'nov', $typeLabel, 'dropdown', ['required' => true, 'options' => $opts([
                        ['Simsiz pult', 'simsiz-pult'], ['Naqilli panel', 'naqilli-panel'], ['Mərkəzi kontroller', 'kontroller'], ['Rabitə adapteri', 'adapter'],
                    ])]),
                    $compat(),
                    $f('H13_3', 'tutum', $n('İdarəetmə tutumu', 'Ёмкость управления', 'Control capacity'), 'decimal', ['unit' => 'blok / zona', 'rules' => ['integer' => true]]
                        + $hint('Blok və ya zona sayı, istehsalçıya görə.', 'Число блоков или зон.', 'Units or zones, per the maker.')),
                    $f('H13_3', 'qidalanma', $n('Qidalanma', 'Питание', 'Power'), 'dropdown', ['options' => $opts([
                        ['Batareya', 'batareya'], ['Şindən', 'sinden'], ['Adapter', 'adapter'], ['Şəbəkə', 'sebeke'],
                    ])]),
                    ...$supply(false, $when('kart-h13-3-qidalanma', 'sinden', 'adapter', 'sebeke')),
                    ...$smartBlock,
                    $f('H13_3', 'funksiyalar', $n('Funksiyalar', 'Функции', 'Functions'), 'multiselect', ['options' => [...$opts([
                        ['Rejim', 'rejim'], ['Sürət', 'suret'], ['Temperatur', 'temperatur'], ['Həftəlik proqram', 'heftelik'], ['Qruplar', 'qruplar'],
                    ]), $other()]]),
                    ...$dims(),
                    $kit('H13_3', [['Pult', 'pult'], ['Panel', 'panel'], ['Adapter', 'adapter'], ['Kabel', 'kabel'], ['Bərkitmə', 'berkitme']]),
                    ...$guarantee,
                ]),
                'radiator-klapanlari' => $card('H14_1', $n('Radiator klapanları', 'Радиаторные клапаны', 'Radiator valves'), $ps, [
                    $f('H14_1', 'funksiya', $n('Funksiya', 'Функция', 'Function'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Termostatik gövdə', 'termostatik'], ['Əl ilə tənzimləmə', 'el-ile'], ['Geri dönüş klapanı', 'geri-donus'],
                    ])]),
                    $f('H14_1', 'forma', $n('Forma', 'Форма', 'Shape'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Düz', 'duz'], ['Künc', 'kunc'], ['Oxboyu', 'oxboyu'],
                    ]), $other()]]),
                    $f('H14_1', 'qosulma', $n('Qoşulma ölçüləri', 'Присоединительные размеры', 'Connection sizes'), 'text', ['required' => true]
                        + $hint('Hər ucdakı ölçü və birləşmə tipi: radiator / boru / başlıq tərəfi, məs. 1/2″ xarici yiv × 16×2 sıxma.', 'Размер и тип соединения каждого конца: радиатор / труба / головка.', 'Each end’s size and joint: radiator / pipe / head side.')),
                    $f('H14_1', 'sistem', $n('Sistem uyğunluğu', 'Совместимость с системой', 'System'), 'dropdown', ['options' => $opts([
                        ['Birborulu', 'birborulu'], ['İkiborulu', 'ikiborulu'], ['Hər ikisi', 'her-ikisi'],
                    ])]),
                    $f('H14_1', 'material', $materialLabel, 'dropdown', ['options' => [...$opts([['Latun', 'latun'], ['Polad', 'polad']]), $other()]]),
                    ...$look,
                    ...$limits(),
                    $f('H14_1', 'basliq', $n('Başlıq uyğunluğu', 'Совместимость с головкой', 'Head compatibility'), 'text', ['when' => $when('kart-h14-1-funksiya', 'termostatik')]
                        + $hint('Uyğun birləşmə və seriya.', 'Подходящее соединение и серия.', 'Matching connection and series.')),
                    $kit('H14_1', [['Klapan', 'klapan'], ['Başlıq', 'basliq'], ['Adapter', 'adapter'], ['Dekorativ örtük', 'ortuk']]),
                ]),
                'radiator-qosulma-destleri' => $card('H14_2', $n('Radiator qoşulma dəstləri', 'Комплекты подключения радиаторов', 'Radiator connection kits'), ['set'], [
                    $f('H14_2', 'nov', $typeLabel, 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Alt qoşulma bloku', 'alt'], ['Yan qoşulma dəsti', 'yan'], ['Dəsmalqurudan dəsti', 'desmalqurudan'],
                    ]), $other()]]),
                    $f('H14_2', 'forma', $n('Forma', 'Форма', 'Shape'), 'dropdown', ['options' => [...$opts([['Düz', 'duz'], ['Künc', 'kunc']]), $other()]]),
                    $f('H14_2', 'qosulma', $n('Qoşulma ölçüləri', 'Присоединительные размеры', 'Connection sizes'), 'text', ['required' => true]
                        + $hint('Radiator və boru tərəfi ölçüləri.', 'Размеры со стороны радиатора и трубы.', 'Radiator-side and pipe-side sizes.')),
                    $centres,
                    $compat(),
                    ...$look,
                    ...$limits(),
                    $f('H14_2', 'terkib', $n('Dəstin tərkibi', 'Состав комплекта', 'Kit contents'), 'textarea', ['required' => true]
                        + $hint('Klapanlar, adapterlər, başlıq və dekorativ hissələrin adları və sayı.', 'Клапаны, адаптеры, головка и декоративные части с количеством.', 'Valves, adapters, head and trim parts with counts.')),
                ]),
                'isitme-cihazi-kronsteynleri' => $card('H14_3', $n('İsitmə cihazı kronşteynləri', 'Кронштейны для отопительных приборов', 'Heater brackets'), $ps, [
                    $f('H14_3', 'teyinat', $n('Təyinat', 'Назначение', 'For'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Radiator', 'radiator'], ['Konvektor', 'konvektor'], ['Dəsmalqurudan', 'desmalqurudan'],
                    ])]),
                    $compat(),
                    $f('H14_3', 'montaj', $mountLabel, 'dropdown', ['required' => true, 'options' => $opts([['Divar', 'divar'], ['Döşəmə', 'doseme']])]),
                    $f('H14_3', 'olculer', $n('Ölçülər və məsafə', 'Размеры и расстояния', 'Sizes and distances'), 'text', ['required' => true]
                        + $hint('Bərkitmə ölçüləri, divardan məsafə və tənzimləmə aralığı, mm.', 'Крепёжные размеры, расстояние от стены и диапазон регулировки, мм.', 'Fixing sizes, wall distance and adjustment range, mm.')),
                    ...$load(),
                    $f('H14_3', 'material', $materialLabel, 'dropdown', ['options' => [...$opts([['Polad', 'polad'], ['Alüminium', 'aluminium']]), $other()]]),
                    ...$look,
                    $setCount,
                    $kit('H14_3', [['Kronşteyn', 'kronsteyn'], ['Vint', 'vint'], ['Dübel', 'dubel']]),
                ]),
                'radiator-tixaclari-ve-havacixaranlar' => $card('H14_4', $n('Radiator tıxacları və havaçıxaranlar', 'Заглушки и воздухоотводчики для радиаторов', 'Radiator plugs and air vents'), ['piece', 'pack', 'set'], [
                    $f('H14_4', 'nov', $typeLabel, 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Tıxac', 'tixac'], ['Əl havaçıxaranı', 'el-havacixaran'], ['Avtomatik havaçıxaran', 'avtomatik-havacixaran'], ['Adapter', 'adapter'],
                    ])]),
                    ['required' => true] + $thread,
                    $f('H14_4', 'yiv-novu', $n('Yiv', 'Резьба', 'Thread'), 'dropdown', ['required' => true, 'options' => $opts([['Daxili', 'daxili'], ['Xarici', 'xarici']])]),
                    $f('H14_4', 'istiqamet', $n('İstiqamət', 'Направление', 'Hand'), 'dropdown', ['options' => $opts([['Sağ', 'sag'], ['Sol', 'sol'], ['Universal', 'universal']])]),
                    $f('H14_4', 'material', $materialLabel, 'dropdown', ['options' => [...$opts([['Latun', 'latun'], ['Polad', 'polad'], ['Plastik', 'plastik']]), $other()]]),
                    ...$limits(),
                    ...$look,
                    $compat(),
                    $packCount,
                    $kit('H14_4', [['Hissə', 'hisse'], ['Conta', 'conta'], ['Açar', 'acar']]),
                ]),
                'kondisioner-kronsteynleri-ve-dayaqlari' => $card('H15_1', $n('Kondisioner kronşteynləri və dayaqları', 'Кронштейны и опоры для кондиционеров', 'Air-conditioner brackets and stands'), $ps, [
                    $f('H15_1', 'montaj', $mountLabel, 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Divar', 'divar'], ['Döşəmə', 'doseme'], ['Dam', 'dam'],
                    ]), $other()]]),
                    ...$load(true),
                    $f('H15_1', 'olculer', $n('Ölçülər və bərkitmə', 'Размеры и крепление', 'Sizes and fixing'), 'text', ['required' => true]
                        + $hint('Qol uzunluğu, dayaq ölçüsü və deşiklərarası aralıq, mm.', 'Длина консоли, размер опоры и межосевое расстояние отверстий, мм.', 'Arm length, stand size and hole spacing, mm.')),
                    $f('H15_1', 'material', $n('Material və örtük', 'Материал и покрытие', 'Material and finish'), 'dropdown', ['options' => [...$opts([
                        ['Sinklənmiş polad', 'sinklenmis'], ['Boyalı polad', 'boyali'], ['Paslanmayan polad', 'paslanmayan'],
                    ]), $other()]]),
                    $compat(),
                    $setCount,
                    $kit('H15_1', [['Kronşteyn', 'kronsteyn'], ['Dayaq', 'dayaq'], ['Bərkitmə', 'berkitme'], ['Vibrasiya altlığı', 'altliq']]),
                ]),
                'kondensat-drenaj-pompalari' => $card('H15_2', $n('Kondensat drenaj pompaları', 'Дренажные насосы для конденсата', 'Condensate pumps'), $ps, [
                    $f('H15_2', 'nov', $typeLabel, 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Mini', 'mini'], ['Çənli', 'cenli'], ['Daxili quraşdırılan', 'daxili'],
                    ]), $other()]]),
                    $f('H15_2', 'mehsuldarliq', $n('Nasos məhsuldarlığı', 'Производительность насоса', 'Pump flow'), 'decimal', ['required' => true, 'unit' => 'l/saat']),
                    $f('H15_2', 'axin-qaldirma', $n('Həmin axında qaldırma hündürlüyü', 'Высота подъёма при этом расходе', 'Head at that flow'), 'decimal', ['required' => true, 'unit' => 'm']),
                    $f('H15_2', 'maks-qaldirma', $n('Maksimum qaldırma hündürlüyü', 'Макс. высота подъёма', 'Maximum head'), 'decimal', ['required' => true, 'unit' => 'm']
                        + $hint('Maksimum axınla eyni iş nöqtəsi deyil.', 'Не та же рабочая точка, что макс. расход.', 'Not the same duty point as the maximum flow.')),
                    $f('H15_2', 'maks-sorma', $n('Maksimum sorma hündürlüyü', 'Макс. высота всасывания', 'Maximum suction lift'), 'decimal', ['unit' => 'm']),
                    ...$supply(),
                    ['unit' => 'W'] + $elPower,
                    ...$noise,
                    $f('H15_2', 'giris', $n('Giriş diametri', 'Диаметр входа', 'Inlet diameter'), 'decimal', ['unit' => 'mm']),
                    $f('H15_2', 'cixis', $n('Çıxış diametri', 'Диаметр выхода', 'Outlet diameter'), 'decimal', ['unit' => 'mm']),
                    $f('H15_2', 'siqnal', $n('Daşma siqnalı və ya təhlükəsizlik kontaktı', 'Сигнал перелива или аварийный контакт', 'Overflow alarm or safety contact'), 'dropdown', ['options' => $ynn]),
                    ...$dims(),
                    $compat(),
                    $kit('H15_2', [['Pompa', 'pompa'], ['Rezervuar', 'rezervuar'], ['Sensor', 'sensor'], ['Şlanq', 'slanq']]),
                    ...$guarantee,
                ]),
                'vibrasiya-izolyasiya-dayaqlari' => $card('H15_3', $n('Vibrasiya izolyasiya dayaqları', 'Виброизолирующие опоры', 'Anti-vibration mounts'), $ps, [
                    $f('H15_3', 'teyinat', $n('Təyinat', 'Назначение', 'For'), 'dropdown', ['required' => true, 'options' => [...$opts([
                        ['Kondisioner', 'kondisioner'], ['Ventilyasiya qurğusu', 'ventilyasiya'], ['İstilik nasosu', 'istilik-nasosu'],
                    ]), $other()]]),
                    $f('H15_3', 'tip', $n('Tip', 'Тип', 'Type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Rezin', 'rezin'], ['Yaylı', 'yayli'], ['Rezin-metal', 'rezin-metal'],
                    ]), $other()]]),
                    $f('H15_3', 'yuk-min', $n('Yük aralığı, min', 'Диапазон нагрузки, мин.', 'Load range, min'), 'decimal', ['required' => true, 'unit' => 'kq',
                        'rules' => ['lte' => 'kart-h15-3-yuk-maks']] + $hint('Bir dayaq üçün.', 'На одну опору.', 'Per mount.')),
                    $f('H15_3', 'yuk-maks', $n('Yük aralığı, maks', 'Диапазон нагрузки, макс.', 'Load range, max'), 'decimal', ['required' => true, 'unit' => 'kq']),
                    $f('H15_3', 'olcu', $n('Ölçü və bərkitmə', 'Размер и крепление', 'Size and fixing'), 'text', ['required' => true]
                        + $hint('Ölçülər, mm; yiv / deşik ölçüsü, məs. 50 × 50 × 30, M8.', 'Размеры, мм; резьба / отверстие, напр. M8.', 'Size, mm; thread / hole, e.g. M8.')),
                    $f('H15_3', 'montaj', $mountLabel, 'dropdown', ['options' => $opts([['Döşəmə', 'doseme'], ['Kronşteyn', 'kronsteyn'], ['Asqı', 'asqi']])]),
                    $compat(),
                    $setCount,
                    $kit('H15_3', [['Dayaq', 'dayaq'], ['Qayka', 'qayka'], ['Şayba', 'sayba']]),
                ]),
                'iqlim-avadanligi-ucun-deyisme-filtrleri' => $card('H15_4', $n('İqlim avadanlığı üçün dəyişmə filtrləri', 'Сменные фильтры для климатического оборудования', 'Replacement filters for climate equipment'), ['piece', 'pack', 'set'], [
                    $f('H15_4', 'teyinat', $n('Təyinat', 'Назначение', 'For'), 'dropdown', ['required' => true, 'filter' => true, 'options' => $opts([
                        ['Kondisioner', 'kondisioner'], ['Brizer', 'brizer'], ['Havalandırma qurğusu', 'havalandirma'], ['Fankoyl', 'fankoyl'],
                    ])]),
                    $compat(),
                    $f('H15_4', 'nov', $n('Filtr növü', 'Тип фильтра', 'Filter type'), 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['İlkin', 'ilkin'], ['İncə', 'ince'], ['HEPA', 'hepa'], ['Aktiv karbon', 'karbon'], ['Kombinə', 'kombine'],
                    ]), $other()]]),
                    $filterClass,
                    ['required' => true, 'unit' => 'mm'] + $width + $hint('En × uzunluq × qalınlıq.', 'Ширина × длина × толщина.', 'Width × length × thickness.'),
                    ['required' => true, 'unit' => 'mm'] + $length,
                    ['required' => true] + $thickness,
                    $f('H15_4', 'yuyulma', $n('Yuyulma', 'Промывка', 'Washable'), 'dropdown', ['options' => $opts([
                        ['Yuyula bilən', 'yuyula-bilen'], ['Birdəfəlik dəyişən', 'birdefelik'], ['İstehsalçıya görə', 'istehsalciya-gore'],
                    ])]),
                    $packCount,
                    $kit('H15_4', [['Filtr', 'filtr'], ['Filtr çərçivəsi', 'cercive']]),
                ]),
                'iqlim-avadanligi-ucun-dekorativ-paneller-ve-aksesuarlar' => $card('H15_5', $n('Dekorativ panellər və uyğun aksesuarlar', 'Декоративные панели и аксессуары', 'Decorative panels and accessories'), $ps, [
                    $f('H15_5', 'nov', $typeLabel, 'dropdown', ['required' => true, 'filter' => true, 'options' => [...$opts([
                        ['Kaset paneli', 'kaset-paneli'], ['Qoruyucu örtük', 'qoruyucu'], ['Dekorativ ekran', 'ekran'], ['Cihaz adapteri', 'adapter'],
                    ]), $other()]] + $hint('Hava kanalları, mis borular və gizli montaj materialları daxil deyil.', 'Воздуховоды, медные трубы и монтажные материалы не входят.', 'Ducts, copper pipes and concealed-install materials are not included.')),
                    $compat(),
                    ['required' => true, 'unit' => 'mm'] + $width + $hint('En × uzunluq × dərinlik.', 'Ширина × длина × глубина.', 'Width × length × depth.'),
                    ['required' => true, 'unit' => 'mm'] + $length,
                    ['slug' => 'kart-iq-derinlik', 'name' => $n('Dərinlik', 'Глубина', 'Depth'), 'type' => 'decimal', 'required' => true, 'unit' => 'mm'],
                    $f('H15_5', 'berkitme', $n('Bərkitmə ölçüləri', 'Крепёжные размеры', 'Fixing sizes'), 'text'),
                    $f('H15_5', 'material', $materialLabel, 'dropdown', ['options' => [...$opts([['Plastik', 'plastik'], ['Alüminium', 'aluminium'], ['Polad', 'polad']]), $other()]]),
                    ...$look,
                    $f('H15_5', 'elektrik', $n('Elektrik bağlantısı', 'Электроподключение', 'Electrical connection'), 'dropdown', ['options' => $opts([['Yoxdur', 'yoxdur'], ['Var', 'var']])]),
                    $f('H15_5', 'elektrik-qeyd', $n('Konnektor / model və gərginlik', 'Разъём / модель и напряжение', 'Connector / model and voltage'), 'text', ['when' => $when('kart-h15-5-elektrik', 'var')]),
                    $setCount,
                    $kit('H15_5', [['Panel', 'panel'], ['Örtük', 'ortuk'], ['Bərkitmə', 'berkitme'], ['Qoşulma hissələri', 'qosulma']]),
                ]),
            ],
        ],

        // ════ Not in the workbook — «ayrı borular və kollektorlar sonraya saxlanılır» ══
        [
            'slug' => 'istilik-ucun-borular-armatur-ve-nasos-avadanligi',
            'name' => $n('Borular, armatur və kollektorlar', 'Трубы, арматура и коллекторы', 'Pipes, fittings and manifolds'),
            'plain' => [
                'istilik-ucun-borular-ve-fitinqler',
                'genislenme-cenleri',
                'istilik-sistemi-ucun-komplektlesdiriciler-havacixaricilar-baglayici-armatur-termo-basliqlar-manometrler-kronsteynler',
                'isti-doseme-istilik-ucun-kollektor-paylayici',
            ],
        ],
        [
            'slug' => 'senaye-ve-merkezi-kondisionerlesdirme',
            'name' => $n('Sənaye və mərkəzi kondisionerləşdirmə', 'Промышленное и центральное кондиционирование', 'Industrial and central air conditioning'),
            'plain' => ['ciller', 'hava-nemlendiricisi-quruducusu-senaye'],
        ],
    ],

    // Duct fans are H10 «Sorucu ventilyatorlar» with «Tip = Kanal üçün».
    'merge' => [
        'kanal-ventilyatorlari' => ['into' => 'ventilyatorlar-ve-ventilyasiya-qurgulari', 'set' => ['kart-h10-tip' => 'kanal']],
        // Santexnika's plain «Dəsmal quruducu» is the same product as H03 (santexnika.php no longer lists it).
        'desmal-qurudan' => 'desmalqurudanlar',
    ],

    'retire_groups' => ['qazanlar-ve-istilik-generatorlari', 'istilik-nasoslari-ve-kollektorlar'],
];
