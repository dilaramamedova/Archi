<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use Illuminate\Database\Seeder;

class BlogCategorySeeder extends Seeder
{
    public function run(): void
    {
        $cats = [
            ['title' => ['az' => 'Təmir', 'en' => 'Renovation', 'ru' => 'Ремонт'], 'slug' => 'repair', 'sort_order' => 1],
            ['title' => ['az' => 'Materiallar', 'en' => 'Materials', 'ru' => 'Материалы'], 'slug' => 'materials', 'sort_order' => 2],
            ['title' => ['az' => 'Büdcə', 'en' => 'Budget', 'ru' => 'Бюджет'], 'slug' => 'budget', 'sort_order' => 3],
            ['title' => ['az' => 'Dizayn', 'en' => 'Design', 'ru' => 'Дизайн'], 'slug' => 'design', 'sort_order' => 4],
            ['title' => ['az' => 'Ustalar', 'en' => 'Masters', 'ru' => 'Мастера'], 'slug' => 'masters', 'sort_order' => 5],
            ['title' => ['az' => 'San texnika', 'en' => 'Plumbing', 'ru' => 'Сантехника'], 'slug' => 'plumbing', 'sort_order' => 6],
            ['title' => ['az' => 'İzolyasiya', 'en' => 'Insulation', 'ru' => 'Изоляция'], 'slug' => 'insulation', 'sort_order' => 7],
        ];

        foreach ($cats as $c) {
            BlogCategory::updateOrCreate(['slug' => $c['slug']], $c);
        }
    }
}
