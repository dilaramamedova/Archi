<?php

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Translation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fixes from the card walk-through of 18.11.2026 (floor, ceiling and wall workbooks),
 * applied to the live rows without a full re-import, so lists Archi edited in the
 * admin stay as they are:
 * - floor and ceiling sizes become open lists («Hazır ölçü və ya rəqəmlər»): a listed
 *   size or «Başqa ölçü» — what `open_size_lists` does on a fresh import;
 * - a custom-size wall product is «Sifarişlə» only (`on_order_when`);
 * - the new card copy (badges, «Qiymət sorğu ilə», limits).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (require database_path('seeders/data/product_card_translations.php') as $group => $items) {
            foreach ($items as $key => $value) {
                Translation::firstOrCreate(['group' => $group, 'key' => $key], ['value' => $value]);
            }
        }
        Translation::clearCache();

        foreach (['dosemeler', 'tavanlar'] as $slug) {
            $section = Category::query()->where('slug', $slug)->whereNull('parent_id')->value('id');
            if (! $section) {
                continue;
            }

            $classes = SubCategory::query()
                ->whereIn('category_id', Category::query()->where('parent_id', $section)->select('id'))
                ->where('card_enabled', true)
                ->pluck('id');

            $rows = DB::table('attribute_sub_category')
                ->join('attributes', 'attributes.id', '=', 'attribute_sub_category.attribute_id')
                ->whereIn('attribute_sub_category.sub_category_id', $classes)
                ->where('attributes.field_type', 'size')
                ->whereNotNull('attribute_sub_category.presets')
                ->get(['attribute_sub_category.id', 'attribute_sub_category.presets', 'attribute_sub_category.rules']);

            foreach ($rows as $row) {
                if ((array) json_decode((string) $row->presets, true) === []) {
                    continue;
                }
                $rules = (array) (json_decode((string) $row->rules, true) ?: []);
                DB::table('attribute_sub_category')->where('id', $row->id)
                    ->update(['rules' => json_encode(['open' => true] + $rules)]);
            }
        }

        // Classes that sell a custom size by the m²: that answer is made to order.
        foreach (SubCategory::query()->where('card_enabled', true)->get() as $class) {
            $settings = (array) $class->card_settings;
            $custom = $settings['unit_when']['m2'] ?? null;
            if ($custom && empty($settings['on_order_when'])) {
                $settings['on_order_when'] = $custom;
                $class->forceFill(['card_settings' => $settings])->save();
            }
        }
    }

    public function down(): void
    {
        // Data refinement; nothing to undo.
    }
};
