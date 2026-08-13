<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;

/**
 * Catalog navigation (classifier sheet 7_Навигация).
 *
 * Replaces the six legacy "Header — Mega Kataloq" placeholder cards with the
 * twelve classifier sections. Each card links to the catalog filtered by that
 * section (/catalog?section=slug). The five customer navigation clusters are
 * NOT extra header items — the header has no room for five more top-level
 * entries — they are rendered as group headings inside the catalog page's
 * left rail (see resources/views/pages/catalog.blade.php) and the section
 * cards below keep the cluster order 1-12.
 *
 * Idempotent: cards are matched by (location, url); anything else left in the
 * header_mega_catalog location is removed, so re-running never duplicates.
 *
 * The same pass repoints the footer "Məhsullar" column, which still linked to
 * four legacy `?category=` slugs — two of them retired (kafel, santexnika), two
 * that never existed (boya, izolyasiya).
 */
class NavigationSeeder extends Seeder
{
    /**
     * Footer "Məhsullar" column: section slug => [az, ru, en] label. Labels are
     * shortened versions of the section names — the footer column is narrow.
     * Keep in sync with MenuItemSeeder's footer block (fresh installs).
     */
    private const FOOTER_SECTIONS = [
        'dosemeler' => ['Döşəmələr', 'Полы', 'Flooring'],
        'divarlar-ve-arakesmeler' => ['Divarlar və arakəsmələr', 'Стены и перегородки', 'Walls & partitions'],
        'santexnika-su-techizati-ve-kanalizasiya' => ['Santexnika', 'Сантехника', 'Plumbing'],
        'elektrik-isiqlandirma-zeif-cereyan-sistemleri-ve-agilli-ev' => ['Elektrik və işıqlandırma', 'Электрика и освещение', 'Electrical & lighting'],
    ];

    public function run(): void
    {
        // slug => [icon, ru label, en label, [az desc, ru desc, en desc]]
        $sections = [
            'divarlar-ve-arakesmeler' => [
                'icon-bricks.svg', 'Стены и перегородки', 'Walls & partitions',
                ['Üzlük örtüklər, arakəsmə sistemləri, dekor', 'Облицовка, перегородки, декор', 'Cladding, partition systems, decor'],
            ],
            'dosemeler' => [
                'icon-floor-tiles.svg', 'Полы', 'Flooring',
                ['Laminat, parket, plitə, xalça örtükləri', 'Ламинат, паркет, плитка, ковровые покрытия', 'Laminate, parquet, tiles, carpets'],
            ],
            'tavanlar' => [
                'icon-pendant-lamp.svg', 'Потолки', 'Ceilings',
                ['Dartma və asma tavanlar, dekorativ üzləmə', 'Натяжные и подвесные потолки, декор', 'Stretch & suspended ceilings, decor'],
            ],
            'qara-is-ve-umumi-tikinti-materiallari' => [
                'icon-tower-crane.svg', 'Черновые и общестроительные материалы', 'Structural & general materials',
                ['Quru qarışıqlar, hörgü, izolyasiya, bərkitmə', 'Смеси, кладка, изоляция, крепёж', 'Mixes, masonry, insulation, fasteners'],
            ],
            'santexnika-su-techizati-ve-kanalizasiya' => [
                'icon-faucet.svg', 'Сантехника, водоснабжение и канализация', 'Plumbing, water & sewerage',
                ['Borular, qarışdırıcılar, sanitar keramika', 'Трубы, смесители, сантехническая керамика', 'Pipes, mixers, sanitary ceramics'],
            ],
            'elektrik-isiqlandirma-zeif-cereyan-sistemleri-ve-agilli-ev' => [
                'icon-power-plug.svg', 'Электрика, освещение и умный дом', 'Electrical, lighting & smart home',
                ['Kabel, rozetka, işıqlandırma, ağıllı ev', 'Кабели, розетки, освещение, умный дом', 'Cables, sockets, lighting, smart home'],
            ],
            'istilik-ventilyasiya-ve-kondisionerlesdirme' => [
                'icon-hammer-wrench.svg', 'Отопление, вентиляция и кондиционирование', 'Heating, ventilation & AC',
                ['Qazanlar, radiatorlar, ventilyasiya, kondisioner', 'Котлы, радиаторы, вентиляция, кондиционеры', 'Boilers, radiators, ventilation, AC'],
            ],
            'qapilar-pencereler-ve-suseleme' => [
                'icon-interior-design.svg', 'Двери, окна и остекление', 'Doors, windows & glazing',
                ['Giriş və otaq qapıları, pəncərələr, furnitura', 'Входные и межкомнатные двери, окна, фурнитура', 'Entrance & interior doors, windows, hardware'],
            ],
            'dam-ortuyu-ve-fasad' => [
                'icon-blueprint.svg', 'Кровля и фасад', 'Roofing & facade',
                ['Dam örtüyü materialları, fasad üzləməsi', 'Кровельные материалы, фасадная облицовка', 'Roofing materials, facade cladding'],
            ],
            'mebel-komplektlesdiriciler-furnitura-ve-mexanizmler' => [
                'icon-armchair.svg', 'Мебель, комплектующие и фурнитура', 'Furniture, components & hardware',
                ['Korpus mebel, mətbəxlər, furnitura, mexanizmlər', 'Корпусная мебель, кухни, фурнитура', 'Cabinet furniture, kitchens, hardware'],
            ],
            'tekstil-perdeler-gunesden-qorunma-ve-interyer-dekoru' => [
                'icon-crown-gold.svg', 'Текстиль, шторы и декор интерьера', 'Textiles, curtains & decor',
                ['Pərdələr, jalüzlər, xalçalar, interyer dekoru', 'Шторы, жалюзи, ковры, декор интерьера', 'Curtains, blinds, carpets, interior decor'],
            ],
            'landsaft-ve-abadlasdirma' => [
                'icon-map-pin.svg', 'Ландшафт и благоустройство', 'Landscape & outdoor',
                ['Səki örtükləri, yaşıllaşdırma, hasarlar, hovuzlar', 'Мощение, озеленение, ограждения, бассейны', 'Paving, greenery, fences, pools'],
            ],
        ];

        $keptUrls = [];
        $order = 0;

        foreach ($sections as $slug => [$icon, $ru, $en, $desc]) {
            $order++;
            $section = Category::roots()->where('slug', $slug)->first();
            if (! $section) {
                continue; // classifier not imported yet — leave the existing menu alone
            }

            $url = '/catalog?section='.$slug;
            $keptUrls[] = $url;

            MenuItem::updateOrCreate(
                ['location' => 'header_mega_catalog', 'url' => $url],
                [
                    'label' => [
                        'az' => $section->getTranslation('name', 'az'),
                        'ru' => $ru,
                        'en' => $en,
                    ],
                    'description' => ['az' => $desc[0], 'ru' => $desc[1], 'en' => $desc[2]],
                    'icon' => $icon,
                    'sort_order' => $order,
                    'is_active' => true,
                    'parent_id' => null,
                ],
            );
        }

        // Retire whatever else is left in the mega catalog panel (the six legacy
        // placeholder cards). Only runs once the new cards actually exist.
        if ($keptUrls !== []) {
            MenuItem::location('header_mega_catalog')->whereNotIn('url', $keptUrls)->delete();
        }

        $this->syncFooterProducts();
    }

