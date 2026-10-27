<?php

declare(strict_types=1);

namespace App\Services\Laminate;

use App\Enums\DictionaryRequestStatus;
use App\Enums\Laminate\DocumentKind;
use App\Enums\Laminate\HeatingType;
use App\Enums\Laminate\Tri;
use App\Enums\Laminate\WarrantyScope;
use App\Enums\Laminate\WarrantyTerm;
use App\Enums\Laminate\WaterClaim;
use App\Models\AuditEvent;
use App\Models\Brand;
use App\Models\DictionaryRequest;
use App\Models\LaminateSpec;
use App\Models\LaminateSpecDocument;
use App\Models\LaminateSpecHeatingType;
use App\Models\LaminateSpecWarranty;
use App\Models\Product;
use App\Support\IsoCountries;

/**
 * Turns a laminate payload into a validated, normalised plan and persists it
 * (TZ «Ламинат» §3, §4, §6).
 *
 * Every rule lives here, on the server, whatever the form does (§6: «Все проверки
 * выполняются на сервере независимо от интерфейса»):
 *
 *   - exact decimals, comma or point, never rounded silently;
 *   - dictionary answers only by an existing option id of the RIGHT dictionary;
 *   - yes / no / unknown answers that default to unknown, never to "no";
 *   - dependent answers that only exist while their parent is "yes", cleared by the
 *     server when it is not, and a 422 DEPENDENCY_CONFLICT when one request tries to
 *     switch the parent off and set a dependant at the same time;
 *   - unknown keys rejected, so a typo cannot be stored unnoticed.
 *
 * The payload is PATCH-shaped (§7): an absent key keeps the stored value, an empty
 * one clears it.
 */
final class LaminateSpecService
{
    /** Keys a laminate payload may carry. Anything else is UNKNOWN_FIELD. */
    public const KEYS = [
        'model_name', 'collection_name', 'manufacturer_sku', 'country_code',
        'usage_class_option_id', 'usage_class_proposed',
        'abrasion_option_id', 'abrasion_proposed',
        'thickness_mm', 'length_mm', 'width_mm',
        'water_protection', 'water_claim', 'water_hours',
        'floor_heating', 'heating_types_known', 'heating_types',
        'max_surface_temp_c', 'thermal_resistance_m2k_w',
        'integrated_underlay', 'underlay_thickness_mm', 'thickness_includes_underlay',
        'connection_option_id', 'connection_proposed',
        'pack_area_m2',
        'warranty', 'warranties',
        'expected_version',
    ];

    /** Decimal columns: key => [scale, integer digits]. Mirrors the migration. */
    private const DECIMALS = [
        'thickness_mm' => [4, 8],
        'length_mm' => [4, 8],
        'width_mm' => [4, 8],
        'pack_area_m2' => [4, 8],
        'underlay_thickness_mm' => [4, 8],
        'max_surface_temp_c' => [2, 4],
        'thermal_resistance_m2k_w' => [6, 6],
    ];

    private const TRI_FIELDS = [
        'water_protection', 'floor_heating', 'integrated_underlay', 'thickness_includes_underlay', 'warranty',
    ];

    private const TEXT_FIELDS = ['model_name' => 200, 'collection_name' => 150, 'manufacturer_sku' => 100];

    /** Fields the publication rule requires (TZ §3 «Обязательность при публикации»). */
    public const PUBLISH_REQUIRED = ['model_name', 'manufacturer_sku', 'thickness_mm', 'length_mm', 'width_mm', 'pack_area_m2'];

    private const MAX_INT = 4294967295;

    private const MAX_YEARS = 65535;

    private const CONDITIONS_MAX = 2000;

    private const PROPOSAL_MAX = 100;

    /** @var array<string, string> */
    private array $errors = [];

    /** @var array<string, string> */
    private array $codes = [];

    // ------------------------------------------------------------------ validate

