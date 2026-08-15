<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Removes everything ScaleSeeder created.
 *
 * The seeder marks its rows with a `bench-` slug and email prefix precisely so
 * they can be taken out again without touching real content. Products, images,
 * attribute values and reviews go with their owners through the existing
 * cascade constraints.
 *
 *   php artisan bench:purge
 */
class PurgeBenchmarkData extends Command
{
    protected $signature = 'bench:purge {--force : Skip the confirmation}';

    protected $description = 'Delete the rows created by ScaleSeeder';

    public function handle(): int
    {
        $products = DB::table('products')->where('slug', 'like', 'bench-%')->count();
        $users = DB::table('users')->where('email', 'like', 'bench-%')->count();

        if ($products === 0 && $users === 0) {
            $this->info('Nothing to purge.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Delete {$products} products and {$users} users?")) {
            return self::SUCCESS;
        }

        // Reviews first: they are polymorphic, so no foreign key cascades them.
        $this->info('Removing benchmark reviews…');
        DB::table('reviews')->where('comment', 'like', 'bench-%')->delete();

        // product_images, product_attribute_values and the pivots all cascade
        // from products, and products cascade from users.
        $this->info('Removing benchmark products…');
        DB::table('products')->where('slug', 'like', 'bench-%')->delete();

        $this->info('Removing benchmark users…');
        DB::table('users')->where('email', 'like', 'bench-%')->delete();

        $this->info('Done.');

        return self::SUCCESS;
    }
}
