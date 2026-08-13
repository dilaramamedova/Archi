<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AttributeComplexity;
use App\Enums\AttributeFieldType;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Product;
use App\Models\ProductAttributeValue;
use App\Models\SearchSynonym;
use App\Models\SubCategory;
use App\Models\User;
use Database\Seeders\NavigationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Customer-facing classifier UX: catalog drill-down (?section/?group/?class),
 * dynamic attribute filters, synonym-driven search and the structured specs
 * block on the product page.
 */
class CatalogClassifierUxTest extends TestCase
{
    use RefreshDatabase;

    private Category $section;

    private Category $group;

    private SubCategory $class;

    private Attribute $color;

    private Attribute $thickness;

    private Product $red;

    private Product $white;

    private Product $bare;

    protected function setUp(): void
    {
        parent::setUp();

        $this->section = Category::create([
            'name' => ['az' => 'Döşəmələr'], 'slug' => 'dosemeler',
            'is_active' => true, 'show_on_home' => true, 'sort_order' => 2,
        ]);
        $this->group = Category::create([
            'name' => ['az' => 'Taxta örtüklər'], 'slug' => 'taxta-ortukler',
            'parent_id' => $this->section->id, 'is_active' => true, 'sort_order' => 1,
        ]);
        $this->class = SubCategory::create([
            'name' => ['az' => 'Laminat'], 'slug' => 'laminat',
            'category_id' => $this->group->id, 'is_active' => true, 'sort_order' => 1,
        ]);

        $this->color = Attribute::create([
            'name' => ['az' => 'Rəng'], 'slug' => 'reng',
            'field_type' => AttributeFieldType::Dropdown,
            'complexity' => AttributeComplexity::Basic,
            'tooltip' => ['az' => 'Səthin əsas rəngi'],
            'is_active' => true,
        ]);
        $redOpt = AttributeOption::create(['attribute_id' => $this->color->id, 'value' => ['az' => 'Qırmızı'], 'slug' => 'qirmizi', 'sort_order' => 1]);
        $whiteOpt = AttributeOption::create(['attribute_id' => $this->color->id, 'value' => ['az' => 'Ağ'], 'slug' => 'ag', 'sort_order' => 2]);

        $this->thickness = Attribute::create([
            'name' => ['az' => 'Qalınlıq'], 'slug' => 'qalinliq',
            'field_type' => AttributeFieldType::Numeric,
            'complexity' => AttributeComplexity::Professional,
            'is_active' => true,
        ]);

        $this->class->attributes()->attach($this->color->id, [
            'is_required' => true, 'is_filterable' => true, 'filter_priority' => 'top', 'sort_order' => 1,
        ]);
        $this->class->attributes()->attach($this->thickness->id, [
            'is_required' => false, 'is_filterable' => true, 'filter_priority' => 'top', 'unit' => 'mm', 'sort_order' => 2,
        ]);

        $seller = User::factory()->seller()->create();

        $this->red = $this->product($seller, 'Laminat Palıd qırmızı');
        ProductAttributeValue::create(['product_id' => $this->red->id, 'attribute_id' => $this->color->id, 'attribute_option_id' => $redOpt->id]);
        ProductAttributeValue::create(['product_id' => $this->red->id, 'attribute_id' => $this->thickness->id, 'value_numeric' => 8]);

        $this->white = $this->product($seller, 'Laminat Qoz ağ');
        ProductAttributeValue::create(['product_id' => $this->white->id, 'attribute_id' => $this->color->id, 'attribute_option_id' => $whiteOpt->id]);
        ProductAttributeValue::create(['product_id' => $this->white->id, 'attribute_id' => $this->thickness->id, 'value_numeric' => 12]);

        // No attribute values at all — must still show up while no filter is active.
        $this->bare = $this->product($seller, 'Laminat sadə model');
    }

    // ─── Drill-down scoping ─────────────────────────────────────────────

    public function test_class_page_lists_all_class_products_when_no_attribute_filter_is_active(): void
    {
        $this->get('/catalog?class=laminat')
            ->assertOk()
            ->assertSeeText('Laminat Palıd qırmızı')
            ->assertSeeText('Laminat Qoz ağ')
            ->assertSeeText('Laminat sadə model');
    }

