<?php

namespace App\Http\Controllers;

use App\Enums\Calculator\FinishCategory;
use App\Enums\Calculator\HvacSystemType;
use App\Enums\Calculator\ObjectCondition;
use App\Enums\Calculator\ObjectType;
use App\Enums\Calculator\PriceSegment;
use App\Enums\Calculator\RenovationType;
use App\Enums\Calculator\RequestStatus;
use App\Enums\Calculator\RequestType;
use App\Enums\Calculator\RoomType;
use App\Enums\Calculator\SurfaceState;
use App\Enums\Calculator\WarmFloorType;
use App\Models\CalculatorRequest;
use App\Models\CalculatorSession;
use App\Models\CalculatorSnapshot;
use App\Models\Product;
use App\Services\Calculator\CalculatorConfig;
use App\Services\Calculator\CityRate;
use App\Services\Calculator\EstimateBuilder;
use App\Services\Calculator\MaterialFallback;
use App\Services\Calculator\QuickCalculator;
use App\Services\Calculator\WorkRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Backend of the renovation calculator (master spec §18).
 *
 * The quick calculator gets its six prices once and multiplies locally, so no
 * request is made per keystroke (TZ §43). The detailed calculator posts its answers
 * and gets a fully traced estimate back, because the arithmetic — dependency chains,
 * consumption norms, package counts, logistics — belongs in one place that the web
 * and any future mobile client share.
 */
class CalculatorController extends Controller
{
    public function __construct(
        private readonly CalculatorConfig $config,
        private readonly QuickCalculator $quick,
        private readonly EstimateBuilder $builder,
    ) {}

    /**
     * The quick calculator page. The matrix travels with the HTML so the first paint
     * already shows 80 m² · White Box · Standart = 36 000 AZN and never a 0 or a
     * loading flicker (TZ §16, §38).
     */
    public function page()
    {
        return archiView('pages.calculator', [
            'quick' => $this->quick->payload(),
        ]);
    }

    /** The detailed calculator page. */
    public function detailedPage(Request $request)
    {
        $prefill = [
            'area' => $request->filled('area') ? (float) $request->input('area') : null,
            'renovationType' => RenovationType::fromSlug($request->input('type'))?->value,
            'segment' => PriceSegment::fromSlug($request->input('segment'))?->value,
        ];

        return archiView('pages.calculator-detailed', [
            'calculatorConfig' => $this->config()->getData(true),
            'prefill' => array_filter($prefill, fn ($v) => $v !== null),
            'cartProducts' => array_map(fn (array $item) => [
                'id' => $item['product']->id,
                'name' => $item['product']->name,
                'price' => (float) $item['product']->price,
                'unit' => $item['product']->unit,
                'image' => $item['product']->mainImageUrl,
                'packageSize' => $item['product']->package_size ? (float) $item['product']->package_size : null,
                'quantity' => $item['quantity'],
            ], $this->cartItems($request)),
        ]);
    }

    /** The price matrix, limits and defaults of the quick calculator. */
    public function quickRates(): JsonResponse
    {
        return response()->json($this->quick->payload());
    }

    /**
     * Server-side quick calculation. The browser does the same arithmetic locally;
     * this exists so a share link, a CRM hand-off or a test can get the authoritative
     * number without a browser.
     */
    public function quickEstimate(Request $request): JsonResponse
    {
        $type = RenovationType::fromSlug($request->input('renovationType', $request->input('type')))
            ?? $this->quick->defaultRenovationType();
        $segment = PriceSegment::fromSlug($request->input('segment'))
            ?? $this->quick->defaultSegment();

        $result = $this->quick->calculate($request->input('area'), $type, $segment);

        return response()->json($result + [
            'showFromPrefix' => $this->config->bool('SHOW_FROM_PREFIX', true),
            'detailedUrl' => $result['valid']
                ? $this->quick->handoffUrl($result['area'], $type, $segment)
                : null,
        ], $result['valid'] ? 200 : 422);
    }

