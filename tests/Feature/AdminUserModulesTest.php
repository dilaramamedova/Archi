<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\AdminResource;
use App\Filament\Resources\AdminResource\Pages\CreateAdmin;
use App\Filament\Resources\SellerResource;
use App\Filament\Resources\SellerResource\Pages\CreateSeller;
use App\Filament\Resources\SpecialistResource;
use App\Filament\Resources\SpecialistResource\Pages\CreateSpecialist;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Models\SpecialistSpecialty;
use App\Models\User;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUserModulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_each_resource_only_queries_its_own_role(): void
    {
        User::factory()->buyer()->create();
        User::factory()->seller()->create();
        User::factory()->master()->create();
        User::factory()->admin()->create();

        $this->assertSame([UserRole::Buyer], UserResource::getEloquentQuery()->pluck('role')->unique()->values()->all());
        $this->assertSame([UserRole::Seller], SellerResource::getEloquentQuery()->pluck('role')->unique()->values()->all());
        $this->assertSame([UserRole::Master], SpecialistResource::getEloquentQuery()->pluck('role')->unique()->values()->all());
        $this->assertSame([UserRole::Admin], AdminResource::getEloquentQuery()->pluck('role')->unique()->values()->all());
    }

    public function test_resource_data_always_enforces_its_role_and_full_name(): void
    {
        $data = SellerResource::prepareUserData([
            'first_name' => 'Aysel',
            'last_name' => 'Məmmədova',
            'role' => UserRole::Admin->value,
            'status' => UserStatus::Active->value,
        ]);

        $this->assertSame('Aysel Məmmədova', $data['name']);
        $this->assertSame(UserRole::Seller, $data['role']);
        $this->assertNotNull($data['approved_at']);
    }

    public function test_only_active_admins_can_access_the_admin_panel(): void
    {
        $panel = Filament::getPanel('admin');
        $admin = User::factory()->admin()->create();
        $buyer = User::factory()->buyer()->create();
        $blockedAdmin = User::factory()->admin()->create(['status' => UserStatus::Blocked]);

        $this->assertTrue($admin->canAccessPanel($panel));
        $this->assertFalse($buyer->canAccessPanel($panel));
        $this->assertFalse($blockedAdmin->canAccessPanel($panel));

        $this->actingAs($admin, 'admin')->get('/admin')->assertOk();
    }

    public function test_active_admin_can_log_in_to_filament(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'panel-admin@example.com']);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $admin->email,
                'password' => 'password',
                'remember' => false,
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_non_admin_cannot_log_in_to_filament(): void
    {
        $buyer = User::factory()->buyer()->create(['email' => 'buyer-login@example.com']);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $buyer->email,
                'password' => 'password',
                'remember' => false,
            ])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest('admin');
    }

    public function test_admin_cannot_use_the_public_customer_login(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'admin-public-login@example.com']);

        $this->postJson('/login', [
            'identifier' => $admin->email,
            'password' => 'password',
        ])->assertUnprocessable();

        $this->assertGuest('web');
    }

    public function test_admin_cannot_delete_self_or_the_last_active_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin, 'admin');

        $this->assertFalse(AdminResource::canDelete($admin));

        $secondAdmin = User::factory()->admin()->create();
        $this->assertTrue(AdminResource::canDelete($secondAdmin));
    }

    public function test_status_workflow_approve_reject_block_and_unblock(): void
    {
        $user = User::factory()->buyer()->pending()->create();

        $user->approve();
        $this->assertSame(UserStatus::Active, $user->fresh()->status);
        $this->assertNotNull($user->fresh()->approved_at);

        $user->reject('Sənəd natamamdır');
        $this->assertSame(UserStatus::Rejected, $user->fresh()->status);
        $this->assertSame('Sənəd natamamdır', $user->fresh()->rejection_reason);

        $user->approve();
        $user->block();
        $this->assertSame(UserStatus::Blocked, $user->fresh()->status);
    }

    public function test_buyer_admin_crud_create_update_and_delete(): void
    {
        $this->signInAsAdmin();

        Livewire::test(CreateUser::class)
            ->fillForm($this->userFormData('buyer@example.com', '+994501111111'))
            ->call('create')
            ->assertHasNoFormErrors();

        $buyer = User::query()->where('email', 'buyer@example.com')->firstOrFail();
        $this->assertSame(UserRole::Buyer, $buyer->role);

        Livewire::test(EditUser::class, ['record' => $buyer->getRouteKey()])
            ->fillForm(['first_name' => 'Yenilənmiş', 'last_name' => 'İstifadəçi'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Yenilənmiş İstifadəçi', $buyer->fresh()->name);

        Livewire::test(EditUser::class, ['record' => $buyer->getRouteKey()])
            ->callAction('delete');

        $this->assertModelMissing($buyer);
    }

    public function test_seller_crud_creates_seller_profile(): void
    {
        $this->signInAsAdmin();

        Livewire::test(CreateSeller::class)
            ->fillForm($this->userFormData('seller@example.com', '+994502222222') + [
                'sellerProfile' => [
                    'brand_name' => 'Test Store',
                    'city' => 'Bakı',
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $seller = User::query()->where('email', 'seller@example.com')->firstOrFail();
        $this->assertSame(UserRole::Seller, $seller->role);
        $this->assertSame('Test Store', $seller->sellerProfile?->brand_name);
    }

    public function test_specialist_crud_creates_specialist_profile(): void
    {
        $this->signInAsAdmin();
        $specialty = SpecialistSpecialty::query()->create(['name' => 'Santexnik', 'slug' => 'santexnik']);

        Livewire::test(CreateSpecialist::class)
            ->fillForm($this->userFormData('master@example.com', '+994503333333') + [
                'specialistProfile' => [
                    'specialist_specialty_id' => $specialty->id,
                    'city' => 'Gəncə',
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $master = User::query()->where('email', 'master@example.com')->firstOrFail();
        $this->assertSame(UserRole::Master, $master->role);
        $this->assertSame('Santexnik', $master->specialistProfile?->craft);
    }

    public function test_admin_crud_creates_an_active_admin(): void
    {
        $this->signInAsAdmin();

        Livewire::test(CreateAdmin::class)
            ->fillForm($this->userFormData('second-admin@example.com', '+994504444444'))
            ->call('create')
            ->assertHasNoFormErrors();

        $admin = User::query()->where('email', 'second-admin@example.com')->firstOrFail();
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertSame(UserStatus::Active, $admin->status);
        $this->assertTrue($admin->canAccessPanel(Filament::getPanel('admin')));
    }

    private function signInAsAdmin(): User
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin, 'admin');

        return $admin;
    }

    private function userFormData(string $email, string $phone): array
    {
        return [
            'first_name' => 'Test',
            'last_name' => 'Hesab',
            'email' => $email,
            'phone' => $phone,
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'status' => UserStatus::Active->value,
        ];
    }
}
