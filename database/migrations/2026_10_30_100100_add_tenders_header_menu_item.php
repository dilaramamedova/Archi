<?php

declare(strict_types=1);

use App\Models\MenuItem;
use Illuminate\Database\Migrations\Migration;

/**
 * "Tenderlər" in the header nav, right after "Haqqımızda". The header is
 * admin-managed (MenuItem, location header_main), so this only inserts the
 * row when it is missing and shifts the items behind it by one.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (MenuItem::query()->where('location', 'header_main')->where('route_name', 'tenders.index')->exists()) {
            return;
        }

        $after = MenuItem::query()->where('location', 'header_main')->whereNull('parent_id')
            ->where('route_name', 'about')->value('sort_order');

        $sort = $after !== null
            ? (int) $after + 1
            : (int) MenuItem::query()->where('location', 'header_main')->whereNull('parent_id')->max('sort_order') + 1;

        MenuItem::query()->where('location', 'header_main')->whereNull('parent_id')
            ->where('sort_order', '>=', $sort)
            ->increment('sort_order');

        MenuItem::query()->create([
            'location' => 'header_main',
            'label' => ['az' => 'Tenderlər', 'ru' => 'Тендеры', 'en' => 'Tenders'],
            'route_name' => 'tenders.index',
            'sort_order' => $sort,
            'is_active' => true,
        ]);
    }

    public function down(): void
    {
        $item = MenuItem::query()->where('location', 'header_main')->where('route_name', 'tenders.index')->first();

        if (! $item) {
            return;
        }

        $item->delete();

        MenuItem::query()->where('location', 'header_main')->whereNull('parent_id')
            ->where('sort_order', '>', $item->sort_order)
            ->decrement('sort_order');
    }
};
