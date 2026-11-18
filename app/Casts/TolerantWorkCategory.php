<?php

namespace App\Casts;

use App\Enums\Calculator\WorkCategory;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * calculator_work_rates.category as the enum — but a value the enum does not know
 * (a category added in the database before the code) comes back as its plain string
 * instead of throwing. A plain enum cast turned one such row into a 500 on the whole
 * «İş qiymətləri» page (TZ 17.11.2026 §37); now the row shows its raw value and the
 * engine skips it.
 */
class TolerantWorkCategory implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): WorkCategory|string|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        return WorkCategory::tryFrom((string) $value) ?? (string) $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value instanceof WorkCategory) {
            return $value->value;
        }

        return $value === null ? null : (string) $value;
    }
}
