<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\SellerProfile;
use App\Models\SpecialistProfile;
use App\Models\SpecialistSpecialty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regressions found driving the two professional cabinets by hand, with the
 * brand-new auto-approved accounts that registration now produces.
 *
 *  - /store/{seller} fell back to users.phone when the seller had not filled the
 *    contact tab, publishing the personal number used at sign-up on an anonymous
 *    page. The specialist side already gated its phone behind an authenticated,
 *    throttled endpoint; the seller storefront did not.
 *  - /specialist/owner ("this is how clients see you") rendered the Figma copy
 *    deck as the master's own data: a 4.9 rating, 416 reviews, two invented
 *    customer testimonials with names, 1,284 profile views, 36 enquiries, a 98%
 *    response rate and an unconditional green "Təsdiqlənmiş" badge.
 *  - the company tab's "VÖEN ✓ Təsdiqlənib" chip rendered unconditionally, so an
 *    empty VÖEN field was shown as verified.
 *  - /specialist/onboarding hardcoded a 40% banner and four fixed checklist
 *    states, contradicting the cabinet sidebar's real ProfileCompleteness score.
 */
final class ProfessionalCabinetQaTest extends TestCase
{
    use RefreshDatabase;

    private function seller(array $profile = []): User
    {
        $user = User::factory()->create([
            'role' => UserRole::Seller,
            'phone' => '+994501119988',
        ]);

        SellerProfile::create(['user_id' => $user->id, 'brand_name' => 'qa-Brand MMC'] + $profile);

        return $user;
    }

    private function master(): User
    {
        $user = User::factory()->create(['role' => UserRole::Master]);

        // Owns its specialty instead of borrowing whichever one happens to be in the
        // database. Reusing `->first()` made the test depend on rows a migration seeds,
        // and when the fallback branch did run it inserted without a slug — a NOT NULL
        // column with no default — so the file passed alone and died with a
        // QueryException in the full suite.
        $specialty = SpecialistSpecialty::query()->firstOrCreate(
            ['slug' => 'qa-cabinet-memar'],
            ['name' => ['az' => 'Memar'], 'is_active' => true, 'sort_order' => 1],
        );

        SpecialistProfile::create([
            'user_id' => $user->id,
            'specialist_specialty_id' => $specialty->id,
            'city' => 'Bakı',
        ]);

        return $user;
    }

    public function test_public_store_never_publishes_the_sellers_personal_signup_phone(): void
    {
        $seller = $this->seller();

        $html = $this->get("/store/{$seller->id}")->assertOk()->getContent();

        $this->assertStringNotContainsString('+994501119988', $html);
        $this->assertStringNotContainsString('tel:+994501119988', $html);
    }

    public function test_public_store_does_publish_the_contact_phone_the_seller_chose(): void
    {
        $seller = $this->seller(['contact_phone' => '+994551110000']);

        $this->get("/store/{$seller->id}")
            ->assertOk()
            ->assertSee('+994551110000');
    }

    public function test_owner_preview_matches_the_public_page_and_hides_the_personal_phone(): void
    {
        $seller = $this->seller();

        $html = $this->actingAs($seller)->get('/business/profile')->assertOk()->getContent();

        $this->assertStringNotContainsString('+994501119988', $html);
    }

    public function test_company_tab_does_not_claim_an_empty_tax_id_is_verified(): void
    {
        $seller = $this->seller();

        $html = $this->actingAs($seller)->get('/business/profile/company')->assertOk()->getContent();

        $this->assertStringNotContainsString(
            t('business-profile-company.company.tax_id_verified'),
            $html,
            'The VÖEN "verified" chip rendered for a profile whose tax_id_verified is false.',
        );
    }

    public function test_company_tab_logo_placeholder_uses_the_profiles_own_initials(): void
    {
        $seller = $this->seller();

        $this->actingAs($seller)->get('/business/profile/company')
            ->assertOk()
            ->assertSee('QA')          // from "qa-Brand MMC"
            ->assertDontSee('>AB<', false);
    }

    public function test_contact_tab_language_chips_default_to_az_only(): void
    {
        $seller = $this->seller();

        $html = $this->actingAs($seller)->get('/business/profile/contact')->assertOk()->getContent();

        preg_match_all('/data-lang="([a-z]+)"\s*\n?\s*data-on="([a-z]+)"/', $html, $m, PREG_SET_ORDER);

        $on = array_values(array_map(
            static fn (array $row): string => $row[1],
            array_filter($m, static fn (array $row): bool => $row[2] === 'true'),
        ));

        $this->assertSame(['az'], $on, 'A brand-new seller must not be pre-ticked into languages they never chose.');
    }

    public function test_owner_page_shows_no_fabricated_rating_reviews_or_stats(): void
    {
        $master = $this->master();

        $html = $this->actingAs($master)->get('/specialist/owner')->assertOk()->getContent();

        foreach (['416', '1,284', '4.9', '98%', 'Aysel', 'Elvin'] as $fabricated) {
            $this->assertStringNotContainsString(
                $fabricated,
                $html,
                "The owner page printed the Figma demo value \"{$fabricated}\" as the master's own data.",
            );
        }
    }

    public function test_owner_page_does_not_badge_an_unverified_master_as_verified(): void
    {
        $master = $this->master();

        $html = $this->actingAs($master)->get('/specialist/owner')->assertOk()->getContent();

        $this->assertStringNotContainsString('spo-badge ok', $html);
    }

    public function test_onboarding_progress_matches_the_cabinet_sidebar(): void
    {
        $master = $this->master();

        $onboarding = $this->actingAs($master)->get('/specialist/onboarding')->assertOk()->getContent();
        $cabinet = $this->actingAs($master)->get('/specialist/cabinet')->assertOk()->getContent();

        $this->assertSame(
            1,
            preg_match('/<div class="cab-snav-prog" data-completeness="(\d+)"/', $cabinet, $sidebar),
            'Could not read the sidebar completeness meter.',
        );
        $this->assertSame(
            1,
            preg_match('/role="progressbar"[^>]*aria-valuenow="(\d+)"/s', $onboarding, $banner),
            'Could not read the onboarding progress bar.',
        );

        $this->assertSame(
            $sidebar[1],
            $banner[1],
            'The onboarding banner and the cabinet sidebar must report the same completeness.',
        );
        $this->assertNotSame('40', $banner[1], 'The banner is still the hardcoded 40%.');
    }

    public function test_onboarding_checklist_reflects_the_real_profile(): void
    {
        $master = $this->master();

        $html = $this->actingAs($master)->get('/specialist/onboarding')->assertOk()->getContent();

        preg_match_all('/data-step data-done="([a-z]+)"/', $html, $m);

        // basics + specialty are filled by registration; portfolio and schedule are not.
        $this->assertSame(['true', 'true', 'false', 'false'], $m[1]);
    }
}
