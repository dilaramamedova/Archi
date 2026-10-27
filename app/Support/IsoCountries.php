<?php

declare(strict_types=1);

namespace App\Support;

/**
 * ISO 3166-1 alpha-2 country codes, with names in the visitor's language.
 *
 * The codes are a fixed list here rather than derived at runtime: PHP's intl
 * extension can name a region but cannot enumerate the official set, and a country
 * dictionary that silently changes with the ICU version is not one a filter can
 * rely on. Names come from intl, so az / ru / en are the CLDR translations and
 * nobody has to maintain 249 × 3 strings.
 */
final class IsoCountries
{
    /** @var list<string> */
    public const CODES = [
        'AD', 'AE', 'AF', 'AG', 'AI', 'AL', 'AM', 'AO', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AW', 'AX', 'AZ',
        'BA', 'BB', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BL', 'BM', 'BN', 'BO', 'BQ', 'BR', 'BS',
        'BT', 'BV', 'BW', 'BY', 'BZ', 'CA', 'CC', 'CD', 'CF', 'CG', 'CH', 'CI', 'CK', 'CL', 'CM', 'CN',
        'CO', 'CR', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ', 'DE', 'DJ', 'DK', 'DM', 'DO', 'DZ', 'EC', 'EE',
        'EG', 'EH', 'ER', 'ES', 'ET', 'FI', 'FJ', 'FK', 'FM', 'FO', 'FR', 'GA', 'GB', 'GD', 'GE', 'GF',
        'GG', 'GH', 'GI', 'GL', 'GM', 'GN', 'GP', 'GQ', 'GR', 'GS', 'GT', 'GU', 'GW', 'GY', 'HK', 'HM',
        'HN', 'HR', 'HT', 'HU', 'ID', 'IE', 'IL', 'IM', 'IN', 'IO', 'IQ', 'IR', 'IS', 'IT', 'JE', 'JM',
        'JO', 'JP', 'KE', 'KG', 'KH', 'KI', 'KM', 'KN', 'KP', 'KR', 'KW', 'KY', 'KZ', 'LA', 'LB', 'LC',
        'LI', 'LK', 'LR', 'LS', 'LT', 'LU', 'LV', 'LY', 'MA', 'MC', 'MD', 'ME', 'MF', 'MG', 'MH', 'MK',
        'ML', 'MM', 'MN', 'MO', 'MP', 'MQ', 'MR', 'MS', 'MT', 'MU', 'MV', 'MW', 'MX', 'MY', 'MZ', 'NA',
        'NC', 'NE', 'NF', 'NG', 'NI', 'NL', 'NO', 'NP', 'NR', 'NU', 'NZ', 'OM', 'PA', 'PE', 'PF', 'PG',
        'PH', 'PK', 'PL', 'PM', 'PN', 'PR', 'PS', 'PT', 'PW', 'PY', 'QA', 'RE', 'RO', 'RS', 'RU', 'RW',
        'SA', 'SB', 'SC', 'SD', 'SE', 'SG', 'SH', 'SI', 'SJ', 'SK', 'SL', 'SM', 'SN', 'SO', 'SR', 'SS',
        'ST', 'SV', 'SX', 'SY', 'SZ', 'TC', 'TD', 'TF', 'TG', 'TH', 'TJ', 'TK', 'TL', 'TM', 'TN', 'TO',
        'TR', 'TT', 'TV', 'TW', 'TZ', 'UA', 'UG', 'UM', 'US', 'UY', 'UZ', 'VA', 'VC', 'VE', 'VG', 'VI',
        'VN', 'VU', 'WF', 'WS', 'YE', 'YT', 'ZA', 'ZM', 'ZW',
    ];

    public static function isValid(?string $code): bool
    {
        return $code !== null && in_array($code, self::CODES, true);
    }

    public static function name(string $code, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        if (! class_exists(\Locale::class)) {
            return $code;
        }

        $name = \Locale::getDisplayRegion('-'.$code, $locale);

        return is_string($name) && $name !== '' ? $name : $code;
    }

    /**
     * Code => name, sorted by name in the given locale.
     *
     * @return array<string, string>
     */
    public static function options(?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $out = [];

        foreach (self::CODES as $code) {
            $out[$code] = self::name($code, $locale);
        }

        if (class_exists(\Collator::class)) {
            $collator = new \Collator($locale);
            uasort($out, fn (string $a, string $b) => $collator->compare($a, $b));
        } else {
            asort($out, SORT_NATURAL | SORT_FLAG_CASE);
        }

        return $out;
    }
}
