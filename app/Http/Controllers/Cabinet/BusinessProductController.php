<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BusinessProductController extends Controller
{
    /** Stock quantity at or below which a product counts as "running low". */
    public const LOW_STOCK_THRESHOLD = 10;

    /** Maximum number of products a single seller may own. Admins are exempt. */
    public const MAX_PRODUCTS_PER_SELLER = 5;

    // ---------------------------------------------------------------- pages

    public function index(Request $request)
    {
        app()->setLocale(session('locale', config('app.locale')));

        // Eloquent builder (not the HasMany relation) so SearchService can decorate it.
        $query = Product::query()->where('user_id', $request->user()->id)->with(['images', 'category']);

        // `name` is a JSON column, so a plain LIKE would be case-sensitive.
        if ($search = trim((string) $request->query('q'))) {
            $query = SearchService::buildSellerProductQuery($query, $search);
        }

        if ($category = $request->query('category')) {
            $query->where('category_id', $category);
        }

        $query = match ($request->query('status')) {
            'published' => $query->where('is_approved', true)->where('is_visible', true),
            'pending' => $query->pendingReview()->where('is_visible', true),
            'rejected' => $query->rejected(),
            'draft' => $query->where('is_visible', false),
            default => $query,
        };

        $products = $query->latest()->paginate(15)->withQueryString();
        $categories = Category::roots()->active()->ordered()->get();

        $counts = [
            'all' => $request->user()->products()->count(),
            'published' => $request->user()->products()->where('is_approved', true)->where('is_visible', true)->count(),
            'pending' => $request->user()->products()->pendingReview()->where('is_visible', true)->count(),
            'rejected' => $request->user()->products()->rejected()->count(),
        ];

        $quota = $this->productQuota($request->user());

        return view('pages.business-profile-products', compact('products', 'categories', 'counts', 'quota'));
    }

    public function create(Request $request)
    {
        app()->setLocale(session('locale', config('app.locale')));

        return view('pages.business-product-edit', [
            'product' => null,
            'categories' => Category::roots()->active()->ordered()->get(),
            'brands' => Brand::orderBy('name')->get(),
            'quota' => $this->productQuota($request->user()),
        ]);
    }

    public function edit(Request $request, Product $product)
    {
        $this->authorizeProduct($request, $product);
        app()->setLocale(session('locale', config('app.locale')));

        return view('pages.business-product-edit', [
            'product' => $product->load('images'),
            'categories' => Category::roots()->active()->ordered()->get(),
            'brands' => Brand::orderBy('name')->get(),
            // Editing an existing product never consumes quota.
            'quota' => ['limit' => null, 'used' => 0, 'remaining' => null, 'reached' => false],
        ]);
    }

    public function inventory(Request $request)
    {
        app()->setLocale(session('locale', config('app.locale')));

        $base = $request->user()->products();

        $stats = [
            'total' => (clone $base)->count(),
            'low' => (clone $base)->where('stock', '>', 0)->where('stock', '<=', self::LOW_STOCK_THRESHOLD)->count(),
            'out' => (clone $base)->where('stock', '<=', 0)->count(),
        ];

        $query = Product::query()->where('user_id', $request->user()->id)->with(['images']);

        // `name` is a JSON column, so a plain LIKE would be case-sensitive.
        if ($search = trim((string) $request->query('q'))) {
            $query = SearchService::buildSellerProductQuery($query, $search);
        }

        $query = match ($request->query('filter')) {
            'low' => $query->where('stock', '>', 0)->where('stock', '<=', self::LOW_STOCK_THRESHOLD),
            'out' => $query->where('stock', '<=', 0),
            'unpublished' => $query->where(fn ($q) => $q->where('is_visible', false)->orWhere('is_approved', false)),
            default => $query,
        };

        $products = $query->orderBy('stock')->paginate(20)->withQueryString();

        return view('pages.business-inventory', compact('products', 'stats'));
    }

    // ---------------------------------------------------------------- API

    public function store(Request $request): JsonResponse
    {
        $this->guardProductQuota($request->user());

        $validated = $this->validateProduct($request);

        // Publishing requires at least one image; drafts may be saved without.
        if (($validated['publish'] ?? false) && count($request->file('images') ?? []) === 0) {
            throw ValidationException::withMessages([
                'images' => $this->msg('business-product-edit.save.error_image', 'Ən azı 1 şəkil əlavə edin'),
            ]);
        }

        // Re-check the quota under a row lock: the check above is a plain count(), so two
        // concurrent submissions could both pass it and push the seller over the cap.
        $product = DB::transaction(function () use ($request, $validated): Product {
            User::whereKey($request->user()->id)->lockForUpdate()->first();
            $this->guardProductQuota($request->user()->fresh());

            return Product::create([
                ...$this->productAttributes($validated),
                'user_id' => $request->user()->id,
                'slug' => $this->uniqueSlug($validated['name']),
                // Publish → pending admin review; draft → hidden, no review needed yet.
                'is_visible' => ($validated['publish'] ?? false) ? true : false,
                'is_approved' => false,
                'rejected_at' => null,
                'rejection_reason' => null,
            ]);
        });

        $this->syncImages($request, $product);

        return response()->json([
            'success' => true,
            'message' => ($validated['publish'] ?? false)
                ? t('business-cabinet.product_submitted')
                : t('business-cabinet.draft_saved'),
            'redirect' => route('business.profile.products'),
        ]);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $this->authorizeProduct($request, $product);
        $validated = $this->validateProduct($request);

        // A published product must keep at least one image: survivors + new uploads.
        $willBeVisible = ($validated['publish'] ?? false) || $product->is_visible;
        $keptCount = $product->images()
            ->whereIn('id', array_map('intval', (array) $request->input('kept_image_ids', [])))
            ->count();

        if ($willBeVisible && $keptCount + count($request->file('images') ?? []) === 0) {
            throw ValidationException::withMessages([
                'images' => $this->msg('business-product-edit.save.error_image', 'Ən azı 1 şəkil əlavə edin'),
            ]);
        }

        $wasApproved = $product->is_approved;

        $product->update([
            ...$this->productAttributes($validated),
            'is_visible' => ($validated['publish'] ?? false) ? true : $product->is_visible,
            // Substantive edits send the product back to moderation.
            'is_approved' => false,
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);

        $this->syncImages($request, $product);

        return response()->json([
            'success' => true,
            'message' => $wasApproved
                ? t('business-cabinet.product_resubmitted')
                : t('business-cabinet.saved'),
            'redirect' => route('business.profile.products'),
        ]);
    }

    public function toggleVisibility(Request $request, Product $product): JsonResponse
    {
        $this->authorizeProduct($request, $product);

        // Making a product visible is a publish action, so it has to honour the same
        // "at least 1 image" rule as store()/update() — otherwise an imageless draft
        // could be flipped straight into the catalog.
        if (! $product->is_visible && $product->images()->doesntExist()) {
            throw ValidationException::withMessages([
                'images' => $this->msg('business-product-edit.save.error_image', 'Ən azı 1 şəkil əlavə edin'),
            ]);
        }

        $product->update(['is_visible' => ! $product->is_visible]);

        return response()->json(['success' => true, 'is_visible' => $product->is_visible]);
    }

    public function updateStock(Request $request, Product $product): JsonResponse
    {
        $this->authorizeProduct($request, $product);

        $validated = $request->validate([
            'stock' => ['required', 'integer', 'min:0', 'max:1000000'],
        ]);

        $product->update(['stock' => $validated['stock']]);

        return response()->json(['success' => true, 'stock' => $product->stock]);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->authorizeProduct($request, $product);

        foreach ($product->images as $image) {
            if ($image->path && ! str_starts_with($image->path, 'assets/')) {
                Storage::disk('public')->delete($image->path);
            }
        }

        $product->delete();

        return response()->json(['success' => true]);
    }

    // ---------------------------------------------------------------- internals

    /**
     * Translate a UI string, falling back to `$fallback` while the key is still
     * missing from the translations table (t() echoes the key back otherwise).
     */
    private function msg(string $key, string $fallback, array $replace = []): string
    {
        $value = t($key, $replace);

        return is_string($value) && $value !== $key ? $value : strtr($fallback, [':limit' => (string) ($replace['limit'] ?? '')]);
    }

    /**
     * Remaining product allowance for a seller. Admins are never capped, and get
     * a null limit/remaining so the UI can render an "unlimited" state.
     *
     * @return array{limit: int|null, used: int, remaining: int|null, reached: bool}
     */
    private function productQuota(User $user): array
    {
        $used = $user->products()->count();

        if ($user->isAdmin()) {
            return ['limit' => null, 'used' => $used, 'remaining' => null, 'reached' => false];
        }

        $remaining = max(0, self::MAX_PRODUCTS_PER_SELLER - $used);

        return [
            'limit' => self::MAX_PRODUCTS_PER_SELLER,
            'used' => $used,
            'remaining' => $remaining,
            'reached' => $remaining === 0,
        ];
    }

    private function guardProductQuota(User $user): void
    {
        if ($this->productQuota($user)['reached']) {
            throw ValidationException::withMessages([
                'name' => $this->msg(
                    'business-product-edit.limit.reached',
                    'Maksimum :limit məhsul əlavə edə bilərsiniz.',
                    ['limit' => self::MAX_PRODUCTS_PER_SELLER],
                ),
            ]);
        }
    }

    private function validateProduct(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'sku' => ['nullable', 'string', 'max:64'],
            'barcode' => ['nullable', 'string', 'max:64'],
            'price' => ['required', 'numeric', 'min:0'],
            'old_price' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:20'],
            'stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'min_order' => ['nullable', 'integer', 'min:1'],
            'shelf' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:5000'],
            'dimensions' => ['nullable', 'string', 'max:100'],
            'material' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'publish' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'kept_image_ids' => ['nullable', 'array'],
            'kept_image_ids.*' => ['integer'],
        ]);
    }

    private function productAttributes(array $validated): array
    {
        $locale = app()->getLocale();

        return [
            'category_id' => $validated['category_id'],
            'brand_id' => $validated['brand_id'] ?? null,
            'sku' => $validated['sku'] ?? null,
            'name' => [$locale => $validated['name']],
            'description' => [$locale => $validated['description'] ?? ''],
            'price' => $validated['price'],
            'old_price' => $validated['old_price'] ?? null,
            'unit' => $validated['unit'] ?? 'piece',
            'stock' => $validated['stock'],
            'min_order' => $validated['min_order'] ?? 1,
            'specifications' => array_filter([
                'barcode' => $validated['barcode'] ?? null,
                'shelf' => $validated['shelf'] ?? null,
                'dimensions' => $validated['dimensions'] ?? null,
                'material' => $validated['material'] ?? null,
                'color' => $validated['color'] ?? null,
                'country' => $validated['country'] ?? null,
            ]),
        ];
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $i = 1;

        while (Product::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function syncImages(Request $request, Product $product): void
    {
        // Remove images the seller deleted in the form (kept_image_ids = survivors).
        if ($request->has('kept_image_ids') || $request->hasFile('images')) {
            $kept = array_map('intval', (array) $request->input('kept_image_ids', []));

            foreach ($product->images()->whereNotIn('id', $kept)->get() as $stale) {
                if ($stale->path && ! str_starts_with($stale->path, 'assets/')) {
                    Storage::disk('public')->delete($stale->path);
                }
                $stale->delete();
            }
        }

        if ($request->hasFile('images')) {
            $sort = ($product->images()->max('sort_order') ?? -1) + 1;

            foreach ($request->file('images') as $file) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'path' => $file->store('products', 'public'),
                    'is_main' => false,
                    'sort_order' => $sort++,
                ]);
            }
        }

        // Guarantee exactly one main image.
        if ($product->images()->count() > 0 && $product->images()->where('is_main', true)->doesntExist()) {
            $product->images()->orderBy('sort_order')->first()->update(['is_main' => true]);
        }
    }

    private function authorizeProduct(Request $request, Product $product): void
    {
        abort_unless($product->user_id === $request->user()->id, 403);
    }
}
