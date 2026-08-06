<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PortfolioStatus;
use App\Enums\UserStatus;
use App\Filament\Resources\PortfolioApprovalResource\Pages\ListPortfolioApprovals;
use App\Filament\Resources\SpecialistResource\Pages\EditSpecialist;
use App\Filament\Resources\SpecialistResource\RelationManagers\PortfolioItemsRelationManager;
use App\Filament\Resources\SpecialistResource\RelationManagers\SchedulesRelationManager;
use App\Filament\Resources\SpecialistResource\RelationManagers\ServicesRelationManager;
use App\Models\SpecialistProfile;
use App\Models\SpecialistSpecialty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SpecialistCabinetTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_specialists_can_access_specialist_cabinet_routes(): void
    {
        $buyer = User::factory()->buyer()->create();

        foreach ([
            '/specialist/cabinet',
            '/specialist/cabinet/portfolio',
            '/specialist/cabinet/services',
            '/specialist/cabinet/schedule',
            '/specialist/cabinet/security',
        ] as $url) {
            $this->actingAs($buyer)->get($url)->assertForbidden();
        }
    }

    public function test_specialist_can_update_profile_and_full_name(): void
    {
        [$user] = $this->specialist();
        $specialty = SpecialistSpecialty::query()->create(['name' => 'Elektrik', 'slug' => 'elektrik']);

        $this->actingAs($user)->putJson('/specialist/cabinet', [
            'first_name' => 'Vüsal',
            'last_name' => 'Yenilənmiş',
            'specialist_specialty_id' => $specialty->id,
            'experience_years' => 12,
            'city' => 'Bakı',
            'phone' => '+994501234567',
            'whatsapp' => '+994501234568',
            'about' => 'Peşəkar mütəxəssis',
            'skills' => ['Elektrik', 'Smart ev'],
        ])->assertOk()->assertJsonPath('success', true);

        $user->refresh();
        $this->assertSame('Vüsal Yenilənmiş', $user->name);
        $this->assertSame('Elektrik', $user->specialistProfile?->craft);
        $this->assertTrue($user->specialistProfile?->specialty->is($specialty));
        $this->assertSame(['Elektrik', 'Smart ev'], $user->specialistProfile?->skills);
    }

    public function test_specialist_can_upload_and_delete_avatar(): void
    {
        Storage::fake('public');
        [$user] = $this->specialist();

        $response = $this->actingAs($user)->post('/specialist/cabinet/avatar', [
            'avatar' => UploadedFile::fake()->image('avatar.webp', 300, 300),
        ])->assertOk();

        $path = $response->json('path');
        Storage::disk('public')->assertExists($path);

        $this->actingAs($user)->deleteJson('/specialist/cabinet/avatar')->assertOk();
        Storage::disk('public')->assertMissing($path);
        $this->assertNull($user->specialistProfile?->fresh()->avatar_path);
    }

    public function test_specialist_can_create_update_reorder_and_delete_services(): void
    {
        [$user, $profile] = $this->specialist();
        $first = $profile->services()->create(['name' => 'Köhnə xidmət', 'price' => 10, 'unit' => 'hour']);

        $this->actingAs($user)->putJson('/specialist/cabinet/services', [
            'services' => [
                [
                    'id' => $first->id,
                    'name' => 'Yenilənmiş xidmət',
                    'description' => 'Təsvir',
                    'price' => 25,
                    'unit' => 'piece',
                    'is_active' => true,
                ],
                [
                    'name' => 'Yeni xidmət',
                    'description' => null,
                    'price' => 40,
                    'unit' => 'hour',
                    'is_active' => false,
                ],
            ],
        ])->assertOk();

        $services = $profile->services()->orderBy('sort_order')->get();
        $this->assertCount(2, $services);
        $this->assertSame('Yenilənmiş xidmət', $services[0]->name);
        $this->assertSame('Yeni xidmət', $services[1]->name);
        $this->assertFalse($services[1]->is_active);

        $this->actingAs($user)->putJson('/specialist/cabinet/services', ['services' => []])->assertOk();
        $this->assertSame(0, $profile->services()->count());
    }

    public function test_empty_services_page_contains_an_independent_add_row_template(): void
    {
        [$user] = $this->specialist();

        $this->actingAs($user)->get('/specialist/cabinet/services')
            ->assertOk()
            ->assertSee('specialistServiceRowTemplate')
            ->assertSee('data-add', false);
    }

    public function test_empty_profile_does_not_render_placeholder_about_tags_services_or_portfolio(): void
    {
        [$user, $profile] = $this->specialist();

        $this->get("/specialist/{$profile->id}")
            ->assertOk()
            ->assertDontSee(__('specialist.about.text'))
            ->assertDontSee(__('specialist.services.items.s1.title'))
            ->assertDontSee('class="pp-tags"', false);

        $this->actingAs($user)->get('/specialist/owner')
            ->assertOk()
            ->assertDontSee(__('specialist-owner.about.text'))
            ->assertDontSee(__('specialist-owner.services.items.s1.title'))
            ->assertDontSee('portfolio-stone-tile-samples.jpg')
            ->assertDontSee('class="spo-tags"', false);
    }

    public function test_specialist_can_save_complete_weekly_schedule(): void
    {
        [$user, $profile] = $this->specialist();
        $days = collect(range(1, 7))->map(fn (int $day): array => [
            'day_of_week' => $day,
            'is_day_off' => $day > 5,
            'start_time' => $day > 5 ? null : '09:00',
            'end_time' => $day > 5 ? null : '18:00',
        ])->all();

        $this->actingAs($user)->putJson('/specialist/cabinet/schedule', [
            'days' => $days,
            'available_slots' => 8,
            'is_on_vacation' => true,
        ])->assertOk();

        $this->assertSame(7, $profile->schedules()->count());
        $this->assertSame(8, $profile->fresh()->available_slots);
        $this->assertTrue($profile->fresh()->is_on_vacation);
    }

    public function test_schedule_rejects_invalid_working_hours(): void
    {
        [$user] = $this->specialist();
        $days = collect(range(1, 7))->map(fn (int $day): array => [
            'day_of_week' => $day,
            'is_day_off' => false,
            'start_time' => '18:00',
            'end_time' => '09:00',
        ])->all();

        $this->actingAs($user)->putJson('/specialist/cabinet/schedule', [
            'days' => $days,
            'available_slots' => 1,
            'is_on_vacation' => false,
        ])->assertUnprocessable();
    }

    public function test_specialist_can_upload_reorder_and_delete_portfolio(): void
    {
        Storage::fake('public');
        [$user, $profile] = $this->specialist();
        $old = $profile->portfolioItems()->create([
            'title' => 'Köhnə',
            'image_path' => 'specialists/portfolio/old.jpg',
            'is_cover' => true,
        ]);
        Storage::disk('public')->put($old->image_path, 'old');

        $this->actingAs($user)->post('/specialist/cabinet/portfolio', [
            'items' => json_encode([
                ['id' => $old->id, 'title' => 'Yenilənmiş'],
                ['id' => null, 'title' => 'Yeni', 'file_index' => 0],
            ], JSON_THROW_ON_ERROR),
            'images' => [UploadedFile::fake()->image('work.jpg')],
        ], ['Accept' => 'application/json'])->assertOk();

        $items = $profile->portfolioItems()->orderBy('sort_order')->get();
        $this->assertCount(2, $items);
        $this->assertTrue($items[0]->is_cover);
        $this->assertFalse($items[1]->is_cover);
        $this->assertSame(PortfolioStatus::Pending, $items[1]->status);
        Storage::disk('public')->assertExists($items[1]->image_path);

        $this->actingAs($user)->post('/specialist/cabinet/portfolio', [
            'items' => json_encode([], JSON_THROW_ON_ERROR),
        ], ['Accept' => 'application/json'])->assertOk();

        $this->assertSame(0, $profile->portfolioItems()->count());
        Storage::disk('public')->assertMissing($old->image_path);
    }

    public function test_only_approved_portfolio_is_visible_on_public_specialist_page(): void
    {
        [, $profile] = $this->specialist();
        $profile->portfolioItems()->create([
            'title' => 'Yoxlanılan şəkil',
            'image_path' => 'specialists/portfolio/pending.jpg',
            'status' => PortfolioStatus::Pending,
        ]);
        $profile->portfolioItems()->create([
            'title' => 'Təsdiqlənmiş şəkil',
            'image_path' => 'specialists/portfolio/approved.jpg',
            'status' => PortfolioStatus::Approved,
        ]);

        $this->get("/specialist/{$profile->id}")
            ->assertOk()
            ->assertDontSee('Yoxlanılan şəkil')
            ->assertSee('Təsdiqlənmiş şəkil');
    }

    public function test_public_specialist_profile_shows_contact_phone_and_hides_disabled_sections(): void
    {
        [, $profile] = $this->specialist();
        $profile->update(['phone' => '+994 50 123 45 67']);

        $this->get("/specialist/{$profile->id}")
            ->assertOk()
            ->assertSee('Əlaqə saxla')
            ->assertSee('data-phone="+994 50 123 45 67"', false)
            ->assertDontSee('Müştəri rəyləri')
            ->assertDontSee('Mesaj göndər')
            ->assertDontSee('· ilk zəng')
            ->assertDontSee('Tamamlanmış');
    }

    public function test_specialist_cabinet_navigation_hides_reviews_and_notifications(): void
    {
        [$user] = $this->specialist();

        $this->actingAs($user)->get('/specialist/cabinet')
            ->assertOk()
            ->assertDontSee(route('specialist.cabinet.reviews'))
            ->assertDontSee(route('specialist.cabinet.notifications'));
    }

    public function test_empty_portfolio_page_contains_upload_template_and_pending_status(): void
    {
        [$user] = $this->specialist();

        $this->actingAs($user)->get('/specialist/cabinet/portfolio')
            ->assertOk()
            ->assertSee('portfolioTileTemplate')
            ->assertSee('Yoxlanılır');
    }

    public function test_admin_has_a_pending_portfolio_queue_and_can_approve_an_item(): void
    {
        [, $profile] = $this->specialist();
        $item = $profile->portfolioItems()->create([
            'title' => 'Admin təsdiqi gözləyir',
            'image_path' => 'specialists/portfolio/pending.jpg',
            'status' => PortfolioStatus::Pending,
        ]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'admin');

        Livewire::test(ListPortfolioApprovals::class)
            ->assertCanSeeTableRecords([$item])
            ->callTableAction('approve', $item);

        $item->refresh();
        $this->assertSame(PortfolioStatus::Approved, $item->status);
        $this->assertSame($admin->id, $item->approved_by);
        $this->assertNotNull($item->approved_at);
    }

    public function test_specialist_cannot_modify_another_specialists_records(): void
    {
        [$user] = $this->specialist();
        [, $otherProfile] = $this->specialist('other-specialist@example.com');
        $foreignService = $otherProfile->services()->create(['name' => 'Başqasının xidməti', 'unit' => 'hour']);

        $this->actingAs($user)->putJson('/specialist/cabinet/services', [
            'services' => [[
                'id' => $foreignService->id,
                'name' => 'Oğurlanmış',
                'description' => null,
                'price' => 1,
                'unit' => 'hour',
                'is_active' => true,
            ]],
        ])->assertNotFound();

        $this->assertSame('Başqasının xidməti', $foreignService->fresh()->name);
    }

    public function test_security_password_and_deactivation_work_without_removed_security_blocks(): void
    {
        [$user] = $this->specialist();

        $this->actingAs($user)->postJson('/cabinet/password', [
            'current_password' => 'password',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])->assertOk();
        $this->assertTrue(Hash::check('new-secure-password', $user->fresh()->password));

        $this->actingAs($user)->get('/specialist/cabinet/security')
            ->assertOk()
            ->assertDontSee('İki mərhələli doğrulama')
            ->assertDontSee('Aktiv sessiyalar');

        $this->actingAs($user)->putJson('/specialist/cabinet/two-factor', ['enabled' => true])->assertNotFound();
        $this->actingAs($user)->getJson('/cabinet/sessions')->assertNotFound();

        $this->actingAs($user)->postJson('/cabinet/deactivate', ['password' => 'new-secure-password'])->assertOk();
        $this->assertSame(UserStatus::Blocked, $user->fresh()->status);
        $this->assertGuest();
    }

    public function test_admin_can_render_all_specialist_data_in_edit_form(): void
    {
        [$specialist, $profile] = $this->specialist();
        $service = $profile->services()->create(['name' => 'Admin xidməti', 'unit' => 'hour']);
        $schedule = $profile->schedules()->create(['day_of_week' => 1, 'start_time' => '09:00', 'end_time' => '18:00']);
        $portfolio = $profile->portfolioItems()->create(['title' => 'Admin portfolio', 'image_path' => 'assets/work.jpg']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'admin')
            ->get("/admin/specialists/{$specialist->id}/edit")
            ->assertOk();

        foreach ([
            [ServicesRelationManager::class, $service],
            [SchedulesRelationManager::class, $schedule],
            [PortfolioItemsRelationManager::class, $portfolio],
        ] as [$manager, $record]) {
            Livewire::test($manager, [
                'ownerRecord' => $specialist,
                'pageClass' => EditSpecialist::class,
            ])->assertCanSeeTableRecords([$record]);
        }
    }

    private function specialist(string $email = 'specialist@example.com'): array
    {
        $user = User::factory()->master()->create(['email' => $email]);
        $specialty = SpecialistSpecialty::query()->firstOrCreate(
            ['slug' => 'santexnik'],
            ['name' => 'Santexnik', 'is_active' => true],
        );
        $profile = SpecialistProfile::query()->create([
            'user_id' => $user->id,
            'specialist_specialty_id' => $specialty->id,
            'craft' => 'Santexnik',
            'city' => 'Bakı',
        ]);

        return [$user, $profile];
    }
}