    /**
     * Reference data for the detailed calculator: the work catalog, the classifiers
     * behind every dropdown and the limits. Everything is server-owned so a new work
     * or a new city needs no frontend release.
     */
    public function config(): JsonResponse
    {
        return response()->json([
            'calculatorVersion' => CalculatorConfig::CALCULATOR_VERSION,
            'configVersion' => $this->config->configVersion(),
            'currency' => 'AZN',
            'quick' => $this->quick->payload(),
            'works' => array_values(array_map(fn (WorkRate $rate) => [
                'workId' => $rate->workId,
                'category' => $rate->category->value,
                'name' => $rate->name(),
                'unit' => $rate->unit,
                'quantitySource' => $rate->quantitySource,
                'precisionClass' => $rate->precisionClass->value,
                // The rate itself is deliberately not exposed: the "İşlər" step shows
                // no prices (master spec §10, §20) — money appears in the estimate.
            ], $this->config->workRates())),
            'materialCategories' => array_values(array_map(fn (MaterialFallback $fallback) => [
                'id' => $fallback->categoryId,
                'group' => $fallback->group,
                'name' => $fallback->name(),
                'unit' => $fallback->unit,
            ], $this->config->materialFallbacks())),
            'cities' => array_values(array_map(fn (CityRate $city) => [
                'key' => $city->key,
                'name' => $city->name(),
                'isDefault' => $city->isDefault,
            ], $this->config->cities())),
            'enums' => [
                'objectTypes' => array_map(fn (ObjectType $type) => [
                    'value' => $type->value,
                    'label' => __($type->labelKey()),
                    // The room set a fresh session starts from, so step 2 is never an
                    // empty screen and a commercial object does not open with a bedroom.
                    'defaultRoomTypes' => array_map(fn (RoomType $room) => $room->value, $type->defaultRoomTypes()),
                ], ObjectType::cases()),
                'conditions' => $this->enumPayload(ObjectCondition::cases()),
                'roomTypes' => $this->roomTypePayload(),
                'surfaceStates' => $this->enumPayload(SurfaceState::cases()),
                'finishes' => array_map(fn (FinishCategory $f) => [
                    'value' => $f->value,
                    'label' => __($f->labelKey()),
                    'surface' => $f->surface()->value,
                    'materialCategoryId' => $f->materialCategoryId(),
                    'incompatibleWith' => $f->incompatibleWith(),
                ], FinishCategory::cases()),
                'warmFloorTypes' => array_map(fn (WarmFloorType $t) => ['value' => $t->value], WarmFloorType::cases()),
                'hvacSystems' => array_map(fn (HvacSystemType $t) => [
                    'value' => $t->value,
                    'label' => __($t->labelKey()),
                    'precisionClass' => $t->precisionClass()->value,
                ], HvacSystemType::cases()),
                'segments' => array_map(fn (PriceSegment $s) => ['value' => $s->value, 'label' => __($s->labelKey())], PriceSegment::cases()),
            ],
            'settings' => [
                'contingencyPercent' => $this->config->float('CONTINGENCY_PERCENT', 0),
                'vatMode' => $this->config->setting('VAT_MODE', 'INCLUDED'),
                'vatPercent' => $this->config->float('VAT_PERCENT', 0),
                'areaTolerancePercent' => $this->config->float('AREA_TOLERANCE_PERCENT', 5),
                'defaultRoomHeight' => $this->config->float('DEFAULT_ROOM_HEIGHT', 2.8),
                'defaultDoor' => [
                    'width' => $this->config->float('DEFAULT_DOOR_WIDTH', 0.9),
                    'height' => $this->config->float('DEFAULT_DOOR_HEIGHT', 2.1),
                ],
                'defaultWindow' => [
                    'width' => $this->config->float('DEFAULT_WINDOW_WIDTH', 1.5),
                    'height' => $this->config->float('DEFAULT_WINDOW_HEIGHT', 1.4),
                ],
                'estimateValidDays' => $this->config->int('ESTIMATE_VALID_DAYS', 30),
            ],
        ]);
    }

    /** Recalculate from scratch. Stateless: nothing is written. */
    public function estimate(Request $request): JsonResponse
    {
        $inputs = $this->inputs($request);

        return response()->json($this->build($inputs, $request));
    }

    /**
     * Save the answers so a visitor can come back to them. Anonymous visitors get a
     * session too — the quick calculator collects no personal data (TZ §46) and
     * neither does this.
     */
    public function storeSession(Request $request): JsonResponse
    {
        $inputs = $this->inputs($request);
        $estimate = $this->build($inputs, $request);

        $session = CalculatorSession::create([
            'user_id' => auth()->id(),
            'anonymous_id' => auth()->check() ? null : $request->session()->getId(),
            'status' => 'DRAFT',
            'current_step' => (string) $request->input('currentStep', 'OBJECT'),
            'inputs' => $inputs,
            'totals' => $estimate['totals'],
        ]);

        return response()->json(['uuid' => $session->uuid, 'estimate' => $estimate], 201);
    }

