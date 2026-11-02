<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\B2bCooperationType;
use App\Enums\B2bPlan;
use App\Models\B2bCategory;
use App\Models\B2bCompany;
use App\Models\MenuItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class B2bDirectoryTest extends TestCase
{
    use RefreshDatabase;

    private B2bCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = B2bCategory::query()->create(['name' => ['az' => 'Keramika'], 'slug' => 'keramika-test']);
    }

    public function test_directory_lists_published_companies_to_a_guest(): void
    {
        $company = $this->makeCompany(['brand_name' => 'Anatolia Ceramics']);

        $this->get(route('b2b.index'))
            ->assertOk()
            ->assertSee('Anatolia Ceramics')
            ->assertSee($company->country);
    }

    public function test_pricing_is_switched_off_for_now(): void
    {
        $this->get(route('b2b.index'))
            ->assertOk()
            ->assertDontSee('data-plan-cta', false)
            ->assertDontSee('b2LeadModal', false)
            ->assertSee('<h1 class="b2-dir-title">', false);
    }

    public function test_unpublished_and_future_companies_are_hidden(): void
    {
        $draft = $this->makeCompany(['brand_name' => 'Gizli MMC', 'is_published' => false]);
        $future = $this->makeCompany(['brand_name' => 'Gələcək MMC', 'published_at' => now()->addDay()]);

        $ids = $this->get(route('b2b.index'))->assertOk()->viewData('companies')->getCollection()->pluck('id')->all();

        $this->assertNotContains($draft->id, $ids);
        $this->assertNotContains($future->id, $ids);
        $this->get(route('b2b.show', $draft->slug))->assertNotFound();
    }

    public function test_filters_by_category_country_cooperation_and_search(): void
    {
        $other = B2bCategory::query()->create(['name' => ['az' => 'Mebel'], 'slug' => 'mebel-test']);

        $a = $this->makeCompany(['brand_name' => 'Anatolia', 'country' => 'Türkiyə', 'cooperation_type' => B2bCooperationType::Distributor]);
        $b = $this->makeCompany(['brand_name' => 'Milano Furniture', 'country' => 'İtaliya', 'b2b_category_id' => $other->id, 'cooperation_type' => B2bCooperationType::OfficialRep]);

        $ids = fn (array $q) => $this->get(route('b2b.index', $q))->assertOk()->viewData('companies')->getCollection()->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$b->id], $ids(['category' => ['mebel-test']]));
        $this->assertEqualsCanonicalizing([$a->id], $ids(['country' => 'Türkiyə']));
        $this->assertEqualsCanonicalizing([$b->id], $ids(['cooperation' => B2bCooperationType::OfficialRep->value]));
        $this->assertEqualsCanonicalizing([$b->id], $ids(['q' => 'milano']));
    }

    public function test_search_matches_description_text_not_its_locale_keys(): void
    {
        $tiles = $this->makeCompany(['brand_name' => 'Qwerty', 'legal_name' => 'Qwerty MMC', 'description' => ['az' => 'Kafel istehsalı', 'en' => 'Tile maker']]);

        $ids = fn (string $q) => $this->get(route('b2b.index', ['q' => $q]))->assertOk()->viewData('companies')->getCollection()->pluck('id')->all();

        // The description is stored as {"az": …, "en": …}; a LIKE on the raw JSON matched every company for "az"/"en".
        $this->assertSame([], $ids('az'));
        $this->assertSame([], $ids('en'));
        $this->assertSame([$tiles->id], $ids('kafel'));
        $this->assertSame([$tiles->id], $ids('tile maker'));
        $this->assertSame([$tiles->id], $ids('istehsalı'));
        $this->assertSame([$tiles->id], $ids('istehsali'), 'Accent folding still applies to the JSON values.');
    }

    public function test_featured_sort_puts_featured_and_higher_plans_first(): void
    {
        $free = $this->makeCompany(['brand_name' => 'Free Co', 'plan' => B2bPlan::Free, 'published_at' => now()->subDays(1)]);
        $premium = $this->makeCompany(['brand_name' => 'Premium Co', 'plan' => B2bPlan::Premium, 'is_featured' => true, 'published_at' => now()->subDays(3)]);

        $ids = $this->get(route('b2b.index', ['sort' => 'featured']))->viewData('companies')->getCollection()->pluck('id')->all();

        $this->assertSame($premium->id, $ids[0]);
        $this->assertContains($free->id, $ids);
    }

    public function test_profile_renders_and_counts_a_view(): void
    {
        $company = $this->makeCompany(['brand_name' => 'Anatolia Ceramics', 'email' => 'export@anatolia.example']);

        $this->get(route('b2b.show', $company->slug))
            ->assertOk()
            ->assertSee('Anatolia Ceramics')
            ->assertSee('export@anatolia.example');

        $this->assertSame(1, $company->fresh()->views_count);
    }

    public function test_inactive_category_is_not_shown_on_cards_profiles_or_related(): void
    {
        $company = $this->makeCompany(['brand_name' => 'Anatolia']);
        $this->makeCompany(['brand_name' => 'Qonşu']); // same category → "related" card on the profile
        $this->category->update(['name' => ['az' => 'Köhnə kateqoriya'], 'is_active' => false]);

        $this->get(route('b2b.index'))->assertOk()->assertSee('Anatolia')->assertDontSee('Köhnə kateqoriya');
        $this->get(route('b2b.show', $company->slug))->assertOk()->assertSee('Qonşu')->assertDontSee('Köhnə kateqoriya');

        $this->category->update(['is_active' => true]);
        $this->get(route('b2b.show', $company->slug))->assertOk()->assertSee('Köhnə kateqoriya');
    }

    public function test_model_helpers(): void
    {
        $company = $this->makeCompany([
            'legal_name' => 'Anatolia Ceramics Ltd.', 'brand_name' => null, 'slug' => null,
            'whatsapp' => '+994 50 111 22 33', 'languages' => ['az', 'en'],
        ]);

        $this->assertSame('anatolia-ceramics-ltd', $company->slug);
        $this->assertSame('Anatolia Ceramics Ltd.', $company->displayName());
        $this->assertSame('AC', $company->initials());
        $this->assertSame('https://wa.me/994501112233', $company->whatsappUrl());
        $this->assertSame(['Azərbaycan', 'İngilis'], $company->languageLabels());
    }

    public function test_b2b_header_menu_points_to_the_directory(): void
    {
        // The test DB is not seeded, so recreate the old placeholder item and replay the repoint.
        MenuItem::query()->create(['location' => 'header_main', 'label' => ['az' => 'B2B'], 'route_name' => 'business.register', 'sort_order' => 50, 'is_active' => true]);
        (require database_path('migrations/2026_11_02_110100_point_b2b_menu_to_directory.php'))->up();

        $this->assertDatabaseHas('menu_items', ['location' => 'header_main', 'route_name' => 'b2b.index']);
        $this->assertDatabaseMissing('menu_items', ['location' => 'header_main', 'route_name' => 'business.register']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeCompany(array $overrides = []): B2bCompany
    {
        static $i = 0;
        $i++;

        return B2bCompany::query()->create(array_merge([
            'b2b_category_id' => $this->category->id,
            'legal_name' => 'Şirkət '.$i.' MMC',
            'brand_name' => 'Brend '.$i,
            'country' => 'Azərbaycan',
            'cooperation_type' => B2bCooperationType::Distributor,
            'description' => ['az' => 'Qısa təsvir'],
            'market' => 'international',
            'plan' => B2bPlan::Free,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ], $overrides));
    }
}
