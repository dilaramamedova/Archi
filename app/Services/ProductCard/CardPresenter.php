<?php

declare(strict_types=1);

namespace App\Services\ProductCard;

use App\Models\Product;
use App\Support\IsoCountries;

/**
 * What the buyer sees of a card product (TZ «Alıcı kartında»): brand, country, the
 * card's parameters in card order, and the price / stock of what is actually sold —
 * the pack price and the m² behind the stock are computed from what the seller
 * entered, never stored twice.
 */
final class CardPresenter
{
    /**
     * @return array{rows: list<array{name: string, value: string}>, sale: array<string, mixed>}|null
     */
    public static function present(Product $product): ?array
    {
        $schema = CardSchema::for($product->sub_category_id);

        if (! $schema) {
            return null;
        }

        $product->loadMissing(['brand', 'attributeValues']);
        $values = $product->attributeValues->groupBy('attribute_id');

        $rows = [];

        $brand = $product->brand?->name ?? ($product->is_unbranded ? CardText::get('unbranded', 'Brendsiz') : null);
        if ($brand) {
            $rows[] = ['name' => CardText::get('page.brand', 'Brend'), 'value' => (string) $brand];
        }
        if ($product->origin_country) {
            $rows[] = ['name' => CardText::get('country', 'İstehsal ölkəsi'), 'value' => IsoCountries::name($product->origin_country)];
        }
        if ($product->collection) {
            $rows[] = ['name' => CardText::get('collection', 'Kolleksiya'), 'value' => $product->collection];
        }
        if ($product->sku) {
            $rows[] = ['name' => CardText::get('page.sku', 'Artikul'), 'value' => $product->sku];
        }

        foreach ($schema['fields'] as $field) {
            $value = self::value($field, $values->get($field['id']));
            if ($value !== null) {
                $rows[] = ['name' => $field['name'], 'value' => $value];
            }
        }

        return ['rows' => $rows, 'sale' => self::sale($product)];
    }

    private static function value(array $field, $rows): ?string
    {
        if (! $rows || $rows->isEmpty()) {
            return null;
        }

        $unit = $field['unit'] ? ' '.$field['unit'] : '';
        $num = fn ($v) => str_replace('.', ',', (string) CardProduct::trim($v));

        if (in_array($field['type'], ['dropdown', 'multiselect'], true)) {
            $options = collect($field['options'])->keyBy('id');

            $parts = $rows->map(function ($row) use ($options) {
                $option = $options->get($row->attribute_option_id);
                if (! $option) {
                    return null;
                }
                // «Digər» shows what the seller wrote; «Bəli» with a source shows both.
                if ($row->value_text) {
                    return str_starts_with($option['slug'], 'diger') ? $row->value_text : $option['value'].' ('.$row->value_text.')';
                }

                return $option['value'];
            })->filter();

            return $parts->isEmpty() ? null : $parts->implode(', ');
        }

        $row = $rows->first();

        return match ($field['type']) {
            'size' => $row->value_max !== null
                ? $num($row->value_min).'–'.$num($row->value_max).' × '.$num($row->value_numeric).$unit
                : $num($row->value_min).' × '.$num($row->value_numeric).$unit,
            'decimal', 'numeric' => $num($row->value_numeric).$unit,
            default => $row->value_text ?: null,
        };
    }

    /**
     * @return array{quote: bool, price_unit: string, sold_unit: ?string, sold_price: ?float, sold_old_price: ?float, sold_area: ?float, stock_area: ?float, availability: string, lead_time_days: ?int}
     */
    public static function sale(Product $product): array
    {
        $factor = $product->sale_factor !== null ? (float) $product->sale_factor : null;
        $soldUnit = $product->sale_unit;
        $area = $product->unit === 'm2' && $soldUnit && $soldUnit !== 'lm' ? $factor : null;

        return [
            'quote' => (bool) $product->quote_only,
            'price_unit' => (string) $product->unit,
            'sold_unit' => $soldUnit,
            // Only when one sold unit differs from the priced one.
            'sold_price' => $factor !== null && abs($factor - 1.0) > 0.000001 ? $product->salePrice() : null,
            'sold_old_price' => $factor !== null && $product->old_price && (float) $product->old_price > (float) $product->price
                ? $product->salePrice((float) $product->old_price) : null,
            'sold_area' => $area,
            'stock_area' => $area !== null && (int) $product->stock > 0 ? round($area * (int) $product->stock, 2) : null,
            'availability' => (string) ($product->availability ?: Product::IN_STOCK),
            'lead_time_days' => $product->lead_time_days,
        ];
    }
}
