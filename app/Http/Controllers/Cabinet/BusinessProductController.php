<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BusinessProductController extends Controller
{
    /** Stock quantity at or below which a product counts as "running low". */
    public const LOW_STOCK_THRESHOLD = 10;

    // ---------------------------------------------------------------- pages

    public function index(Request $request)
    {
        app()->setLocale(session('locale', config('app.locale')));

        $query = $request->user()->products()->with(['images', 'category']);

        if ($search = trim((string) $request->query('q'))) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%"));
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

        return view('pages.business-profile-products', compact('products', 'categories', 'counts'));
    }

    public function create()
    {
        app()->setLocale(session('locale', config('app.locale')));

        return view('pages.business-product-edit', [
            'product' => null,
            'categories' => Category::roots()->active()->ordered()->get(),
            'brands' => Brand::orderBy('name')->get(),
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

        $query = $request->user()->products()->with(['images']);

        if ($search = trim((string) $request->query('q'))) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%"));
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
        $validated = $this->validateProduct($request);

        $product = Product::create([
            ...$this->productAttributes($validated),
            'user_id' => $request->user()->id,
            'slug' => $this->uniqueSlug($validated['name']),
            // Publish → pending admin review; draft → hidden, no review needed yet.
            'is_visible' => ($validated['publish'] ?? false) ? true : false,
            'is_approved' => false,
            'rejected_at' => null,
            'rejection_reason' => null,
        ]);

        $this->syncImages($request, $product);

        return response()->json([
            'success' => true,
            'message' => ($validated['publish'] ?? false)
                ? __('business-cabinet.product_submitted')
                : __('business-cabinet.draft_saved'),
            'redirect' => route('business.profile.products'),
        ]);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $this->authorizeProduct($request, $product);
        $validated = $this->validateProduct($request);

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
                ? __('business-cabinet.product_resubmitted')
                : __('business-cabinet.saved'),
            'redirect' => route('business.profile.products'),
        ]);
    }

    public function toggleVisibility(Request $request, Product $product): JsonResponse
    {
        $this->authorizeProduct($request, $product);
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
