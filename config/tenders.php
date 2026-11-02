<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Paid access
    |--------------------------------------------------------------------------
    |
    | While this is false the tender section is free: every visitor sees the
    | board and the admin's tenders directly, with no package and no lock card.
    |
    | Set TENDERS_PAID_ACCESS=true to turn the paid gate back on — the whole
    | access / request / subscription flow (App\Support\TenderAccess, the
    | request form, the admin "Girişi aktivləşdir" action) stays in place and
    | simply starts enforcing again. Nothing is deleted when it is free.
    |
    */

    'paid_access' => (bool) env('TENDERS_PAID_ACCESS', false),

    /*
    |--------------------------------------------------------------------------
    | Budget sort rates
    |--------------------------------------------------------------------------
    |
    | AZN value of one unit of each tender currency. Only used to order the
    | board by "Büdcə (yüksək)" across currencies — budgets are always shown in
    | their own currency, so approximate rates are enough.
    |
    */

    'budget_rates' => [
        'AZN' => 1.0,
        'USD' => (float) env('TENDERS_RATE_USD', 1.70),
        'EUR' => (float) env('TENDERS_RATE_EUR', 1.95),
    ],

];
