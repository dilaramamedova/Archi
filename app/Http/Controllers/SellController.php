<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SellController extends Controller
{
    public function index()
    {
        // Pull top-level product categories from the database for the category dropdown.
        // Fall back to the hardcoded translation keys if no categories exist yet.
        $dbCategories = Category::roots()->active()->ordered()->get();

        if ($dbCategories->isNotEmpty()) {
            // Build id => name map for the select dropdown
            $categories = $dbCategories->pluck('name', 'id')->toArray();
        } else {
            $categories = [
                __('sell.categories.tiles'),
                __('sell.categories.paint'),
                __('sell.categories.plumbing'),
                __('sell.categories.electric'),
                __('sell.categories.laminate'),
                __('sell.categories.building'),
                __('sell.categories.decor'),
            ];
        }

        return view('pages.sell', compact('categories'));
    }

    /**
     * Store a new product listing submitted via the /sell form.
     * Requires authentication. Product is created with is_approved=false (pending admin review).
     */
    public function store(Request $request)
    {
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
}
