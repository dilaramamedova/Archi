<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\B2bCooperationType;
use App\Enums\B2bPlan;
use App\Filament\Resources\B2bCompanyResource\Pages\CreateB2bCompany;
use App\Models\B2bCategory;
use App\Models\B2bCompany;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * What the admin enters in the panel is what the public B2B pages show: every
 * user-facing field of a fully filled company reaches the directory card and the
 * profile, a minimal company renders without empty cards or dangling labels, and
 * links are absolute http(s)/tel/mailto only.
 */
final class B2bAdminToFrontTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create(), 'admin');
    }

    public function test_every_admin_field_reaches_the_profile(): void
    {
        $company = $this->createFullCompanyThroughAdmin();

        $html = $this->get(route('b2b.show', $company->slug))->assertOk();

        // header: logo, badges, name, chips, legal line
        $html->assertSee('<img class="b2-logo b2c-logo" src="'.e($company->logo_url).'"', false)
            ->assertSeeInOrder(['b2-badge-ok', 'Təsdiqlənib', 'b2-badge-feat', 'Seçilmiş', 'b2-badge-plan', 'Pro'], false)
            ->assertSee('<h1>Anatolia</h1>', false)
            ->assertSee('Türkiyə')
            ->assertSee('Distribütor')
            ->assertSee('Anatolia Ceramics Ltd.')
            ->assertSee('Qeydiyyat №: 1234567890');

        // main column
        $html->assertSee('Şirkət haqqında')
            ->assertSee('Keramika ixracatçısı')
            ->assertSee('Kataloq / şəkillər');
        foreach ($company->imageUrls() as $src) {
            $html->assertSee('<img src="'.e($src).'"', false);
        }
        $html->assertSeeInOrder(['Kateqoriya', 'Keramika', 'Brendlər', 'Anatolia, Vitra', 'Minimum sifariş (MOQ)', '1 konteyner', 'Əməkdaşlıq növü', 'Distribütor'])
            ->assertSee('Kafel və keramoqranit')
            ->assertSee('Ünsiyyət dilləri')
            ->assertSeeInOrder(['Türk', 'İngilis', 'Rus']);

        // contact card: person + direct line (≠ office phone), then every contact row
        $html->assertSeeInOrder(['Aylin Kaya', 'İxrac meneceri', 'href="tel:+905321112233"', '+90 532 111 22 33'], false);
        $this->assertExternalLink($html, 'https://wa.me/905000000000', '+90 500 000 00 00');
        $this->assertExternalLink($html, 'https://t.me/anatolia_export', '@anatolia_export');
        $this->assertExternalLink($html, 'https://anatolia.example', 'https://anatolia.example');
        $html->assertSee('href="tel:+902120000000"', false)
            ->assertSee('+90 212 000 00 00')
            ->assertSee('href="mailto:export@anatolia.example"', false)
            ->assertSee('B.e–Cümə 09:00–18:00')
            ->assertSee('İstanbul, Levent 12')
            ->assertSee('href="https://www.instagram.com/anatolia.tiles" target="_blank" rel="noopener noreferrer" aria-label="Instagram"', false)
            ->assertSee('href="https://www.linkedin.com/company/anatolia" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn"', false)
            ->assertSee('href="https://facebook.com/anatolia" target="_blank" rel="noopener noreferrer" aria-label="Facebook"', false)
            ->assertSee('data-copy="export@anatolia.example"', false)
            ->assertSee('Əlaqə saxla');

        $this->assertNoRawTranslationKeys($html);
    }

    public function test_every_card_field_reaches_the_directory(): void
    {
        $company = $this->createFullCompanyThroughAdmin();

        $html = $this->get(route('b2b.index'))->assertOk();

        $html->assertSee('<img class="b2-logo" src="'.e($company->logo_url).'"', false)
            ->assertSee('<a href="'.e(route('b2b.show', $company->slug)).'">Anatolia</a>', false)
            ->assertSee('Türkiyə')
            ->assertSee('Distribütor')
            ->assertSeeInOrder(['b2-badge-ok', 'Təsdiqlənib', 'b2-badge-feat', 'Seçilmiş', 'b2-badge-plan', 'Pro'], false)
            ->assertSee('Keramika ixracatçısı')
            ->assertSee('Keramika</span>', false)
            ->assertSee('Ətraflı');

        $this->assertNoRawTranslationKeys($html);
    }

    public function test_profile_is_translated_on_another_locale(): void
    {
        $company = $this->createFullCompanyThroughAdmin();

        $this->withSession(['locale' => 'en'])
            ->get(route('b2b.show', $company->slug))
            ->assertOk()
            ->assertSee('About the company')
            ->assertSee('Ceramics exporter')
            ->assertSee('Verified')
            ->assertSeeInOrder(['Turkish', 'English', 'Russian'])
            ->assertSee('Legal address')
            ->assertDontSee('İngilis');
    }

    public function test_minimal_company_profile_has_no_empty_sections(): void
    {
        Livewire::test(CreateB2bCompany::class)
            ->fillForm(['legal_name' => 'Minimal MMC', 'market' => 'local', 'plan' => B2bPlan::Free->value, 'is_published' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $company = B2bCompany::query()->where('legal_name', 'Minimal MMC')->sole();
        $html = $this->get(route('b2b.show', $company->slug))->assertOk();

        $html->assertSee('<h1>Minimal MMC</h1>', false)
            ->assertSee('<span class="b2-logo b2c-logo" aria-hidden="true">MM</span>', false);

        // No card, label or link without a value behind it.
        foreach ([
            'b2c-card', 'b2c-cols', 'b2c-side', 'b2c-badges', 'b2c-head-chips', 'b2c-legal', 'b2c-person', 'b2c-lines', 'b2c-social',
            'b2c-actions', 'b2c-gallery', 'b2c-facts', 'b2-chip', '<dd></dd>', 'href=""', 'Şirkət haqqında', 'Əlaqə saxla',
        ] as $fragment) {
            $html->assertDontSee($fragment, false);
        }

        // The card in the directory: no badges row, no description, no chips.
        $card = $this->get(route('b2b.index'))->assertOk()->assertSee('Minimal MMC');
        $card->assertDontSee('b2-card-badges', false)
            ->assertDontSee('b2-card-desc', false)
            ->assertDontSee('b2-card-cat', false);

        $this->assertNoRawTranslationKeys($html);
    }

    public function test_contact_phone_is_shown_only_when_it_differs_from_the_office_phone(): void
    {
        $same = $this->makeCompany(['brand_name' => 'Eyni', 'phone' => '+994 12 555 55 55', 'contact_phone' => '+994125555555', 'contact_person' => 'Aynur']);
        $direct = $this->makeCompany(['brand_name' => 'Fərqli', 'phone' => '+994 12 555 55 55', 'contact_phone' => '+994 50 777 77 77']);

        $html = $this->get(route('b2b.show', $same->slug))->assertOk();
        $this->assertSame(1, substr_count($html->getContent(), 'href="tel:+994125555555"'));
        $html->assertDontSee('Birbaşa telefon');

        // Without a contact person, the direct line still gets the person block.
        $this->get(route('b2b.show', $direct->slug))
            ->assertOk()
            ->assertSeeInOrder(['b2c-person', 'Birbaşa telefon', 'href="tel:+994507777777"', 'b2c-lines', 'href="tel:+994125555555"'], false);
    }

    public function test_unsafe_links_stored_in_the_database_are_never_rendered(): void
    {
        // Bypasses the admin validation (old rows, imports) — the view must still be safe.
        $company = $this->makeCompany([
            'website' => 'javascript:alert(1)',
            'instagram' => 'javascript:alert(2)',
            'linkedin' => 'data:text/html,<script>alert(3)</script>',
            'facebook' => 'JaVaScRiPt:alert(4)',
            'telegram' => 'javascript:alert(5)',
            'whatsapp' => 'yoxdur',
        ]);

        $html = $this->get(route('b2b.show', $company->slug))->assertOk();

        $html->assertDontSee('href="javascript', false)
            ->assertDontSee('href="JaVaScRiPt', false)
            ->assertDontSee('href="data:', false)
            ->assertDontSee('https://wa.me/"', false)
            ->assertDontSee('b2c-social', false)
            ->assertDontSee('Əlaqə saxla');
    }

    public function test_link_helpers_normalise_handles_and_hosts(): void
    {
        $c = new B2bCompany([
            'website' => 'anatolia.example/az',
            'telegram' => '+994 50 111 22 33',
            'instagram' => 'https://instagram.com/anatolia',
            'facebook' => '@anatolia.page',
            'linkedin' => 'linkedin.com/company/anatolia',
        ]);

        $this->assertSame('https://anatolia.example/az', $c->websiteUrl());
        $this->assertSame('https://t.me/+994501112233', $c->socialUrl('telegram'));
        $this->assertSame('https://instagram.com/anatolia', $c->socialUrl('instagram'));
        $this->assertSame('https://www.facebook.com/anatolia.page', $c->socialUrl('facebook'));
        $this->assertSame('https://linkedin.com/company/anatolia', $c->socialUrl('linkedin'));
        $this->assertNull($c->socialUrl('email'));

        foreach (['javascript:alert(1)', 'mailto:a@b.c', 'ftp://x.example', 'http://localhost', ''] as $bad) {
            $this->assertNull(B2bCompany::safeUrl($bad), $bad);
        }
        $this->assertSame('http://anatolia.example:8080/x', B2bCompany::safeUrl('http://anatolia.example:8080/x'));
        $this->assertNull(B2bCompany::telUrl('yoxdur'));
        $this->assertNull((new B2bCompany(['whatsapp' => '—']))->whatsappUrl());
    }

    public function test_long_values_have_wrapping_styles(): void
    {
        // Long free text (names, e-mails, URLs, countries) must wrap or truncate, not overflow on phones.
        $css = (string) file_get_contents(resource_path('css/pages/b2b.css'));

        foreach ([
            '/\.b2-card-name \{[^}]*overflow-wrap: anywhere/',
            '/\.b2-card-desc \{[^}]*overflow-wrap: anywhere/',
            '/\.b2c-head-main h1 \{[^}]*overflow-wrap: anywhere/',
            '/\.b2c-lines a, \.b2c-lines span \{[^}]*overflow-wrap: anywhere/',
            '/\.b2c-person \.n \{[^}]*overflow-wrap: anywhere/',
            '/\.b2c-legal span \{[^}]*overflow-wrap: anywhere/',
            '/\.b2-chip > span \{[^}]*text-overflow: ellipsis/',
        ] as $pattern) {
            $this->assertMatchesRegularExpression($pattern, $css, $pattern);
        }
    }

    public function test_badges_render_the_same_on_card_and_profile(): void
    {
        $company = $this->makeCompany(['brand_name' => 'Nişanlı', 'is_verified' => true, 'is_featured' => true, 'plan' => B2bPlan::Premium]);
        $badges = fn (TestResponse $r): array => preg_match_all('/<span class="b2-badge (b2-badge-[a-z]+)"/', $r->getContent(), $m) ? $m[1] : [];

        $card = $badges($this->get(route('b2b.index')));
        $profile = $badges($this->get(route('b2b.show', $company->slug)));

        $this->assertSame(['b2-badge-ok', 'b2-badge-feat', 'b2-badge-plan'], $profile);
        $this->assertSame($profile, $card);
    }

    public function test_admin_sort_order_breaks_ties_in_the_featured_sort(): void
    {
        $later = $this->makeCompany(['brand_name' => 'Sıra 5', 'sort_order' => 5, 'published_at' => now()->subHour()]);
        $first = $this->makeCompany(['brand_name' => 'Sıra 1', 'sort_order' => 1, 'published_at' => now()->subDays(5)]);

        $ids = $this->get(route('b2b.index'))->viewData('companies')->getCollection()->pluck('id')->all();

        $this->assertSame([$first->id, $later->id], $ids);
    }

    // ─────────────────────────────────────────────────────────── helpers

    private function createFullCompanyThroughAdmin(): B2bCompany
    {
        Storage::fake('public');
        $category = B2bCategory::query()->create(['name' => ['az' => 'Keramika', 'en' => 'Ceramics'], 'slug' => 'keramika-front']);

        Livewire::test(CreateB2bCompany::class)
            ->fillForm(AdminB2bResourcesTest::fullFormData($category->id))
            ->call('create')
            ->assertHasNoFormErrors();

        return B2bCompany::query()->where('legal_name', 'Anatolia Ceramics Ltd.')->sole();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeCompany(array $overrides = []): B2bCompany
    {
        return B2bCompany::query()->create(array_merge([
            'legal_name' => 'Test Şirkət MMC',
            'cooperation_type' => B2bCooperationType::Dealer,
            'market' => 'local',
            'plan' => B2bPlan::Free,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ], $overrides));
    }

    /** `<a href="…" target="_blank" rel="noopener noreferrer">text</a>`, whitespace-tolerant. */
    private function assertExternalLink(TestResponse $response, string $href, string $text): void
    {
        $pattern = '/<a href="'.preg_quote(e($href), '/').'"\s+target="_blank"\s+rel="noopener noreferrer"\s*>\s*'.preg_quote(e($text), '/').'\s*<\/a>/u';

        $this->assertMatchesRegularExpression($pattern, (string) $response->getContent(), "External link to {$href} missing.");
    }

    /** No `b2b.some.key` copy may leak to the page (asset paths and route names aside). */
    private function assertNoRawTranslationKeys(TestResponse $response): void
    {
        $text = preg_replace('/<(script|style|link)\b[^>]*>.*?<\/\1>|<link\b[^>]*>/is', '', $response->getContent());
        preg_match_all('/\bb2b\.[a-z_]+(?:\.[a-z_]+)*/', (string) $text, $m);

        $leaks = array_values(array_diff(array_unique($m[0]), ['b2b.css', 'b2b.js']));
        $this->assertSame([], $leaks, 'Raw translation keys on the page: '.implode(', ', $leaks));
    }
}
