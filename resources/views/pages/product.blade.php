{{--
  Product page (Figma 572:10504). Ported from the old product.html: the navbar/footer
  placeholders are gone (the layout renders them) and everything the old page built in
  JS — the "bought together" cards, the rating distribution, the review cards and the
  two card grids — is rendered server-side here. The round card cursor comes from the
  shared resources/js/shared/cursor.js.
--}}
@php
    // Build thumbnail list from product images, fall back to hardcoded if none
    $thumbs = $product->images->count()
        ? $product->images->map(fn ($img) => storage_url($img->path))->toArray()
        : [
            '/assets/product-marble-tile-square.jpg',
            '/assets/interior-marble-corridor.jpg',
            '/assets/interior-marble-corridor.jpg',
            '/assets/interior-marble-corridor.jpg',
        ];

    $mainImg = $product->mainImageUrl ?? '/assets/product-marble-tile-square.jpg';

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
    <x-ui.breadcrumbs class="pd-crumb" :items="[
        ['label' => t('common.home'), 'href' => route('home')],
        ['label' => $product->category?->name ?? t('product.crumb.flooring'), 'href' => $product->category ? route('catalog', ['category' => $product->category->slug ?? $product->category->id]) : '#'],
        ['label' => $product->name],
    ]" />

    {{-- ===================== TOP: gallery + info ===================== --}}
    <div class="pd-top">
      {{-- gallery --}}
      <div class="pd-gallery">
        <div class="pd-thumbs" id="pdThumbs">
          @foreach ($thumbs as $i => $t)
            <div class="pd-thumb" data-on="{{ $i === 0 ? 'true' : 'false' }}"><img src="{{ $t }}" alt="{{ $product->name }}"></div>
          @endforeach
        </div>
        <div class="pd-main">
          <div class="badges">
            @foreach ($product->badges as $badge)
              <span class="b" style="background:{{ $badge->bg_color }};color:{{ $badge->color }}">{{ $badge->name }}</span>
            @endforeach
            @if ($product->badges->isEmpty())
              @if ($product->is_sale)<span class="b new">{{ t('common.badge_new') }}</span>@endif
              <span class="b">{{ $product->stock > 0 ? t('common.badge_in_stock') : t('common.badge_out_of_stock') }}</span>
            @endif
          </div>
          <div class="heart" id="pdHeartTop" data-liked="false"><img src="/assets/icon-heart-pointed.svg" alt="{{ t('product.gallery.favorite') }}"></div>
          <img id="pdMainImg" src="{{ $mainImg }}" alt="{{ $product->name }}">
        </div>
      </div>

      {{-- info --}}
      <div class="pd-info">
        <div class="cat">{{ $product->category?->name ?? t('product.info.cat') }}</div>
        <h1>{{ $product->name }}</h1>

        <div class="pd-meta">
          @if($reviewsCount > 0)
          <div class="pd-stars">
            <span class="s"><x-ui.stars :rating="$avgRating" /></span>
            <b>{{ number_format($avgRating, 1) }}</b>
          </div>
          <span class="rev" data-goto="reviews">{{ $reviewsCount }} {{ t('product.info.reviews') }}</span>
          <span class="dot"></span>
          @endif
          <span class="sold">{{ $product->sold_count ? number_format($product->sold_count) . '+ ' . t('product.info.sold') : t('product.info.sold') }}</span>
        </div>

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
        <div class="pd-unit">{{ $product->unit ?? t('product.info.unit') }}</div>

        <div class="pd-line"></div>

        <div class="pd-feats">
          @if ($product->features_text)
            <div class="f-rich">{!! $product->features_text !!}</div>
          @elseif ($product->features)
            @foreach ($product->features as $key => $feat)
              <div class="f"><span class="tick"><img src="/assets/icon-check-green.svg" alt=""></span> {{ is_string($key) ? $key . ': ' . $feat : $feat }}</div>
            @endforeach
          @else
            <div class="f"><span class="tick"><img src="/assets/icon-check-green.svg" alt=""></span> {{ t('product.info.feat_1') }}</div>
            <div class="f"><span class="tick"><img src="/assets/icon-check-green.svg" alt=""></span> {{ t('product.info.feat_2') }}</div>
            <div class="f"><span class="tick"><img src="/assets/icon-check-green.svg" alt=""></span> {{ t('product.info.feat_3') }}</div>
          @endif
        </div>

        <div class="pd-stock"><i></i> {{ $product->stock > 0 ? t('product.info.stock') : t('product.info.out_of_stock') }}</div>

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
                  data-cart-unit="{{ $product->unit ?? t('product.info.cart_unit') }}"
                  data-cart-brand="{{ $product->brand ?? t('common.site_name') }}"
                  data-cart-stock="{{ $product->stock > 0 ? t('common.badge_in_stock') : '' }}"><img src="/assets/icon-cart.svg" alt="">{{ t('product.info.add_cart') }}</button>
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
        <div class="pd-seller">
          <div class="ps-top">
            <div class="ps-logo"><img src="/assets/icon-tower-crane.svg" alt=""></div>
            <div class="ps-id">
              <div class="ps-name">{{ $product->user->name ?? t('product.seller.name') }}</div>
              <div class="ps-sub">{{ $product->user->sellerProfile->company_name ?? t('product.seller.sub') }}</div>
            </div>
            <span class="ps-vf"><img src="/assets/icon-check-green.svg" alt="">{{ t('common.badge_verified') }}</span>
          </div>
          <div class="ps-stats">
            @php
                $sellerProductIds = $product->user?->products()->pluck('id') ?? collect();
                $sellerAvgRating = $sellerProductIds->isNotEmpty()
                    ? \App\Models\Review::whereIn('reviewable_id', $sellerProductIds)
                        ->where('reviewable_type', \App\Models\Product::class)
                        ->where('status', 'approved')
                        ->avg('rating')
                    : null;
                $sellerProductCount = $product->user?->products()->count() ?? 0;
            @endphp
            <div class="ps-stat"><b><img src="/assets/icon-star-yellow.svg" alt="">{{ $sellerAvgRating ? number_format($sellerAvgRating, 1) : '—' }}</b><span>{{ t('product.seller.rating_label') }}</span></div>
            <div class="ps-stat"><b>{{ number_format($sellerProductCount) }}</b><span>{{ t('product.seller.products_label') }}</span></div>
            <div class="ps-stat"><b>{{ t('product.seller.response') }}</b><span>{{ t('product.seller.response_label') }}</span></div>
          </div>
          <div class="ps-actions">
            <x-ui.button variant="primary" href="#"
                class="h-[46px] flex-1 rounded-none text-sm font-semibold duration-200 hover:brightness-[.93]">{{ t('product.seller.visit') }}</x-ui.button>
          </div>
        </div>
      </div>
    </div>

    {{-- ===================== ABOUT + SPECIFICATIONS ===================== --}}
    <div class="pd-section">
      <div class="sec-tag"><span class="line"></span><p>{{ t('product.about.tag') }}</p></div>
      <div class="sec-title mb-6">{{ t('product.about.title') }}</div>

      <div class="pd-tabs" id="pdTabs">
        <button data-on="true" data-pane="desc">{{ t('product.about.tab_desc') }}</button>
        <button data-on="false" data-pane="specs">{{ t('product.about.tab_specs') }}</button>
      </div>

      <div class="pd-pane" data-on="true" data-pane="desc">
        <div class="pd-desc">
          @if ($product->description)
            {!! $product->description !!}
          @else
            <p>{{ t('product.about.p1') }}</p>
            <p>{{ t('product.about.p2') }}</p>
            <h4>{{ t('product.about.h4') }}</h4>
            <p>{{ t('product.about.p3') }}</p>
          @endif
        </div>
      </div>

      <div class="pd-pane" data-on="false" data-pane="specs">
        <div class="pd-specs">
          @forelse ($product->specifications ?? [] as $key => $val)
            <div class="row"><div class="k">{{ $key }}</div><div class="v">{{ $val }}</div></div>
          @empty
            @php $defaultSpecs = ['size', 'thickness', 'surface', 'material', 'color', 'box', 'slip', 'usage', 'warranty', 'country']; @endphp
            @foreach ($defaultSpecs as $s)
              <div class="row"><div class="k">{{ t('product.specs.' . $s . '_k') }}</div><div class="v">{{ t('product.specs.' . $s . '_v') }}</div></div>
            @endforeach
          @endforelse
        </div>
      </div>
    </div>

    {{-- ===================== FREQUENTLY BOUGHT TOGETHER ===================== --}}
    @if ($fbtProducts->isNotEmpty())
    <div class="pd-section">
      <div class="sec-tag"><span class="line"></span><p>{{ t('product.fbt.tag') }}</p></div>
      <div class="sec-title mb-2.5">{{ t('product.fbt.title') }}</div>
      <p class="pdb-lead">{{ t('product.fbt.lead') }}</p>

      <div class="fbt-cards" id="fbtCards">
        {{-- Current product card (always first) --}}
        @php
            $mainBadges = [
                ['icon' => '/assets/icon-check-green.svg', 'class' => 'fbt-ok'],
                ['label' => t('product.fbt.badge_this'), 'mine' => true],
                ['label' => $product->stock > 0 ? t('common.badge_in_stock') : t('common.badge_out_of_stock')],
            ];
        @endphp
        <x-pcard :product-id="$product->id" :img="$mainImg" :badges="$mainBadges"
                 :rate="$reviewsCount > 0 ? number_format($avgRating, 1) : null"
                 :reviews="$reviewsCount > 0 ? $reviewsCount . ' ' . t('product.fbt.card_reviews') : null"
                 :cat="$product->category?->name ?? t('product.fbt.card_cat')"
                 :name="$product->name"
                 :now="number_format($product->price, 2) . ' ₼'"
                 :old="$product->old_price && $product->old_price > $product->price ? number_format($product->old_price, 2) . ' ₼' : null"
                 :off="$product->old_price && $product->old_price > $product->price ? '-' . round((1 - $product->price / $product->old_price) * 100) . '%' : null" />

        {{-- FBT product cards --}}
        @foreach ($fbtProducts as $fbt)
          <div class="fbt-plus">+</div>
          @php
              $fbtBadges = [['label' => $fbt->stock > 0 ? t('common.badge_in_stock') : t('common.badge_out_of_stock')]];
          @endphp
          <x-pcard :product-id="$fbt->id" :href="route('product.show', $fbt->slug)"
                   :img="$fbt->mainImageUrl ?? '/assets/product-marble-tile-wide.jpg'"
                   :badges="$fbtBadges"
                   :rate="$fbt->reviewsCount > 0 ? number_format($fbt->averageRating, 1) : null"
                   :reviews="$fbt->reviewsCount > 0 ? $fbt->reviewsCount . ' ' . t('product.fbt.card_reviews') : null"
                   :cat="$fbt->category?->name ?? t('product.fbt.card_cat')"
                   :name="$fbt->name"
                   :now="number_format($fbt->price, 2) . ' ₼'"
                   :old="$fbt->old_price && $fbt->old_price > $fbt->price ? number_format($fbt->old_price, 2) . ' ₼' : null"
                   :off="$fbt->old_price && $fbt->old_price > $fbt->price ? '-' . round((1 - $fbt->price / $fbt->old_price) * 100) . '%' : null" />
        @endforeach

        @php
            $fbtTotal = $product->price + $fbtProducts->sum('price');
            $fbtDiscounted = $fbtTotal * 0.90;
        @endphp
        <div class="fbt-panel">
          <span class="lbl">{{ t('product.fbt.selected') }}</span>
          <div class="tot"><b>{{ number_format($fbtDiscounted, 2) }} ₼</b><span class="old">{{ number_format($fbtTotal, 2) }} ₼</span></div>
          <div class="fbt-save"><span class="ic"><img src="/assets/icon-check-green.svg" alt=""></span><p>{{ t('product.fbt.save_1') }}<br>{{ t('product.fbt.save_2') }}</p></div>
          <button class="fbt-addall" type="button"><img src="/assets/icon-cart.svg" alt="">{{ t('product.fbt.add_all') }}</button>
          <div class="fbt-hint">{{ t('product.fbt.hint') }}</div>
        </div>
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
<div class="wrap"><div class="inner section">
  <x-section-head :tag="t('product.similar.tag')" :title="t('product.similar.title')"
                  :more="$product->category ? route('catalog', ['category' => $product->category->slug ?? $product->category->id]) : route('catalog')" :more-label="t('common.view_all')" />
  <div class="grid4" id="simGrid">
    @forelse ($related as $rel)
      <x-pcard
          :href="route('product.show', $rel->slug)"
          :img="$rel->mainImageUrl ?? '/assets/product-marble-tile.png'"
          :cat="$rel->category?->name"
          :name="$rel->name"
          :now="number_format($rel->price, 2) . ' ₼'"
          :old="$rel->old_price && $rel->old_price > $rel->price ? number_format($rel->old_price, 2) . ' ₼' : null"
          :off="$rel->old_price && $rel->old_price > $rel->price ? '-' . round((1 - $rel->price / $rel->old_price) * 100) . '%' : null"
          :rate="$rel->reviewsCount > 0 ? number_format($rel->averageRating, 1) : null"
          :reviews="$rel->reviewsCount > 0 ? $rel->reviewsCount . ' ' . t('product.similar.reviews') : null" />
    {{-- TODO: Replace with dynamic data from controller --}}
    @empty
      @php
          $similar = [
              ['img' => '/assets/product-facade-paint-bucket.jpg', 'cat' => t('product.similar.cat_paint'), 'name' => t('product.similar.name_paint')],
              ['img' => '/assets/product-mineral-wool-roll.jpg', 'cat' => t('product.similar.cat_insulation'), 'name' => t('product.similar.name_insulation')],
              ['img' => '/assets/product-facade-paint-bucket.jpg', 'cat' => t('product.similar.cat_paint'), 'name' => t('product.similar.name_paint')],
              ['img' => '/assets/product-marble-tile-wide.jpg', 'cat' => t('product.similar.cat_tiles'), 'name' => t('product.similar.name_tiles')],
          ];
      @endphp
      @foreach ($similar as $p)
        <x-pcard :img="$p['img']" :cat="$p['cat']" :name="$p['name']"
                 :now="t('product.similar.fallback_price_now')" :old="t('product.similar.fallback_price_old')" :off="t('product.similar.fallback_discount')"
                 :rate="t('product.similar.fallback_rate')" :reviews="t('product.similar.reviews')" />
      @endforeach
    @endforelse
  </div>
