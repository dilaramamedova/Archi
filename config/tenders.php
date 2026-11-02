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

];
