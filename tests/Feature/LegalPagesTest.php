<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\LegalPage;
use Database\Seeders\LegalPageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_four_legal_pages_render_with_toc_anchors(): void
    {
        $this->seed(LegalPageSeeder::class);

        foreach ([
            '/terms' => 'İstifadə şərtləri',
            '/privacy' => 'Gizlilik siyasəti',
            '/delivery' => 'Çatdırılma & qaytarma',
            '/cookies' => 'Cookie siyasəti',
        ] as $url => $title) {
            $this->get($url)
                ->assertOk()
                ->assertSee($title)
                ->assertSee('Bu səhifədə')       // TOC sidebar
                ->assertSee('Son yenilənmə')     // last-updated line
                ->assertSee('id="sec-1-', false) // injected anchor ids
                ->assertSee('data-toc-link', false);
        }
    }

    public function test_inactive_legal_page_returns_404(): void
    {
        $this->seed(LegalPageSeeder::class);
        LegalPage::where('slug', 'terms')->update(['is_active' => false]);

        $this->get('/terms')->assertNotFound();
    }

    public function test_legal_pages_render_in_russian(): void
    {
        $this->seed(LegalPageSeeder::class);

        $this->withSession(['locale' => 'ru'])
            ->get('/terms')
            ->assertOk()
            ->assertSee('Условия использования')
            ->assertSee('На этой странице');
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(LegalPageSeeder::class);
        $this->seed(LegalPageSeeder::class);

        $this->assertSame(4, LegalPage::count());
    }
}
