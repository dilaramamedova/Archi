<?php

use App\Models\Attribute;
use App\Models\SubCategory;
use Database\Seeders\ProductCardSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Catalog filters for the card files that had none (doseme, divar, santexnika,
 * elektrik): only the class↔attribute pivot's `is_filterable` follows each field's
 * `filter` key — nothing else of the cards is re-imported. Runs over every file, so
 * it is also a no-op check for the ones already imported with their filters.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (ProductCardSeeder::FILES as $file) {
            $path = database_path("seeders/data/product_cards/{$file}.php");
            if (! is_file($path)) {
                continue;
            }
            $data = require $path;

            foreach ($data['groups'] ?? [['classes' => $data['classes'] ?? []]] as $group) {
                foreach ($group['classes'] ?? [] as $classSlug => $class) {
                    $classId = SubCategory::query()->where('slug', $classSlug)->value('id');
                    if (! $classId) {
                        continue;
                    }
                    foreach ($class['fields'] as $field) {
                        $attributeId = Attribute::query()->where('slug', $field['slug'])->value('id');
                        if (! $attributeId) {
                            continue;
                        }
                        DB::table('attribute_sub_category')
                            ->where('sub_category_id', $classId)
                            ->where('attribute_id', $attributeId)
                            ->update([
                                'is_filterable' => (bool) ($field['filter'] ?? false),
                                'filter_priority' => ($field['filter'] ?? false) ? (is_string($field['filter']) ? $field['filter'] : 'top') : null,
                            ]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        // Filters are display-only; leaving them on is harmless.
    }
};
