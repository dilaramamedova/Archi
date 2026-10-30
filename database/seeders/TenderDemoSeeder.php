<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tender;
use App\Models\TenderCategory;
use Illuminate\Database\Seeder;

/**
 * Demo tenders from the Figma mock-ups — local/dev only, never run on prod.
 * php artisan db:seed --class=TenderDemoSeeder
 */
final class TenderDemoSeeder extends Seeder
{
    public function run(): void
    {
        $category = fn (string $slug): ?int => TenderCategory::query()->where('slug', $slug)->value('id');

        $rows = [
            [
                'category' => 'memarliq-ve-dizayn', 'organizer' => 'Forma Development', 'city' => 'Bakı',
                'title' => 'Satış ofisinin interyer dizaynı',
                'summary' => '350 m² satış ofisi üçün konsepsiya, işçi çertyojlar və vizuallaşdırma.',
                'description' => 'Bakı şəhərində 350 m² satış ofisi üçün interyer layihəsinin hazırlanması. Məqsəd — müasir, funksional və brend kimliyinə uyğun satış məkanının konsepsiyadan icra sənədlərinədək hazırlanmasıdır.',
                'scope' => "Konsepsiya və moodboard\nPlanlaşdırma həlli\nİşçi çertyojlar\n3D vizuallaşdırma",
                'requirements' => "Oxşar kommersiya layihələri üzrə təcrübə\nVÖEN və şirkət rekvizitləri\nLayihə komandası haqqında məlumat",
                'documents' => "Kommersiya təklifi — PDF\nŞirkət təqdimatı və portfolio — PDF\nİş qrafiki — PDF və ya XLSX\nVÖEN və rekvizitlər — PDF\nTexniki yanaşma — PDF",
                'area' => 350, 'min' => null, 'max' => null, 'duration' => '8 həftə', 'deadline' => '+12 days',
            ],
            [
                'category' => 'tikinti-ve-temir', 'organizer' => 'Nova Build', 'city' => 'Sumqayıt',
                'title' => 'Yaşayış binasının fasad işləri',
                'summary' => 'Çoxmərtəbəli yaşayış binasının fasad üzlənməsi, izolyasiya və tamamlayıcı işlər.',
                'description' => 'Sumqayıtda 12 mərtəbəli yaşayış binasının fasadının üzlənməsi və istilik izolyasiyası.',
                'scope' => "Fasad izolyasiyası\nÜzlük panellərinin quraşdırılması\nTamamlayıcı işlər",
                'requirements' => "Fasad işləri üzrə ən azı 3 il təcrübə\nTəhlükəsizlik sertifikatları",
                'documents' => "Kommersiya təklifi — PDF\nSmeta — XLSX",
                'area' => 4200, 'min' => 180000, 'max' => 220000, 'duration' => '4 ay', 'deadline' => '+15 days',
            ],
            [
                'category' => 'mebel', 'organizer' => 'Linea Interiors', 'city' => 'Bakı',
                'title' => 'Ofis mebellərinin istehsalı',
                'summary' => 'Yeni ofis üçün işçi masaları, şkaflar və görüş otağı mebellərinin istehsalı və quraşdırılması.',
                'description' => 'Yeni açılan ofis üçün 60 iş yeri, arxiv şkafları və görüş otağı mebelləri.',
                'scope' => "İşçi masaları — 60 əd.\nArxiv şkafları\nGörüş otağı dəsti",
                'requirements' => "Öz istehsal sahəsi\nNümunə layihələr",
                'documents' => "Kommersiya təklifi — PDF\nMaterial nümunələri — PDF",
                'area' => null, 'min' => 45000, 'max' => 60000, 'duration' => '6 həftə', 'deadline' => '+18 days',
            ],
            [
                'category' => 'material-techizati', 'organizer' => 'Caspian Supply', 'city' => 'Bakı',
                'title' => 'Keramoqranit təchizatı',
                'summary' => 'Layihə üçün 1200 m² keramoqranitin təchizatı və çatdırılması.',
                'description' => 'Ticarət mərkəzi layihəsi üçün 60×120 formatlı keramoqranit təchizatı.',
                'scope' => "1200 m² keramoqranit\nObyektə çatdırılma",
                'requirements' => "Sertifikatlı məhsul\nÇatdırılma müddəti 30 gün",
                'documents' => "Qiymət təklifi — PDF\nMəhsul sertifikatları — PDF",
                'area' => 1200, 'min' => null, 'max' => null, 'duration' => '30 gün', 'deadline' => '+22 days',
            ],
            [
                'category' => 'muhendislik', 'organizer' => 'Terra Metal', 'city' => 'Gəncə',
                'title' => 'Metal konstruksiyaların montajı',
                'summary' => 'Anbar binası üçün karkasın hazırlanması və montajı.',
                'description' => 'Gəncədə 2400 m² anbar binası üçün metal karkasın hazırlanması və quraşdırılması.',
                'scope' => "Karkasın hazırlanması\nMontaj\nAntikorroziya örtüyü",
                'requirements' => "Metal konstruksiya üzrə təcrübə\nÖz texnikası",
                'documents' => "Kommersiya təklifi — PDF",
                'area' => 2400, 'min' => 95000, 'max' => null, 'duration' => '10 həftə', 'deadline' => '-30 days',
            ],
        ];

        foreach ($rows as $row) {
            if (Tender::query()->where('organizer_name', $row['organizer'])->exists()) {
                continue;
            }

            Tender::query()->create([
                'tender_category_id' => $category($row['category']),
                'organizer_name' => $row['organizer'],
                'city' => $row['city'],
                'title' => ['az' => $row['title']],
                'summary' => ['az' => $row['summary']],
                'description' => ['az' => $row['description']],
                'scope' => ['az' => $row['scope']],
                'requirements' => ['az' => $row['requirements']],
                'submission_documents' => ['az' => $row['documents']],
                'duration' => ['az' => $row['duration']],
                'contact_name' => ['az' => 'Tender şöbəsi'],
                'contact_email' => 'tender@'.str($row['organizer'])->slug().'.example',
                'contact_phone' => '+994 12 000 00 00',
                'area_sqm' => $row['area'],
                'budget_min' => $row['min'],
                'budget_max' => $row['max'],
                'deadline_at' => now()->modify($row['deadline'])->setTime(14, 0),
                'is_published' => true,
                'published_at' => now()->subDays(5),
            ]);
        }
    }
}
