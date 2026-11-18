<?php

declare(strict_types=1);

namespace App\Services\ProductCard;

use App\Models\Product;
use App\Models\ProductAttributeValue;
use App\Support\IsoCountries;

/**
 * The part of a card every class shares (brand, country, collection, availability,
 * price basis) and the product columns a card decides: how the product is priced,
 * what one sold unit is and what it costs. Used by the seller form and the admin.
 */
final class CardProduct
{
    /**
     * @param  array<string, mixed>  $schema  CardSchema::for()
     * @param  array<string, mixed>  $data  product-level input: brand_id, new_brand, is_unbranded,
     *                                      origin_country, collection, availability, lead_time_days,
     *                                      price_basis, price, old_price, stock
     * @param  array<int|string, mixed>  $card  the card input (see CardInput)
     * @return array{errors: array<string, string>, missing: list<string>, rows: list<array<string, mixed>>, columns: array<string, mixed>}
     */
    public static function check(array $schema, array $data, array $card, bool $strict, string $cardPrefix = 'card'): array
    {
        $result = (new CardInput($schema, $card, $strict, $cardPrefix))->run();
        $errors = $result['errors'];
        $missing = $result['missing'];

        $quote = $schema['sale']['quote_when']
            && CardSchema::holds($schema['sale']['quote_when'], $result['answers']);

        // Brand: one from the list, a typed new one ("Brendi tapmadım"), or «Brendsiz».
        $hasBrand = ! empty($data['brand_id']) || trim((string) ($data['new_brand'] ?? '')) !== '' || ! empty($data['is_unbranded']);
        if (! $hasBrand) {
            $missing[] = CardText::get('brand', 'Brend');
            if ($strict) {
                $errors['brand_id'] = CardText::get('brand_required', 'Brendi seçin, yazın və ya «Brendsiz» qeyd edin');
            }
        }

        $country = strtoupper(trim((string) ($data['origin_country'] ?? '')));
        if ($country !== '' && ! IsoCountries::isValid($country)) {
            $errors['origin_country'] = CardText::get('country_invalid', 'İstehsal ölkəsi siyahıdan seçilməlidir');
        } elseif ($country === '' && ($schema['country_required'] ?? true)) {
            $missing[] = CardText::get('country', 'İstehsal ölkəsi');
            if ($strict) {
                $errors['origin_country'] = CardText::get('country_required', 'İstehsal ölkəsini seçin');
            }
        }

        $availability = (string) ($data['availability'] ?? '');
        if (! in_array($availability, Product::AVAILABILITIES, true)) {
            $errors['availability'] = CardText::get('availability_required', 'Mövcudluğu seçin');
        } elseif ($availability !== Product::ON_ORDER && ($schema['sale']['on_order_when'] ?? null)
            && CardSchema::holds($schema['sale']['on_order_when'], $result['answers'])) {
            // «Fərdi istehsalda mövcudluq “Sifarişlə”» — a custom size is never in stock.
            $errors['availability'] = CardText::get('availability_custom_on_order','Fərdi ölçüdə mövcudluq «Sifarişlə» olmalıdır');
        }

        $basis = null;
        if ($schema['sale']['type'] === CardSchema::SALE_BOARD) {
            $basis = (string) ($data['price_basis'] ?? '');
            if (! in_array($basis, CardSchema::BOARD_BASES, true)) {
                $errors['price_basis'] = CardText::get('basis_required', 'Qiymət bazasını seçin');
                $basis = null;
            }
        } elseif ($schema['sale']['type'] === CardSchema::SALE_UNIT) {
            // The seller's sales unit travels in the same field as the board basis.
            $basis = (string) ($data['price_basis'] ?? '');
            $unitWhen = (array) ($schema['sale']['unit_when'] ?? []);
            if ($quote && ! in_array($basis, $schema['sale']['units'], true)) {
                // Priced on request: the form hides the unit; record the one the
                // answers point to (a custom size → m²).
                $basis = collect($schema['sale']['units'])
                    ->first(fn ($u) => ! isset($unitWhen[$u]) || CardSchema::holds($unitWhen[$u], $result['answers']));
            } elseif (! in_array($basis, $schema['sale']['units'], true)) {
                $errors['price_basis'] = CardText::get('sale_unit_required', 'Satış vahidini seçin');
                $basis = null;
            } elseif (($cond = $schema['sale']['unit_when'][$basis] ?? null) && ! CardSchema::holds($cond, $result['answers'])) {
                // The card's own answers decide the unit (a ready-made piece vs a custom size).
                $errors['price_basis'] = CardText::get('sale_unit_mismatch', 'Bu satış vahidi seçilmiş formata uyğun deyil');
                $basis = null;
            }
        }

        $price = is_numeric($data['price'] ?? null) ? (float) $data['price'] : null;
        if (! $quote && ($price === null || $price <= 0)) {
            $missing[] = CardText::get('price', 'Qiymət');
            if ($strict) {
                $errors['price'] = CardText::get('price_required', 'Qiyməti yazın');
            }
        }

        $old = is_numeric($data['old_price'] ?? null) ? (float) $data['old_price'] : null;
        if ($old !== null && $old > 0 && $price !== null && $old <= $price) {
            $errors['old_price'] = CardText::get('old_price', 'Əvvəlki qiymət cari qiymətdən böyük olmalıdır');
        }

        $lead = $data['lead_time_days'] ?? null;
        if ($availability === Product::ON_ORDER && $lead !== null && $lead !== ''
            && (! ctype_digit((string) $lead) || (int) $lead < 1 || (int) $lead > 365)) {
            $errors['lead_time_days'] = CardText::get('lead_time_invalid', 'Hazırlanma müddəti 1–365 gün olmalıdır');
        }

        return [
            'errors' => $errors,
            'missing' => array_values(array_unique($missing)),
            'rows' => $result['rows'],
            'columns' => self::columns($schema, $result['mapped'], $data, $quote, $basis, $country, $availability),
        ];
    }

