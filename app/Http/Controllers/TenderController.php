<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TenderAccessRequestStatus;
use App\Http\Requests\StoreTenderAccessRequest;
use App\Models\Tender;
use App\Models\TenderAccessRequest;
use App\Models\TenderCategory;
use App\Models\TenderPackage;
use App\Notifications\TenderAccessRequestNotification;
use App\Services\SearchService;
use App\Support\TenderAccess;
use App\Support\TenderNotificationRecipients;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tender board (Figma «11 · TENDERLƏR», T1–T7 / M1–M4).
 *
 * In paid mode everything past the lock card — list, detail, contacts, files —
 * needs an active package (App\Support\TenderAccess); without one the same lock
 * page is rendered for /tenders and every /tenders/{slug}, so a shared direct
 * link reveals nothing either. While the section is free (config/tenders.php),
 * TenderAccess grants everyone, so the board shows to all visitors and the lock
 * page is never reached — the gating code simply stays dormant.
 */
final class TenderController extends Controller
{
    public const STATUSES = ['all', 'open', 'closed'];

    public const SORTS = ['newest', 'deadline', 'budget'];

    public function index(Request $request): View
    {
        $access = TenderAccess::for($request->user());

        if (! $access->granted()) {
            return $this->locked($access);
        }

        $filters = [
            'q' => trim((string) $request->input('q', '')),
            'categories' => array_values(array_filter((array) $request->input('category', []), 'is_string')),
            'city' => trim((string) $request->input('city', '')),
            'status' => in_array($request->input('status'), self::STATUSES, true) ? $request->input('status') : 'all',
            'sort' => in_array($request->input('sort'), self::SORTS, true) ? $request->input('sort') : 'newest',
            // The saved tab needs an account — a free-mode guest never gets it.
            'tab' => ($request->user() && $request->input('tab') === 'saved') ? 'saved' : 'all',
        ];

        // A deactivated category is hidden from the board: it is not loaded, so the
        // card shows none rather than a category the filter no longer offers.
        $query = Tender::query()->published()->with(['category' => fn ($q) => $q->active()]);

        if ($filters['tab'] === 'saved') {
            $query->whereHas('savedBy', fn (Builder $q) => $q->whereKey($request->user()->id));
        }

        if ($filters['q'] !== '') {
            $query = SearchService::buildTenderQuery($query, $filters['q']);
        }

        if ($filters['categories'] !== []) {
            $query->whereHas('category', fn (Builder $q) => $q->whereIn('slug', $filters['categories']));
        }

        if ($filters['city'] !== '') {
            $query->where('city', $filters['city']);
        }

        match ($filters['status']) {
            'open' => $query->open(),
            'closed' => $query->closed(),
            default => null,
        };

        // Open tenders always come before closed ones; the sort orders within each group.
        $query->orderByRaw('CASE WHEN deadline_at IS NULL OR deadline_at > ? THEN 0 ELSE 1 END', [now()]);

        match ($filters['sort']) {
            'deadline' => $query->orderByRaw('deadline_at IS NULL')->orderBy('deadline_at'),
            'budget' => $this->orderByBudget($query),
            default => null,
        };

        $tenders = $query->latest('published_at')->latest('id')->paginate(10)->withQueryString();

        $categories = TenderCategory::query()->active()->ordered()
            ->withCount(['tenders' => fn (Builder $q) => $q->published()])
            ->get();

        $cities = Tender::query()->published()->whereNotNull('city')->where('city', '!=', '')
            ->distinct()->orderBy('city')->pluck('city');

        $savedIds = $request->user()?->savedTenders()->pluck('tenders.id')->all() ?? [];

        $activeFilterCount = count($filters['categories'])
            + ($filters['city'] !== '' ? 1 : 0)
            + ($filters['status'] !== 'all' ? 1 : 0);

        $paidMode = TenderAccess::paidMode();

        return view('pages.tenders', compact('access', 'tenders', 'filters', 'categories', 'cities', 'savedIds', 'activeFilterCount', 'paidMode'));
    }

