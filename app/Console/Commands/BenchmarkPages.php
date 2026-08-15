<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Query count and wall time per storefront route.
 *
 * Page speed at catalog scale is decided by how many round trips a request
 * makes, not by how fast any one of them is, and a query count is the one
 * number that stays honest on a laptop with fifty products *and* on production
 * with sixty thousand. Run it before and after a change:
 *
 *   php artisan bench:pages
 *   php artisan bench:pages --warm   (second pass, caches primed)
 *
 * A route whose count grows with the number of rows on the page is an N+1;
 * that is what this is for.
 */
class BenchmarkPages extends Command
{
    protected $signature = 'bench:pages
        {--warm : Hit each route twice and report the second run, so cached pages are measured warm}
        {--slow=20 : Log queries slower than this many milliseconds}';

    protected $description = 'Report query count and duration for the main storefront routes';

    /** @var list<string> */
    private const ROUTES = [
        '/',
        '/catalog',
        '/catalog?sort=cheap',
        '/catalog?sort=new',
        '/catalog?min_price=10&max_price=500',
        '/catalog?in_stock=1&free_delivery=1',
        '/search?q=laminat',
        '/api/search?q=lam',
        '/specialists',
        '/blog',
    ];

    public function handle(): int
    {
        $rows = [];

        foreach (self::ROUTES as $route) {
            if ($this->option('warm')) {
                $this->measure($route);
            }

            $rows[] = $this->measure($route);
        }

        $this->table(['Route', 'Queries', 'Time (ms)', 'Slowest query (ms)'], $rows);

        $total = array_sum(array_map(fn ($r) => (int) $r[1], $rows));
        $this->newLine();
        $this->line('Total queries across '.count($rows)." routes: <options=bold>{$total}</>");

        return self::SUCCESS;
    }

    /**
     * @return array{0: string, 1: int, 2: string, 3: string}
     */
    private function measure(string $route): array
    {
        $queries = [];

        DB::flushQueryLog();
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->time;
        });

        $started = microtime(true);

        $kernel = app(Kernel::class);
        $response = $kernel->handle(Request::create($route, 'GET'));

        $elapsed = (microtime(true) - $started) * 1000;

        $status = $response->getStatusCode();
        $label = $status === 200 ? $route : "{$route}  [{$status}]";

        return [
            $label,
            count($queries),
            number_format($elapsed, 1),
            $queries === [] ? '—' : number_format(max($queries), 1),
        ];
    }
}
