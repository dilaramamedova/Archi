<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CalculatorQuickRate;
use App\Support\VersionedCache;
use Database\Seeders\CalculatorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The two storefront pages. What is asserted here is mostly that no price is baked
 * into the markup: the six rates travel with the page as data, so an admin edit shows
 * up on the next request (TZ §30, §34).
 */
class CalculatorPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CalculatorSeeder::class);
    }

    public function test_the_quick_calculator_page_ships_the_price_matrix(): void
    {
        $response = $this->get('/calculator');

        $response->assertOk()
            ->assertSee('data-quick', false)
            // The two renovation types of the TZ, and nothing else.
            ->assertSee('White Box')
            ->assertSee('Tam təmir')
            ->assertSee('Ekonom')
            ->assertSee('Premium');

        // The matrix itself, JSON-encoded into the attribute.
        $response->assertSee('&quot;WHITE_BOX&quot;:{&quot;ECONOMY&quot;:350', false);
    }

    public function test_an_edited_rate_reaches_the_page_markup(): void
    {
        CalculatorQuickRate::where('renovation_type', 'WHITE_BOX')
            ->where('segment', 'ECONOMY')
            ->update(['price_per_m2' => 399]);

        VersionedCache::bump(VersionedCache::CALCULATOR);

        $this->get('/calculator')->assertSee('&quot;WHITE_BOX&quot;:{&quot;ECONOMY&quot;:399', false);
    }

    public function test_the_detailed_calculator_page_ships_the_work_catalog_and_classifiers(): void
    {
        $response = $this->get('/calculator/detailed');

        $response->assertOk()
            ->assertSee('data-config', false)
            ->assertSee('WALL_PLASTER', false)
            ->assertSee('FLOOR_TILE', false);

        // The works step must not be able to print a rate: the catalog handed to the
        // browser deliberately carries no price (master spec §10, §20).
        $this->assertStringNotContainsString('"rate"', $response->getContent());
    }

    /** TZ §47 — the quick calculator's answers survive the jump. */
    public function test_the_detailed_page_accepts_the_quick_calculator_parameters(): void
    {
        $this->get('/calculator/detailed?area=120&type=full-renovation&segment=premium')
            ->assertOk()
            ->assertSee('&quot;area&quot;:120', false)
            ->assertSee('FULL_RENOVATION', false)
            ->assertSee('&quot;segment&quot;:&quot;PREMIUM&quot;', false);
    }

    public function test_the_config_endpoint_describes_the_calculator_without_exposing_rates(): void
    {
        $response = $this->getJson('/api/calculator/config');

        $response->assertOk()
            ->assertJsonPath('currency', 'AZN')
            ->assertJsonPath('quick.prices.WHITE_BOX.STANDARD', 450);

        $works = collect($response->json('works'));

        $this->assertGreaterThan(100, $works->count());
        $this->assertFalse($works->first()->has ?? false);
        $this->assertArrayNotHasKey('rate', $works->first());
    }
}
