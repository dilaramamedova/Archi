<?php

declare(strict_types=1);

namespace App\Services\ProductCard;

use App\Enums\AttributeComplexity;
use App\Enums\AttributeFieldType;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttributeValue;
use App\Models\SubCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Loads a section's product cards from database/seeders/data/product_cards/<file>.php
 * (format: docs/product-cards.md). Idempotent — run it again after editing the file.
 *
 * What it does, in order: makes sure the target group exists under the section;
 * moves each card class into it (creating classes the site does not have yet),
 * renames it, turns its card on and writes its fields; moves the "plain" classes in
 * without a card; folds merged classes into their target; closes groups left empty.
 * Products follow their class into the group. Values of attributes a card no longer
 * asks for are deleted — they belonged to the retired classifier.
 */
final class CardImporter
{
    /** @var array<string, string> attribute slug → definition fingerprint */
    private array $seen = [];

    /** @var list<string> */
    private array $log = [];

    /** @return list<string> */
    public function importFile(string $name): array
    {
        $path = database_path('seeders/data/product_cards/'.basename($name, '.php').'.php');

        if (! is_file($path)) {
            throw new RuntimeException("Card file not found: {$path}");
        }

        return $this->import(require $path);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    public function import(array $data): array
    {
        $this->seen = [];
        $this->log = [];

        DB::transaction(function () use ($data): void {
            $section = Category::query()->where('slug', $data['section'])->whereNull('parent_id')->first()
                ?? throw new RuntimeException("Section «{$data['section']}» not found");

            $group = $this->group($section, $data['group']);

            $sort = 1;
            foreach ($data['classes'] as $slug => $class) {
                $this->cardClass($group, $slug, $class, $sort++);
            }

            foreach ($data['plain'] ?? [] as $slug) {
                $this->plainClass($group, $slug, $sort++);
            }

            foreach ($data['merge'] ?? [] as $from => $into) {
                $this->merge($from, $into);
            }

            foreach ($data['retire_groups'] ?? [] as $slug) {
                $this->retireGroup($slug);
            }
        });

        return $this->log;
    }

    private function group(Category $section, array $def): Category
    {
        $group = Category::query()->where('slug', $def['slug'])->first() ?? new Category(['slug' => $def['slug']]);
        $group->fill([
            'parent_id' => $section->id,
            'name' => $def['name'],
            'sort_order' => $def['sort_order'] ?? 0,
            'is_active' => true,
        ])->save();

        $this->log[] = "group {$group->slug} #{$group->id}";

        return $group;
    }

    private function cardClass(Category $group, string $slug, array $def, int $sort): void
    {
        $class = $this->placeClass($group, $slug, $def['name'], $sort);

        $class->forceFill([
            'card_enabled' => true,
            'card_settings' => array_filter([
                'code' => $def['code'] ?? null,
                'sale' => $def['sale'] ?? CardSchema::SALE_AREA,
                'quote_when' => $def['quote_when'] ?? null,
            ]),
        ])->save();

        $pivot = [];
        foreach (array_values($def['fields']) as $i => $field) {
            $attribute = $this->attribute($field);
            $pivot[$attribute->id] = [
                'is_required' => (bool) ($field['required'] ?? false),
                'is_filterable' => false,
                'unit' => $field['unit'] ?? null,
                'sort_order' => ($i + 1) * 10,
                // Arrays, not JSON: the pivot model (SubCategoryAttribute) casts them.
                'hint' => $field['hint'] ?? null,
                'presets' => isset($field['presets']) ? array_values($field['presets']) : null,
                'visible_when' => $field['when'] ?? null,
                'rules' => ! empty($field['rules']) ? $field['rules'] : null,
                'maps_to' => $field['maps_to'] ?? null,
            ];
        }

        // sync() keeps exactly the card's attributes on the class — the retired
        // classifier rows go.
        $class->attributes()->sync($pivot);

        // Stored values of attributes the card does not ask for are the old
        // classifier's; nothing renders or validates them any more.
        $removed = ProductAttributeValue::query()
            ->whereIn('product_id', Product::query()->where('sub_category_id', $class->id)->select('id'))
            ->whereNotIn('attribute_id', array_keys($pivot))
            ->delete();

        $this->log[] = sprintf('card %s %s #%d — %d fields%s', $def['code'] ?? '', $slug, $class->id, count($pivot), $removed ? ", {$removed} old values removed" : '');
    }

    private function plainClass(Category $group, string $slug, int $sort): void
    {
        $class = SubCategory::query()->where('slug', $slug)->first();

        if (! $class) {
            $this->log[] = "plain {$slug}: not found, skipped";

            return;
        }

        $this->placeClass($group, $slug, null, $sort);
        $this->log[] = "plain {$slug} #{$class->id}";
    }

    /** Find or create the class, put it (and its products) in the group. */
    private function placeClass(Category $group, string $slug, ?array $name, int $sort): SubCategory
    {
        $class = SubCategory::query()->where('slug', $slug)->first() ?? new SubCategory(['slug' => $slug]);

        $class->fill(array_filter([
            'category_id' => $group->id,
            'name' => $name,
            'sort_order' => $sort,
            'is_active' => true,
        ], fn ($v) => $v !== null))->save();

        Product::query()->where('sub_category_id', $class->id)->update(['category_id' => $group->id]);

        return $class;
    }

    private function merge(string $from, string $into): void
    {
        $source = SubCategory::query()->where('slug', $from)->first();
        $target = SubCategory::query()->where('slug', $into)->first();

        if (! $source || ! $target) {
            $this->log[] = "merge {$from} → {$into}: skipped";

            return;
        }

        $moved = Product::query()->where('sub_category_id', $source->id)
            ->update(['sub_category_id' => $target->id, 'category_id' => $target->category_id]);

        $source->forceFill(['is_active' => false, 'card_enabled' => false])->save();
        $this->log[] = "merge {$from} → {$into}: {$moved} products";
    }

    private function retireGroup(string $slug): void
    {
        $group = Category::query()->where('slug', $slug)->first();

        if (! $group) {
            return;
        }

        $busy = SubCategory::query()->where('category_id', $group->id)->where('is_active', true)->exists()
            || Product::query()->where('category_id', $group->id)->exists()
            || Category::query()->where('parent_id', $group->id)->where('is_active', true)->exists();

        if ($busy) {
            $this->log[] = "group {$slug}: still in use, kept";

            return;
        }

        $group->forceFill(['is_active' => false])->save();
        $this->log[] = "group {$slug}: closed";
    }

    private function attribute(array $field): Attribute
    {
        $type = AttributeFieldType::from($field['type']);
        $options = $field['options'] ?? [];

        // One slug, one definition: a shared attribute may not drift between classes.
        $fingerprint = md5(json_encode([$field['name'], $field['type'], $options], JSON_UNESCAPED_UNICODE));
        if (isset($this->seen[$field['slug']]) && $this->seen[$field['slug']] !== $fingerprint) {
            throw new RuntimeException("Attribute «{$field['slug']}» is defined differently in two classes");
        }
        $this->seen[$field['slug']] = $fingerprint;

        $attribute = Attribute::query()->where('slug', $field['slug'])->first() ?? new Attribute(['slug' => $field['slug']]);
        $attribute->fill([
            'name' => $field['name'],
            'field_type' => $type,
            'complexity' => AttributeComplexity::Basic,
            'is_active' => true,
        ])->save();

        if ($type->hasOptions()) {
            $keep = [];
            foreach (array_values($options) as $i => $option) {
                $slug = $option['slug'] ?? Str::slug($option['value']['az']);
                $keep[] = $slug;

                $row = AttributeOption::query()->where('attribute_id', $attribute->id)->where('slug', $slug)->first()
                    ?? new AttributeOption(['attribute_id' => $attribute->id, 'slug' => $slug]);
                $row->fill([
                    'value' => $option['value'],
                    'visible_when' => $option['when'] ?? null,
                    'is_exclusive' => (bool) ($option['exclusive'] ?? false),
                    'sort_order' => $i + 1,
                ]);
                if (isset($option['prompt'])) {
                    $row->text_prompt = $option['prompt'];
                }
                $row->save();

                // A translatable attribute cannot be set to a plain NULL through the
                // model (it would store {"az": null}) — clear it on the row instead.
                if (! isset($option['prompt'])) {
                    AttributeOption::query()->whereKey($row->id)->update(['text_prompt' => null]);
                }
            }

            // Options dropped from the definition go, together with any answer that
            // pointed at them (cascade).
            AttributeOption::query()->where('attribute_id', $attribute->id)->whereNotIn('slug', $keep)->delete();
        }

        return $attribute;
    }
}
