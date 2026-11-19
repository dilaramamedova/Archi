<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SpecialistCategory;
use App\Enums\SpecialistCategoryGroup;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\SpecialistResource;
use App\Filament\Resources\SpecialistResource\Pages\ListSpecialists;
use App\Models\SpecialistProfile;
use App\Models\SpecialistSpecialty;
use App\Models\User;
use App\Support\ProfileCompleteness;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * «Briqadir» (foreman with a crew) and «Makler» (real-estate agent) — the two specialist
 * kinds added on 2026-11-19. Both are role=master with their own SpecialistCategory, one
 * specialty row each (seeded by 2026_11_19_120100) and a `details` json block.
 * See docs/specialist-kinds.md.
 */
final class SpecialistKindsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The two specialty rows come from the 2026_11_19_120100 migration — but test classes
     * that truncate (CommitsToDatabase) wipe migration data and reseed translations only,
     * so in the full suite they may be gone. Ensure them here, as that migration does.
     */
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            ['slug' => 'briqadir', 'category' => SpecialistCategory::Foreman, 'name' => ['az' => 'Briqadir (usta dəstəsi)', 'ru' => 'Бригадир (бригада мастеров)', 'en' => 'Foreman (crew of craftsmen)'], 'sort_order' => 15],
            ['slug' => 'makler', 'category' => SpecialistCategory::Realtor, 'name' => ['az' => 'Makler (əmlak agenti)', 'ru' => 'Риелтор (агент по недвижимости)', 'en' => 'Realtor (real-estate agent)'], 'sort_order' => 16],
        ] as $row) {
            SpecialistSpecialty::query()->firstOrCreate(['slug' => $row['slug']], $row + ['is_active' => true]);
        }
    }

    // ─── registration ─────────────────────────────────────────

    public function test_registering_as_a_foreman_assigns_the_briqadir_specialty(): void
    {
        $this->postJson('/register', $this->registration(['role' => 'master', 'kind' => 'foreman']))
            ->assertCreated()
            ->assertJsonPath('redirect', route('specialist.cabinet'));

        $user = User::query()->where('email', 'kind@example.com')->firstOrFail();

        $this->assertSame(UserRole::Master, $user->role);
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertSame('briqadir', $user->specialistProfile?->specialty?->slug);
        $this->assertSame(SpecialistCategory::Foreman, $user->specialistProfile->specialty->category);
        $this->assertSame('Bakı', $user->specialistProfile->city);
        $this->assertAuthenticatedAs($user);

        $this->get('/specialist/cabinet')->assertOk()->assertViewHas('kind', SpecialistCategory::Foreman);
    }

    public function test_registering_as_a_realtor_assigns_the_makler_specialty(): void
    {
        $this->postJson('/register', $this->registration(['role' => 'master', 'kind' => 'realtor']))
            ->assertCreated()
            ->assertJsonPath('redirect', route('specialist.cabinet'));

        $user = User::query()->where('email', 'kind@example.com')->firstOrFail();

        $this->assertSame(UserRole::Master, $user->role);
        $this->assertSame('makler', $user->specialistProfile?->specialty?->slug);
    }

    public function test_the_kind_wins_over_a_posted_specialty_and_needs_no_select(): void
    {
        $tiler = $this->trade('kafelci-kinds');

        // No specialty at all is fine for a kind card…
        $this->postJson('/register', $this->registration(['role' => 'master', 'kind' => 'foreman']))->assertCreated();
        $this->post('/logout');

        // …and a stray specialty id does not turn a Makler into a tiler.
        $this->postJson('/register', $this->registration([
            'role' => 'master',
            'kind' => 'realtor',
            'email' => 'kind2@example.com',
            'phone' => '+994507654321',
            'specialist_specialty_id' => $tiler->id,
        ]))->assertCreated();

        $this->assertSame('makler', User::query()->where('email', 'kind2@example.com')->firstOrFail()->specialistProfile->specialty->slug);
    }

    public function test_the_card_name_posted_as_the_role_is_folded_into_master(): void
    {
        $this->postJson('/register', $this->registration(['role' => 'foreman']))->assertCreated();

        $user = User::query()->where('email', 'kind@example.com')->firstOrFail();
        $this->assertSame(UserRole::Master, $user->role);
        $this->assertSame('briqadir', $user->specialistProfile->specialty->slug);
    }

    public function test_the_master_card_still_requires_a_specialty(): void
    {
        $this->postJson('/register', $this->registration(['role' => 'master']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['specialist_specialty_id']);
    }

    public function test_a_missing_city_reads_as_plain_copy_not_the_raw_role(): void
    {
        $data = $this->registration(['role' => 'master', 'kind' => 'realtor']);
        unset($data['city']);

        $response = $this->postJson('/register', $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['city']);

        $this->assertSame(t('register.form.city_required'), $response->json('errors.city.0'));
        $this->assertStringNotContainsString('master', $response->json('errors.city.0'));
    }

    public function test_a_kind_whose_specialty_was_deactivated_is_refused(): void
    {
        SpecialistSpecialty::query()->where('slug', 'briqadir')->update(['is_active' => false]);

        $this->postJson('/register', $this->registration(['role' => 'master', 'kind' => 'foreman']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['kind']);

        $this->assertDatabaseMissing('users', ['email' => 'kind@example.com']);
    }

    public function test_register_page_offers_five_cards_and_keeps_the_kinds_out_of_the_select(): void
    {
        $tiler = $this->trade('kafelci-kinds');
        $briqadir = SpecialistSpecialty::query()->where('slug', 'briqadir')->firstOrFail();
        $makler = SpecialistSpecialty::query()->where('slug', 'makler')->firstOrFail();

        $response = $this->get('/register')->assertOk();
        foreach (['buyer', 'seller', 'master', 'foreman', 'realtor'] as $card) {
            $response->assertSee('data-role="'.$card.'"', false);
        }

        $options = $response->viewData('specialties');
        $this->assertTrue($options->has($tiler->id));
        $this->assertFalse($options->has($briqadir->id), 'Briqadir has its own card');
        $this->assertFalse($options->has($makler->id), 'Makler has its own card');

        // The shared info note lists every professional card, not just "seller,master".
        $response->assertSee('data-for="seller,master,foreman,realtor"', false);
    }

    public function test_role_query_preselects_the_foreman_and_realtor_cards(): void
    {
        $this->get('/register?role=foreman')->assertOk()
            ->assertSee('data-role="foreman" data-sel="true"', false)
            ->assertSee('name="role" value="foreman"', false);

        $this->get('/register?role=realtor')->assertOk()
            ->assertSee('data-role="realtor" data-sel="true"', false);
    }

    // ─── cabinet details ──────────────────────────────────────

    public function test_a_foreman_saves_crew_details_and_only_crew_keys_are_kept(): void
    {
        $tiler = $this->trade('kafelci-kinds');
        $painter = $this->trade('boyaci-kinds');
        $profile = $this->kindProfile('briqadir', 'foreman@example.com');

        $this->actingAs($profile->user)->putJson('/specialist/cabinet', $this->cabinetPayload($profile, [
            'crew_size' => '8',
            'crew_trades' => [(string) $tiler->id, $painter->id],
            'crew_members' => [
                ['name' => 'Əli Məmmədov', 'specialty_id' => (string) $tiler->id, 'experience_years' => '12'],
                ['name' => 'Vüqar Həsənov', 'specialty_id' => null, 'experience_years' => null],
            ],
            'takes_full_projects' => true,
            // A realtor key smuggled in must not be stored on a foreman.
            'agency_name' => 'Ev Əmlak',
        ]))->assertOk();

        $details = $profile->fresh()->details;

        $this->assertEqualsCanonicalizing(['crew_size', 'crew_trades', 'crew_members', 'takes_full_projects'], array_keys($details));
        $this->assertSame(8, $details['crew_size']);
        $this->assertSame([$tiler->id, $painter->id], $details['crew_trades']);
        $this->assertTrue($details['takes_full_projects']);
        $this->assertSame(
            ['name' => 'Əli Məmmədov', 'specialty_id' => $tiler->id, 'experience_years' => 12],
            $details['crew_members'][0],
        );
        $this->assertCount(2, $details['crew_members']);
    }

    public function test_a_realtor_saves_market_details(): void
    {
        $profile = $this->kindProfile('makler', 'realtor@example.com');

        $this->actingAs($profile->user)->putJson('/specialist/cabinet', $this->cabinetPayload($profile, [
            'agency_name' => 'Ev Əmlak',
            'deal_types' => ['rent', 'sale'],
            'property_types' => ['apartment', 'land'],
            'service_areas' => 'Nəsimi, Yasamal',
            'license_no' => 'AZ-123',
            'crew_size' => 5,
        ]))->assertOk();

        $details = $profile->fresh()->details;

        // A MySQL json column reorders object keys, so compare the set, not the order.
        $this->assertEqualsCanonicalizing(['agency_name', 'deal_types', 'property_types', 'service_areas', 'license_no'], array_keys($details));
        $this->assertSame('Ev Əmlak', $details['agency_name']);
        // Canonical order, whatever order the boxes were ticked in.
        $this->assertSame(['sale', 'rent'], $details['deal_types']);
        $this->assertSame(['apartment', 'land'], $details['property_types']);
    }

    public function test_details_are_validated_per_kind(): void
    {
        $foreman = $this->kindProfile('briqadir', 'foreman@example.com');
        $makler = SpecialistSpecialty::query()->where('slug', 'makler')->firstOrFail();

        $this->actingAs($foreman->user)->putJson('/specialist/cabinet', $this->cabinetPayload($foreman, [
            'crew_size' => 500,
            // Only master-category trades can be in a crew.
            'crew_trades' => [$makler->id],
            'crew_members' => array_fill(0, 31, ['name' => 'X']),
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['details.crew_size', 'details.crew_trades.0', 'details.crew_members']);

        $realtor = $this->kindProfile('makler', 'realtor@example.com');

        $this->actingAs($realtor->user)->putJson('/specialist/cabinet', $this->cabinetPayload($realtor, [
            'deal_types' => ['barter'],
            'property_types' => ['castle'],
        ]))->assertUnprocessable()
            ->assertJsonValidationErrors(['details.deal_types.0', 'details.property_types.0']);
    }

    public function test_validation_messages_name_the_field_not_the_json_key(): void
    {
        $foreman = $this->kindProfile('briqadir', 'foreman@example.com');

        $response = $this->actingAs($foreman->user)->putJson('/specialist/cabinet', $this->cabinetPayload($foreman, [
            'crew_size' => 0,
        ]))->assertUnprocessable()->assertJsonValidationErrors(['details.crew_size']);

        $message = $response->json('errors')['details.crew_size'][0];
        $this->assertStringContainsString(t('specialist-cabinet.crew.size'), $message);
        $this->assertStringNotContainsString('details.', $message);
        $this->assertStringNotContainsString('crew size', $message);
    }

    public function test_a_realtor_must_pick_at_least_one_deal_type(): void
    {
        $realtor = $this->kindProfile('makler', 'realtor@example.com');

        $response = $this->actingAs($realtor->user)->putJson('/specialist/cabinet', $this->cabinetPayload($realtor, [
            'agency_name' => 'Ev Əmlak',
            'deal_types' => [],
            'property_types' => ['apartment'],
        ]))->assertUnprocessable()->assertJsonValidationErrors(['details.deal_types']);

        $this->assertSame(t('specialist-cabinet.realtor.deal_types_required'), $response->json('errors')['details.deal_types'][0]);
        $this->assertNull($realtor->fresh()->details);

        // A save that carries no details block at all (older client) is not blocked.
        $payload = $this->cabinetPayload($realtor, []);
        unset($payload['details']);
        $this->actingAs($realtor->user)->putJson('/specialist/cabinet', $payload)->assertOk();
    }

    public function test_a_crew_member_without_a_name_is_rejected_not_dropped(): void
    {
        $tiler = $this->trade('kafelci-kinds');
        $profile = $this->kindProfile('briqadir', 'foreman@example.com');

        $response = $this->actingAs($profile->user)->putJson('/specialist/cabinet', $this->cabinetPayload($profile, [
            'crew_size' => 3,
            'crew_members' => [
                ['name' => 'Əli Məmmədov', 'specialty_id' => $tiler->id, 'experience_years' => 12],
                ['name' => '', 'specialty_id' => $tiler->id, 'experience_years' => 5],
            ],
        ]))->assertUnprocessable()->assertJsonValidationErrors(['details.crew_members.1.name']);

        $this->assertSame(t('specialist-cabinet.crew.member_name_required'), $response->json('errors')['details.crew_members.1.name'][0]);
        $this->assertNull($profile->fresh()->details, 'nothing is saved while a member is invalid');
    }

    public function test_switching_to_a_plain_trade_clears_the_details(): void
    {
        $tiler = $this->trade('kafelci-kinds');
        $profile = $this->kindProfile('briqadir', 'foreman@example.com', ['crew_size' => 4, 'crew_trades' => [$tiler->id]]);

        $this->actingAs($profile->user)->putJson('/specialist/cabinet', [
            ...$this->cabinetPayload($profile, ['crew_size' => 4]),
            'specialist_specialty_id' => $tiler->id,
        ])->assertOk();

        $this->assertNull($profile->fresh()->details);
    }

    public function test_a_save_without_details_leaves_the_crew_alone(): void
    {
        $profile = $this->kindProfile('briqadir', 'foreman@example.com', ['crew_size' => 4]);

        $payload = $this->cabinetPayload($profile, []);
        unset($payload['details']);

        $this->actingAs($profile->user)->putJson('/specialist/cabinet', $payload)->assertOk();

        $this->assertSame(4, $profile->fresh()->details['crew_size']);
    }

    public function test_completeness_counts_the_kind_block(): void
    {
        $tiler = $this->trade('kafelci-kinds');
        // With a photo on file the kind block is the next item after the specialty.
        $photo = ['avatar_path' => 'specialists/avatars/a.jpg'];
        $empty = $this->kindProfile('briqadir', 'foreman@example.com', null, $photo);
        $filled = $this->kindProfile('briqadir', 'foreman2@example.com', ['crew_size' => 6, 'crew_trades' => [$tiler->id]], $photo);
        $realtor = $this->kindProfile('makler', 'realtor@example.com', ['deal_types' => ['sale']], $photo);
        $tilerProfile = $this->kindProfile('kafelci-kinds', 'tiler@example.com', null, $photo);

        $this->assertSame('specialist-cabinet.crew.heading', ProfileCompleteness::forSpecialist($empty->user->fresh())['next']);
        $this->assertNotSame('specialist-cabinet.crew.heading', ProfileCompleteness::forSpecialist($filled->user->fresh())['next']);
        // Deal types alone are not enough: property types too.
        $this->assertSame('specialist-cabinet.realtor.heading', ProfileCompleteness::forSpecialist($realtor->user->fresh())['next']);
        // A plain trade gets no extra item.
        $this->assertNotContains(
            ProfileCompleteness::forSpecialist($tilerProfile->user->fresh())['next'],
            ['specialist-cabinet.crew.heading', 'specialist-cabinet.realtor.heading'],
        );
        $this->assertGreaterThan(
            ProfileCompleteness::forSpecialist($empty->user->fresh())['percent'],
            ProfileCompleteness::forSpecialist($filled->user->fresh())['percent'],
        );
    }

    // ─── directory ────────────────────────────────────────────

    public function test_type_filters_foremen_and_realtors(): void
    {
        $foreman = $this->kindProfile('briqadir', 'foreman@example.com', ['crew_size' => 8]);
        $realtor = $this->kindProfile('makler', 'realtor@example.com', ['agency_name' => 'Ev Əmlak']);
        $tiler = $this->kindProfile('kafelci-kinds', 'tiler@example.com');

        $this->get('/specialists?type=foreman')->assertOk()
            ->assertSee($foreman->user->name)
            ->assertDontSee($realtor->user->name)
            ->assertDontSee($tiler->user->name)
            ->assertSee(t('specialists.card.crew_size', ['count' => 8]))
            ->assertSee('sp-chip-static', false);

        $this->get('/specialists?type=realtor')->assertOk()
            ->assertSee($realtor->user->name)
            ->assertDontSee($foreman->user->name)
            ->assertSee(t('specialists.card.agency', ['name' => 'Ev Əmlak']));
    }

    public function test_foremen_and_realtors_have_their_own_sections_in_order(): void
    {
        $this->kindProfile('kafelci-kinds', 'tiler@example.com');
        $this->kindProfile('briqadir', 'foreman@example.com');
        $this->kindProfile('makler', 'realtor@example.com');
        $this->kindProfile('tikinti-sirketi', 'company@example.com');

        $this->assertSame(
            [
                SpecialistCategoryGroup::Design,
                SpecialistCategoryGroup::Master,
                SpecialistCategoryGroup::Foreman,
                SpecialistCategoryGroup::Company,
                SpecialistCategoryGroup::Realtor,
            ],
            $this->get('/specialists')->assertOk()->viewData('groups')->pluck('group')->all(),
        );

        $this->assertSame(SpecialistCategoryGroup::Foreman, SpecialistCategory::Foreman->group());
        $this->assertSame(SpecialistCategoryGroup::Realtor, SpecialistCategory::Realtor->group());
    }

    public function test_the_empty_kind_sections_show_on_the_unfiltered_directory(): void
    {
        $this->kindProfile('kafelci-kinds', 'tiler@example.com');

        $groups = $this->get('/specialists')->assertOk()->viewData('groups')->pluck('group')->all();

        $this->assertContains(SpecialistCategoryGroup::Foreman, $groups);
        $this->assertContains(SpecialistCategoryGroup::Realtor, $groups);
    }

    // ─── public profile ───────────────────────────────────────

    public function test_the_foreman_profile_shows_the_crew_block(): void
    {
        $tiler = $this->trade('kafelci-kinds', 'Kafelçi KT');
        $profile = $this->kindProfile('briqadir', 'foreman@example.com', [
            'crew_size' => 8,
            'crew_trades' => [$tiler->id],
            'crew_members' => [['name' => 'Əli Məmmədov', 'specialty_id' => $tiler->id, 'experience_years' => 12]],
            'takes_full_projects' => true,
        ]);

        $this->get("/specialist/{$profile->id}")->assertOk()
            ->assertSee('data-kind="foreman"', false)
            ->assertSee(t('specialist.crew.size_value', ['count' => 8]))
            ->assertSee('Kafelçi KT')
            ->assertSee('Əli Məmmədov')
            ->assertSee(t('specialist.crew.member_years', ['years' => 12]))
            ->assertSee(t('specialist.crew.full_projects'));
    }

    public function test_the_realtor_profile_shows_the_makler_block(): void
    {
        $profile = $this->kindProfile('makler', 'realtor@example.com', [
            'agency_name' => 'Ev Əmlak',
            'deal_types' => ['daily_rent'],
            'property_types' => ['house'],
            'service_areas' => 'Nəsimi, Yasamal',
            'license_no' => 'AZ-123',
        ]);

        $this->get("/specialist/{$profile->id}")->assertOk()
            ->assertSee('data-kind="realtor"', false)
            ->assertSee('Ev Əmlak')
            ->assertSee(t('specialist-kinds.deal_type.daily_rent'))
            ->assertSee(t('specialist-kinds.property_type.house'))
            ->assertSee('Nəsimi, Yasamal')
            ->assertSee('AZ-123');
    }

    public function test_a_plain_trade_profile_has_no_kind_block(): void
    {
        $profile = $this->kindProfile('kafelci-kinds', 'tiler@example.com');

        $this->get("/specialist/{$profile->id}")->assertOk()->assertDontSee('pp-kind', false);
    }

    // ─── admin ────────────────────────────────────────────────

    public function test_admin_specialist_list_filters_by_kind(): void
    {
        $this->signInAsAdmin();
        $foreman = $this->kindProfile('briqadir', 'foreman@example.com');
        $realtor = $this->kindProfile('makler', 'realtor@example.com');
        $tiler = $this->kindProfile('kafelci-kinds', 'tiler@example.com');

        Livewire::test(ListSpecialists::class)
            ->assertCanSeeTableRecords([$foreman->user, $realtor->user, $tiler->user])
            ->filterTable('kind', SpecialistCategory::Foreman->value)
            ->assertCanSeeTableRecords([$foreman->user])
            ->assertCanNotSeeTableRecords([$realtor->user, $tiler->user]);

        Livewire::test(ListSpecialists::class)
            ->filterTable('kind', SpecialistCategory::Realtor->value)
            ->assertCanSeeTableRecords([$realtor->user])
            ->assertCanNotSeeTableRecords([$foreman->user, $tiler->user]);
    }

    public function test_admin_view_page_shows_the_crew(): void
    {
        $this->signInAsAdmin();
        $profile = $this->kindProfile('briqadir', 'foreman@example.com', [
            'crew_size' => 8,
            'crew_members' => [['name' => 'Əli Məmmədov', 'specialty_id' => null, 'experience_years' => 12]],
        ]);

        $this->get(SpecialistResource::getUrl('view', ['record' => $profile->user]))
            ->assertOk()
            ->assertSee('Əli Məmmədov');
    }

    public function test_the_specialty_admin_offers_the_new_categories(): void
    {
        $options = SpecialistCategory::options();

        $this->assertArrayHasKey('foreman', $options);
        $this->assertArrayHasKey('realtor', $options);

        // The admin «Növ» badge is one person: singular, not the plural section name.
        $this->assertSame('Briqadir', SpecialistCategory::Foreman->singularLabel());
        $this->assertSame('Makler', SpecialistCategory::Realtor->singularLabel());
    }

    // ─── helpers ──────────────────────────────────────────────

    /** @param array<string, mixed> $overrides */
    private function registration(array $overrides = []): array
    {
        return [
            'first_name' => 'Kənan',
            'last_name' => 'Əliyev',
            'email' => 'kind@example.com',
            'phone' => '+994501234567',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'terms' => true,
            'city' => 'Bakı',
            ...$overrides,
        ];
    }

    /** @param array<string, mixed> $details */
    private function cabinetPayload(SpecialistProfile $profile, array $details): array
    {
        return [
            'first_name' => 'Test',
            'last_name' => 'Usta',
            'specialist_specialty_id' => $profile->specialist_specialty_id,
            'experience_years' => 10,
            'city' => 'Bakı',
            'phone' => '+994500000000',
            'whatsapp' => '',
            'about' => 'Haqqımda',
            'skills' => [],
            'details' => $details,
        ];
    }

    private function trade(string $slug, string $name = 'Kafelçi'): SpecialistSpecialty
    {
        return SpecialistSpecialty::query()->firstOrCreate(
            ['slug' => $slug],
            ['name' => $name.' '.$slug, 'category' => SpecialistCategory::Master, 'is_active' => true],
        );
    }

    /**
     * A listable profile on the specialty with `$slug` — the migration-seeded briqadir /
     * makler / tikinti-sirketi rows, or a master-category trade created on the fly.
     *
     * @param  array<string, mixed>|null  $details
     * @param  array<string, mixed>  $attributes
     */
    private function kindProfile(string $slug, string $email, ?array $details = null, array $attributes = []): SpecialistProfile
    {
        $specialty = SpecialistSpecialty::query()->where('slug', $slug)->first() ?? $this->trade($slug);
        $user = User::factory()->master()->create(['email' => $email, 'status' => UserStatus::Active]);

        return SpecialistProfile::query()->create([
            'user_id' => $user->id,
            'specialist_specialty_id' => $specialty->id,
            'city' => 'Bakı',
            'phone' => '+994500000000',
            'about' => 'Test profili.',
            'details' => $details,
            ...$attributes,
        ]);
    }

    private function signInAsAdmin(): User
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin, 'admin');

        return $admin;
    }
}
