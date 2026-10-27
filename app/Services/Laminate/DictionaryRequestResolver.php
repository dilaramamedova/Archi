<?php

declare(strict_types=1);

namespace App\Services\Laminate;

use App\Enums\DictionaryRequestStatus;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\AuditEvent;
use App\Models\Brand;
use App\Models\DictionaryRequest;
use App\Models\LaminateSpec;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Moderator decisions on proposed dictionary values (TZ «Ламинат» §1, §8).
 *
 * Approving either creates the value or maps the proposal onto one that already
 * exists — the moderator's choice; near-identical names are offered, never merged
 * automatically (§6 «Имена справочников»). Every pending proposal of the same value
 * is resolved with it, so ten sellers who typed "Egger" are not ten tickets.
 */
final class DictionaryRequestResolver
{
    /**
     * @param  ?int  $existingId  map onto this brand / option instead of creating one
     * @param  array<string, string>  $labels  az/ru/en labels for a new option
     */
    public function approve(DictionaryRequest $request, ?int $actorId, ?int $existingId = null, array $labels = []): int
    {
        if ($request->status !== DictionaryRequestStatus::Pending) {
            throw new InvalidArgumentException('Only a pending request can be approved.');
        }

        return DB::transaction(function () use ($request, $actorId, $existingId, $labels): int {
            $resolvedId = $request->entity_type === DictionaryRequest::ENTITY_BRAND
                ? $this->resolveBrand($request, $existingId)
                : $this->resolveOption($request, $existingId, $labels);

            $siblings = DictionaryRequest::query()
                ->pending()
                ->where('field_code', $request->field_code)
                ->where('normalized_label', $request->normalized_label)
                ->get();

            foreach ($siblings as $sibling) {
                $this->applyToProduct($sibling, $resolvedId);

                $sibling->update([
                    'status' => DictionaryRequestStatus::Approved,
                    'resolved_id' => $resolvedId,
                    'resolved_by' => $actorId,
                    'resolved_at' => now(),
                ]);

                AuditEvent::record($actorId, 'dictionary_request', $sibling->id, 'approved', [
                    'resolved_id' => [null, $resolvedId],
                ]);
            }

            return $resolvedId;
        });
    }

    public function reject(DictionaryRequest $request, ?int $actorId, string $reason): void
    {
        if ($request->status !== DictionaryRequestStatus::Pending) {
            throw new InvalidArgumentException('Only a pending request can be rejected.');
        }

        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('A rejection needs a reason.');
        }

        $request->update([
            'status' => DictionaryRequestStatus::Rejected,
            'rejection_reason' => Str::limit($reason, 500, ''),
            'resolved_by' => $actorId,
            'resolved_at' => now(),
        ]);

        AuditEvent::record($actorId, 'dictionary_request', $request->id, 'rejected', ['reason' => [null, $reason]]);
    }

    /**
     * Existing values that look like the proposal — offered to the moderator, never
     * applied on their own.
     *
     * @return array<int, string> id => name
     */
    public function similar(DictionaryRequest $request): array
    {
        $needle = $request->normalized_label;

        if ($request->entity_type === DictionaryRequest::ENTITY_BRAND) {
            return Brand::query()->get()
                ->filter(fn (Brand $b) => $this->near($needle, DictionaryRequest::normalize((string) $b->getTranslation('name', 'az', false))))
                ->mapWithKeys(fn (Brand $b) => [$b->id => (string) $b->getTranslation('name', 'az', false)])
                ->all();
        }

        return $this->attributeFor($request)->options
            ->filter(fn (AttributeOption $o) => $this->near($needle, DictionaryRequest::normalize((string) $o->getTranslation('value', 'az', false))))
            ->mapWithKeys(fn (AttributeOption $o) => [$o->id => (string) $o->getTranslation('value', 'az', false)])
            ->all();
    }

    private function resolveBrand(DictionaryRequest $request, ?int $existingId): int
    {
        if ($existingId !== null) {
            return (int) Brand::query()->findOrFail($existingId)->id;
        }

        $name = $request->proposed_label;
        $slug = Str::slug($name) ?: 'brand-'.substr(md5($request->normalized_label), 0, 8);

        if ($existing = Brand::query()->where('slug', $slug)->first()) {
            return (int) $existing->id;
        }

        // An approved brand is active; catalog filter visibility stays an admin opt-in,
        // as for every other brand.
        return (int) Brand::create([
            'name' => ['az' => $name, 'ru' => $name, 'en' => $name],
            'slug' => $slug,
            'sort_order' => 0,
            'is_active' => true,
            'show_in_filters' => false,
        ])->id;
    }

    private function resolveOption(DictionaryRequest $request, ?int $existingId, array $labels): int
    {
        $attribute = $this->attributeFor($request);

        if ($existingId !== null) {
            $option = $attribute->options->firstWhere('id', $existingId);
            if (! $option) {
                throw new InvalidArgumentException('That option belongs to another dictionary.');
            }

            return (int) $option->id;
        }

        $label = $request->proposed_label;
        $code = Str::slug($label, '_') ?: substr(md5($request->normalized_label), 0, 8);

        if ($existing = $attribute->options->firstWhere('slug', $code)) {
            return (int) $existing->id;
        }

        $option = AttributeOption::create([
            'attribute_id' => $attribute->id,
            'slug' => $code,
            'value' => [
                'az' => $labels['az'] ?? $label,
                'ru' => $labels['ru'] ?? $label,
                'en' => $labels['en'] ?? $label,
            ],
            'sort_order' => (int) $attribute->options->max('sort_order') + 1,
        ]);

        LaminateCatalog::flush();

        return (int) $option->id;
    }

    /**
     * The proposal becomes the product's value — unless the seller has since picked
     * another one themselves, which wins.
     */
    private function applyToProduct(DictionaryRequest $request, int $resolvedId): void
    {
        if (! $request->product_id) {
            return;
        }

        if ($request->entity_type === DictionaryRequest::ENTITY_BRAND) {
            Product::query()->whereKey($request->product_id)->whereNull('brand_id')->update(['brand_id' => $resolvedId]);

            return;
        }

        $column = $request->field_code.'_option_id';
        LaminateSpec::query()->where('product_id', $request->product_id)->whereNull($column)->update([$column => $resolvedId]);
    }

    private function attributeFor(DictionaryRequest $request): Attribute
    {
        $slug = LaminateCatalog::OPTION_FIELDS[$request->field_code] ?? null;

        if ($slug === null) {
            throw new InvalidArgumentException("Unknown dictionary field {$request->field_code}.");
        }

        return Attribute::query()->where('slug', $slug)->with('options')->firstOrFail();
    }

    private function near(string $a, string $b): bool
    {
        if ($a === '' || $b === '') {
            return false;
        }

        return $a === $b || str_contains($a, $b) || str_contains($b, $a) || levenshtein($a, $b) <= 2;
    }
}
