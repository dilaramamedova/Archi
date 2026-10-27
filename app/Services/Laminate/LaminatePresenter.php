<?php

declare(strict_types=1);

namespace App\Services\Laminate;

use App\Enums\Laminate\DocumentKind;
use App\Enums\Laminate\Tri;
use App\Enums\Laminate\WarrantyScope;
use App\Enums\Laminate\WarrantyTerm;
use App\Enums\Laminate\WaterClaim;
use App\Models\LaminateSpec;
use App\Models\LaminateSpecDocument;
use App\Models\Product;
use App\Support\IsoCountries;

/**
 * The buyer's view of a laminate card (TZ «Ламинат» §9 «Отображение»).
 *
 * The short block hides what is unknown rather than printing "not stated" twelve
 * times; a zero never stands in for a missing value; every number carries its unit;
 * thickness says whether it includes the underlay; heating lists only confirmed
 * systems; warranty shows scope and term, and an absent commercial record reads as
 * "not stated for commercial use", never as a refusal.
 */
final class LaminatePresenter
{
    /**
     * @return array{
     *     rows: list<array{key: string, label: string, value: string, note: ?string}>,
     *     documents: list<array{kind: string, label: string, url: string, is_link: bool}>,
     *     installation_url: ?string,
     *     prices: ?array{per_m2: string, per_pack: string, source: string}
     * }|null
     */
    public function present(Product $product): ?array
    {
        $spec = $product->relationLoaded('laminateSpec') ? $product->laminateSpec : $product->laminateSpec()->first();

        if (! $spec) {
            return null;
        }

        $spec->loadMissing(['usageClass', 'abrasion', 'connection', 'heatingTypes', 'warranties', 'documents']);

        $rows = [];
        $add = function (string $key, ?string $value, ?string $note = null) use (&$rows): void {
            if ($value !== null && $value !== '') {
                $rows[] = ['key' => $key, 'label' => $this->t('field.'.$key), 'value' => $value, 'note' => $note];
            }
        };

        $mm = $this->t('unit.mm');

        $add('brand_id', $product->brand ? (string) $product->brand->name : null);
        $add('model_name', $spec->model_name);
        $add('collection_name', $spec->collection_name);
        $add('manufacturer_sku', $spec->manufacturer_sku);
        $add('country_code', $spec->country_code ? IsoCountries::name($spec->country_code) : null);
        $add('usage_class_option_id', $spec->usageClass ? (string) $spec->usageClass->value : null);
        $add('abrasion_option_id', $spec->abrasion ? (string) $spec->abrasion->value : null);

        $thickness = LaminateDecimal::trim($spec->thickness_mm);
        $add('thickness_mm', $thickness ? $thickness.' '.$mm : null, $this->underlayNote($spec));

        $length = LaminateDecimal::trim($spec->length_mm);
        $width = LaminateDecimal::trim($spec->width_mm);
        $add('size', $length && $width ? $length.' × '.$width.' '.$mm : null);

        $add('water_protection', $this->water($spec));
        $add('floor_heating', $this->heating($spec));
        $add('integrated_underlay', $this->underlay($spec));
        $add('connection_option_id', $spec->connection ? (string) $spec->connection->value : null);

        $pack = LaminateDecimal::trim($spec->pack_area_m2);
        $add('pack_area_m2', $pack ? $pack.' '.$this->t('unit.m2') : null);

        foreach ($this->warrantyRows($spec) as [$label, $value]) {
            $rows[] = ['key' => 'warranty', 'label' => $label, 'value' => $value, 'note' => null];
        }

        $documents = $spec->documents->map(fn (LaminateSpecDocument $d) => [
            'kind' => $d->kind->value,
            'label' => $this->t('document_kind.'.$d->kind->value),
            'url' => route('laminate.documents.show', $d),
            'is_link' => $d->isLink(),
        ])->values()->all();

        $installation = $spec->documents->first(fn (LaminateSpecDocument $d) => $d->kind === DocumentKind::Installation);

        return [
            'rows' => $rows,
            'documents' => $documents,
            'installation_url' => $installation ? route('laminate.documents.show', $installation) : null,
            'prices' => $this->prices($product, $spec),
        ];
    }

    private function underlayNote(LaminateSpec $spec): ?string
    {
        if ($spec->integrated_underlay !== Tri::Yes) {
            return null;
        }

        return match ($spec->thickness_includes_underlay) {
            Tri::Yes => $this->t('card.thickness_incl_underlay'),
            Tri::No => $this->t('card.thickness_excl_underlay'),
            default => $this->t('card.thickness_underlay_unknown'),
        };
    }

