<?php

declare(strict_types=1);

namespace App\Services\ProductCard;

use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\SubCategory;

/**
 * A class's product card as plain data — the one shape the seller form (JSON), the
 * admin form, the validator and the product page all read. See docs/product-cards.md.
 *
 * Field shape:
 *   id, slug, name, type (dropdown|multiselect|decimal|size|text|textarea), required,
 *   unit, hint, presets (list of numbers, or "L×W" strings for size),
 *   visible_when {attr: <field slug>, in: [<option slug>…]} | null,
 *   rules {lt|lte: <field slug>, only: [<option slug>…], mixed_when: {…},
 *          square_when: {…}, min, max},
 *   maps_to (package_size | package_unit | roll_width | length_mm | width_mm) | null,
 *   options [{id, slug, value, text_prompt, visible_when, exclusive}]
 */
final class CardSchema
{
    public const SALE_AREA = 'area';

    public const SALE_BOARD = 'board';

    /** Price bases a board class may be priced in (TZ D11 «Qiymət bazası»). */
    public const BOARD_BASES = ['piece', 'lm', 'm2'];

    /** Option slug of a "sold unit" answer → products.sale_unit key. */
    public const UNIT_BY_OPTION = [
        'eded' => 'piece',
        'qablasdirma' => 'pack',
        'dest' => 'set',
        'palet' => 'pallet',
    ];

    public static function enabledFor(?int $subCategoryId): bool
    {
        if (! $subCategoryId) {
            return false;
        }

        return (bool) SubCategory::query()->whereKey($subCategoryId)->value('card_enabled');
    }

    /**
     * @return array{sub_category_id: int, name: string, sale: array<string, mixed>, fields: list<array<string, mixed>>}|null
     */
    public static function for(?int $subCategoryId): ?array
    {
        if (! $subCategoryId) {
            return null;
        }

        $subCategory = SubCategory::query()
            ->with([
                'attributes' => fn ($q) => $q->where('attributes.is_active', true),
                'attributes.options' => fn ($q) => $q->orderBy('sort_order'),
            ])
            ->find($subCategoryId);

        if (! $subCategory || ! $subCategory->card_enabled) {
            return null;
        }

        $settings = $subCategory->card_settings ?? [];

        return [
            'sub_category_id' => $subCategory->id,
            'name' => (string) $subCategory->name,
            'sale' => [
                'type' => $settings['sale'] ?? self::SALE_AREA,
                'quote_when' => $settings['quote_when'] ?? null,
            ],
            'fields' => $subCategory->attributes
                ->map(fn (Attribute $a) => self::field($a))
                ->values()
                ->all(),
        ];
    }

    private static function field(Attribute $attribute): array
    {
        $pivot = $attribute->pivot;

        return [
            'id' => $attribute->id,
            'slug' => $attribute->slug,
            'name' => (string) $attribute->name,
            'type' => $attribute->field_type->value,
            'required' => (bool) $pivot->is_required,
            'unit' => $pivot->unit ?: null,
            'hint' => self::localized($pivot->hint) ?? (trim((string) $attribute->tooltip) ?: null),
            'presets' => array_values((array) ($pivot->presets ?? [])),
            'visible_when' => $pivot->visible_when ?: null,
            'rules' => (array) ($pivot->rules ?? []),
            'maps_to' => $pivot->maps_to ?: null,
            'options' => $attribute->field_type->hasOptions()
                ? $attribute->options->map(fn (AttributeOption $o) => [
                    'id' => $o->id,
                    'slug' => $o->slug,
                    'value' => (string) $o->value,
                    'text_prompt' => trim((string) $o->text_prompt) ?: null,
                    'visible_when' => $o->visible_when ?: null,
                    'exclusive' => (bool) $o->is_exclusive,
                ])->values()->all()
                : [],
        ];
    }

    /**
     * "1380×193" (or 1380x193, 1380*193) → ['1380', '193'].
     *
     * @return list<string>
     */
    public static function splitSize(string $pair): array
    {
        return array_values(array_filter(
            array_map(fn ($p) => str_replace(',', '.', trim($p)), preg_split('/\s*[×xX*]\s*/u', trim($pair)) ?: []),
            fn ($p) => $p !== '',
        ));
    }

    /**
     * The distinct lengths and widths of a list of size pairs, ascending — the
     * choices of a mixed-length (min–max) size.
     *
     * @return array{0: list<float>, 1: list<float>}
     */
    public static function sizeParts(array $presets): array
    {
        $lengths = [];
        $widths = [];

        foreach ($presets as $pair) {
            [$l, $w] = array_pad(self::splitSize((string) $pair), 2, null);
            if (is_numeric($l)) {
                $lengths[] = (float) $l;
            }
            if (is_numeric($w)) {
                $widths[] = (float) $w;
            }
        }

        $clean = function (array $list): array {
            $list = array_values(array_unique($list, SORT_REGULAR));
            sort($list);

            return $list;
        };

        return [$clean($lengths), $clean($widths)];
    }

    /** A {az, ru, en} JSON value in the current locale, falling back to az. */
    public static function localized(mixed $value): ?string
    {
        if (! is_array($value)) {
            return is_string($value) && trim($value) !== '' ? $value : null;
        }

        $text = $value[app()->getLocale()] ?? $value['az'] ?? null;

        return is_string($text) && trim($text) !== '' ? $text : null;
    }

    /**
     * Does a {attr, in} condition hold for the given answers (field slug → list of
     * chosen option slugs)? No condition always holds.
     *
     * @param  array<string, list<string>>  $answers
     */
    public static function holds(?array $condition, array $answers): bool
    {
        if (! $condition || empty($condition['attr'])) {
            return true;
        }

        return array_intersect($answers[$condition['attr']] ?? [], (array) ($condition['in'] ?? [])) !== [];
    }
}