    public function show(Request $request, string $slug): View
    {
        $tender = Tender::query()->published()->where('slug', $slug)->with(['category' => fn ($q) => $q->active()])->firstOrFail();
        $access = TenderAccess::for($request->user());

        if (! $access->granted()) {
            return $this->locked($access, $tender);
        }

        $tender->increment('views_count');

        $isSaved = $request->user()?->savedTenders()->whereKey($tender->id)->exists() ?? false;
        $paidMode = TenderAccess::paidMode();

        return view('pages.tender', compact('access', 'tender', 'isSaved', 'paidMode'));
    }

    public function file(Request $request, string $slug, int $index): StreamedResponse|RedirectResponse
    {
        $tender = Tender::query()->published()->where('slug', $slug)->firstOrFail();

        if (! TenderAccess::for($request->user())->granted()) {
            return $request->user()
                ? redirect()->route('tenders.show', $tender->slug)
                : redirect()->guest(route('login'));
        }

        $file = collect($tender->fileList())->firstWhere('index', $index);
        $path = array_values(array_filter((array) $tender->files, 'is_string'))[$index] ?? null;
        $disk = Storage::disk(Tender::FILES_DISK);

        abort_unless($file && $path && $disk->exists($path), 404);

        return $disk->download($path, $file['name'], [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=0, no-store',
        ]);
    }

    public function toggleSave(Request $request, string $slug): JsonResponse
    {
        $tender = Tender::query()->published()->where('slug', $slug)->firstOrFail();

        abort_unless(TenderAccess::for($request->user())->granted(), 403);

        $result = $request->user()->savedTenders()->toggle([$tender->id => ['created_at' => now()]]);
        $saved = $result['attached'] !== [];

        return response()->json([
            'saved' => $saved,
            'message' => t($saved ? 'tenders.save.added' : 'tenders.save.removed'),
        ]);
    }

    public function requestAccess(StoreTenderAccessRequest $request): JsonResponse
    {
        $user = $request->user();
        $access = TenderAccess::for($user);

        if (! $access->canRequest()) {
            $key = $access->state === TenderAccess::PENDING ? 'tenders.access.already_pending' : 'tenders.access.already_active';

            return response()->json(['message' => t($key)], 422);
        }

        $accessRequest = TenderAccessRequest::query()->create([
            ...$request->validated(),
            'user_id' => $user->id,
            'status' => TenderAccessRequestStatus::Pending,
            'ip_address' => $request->ip(),
            'user_agent' => str($request->userAgent())->limit(500)->toString(),
        ]);

        $recipients = TenderNotificationRecipients::all();

        if ($recipients !== []) {
            Notification::route('mail', $recipients)->notify(new TenderAccessRequestNotification($accessRequest));
        } else {
            report(new \RuntimeException('Tender access request #'.$accessRequest->id.' has no admin recipient.'));
        }

        return response()->json(['message' => t('tenders.access.sent')], 201);
    }

    /**
     * Highest budget first, compared in AZN (config tenders.budget_rates) so that
     * 50 000 USD ranks above 60 000 AZN. "Sorğu əsasında" (no budget) goes last.
     */
    private function orderByBudget(Builder $query): void
    {
        $cases = '';
        $bindings = [];

        foreach ((array) config('tenders.budget_rates', []) as $currency => $rate) {
            $cases .= ' WHEN ? THEN ?';
            array_push($bindings, (string) $currency, (float) $rate);
        }

        $rate = $cases === '' ? '1' : "CASE currency{$cases} ELSE 1 END";

        $query->orderByRaw('COALESCE(budget_max, budget_min) IS NULL')
            ->orderByRaw("COALESCE(budget_max, budget_min) * {$rate} DESC", $bindings);
    }

    /** T3 / T4 / T5 / M3 — the lock card, with the request form for signed-in users. */
    private function locked(TenderAccess $access, ?Tender $tender = null): View
    {
        $user = request()->user();

        $packages = TenderPackage::query()->active()->ordered()->get();

        $prefill = [
            'full_name' => $user ? trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: $user->name : '',
            'company_name' => $user?->sellerProfile?->brand_name ?? $user?->sellerProfile?->legal_name ?? '',
            'phone' => $user?->phone ?? '',
            'email' => $user?->email ?? '',
        ];

        return view('pages.tenders-locked', compact('access', 'tender', 'packages', 'prefill'));
    }
}
