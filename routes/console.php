<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled maintenance
|--------------------------------------------------------------------------
|
| Production needs one cron entry for all of this:
|
|   * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
|
| Without it the site still serves every page; only the numbers below go
| stale, which is the failure mode we want from a background task.
*/

// Product view counts are buffered in the cache to keep a page view from
// writing to the hottest row in the products table — see ProductViewCounter.
Schedule::command('products:flush-views')
    ->everyMinute()
    ->withoutOverlapping();
