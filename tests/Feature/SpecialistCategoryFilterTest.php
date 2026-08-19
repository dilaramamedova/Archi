<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SpecialistCategory;
use App\Enums\UserStatus;
use App\Models\SpecialistProfile;
use App\Models\SpecialistSpecialty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The header's "Mütəxəssislər" mega panel links to /specialists?type=architect and
 * friends. Those links were seeded long before anything read the parameter, so every
 * card silently landed on the undifferentiated directory — clicking "İnteryer
 * dizaynerlər" and clicking "Ustalar" produced the same page.
 */
final class SpecialistCategoryFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_type_narrows_the_listing_to_that_category(): void
    {
        $designer = $this->specialist('İnteryer dizayner', 'interyer-dizayner', SpecialistCategory::Designer, 'designer@example.com');
        $tiler = $this->specialist('Kafelçi', 'kafelci', SpecialistCategory::Master, 'tiler@example.com');

        $this->get('/specialists?type=designer')
            ->assertOk()
            ->assertSee($designer->user->name)
            ->assertDontSee($tiler->user->name);

        $this->get('/specialists?type=master')
            ->assertOk()
            ->assertSee($tiler->user->name)
            ->assertDontSee($designer->user->name);
    }

    public function test_every_menu_category_is_a_valid_type(): void
    {
        // The four values are a contract with the seeded menu items; a rename on
        // either side must fail here rather than silently stop filtering.
        foreach (['architect', 'designer', 'master', 'company'] as $value) {
            $this->assertNotNull(SpecialistCategory::tryFrom($value), "menu ?type={$value} has no enum case");
            $this->get('/specialists?type='.$value)->assertOk();
        }
    }

    public function test_unknown_type_leaves_the_listing_unfiltered(): void
    {
        $tiler = $this->specialist('Kafelçi', 'kafelci', SpecialistCategory::Master, 'tiler@example.com');

        // Same contract as an unknown ?category= slug on the catalog: fall through to
        // the full list rather than 404 or show nothing.
        $this->get('/specialists?type=nonsense')
            ->assertOk()
            ->assertSee($tiler->user->name);
    }

    public function test_active_category_is_shown_as_a_clearable_chip(): void
    {
        // A category with no active members must not look like a broken page.
        $this->get('/specialists?type=architect')
            ->assertOk()
            ->assertSee(SpecialistCategory::Architect->label())
            ->assertSee('sp-chip-static', escape: false);
    }

    private function specialist(string $name, string $slug, SpecialistCategory $category, string $email): SpecialistProfile
    {
        $specialty = SpecialistSpecialty::query()->create([
            'name' => $name,
            'slug' => $slug,
            'category' => $category,
        ]);

        $user = User::factory()->master()->create(['email' => $email, 'status' => UserStatus::Active]);

        return SpecialistProfile::query()->create([
            'user_id' => $user->id,
            'specialist_specialty_id' => $specialty->id,
            'city' => 'Bakı',
        ]);
    }
}
