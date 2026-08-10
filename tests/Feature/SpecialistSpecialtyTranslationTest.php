<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Filament\Resources\SpecialistSpecialtyResource\Pages\CreateSpecialistSpecialty;
use App\Filament\Resources\SpecialistSpecialtyResource\Pages\EditSpecialistSpecialty;
use App\Models\SpecialistProfile;
use App\Models\SpecialistSpecialty;
use App\Models\User;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Tests\TestCase;

final class SpecialistSpecialtyTranslationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_created_specialty_appears_in_the_registration_dropdown(): void
    {
        $this->actingAs(User::factory()->admin()->create(), 'admin');

        Livewire::test(CreateSpecialistSpecialty::class)
            ->fillForm([
                'name' => ['az' => 'Suvaqçı', 'ru' => 'Штукатур', 'en' => 'Plasterer'],
                'slug' => 'suvaqci',
                'is_active' => true,
                'sort_order' => 0,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $specialty = SpecialistSpecialty::query()->where('slug', 'suvaqci')->firstOrFail();

        // /register is guest-only; drop the admin session before visiting it.
        Auth::guard('admin')->logout();
        Auth::shouldUse('web');

        $this->get('/register')
            ->assertOk()
            ->assertSee('value="'.$specialty->id.'"', false)
            ->assertSee('Suvaqçı');
    }

    public function test_registration_dropdown_renders_the_russian_and_english_names(): void
    {
        SpecialistSpecialty::query()->create([
            'name' => ['az' => 'Suvaqçı', 'ru' => 'Штукатур', 'en' => 'Plasterer'],
            'slug' => 'suvaqci',
        ]);

        $this->withSession(['locale' => 'ru'])->get('/register')->assertOk()->assertSee('Штукатур');
        $this->withSession(['locale' => 'en'])->get('/register')->assertOk()->assertSee('Plasterer');
    }

    public function test_public_specialist_page_shows_the_specialty_in_each_locale(): void
    {
        $specialty = SpecialistSpecialty::query()->create([
            'name' => ['az' => 'Boyaçı', 'ru' => 'Маляр', 'en' => 'Painter'],
            'slug' => 'boyaci',
        ]);
        $profile = $this->activeSpecialist($specialty, 'painter@example.com');

        $this->get("/specialist/{$profile->id}")->assertOk()->assertSee('Boyaçı');
        $this->withSession(['locale' => 'ru'])->get("/specialist/{$profile->id}")->assertOk()->assertSee('Маляр');
        $this->withSession(['locale' => 'en'])->get("/specialist/{$profile->id}")->assertOk()->assertSee('Painter');
    }

    public function test_renaming_a_specialty_in_the_panel_updates_the_public_pages(): void
    {
        $specialty = SpecialistSpecialty::query()->create([
            'name' => ['az' => 'Boyaçı', 'ru' => 'Маляр', 'en' => 'Painter'],
            'slug' => 'boyaci',
        ]);
        $profile = $this->activeSpecialist($specialty, 'painter@example.com');

        $this->actingAs(User::factory()->admin()->create(), 'admin');

        Livewire::test(EditSpecialistSpecialty::class, ['record' => $specialty->getRouteKey()])
            ->fillForm(['name' => ['az' => 'Rəngsaz', 'ru' => 'Маляр-декоратор', 'en' => 'Decorator']])
            ->call('save')
            ->assertHasNoFormErrors();

        // The denormalized snapshot follows the Azerbaijani name.
        $this->assertSame('Rəngsaz', $profile->fresh()->craft);

        $this->get("/specialist/{$profile->id}")->assertOk()->assertSee('Rəngsaz')->assertDontSee('Boyaçı');
        $this->get('/specialists')->assertOk()->assertSee('Rəngsaz');
        $this->withSession(['locale' => 'en'])->get("/specialist/{$profile->id}")->assertOk()->assertSee('Decorator');
    }

    public function test_deactivating_a_specialty_hides_it_from_dropdowns_but_keeps_profiles_rendering(): void
    {
        $specialty = SpecialistSpecialty::query()->create([
            'name' => ['az' => 'Qaynaqçı', 'ru' => 'Сварщик', 'en' => 'Welder'],
            'slug' => 'qaynaqci',
        ]);
        $profile = $this->activeSpecialist($specialty, 'welder@example.com');

        $specialty->update(['is_active' => false]);

        $this->get('/register')->assertOk()->assertDontSee('Qaynaqçı');

        // A master who is NOT on the deactivated specialty must not be offered it.
        $other = $this->activeSpecialist(
            SpecialistSpecialty::query()->create(['name' => ['az' => 'Dülgər'], 'slug' => 'dulger']),
            'other-welder@example.com',
        );

        $this->actingAs($other->user)
            ->get('/specialist/cabinet')
            ->assertOk()
            ->assertDontSee('value="'.$specialty->id.'"', false);

        // The assigned master keeps seeing it, selected — otherwise the select
        // would fall back to its first option and silently rewrite their trade.
        $this->actingAs($profile->user)
            ->get('/specialist/cabinet')
            ->assertOk()
            ->assertSee('value="'.$specialty->id.'" selected', false);

        $this->get("/specialist/{$profile->id}")->assertOk()->assertSee('Qaynaqçı');
        $this->get('/specialists')->assertOk()->assertSee($profile->user->name);
    }

    public function test_deleting_a_specialty_keeps_the_profile_pages_rendering(): void
    {
        $specialty = SpecialistSpecialty::query()->create([
            'name' => ['az' => 'Qaynaqçı', 'ru' => 'Сварщик', 'en' => 'Welder'],
            'slug' => 'qaynaqci',
        ]);
        $profile = $this->activeSpecialist($specialty, 'welder@example.com');

        $specialty->delete();

        $this->assertNull($profile->fresh()->specialist_specialty_id);
        $this->get("/specialist/{$profile->id}")->assertOk();
        $this->get('/specialists')->assertOk()->assertSee($profile->user->name);
    }

    public function test_legacy_profile_without_a_specialty_falls_back_to_the_craft_map(): void
    {
        $user = User::factory()->master()->create(['email' => 'legacy@example.com', 'status' => UserStatus::Active]);
        $profile = SpecialistProfile::query()->create([
            'user_id' => $user->id,
            'craft' => 'Dülgər',
            'city' => 'Bakı',
        ]);

        $this->get("/specialist/{$profile->id}")->assertOk()->assertSee('Dülgər');
        $this->withSession(['locale' => 'en'])->get("/specialist/{$profile->id}")->assertOk()->assertSee('Carpenter');
    }

    public function test_search_finds_a_specialist_by_the_specialty_name_in_each_locale(): void
    {
        $specialty = SpecialistSpecialty::query()->create([
            'name' => ['az' => 'Suvaqçı', 'ru' => 'Штукатур', 'en' => 'Plasterer'],
            'slug' => 'suvaqci',
        ]);
        $profile = $this->activeSpecialist($specialty, 'plasterer@example.com');

        foreach (['Suvaqçı', 'штукатур', 'PLASTERER'] as $term) {
            $ids = SearchService::buildSpecialistQuery(SpecialistProfile::query(), $term)->pluck('id');
            $this->assertTrue($ids->contains($profile->id), "Search did not match '{$term}'");
        }

        $this->get('/specialists?'.http_build_query(['q' => 'Штукатур']))
            ->assertOk()
            ->assertSee($profile->user->name);
    }

    public function test_cabinet_save_does_not_silently_move_a_master_off_a_deactivated_specialty(): void
    {
        $specialty = SpecialistSpecialty::query()->create([
            'name' => ['az' => 'Qaynaqçı', 'ru' => 'Сварщик', 'en' => 'Welder'],
            'slug' => 'qaynaqci',
        ]);
        $profile = $this->activeSpecialist($specialty, 'welder@example.com');
        $specialty->update(['is_active' => false]);

        $this->actingAs($profile->user)
            ->putJson('/specialist/cabinet', [
                'first_name' => $profile->user->first_name,
                'last_name' => $profile->user->last_name,
                'specialist_specialty_id' => $specialty->id,
                'city' => 'Bakı',
            ])
            ->assertOk();

        $this->assertSame($specialty->id, $profile->fresh()->specialist_specialty_id);
        $this->assertSame('Qaynaqçı', $profile->fresh()->craft);
    }

    public function test_a_master_cannot_switch_onto_someone_elses_deactivated_specialty(): void
    {
        $retired = SpecialistSpecialty::query()->create([
            'name' => ['az' => 'Qaynaqçı'], 'slug' => 'qaynaqci', 'is_active' => false,
        ]);
        $active = SpecialistSpecialty::query()->create(['name' => ['az' => 'Dülgər'], 'slug' => 'dulger']);
        $profile = $this->activeSpecialist($active, 'carpenter@example.com');

        $this->actingAs($profile->user)
            ->putJson('/specialist/cabinet', [
                'first_name' => $profile->user->first_name,
                'last_name' => $profile->user->last_name,
                'specialist_specialty_id' => $retired->id,
                'city' => 'Bakı',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('specialist_specialty_id');

        $this->assertSame($active->id, $profile->fresh()->specialist_specialty_id);
    }

    public function test_two_specialties_may_share_an_azerbaijani_name(): void
    {
        SpecialistSpecialty::query()->create(['name' => ['az' => 'Santexnik'], 'slug' => 'santexnik']);
        SpecialistSpecialty::query()->create(['name' => ['az' => 'Santexnik'], 'slug' => 'santexnik-vip']);

        $this->assertSame(2, SpecialistSpecialty::query()->count());
    }

    public function test_specialty_names_never_leak_raw_json_into_the_html(): void
    {
        $specialty = SpecialistSpecialty::query()->create([
            'name' => ['az' => 'Suvaqçı', 'ru' => 'Штукатур', 'en' => 'Plasterer'],
            'slug' => 'suvaqci',
        ]);
        $profile = $this->activeSpecialist($specialty, 'plasterer@example.com');

        foreach (['az', 'ru', 'en'] as $locale) {
            foreach (['/', '/specialists', "/specialist/{$profile->id}", '/register', '/catalog'] as $path) {
                $html = $this->withSession(['locale' => $locale])->get($path)->assertOk()->getContent();

                $this->assertStringNotContainsString('{"az"', $html, "Raw json leaked on {$path} [{$locale}]");
                $this->assertStringNotContainsString('{&quot;az&quot;', $html, "Raw json leaked on {$path} [{$locale}]");
            }
        }
    }

    public function test_seeding_the_canonical_trades_twice_keeps_admin_edits_and_adds_no_duplicates(): void
    {
        $this->seed(\Database\Seeders\DemoSeeder::class);

        $before = SpecialistSpecialty::query()->count();

        $kafelci = SpecialistSpecialty::query()->where('slug', 'kafelci')->firstOrFail();
        $kafelci->setTranslation('name', 'az', 'Kafel ustası (admin)');
        $kafelci->setTranslation('name', 'ru', 'Плиточник (admin)');
        $kafelci->save();

        $this->seed(\Database\Seeders\DemoSeeder::class);

        $kafelci->refresh();
        $this->assertSame('Kafel ustası (admin)', $kafelci->getTranslation('name', 'az'));
        $this->assertSame('Плиточник (admin)', $kafelci->getTranslation('name', 'ru'));
        $this->assertSame($before, SpecialistSpecialty::query()->count());
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
