<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\SpecialistController;
use App\Models\SpecialistProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SpecialistPhonePrivacyTest extends TestCase
{
    use RefreshDatabase;

    private const PHONE = '+994559876543';

    protected function setUp(): void
    {
        parent::setUp();

        // The phone endpoint lives in routes/web.php, which another engineer owns; the
        // route line is registered here so the action can be covered meanwhile.
        Route::get('/api/specialist/{specialist}/phone', [SpecialistController::class, 'phone'])
            ->middleware(['web', 'auth', 'throttle:20,1'])
            ->name('api.specialist.phone');
    }

    public function test_guest_never_receives_the_phone_number_in_the_profile_html(): void
    {
        $specialist = $this->specialist();

        $response = $this->get('/specialist/'.$specialist->id);

        $response->assertOk();
        $response->assertDontSee(self::PHONE);
        $response->assertDontSee('0559876543');
        $response->assertDontSee('data-phone=', false);
    }

    public function test_authenticated_visitor_does_not_receive_the_phone_in_the_html_either(): void
    {
        $specialist = $this->specialist();

        $response = $this->actingAs(User::factory()->buyer()->create())
            ->get('/specialist/'.$specialist->id);

        $response->assertOk();
        $response->assertDontSee(self::PHONE);
        // Only the reveal URL is embedded, never the number.
        $response->assertSee('/api/specialist/'.$specialist->id.'/phone', false);
    }

    public function test_guest_cannot_fetch_the_phone_endpoint(): void
    {
        $specialist = $this->specialist();

        $this->get('/api/specialist/'.$specialist->id.'/phone')->assertUnauthorized();
    }

    public function test_authenticated_user_can_fetch_the_phone(): void
    {
        $specialist = $this->specialist();

        $this->actingAs(User::factory()->buyer()->create())
            ->getJson('/api/specialist/'.$specialist->id.'/phone')
            ->assertOk()
            ->assertJson([
                'phone' => self::PHONE,
                'tel' => self::PHONE,
            ]);
    }

    public function test_dead_message_modal_is_gone(): void
    {
        $specialist = $this->specialist();

        $this->get('/specialist/'.$specialist->id)
            ->assertOk()
            ->assertDontSee('msgModal')
            ->assertDontSee('mailto:');
    }

    private function specialist(): SpecialistProfile
    {
        $user = User::factory()->master()->create();

        return SpecialistProfile::query()->create([
            'user_id' => $user->id,
            'craft' => 'Kafelçi',
            'city' => 'Bakı',
            'phone' => self::PHONE,
            'experience_years' => 5,
        ]);
    }
}