    public function test_section_and_group_pages_scope_to_the_classifier_subtree(): void
    {
        $otherSeller = User::factory()->seller()->create();
        Product::create([
            'user_id' => $otherSeller->id,
            'category_id' => Category::create(['name' => ['az' => 'Başqa'], 'slug' => 'basqa', 'is_active' => true])->id,
            'name' => ['az' => 'Sement kisəsi'], 'description' => ['az' => ''],
            'slug' => 'sement-kisesi', 'price' => 5, 'stock' => 3,
            'is_visible' => true, 'is_approved' => true,
        ]);

        $this->get('/catalog?section=dosemeler')
            ->assertOk()
            ->assertSeeText('Laminat Palıd qırmızı')
            ->assertDontSeeText('Sement kisəsi');

        $this->get('/catalog?group=taxta-ortukler')
            ->assertOk()
            ->assertSeeText('Laminat Qoz ağ')
            ->assertDontSeeText('Sement kisəsi');
    }

    // ─── Dynamic attribute filters ──────────────────────────────────────

    public function test_catalog_filters_by_attribute_option(): void
    {
        $this->get('/catalog?class=laminat&attr[reng]=qirmizi')
            ->assertOk()
            ->assertSeeText('Laminat Palıd qırmızı')
            ->assertDontSeeText('Laminat Qoz ağ')
            ->assertDontSeeText('Laminat sadə model');
    }

    public function test_option_filter_is_or_within_one_attribute(): void
    {
        $this->get('/catalog?class=laminat&attr[reng]=qirmizi,ag')
            ->assertOk()
            ->assertSeeText('Laminat Palıd qırmızı')
            ->assertSeeText('Laminat Qoz ağ')
            ->assertDontSeeText('Laminat sadə model');
    }

    public function test_catalog_filters_by_numeric_range(): void
    {
        $this->get('/catalog?class=laminat&attr_min[qalinliq]=10')
            ->assertOk()
            ->assertSeeText('Laminat Qoz ağ')
            ->assertDontSeeText('Laminat Palıd qırmızı');

        $this->get('/catalog?class=laminat&attr_min[qalinliq]=6&attr_max[qalinliq]=9')
            ->assertOk()
            ->assertSeeText('Laminat Palıd qırmızı')
            ->assertDontSeeText('Laminat Qoz ağ');
    }

    public function test_filters_combine_with_and_between_attributes(): void
    {
        $this->get('/catalog?class=laminat&attr[reng]=qirmizi&attr_min[qalinliq]=10')
            ->assertOk()
            ->assertDontSeeText('Laminat Palıd qırmızı')
            ->assertDontSeeText('Laminat Qoz ağ')
            ->assertSee('cat-empty', false); // nothing matches both filters at once
    }

    public function test_class_page_renders_top_filter_markup_with_unit_and_tooltip(): void
    {
        $response = $this->get('/catalog?class=laminat')->assertOk();

        $response->assertSee('data-attr-block="reng"', false);
        $response->assertSee('data-opt="qirmizi"', false);
        $response->assertSee('data-attr-min', false);
        $response->assertSee('(mm)', false);          // pivot unit next to the numeric filter title
        $response->assertSee('Səthin əsas rəngi');    // tooltip text
    }

    // ─── Synonym search ─────────────────────────────────────────────────

    public function test_synonym_phrase_search_finds_the_class_products(): void
    {
        SearchSynonym::create(['sub_category_id' => $this->class->id, 'phrase' => 'kvars vinil plitə', 'locale' => 'az']);

        // No product name contains "kvars vinil" — only the synonym links it.
        $ids = Product::visible()->approved()->search('kvars vinil')->pluck('id');
        $this->assertTrue($ids->contains($this->red->id));
        $this->assertTrue($ids->contains($this->bare->id));

        $this->get('/catalog?'.http_build_query(['q' => 'kvars vinil']))
            ->assertOk()
            ->assertSeeText('Laminat Palıd qırmızı');

        // Reverse containment: the query contains the whole phrase.
        $this->assertTrue(
            Product::visible()->approved()->search('ucuz kvars vinil plitə almaq')->pluck('id')->contains($this->red->id)
        );
    }

    public function test_class_name_search_finds_the_class_products_without_name_match(): void
    {
        // "лам" fragments aside — search the class name in a way the product
        // names do not contain ("laminat" IS in the names here, so use a class
        // whose name differs from its products').
        $quartz = SubCategory::create([
            'name' => ['az' => 'Kvars-vinil örtük'], 'slug' => 'kvars-vinil',
            'category_id' => $this->group->id, 'is_active' => true, 'sort_order' => 2,
        ]);
        $this->bare->update(['sub_category_id' => $quartz->id]);

        $ids = Product::visible()->approved()->search('kvars-vinil örtük')->pluck('id');
        $this->assertTrue($ids->contains($this->bare->id));
    }

