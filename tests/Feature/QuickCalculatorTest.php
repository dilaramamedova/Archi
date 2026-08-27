<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Calculator\PriceSegment;
use App\Enums\Calculator\RenovationType;
use App\Models\CalculatorQuickRate;
use App\Models\CalculatorSetting;
use App\Services\Calculator\QuickCalculator;
use App\Support\VersionedCache;
use Database\Seeders\CalculatorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The acceptance criteria of the client's TZ (§50), asserted against the engine
 * rather than against the browser: AC-01…AC-06 are the price matrix, AC-13…AC-16 are
 * the formatting, the admin-editability and the input validation.
 */
class QuickCalculatorTest extends TestCase
{
    use RefreshDatabase;

    private QuickCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CalculatorSeeder::class);
        $this->calculator = app(QuickCalculator::class);
    }

    /** AC-01 … AC-06 — the six approved prices, for a 100 m² object. */
    public static function acceptanceMatrix(): array
    {
        return [
            'AC-01 white box · economy' => ['WHITE_BOX', 'ECONOMY', 350.0, 35000.0],
            'AC-02 white box · standard' => ['WHITE_BOX', 'STANDARD', 450.0, 45000.0],
            'AC-03 white box · premium' => ['WHITE_BOX', 'PREMIUM', 550.0, 55000.0],
            'AC-04 full renovation · economy' => ['FULL_RENOVATION', 'ECONOMY', 500.0, 50000.0],
            'AC-05 full renovation · standard' => ['FULL_RENOVATION', 'STANDARD', 650.0, 65000.0],
            'AC-06 full renovation · premium' => ['FULL_RENOVATION', 'PREMIUM', 800.0, 80000.0],
        ];
    }

    #[DataProvider('acceptanceMatrix')]
    public function test_the_six_approved_prices_produce_the_expected_totals(
        string $type,
        string $segment,
        float $pricePerM2,
        float $expected,
    ): void {
        $result = $this->calculator->calculate(100, RenovationType::from($type), PriceSegment::from($segment));

        $this->assertTrue($result['valid']);
        $this->assertSame($pricePerM2, $result['pricePerM2']);
        $this->assertSame($expected, $result['total']);
    }

    /** The TZ's worked examples (§14) and control table (§51). */
    public static function controlTable(): array
    {
        return [
            '50 m² · white box · economy' => [50, 'WHITE_BOX', 'ECONOMY', 17500.0],
            '50 m² · full renovation · premium' => [50, 'FULL_RENOVATION', 'PREMIUM', 40000.0],
            '80 m² · white box · standard' => [80, 'WHITE_BOX', 'STANDARD', 36000.0],
            '80 m² · full renovation · standard' => [80, 'FULL_RENOVATION', 'STANDARD', 52000.0],
            '80 m² · full renovation · premium' => [80, 'FULL_RENOVATION', 'PREMIUM', 64000.0],
            '120 m² · white box · standard' => [120, 'WHITE_BOX', 'STANDARD', 54000.0],
            '120 m² · white box · premium' => [120, 'WHITE_BOX', 'PREMIUM', 66000.0],
            '120 m² · full renovation · premium' => [120, 'FULL_RENOVATION', 'PREMIUM', 96000.0],
        ];
    }

    #[DataProvider('controlTable')]
    public function test_control_table_of_the_specification(int $area, string $type, string $segment, float $expected): void
    {
        $result = $this->calculator->calculate($area, RenovationType::from($type), PriceSegment::from($segment));

        $this->assertSame($expected, $result['total']);
    }

    public function test_the_defaults_show_a_result_the_moment_the_calculator_opens(): void
    {
        // TZ §16: 80 m² · White Box · Standart = 36 000 AZN before the visitor
        // touches anything.
        $result = $this->calculator->calculate(
            $this->calculator->defaultArea(),
            $this->calculator->defaultRenovationType(),
            $this->calculator->defaultSegment(),
        );

        $this->assertSame(80.0, $this->calculator->defaultArea());
        $this->assertSame(36000.0, $result['total']);
    }

    /** AC-16 — a bad area never produces a wrong price. */
    public function test_invalid_areas_are_rejected_rather_than_priced(): void
    {
        $type = RenovationType::WhiteBox;
        $segment = PriceSegment::Standard;

        foreach ([null, '', 'abc'] as $empty) {
            $result = $this->calculator->calculate($empty, $type, $segment);
            $this->assertFalse($result['valid']);
            $this->assertSame('AREA_REQUIRED', $result['error']);
            $this->assertNull($result['total']);
        }

        $this->assertSame('AREA_MUST_BE_POSITIVE', $this->calculator->calculate(0, $type, $segment)['error']);
        $this->assertSame('AREA_MUST_BE_POSITIVE', $this->calculator->calculate(-50, $type, $segment)['error']);
        $this->assertSame('AREA_BELOW_MINIMUM', $this->calculator->calculate(5, $type, $segment)['error']);
        $this->assertSame('AREA_ABOVE_MAXIMUM', $this->calculator->calculate(20000, $type, $segment)['error']);
    }

    /**
     * Master spec audit §2.3: a combination without an approved rate must say so,
     * never show 0 AZN or NaN.
     */
    public function test_a_missing_rate_is_reported_instead_of_priced_at_zero(): void
    {
        CalculatorQuickRate::where('renovation_type', 'FULL_RENOVATION')
            ->where('segment', 'PREMIUM')
            ->update(['price_per_m2' => null]);

        VersionedCache::bump(VersionedCache::CALCULATOR);

        $result = app(QuickCalculator::class)->calculate(100, RenovationType::FullRenovation, PriceSegment::Premium);

        $this->assertFalse($result['valid']);
        $this->assertSame('PRICE_NOT_AVAILABLE', $result['error']);
        $this->assertNull($result['total']);
    }

    /** AC-14 — all six prices are editable without touching a line of code. */
    public function test_an_admin_price_change_is_used_by_the_next_calculation(): void
    {
        CalculatorQuickRate::where('renovation_type', 'WHITE_BOX')
            ->where('segment', 'STANDARD')
            ->update(['price_per_m2' => 500]);

        VersionedCache::bump(VersionedCache::CALCULATOR);

        $result = app(QuickCalculator::class)->calculate(100, RenovationType::WhiteBox, PriceSegment::Standard);

        $this->assertSame(50000.0, $result['total']);
    }

    /** The limits are configuration, not constants in the code (TZ §6). */
    public function test_area_limits_come_from_the_settings_table(): void
    {
        CalculatorSetting::where('key', 'QUICK_MIN_AREA')->update(['value' => '25']);
        VersionedCache::bump(VersionedCache::CALCULATOR);

        $calculator = app(QuickCalculator::class);

        $this->assertSame(25.0, $calculator->minArea());
        $this->assertSame('AREA_BELOW_MINIMUM', $calculator->calculate(20, RenovationType::WhiteBox, PriceSegment::Standard)['error']);
    }

    public function test_the_public_endpoint_hands_the_matrix_to_the_frontend_once(): void
    {
        $response = $this->getJson('/api/calculator/quick-rates');

        $response->assertOk()
            ->assertJsonPath('prices.WHITE_BOX.STANDARD', 450)
            ->assertJsonPath('prices.FULL_RENOVATION.PREMIUM', 800)
            ->assertJsonPath('defaults.area', 80)
            ->assertJsonPath('limits.minArea', 10)
            ->assertJsonPath('limits.maxArea', 10000);
    }

    /** TZ §47 — the three answers travel to the detailed calculator. */
    public function test_the_cta_hands_the_selection_over_to_the_detailed_calculator(): void
    {
        $url = $this->calculator->handoffUrl(120, RenovationType::FullRenovation, PriceSegment::Premium);

        $this->assertStringContainsString('area=120', $url);
        $this->assertStringContainsString('type=full-renovation', $url);
        $this->assertStringContainsString('segment=premium', $url);
    }
}
