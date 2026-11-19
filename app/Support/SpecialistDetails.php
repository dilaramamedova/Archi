<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\SpecialistCategory;
use App\Models\SpecialistProfile;
use App\Models\SpecialistSpecialty;
use Illuminate\Validation\Rule;

/**
 * The kind-specific half of a specialist profile, stored in specialist_profiles.details
 * (json). Only the two "own kinds" have one — a Briqadir describes his crew, a Makler
 * his agency and market; every other specialist keeps `details = null`.
 *
 * One class owns the shape so the cabinet validation, the admin form, the public
 * profile and the directory card can never disagree about which keys exist. Whatever
 * is submitted, `normalize()` keeps only the keys of the profile's own kind — switching
 * a foreman to a tiler drops the crew rather than leaving it orphaned in the json.
 *
 * Foreman: crew_size (int), crew_trades (master-category specialty ids),
 *          crew_members (list of {name, specialty_id, experience_years}),
 *          takes_full_projects (bool).
 * Realtor: agency_name, deal_types[], property_types[], service_areas, license_no.
 *
 * See docs/specialist-kinds.md.
 */
final class SpecialistDetails
{
    public const MAX_CREW_SIZE = 200;

    public const MAX_CREW_MEMBERS = 30;

    /** alqı-satqı / kirayə / günlük kirayə */
    public const DEAL_TYPES = ['sale', 'rent', 'daily_rent'];

    /** mənzil / həyət evi / ofis / obyekt / torpaq */
    public const PROPERTY_TYPES = ['apartment', 'house', 'office', 'commercial', 'land'];

    public const FOREMAN_KEYS = ['crew_size', 'crew_trades', 'crew_members', 'takes_full_projects'];

    public const REALTOR_KEYS = ['agency_name', 'deal_types', 'property_types', 'service_areas', 'license_no'];

    /** The own kind of a profile (Foreman/Realtor), or null for every other specialist. */
    public static function kindOf(?SpecialistProfile $profile): ?SpecialistCategory
    {
        return self::ownKind($profile?->specialty?->category);
    }

    /** The own kind behind a specialty id — used where only the submitted id is known. */
    public static function kindOfSpecialtyId(mixed $specialtyId): ?SpecialistCategory
    {
        if (! is_numeric($specialtyId)) {
            return null;
        }

        return self::ownKind(SpecialistSpecialty::query()->find((int) $specialtyId)?->category);
    }

    public static function ownKind(?SpecialistCategory $category): ?SpecialistCategory
    {
        return $category?->hasOwnKind() ? $category : null;
    }

