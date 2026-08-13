{{--
  Product page (Figma 572:10504). Ported from the old product.html: the navbar/footer
  placeholders are gone (the layout renders them) and everything the old page built in
  JS — the "bought together" cards, the rating distribution, the review cards and the
  two card grids — is rendered server-side here. The round card cursor comes from the
  shared resources/js/shared/cursor.js.
--}}
@php
    use Illuminate\Support\Str;

    // Translate, falling back to a literal while a key is still missing from the
    // translations table (t() echoes the key back otherwise).
    $tOr = fn (string $key, string $fallback) => is_string($v = t($key)) && $v !== $key ? $v : $fallback;

    // Gallery: only this product's own images. No images → neutral placeholder,
    // never another product's photos.
    $thumbs = $product->images->map(fn ($img) => storage_url($img->path))->all();
    $mainImg = $product->mainImageUrl;

    $inStock = (int) $product->stock > 0;
    $unitLabel = $product->unit ? $tOr('business-product-edit.pricing.units.'.$product->unit, $product->unit) : '';

    // Only the specs the seller actually filled in; rows without data are omitted.
    $specLabelKeys = [
        'dimensions' => 'product.specs.size_k',
        'material' => 'product.specs.material_k',
        'color' => 'product.specs.color_k',
        'country' => 'product.specs.country_k',
        'barcode' => 'product.specs.barcode_k',
        'shelf' => 'product.specs.shelf_k',
    ];
    $specLabel = fn (string $key) => isset($specLabelKeys[$key])
        ? $tOr($specLabelKeys[$key], Str::headline($key))
        : Str::headline($key);

    // Same array shape the admin KeyValue field / seller form saves — only the
    // display is filtered here. A value counts as empty when it is null, an empty
    // or whitespace-only string, or an array with no non-empty entries.
    $specs = collect($product->specifications ?? [])
        ->map(fn ($v) => is_array($v) ? array_values(array_filter($v, fn ($x) => $x !== null && trim((string) $x) !== '')) : $v)
        ->reject(fn ($v) => $v === null || $v === [] || (is_string($v) && trim($v) === ''));

    // Rating distribution from reviews
    $ratingCounts = $product->reviews->groupBy('rating')->map->count();
    $dist = [];
    for ($i = 5; $i >= 1; $i--) {
        $dist[$i] = $ratingCounts->get($i, 0);
    }
    $distMax = max(1, max($dist));
    $totalRatings = array_sum($dist);

    $avgRating = $product->averageRating;
    $reviewsCount = $product->reviewsCount;
@endphp
<x-layout page="product" :title="$product->name">