    /**
     * @param  array<string, mixed>  $mapped
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private static function columns(array $schema, array $mapped, array $data, bool $quote, ?string $basis, string $country, string $availability): array
    {
        $columns = [
            'origin_country' => $country !== '' && IsoCountries::isValid($country) ? $country : null,
            'is_unbranded' => ! empty($data['is_unbranded']),
            'collection' => trim((string) ($data['collection'] ?? '')) ?: null,
            'availability' => in_array($availability, Product::AVAILABILITIES, true) ? $availability : Product::IN_STOCK,
            'lead_time_days' => $availability === Product::ON_ORDER && ctype_digit((string) ($data['lead_time_days'] ?? ''))
                ? (int) $data['lead_time_days'] : null,
            'quote_only' => $quote,
            'sale_factor' => null,
            'sale_unit' => null,
            'package_size' => null,
            'package_unit' => null,
        ];

        if ($schema['sale']['type'] === CardSchema::SALE_BOARD) {
            $length = isset($mapped['length_mm']) ? $mapped['length_mm'] / 1000 : null;
            $width = isset($mapped['width_mm']) ? $mapped['width_mm'] / 1000 : null;

            $columns['unit'] = $basis ?? 'piece';
            $columns['sale_unit'] = 'piece';
            $columns['sale_factor'] = match ($basis) {
                'lm' => $length,
                'm2' => $length !== null && $width !== null ? round($length * $width, 6) : null,
                default => 1.0,
            };
            // The calculator counts boards when it knows what one covers.
            if ($length !== null && $width !== null) {
                $columns['package_size'] = round($length * $width, 4);
                $columns['package_unit'] = self::unitName('piece');
            }

            return $columns;
        }

        if ($schema['sale']['type'] === CardSchema::SALE_UNIT) {
            // Priced and stocked in the seller's unit. A known coverage per unit
            // (Örtmə sahəsi) is what lets the page add an AZN/m² figure and the
            // calculator count units.
            $unit = $basis ?? 'piece';
            $coverage = $mapped['package_size'] ?? null;
            $columns['unit'] = $unit;
            $columns['sale_unit'] = $unit;
            $columns['sale_factor'] = 1.0;
            if ($coverage !== null && $coverage > 0) {
                $columns['package_size'] = $coverage;
                $columns['package_unit'] = self::unitName($unit);
            }

            return $columns;
        }

        // Area: priced per m².
        $columns['unit'] = 'm2';

        if ($quote) {
            return $columns;
        }

        $packArea = $mapped['package_size'] ?? null;
        $rollWidth = $mapped['roll_width'] ?? null;
        $rollLength = $mapped['roll_length'] ?? null;

        if ($rollWidth !== null && $rollWidth > 0 && $rollLength !== null && $rollLength > 0) {
            // A whole roll: its price is the m² price × width × length.
            $area = round($rollWidth * $rollLength, 4);
            $columns['sale_factor'] = $area;
            $columns['sale_unit'] = 'roll';
            $columns['package_size'] = $area;
            $columns['package_unit'] = self::unitName('roll');
        } elseif ($packArea !== null && $packArea > 0) {
            $unit = $mapped['package_unit'] ?? 'pack';
            $columns['sale_factor'] = $packArea;
            $columns['sale_unit'] = $unit;
            $columns['package_size'] = $packArea;
            $columns['package_unit'] = self::unitName($unit);
        } elseif ($rollWidth !== null && $rollWidth > 0 && $schema['sale']['cut_unit'] !== 'm2') {
            // A roll is cut to length: one running metre is roll width × 1 m.
            $columns['sale_factor'] = $rollWidth;
            $columns['sale_unit'] = 'lm';
        }
        // cut_unit m2 (stretch-ceiling film): the cut is sold by the m² itself.

        return $columns;
    }

    /** "8.0000" → "8", "2.1310" → "2.131"; null stays null. */
    public static function trim(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $text = (string) $value;

        return str_contains($text, '.') ? rtrim(rtrim($text, '0'), '.') : $text;
    }

