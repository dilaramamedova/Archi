<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\SpecialistProfile;
use App\Models\SpecialistSpecialty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageCurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_shows_only_flagged_and_curated_products(): void
    {
        $seller = User::factory()->seller()->create();

        $onHome = $this->product($seller, ['name' => ['az' => 'Ana səhifə seçilmiş'], 'is_featured' => true, 'show_on_homepage' => true]);
        $offHome = $this->product($seller, ['name' => ['az' => 'Gizli seçilmiş'], 'is_featured' => true, 'show_on_homepage' => false]);

        $saleOn = $this->product($seller, ['name' => ['az' => 'Ana səhifə SALE'], 'is_sale' => true, 'show_on_homepage' => true]);
        $saleOff = $this->product($seller, ['name' => ['az' => 'Gizli SALE'], 'is_sale' => true, 'show_on_homepage' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Ana səhifə seçilmiş')
            ->assertDontSee('Gizli seçilmiş')
            ->assertSee('Ana səhifə SALE')
            ->assertDontSee('Gizli SALE');
    }

    public function test_homepage_shows_only_curated_featured_specialists(): void
    {
        [$onUser] = $this->specialist('Görünən Usta', ['is_featured' => true, 'show_on_homepage' => true]);
        [$offUser] = $this->specialist('Gizli Usta', ['is_featured' => true, 'show_on_homepage' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Görünən Usta')
            ->assertDontSee('Gizli Usta');
    }

    public function test_catalog_featured_filter_shows_only_featured_products(): void
    {
        $seller = User::factory()->seller()->create();

        $this->product($seller, ['name' => ['az' => 'Featured məhsul'], 'is_featured' => true]);
        $this->product($seller, ['name' => ['az' => 'Adi məhsul'], 'is_featured' => false]);

        $this->get('/catalog?featured=1')
            ->assertOk()
            ->assertSee('Featured məhsul')
            ->assertDontSee('Adi məhsul');
    }

    public function test_catalog_sale_filter_shows_only_sale_products(): void
    {
        $seller = User::factory()->seller()->create();

        $this->product($seller, ['name' => ['az' => 'SALE məhsul'], 'is_sale' => true]);
        $this->product($seller, ['name' => ['az' => 'Normal məhsul'], 'is_sale' => false]);

        $this->get('/catalog?sale=1')
            ->assertOk()
            ->assertSee('SALE məhsul')
            ->assertDontSee('Normal məhsul');
    }

    public function test_specialists_featured_filter_shows_only_featured(): void
    {
        $this->specialist('Featured Mütəxəssis', ['is_featured' => true]);
        $this->specialist('Adi Mütəxəssis', ['is_featured' => false]);

        $this->get('/specialists?featured=1')
            ->assertOk()
            ->assertSee('Featured Mütəxəssis')
            ->assertDontSee('Adi Mütəxəssis');
    }

    // ─── Helpers ──────────────────────────────────────────────

    private function product(User $seller, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'user_id' => $seller->id,
            'category_id' => Category::query()->create([
                'name' => ['az' => 'Kateqoriya'],
                'slug' => 'cat-'.uniqid(),
                'is_active' => true,
            ])->id,
            'name' => ['az' => 'Məhsul'],
            'description' => ['az' => ''],
            'slug' => 'p-'.uniqid(),
            'price' => 10,
            'stock' => 10,
            'is_visible' => true,
            'is_approved' => true,
        ], $overrides));
    }

    private function specialist(string $name, array $overrides = []): array
    {
        $user = User::factory()->master()->create(['name' => $name, 'first_name' => explode(' ', $name)[0], 'last_name' => explode(' ', $name)[1] ?? '']);

        $specialty = SpecialistSpecialty::firstOrCreate(
            ['slug' => 'usta'],
            ['name' => 'Usta', 'is_active' => true],
        );

        $profile = SpecialistProfile::create(array_merge([
            'user_id' => $user->id,
            'specialist_specialty_id' => $specialty->id,
            'city' => 'Bakı',
        ], $overrides));

        return [$user, $profile];
    }
}