    private function water(LaminateSpec $spec): ?string
    {
        return match ($spec->water_protection) {
            Tri::Yes => $this->join($this->t('tri.yes'), [
                $spec->water_claim !== WaterClaim::Unknown ? $this->t('water_claim.'.$spec->water_claim->value) : null,
                $spec->water_hours ? $spec->water_hours.' '.$this->t('ui.hours') : null,
            ]),
            Tri::No => $this->t('tri.no'),
            default => null,
        };
    }

    private function heating(LaminateSpec $spec): ?string
    {
        if ($spec->floor_heating === Tri::No) {
            return $this->t('tri.no');
        }

        if ($spec->floor_heating !== Tri::Yes) {
            return null;
        }

        $types = $spec->heating_types_known
            ? implode(', ', array_map(fn ($type) => $this->t('heating_type.'.$type->value), $spec->heatingTypeList()))
            : $this->t('card.heating_systems_unknown');

        $temp = LaminateDecimal::trim($spec->max_surface_temp_c);
        $resistance = LaminateDecimal::trim($spec->thermal_resistance_m2k_w);

        return $this->join($this->t('tri.yes'), [
            $types,
            $temp ? $this->t('card.up_to').' '.$temp.' '.$this->t('unit.celsius') : null,
            $resistance ? 'R '.$resistance.' '.$this->t('unit.resistance') : null,
        ]);
    }

    private function underlay(LaminateSpec $spec): ?string
    {
        return match ($spec->integrated_underlay) {
            Tri::Yes => $this->join($this->t('tri.yes'), [
                ($u = LaminateDecimal::trim($spec->underlay_thickness_mm)) ? $u.' '.$this->t('unit.mm') : null,
            ]),
            Tri::No => $this->t('tri.no'),
            default => null,
        };
    }

    /** @return list<array{0: string, 1: string}> */
    private function warrantyRows(LaminateSpec $spec): array
    {
        if ($spec->warranty === Tri::No) {
            return [[$this->t('field.warranty'), $this->t('warranty_tri.no')]];
        }

        if ($spec->warranty !== Tri::Yes) {
            return [];
        }

        $rows = [];
        $scopes = [];

        // Residential first, then commercial — the enum's order, not the alphabet's.
        $order = array_flip(array_column(WarrantyScope::cases(), 'value'));
        $warranties = $spec->warranties->sortBy(fn ($w) => $order[$w->scope->value] ?? 99);

        foreach ($warranties as $w) {
            $scopes[] = $w->scope;
            $term = match ($w->term_kind) {
                WarrantyTerm::Years => $w->years.' '.$this->t('ui.years'),
                WarrantyTerm::Lifetime => $this->t('warranty_term.lifetime'),
                WarrantyTerm::Unknown => $this->t('warranty_term.unknown'),
            };
            $rows[] = [$this->t('field.warranty').' — '.$this->t('warranty_scope.'.$w->scope->value), $term];
        }

        // No commercial record is "not stated for commercial use" — said in exactly
        // those words, so it cannot be read as "no warranty for commercial use".
        if (! in_array(WarrantyScope::Commercial, $scopes, true)) {
            $rows[] = [$this->t('field.warranty'), $this->t('card.commercial_not_stated')];
        }

        return $rows;
    }

    /**
     * TZ §5: one source price, the other derived for display — per pack = price ×
     * pack area, per m² = price ÷ pack area. The stored price is never rewritten,
     * so a corrected pack area moves only the derived figure (acceptance 19).
     *
     * @return array{per_m2: string, per_pack: string, source: string}|null
     */
    private function prices(Product $product, LaminateSpec $spec): ?array
    {
        $area = $spec->pack_area_m2 !== null ? (string) $spec->pack_area_m2 : null;

        if ($area === null || bccomp($area, '0', 4) <= 0 || $product->price === null) {
            return null;
        }

        $price = (string) $product->price;

        return match ($product->unit) {
            'm2' => [
                'per_m2' => LaminateDecimal::round($price, 2),
                'per_pack' => LaminateDecimal::round(bcmul($price, $area, 8), 2),
                'source' => 'm2',
            ],
            'pack' => [
                'per_m2' => LaminateDecimal::round(bcdiv($price, $area, 8), 2),
                'per_pack' => LaminateDecimal::round($price, 2),
                'source' => 'pack',
            ],
            default => null,
        };
    }

    /** "Bəli — Suya davamlı, 72 saat" */
    private function join(string $head, array $parts): string
    {
        $parts = array_values(array_filter($parts, fn ($p) => $p !== null && $p !== ''));

        return $parts === [] ? $head : $head.' — '.implode(', ', $parts);
    }

    private function t(string $key): string
    {
        $full = 'laminate.'.$key;
        $value = t($full);

        return is_string($value) && $value !== $full ? $value : $key;
    }
}
