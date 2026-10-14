<?php

namespace App\Http\Controllers;

use App\Enums\City;
use App\Enums\SpecialistCategory;
use App\Enums\SpecialistCategoryGroup;
use App\Enums\UserStatus;
use App\Models\SpecialistProfile;
use App\Models\SpecialistSpecialty;
use App\Services\SearchService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SpecialistController extends Controller
{
    /**
     * Sort key => [column, direction]. The column is either a real one on
     * specialist_profiles or an aggregate aliased onto the query below, so every menu
     * entry sorts by what its label promises. Before this map, `rating`, `reviews`,
     * `cheap` and `projects` all silently fell back to experience_years — four menu
     * entries that produced one identical order.
     */
    private const SORTS = [
        'rating' => ['specialist_profiles.rating_avg', 'desc'],
        'reviews' => ['specialist_profiles.reviews_count', 'desc'],
        'exp' => ['specialist_profiles.experience_years', 'desc'],
        'experience' => ['specialist_profiles.experience_years', 'desc'],
        'newest' => ['specialist_profiles.created_at', 'desc'],
        'projects' => ['portfolio_count', 'desc'],
        // The only ascending one: "ən ucuz" means the cheapest service first.
        'cheap' => ['min_service_price', 'asc'],
    ];

    public function show(SpecialistProfile $specialist)
    {
        $specialist->load(['user', 'specialty', 'services' => fn ($q) => $q->where('is_active', true), 'approvedPortfolioItems']);

        // Abort if the owning user account is not active.
        if (! $specialist->user || $specialist->user->status !== UserStatus::Active) {
            abort(404);
        }

        // Load approved reviews with their authors
        $reviews = $specialist->reviews()
            ->where('status', 'approved')
            ->with('user')
            ->latest()
            ->get();

        $avgRating = $reviews->avg('rating') ?? 0;
        $reviewsCount = $reviews->count();

        return view('pages.specialist', compact('specialist', 'reviews', 'avgRating', 'reviewsCount'));
    }

    /**
     * Reveal a specialist's phone number. The number is never rendered into the
     * public profile HTML; it is fetched from here so only signed-in users
     * (and only on an explicit click) can read it.
     */
    public function phone(SpecialistProfile $specialist): JsonResponse
    {
        if (! $specialist->user || $specialist->user->status !== UserStatus::Active) {
            abort(404);
        }

        $phone = $specialist->phone ?: ($specialist->whatsapp ?: $specialist->user->phone);

        return response()->json([
            'phone' => $phone ?: null,
            // Digits + leading "+" only, for the tel: href.
            'tel' => $phone ? preg_replace('/[^+\d]/', '', $phone) : null,
        ]);
    }

    public function index(Request $request)
    {
        // The listing is ordered by directory section first, so the specialty's category
        // has to be reachable from the ORDER BY. A left join keeps that a single indexed
        // lookup on the FK; a correlated subquery would re-run per row. Every column
        // reference below is table-qualified because the join brings a second
        // `created_at`, `is_active` and `id` into scope.
        $query = SpecialistProfile::query()
            ->select('specialist_profiles.*')
            ->leftJoin(
                'specialist_specialties',
                'specialist_specialties.id',
                '=',
                'specialist_profiles.specialist_specialty_id',
            )
            ->whereHas('user', fn ($q) => $q->where('status', UserStatus::Active))
            // Complete enough to be worth showing — see SpecialistProfile::scopeListable.
            // Registration no longer waits for an admin, so without this a master who
            // signed up seconds ago would sit in the grid with nothing on their card.
            ->listable()
            ->with(['user', 'specialty'])
            // Both feed a sort AND the card: the grid prints "from X ₼" and the portfolio
            // count. The card used to load the whole approvedPortfolioItems relation just
            // to call count() on it — one hydrated row set per card, for one integer.
            ->withCount(['approvedPortfolioItems as portfolio_count'])
            ->withMin(
                ['services as min_service_price' => fn (Builder $q) => $q->where('is_active', true)],
                'price',
            );

        // Free-text search over craft/skills/specialty/user name.
        if ($request->filled('q')) {
            $query = SearchService::buildSpecialistQuery($query, (string) $request->input('q'));
        }

        // Accept either the slug ("baku") or the label ("Bakı") — rows store the label.
        if ($request->filled('city') && ($city = City::canonical($request->input('city')))) {
            $query->where('specialist_profiles.city', $city);
        }

        // Filter by the normalized specialist specialty relation.
        if ($request->filled('spec')) {
            $query->where('specialist_profiles.specialist_specialty_id', (int) $request->input('spec'));
        }

        // The header's "Mütəxəssislər" mega panel links here as ?type=architect etc.
        // Nothing used to read that parameter, so all four cards landed on the full
        // directory and the choice the visitor had just made was thrown away.
        // input() may hand back an array (?type[]=x) or null, and casting either to
        // string is a fatal — the catalog already tolerates junk in ?category=, so this
        // must not be the one filter that 500s on a malformed URL.
        $type = $request->input('type');
        $category = is_string($type) ? SpecialistCategory::tryFrom($type) : null;

        if ($category !== null) {
            $query->whereHas('specialty', fn ($q) => $q->category($category));
        }

        // Filter by featured/top
        if ($request->boolean('featured', false)) {
            $query->where('specialist_profiles.is_featured', true);
        }

        // Filter by minimum experience years
        if ($request->filled('min_years')) {
            $query->where('specialist_profiles.experience_years', '>=', (int) $request->input('min_years'));
        }

        // Filter by max experience years
        if ($request->filled('max_years')) {
            $query->where('specialist_profiles.experience_years', '<=', (int) $request->input('max_years'));
        }

        // Filter by availability (not on vacation)
        if ($request->boolean('free', false)) {
            $query->where('specialist_profiles.is_on_vacation', false);
        }

        // An editorial verification, not the promotion flag — see the is_verified
        // migration. This used to read is_featured, which made the toggle an exact
        // duplicate of the "featured" filter.
        if ($request->boolean('verified', false)) {
            $query->where('specialist_profiles.is_verified', true);
        }

        // How many match each section IN TOTAL, not just on this page. The heading has
        // to say "12 mütəxəssis" on page 2 as well; counting the rows the page happens
        // to carry would make the same section shrink as the visitor paged through it.
        // Taken here, after every filter and before any ordering, so it answers the
        // same question the paginator does.
        $sectionTotals = $this->sectionTotals($query);

        $sort = $request->input('sort', 'rating');
        [$sortColumn, $sortDirection] = self::SORTS[is_string($sort) ? $sort : ''] ?? self::SORTS['rating'];

        // Section first: the grid renders one block per group, so the rows of a group
        // have to arrive together even across a page boundary.
        $query->orderByRaw(
            SpecialistCategoryGroup::orderByCaseSql('specialist_specialties.category').' asc'
        );

        // A specialist with no services at all must not win the "cheapest" sort with an
        // empty price. MySQL sorts NULL first ascending, so push them to the back.
        if ($sortColumn === 'min_service_price') {
            $query->orderByRaw('min_service_price is null asc');
        }

        $query->orderBy($sortColumn, $sortDirection)
            // Featured profiles break ties rather than overriding the visitor's choice:
            // someone who asked for "cheapest first" should not get a promoted profile
            // at the top of the block with the highest price on the page.
            ->orderByDesc('specialist_profiles.is_featured')
            // Deterministic across pages — two profiles on the same rating would
            // otherwise be free to swap between page 1 and page 2.
            ->orderBy('specialist_profiles.id');

        $specialists = $query->paginate(20)->withQueryString();

        // The canonical list, not a DISTINCT over the column — the distinct is what let a
        // stale raw slug show up as its own filter row next to the proper city.
        $cities = collect(City::labels());

        $specialties = SpecialistSpecialty::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // Passed through so the page can show which mega-panel card the visitor
        // arrived from. Without it a category with no active members renders as a
        // plain "no specialists" page and looks broken rather than empty.
        $activeCategory = $category;

        return view('pages.specialists', [
            'specialists' => $specialists,
            'cities' => $cities,
            'specialties' => $specialties,
            'activeCategory' => $activeCategory,
            'groups' => $this->groupForDisplay(
                $specialists->getCollection(),
                $sectionTotals,
                // Empty sections are an orientation device for someone arriving at the
                // directory, so they belong on the entry page only. By page 2 the
                // visitor is working through results and does not need to be told again
                // which trades have nobody in them.
                ! $this->isFiltered($request) && $specialists->currentPage() === 1,
            ),
        ]);
    }

    /**
     * One grouped COUNT over the filtered set: section rank => how many match.
     *
     * The clone drops the eager loads and the two aggregate sub-selects with the
     * replaced select list, so this is a single scan rather than a second copy of the
     * card query.
     *
     * @return array<int, int>
     */
    private function sectionTotals(Builder $query): array
    {
        $rank = SpecialistCategoryGroup::orderByCaseSql('specialist_specialties.category');

        return (clone $query)
            ->reorder()
            // select(), not selectRaw(): selectRaw APPENDS, which would leave
            // `specialist_profiles.*` and the two aggregate sub-selects in the list and
            // make MySQL reject the whole statement under only_full_group_by.
            ->select([
                DB::raw($rank.' as section_rank'),
                DB::raw('count(*) as section_total'),
            ])
            ->groupBy('section_rank')
            ->pluck('section_total', 'section_rank')
            ->mapWithKeys(fn ($total, $sectionRank): array => [(int) $sectionRank => (int) $total])
            ->all();
    }

    /**
     * Split one page of results into the directory's sections.
     *
     * On the unfiltered directory every section is returned even when it is empty: the
     * page's job there is to show a visitor what kinds of professional ARCHİ has, and a
     * section that silently disappears teaches them the opposite. Once a filter is on,
     * empty sections are dropped — someone who searched for a city wants results, not
     * three headings explaining what they did not find.
     *
     * @param  Collection<int, SpecialistProfile>  $items
     * @param  array<int, int>  $sectionTotals
     * @return Collection<int, array{group: SpecialistCategoryGroup, items: Collection<int, SpecialistProfile>, total: int}>
     */
    private function groupForDisplay(Collection $items, array $sectionTotals, bool $keepEmpty): Collection
    {
        $byGroup = $items->groupBy(
            fn (SpecialistProfile $profile): string => (
                $profile->specialty?->category?->group() ?? SpecialistCategoryGroup::Other
            )->value,
        );

        return collect(SpecialistCategoryGroup::ordered())
            ->map(fn (SpecialistCategoryGroup $group): array => [
                'group' => $group,
                'items' => $byGroup->get($group->value, collect()),
                'total' => $sectionTotals[$group->rank()] ?? 0,
            ])
            ->reject(function (array $section) use ($keepEmpty): bool {
                if ($section['items']->isNotEmpty()) {
                    return false;
                }

                // Nothing on THIS page. A section whose members sit on another page is
                // not empty, it is elsewhere — rendering its "nobody here yet" line
                // would be a lie. Only a genuinely empty section survives, only on the
                // unfiltered directory, and never the "Other" data-repair bucket.
                return $section['total'] > 0
                    || ! $keepEmpty
                    || $section['group'] === SpecialistCategoryGroup::Other;
            })
            ->values();
    }

    /** Is any filter, search term or sort narrowing the listing? */
    private function isFiltered(Request $request): bool
    {
        return $request->hasAny(['q', 'city', 'spec', 'type', 'min_years', 'max_years'])
            || $request->boolean('featured')
            || $request->boolean('free')
            || $request->boolean('verified');
    }
}
