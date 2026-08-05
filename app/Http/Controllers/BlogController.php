<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $query = BlogPost::published();

        // Filter by category/tag — the blog_posts table has a JSON `tags` column.
        // The filter tab keys (used in ?cat=...) map to one or more actual tag values
        // stored in the database, because the UI uses English category slugs while
        // the seeder stores Azerbaijani tag names or slightly different English forms.
        if ($request->filled('cat') && $request->input('cat') !== 'all') {
            $cat = $request->input('cat');

            // Map filter keys to the actual tag values stored in the DB
            $tagMap = [
                'repair'     => ['repair', 'təmir'],
                'materials'  => ['material', 'tikinti', 'kafel', 'dam'],
                'budget'     => ['budget', 'büdcə'],
                'design'     => ['design', 'dizayn', 'interyer'],
                'masters'    => ['masters', 'usta', 'mütəxəssis'],
                'plumbing'   => ['plumbing', 'santexnika'],
                'insulation' => ['insulation', 'izolyasiya'],
            ];

            $tags = $tagMap[$cat] ?? [$cat];

            $query->where(function ($q) use ($tags) {
                foreach ($tags as $tag) {
                    $q->orWhereJsonContains('tags', $tag);
                }
            });
        }

        // Search
        if ($request->filled('q')) {
            $term = $request->input('q');
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                  ->orWhere('excerpt', 'like', "%{$term}%")
                  ->orWhere('body', 'like', "%{$term}%");
            });
        }

        $posts = $query->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        $featured = BlogPost::published()->featured()
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('pages.blog', compact('posts', 'featured'));
    }

    public function show(string $slug)
    {
        $post = BlogPost::published()
            ->where('slug', $slug)
            ->firstOrFail();

        $post->increment('views_count');

        $related = BlogPost::published()
            ->where('id', '!=', $post->id)
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('pages.blog-article', compact('post', 'related'));
    }
}
