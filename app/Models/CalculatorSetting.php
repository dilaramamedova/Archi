<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * Scalar knobs of the calculator: contingency percent, area bounds, free lift floors,
 * vehicle capacities, default coat counts, the "-den" prefix switch, the detailed
 * calculator URL. Values are stored as strings and cast on read through type, which
 * keeps one table for numbers, booleans and strings alike.
 */
class CalculatorSetting extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    public array $translatable = ['description'];

    public function getTypedValueAttribute(): mixed
    {
        return static::cast($this->value, $this->type);
    }

    public static function cast(?string $value, string $type): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOL),
            'string' => $value,
            default => (float) $value,
        };
    }
}
