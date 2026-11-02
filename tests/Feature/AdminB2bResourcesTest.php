<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\B2bCooperationType;
use App\Enums\B2bPlan;
use App\Filament\Resources\B2bCategoryResource;
use App\Filament\Resources\B2bCategoryResource\Pages\CreateB2bCategory;
use App\Filament\Resources\B2bCompanyResource;
use App\Filament\Resources\B2bCompanyResource\Pages\CreateB2bCompany;
use App\Filament\Resources\B2bCompanyResource\Pages\EditB2bCompany;
use App\Filament\Resources\B2bCompanyResource\Pages\ListB2bCompanies;
use App\Models\B2bCategory;
use App\Models\B2bCompany;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class AdminB2bResourcesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create(), 'admin');
    }

    public function test_all_b2b_resource_pages_render(): void
    {
        $company = $this->makeCompany();

        foreach ([B2bCompanyResource::class, B2bCategoryResource::class] as $resource) {
            $this->get($resource::getUrl('index'))->assertOk();
        }

        $this->get(B2bCompanyResource::getUrl('create'))->assertOk();
        $this->get(B2bCompanyResource::getUrl('edit', ['record' => $company]))->assertOk();
    }

    public function test_admin_creates_a_company_that_appears_on_the_directory(): void
    {
        $category = B2bCategory::query()->create(['name' => ['az' => 'Keramika'], 'slug' => 'keramika-admin']);

        Livewire::test(CreateB2bCompany::class)
            ->fillForm([
                'legal_name' => 'Anatolia Ceramics Ltd.',
                'brand_name' => 'Anatolia Ceramics',
                'b2b_category_id' => $category->id,
                'country' => 'Türkiyə',
                'cooperation_type' => B2bCooperationType::Distributor->value,
                'description' => ['az' => 'Keramika ixracatçısı'],
                'email' => 'export@anatolia.example',
                'market' => 'international',
                'plan' => B2bPlan::Premium->value,
                'is_featured' => true,
                'is_published' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $company = B2bCompany::query()->where('legal_name', 'Anatolia Ceramics Ltd.')->sole();
        $this->assertSame('anatolia-ceramics', $company->slug);
        $this->assertNotNull($company->published_at);
        $this->assertTrue($company->is_featured);
        $this->assertSame(B2bPlan::Premium, $company->plan);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('b2b.index'))
            ->assertOk()
            ->assertSee('Anatolia Ceramics');
    }

    public function test_company_requires_legal_name(): void
    {
        Livewire::test(CreateB2bCompany::class)
            ->fillForm(['legal_name' => '', 'market' => 'local', 'plan' => B2bPlan::Free->value])
            ->call('create')
            ->assertHasFormErrors(['legal_name' => 'required']);
    }

    public function test_admin_edits_and_deletes_a_company(): void
    {
        $company = $this->makeCompany();

        Livewire::test(EditB2bCompany::class, ['record' => $company->getRouteKey()])
            ->fillForm(['brand_name' => 'Yeni Brend', 'is_published' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Yeni Brend', $company->fresh()->brand_name);
        $this->assertFalse($company->fresh()->is_published);

        Livewire::test(ListB2bCompanies::class)
            ->assertCanSeeTableRecords([$company])
            ->callTableAction('delete', $company);

        $this->assertModelMissing($company);
    }

    public function test_admin_creates_a_category(): void
    {
        // A slug the seed migration does not already use.
        Livewire::test(CreateB2bCategory::class)
            ->fillForm(['name' => ['az' => 'Xüsusi avadanlıq'], 'sort_order' => 99, 'is_active' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('xususi-avadanliq', B2bCategory::query()->where('sort_order', 99)->sole()->slug);
    }

    private function makeCompany(): B2bCompany
    {
        return B2bCompany::query()->create([
            'legal_name' => 'Mövcud Şirkət MMC',
            'brand_name' => 'Mövcud',
            'country' => 'Azərbaycan',
            'cooperation_type' => B2bCooperationType::Distributor,
            'market' => 'local',
            'plan' => B2bPlan::Basic,
            'is_published' => true,
        ]);
    }
}
