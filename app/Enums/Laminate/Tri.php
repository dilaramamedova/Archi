<?php

declare(strict_types=1);

namespace App\Enums\Laminate;

use App\Enums\Concerns\TranslatesLabels;

/**
 * The yes / no / unknown answer the laminate TZ uses for every "does it have X"
 * question (§3: water protection, floor heating, integrated underlay, warranty).
 *
 * `unknown` is a real answer, not an empty one: it is the column default, it is
 * what the form starts on, and it is never shown as "no". A buyer filtering for
 * water-protected laminate must not get a product whose seller simply did not say.
 */
enum Tri: string
{
    use TranslatesLabels;

    case Yes = 'yes';
    case No = 'no';
    case Unknown = 'unknown';

    public function label(): string
    {
        return self::translatedLabel('laminate.tri.'.$this->value, match ($this) {
            self::Yes => 'Bəli',
            self::No => 'Xeyr',
            self::Unknown => 'Göstərilməyib',
        });
    }

    /**
     * Lenient parse for form input: an untouched control posts nothing or an empty
     * string, and both mean "not stated" — never "no".
     */
    public static function fromInput(mixed $raw): ?self
    {
        if ($raw === null || $raw === '') {
            return self::Unknown;
        }

        return is_string($raw) ? self::tryFrom($raw) : null;
    }
}
