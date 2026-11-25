<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\User;
use App\Services\ProductCard\CardImporter;
use App\Services\ProductCard\CardSchema;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * TZ Döşəmələr (24.11.2026): cards D13–D22, the products follow their class's group,
 * admin-created products are approved, a class switched off in the admin stays off.
 */
class DosemeTzTest extends TestCase
{
    use RefreshDatabase;

    public function test_moving_a_class_to_another_group_moves_its_products(): void
    {
        $section = Category::create(['name' => ['az' => 'Döşəmələr'], 'slug' => 'dosemeler-x', 'is_active' => true]);
        $old = Category::create(['parent_id' => $section->id, 'name' => ['az' => 'Köhnə'], 'slug' => 'kohne-x', 'is_active' => true]);
        $new = Category::create(['parent_id' => $section->id, 'name' => ['az' => 'Yeni'], 'slug' => 'yeni-x', 'is_active' => true]);
        $class = SubCategory::create(['category_id' => $old->id, 'slug' => 'plintus-x', 'name' => ['az' => 'Plintus'], 'is_active' => true]);
        $product = $this->product($class);

        $class->update(['category_id' => $new->id]);

        $this->assertSame($new->id, $product->fresh()->category_id);
    }

    public function test_a_product_the_admin_creates_is_approved_by_default(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create(), 'admin');

        Livewire::test(CreateProduct::class)->assertFormSet(['is_approved' => true]);
    }

    public function test_the_doseme_import_keeps_admin_names_and_the_hidden_class(): void
    {
        $this->importDoseme();

        $this->assertSame('Plintus, profillər və altlıqlar', Category::where('slug', 'altliqlar-ve-birlesdirici-elementler')->first()->getTranslation('name', 'az'));
        $this->assertFalse((bool) SubCategory::where('slug', 'seki-pliteleri-ve-tamet')->first()->is_active);
        $this->assertTrue((bool) SubCategory::where('slug', 'seki-pliteleri-ve-tamet')->first()->card_enabled);

        // Re-import: still off.
        app(CardImporter::class)->importFile('doseme');
        $this->assertFalse((bool) SubCategory::where('slug', 'seki-pliteleri-ve-tamet')->first()->is_active);
    }

    public function test_the_ten_new_classes_have_cards_with_their_units(): void
    {
        $this->importDoseme();

        $expected = [
            'mozaika' => ['m2', 'sheet'],
            'mantar-dosemeler' => ['m2'],
            'ozuyayilan-dosemeler-dekorativ' => ['kg', 'pack'],
            'idman-ortuyu' => ['m2', 'roll'],
            'qaldirilmis-doseme' => ['m2', 'piece'],
            'plintus-ve-doseme-profilleri' => ['piece', 'lm'],
            'csp-gvl-osp-dsp-faner-keramzit-laqalar-dosemenin-qara-bazasi' => ['sheet', 'm2'],
            'doseme-ortukleri-ucun-altliq' => ['m2', 'roll'],
            'kecid-ve-birlesdirici-profiller' => ['piece', 'lm'],
            'dempfer-lenti-sap-ucun' => ['roll', 'lm'],
        ];

        foreach ($expected as $slug => $units) {
            $class = SubCategory::where('slug', $slug)->firstOrFail();
            $this->assertTrue((bool) $class->card_enabled, $slug);
            $this->assertTrue((bool) $class->is_active, $slug);
            $schema = CardSchema::for($class->id);
            $this->assertSame(CardSchema::SALE_UNIT, $schema['sale']['type'], $slug);
            foreach ($units as $unit) {
                $this->assertContains($unit, $schema['sale']['units'], "{$slug}: {$unit}");
            }
            $this->assertNotEmpty($schema['fields'], $slug);
            $this->assertTrue($class->attributes()->wherePivot('is_filterable', true)->exists(), "{$slug}: filter");
        }

        $this->assertSame(
            ['doseme-ortukleri', 'texniki-doseme-ve-alt-sistemler', 'altliqlar-ve-birlesdirici-elementler'],
            Category::where('parent_id', Category::where('slug', 'dosemeler')->value('id'))->where('is_active', true)->orderBy('sort_order')->pluck('slug')->all(),
        );
    }

    private function importDoseme(): void
    {
        $section = Category::create(['name' => ['az' => 'Döşəmələr'], 'slug' => 'dosemeler', 'is_active' => true]);
        // The admin switched this class off before the import.
        $group = Category::create(['parent_id' => $section->id, 'name' => ['az' => 'Döşəmə örtükləri'], 'slug' => 'doseme-ortukleri', 'is_active' => true]);
        $tamet = SubCategory::create(['category_id' => $group->id, 'slug' => 'seki-pliteleri-ve-tamet', 'name' => ['az' => 'Səki plitələri və tamet'], 'is_active' => false]);
        $this->product($tamet);

        app(CardImporter::class)->importFile('doseme');
    }

    private function product(SubCategory $class): Product
    {
        return Product::create([
            'user_id' => User::factory()->seller()->create()->id,
            'category_id' => $class->category_id,
            'sub_category_id' => $class->id,
            'name' => ['az' => 'TEST'],
            'description' => ['az' => ''],
            'slug' => 'test-'.uniqid(),
            'price' => 10,
            'stock' => 1,
            'is_visible' => true,
            'is_approved' => true,
        ]);
    }
}
