<?php

namespace App\Http\Controllers;

use App\Enums\AttributeFieldType;
use App\Enums\UserStatus;
use App\Models\Product;
use App\Models\SpecialistProfile;
use App\Support\ProductViewCounter;
use Illuminate\Support\Collection;

class ProductController extends Controller
{
    public function show(string $slug)
    {
        $product = Product::where('slug', $slug)
            ->visible()
            ->approved()
            ->with(['images', 'category', 'user.sellerProfile', 'badges', 'subCategory', 'attributeValues.attribute', 'attributeValues.option'])
            ->firstOrFail();

        // Only the reviews the page actually renders. This used to load EVERY
        // approved review into memory to show five of them and to count the
        // stars — fine for a product with four reviews, a few thousand rows per
        // request for a popular one. The star breakdown is now a GROUP BY, and
        // the total comes from the denormalized reviews_count column.
        $product->load(['reviews' => fn ($q) => $q->where('status', 'approved')->with('user')->latest()->limit(5)]);

        $ratingCounts = $product->reviews()
            ->where('status', 'approved')
            ->groupBy('rating')
            ->selectRaw('rating, count(*) as total')
            ->pluck('total', 'rating');

        // Structured classifier specs (EAV attribute values), rendered above the
        // legacy flat specifications rows on the specs tab.
        $attributeSpecs = $this->buildAttributeSpecs($product);

        ProductViewCounter::record($product->id);

        // "Bunları da unutma" — the accessories an admin picked on the product
        // (product_accessory pivot). The older frequently_bought_together JSON
        // column is kept as a fallback for products filled in before that field
        // existed; the section is hidden entirely when both are empty.
        $fbtProducts = $product->accessoryProducts()
            ->visible()
            ->approved()
            ->with(['images', 'category'])
            ->get();

        if ($fbtProducts->isEmpty() && ! empty($product->frequently_bought_together)) {
            $fbtProducts = Product::whereIn('id', $product->frequently_bought_together)
                ->visible()
                ->approved()
                ->with(['images', 'category'])
                ->get();
        }

        $related = Product::visible()->approved()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with(['images', 'category'])
            ->take(4)
            ->get();

        $specialists = SpecialistProfile::where('is_featured', true)
            ->whereHas('user', fn ($q) => $q->where('status', UserStatus::Active))
            ->with(['user', 'specialty'])
            ->take(4)
            ->get();

        return view('pages.product', compact('product', 'related', 'specialists', 'fbtProducts', 'attributeSpecs', 'ratingCounts'));
    }

    /**
     * Rows for the specs tab from the product's structured attribute values:
     * one row per attribute — multiselect options joined, option values and
     * booleans translated, units and ordering taken from the product class's
     * attribute pivot, tooltip carried along for the professional attributes.
     *
     * @return Collection<int, array{name: string, value: string, tooltip: string}>
     */
    private function buildAttributeSpecs(Product $product): Collection
    {
        if ($product->attributeValues->isEmpty()) {
            return collect();
        }

        // Per-class attribute settings (unit + display order) live on the pivot.
        $classAttributes = $product->subCategory
            ? $product->subCategory->attributes()->get()->keyBy('id')
            : collect();

        return $product->attributeValues
            ->filter(fn ($row) => $row->attribute !== null)
            ->groupBy('attribute_id')
            ->map(function ($rows) use ($classAttributes) {
                $attribute = $rows->first()->attribute;
                $pivot = $classAttributes->get($attribute->id)?->pivot;

                $value = match (true) {
                    $attribute->field_type->hasOptions() => $rows->map(fn ($r) => $r->option?->value)->filter()->implode(', '),
                    $attribute->field_type === AttributeFieldType::Boolean => $rows->first()->value_bool !== null
                        ? t('catalog-classifier.specs.'.($rows->first()->value_bool ? 'yes' : 'no'))
                        : '',
                    $attribute->field_type === AttributeFieldType::Range => $this->formatSpecRange($rows->first()),
                    in_array($attribute->field_type, [AttributeFieldType::Numeric, AttributeFieldType::Decimal], true) => $this->trimNumber($rows->first()->value_numeric),
                    default => (string) $rows->first()->value_text,
                };

                if (trim($value) === '') {
                    return null;
                }

                if ($pivot?->unit) {
                    $value .= ' '.$pivot->unit;
                }

                return [
                    'name' => $attribute->name,
                    'value' => $value,
                    'tooltip' => (string) $attribute->tooltip,
                    'sort' => $pivot->sort_order ?? PHP_INT_MAX,
                ];
            })
            ->filter()
            ->sortBy('sort')
            ->values();
    }

    private function formatSpecRange($row): string
    {
        $min = $this->trimNumber($row->value_min);
        $max = $this->trimNumber($row->value_max);

        if ($min === '' && $max === '') {
            return $this->trimNumber($row->value_numeric);
        }

        if ($min !== '' && $max !== '') {
            return $min.' – '.$max;
        }

        return $min !== '' ? $min : $max;
    }

    /**
     * decimal:3 casts render "8.000" — cut the meaningless zeros for display.
     */
    private function trimNumber($value): string
    {
        if ($value === null) {
            return '';
        }

        $s = (string) $value;

        return str_contains($s, '.') ? rtrim(rtrim($s, '0'), '.') : $s;
    }
}