    /**
     * Repoint the footer "Məhsullar" column at live classifier sections.
     *
     * Rows are matched by (location, parent_id, sort_order) rather than by url,
     * so the existing rows are CORRECTED in place — re-running never duplicates
     * them and the admin's row ids survive. Extra rows below the last managed
     * position are removed. No-op while the classifier is not imported.
     */
    private function syncFooterProducts(): void
    {
        $column = MenuItem::location('footer')->roots()->ordered()->get()
            ->first(fn (MenuItem $root) => MenuItem::where('parent_id', $root->id)
                ->where('url', 'like', '/catalog%')->exists());

        if (! $column) {
            return; // no products column (or an admin already rebuilt it) — leave it alone
        }

        $order = 0;
        foreach (self::FOOTER_SECTIONS as $slug => [$az, $ru, $en]) {
            if (! Category::roots()->active()->where('slug', $slug)->exists()) {
                continue; // classifier not imported yet
            }

            $order++;
            MenuItem::updateOrCreate(
                ['location' => 'footer', 'parent_id' => $column->id, 'sort_order' => $order],
                [
                    'label' => ['az' => $az, 'ru' => $ru, 'en' => $en],
                    'url' => '/catalog?section='.$slug,
                    'route_name' => null,
                    'is_active' => true,
                ],
            );
        }

        if ($order === 0) {
            return;
        }

        // "Bütün kateqoriyalar" always closes the column.
        $order++;
        MenuItem::updateOrCreate(
            ['location' => 'footer', 'parent_id' => $column->id, 'sort_order' => $order],
            [
                'label' => ['az' => 'Bütün kateqoriyalar', 'ru' => 'Все категории', 'en' => 'All categories'],
                'url' => '/catalog',
                'route_name' => null,
                'is_active' => true,
            ],
        );

        MenuItem::location('footer')->where('parent_id', $column->id)
            ->where('sort_order', '>', $order)->delete();
    }
}