    /** Azerbaijani display name of a sold unit (the calculator prints it as the pack name). */
    public static function unitName(string $unit): string
    {
        $label = t('business-product-edit.pricing.units.'.$unit, [], 'az');

        return is_string($label) && ! str_contains($label, 'pricing.units') ? mb_strtolower($label) : $unit;
    }

    /**
     * Replace the product's card values. Values of attributes outside the card (a
     * previous class, the retired classifier) go too: a card product carries exactly
     * what its card asks.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    public static function saveValues(Product $product, array $rows): void
    {
        ProductAttributeValue::query()->where('product_id', $product->id)->delete();

        foreach ($rows as $row) {
            ProductAttributeValue::create(['product_id' => $product->id] + $row);
        }
    }

    /**
     * The stored values in the card input shape, for refilling a form.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function formState(Product $product, array $schema): array
    {
        $types = collect($schema['fields'])->keyBy('id');
        $state = [];

        foreach ($product->attributeValues()->get()->groupBy('attribute_id') as $attributeId => $rows) {
            $field = $types->get((int) $attributeId);
            if (! $field) {
                continue;
            }

            $text = $rows->pluck('value_text')->filter()->first();
            $number = fn ($v) => self::trim($v);

            $state[(int) $attributeId] = match ($field['type']) {
                'dropdown' => ['option' => $rows->first()->attribute_option_id, 'text' => $text],
                'multiselect' => ['options' => $rows->pluck('attribute_option_id')->filter()->values()->all(), 'text' => $text],
                'size' => [
                    'length' => $number($rows->first()->value_min),
                    'length_max' => $number($rows->first()->value_max),
                    'width' => $number($rows->first()->value_numeric),
                    // The same size as one listed choice ("1380×193").
                    'pair' => $rows->first()->value_max === null
                        ? $number($rows->first()->value_min).'×'.$number($rows->first()->value_numeric)
                        : null,
                ],
                'decimal', 'numeric' => ['value' => $number($rows->first()->value_numeric)],
                default => ['value' => $rows->first()->value_text],
            };
        }

        return $state;
    }
}
