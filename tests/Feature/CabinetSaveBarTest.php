<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ['/business/profile/showrooms'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('sellerCabinetPages')]
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
