<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The buyer's own profile form. `users.phone` is UNIQUE and `users.name` is what
 * the navbar, the checkout prefill and the seller's order view read — neither was
 * handled here, so a taken number came back as a 500 and a rename never reached
 * the rest of the site.
 */
class AccountProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_phone_already_taken_is_a_validation_error_not_a_server_error(): void
    {
        User::factory()->create(['phone' => '+994500000001']);
        $user = User::factory()->create(['phone' => '+994500000002']);

        $this->actingAs($user)
            ->from('/account')
            ->put('/account', [
                'first_name' => 'Nigar',
                'last_name' => 'Əliyeva',
                'phone' => '+994500000001',
            ])
            ->assertRedirect('/account')
            ->assertSessionHasErrors('phone');

        $this->assertSame('+994500000002', $user->fresh()->phone);
    }

    public function test_keeping_your_own_phone_is_allowed(): void
    {
        $user = User::factory()->create(['phone' => '+994500000003']);

        $this->actingAs($user)
            ->put('/account', [
                'first_name' => 'Nigar',
                'last_name' => 'Əliyeva',
                'phone' => '+994500000003',
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_renaming_also_updates_the_name_the_rest_of_the_site_reads(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Old',
            'last_name' => 'Name',
            'name' => 'Old Name',
        ]);

        $this->actingAs($user)->put('/account', [
            'first_name' => 'Nigar',
            'last_name' => 'Əliyeva',
            'phone' => null,
        ])->assertSessionHasNoErrors();

        $this->assertSame('Nigar Əliyeva', $user->fresh()->name);
    }

    public function test_a_profile_without_a_surname_still_gets_a_clean_name(): void
    {
        $user = User::factory()->create(['name' => 'Old Name']);

        $this->actingAs($user)->put('/account', [
            'first_name' => 'Nigar',
            'last_name' => null,
            'phone' => null,
        ])->assertSessionHasNoErrors();

        $this->assertSame('Nigar', $user->fresh()->name);
    }
}