<section class="pd">
  <div class="wrap"><div class="inner">

    {{-- breadcrumb --}}
    <x-ui.breadcrumbs class="pd-crumb" :items="array_values(array_filter([
        ['label' => t('common.home'), 'href' => route('home')],
        $product->category
            ? ['label' => $product->category->name, 'href' => route('catalog', ['category' => $product->category->slug ?? $product->category->id])]
            : null,
        ['label' => $product->name],
    ]))" />

    {{-- ===================== TOP: gallery + info ===================== --}}
    <div class="pd-top">
      {{-- gallery --}}
      <div class="pd-gallery">
        @if ($thumbs)
          <div class="pd-thumbs" id="pdThumbs">
            @foreach ($thumbs as $i => $t)
              <div class="pd-thumb" data-on="{{ $i === 0 ? 'true' : 'false' }}"><img src="{{ $t }}" alt="{{ $product->name }}"></div>
            @endforeach
          </div>
        @endif
        <div class="pd-main">
          <div class="badges">
            @foreach ($product->badges as $badge)
              <span class="b" style="background:{{ $badge->bg_color }};color:{{ $badge->color }}">{{ $badge->name }}</span>
            @endforeach
            @if ($product->badges->isEmpty())
              <span class="b">{{ $inStock ? t('common.badge_in_stock') : t('common.badge_out_of_stock') }}</span>
            @endif
          </div>
          <div class="heart" id="pdHeartTop" data-liked="false"><img src="/assets/icon-heart-pointed.svg" alt="{{ t('product.gallery.favorite') }}"></div>
          @if ($mainImg)
            <img id="pdMainImg" src="{{ $mainImg }}" alt="{{ $product->name }}">
          @else
            <span class="absolute left-1/2 top-1/2 flex size-28 -translate-x-1/2 -translate-y-1/2 opacity-15" aria-hidden="true">
              <img src="/assets/icon-tower-crane.svg" alt="" class="size-full">
            </span>
          @endif
        </div>
      </div>

      {{-- info --}}
      <div class="pd-info">
        @if ($product->category?->name)<div class="cat">{{ $product->category->name }}</div>@endif
        <h1>{{ $product->name }}</h1>

        @if ($reviewsCount > 0 || $product->sold_count)
        <div class="pd-meta">
          @if($reviewsCount > 0)
          <div class="pd-stars">
            <span class="s"><x-ui.stars :rating="$avgRating" /></span>
            <b>{{ number_format($avgRating, 1) }}</b>
          </div>
          <span class="rev" data-goto="reviews">{{ $reviewsCount }} {{ t('product.info.reviews') }}</span>
          @if ($product->sold_count)<span class="dot"></span>@endif
          @endif
          {{-- Real sold count only; nothing is shown when the product never sold. --}}
          @if ($product->sold_count)
            <span class="sold">{{ number_format($product->sold_count) }} {{ $tOr('product.info.sold_suffix', 'satıldı') }}</span>
          @endif
        </div>
        @endif

        <div class="pd-price">
          <span class="now">{{ number_format($product->price, 2) }} ₼</span>
          @if ($product->old_price && $product->old_price > $product->price)
            <span class="old">{{ number_format($product->old_price, 2) }} ₼</span>
          @endif
          @if ($product->discount_percent && $product->old_price > $product->price)
            <span class="off">-{{ $product->discount_percent }}%</span>
          @elseif ($product->old_price && $product->old_price > $product->price)
            <span class="off">-{{ round((1 - $product->price / $product->old_price) * 100) }}%</span>
          @endif
        </div>
        @if ($unitLabel)<div class="pd-unit">{{ $unitLabel }}</div>@endif

        <div class="pd-line"></div>

        {{-- Feature bullets: the seller's own first, otherwise the marketplace-wide
             ones every listing shares (editable in the admin "Tərcümələr" module,
             keys product.features.default_1..3). --}}
        @php
            $defaultFeats = collect([1, 2, 3])
                ->map(fn ($i) => trim((string) t('product.features.default_'.$i)))
                ->filter(fn ($line) => $line !== '' && ! str_starts_with($line, 'product.features.'))
                ->all();
        @endphp
        <div class="pd-feats">
          @if ($product->features_text)
            <div class="f-rich">{!! $product->features_text !!}</div>
          @elseif ($product->features)
            @foreach ($product->features as $key => $feat)
              <div class="f"><span class="tick"><img src="/assets/icon-check-green.svg" alt=""></span> {{ is_string($key) ? $key . ': ' . $feat : $feat }}</div>
            @endforeach
          @else
            @foreach ($defaultFeats as $feat)
              <div class="f"><span class="tick"><img src="/assets/icon-check-green.svg" alt=""></span> {{ $feat }}</div>
            @endforeach
          @endif
        </div>

        {{-- Single source of truth for stock, built from the real quantity. --}}
        <div class="pd-stock" data-in-stock="{{ $inStock ? 'true' : 'false' }}"><i></i>
          @if ($inStock)
            {{ t('common.badge_in_stock') }} — {{ number_format($product->stock) }}@if ($unitLabel) {{ $unitLabel }}@endif
          @else
            {{ t('product.info.out_of_stock') }}
          @endif
        </div>

        <div class="pd-buy">
          <div class="pd-qty">
            <button type="button" id="qtyMinus" aria-label="{{ t('product.info.qty_minus') }}">−</button>
            <input type="number" id="qtyVal" value="1" min="1" aria-label="{{ t('product.info.qty') }}">
            <button type="button" id="qtyPlus" aria-label="{{ t('product.info.qty_plus') }}">+</button>
          </div>
          {{-- The labels/values the "add to cart" behaviour needs come from data-* --}}
          <button class="pd-add" id="addCart" data-added="false"
                  data-product-id="{{ $product->id }}"
                  data-label-add="{{ t('product.info.add_cart') }}"
                  data-label-added="{{ t('product.info.added') }}"
                  data-label-in-cart="{{ t('product.info.in_cart') }}"
                  {{-- popup buttons shown after a successful add (shared/popup.js) --}}
                  data-btn-goto-cart="{{ t('common.go_to_cart') }}"
                  data-btn-continue="{{ t('common.continue') }}"
                  data-cart-unit="{{ $unitLabel ?: t('product.info.cart_unit') }}"
                  {{-- brand?->name, not the model itself (which stringifies to JSON) --}}
                  data-cart-brand="{{ $product->brand?->name ?? $product->category?->name ?? t('common.site_name') }}"
                  data-cart-img="{{ $mainImg ?? '' }}"
                  data-cart-stock="{{ $inStock ? t('common.badge_in_stock') : t('common.badge_out_of_stock') }}"><img src="/assets/icon-cart.svg" alt="">{{ t('product.info.add_cart') }}</button>
          <button class="pd-wish" id="pdWish" data-liked="false" aria-label="{{ t('product.info.wish') }}"><img src="/assets/icon-heart-pointed.svg" alt=""></button>
        </div>

        <div class="pd-assure">
          @if ($product->free_delivery)
          <div class="a"><span class="ic"><img src="/assets/icon-check-green.svg" alt=""></span><div><div class="t1">{{ t('product.info.delivery_t1') }}</div><div class="t2">{{ t('product.info.delivery_t2') }}</div></div></div>
          @endif
          @if ($product->return_14_days)
          <div class="a"><span class="ic"><img src="/assets/icon-check-green.svg" alt=""></span><div><div class="t1">{{ t('product.info.return_t1') }}</div><div class="t2">{{ t('product.info.return_t2') }}</div></div></div>
          @endif
          @if (!$product->free_delivery && !$product->return_14_days)
          <div class="a"><span class="ic"><img src="/assets/icon-check-green.svg" alt=""></span><div><div class="t1">{{ t('product.info.delivery_t1') }}</div><div class="t2">{{ t('product.info.delivery_t2') }}</div></div></div>
          <div class="a"><span class="ic"><img src="/assets/icon-check-green.svg" alt=""></span><div><div class="t1">{{ t('product.info.return_t1') }}</div><div class="t2">{{ t('product.info.return_t2') }}</div></div></div>
          @endif
        </div>

        {{-- seller / store --}}
        @php
            $seller = $product->user;
            $sellerProfile = $seller?->sellerProfile;
            $sellerSub = $sellerProfile?->brand_name ?: $sellerProfile?->legal_name;

            // Only an active seller account gets a public store page (StoreController
            // 404s everyone else) — the "Mağazaya keç" button follows the same rule.
            $sellerHasStore = $seller && $seller->isSeller() && $seller->isActive();

            $sellerProductIds = $seller?->products()->visible()->approved()->pluck('products.id') ?? collect();
            $sellerAvgRating = $sellerProductIds->isNotEmpty()
                ? \App\Models\Review::whereIn('reviewable_id', $sellerProductIds)
                    ->where('reviewable_type', \App\Models\Product::class)
                    ->where('status', 'approved')
                    ->avg('rating')
                : null;
            $sellerProductCount = $sellerProductIds->count();
        @endphp
        @if ($seller)
        <div class="pd-seller">
          <div class="ps-top">
            <div class="ps-logo"><img src="{{ $sellerProfile?->logo_path ? storage_url($sellerProfile->logo_path) : '/assets/icon-tower-crane.svg' }}" alt=""></div>
            <div class="ps-id">
              <div class="ps-name">{{ $seller->name }}</div>
              @if ($sellerSub)<div class="ps-sub">{{ $sellerSub }}</div>@endif
            </div>
            {{-- "Verified" is earned: it tracks the real tax_id_verified flag. --}}
            @if ($sellerProfile?->tax_id_verified)
              <span class="ps-vf"><img src="/assets/icon-check-green.svg" alt="">{{ t('common.badge_verified') }}</span>
            @endif
          </div>
          <div class="ps-stats">
            @if ($sellerAvgRating)
              <div class="ps-stat"><b><img src="/assets/icon-star-yellow.svg" alt="">{{ number_format($sellerAvgRating, 1) }}</b><span>{{ t('product.seller.rating_label') }}</span></div>
            @endif
            <div class="ps-stat"><b>{{ number_format($sellerProductCount) }}</b><span>{{ t('product.seller.products_label') }}</span></div>
          </div>
          @if ($sellerHasStore)
          <div class="ps-actions">
            <x-ui.button variant="primary" :href="route('store.show', $seller)"
                class="h-[46px] flex-1 rounded-none text-sm font-semibold duration-200 hover:brightness-[.93]">{{ t('product.seller.visit') }}</x-ui.button>
          </div>
          @endif
        </div>
        @endif
      </div>
    </div>

    {{-- ===================== ABOUT + SPECIFICATIONS ===================== --}}
    <div class="pd-section">
      <div class="sec-tag"><span class="line"></span><p>{{ t('product.about.tag') }}</p></div>
      <div class="sec-title mb-6">{{ t('product.about.title') }}</div>

      {{-- A product with neither structured attribute values nor filled-in legacy
           specifications gets no specs tab at all: the tab bar collapses to the
           single description tab (initTabs handles one button fine) and the specs
           pane is not rendered. --}}
      @php $hasSpecsTab = $attributeSpecs->isNotEmpty() || $specs->isNotEmpty(); @endphp
      <div class="pd-tabs" id="pdTabs">
        <button data-on="true" data-pane="desc">{{ t('product.about.tab_desc') }}</button>
        @if ($hasSpecsTab)
          <button data-on="false" data-pane="specs">{{ t('product.about.tab_specs') }}</button>
        @endif
      </div>

      <div class="pd-pane" data-on="true" data-pane="desc">
        <div class="pd-desc">
          @if ($product->description)
            {!! $product->description !!}
          @else
            <p class="text-sm text-black/45">{{ $tOr('product.about.empty', 'Bu məhsul üçün təsvir əlavə edilməyib.') }}</p>
          @endif
        </div>
      </div>

      @if ($hasSpecsTab)
      <div class="pd-pane" data-on="false" data-pane="specs">
        <div class="pd-specs">
          {{-- Structured classifier attributes first (translated option values,
               units from the class pivot, tooltip on professional attributes)… --}}
          @foreach ($attributeSpecs as $spec)
            <div class="row">
              <div class="k">
                {{ $spec['name'] }}
                @if ($spec['tooltip'] !== '')
                  <span class="pd-spec-tip" tabindex="0" role="note" aria-label="{{ $spec['tooltip'] }}"><span class="i">?</span><span class="pop">{{ $spec['tooltip'] }}</span></span>
                @endif
              </div>
              <div class="v">{{ $spec['value'] }}</div>
            </div>
          @endforeach
          {{-- …then the legacy flat rows the seller filled in; no demo rows. --}}
          @foreach ($specs as $key => $val)
            <div class="row"><div class="k">{{ $specLabel((string) $key) }}</div><div class="v">{{ is_array($val) ? implode(', ', $val) : $val }}</div></div>
          @endforeach
        </div>
      </div>
      @endif
    </div>

    {{-- ===================== FREQUENTLY BOUGHT TOGETHER ===================== --}}
    @if ($fbtProducts->isNotEmpty())
    <div class="pd-section">
      <div class="sec-tag"><span class="line"></span><p>{{ t('product.fbt.tag') }}</p></div>
      <div class="sec-title mb-2.5">{{ t('product.fbt.title') }}</div>
      <p class="pdb-lead">{{ t('product.fbt.lead') }}</p>

      {{-- Accessories only: this is a recommendation row like "Oxşar məhsullar",
           not a bundle — checkout has no bundle pricing to total up. --}}
      <div class="fbt-cards" id="fbtCards">
        @foreach ($fbtProducts as $fbt)
          @php
              $fbtBadges = [['label' => $fbt->stock > 0 ? t('common.badge_in_stock') : t('common.badge_out_of_stock')]];
          @endphp
          <x-pcard :product-id="$fbt->id" :href="route('product.show', $fbt->slug)"
                   :img="$fbt->mainImageUrl"
                   :badges="$fbtBadges"
                   :rate="$fbt->reviewsCount > 0 ? number_format($fbt->averageRating, 1) : null"
                   :reviews="$fbt->reviewsCount > 0 ? $fbt->reviewsCount . ' ' . t('product.fbt.card_reviews') : null"
                   :cat="$fbt->category?->name"
                   :name="$fbt->name"
                   :now="number_format($fbt->price, 2) . ' ₼'"
                   :old="$fbt->old_price && $fbt->old_price > $fbt->price ? number_format($fbt->old_price, 2) . ' ₼' : null"
                   :off="$fbt->old_price && $fbt->old_price > $fbt->price ? '-' . round((1 - $fbt->price / $fbt->old_price) * 100) . '%' : null" />
        @endforeach
      </div>
    </div>
    @endif

    {{-- ===================== RATING / REVIEWS ===================== --}}
    @if (false)
    {{-- Müştəri qiymətləndirməsi bölməsi hazırda göstərilmir. --}}
    @if ($product->reviews->isNotEmpty())
    <div class="pd-section" id="reviews">
      <div class="sec-tag"><span class="line"></span><p>{{ t('product.reviews.tag') }}</p></div>
      <div class="sec-title mb-6">{{ t('product.reviews.title') }}</div>

      <div class="flex gap-10 max-[900px]:flex-col">
        {{-- Rating summary --}}
        <div class="flex w-[240px] shrink-0 flex-col items-center gap-3 max-[900px]:w-full max-[900px]:flex-row max-[900px]:justify-center">
          <p class="text-[48px] font-bold leading-none text-ink">{{ number_format($product->averageRating, 1) }}</p>
          <x-ui.stars :rating="$product->averageRating" />
          <p class="text-sm text-black/50">{{ $product->reviews->count() }} {{ t('common.reviews') }}</p>
        </div>

        {{-- Rating bars --}}
        <div class="flex flex-1 flex-col gap-2">
          @for ($star = 5; $star >= 1; $star--)
            @php $count = $product->reviews->where('rating', $star)->count(); $pct = $product->reviews->count() > 0 ? ($count / $product->reviews->count()) * 100 : 0; @endphp
            <div class="flex items-center gap-3">
              <span class="w-8 text-right text-sm font-medium text-ink">{{ $star }} ★</span>
              <div class="h-2 flex-1 rounded-full bg-black/8">
                <div class="h-2 rounded-full bg-yellow" style="width: {{ $pct }}%"></div>
              </div>
              <span class="w-8 text-sm text-black/50">{{ $count }}</span>
            </div>
          @endfor
        </div>
      </div>

      {{-- Review cards --}}
      <div class="mt-8 flex flex-col gap-4">
        @foreach ($product->reviews->take(5) as $review)
          <div class="rounded border border-black/8 bg-white p-5">
            <div class="mb-2 flex items-center gap-3">
              <span class="flex size-9 items-center justify-center rounded-full bg-[#f5f7f9] text-xs font-bold text-ink">{{ mb_strtoupper(mb_substr($review->user?->first_name ?? 'A', 0, 1) . mb_substr($review->user?->last_name ?? '', 0, 1)) }}</span>
              <div>
                <p class="text-sm font-semibold text-ink">{{ $review->user?->first_name }} {{ $review->user?->last_name }}</p>
                <p class="text-xs text-black/40">{{ $review->created_at->diffForHumans() }}</p>
              </div>
              <div class="ml-auto"><x-ui.stars :rating="$review->rating" size="sm" /></div>
            </div>
            @if ($review->comment)
              <p class="text-sm leading-[1.6] text-black/60">{{ $review->comment }}</p>
            @endif
          </div>
        @endforeach
      </div>
    </div>
    @endif
    @endif

  </div></div>