    public function updateSession(Request $request, CalculatorSession $session): JsonResponse
    {
        $this->authorizeSession($request, $session);

        $inputs = $this->inputs($request);
        $estimate = $this->build($inputs, $request);

        $session->update([
            'inputs' => $inputs,
            'current_step' => (string) $request->input('currentStep', $session->current_step),
            'totals' => $estimate['totals'],
        ]);

        return response()->json(['uuid' => $session->uuid, 'estimate' => $estimate]);
    }

    public function showSession(Request $request, CalculatorSession $session): JsonResponse
    {
        $this->authorizeSession($request, $session);

        return response()->json([
            'uuid' => $session->uuid,
            'currentStep' => $session->current_step,
            'inputs' => $session->inputs,
            'estimate' => $this->build($session->inputs ?? [], $request),
        ]);
    }

    /**
     * Freeze the estimate. Everything that leaves the site — PDF, WhatsApp, a saved
     * estimate, a turnkey order — points at a snapshot id, so two channels can never
     * quote different sums while a rate is being edited (master spec §22).
     */
    public function storeSnapshot(Request $request, CalculatorSession $session): JsonResponse
    {
        $this->authorizeSession($request, $session);

        $snapshot = $this->freeze($session, $request);

        return response()->json([
            'uuid' => $snapshot->uuid,
            'version' => $snapshot->version,
            'totals' => $snapshot->totals,
        ], 201);
    }

    /** Recalculate once and store the result as the next immutable version. */
    private function freeze(CalculatorSession $session, Request $request): CalculatorSnapshot
    {
        $estimate = $this->build($session->inputs ?? [], $request);
        $validDays = $this->config->int('ESTIMATE_VALID_DAYS', 0);

        $snapshot = CalculatorSnapshot::create([
            'calculator_session_id' => $session->id,
            'version' => ($session->snapshots()->max('version') ?? 0) + 1,
            'inputs' => $session->inputs ?? [],
            'derived' => $estimate['derived'],
            'lines' => $estimate['lines'],
            'totals' => $estimate['totals'],
            'config_version' => $estimate['configVersion'],
            'calculator_version' => $estimate['calculatorVersion'],
            'currency' => $estimate['currency'],
            'expires_at' => $validDays > 0 ? now()->addDays($validDays) : null,
        ]);

        $session->update(['status' => 'SAVED', 'totals' => $estimate['totals']]);

        return $snapshot;
    }

