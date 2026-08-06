<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password = null;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('+99450#######'),
            'role' => UserRole::Buyer,
            'status' => UserStatus::Active,
            'approved_at' => now(),
            'terms_accepted' => true,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function buyer(): static
    {
        return $this->state(fn (): array => ['role' => UserRole::Buyer]);
    }

    public function seller(): static
    {
        return $this->state(fn (): array => ['role' => UserRole::Seller]);
    }

    public function master(): static
    {
        return $this->state(fn (): array => ['role' => UserRole::Master]);
    }

    public function admin(): static
    {
        return $this->state(fn (): array => [
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => UserStatus::Pending,
            'approved_at' => null,
        ]);
    }
}
