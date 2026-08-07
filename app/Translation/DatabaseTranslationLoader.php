<?php

namespace App\Translation;

use App\Models\Translation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Translation\FileLoader;

/**
 * Translation loader that serves the app's UI strings from the "translations"
 * table (editable in the Filament "Tərcümələr" module) while still falling back
 * to the file-based lang folder for framework groups such as "validation".
 *
 * For a given (locale, group) it first loads whatever the parent FileLoader finds
 * on disk, then overlays the database rows on top (DB wins). This means:
 *   - t('common.site_name'), __('common.site_name'), trans_choice(...) and
 *     Lang::has(...) all resolve from the DB.
 *   - Deleting an app lang file simply makes that group come purely from the DB.
 *   - The framework validation lang file keeps working from disk.
 *
 * Results are cached forever per (group, locale) and flushed by the Translation
 * model whenever a row is saved or deleted (see Translation::booted()).
 */
class DatabaseTranslationLoader extends FileLoader
{
    public function load($locale, $group, $namespace = null)
    {
        $fileLines = parent::load($locale, $group, $namespace);

        // Only override the default namespace — leave vendor package namespaces alone.
        if ($namespace !== null && $namespace !== '*') {
            return $fileLines;
        }

        $dbLines = $this->loadDatabaseGroup($locale, $group);

        if (empty($dbLines)) {
            return $fileLines;
        }

        return array_replace_recursive($fileLines, $dbLines);
    }

    /**
     * @return array<string, mixed> nested lang array for the group in the given locale
     */
    protected function loadDatabaseGroup(string $locale, string $group): array
    {
        return Cache::rememberForever("translations.{$group}.{$locale}", function () use ($locale, $group) {
            try {
                if (! Schema::hasTable('translations')) {
                    return [];
                }
            } catch (\Throwable) {
                // DB not reachable yet (e.g. during install/migrate) — fall back to files.
                return [];
            }

            $lines = [];

            foreach (Translation::where('group', $group)->get() as $row) {
                $value = $row->getTranslation('value', $locale, false);

                if ($value === '' || $value === null) {
                    // Fall back to Azerbaijani (the source language) for empty values.
                    $value = $row->getTranslation('value', 'az', false);
                }

                // Support nested keys ("nav.profile" => ['nav' => ['profile' => ...]]).
                data_set($lines, $row->key, $value);
            }

            return $lines;
        });
    }
}
