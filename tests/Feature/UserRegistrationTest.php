<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\SpecialistSpecialty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_registration_creates_pending_buyer_without_professional_profile(): void
    {
        $this->postJson('/register', $this->registrationData(UserRole::Buyer))
            ->assertCreated()
            ->assertJsonPath('success', true);

        $user = User::query()->where('email', 'new-user@example.com')->firstOrFail();

        $this->assertSame(UserRole::Buyer, $user->role);
        $this->assertSame(UserStatus::Pending, $user->status);
        $this->assertNull($user->sellerProfile);
        $this->assertNull($user->specialistProfile);
    }

    public function test_seller_registration_creates_seller_profile(): void
    {
        $data = $this->registrationData(UserRole::Seller) + ['company_name' => 'ARCHI Store'];

        $this->postJson('/register', $data)->assertCreated();

        $user = User::query()->where('email', 'new-user@example.com')->firstOrFail();

        $this->assertSame(UserRole::Seller, $user->role);
        $this->assertSame('ARCHI Store', $user->sellerProfile?->brand_name);
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
    }

    public function test_public_registration_cannot_create_an_admin(): void
    {
        $this->postJson('/register', $this->registrationData(UserRole::Admin))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'new-user@example.com']);
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
