<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Services\SearchService;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $query = BlogPost::published();

        $categories = BlogCategory::active()->ordered()->get();
        $activeCategory = null;

        if ($request->filled('cat')) {
            $activeCategory = BlogCategory::where('slug', $request->input('cat'))->first();
            if ($activeCategory) {
                $query->where('blog_category_id', $activeCategory->id);
            }
        }

        if ($request->filled('q')) {
            // Title/excerpt/body are translatable JSON columns — go through
            // SearchService so the comparison stays case-insensitive.
            $query = SearchService::buildBlogQuery($query, (string) $request->input('q'));
        }

        $featured = collect();

        if (! $activeCategory) {
            $featured = BlogPost::published()->featured()
                ->with(['blogCategory', 'author'])
                ->latest('published_at')
                ->take(3)
                ->get();
        }

        $posts = $query->with('blogCategory')
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        return view('pages.blog', compact('posts', 'featured', 'categories'));
    }

    public function show(string $slug)
    {
        $post = BlogPost::published()
            ->where('slug', $slug)
            ->firstOrFail();

        $post->increment('views_count');

        $related = BlogPost::published()
            ->where('id', '!=', $post->id)
            ->where('blog_category_id', $post->blog_category_id)
            ->latest('published_at')
            ->take(3)
            ->get();

        if ($related->count() < 3) {
            $remaining = 3 - $related->count();
            $extra = BlogPost::published()
                ->whereNotIn('id', $related->pluck('id')->push($post->id))
                ->latest('published_at')
                ->take($remaining)
                ->get();
            $related = $related->concat($extra);
        }

        return view('pages.blog-article', compact('post', 'related'));
    }
}