    /**
     * @param  array<string, mixed>  $input  the `laminate` part of the request
     * @return array{
     *     values: array<string, mixed>,
     *     heating_types: list<string>,
     *     warranties: list<array{scope: string, term_kind: string, years: ?int, conditions_text: ?string}>,
     *     proposals: array<string, string>,
     *     detach_kinds: list<string>
     * }
     *
     * @throws LaminateRequestException
     */
    public function validate(array $input, ?LaminateSpec $current): array
    {
        $this->errors = [];
        $this->codes = [];

        foreach (array_keys($input) as $key) {
            if (! in_array($key, self::KEYS, true)) {
                $this->fail((string) $key, 'UNKNOWN_FIELD');
            }
        }

        $this->checkVersion($input, $current);

        $state = $this->stateOf($current);
        $provided = fn (string $key): bool => array_key_exists($key, $input);

        // ── Plain fields ─────────────────────────────────────────────────
        foreach (self::TEXT_FIELDS as $key => $max) {
            if ($provided($key)) {
                $state['values'][$key] = $this->text($key, $input[$key], $max);
            }
        }

        if ($provided('country_code')) {
            $state['values']['country_code'] = $this->country($input['country_code']);
        }

        foreach (self::DECIMALS as $key => [$scale, $digits]) {
            if ($provided($key)) {
                $state['values'][$key] = $this->decimal($key, $input[$key], $scale, $digits);
            }
        }

        foreach (self::TRI_FIELDS as $key) {
            if ($provided($key)) {
                $state['values'][$key] = $this->tri($key, $input[$key]);
            }
        }

        if ($provided('water_claim')) {
            $claim = $input['water_claim'] === null || $input['water_claim'] === ''
                ? WaterClaim::Unknown
                : (is_string($input['water_claim']) ? WaterClaim::tryFrom($input['water_claim']) : null);
            $claim === null
                ? $this->fail('water_claim', 'INVALID_OPTION')
                : $state['values']['water_claim'] = $claim->value;
        }

        if ($provided('water_hours')) {
            $state['values']['water_hours'] = $this->positiveInt('water_hours', $input['water_hours'], self::MAX_INT);
        }

        if ($provided('heating_types_known')) {
            $known = filter_var($input['heating_types_known'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $known === null
                ? $this->fail('heating_types_known', 'INVALID_OPTION')
                : $state['values']['heating_types_known'] = $known;
        }

        if ($provided('heating_types')) {
            $state['heating_types'] = $this->heatingTypes($input['heating_types']);
        }

        if ($provided('warranties')) {
            $state['warranties'] = $this->warranties($input['warranties']);
        }

        $proposals = $this->options($input, $state);

        // ── Dependencies (TZ §4 «Правило выключения зависимых полей») ────────
        $detach = $this->applyDependencies($input, $state);

        // ── Cross-field consistency ─────────────────────────────────────
        $this->checkConsistency($state);

        if ($this->errors !== []) {
            throw new LaminateRequestException(
                $this->prefixed($this->errors),
                $this->prefixed($this->codes),
            );
        }

        $sku = $state['values']['manufacturer_sku'];
        $state['values']['sku_normalized'] = $sku === null ? null : self::normalizeSku($sku);

        return [
            'values' => $state['values'],
            'heating_types' => $state['heating_types'],
            'warranties' => $state['warranties'],
            'proposals' => $proposals,
            'detach_kinds' => $detach,
        ];
    }

    /**
     * Search form of a manufacturer article (TZ §6 «Артикул»): outer spaces trimmed,
     * Unicode and case folded. Hyphens and inner symbols are part of the article and
     * stay.
     */
    public static function normalizeSku(string $sku): string
    {
        $sku = trim($sku);

        if (class_exists(\Normalizer::class)) {
            $sku = \Normalizer::normalize($sku, \Normalizer::FORM_KC) ?: $sku;
        }

        return mb_strtolower($sku);
    }

    // ------------------------------------------------------------------ publish

    /**
     * What still blocks publication (TZ §3 «Обязательность при публикации»): an
     * approved brand, model name, manufacturer article, thickness, length, width,
     * pack area and at least one photo. A draft may lack all of them.
     *
     * @param  array<string, mixed>  $values  plan values
     * @return list<string> field paths, in form order
     */
    public function missingForPublish(array $values, ?int $brandId, int $imageCount): array
    {
        $missing = [];

        $brandApproved = $brandId !== null && Brand::query()->whereKey($brandId)->where('is_active', true)->exists();
        if (! $brandApproved) {
            $missing[] = 'brand_id';
        }

        foreach (self::PUBLISH_REQUIRED as $key) {
            if ($values[$key] === null || $values[$key] === '') {
                $missing[] = 'laminate.'.$key;
            }
        }

        if ($imageCount < 1) {
            $missing[] = 'images';
        }

        return $missing;
    }

    /**
     * @param  list<string>  $missing
     */
    public function publishException(array $missing): LaminateRequestException
    {
        $messages = [];
        $codes = [];

        foreach ($missing as $path) {
            $field = str_starts_with($path, 'laminate.') ? substr($path, 9) : $path;
            $messages[$path] = $this->message('PUBLISH_REQUIRED', $field);
            $codes[$path] = 'PUBLISH_REQUIRED';
        }

        return new LaminateRequestException($messages, $codes, 422, ['missing_fields' => $missing]);
    }

    // ------------------------------------------------------------------ persist

    /**
     * Write the plan. Must run inside the caller's transaction, so the product, the
     * card, its sets and the audit row land together or not at all (acceptance 12:
     * «частичных записей нет»).
     */
    public function save(Product $product, array $plan, ?int $actorId): LaminateSpec
    {
        $spec = $product->laminateSpec()->first();
        $before = $spec ? $this->snapshot($spec->load(['heatingTypes', 'warranties'])) : null;

        $spec ??= new LaminateSpec(['product_id' => $product->id, 'created_by' => $actorId, 'version' => 0]);
        $spec->fill($plan['values']);
        $spec->updated_by = $actorId;
        $spec->version = (int) $spec->version + 1;
        $spec->save();

        // Sets are rewritten whole — the composite key makes a duplicate impossible.
        LaminateSpecHeatingType::query()->where('laminate_spec_id', $spec->id)->delete();
        foreach ($plan['heating_types'] as $code) {
            LaminateSpecHeatingType::query()->insert(['laminate_spec_id' => $spec->id, 'heating_type_code' => $code]);
        }

        LaminateSpecWarranty::query()->where('laminate_spec_id', $spec->id)->delete();
        foreach ($plan['warranties'] as $row) {
            LaminateSpecWarranty::create(['laminate_spec_id' => $spec->id] + $row);
        }

        // A withdrawn "yes" takes its conditions document off the live card. The
        // file and the row stay, so the history still has them (TZ §4).
        $activeScopes = array_column($plan['warranties'], 'scope');
        LaminateSpecDocument::query()
            ->where('laminate_spec_id', $spec->id)
            ->whereNull('detached_at')
            ->where(function ($q) use ($plan, $activeScopes): void {
                $q->whereIn('kind', $plan['detach_kinds'])
                    ->orWhere(fn ($w) => $w->where('kind', DocumentKind::WarrantyConditions->value)
                        ->whereNotIn('warranty_scope', $activeScopes === [] ? ['__none__'] : $activeScopes));
            })
            ->update(['detached_at' => now()]);

        $this->syncProposals($product, $spec, $plan['proposals'], $actorId);

        $after = $this->snapshot($spec->fresh(['heatingTypes', 'warranties']));
        $diff = $this->diff($before ?? [], $after);

        if ($before === null || $diff !== []) {
            AuditEvent::record($actorId, 'laminate_spec', $spec->id, $before === null ? 'created' : 'updated', $diff);
        }

        return $spec;
    }

    /**
     * One pending request per product and field. A new label replaces the pending
     * one; an empty label (the seller picked a listed value or "not stated")
     * withdraws it. Resolved requests are history and are never touched.
     *
     * @param  array<string, string>  $proposals  field => label ('' = withdraw)
     */
    private function syncProposals(Product $product, LaminateSpec $spec, array $proposals, ?int $actorId): void
    {
        foreach ($proposals as $field => $label) {
            $pending = DictionaryRequest::query()
                ->where('product_id', $product->id)
                ->where('field_code', $field)
                ->pending()
                ->first();

            if ($label === '') {
                $pending?->delete();

                continue;
            }

            $attributes = [
                'entity_type' => DictionaryRequest::ENTITY_LAMINATE_OPTION,
                'proposed_label' => $label,
                'normalized_label' => DictionaryRequest::normalize($label),
                'proposed_by' => $actorId,
            ];

            $pending
                ? $pending->update($attributes)
                : DictionaryRequest::create($attributes + [
                    'field_code' => $field,
                    'product_id' => $product->id,
                    'status' => DictionaryRequestStatus::Pending,
                ]);
        }
    }

    /**
     * File (or withdraw) the brand request of a laminate product. A typed brand that
     * matches an existing one by its normalised name is not a request at all — the
     * existing, approved brand is used.
     */
    public function syncBrandRequest(Product $product, ?string $label, ?int $actorId): void
    {
        $pending = DictionaryRequest::query()
            ->where('product_id', $product->id)
            ->where('field_code', 'brand')
            ->pending()
            ->first();

        if ($label === null || trim($label) === '') {
            $pending?->delete();

            return;
        }

        $attributes = [
            'entity_type' => DictionaryRequest::ENTITY_BRAND,
            'proposed_label' => trim($label),
            'normalized_label' => DictionaryRequest::normalize($label),
            'proposed_by' => $actorId,
        ];

        $pending
            ? $pending->update($attributes)
            : DictionaryRequest::create($attributes + [
                'field_code' => 'brand',
                'product_id' => $product->id,
                'status' => DictionaryRequestStatus::Pending,
            ]);
    }

    // ------------------------------------------------------------------ read

    /**
     * Form values for a stored card, in the payload's own shape (decimals trimmed,
     * enums as codes), plus the pending proposals so the seller sees what is still
     * waiting for a moderator.
     *
     * @return array<string, mixed>
     */
    public function formValues(?LaminateSpec $spec, ?Product $product = null): array
    {
        $state = $this->stateOf($spec);
        $values = $state['values'];

        foreach (array_keys(self::DECIMALS) as $key) {
            $values[$key] = LaminateDecimal::trim($values[$key]);
        }

        $values['heating_types'] = $state['heating_types'];
        $values['warranties'] = $state['warranties'];
        $values['version'] = $spec?->version;

        $pending = $product
            ? DictionaryRequest::query()->where('product_id', $product->id)->pending()->pluck('proposed_label', 'field_code')->all()
            : [];

        foreach (array_keys(LaminateCatalog::OPTION_FIELDS) as $field) {
            $values[$field.'_proposed'] = $pending[$field] ?? null;
        }
        $values['brand_proposed'] = $pending['brand'] ?? null;

        $values['documents'] = $spec
            ? $spec->documents()->get()->map(fn (LaminateSpecDocument $d) => [
                'id' => $d->id,
                'kind' => $d->kind->value,
                'name' => $d->original_name ?: $d->external_url,
                'url' => route('laminate.documents.show', $d),
                'is_link' => $d->isLink(),
                'warranty_scope' => $d->warranty_scope?->value,
            ])->values()->all()
            : [];

        return $values;
    }

    // ------------------------------------------------------------------ internals

    /**
     * The card as a mutable plan: stored values, or the TZ defaults for a new card
     * (every yes/no/unknown answer starts on unknown, every number on NULL).
     *
     * @return array{values: array<string, mixed>, heating_types: list<string>, warranties: list<array<string, mixed>>}
     */
    private function stateOf(?LaminateSpec $spec): array
    {
        $values = [
            'model_name' => null, 'collection_name' => null, 'manufacturer_sku' => null,
            'country_code' => null,
            'usage_class_option_id' => null, 'abrasion_option_id' => null, 'connection_option_id' => null,
            'thickness_mm' => null, 'length_mm' => null, 'width_mm' => null,
            'water_protection' => Tri::Unknown->value, 'water_claim' => WaterClaim::Unknown->value, 'water_hours' => null,
            'floor_heating' => Tri::Unknown->value, 'heating_types_known' => false,
            'max_surface_temp_c' => null, 'thermal_resistance_m2k_w' => null,
            'integrated_underlay' => Tri::Unknown->value, 'underlay_thickness_mm' => null,
            'thickness_includes_underlay' => Tri::Unknown->value,
            'pack_area_m2' => null,
            'warranty' => Tri::Unknown->value,
        ];

        if (! $spec) {
            return ['values' => $values, 'heating_types' => [], 'warranties' => []];
        }

        foreach (array_keys($values) as $key) {
            $raw = $spec->getAttribute($key);
            $values[$key] = $raw instanceof \BackedEnum ? $raw->value : $raw;
        }

        foreach (array_keys(self::DECIMALS) as $key) {
            $values[$key] = LaminateDecimal::trim($values[$key] === null ? null : (string) $values[$key]);
        }

        $values['heating_types_known'] = (bool) $values['heating_types_known'];

        $heating = $spec->relationLoaded('heatingTypes') ? $spec->heatingTypes : $spec->heatingTypes()->get();
        $warranties = $spec->relationLoaded('warranties') ? $spec->warranties : $spec->warranties()->get();

        return [
            'values' => $values,
            'heating_types' => $heating->pluck('heating_type_code')->sort()->values()->all(),
            'warranties' => $warranties->map(fn (LaminateSpecWarranty $w) => [
                'scope' => $w->scope->value,
                'term_kind' => $w->term_kind->value,
                'years' => $w->years,
                'conditions_text' => $w->conditions_text,
            ])->values()->all(),
        ];
    }

    private function checkVersion(array $input, ?LaminateSpec $current): void
    {
        if (! $current || ! array_key_exists('expected_version', $input) || $input['expected_version'] === null || $input['expected_version'] === '') {
            return;
        }

        if ((string) $input['expected_version'] !== (string) $current->version) {
            // Somebody saved this card since the form was opened. Their change is not
            // overwritten (TZ §6 «Конкурирующая правка»).
            throw new LaminateRequestException(
                ['laminate.expected_version' => $this->message('VERSION_CONFLICT', 'expected_version')],
                ['laminate.expected_version' => 'VERSION_CONFLICT'],
                409,
                ['current_version' => $current->version],
            );
        }
    }

    private function text(string $key, mixed $raw, int $max): ?string
    {
        if ($raw === null) {
            return null;
        }

        if (! is_scalar($raw)) {
            $this->fail($key, 'INVALID_OPTION');

            return null;
        }

        // Names: outer and repeated spaces removed. The article keeps its inner
        // spacing — it is copied from a label, not typed as prose.
        $value = $key === 'manufacturer_sku'
            ? trim((string) $raw)
            : (preg_replace('/\s+/u', ' ', trim((string) $raw)) ?? trim((string) $raw));

        if ($value === '') {
            return null;
        }

        if (mb_strlen($value) > $max) {
            $this->fail($key, 'TEXT_TOO_LONG');

            return null;
        }

        return $value;
    }

    private function country(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            // «Не указана» → NULL. The brand's country is never substituted (TZ §3).
            return null;
        }

        $code = is_string($raw) ? strtoupper(trim($raw)) : '';

        if (! IsoCountries::isValid($code)) {
            $this->fail('country_code', 'INVALID_COUNTRY');

            return null;
        }

        return $code;
    }

    private function decimal(string $key, mixed $raw, int $scale, int $digits): ?string
    {
        [$value, $error] = LaminateDecimal::parse($raw, $scale, $digits);

        if ($error !== null) {
            $this->fail($key, $error, ['scale' => $scale]);
        }

        return $value;
    }

    private function tri(string $key, mixed $raw): string
    {
        $tri = Tri::fromInput($raw);

        if ($tri === null) {
            $this->fail($key, 'INVALID_OPTION');

            return Tri::Unknown->value;
        }

        return $tri->value;
    }

    private function positiveInt(string $key, mixed $raw, int $max): ?int
    {
        if ($raw === null || (is_string($raw) && trim($raw) === '')) {
            return null;
        }

        $s = is_int($raw) ? (string) $raw : (is_string($raw) ? trim($raw) : '');

        if (! preg_match('/^\d+$/D', $s)) {
            $this->fail($key, 'NUMBER_FORMAT');

            return null;
        }

        $s = ltrim($s, '0');

        if ($s === '') {
            $this->fail($key, 'POSITIVE_REQUIRED');

            return null;
        }

        if (strlen($s) > strlen((string) $max) || (int) $s > $max) {
            $this->fail($key, 'OUT_OF_RANGE');

            return null;
        }

        return (int) $s;
    }

    /** @return list<string> */
    private function heatingTypes(mixed $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        if (! is_array($raw)) {
            $this->fail('heating_types', 'INVALID_OPTION');

            return [];
        }

        $codes = [];

        foreach ($raw as $code) {
            if ($code === null || $code === '') {
                continue; // the form's empty "unticked" marker
            }

            if (! is_string($code) || HeatingType::tryFrom($code) === null) {
                $this->fail('heating_types', 'INVALID_OPTION');

                return [];
            }

            $codes[$code] = $code; // ticking twice is not two approvals
        }

        ksort($codes);

        return array_values($codes);
    }

    /**
     * @return list<array{scope: string, term_kind: string, years: ?int, conditions_text: ?string}>
     */
    private function warranties(mixed $raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        if (! is_array($raw)) {
            $this->fail('warranties', 'INVALID_OPTION');

            return [];
        }

        $out = [];
        $seen = [];

        foreach (array_values($raw) as $i => $entry) {
            $path = "warranties.$i";

            if (! is_array($entry)) {
                $this->fail($path, 'INVALID_OPTION');

                continue;
            }

            foreach (array_keys($entry) as $k) {
                if (! in_array($k, ['scope', 'term_kind', 'years', 'conditions_text'], true)) {
                    $this->fail("$path.$k", 'UNKNOWN_FIELD');
                }
            }

            $scope = is_string($entry['scope'] ?? null) ? WarrantyScope::tryFrom($entry['scope']) : null;
            if ($scope === null) {
                $this->fail("$path.scope", 'INVALID_OPTION');

                continue;
            }

            if (isset($seen[$scope->value])) {
                $this->fail("$path.scope", 'DUPLICATE_SCOPE');

                continue;
            }
            $seen[$scope->value] = true;

            $termRaw = $entry['term_kind'] ?? null;
            $term = $termRaw === null || $termRaw === ''
                ? WarrantyTerm::Unknown
                : (is_string($termRaw) ? WarrantyTerm::tryFrom($termRaw) : null);

            if ($term === null) {
                $this->fail("$path.term_kind", 'INVALID_OPTION');

                continue;
            }

            $yearsRaw = $entry['years'] ?? null;
            $years = null;

            if ($term === WarrantyTerm::Years) {
                $years = $this->positiveInt("$path.years", $yearsRaw, self::MAX_YEARS);
                if ($years === null && ! isset($this->errors["$path.years"])) {
                    $this->fail("$path.years", 'WARRANTY_YEARS_REQUIRED');
                }
            } elseif ($yearsRaw !== null && $yearsRaw !== '') {
                // A lifetime or unknown term has no number. 0 and 999 are exactly what
                // the TZ forbids lifetime to be stored as.
                $this->fail("$path.years", 'WARRANTY_YEARS_FORBIDDEN');
            }

            $conditions = $entry['conditions_text'] ?? null;
            if ($conditions !== null && ! is_string($conditions)) {
                $this->fail("$path.conditions_text", 'INVALID_OPTION');
                $conditions = null;
            }
            $conditions = $conditions === null ? null : (trim($conditions) === '' ? null : trim($conditions));
            if ($conditions !== null && mb_strlen($conditions) > self::CONDITIONS_MAX) {
                $this->fail("$path.conditions_text", 'TEXT_TOO_LONG');
            }

            $out[] = [
                'scope' => $scope->value,
                'term_kind' => $term->value,
                'years' => $years,
                'conditions_text' => $conditions,
            ];
        }

        usort($out, fn ($a, $b) => strcmp($a['scope'], $b['scope']));

        return $out;
    }

    /**
     * Dictionary answers: an option id of the right dictionary, or a proposal for a
     * new value — never both, never an AC id in the usage-class field (TZ §6).
     *
     * @return array<string, string> field => proposal label, '' = withdraw
     */
    private function options(array $input, array &$state): array
    {
        $proposals = [];

        foreach (LaminateCatalog::OPTION_FIELDS as $field => $attributeSlug) {
            $idKey = $field.'_option_id';
            $proposalKey = $field.'_proposed';
            $hasId = array_key_exists($idKey, $input);
            $hasProposal = array_key_exists($proposalKey, $input);

            $id = null;
            if ($hasId && $input[$idKey] !== null && $input[$idKey] !== '') {
                $raw = $input[$idKey];
                $valid = LaminateCatalog::optionIds($attributeSlug);

                if ((is_int($raw) || (is_string($raw) && ctype_digit($raw))) && in_array((int) $raw, $valid, true)) {
                    $id = (int) $raw;
                } else {
                    $this->fail($idKey, 'INVALID_OPTION');
                }
            }

            $label = null;
            if ($hasProposal) {
                $label = $this->text($proposalKey, $input[$proposalKey], self::PROPOSAL_MAX);
            }

            if ($id !== null && $label !== null) {
                $this->fail($proposalKey, 'OPTION_AND_PROPOSAL');

                continue;
            }

            if ($hasId) {
                $state['values'][$idKey] = $id;
            }

            if ($label !== null) {
                // The value is pending: the card's own reference stays empty until a
                // moderator approves it (TZ §2 «dictionary_requests»).
                $state['values'][$idKey] = null;
                $proposals[$field] = $label;
            } elseif ($hasProposal || $id !== null) {
                $proposals[$field] = '';
            }
        }

        return $proposals;
    }

    /**
     * TZ §4: a dependant exists only while its parent is "yes". When the parent is
     * no or unknown the server clears it — numbers to NULL, enums to unknown, sets to
     * empty — whatever the client sent. Sending the parent's "no" together with a
     * non-empty dependant is a contradiction, answered with DEPENDENCY_CONFLICT
     * rather than a guess about which half was meant.
     *
     * @return list<string> document kinds to take off the live card
     */
    private function applyDependencies(array $input, array &$state): array
    {
        $v = &$state['values'];
        $detach = [];

        $nonEmpty = [
            'water_claim' => fn ($x) => $x !== null && $x !== '' && $x !== WaterClaim::Unknown->value,
            'water_hours' => fn ($x) => $x !== null && $x !== '',
            'heating_types_known' => fn ($x) => filter_var($x, FILTER_VALIDATE_BOOLEAN) === true,
            'heating_types' => fn ($x) => is_array($x) && array_filter($x, fn ($c) => $c !== null && $c !== '') !== [],
            'max_surface_temp_c' => fn ($x) => $x !== null && $x !== '',
            'thermal_resistance_m2k_w' => fn ($x) => $x !== null && $x !== '',
            'underlay_thickness_mm' => fn ($x) => $x !== null && $x !== '',
            'thickness_includes_underlay' => fn ($x) => $x !== null && $x !== '' && $x !== Tri::Unknown->value,
            'warranties' => fn ($x) => is_array($x) && $x !== [],
        ];

        $groups = [
            'water_protection' => ['water_claim', 'water_hours'],
            'floor_heating' => ['heating_types_known', 'heating_types', 'max_surface_temp_c', 'thermal_resistance_m2k_w'],
            'integrated_underlay' => ['underlay_thickness_mm', 'thickness_includes_underlay'],
            'warranty' => ['warranties'],
        ];

        foreach ($groups as $parent => $dependants) {
            if ($v[$parent] === Tri::Yes->value) {
                continue;
            }

            foreach ($dependants as $dep) {
                if (array_key_exists($dep, $input) && $nonEmpty[$dep]($input[$dep])) {
                    $this->fail($dep, 'DEPENDENCY_CONFLICT', ['parent' => $parent]);
                }
            }

            switch ($parent) {
                case 'water_protection':
                    $v['water_claim'] = WaterClaim::Unknown->value;
                    $v['water_hours'] = null;
                    $detach[] = DocumentKind::WaterConditions->value;
                    break;

                case 'floor_heating':
                    $v['heating_types_known'] = false;
                    $v['max_surface_temp_c'] = null;
                    $v['thermal_resistance_m2k_w'] = null;
                    $state['heating_types'] = [];
                    break;

                case 'integrated_underlay':
                    $v['underlay_thickness_mm'] = null;
                    $v['thickness_includes_underlay'] = Tri::Unknown->value;
                    break;

                case 'warranty':
                    $state['warranties'] = [];
                    $detach[] = DocumentKind::WarrantyConditions->value;
                    break;
            }
        }

        // Inside floor heating: "types not stated" and a ticked type cannot both be
        // true (acceptance 10 — «water и unknown одновременно»).
        if ($v['floor_heating'] === Tri::Yes->value && $v['heating_types_known'] === false) {
            if (array_key_exists('heating_types', $input) && $nonEmpty['heating_types']($input['heating_types'])) {
                $this->fail('heating_types', 'HEATING_TYPES_UNKNOWN');
            }
            $state['heating_types'] = [];
        }

        return $detach;
    }

    private function checkConsistency(array $state): void
    {
        $v = $state['values'];

        if ($v['floor_heating'] === Tri::Yes->value && $v['heating_types_known'] === true && $state['heating_types'] === []
            && ! isset($this->errors['heating_types'])) {
            $this->fail('heating_types', 'HEATING_TYPE_REQUIRED');
        }

        if ($v['warranty'] === Tri::Yes->value && $state['warranties'] === [] && ! $this->hasErrorUnder('warranties')) {
            $this->fail('warranties', 'WARRANTY_SCOPE_REQUIRED');
        }

        // TZ §4: if the stated thickness includes the underlay and both are known,
        // the panel must be thicker than the underlay alone. Nothing is summed or
        // derived when either is unknown.
        if ($v['integrated_underlay'] === Tri::Yes->value
            && $v['thickness_includes_underlay'] === Tri::Yes->value
            && $v['thickness_mm'] !== null && $v['underlay_thickness_mm'] !== null
            && ! LaminateDecimal::greaterThan($v['thickness_mm'], $v['underlay_thickness_mm'])) {
            $this->fail('underlay_thickness_mm', 'UNDERLAY_THICKER_THAN_PANEL');
        }
    }

    private function hasErrorUnder(string $prefix): bool
    {
        foreach (array_keys($this->errors) as $key) {
            if ($key === $prefix || str_starts_with($key, $prefix.'.')) {
                return true;
            }
        }

        return false;
    }

    private function fail(string $field, string $code, array $replace = []): void
    {
        // First error per field wins — one clear message beats a stack of them.
        if (isset($this->errors[$field])) {
            return;
        }

        $this->errors[$field] = $this->message($code, $field, $replace);
        $this->codes[$field] = $code;
    }

    private function message(string $code, string $field, array $replace = []): string
    {
        $base = preg_replace('/^warranties\.\d+\./', 'warranty_', $field) ?? $field;
        $labelKey = 'laminate.field.'.$base;
        $label = t($labelKey);
        $label = is_string($label) && $label !== $labelKey ? $label : $base;

        if (isset($replace['parent'])) {
            $parentKey = 'laminate.field.'.$replace['parent'];
            $parent = t($parentKey);
            $replace['parent'] = is_string($parent) && $parent !== $parentKey ? $parent : $replace['parent'];
        }

        $key = 'laminate.errors.'.$code;
        $text = t($key, ['field' => $label] + $replace);

        return is_string($text) && $text !== $key ? $text : $code.': '.$label;
    }

    /**
     * @param  array<string, string>  $map
     * @return array<string, string>
     */
    private function prefixed(array $map): array
    {
        $out = [];
        foreach ($map as $k => $v) {
            $out['laminate.'.$k] = $v;
        }

        return $out;
    }

    /** @return array<string, mixed> */
    private function snapshot(LaminateSpec $spec): array
    {
        $state = $this->stateOf($spec);

        return $state['values'] + [
            'heating_types' => $state['heating_types'],
            'warranties' => $state['warranties'],
        ];
    }

    /**
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    private function diff(array $before, array $after): array
    {
        $out = [];

        foreach ($after as $key => $value) {
            $old = $before[$key] ?? null;
            if ($old !== $value) {
                $out[$key] = [$old, $value];
            }
        }

        return $out;
    }
}
