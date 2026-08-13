<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        // Header main nav
        $headerItems = [
            ['label' => ['az' => 'Kataloq', 'ru' => 'Каталог', 'en' => 'Catalog'], 'route_name' => 'catalog', 'icon' => 'icon-menu.svg', 'has_dropdown' => true, 'css_class' => 'catalog', 'sort_order' => 1],
            ['label' => ['az' => 'Mütəxəssislər', 'ru' => 'Специалисты', 'en' => 'Specialists'], 'route_name' => 'specialists', 'has_dropdown' => true, 'sort_order' => 2],
            ['label' => ['az' => 'Bloq', 'ru' => 'Блог', 'en' => 'Blog'], 'route_name' => 'blog', 'has_dropdown' => true, 'sort_order' => 3],
            ['label' => ['az' => 'Haqqımızda', 'ru' => 'О нас', 'en' => 'About'], 'route_name' => 'about', 'sort_order' => 4],
            ['label' => ['az' => 'B2B', 'ru' => 'B2B', 'en' => 'B2B'], 'route_name' => 'business.register', 'sort_order' => 5],
            ['label' => ['az' => 'Təmir kalkulyatoru', 'ru' => 'Калькулятор ремонта', 'en' => 'Renovation calculator'], 'route_name' => 'calculator', 'icon' => 'icon-calculator.svg', 'css_class' => 'nav-calc', 'sort_order' => 6],
        ];

        foreach ($headerItems as $item) {
            MenuItem::create(array_merge($item, ['location' => 'header_main']));
        }

        // Mega Catalog cards
        $megaCatalog = [
            ['label' => ['az' => 'Tikinti materialları', 'ru' => 'Стройматериалы', 'en' => 'Building materials'], 'description' => ['az' => 'Kərpic, sement, beton qarışıqları', 'ru' => 'Кирпич, цемент, бетонные смеси', 'en' => 'Bricks, cement, concrete mixes'], 'icon' => 'icon-bricks.svg', 'url' => '/catalog?category=tikinti', 'sort_order' => 1],
            ['label' => ['az' => 'Santexnika', 'ru' => 'Сантехника', 'en' => 'Plumbing'], 'description' => ['az' => 'Kran, boru, duş kabinləri', 'ru' => 'Краны, трубы, душевые кабины', 'en' => 'Faucets, pipes, shower cabins'], 'icon' => 'icon-faucet.svg', 'url' => '/catalog?category=santexnika', 'sort_order' => 2],
            ['label' => ['az' => 'Elektrik', 'ru' => 'Электрика', 'en' => 'Electrical'], 'description' => ['az' => 'Kabel, rozetka, avtomatlar', 'ru' => 'Кабели, розетки, автоматы', 'en' => 'Cables, sockets, circuit breakers'], 'icon' => 'icon-power-plug.svg', 'url' => '/catalog?category=elektrik', 'sort_order' => 3],
            ['label' => ['az' => 'Döşemə & üzlük', 'ru' => 'Полы & облицовка', 'en' => 'Flooring & tiles'], 'description' => ['az' => 'Kafel, laminat, parket', 'ru' => 'Плитка, ламинат, паркет', 'en' => 'Tiles, laminate, parquet'], 'icon' => 'icon-floor-tiles.svg', 'url' => '/catalog?category=doseme', 'sort_order' => 4],
            ['label' => ['az' => 'İşıqlandırma', 'ru' => 'Освещение', 'en' => 'Lighting'], 'description' => ['az' => 'Lüstr, spot, LED panel', 'ru' => 'Люстры, споты, LED панели', 'en' => 'Chandeliers, spots, LED panels'], 'icon' => 'icon-pendant-lamp.svg', 'url' => '/catalog?category=isiqlandirma', 'sort_order' => 5],
            ['label' => ['az' => 'Dekor & mebel', 'ru' => 'Декор & мебель', 'en' => 'Decor & furniture'], 'description' => ['az' => 'İnteryer aksessuarları', 'ru' => 'Интерьерные аксессуары', 'en' => 'Interior accessories'], 'icon' => 'icon-armchair.svg', 'url' => '/catalog?category=dekor', 'sort_order' => 6],
        ];

        foreach ($megaCatalog as $item) {
            MenuItem::create(array_merge($item, ['location' => 'header_mega_catalog']));
        }

        // Mega Specialists cards
        $megaSpec = [
            ['label' => ['az' => 'Arxitektorlar', 'ru' => 'Архитекторы', 'en' => 'Architects'], 'description' => ['az' => 'Layihə, plan çəkmə', 'ru' => 'Проекты, чертежи', 'en' => 'Projects, blueprints'], 'icon' => 'icon-blueprint.svg', 'url' => '/specialists?type=architect', 'sort_order' => 1],
            ['label' => ['az' => 'İnteryer dizaynerlər', 'ru' => 'Интерьерные дизайнеры', 'en' => 'Interior designers'], 'description' => ['az' => 'Daxili dizayn həlləri', 'ru' => 'Решения дизайна интерьера', 'en' => 'Interior design solutions'], 'icon' => 'icon-interior-design.svg', 'url' => '/specialists?type=designer', 'sort_order' => 2],
            ['label' => ['az' => 'Ustalar', 'ru' => 'Мастера', 'en' => 'Masters'], 'description' => ['az' => 'Kafelçi, boyaçı, elektrik', 'ru' => 'Плиточник, маляр, электрик', 'en' => 'Tiler, painter, electrician'], 'icon' => 'icon-hammer-wrench.svg', 'url' => '/specialists?type=master', 'sort_order' => 3],
            ['label' => ['az' => 'Tikinti şirkətləri', 'ru' => 'Строительные компании', 'en' => 'Construction companies'], 'description' => ['az' => 'Tam təmir xidmətləri', 'ru' => 'Полные ремонтные услуги', 'en' => 'Full renovation services'], 'icon' => 'icon-tower-crane.svg', 'url' => '/specialists?type=company', 'sort_order' => 4],
        ];

        foreach ($megaSpec as $item) {
            MenuItem::create(array_merge($item, ['location' => 'header_mega_specialists']));
        }

        // Footer columns
        $footerData = [
            [
                'label' => ['az' => 'Məhsullar', 'ru' => 'Товары', 'en' => 'Products'],
                'is_clickable' => false,
                'sort_order' => 1,
                // Four classifier sections (slugs must stay in sync with
                // NavigationSeeder::FOOTER_SECTIONS, which corrects existing rows).
                'children' => [
                    ['label' => ['az' => 'Döşəmələr', 'ru' => 'Полы', 'en' => 'Flooring'], 'url' => '/catalog?section=dosemeler', 'sort_order' => 1],
                    ['label' => ['az' => 'Divarlar və arakəsmələr', 'ru' => 'Стены и перегородки', 'en' => 'Walls & partitions'], 'url' => '/catalog?section=divarlar-ve-arakesmeler', 'sort_order' => 2],
                    ['label' => ['az' => 'Santexnika', 'ru' => 'Сантехника', 'en' => 'Plumbing'], 'url' => '/catalog?section=santexnika-su-techizati-ve-kanalizasiya', 'sort_order' => 3],
                    ['label' => ['az' => 'Elektrik və işıqlandırma', 'ru' => 'Электрика и освещение', 'en' => 'Electrical & lighting'], 'url' => '/catalog?section=elektrik-isiqlandirma-zeif-cereyan-sistemleri-ve-agilli-ev', 'sort_order' => 4],
                    ['label' => ['az' => 'Bütün kateqoriyalar', 'ru' => 'Все категории', 'en' => 'All categories'], 'url' => '/catalog', 'sort_order' => 5],
                ],
            ],
            [
                'label' => ['az' => 'Mütəxəssislər', 'ru' => 'Специалисты', 'en' => 'Specialists'],
                'is_clickable' => false,
                'sort_order' => 2,
                'children' => [
                    ['label' => ['az' => 'Usta tap', 'ru' => 'Найти мастера', 'en' => 'Find a master'], 'url' => '/specialists', 'sort_order' => 1],
                    ['label' => ['az' => 'Top ustalar', 'ru' => 'Топ мастера', 'en' => 'Top masters'], 'url' => '/specialists?featured=1', 'sort_order' => 2],
                    ['label' => ['az' => 'Hamısına bax', 'ru' => 'Смотреть все', 'en' => 'View all'], 'url' => '/specialists', 'sort_order' => 3],
                ],
            ],
            [
                'label' => ['az' => 'ARCHI-yə qoşul', 'ru' => 'Присоединяйся', 'en' => 'Join ARCHI'],
                'is_clickable' => false,
                'sort_order' => 3,
                'children' => [
                    ['label' => ['az' => 'Satıcı ol', 'ru' => 'Стать продавцом', 'en' => 'Become a seller'], 'url' => '/sell', 'sort_order' => 1],
                    ['label' => ['az' => 'Usta ol', 'ru' => 'Стать мастером', 'en' => 'Become a master'], 'url' => '/register?role=master', 'sort_order' => 2],
                    ['label' => ['az' => 'Partnyor proqramı', 'ru' => 'Партнёрская программа', 'en' => 'Partner program'], 'url' => '/business/register', 'sort_order' => 3],
                    ['label' => ['az' => 'Biznes əməkdaşlığı', 'ru' => 'Бизнес сотрудничество', 'en' => 'Business cooperation'], 'url' => '/business/register', 'sort_order' => 4],
                ],
            ],
            [
                'label' => ['az' => 'Şirkət & dəstək', 'ru' => 'Компания & поддержка', 'en' => 'Company & support'],
                'is_clickable' => false,
                'sort_order' => 4,
                'children' => [
                    ['label' => ['az' => 'Haqqımızda', 'ru' => 'О нас', 'en' => 'About us'], 'url' => '/about', 'sort_order' => 1],
                    ['label' => ['az' => 'Pulsuz konsultasiya', 'ru' => 'Бесплатная консультация', 'en' => 'Free consultation'], 'url' => '/specialists', 'sort_order' => 2],
                    ['label' => ['az' => 'Məqalələr', 'ru' => 'Статьи', 'en' => 'Articles'], 'url' => '/blog', 'sort_order' => 3],
                    ['label' => ['az' => 'Yardım mərkəzi', 'ru' => 'Центр помощи', 'en' => 'Help center'], 'url' => '/about', 'sort_order' => 4],
                    ['label' => ['az' => 'Əlaqə', 'ru' => 'Контакты', 'en' => 'Contact'], 'url' => '/about', 'sort_order' => 5],
                ],
            ],
        ];

        foreach ($footerData as $col) {
            $children = $col['children'] ?? [];
            unset($col['children']);

            $parent = MenuItem::create(array_merge($col, ['location' => 'footer']));

            foreach ($children as $child) {
                MenuItem::create(array_merge($child, ['location' => 'footer', 'parent_id' => $parent->id]));
            }
        }

        // Footer legal links
        $legal = [
            ['label' => ['az' => 'İstifadə şərtləri', 'ru' => 'Условия использования', 'en' => 'Terms of use'], 'url' => '/terms', 'sort_order' => 1],
            ['label' => ['az' => 'Gizlilik siyasəti', 'ru' => 'Политика конфиденциальности', 'en' => 'Privacy policy'], 'url' => '/privacy', 'sort_order' => 2],
            ['label' => ['az' => 'Çatdırılma & qaytarma', 'ru' => 'Доставка & возврат', 'en' => 'Delivery & returns'], 'url' => '/delivery', 'sort_order' => 3],
            ['label' => ['az' => 'Cookie siyasəti', 'ru' => 'Политика cookie', 'en' => 'Cookie policy'], 'url' => '/cookies', 'sort_order' => 4],
            ['label' => ['az' => 'Sayt xəritəsi', 'ru' => 'Карта сайта', 'en' => 'Sitemap'], 'url' => '/sitemap', 'sort_order' => 5],
        ];

        foreach ($legal as $item) {
            MenuItem::create(array_merge($item, ['location' => 'footer_legal']));
        }
    }
}
