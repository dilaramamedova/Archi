<?php

/*
|--------------------------------------------------------------------------
| Calculator TZ №2 of 19.11.2026 («Texniki tapşırıq №2 — Ətraflı kalkulyator»)
|--------------------------------------------------------------------------
|
| The same shape as calculator_tz_2026_11.php and applied by the same seeder
| (CalculatorTzSeeder), so the production migration (2026_11_19_110100) only ADDS
| what is missing — a price Archi has already set is never overwritten — while
| CalculatorSeeder writes everything (fresh installs, tests).
|
| §43 lighting type and track norms, §44 the track system, §45 LED outputs,
| §46 the BALCONY and WARDROBE room types.
|
| Prices of the new rows are DRAFT (Baku, November 2026) for Archi to confirm.
| The TZ only fixes their order: a track light costs more than a spot, both to
| install and to buy — `above` keeps that true against Archi's own spot prices.
|
*/

$draft = 'DRAFT — TZ №2 19.11.2026; qiyməti Archi təsdiqləməlidir.';
$n = fn (string $az, string $ru, string $en): array => compact('az', 'ru', 'en');

return [
    'draft_note' => $draft,

    'work_stages' => [
        'WHITE_BOX' => ['ELEC_TRACK_FEED_POINT', 'ELEC_LED_OUTPUT_POINT'],
        'FINISH' => ['LIGHT_TRACK_RAIL_INSTALL', 'LIGHT_TRACK_INSTALL'],
    ],

    'fallback_stages' => [
        'FINISH' => ['MAT_TRACK_RAIL', 'MAT_LIGHT_TRACK'],
    ],

    'works' => [
        ['work_id' => 'ELEC_TRACK_FEED_POINT', 'category' => 'ELECTRICAL', 'unit' => 'nöqtə', 'quantity_source' => 'TrackFeedPointCount', 'rate' => 22,
            'notes' => $draft, 'formula' => 'CEIL(şina uzunluğu / TRACK_RAIL_FEED_M), ən azı 1',
            'keywords' => 'trek; şina qidalanması; трек; питание шинопровода; track feed',
            'name' => $n('Trek şinasının qidalanma nöqtəsi', 'Точка питания трекового шинопровода', 'Track rail feed point')],
        ['work_id' => 'ELEC_LED_OUTPUT_POINT', 'category' => 'ELECTRICAL', 'unit' => 'nöqtə', 'quantity_source' => 'LedOutputPointCount', 'rate' => 22,
            'notes' => $draft,
            'keywords' => 'LED çıxışı; LED lent; вывод 220V для LED подсветки; LED output',
            'name' => $n('LED işıqlandırma üçün 220V çıxışı', 'Вывод 220V для LED-подсветки', '220V output for LED lighting')],
        ['work_id' => 'LIGHT_TRACK_RAIL_INSTALL', 'category' => 'LIGHTING', 'unit' => 'p.m', 'quantity_source' => 'TrackRailMeters', 'rate' => 12,
            'notes' => $draft,
            'keywords' => 'trek şinası; şinoprovod; шинопровод; track rail',
            'name' => $n('Trek şinasının montajı', 'Монтаж трекового шинопровода', 'Track rail installation')],
        ['work_id' => 'LIGHT_TRACK_INSTALL', 'category' => 'LIGHTING', 'unit' => 'əd', 'quantity_source' => 'TrackLightCount', 'rate' => 15,
            'notes' => $draft, 'above' => 'LIGHT_SPOT_INSTALL',
            'keywords' => 'trek işığı; трековый светильник; track light',
            'name' => $n('Trek işıqlandırıcısının montajı', 'Монтаж трекового светильника', 'Track light installation')],
    ],

    'fallbacks' => [
        ['id' => 'MAT_TRACK_RAIL', 'group' => 'LIGHTING', 'unit' => 'p.m', 'economy' => 20, 'standard' => 35, 'premium' => 60,
            'name' => $n('Trek şinası, birləşdiricilər, qidalanma bloku', 'Трековый шинопровод, соединители, блок питания', 'Track rail, connectors and feed block')],
        ['id' => 'MAT_LIGHT_TRACK', 'group' => 'LIGHTING', 'unit' => 'əd', 'economy' => 30, 'standard' => 55, 'premium' => 110, 'above' => 'MAT_LIGHT_SPOT',
            'name' => $n('Trek işıqlandırıcısı', 'Трековый светильник', 'Track light')],
    ],

    // «Ümumi parametrlər → Elektrik və santexnika normaları» (TZ §43). Proposed by the
    // TZ and calibrated on Leyla Loft: 60.7 m² under track → ≈ 44 lights, ≈ 20 m rail.
    'settings' => [
        ['key' => 'TRACK_LIGHT_AREA_M2', 'group' => 'ENGINEERING', 'type' => 'decimal', 'value' => 1.4, 'unit' => 'm²/işıq',
            'description' => $n(
                'Trek sistemli otaqda bir trek işıqlandırıcısına düşən sahə («Sadə» rejim norması)',
                'Площадь на один трековый светильник в комнате с трековой системой (норма режима «Простой»)',
                'Area per track light in a room with a track system («Simple» mode norm)',
            )],
        ['key' => 'TRACK_RAIL_M_PER_M2', 'group' => 'ENGINEERING', 'type' => 'decimal', 'value' => 0.33, 'unit' => 'p.m/m²',
            'description' => $n(
                'Trek sistemli otaqda otağın hər m²-nə düşən şina uzunluğu, xətti metr («Sadə» rejim norması)',
                'Длина шинопровода на каждый м² комнаты с трековой системой, пог. м (норма режима «Простой»)',
                'Track rail length per m² of a room with a track system, running metres («Simple» mode norm)',
            )],
        ['key' => 'TRACK_RAIL_FEED_M', 'group' => 'ENGINEERING', 'type' => 'decimal', 'value' => 3, 'unit' => 'p.m/nöqtə',
            'description' => $n(
                'Hər bu qədər metr trek şinasına bir qidalanma nöqtəsi; nöqtələr elektrik nöqtələrinin cəminə və kabel normasına daxildir',
                'Одна точка питания на каждые столько метров шинопровода; точки входят в сумму электроточек и норму кабеля',
                'One feed point per this many metres of track rail; the points count as electrical points and add cable',
            )],
    ],

    // §46. Lights are spots, «1 per 2 m²» / «1 per 1.5 m²» rounded up — a share per m²:
    // 4.8 m² balcony → 1 / 1 / 3, 2.4 m² wardrobe → 1 / 1 / 2 (as in the project).
    // `after` places the new rows behind the corridor in the admin's order.
    'room_types' => [
        'BALCONY' => ['name' => $n('Balkon / lociya', 'Балкон / лоджия', 'Balcony / loggia'), 'plumbing' => false, 'waterproof' => false,
            'points' => ['sockets' => [1, 0], 'switches' => [1, 0], 'lights_spot' => [0, 0.5]],
            'fixtures' => [], 'ac_extra_above' => null, 'tv_min_area' => null, 'after' => 'CORRIDOR'],
        'WARDROBE' => ['name' => $n('Qarderob', 'Гардеробная', 'Walk-in wardrobe'), 'plumbing' => false, 'waterproof' => false,
            'points' => ['sockets' => [1, 0], 'switches' => [1, 0], 'lights_spot' => [0, 0.6667]],
            'fixtures' => [], 'ac_extra_above' => null, 'tv_min_area' => null, 'after' => 'BALCONY'],
    ],
];
