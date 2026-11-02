<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tender;
use App\Models\TenderCategory;
use Illuminate\Database\Seeder;

/**
 * One real, published tender so the board is not empty on launch.
 * Idempotent (keyed by slug), so it is safe to run on prod:
 *   php artisan db:seed --class=TenderSeeder
 */
final class TenderSeeder extends Seeder
{
    private const SLUG = 'ofis-interyer-dizayni-ve-temir';

    public function run(): void
    {
        if (Tender::query()->where('slug', self::SLUG)->exists()) {
            return;
        }

        $categoryId = TenderCategory::query()
            ->whereIn('slug', ['memarliq-ve-dizayn', 'tikinti-ve-temir'])
            ->orderBy('sort_order')
            ->value('id');

        Tender::query()->create([
            'slug' => self::SLUG,
            'tender_category_id' => $categoryId,
            'title' => ['az' => 'Ofis interyer dizaynı və təmir işləri'],
            'summary' => ['az' => '420 m² ofis sahəsi üçün dizayn layihəsi, təmir və açar təslim quraşdırma.'],
            'description' => ['az' => 'Bakı şəhərində 420 m² ofis sahəsinin müasir dizayn konsepsiyasının hazırlanması, təmir-tikinti işləri və mebel quraşdırılması nəzərdə tutulur. Məqsəd — funksional, işçi rahatlığını nəzərə alan və şirkət kimliyinə uyğun iş məkanı yaratmaqdır.'],
            'scope' => ['az' => "Dizayn konsepsiyası və 3D vizuallaşdırma\nİşçi çertyojların hazırlanması\nTəmir-tikinti işləri\nMebel və avadanlığın quraşdırılması"],
            'requirements' => ['az' => "Oxşar kommersiya obyektlərində təcrübə\nVÖEN və şirkət rekvizitləri\nİcra qrafiki və smeta\nTamamlanmış layihələrdən nümunələr"],
            'submission_documents' => ['az' => "Kommersiya təklifi — PDF\nŞirkət təqdimatı və portfolio — PDF\nİcra qrafiki — PDF və ya XLSX\nVÖEN və rekvizitlər — PDF"],
            'organizer_name' => 'ARCHI Development',
            'city' => 'Bakı',
            'area_sqm' => 420,
            'budget_min' => null,
            'budget_max' => null,
            'duration' => ['az' => '10 həftə'],
            'contact_name' => ['az' => 'Tender şöbəsi'],
            'contact_email' => 'tender@archi.az',
            'contact_phone' => '+994 12 310 00 00',
            'deadline_at' => now()->addDays(21)->setTime(18, 0),
            'is_published' => true,
            'published_at' => now(),
        ]);
    }
}
