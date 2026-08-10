<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * /sell used to advertise "Qeydiyyat tələb olunmur" ("no registration required")
 * while POST /sell sits behind `auth` and additionally rejects non-sellers. The
 * page copy must match what the endpoint actually does.
 */
class SellPageHonestyTest extends TestCase
{
    use RefreshDatabase;

    public function test_sell_page_does_not_promise_that_no_registration_is_needed(): void
    {
        $html = $this->get('/sell')->assertOk()->getContent();

        $this->assertStringNotContainsString('Qeydiyyat tələb olunmur', $html);
        $this->assertStringNotContainsString('No registration required', $html);
        $this->assertStringNotContainsString('Регистрация не нужна', $html);

        // ... and it states the real requirement instead.
        $this->assertStringContainsString('satıcı hesabı lazımdır', $html);
    }

    public function test_posting_a_listing_really_does_require_authentication(): void
    {
        $this->post('/sell', [])->assertRedirect('/login');
    }

    public function test_an_authenticated_buyer_is_still_refused(): void
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer)
            ->postJson('/sell', [])
            ->assertForbidden();
    }
}
