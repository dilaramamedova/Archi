<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Product;
use App\Models\SpecialistProfile;
use App\Models\SpecialistSpecialty;
use App\Models\User;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    // ─── The core bug: json columns compared case-sensitively ──────────

    public function test_lowercase_query_finds_capitalised_product_name(): void
    {
        $seller = User::factory()->seller()->create();
        $this->product($seller, ['name' => ['az' => 'Keramik kafel 30x60 ağ mat']]);

        $this->assertSame(1, Product::visible()->approved()->search('keramik')->count());
        $this->assertSame(1, Product::visible()->approved()->search('KERAMIK')->count());
        $this->assertSame(1, Product::visible()->approved()->search('Keramik')->count());
    }

    public function test_catalog_finds_products_regardless_of_case(): void
    {
        $seller = User::factory()->seller()->create();
        $this->product($seller, ['name' => ['az' => 'Laminat döşəmə 8mm Palıd']]);
        $this->product($seller, ['name' => ['az' => 'LED panel işıq 60x60']]);

        $this->get('/catalog?'.http_build_query(['q' => 'Palıd']))->assertOk()->assertSee('Palıd');
        $this->get('/catalog?'.http_build_query(['q' => 'palıd']))->assertOk()->assertSee('Palıd');
        $this->get('/catalog?q=LED')->assertOk()->assertSee('LED panel');
        $this->get('/catalog?q=led')->assertOk()->assertSee('LED panel');
    }

    public function test_search_matches_non_default_locale_values(): void
    {
        $seller = User::factory()->seller()->create();
        $this->product($seller, ['name' => ['az' => 'Sement M400', 'ru' => 'Цемент М400', 'en' => 'Cement M400']]);

        $this->assertSame(1, Product::visible()->approved()->search('цемент')->count());
        $this->assertSame(1, Product::visible()->approved()->search('CEMENT')->count());
    }

    // ─── Tokenisation ──────────────────────────────────────────────────

    public function test_multi_word_query_finds_the_matching_product(): void
    {
        $seller = User::factory()->seller()->create();
        $target = $this->product($seller, ['name' => ['az' => 'Keramik kafel 30x60 ağ mat']]);
        $this->product($seller, ['name' => ['az' => 'Metal dam örtüyü']]);

        $ids = Product::visible()->approved()->search('Keramik kafel 30')->pluck('id');

        $this->assertTrue($ids->contains($target->id));
    }

    public function test_multi_word_query_ranks_the_phrase_match_first(): void
    {
        $seller = User::factory()->seller()->create();
        $this->product($seller, ['name' => ['az' => 'Porselan kafel 30x30']]);
        $target = $this->product($seller, ['name' => ['az' => 'Keramik kafel 30x60 ağ mat']]);

        $this->get('/search?'.http_build_query(['q' => 'Keramik kafel 30']))
            ->assertOk()
            ->assertSeeText('Keramik kafel 30x60 ağ mat');

        $query = Product::visible()->approved()->search('Keramik kafel 30');
        SearchService::orderProductsByRelevance($query, 'Keramik kafel 30');

        $this->assertSame($target->id, $query->first()->id);
    }

    public function test_unrelated_multi_word_query_returns_nothing(): void
    {
        $seller = User::factory()->seller()->create();
        $this->product($seller, ['name' => ['az' => 'Keramik kafel 30x60 ağ mat']]);

        $this->assertSame(0, Product::visible()->approved()->search('mebel divanı')->count());
    }

    /**
     * The originally reported false positive: "Keramik kafel 30" returned four
     * unrelated tiles. Synonym expansion of the whole phrase used to emit a bare
     * "kafel" (plus "tile"/"plitka"/"плитка") as a standalone OR term, so every
     * tile matched even though nothing in the catalogue is "Keramik".
     */
    public function test_multi_word_query_does_not_match_on_one_word_alone(): void
    {
        $seller = User::factory()->seller()->create();
        $this->product($seller, ['name' => ['az' => 'Mərmər kafel 60x60', 'en' => 'Marble tile 60x60']]);
        $this->product($seller, ['name' => ['az' => 'Dekorativ divar kafeli', 'en' => 'Decorative wall tile']]);
        $this->product($seller, ['name' => ['az' => 'Porselan kafel 30x30', 'en' => 'Porcelain tile 30x30']]);
        $this->product($seller, ['name' => ['az' => 'Metal dam örtüyü', 'en' => 'Metal roofing tile']]);

        // No product is "Keramik", so nothing may come back.
        $this->assertSame(0, Product::visible()->approved()->search('Keramik kafel 30')->count());
        $this->assertSame(0, Product::visible()->approved()->search('keramik kafel 30')->count());

        $this->get('/catalog?'.http_build_query(['q' => 'Keramik kafel 30']))
            ->assertOk()
            ->assertDontSeeText('Mərmər kafel 60x60')
            ->assertDontSeeText('Metal dam örtüyü');
    }

    /**
     * A phrase must still narrow the result set rather than widening it to every
     * product sharing a synonym with one of its words.
     */
    public function test_phrase_narrows_instead_of_widening_via_synonyms(): void
    {
        $seller = User::factory()->seller()->create();
        $target = $this->product($seller, ['name' => ['az' => 'Mərmər kafel 60x60', 'en' => 'Marble tile 60x60']]);
        $this->product($seller, ['name' => ['az' => 'Porselan kafel 30x30', 'en' => 'Porcelain tile 30x30']]);
        $this->product($seller, ['name' => ['az' => 'Metal dam örtüyü', 'en' => 'Metal roofing tile']]);

        $ids = Product::visible()->approved()->search('Mərmər kafel')->pluck('id');

        $this->assertSame([$target->id], $ids->all());

        // Diacritics-stripped spelling must behave identically.
        $this->assertSame(
            [$target->id],
            Product::visible()->approved()->search('mermer kafel')->pluck('id')->all()
        );
    }

    /**
     * The single-word case must keep the full synonym expansion — narrowing the
     * phrase branch must not break "kafel" finding every tile.
     */
    public function test_single_word_query_still_expands_synonyms_broadly(): void
    {
        $seller = User::factory()->seller()->create();
        $this->product($seller, ['name' => ['az' => 'Mərmər kafel 60x60', 'en' => 'Marble tile 60x60']]);
        $this->product($seller, ['name' => ['az' => 'Porselan kafel 30x30', 'en' => 'Porcelain tile 30x30']]);
        $this->product($seller, ['name' => ['az' => 'Portland sementi M400', 'en' => 'Portland cement M400']]);

        $this->assertSame(2, Product::visible()->approved()->search('kafel')->count());
        $this->assertSame(2, Product::visible()->approved()->search('plitka')->count());
        $this->assertSame(2, Product::visible()->approved()->search('TILE')->count());
    }

    // ─── Category / brand / synonyms ───────────────────────────────────

    public function test_category_name_search_returns_that_categorys_products(): void
    {
        $seller = User::factory()->seller()->create();
        $category = Category::create([
            'name' => ['az' => 'Santexnika'],
            'slug' => 'santexnika',
            'is_active' => true,
        ]);

        $this->product($seller, ['name' => ['az' => 'Kran qarışdırıcı krom'], 'category_id' => $category->id]);

        $this->assertSame(1, Product::visible()->approved()->search('santexnika')->count());
        $this->assertSame(1, Product::visible()->approved()->search('SANTEXNIKA')->count());
    }

    public function test_synonym_expansion_still_works(): void
    {
        $seller = User::factory()->seller()->create();
        $this->product($seller, ['name' => ['az' => 'Keramik kafel 30x60 ağ mat']]);

        // "plitka" is a synonym of "kafel"
        $this->assertSame(1, Product::visible()->approved()->search('plitka')->count());
    }

    public function test_diacritics_stripped_query_finds_azerbaijani_name(): void
    {
        $seller = User::factory()->seller()->create();
        $this->product($seller, ['name' => ['az' => 'Laminat döşəmə 8mm Palıd']]);

        $this->assertSame(1, Product::visible()->approved()->search('doseme')->count());
        $this->assertSame(1, Product::visible()->approved()->search('palid')->count());
    }

    // ─── Other entry points ────────────────────────────────────────────

    public function test_specialist_search_is_case_insensitive(): void
    {
        $this->specialist('Rəşad Məmmədov', 'Santexnik');

        $this->get('/specialists?q=santexnik')->assertOk()->assertSee('Rəşad');
        $this->get('/specialists?q=SANTEXNIK')->assertOk()->assertSee('Rəşad');
        $this->get('/specialists?q=santexnika')->assertOk()->assertSee('Rəşad');
    }

    public function test_search_page_finds_products_and_masters(): void
    {
        $seller = User::factory()->seller()->create();
        $category = Category::create([
            'name' => ['az' => 'Santexnika'],
            'slug' => 'santexnika',
            'is_active' => true,
        ]);
        $this->product($seller, ['name' => ['az' => 'Kran qarışdırıcı krom'], 'category_id' => $category->id]);
        $this->specialist('Rəşad Məmmədov', 'Santexnik');

        $this->get('/search?q=santexnika')
            ->assertOk()
            ->assertSeeText('Kran qarışdırıcı krom')
            ->assertSeeText('Rəşad');
    }

    public function test_autocomplete_endpoint_is_case_insensitive(): void
    {
        $seller = User::factory()->seller()->create();
        $this->product($seller, ['name' => ['az' => 'LED panel işıq 60x60']]);

        $response = $this->getJson('/api/search?q=led')->assertOk();

        $this->assertGreaterThan(0, $response->json('total'));
        $this->assertSame('LED panel işıq 60x60', $response->json('products.0.name'));
    }

    public function test_blog_search_is_case_insensitive(): void
    {
        BlogPost::create([
            'title' => ['az' => 'Evdə təmir: haradan başlamalı?'],
            'excerpt' => ['az' => 'Qısa məlumat'],
            'body' => ['az' => 'Mətn'],
            'slug' => 'evde-temir',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->get('/blog?'.http_build_query(['q' => 'təmir']))->assertOk()->assertSeeText('Evdə təmir');
        $this->get('/blog?'.http_build_query(['q' => 'TƏMİR']))->assertOk()->assertSeeText('Evdə təmir');
    }

    // ─── Helpers ───────────────────────────────────────────────────────

    private function product(User $seller, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'user_id' => $seller->id,
            'category_id' => Category::query()->create([
                'name' => ['az' => 'Kateqoriya'],
                'slug' => 'cat-'.uniqid(),
                'is_active' => true,
            ])->id,
            'name' => ['az' => 'Məhsul'],
            'description' => ['az' => ''],
            'slug' => 'p-'.uniqid(),
            'price' => 10,
            'stock' => 10,
            'is_visible' => true,
            'is_approved' => true,
        ], $overrides));
    }

    private function specialist(string $name, string $craft): SpecialistProfile
    {
        $user = User::factory()->master()->create([
            'name' => $name,
            'first_name' => explode(' ', $name)[0],
            'last_name' => explode(' ', $name)[1] ?? '',
        ]);

        // SpecialistProfile derives `craft` from the specialty on save.
        $specialty = SpecialistSpecialty::firstOrCreate(
            ['slug' => Str::slug($craft)],
            ['name' => $craft, 'is_active' => true],
        );

        return SpecialistProfile::create([
            'user_id' => $user->id,
            'specialist_specialty_id' => $specialty->id,
            'craft' => $craft,
            'city' => 'Bakı',
        ]);
    }
}
