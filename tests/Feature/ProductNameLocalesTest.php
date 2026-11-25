<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * TZ «название товара RU/EN» (24.11.2026): the name is required in az only, an
 * empty ru/en falls back to az on the site, and the admin form marks the empty
 * language tabs. Plus the fallback redirect for closed classes and groups.
 */
class ProductNameLocalesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_an_empty_ru_or_en_name_falls_back_to_az(): void
    {
        [, , $class] = $this->tree();
        $product = $this->product($class, ['az' => 'Gips tavan', 'ru' => '', 'en' => null]);

        foreach (['ru', 'en', 'az'] as $locale) {
            app()->setLocale($locale);
            $this->assertSame('Gips tavan', $product->fresh()->name, $locale);
        }
    }

    public function test_the_form_marks_empty_language_tabs_and_requires_only_az(): void
    {
        $this->actingAs(User::factory()->admin()->create(), 'admin');
        [, , $class] = $this->tree();
        $product = $this->product($class, ['az' => 'Gips tavan']);

        $page = Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()]);
        $this->assertSame(2, preg_match_all('/>\s*boş\s*</u', $page->html()), 'two «boş» badges (ru, en)');
        $this->assertStringContainsString('Boş qalsa, saytda azərbaycanca ad göstərilir.', $page->html());

        $page->fillForm(['name' => ['az' => 'Gips tavan', 'ru' => 'Гипсовый потолок', 'en' => '']]);
        $this->assertSame(1, preg_match_all('/>\s*boş\s*</u', $page->html()), 'only en is still empty');

        $page->call('save')->assertHasNoFormErrors(['name.ru', 'name.en']);
        $this->assertSame('Гипсовый потолок', $product->fresh()->getTranslation('name', 'ru', false));

        $page->fillForm(['name' => ['az' => '', 'ru' => 'Гипсовый потолок', 'en' => '']])
            ->call('save')
            ->assertHasFormErrors(['name.az' => 'required']);
    }

    public function test_a_closed_class_or_group_without_a_target_goes_to_its_live_parent(): void
    {
        [$section, $group, $class] = $this->tree();
        $closedClass = SubCategory::create(['category_id' => $group->id, 'slug' => 'kohne-sinif', 'name' => ['az' => 'Köhnə sinif'], 'is_active' => false]);
        $closedGroup = Category::create(['parent_id' => $section->id, 'slug' => 'kohne-qrup', 'name' => ['az' => 'Köhnə qrup'], 'is_active' => false]);
        $orphan = SubCategory::create(['category_id' => $closedGroup->id, 'slug' => 'yetim-sinif', 'name' => ['az' => 'Yetim'], 'is_active' => false]);

        $this->get('/catalog?class=kohne-sinif&sort=new')->assertStatus(301)
            ->assertRedirect(route('catalog', ['sort' => 'new', 'group' => $group->slug]));
        $this->get('/catalog?group=kohne-qrup')->assertStatus(301)
            ->assertRedirect(route('catalog', ['section' => $section->slug]));
        $this->get('/catalog?class=yetim-sinif')->assertStatus(301)
            ->assertRedirect(route('catalog', ['section' => $section->slug]));

        // A live class and an unknown slug stay where they are.
        $this->get('/catalog?class='.$class->slug)->assertOk();
        $this->get('/catalog?class=bele-sinif-yoxdur')->assertOk();
    }

    /** @return array{0: Category, 1: Category, 2: SubCategory} */
    private function tree(): array
    {
        $section = Category::create(['name' => ['az' => 'Tavanlar'], 'slug' => 'tavanlar-'.uniqid(), 'is_active' => true]);
        $group = Category::create(['parent_id' => $section->id, 'name' => ['az' => 'Tavan növləri'], 'slug' => 'tavan-novleri-'.uniqid(), 'is_active' => true]);
        $class = SubCategory::create(['category_id' => $group->id, 'slug' => 'gips-'.uniqid(), 'name' => ['az' => 'Gips'], 'is_active' => true]);

        return [$section, $group, $class];
    }

    private function product(SubCategory $class, array $name): Product
    {
        return Product::create([
            'user_id' => User::factory()->seller()->create()->id,
            'category_id' => $class->category_id,
            'sub_category_id' => $class->id,
            'name' => $name,
            'description' => ['az' => ''],
            'slug' => 'gips-tavan-'.uniqid(),
            'price' => 25,
            'stock' => 10,
            'is_visible' => true,
            'is_approved' => true,
        ]);
    }
}
