<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Composite indexes for the storefront's real access patterns.
 *
 * Every index here was chosen against an actual query in the codebase, not
 * added speculatively — an index that no query uses still costs on every write.
 * The leading `is_visible, is_approved` pair is low-cardinality on its own, but
 * every catalog query starts with it (Product::visible()->approved()), so it is
 * the correct prefix: MySQL walks the composite from the left and lands on the
 * selective column (sub_category_id / category_id) inside the visible slice.
 *
 * `sort_rank` replaces the un-indexable `ORDER BY sort_order = 0` expression the
 * catalog used for its default listing. Same semantics — 0 means "an admin
 * dragged this product into position", and those lead — but as a stored column
 * it can sit inside an index, so the default catalog page no longer filesorts
 * the whole table.
 */
return new class extends Migration
{
    public function up(): void
    {
        $mysql = DB::getDriverName() === 'mysql';

        // ── products ──────────────────────────────────────────────────────
        if ($mysql && ! $this->hasColumn('products', 'sort_rank')) {
            DB::statement(
                'ALTER TABLE products ADD COLUMN sort_rank TINYINT UNSIGNED
                 AS (CASE WHEN sort_order = 0 THEN 1 ELSE 0 END) STORED AFTER sort_order'
            );
        }

        // MySQL caps index names at 64 characters, and the composites below are
        // long enough that the generated names would overflow — each one is
        // named explicitly with a `p_` prefix.
        Schema::table('products', function (Blueprint $table) {
            // Default catalog listing: pinned products first, then popularity.
            $this->index($table, 'products', ['is_visible', 'is_approved', 'sort_rank', 'sort_order', 'views_count'], 'p_live_rank_index');

            // Class / group / section drill-down, with price available for both
            // the price filter and the cheap/expensive sorts.
            $this->index($table, 'products', ['is_visible', 'is_approved', 'sub_category_id', 'price'], 'p_live_class_price_index');
            $this->index($table, 'products', ['is_visible', 'is_approved', 'category_id', 'price'], 'p_live_category_price_index');
            $this->index($table, 'products', ['is_visible', 'is_approved', 'brand_id', 'price'], 'p_live_brand_price_index');

            // "Ən yeni" sort and the newest-first fallbacks.
            $this->index($table, 'products', ['is_visible', 'is_approved', 'created_at'], 'p_live_created_index');

            // Homepage grids: sale/featured AND show_on_homepage.
            $this->index($table, 'products', ['is_visible', 'is_approved', 'show_on_homepage', 'is_sale'], 'p_live_home_sale_index');
            $this->index($table, 'products', ['is_visible', 'is_approved', 'show_on_homepage', 'is_featured'], 'p_live_home_featured_index');

            // Price-range aggregate for the catalog slider (MIN/MAX scan).
            $this->index($table, 'products', ['is_visible', 'is_approved', 'price'], 'p_live_price_index');

            // Seller cabinet: a seller's own products, moderation state first.
            $this->index($table, 'products', ['user_id', 'is_approved', 'created_at'], 'p_seller_moderation_index');
        });

        // ── reviews ───────────────────────────────────────────────────────
        // The only existing index is UNIQUE(user_id, reviewable_type,
        // reviewable_id) — user_id leftmost, so "approved reviews of product X"
        // could not use it and scanned the table.
        Schema::table('reviews', function (Blueprint $table) {
            $this->index($table, 'reviews', ['reviewable_type', 'reviewable_id', 'status']);
            $this->index($table, 'reviews', ['status', 'created_at']);
        });

        // ── product_attribute_values (EAV filters) ────────────────────────
        Schema::table('product_attribute_values', function (Blueprint $table) {
            // Option filter: attribute + option -> product ids (covering).
            $this->index($table, 'product_attribute_values', ['attribute_id', 'attribute_option_id', 'product_id'], 'pav_attr_option_product_index');
            // Numeric and range filters.
            $this->index($table, 'product_attribute_values', ['attribute_id', 'value_numeric'], 'pav_attr_numeric_index');
            $this->index($table, 'product_attribute_values', ['attribute_id', 'value_min', 'value_max'], 'pav_attr_range_index');
        });

        // ── classifier tree ───────────────────────────────────────────────
        Schema::table('sub_categories', function (Blueprint $table) {
            $this->index($table, 'sub_categories', ['category_id', 'is_active', 'sort_order']);
            $this->index($table, 'sub_categories', ['is_active', 'sort_order']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $this->index($table, 'categories', ['parent_id', 'is_active', 'sort_order']);
            $this->index($table, 'categories', ['is_active', 'show_on_home', 'sort_order']);
        });

        // ── users ─────────────────────────────────────────────────────────
        // role and status are indexed separately; every admin badge and every
        // withRole() scope filters on both at once.
        Schema::table('users', function (Blueprint $table) {
            $this->index($table, 'users', ['role', 'status']);
        });

        // ── specialist profiles (homepage + catalog rail) ─────────────────
        Schema::table('specialist_profiles', function (Blueprint $table) {
            $this->index($table, 'specialist_profiles', ['is_featured', 'show_on_homepage']);
        });

        // ── product images: "the main image of product X" ──────────────────
        Schema::table('product_images', function (Blueprint $table) {
            $this->index($table, 'product_images', ['product_id', 'is_main', 'sort_order']);
        });

        // ── chrome: menus, banners, blog ──────────────────────────────────
        Schema::table('menu_items', function (Blueprint $table) {
            $this->index($table, 'menu_items', ['location', 'parent_id', 'is_active', 'sort_order'], 'menu_items_location_lookup_index');
        });

        Schema::table('banners', function (Blueprint $table) {
            $this->index($table, 'banners', ['position', 'is_active', 'sort_order']);
        });

        Schema::table('blog_posts', function (Blueprint $table) {
            $this->index($table, 'blog_posts', ['is_published', 'published_at']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $this->index($table, 'orders', ['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        $drop = [
            'products' => [
                'p_live_rank_index',
                'p_live_class_price_index',
                'p_live_category_price_index',
                'p_live_brand_price_index',
                'p_live_created_index',
                'p_live_home_sale_index',
                'p_live_home_featured_index',
                'p_live_price_index',
                'p_seller_moderation_index',
            ],
            'reviews' => [
                'reviews_reviewable_type_reviewable_id_status_index',
                'reviews_status_created_at_index',
            ],
            'product_attribute_values' => [
                'pav_attr_option_product_index',
                'pav_attr_numeric_index',
                'pav_attr_range_index',
            ],
            'sub_categories' => [
                'sub_categories_category_id_is_active_sort_order_index',
                'sub_categories_is_active_sort_order_index',
            ],
            'categories' => [
                'categories_parent_id_is_active_sort_order_index',
                'categories_is_active_show_on_home_sort_order_index',
            ],
            'users' => ['users_role_status_index'],
            'specialist_profiles' => ['specialist_profiles_is_featured_show_on_homepage_index'],
            'product_images' => ['product_images_product_id_is_main_sort_order_index'],
            'menu_items' => ['menu_items_location_lookup_index'],
            'banners' => ['banners_position_is_active_sort_order_index'],
            'blog_posts' => ['blog_posts_is_published_published_at_index'],
            'orders' => ['orders_user_id_created_at_index'],
        ];

        foreach ($drop as $table => $indexes) {
            Schema::table($table, function (Blueprint $blueprint) use ($table, $indexes) {
                foreach ($indexes as $index) {
                    if ($this->hasIndex($table, $index)) {
                        $blueprint->dropIndex($index);
                    }
                }
            });
        }

        if (DB::getDriverName() === 'mysql' && $this->hasColumn('products', 'sort_rank')) {
            DB::statement('ALTER TABLE products DROP COLUMN sort_rank');
        }
    }

    /**
     * Add an index only when an identically-named one is not already present, so
     * the migration is safe to re-run against a partially-migrated database.
     */
    private function index(Blueprint $table, string $tableName, array $columns, ?string $name = null): void
    {
        $name ??= $tableName.'_'.implode('_', $columns).'_index';

        if (! $this->hasIndex($tableName, $name)) {
            $table->index($columns, $name);
        }
    }

    private function hasIndex(string $table, string $name): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $index) => $index['name'] === $name);
    }

    private function hasColumn(string $table, string $column): bool
    {
        return Schema::hasColumn($table, $column);
    }
};
