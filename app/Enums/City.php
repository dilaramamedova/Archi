<?php

namespace App\Enums;

/**
 * The single canonical city list for the whole site (registration, cabinets, filters).
 *
 * The enum *value* is a stable slug used by front-end selects and query strings;
 * the *label* is the Azerbaijani name that gets persisted into profile `city`
 * columns and rendered on public pages. `display()` resolves either form, so rows
 * written before this enum existed (raw slugs such as "baku") still render correctly.
 */
enum City: string
{
    case Baku = 'baku';
    case Sumgayit = 'sumgayit';
    case Ganja = 'ganja';
    case Mingachevir = 'mingachevir';
    case Shirvan = 'shirvan';
    case Sheki = 'sheki';
    case Lankaran = 'lankaran';
    case Nakhchivan = 'nakhchivan';
    case Quba = 'quba';
    case Khirdalan = 'khirdalan';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Baku => 'Bakı',
            self::Sumgayit => 'Sumqayıt',
            self::Ganja => 'Gəncə',
            self::Mingachevir => 'Mingəçevir',
            self::Shirvan => 'Şirvan',
            self::Sheki => 'Şəki',
            self::Lankaran => 'Lənkəran',
            self::Nakhchivan => 'Naxçıvan',
            self::Quba => 'Quba',
            self::Khirdalan => 'Xırdalan',
            self::Other => 'Digər',
        };
    }

    /**
     * Slug => Azerbaijani label, in display order ("Digər" last).
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $city): array => [$city->value => $city->label()])
            ->all();
    }

    /**
     * Labels only — for selects that submit the label as their value.
     *
     * @return list<string>
     */
    public static function labels(): array
    {
        return array_values(self::options());
    }

    /**
     * Every value the app accepts on input: slugs plus Azerbaijani labels.
     *
     * @return list<string>
     */
    public static function acceptedValues(): array
    {
        return array_merge(array_keys(self::options()), self::labels());
    }

    /** Resolve a slug or an Azerbaijani label (case-insensitive) to a case. */
    public static function resolve(?string $value): ?self
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $needle = mb_strtolower($value);

        foreach (self::cases() as $city) {
            if ($needle === $city->value || $needle === mb_strtolower($city->label())) {
                return $city;
            }
        }

        return null;
    }

    /** Canonical storage form for a user-supplied value. */
    public static function canonical(?string $value): ?string
    {
        return self::resolve($value)?->label();
    }

    /** Render a stored value; unknown values are passed through untouched. */
    public static function display(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return self::resolve($value)?->label() ?? $value;
    }
}
