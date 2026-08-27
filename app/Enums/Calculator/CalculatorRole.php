<?php

namespace App\Enums\Calculator;

/**
 * Master spec audit 2.8 - who may change what. Enforced on the API, not only in the
 * UI: only a sales manager may push manual quote lines or override a unit price.
 */
enum CalculatorRole: string
{
    case Client = 'CLIENT';
    case SalesManager = 'SALES_MANAGER';
    case Admin = 'ADMIN';

    public function canEditLines(): bool
    {
        return $this !== self::Client;
    }

    public function canManageRates(): bool
    {
        return $this === self::Admin;
    }
}
