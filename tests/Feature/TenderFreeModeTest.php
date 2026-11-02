<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tender;
use App\Models\TenderAccessRequest;
use App\Models\TenderCategory;
use App\Models\TenderPackage;
use App\Models\User;
use App\Support\TenderAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
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

    public function test_signed_in_user_without_package_can_save_and_see_the_saved_tab(): void
    {
        $tender = $this->makeTender();
        $user = User::factory()->buyer()->create();

        $this->actingAs($user)->get(route('tenders.index'))
            ->assertOk()
            ->assertSee(route('tenders.save', $tender->slug), false)
            ->assertSee(t('tenders.tabs.saved'))
            ->assertDontSee(t('tenders.package.active'));   // no paid banner when free

        $this->actingAs($user)->postJson(route('tenders.save', $tender->slug))->assertOk()->assertJson(['saved' => true]);

        $ids = $this->actingAs($user)->get(route('tenders.index', ['tab' => 'saved']))->viewData('tenders')->getCollection()->pluck('id')->all();
        $this->assertSame([$tender->id], $ids);

        $this->actingAs($user)->get(route('tenders.show', $tender->slug))
            ->assertOk()
            ->assertSee('aria-pressed="true"', false)
            ->assertDontSee(t('tenders.package.active'));
    }

    public function test_guest_saved_tab_falls_back_to_all_tenders_and_files_download(): void
    {
        Storage::fake(Tender::FILES_DISK);
        $path = UploadedFile::fake()->create('plan.pdf', 10, 'application/pdf')->store('tenders', Tender::FILES_DISK);
        $tender = $this->makeTender(['files' => [$path], 'file_names' => [$path => 'plan.pdf']]);

        $this->assertSame([$tender->id], $this->get(route('tenders.index', ['tab' => 'saved']))->viewData('tenders')->getCollection()->pluck('id')->all());
        $this->get(route('tenders.files.show', [$tender->slug, 0]))->assertOk()->assertDownload('plan.pdf');
        $this->postJson(route('tenders.save', $tender->slug))->assertUnauthorized();
    }

    public function test_access_request_endpoint_refuses_while_free(): void
    {
        Notification::fake();
        $package = TenderPackage::query()->create(['name' => ['az' => 'Paket'], 'duration_months' => 1]);

        $this->actingAs(User::factory()->buyer()->create())->postJson(route('tenders.access-request'), [
            'tender_package_id' => $package->id,
            'full_name' => 'Pulsuz İstifadəçi',
            'phone' => '+994 50 000 00 00',
            'email' => 'free@example.com',
        ])->assertStatus(422);

        $this->assertSame(0, TenderAccessRequest::query()->count());
        Notification::assertNothingSent();
    }

    public function test_filtered_miss_shows_the_no_match_copy_not_the_empty_board_copy(): void
    {
        $this->makeTender(['city' => 'Bakı']);

        $this->get(route('tenders.index', ['city' => 'Şəki']))
            ->assertOk()
            ->assertSee(t('tenders.empty.title'))
            ->assertSee(t('tenders.empty.reset'))
            ->assertDontSee(t('tenders.empty.none_title'));
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
