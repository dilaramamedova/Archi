<?php

namespace Database\Seeders;

use App\Enums\SpecialistCategory;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Banner;
use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\PromoBanner;
use App\Models\SpecialistProfile;
use App\Models\SpecialistService;
use App\Models\SpecialistSpecialty;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCategories();
        $seller = $this->ensureSeller();
        $author = $this->ensureAuthor();
        $this->seedProducts($seller);
        $this->seedBlogPosts($author);
        $this->seedBanners();
        $this->seedPromoBanners();
        $this->seedSpecialties();
        $this->seedSpecialists();
    }

    // ─── Specialist specialties ───────────────────────────────

    /**
     * The canonical trade list. Keyed on slug and only filling attributes that
     * are missing, so re-running never duplicates rows nor overwrites names the
     * admin edited in the panel.
     *
     * Every row carries its category. Leaving it off used to hand the column its
     * `master` default, which is how the interior designer ended up filed as a trade
     * and the header's "İnteryer dizaynerlər" card returned an empty directory — see
     * the 2026_09_24 recategorise migration, which repairs installs seeded before this.
     */
    private function seedSpecialties(): void
    {
        $specialties = [
            ['slug' => 'memar', 'category' => SpecialistCategory::Architect, 'name' => ['az' => 'Memar', 'ru' => 'Архитектор', 'en' => 'Architect']],
            ['slug' => 'interyer-dizayner', 'category' => SpecialistCategory::Designer, 'name' => ['az' => 'İnteryer dizayner', 'ru' => 'Дизайнер интерьера', 'en' => 'Interior designer']],
            ['slug' => 'tikinti-sirketi', 'category' => SpecialistCategory::Company, 'name' => ['az' => 'Tikinti şirkəti', 'ru' => 'Строительная компания', 'en' => 'Construction company']],
            ['slug' => 'kafelci', 'category' => SpecialistCategory::Master, 'name' => ['az' => 'Kafelçi', 'ru' => 'Плиточник', 'en' => 'Tiler']],
            ['slug' => 'santexnik', 'category' => SpecialistCategory::Master, 'name' => ['az' => 'Santexnik', 'ru' => 'Сантехник', 'en' => 'Plumber']],
            ['slug' => 'elektrik-ustasi', 'category' => SpecialistCategory::Master, 'name' => ['az' => 'Elektrik ustası', 'ru' => 'Электрик', 'en' => 'Electrician']],
            ['slug' => 'dam-ustasi', 'category' => SpecialistCategory::Master, 'name' => ['az' => 'Dam ustası', 'ru' => 'Кровельщик', 'en' => 'Roofer']],
            ['slug' => 'boyaci', 'category' => SpecialistCategory::Master, 'name' => ['az' => 'Boyaçı', 'ru' => 'Маляр', 'en' => 'Painter']],
            ['slug' => 'suvaqci', 'category' => SpecialistCategory::Master, 'name' => ['az' => 'Suvaqçı', 'ru' => 'Штукатур', 'en' => 'Plasterer']],
            ['slug' => 'gips-karton-ustasi', 'category' => SpecialistCategory::Master, 'name' => ['az' => 'Gips-karton ustası', 'ru' => 'Мастер по гипсокартону', 'en' => 'Drywall installer']],
            ['slug' => 'laminat-parket-ustasi', 'category' => SpecialistCategory::Master, 'name' => ['az' => 'Laminat/parket ustası', 'ru' => 'Укладчик ламината и паркета', 'en' => 'Laminate & parquet fitter']],
            ['slug' => 'pencere-qapi-ustasi', 'category' => SpecialistCategory::Master, 'name' => ['az' => 'Pəncərə & qapı ustası', 'ru' => 'Мастер по окнам и дверям', 'en' => 'Window & door fitter']],
            ['slug' => 'mebel-ustasi', 'category' => SpecialistCategory::Master, 'name' => ['az' => 'Mebel ustası', 'ru' => 'Мебельщик', 'en' => 'Carpenter']],
            ['slug' => 'qaynaqci', 'category' => SpecialistCategory::Master, 'name' => ['az' => 'Qaynaqçı', 'ru' => 'Сварщик', 'en' => 'Welder']],
            ['slug' => 'kondisioner-ventilyasiya-ustasi', 'category' => SpecialistCategory::Master, 'name' => ['az' => 'Kondisioner & ventilyasiya ustası', 'ru' => 'Мастер по кондиционерам и вентиляции', 'en' => 'HVAC technician']],
            ['slug' => 'hovuz-ustasi', 'category' => SpecialistCategory::Master, 'name' => ['az' => 'Hovuz ustası', 'ru' => 'Мастер по бассейнам', 'en' => 'Pool installer']],
            ['slug' => 'landsaft-ustasi', 'category' => SpecialistCategory::Master, 'name' => ['az' => 'Landşaft ustası', 'ru' => 'Ландшафтный мастер', 'en' => 'Landscaper']],
        ];

        foreach ($specialties as $index => $data) {
            $specialty = SpecialistSpecialty::firstOrNew(['slug' => $data['slug']]);

            if (! $specialty->exists) {
                $specialty->fill([
                    'name' => $data['name'],
                    'category' => $data['category'],
                    'is_active' => true,
                    'sort_order' => $index,
                ]);
                $specialty->save();

                continue;
            }

            // Existing row: only fill locales the admin has not provided yet.
            foreach ($data['name'] as $locale => $value) {
                if ($specialty->getTranslation('name', $locale, false) === '') {
                    $specialty->setTranslation('name', $locale, $value);
                }
            }

            $specialty->save();
        }
    }

    // ─── Categories ───────────────────────────────────────────

    private function seedCategories(): void
    {
        $categories = [
            [
                'name' => ['az' => 'Kafel', 'ru' => 'Плитка', 'en' => 'Tiles'],
                'slug' => 'kafel',
                'image' => 'assets/category-tile-showroom.png',
                'sort_order' => 1,
                'is_active' => true,
                'show_on_home' => true,
            ],
            [
                'name' => ['az' => 'Dam örtükləri', 'ru' => 'Кровля', 'en' => 'Roofing'],
                'slug' => 'dam-ortukleri',
                'image' => 'assets/category-roofing.png',
                'sort_order' => 2,
                'is_active' => true,
                'show_on_home' => true,
            ],
            [
                'name' => ['az' => 'Laminat', 'ru' => 'Ламинат', 'en' => 'Laminate'],
                'slug' => 'laminat',
                'image' => 'assets/category-laminate-flooring.png',
                'sort_order' => 3,
                'is_active' => true,
                'show_on_home' => true,
            ],
            [
                'name' => ['az' => 'Elektrik', 'ru' => 'Электрика', 'en' => 'Electrical'],
                'slug' => 'elektrik',
                'image' => 'assets/category-electrical.png',
                'sort_order' => 4,
                'is_active' => true,
                'show_on_home' => true,
            ],
            [
                'name' => ['az' => 'Santexnika', 'ru' => 'Сантехника', 'en' => 'Plumbing'],
                'slug' => 'santexnika',
                'image' => 'assets/category-plumbing.png',
                'sort_order' => 5,
                'is_active' => true,
                'show_on_home' => true,
            ],
            [
                'name' => ['az' => 'Kərpic', 'ru' => 'Кирпич', 'en' => 'Brick'],
                'slug' => 'kerpic',
                'image' => 'assets/category-brick-and-block.png',
                'sort_order' => 6,
                'is_active' => true,
                'show_on_home' => true,
            ],
            [
                'name' => ['az' => 'Sement', 'ru' => 'Цемент', 'en' => 'Cement'],
                'slug' => 'sement',
                'image' => 'assets/category-cement-bags.png',
                'sort_order' => 7,
                'is_active' => true,
                'show_on_home' => true,
            ],
        ];

        foreach ($categories as $data) {
            Category::firstOrCreate(['slug' => $data['slug']], $data);
        }
    }

    // ─── Demo users ───────────────────────────────────────────

    private function ensureSeller(): User
    {
        return User::firstOrCreate(
            ['email' => 'seller@archi.test'],
            [
                'name' => 'ARCHI Seller',
                'first_name' => 'Elvin',
                'last_name' => 'Mammadov',
                'phone' => '+994501234561',
                'password' => 'password',
                'role' => UserRole::Seller,
                'status' => UserStatus::Active,
            ],
        );
    }

    private function ensureAuthor(): User
    {
        return User::firstOrCreate(
            ['email' => 'author@archi.test'],
            [
                'name' => 'ARCHI Author',
                'first_name' => 'Nigar',
                'last_name' => 'Huseynova',
                'phone' => '+994502345678',
                'password' => 'password',
                'role' => UserRole::Buyer,
                'status' => UserStatus::Active,
            ],
        );
    }

    // ─── Products ─────────────────────────────────────────────

    private function seedProducts(User $seller): void
    {
        $products = [
            // ── Kafel (Tiles) ──
            [
                'name' => ['az' => 'Mərmər kafel 60x60', 'ru' => 'Мраморная плитка 60x60', 'en' => 'Marble tile 60x60'],
                'description' => ['az' => 'Yüksək keyfiyyətli İtalyan mərmər kafeli. Döşəmə və divar üçün uyğundur.', 'ru' => 'Высококачественная итальянская мраморная плитка. Подходит для пола и стен.', 'en' => 'High-quality Italian marble tile. Suitable for floor and walls.'],
                'slug' => 'mermer-kafel-60x60',
                'sku' => 'TILE-001',
                'brand' => 'Kerama Marazzi',
                'price' => 45.00,
                'old_price' => 59.90,
                'discount_percent' => 25,
                'is_sale' => true,
                'is_featured' => true,
                'category' => 'kafel',
                'stock' => 500,
                'unit' => 'm2',
                'image' => 'assets/product-marble-tile.png',
            ],
            [
                'name' => ['az' => 'Dekorativ divar kafeli', 'ru' => 'Декоративная настенная плитка', 'en' => 'Decorative wall tile'],
                'description' => ['az' => 'Müasir dizaynlı dekorativ divar kafeli. Mətbəx və hamam otağı üçün ideal.', 'ru' => 'Декоративная настенная плитка современного дизайна. Идеально для кухни и ванной.', 'en' => 'Decorative wall tile with modern design. Ideal for kitchen and bathroom.'],
                'slug' => 'dekorativ-divar-kafeli',
                'sku' => 'TILE-002',
                'brand' => 'Italon',
                'price' => 38.50,
                'is_featured' => true,
                'category' => 'kafel',
                'stock' => 300,
                'unit' => 'm2',
                'image' => 'assets/product-marble-tile-square.jpg',
            ],
            [
                'name' => ['az' => 'Porselan kafel 30x30', 'ru' => 'Керамогранит 30x30', 'en' => 'Porcelain tile 30x30'],
                'description' => ['az' => 'Davamlı porselan kafel, xarici və daxili istifadə üçün.', 'ru' => 'Прочный керамогранит для наружного и внутреннего использования.', 'en' => 'Durable porcelain tile for outdoor and indoor use.'],
                'slug' => 'porselan-kafel-30x30',
                'sku' => 'TILE-003',
                'brand' => 'Atlas Concorde',
                'price' => 28.00,
                'category' => 'kafel',
                'stock' => 800,
                'unit' => 'm2',
                'image' => 'assets/product-marble-tile-dark.jpg',
            ],

            // ── Dam örtükləri (Roofing) ──
            [
                'name' => ['az' => 'Metal dam örtüyü', 'ru' => 'Металлочерепица', 'en' => 'Metal roofing tile'],
                'description' => ['az' => 'Paslanmaz metal dam örtüyü, 25 il zəmanətlə.', 'ru' => 'Металлочерепица с защитой от коррозии, гарантия 25 лет.', 'en' => 'Corrosion-resistant metal roofing tile with 25-year warranty.'],
                'slug' => 'metal-dam-ortuyu',
                'sku' => 'ROOF-001',
                'brand' => 'Grand Line',
                'price' => 18.50,
                'old_price' => 22.00,
                'discount_percent' => 16,
                'is_sale' => true,
                'category' => 'dam-ortukleri',
                'stock' => 1200,
                'unit' => 'm2',
                'image' => 'assets/product-roof-tiles.jpg',
            ],
            [
                'name' => ['az' => 'Bitum dam örtüyü', 'ru' => 'Битумная черепица', 'en' => 'Bitumen shingle'],
                'description' => ['az' => 'Yüngül və davamlı bitum dam örtüyü, müxtəlif rənglərdə.', 'ru' => 'Легкая и прочная битумная черепица, различные цвета.', 'en' => 'Lightweight and durable bitumen shingle, various colors.'],
                'slug' => 'bitum-dam-ortuyu',
                'sku' => 'ROOF-002',
                'brand' => 'Tegola',
                'price' => 14.00,
                'category' => 'dam-ortukleri',
                'stock' => 600,
                'unit' => 'm2',
                'image' => 'assets/category-roofing.png',
            ],

            // ── Laminat (Laminate) ──
            [
                'name' => ['az' => 'Laminat döşəmə 8mm Palıd', 'ru' => 'Ламинат 8мм Дуб', 'en' => 'Laminate flooring 8mm Oak'],
                'description' => ['az' => 'AC4 sinif laminat döşəmə, palıd rəngi. Yüksək yükə davamlı.', 'ru' => 'Ламинат класса AC4, цвет дуб. Высокая износостойкость.', 'en' => 'AC4 class laminate flooring, oak color. High wear resistance.'],
                'slug' => 'laminat-8mm-palid',
                'sku' => 'LAM-001',
                'brand' => 'Kronospan',
                'price' => 16.90,
                'old_price' => 21.00,
                'discount_percent' => 20,
                'is_sale' => true,
                'is_featured' => true,
                'category' => 'laminat',
                'stock' => 450,
                'unit' => 'm2',
                'image' => 'assets/category-laminate-flooring.png',
            ],
            [
                'name' => ['az' => 'Laminat döşəmə 12mm Qoz', 'ru' => 'Ламинат 12мм Орех', 'en' => 'Laminate flooring 12mm Walnut'],
                'description' => ['az' => 'Premium 12mm qalınlıqda laminat, qoz ağacı teksturası.', 'ru' => 'Премиальный ламинат толщиной 12мм, текстура ореха.', 'en' => 'Premium 12mm thick laminate, walnut texture.'],
                'slug' => 'laminat-12mm-qoz',
                'sku' => 'LAM-002',
                'brand' => 'Quick-Step',
                'price' => 29.90,
                'is_featured' => true,
                'category' => 'laminat',
                'stock' => 200,
                'unit' => 'm2',
                'image' => 'assets/category-laminate-flooring.png',
            ],

            // ── Elektrik (Electrical) ──
            [
                'name' => ['az' => 'LED panel işıq 60x60', 'ru' => 'Светодиодная панель 60x60', 'en' => 'LED panel light 60x60'],
                'description' => ['az' => '40W LED panel işıq, ofis və ev üçün. Enerji qənaətli.', 'ru' => 'Светодиодная панель 40W для офиса и дома. Энергосберегающая.', 'en' => '40W LED panel light for office and home. Energy efficient.'],
                'slug' => 'led-panel-isiq-60x60',
                'sku' => 'ELEC-001',
                'brand' => 'Philips',
                'price' => 35.00,
                'category' => 'elektrik',
                'stock' => 150,
                'unit' => 'piece',
                'image' => 'assets/category-electrical.png',
            ],

            // ── Santexnika (Plumbing) ──
            [
                'name' => ['az' => 'Kran qarışdırıcı krom', 'ru' => 'Смеситель хром', 'en' => 'Chrome mixer faucet'],
                'description' => ['az' => 'Mətbəx üçün xromlanmış kran qarışdırıcı. Keramik kartric ilə.', 'ru' => 'Хромированный смеситель для кухни. С керамическим картриджем.', 'en' => 'Chrome-plated kitchen mixer faucet. With ceramic cartridge.'],
                'slug' => 'kran-qarisdiric-krom',
                'sku' => 'PLMB-001',
                'brand' => 'Grohe',
                'price' => 89.00,
                'old_price' => 119.00,
                'discount_percent' => 25,
                'is_sale' => true,
                'is_featured' => true,
                'category' => 'santexnika',
                'stock' => 80,
                'unit' => 'piece',
                'image' => 'assets/category-plumbing.png',
            ],

            // ── Kərpic (Brick) ──
            [
                'name' => ['az' => 'Qırmızı tikinti kərpici', 'ru' => 'Красный строительный кирпич', 'en' => 'Red building brick'],
                'description' => ['az' => 'Standart ölçülü qırmızı tikinti kərpici M150. Yüksək möhkəmlik.', 'ru' => 'Красный строительный кирпич стандартного размера М150. Высокая прочность.', 'en' => 'Standard size red building brick M150. High strength.'],
                'slug' => 'qirmizi-tikinti-kerpici',
                'sku' => 'BRCK-001',
                'brand' => 'BakuBrick',
                'price' => 0.45,
                'category' => 'kerpic',
                'stock' => 50000,
                'unit' => 'piece',
                'min_order' => 100,
                'image' => 'assets/category-brick-and-block.png',
            ],

            // ── Sement (Cement) ──
            [
                'name' => ['az' => 'Portland sementi M400 50kq', 'ru' => 'Портландцемент М400 50кг', 'en' => 'Portland cement M400 50kg'],
                'description' => ['az' => 'Yüksək keyfiyyətli Portland sementi, 50 kq kisə.', 'ru' => 'Высококачественный портландцемент, мешок 50 кг.', 'en' => 'High-quality Portland cement, 50 kg bag.'],
                'slug' => 'portland-sementi-m400-50kq',
                'sku' => 'CMNT-001',
                'brand' => 'Norm Cement',
                'price' => 7.50,
                'category' => 'sement',
                'stock' => 3000,
                'unit' => 'piece',
                'min_order' => 10,
                'image' => 'assets/category-cement-bags.png',
            ],
            [
                'name' => ['az' => 'Əlavə güclü sement M500', 'ru' => 'Цемент повышенной прочности М500', 'en' => 'Extra strong cement M500'],
                'description' => ['az' => 'M500 markalı əlavə güclü sement, kritik konstruksiyalar üçün.', 'ru' => 'Цемент марки М500 повышенной прочности для ответственных конструкций.', 'en' => 'M500 grade extra strong cement for critical constructions.'],
                'slug' => 'elave-guclu-sement-m500',
                'sku' => 'CMNT-002',
                'brand' => 'Holcim',
                'price' => 9.80,
                'old_price' => 11.50,
                'discount_percent' => 15,
                'is_sale' => true,
                'category' => 'sement',
                'stock' => 1500,
                'unit' => 'piece',
                'min_order' => 10,
                'image' => 'assets/category-cement-bags.png',
            ],
        ];

        foreach ($products as $data) {
            $categorySlug = $data['category'];
            $imagePath = $data['image'];
            $brandName = $data['brand'] ?? null;
            unset($data['category'], $data['image'], $data['brand']);

            $category = Category::where('slug', $categorySlug)->first();
            $brand = $brandName ? $this->ensureBrand($brandName) : null;

            $product = Product::firstOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, [
                    'user_id' => $seller->id,
                    'category_id' => $category?->id,
                    'brand_id' => $brand?->id,
                    'is_visible' => true,
                    'is_approved' => true,
                    // The homepage grids need BOTH the flag and the curation bit; without
                    // this the "featured"/"sale" rows render empty on a fresh seed.
                    'show_on_homepage' => ($data['is_featured'] ?? false) || ($data['is_sale'] ?? false),
                    'condition' => 'new',
                    'views_count' => rand(50, 800),
                    'free_delivery' => (bool) rand(0, 1),
                    'return_14_days' => true,
                ]),
            );

            if ($product->wasRecentlyCreated) {
                // Copy the demo asset onto the public storage disk: product_images.path
                // must live where Filament's FileUpload (disk "public") can hydrate it —
                // raw public/assets/* paths break the admin edit form.
                $source = public_path($imagePath);
                $storedPath = 'products/seed-'.$product->id.'-'.basename($imagePath);

                if (is_file($source)) {
                    Storage::disk('public')->put($storedPath, file_get_contents($source));
                } else {
                    $storedPath = $imagePath; // asset missing locally — keep the legacy path
                }

                ProductImage::create([
                    'product_id' => $product->id,
                    'path' => $storedPath,
                    'alt_text' => $data['name'],
                    'is_main' => true,
                    'sort_order' => 1,
                ]);
            }
        }
    }

    private function ensureBrand(string $name): Brand
    {
        return Brand::firstOrCreate(
            ['slug' => Str::slug($name)],
            [
                'name' => ['az' => $name, 'ru' => $name, 'en' => $name],
                'is_active' => true,
                'show_in_filters' => true,
            ],
        );
    }

    // ─── Blog posts ───────────────────────────────────────────

    private function seedBlogPosts(User $author): void
    {
        $posts = [
            [
                'title' => ['az' => 'Evdə təmir: haradan başlamalı?', 'ru' => 'Ремонт дома: с чего начать?', 'en' => 'Home renovation: where to start?'],
                'slug' => 'evde-temir-haradan-baslamali',
                'excerpt' => ['az' => 'Təmirə başlamaq üçün addım-addım bələdçi.', 'ru' => 'Пошаговое руководство для начала ремонта.', 'en' => 'Step-by-step guide for starting a renovation.'],
                'body' => [
                    'az' => '<h2>Təmirə hazırlıq</h2><p>Evdə təmir etmək qərarına gəldinizsə, ilk addım büdcə planlamasıdır. Təmir materiallarını seçərkən keyfiyyətə diqqət yetirin. ARCHI platformasında siz ən yaxşı tikinti materiallarını tapa bilərsiniz.</p><p>İkinci addım, peşəkar ustalar tapmaqdır. Platformamızda təcrübəli mütəxəssisləri asanlıqla tapa bilərsiniz.</p>',
                    'ru' => '<h2>Подготовка к ремонту</h2><p>Если вы решили сделать ремонт дома, первый шаг — планирование бюджета. При выборе материалов обратите внимание на качество. На платформе ARCHI вы найдете лучшие строительные материалы.</p><p>Второй шаг — найти профессиональных мастеров. На нашей платформе вы легко найдете опытных специалистов.</p>',
                    'en' => '<h2>Preparing for renovation</h2><p>If you have decided to renovate your home, the first step is budget planning. Pay attention to quality when choosing materials. On the ARCHI platform you can find the best construction materials.</p><p>The second step is finding professional craftsmen. On our platform you can easily find experienced specialists.</p>',
                ],
                'cover_image' => 'assets/hero-renovation-before-after.jpg',
                'published_at' => now()->subDays(3),
            ],
            [
                'title' => ['az' => 'Kafel seçimində 5 vacib məqam', 'ru' => '5 важных моментов при выборе плитки', 'en' => '5 important things when choosing tiles'],
                'slug' => 'kafel-seciminde-5-vacib-meqam',
                'excerpt' => ['az' => 'Doğru kafel seçimi üçün bilməli olduğunuz məqamlar.', 'ru' => 'Что нужно знать для правильного выбора плитки.', 'en' => 'What you need to know for the right tile choice.'],
                'body' => [
                    'az' => '<h2>Kafel alarkən nələrə diqqət etməli?</h2><p>1. <strong>Ölçü</strong> — Otağın ölçüsünə uyğun kafel seçin. Kiçik otaqlar üçün böyük kafellər otağı daha geniş göstərə bilər.</p><p>2. <strong>Material</strong> — Porselan kafellər daha davamlıdır, keramika isə daha sərfəlidir.</p><p>3. <strong>Sürüşmə müqaviməti</strong> — Hamam otağı üçün sürüşməyə davamlı kafel seçin.</p><p>4. <strong>Rəng harmoniyası</strong> — İnteryerinizə uyğun rənglər seçin.</p><p>5. <strong>Keyfiyyət sinfi</strong> — PEI dəyərinə diqqət yetirin.</p>',
                    'ru' => '<h2>На что обращать внимание при покупке плитки?</h2><p>1. <strong>Размер</strong> — выбирайте плитку в соответствии с размером помещения. Крупная плитка визуально увеличит маленькую комнату.</p><p>2. <strong>Материал</strong> — керамогранит более прочный, керамика — более доступная.</p><p>3. <strong>Антискользящее покрытие</strong> — для ванной выбирайте нескользящую плитку.</p><p>4. <strong>Цветовая гармония</strong> — подбирайте цвета под интерьер.</p><p>5. <strong>Класс качества</strong> — обратите внимание на показатель PEI.</p>',
                    'en' => '<h2>What to consider when buying tiles?</h2><p>1. <strong>Size</strong> — Choose tiles according to room size. Large tiles can make small rooms appear bigger.</p><p>2. <strong>Material</strong> — Porcelain tiles are more durable, ceramic is more affordable.</p><p>3. <strong>Slip resistance</strong> — Choose non-slip tiles for bathrooms.</p><p>4. <strong>Color harmony</strong> — Select colors matching your interior.</p><p>5. <strong>Quality class</strong> — Pay attention to the PEI rating.</p>',
                ],
                'cover_image' => 'assets/blog-cover-default.png',
                'published_at' => now()->subDays(7),
            ],
            [
                'title' => ['az' => 'Müasir villa dizaynı trendləri 2024', 'ru' => 'Тренды дизайна современных вилл 2024', 'en' => 'Modern villa design trends 2024'],
                'slug' => 'muasir-villa-dizayni-trendleri-2024',
                'excerpt' => ['az' => 'Bu ilin ən populyar villa dizayn yanaşmaları.', 'ru' => 'Самые популярные подходы к дизайну вилл этого года.', 'en' => 'This year\'s most popular villa design approaches.'],
                'body' => [
                    'az' => '<h2>2024-cü ilin villa trendləri</h2><p>Bu il villa dizaynında minimalizm və təbii materiallar öndə gedir. Daş, ağac və metal kombinasiyaları müasir villaların əsas elementi olub.</p><p>Enerji effektivliyi də mühüm trend olaraq qalır. Günəş panelləri və istilik izolyasiya materialları populyarlıq qazanır.</p>',
                    'ru' => '<h2>Тренды вилл 2024 года</h2><p>В этом году в дизайне вилл лидирует минимализм и натуральные материалы. Комбинации камня, дерева и металла стали основным элементом современных вилл.</p><p>Энергоэффективность также остается важным трендом. Солнечные панели и теплоизоляционные материалы набирают популярность.</p>',
                    'en' => '<h2>Villa trends of 2024</h2><p>This year, minimalism and natural materials lead in villa design. Combinations of stone, wood, and metal have become the main element of modern villas.</p><p>Energy efficiency also remains an important trend. Solar panels and thermal insulation materials are gaining popularity.</p>',
                ],
                'cover_image' => 'assets/blog-cover-modern-villa.jpg',
                'published_at' => now()->subDays(14),
            ],
            [
                'title' => ['az' => 'Dam örtüyü necə seçilir?', 'ru' => 'Как выбрать кровлю?', 'en' => 'How to choose roofing?'],
                'slug' => 'dam-ortuyu-nece-secilir',
                'excerpt' => ['az' => 'Eviniz üçün ən uyğun dam örtüyünü seçmək üçün məsləhətlər.', 'ru' => 'Советы по выбору лучшей кровли для вашего дома.', 'en' => 'Tips for choosing the best roofing for your home.'],
                'body' => [
                    'az' => '<h2>Dam örtüyü növləri</h2><p>Dam örtüyü seçərkən iqliminizi, büdcənizi və evin arxitekturasını nəzərə almalısınız.</p><p><strong>Metal dam örtüyü</strong> — ən populyar seçimlərdən biridir. Davamlı, yüngül və quraşdırılması asandır.</p><p><strong>Bitum dam örtüyü</strong> — estetik görünüşü və müxtəlif rəng seçimləri ilə seçilir.</p><p><strong>Keramik dam örtüyü</strong> — ən uzunömürlü seçimdir, lakin daha ağır və bahalıdır.</p>',
                    'ru' => '<h2>Виды кровли</h2><p>При выборе кровли следует учитывать климат, бюджет и архитектуру дома.</p><p><strong>Металлочерепица</strong> — один из самых популярных вариантов. Прочная, легкая и простая в монтаже.</p><p><strong>Битумная черепица</strong> — отличается эстетичным внешним видом и разнообразием цветов.</p><p><strong>Керамическая черепица</strong> — самый долговечный вариант, но тяжелее и дороже.</p>',
                    'en' => '<h2>Types of roofing</h2><p>When choosing roofing, consider your climate, budget, and house architecture.</p><p><strong>Metal roofing</strong> — one of the most popular choices. Durable, lightweight, and easy to install.</p><p><strong>Bitumen shingles</strong> — distinguished by aesthetic appearance and variety of colors.</p><p><strong>Ceramic roofing</strong> — the most long-lasting option, but heavier and more expensive.</p>',
                ],
                'cover_image' => 'assets/blog-cover-renovation.jpg',
                'published_at' => now()->subDays(21),
            ],
        ];

        foreach ($posts as $data) {
            BlogPost::firstOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, [
                    'author_id' => $author->id,
                    'is_published' => true,
                    'is_featured' => true,
                    'show_on_home' => true,
                    'show_in_header' => true,
                    'views_count' => rand(100, 2000),
                ]),
            );
        }
    }

    // ─── Banners ──────────────────────────────────────────────

    private function seedBanners(): void
    {
        $banners = [
            [
                'position' => 'hero_main',
                'title' => ['az' => 'Tikinti materialları bir platformada', 'ru' => 'Строительные материалы на одной платформе', 'en' => 'Construction materials on one platform'],
                'subtitle' => ['az' => 'Ən yaxşı qiymətlərlə keyfiyyətli materiallar', 'ru' => 'Качественные материалы по лучшим ценам', 'en' => 'Quality materials at the best prices'],
                'button_text' => ['az' => 'Kataloqa bax', 'ru' => 'Смотреть каталог', 'en' => 'View catalog'],
                'button_url' => '/catalog',
                'image' => 'assets/hero-modern-house.jpg',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'position' => 'hero_promo',
                'title' => ['az' => 'Yay endirimləri başladı!', 'ru' => 'Летние скидки начались!', 'en' => 'Summer sale has started!'],
                'subtitle' => ['az' => 'Seçilmiş məhsullarda 60%-dək endirim', 'ru' => 'Скидки до 60% на избранные товары', 'en' => 'Up to 60% off on selected products'],
                'button_text' => ['az' => 'Endirimlərə bax', 'ru' => 'Смотреть скидки', 'en' => 'View discounts'],
                'button_url' => '/catalog?sale=1',
                'image' => 'assets/hero-customer-in-tile-store.jpg',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'position' => 'hero_promo',
                'title' => ['az' => 'Peşəkar ustalar platforması', 'ru' => 'Платформа профессиональных мастеров', 'en' => 'Professional masters platform'],
                'subtitle' => ['az' => 'Təcrübəli mütəxəssisləri tapın və işə götürün', 'ru' => 'Найдите и наймите опытных специалистов', 'en' => 'Find and hire experienced specialists'],
                'button_text' => ['az' => 'Ustaları tap', 'ru' => 'Найти мастера', 'en' => 'Find masters'],
                'button_url' => '/specialists',
                'image' => 'assets/hero-bricklayer.jpg',
                'sort_order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($banners as $data) {
            // Use title + position to identify uniqueness
            Banner::firstOrCreate(
                ['sort_order' => $data['sort_order'], 'position' => $data['position']],
                $data,
            );
        }
    }

    // ─── Promo banners ────────────────────────────────────────

    private function seedPromoBanners(): void
    {
        $promos = [
            [
                'badge_text' => ['az' => 'Endirim', 'ru' => 'Скидка', 'en' => 'Discount'],
                'title' => ['az' => '60%-dək endirim', 'ru' => 'Скидки до 60%', 'en' => 'Up to 60% off'],
                'description' => ['az' => 'Seçilmiş tikinti materiallarında böyük endirimlər əldə edin.', 'ru' => 'Получите большие скидки на избранные строительные материалы.', 'en' => 'Get big discounts on selected construction materials.'],
                'code' => null,
                'button_text' => ['az' => 'Indi al', 'ru' => 'Купить сейчас', 'en' => 'Buy now'],
                'button_url' => '/catalog?sale=1',
                'background_color' => '#1E3A5F',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'badge_text' => ['az' => 'Xüsusi təklif', 'ru' => 'Спецпредложение', 'en' => 'Special offer'],
                'title' => ['az' => '15% endirim ilk sifarişə', 'ru' => '15% скидка на первый заказ', 'en' => '15% off first order'],
                'description' => ['az' => 'Seçilmiş məhsullarda xüsusi təkliflərdən yararlanın.', 'ru' => 'Воспользуйтесь специальными предложениями на избранные товары.', 'en' => 'Take advantage of special offers on selected products.'],
                'code' => null,
                'button_text' => ['az' => 'Məhsullara bax', 'ru' => 'Смотреть товары', 'en' => 'View products'],
                'button_url' => '/catalog',
                'background_color' => '#2D6A4F',
                'sort_order' => 2,
                'is_active' => true,
            ],
        ];

        // Keyed on sort_order, not on `code`: promo codes were retired and are now null,
        // which would collapse every banner onto a single row.
        foreach ($promos as $data) {
            PromoBanner::updateOrCreate(
                ['sort_order' => $data['sort_order']],
                $data,
            );
        }
    }

    // ─── Specialist profiles ──────────────────────────────────

    private function seedSpecialists(): void
    {
        $specialists = [
            [
                'first_name' => 'Ramil',
                'last_name' => 'Əliyev',
                'email' => 'ramil.aliyev@archi.test',
                'phone' => '+994503456789',
                'craft' => 'Kafelçi',
                'specialty' => 'kafelci',
                'experience' => 12,
                'city' => 'Bakı',
                'about' => 'Təcrübəli kafelçi. 12 ildən artıq təcrübə. Hamam otağı, mətbəx və döşəmə kafel işləri.',
                'skills' => ['Kafel döşəmə', 'Divar kafelləmə', 'Mozaika', 'Hidroizolyasiya'],
                'avatar' => 'assets/bricklayer-at-work.jpg',
                'homepage' => true,
            ],
            [
                'first_name' => 'Tural',
                'last_name' => 'Həsənov',
                'email' => 'tural.hasanov@archi.test',
                'phone' => '+994504567890',
                'craft' => 'Elektrik ustası',
                'specialty' => 'elektrik-ustasi',
                'experience' => 8,
                'city' => 'Bakı',
                'about' => 'Peşəkar elektrik ustası. Elektrik quraşdırma, təmir və texniki xidmət.',
                'skills' => ['Elektrik quraşdırma', 'Kabel çəkilişi', 'İşıqlandırma', 'Siqnalizasiya'],
                'avatar' => 'assets/avatar-placeholder.png',
                'homepage' => true,
            ],
            [
                'first_name' => 'Orxan',
                'last_name' => 'Məmmədov',
                'email' => 'orxan.mammadov@archi.test',
                'phone' => '+994505678901',
                'craft' => 'Dam ustası',
                'specialty' => 'dam-ustasi',
                'experience' => 15,
                'city' => 'Sumqayıt',
                'about' => 'Dam örtüyü quraşdırılması və təmiri üzrə mütəxəssis. Metal, bitum və keramik dam örtükləri.',
                'skills' => ['Metal dam', 'Bitum dam', 'Hidroizolyasiya', 'Yağış suyu sistemi'],
                'avatar' => 'assets/avatar-placeholder.png',
                'homepage' => true,
            ],
            [
                'first_name' => 'Vüsal',
                'last_name' => 'Quliyev',
                'email' => 'vusal.quliyev@archi.test',
                'phone' => '+994506789012',
                'craft' => 'Santexnik',
                'specialty' => 'santexnik',
                'experience' => 10,
                'city' => 'Bakı',
                'about' => 'Santexnika quraşdırılması və təmiri. Su və kanalizasiya sistemləri, isitmə sistemləri.',
                'skills' => ['Su təchizatı', 'Kanalizasiya', 'İsitmə sistemi', 'Kran təmiri'],
                'avatar' => 'assets/avatar-placeholder.png',
                'homepage' => true,
            ],

            // ─── Memarlar (architect) ───
            // The directory renders one block per category group, and until these
            // existed two of the three blocks were headings over an empty state: nobody
            // could register as an architect or a designer either, because the sign-up
            // dropdown is built from the same specialty table. avatar is null on
            // purpose — the card draws initials in a yellow ring, which reads better
            // than the same grey placeholder photo repeated nine times.
            [
                'first_name' => 'Nigar',
                'last_name' => 'Əliyeva',
                'email' => 'nigar.aliyeva@archi.test',
                'phone' => '+994507890123',
                'craft' => 'Memar',
                'specialty' => 'memar',
                'category' => SpecialistCategory::Architect,
                'experience' => 14,
                'city' => 'Bakı',
                'about' => 'Yaşayış və kommersiya obyektləri üzrə memar. Eskiz layihədən icra sənədlərinə qədər tam dövrü aparıram.',
                'skills' => ['Eskiz layihə', 'İş layihəsi', 'Fasad həlli', '3D vizuallaşdırma'],
                'avatar' => null,
                'verified' => true,
            ],
            [
                'first_name' => 'Elçin',
                'last_name' => 'Rzayev',
                'email' => 'elchin.rzayev@archi.test',
                'phone' => '+994508901234',
                'craft' => 'Memar',
                'specialty' => 'memar',
                'category' => SpecialistCategory::Architect,
                'experience' => 9,
                'city' => 'Bakı',
                'about' => 'Fərdi evlər və həyət evləri üzrə memarlıq layihələri. Planlaşdırma, konstruktiv həllər və icazə sənədləri.',
                'skills' => ['Fərdi ev layihəsi', 'Planlaşdırma', 'Konstruktiv hesabat', 'İcazə sənədləri'],
                'avatar' => null,
                'featured' => false,
            ],
            [
                'first_name' => 'Ayşən',
                'last_name' => 'Kərimova',
                'email' => 'aysen.kerimova@archi.test',
                'phone' => '+994509012345',
                'craft' => 'Memar',
                'specialty' => 'memar',
                'category' => SpecialistCategory::Architect,
                'experience' => 6,
                'city' => 'Gəncə',
                'about' => 'Ofis və ictimai məkanların memarlıq həlləri. Məkan planlaşdırması və işıq layihəsi.',
                'skills' => ['Ofis layihəsi', 'Məkan planlaşdırması', 'İşıq layihəsi'],
                'avatar' => null,
                'featured' => false,
            ],

            // ─── İnteryer dizaynerlər (designer) ───
            [
                'first_name' => 'Leyla',
                'last_name' => 'Hüseynova',
                'email' => 'leyla.huseynova@archi.test',
                'phone' => '+994551234567',
                'craft' => 'İnteryer dizayner',
                'specialty' => 'interyer-dizayner',
                'category' => SpecialistCategory::Designer,
                'experience' => 11,
                'city' => 'Bakı',
                'about' => 'Mənzil və villa interyerləri. Konsepsiya, material seçimi və müəllif nəzarəti.',
                'skills' => ['İnteryer konsepsiyası', 'Material seçimi', '3D vizuallaşdırma', 'Müəllif nəzarəti'],
                'avatar' => null,
                'verified' => true,
            ],
            [
                'first_name' => 'Kamran',
                'last_name' => 'Səfərov',
                'email' => 'kamran.safarov@archi.test',
                'phone' => '+994552345678',
                'craft' => 'İnteryer dizayner',
                'specialty' => 'interyer-dizayner',
                'category' => SpecialistCategory::Designer,
                'experience' => 7,
                'city' => 'Bakı',
                'about' => 'Kafe, restoran və mağaza interyerləri üzrə dizayner. Brend kimliyinə uyğun məkan həlləri.',
                'skills' => ['Kommersiya interyeri', 'Mebel layihəsi', 'Brend məkanı'],
                'avatar' => null,
                'featured' => false,
            ],
            [
                'first_name' => 'Günel',
                'last_name' => 'Nəbiyeva',
                'email' => 'gunel.nabiyeva@archi.test',
                'phone' => '+994553456789',
                'craft' => 'İnteryer dizayner',
                'specialty' => 'interyer-dizayner',
                'category' => SpecialistCategory::Designer,
                'experience' => 5,
                'city' => 'Sumqayıt',
                'about' => 'Kiçik mənzillər üzrə praktik dizayn. Az büdcə ilə funksional planlaşdırma və rəng həlli.',
                'skills' => ['Kiçik mənzil', 'Rəng həlli', 'Anbar-qarderob planı'],
                'avatar' => null,
                'featured' => false,
            ],

            // ─── Tikinti şirkətləri (company) ───
            // A company registers as one account: first_name holds the trade name and
            // last_name the legal form, so `users.name` reads "Aztikinti Group MMC".
            [
                'first_name' => 'Aztikinti Group',
                'last_name' => 'MMC',
                'email' => 'info@aztikinti.archi.test',
                'phone' => '+994124567890',
                'craft' => 'Tikinti şirkəti',
                'specialty' => 'tikinti-sirketi',
                'category' => SpecialistCategory::Company,
                'experience' => 18,
                'city' => 'Bakı',
                'about' => 'Tam dövrlü tikinti şirkəti. Sıfırdan təhvilə qədər yaşayış və kommersiya obyektləri.',
                'skills' => ['Açar təslimi', 'Monolit tikinti', 'Fasad işləri', 'Layihə idarəetməsi'],
                'avatar' => null,
                'verified' => true,
            ],
            [
                'first_name' => 'Inşa Servis',
                'last_name' => 'MMC',
                'email' => 'info@insaservis.archi.test',
                'phone' => '+994125678901',
                'craft' => 'Tikinti şirkəti',
                'specialty' => 'tikinti-sirketi',
                'category' => SpecialistCategory::Company,
                'experience' => 12,
                'city' => 'Bakı',
                'about' => 'Mənzil və ofis təmiri üzrə briqada. Qiymətləndirmə, smeta və mərhələli təhvil.',
                'skills' => ['Açar təslimi təmir', 'Smeta', 'Ofis təmiri', 'Dizayn nəzarəti'],
                'avatar' => null,
                'featured' => false,
            ],
            [
                'first_name' => 'Xəzər Tikinti',
                'last_name' => 'MMC',
                'email' => 'info@xezertikinti.archi.test',
                'phone' => '+994126789012',
                'craft' => 'Tikinti şirkəti',
                'specialty' => 'tikinti-sirketi',
                'category' => SpecialistCategory::Company,
                'experience' => 8,
                'city' => 'Gəncə',
                'about' => 'Fərdi evlərin tikintisi və bərpası. Bünövrədən dam örtüyünə qədər bütün mərhələlər.',
                'skills' => ['Fərdi ev tikintisi', 'Bünövrə', 'Bərpa işləri'],
                'avatar' => null,
                'featured' => false,
            ],
        ];

        foreach ($specialists as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['first_name'].' '.$data['last_name'],
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'phone' => $data['phone'],
                    'password' => 'password',
                    'role' => UserRole::Master,
                    'status' => UserStatus::Active,
                ],
            );

            // Looked up by the slug the entry names, never by Str::slug($craft):
            // transliterating "İnteryer dizayner" does not reliably land on the
            // `interyer-dizayner` row, and a miss would mint a duplicate specialty on
            // the default `master` category — the exact bug the recategorise migration
            // exists to clean up.
            $specialty = SpecialistSpecialty::firstOrCreate(
                ['slug' => $data['specialty']],
                [
                    'name' => ['az' => $data['craft']],
                    'category' => $data['category'] ?? SpecialistCategory::Master,
                    'is_active' => true,
                ],
            );

            SpecialistProfile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'specialist_specialty_id' => $specialty->id,
                    'craft' => $data['craft'],
                    'experience_years' => $data['experience'],
                    'city' => $data['city'],
                    'phone' => $data['phone'],
                    'about' => $data['about'],
                    'skills' => $data['skills'],
                    'avatar_path' => $data['avatar'],
                    'is_featured' => $data['featured'] ?? true,
                    'is_verified' => $data['verified'] ?? false,
                    // Only the original four are curated onto the homepage and the
                    // header; the rest exist to fill the directory's sections.
                    'show_on_homepage' => $data['homepage'] ?? false,
                    'show_in_header' => $data['homepage'] ?? false,
                    'is_on_vacation' => false,
                    'available_slots' => rand(3, 8),
                ],
            );
        }

        // Seed services for each specialist
        $this->seedSpecialistServices();
    }

    private function seedSpecialistServices(): void
    {
        $servicesByEmail = [
            'ramil.aliyev@archi.test' => [
                ['name' => 'Kafel & metlax döşəmə', 'description' => 'Mətbəx, hamam, döşəmə üzlüyü', 'price' => 25.00, 'unit' => 'sqm'],
                ['name' => 'Mozaika quraşdırma', 'description' => 'Dekorativ mozaika, bordür', 'price' => 35.00, 'unit' => 'sqm'],
                ['name' => 'Hidroizolyasiya', 'description' => 'Hamam və balkon üçün', 'price' => 18.00, 'unit' => 'sqm'],
                ['name' => 'Köhnə kafelin sökülməsi', 'description' => 'Demontaj və təmizlik', 'price' => 8.00, 'unit' => 'sqm'],
            ],
            'tural.hasanov@archi.test' => [
                ['name' => 'Elektrik quraşdırma', 'description' => 'Yeni tikililər üçün tam elektrik sistemi', 'price' => 15.00, 'unit' => 'sqm'],
                ['name' => 'Kabel çəkilişi', 'description' => 'Güc və zəif cərəyan kabelləri', 'price' => 5.00, 'unit' => 'metre'],
                ['name' => 'İşıqlandırma quraşdırma', 'description' => 'Lüstr, spot, LED panel', 'price' => 30.00, 'unit' => 'piece'],
                ['name' => 'Avtomatlar və panel', 'description' => 'Elektrik paneli quraşdırılması', 'price' => 120.00, 'unit' => 'piece'],
            ],
            'orxan.mammadov@archi.test' => [
                ['name' => 'Metal dam örtüyü', 'description' => 'Quraşdırma və təmir', 'price' => 20.00, 'unit' => 'sqm'],
                ['name' => 'Bitum dam örtüyü', 'description' => 'Yumşaq dam örtüyü quraşdırma', 'price' => 18.00, 'unit' => 'sqm'],
                ['name' => 'Dam hidroizolyasiyası', 'description' => 'Su keçirməzlik təminatı', 'price' => 12.00, 'unit' => 'sqm'],
                ['name' => 'Yağış suyu sistemi', 'description' => 'Nov və boru quraşdırma', 'price' => 25.00, 'unit' => 'metre'],
            ],
            'vusal.quliyev@archi.test' => [
                ['name' => 'Su təchizatı quraşdırma', 'description' => 'Boru çəkilişi və qoşma', 'price' => 20.00, 'unit' => 'sqm'],
                ['name' => 'Kanalizasiya sistemi', 'description' => 'Kanalizasiya boru quraşdırma', 'price' => 22.00, 'unit' => 'sqm'],
                ['name' => 'Kran və qarışdırıcı', 'description' => 'Quraşdırma və təmir', 'price' => 40.00, 'unit' => 'piece'],
                ['name' => 'İsitmə sistemi', 'description' => 'Radiator və boru quraşdırma', 'price' => 30.00, 'unit' => 'sqm'],
            ],

            // Design and construction work is quoted per m² of the object or per job,
            // never per running metre — the card prints "≈ from X ₼" without a unit for
            // exactly this reason, and the "ən ucuz" sort reads the lowest of these.
            'nigar.aliyeva@archi.test' => [
                ['name' => 'Eskiz layihə', 'description' => 'Planlaşdırma və həcm həlli', 'price' => 12.00, 'unit' => 'sqm'],
                ['name' => 'İş layihəsi', 'description' => 'İcra sənədlərinin tam dəsti', 'price' => 28.00, 'unit' => 'sqm'],
                ['name' => 'Fasad həlli', 'description' => 'Material və rəng passportu', 'price' => 18.00, 'unit' => 'sqm'],
                ['name' => 'Müəllif nəzarəti', 'description' => 'Tikinti boyu aylıq nəzarət', 'price' => 400.00, 'unit' => 'piece'],
            ],
            'elchin.rzayev@archi.test' => [
                ['name' => 'Fərdi ev layihəsi', 'description' => 'Bünövrədən dama tam layihə', 'price' => 22.00, 'unit' => 'sqm'],
                ['name' => 'Planlaşdırma həlli', 'description' => 'Mərtəbə planları, 2 variant', 'price' => 9.00, 'unit' => 'sqm'],
                ['name' => 'Konstruktiv hesabat', 'description' => 'Daşıyıcı elementlərin hesabatı', 'price' => 14.00, 'unit' => 'sqm'],
                ['name' => 'İcazə sənədlərinin hazırlanması', 'description' => 'Tikinti icazəsi üçün dəst', 'price' => 650.00, 'unit' => 'piece'],
            ],
            'aysen.kerimova@archi.test' => [
                ['name' => 'Ofis planlaşdırması', 'description' => 'İş yerlərinin yerləşdirilməsi', 'price' => 10.00, 'unit' => 'sqm'],
                ['name' => 'İctimai məkan layihəsi', 'description' => 'Qəbul, dəhliz və zal həlli', 'price' => 20.00, 'unit' => 'sqm'],
                ['name' => 'İşıq layihəsi', 'description' => 'İşıqlandırma sxemi və hesabat', 'price' => 7.00, 'unit' => 'sqm'],
            ],

            'leyla.huseynova@archi.test' => [
                ['name' => 'İnteryer konsepsiyası', 'description' => 'Üslub, rəng və material lövhəsi', 'price' => 15.00, 'unit' => 'sqm'],
                ['name' => 'Tam interyer layihəsi', 'description' => 'Çertyojlar, 3D və spesifikasiya', 'price' => 35.00, 'unit' => 'sqm'],
                ['name' => '3D vizuallaşdırma', 'description' => 'Otaq başına foto-real render', 'price' => 180.00, 'unit' => 'piece'],
                ['name' => 'Müəllif nəzarəti', 'description' => 'Təmir boyu aylıq nəzarət', 'price' => 350.00, 'unit' => 'piece'],
            ],
            'kamran.safarov@archi.test' => [
                ['name' => 'Kommersiya interyeri', 'description' => 'Kafe, mağaza və restoran', 'price' => 30.00, 'unit' => 'sqm'],
                ['name' => 'Mebel layihəsi', 'description' => 'Sifarişlə mebel çertyojları', 'price' => 25.00, 'unit' => 'sqm'],
                ['name' => 'Brend məkanı həlli', 'description' => 'Naviqasiya və vitrin konsepsiyası', 'price' => 220.00, 'unit' => 'piece'],
            ],
            'gunel.nabiyeva@archi.test' => [
                ['name' => 'Kiçik mənzil planı', 'description' => '1-2 otaqlı mənzil üçün həll', 'price' => 11.00, 'unit' => 'sqm'],
                ['name' => 'Rəng və material həlli', 'description' => 'Hazır alış siyahısı ilə', 'price' => 6.00, 'unit' => 'sqm'],
                ['name' => 'Qarderob və anbar planı', 'description' => 'Daxili bölgü çertyoju', 'price' => 90.00, 'unit' => 'piece'],
            ],

            'info@aztikinti.archi.test' => [
                ['name' => 'Açar təslimi tikinti', 'description' => 'Sıfırdan təhvilə qədər', 'price' => 620.00, 'unit' => 'sqm'],
                ['name' => 'Monolit karkas', 'description' => 'Bünövrə və daşıyıcı karkas', 'price' => 240.00, 'unit' => 'sqm'],
                ['name' => 'Fasad işləri', 'description' => 'İstilik izolyasiyası və üzlük', 'price' => 85.00, 'unit' => 'sqm'],
                ['name' => 'Layihə idarəetməsi', 'description' => 'Aylıq icra və smeta nəzarəti', 'price' => 900.00, 'unit' => 'piece'],
            ],
            'info@insaservis.archi.test' => [
                ['name' => 'Açar təslimi təmir', 'description' => 'Mənzil və ofis, material daxil', 'price' => 310.00, 'unit' => 'sqm'],
                ['name' => 'Təmir smetası', 'description' => 'Obyektə baxış və qiymətləndirmə', 'price' => 120.00, 'unit' => 'piece'],
                ['name' => 'Ofis təmiri', 'description' => 'Açıq ofis və iclas otaqları', 'price' => 260.00, 'unit' => 'sqm'],
            ],
            'info@xezertikinti.archi.test' => [
                ['name' => 'Fərdi ev tikintisi', 'description' => 'Bünövrədən dam örtüyünə qədər', 'price' => 540.00, 'unit' => 'sqm'],
                ['name' => 'Bünövrə işləri', 'description' => 'Qazıntı, armatur və beton', 'price' => 150.00, 'unit' => 'sqm'],
                ['name' => 'Bərpa işləri', 'description' => 'Köhnə tikilinin gücləndirilməsi', 'price' => 190.00, 'unit' => 'sqm'],
            ],
        ];

        foreach ($servicesByEmail as $email => $services) {
            $user = User::where('email', $email)->first();
            if (! $user) {
                continue;
            }
            $profile = SpecialistProfile::where('user_id', $user->id)->first();
            if (! $profile) {
                continue;
            }

            foreach ($services as $i => $svc) {
                SpecialistService::firstOrCreate(
                    ['specialist_profile_id' => $profile->id, 'name' => $svc['name']],
                    array_merge($svc, [
                        'specialist_profile_id' => $profile->id,
                        'is_active' => true,
                        'sort_order' => $i + 1,
                    ]),
                );
            }
        }
    }
}
