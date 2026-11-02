<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\B2bCooperationType;
use App\Enums\B2bPlan;
use App\Filament\Resources\B2bCategoryResource;
use App\Filament\Resources\B2bCategoryResource\Pages\CreateB2bCategory;
use App\Filament\Resources\B2bCategoryResource\Pages\EditB2bCategory;
use App\Filament\Resources\B2bCategoryResource\Pages\ListB2bCategories;
use App\Filament\Resources\B2bCompanyResource;
use App\Filament\Resources\B2bCompanyResource\Pages\CreateB2bCompany;
use App\Filament\Resources\B2bCompanyResource\Pages\EditB2bCompany;
use App\Filament\Resources\B2bCompanyResource\Pages\ListB2bCompanies;
use App\Models\B2bCategory;
use App\Models\B2bCompany;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * CRUD of the two B2B admin resources (companies, categories): pages, create with
 * every field (logo + catalog photos), validation, edit, delete, files on disk,
 * table filters and the "Saytda" link. Admin → public-page consistency lives in
 * B2bAdminToFrontTest.
 */
final class AdminB2bResourcesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create(), 'admin');
    }

    // ─────────────────────────────────────────────────────────── pages

    public function test_all_b2b_resource_pages_render(): void
    {
        $company = $this->makeCompany();
        $category = B2bCategory::query()->create(['name' => ['az' => 'Render kateqoriyası'], 'slug' => 'render-kat']);

        foreach ([B2bCompanyResource::class, B2bCategoryResource::class] as $resource) {
            $this->get($resource::getUrl('index'))->assertOk();
            $this->get($resource::getUrl('create'))->assertOk();
        }

        $this->get(B2bCompanyResource::getUrl('edit', ['record' => $company]))->assertOk();
        $this->get(B2bCategoryResource::getUrl('edit', ['record' => $category]))->assertOk();
    }

    public function test_phone_fields_are_labelled_apart(): void
    {
        $this->get(B2bCompanyResource::getUrl('create'))
            ->assertOk()
            ->assertSee('Şirkət telefonu (ofis)')
            ->assertSee('Əlaqələndiricinin telefonu');
    }

    // ─────────────────────────────────────────────────────────── company: create

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

        $this->get(route('b2b.index'))
            ->assertOk()
            ->assertSee('Anatolia Ceramics');
    }

    public function test_admin_creates_a_company_with_every_field_and_images(): void
    {
        config(['b2b.pricing' => true]); // the market field only shows with pricing on
        Storage::fake('public');
        $category = B2bCategory::query()->create(['name' => ['az' => 'Keramika'], 'slug' => 'keramika-full']);

        Livewire::test(CreateB2bCompany::class)
            ->fillForm(self::fullFormData($category->id))
            ->call('create')
            ->assertHasNoFormErrors();

        $company = B2bCompany::query()->where('legal_name', 'Anatolia Ceramics Ltd.')->sole();

        $this->assertSame('anatolia', $company->slug);
        $this->assertSame('Anatolia', $company->brand_name);
        $this->assertSame('1234567890', $company->registration_no);
        $this->assertSame('Türkiyə', $company->country);
        $this->assertSame('İstanbul, Levent 12', $company->legal_address);
        $this->assertSame('+90 212 000 00 00', $company->phone);
        $this->assertSame('https://anatolia.example', $company->website);
        $this->assertSame('Keramika ixracatçısı', $company->getTranslation('description', 'az'));
        $this->assertSame('Ceramics exporter', $company->getTranslation('description', 'en'));
        $this->assertSame($category->id, $company->b2b_category_id);
        $this->assertSame(B2bCooperationType::Distributor, $company->cooperation_type);
        $this->assertSame('Anatolia, Vitra', $company->brands);
        $this->assertSame('1 konteyner', $company->moq);
        $this->assertSame('Kafel və keramoqranit', $company->getTranslation('catalog_description', 'az'));
        $this->assertSame('Aylin Kaya', $company->contact_person);
        $this->assertSame('İxrac meneceri', $company->contact_position);
        $this->assertSame('+90 532 111 22 33', $company->contact_phone);
        $this->assertSame('+90 500 000 00 00', $company->whatsapp);
        $this->assertSame('@anatolia_export', $company->telegram);
        $this->assertSame('export@anatolia.example', $company->email);
        $this->assertSame('B.e–Cümə 09:00–18:00', $company->working_hours);
        $this->assertSame('@anatolia.tiles', $company->instagram);
        $this->assertSame('https://www.linkedin.com/company/anatolia', $company->linkedin);
        $this->assertSame('facebook.com/anatolia', $company->facebook);
        $this->assertEqualsCanonicalizing(['tr', 'en', 'ru'], $company->languages);
        $this->assertSame('international', $company->market);
        $this->assertSame(B2bPlan::Pro, $company->plan);
        $this->assertSame(5, (int) $company->sort_order);
        $this->assertTrue($company->is_featured);
        $this->assertTrue($company->is_verified);
        $this->assertTrue($company->is_published);
        $this->assertNotNull($company->published_at);

        // Files land on the public disk under the configured directories.
        $this->assertStringStartsWith('b2b/logos/', (string) $company->logo);
        Storage::disk('public')->assertExists($company->logo);
        $this->assertCount(2, $company->catalog_images);
        foreach ($company->catalog_images as $path) {
            $this->assertStringStartsWith('b2b/catalog/', $path);
            Storage::disk('public')->assertExists($path);
        }
        $this->assertSame(storage_url($company->logo), $company->logo_url);
        $this->assertCount(2, $company->imageUrls());
    }

    public function test_company_requires_legal_name(): void
    {
        Livewire::test(CreateB2bCompany::class)
            ->fillForm(['legal_name' => '', 'market' => 'local', 'plan' => B2bPlan::Free->value])
            ->call('create')
            ->assertHasFormErrors(['legal_name' => 'required']);
    }

    public function test_market_field_is_hidden_while_pricing_is_off(): void
    {
        $this->get(B2bCompanyResource::getUrl('create'))->assertOk()->assertDontSee('Beynəlxalq (USD)');

        Livewire::test(CreateB2bCompany::class)
            ->fillForm(['legal_name' => 'Bazarsız MMC', 'plan' => B2bPlan::Free->value])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('b2b_companies', ['legal_name' => 'Bazarsız MMC']);

        config(['b2b.pricing' => true]);
        $this->get(B2bCompanyResource::getUrl('create'))->assertOk()->assertSee('Beynəlxalq (USD)');
    }

    public function test_company_requires_market_and_plan(): void
    {
        config(['b2b.pricing' => true]);

        Livewire::test(CreateB2bCompany::class)
            ->fillForm(['legal_name' => 'Test MMC', 'market' => null, 'plan' => null])
            ->call('create')
            ->assertHasFormErrors(['market' => 'required', 'plan' => 'required']);

        $this->assertDatabaseCount('b2b_companies', 0);
    }

    public function test_company_validates_email_url_and_unique_slug(): void
    {
        $this->makeCompany(['slug' => 'tutulub']);

        Livewire::test(CreateB2bCompany::class)
            ->fillForm([
                'legal_name' => 'Test MMC', 'market' => 'local', 'plan' => B2bPlan::Free->value,
                'email' => 'not-an-email',
                'website' => 'javascript:alert(1)',
                'slug' => 'tutulub',
            ])
            ->call('create')
            ->assertHasFormErrors(['email', 'website', 'slug']);

        // ftp:// passes Laravel's plain `url` rule — the profile only links http(s).
        Livewire::test(CreateB2bCompany::class)
            ->fillForm(['legal_name' => 'Test MMC', 'market' => 'local', 'plan' => B2bPlan::Free->value, 'website' => 'ftp://files.example.com'])
            ->call('create')
            ->assertHasFormErrors(['website']);
    }

    public function test_social_fields_accept_handles_and_links_but_not_other_schemes(): void
    {
        $base = ['legal_name' => 'Sosial MMC', 'market' => 'local', 'plan' => B2bPlan::Free->value];

        Livewire::test(CreateB2bCompany::class)
            ->fillForm($base + [
                'telegram' => 'javascript:alert(1)',
                'instagram' => 'javascript://instagram.com/%0Aalert(1)',
                'linkedin' => 'data:text/html,hi',
                'facebook' => '<script>',
            ])
            ->call('create')
            ->assertHasFormErrors(['telegram', 'instagram', 'linkedin', 'facebook']);

        Livewire::test(CreateB2bCompany::class)
            ->fillForm($base + [
                'telegram' => '+994 50 111 22 33',
                'instagram' => '@sosial.mmc',
                'linkedin' => 'linkedin.com/company/sosial',
                'facebook' => 'https://facebook.com/sosial',
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    // ─────────────────────────────────────────────────────────── company: edit / delete

    public function test_admin_edits_and_deletes_a_company(): void
    {
        $company = $this->makeCompany();

        Livewire::test(EditB2bCompany::class, ['record' => $company->getRouteKey()])
            ->assertSchemaStateSet(['legal_name' => 'Mövcud Şirkət MMC', 'brand_name' => 'Mövcud'])
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

    public function test_clearing_the_slug_regenerates_it_without_a_suffix(): void
    {
        $company = $this->makeCompany(['slug' => 'kohne-slug']);

        Livewire::test(EditB2bCompany::class, ['record' => $company->getRouteKey()])
            ->fillForm(['slug' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        // "movcud", not "movcud-2" (the record's own old slug used to count as taken).
        $this->assertSame('movcud', $company->fresh()->slug);
    }

    public function test_replacing_the_logo_and_dropping_a_catalog_photo_deletes_the_old_files(): void
    {
        Storage::fake('public');
        $disk = Storage::disk('public');

        $company = $this->makeCompany([
            'logo' => UploadedFile::fake()->image('old-logo.png')->store('b2b/logos', 'public'),
            'catalog_images' => [
                UploadedFile::fake()->image('a.jpg')->store('b2b/catalog', 'public'),
                UploadedFile::fake()->image('b.jpg')->store('b2b/catalog', 'public'),
            ],
        ]);
        [$keep, $drop] = $company->catalog_images;
        $oldLogo = $company->logo;

        // As in the panel: remove the old logo, upload a new one, drop one photo.
        Livewire::test(EditB2bCompany::class, ['record' => $company->getRouteKey()])
            ->fillForm(['logo' => null, 'catalog_images' => [$keep]])
            ->fillForm(['logo' => [UploadedFile::fake()->image('new-logo.png')]])
            ->call('save')
            ->assertHasNoFormErrors();

        $company->refresh();
        $this->assertSame([$keep], $company->catalog_images);
        $this->assertNotSame($oldLogo, $company->logo);

        $disk->assertExists($keep);
        $disk->assertMissing($drop);
        $disk->assertMissing($oldLogo);
        $disk->assertExists($company->logo);
    }

    public function test_removing_the_logo_deletes_the_file(): void
    {
        Storage::fake('public');
        $company = $this->makeCompany(['logo' => UploadedFile::fake()->image('logo.png')->store('b2b/logos', 'public')]);
        $logo = $company->logo;

        $company->update(['logo' => null]);

        Storage::disk('public')->assertMissing($logo);
        $this->assertNull($company->fresh()->logo_url);
    }

    public function test_deleting_a_company_removes_its_logo_and_catalog_photos(): void
    {
        Storage::fake('public');
        $disk = Storage::disk('public');

        $company = $this->makeCompany([
            'logo' => UploadedFile::fake()->image('logo.png')->store('b2b/logos', 'public'),
            'catalog_images' => [UploadedFile::fake()->image('a.jpg')->store('b2b/catalog', 'public')],
        ]);
        $files = [$company->logo, ...$company->catalog_images];
        $other = $this->makeCompany([
            'legal_name' => 'Başqa MMC', 'brand_name' => null,
            'logo' => UploadedFile::fake()->image('other.png')->store('b2b/logos', 'public'),
        ]);

        Livewire::test(EditB2bCompany::class, ['record' => $company->getRouteKey()])
            ->callAction('delete');

        $this->assertModelMissing($company);
        foreach ($files as $path) {
            $disk->assertMissing($path);
        }
        $disk->assertExists($other->logo);
    }

    public function test_bulk_delete_removes_companies(): void
    {
        $a = $this->makeCompany();
        $b = $this->makeCompany(['legal_name' => 'İkinci MMC', 'brand_name' => null]);

        Livewire::test(ListB2bCompanies::class)
            ->callTableBulkAction('delete', [$a, $b]);

        $this->assertModelMissing($a);
        $this->assertModelMissing($b);
    }

    // ─────────────────────────────────────────────────────────── company: table

    public function test_company_table_filters_and_search(): void
    {
        config(['b2b.pricing' => true]); // the market filter only shows with pricing on
        $tiles = B2bCategory::query()->create(['name' => ['az' => 'Kafel'], 'slug' => 'kafel-filter']);
        $furniture = B2bCategory::query()->create(['name' => ['az' => 'Mebel'], 'slug' => 'mebel-filter']);

        $local = $this->makeCompany(['brand_name' => 'Yerli', 'market' => 'local', 'cooperation_type' => B2bCooperationType::Dealer, 'b2b_category_id' => $tiles->id]);
        $intl = $this->makeCompany(['legal_name' => 'Milano Furniture SpA', 'brand_name' => null, 'market' => 'international', 'cooperation_type' => B2bCooperationType::OfficialRep, 'b2b_category_id' => $furniture->id]);
        $draft = $this->makeCompany(['brand_name' => 'Qaralama', 'is_published' => false, 'market' => 'local']);

        Livewire::test(ListB2bCompanies::class)
            ->assertCanSeeTableRecords([$local, $intl, $draft])
            ->filterTable('is_published', false)
            ->assertCanSeeTableRecords([$draft])
            ->assertCanNotSeeTableRecords([$local, $intl]);

        Livewire::test(ListB2bCompanies::class)
            ->filterTable('market', 'international')
            ->assertCanSeeTableRecords([$intl])
            ->assertCanNotSeeTableRecords([$local, $draft]);

        Livewire::test(ListB2bCompanies::class)
            ->filterTable('cooperation_type', B2bCooperationType::Dealer->value)
            ->assertCanSeeTableRecords([$local])
            ->assertCanNotSeeTableRecords([$intl, $draft]);

        Livewire::test(ListB2bCompanies::class)
            ->filterTable('b2b_category_id', $furniture->id)
            ->assertCanSeeTableRecords([$intl])
            ->assertCanNotSeeTableRecords([$local, $draft]);

        Livewire::test(ListB2bCompanies::class)
            ->searchTable('Milano')
            ->assertCanSeeTableRecords([$intl])
            ->assertCanNotSeeTableRecords([$local, $draft]);
    }

    public function test_view_on_site_action_links_to_the_live_profile_only(): void
    {
        $live = $this->makeCompany(['published_at' => now()->subHour()]);
        $draft = $this->makeCompany(['legal_name' => 'Qaralama MMC', 'brand_name' => null, 'is_published' => false]);
        $future = $this->makeCompany(['legal_name' => 'Gələcək MMC', 'brand_name' => null, 'published_at' => now()->addDay()]);

        Livewire::test(ListB2bCompanies::class)
            ->assertTableActionVisible('view', $live)
            ->assertTableActionHasUrl('view', route('b2b.show', $live->slug), $live)
            ->assertTableActionShouldOpenUrlInNewTab('view', $live)
            ->assertTableActionHidden('view', $draft)
            // Was visible: is_published alone, yet the profile 404s until published_at.
            ->assertTableActionHidden('view', $future);

        $this->get(route('b2b.show', $live->slug))->assertOk();
    }

    // ─────────────────────────────────────────────────────────── categories

    public function test_admin_creates_a_category(): void
    {
        // A slug the seed migration does not already use.
        Livewire::test(CreateB2bCategory::class)
            ->fillForm(['name' => ['az' => 'Xüsusi avadanlıq', 'en' => 'Special equipment'], 'sort_order' => 99, 'is_active' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = B2bCategory::query()->where('sort_order', 99)->sole();
        $this->assertSame('xususi-avadanliq', $category->slug);
        $this->assertSame('Special equipment', $category->getTranslation('name', 'en'));
        $this->assertTrue($category->is_active);
    }

    public function test_category_requires_an_azerbaijani_name_and_a_unique_slug(): void
    {
        B2bCategory::query()->create(['name' => ['az' => 'Var olan'], 'slug' => 'var-olan']);

        Livewire::test(CreateB2bCategory::class)
            ->fillForm(['name' => ['az' => '', 'en' => 'Only English'], 'slug' => 'var-olan'])
            ->call('create')
            ->assertHasFormErrors(['name.az' => 'required', 'slug' => 'unique']);
    }

    public function test_a_second_category_with_the_same_name_gets_a_unique_slug(): void
    {
        foreach ([1, 2] as $i) {
            Livewire::test(CreateB2bCategory::class)
                ->fillForm(['name' => ['az' => 'Təkrar ad'], 'sort_order' => 90 + $i])
                ->call('create')
                ->assertHasNoFormErrors();
        }

        // Used to be a duplicate-key 500: the slug was generated without a uniqueness check.
        $this->assertSame(['tekrar-ad', 'tekrar-ad-2'], B2bCategory::query()->whereIn('sort_order', [91, 92])->orderBy('sort_order')->pluck('slug')->all());
    }

    public function test_admin_edits_a_category(): void
    {
        $category = B2bCategory::query()->create(['name' => ['az' => 'Köhnə ad'], 'slug' => 'kohne-ad', 'is_active' => true]);

        Livewire::test(EditB2bCategory::class, ['record' => $category->getRouteKey()])
            ->fillForm(['name' => ['az' => 'Yeni ad'], 'is_active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $category->refresh();
        $this->assertSame('Yeni ad', $category->getTranslation('name', 'az'));
        $this->assertSame('kohne-ad', $category->slug, 'Renaming keeps the slug (filter links stay valid).');
        $this->assertFalse($category->is_active);

        // An inactive category drops out of the public filter.
        $slugs = $this->get(route('b2b.index'))->assertOk()->viewData('categories')->pluck('slug')->all();
        $this->assertNotContains('kohne-ad', $slugs);
    }

    public function test_category_table_counts_companies(): void
    {
        $category = B2bCategory::query()->create(['name' => ['az' => 'Sayılan'], 'slug' => 'sayilan']);
        $this->makeCompany(['b2b_category_id' => $category->id]);
        $this->makeCompany(['legal_name' => 'İkinci MMC', 'brand_name' => null, 'b2b_category_id' => $category->id]);

        Livewire::test(ListB2bCategories::class)
            ->assertCanSeeTableRecords([$category])
            ->assertTableColumnStateSet('companies_count', 2, $category);
    }

    public function test_deleting_a_category_keeps_its_companies_and_the_front_still_renders(): void
    {
        $category = B2bCategory::query()->create(['name' => ['az' => 'Silinəcək'], 'slug' => 'silinecek']);
        $company = $this->makeCompany(['b2b_category_id' => $category->id, 'published_at' => now()->subHour()]);

        Livewire::test(ListB2bCategories::class)
            ->callTableAction('delete', $category);

        $this->assertModelMissing($category);
        $company->refresh();
        $this->assertModelExists($company);
        $this->assertNull($company->b2b_category_id);

        $this->get(route('b2b.index'))->assertOk()->assertSee('Mövcud')->assertDontSee('Silinəcək');
        $this->get(route('b2b.show', $company->slug))->assertOk()->assertSee('Mövcud')->assertDontSee('Silinəcək');
        $this->get(route('b2b.index', ['category' => ['silinecek']]))->assertOk()->assertDontSee('b2-card"', false);
    }

    // ─────────────────────────────────────────────────────────── helpers

    /**
     * The create form with every field filled (files included).
     *
     * @return array<string, mixed>
     */
    public static function fullFormData(int $categoryId): array
    {
        return [
            'legal_name' => 'Anatolia Ceramics Ltd.',
            'brand_name' => 'Anatolia',
            'registration_no' => '1234567890',
            'country' => 'Türkiyə',
            'legal_address' => 'İstanbul, Levent 12',
            'phone' => '+90 212 000 00 00',
            'website' => 'https://anatolia.example',
            'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
            'description' => ['az' => 'Keramika ixracatçısı', 'en' => 'Ceramics exporter'],
            'b2b_category_id' => $categoryId,
            'cooperation_type' => B2bCooperationType::Distributor->value,
            'brands' => 'Anatolia, Vitra',
            'moq' => '1 konteyner',
            'catalog_description' => ['az' => 'Kafel və keramoqranit'],
            'catalog_images' => [
                UploadedFile::fake()->image('catalog-1.jpg', 800, 600),
                UploadedFile::fake()->image('catalog-2.jpg', 800, 600),
            ],
            'contact_person' => 'Aylin Kaya',
            'contact_position' => 'İxrac meneceri',
            'contact_phone' => '+90 532 111 22 33',
            'whatsapp' => '+90 500 000 00 00',
            'telegram' => '@anatolia_export',
            'email' => 'export@anatolia.example',
            'working_hours' => 'B.e–Cümə 09:00–18:00',
            'instagram' => '@anatolia.tiles',
            'linkedin' => 'https://www.linkedin.com/company/anatolia',
            'facebook' => 'facebook.com/anatolia',
            'languages' => ['tr', 'en', 'ru'],
            'market' => 'international',
            'plan' => B2bPlan::Pro->value,
            'slug' => '',
            'sort_order' => 5,
            'is_featured' => true,
            'is_verified' => true,
            'is_published' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeCompany(array $overrides = []): B2bCompany
    {
        return B2bCompany::query()->create(array_merge([
            'legal_name' => 'Mövcud Şirkət MMC',
            'brand_name' => 'Mövcud',
            'country' => 'Azərbaycan',
            'cooperation_type' => B2bCooperationType::Distributor,
            'market' => 'local',
            'plan' => B2bPlan::Basic,
            'is_published' => true,
        ], $overrides));
    }
}
