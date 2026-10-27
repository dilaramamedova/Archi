<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DictionaryRequestStatus;
use App\Filament\Resources\DictionaryRequestResource;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Models\AttributeOption;
use App\Models\Brand;
use App\Models\Category;
use App\Models\DictionaryRequest;
use App\Models\LaminateSpec;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\User;
use App\Services\Laminate\DictionaryRequestResolver;
use App\Services\Laminate\LaminateCatalog;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The moderator's side of the laminate card (TZ «Ламинат» §8): dictionary proposals
 * become values only when a moderator approves them, and an incomplete card cannot
 * be approved.
 */
final class LaminateModerationTest extends TestCase
{
    use RefreshDatabase;

    private SubCategory $class;

    private Category $group;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $section = Category::create(['name' => ['az' => 'Döşəmələr'], 'slug' => 'dosemeler', 'is_active' => true]);
        $this->group = Category::create(['name' => ['az' => 'Polimer'], 'slug' => 'polimer', 'parent_id' => $section->id, 'is_active' => true]);
        $this->class = SubCategory::create([
            'name' => ['az' => 'Laminat'], 'slug' => LaminateCatalog::CLASS_SLUG,
            'category_id' => $this->group->id, 'is_active' => true,
        ]);

        // Migration-made dictionaries do not survive a truncating test earlier in
        // the suite; this restores them and forgets memoised ids.
        LaminateCatalog::ensureDictionaries();
    }

    public function test_approving_a_brand_creates_it_and_gives_it_to_every_card_that_proposed_it(): void
    {
        $a = $this->draftWithNewBrand('a@example.com', 'Egger');
        $b = $this->draftWithNewBrand('b@example.com', '  EGGER ');

        $request = DictionaryRequest::query()->where('product_id', $a->id)->firstOrFail();
        $brandId = app(DictionaryRequestResolver::class)->approve($request, null);

        $this->assertSame('Egger', Brand::findOrFail($brandId)->getTranslation('name', 'az'));
        $this->assertTrue(Brand::findOrFail($brandId)->is_active);
        $this->assertSame($brandId, $a->fresh()->brand_id);
        $this->assertSame($brandId, $b->fresh()->brand_id, 'the same value proposed by another seller is resolved with it');
        $this->assertSame(0, DictionaryRequest::query()->pending()->count());
    }

    public function test_a_proposal_can_be_mapped_onto_an_existing_brand_instead(): void
    {
        $existing = Brand::create(['name' => ['az' => 'Kronospan'], 'slug' => 'kronospan', 'is_active' => true]);
        $product = $this->draftWithNewBrand('a@example.com', 'Kronospan Group');

        app(DictionaryRequestResolver::class)->approve(DictionaryRequest::firstOrFail(), null, $existing->id);

        $this->assertSame($existing->id, $product->fresh()->brand_id);
        $this->assertSame(1, Brand::count(), 'no near-duplicate brand was created');
    }

    public function test_approving_an_option_adds_it_to_the_right_dictionary_and_to_the_card(): void
    {
        $product = $this->draft('a@example.com', ['usage_class_proposed' => '35']);

        $optionId = app(DictionaryRequestResolver::class)->approve(
            DictionaryRequest::firstOrFail(), null, null, ['az' => '35', 'ru' => '35', 'en' => '35'],
        );

        $this->assertSame(LaminateCatalog::ATTR_USAGE, AttributeOption::findOrFail($optionId)->attribute->slug);
        $this->assertSame($optionId, LaminateSpec::where('product_id', $product->id)->value('usage_class_option_id'));
    }

    public function test_mapping_onto_an_option_of_another_dictionary_is_refused(): void
    {
        $this->draft('a@example.com', ['usage_class_proposed' => 'Super']);
        $ac = LaminateCatalog::optionIds(LaminateCatalog::ATTR_ABRASION)['ac4'];

        $this->expectException(InvalidArgumentException::class);
        app(DictionaryRequestResolver::class)->approve(DictionaryRequest::firstOrFail(), null, $ac);
    }

    public function test_a_rejection_needs_a_reason_and_leaves_the_card_empty(): void
    {
        $product = $this->draft('a@example.com', ['abrasion_proposed' => 'AC7']);
        $request = DictionaryRequest::firstOrFail();

        try {
            app(DictionaryRequestResolver::class)->reject($request, null, '   ');
            $this->fail('an empty reason must be refused');
        } catch (InvalidArgumentException) {
        }

        app(DictionaryRequestResolver::class)->reject($request, null, 'AC7 sinfi standartda yoxdur');

        $this->assertSame(DictionaryRequestStatus::Rejected, $request->fresh()->status);
        $this->assertNull(LaminateSpec::where('product_id', $product->id)->value('abrasion_option_id'));
    }

    public function test_the_moderator_screens_render_with_the_laminate_card(): void
    {
        $product = $this->draft('a@example.com', ['model_name' => 'Classic', 'usage_class_proposed' => '35']);
        $this->actingAs(User::factory()->admin()->create(), 'admin');

        $this->get(ProductResource::getUrl('edit', ['record' => $product]))
            ->assertOk()
            ->assertSee('Laminat kartı')
            ->assertSee('Yayım üçün çatışmır')
            ->assertSee('35');

        $this->get(DictionaryRequestResource::getUrl('index'))->assertOk()->assertSee('35');
    }

    public function test_an_incomplete_laminate_card_cannot_be_approved(): void
    {
        $product = $this->draft('a@example.com', ['model_name' => 'Classic']);
        $product->update(['is_visible' => true]);
        $this->actingAs(User::factory()->admin()->create(), 'admin');

        Livewire::test(ListProducts::class)->callTableAction('approve', $product);

        $this->assertFalse($product->fresh()->is_approved);
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    private function draft(string $email, array $laminate, array $extra = []): Product
    {
        $seller = User::where('email', $email)->first() ?? User::factory()->seller()->create(['email' => $email]);

        $this->actingAs($seller)->post('/business/products', array_merge([
            'name' => 'Laminat '.uniqid(),
            'category_id' => $this->group->id,
            'sub_category_id' => $this->class->id,
            'price' => '16.90',
            'unit' => 'm2',
            'stock' => 10,
            'publish' => '0',
            'images' => [UploadedFile::fake()->image('p.jpg')],
            'laminate' => $laminate,
        ], $extra), ['Accept' => 'application/json'])->assertOk();

        return Product::where('user_id', $seller->id)->latest('id')->firstOrFail();
    }

    private function draftWithNewBrand(string $email, string $brand): Product
    {
        return $this->draft($email, ['model_name' => 'X'], ['new_brand' => $brand]);
    }
}
