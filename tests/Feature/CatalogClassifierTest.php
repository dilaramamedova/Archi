<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AttributeFieldType;
use App\Enums\FilterPriority;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\SubCategory;
use Database\Seeders\CatalogClassifierSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogClassifierTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CatalogClassifierSeeder::class);
    }

    public function test_tree_has_twelve_active_root_sections(): void
    {
        $this->assertSame(12, Category::roots()->active()->count());
        $this->assertSame(12, Category::roots()->active()->showOnHome()->count());

        // Every group hangs off a section, every class off a group.
        $this->assertSame(0, Category::whereNotNull('parent_id')->whereHas('parent', fn ($q) => $q->whereNotNull('parent_id'))->count());
        $this->assertSame(0, SubCategory::whereDoesntHave('category')->count());
        $this->assertGreaterThanOrEqual(250, SubCategory::count());
    }

    public function test_laminat_class_sits_under_dosemeler_with_expected_form_definition(): void
    {
        $laminat = SubCategory::where('slug', 'laminat')->firstOrFail();

        $this->assertSame('Laminat', $laminat->getTranslation('name', 'az'));
        $this->assertSame('Döşəmələr', $laminat->category->parent->getTranslation('name', 'az'));

        $attributes = $laminat->attributes;
        $this->assertGreaterThanOrEqual(4, $attributes->count());

        // A known required, top-priority filter attribute with options.
        $wear = $attributes->first(fn ($a) => $a->getTranslation('name', 'az') === 'Aşınma davamlılığı sinfi');
        $this->assertNotNull($wear);
        $this->assertSame(AttributeFieldType::Dropdown, $wear->field_type);
        $this->assertTrue($wear->pivot->is_required);
        $this->assertTrue($wear->pivot->is_filterable);
        $this->assertSame(FilterPriority::Top, $wear->pivot->filter_priority);
        $this->assertNotEmpty($wear->options);
        $this->assertNotSame('', (string) $wear->getTranslation('tooltip', 'az'));

        // Display unit lives on the pivot.
        $thickness = $attributes->first(fn ($a) => $a->getTranslation('name', 'az') === 'Qalınlıq');
        $this->assertSame('мм', $thickness->pivot->unit);

        // ru/en were seeded as explicit copies of az.
        $this->assertSame('Laminat', $laminat->getTranslation('name', 'ru'));

        $this->assertContains('mənzil üçün laminat', $laminat->synonyms->pluck('phrase')->all());
        $this->assertContains('döşəmə', $laminat->applications->map(fn ($a) => $a->getTranslation('name', 'az'))->all());
    }

    public function test_seeder_is_idempotent(): void
    {
        $counts = fn (): array => [
            Category::count(),
            SubCategory::count(),
            Attribute::count(),
            DB::table('attribute_options')->count(),
            DB::table('attribute_sub_category')->count(),
            DB::table('applications')->count(),
            DB::table('sub_category_applications')->count(),
            DB::table('search_synonyms')->count(),
        ];

        $before = $counts();
        $this->seed(CatalogClassifierSeeder::class);

        $this->assertSame($before, $counts());
    }
}
