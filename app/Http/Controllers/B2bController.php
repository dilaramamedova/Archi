<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\B2bCooperationType;
use App\Models\B2bCategory;
use App\Models\B2bCompany;
use App\Services\SearchService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * B2B directory (Figma «10 · B2B BEYNƏLXALQ / ArchiAds»).
 *
 * One public page: a pricing hero ("Find Your Partner in Azerbaijan") with the
 * plan tables, then a directory of admin-added companies. No self-registration —
 * the pricing CTAs open the consultation form. Each company has a profile page.
 */
final class B2bController extends Controller
{
    public const SORTS = ['featured', 'newest', 'name'];

    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->input('q', '')),
            'categories' => array_values(array_filter((array) $request->input('category', []), 'is_string')),
            'country' => trim((string) $request->input('country', '')),
            'cooperation' => in_array($request->input('cooperation'), array_column(B2bCooperationType::cases(), 'value'), true)
                ? $request->input('cooperation')
                : '',
            'sort' => in_array($request->input('sort'), self::SORTS, true) ? $request->input('sort') : 'featured',
            'market' => in_array($request->input('market'), ['local', 'international'], true) ? $request->input('market') : 'international',
        ];

        $query = B2bCompany::query()->published()->with('category');

        if ($filters['q'] !== '') {
            $query = SearchService::buildB2bQuery($query, $filters['q']);
        }

        if ($filters['categories'] !== []) {
            $query->whereHas('category', fn (Builder $q) => $q->whereIn('slug', $filters['categories']));
        }

        if ($filters['country'] !== '') {
            $query->where('country', $filters['country']);
        }

        if ($filters['cooperation'] !== '') {
            $query->where('cooperation_type', $filters['cooperation']);
        }

        match ($filters['sort']) {
            'newest' => $query->latest('published_at')->latest('id'),
            'name' => $query->orderByRaw('COALESCE(NULLIF(brand_name, ""), legal_name)'),
            // featured: paid tiers and the featured flag first, then newest.
            default => $query
                ->orderByDesc('is_featured')
                ->orderByRaw("FIELD(plan, 'premium', 'pro', 'basic', 'free') ")
                ->latest('published_at'),
        };

        $companies = $query->paginate(12)->withQueryString();

        $categories = B2bCategory::query()->active()->ordered()
            ->withCount(['companies' => fn (Builder $q) => $q->published()])
            ->get();

        $countries = B2bCompany::query()->published()->whereNotNull('country')->where('country', '!=', '')
            ->distinct()->orderBy('country')->pluck('country');

        $activeFilterCount = count($filters['categories'])
            + ($filters['country'] !== '' ? 1 : 0)
            + ($filters['cooperation'] !== '' ? 1 : 0);

        $cooperationTypes = B2bCooperationType::cases();

        return view('pages.b2b', compact(
            'companies', 'filters', 'categories', 'countries', 'cooperationTypes', 'activeFilterCount',
        ));
    }

    public function show(Request $request, string $slug): View
    {
        $company = B2bCompany::query()->published()->where('slug', $slug)->with('category')->firstOrFail();

        $company->increment('views_count');

        $related = B2bCompany::query()->published()
            ->whereKeyNot($company->id)
            ->when($company->b2b_category_id, fn (Builder $q) => $q->where('b2b_category_id', $company->b2b_category_id))
            ->orderByDesc('is_featured')
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('pages.b2b-company', compact('company', 'related'));
    }
}
