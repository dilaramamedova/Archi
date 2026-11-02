<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\B2bCooperationType;
use App\Enums\B2bPlan;
use App\Models\B2bCategory;
use App\Models\B2bCompany;
use Illuminate\Database\Seeder;

/**
 * A few real B2B companies so the directory is not empty on launch.
 * Idempotent (keyed by slug), safe to run on prod:
 *   php artisan db:seed --class=B2bCompanySeeder
 */
final class B2bCompanySeeder extends Seeder
{
    public function run(): void
    {
        $category = fn (string $slug): ?int => B2bCategory::query()->where('slug', $slug)->value('id');

        $rows = [
            [
                'slug' => 'anatolia-ceramics',
                'legal_name' => 'Anatolia Ceramics Ltd.',
                'brand_name' => 'Anatolia Ceramics',
                'category' => 'keramika-ve-kafel',
                'country' => 'Türkiyə',
                'cooperation_type' => B2bCooperationType::Distributor,
                'market' => 'international', 'plan' => B2bPlan::Premium, 'featured' => true, 'verified' => true,
                'description' => 'Türkiyə istehsalı keramoqranit və kafel üzrə ixracatçı. 20 ildən artıq təcrübə, 40+ ölkəyə tədarük.',
                'moq' => '1 konteyner', 'brands' => 'Anatolia, Vitra',
                'email' => 'export@anatolia.example', 'phone' => '+90 212 000 00 00', 'whatsapp' => '+905000000000',
                'website' => 'https://anatolia.example', 'working_hours' => 'B.e–Cümə 09:00–18:00',
                'languages' => ['tr', 'en', 'ru'],
            ],
            [
                'slug' => 'flos-lighting',
                'legal_name' => 'FLOS S.p.A.',
                'brand_name' => 'FLOS',
                'category' => 'elektrik-ve-isiqlandirma',
                'country' => 'İtaliya',
                'cooperation_type' => B2bCooperationType::OfficialRep,
                'market' => 'international', 'plan' => B2bPlan::Pro, 'featured' => false, 'verified' => true,
                'description' => 'İtaliya dizayn işıqlandırma brendi — dekorativ və memarlıq işıqlandırması üzrə rəsmi tədarük.',
                'moq' => 'Razılaşma ilə', 'brands' => 'FLOS',
                'email' => 'b2b@flos.example', 'phone' => '+39 02 000 00 00',
                'website' => 'https://flos.example', 'working_hours' => 'B.e–Cümə 09:00–17:00',
                'languages' => ['en', 'ru'],
            ],
            [
                'slug' => 'knauf-azerbaijan',
                'legal_name' => 'Knauf Azərbaycan MMC',
                'brand_name' => 'Knauf',
                'category' => 'tikinti-materiallari',
                'country' => 'Azərbaycan',
                'cooperation_type' => B2bCooperationType::Distributor,
                'market' => 'local', 'plan' => B2bPlan::Basic, 'featured' => false, 'verified' => true,
                'description' => 'Gips əsaslı quru qarışıqlar və tikinti materialları üzrə yerli distribütor və təchizatçı.',
                'moq' => '1 palet', 'brands' => 'Knauf',
                'email' => 'info@knauf.example', 'phone' => '+994 12 000 00 00', 'whatsapp' => '+994500000000',
                'website' => 'https://knauf.example', 'working_hours' => 'B.e–Şənbə 09:00–18:00',
                'languages' => ['az', 'ru'],
            ],
        ];

        foreach ($rows as $row) {
            if (B2bCompany::query()->where('slug', $row['slug'])->exists()) {
                continue;
            }

            B2bCompany::query()->create([
                'slug' => $row['slug'],
                'b2b_category_id' => $category($row['category']),
                'legal_name' => $row['legal_name'],
                'brand_name' => $row['brand_name'],
                'country' => $row['country'],
                'cooperation_type' => $row['cooperation_type'],
                'market' => $row['market'],
                'plan' => $row['plan'],
                'is_featured' => $row['featured'],
                'is_verified' => $row['verified'],
                'description' => ['az' => $row['description']],
                'moq' => $row['moq'],
                'brands' => $row['brands'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'whatsapp' => $row['whatsapp'] ?? null,
                'website' => $row['website'],
                'working_hours' => $row['working_hours'],
                'languages' => $row['languages'],
                'contact_person' => 'Export Department',
                'is_published' => true,
                'published_at' => now(),
            ]);
        }
    }
}
