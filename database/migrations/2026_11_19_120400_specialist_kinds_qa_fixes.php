<?php

declare(strict_types=1);

use App\Models\MenuItem;
use App\Models\Translation;
use App\Support\VersionedCache;
use Illuminate\Database\Migrations\Migration;

/**
 * QA follow-up for the Briqadir / Makler kinds (docs/specialist-kinds.md).
 *
 * - Mega-panel icons: Briqadirlər gets icon-bricks.svg (it shared Ustalar's
 *   hammer-wrench) and Maklerlər the new icon-house.svg, drawn on the same 40px grid as
 *   the other cards (the 24px map-pin rendered larger and heavier).
 * - Copy from database/seeders/data/specialist_kinds_qa_translations.php: keys listed
 *   in OVERWRITE are deliberate changes (kind-neutral cabinet headings, the register
 *   subtitle, «О специалисте»); every other key is new and only inserted if missing.
 */
return new class extends Migration
{
    private const LOCATION = 'header_mega_specialists';

    private const ICONS = [
        '/specialists?type=foreman' => ['icon-hammer-wrench.svg', 'icon-bricks.svg'],
        '/specialists?type=realtor' => ['icon-map-pin.svg', 'icon-house.svg'],
    ];

    /** group => keys whose existing value is replaced on purpose. */
    private const OVERWRITE = [
        'register' => ['head.subtitle'],
        'specialist' => ['about.eyebrow'],
        'specialist-owner' => ['about.eyebrow'],
        'specialist-cabinet' => ['heading', 'crumbs.panel'],
        'specialist-cabinet-notifications' => ['heading', 'crumbs.panel', 'title'],
        'specialist-cabinet-portfolio' => ['heading', 'crumbs.panel'],
        'specialist-cabinet-reviews' => ['heading', 'crumbs.panel'],
        'specialist-cabinet-schedule' => ['heading', 'crumbs.panel', 'title'],
        'specialist-cabinet-security' => ['heading', 'crumbs.panel'],
        'specialist-cabinet-services' => ['heading', 'crumbs.panel'],
    ];

    public function up(): void
    {
        foreach (self::ICONS as $url => [$old, $new]) {
            MenuItem::query()->where('location', self::LOCATION)->where('url', $url)
                ->where('icon', $old)->update(['icon' => $new]);
        }

        foreach ($this->rows() as $group => $items) {
            foreach ($items as $key => $value) {
                if (in_array($key, self::OVERWRITE[$group] ?? [], true)) {
                    Translation::updateOrCreate(['group' => $group, 'key' => $key], ['value' => $value]);
                } else {
                    Translation::firstOrCreate(['group' => $group, 'key' => $key], ['value' => $value]);
                }
            }
        }

        Translation::clearCache();
        // A query-builder update fires no model events, so the cached header (menu)
        // has to be told explicitly.
        VersionedCache::bump(VersionedCache::CHROME);
    }

    public function down(): void
    {
        foreach (self::ICONS as $url => [$old, $new]) {
            MenuItem::query()->where('location', self::LOCATION)->where('url', $url)
                ->where('icon', $new)->update(['icon' => $old]);
        }

        // Only the keys this migration introduced; the overwritten ones keep their new
        // copy (the previous wording is not worth restoring).
        foreach ($this->rows() as $group => $items) {
            Translation::query()
                ->where('group', $group)
                ->whereIn('key', array_diff(array_keys($items), self::OVERWRITE[$group] ?? []))
                ->delete();
        }

        Translation::clearCache();
        // A query-builder update fires no model events, so the cached header (menu)
        // has to be told explicitly.
        VersionedCache::bump(VersionedCache::CHROME);
    }

    /**
     * @return array<string, array<string, array{az: string, ru: string, en: string}>>
     */
    private function rows(): array
    {
        return require database_path('seeders/data/specialist_kinds_qa_translations.php');
    }
};
