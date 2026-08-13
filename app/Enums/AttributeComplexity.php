<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\TranslatesLabels;

enum AttributeComplexity: string
{
    use TranslatesLabels;

    case Basic = 'basic';
    case Professional = 'professional';

    public function label(): string
    {
        return static::translatedLabel('admin-catalog.complexity.'.$this->value, match ($this) {
            self::Basic => 'Əsas',
            self::Professional => 'Peşəkar',
        });
    }
}