    public function test_short_queries_do_not_trigger_class_matching(): void
    {
        SearchSynonym::create(['sub_category_id' => $this->class->id, 'phrase' => 'ab', 'locale' => 'az']);

        $this->assertSame(0, Product::visible()->approved()->search('ab')->count());
    }

    // ─── Navigation seeder ──────────────────────────────────────────────

    public function test_navigation_seeder_is_idempotent_and_retires_legacy_cards(): void
    {
        MenuItem::create([
            'location' => 'header_mega_catalog',
            'label' => ['az' => 'Tikinti materialları'],
            'url' => '/catalog?category=tikinti',
            'sort_order' => 1,
        ]);

        $this->seed(NavigationSeeder::class);

        // Only the sections that exist get a card; the legacy card is gone.
        $this->assertSame(1, MenuItem::location('header_mega_catalog')->count());
        $item = MenuItem::location('header_mega_catalog')->first();
        $this->assertSame('/catalog?section=dosemeler', $item->url);
        $this->assertSame('Döşəmələr', $item->getTranslation('label', 'az'));

        $this->seed(NavigationSeeder::class);
        $this->assertSame(1, MenuItem::location('header_mega_catalog')->count());
    }

    public function test_navigation_seeder_repoints_the_footer_products_column_at_live_sections(): void
    {
        $column = MenuItem::create([
            'location' => 'footer', 'label' => ['az' => 'Məhsullar'], 'is_clickable' => false, 'sort_order' => 1,
        ]);
        $legacy = collect([
            ['az' => 'Kafel & metlax', 'url' => '/catalog?category=kafel', 'sort_order' => 1],
            ['az' => 'Boya & emal', 'url' => '/catalog?category=boya', 'sort_order' => 2],
            ['az' => 'Santexnika', 'url' => '/catalog?category=santexnika', 'sort_order' => 3],
            ['az' => 'İzolyasiya & istilik', 'url' => '/catalog?category=izolyasiya', 'sort_order' => 4],
            ['az' => 'Bütün kateqoriyalar', 'url' => '/catalog', 'sort_order' => 5],
        ])->map(fn (array $row) => MenuItem::create([
            'location' => 'footer', 'parent_id' => $column->id,
            'label' => ['az' => $row['az']], 'url' => $row['url'], 'sort_order' => $row['sort_order'],
        ]));

        $this->seed(NavigationSeeder::class);

        // Only "dosemeler" exists in this fixture, so the column keeps that one
        // section plus the closing "all categories" row — the dead slugs are gone.
        $children = MenuItem::location('footer')->where('parent_id', $column->id)->ordered()->get();
        $this->assertSame(
            ['/catalog?section=dosemeler', '/catalog'],
            $children->pluck('url')->all()
        );
        // Rows are corrected in place, never duplicated.
        $this->assertSame($legacy->first()->id, $children->first()->id);
        $this->assertSame('Döşəmələr', $children->first()->getTranslation('label', 'az'));

        $this->seed(NavigationSeeder::class);
        $this->assertSame(2, MenuItem::location('footer')->where('parent_id', $column->id)->count());
    }

    // ─── Legacy taxonomy ────────────────────────────────────────────────

    public function test_retired_category_slug_falls_back_to_the_unfiltered_catalog(): void
    {
        Category::create([
            'name' => ['az' => 'Kafel'], 'slug' => 'kafel', 'is_active' => false, 'sort_order' => 1,
        ]);

        // The retired root has no products of its own; before the fix the page
        // rendered with its name and an empty grid. It now behaves exactly like
        // an unknown slug: the filter is ignored, the full catalog is shown.
        $this->get('/catalog?category=kafel')
            ->assertOk()
            ->assertSeeText('Laminat Palıd qırmızı')
            ->assertDontSee('cat-empty', false);
    }

    // ─── Cluster-aware header navigation ────────────────────────────────

