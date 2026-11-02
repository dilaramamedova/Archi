<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tender;
use App\Models\TenderCategory;
use App\Models\User;
use App\Support\TenderAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The tender section ships free (TENDERS_PAID_ACCESS=false): the board and every
 * detail are open to everyone, the lock page is never shown, and the paid code
 * just stays dormant.
 */
final class TenderFreeModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['tenders.paid_access' => false]);
    }

    public function test_access_is_granted_to_everyone_when_free(): void
    {
        $this->assertTrue(TenderAccess::for(null)->granted());
        $this->assertTrue(TenderAccess::for(User::factory()->buyer()->create())->granted());
        $this->assertFalse(TenderAccess::paidMode());
    }

    public function test_guest_sees_the_board_and_the_tender_directly(): void
    {
        $tender = $this->makeTender(['organizer_name' => 'Forma Development', 'contact_email' => 'tender@forma.example']);

        $this->get(route('tenders.index'))
            ->assertOk()
            ->assertSee('Forma Development')
            ->assertSee($tender->title)
            ->assertDontSee('Tenderlərə giriş əldə edin')   // no lock card
            ->assertDontSee('Ödənişli paket aktivdir');     // no paid banner

        $this->get(route('tenders.show', $tender->slug))
            ->assertOk()
            ->assertSee($tender->number)
            ->assertSee('tender@forma.example');
    }

    public function test_empty_board_shows_the_no_tenders_message(): void
    {
        $this->get(route('tenders.index'))
            ->assertOk()
            ->assertSee(t('tenders.empty.none_title'))
            ->assertDontSee(t('tenders.empty.title')); // not the "no match" copy
    }

    public function test_guest_board_has_no_save_controls(): void
    {
        $this->makeTender();

        $this->get(route('tenders.index'))
            ->assertOk()
            ->assertDontSee(route('tenders.save', Tender::query()->value('slug')), false)
            ->assertDontSee(t('tenders.tabs.saved'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeTender(array $overrides = []): Tender
    {
        $category = TenderCategory::query()->create(['name' => ['az' => 'Kateqoriya'], 'slug' => 'kat-free']);

        return Tender::query()->create(array_merge([
            'tender_category_id' => $category->id,
            'title' => ['az' => 'Pulsuz rejim tenderi'],
            'summary' => ['az' => 'Qısa təsvir'],
            'description' => ['az' => 'Ətraflı təsvir'],
            'organizer_name' => 'Şirkət',
            'contact_email' => 'tender@example.com',
            'city' => 'Bakı',
            'deadline_at' => now()->addWeek(),
            'is_published' => true,
            'published_at' => now()->subDay(),
        ], $overrides));
    }
}
