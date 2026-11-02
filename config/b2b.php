<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Pricing
    |--------------------------------------------------------------------------
    |
    | While this is false /b2b opens straight on the company directory: the
    | pricing hero, the plan cards and the plan lead form are not rendered, and
    | the admin form hides the company "Bazar" (USD / AZN market) field, which
    | only matters for those plans.
    |
    | Set B2B_PRICING=true to bring all of it back — nothing is deleted.
    |
    */

    'pricing' => (bool) env('B2B_PRICING', false),

];