    /**
     * Validation rules for the `details` payload of one kind. Prefix them with the
     * request key ("details.") — the cabinet posts them nested.
     *
     * `$detailsPosted` false (a save that carries no `details` at all) relaxes the one
     * required field, so a Makler can still change his phone from an older client.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(?SpecialistCategory $kind, string $prefix = 'details.', bool $detailsPosted = true): array
    {
        return match ($kind) {
            SpecialistCategory::Foreman => [
                $prefix.'crew_size' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_CREW_SIZE],
                $prefix.'crew_trades' => ['nullable', 'array', 'max:30'],
                $prefix.'crew_trades.*' => [
                    'integer',
                    Rule::exists('specialist_specialties', 'id')
                        ->where('category', SpecialistCategory::Master->value),
                ],
                $prefix.'crew_members' => ['nullable', 'array', 'max:'.self::MAX_CREW_MEMBERS],
                $prefix.'crew_members.*.name' => ['required', 'string', 'max:100'],
                $prefix.'crew_members.*.specialty_id' => [
                    'nullable',
                    'integer',
                    Rule::exists('specialist_specialties', 'id')
                        ->where('category', SpecialistCategory::Master->value),
                ],
                $prefix.'crew_members.*.experience_years' => ['nullable', 'integer', 'min:0', 'max:80'],
                $prefix.'takes_full_projects' => ['nullable', 'boolean'],
            ],
            SpecialistCategory::Realtor => [
                $prefix.'agency_name' => ['nullable', 'string', 'max:150'],
                // A Makler has to say what he does: at least one deal type.
                $prefix.'deal_types' => $detailsPosted ? ['required', 'array', 'min:1'] : ['nullable', 'array'],
                $prefix.'deal_types.*' => ['string', Rule::in(self::DEAL_TYPES)],
                $prefix.'property_types' => ['nullable', 'array'],
                $prefix.'property_types.*' => ['string', Rule::in(self::PROPERTY_TYPES)],
                $prefix.'service_areas' => ['nullable', 'string', 'max:300'],
                $prefix.'license_no' => ['nullable', 'string', 'max:60'],
            ],
            default => [],
        };
    }

    /**
     * Human names for the `details.*` fields, so a message reads «Dəstədə neçə nəfər var
     * ən azı 1 olmalıdır» rather than «details.crew size …». The cabinet labels are reused
     * (t() with an az fallback, like the enums), and every crew member row is numbered.
     *
     * @return array<string, string>
     */
    public static function attributes(?SpecialistCategory $kind, string $prefix = 'details.'): array
    {
        $label = static function (string $key, string $fallback): string {
            $value = t('specialist-cabinet.'.$key);

            return is_string($value) && $value !== 'specialist-cabinet.'.$key ? $value : $fallback;
        };

        return match ($kind) {
            SpecialistCategory::Foreman => [
                $prefix.'crew_size' => $label('crew.size', 'Dəstədə neçə nəfər var'),
                $prefix.'crew_trades' => $label('crew.trades', 'Dəstənin əhatə etdiyi ixtisaslar'),
                $prefix.'crew_trades.*' => $label('crew.trades', 'Dəstənin əhatə etdiyi ixtisaslar'),
                $prefix.'crew_members' => $label('crew.members', 'Dəstə üzvləri'),
                $prefix.'crew_members.*.name' => $label('crew.member_name', 'Ad, soyad'),
                $prefix.'crew_members.*.specialty_id' => $label('crew.member_specialty', 'İxtisas'),
                $prefix.'crew_members.*.experience_years' => $label('crew.member_years', 'Təcrübə (il)'),
                $prefix.'takes_full_projects' => $label('crew.full_projects', 'Tam layihə götürürəm'),
            ],
            SpecialistCategory::Realtor => [
                $prefix.'agency_name' => $label('realtor.agency', 'Agentlik adı'),
                $prefix.'deal_types' => $label('realtor.deal_types', 'Əməliyyat növləri'),
                $prefix.'deal_types.*' => $label('realtor.deal_types', 'Əməliyyat növləri'),
                $prefix.'property_types' => $label('realtor.property_types', 'Əmlak növləri'),
                $prefix.'property_types.*' => $label('realtor.property_types', 'Əmlak növləri'),
                $prefix.'service_areas' => $label('realtor.service_areas', 'Xidmət göstərdiyiniz ərazilər'),
                $prefix.'license_no' => $label('realtor.license', 'Lisenziya nömrəsi'),
            ],
            default => [],
        };
    }

    /**
     * Plain messages for the two cases a generic «:attribute is required» reads badly.
     *
     * @return array<string, string>
     */
    public static function messages(?SpecialistCategory $kind, string $prefix = 'details.'): array
    {
        $text = static function (string $key, string $fallback): string {
            $value = t('specialist-cabinet.'.$key);

            return is_string($value) && $value !== 'specialist-cabinet.'.$key ? $value : $fallback;
        };

        return match ($kind) {
            SpecialistCategory::Foreman => [
                $prefix.'crew_members.*.name.required' => $text('crew.member_name_required', 'Dəstə üzvünün adını yazın'),
            ],
            SpecialistCategory::Realtor => [
                $prefix.'deal_types.required' => $text('realtor.deal_types_required', 'Ən azı bir əməliyyat növü seçin'),
                $prefix.'deal_types.min' => $text('realtor.deal_types_required', 'Ən azı bir əməliyyat növü seçin'),
            ],
            default => [],
        };
    }