</div></div>

{{-- ===================== FEATURED SPECIALISTS ===================== --}}
<div class="wrap"><div class="inner section">
  {{-- the old page left this "view more" without an href; it now points at /specialists --}}
  <x-section-head :tag="t('product.specialists.tag')" :title="t('product.specialists.title')"
                  :more="route('specialists')" />
  <div class="grid4" id="specGrid">
    @if($specialists->isNotEmpty())
      @foreach ($specialists as $s)
        <x-scard :href="route('specialist.show', $s)"
                 :bg="['#f5fbff', '#fdf5ff', '#f5fffb', '#fff5f5'][$loop->index % 4]"
                 :avatar="$s->user?->avatar ?? '/assets/icon-user.svg'"
                 :role="$s->specialty?->name ?? translate_craft($s->craft)"
                 :rate="null"
                 :reviews="t('product.specialists.reviews_416')"
                 :name="$s->user?->name ?? t('product.specialists.name_1')"
                 :exp="$s->experience_years ? $s->experience_years . ' ' . t('home.specialists.years') : t('product.specialists.exp_12')"
                 :proj="$s->approvedPortfolioItems()->count() . ' ' . t('home.specialists.projects')" />
      @endforeach
    @else
      @php
          $fallbackSpecialists = [
              ['bg' => '#f5fbff', 'role' => t('product.specialists.role_tiler'), 'name' => t('product.specialists.name_1')],
              ['bg' => '#fdf5ff', 'role' => t('product.specialists.role_tiler'), 'name' => t('product.specialists.name_1')],
              ['bg' => '#f5fffb', 'role' => t('product.specialists.role_interior'), 'name' => t('product.specialists.name_2')],
              ['bg' => '#fff5f5', 'role' => t('product.specialists.role_tiler'), 'name' => t('product.specialists.name_1')],
          ];
      @endphp
      @foreach ($fallbackSpecialists as $s)
        <x-scard :bg="$s['bg']" :role="$s['role']" :reviews="t('product.specialists.reviews_416')"
                 :name="$s['name']" :exp="t('product.specialists.exp_12')" :proj="'0 ' . t('home.specialists.projects')" />
      @endforeach
    @endif
  </div>
</div></div>

</x-layout>
