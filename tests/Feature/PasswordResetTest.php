<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_sends_a_reset_link(): void
    {
        Notification::fake();
        $user = $this->makeUser(UserStatus::Active);

        $this->postJson('/forgot-password', ['email' => $user->email])
            ->assertOk()
            ->assertJsonPath('success', true);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_forgot_password_does_not_reveal_whether_an_address_exists(): void
    {
        Notification::fake();
        $user = $this->makeUser(UserStatus::Active);

        $known = $this->postJson('/forgot-password', ['email' => $user->email]);
        $unknown = $this->postJson('/forgot-password', ['email' => 'nobody@example.com']);

        $this->assertSame($known->json('message'), $unknown->json('message'));
        Notification::assertCount(1);
    }

    public function test_a_user_can_reset_their_password_and_is_signed_in(): void
    {
        $user = $this->makeUser(UserStatus::Active);
        $token = Password::createToken($user);

        $this->postJson('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewSecret123',
            'password_confirmation' => 'NewSecret123',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertTrue(Hash::check('NewSecret123', $user->fresh()->password));
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_pending_user_cannot_reset_their_password(): void
    {
        $user = $this->makeUser(UserStatus::Pending, UserRole::Seller);
        $token = Password::createToken($user);

        $this->postJson('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewSecret123',
            'password_confirmation' => 'NewSecret123',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->assertTrue(Hash::check('secure-password', $user->fresh()->password));
        $this->assertGuest();
    }

    public function test_an_invalid_token_is_rejected(): void
    {
        $user = $this->makeUser(UserStatus::Active);

        $this->postJson('/reset-password', [
            'token' => 'not-a-real-token',
            'email' => $user->email,
            'password' => 'NewSecret123',
            'password_confirmation' => 'NewSecret123',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->assertGuest();
    }

    private function makeUser(UserStatus $status, UserRole $role = UserRole::Buyer): User
    {
        return User::create([
            'name' => 'Test User',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'reset-user@example.com',
            'phone' => '+994501112233',
            'password' => 'secure-password',
            'role' => $role,
            'status' => $status,
            'terms_accepted' => true,
        ]);
    }
}