    public function test_kataloq_mega_panel_and_mobile_drawer_group_sections_into_clusters(): void
    {
        Category::create([
            'name' => ['az' => 'Dam örtüyü və fasad'], 'slug' => 'dam-ortuyu-ve-fasad',
            'is_active' => true, 'sort_order' => 9,
        ]);

        MenuItem::create([
            'location' => 'header_main', 'label' => ['az' => 'Kataloq'], 'route_name' => 'catalog',
            'css_class' => 'catalog', 'has_dropdown' => true, 'sort_order' => 1,
        ]);
        MenuItem::create(['location' => 'header_mega_catalog', 'label' => ['az' => 'Döşəmələr'], 'url' => '/catalog?section=dosemeler', 'sort_order' => 1]);
        MenuItem::create(['location' => 'header_mega_catalog', 'label' => ['az' => 'Dam örtüyü və fasad'], 'url' => '/catalog?section=dam-ortuyu-ve-fasad', 'sort_order' => 2]);
        MenuItem::create(['location' => 'header_mega_catalog', 'label' => ['az' => 'Endirimlər'], 'url' => '/catalog?sale=1', 'sort_order' => 3]);

        $html = $this->get('/')->assertOk()->getContent();

        // Desktop: five-cluster grid, each heading immediately followed by its
        // sections (labels come from the catalog rail's own translation keys).
        $construction = preg_quote((string) t('catalog-classifier.clusters.construction'), '#');
        $openings = preg_quote((string) t('catalog-classifier.clusters.openings'), '#');

        $this->assertStringContainsString('mega-cats mega-clusters', $html);
        $this->assertMatchesRegularExpression(
            '#<p class="mclus-h">'.$construction.'</p>\s*<a class="mcat mclus-link" href="/catalog\?section=dosemeler"#',
            $html
        );
        $this->assertMatchesRegularExpression(
            '#<p class="mclus-h">'.$openings.'</p>\s*<a class="mcat mclus-link" href="/catalog\?section=dam-ortuyu-ve-fasad"#',
            $html
        );
        // A card that is not a section keeps its place in an unlabelled block.
        $this->assertStringContainsString('href="/catalog?sale=1"', $html);

        // Mobile drawer: the same nesting as indented sub-links.
        $this->assertMatchesRegularExpression(
            '#<p class="mob-group">'.$construction.'</p>\s*<a class="mob-link mob-sub" href="/catalog\?section=dosemeler"#',
            $html
        );
    }

    public function test_mega_panel_keeps_the_flat_card_grid_when_no_card_maps_to_a_section(): void
    {
        MenuItem::create(['location' => 'header_mega_catalog', 'label' => ['az' => 'Tikinti materialları'], 'url' => '/catalog?category=tikinti', 'sort_order' => 1]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('mega-clusters', false)
            ->assertSee('href="/catalog?category=tikinti"', false);
    }

    // ─── Homepage category tiles ────────────────────────────────────────

    public function test_homepage_tiles_count_the_whole_classifier_subtree(): void
    {
        $response = $this->get('/')->assertOk();

        // Three products hang off the GROUP/CLASS, never off the section itself —
        // withCount('products') on the root returned 0 for every tile.
        $tile = $response->viewData('categories')->firstWhere('slug', 'dosemeler');
        $this->assertNotNull($tile);
        $this->assertSame(3, (int) $tile->products_count);

        // …and the tile links with the classifier param the count is computed over.
        $response->assertSee('href="'.route('catalog', ['section' => 'dosemeler']).'"', false);
    }

    // ─── Product page structured specs ──────────────────────────────────

    public function test_product_page_shows_structured_attribute_values(): void
    {
        $response = $this->get('/product/'.$this->red->slug)->assertOk();

        $response->assertSee('data-pane="specs"', false);
        $response->assertSeeText('Rəng');
        $response->assertSeeText('Qırmızı');
        $response->assertSeeText('8 mm');            // trimmed decimal + pivot unit
        $response->assertSee('pd-spec-tip', false);  // tooltip bubble on the professional attribute
    }

    public function test_specs_tab_stays_hidden_when_both_sources_are_empty(): void
    {
        $this->get('/product/'.$this->bare->slug)
            ->assertOk()
            ->assertDontSee('data-pane="specs"', false);
    }

    // ─── Helpers ────────────────────────────────────────────────────────

    private function product(User $seller, string $name): Product
    {
        return Product::create([
            'user_id' => $seller->id,
            'category_id' => $this->group->id,
            'sub_category_id' => $this->class->id,
            'name' => ['az' => $name],
            'description' => ['az' => ''],
            'slug' => 'p-'.uniqid(),
            'price' => 10,
            'stock' => 10,
            'is_visible' => true,
            'is_approved' => true,
        ]);
    }
}
