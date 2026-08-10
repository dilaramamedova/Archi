<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;

/**
 * Real "profile completeness" for the cabinet sidebar.
 *
 * Every check is one filled field (or one existing row); the percentage is simply the
 * share of checks that pass, so it moves as the user fills the profile in. `next` is the
 * translation key of the first field still missing — the honest replacement for the old
 * hardcoded "Video əlavə et — +15%" hint (there is no video field, and no certificate
 * upload either).
 */
final class ProfileCompleteness
{
    /** @return array{percent: int, next: ?string} */
    public static function forSeller(?User $user): array
    {
        $profile = $user?->sellerProfile;

        return self::score([
            'business-profile-company.company.brand_name' => filled($profile?->brand_name),
            'business-profile-company.company.legal_name' => filled($profile?->legal_name),
            'business-profile-company.company.tax_id' => filled($profile?->tax_id),
            'business-profile-company.company.city' => filled($profile?->city),
            'business-profile-company.company.address' => filled($profile?->address),
            'business-profile-company.company.about' => filled($profile?->about),
            'business-profile-company.media.heading' => filled($profile?->logo_path) && filled($profile?->cover_path),
            'business-profile-contact.contact.person_label' => filled($profile?->contact_person),
            'business-profile-contact.contact.phone_label' => filled($profile?->contact_phone),
            'business-profile-contact.contact.email_label' => filled($profile?->contact_email),
            'business-profile-contact.contact.hours_label' => filled($profile?->work_hours),
            'business-profile-showrooms.nav.showrooms' => (int) ($profile?->showrooms()->count() ?? 0) > 0,
            'business-profile-products.nav.products' => (int) ($user?->products()->count() ?? 0) > 0,
        ]);
    }

    /** @return array{percent: int, next: ?string} */
    public static function forSpecialist(?User $user): array
    {
        $profile = $user?->specialistProfile;
        $skills = is_array($profile?->skills) ? $profile->skills : [];

        return self::score([
            'specialist-cabinet.photo.heading' => filled($profile?->avatar_path),
            'specialist-cabinet.main.craft' => filled($profile?->specialist_specialty_id) || filled($profile?->craft),
            'specialist-cabinet.main.experience' => filled($profile?->experience_years),
            'specialist-cabinet.main.city' => filled($profile?->city),
            'specialist-cabinet.main.phone' => filled($profile?->phone),
            'specialist-cabinet.main.whatsapp' => filled($profile?->whatsapp),
            'specialist-cabinet.main.about' => filled($profile?->about),
            'specialist-cabinet.skills.heading' => $skills !== [],
            'specialist-cabinet.nav.portfolio' => (int) ($profile?->portfolioItems()->count() ?? 0) > 0,
            'specialist-cabinet.nav.services' => (int) ($profile?->services()->count() ?? 0) > 0,
            'specialist-cabinet.nav.schedule' => (int) ($profile?->schedules()->count() ?? 0) > 0,
        ]);
    }

    /**
     * @param  array<string, bool>  $checks  label translation key => is it filled
     * @return array{percent: int, next: ?string}
     */
    private static function score(array $checks): array
    {
        $total = count($checks);
        $done = count(array_filter($checks));
        $missing = array_keys(array_filter($checks, static fn (bool $ok): bool => ! $ok));

        return [
            'percent' => $total === 0 ? 0 : (int) round($done / $total * 100),
            'next' => $missing[0] ?? null,
        ];
    }
}
