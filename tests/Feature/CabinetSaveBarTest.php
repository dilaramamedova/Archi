<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The save bar used to greet every cabinet page with "Yadda saxlanmamış
 * dəyişikliklər var" before the user had touched anything. It must now render
 * in a neutral state and only warn once JS flips data-dirty.
 */
class CabinetSaveBarTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<int, string> */
    public static function sellerCabinetPages(): array
    {
        return [
            ['/business/profile/company'],
            ['/business/profile/contact'],
            ['/business/profile/notifications'],
        ];
    }

    /**
     * Showrooms is deliberately absent from the list above: every write on that
     * page goes through the modal, which POSTs and reloads, so a page-level Save
     * has nothing to submit. It used to render one anyway and the button merely
     * flipped the bar to "saved" — two Save buttons on screen, and the wrong one
     * silently discarded the showroom the user had just filled in.
     */
    public function test_showrooms_page_has_no_save_bar(): void
    {
        $seller = User::factory()->seller()->create();

        $html = $this->actingAs($seller)->get('/business/profile/showrooms')->assertOk()->getContent();

        $this->assertStringNotContainsString('cab-save-bar', $html);
    }

    #[DataProvider('sellerCabinetPages')]
    public function test_save_bar_starts_clean_on_load(string $url): void
    {
        $seller = User::factory()->seller()->create();

        $html = $this->actingAs($seller)->get($url)->assertOk()->getContent();

        $this->assertStringContainsString('data-dirty="false"', $html, $url.' must render a clean save bar');
        $this->assertStringNotContainsString('data-dirty="true"', $html, $url.' must not start dirty');
        $this->assertStringContainsString('data-saved="false"', $html);
    }

    public function test_specialist_cabinet_save_bar_starts_clean(): void
    {
        $master = User::factory()->master()->create();

        $html = $this->actingAs($master)->get('/specialist/cabinet')->assertOk()->getContent();

        $this->assertStringContainsString('data-dirty="false"', $html);
        $this->assertStringNotContainsString('data-dirty="true"', $html);
    }
}
