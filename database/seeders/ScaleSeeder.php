<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Models\AttributeOption;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Fills the database to the size production is expected to reach, so query
 * plans can be judged against real cardinality instead of a fifty-row laptop
 * dataset. An index that looks unnecessary at fifty rows is the difference
 * between 8 ms and 8 s at sixty thousand.
 *
 *   php artisan db:seed --class=ScaleSeeder
 *   php artisan db:seed --class=ScaleSeeder -- --products=60000 --users=100000
 *
 * Everything is written with chunked raw inserts: Eloquent would fire model
 * events and hydrate objects for every row, turning a two-minute seed into an
 * hour. Rows are marked with a `bench-` slug prefix so they can be removed
 * again without touching real content.
 */
class ScaleSeeder extends Seeder
{
    private const CHUNK = 2000;

    public int $products = 60000;

    public int $users = 100000;

    public int $reviewsPerHundredProducts = 300;

    public function run(): void
    {
        $this->command?->warn('Seeding at production scale. This is destructive only for rows prefixed "bench-".');

        $sellerIds = $this->seedUsers();
        $this->seedProducts($sellerIds);
        $this->seedReviews();

        $this->command?->info('Done. Run `php artisan bench:pages --warm` to measure.');
    }

    /**
     * @return list<int> ids of the seller accounts products can be attached to
     */
    private function seedUsers(): array
    {
        $existing = DB::table('users')->where('email', 'like', 'bench-%')->count();
        $missing = max(0, $this->users - $existing);

        if ($missing > 0) {
            $this->command?->info("Users: creating {$missing}…");
            // One hash for every generated account: bcrypt is deliberately slow,
            // and hashing a hundred thousand times would dominate the seed.
            $password = Hash::make('password');
            $now = now();
            $bar = $this->command?->getOutput()->createProgressBar($missing);

            for ($offset = 0; $offset < $missing; $offset += self::CHUNK) {
                $rows = [];
                $size = min(self::CHUNK, $missing - $offset);

                for ($i = 0; $i < $size; $i++) {
                    $n = $existing + $offset + $i;
                    // Every tenth account sells, which is roughly the shape of a
                    // marketplace and keeps the seller-products join realistic.
                    $role = $n % 10 === 0 ? 'seller' : 'buyer';

                    $rows[] = [
                        'name' => "Bench User {$n}",
                        'first_name' => 'Bench',
                        'last_name' => "User {$n}",
                        'email' => "bench-{$n}@example.test",
                        'phone' => '+994'.str_pad((string) (500000000 + $n), 9, '0', STR_PAD_LEFT),
                        'password' => $password,
                        'role' => $role,
                        'status' => UserStatus::Active->value,
                        'terms_accepted' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                DB::table('users')->insert($rows);
                $bar?->advance($size);
            }

            $bar?->finish();
            $this->command?->newLine();
        }

        return DB::table('users')
            ->where('email', 'like', 'bench-%')
            ->where('role', 'seller')
            ->limit(2000)
            ->pluck('id')
            ->all();
    }

    private function seedProducts(array $sellerIds): void
    {
        if ($sellerIds === []) {
            $sellerIds = DB::table('users')->limit(1)->pluck('id')->all();
        }

        $existing = DB::table('products')->where('slug', 'like', 'bench-%')->count();
        $missing = max(0, $this->products - $existing);

        if ($missing === 0) {
            $this->command?->info('Products: already at target.');

            return;
        }

        $groupIds = Category::whereNotNull('parent_id')->pluck('id')->all() ?: Category::pluck('id')->all();
        $classIds = SubCategory::pluck('id')->all();
        $brandIds = Brand::pluck('id')->all();
        $optionIds = AttributeOption::inRandomOrder()->limit(400)->pluck('id', 'attribute_id')->all();

        $this->command?->info("Products: creating {$missing}…");
        $bar = $this->command?->getOutput()->createProgressBar($missing);
        $now = now();

        for ($offset = 0; $offset < $missing; $offset += self::CHUNK) {
            $size = min(self::CHUNK, $missing - $offset);
            $products = [];

            for ($i = 0; $i < $size; $i++) {
                $n = $existing + $offset + $i;
                $price = random_int(5, 4000) + random_int(0, 99) / 100;
                $hasOldPrice = $n % 4 === 0;

                $products[] = [
                    'user_id' => $sellerIds[$n % count($sellerIds)],
                    'category_id' => $groupIds ? $groupIds[$n % count($groupIds)] : null,
                    'sub_category_id' => $classIds && $n % 3 !== 0 ? $classIds[$n % count($classIds)] : null,
                    'brand_id' => $brandIds ? $brandIds[$n % count($brandIds)] : null,
                    'name' => json_encode([
                        'az' => 'Bench məhsul '.$n.' '.Str::random(6),
                        'ru' => 'Бенч товар '.$n,
                        'en' => 'Bench product '.$n,
                    ], JSON_UNESCAPED_UNICODE),
                    'description' => json_encode(['az' => 'Test təsviri '.$n], JSON_UNESCAPED_UNICODE),
                    'slug' => "bench-{$n}-".Str::lower(Str::random(6)),
                    'sku' => "BENCH-{$n}",
                    'price' => $price,
                    'old_price' => $hasOldPrice ? $price * 1.3 : null,
                    'stock' => $n % 7 === 0 ? 0 : random_int(1, 500),
                    'sort_order' => $n % 500 === 0 ? random_int(1, 100) : 0,
                    'sold_count' => random_int(0, 300),
                    'is_visible' => true,
                    // A tenth stays unapproved, so the moderation queue and the
                    // "visible slice of a big table" both get exercised.
                    'is_approved' => $n % 10 !== 0,
                    'is_featured' => $n % 25 === 0,
                    'is_sale' => $hasOldPrice,
                    'show_on_homepage' => $n % 250 === 0,
                    'free_delivery' => $n % 5 === 0,
                    'views_count' => random_int(0, 20000),
                    'created_at' => $now->copy()->subMinutes($n % 500000),
                    'updated_at' => $now,
                ];
            }

            DB::table('products')->insert($products);

            $ids = DB::table('products')
                ->where('slug', 'like', 'bench-%')
                ->orderByDesc('id')
                ->limit($size)
                ->pluck('id')
                ->all();

            $this->seedImages($ids, $now);
            $this->seedAttributeValues($ids, $optionIds, $now);

            $bar?->advance($size);
        }

        $bar?->finish();
        $this->command?->newLine();
    }

    private function seedImages(array $productIds, $now): void
    {
        $rows = [];

        foreach ($productIds as $id) {
            // Three images each — the catalog only ever renders the main one, so
            // this is what makes a missing eager load visible in the numbers.
            foreach ([true, false, false] as $index => $isMain) {
                $rows[] = [
                    'product_id' => $id,
                    // Must be a file that actually exists in public/assets — a made-up
                    // placeholder name meant every seeded card 404'd its image and the
                    // catalog rendered rows of empty grey boxes.
                    'path' => 'assets/product-marble-tile.png',
                    'is_main' => $isMain,
                    'sort_order' => $index,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, self::CHUNK) as $chunk) {
            DB::table('product_images')->insert($chunk);
        }
    }

    /**
     * Structured attribute values, so the classifier filters have something to
     * filter and product_attribute_values reaches a realistic row count
     * (several per product, i.e. hundreds of thousands overall).
     */
    private function seedAttributeValues(array $productIds, array $optionIds, $now): void
    {
        if ($optionIds === []) {
            return;
        }

        $pairs = [];
        foreach ($optionIds as $attributeId => $optionId) {
            $pairs[] = [$attributeId, $optionId];
        }

        $rows = [];
        foreach ($productIds as $index => $id) {
            for ($k = 0; $k < 4; $k++) {
                [$attributeId, $optionId] = $pairs[($index + $k) % count($pairs)];

                $rows[] = [
                    'product_id' => $id,
                    'attribute_id' => $attributeId,
                    'attribute_option_id' => $optionId,
                    'value_numeric' => random_int(1, 500),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, self::CHUNK) as $chunk) {
            DB::table('product_attribute_values')->insert($chunk);
        }
    }

    /**
     * Reviews drive the denormalized counters, so the seed writes the aggregates
     * directly rather than saving Review models one at a time and letting the
     * observer recount per row.
     */
    private function seedReviews(): void
    {
        $target = (int) ($this->products / 100 * $this->reviewsPerHundredProducts);
        $existing = DB::table('reviews')->where('comment', 'like', 'bench-%')->count();
        $missing = max(0, $target - $existing);

        if ($missing === 0) {
            return;
        }

        $productIds = DB::table('products')->where('slug', 'like', 'bench-%')->limit(20000)->pluck('id')->all();
        $userIds = DB::table('users')->where('email', 'like', 'bench-%')->limit(20000)->pluck('id')->all();

        if ($productIds === [] || $userIds === []) {
            return;
        }

        $this->command?->info("Reviews: creating up to {$missing}…");
        $now = now();
        $seen = [];

        for ($offset = 0; $offset < $missing; $offset += self::CHUNK) {
            $rows = [];
            $size = min(self::CHUNK, $missing - $offset);

            for ($i = 0; $i < $size; $i++) {
                $n = $offset + $i;
                $productId = $productIds[$n % count($productIds)];
                $userId = $userIds[($n * 7 + intdiv($n, count($productIds))) % count($userIds)];

                // reviews has UNIQUE(user_id, reviewable_type, reviewable_id).
                $key = "{$userId}:{$productId}";
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;

                $rows[] = [
                    'user_id' => $userId,
                    'reviewable_type' => Product::class,
                    'reviewable_id' => $productId,
                    'rating' => random_int(1, 5),
                    'comment' => 'bench-review '.$n,
                    'status' => $n % 8 === 0 ? 'pending' : 'approved',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($rows !== []) {
                DB::table('reviews')->insertOrIgnore($rows);
            }
        }

        $this->command?->info('Reviews: recomputing product aggregates…');

        // One set-based rollup instead of the observer's per-review recount.
        DB::statement('
            UPDATE products p
            JOIN (
                SELECT reviewable_id, COUNT(*) c, AVG(rating) a
                FROM reviews
                WHERE reviewable_type = ? AND status = ?
                GROUP BY reviewable_id
            ) agg ON agg.reviewable_id = p.id
            SET p.reviews_count = agg.c, p.rating_avg = ROUND(agg.a, 2)
        ', [Product::class, 'approved']);
    }
}
