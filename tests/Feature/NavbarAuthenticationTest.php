<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavbarAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sign_in_link_opens_the_login_modal(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('class="txt" data-login', false);
    }

    public function test_authenticated_user_name_does_not_open_the_login_modal(): void
    {
        $user = User::factory()->buyer()->create(['first_name' => 'Aysel']);

        $response = $this->actingAs($user)->get('/')->assertOk();

        $response->assertSee('id="navAccountBtn"', false)
            ->assertSee('class="txt nav-user max-[900px]:hidden">Aysel</span>', false)
            ->assertDontSee('class="txt" data-login', false);
    }
}
