<?php

declare(strict_types=1);

namespace App\Services\Laminate;

use App\Models\AuditEvent;
use App\Models\DictionaryRequest;
use App\Models\LaminateSpec;
use App\Models\Product;

/**
 * What a moderator needs to judge a laminate card (TZ «Ламинат» §8 «Что проверяет
 * модератор»): the claims as the buyer will see them, what still blocks
 * publication, cards with the same brand + article, pending dictionary proposals and
 * how recently the card changed.
 */
final class LaminateModeration
{
    /** Fields that identify a variant (TZ §6 «нормализованный ключ варианта»). */
    private const IDENTITY = ['country_code', 'length_mm', 'width_mm', 'thickness_mm', 'thickness_includes_underlay', 'pack_area_m2'];

    public function __construct(private readonly LaminateSpecService $service) {}

    /** @return array<string, mixed>|null */
    public function summary(Product $product): ?array
    {
        $spec = LaminateSpec::query()->where('product_id', $product->id)->first();

        if (! $spec) {
            return null;
        }

        $product->loadMissing('brand');
        $card = (new LaminatePresenter)->present($product->setRelation('laminateSpec', $spec));
        $values = $this->service->formValues($spec, $product);

        return [
            'rows' => $card['rows'] ?? [],
            'documents' => $card['documents'] ?? [],
            'missing' => array_map(fn (string $path) => $this->label($path), $this->service->missingForPublish(
                $values,
                $product->brand_id,
                $product->images()->count(),
            )),
            'duplicates' => $this->duplicates($product, $spec),
            'requests' => DictionaryRequest::query()->where('product_id', $product->id)->pending()->get()
                ->map(fn (DictionaryRequest $r) => [
                    'field' => $this->label($r->field_code === 'brand' ? 'brand_id' : $r->field_code.'_option_id'),
                    'label' => $r->proposed_label,
                ])->all(),
            'version' => $spec->version,
            'last_change' => AuditEvent::query()->where('entity_type', 'laminate_spec')->where('entity_id', $spec->id)->latest('id')->value('created_at'),
        ];
    }

    /**
     * Other cards with the same brand and normalised article. Matching identity
     * fields mean a likely duplicate; differing ones mean either a separate variant or
     * a mistake — the moderator decides, nothing is merged (TZ §6, acceptance 17).
     *
     * @return list<array{product_id: int, name: string, approved: bool, differs: list<string>}>
     */
    private function duplicates(Product $product, LaminateSpec $spec): array
    {
        if ($spec->sku_normalized === null || $product->brand_id === null) {
            return [];
        }

        $mine = $this->identity($spec);

        return LaminateSpec::query()
            ->where('sku_normalized', $spec->sku_normalized)
            ->where('id', '!=', $spec->id)
            ->whereHas('product', fn ($q) => $q->where('brand_id', $product->brand_id))
            ->with('product')
            ->limit(10)
            ->get()
            ->map(function (LaminateSpec $other) use ($mine): array {
                $theirs = $this->identity($other);
                $differs = array_keys(array_filter($mine, fn ($v, $k) => $v !== $theirs[$k], ARRAY_FILTER_USE_BOTH));

                return [
                    'product_id' => $other->product_id,
                    'name' => (string) $other->product->name,
                    'approved' => (bool) $other->product->is_approved,
                    'differs' => array_map(fn (string $k) => $this->label($k), $differs),
                ];
            })
            ->all();
    }

    /** @return array<string, ?string> */
    private function identity(LaminateSpec $spec): array
    {
        $out = [];

        foreach (self::IDENTITY as $key) {
            $raw = $spec->getAttribute($key);
            $out[$key] = $raw instanceof \BackedEnum ? $raw->value : LaminateDecimal::trim($raw === null ? null : (string) $raw);
        }

        return $out;
    }

    private function label(string $path): string
    {
        $key = str_starts_with($path, 'laminate.') ? substr($path, 9) : $path;
        $full = 'laminate.field.'.$key;
        $text = t($full);

        return is_string($text) && $text !== $full ? $text : $key;
    }
}
