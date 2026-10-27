<?php

declare(strict_types=1);

namespace App\Services\Laminate;

use App\Enums\Laminate\DocumentKind;
use App\Enums\Laminate\HeatingType;
use App\Enums\Laminate\Tri;
use App\Enums\Laminate\WarrantyScope;
use App\Enums\Laminate\WarrantyTerm;
use App\Enums\Laminate\WaterClaim;
use App\Models\Attribute;
use App\Support\IsoCountries;

/**
 * The laminate form definition the seller cabinet renders from (TZ §7
 * «GET /laminate/form-schema»): the twelve fields, dictionary values, quick picks,
 * units, show conditions and the publication rule.
 *
 * Dictionaries go out as {id, code, label}: the id is what the form posts back,
 * the code is stable across environments, and the label is display only — never a
 * key (TZ §6 «Списки»).
 */
final class LaminateFormSchema
{
    public const VERSION = 1;

    /**
     * Generic product-form blocks that do not belong on a laminate card. Colour,
     * free-form rows and "where to use" are excluded by the TZ (§1); size, country,
     * material and pack size are asked by the laminate block itself, in a stricter
     * form, and showing both would store the same property twice.
     */
    public const HIDDEN_GENERIC = ['color', 'dimensions', 'material', 'country', 'package', 'free_attributes', 'applications'];

    public function build(): array
    {
        return [
            'schema_version' => self::VERSION,
            'class_slug' => LaminateCatalog::CLASS_SLUG,
            'hide_generic' => self::HIDDEN_GENERIC,
            'price_units' => array_map(fn (string $u) => [
                'value' => $u,
                'label' => $this->t('ui.price_unit_'.$u),
            ], LaminateCatalog::PRICE_UNITS),
            'required_for_publish' => [
                'brand_id',
                ...array_map(fn ($k) => 'laminate.'.$k, LaminateSpecService::PUBLISH_REQUIRED),
                'images',
            ],
            'countries' => array_map(
                fn (string $code, string $name) => ['code' => $code, 'name' => $name],
                array_keys($countries = IsoCountries::options()),
                $countries,
            ),
            'dictionaries' => $this->dictionaries(),
            'picks' => [
                'thickness_mm' => LaminateCatalog::THICKNESS_PICKS,
                'sizes' => LaminateCatalog::SIZE_PICKS,
                'water_hours' => LaminateCatalog::WATER_HOURS_PICKS,
                'max_surface_temp_c' => LaminateCatalog::SURFACE_TEMP_PICKS,
                'underlay_thickness_mm' => LaminateCatalog::UNDERLAY_PICKS,
                'warranty_years' => LaminateCatalog::WARRANTY_YEAR_PICKS,
            ],
            // Fractional digits the server accepts; more is an error, never a rounding.
            'precision' => [
                'thickness_mm' => 4, 'length_mm' => 4, 'width_mm' => 4, 'pack_area_m2' => 4,
                'underlay_thickness_mm' => 4, 'max_surface_temp_c' => 2, 'thermal_resistance_m2k_w' => 6,
            ],
            'units' => [
                'mm' => $this->t('unit.mm'),
                'm2' => $this->t('unit.m2'),
                'celsius' => $this->t('unit.celsius'),
                'resistance' => $this->t('unit.resistance'),
                'hours' => $this->t('ui.hours'),
                'years' => $this->t('ui.years'),
            ],
            // Each parent's dependants are shown only while it is "yes", and are not
            // posted at all otherwise — the server clears them and would answer a
            // posted dependant with DEPENDENCY_CONFLICT.
            'dependencies' => [
                'water_protection' => ['water_claim', 'water_hours', 'documents:water_conditions'],
                'floor_heating' => ['heating_types_known', 'heating_types', 'max_surface_temp_c', 'thermal_resistance_m2k_w'],
                'integrated_underlay' => ['underlay_thickness_mm', 'thickness_includes_underlay'],
                'warranty' => ['warranties', 'documents:warranty_conditions'],
            ],
            'enums' => [
                'tri' => $this->enum(Tri::cases(), 'tri'),
                'warranty_tri' => $this->enum(Tri::cases(), 'warranty_tri'),
                'water_claim' => $this->enum(WaterClaim::cases(), 'water_claim'),
                'heating_types' => $this->enum(HeatingType::cases(), 'heating_type'),
                'warranty_scopes' => $this->enum(WarrantyScope::cases(), 'warranty_scope'),
                'warranty_terms' => $this->enum(WarrantyTerm::cases(), 'warranty_term'),
                'document_kinds' => $this->enum(DocumentKind::cases(), 'document_kind'),
            ],
            'documents' => [
                'max' => LaminateDocuments::MAX_DOCUMENTS,
                'max_kb' => LaminateDocuments::MAX_KB,
                'accept' => '.pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
            't' => $this->strings(),
        ];
    }

    /** @return array<string, list<array{id: int, code: string, label: string}>> */
    private function dictionaries(): array
    {
        $out = [];

        foreach (LaminateCatalog::OPTION_FIELDS as $field => $slug) {
            $attribute = Attribute::query()->where('slug', $slug)->with('options')->first();
            $out[$field] = $attribute
                ? $attribute->options->map(fn ($o) => [
                    'id' => $o->id,
                    'code' => $o->slug,
                    'label' => (string) $o->value,
                ])->values()->all()
                : [];
        }

        return $out;
    }

    /**
     * @param  list<\BackedEnum>  $cases
     * @return list<array{value: string, label: string}>
     */
    private function enum(array $cases, string $group): array
    {
        return array_map(fn (\BackedEnum $c) => [
            'value' => $c->value,
            'label' => $this->t($group.'.'.$c->value),
        ], $cases);
    }

    /**
     * Every `laminate.field.*`, `laminate.ui.*` and `laminate.errors.*` string, so the
     * form needs no hardcoded copy in any language.
     *
     * @return array<string, string>
     */
    private function strings(): array
    {
        $out = [];
        $keys = array_keys((require database_path('seeders/data/laminate_translations.php'))['laminate']);

        foreach ($keys as $key) {
            if (str_starts_with($key, 'field.') || str_starts_with($key, 'ui.') || str_starts_with($key, 'errors.')) {
                $out[$key] = $this->t($key);
            }
        }

        return $out;
    }

    private function t(string $key): string
    {
        $full = 'laminate.'.$key;
        $value = t($full);

        return is_string($value) && $value !== $full ? $value : $key;
    }
}
