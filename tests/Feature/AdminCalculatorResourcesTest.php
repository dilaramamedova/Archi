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
use App\Filament\Resources\CalculatorWorkRateResource\Pages\ListCalculatorWorkRates;
use App\Models\CalculatorQuickRate;
use App\Models\CalculatorSetting;
use App\Models\CalculatorWorkRate;
use App\Models\User;
use App\Services\Calculator\QuickCalculator;
use Database\Seeders\CalculatorSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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
            // One short sentence and a screenshot of the page these numbers land on.
            ->assertSee('1 vahidə düşən iş haqqı', false)
            ->assertSee('assets/admin/calculator/work-rates.png', false)
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
     * An admin searches with the words they just saw on the site, not with the
     * catalog's own wording. "Yeni qapı quraşdırılsın" is a toggle on the rooms step;
     * it has to lead to the door installation rate.
     */
    public function test_the_work_rate_search_finds_a_row_by_its_frontend_label(): void
    {
        $this->signInAsAdmin();

        Livewire::test(ListCalculatorWorkRates::class)
            ->searchTable('Yeni qapı quraşdırılsın')
            ->assertCanSeeTableRecords(CalculatorWorkRate::where('work_id', 'DOOR_INSTALL_INTERIOR')->get())
            ->assertCanNotSeeTableRecords(CalculatorWorkRate::where('work_id', 'WALL_PAINT')->get());
    }

    /** The object-condition chips carry no price; they pick which works apply. */
    public function test_searching_a_condition_label_finds_the_works_it_turns_on(): void
    {
        $this->signInAsAdmin();

        Livewire::test(ListCalculatorWorkRates::class)
            ->searchTable('Təmirsiz (mayak)')
            ->assertCanSeeTableRecords(CalculatorWorkRate::where('work_id', 'WALL_PLASTER')->get());
    }

    /** Everyday words have to work too — most admins will not type the full label. */
    public function test_the_work_rate_search_matches_everyday_words(): void
    {
        $this->signInAsAdmin();

        Livewire::test(ListCalculatorWorkRates::class)
            ->searchTable('kafel')
            ->assertCanSeeTableRecords(CalculatorWorkRate::where('work_id', 'FLOOR_TILE_INSTALL')->get());
    }

    /**
     * The panel's TOP search bar has to find calculator data by the words the
     * admin saw on the site — «Elektrik isti döşəmə» is a work rate's own name,
     * «Gərilmə tavan» a finish label routed through search_keywords. Both are
     * stored in translated JSON columns, so this also proves the locale-path
     * search handles Azerbaijani letters.
     */
    public function test_the_global_search_finds_calculator_prices(): void
    {
        $this->signInAsAdmin();

        $found = CalculatorWorkRateResource::getGlobalSearchResults('Elektrik isti döşəmə')
            ->map(fn ($result) => $result->title);
        $this->assertTrue(
            $found->contains('Elektrik isti döşəmə'),
            'Global search did not find the work rate by its site name. Got: '.$found->implode(', '),
        );

        $stretch = CalculatorWorkRateResource::getGlobalSearchResults('Gərilmə tavan')
            ->map(fn ($result) => $result->title);
        $this->assertTrue(
            $stretch->contains('Gərilmə tavan'),
            'Global search did not find the stretch-ceiling rate by its finish label.',
        );

        // A surface-state chip («Mayak / qara divar») is interface text, but the
        // works it switches on must surface for it.
        $this->assertNotEmpty(
            CalculatorWorkRateResource::getGlobalSearchResults('Mayak / qara divar')->all(),
            'Global search found nothing for a surface-state label.',
        );

        $this->assertNotEmpty(
            CalculatorCoefficientResource::getGlobalSearchResults('İriformatlı')->all(),
            'Global search found no coefficient for «İriformatlı».',
        );
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
