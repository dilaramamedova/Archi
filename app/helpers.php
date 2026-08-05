<?php

use App\Models\Setting;
use App\Models\Translation;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('trans_db')) {
    function trans_db(string $groupAndKey): string
    {
        return Translation::trans($groupAndKey);
    }
}

if (! function_exists('translate_craft')) {
    /**
     * Translate a specialist craft name from its stored (Azerbaijani) value
     * to the current locale. If no mapping exists, returns the original string.
     */
    function translate_craft(?string $craft): ?string
    {
        if ($craft === null) {
            return null;
        }

        $locale = app()->getLocale();

        // Azerbaijani is the source language — no translation needed.
        if ($locale === 'az') {
            return $craft;
        }

        // Mapping: lowercase AZ craft name => [en, ru]
        static $map = [
            'kafelçi'                    => ['en' => 'Tiler',                        'ru' => 'Плиточник'],
            'kafel ustası'               => ['en' => 'Tiler',                        'ru' => 'Плиточник'],
            'kafel & metlax ustası'      => ['en' => 'Wall & floor tiler',           'ru' => 'Мастер плитки и метлах'],
            'santexnik'                  => ['en' => 'Plumber',                      'ru' => 'Сантехник'],
            'santexnik & istilik'        => ['en' => 'Plumbing & heating',           'ru' => 'Сантехника и отопление'],
            'santexnik & kafel'          => ['en' => 'Plumbing & tiling',            'ru' => 'Сантехника и плитка'],
            'elektrik'                   => ['en' => 'Electrician',                  'ru' => 'Электрик'],
            'elektrik ustası'            => ['en' => 'Electrician',                  'ru' => 'Мастер-электрик'],
            'elektrik & işıqlandırma'    => ['en' => 'Electrics & lighting',         'ru' => 'Электрика и освещение'],
            'elektrik & smart ev'        => ['en' => 'Electrics & smart home',       'ru' => 'Электрика и умный дом'],
            'rəngsaz'                    => ['en' => 'Painter',                      'ru' => 'Маляр'],
            'boyaçı'                     => ['en' => 'Painter',                      'ru' => 'Маляр'],
            'boyaçı & dekorativ suvaq'   => ['en' => 'Painter & decorative plaster', 'ru' => 'Маляр и декоративная штукатурка'],
            'interyer dizayner'          => ['en' => 'Interior designer',            'ru' => 'Дизайнер интерьера'],
            'interyer dizayneri'         => ['en' => 'Interior designer',            'ru' => 'Дизайнер интерьера'],
            'interyer & dekor'           => ['en' => 'Interior & decor',             'ru' => 'Интерьер и декор'],
            'memar'                      => ['en' => 'Architect',                    'ru' => 'Архитектор'],
            'memar & layihələndirmə'     => ['en' => 'Architect & design',           'ru' => 'Архитектор и проектирование'],
            'kafel & santexnika'         => ['en' => 'Tiling & plumbing',            'ru' => 'Плитка и сантехника'],
            'kafel & mozaika ustası'     => ['en' => 'Tile & mosaic installer',      'ru' => 'Мастер плитки и мозаики'],
            'kafel & dekor ustası'       => ['en' => 'Tile & decor installer',       'ru' => 'Мастер плитки и декора'],
            'kafel & hidroizolyasiya'    => ['en' => 'Tiling & waterproofing',       'ru' => 'Плитка и гидроизоляция'],
            'metlax ustası'              => ['en' => 'Metlakh installer',            'ru' => 'Мастер метлаха'],
            'metlax & qranit ustası'     => ['en' => 'Metlakh & granite installer',  'ru' => 'Мастер метлаха и гранита'],
            'suvaqçı'                    => ['en' => 'Plasterer',                    'ru' => 'Штукатур'],
            'dülgər'                     => ['en' => 'Carpenter',                    'ru' => 'Плотник'],
            'qaynaqçı'                   => ['en' => 'Welder',                       'ru' => 'Сварщик'],
            'dam ustası'                 => ['en' => 'Roofer',                       'ru' => 'Кровельщик'],
            'kondisioner ustası'         => ['en' => 'HVAC technician',              'ru' => 'Мастер кондиционеров'],
        ];

        $key = mb_strtolower(trim($craft));

        if (isset($map[$key][$locale])) {
            return $map[$key][$locale];
        }

        // No mapping found — return original.
        return $craft;
    }
}
