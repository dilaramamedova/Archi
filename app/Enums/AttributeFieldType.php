<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\TranslatesLabels;

enum AttributeFieldType: string
{
    use TranslatesLabels;

    case Dropdown = 'dropdown';
    case Multiselect = 'multiselect';
    case Boolean = 'boolean';
    case Numeric = 'numeric';
    case Decimal = 'decimal';
    case Range = 'range';
    case Text = 'text';
    case Textarea = 'textarea';

    public function label(): string
    {
        return static::translatedLabel('admin-catalog.field_type.'.$this->value, match ($this) {
            self::Dropdown => 'Seçim (dropdown)',
            self::Multiselect => 'Çoxlu seçim',
            self::Boolean => 'Bəli / Xeyr',
            self::Numeric => 'Rəqəm',
            self::Decimal => 'Onluq rəqəm',
            self::Range => 'Aralıq (min–maks)',
            self::Text => 'Mətn',
            self::Textarea => 'Uzun mətn',
        });
    }

    /**
     * Field types whose allowed values live in attribute_options.
     */
    public function hasOptions(): bool
    {
        return match ($this) {
            self::Dropdown, self::Multiselect => true,
            default => false,
        };
    }
}
