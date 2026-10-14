<?php

namespace Database\Seeders;

use App\Models\CalculatorCity;
use App\Models\CalculatorCoefficient;
use App\Models\CalculatorMaterialFallback;
use App\Models\CalculatorMaterialNorm;
use App\Models\CalculatorQuickRate;
use App\Models\CalculatorSetting;
use App\Models\CalculatorWorkRate;
use App\Support\VersionedCache;
use Illuminate\Database\Seeder;

/**
 * The renovation calculator's starting configuration, transcribed from the client's
 * filled price matrix (02_Archi_Calculator_Price_Input_Matrix) and the TZ.
 *
 * The six quick-calculator prices are APPROVED by Archi. Everything else — work
 * rates, fallback material prices, coefficients, norms and limits — is the DRAFT
 * 2026 Baku research that came with the matrix and is meant to be reviewed in the
 * admin panel; the seeder only puts it there. Re-running is safe: every row is
 * matched on its technical id and updated, so an admin's edits to prices are only
 * overwritten if the seeder is run again deliberately.
 */
class CalculatorSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedQuickRates();
        $this->seedSettings();
        $this->seedCities();
        $this->seedCoefficients();
        $this->seedNorms();
        $this->seedWorkRates();
        $this->seedMaterialFallbacks();

        VersionedCache::bump(VersionedCache::CALCULATOR);
    }

    /** TZ §54 — the only figures the client has already approved. */
    private function seedQuickRates(): void
    {
        $prices = [
            ['WHITE_BOX', 'ECONOMY', 350],
            ['WHITE_BOX', 'STANDARD', 450],
            ['WHITE_BOX', 'PREMIUM', 550],
            ['FULL_RENOVATION', 'ECONOMY', 500],
            ['FULL_RENOVATION', 'STANDARD', 650],
            ['FULL_RENOVATION', 'PREMIUM', 800],
        ];

        foreach ($prices as [$type, $segment, $price]) {
            CalculatorQuickRate::updateOrCreate(
                ['renovation_type' => $type, 'segment' => $segment],
                ['price_per_m2' => $price, 'currency' => 'AZN', 'is_active' => true],
            );
        }
    }

    private function seedSettings(): void
    {
        foreach ($this->settings() as $row) {
            CalculatorSetting::updateOrCreate(
                ['key' => $row['key']],
                [
                    'group' => $row['group'],
                    'description' => $row['description'],
                    'value' => (string) $row['value'],
                    'type' => $row['type'],
                    'unit' => $row['unit'] ?? null,
                    'notes' => $row['notes'] ?? null,
                ],
            );
        }
    }

    private function settings(): array
    {
        return [
            // Quick calculator (TZ §6, §16, §20, §22)
            ['key' => 'QUICK_MIN_AREA', 'group' => 'QUICK', 'type' => 'decimal', 'value' => 10, 'unit' => 'm²',
                'description' => ['az' => 'Sürətli kalkulyator — minimum sahə', 'ru' => 'Быстрый калькулятор — минимальная площадь', 'en' => 'Quick calculator — minimum area']],
            ['key' => 'QUICK_MAX_AREA', 'group' => 'QUICK', 'type' => 'decimal', 'value' => 10000, 'unit' => 'm²',
                'description' => ['az' => 'Sürətli kalkulyator — maksimum sahə', 'ru' => 'Быстрый калькулятор — максимальная площадь', 'en' => 'Quick calculator — maximum area']],
            ['key' => 'QUICK_DEFAULT_AREA', 'group' => 'QUICK', 'type' => 'decimal', 'value' => 80, 'unit' => 'm²',
                'description' => ['az' => 'Sürətli kalkulyator — ilkin sahə', 'ru' => 'Быстрый калькулятор — площадь по умолчанию', 'en' => 'Quick calculator — default area']],
            ['key' => 'QUICK_DEFAULT_TYPE', 'group' => 'QUICK', 'type' => 'string', 'value' => 'WHITE_BOX',
                'description' => ['az' => 'Sürətli kalkulyator — ilkin təmir növü', 'ru' => 'Быстрый калькулятор — тип ремонта по умолчанию', 'en' => 'Quick calculator — default renovation type']],
            ['key' => 'QUICK_DEFAULT_SEGMENT', 'group' => 'QUICK', 'type' => 'string', 'value' => 'STANDARD',
                'description' => ['az' => 'Sürətli kalkulyator — ilkin qiymət kateqoriyası', 'ru' => 'Быстрый калькулятор — категория по умолчанию', 'en' => 'Quick calculator — default segment']],
            ['key' => 'SHOW_FROM_PREFIX', 'group' => 'QUICK', 'type' => 'boolean', 'value' => 1,
                'description' => ['az' => '«-dən» prefiksini göstər', 'ru' => 'Показывать «от»', 'en' => 'Show the "from" prefix'],
                'notes' => 'TZ §20 — hesablama orientir xarakterlidir, ona görə tövsiyə olunur.'],
            ['key' => 'DETAILED_CALCULATOR_URL', 'group' => 'QUICK', 'type' => 'string', 'value' => '/calculator/detailed',
                'description' => ['az' => 'CTA düyməsinin ünvanı', 'ru' => 'Адрес CTA-кнопки', 'en' => 'CTA target URL']],

            // Finance
            ['key' => 'CONTINGENCY_PERCENT', 'group' => 'FINANCE', 'type' => 'decimal', 'value' => 10, 'unit' => '%',
                'description' => ['az' => 'Maliyyə ehtiyatı (contingency)', 'ru' => 'Финансовый резерв', 'en' => 'Financial contingency'],
                'notes' => 'Material waste faizi ilə qarışdırmayın — o, material miqdarının içindədir.'],
            ['key' => 'VAT_MODE', 'group' => 'FINANCE', 'type' => 'string', 'value' => 'INCLUDED',
                'description' => ['az' => 'ƏDV rejimi (INCLUDED / ADDED)', 'ru' => 'Режим НДС (INCLUDED / ADDED)', 'en' => 'VAT mode (INCLUDED / ADDED)'],
                'notes' => 'INCLUDED = tariflər ƏDV-ni daxil edir, ayrıca sətir göstərilmir.'],
            ['key' => 'VAT_PERCENT', 'group' => 'FINANCE', 'type' => 'decimal', 'value' => 18, 'unit' => '%',
                'description' => ['az' => 'ƏDV faizi', 'ru' => 'Ставка НДС', 'en' => 'VAT percent']],
            ['key' => 'ROUND_TOTALS_TO_INTEGER', 'group' => 'FINANCE', 'type' => 'boolean', 'value' => 1,
                'description' => ['az' => 'Yekun məbləği tam ədədə yuvarlaqlaşdır', 'ru' => 'Округлять итог до целого', 'en' => 'Round the grand total to whole manats']],

            // Validation
            ['key' => 'AREA_TOLERANCE_PERCENT', 'group' => 'VALIDATION', 'type' => 'decimal', 'value' => 5, 'unit' => '%',
                'description' => ['az' => 'Obyekt sahəsi ilə otaqların cəmi arasında icazə verilən fərq', 'ru' => 'Допустимое расхождение площади объекта и суммы комнат', 'en' => 'Allowed deviation between object area and the sum of rooms']],

            // Logistics
            ['key' => 'FREE_LIFT_FLOORS', 'group' => 'LOGISTICS', 'type' => 'integer', 'value' => 1, 'unit' => 'mərtəbə',
                'description' => ['az' => 'Lift olmadıqda pulsuz mərtəbə sayı', 'ru' => 'Этажи без начисления подъёма', 'en' => 'Floors carried free when there is no lift']],
            ['key' => 'DELIVERY_VEHICLE_WEIGHT_CAPACITY', 'group' => 'LOGISTICS', 'type' => 'decimal', 'value' => 1500, 'unit' => 'kq',
                'description' => ['az' => 'Nəqliyyatın yükgötürmə qabiliyyəti', 'ru' => 'Грузоподъёмность транспорта', 'en' => 'Vehicle weight capacity']],
            ['key' => 'DELIVERY_VEHICLE_VOLUME_CAPACITY', 'group' => 'LOGISTICS', 'type' => 'decimal', 'value' => 12, 'unit' => 'm³',
                'description' => ['az' => 'Nəqliyyatın həcmi', 'ru' => 'Объём кузова', 'en' => 'Vehicle volume capacity']],
            ['key' => 'DEBRIS_VOLUME_PER_M2', 'group' => 'LOGISTICS', 'type' => 'decimal', 'value' => 0.05, 'unit' => 'm³/m²',
                'description' => ['az' => 'Demontajda 1 m²-ə düşən tikinti tullantısı', 'ru' => 'Объём строительного мусора на 1 м²', 'en' => 'Debris volume per m² of demolition'],
                'notes' => 'İstifadəçi həcmi özü daxil etmədikdə istifadə olunur.'],
            ['key' => 'DEBRIS_VEHICLE_VOLUME_CAPACITY', 'group' => 'LOGISTICS', 'type' => 'decimal', 'value' => 6, 'unit' => 'm³',
                'description' => ['az' => 'Tullantı maşınının həcmi (bir reys)', 'ru' => 'Объём мусоровоза (один рейс)', 'en' => 'Debris vehicle volume per trip']],

            // HVAC
            ['key' => 'SPLIT_INCLUDED_ROUTE_METERS', 'group' => 'HVAC', 'type' => 'decimal', 'value' => 3, 'unit' => 'm/blok',
                'description' => ['az' => 'Standart split montajına daxil olan trassa', 'ru' => 'Трасса, включённая в стандартный монтаж split', 'en' => 'Route metres included in a standard split installation']],

            // Material defaults
            ['key' => 'DEFAULT_PAINT_COATS', 'group' => 'MATERIAL', 'type' => 'decimal', 'value' => 2, 'unit' => 'qat',
                'description' => ['az' => 'Boyanın ilkin qat sayı', 'ru' => 'Количество слоёв краски по умолчанию', 'en' => 'Default paint coats']],
            ['key' => 'DEFAULT_PRIMER_COATS', 'group' => 'MATERIAL', 'type' => 'decimal', 'value' => 1, 'unit' => 'qat',
                'description' => ['az' => 'Qruntun ilkin qat sayı', 'ru' => 'Количество слоёв грунтовки по умолчанию', 'en' => 'Default primer coats']],
            ['key' => 'DEFAULT_WALL_PUTTY_LAYERS', 'group' => 'MATERIAL', 'type' => 'decimal', 'value' => 2, 'unit' => 'qat',
                'description' => ['az' => 'Şpaklyovkanın ilkin qat sayı', 'ru' => 'Слоёв шпаклёвки по умолчанию', 'en' => 'Default putty layers']],
            ['key' => 'DEFAULT_WATERPROOF_LAYERS', 'group' => 'MATERIAL', 'type' => 'decimal', 'value' => 2, 'unit' => 'qat',
                'description' => ['az' => 'Hidroizolyasiyanın ilkin qat sayı', 'ru' => 'Слоёв гидроизоляции по умолчанию', 'en' => 'Default waterproofing layers']],
            ['key' => 'PLASTER_DEFAULT_THICKNESS_MM', 'group' => 'MATERIAL', 'type' => 'decimal', 'value' => 20, 'unit' => 'mm',
                'description' => ['az' => 'Suvağın ilkin qalınlığı', 'ru' => 'Толщина штукатурки по умолчанию', 'en' => 'Default plaster thickness']],
            ['key' => 'PLASTER_THICK_THRESHOLD_MM', 'group' => 'MATERIAL', 'type' => 'decimal', 'value' => 30, 'unit' => 'mm',
                'description' => ['az' => 'Qalın suvaq əmsalının işə düşdüyü hədd', 'ru' => 'Порог применения коэффициента толстой штукатурки', 'en' => 'Thickness above which the thick-plaster coefficient applies']],
            ['key' => 'SCREED_DEFAULT_THICKNESS_MM', 'group' => 'MATERIAL', 'type' => 'decimal', 'value' => 50, 'unit' => 'mm',
                'description' => ['az' => 'Stəjkanın ilkin qalınlığı', 'ru' => 'Толщина стяжки по умолчанию', 'en' => 'Default screed thickness']],
            ['key' => 'SELF_LEVEL_DEFAULT_THICKNESS_MM', 'group' => 'MATERIAL', 'type' => 'decimal', 'value' => 5, 'unit' => 'mm',
                'description' => ['az' => 'Özüyayılan döşəmənin ilkin qalınlığı', 'ru' => 'Толщина наливного пола по умолчанию', 'en' => 'Default self-levelling thickness']],

            // Geometry defaults (master spec §6). Millimetres, like every dimension
            // the visitor types — see App\Services\Calculator\Units.
            ['key' => 'DEFAULT_ROOM_HEIGHT_MM', 'group' => 'GEOMETRY', 'type' => 'decimal', 'value' => 2800, 'unit' => 'mm',
                'description' => ['az' => 'Otağın ilkin hündürlüyü', 'ru' => 'Высота комнаты по умолчанию', 'en' => 'Default room height']],
            ['key' => 'DEFAULT_DOOR_WIDTH_MM', 'group' => 'GEOMETRY', 'type' => 'decimal', 'value' => 900, 'unit' => 'mm',
                'description' => ['az' => 'Qapının ilkin eni', 'ru' => 'Ширина двери по умолчанию', 'en' => 'Default door width']],
            ['key' => 'DEFAULT_DOOR_HEIGHT_MM', 'group' => 'GEOMETRY', 'type' => 'decimal', 'value' => 2100, 'unit' => 'mm',
                'description' => ['az' => 'Qapının ilkin hündürlüyü', 'ru' => 'Высота двери по умолчанию', 'en' => 'Default door height']],
            ['key' => 'DEFAULT_WINDOW_WIDTH_MM', 'group' => 'GEOMETRY', 'type' => 'decimal', 'value' => 1500, 'unit' => 'mm',
                'description' => ['az' => 'Pəncərənin ilkin eni', 'ru' => 'Ширина окна по умолчанию', 'en' => 'Default window width']],
            ['key' => 'DEFAULT_WINDOW_HEIGHT_MM', 'group' => 'GEOMETRY', 'type' => 'decimal', 'value' => 1400, 'unit' => 'mm',
                'description' => ['az' => 'Pəncərənin ilkin hündürlüyü', 'ru' => 'Высота окна по умолчанию', 'en' => 'Default window height']],

            // Snapshot
            ['key' => 'ESTIMATE_VALID_DAYS', 'group' => 'SNAPSHOT', 'type' => 'integer', 'value' => 30, 'unit' => 'gün',
                'description' => ['az' => 'Smetanın etibarlılıq müddəti', 'ru' => 'Срок действия сметы', 'en' => 'Estimate validity period']],
        ];
    }

    private function seedCities(): void
    {
        $cities = [
            ['BAKU', ['az' => 'Bakı', 'ru' => 'Баку', 'en' => 'Baku'], 1.0, true],
            ['SUMQAYIT', ['az' => 'Sumqayıt', 'ru' => 'Сумгаит', 'en' => 'Sumqayit'], 1.1, false],
            ['GANJA', ['az' => 'Gəncə', 'ru' => 'Гянджа', 'en' => 'Ganja'], 1.1, false],
            ['MINGACHEVIR', ['az' => 'Mingəçevir', 'ru' => 'Мингечевир', 'en' => 'Mingachevir'], 1.1, false],
            ['SHAKI', ['az' => 'Şəki', 'ru' => 'Шеки', 'en' => 'Shaki'], 1.1, false],
            ['LANKARAN', ['az' => 'Lənkəran', 'ru' => 'Ленкорань', 'en' => 'Lankaran'], 1.1, false],
            ['OTHER', ['az' => 'Digər şəhər', 'ru' => 'Другой город', 'en' => 'Other city'], 1.1, false],
        ];

        foreach ($cities as $index => [$key, $name, $coefficient, $isDefault]) {
            CalculatorCity::updateOrCreate(
                ['city_key' => $key],
                ['name' => $name, 'coefficient' => $coefficient, 'is_default' => $isDefault, 'sort_order' => $index, 'is_active' => true],
            );
        }
    }

    private function seedCoefficients(): void
    {
        $rows = [
            ['K_BASE', 'GLOBAL', ['az' => 'Baza əmsalı', 'ru' => 'Базовый коэффициент', 'en' => 'Base coefficient'], 1.0, 'All'],
            ['K_HEIGHT_3_01_3_50', 'HEIGHT', ['az' => 'Tavan hündürlüyü 3.01–3.50 m', 'ru' => 'Высота потолка 3.01–3.50 м', 'en' => 'Ceiling height 3.01–3.50 m'], 1.15, 'wall/ceiling labor'],
            ['K_HEIGHT_3_51_4_00', 'HEIGHT', ['az' => 'Tavan hündürlüyü 3.51–4.00 m', 'ru' => 'Высота потолка 3.51–4.00 м', 'en' => 'Ceiling height 3.51–4.00 m'], 1.30, 'wall/ceiling labor'],
            ['K_HEIGHT_OVER_4', 'HEIGHT', ['az' => 'Tavan hündürlüyü 4.00 m-dən çox', 'ru' => 'Высота потолка более 4.00 м', 'en' => 'Ceiling height above 4.00 m'], 1.50, 'wall/ceiling labor'],
            ['K_TILE_LARGE', 'TILE', ['az' => 'İriformatlı keramoqranit', 'ru' => 'Крупноформатная плитка', 'en' => 'Large-format tile'], 1.25, 'tile install'],
            ['K_TILE_MOSAIC', 'TILE', ['az' => 'Mozaika', 'ru' => 'Мозаика', 'en' => 'Mosaic'], 1.60, 'tile install'],
            ['K_TILE_DIAGONAL', 'TILE', ['az' => 'Diaqonal düzülüş', 'ru' => 'Диагональная раскладка', 'en' => 'Diagonal layout'], 1.15, 'tile install'],
            ['K_PARQUET_HERRINGBONE', 'FLOOR', ['az' => 'Parket «yolka» düzülüşü', 'ru' => 'Паркет ёлкой', 'en' => 'Herringbone parquet'], 1.35, 'parquet install'],
            ['K_DECOR_COMPLEX', 'WALL', ['az' => 'Mürəkkəb dekorativ üzlük', 'ru' => 'Сложная декоративная отделка', 'en' => 'Complex decorative finish'], 1.30, 'decor plaster/panel'],
            ['K_PLASTER_THICK', 'WALL', ['az' => 'Qalın suvaq qatı', 'ru' => 'Толстый слой штукатурки', 'en' => 'Thick plaster layer'], 1.20, 'plaster'],
            ['K_HVAC_ZONE', 'HVAC', ['az' => 'Zonaların sayı əmsalı', 'ru' => 'Коэффициент количества зон', 'en' => 'Zone count coefficient'], 1.10, 'ducted/VRF'],
            ['K_HVAC_COMPLEX', 'HVAC', ['az' => 'HVAC mürəkkəbliyi', 'ru' => 'Сложность HVAC', 'en' => 'HVAC complexity'], 1.20, 'ducted/VRF/central'],
        ];

        foreach ($rows as [$id, $group, $description, $value, $appliesTo]) {
            CalculatorCoefficient::updateOrCreate(
                ['coefficient_id' => $id],
                ['group' => $group, 'description' => $description, 'value' => $value, 'applies_to' => $appliesTo, 'is_active' => true],
            );
        }
    }

    private function seedNorms(): void
    {
        $rows = [
            ['WASTE_TILE_STANDARD', ['az' => 'Kafel — texnoloji ehtiyat', 'ru' => 'Плитка — запас', 'en' => 'Tile — waste'], 'WastePercent', 10, '%'],
            ['WASTE_TILE_DIAGONAL', ['az' => 'Kafel diaqonal — ehtiyat', 'ru' => 'Плитка диагонально — запас', 'en' => 'Diagonal tile — waste'], 'WastePercent', 15, '%'],
            ['WASTE_LAMINATE', ['az' => 'Laminat — ehtiyat', 'ru' => 'Ламинат — запас', 'en' => 'Laminate — waste'], 'WastePercent', 8, '%'],
            ['WASTE_SPC', ['az' => 'SPC — ehtiyat', 'ru' => 'SPC — запас', 'en' => 'SPC — waste'], 'WastePercent', 8, '%'],
            ['WASTE_PARQUET', ['az' => 'Parket — ehtiyat', 'ru' => 'Паркет — запас', 'en' => 'Parquet — waste'], 'WastePercent', 10, '%'],
            ['WASTE_SKIRTING', ['az' => 'Plintus — ehtiyat', 'ru' => 'Плинтус — запас', 'en' => 'Skirting — waste'], 'WastePercent', 5, '%'],
            ['PAINT_COVERAGE', ['az' => 'Boya — örtmə qabiliyyəti', 'ru' => 'Краска — укрывистость', 'en' => 'Paint — coverage'], 'CoverageM2PerLiter', 10, 'm²/L'],
            ['PRIMER_CONSUMPTION', ['az' => 'Qrunt — sərfiyyat', 'ru' => 'Грунтовка — расход', 'en' => 'Primer — consumption'], 'ConsumptionLPerM2', 0.12, 'L/m²'],
            ['PUTTY_CONSUMPTION', ['az' => 'Şpaklyovka — sərfiyyat', 'ru' => 'Шпаклёвка — расход', 'en' => 'Putty — consumption'], 'ConsumptionKgPerM2PerLayer', 1, 'kq/m²/qat'],
            ['PLASTER_CONSUMPTION', ['az' => 'Suvaq — sərfiyyat', 'ru' => 'Штукатурка — расход', 'en' => 'Plaster — consumption'], 'ConsumptionKgPerM2PerMm', 1.4, 'kq/m²/mm'],
            ['TILE_ADHESIVE_CONSUMPTION', ['az' => 'Kafel yapışqanı — sərfiyyat', 'ru' => 'Клей для плитки — расход', 'en' => 'Tile adhesive — consumption'], 'ConsumptionKgPerM2', 4.5, 'kq/m²'],
            ['GROUT_CONSUMPTION', ['az' => 'Dərz doldurucu — sərfiyyat', 'ru' => 'Затирка — расход', 'en' => 'Grout — consumption'], 'ConsumptionKgPerM2', 0.5, 'kq/m²'],
            ['WATERPROOF_CONSUMPTION', ['az' => 'Hidroizolyasiya — sərfiyyat', 'ru' => 'Гидроизоляция — расход', 'en' => 'Waterproofing — consumption'], 'ConsumptionPerM2PerLayer', 1.3, 'kq/m²/qat'],
            ['SCREED_CONSUMPTION', ['az' => 'Stəjka — sərfiyyat', 'ru' => 'Стяжка — расход', 'en' => 'Screed — consumption'], 'ConsumptionKgPerM2PerMm', 1.9, 'kq/m²/mm'],
            ['SELF_LEVEL_CONSUMPTION', ['az' => 'Özüyayılan döşəmə — sərfiyyat', 'ru' => 'Наливной пол — расход', 'en' => 'Self-levelling — consumption'], 'ConsumptionKgPerM2PerMm', 1.6, 'kq/m²/mm'],
        ];

        foreach ($rows as [$id, $name, $parameter, $value, $unit]) {
            CalculatorMaterialNorm::updateOrCreate(
                ['rule_id' => $id],
                ['name' => $name, 'parameter' => $parameter, 'value' => $value, 'unit' => $unit, 'is_active' => true],
            );
        }
    }

    private function seedWorkRates(): void
    {
        $draft = 'DRAFT — 2026 Bakı bazar araşdırması; Archi tərəfindən təsdiqlənməlidir.';
        $sort = 0;

        foreach (require database_path('seeders/data/calculator_work_rates.php') as $row) {
            CalculatorWorkRate::updateOrCreate(
                ['work_id' => $row['work_id']],
                [
                    'category' => $row['category'],
                    'name' => $row['name'],
                    'unit' => $row['unit'],
                    'quantity_source' => $row['quantity_source'],
                    'formula' => $row['formula'] ?? 'Q × Rate',
                    'rate' => $row['rate'],
                    'minimum_charge' => $row['min'] ?? 0,
                    'precision_class' => $row['precision'] ?? 'CALCULATED',
                    'contingency_eligible' => $row['contingency'] ?? true,
                    'notes' => $row['notes'] ?? $draft,
                    'search_keywords' => $row['keywords'] ?? null,
                    'sort_order' => $sort++,
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedMaterialFallbacks(): void
    {
        $sort = 0;

        foreach (require database_path('seeders/data/calculator_material_fallbacks.php') as $row) {
            CalculatorMaterialFallback::updateOrCreate(
                ['material_category_id' => $row['id']],
                [
                    'surface_group' => $row['group'],
                    'name' => $row['name'],
                    'unit' => $row['unit'],
                    'economy_price' => $row['economy'],
                    'standard_price' => $row['standard'],
                    'premium_price' => $row['premium'],
                    'notes' => $row['notes'] ?? null,
                    'sort_order' => $sort++,
                    'is_active' => true,
                ],
            );
        }
    }
}
