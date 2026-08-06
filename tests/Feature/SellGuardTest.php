<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_master_is_redirected_from_sell_to_business_register(): void
    {
        $master = User::factory()->master()->create();

        $this->actingAs($master)
            ->get('/sell')
            ->assertRedirect(route('business.register'))
            ->assertSessionHas('flash_error');
    }

    public function test_buyer_is_redirected_from_sell_to_business_register(): void
    {
        $buyer = User::factory()->buyer()->create();

        $this->actingAs($buyer)
            ->get('/sell')
            ->assertRedirect(route('business.register'));
    }

    public function test_master_cannot_post_a_product(): void
    {
        $master = User::factory()->master()->create();

        $this->actingAs($master)
            ->postJson('/sell', ['name' => 'Hack məhsul', 'price' => 5])
            ->assertForbidden()
            ->assertJson(['success' => false]);
    }

    public function test_seller_and_guest_can_open_sell_page(): void
    {
        $this->get('/sell')->assertOk();

        $seller = User::factory()->seller()->create();
        $this->actingAs($seller)->get('/sell')->assertOk();
    }

    public function test_flash_error_is_localized(): void
    {
        $master = User::factory()->master()->create();

        $response = $this->actingAs($master)->withSession(['locale' => 'ru'])->get('/sell');
        $this->assertStringContainsString('бизнес', session('flash_error'));
    }
}
