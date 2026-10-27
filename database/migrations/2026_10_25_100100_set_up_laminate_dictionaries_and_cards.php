<?php

declare(strict_types=1);

use App\Models\Translation;
use App\Services\Laminate\LaminateCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data side of the laminate card (TZ «Ламинат» §2, §9 «Перенос существующих данных»).
 *
 * 1. Creates the three dictionaries the card points at — usage class (31–34),
 *    abrasion (AC3–AC6) and joint type — as attributes that belong to no class, so
 *    they never render as generic form fields.
 *
 * 2. Takes the five old class attributes off the laminate class. Measured before
 *    writing this: each of them was attached to the laminate class only, and none
 *    carried a single stored value, so no other material's classification and no
 *    product data is touched (TZ §1 «Не изменять классификацию других материалов»).
 *    They are deactivated, not deleted, so down() can put them back.
 *
 * 3. Gives every existing laminate product an empty card. Missing properties stay
 *    unknown / NULL — nothing is guessed from a product name (TZ: «не заменять
 *    пробелы положительными ответами»). Products are not unpublished or deleted.
 *
 * 4. Upserts the `laminate` translation group, and only that group.
 *
 * Re-runnable: every step is keyed on a stable slug or product id.
 */
return new class extends Migration
{
    /** The old laminate class attributes (slug => kept for down()). */
    private const OLD_ATTRIBUTES = [
        'asinma-davamliligi-sinfi',
        'qalinliq-2',
        'surtunmeye-davamliliq-sinfi',
        'formaldehid-emissiyasi-sinfi',
        'format-2',
    ];

    public function up(): void
    {
        $now = now();

        LaminateCatalog::ensureDictionaries();

        $classId = DB::table('sub_categories')->where('slug', LaminateCatalog::CLASS_SLUG)->value('id');

        if ($classId) {
            $oldIds = DB::table('attributes')->whereIn('slug', self::OLD_ATTRIBUTES)->pluck('id');

            DB::table('attribute_sub_category')
                ->where('sub_category_id', $classId)
                ->whereIn('attribute_id', $oldIds)
                ->delete();

            // Deactivate only those no other class uses — today that is all five.
            foreach ($oldIds as $id) {
                if (! DB::table('attribute_sub_category')->where('attribute_id', $id)->exists()) {
                    DB::table('attributes')->where('id', $id)->update(['is_active' => false, 'updated_at' => $now]);
                }
            }

            // The class name was Azerbaijani in every locale.
            DB::table('sub_categories')->where('id', $classId)->update([
                'name' => json_encode(['az' => 'Laminat', 'ru' => 'Ламинат', 'en' => 'Laminate'], JSON_UNESCAPED_UNICODE),
                'updated_at' => $now,
            ]);

            $productIds = DB::table('products')
                ->where('sub_category_id', $classId)
                ->whereNotIn('id', DB::table('laminate_specs')->select('product_id'))
                ->pluck('id');

            foreach ($productIds as $productId) {
                DB::table('laminate_specs')->insert([
                    'product_id' => $productId,
                    'version' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // Pack size feeds the renovation calculator; laminate keeps it as m².
            DB::table('products')
                ->where('sub_category_id', $classId)
                ->whereNull('package_unit')
                ->whereNotNull('package_size')
                ->update(['package_unit' => 'm2']);
        }

        foreach ((require database_path('seeders/data/laminate_translations.php')) as $group => $items) {
            foreach ($items as $key => $value) {
                Translation::updateOrCreate(['group' => $group, 'key' => $key], ['value' => $value]);
            }
        }

        Translation::clearCache();
    }

    /**
     * Puts the old class form back. The dictionaries, the cards and the translations
     * stay — the create migration's down() drops the cards with their tables.
     */
    public function down(): void
    {
        $classId = DB::table('sub_categories')->where('slug', LaminateCatalog::CLASS_SLUG)->value('id');

        if (! $classId) {
            return;
        }

        $now = now();
        $order = 0;

        foreach (DB::table('attributes')->whereIn('slug', self::OLD_ATTRIBUTES)->get(['id', 'slug']) as $attribute) {
            DB::table('attributes')->where('id', $attribute->id)->update(['is_active' => true, 'updated_at' => $now]);

            $attached = DB::table('attribute_sub_category')
                ->where('sub_category_id', $classId)
                ->where('attribute_id', $attribute->id)
                ->exists();

            if (! $attached) {
                DB::table('attribute_sub_category')->insert([
                    'sub_category_id' => $classId,
                    'attribute_id' => $attribute->id,
                    'is_required' => in_array($attribute->slug, ['asinma-davamliligi-sinfi', 'qalinliq-2'], true),
                    'is_filterable' => true,
                    'sort_order' => ++$order,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
};
