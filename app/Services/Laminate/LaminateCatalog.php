<?php

declare(strict_types=1);

namespace App\Services\Laminate;

use App\Models\Attribute;
use App\Models\SubCategory;
use App\Support\ClassifierFields;
use Illuminate\Support\Facades\DB;

/**
 * Fixed identity of the laminate card: the class it belongs to, its dictionaries
 * and the quick-pick values of the form (TZ «Ламинат» §1, §3, §4).
 *
 * The TZ names the class key `laminate`; on this site that class already exists
 * with the slug `laminat` (Döşəmələr → Polimer və rulon örtüklər → Laminat), and
 * catalog URLs use it, so the existing slug is the stable key rather than a new one.
 */
final class LaminateCatalog
{
    public const CLASS_SLUG = 'laminat';

    public const ATTR_USAGE = 'laminate-usage-class';

    public const ATTR_ABRASION = 'laminate-abrasion';

    public const ATTR_CONNECTION = 'laminate-connection';

    /** The form fields that point at a dictionary, and which dictionary. */
    public const OPTION_FIELDS = [
        'usage_class' => self::ATTR_USAGE,
        'abrasion' => self::ATTR_ABRASION,
        'connection' => self::ATTR_CONNECTION,
    ];

    /**
     * The three dictionaries (TZ §2 «attribute_options»): attribute code →
     * name + options keyed by their stable value code.
     *
     * @var array<string, array{name: array<string,string>, options: array<string, array<string,string>>}>
     */
    public const DICTIONARIES = [
        self::ATTR_USAGE => [
            'name' => ['az' => 'İstismar sinfi', 'ru' => 'Класс эксплуатации', 'en' => 'Usage class'],
            'options' => [
                '31' => ['az' => '31', 'ru' => '31', 'en' => '31'],
                '32' => ['az' => '32', 'ru' => '32', 'en' => '32'],
                '33' => ['az' => '33', 'ru' => '33', 'en' => '33'],
                '34' => ['az' => '34', 'ru' => '34', 'en' => '34'],
            ],
        ],
        self::ATTR_ABRASION => [
            'name' => ['az' => 'Aşınma sinfi (AC)', 'ru' => 'Истираемость (AC)', 'en' => 'Abrasion class (AC)'],
            'options' => [
                'ac3' => ['az' => 'AC3', 'ru' => 'AC3', 'en' => 'AC3'],
                'ac4' => ['az' => 'AC4', 'ru' => 'AC4', 'en' => 'AC4'],
                'ac5' => ['az' => 'AC5', 'ru' => 'AC5', 'en' => 'AC5'],
                'ac6' => ['az' => 'AC6', 'ru' => 'AC6', 'en' => 'AC6'],
            ],
        ],
        self::ATTR_CONNECTION => [
            'name' => ['az' => 'Birləşmə növü', 'ru' => 'Тип соединения', 'en' => 'Joint type'],
            'options' => [
                'click' => ['az' => 'Click (kilidli)', 'ru' => 'Click (замковое)', 'en' => 'Click (locking)'],
                'glued_tongue_groove' => ['az' => 'Yapışqanlı şip-paz', 'ru' => 'Клеевое шпунт-паз', 'en' => 'Glued tongue and groove'],
            ],
        ],
    ];

    /** TZ §3 №5: 6 / 7 / 8 / 9 / 10 / 12 / другая. */
    public const THICKNESS_PICKS = ['6', '7', '8', '9', '10', '12'];

    /**
     * TZ §3 №6: ready size pairs + "Другой размер". The TZ means the pairs of the
     * chosen collection; collections are not a dictionary on this site yet, so these
     * are the common laminate formats already listed in the old class definition.
     *
     * @var list<array{0: string, 1: string}>
     */
    public const SIZE_PICKS = [['1200', '190'], ['1285', '192'], ['1380', '193']];

    /** TZ §4: 24 / 48 / 72 / 100 / другое / не указано. */
    public const WATER_HOURS_PICKS = [24, 48, 72, 100];

    /** TZ §4: 27 / 28 / другое / не указано. */
    public const SURFACE_TEMP_PICKS = ['27', '28'];

    /**
     * TZ §4 asks for "values of verified models"; no such list exists yet, so these
     * are the usual integrated-underlay thicknesses. "Digər" accepts any value.
     */
    public const UNDERLAY_PICKS = ['1', '1.5', '2'];

    /** TZ §5: 5 / 10 / 15 / 20 / 25 / 30 / другой / пожизненная / не указан. */
    public const WARRANTY_YEAR_PICKS = [5, 10, 15, 20, 25, 30];

    /** TZ §5 «Предложение»: the price is per m² or per pack, nothing else. */
    public const PRICE_UNITS = ['m2', 'pack'];

    private static ?int $classId = null;

    private static bool $classResolved = false;

    /** @var array<string, array<string, int>> */
    private static array $optionIds = [];

    public static function classId(): ?int
    {
        if (! self::$classResolved) {
            self::$classId = SubCategory::query()->where('slug', self::CLASS_SLUG)->value('id');
            self::$classResolved = true;
        }

        return self::$classId;
    }

    /**
     * Whether this class uses the laminate card. False for every class while
     * class-specific fields are switched off — laminate then goes through the
     * same plain product path as everything else.
     */
    public static function isLaminateClass(?int $subCategoryId): bool
    {
        return ClassifierFields::enabled() && $subCategoryId !== null && $subCategoryId === self::classId();
    }

    /**
     * Option value code => id for one dictionary.
     *
     * @return array<string, int>
     */
    public static function optionIds(string $attributeSlug): array
    {
        if (! isset(self::$optionIds[$attributeSlug])) {
            $attribute = Attribute::query()->where('slug', $attributeSlug)->with('options')->first();
            self::$optionIds[$attributeSlug] = $attribute
                ? $attribute->options->pluck('id', 'slug')->map(fn ($id) => (int) $id)->all()
                : [];
        }

        return self::$optionIds[$attributeSlug];
    }

    /**
     * Create the three dictionaries and their options where missing. Keyed on slugs,
     * so it is safe to run any number of times: the setup migration calls it, and so
     * do tests that run after a truncating test has wiped migration-made rows.
     */
    public static function ensureDictionaries(): void
    {
        $now = now();

        foreach (self::DICTIONARIES as $slug => $definition) {
            $attributeId = DB::table('attributes')->where('slug', $slug)->value('id');

            if (! $attributeId) {
                $attributeId = DB::table('attributes')->insertGetId([
                    'name' => json_encode($definition['name'], JSON_UNESCAPED_UNICODE),
                    'slug' => $slug,
                    'field_type' => 'dropdown',
                    'complexity' => 'basic',
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $order = 0;
            foreach ($definition['options'] as $code => $label) {
                $order++;
                $exists = DB::table('attribute_options')
                    ->where('attribute_id', $attributeId)
                    ->where('slug', (string) $code)
                    ->exists();

                if (! $exists) {
                    DB::table('attribute_options')->insert([
                        'attribute_id' => $attributeId,
                        'slug' => (string) $code,
                        'value' => json_encode($label, JSON_UNESCAPED_UNICODE),
                        'sort_order' => $order,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        self::flush();
    }

    /**
     * Forget memoised ids. Tests and the dictionary approval flow create classes and
     * options at runtime, and a stale id would make a valid option look foreign.
     */
    public static function flush(): void
    {
        self::$classId = null;
        self::$classResolved = false;
        self::$optionIds = [];
    }
}
