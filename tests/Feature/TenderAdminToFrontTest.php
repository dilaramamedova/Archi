<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\TenderResource\Pages\CreateTender;
use App\Filament\Resources\TenderResource\Pages\EditTender;
use App\Models\Tender;
use App\Models\TenderCategory;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * What the admin enters in the Tender form is what the board card and the detail
 * page show — every user-facing field, in the same format on both, in Bakı time.
 * Runs in the shipped free mode (guest sees everything) unless a test turns the
 * paid gate on.
 */
final class TenderAdminToFrontTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['tenders.paid_access' => false]);
        Storage::fake(Tender::FILES_DISK);
        Storage::fake('public');

        // A fixed "now" so the Bakı-vs-UTC date checks never drift.
        Carbon::setTestNow(Carbon::parse('2026-11-02 12:00', 'Asia/Baku'));

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create(), 'admin');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_every_admin_field_reaches_the_card_and_the_detail(): void
    {
        $category = TenderCategory::query()->create(['name' => ['az' => 'Memarlıq və dizayn', 'ru' => 'Архитектура'], 'slug' => 'memarliq-front']);

        // 21 Nov 02:30 in Bakı is still 20 Nov in UTC — the front must show the Bakı date.
        $tender = $this->createThroughAdmin([
            'title' => ['az' => 'Ofis mərkəzinin interyer dizaynı', 'ru' => 'Дизайн интерьера офисного центра'],
            'tender_category_id' => $category->id,
            'number' => 'T-2026-777',
            'summary' => ['az' => 'Kartdakı qısa təsvir'],
            'description' => ['az' => "Birinci abzas\nİkinci abzas"],
            'scope' => ['az' => "Konsepsiya\n3D vizuallaşdırma"],
            'requirements' => ['az' => "Ən azı 5 il təcrübə\nPortfolio"],
            'submission_documents' => ['az' => 'Kommersiya təklifi — PDF'],
            'organizer_name' => 'Forma Development',
            'organizer_logo' => [UploadedFile::fake()->image('forma.png')],
            'contact_name' => ['az' => 'Tender şöbəsi — Aysel'],
            'contact_email' => 'tender@forma.example',
            'contact_phone' => '+994 50 123-45-67',
            'city' => 'Bakı',
            'area_sqm' => '250.5',
            'duration' => ['az' => '8 həftə'],
            'budget_min' => '180000',
            'budget_max' => '220000',
            'currency' => 'USD',
            'deadline_at' => '2026-11-21 02:30',
            'files' => [UploadedFile::fake()->create('Texniki tapşırıq.pdf', 120, 'application/pdf')],
            'is_published' => true,
            'published_at' => '2026-11-01 10:00',
        ]);

        $this->asGuest();

        $this->assertSame('2026-11-20 22:30:00', $tender->deadline_at->toDateTimeString(), 'deadline is stored in UTC');
        $this->assertSame('ofis-merkezinin-interyer-dizayni', $tender->slug);
        $this->assertNotNull($tender->organizer_logo);
        Storage::disk('public')->assertExists($tender->organizer_logo);
        $this->assertCount(1, $tender->files);
        Storage::disk(Tender::FILES_DISK)->assertExists($tender->files[0]);

        // --- board card -----------------------------------------------------
        $card = $this->get(route('tenders.index'))->assertOk();
        $card->assertSee('Forma Development')
            ->assertSee('Memarlıq və dizayn')
            ->assertSee('Ofis mərkəzinin interyer dizaynı')
            ->assertSee('Kartdakı qısa təsvir')
            ->assertSee('Bakı')
            ->assertSee('Büdcə: 180 000–220 000 USD')
            ->assertSee('Son tarix: 21.11.2026')
            ->assertSee(route('tenders.show', $tender->slug), false)
            ->assertSee($tender->organizer_logo_url, false)
            ->assertSee('data-tone="open"', false);
        $this->assertNoRawKeys($card);

        // --- detail ---------------------------------------------------------
        $detail = $this->get(route('tenders.show', $tender->slug))->assertOk();
        $detail->assertSee('Forma Development')
            ->assertSee('Memarlıq və dizayn')
            ->assertSee('Ofis mərkəzinin interyer dizaynı')
            ->assertSee('Tender № T-2026-777')
            ->assertSee('Dərc edilib: 01.11.2026')
            ->assertSee('Birinci abzas<br />', false)
            ->assertSee('İkinci abzas')
            ->assertSeeInOrder(['Konsepsiya', '3D vizuallaşdırma'])
            ->assertSee('Ən azı 5 il təcrübə')
            ->assertSee('Kommersiya təklifi — PDF')
            ->assertSee('Texniki tapşırıq.pdf')
            ->assertSee(route('tenders.files.show', [$tender->slug, 0]), false)
            ->assertSee('21.11.2026, saat 02:30')
            ->assertSee('250.5 m²')
            ->assertSee('180 000–220 000 USD')
            ->assertSee('8 həftə')
            ->assertSee('Tender şöbəsi — Aysel')
            ->assertSee('mailto:tender@forma.example?subject=', false)
            ->assertSee('href="tel:+994501234567"', false)
            ->assertSee('+994 50 123-45-67')
            ->assertSee($tender->organizer_logo_url, false);
        $this->assertNoRawKeys($detail);

        // The uploaded file downloads under its original name (UTF-8 filename*, ASCII fallback).
        $download = $this->get(route('tenders.files.show', [$tender->slug, 0]))->assertOk()->assertDownload('Texniki tapsiriq.pdf');
        $this->assertStringContainsString("filename*=utf-8''".rawurlencode('Texniki tapşırıq.pdf'), (string) $download->headers->get('content-disposition'));

        // Russian visitors get the ru title / category, and az for what has no ru.
        $ru = $this->withSession(['locale' => 'ru'])->get(route('tenders.show', $tender->slug))->assertOk();
        $ru->assertSee('Дизайн интерьера офисного центра')
            ->assertSee('Архитектура')
            ->assertSee('Konsepsiya')          // scope has no ru → az fallback, never blank
            ->assertSee('Tender şöbəsi — Aysel');
        $this->assertNoRawKeys($ru);
    }

    public function test_optional_fields_left_empty_render_their_placeholders_on_both_pages(): void
    {
        $tender = $this->createThroughAdmin([
            'title' => ['az' => 'Minimal tender'],
            'organizer_name' => 'Terra',
            'contact_email' => 'info@terra.example',
            'currency' => 'AZN',
            'is_published' => true,
        ]);

        $card = $this->get(route('tenders.index'))->assertOk();
        $card->assertSee('Minimal tender')
            ->assertSee('>TE<', false)                       // initials instead of a logo
            ->assertSee('Büdcə: Sorğu əsasında')
            ->assertSee('Son tarix: Müddətsiz')
            ->assertSee('data-tone="open"', false);

        $detail = $this->get(route('tenders.show', $tender->slug))->assertOk();
        $detail->assertSee('Minimal tender')
            ->assertSee('Sorğu əsasında')
            // The card says "Müddətsiz"; the detail must not silently drop the deadline.
            ->assertSee('Müddətsiz')
            ->assertSee('Tender şöbəsi')                    // contact name fallback
            ->assertDontSee('tdd-files', false)
            ->assertDontSee('tel:', false);
        $this->assertNoRawKeys($detail);
    }

    public function test_summary_only_tender_still_has_an_about_section_on_the_detail(): void
    {
        $tender = $this->createThroughAdmin([
            'title' => ['az' => 'Yalnız qısa təsvirli'],
            'summary' => ['az' => 'Bu yalnız kartda yazılan mətndir'],
            'organizer_name' => 'Terra',
            'contact_email' => 'info@terra.example',
            'currency' => 'AZN',
            'is_published' => true,
        ]);

        $this->get(route('tenders.show', $tender->slug))
            ->assertOk()
            ->assertSee('Bu yalnız kartda yazılan mətndir');
    }

    public function test_budget_variants_render_the_same_on_card_and_detail(): void
    {
        $cases = [
            [['budget_min' => '95000', 'currency' => 'AZN'], '95 000 ₼'],
            [['budget_max' => '50000', 'currency' => 'EUR'], '50 000 EUR'],
            [['budget_min' => '1000', 'budget_max' => '1000', 'currency' => 'AZN'], '1 000 ₼'],
        ];

        foreach ($cases as $i => [$budget, $label]) {
            $tender = $this->createThroughAdmin(array_merge([
                'title' => ['az' => 'Büdcə halı '.$i],
                'organizer_name' => 'Şirkət',
                'contact_email' => 'b@example.com',
                'is_published' => true,
            ], $budget));

            $this->assertSame($label, $tender->budgetLabel());
            $this->get(route('tenders.index'))->assertSee('Büdcə: '.$label);
            $this->get(route('tenders.show', $tender->slug))->assertSee($label);
        }
    }

    public function test_draft_future_and_expired_tenders_behave_as_set_in_the_panel(): void
    {
        $draft = $this->createThroughAdmin(['title' => ['az' => 'Qaralama tender'], 'organizer_name' => 'A', 'contact_email' => 'a@example.com', 'is_published' => false]);
        $future = $this->createThroughAdmin([
            'title' => ['az' => 'Gələcək tender'], 'organizer_name' => 'B', 'contact_email' => 'b@example.com',
            'is_published' => true, 'published_at' => now()->addDays(2)->timezone('Asia/Baku')->format('Y-m-d H:i'),
        ]);
        $expired = $this->createThroughAdmin([
            'title' => ['az' => 'Bitmiş tender'], 'organizer_name' => 'C', 'contact_email' => 'closed@example.com',
            'is_published' => true, 'published_at' => now()->subWeek()->timezone('Asia/Baku')->format('Y-m-d H:i'),
            'deadline_at' => now()->subDay()->timezone('Asia/Baku')->format('Y-m-d H:i'),
        ]);
        $open = $this->createThroughAdmin(['title' => ['az' => 'Açıq tender'], 'organizer_name' => 'D', 'contact_email' => 'd@example.com', 'is_published' => true]);

        $this->assertNull($draft->published_at, 'a draft gets no publish date until it is published');

        $board = $this->get(route('tenders.index'))->assertOk();
        $board->assertDontSee('Qaralama tender')->assertDontSee('Gələcək tender')
            ->assertSeeInOrder(['Açıq tender', 'Bitmiş tender']);   // open before closed

        $this->get(route('tenders.show', $draft->slug))->assertNotFound();
        $this->get(route('tenders.show', $future->slug))->assertNotFound();
        $this->get(route('tenders.files.show', [$draft->slug, 0]))->assertNotFound();

        $this->get(route('tenders.show', $expired->slug))
            ->assertOk()
            ->assertSee('Bu tenderin müraciət müddəti başa çatıb')
            ->assertSee('data-tone="closed"', false)
            ->assertSee('Müraciət müddəti bitib')
            ->assertDontSee('mailto:closed@example.com?subject=', false)
            ->assertSee('Tender bağlanıb — müraciət qəbulu dayandırılıb.');

        $this->get(route('tenders.show', $open->slug))->assertOk()->assertSee('data-tone="open"', false);

        // Publishing the draft later stamps the date and puts it on the board.
        Livewire::test(EditTender::class, ['record' => $draft->getRouteKey()])
            ->fillForm(['is_published' => true])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertNotNull($draft->fresh()->published_at);
        $this->get(route('tenders.index'))->assertSee('Qaralama tender');
    }

    public function test_paid_mode_hides_admin_tenders_from_guests_but_not_from_subscribers(): void
    {
        config(['tenders.paid_access' => true]);

        $tender = $this->createThroughAdmin([
            'title' => ['az' => 'Ödənişli rejim tenderi'], 'organizer_name' => 'Gizli MMC',
            'contact_email' => 'gizli@example.com', 'is_published' => true,
        ]);

        $this->asGuest();
        $this->get(route('tenders.index'))->assertOk()->assertDontSee('Gizli MMC')->assertSee('Tenderlərə giriş əldə edin');
        $this->get(route('tenders.show', $tender->slug))->assertOk()->assertDontSee('gizli@example.com');

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('tenders.show', $tender->slug))
            ->assertOk()
            ->assertSee('gizli@example.com')
            ->assertSee('Ödənişli paket aktivdir');
    }

    public function test_editing_files_in_the_panel_updates_the_detail_and_the_disk(): void
    {
        $tender = $this->createThroughAdmin([
            'title' => ['az' => 'Fayllı tender'], 'organizer_name' => 'F', 'contact_email' => 'f@example.com', 'is_published' => true,
            'files' => [
                UploadedFile::fake()->create('Plan.dwg', 10),
                UploadedFile::fake()->create('Smeta.xlsx', 10),
            ],
        ]);
        [$first, $second] = $tender->files;

        $this->get(route('tenders.show', $tender->slug))->assertSeeInOrder(['Plan.dwg', 'Smeta.xlsx']);

        // Removing the first file in the panel: the second becomes index 0 and the first leaves the disk.
        $component = Livewire::test(EditTender::class, ['record' => $tender->getRouteKey()]);
        $keys = array_keys($component->get('data.files'));
        $component->set('data.files', [$keys[1] => $second])->call('save')->assertHasNoFormErrors();

        $tender->refresh();
        $this->assertSame([$second], array_values($tender->files));
        Storage::disk(Tender::FILES_DISK)->assertMissing($first);
        Storage::disk(Tender::FILES_DISK)->assertExists($second);

        $this->get(route('tenders.show', $tender->slug))->assertDontSee('Plan.dwg')->assertSee('Smeta.xlsx');
        $this->get(route('tenders.files.show', [$tender->slug, 0]))->assertDownload('Smeta.xlsx');
        $this->get(route('tenders.files.show', [$tender->slug, 1]))->assertNotFound();

        // Deleting the tender takes its files along.
        $tender->delete();
        Storage::disk(Tender::FILES_DISK)->assertMissing($second);
    }

    public function test_replacing_the_logo_in_the_panel_updates_the_front_and_cleans_the_disk(): void
    {
        $tender = $this->createThroughAdmin([
            'title' => ['az' => 'Loqolu tender'], 'organizer_name' => 'Loqo MMC', 'contact_email' => 'l@example.com', 'is_published' => true,
            'organizer_logo' => [UploadedFile::fake()->image('old.png')],
        ]);
        $old = $tender->organizer_logo;
        $this->assertStringStartsWith('tenders/logos/', $old);

        $component = Livewire::test(EditTender::class, ['record' => $tender->getRouteKey()]);
        $component->set('data.organizer_logo', null);
        $component->fillForm(['organizer_logo' => [UploadedFile::fake()->image('new.png')]])->call('save')->assertHasNoFormErrors();

        $new = $tender->fresh()->organizer_logo;
        $this->assertNotSame($old, $new);
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($new);
        $this->get(route('tenders.show', $tender->slug))->assertSee(storage_url($new), false)->assertDontSee(storage_url($old), false);

        // Removing the logo falls back to the initials.
        Livewire::test(EditTender::class, ['record' => $tender->getRouteKey()])
            ->set('data.organizer_logo', null)
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertNull($tender->fresh()->organizer_logo);
        Storage::disk('public')->assertMissing($new);
        $this->get(route('tenders.index'))->assertSee('>LM<', false);
    }

    public function test_a_logo_shared_by_two_tenders_survives_deleting_one_of_them(): void
    {
        Storage::disk('public')->put('tenders/logos/shared.png', 'png');
        $a = Tender::query()->create(['title' => ['az' => 'A'], 'organizer_name' => 'A', 'contact_email' => 'a@example.com', 'organizer_logo' => 'tenders/logos/shared.png']);
        $b = Tender::query()->create(['title' => ['az' => 'B'], 'organizer_name' => 'B', 'contact_email' => 'b@example.com', 'organizer_logo' => 'tenders/logos/shared.png']);

        $a->delete();
        Storage::disk('public')->assertExists('tenders/logos/shared.png');

        $b->delete();
        Storage::disk('public')->assertMissing('tenders/logos/shared.png');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createThroughAdmin(array $data): Tender
    {
        $before = Tender::query()->max('id') ?? 0;

        Livewire::test(CreateTender::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasNoFormErrors();

        return Tender::query()->where('id', '>', $before)->sole();
    }

    /** The panel login must not leak onto the public pages: visit them as a guest. */
    private function asGuest(): void
    {
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');
    }

    private function assertNoRawKeys(TestResponse $response): void
    {
        // t() echoes the key back when a row is missing — none may reach the page.
        $text = strip_tags((string) $response->getContent());
        $this->assertDoesNotMatchRegularExpression('/\btenders\.[a-z_]+\.[a-z_.]+\b/', $text);
    }
}