    /**
     * A request raised from the finished estimate: a turnkey order, a saved estimate,
     * a PDF or a WhatsApp share. Everything Archi needs to call the client back lands
     * in the admin panel under "Kalkulyator müraciətləri".
     *
     * The snapshot is created here rather than referenced: by the time someone asks
     * for a call back, the figure they are asking about has to stop moving.
     */
    public function storeRequest(Request $request, CalculatorSession $session): JsonResponse
    {
        $this->authorizeSession($request, $session);

        $validated = $request->validate([
            'type' => ['required', Rule::enum(RequestType::class)],
            'name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:190'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $snapshot = $this->freeze($session, $request);
        $estimate = $snapshot->totals;

        $calculatorRequest = CalculatorRequest::create([
            'type' => $validated['type'],
            'status' => RequestStatus::New,
            'user_id' => auth()->id(),
            'calculator_session_id' => $session->id,
            'calculator_snapshot_id' => $snapshot->id,
            'name' => $validated['name'] ?? auth()->user()?->name,
            'phone' => $validated['phone'] ?? auth()->user()?->phone,
            'email' => $validated['email'] ?? auth()->user()?->email,
            'message' => $validated['message'] ?? null,
            'total_amount' => $estimate['grandTotal'] ?? null,
            'area' => data_get($session->inputs, 'object.area'),
            'city_key' => data_get($session->inputs, 'object.cityKey'),
            'room_count' => count((array) data_get($session->inputs, 'rooms', [])),
        ]);

        return response()->json([
            'id' => $calculatorRequest->id,
            'snapshotUuid' => $snapshot->uuid,
            'totals' => $snapshot->totals,
        ], 201);
    }

    /** A snapshot is served exactly as it was stored — never recalculated. */
    public function showSnapshot(CalculatorSnapshot $snapshot): JsonResponse
    {
        return response()->json([
            'uuid' => $snapshot->uuid,
            'version' => $snapshot->version,
            'currency' => $snapshot->currency,
            'createdAt' => $snapshot->created_at,
            'expiresAt' => $snapshot->expires_at,
            'expired' => $snapshot->isExpired(),
            'configVersion' => $snapshot->config_version,
            'calculatorVersion' => $snapshot->calculator_version,
            'derived' => $snapshot->derived,
            'lines' => $snapshot->lines,
            'totals' => $snapshot->totals,
        ]);
    }

    private function inputs(Request $request): array
    {
        $request->validate([
            'object' => ['array'],
            'object.area' => ['nullable', 'numeric', 'min:0'],
            'rooms' => ['array', 'max:60'],
            'works' => ['array'],
            'materials' => ['array'],
            'modules' => ['array'],
            'basket' => ['array'],
            // The basket rows are deliberately NOT validated field by field. They come
            // from the browser's own cart, which may hold anything an older version of
            // the storefront wrote — a fractional quantity, a name-only row from before
            // ids existed. Rejecting the request over one stale row would take the whole
            // estimate down; cartItems() drops what it cannot read instead.
            'basket.items' => ['array', 'max:60'],
        ]);

        return [
            'object' => (array) $request->input('object', []),
            'rooms' => array_values((array) $request->input('rooms', [])),
            'works' => (array) $request->input('works', []),
            'materials' => (array) $request->input('materials', []),
            'modules' => (array) $request->input('modules', []),
            'basket' => (array) $request->input('basket', []),
        ];
    }

    private function build(array $inputs, Request $request): array
    {
        return $this->builder->build(
            $inputs,
            $this->selectedProducts($inputs),
            $this->cartItems($request),
        );
    }

    /**
     * Products the visitor picked for a material category. Loaded in one query, and
     * only the visible, approved ones — an estimate must not quote a product that
     * cannot be bought.
     *
     * @return array<int, Product>
     */
    private function selectedProducts(array $inputs): array
    {
        $ids = [];

        foreach ((array) ($inputs['materials'] ?? []) as $selection) {
            if (is_array($selection) && ! empty($selection['productId'])) {
                $ids[] = (int) $selection['productId'];
            }
        }

        if ($ids === []) {
            return [];
        }

        return Product::query()
            ->whereIn('id', array_unique($ids))
            ->where('is_visible', true)
            ->where('is_approved', true)
            ->get()
            ->keyBy('id')
            ->all();
    }

    /**
     * The visitor's own cart. Master spec §13: an item already in the cart is offered
     * to the estimate rather than silently priced as "1 piece" of something the
     * calculator never asked for.
     *
     * The storefront cart lives in the browser (localStorage "archi-cart") — that is
     * the only cart a guest has, and even a logged-in visitor's most recent adds may
     * only exist there. So the account cart and the browser cart sent with the
     * request are merged, deduplicated by product. Quantities may come from the
     * browser; prices NEVER do — every product is re-read from the catalog, so a
     * tampered localStorage entry cannot change an estimate.
     *
     * @return array<int, array{product: Product, quantity: int}>
     */
    private function cartItems(Request $request): array
    {
        /** @var array<int, int> product id => quantity */
        $wanted = [];

        if (auth()->check()) {
            foreach (auth()->user()->cartItems()->get() as $item) {
                $wanted[(int) $item->product_id] = max(1, (int) $item->quantity);
            }
        }

        foreach ((array) $request->input('basket.items', []) as $row) {
            if (! is_array($row) || ! is_numeric($row['productId'] ?? null)) {
                continue;
            }

            $id = (int) $row['productId'];
            $quantity = is_numeric($row['quantity'] ?? null) ? (float) $row['quantity'] : 1;

            if ($id > 0 && ! isset($wanted[$id])) {
                // Whole units only, and never zero: a cart row exists because someone
                // wanted at least one of the thing.
                $wanted[$id] = (int) min(999, max(1, ceil($quantity)));
            }
        }

        if ($wanted === []) {
            return [];
        }

        return Product::query()
            // The cart step shows a thumbnail per row; without this the images are an
            // N+1 across the whole basket.
            ->with('images')
            ->whereIn('id', array_slice(array_keys($wanted), 0, 60))
            ->where('is_visible', true)
            ->where('is_approved', true)
            ->get()
            ->map(fn (Product $product) => ['product' => $product, 'quantity' => $wanted[$product->id]])
            ->values()
            ->all();
    }

    /** A session belongs to its owner, or to the browser session that created it. */
    private function authorizeSession(Request $request, CalculatorSession $session): void
    {
        if ($session->user_id !== null) {
            abort_unless($session->user_id === auth()->id(), 403);

            return;
        }

        abort_unless($session->anonymous_id === $request->session()->getId(), 403);
    }

    private function enumPayload(array $cases): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => __($case->labelKey()),
        ], $cases);
    }

    private function roomTypePayload(): array
    {
        return array_map(fn (RoomType $type) => [
            'value' => $type->value,
            'label' => __($type->labelKey()),
            'isWet' => $type->isWet(),
            'defaultPoints' => $type->defaultPoints(),
        ], RoomType::cases());
    }
}
