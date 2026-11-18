<?php

/*
|--------------------------------------------------------------------------
| Calculator TZ of 17.11.2026 («Archi ətraflı kalkulyatorunun təkmilləşdirilməsi»)
|--------------------------------------------------------------------------
|
| One source for both the production migration (2026_11_18_100100) and
| CalculatorSeeder. The migration only ADDS what is missing and switches rows on/off —
| it never overwrites a price Archi has set in the admin panel; the seeder writes
| everything (fresh installs, tests).
|
| Prices of NEW rows are DRAFT market figures (Baku, November 2026) for Archi to
| confirm — the TZ leaves them to Archi («Tarifləri Archi admin paneldə müəyyən edir»).
|
*/

$draft = 'DRAFT — TZ 17.11.2026; qiyməti Archi təsdiqləməlidir.';
$n = fn (string $az, string $ru, string $en): array => compact('az', 'ru', 'en');

return [
    // ── Stage of every work (WHITE_BOX | FINISH | LAST = the last selected stage |
    //    null = outside both stages, e.g. the design project). TZ §2 table.
    'work_stages' => [
        'WHITE_BOX' => [
            'DEM_FLOOR_LAMINATE', 'DEM_FLOOR_PARQUET', 'DEM_FLOOR_TILE', 'DEM_WALL_TILE', 'DEM_WALL_FINISH', 'DEM_WALL_PLASTER',
            'DEM_PARTITION_MASONRY', 'DEM_PARTITION_GYPSUM', 'DEM_CEILING_GYPSUM', 'DEM_CEILING_STRETCH', 'DEM_DOOR',
            'DEM_PLUMB_FIXTURE', 'DEM_ELECTRIC_POINT', 'DEM_DEBRIS_LOADING', 'DEM_DEBRIS_DISPOSAL',
            'WALL_PARTITION_BLOCK', 'WALL_PARTITION_GYPSUM', 'WALL_PRIMER_BASE', 'WALL_PLASTER', 'WALL_MESH', 'WALL_PUTTY',
            'WALL_SANDING', 'WALL_PRIMER_FINISH', 'WALL_PAINT',
            'FLOOR_SCREED', 'FLOOR_SELF_LEVEL', 'FLOOR_WATERPROOF', 'FLOOR_SOUND_INSULATION', 'FLOOR_HEAT_ELECTRIC', 'FLOOR_HEAT_WATER',
            'CEIL_PRIMER_BASE', 'CEIL_PLASTER', 'CEIL_PUTTY', 'CEIL_SANDING', 'CEIL_PAINT', 'CEIL_GYPSUM_FLAT', 'CEIL_GYPSUM_COMPLEX',
            'TILE_WATERPROOF_WALL',
            'ELEC_SOCKET_POINT', 'ELEC_SWITCH_POINT', 'ELEC_LIGHT_POINT', 'ELEC_WALL_LIGHT_POINT', 'ELEC_TV_POINT', 'ELEC_DATA_POINT',
            'ELEC_AC_POINT', 'ELEC_APPLIANCE_POINT', 'ELEC_DEDICATED_LINE', 'ELEC_CABLE', 'ELEC_CONDUIT', 'ELEC_PANEL_SMALL',
            'ELEC_PANEL_LARGE', 'ELEC_BREAKER', 'ELEC_RCD', 'ELEC_FLOOR_HEAT_CONTROL', 'ELEC_INTERCOM_POINT',
            'PLUMB_WATER_POINT', 'PLUMB_SEWER_POINT', 'PLUMB_PIPE_WATER', 'PLUMB_PIPE_SEWER', 'PLUMB_MANIFOLD', 'PLUMB_TOILET_INSTALL',
            'PLUMB_CONCEALED_CISTERN', 'PLUMB_BASIN_INSTALL', 'PLUMB_KITCHEN_SINK', 'PLUMB_SHOWER_INSTALL', 'PLUMB_BATHTUB_INSTALL',
            'PLUMB_BIDET_INSTALL', 'PLUMB_HYGIENIC_SHOWER', 'PLUMB_MIXER_INSTALL', 'PLUMB_BOILER_INSTALL', 'PLUMB_WASHER_CONNECT',
            'PLUMB_DISHWASHER_CONNECT', 'PLUMB_TOWEL_RAIL',
            'HVAC_SPLIT_EXTRA_ROUTE', 'HVAC_SPLIT_DRAIN', 'HVAC_CONDENSATE_PUMP', 'HVAC_DUCTED_ESTIMATE', 'HVAC_VRF_ESTIMATE',
            'HVAC_CENTRAL_ESTIMATE', 'VENT_DUCT', 'VENT_DIFFUSER', 'VENT_GRILLE', 'VENT_FAN', 'VENT_CORE_DRILL',
            'DOOR_OPENING_PREP', 'WINDOW_SLOPES', 'LOG_SITE_PROTECTION',
        ],
        'FINISH' => [
            'TILE_SURFACE_PREP', 'TILE_INSTALL_STANDARD', 'TILE_INSTALL_LARGE', 'TILE_INSTALL_MOSAIC', 'TILE_CUT_45', 'TILE_GROUT', 'TILE_SILICONE',
            'FLOOR_TILE_INSTALL', 'FLOOR_LAMINATE_INSTALL', 'FLOOR_SPC_INSTALL', 'FLOOR_PARQUET_INSTALL', 'FLOOR_STONE_INSTALL', 'SKIRTING_INSTALL',
            'WALL_WALLPAPER', 'WALL_DECOR_PLASTER', 'WALL_PANEL_INSTALL', 'WALL_STONE_INSTALL',
            'CEIL_STRETCH', 'CEIL_CORNICE', 'CEIL_SHADOW_GAP',
            'LIGHT_SPOT_INSTALL', 'LIGHT_PENDANT_INSTALL', 'LIGHT_CHANDELIER_INSTALL', 'LIGHT_WALL_INSTALL', 'LIGHT_FIXTURE_INSTALL',
            'LIGHT_LED_STRIP_INSTALL', 'LIGHT_LED_PROFILE_INSTALL',
            'HVAC_SPLIT_STD', 'HVAC_MULTISPLIT_OUTDOOR', 'HVAC_MULTISPLIT_INDOOR', 'HVAC_MULTISPLIT_ROUTE',
            'DOOR_INSTALL_INTERIOR', 'DOOR_INSTALL_HIDDEN', 'DOOR_INSTALL_SLIDING', 'WINDOW_SILL',
        ],
        'LAST' => ['LOG_DELIVERY_TRIP', 'LOG_MANUAL_LIFT', 'LOG_PLATFORM_DAY', 'LOG_SCAFFOLD', 'LOG_FINAL_CLEANING'],
        // SERVICE_DESIGN, SERVICE_TECH_SUPERVISION, SERVICE_AUTHOR_SUPERVISION → null
    ],

    // ── Finishes: labour / material stage and the new flags.
    'finish_stages' => [
        'LAMINATE' => ['FINISH', 'FINISH'], 'SPC' => ['FINISH', 'FINISH'], 'PARQUET' => ['FINISH', 'FINISH'], 'CARPET' => ['FINISH', 'FINISH'],
        'FLOOR_TILE' => ['FINISH', 'FINISH'], 'FLOOR_TILE_LARGE' => ['FINISH', 'FINISH'], 'FLOOR_STONE' => ['FINISH', 'FINISH'],
        'PAINT' => ['WHITE_BOX', 'WHITE_BOX'], 'WALLPAPER' => ['FINISH', 'FINISH'], 'DECOR_PLASTER' => ['FINISH', 'FINISH'],
        'WALL_TILE' => ['FINISH', 'FINISH'], 'WALL_TILE_LARGE' => ['FINISH', 'FINISH'], 'MOSAIC' => ['FINISH', 'FINISH'],
        'WALL_STONE' => ['FINISH', 'FINISH'], 'WALL_PANEL' => ['FINISH', 'FINISH'],
        'CEILING_PAINT' => ['WHITE_BOX', 'WHITE_BOX'], 'GYPSUM_FLAT' => ['WHITE_BOX', 'WHITE_BOX'], 'GYPSUM_COMPLEX' => ['WHITE_BOX', 'WHITE_BOX'],
        'STRETCH_CEILING' => ['FINISH', 'FINISH'],
    ],
    // Laminate-type floors lie on an underlay (MAT_LAMINATE_UNDERLAY).
    'finish_underlay' => ['LAMINATE', 'SPC', 'PARQUET'],
    // Paint is bought by the litre: area × coats / coverage (TZ §23).
    'finish_paint_norm' => ['PAINT', 'CEILING_PAINT'],
    // A plasterboard ceiling is filled, sanded, primed and painted on top (TZ §13).
    'finish_top_coat' => ['GYPSUM_FLAT' => 'CEILING_PAINT', 'GYPSUM_COMPLEX' => 'CEILING_PAINT'],

    // ── Material stages.
    'fallback_stages' => [
        'WHITE_BOX' => ['MAT_PRIMER', 'MAT_PUTTY', 'MAT_PLASTER', 'MAT_WATERPROOF', 'MAT_SCREED', 'MAT_SELF_LEVEL', 'MAT_CABLE',
            'MAT_WARM_FLOOR', 'MAT_PIPE_WATER', 'MAT_PIPE_SEWER', 'MAT_ELECTRIC_PANEL', 'MAT_CONDUIT', 'MAT_PLASTER_ACCESSORIES'],
        'FINISH' => ['MAT_SKIRTING', 'MAT_DOOR_INTERIOR', 'MAT_DOOR_HIDDEN', 'MAT_DOOR_SLIDING', 'MAT_SANITARY_BASIC', 'MAT_LIGHTING_BASIC',
            'MAT_ELECTRIC_ACCESSORIES', 'MAT_TILE_ADHESIVE', 'MAT_GROUT', 'MAT_LAMINATE_UNDERLAY', 'MAT_TOILET', 'MAT_BASIN',
            'MAT_SHOWER', 'MAT_BATHTUB', 'MAT_KITCHEN_SINK', 'MAT_MIXER', 'MAT_HYGIENIC_SHOWER', 'MAT_LIGHT_SPOT', 'MAT_LIGHT_PENDANT',
            'MAT_LIGHT_CHANDELIER', 'MAT_LIGHT_WALL', 'MAT_WINDOW_SILL'],
    ],

    // ── Works switched on / off (rates already in the table are kept).
    'activate_works' => [
        'DOOR_OPENING_PREP', 'PLUMB_HYGIENIC_SHOWER', 'PLUMB_WASHER_CONNECT', 'PLUMB_DISHWASHER_CONNECT', 'PLUMB_TOWEL_RAIL',
        'PLUMB_BOILER_INSTALL', 'LIGHT_SPOT_INSTALL', 'LIGHT_PENDANT_INSTALL', 'LIGHT_CHANDELIER_INSTALL',
        'HVAC_SPLIT_EXTRA_ROUTE', 'HVAC_SPLIT_DRAIN',
    ],
    'deactivate_works' => ['LIGHT_FIXTURE_INSTALL'],
    'deactivate_fallbacks' => ['MAT_SANITARY_BASIC', 'MAT_LIGHTING_BASIC'],

    // ── New works (inserted when missing; full rows so a fresh install has them too).
    'works' => [
        ['work_id' => 'WINDOW_SLOPES', 'category' => 'DOORS', 'unit' => 'p.m', 'quantity_source' => 'WindowSlopesLength', 'rate' => 15, 'notes' => $draft,
            'formula' => 'Σ (en + 2 × hündürlük) × say', 'keywords' => 'pəncərə yanlığı; otkos; откосы',
            'name' => $n('Pəncərə yanlıqlarının (otkosların) hazırlanması', 'Откосы окон', 'Window reveals')],
        ['work_id' => 'WINDOW_SILL', 'category' => 'DOORS', 'unit' => 'p.m', 'quantity_source' => 'WindowSillLength', 'rate' => 10, 'notes' => $draft,
            'formula' => 'Σ pəncərə eni × say', 'keywords' => 'pəncərə altlığı; podokonnik; подоконник',
            'name' => $n('Pəncərə altlığının (podokonnikin) quraşdırılması', 'Установка подоконника', 'Window sill installation')],
        ['work_id' => 'ELEC_INTERCOM_POINT', 'category' => 'ELECTRICAL', 'unit' => 'nöqtə', 'quantity_source' => 'IntercomPointCount', 'rate' => 25, 'notes' => $draft,
            'keywords' => 'domofon; домофон; intercom',
            'name' => $n('Domofon nöqtəsi', 'Точка домофона', 'Intercom point')],
        ['work_id' => 'LIGHT_WALL_INSTALL', 'category' => 'LIGHTING', 'unit' => 'əd', 'quantity_source' => 'WallLightCount', 'rate' => 15, 'notes' => $draft,
            'keywords' => 'bra; divar işığı; бра',
            'name' => $n('Bra (divar işığı) montajı', 'Монтаж бра', 'Wall light installation')],
        // Existing rows a fresh install (tests) must also have; the migration only
        // switches them on in production, where they already exist with Archi's rates.
        ['work_id' => 'DOOR_OPENING_PREP', 'category' => 'DOORS', 'unit' => 'əd', 'quantity_source' => 'DoorOpeningPrepCount', 'rate' => 50,
            'name' => $n('Qapı yerinin hazırlanması', 'Подготовка дверного проёма', 'Door opening preparation')],
        ['work_id' => 'PLUMB_HYGIENIC_SHOWER', 'category' => 'PLUMBING', 'unit' => 'əd', 'quantity_source' => 'HygienicShowerCount', 'rate' => 30,
            'name' => $n('Gigiyenik duşun quraşdırılması', 'Установка гигиенического душа', 'Hygienic shower installation')],
        ['work_id' => 'PLUMB_WASHER_CONNECT', 'category' => 'PLUMBING', 'unit' => 'əd', 'quantity_source' => 'WasherCount', 'rate' => 45,
            'name' => $n('Paltaryuyan maşının qoşulması', 'Подключение стиральной машины', 'Washing machine connection')],
        ['work_id' => 'PLUMB_DISHWASHER_CONNECT', 'category' => 'PLUMBING', 'unit' => 'əd', 'quantity_source' => 'DishwasherCount', 'rate' => 35,
            'name' => $n('Qabyuyan maşının qoşulması', 'Подключение посудомоечной машины', 'Dishwasher connection')],
        ['work_id' => 'PLUMB_TOWEL_RAIL', 'category' => 'PLUMBING', 'unit' => 'əd', 'quantity_source' => 'TowelRailCount', 'rate' => 40,
            'name' => $n('Dəsmalqurudanın quraşdırılması', 'Установка полотенцесушителя', 'Towel rail installation')],
        ['work_id' => 'PLUMB_BOILER_INSTALL', 'category' => 'PLUMBING', 'unit' => 'əd', 'quantity_source' => 'BoilerCount', 'rate' => 50,
            'name' => $n('Boylerin quraşdırılması', 'Установка бойлера', 'Water heater installation')],
        ['work_id' => 'LIGHT_SPOT_INSTALL', 'category' => 'LIGHTING', 'unit' => 'əd', 'quantity_source' => 'SpotLightCount', 'rate' => 10,
            'name' => $n('Nöqtəvi işıqlandırıcının (spotun) montajı', 'Монтаж точечного светильника', 'Spot light installation')],
        ['work_id' => 'LIGHT_PENDANT_INSTALL', 'category' => 'LIGHTING', 'unit' => 'əd', 'quantity_source' => 'PendantCount', 'rate' => 20,
            'name' => $n('Asma işıqlandırıcının montajı', 'Монтаж подвесного светильника', 'Pendant light installation')],
        ['work_id' => 'LIGHT_CHANDELIER_INSTALL', 'category' => 'LIGHTING', 'unit' => 'əd', 'quantity_source' => 'ChandelierCount', 'rate' => 30,
            'name' => $n('Çilçırağın montajı', 'Монтаж люстры', 'Chandelier installation')],
        ['work_id' => 'HVAC_SPLIT_EXTRA_ROUTE', 'category' => 'HVAC', 'unit' => 'm', 'quantity_source' => 'SplitExtraRouteMeters', 'rate' => 35,
            'name' => $n('Kondisioner freon xəttinin çəkilməsi', 'Прокладка фреоновой трассы кондиционера', 'AC refrigerant line')],
        ['work_id' => 'HVAC_SPLIT_DRAIN', 'category' => 'HVAC', 'unit' => 'm', 'quantity_source' => 'CondensateDrainMeters', 'rate' => 10,
            'name' => $n('Kondisioner drenajı', 'Дренаж кондиционера', 'AC condensate drain')],
    ],

    // ── New material categories (inserted when missing).
    'fallbacks' => [
        ['id' => 'MAT_PIPE_WATER', 'group' => 'PLUMBING', 'unit' => 'm', 'economy' => 3, 'standard' => 5, 'premium' => 8,
            'name' => $n('Su borusu və fitinqlər', 'Водопроводная труба и фитинги', 'Water pipe and fittings')],
        ['id' => 'MAT_PIPE_SEWER', 'group' => 'PLUMBING', 'unit' => 'm', 'economy' => 4, 'standard' => 6, 'premium' => 10,
            'name' => $n('Kanalizasiya borusu və fitinqlər', 'Канализационная труба и фитинги', 'Sewer pipe and fittings')],
        ['id' => 'MAT_ELECTRIC_PANEL', 'group' => 'ELECTRICAL', 'unit' => 'dəst', 'economy' => 150, 'standard' => 250, 'premium' => 450,
            'name' => $n('Elektrik paneli (avtomatlar və diferensial açar)', 'Электрощит (автоматы и УЗО)', 'Electrical panel (breakers and RCD)')],
        ['id' => 'MAT_CONDUIT', 'group' => 'ELECTRICAL', 'unit' => 'm', 'economy' => 0.4, 'standard' => 0.6, 'premium' => 1,
            'name' => $n('Qofro boru', 'Гофротруба', 'Corrugated conduit')],
        ['id' => 'MAT_PLASTER_ACCESSORIES', 'group' => 'ROUGH', 'unit' => 'm²', 'economy' => 1, 'standard' => 1.5, 'premium' => 2.5,
            'name' => $n('Suvaq aksesuarları (tor, mayaklar, künclüklər)', 'Комплектующие штукатурки (сетка, маяки, уголки)', 'Plaster accessories (mesh, beads, corners)')],
        ['id' => 'MAT_LAMINATE_UNDERLAY', 'group' => 'FLOOR', 'unit' => 'm²', 'economy' => 1.5, 'standard' => 3, 'premium' => 6,
            'name' => $n('Laminat / SPC / parket altlığı', 'Подложка под ламинат / SPC / паркет', 'Laminate / SPC / parquet underlay')],
        ['id' => 'MAT_TOILET', 'group' => 'PLUMBING', 'unit' => 'əd', 'economy' => 90, 'standard' => 220, 'premium' => 450,
            'name' => $n('Unitaz', 'Унитаз', 'Toilet')],
        ['id' => 'MAT_BASIN', 'group' => 'PLUMBING', 'unit' => 'əd', 'economy' => 45, 'standard' => 110, 'premium' => 260,
            'name' => $n('Lavabo (əlüzyuyan)', 'Раковина', 'Wash basin')],
        ['id' => 'MAT_SHOWER', 'group' => 'PLUMBING', 'unit' => 'əd', 'economy' => 120, 'standard' => 280, 'premium' => 650,
            'name' => $n('Duş sistemi / poddon', 'Душевая система / поддон', 'Shower set / tray')],
        ['id' => 'MAT_BATHTUB', 'group' => 'PLUMBING', 'unit' => 'əd', 'economy' => 220, 'standard' => 420, 'premium' => 950,
            'name' => $n('Vanna', 'Ванна', 'Bathtub')],
        ['id' => 'MAT_KITCHEN_SINK', 'group' => 'PLUMBING', 'unit' => 'əd', 'economy' => 45, 'standard' => 95, 'premium' => 220,
            'name' => $n('Mətbəx yuyucusu', 'Кухонная мойка', 'Kitchen sink')],
        ['id' => 'MAT_MIXER', 'group' => 'PLUMBING', 'unit' => 'əd', 'economy' => 18, 'standard' => 90, 'premium' => 230,
            'name' => $n('Qarışdırıcı', 'Смеситель', 'Mixer tap')],
        ['id' => 'MAT_HYGIENIC_SHOWER', 'group' => 'PLUMBING', 'unit' => 'əd', 'economy' => 25, 'standard' => 60, 'premium' => 150,
            'name' => $n('Gigiyenik duş', 'Гигиенический душ', 'Hygienic shower')],
        ['id' => 'MAT_LIGHT_SPOT', 'group' => 'LIGHTING', 'unit' => 'əd', 'economy' => 15, 'standard' => 27, 'premium' => 45,
            'name' => $n('Spot (nöqtəvi işıqlandırıcı)', 'Точечный светильник', 'Spot light')],
        ['id' => 'MAT_LIGHT_PENDANT', 'group' => 'LIGHTING', 'unit' => 'əd', 'economy' => 30, 'standard' => 50, 'premium' => 120,
            'name' => $n('Asma işıqlandırıcı', 'Подвесной светильник', 'Pendant light')],
        ['id' => 'MAT_LIGHT_CHANDELIER', 'group' => 'LIGHTING', 'unit' => 'əd', 'economy' => 30, 'standard' => 50, 'premium' => 150,
            'name' => $n('Çilçıraq', 'Люстра', 'Chandelier')],
        ['id' => 'MAT_LIGHT_WALL', 'group' => 'LIGHTING', 'unit' => 'əd', 'economy' => 20, 'standard' => 40, 'premium' => 90,
            'name' => $n('Bra (divar işığı)', 'Бра', 'Wall light')],
        ['id' => 'MAT_WINDOW_SILL', 'group' => 'WINDOWS', 'unit' => 'p.m', 'economy' => 20, 'standard' => 35, 'premium' => 60,
            'name' => $n('Pəncərə altlığı (podokonnik)', 'Подоконник', 'Window sill')],
    ],

    // ── New settings.
    'settings' => [
        ['key' => 'CONTRACTOR_MARGIN_PERCENT', 'group' => 'FINANCE', 'type' => 'decimal', 'value' => 15, 'unit' => '%',
            'description' => $n('Podratçının idarəetmə və mənfəəti — iş, material, logistika və xidmətlərin cəminə faiz (ehtiyatdan ayrı)', 'Управление и прибыль подрядчика — процент к сумме работ, материалов, логистики и услуг (отдельно от резерва)', 'Contractor management and profit — percent of works, materials, logistics and services (separate from the reserve)')],
        ['key' => 'CONSTRUCTION_WASTE_M3_PER_M2', 'group' => 'LOGISTICS', 'type' => 'decimal', 'value' => 0.05, 'unit' => 'm³/m²',
            'description' => $n('Sökülmə olmasa da yaranan tikinti tullantısı — hər m² sahəyə həcm', 'Строительный мусор даже без демонтажа — объём на 1 м² площади', 'Construction waste even without demolition — volume per m² of area')],
        ['key' => 'SPOT_AREA_M2', 'group' => 'ENGINEERING', 'type' => 'decimal', 'value' => 2.7, 'unit' => 'm²/spot',
            'description' => $n('Gipskarton və dartma tavanda bir spota düşən sahə («Sadə» rejim normu)', 'Площадь на один спот при гипсокартонном и натяжном потолке (норма режима «Простой»)', 'Area per spot light under a plasterboard or stretch ceiling («Simple» mode norm)')],
        ['key' => 'AC_ROUTE_M_PER_UNIT', 'group' => 'ENGINEERING', 'type' => 'decimal', 'value' => 4, 'unit' => 'm/kondisioner',
            'description' => $n('Hər kondisioner üçün freon xəttinin uzunluğu (çıxış — White Box)', 'Длина фреоновой трассы на один кондиционер (вывод — White Box)', 'Refrigerant line length per air conditioner (outlet — White Box)')],
        ['key' => 'AC_DRAIN_M_PER_UNIT', 'group' => 'ENGINEERING', 'type' => 'decimal', 'value' => 4, 'unit' => 'm/kondisioner',
            'description' => $n('Hər kondisioner üçün drenaj xəttinin uzunluğu (çıxış — White Box)', 'Длина дренажа на один кондиционер (вывод — White Box)', 'Condensate drain length per air conditioner (outlet — White Box)')],
    ],

    // ── Room types (admin «Otaq növləri»). Points: base + per m² (rounded up).
    //    Calibrated by Archi on the Sea Breeze object (TZ §6).
    'room_types' => [
        'LIVING' => ['name' => $n('Qonaq otağı', 'Гостиная', 'Living room'), 'plumbing' => false, 'waterproof' => false,
            'points' => ['sockets' => [6, 0.2], 'switches' => [2, 0], 'lights_chandelier' => [1, 0], 'wall_lights' => [2, 0], 'tv' => [1, 0], 'data' => [1, 0], 'ac_power' => [1, 0]],
            'fixtures' => [], 'ac_extra_above' => 25, 'tv_min_area' => null],
        'KITCHEN_LIVING' => ['name' => $n('Mətbəx-qonaq otağı', 'Кухня-гостиная', 'Kitchen-living room'), 'plumbing' => true, 'waterproof' => false,
            'points' => ['sockets' => [8, 0.25], 'switches' => [3, 0], 'lights_chandelier' => [1, 0], 'lights_pendant' => [1, 0], 'tv' => [1, 0], 'data' => [1, 0], 'ac_power' => [1, 0], 'appliance' => [3, 0]],
            'fixtures' => ['kitchen_sink' => 1, 'mixer' => 1, 'dishwasher' => 1], 'ac_extra_above' => 25, 'tv_min_area' => null],
        'KITCHEN' => ['name' => $n('Mətbəx', 'Кухня', 'Kitchen'), 'plumbing' => true, 'waterproof' => false,
            'points' => ['sockets' => [8, 0.2], 'switches' => [1, 0], 'lights_pendant' => [1, 0], 'appliance' => [3, 0]],
            'fixtures' => ['kitchen_sink' => 1, 'mixer' => 1, 'dishwasher' => 1], 'ac_extra_above' => null, 'tv_min_area' => null],
        'BEDROOM' => ['name' => $n('Yataq otağı', 'Спальня', 'Bedroom'), 'plumbing' => false, 'waterproof' => false,
            'points' => ['sockets' => [3, 0.25], 'switches' => [2, 0], 'lights_chandelier' => [1, 0], 'wall_lights' => [2, 0], 'tv' => [1, 0], 'data' => [1, 0], 'ac_power' => [1, 0]],
            'fixtures' => [], 'ac_extra_above' => null, 'tv_min_area' => 15],
        'BATHROOM' => ['name' => $n('Sanitar qovşaq (hamam)', 'Санузел (ванная)', 'Bathroom'), 'plumbing' => true, 'waterproof' => true,
            'points' => ['sockets' => [2, 0], 'switches' => [1, 0]],
            'fixtures' => ['toilet' => 1, 'basin' => 1, 'shower' => 1, 'hygienic_shower' => 1, 'washer' => 1, 'mixer' => 2], 'ac_extra_above' => null, 'tv_min_area' => null],
        'WC' => ['name' => $n('Tualet', 'Туалет', 'WC'), 'plumbing' => true, 'waterproof' => true,
            'points' => ['sockets' => [1, 0], 'switches' => [1, 0]],
            'fixtures' => ['toilet' => 1, 'basin' => 1, 'hygienic_shower' => 1, 'mixer' => 1], 'ac_extra_above' => null, 'tv_min_area' => null],
        'CORRIDOR' => ['name' => $n('Dəhliz', 'Коридор', 'Corridor'), 'plumbing' => false, 'waterproof' => false,
            'points' => ['sockets' => [2, 0.15], 'switches' => [3, 0], 'intercom' => [1, 0]],
            'fixtures' => [], 'ac_extra_above' => null, 'tv_min_area' => null],
        'OFFICE' => ['name' => $n('Ofis otağı', 'Офисное помещение', 'Office'), 'plumbing' => false, 'waterproof' => false,
            'points' => ['sockets' => [4, 0.3], 'switches' => [1, 0], 'lights_pendant' => [1, 0], 'data' => [2, 0], 'ac_power' => [1, 0]],
            'fixtures' => [], 'ac_extra_above' => 30, 'tv_min_area' => null],
        'COMMERCIAL' => ['name' => $n('Kommersiya sahəsi', 'Коммерческое помещение', 'Commercial space'), 'plumbing' => false, 'waterproof' => false,
            'points' => ['sockets' => [4, 0.2], 'switches' => [2, 0], 'lights_pendant' => [2, 0], 'data' => [2, 0], 'ac_power' => [1, 0]],
            'fixtures' => [], 'ac_extra_above' => 30, 'tv_min_area' => null],
        'OTHER' => ['name' => $n('Digər otaq', 'Другое помещение', 'Other room'), 'plumbing' => false, 'waterproof' => false,
            'points' => ['sockets' => [2, 0.2], 'switches' => [1, 0], 'lights_chandelier' => [1, 0]],
            'fixtures' => [], 'ac_extra_above' => null, 'tv_min_area' => null],
    ],
];
