<?php

declare(strict_types=1);

use App\Models\MenuItem;
use App\Models\Translation;
use Illuminate\Database\Migrations\Migration;

/**
 * Carries the content changes of the site-wide QA pass to every environment.
 *
 * These are database rows, not code, so they do not travel with a `git pull`. Running
 * `db:seed --class=TranslationsSeeder` would carry them — along with a rewrite of every
 * other key, discarding whatever an admin has edited in the Tərcümələr module. A
 * migration moves exactly these rows and nothing else, and `migrate --force` is already
 * part of the deploy.
 *
 * Idempotent: re-running changes nothing.
 */
return new class extends Migration
{
    /** group => key => [az, ru, en] */
    private const TRANSLATIONS = [
        // Registration no longer waits for an admin, so the two old outcomes
        // ("welcome" vs "wait for approval") collapsed into one message.
        'register' => [
            'active_message' => [
                'az' => 'Qeydiyyat uğurla tamamlandı! Hesabınıza daxil olundu.',
                'ru' => 'Регистрация завершена! Вы вошли в свой аккаунт.',
                'en' => 'Registration complete! You are now signed in.',
            ],
        ],
        // The detailed calculator collects dimensions in millimetres now.
        'calculator-detailed' => [
            'units.mm' => ['az' => 'mm', 'ru' => 'мм', 'en' => 'mm'],
        ],
        // /help printed these two keys as literal button labels in all three locales:
        // the t() calls were added without the rows ever being created.
        'help' => [
            'contact_number' => ['az' => '+994 12 555 00 12', 'ru' => '+994 12 555 00 12', 'en' => '+994 12 555 00 12'],
            'contact_email' => ['az' => 'salam@archi.az', 'ru' => 'salam@archi.az', 'en' => 'salam@archi.az'],
        ],
        // The banner used to bake "40" into the sentence; it now receives the real
        // ProfileCompleteness score, so the string needs a placeholder.
        'specialist-onboarding' => [
            'banner.note' => [
                'az' => 'Profilin :percent% hazırdır — tamamla, axtarışda görünsün',
                'ru' => 'Профиль готов на :percent% — заполни его, чтобы попасть в поиск',
                'en' => 'Your profile is :percent% ready — finish it to show up in search',
            ],
        ],
        // The browser tab read the Figma demo person's name for every master.
        'specialist-owner' => [
            'title' => [
                'az' => ':name — Profilim — ARCHİ',
                'ru' => ':name — Мой профиль — ARCHİ',
                'en' => ':name — My profile — ARCHİ',
            ],
        ],
    ];

    public function up(): void
    {
        foreach (self::TRANSLATIONS as $group => $items) {
            foreach ($items as $key => $value) {
                Translation::updateOrCreate(
                    ['group' => $group, 'key' => $key],
                    ['value' => $value],
                );
            }
        }

        // "Sayt xəritəsi" pointed at /sitemap, a route that has never existed — a 404
        // in the footer of every page. Removed rather than repointed: there is nothing
        // to point it at yet. MenuItemSeeder no longer creates it either.
        MenuItem::query()
            ->where('location', 'footer_legal')
            ->where('url', '/sitemap')
            ->delete();

        Translation::clearCache();
    }

    /**
     * The translation rows are left in place: they are the copy the deployed code
     * reads, and removing them would make live pages echo their own keys. The menu row
     * is restored so a rollback puts the footer back exactly as it was, dead link and
     * all.
     */
    public function down(): void
    {
        if (! MenuItem::query()->where('location', 'footer_legal')->where('url', '/sitemap')->exists()) {
            MenuItem::create([
                'location' => 'footer_legal',
                'label' => ['az' => 'Sayt xəritəsi', 'ru' => 'Карта сайта', 'en' => 'Sitemap'],
                'url' => '/sitemap',
                'sort_order' => 5,
            ]);
        }
    }
};
