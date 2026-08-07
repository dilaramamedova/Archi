<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SellController extends Controller
{
    public function index(Request $request)
    {
        // Posting products is a seller capability: masters/buyers are sent to the
        // business registration page with an explanatory (localized) message.
        if ($guard = $this->guardNonSellers($request)) {
            return $guard;
        }

        // Pull top-level product categories from the database for the category dropdown.
        // Fall back to the hardcoded translation keys if no categories exist yet.
        $dbCategories = Category::roots()->active()->ordered()->get();

        // Uniform shape for the select: [['id' => int|null, 'name' => string], …].
        // Fallback labels carry id=null so the form never submits a fake category_id.
        if ($dbCategories->isNotEmpty()) {
            $categories = $dbCategories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->all();
        } else {
            $categories = array_map(fn ($label) => ['id' => null, 'name' => $label], [
                t('sell.categories.tiles'),
                t('sell.categories.paint'),
                t('sell.categories.plumbing'),
                t('sell.categories.electric'),
                t('sell.categories.laminate'),
                t('sell.categories.building'),
                t('sell.categories.decor'),
            ]);
        }

        return view('pages.sell', compact('categories'));
    }

    /**
     * Store a new product listing submitted via the /sell form.
     * Requires authentication. Product is created with is_approved=false (pending admin review).
     */
    public function store(Request $request)
    {
        if ($this->guardNonSellers($request)) {
            return response()->json([
                'success' => false,
                'message' => t('sell.sellers_only'),
                'redirect' => route('business.register'),
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'old_price' => 'nullable|numeric|min:0',
            'condition' => 'nullable|string|in:new,used',
            'description' => 'nullable|string|max:5000',
            'image' => 'nullable|image|max:5120', // 5MB max
        ]);

        $locale = app()->getLocale();

        // Generate a unique slug from the product name
        $baseSlug = Str::slug($validated['name']);
        if (empty($baseSlug)) {
            $baseSlug = 'product';
        }
        $slug = $baseSlug;
        $counter = 1;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        $product = Product::create([
            'user_id' => $request->user()->id,
            'category_id' => $validated['category_id'] ?? null,
            'name' => [$locale => $validated['name']],
            'description' => [$locale => $validated['description'] ?? ''],
            'slug' => $slug,
            'price' => $validated['price'],
            'old_price' => $validated['old_price'] ?? null,
            'condition' => $validated['condition'] ?? 'new',
            'stock' => 1,
            'is_visible' => true,
            'is_approved' => false, // Requires admin approval
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            ProductImage::create([
                'product_id' => $product->id,
                'path' => $path,
                'is_main' => true,
                'sort_order' => 0,
            ]);
        }

        return response()->json([
            'success' => true,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
            ],
        ]);
    }

    /**
     * Authenticated non-sellers (buyers, masters) may not post products: they are
     * redirected to business registration with a localized flash message. Guests
     * and sellers/admins pass through (null).
     */
    private function guardNonSellers(Request $request): ?RedirectResponse
    {
        $user = $request->user();

        if ($user && ! in_array($user->role, [UserRole::Seller, UserRole::Admin], true)) {
            return redirect()
                ->route('business.register')
                ->with('flash_error', t('sell.sellers_only'));
        }

        return null;
    }
}
