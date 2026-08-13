<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AttributeComplexity;
use App\Enums\AttributeFieldType;
use App\Enums\FilterPriority;
use App\Filament\Resources\ApplicationResource;
use App\Filament\Resources\AttributeResource;
use App\Filament\Resources\AttributeResource\Pages\CreateAttribute;
use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\SearchSynonymResource;
use App\Filament\Resources\SearchSynonymResource\Pages\CreateSearchSynonym;
use App\Filament\Resources\SearchSynonymResource\Pages\EditSearchSynonym;
use App\Filament\Resources\SubCategoryResource;
use App\Models\Application;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Category;
use App\Models\Product;
use App\Models\SearchSynonym;
use App\Models\SubCategory;
use App\Models\Translation;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCatalogResourcesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_catalog_resource_pages_render_for_admin(): void
    {
        $this->signInAsAdmin();

        [$subCategory, $attributes] = $this->makeClassifierFixture();

        SearchSynonym::create([
            'sub_category_id' => $subCategory->id,
            'phrase' => 'mənzil üçün laminat',
            'locale' => 'az',
        ]);

        foreach ([
            CategoryResource::class,
            SubCategoryResource::class,
            AttributeResource::class,
            ApplicationResource::class,
            SearchSynonymResource::class,
            ProductResource::class,
        ] as $resource) {
            $this->get($resource::getUrl('index'))->assertOk();
        }

        // Edit pages host the relation managers (attributes pivot / options).
        $this->get(SubCategoryResource::getUrl('edit', ['record' => $subCategory]))->assertOk();
        $this->get(AttributeResource::getUrl('edit', ['record' => $attributes['dropdown']]))->assertOk();

        // Relation manager tables render lazily — mount them directly so the
        // pivot columns and default sort are actually exercised.
        Livewire::test(\App\Filament\Resources\SubCategoryResource\RelationManagers\AttributesRelationManager::class, [
            'ownerRecord' => $subCategory,
            'pageClass' => \App\Filament\Resources\SubCategoryResource\Pages\EditSubCategory::class,
        ])->assertOk()->assertCanSeeTableRecords($subCategory->attributes);

        Livewire::test(\App\Filament\Resources\AttributeResource\RelationManagers\OptionsRelationManager::class, [
            'ownerRecord' => $attributes['dropdown'],
            'pageClass' => \App\Filament\Resources\AttributeResource\Pages\EditAttribute::class,
        ])->assertOk()->assertCanSeeTableRecords($attributes['dropdown']->options);
    }

    public function test_product_edit_saves_and_reloads_classifier_attribute_values(): void
    {
        $this->signInAsAdmin();

        [$subCategory, $attributes] = $this->makeClassifierFixture();
        $product = $this->makeProduct($subCategory);

        $dropdownOption = $attributes['dropdown']->options->first();
        $multiOptions = $attributes['multiselect']->options->pluck('id')->all();

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm([
                'attribute_values' => [
                    (string) $attributes['dropdown']->id => $dropdownOption->id,
                    (string) $attributes['multiselect']->id => $multiOptions,
                    (string) $attributes['boolean']->id => true,
                    (string) $attributes['numeric']->id => '8',
                    (string) $attributes['range']->id => ['min' => '2', 'max' => '5.5'],
                    (string) $attributes['text']->id => 'AC4',
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(
            $dropdownOption->id,
            $product->attributeValues()->where('attribute_id', $attributes['dropdown']->id)->value('attribute_option_id')
        );
        $this->assertCount(2, $product->attributeValues()->where('attribute_id', $attributes['multiselect']->id)->get());
        $this->assertTrue((bool) $product->attributeValues()->where('attribute_id', $attributes['boolean']->id)->value('value_bool'));
        $this->assertSame(8.0, (float) $product->attributeValues()->where('attribute_id', $attributes['numeric']->id)->value('value_numeric'));

        $range = $product->attributeValues()->where('attribute_id', $attributes['range']->id)->first();
        $this->assertSame(2.0, (float) $range->value_min);
        $this->assertSame(5.5, (float) $range->value_max);

        $this->assertSame('AC4', $product->attributeValues()->where('attribute_id', $attributes['text']->id)->value('value_text'));

        // The saved rows load back into the exact form state shape.
        $state = ProductResource::attributeValuesFormState($product->fresh());
        $this->assertSame($dropdownOption->id, $state[$attributes['dropdown']->id]);
        $this->assertEqualsCanonicalizing($multiOptions, $state[$attributes['multiselect']->id]);
        // Booleans are a 3-state select ('' / '1' / '0'), so the state is a string.
        $this->assertSame('1', $state[$attributes['boolean']->id]);
        $this->assertSame('8', $state[$attributes['numeric']->id]);
        $this->assertSame(['min' => '2', 'max' => '5.5'], $state[$attributes['range']->id]);
        $this->assertSame('AC4', $state[$attributes['text']->id]);

        // Clearing a value deletes its rows (delete + insert per attribute).
        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm([
                'attribute_values' => [
                    (string) $attributes['text']->id => null,
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(0, $product->attributeValues()->where('attribute_id', $attributes['text']->id)->count());
    }

    /**
     * BUG-4: search_synonyms carries UNIQUE(sub_category_id, phrase, locale) —
     * a duplicate used to reach the DB and blow up with SQLSTATE 23000.
     */
    public function test_duplicate_search_synonym_fails_validation_instead_of_the_database(): void
    {
        $this->signInAsAdmin();

        [$subCategory] = $this->makeClassifierFixture();
        $otherClass = $this->makeSiblingClass($subCategory);

        SearchSynonym::create([
            'sub_category_id' => $subCategory->id,
            'phrase' => 'mənzil üçün laminat',
            'locale' => 'az',
        ]);

        Livewire::test(CreateSearchSynonym::class)
            ->fillForm([
                'sub_category_id' => $subCategory->id,
                'phrase' => 'mənzil üçün laminat',
                'locale' => 'az',
            ])
            ->call('create')
            ->assertHasFormErrors(['phrase']);

        $this->assertSame(1, SearchSynonym::count());

        // The rule is scoped: the same phrase in another locale is legitimate.
        Livewire::test(CreateSearchSynonym::class)
            ->fillForm([
                'sub_category_id' => $subCategory->id,
                'phrase' => 'mənzil üçün laminat',
                'locale' => 'ru',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // ...and so is the same phrase under another product class.
        Livewire::test(CreateSearchSynonym::class)
            ->fillForm([
                'sub_category_id' => $otherClass->id,
                'phrase' => 'mənzil üçün laminat',
                'locale' => 'az',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(3, SearchSynonym::count());

        // ignoreRecord: re-saving the row itself must not trip its own rule.
        Livewire::test(EditSearchSynonym::class, ['record' => SearchSynonym::first()->getRouteKey()])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    /**
     * BUG-5: the stored values must always describe the product's CURRENT class.
     */
    public function test_changing_the_product_class_deletes_the_previous_classes_values(): void
    {
        $this->signInAsAdmin();

        [$subCategory, $attributes] = $this->makeClassifierFixture();
        $product = $this->makeProduct($subCategory);

        $product->attributeValues()->create([
            'attribute_id' => $attributes['text']->id,
            'value_text' => 'AC4',
        ]);
        $product->attributeValues()->create([
            'attribute_id' => $attributes['numeric']->id,
            'value_numeric' => 8,
        ]);

        // A second class in the same group, carrying a different attribute.
        $otherClass = $this->makeSiblingClass($subCategory);
        $otherAttribute = Attribute::create([
            'name' => ['az' => 'Yeni sinif atributu'],
            'slug' => 'attr-other-'.uniqid(),
            'field_type' => AttributeFieldType::Text,
        ]);
        $otherClass->attributes()->attach($otherAttribute, ['sort_order' => 0]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm([
                'sub_category_id' => $otherClass->id,
                'attribute_values' => [(string) $otherAttribute->id => 'Yeni dəyər'],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($otherClass->id, $product->fresh()->sub_category_id);
        $this->assertSame(
            [$otherAttribute->id],
            $product->attributeValues()->pluck('attribute_id')->map(fn ($id) => (int) $id)->all(),
        );
        $this->assertSame(
            'Yeni dəyər',
            $product->attributeValues()->where('attribute_id', $otherAttribute->id)->value('value_text'),
        );

        // Clearing the class hides the whole section — nothing is submitted, so the
        // remaining classifier values have to be wiped too.
        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['sub_category_id' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($product->fresh()->sub_category_id);
        $this->assertSame(0, $product->attributeValues()->count());
    }

    /**
     * BUG-7: classes belong to a GROUP category, so only that group's classes may
     * be offered (and accepted) for the selected category.
     */
    public function test_product_class_options_are_scoped_to_the_selected_category(): void
    {
        $this->signInAsAdmin();

        [$subCategory] = $this->makeClassifierFixture();
        $product = $this->makeProduct($subCategory);

        $otherGroup = Category::create([
            'parent_id' => $subCategory->category->parent_id,
            'name' => ['az' => 'Başqa qrup'],
            'slug' => 'basqa-qrup-'.uniqid(),
            'is_active' => true,
        ]);
        $foreignClass = SubCategory::create([
            'category_id' => $otherGroup->id,
            'slug' => 'basqa-sinif-'.uniqid(),
            'name' => ['az' => 'Başqa sinif'],
            'is_active' => true,
        ]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertFormFieldExists('sub_category_id', 'form', function (Select $field) use ($subCategory, $foreignClass): bool {
                $options = $field->getOptions();

                return array_key_exists($subCategory->id, $options)
                    && ! array_key_exists($foreignClass->id, $options);
            });

        // A class from another group must be rejected, not silently stored.
        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['sub_category_id' => $foreignClass->id])
            ->call('save')
            ->assertHasFormErrors(['sub_category_id']);

        $this->assertSame($subCategory->id, $product->fresh()->sub_category_id);
    }

    /**
     * BUG-8: an untouched boolean attribute must not persist value_bool = 0 —
     * those rows matched the catalog's boolean filter as an explicit "Xeyr".
     */
    public function test_boolean_attribute_stores_no_row_until_it_is_answered(): void
    {
        $this->signInAsAdmin();

        [$subCategory, $attributes] = $this->makeClassifierFixture();
        $product = $this->makeProduct($subCategory);
        $booleanId = $attributes['boolean']->id;

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(0, $product->attributeValues()->where('attribute_id', $booleanId)->count());

        // An explicit "Xeyr" is a real answer and IS stored.
        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['attribute_values' => [(string) $booleanId => '0']])
            ->call('save')
            ->assertHasNoFormErrors();

        $row = $product->attributeValues()->where('attribute_id', $booleanId)->first();
        $this->assertNotNull($row);
        $this->assertFalse((bool) $row->value_bool);
        $this->assertSame('0', ProductResource::attributeValuesFormState($product->fresh())[$booleanId]);

        // Emptying it removes the row again.
        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['attribute_values' => [(string) $booleanId => null]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(0, $product->attributeValues()->where('attribute_id', $booleanId)->count());
    }

    /**
     * BUG-9: dropdown/multiselect options can be entered while creating the
     * attribute, instead of only afterwards through the relation manager.
     */
    public function test_attribute_create_form_persists_options(): void
    {
        $this->signInAsAdmin();

        Livewire::test(CreateAttribute::class)
            ->fillForm([
                'name' => ['az' => 'Rəng', 'ru' => 'Цвет', 'en' => 'Colour'],
                'slug' => 'reng-test',
                'field_type' => AttributeFieldType::Dropdown->value,
                'complexity' => AttributeComplexity::Basic->value,
                'options' => [
                    ['value' => ['az' => 'Ağ'], 'sort_order' => 1],
                    ['value' => ['az' => 'Qara'], 'sort_order' => 2],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $attribute = Attribute::where('field_type', AttributeFieldType::Dropdown->value)->firstOrFail();
        $options = $attribute->options()->ordered()->get();

        $this->assertCount(2, $options);
        $this->assertSame(['Ağ', 'Qara'], $options->map(fn (AttributeOption $o) => $o->getTranslation('value', 'az'))->all());
        $this->assertCount(2, array_unique($options->pluck('slug')->all()));
        $this->assertNotContains('', $options->pluck('slug')->all());

        // Field types without options never render the editor, so nothing is saved.
        Livewire::test(CreateAttribute::class)
            ->fillForm([
                'name' => ['az' => 'Eni', 'ru' => 'Ширина', 'en' => 'Width'],
                'slug' => 'eni-test',
                'field_type' => AttributeFieldType::Numeric->value,
                'complexity' => AttributeComplexity::Basic->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $numeric = Attribute::where('field_type', AttributeFieldType::Numeric->value)->firstOrFail();
        $this->assertSame(0, $numeric->options()->count());
    }

    /**
     * BUG-11: a NULL pivot filter_priority (66 real rows have one) must render.
     */
    public function test_relation_manager_renders_attributes_with_a_null_filter_priority(): void
    {
        $this->signInAsAdmin();

        [$subCategory] = $this->makeClassifierFixture();

        $attribute = Attribute::create([
            'name' => ['az' => 'Prioritetsiz atribut'],
            'slug' => 'attr-nullprio-'.uniqid(),
            'field_type' => AttributeFieldType::Text,
        ]);
        $subCategory->attributes()->attach($attribute, [
            'is_filterable' => true,
            'filter_priority' => null,
            'sort_order' => 99,
        ]);

        Livewire::test(\App\Filament\Resources\SubCategoryResource\RelationManagers\AttributesRelationManager::class, [
            'ownerRecord' => $subCategory->fresh(),
            'pageClass' => \App\Filament\Resources\SubCategoryResource\Pages\EditSubCategory::class,
        ])->assertOk();

        $this->get(AttributeResource::getUrl('index'))->assertOk();
    }

    /**
     * BUG-14: enum labels go through the translations table, with the literal
     * Azerbaijani text as the fallback when the key is not seeded (or there is
     * no database at all, e.g. from a console command).
     */
    public function test_enum_labels_use_the_translations_table_with_an_az_fallback(): void
    {
        // Fallback first: a key that is not in the table renders the literal az
        // text. It has to be checked before the group is read for the first time,
        // because Laravel's translator keeps loaded groups in memory.
        Translation::where('group', 'admin-catalog')->where('key', 'field_type.textarea')->first()?->delete();
        App::setLocale('en');
        $this->assertSame('Uzun mətn', AttributeFieldType::Textarea->label());

        // The remaining keys come from TranslationsSeeder (group "admin-catalog"),
        // which the base TestCase seeds for every test.
        App::setLocale('az');
        $this->assertSame('Seçim (dropdown)', AttributeFieldType::Dropdown->label());
        $this->assertSame('Əsas', AttributeComplexity::Basic->label());
        $this->assertSame('Top (standart olaraq görünür)', FilterPriority::Top->label());

        App::setLocale('ru');
        $this->assertSame('Выбор (выпадающий список)', AttributeFieldType::Dropdown->label());
        $this->assertSame('Профессиональный', AttributeComplexity::Professional->label());
        $this->assertSame('Расширенный', FilterPriority::Advanced->label());

        App::setLocale('en');
        $this->assertSame('Select (dropdown)', AttributeFieldType::Dropdown->label());
        $this->assertSame('Basic', AttributeComplexity::Basic->label());
        $this->assertSame('Top (shown by default)', FilterPriority::Top->label());

        App::setLocale('az');
    }

    private function signInAsAdmin(): User
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin, 'admin');

        return $admin;
    }

    /**
     * Section → Group → Class with one attribute of every relevant field type.
     *
     * @return array{0: SubCategory, 1: array<string, Attribute>}
     */
    private function makeClassifierFixture(): array
    {
        $section = Category::create(['name' => ['az' => 'Döşəmələr'], 'slug' => 'dosemeler-'.uniqid(), 'is_active' => true]);
        $group = Category::create(['parent_id' => $section->id, 'name' => ['az' => 'Laminat və parket'], 'slug' => 'laminat-qrup-'.uniqid(), 'is_active' => true]);

        $subCategory = SubCategory::create([
            'category_id' => $group->id,
            'slug' => 'laminat-'.uniqid(),
            'name' => ['az' => 'Laminat'],
            'is_active' => true,
        ]);

        $application = Application::create(['name' => ['az' => 'Döşəmə'], 'slug' => 'doseme-'.uniqid()]);
        $subCategory->applications()->attach($application);

        $attributes = [];
        foreach ([
            'dropdown' => AttributeFieldType::Dropdown,
            'multiselect' => AttributeFieldType::Multiselect,
            'boolean' => AttributeFieldType::Boolean,
            'numeric' => AttributeFieldType::Numeric,
            'range' => AttributeFieldType::Range,
            'text' => AttributeFieldType::Text,
        ] as $key => $type) {
            $attribute = Attribute::create([
                'name' => ['az' => "Atribut {$key}"],
                'slug' => "attr-{$key}-".uniqid(),
                'field_type' => $type,
                'tooltip' => ['az' => 'İzah'],
            ]);

            if ($type->hasOptions()) {
                foreach (['Birinci', 'İkinci'] as $index => $value) {
                    AttributeOption::create([
                        'attribute_id' => $attribute->id,
                        'value' => ['az' => $value],
                        'slug' => "opt-{$index}-".uniqid(),
                        'sort_order' => $index,
                    ]);
                }
            }

            $subCategory->attributes()->attach($attribute, [
                'is_required' => $key === 'dropdown',
                'is_filterable' => true,
                'filter_priority' => 'top',
                'unit' => in_array($key, ['numeric', 'range'], true) ? 'мм' : null,
                'sort_order' => count($attributes),
            ]);

            $attributes[$key] = $attribute->fresh('options');
        }

        return [$subCategory, $attributes];
    }

    /**
     * Another product class under the SAME group category.
     */
    private function makeSiblingClass(SubCategory $subCategory): SubCategory
    {
        return SubCategory::create([
            'category_id' => $subCategory->category_id,
            'slug' => 'qonsu-sinif-'.uniqid(),
            'name' => ['az' => 'Qonşu sinif'],
            'is_active' => true,
        ]);
    }

    public function test_products_can_be_reordered_by_drag_and_drop(): void
    {
        $this->signInAsAdmin();

        [$subCategory] = $this->makeClassifierFixture();
        $first = $this->makeProduct($subCategory);
        $second = $this->makeProduct($subCategory);
        $third = $this->makeProduct($subCategory);

        // The column defaults to 0 in the database; the freshly created models
        // still hold null until they are reloaded.
        $this->assertSame([0, 0, 0], [
            $first->refresh()->sort_order,
            $second->refresh()->sort_order,
            $third->refresh()->sort_order,
        ]);

        Livewire::test(\App\Filament\Resources\ProductResource\Pages\ListProducts::class)
            ->assertOk()
            ->call('reorderTable', [$third->getKey(), $first->getKey(), $second->getKey()]);

        $this->assertSame(1, $third->refresh()->sort_order);
        $this->assertSame(2, $first->refresh()->sort_order);
        $this->assertSame(3, $second->refresh()->sort_order);
    }

    public function test_catalog_lists_positioned_products_first(): void
    {
        [$subCategory] = $this->makeClassifierFixture();
        $pinned = $this->makeProduct($subCategory);
        $other = $this->makeProduct($subCategory);

        // Popularity would put `$other` first; the manual position must win.
        $other->update(['views_count' => 500]);
        $pinned->update(['sort_order' => 1]);

        $slugs = $this->get('/catalog')->assertOk()->viewData('products')->pluck('slug')->all();

        $this->assertSame($pinned->slug, $slugs[0]);
        $this->assertContains($other->slug, $slugs);
    }

    public function test_catalog_order_is_untouched_while_nothing_is_positioned(): void
    {
        [$subCategory] = $this->makeClassifierFixture();
        $quiet = $this->makeProduct($subCategory);
        $popular = $this->makeProduct($subCategory);
        $popular->update(['views_count' => 500]);

        $slugs = $this->get('/catalog')->assertOk()->viewData('products')->pluck('slug')->all();

        $this->assertSame($popular->slug, $slugs[0], 'with every sort_order at 0 popularity still decides');
        $this->assertContains($quiet->slug, $slugs);
    }

    public function test_admin_edit_form_prefills_values_the_seller_saved(): void
    {
        $this->signInAsAdmin();

        [$subCategory, $attributes] = $this->makeClassifierFixture();
        $product = $this->makeProduct($subCategory);

        // Exactly the rows the seller's form writes.
        $option = $attributes['dropdown']->options->first();
        $product->attributeValues()->create([
            'attribute_id' => $attributes['dropdown']->id,
            'attribute_option_id' => $option->id,
        ]);
        $product->attributeValues()->create([
            'attribute_id' => $attributes['text']->id,
            'value_text' => 'AC5',
        ]);

        // The rest of the class's attributes are present but empty, so compare the
        // two the seller actually filled rather than the whole state array.
        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertOk()
            ->assertFormSet(fn (array $state): bool => (int) $state['attribute_values'][$attributes['dropdown']->id] === $option->id
                && $state['attribute_values'][$attributes['text']->id] === 'AC5');
    }

    public function test_seller_written_free_form_attributes_are_visible_in_admin(): void
    {
        $this->signInAsAdmin();

        [$subCategory] = $this->makeClassifierFixture();
        $product = $this->makeProduct($subCategory);

        // Exactly what the seller form writes: fixed keys + the seller's own rows.
        $product->update(['specifications' => [
            'material' => 'Keramoqranit',
            'Zəmanət müddəti' => '24 ay',
        ]]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertOk()
            ->assertFormSet([
                'specifications' => [
                    'material' => 'Keramoqranit',
                    'Zəmanət müddəti' => '24 ay',
                ],
            ]);
    }

    private function makeProduct(SubCategory $subCategory): Product
    {
        $seller = User::factory()->seller()->create();

        return Product::create([
            'user_id' => $seller->id,
            'category_id' => $subCategory->category_id,
            'sub_category_id' => $subCategory->id,
            'name' => ['az' => 'Test laminat', 'ru' => 'Тест ламинат', 'en' => 'Test laminate'],
            'description' => ['az' => ''],
            'slug' => 'test-laminat-'.uniqid(),
            'price' => 25,
            'stock' => 10,
            'is_visible' => true,
            'is_approved' => true,
        ]);
    }
}
