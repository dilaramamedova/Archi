<?php

declare(strict_types=1);

use App\Models\MenuItem;
use Illuminate\Database\Migrations\Migration;

/**
 * Two more cards in the header's «Mütəxəssislər» mega panel — Briqadirlər and
 * Maklerlər — linking to /specialists?type=foreman|realtor.
 *
 * The panel is a two-column grid, so the cards are slotted to read row by row the way
 * the directory sections do: Arxitektor / Dizayner, Ustalar / Briqadirlər,
 * Tikinti şirkətləri / Maklerlər. That moves the company card from 4 to 5.
 * Mirrors MenuItemSeeder for fresh installs.
 */
return new class extends Migration
{
    private const LOCATION = 'header_mega_specialists';

    public function up(): void
    {
        MenuItem::query()
            ->where('location', self::LOCATION)
            ->where('url', '/specialists?type=company')
            ->where('sort_order', 4)
            ->update(['sort_order' => 5]);

        foreach ($this->cards() as $card) {
            $exists = MenuItem::query()
                ->where('location', self::LOCATION)
                ->where('url', $card['url'])
                ->exists();

            if (! $exists) {
                MenuItem::create($card + ['location' => self::LOCATION, 'is_active' => true]);
            }
        }
    }

    public function down(): void
    {
        MenuItem::query()
            ->where('location', self::LOCATION)
            ->whereIn('url', array_column($this->cards(), 'url'))
            ->delete();

        MenuItem::query()
            ->where('location', self::LOCATION)
            ->where('url', '/specialists?type=company')
            ->where('sort_order', 5)
            ->update(['sort_order' => 4]);
    }

    /** @return list<array<string, mixed>> */
    private function cards(): array
    {
        return [
            [
                'label' => ['az' => 'Briqadirlər', 'ru' => 'Бригадиры', 'en' => 'Foremen'],
                'description' => ['az' => 'Usta dəstəsi ilə tam iş', 'ru' => 'Бригада мастеров под ключ', 'en' => 'A whole crew for the whole job'],
                'icon' => 'icon-bricks.svg',
                'url' => '/specialists?type=foreman',
                'sort_order' => 4,
            ],
            [
                'label' => ['az' => 'Maklerlər', 'ru' => 'Риелторы', 'en' => 'Realtors'],
                'description' => ['az' => 'Alqı-satqı və kirayə', 'ru' => 'Покупка, продажа и аренда', 'en' => 'Buying, selling and renting'],
                'icon' => 'icon-house.svg',
                'url' => '/specialists?type=realtor',
                'sort_order' => 6,
            ],
        ];
    }
};