</section>

{{-- ===================== SIMILAR PRODUCTS ===================== --}}
{{-- Nothing to recommend in this category — the whole section stays out of the page. --}}
@if ($related->isNotEmpty())
<div class="wrap"><div class="inner section">
  <x-section-head :tag="t('product.similar.tag')" :title="t('product.similar.title')"
                  :more="$product->category ? route('catalog', ['category' => $product->category->slug ?? $product->category->id]) : route('catalog')" :more-label="t('common.view_all')" />
  <div class="grid4" id="simGrid">
    @foreach ($related as $rel)
      <x-pcard
          :href="route('product.show', $rel->slug)"
          :product="$rel"
          :img="$rel->mainImageUrl"
          :cat="$rel->category?->name"
          :name="$rel->name"
          :now="number_format($rel->price, 2) . ' ₼'"
          :old="$rel->old_price && $rel->old_price > $rel->price ? number_format($rel->old_price, 2) . ' ₼' : null"
          :off="$rel->old_price && $rel->old_price > $rel->price ? '-' . round((1 - $rel->price / $rel->old_price) * 100) . '%' : null"
          :rate="$rel->reviewsCount > 0 ? number_format($rel->averageRating, 1) : null"
          :reviews="$rel->reviewsCount > 0 ? $rel->reviewsCount . ' ' . t('product.similar.reviews') : null" />
    @endforeach
  </div>
</div></div>
@endif

{{-- ===================== FEATURED SPECIALISTS ===================== --}}
<div class="wrap"><div class="inner section">
  {{-- the old page left this "view more" without an href; it now points at /specialists --}}
  <x-section-head :tag="t('product.specialists.tag')" :title="t('product.specialists.title')"
                  :more="route('specialists')" />
  <div class="grid4" id="specGrid">
    @forelse ($specialists as $s)
      <x-scard :href="route('specialist.show', $s)"
               :bg="['#f5fbff', '#fdf5ff', '#f5fffb', '#fff5f5'][$loop->index % 4]"
               :avatar="$s->user?->avatar ?? '/assets/icon-user.svg'"
               :role="$s->craft_label"
               :rate="null"
               :reviews="null"
               :name="$s->user?->name"
               :exp="$s->experience_years ? $s->experience_years . ' ' . t('home.specialists.years') : null"
               :proj="$s->approvedPortfolioItems()->count() . ' ' . t('home.specialists.projects')" />
    @empty
      <p class="w-full py-8 text-sm text-black/45">{{ $tOr('home.specialists.empty', 'Hələ seçilmiş mütəxəssis yoxdur.') }}</p>
    @endforelse
  </div>
</div></div>

</x-layout>