    /**
     * Keep only the keys of `$kind`, cast to their types. Null for a profile with no
     * own kind, so a former foreman's crew does not linger after a change of trade.
     *
     * @return array<string, mixed>|null
     */
    public static function normalize(?SpecialistCategory $kind, mixed $input): ?array
    {
        $input = is_array($input) ? $input : [];

        return match ($kind) {
            SpecialistCategory::Foreman => [
                'crew_size' => self::intOrNull($input['crew_size'] ?? null),
                'crew_trades' => self::idList($input['crew_trades'] ?? []),
                'crew_members' => collect(is_array($input['crew_members'] ?? null) ? $input['crew_members'] : [])
                    ->filter(fn ($member): bool => is_array($member) && trim((string) ($member['name'] ?? '')) !== '')
                    ->take(self::MAX_CREW_MEMBERS)
                    ->map(fn (array $member): array => [
                        'name' => trim((string) $member['name']),
                        'specialty_id' => self::intOrNull($member['specialty_id'] ?? null),
                        'experience_years' => self::intOrNull($member['experience_years'] ?? null),
                    ])
                    ->values()
                    ->all(),
                'takes_full_projects' => filter_var($input['takes_full_projects'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ],
            SpecialistCategory::Realtor => [
                'agency_name' => self::stringOrNull($input['agency_name'] ?? null),
                'deal_types' => self::pick($input['deal_types'] ?? [], self::DEAL_TYPES),
                'property_types' => self::pick($input['property_types'] ?? [], self::PROPERTY_TYPES),
                'service_areas' => self::stringOrNull($input['service_areas'] ?? null),
                'license_no' => self::stringOrNull($input['license_no'] ?? null),
            ],
            default => null,
        };
    }

    /**
     * The profile's details as the views read them: every key of its kind present,
     * defaults filled in. Empty array for a specialist with no own kind.
     *
     * @return array<string, mixed>
     */
    public static function of(?SpecialistProfile $profile, ?SpecialistCategory $kind = null): array
    {
        $kind ??= self::kindOf($profile);

        return self::normalize($kind, $profile?->details) ?? [];
    }

    /** Is the kind's block filled in enough to count in ProfileCompleteness? */
    public static function isComplete(?SpecialistCategory $kind, array $details): bool
    {
        return match ($kind) {
            SpecialistCategory::Foreman => ($details['crew_size'] ?? 0) > 0 && ($details['crew_trades'] ?? []) !== [],
            SpecialistCategory::Realtor => ($details['deal_types'] ?? []) !== [] && ($details['property_types'] ?? []) !== [],
            default => true,
        };
    }

    /** The one-line directory card summary: «Briqada: 8 nəfər» / «Agentlik: X». */
    public static function summary(?SpecialistProfile $profile): ?string
    {
        $kind = self::kindOf($profile);
        $details = self::of($profile, $kind);

        return match ($kind) {
            SpecialistCategory::Foreman => ($details['crew_size'] ?? 0) > 0
                ? t('specialists.card.crew_size', ['count' => $details['crew_size']])
                : null,
            SpecialistCategory::Realtor => filled($details['agency_name'] ?? null)
                ? t('specialists.card.agency', ['name' => $details['agency_name']])
                : null,
            default => null,
        };
    }

    /** @return array<string, string> value => translated label */
    public static function dealTypeOptions(): array
    {
        return self::labels('deal_type', self::DEAL_TYPES);
    }

    /** @return array<string, string> value => translated label */
    public static function propertyTypeOptions(): array
    {
        return self::labels('property_type', self::PROPERTY_TYPES);
    }

    /**
     * Specialty id => name of the trades a crew can cover (the master category).
     *
     * @return array<int, string>
     */
    public static function tradeOptions(): array
    {
        return SpecialistSpecialty::query()
            ->where('category', SpecialistCategory::Master->value)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn (SpecialistSpecialty $s): array => [$s->id => (string) $s->name])
            ->all();
    }

    /**
     * @param  list<string>  $values
     * @return array<string, string>
     */
    private static function labels(string $kind, array $values): array
    {
        $out = [];

        foreach ($values as $value) {
            $key = 'specialist-kinds.'.$kind.'.'.$value;
            $label = t($key);
            $out[$value] = is_string($label) && $label !== $key ? $label : $value;
        }

        return $out;
    }

    /** @return list<string> */
    private static function pick(mixed $values, array $allowed): array
    {
        $values = is_array($values) ? $values : [];

        // In the canonical order, not the submission order, and never a duplicate.
        return array_values(array_filter($allowed, fn (string $v): bool => in_array($v, $values, true)));
    }

    /** @return list<int> */
    private static function idList(mixed $values): array
    {
        $values = is_array($values) ? $values : [];

        return array_values(array_unique(array_map('intval', array_filter($values, 'is_numeric'))));
    }

    private static function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : $value;
    }
}
