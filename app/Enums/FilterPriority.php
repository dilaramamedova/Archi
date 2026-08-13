<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\TranslatesLabels;

enum FilterPriority: string
{
    use TranslatesLabels;

    case Top = 'top';
    case Secondary = 'secondary';
    case Advanced = 'advanced';

    public function label(): string
    {
        return static::translatedLabel('admin-catalog.filter_priority.'.$this->value, match ($this) {
            self::Top => 'Top (standart olaraq görünür)',
            self::Secondary => 'Əlavə',
            self::Advanced => 'Genişləndirilmiş',
        });
    }
}
