<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PortfolioStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\SpecialistPortfolioItem;
use App\Models\SpecialistSpecialty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_registration_creates_an_active_buyer_without_professional_profile(): void
    {
        $this->postJson('/register', $this->registrationData(UserRole::Buyer))
            ->assertCreated()
            ->assertJsonPath('success', true);

        $user = User::query()->where('email', 'new-user@example.com')->firstOrFail();

        $this->assertSame(UserRole::Buyer, $user->role);
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertNotNull($user->approved_at);
        $this->assertNull($user->sellerProfile);
        $this->assertNull($user->specialistProfile);

        // A buyer needs no admin approval, so registration signs them straight in.
        $this->assertAuthenticatedAs($user);
    }

    public function test_buyer_can_log_in_immediately_after_registering(): void
    {
        $this->postJson('/register', $this->registrationData(UserRole::Buyer))->assertCreated();
        $this->post('/logout');

        $this->postJson('/login', [
            'identifier' => 'new-user@example.com',
            'password' => 'secure-password',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertAuthenticated();
    }

    public function test_seller_registration_creates_an_active_seller_signed_in_immediately(): void
    {
        $data = $this->registrationData(UserRole::Seller) + ['company_name' => 'ARCHI Store'];

        $this->postJson('/register', $data)
            ->assertCreated()
            ->assertJsonPath('redirect', route('business.profile'));

        $user = User::query()->where('email', 'new-user@example.com')->firstOrFail();

        $this->assertSame(UserRole::Seller, $user->role);
        // No admin approval for the account itself — only for what the seller publishes.
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertNotNull($user->approved_at);
        $this->assertSame('ARCHI Store', $user->sellerProfile?->brand_name);
        $this->assertAuthenticatedAs($user);
    }

    public function test_freshly_registered_seller_reaches_the_business_cabinet(): void
    {
        $data = $this->registrationData(UserRole::Seller) + ['company_name' => 'ARCHI Store'];
        $this->postJson('/register', $data)->assertCreated();

        $this->get('/business/profile')->assertOk();
        $this->get('/business/profile/company')->assertOk();
    }

    public function test_master_registration_creates_specialist_profile(): void
    {
        $specialty = SpecialistSpecialty::query()->create(['name' => 'Elektrik', 'slug' => 'elektrik']);
        $data = $this->registrationData(UserRole::Master) + [
            // Browser select values arrive as strings; the controller normalizes this safely.
            'specialist_specialty_id' => (string) $specialty->id,
            'city' => 'Bakı',
        ];

        $this->postJson('/register', $data)->assertCreated();

        $user = User::query()->where('email', 'new-user@example.com')->firstOrFail();

        $this->assertSame(UserRole::Master, $user->role);
        $this->assertSame('Elektrik', $user->specialistProfile?->craft);
        $this->assertTrue($user->specialistProfile?->specialty->is($specialty));
        $this->assertSame('Bakı', $user->specialistProfile?->city);
        // No admin approval for the account itself — only for the portfolio they publish.
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertNotNull($user->approved_at);
        $this->assertAuthenticatedAs($user);
    }

    public function test_freshly_registered_master_reaches_the_specialist_cabinet(): void
    {
        $this->registerMaster();

        // A brand-new master has an all-but-empty profile row; the cabinet must render it.
        $this->get('/specialist/cabinet')->assertOk();
        $this->get('/specialist/cabinet/portfolio')->assertOk();
        $this->get('/specialist/onboarding')->assertOk();
        $this->get('/specialist/owner')->assertOk();
    }

    public function test_registration_does_not_notify_anybody_about_an_approval(): void
    {
        Notification::fake();

        $this->registerMaster();

        // Nobody approved this account, so the "your account was approved" mail that the
        // admin approve action sends must not go out on self-registration.
        Notification::assertNothingSent();
    }

    public function test_portfolio_of_a_freshly_registered_master_still_waits_for_approval(): void
    {
        Storage::fake('public');
        $this->registerMaster();

        $this->post('/specialist/cabinet/portfolio', [
            'items' => json_encode([
                ['id' => null, 'title' => 'İlk iş', 'file_index' => 0],
            ], JSON_THROW_ON_ERROR),
            'images' => [UploadedFile::fake()->image('work.jpg')],
        ], ['Accept' => 'application/json'])->assertOk();

        $item = SpecialistPortfolioItem::query()->firstOrFail();

        $this->assertSame(PortfolioStatus::Pending, $item->status);
        $this->assertNull($item->approved_at);
    }

    public function test_an_account_blocked_or_rejected_by_an_admin_still_cannot_log_in(): void
    {
        $data = $this->registrationData(UserRole::Seller) + ['company_name' => 'ARCHI Store'];
        $this->postJson('/register', $data)->assertCreated();
        $this->post('/logout');

        $user = User::query()->where('email', 'new-user@example.com')->firstOrFail();
        $credentials = ['identifier' => 'new-user@example.com', 'password' => 'secure-password'];

        $user->block();
        $this->postJson('/login', $credentials)->assertUnprocessable()->assertJsonValidationErrors('identifier');
        $this->assertGuest();

        $user->reject('Sənədlər natamamdır');
        $this->postJson('/login', $credentials)->assertUnprocessable()->assertJsonValidationErrors('identifier');
        $this->assertGuest();

        $user->update(['status' => UserStatus::Pending]);
        $this->postJson('/login', $credentials)->assertUnprocessable()->assertJsonValidationErrors('identifier');
        $this->assertGuest();

        // ...and an admin can let them back in.
        $user->approve();
        $this->postJson('/login', $credentials)->assertOk();
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_master_registration_normalizes_a_city_slug_to_its_label(): void
    {
        $specialty = SpecialistSpecialty::query()->create(['name' => 'Elektrik', 'slug' => 'elektrik']);
        $data = $this->registrationData(UserRole::Master) + [
            'specialist_specialty_id' => (string) $specialty->id,
            // The register select submits the canonical slug, not the label.
            'city' => 'baku',
        ];

        $this->postJson('/register', $data)->assertCreated();

        $user = User::query()->where('email', 'new-user@example.com')->firstOrFail();

        $this->assertSame('Bakı', $user->specialistProfile?->city);
    }

    public function test_master_registration_rejects_an_unknown_city(): void
    {
        $specialty = SpecialistSpecialty::query()->create(['name' => 'Elektrik', 'slug' => 'elektrik']);
        $data = $this->registrationData(UserRole::Master) + [
            'specialist_specialty_id' => (string) $specialty->id,
            'city' => 'Atlantis',
        ];

        $this->postJson('/register', $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('city');
    }

    public function test_public_registration_cannot_create_an_admin(): void
    {
        $this->postJson('/register', $this->registrationData(UserRole::Admin))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'new-user@example.com']);
    }

    /**
     * Registers a master through the public endpoint and leaves them signed in.
     */
    private function registerMaster(): User
    {
        $specialty = SpecialistSpecialty::query()->create(['name' => 'Elektrik', 'slug' => 'elektrik']);

        $this->postJson('/register', $this->registrationData(UserRole::Master) + [
            'specialist_specialty_id' => (string) $specialty->id,
            'city' => 'baku',
        ])->assertCreated()->assertJsonPath('redirect', route('specialist.cabinet'));

        return User::query()->where('email', 'new-user@example.com')->firstOrFail();
    }

    private function registrationData(UserRole $role): array
    {
        return [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'new-user@example.com',
            'phone' => '+994501234567',
            'password' => 'secure-password',
            'password_confirmation' => 'secure-password',
            'role' => $role->value,
            'terms' => true,
        ];
    }
}
