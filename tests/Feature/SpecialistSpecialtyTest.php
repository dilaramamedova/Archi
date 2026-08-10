<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Filament\Resources\SpecialistResource\Pages\EditSpecialist;
use App\Filament\Resources\SpecialistSpecialtyResource\Pages\CreateSpecialistSpecialty;
use App\Models\SpecialistProfile;
use App\Models\SpecialistSpecialty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class SpecialistSpecialtyTest extends TestCase
{
    use RefreshDatabase;

    public function test_rating_filter_is_removed_from_specialist_page_and_ignored_by_backend(): void
    {
        $specialty = SpecialistSpecialty::query()->create(['name' => 'Elektrik', 'slug' => 'elektrik']);
        $profile = $this->activeSpecialist($specialty, 'electric@example.com');

        $this->get('/specialists?min_rating=5')
            ->assertOk()
            ->assertSee($profile->user->name)
            ->assertDontSee('4.5 və yuxarı')
            ->assertDontSee('4.0 və yuxarı')
            ->assertDontSee('3.5 və yuxarı');
    }

    public function test_specialist_filter_uses_specialty_relationship(): void
    {
        $electric = SpecialistSpecialty::query()->create(['name' => 'Elektrik', 'slug' => 'elektrik']);
        $plumber = SpecialistSpecialty::query()->create(['name' => 'Santexnik', 'slug' => 'santexnik']);
        $electricProfile = $this->activeSpecialist($electric, 'electric@example.com');
        $plumberProfile = $this->activeSpecialist($plumber, 'plumber@example.com');

        $this->get("/specialists?spec={$electric->id}")
            ->assertOk()
            ->assertSee($electricProfile->user->name)
            ->assertDontSee($plumberProfile->user->name);
    }

    public function test_registration_page_uses_active_specialty_dropdown(): void
    {
        $active = SpecialistSpecialty::query()->create(['name' => 'Aktiv ixtisas', 'slug' => 'aktiv', 'is_active' => true]);
        SpecialistSpecialty::query()->create(['name' => 'Bağlı ixtisas', 'slug' => 'bagli', 'is_active' => false]);

        $this->get('/register')
            ->assertOk()
            ->assertSee('name="specialist_specialty_id"', false)
            ->assertSee('value="'.$active->id.'"', false)
            ->assertSee('Aktiv ixtisas')
            ->assertDontSee('Bağlı ixtisas');
    }

    public function test_admin_can_create_a_specialty_and_edit_specialist_relation(): void
    {
        $old = SpecialistSpecialty::query()->create(['name' => 'Köhnə', 'slug' => 'kohne']);
        $profile = $this->activeSpecialist($old, 'master@example.com');
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin, 'admin');

        Livewire::test(CreateSpecialistSpecialty::class)
            ->fillForm([
                'name' => ['az' => 'Yeni ixtisas', 'ru' => 'Новая специальность', 'en' => 'New specialty'],
                'slug' => 'yeni-ixtisas',
                'is_active' => true,
                'sort_order' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $new = SpecialistSpecialty::query()->where('slug', 'yeni-ixtisas')->firstOrFail();

        Livewire::test(EditSpecialist::class, ['record' => $profile->user->getRouteKey()])
            ->fillForm(['specialistProfile' => ['specialist_specialty_id' => $new->id]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($profile->fresh()->specialty->is($new));
        $this->assertSame('Yeni ixtisas', $profile->fresh()->craft);
    }

    private function activeSpecialist(SpecialistSpecialty $specialty, string $email): SpecialistProfile
    {
        $user = User::factory()->master()->create(['email' => $email, 'status' => UserStatus::Active]);

        return SpecialistProfile::query()->create([
            'user_id' => $user->id,
            'specialist_specialty_id' => $specialty->id,
            'city' => 'Bakı',
        ]);
    }
}
