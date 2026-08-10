<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\SellerProfile;
use App\Models\SpecialistProfile;
use App\Models\User;
use App\Support\ProfileCompleteness;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileCompletenessTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_bare_specialist_profile_scores_low_and_a_filled_one_scores_high(): void
    {
        $user = User::factory()->master()->create();
        SpecialistProfile::query()->create(['user_id' => $user->id]);

        $bare = ProfileCompleteness::forSpecialist($user->fresh());
        $this->assertLessThan(20, $bare['percent']);
        $this->assertNotNull($bare['next']);

        $user->specialistProfile->update([
            'craft' => 'Kafelçi',
            'experience_years' => 8,
            'city' => 'Bakı',
            'phone' => '+994501112233',
            'whatsapp' => '+994501112233',
            'about' => 'Peşəkar kafel ustası.',
            'skills' => ['Kafel', 'Mozaika'],
            'avatar_path' => 'specialists/avatars/a.jpg',
        ]);

        $filled = ProfileCompleteness::forSpecialist($user->fresh());
        $this->assertGreaterThan($bare['percent'], $filled['percent']);
        $this->assertGreaterThanOrEqual(70, $filled['percent']);
    }

    public function test_a_bare_seller_profile_scores_low_and_a_filled_one_scores_high(): void
    {
        $user = User::factory()->seller()->create();
        SellerProfile::query()->create(['user_id' => $user->id]);

        $bare = ProfileCompleteness::forSeller($user->fresh());
        $this->assertLessThan(20, $bare['percent']);

        $user->sellerProfile->update([
            'brand_name' => 'ARCHI Tile',
            'legal_name' => 'ARCHI MMC',
            'tax_id' => '1234567890',
            'city' => 'Bakı',
            'address' => 'Nizami küç. 1',
            'about' => 'Kafel mağazası.',
            'logo_path' => 'sellers/logo.png',
            'cover_path' => 'sellers/cover.png',
            'contact_person' => 'Nigar Əliyeva',
            'contact_phone' => '+994701112233',
            'contact_email' => 'info@example.com',
            'work_hours' => '09:00–18:00',
        ]);

        $filled = ProfileCompleteness::forSeller($user->fresh());
        $this->assertGreaterThan($bare['percent'], $filled['percent']);
        $this->assertGreaterThanOrEqual(80, $filled['percent']);
    }

    public function test_sidebar_renders_the_computed_value_not_the_hardcoded_one(): void
    {
        $user = User::factory()->master()->create();
        SpecialistProfile::query()->create(['user_id' => $user->id]);

        $percent = ProfileCompleteness::forSpecialist($user->fresh())['percent'];

        $this->actingAs($user)->get('/specialist/cabinet')
            ->assertOk()
            ->assertSee('data-completeness="'.$percent.'"', false)
            ->assertDontSee('78%')
            ->assertDontSee('Video əlavə et');
    }
}
