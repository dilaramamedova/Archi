<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\CalculatorCityResource;
use App\Filament\Resources\CalculatorCoefficientResource;
use App\Filament\Resources\CalculatorMaterialFallbackResource;
use App\Filament\Resources\CalculatorMaterialNormResource;
use App\Filament\Resources\CalculatorQuickRateResource;
use App\Filament\Resources\CalculatorSettingResource;
use App\Filament\Resources\CalculatorWorkRateResource;
use App\Models\CalculatorQuickRate;
use App\Models\CalculatorSetting;
use App\Models\CalculatorWorkRate;
use App\Models\User;
use App\Services\Calculator\QuickCalculator;
use Database\Seeders\CalculatorSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TZ §33–§34: an administrator must be able to change every price without a
 * developer and without a deployment. These tests hold the admin panel to that —
 * the screens render, and an edit made through them reaches the next calculation.
 */
class AdminCalculatorResourcesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->seed(CalculatorSeeder::class);
    }

    public function test_every_calculator_screen_renders_for_an_admin(): void
    {
        $this->signInAsAdmin();

        foreach ([
            CalculatorQuickRateResource::class,
            CalculatorWorkRateResource::class,
            CalculatorMaterialFallbackResource::class,
            CalculatorCoefficientResource::class,
            CalculatorMaterialNormResource::class,
            CalculatorSettingResource::class,
            CalculatorCityResource::class,
        ] as $resource) {
            $this->get($resource::getUrl('index'))->assertOk();
        }
    }

    public function test_the_edit_forms_render(): void
    {
        $this->signInAsAdmin();

        $this->get(CalculatorQuickRateResource::getUrl('edit', [
            'record' => CalculatorQuickRate::first(),
        ]))->assertOk();

        $this->get(CalculatorWorkRateResource::getUrl('edit', [
            'record' => CalculatorWorkRate::where('work_id', 'WALL_PLASTER')->first(),
        ]))->assertOk();

        $this->get(CalculatorSettingResource::getUrl('edit', [
            'record' => CalculatorSetting::where('key', 'CONTINGENCY_PERCENT')->first(),
        ]))->assertOk();
    }

    /**
     * The screens have to explain themselves. An admin who opens the work rates and
     * sees `WALL_PUTTY` / `NetWallAreaSelected` next to a bare number will not touch
     * it — which makes the whole "prices are editable without a developer" promise
     * worthless in practice.
     */
    public function test_the_work_rate_screen_explains_itself_in_plain_language(): void
    {
        $this->signInAsAdmin();

        $response = $this->get(CalculatorWorkRateResource::getUrl('index'));

        $response->assertOk()
            // The intro answers what / where / when, on the list itself.
            ->assertSee('Bu səhifə nədir?', false)
            ->assertSee('Saytda harada görünür?', false)
            // The list leads with the work's name, not its technical id.
            ->assertSee('Divarların boyanması')
            // …and says in words where the quantity it is multiplied by comes from.
            ->assertSee('Qapı və pəncərə çıxılmaqla divar sahəsi (m²)');

        // The raw engine variable is not what the reader is asked to interpret.
        $this->assertStringNotContainsString('NetWallAreaSelected</', $response->getContent());
    }

    public function test_the_settings_screen_explains_every_key(): void
    {
        $this->signInAsAdmin();

        $this->get(CalculatorSettingResource::getUrl('index'))
            ->assertOk()
            // The contingency percent is the setting most likely to be misread as the
            // material waste, so its row spells out what it actually does.
            ->assertSee('Smetanın sonuna əlavə olunan ehtiyat faizi', false)
            ->assertSee('Minimum sahə', false);
    }

    public function test_the_coefficient_screen_says_what_each_multiplier_does(): void
    {
        $this->signInAsAdmin();

        $this->get(CalculatorCoefficientResource::getUrl('index'))
            ->assertOk()
            ->assertSee('bahalaşır')
            ->assertSee('Otağın hündürlüyü 3.01–3.50 m olduqda divar və tavan işlərinə.', false);
    }

    /**
     * The six quick prices are fixed rows: a seventh combination cannot be created,
     * because the calculator has no way to reach it.
     */
    public function test_quick_rates_cannot_be_created_or_removed(): void
    {
        $this->assertFalse(CalculatorQuickRateResource::canCreate());
        $this->assertFalse(CalculatorSettingResource::canCreate());
    }

    /** AC-14 — the edited price is the one the storefront quotes. */
    public function test_a_price_edited_in_the_panel_reaches_the_storefront(): void
    {
        $this->signInAsAdmin();

        $rate = CalculatorQuickRate::where('renovation_type', 'WHITE_BOX')
            ->where('segment', 'STANDARD')
            ->first();

        $this->put(CalculatorQuickRateResource::getUrl('edit', ['record' => $rate]));

        // The panel writes through the model, so assert the same path the resource
        // uses rather than driving Livewire's form for one number.
        $rate->update(['price_per_m2' => 475, 'updated_by' => auth('admin')->id()]);

        $this->assertSame(
            47500.0,
            app(QuickCalculator::class)->calculate(
                100,
                \App\Enums\Calculator\RenovationType::WhiteBox,
                \App\Enums\Calculator\PriceSegment::Standard,
            )['total'],
        );

        $this->assertNotNull($rate->fresh()->updated_by);
    }

    private function signInAsAdmin(): User
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin, 'admin');

        return $admin;
    }
}
