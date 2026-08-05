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
        ['label' => __('common.home'), 'href' => route('home')],
        ['label' => $product->category?->name ?? __('product.crumb.flooring'), 'href' => $product->category ? route('catalog', ['category' => $product->category->slug ?? $product->category->id]) : '#'],
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
            @if ($product->is_sale)<span class="b new">{{ __('common.badge_new') }}</span>@endif
            <span class="b">{{ $product->stock > 0 ? __('common.badge_in_stock') : __('common.badge_out_of_stock') }}</span>
          </div>
          <div class="heart" id="pdHeartTop" data-liked="false"><img src="/assets/icon-heart-pointed.svg" alt="{{ __('product.gallery.favorite') }}"></div>
          <img id="pdMainImg" src="{{ $mainImg }}" alt="{{ $product->name }}">
        </div>
      </div>

      {{-- info --}}
      <div class="pd-info">
        <div class="cat">{{ $product->category?->name ?? __('product.info.cat') }}</div>
        <h1>{{ $product->name }}</h1>

        <div class="pd-meta">
          @if($reviewsCount > 0)
          <div class="pd-stars">
            <span class="s"><x-ui.stars :rating="$avgRating" /></span>
            <b>{{ number_format($avgRating, 1) }}</b>
          </div>
          <span class="rev" data-goto="reviews">{{ $reviewsCount }} {{ __('product.info.reviews') }}</span>
          <span class="dot"></span>
          @endif
          <span class="sold">{{ __('product.info.sold') }}</span>
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
        <div class="pd-unit">{{ $product->unit ?? __('product.info.unit') }}</div>

        <div class="pd-line"></div>

        <div class="pd-feats">
          @forelse ($product->features ?? [] as $key => $feat)
            <div class="f"><span class="tick"><img src="/assets/icon-check-green.svg" alt=""></span> {{ is_string($key) ? $key . ': ' . $feat : $feat }}</div>
          @empty
            <div class="f"><span class="tick"><img src="/assets/icon-check-green.svg" alt=""></span> {{ __('product.info.feat_1') }}</div>
            <div class="f"><span class="tick"><img src="/assets/icon-check-green.svg" alt=""></span> {{ __('product.info.feat_2') }}</div>
            <div class="f"><span class="tick"><img src="/assets/icon-check-green.svg" alt=""></span> {{ __('product.info.feat_3') }}</div>
          @endforelse
        </div>

        <div class="pd-stock"><i></i> {{ $product->stock > 0 ? __('product.info.stock') : __('product.info.out_of_stock') }}</div>

        <div class="pd-buy">
          <div class="pd-qty">
            <button type="button" id="qtyMinus" aria-label="{{ __('product.info.qty_minus') }}">−</button>
            <input type="number" id="qtyVal" value="1" min="1" aria-label="{{ __('product.info.qty') }}">
            <button type="button" id="qtyPlus" aria-label="{{ __('product.info.qty_plus') }}">+</button>
          </div>
          {{-- The labels/values the "add to cart" behaviour needs come from data-* --}}
          <button class="pd-add" id="addCart" data-added="false"
                  data-product-id="{{ $product->id }}"
                  data-label-add="{{ __('product.info.add_cart') }}"
                  data-label-added="{{ __('product.info.added') }}"
                  data-label-in-cart="{{ __('product.info.in_cart') }}"
                  data-cart-unit="{{ $product->unit ?? __('product.info.cart_unit') }}"
                  data-cart-brand="{{ $product->brand ?? __('common.site_name') }}"
                  data-cart-stock="{{ $product->stock > 0 ? __('common.badge_in_stock') : '' }}"><img src="/assets/icon-cart.svg" alt="">{{ __('product.info.add_cart') }}</button>
          <button class="pd-wish" id="pdWish" data-liked="false" aria-label="{{ __('product.info.wish') }}"><img src="/assets/icon-heart-pointed.svg" alt=""></button>
        </div>

        <div class="pd-assure">
          @if ($product->free_delivery)
          <div class="a"><span class="ic"><img src="/assets/icon-check-green.svg" alt=""></span><div><div class="t1">{{ __('product.info.delivery_t1') }}</div><div class="t2">{{ __('product.info.delivery_t2') }}</div></div></div>
          @endif
          @if ($product->return_14_days)
          <div class="a"><span class="ic"><img src="/assets/icon-check-green.svg" alt=""></span><div><div class="t1">{{ __('product.info.return_t1') }}</div><div class="t2">{{ __('product.info.return_t2') }}</div></div></div>
          @endif
          @if (!$product->free_delivery && !$product->return_14_days)
          <div class="a"><span class="ic"><img src="/assets/icon-check-green.svg" alt=""></span><div><div class="t1">{{ __('product.info.delivery_t1') }}</div><div class="t2">{{ __('product.info.delivery_t2') }}</div></div></div>
          <div class="a"><span class="ic"><img src="/assets/icon-check-green.svg" alt=""></span><div><div class="t1">{{ __('product.info.return_t1') }}</div><div class="t2">{{ __('product.info.return_t2') }}</div></div></div>
          @endif
        </div>

        {{-- seller / store --}}
        <div class="pd-seller">
          <div class="ps-top">
            <div class="ps-logo"><img src="/assets/icon-tower-crane.svg" alt=""></div>
            <div class="ps-id">
              <div class="ps-name">{{ $product->user->name ?? __('product.seller.name') }}</div>
              <div class="ps-sub">{{ $product->user->sellerProfile->company_name ?? __('product.seller.sub') }}</div>
            </div>
            <span class="ps-vf"><img src="/assets/icon-check-green.svg" alt="">{{ __('common.badge_verified') }}</span>
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
            <div class="ps-stat"><b><img src="/assets/icon-star-yellow.svg" alt="">{{ $sellerAvgRating ? number_format($sellerAvgRating, 1) : '—' }}</b><span>{{ __('product.seller.rating_label') }}</span></div>
            <div class="ps-stat"><b>{{ number_format($sellerProductCount) }}</b><span>{{ __('product.seller.products_label') }}</span></div>
            <div class="ps-stat"><b>{{ __('product.seller.response') }}</b><span>{{ __('product.seller.response_label') }}</span></div>
          </div>
          <div class="ps-actions">
            <x-ui.button variant="primary" href="#"
                class="h-[46px] flex-1 rounded-none text-sm font-semibold duration-200 hover:brightness-[.93]">{{ __('product.seller.visit') }}</x-ui.button>
            <button class="ps-btn" type="button">{{ __('product.seller.follow') }}</button>
          </div>
        </div>
      </div>
    </div>

    {{-- ===================== ABOUT + SPECIFICATIONS ===================== --}}
    <div class="pd-section">
      <div class="sec-tag"><span class="line"></span><p>{{ __('product.about.tag') }}</p></div>
      <div class="sec-title mb-6">{{ __('product.about.title') }}</div>

      <div class="pd-tabs" id="pdTabs">
        <button data-on="true" data-pane="desc">{{ __('product.about.tab_desc') }}</button>
        <button data-on="false" data-pane="specs">{{ __('product.about.tab_specs') }}</button>
      </div>

      <div class="pd-pane" data-on="true" data-pane="desc">
        <div class="pd-desc">
          @if ($product->description)
            {!! $product->description !!}
          @else
            <p>{{ __('product.about.p1') }}</p>
            <p>{{ __('product.about.p2') }}</p>
            <h4>{{ __('product.about.h4') }}</h4>
            <p>{{ __('product.about.p3') }}</p>
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
              <div class="row"><div class="k">{{ __('product.specs.' . $s . '_k') }}</div><div class="v">{{ __('product.specs.' . $s . '_v') }}</div></div>
            @endforeach
          @endforelse
        </div>
      </div>
    </div>

    {{-- ===================== FREQUENTLY BOUGHT TOGETHER ===================== --}}
    @if ($fbtProducts->isNotEmpty())
    <div class="pd-section">
      <div class="sec-tag"><span class="line"></span><p>{{ __('product.fbt.tag') }}</p></div>
      <div class="sec-title mb-2.5">{{ __('product.fbt.title') }}</div>
      <p class="pdb-lead">{{ __('product.fbt.lead') }}</p>

      <div class="fbt-cards" id="fbtCards">
        {{-- Current product card (always first) --}}
        @php
            $mainBadges = [
                ['icon' => '/assets/icon-check-green.svg', 'class' => 'fbt-ok'],
                ['label' => __('product.fbt.badge_this'), 'mine' => true],
                ['label' => $product->stock > 0 ? __('common.badge_in_stock') : __('common.badge_out_of_stock')],
            ];
        @endphp
        <x-pcard :product-id="$product->id" :img="$mainImg" :badges="$mainBadges"
                 :rate="$reviewsCount > 0 ? number_format($avgRating, 1) : null"
                 :reviews="$reviewsCount > 0 ? $reviewsCount . ' ' . __('product.fbt.card_reviews') : null"
                 :cat="$product->category?->name ?? __('product.fbt.card_cat')"
                 :name="$product->name"
                 :now="number_format($product->price, 2) . ' ₼'"
                 :old="$product->old_price && $product->old_price > $product->price ? number_format($product->old_price, 2) . ' ₼' : null"
                 :off="$product->old_price && $product->old_price > $product->price ? '-' . round((1 - $product->price / $product->old_price) * 100) . '%' : null" />

        {{-- FBT product cards --}}
        @foreach ($fbtProducts as $fbt)
          <div class="fbt-plus">+</div>
          @php
              $fbtBadges = [['label' => $fbt->stock > 0 ? __('common.badge_in_stock') : __('common.badge_out_of_stock')]];
          @endphp
          <x-pcard :product-id="$fbt->id" :href="route('product.show', $fbt->slug)"
                   :img="$fbt->mainImageUrl ?? '/assets/product-marble-tile-wide.jpg'"
                   :badges="$fbtBadges"
                   :rate="$fbt->reviewsCount > 0 ? number_format($fbt->averageRating, 1) : null"
                   :reviews="$fbt->reviewsCount > 0 ? $fbt->reviewsCount . ' ' . __('product.fbt.card_reviews') : null"
                   :cat="$fbt->category?->name ?? __('product.fbt.card_cat')"
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
          <span class="lbl">{{ __('product.fbt.selected') }}</span>
          <div class="tot"><b>{{ number_format($fbtDiscounted, 2) }} ₼</b><span class="old">{{ number_format($fbtTotal, 2) }} ₼</span></div>
          <div class="fbt-save"><span class="ic"><img src="/assets/icon-check-green.svg" alt=""></span><p>{{ __('product.fbt.save_1') }}<br>{{ __('product.fbt.save_2') }}</p></div>
          <button class="fbt-addall" type="button"><img src="/assets/icon-cart.svg" alt="">{{ __('product.fbt.add_all') }}</button>
          <div class="fbt-hint">{{ __('product.fbt.hint') }}</div>
        </div>
      </div>
    </div>
    @endif

    {{-- ===================== RATING / REVIEWS ===================== --}}
    <div class="pd-section" id="reviews">
      <div class="sec-tag"><span class="line"></span><p>{{ __('product.reviews.tag') }}</p></div>
      <div class="sec-title mb-6">{{ __('product.reviews.title') }}</div>

      <div class="rev-summary">
        <div class="rs-score">
          <div class="num">{{ number_format($avgRating, 1) }}</div>
          <div class="s"><x-ui.stars :rating="$avgRating" /></div>
          <div class="c1">{{ $totalRatings }} {{ __('product.reviews.ratings') }}</div>
          <div class="c2">{{ $reviewsCount }} {{ __('product.reviews.written') }}</div>
        </div>
        <div class="rs-div"></div>
        <div class="rs-dist" id="rsDist">
          @foreach ($dist as $stars => $count)
            <div class="row">
              <span class="st">{{ $stars }} <img src="/assets/icon-star-yellow.svg" alt=""></span>
              <span class="track"><span style="width:{{ $distMax > 0 ? round($count / $distMax * 100) : 0 }}%"></span></span>
              <span class="cnt">{{ $count }}</span>
            </div>
          @endforeach
        </div>
        <div class="rs-div"></div>
        <div class="rs-rec">
          @php
              $recommendPct = $totalRatings > 0 ? round(($ratingCounts->get(4, 0) + $ratingCounts->get(5, 0)) / $totalRatings * 100) : 0;
          @endphp
          <div class="num">{{ $recommendPct }}%</div>
          <div class="t">{{ __('product.reviews.recommend') }}</div>
          <div class="track"><span style="width:{{ $recommendPct }}%"></span></div>
        </div>
      </div>

      <div class="rev-filter">
        <button type="button" data-on="true" data-filter="helpful">{{ __('product.reviews.filter_helpful') }}</button>
        <button type="button" data-on="false" data-filter="newest">{{ __('product.reviews.filter_newest') }}</button>
        <button type="button" data-on="false" data-filter="photo">{{ __('product.reviews.filter_photo') }}</button>
        <button type="button" data-on="false" data-filter="5star">{{ __('product.reviews.filter_5star') }}</button>
      </div>

      {{-- Write a review form --}}
      <div class="rev-write" id="revWrite">
        @auth
          <div id="revFormWrap">
            <h3 class="rev-write-title">{{ __('product.reviews.write_title') }}</h3>
            <form id="revForm" data-url="{{ route('api.reviews.store') }}">
              @csrf
              <input type="hidden" name="reviewable_type" value="product">
              <input type="hidden" name="reviewable_id" value="{{ $product->id }}">
              <div class="rev-stars-input" id="revStarsInput">
                <label>{{ __('product.reviews.your_rating') }}</label>
                <div class="stars-select" data-rating="0">
                  @for ($s = 1; $s <= 5; $s++)
                    <button type="button" class="star-btn" data-star="{{ $s }}" aria-label="{{ $s }} ulduz">
                      <img src="/assets/icon-star-yellow.svg" alt="" style="opacity:0.3">
                    </button>
                  @endfor
                </div>
                <input type="hidden" name="rating" id="revRating" value="">
              </div>
              <div class="rev-comment-input">
                <textarea name="comment" id="revComment" rows="4" maxlength="1000" placeholder="{{ __('product.reviews.comment_placeholder') }}" required></textarea>
              </div>
              <button type="submit" class="rev-submit">{{ __('product.reviews.submit') }}</button>
            </form>
            <div id="revSuccess" style="display:none" class="rev-success">
              <img src="/assets/icon-check-green.svg" alt="" style="width:20px;height:20px">
              <span>{{ __('product.reviews.pending_message') }}</span>
            </div>
            <div id="revError" style="display:none" class="rev-error"></div>
          </div>
        @else
          <div class="rev-login-prompt">
            <p>{{ __('product.reviews.login_prompt') }} <a href="{{ route('login') }}">{{ __('product.reviews.login_link') }}</a>.</p>
          </div>
        @endauth
      </div>

      <div class="rev-cards" id="revCards">
        @php
            $avatarColors = [
                ['bg' => '#eef1f4', 'fg' => '#5a6472'],
                ['bg' => '#e8f5ec', 'fg' => '#2f7d44'],
                ['bg' => '#fdf3e3', 'fg' => '#b07626'],
                ['bg' => '#e9f0fb', 'fg' => '#3c62a8'],
            ];
            $approvedReviews = $product->reviews->where('status', 'approved');
        @endphp
        @forelse ($approvedReviews as $i => $review)
          @php $color = $avatarColors[$i % count($avatarColors)]; @endphp
          <div class="rev-card" data-rating="{{ $review->rating }}" data-date="{{ $review->created_at->timestamp }}" data-helpful="{{ $review->helpful_count ?? 0 }}" data-has-photo="{{ $review->photos ? 'true' : 'false' }}">
            <div class="h">
              <div class="av" style="background:{{ $color['bg'] }};color:{{ $color['fg'] }}">{{ mb_strtoupper(mb_substr($review->user->name ?? 'U', 0, 1)) }}</div>
              <div>
                <div class="nm">{{ $review->user->name ?? __('product.reviews.anonymous') }}</div>
                @if ($review->is_verified_purchase)
                  <div class="vf"><img src="/assets/icon-check-green.svg" alt="">{{ __('product.reviews.verified') }}</div>
                @endif
              </div>
            </div>
            <div class="sd"><span class="s"><x-ui.stars :rating="$review->rating" /></span><span class="date">{{ $review->created_at->format('d.m.Y') }}</span></div>
            <div class="txt">{{ $review->comment }}</div>
            <button type="button" class="help" data-on="false" aria-pressed="false"
                    data-review-id="{{ $review->id }}"
                    data-url="{{ route('api.reviews.helpful', $review) }}">{{ __('product.reviews.helpful') }}&nbsp;&nbsp;&middot;&nbsp;&nbsp;<span class="n">{{ $review->helpful_count ?? 0 }}</span></button>
          </div>
        @empty
          <p class="text-center text-gray-500 py-8">{{ __('product.reviews.empty') }}</p>
        @endforelse
      </div>

      {{-- No reviews route exists, so this stays a plain label (not an <a>) — it must
           not advertise a link it cannot follow. --}}
      <div class="rev-all"><span class="sec-more2">{{ __('product.reviews.all') }}&nbsp;&nbsp;→</span></div>
    </div>

  </div></div>
</section>

{{-- ===================== SIMILAR PRODUCTS ===================== --}}
<div class="wrap"><div class="inner section">
  <x-section-head :tag="__('product.similar.tag')" :title="__('product.similar.title')"
                  :more="$product->category ? route('catalog', ['category' => $product->category->slug ?? $product->category->id]) : route('catalog')" :more-label="__('common.view_all')" />
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
          :reviews="$rel->reviewsCount > 0 ? $rel->reviewsCount . ' ' . __('product.similar.reviews') : null" />
    {{-- TODO: Replace with dynamic data from controller --}}
    @empty
      @php
          $similar = [
              ['img' => '/assets/product-facade-paint-bucket.jpg', 'cat' => __('product.similar.cat_paint'), 'name' => __('product.similar.name_paint')],
              ['img' => '/assets/product-mineral-wool-roll.jpg', 'cat' => __('product.similar.cat_insulation'), 'name' => __('product.similar.name_insulation')],
              ['img' => '/assets/product-facade-paint-bucket.jpg', 'cat' => __('product.similar.cat_paint'), 'name' => __('product.similar.name_paint')],
              ['img' => '/assets/product-marble-tile-wide.jpg', 'cat' => __('product.similar.cat_tiles'), 'name' => __('product.similar.name_tiles')],
          ];
      @endphp
      @foreach ($similar as $p)
        <x-pcard :img="$p['img']" :cat="$p['cat']" :name="$p['name']"
                 :now="__('product.similar.fallback_price_now')" :old="__('product.similar.fallback_price_old')" :off="__('product.similar.fallback_discount')"
                 :rate="__('product.similar.fallback_rate')" :reviews="__('product.similar.reviews')" />
      @endforeach
    @endforelse
  </div>
</div></div>

{{-- ===================== FEATURED SPECIALISTS ===================== --}}
<div class="wrap"><div class="inner section">
  {{-- the old page left this "view more" without an href; it now points at /specialists --}}
  <x-section-head :tag="__('product.specialists.tag')" :title="__('product.specialists.title')"
                  :more="route('specialists')" />
  <div class="grid4" id="specGrid">
    @if($specialists->isNotEmpty())
      @foreach ($specialists as $s)
        <x-scard :href="route('specialist.show', $s)"
                 :bg="['#f5fbff', '#fdf5ff', '#f5fffb', '#fff5f5'][$loop->index % 4]"
                 :avatar="$s->user?->avatar ?? '/assets/icon-user.svg'"
                 :role="translate_craft($s->craft)"
                 :rate="null"
                 :reviews="__('product.specialists.reviews_416')"
                 :name="$s->user?->name ?? __('product.specialists.name_1')"
                 :exp="$s->experience_years ? $s->experience_years . ' ' . __('home.specialists.years') : __('product.specialists.exp_12')"
                 :proj="$s->portfolioItems()->count() . ' ' . __('home.specialists.projects')" />
      @endforeach
    @else
      @php
          $fallbackSpecialists = [
              ['bg' => '#f5fbff', 'role' => __('product.specialists.role_tiler'), 'name' => __('product.specialists.name_1')],
              ['bg' => '#fdf5ff', 'role' => __('product.specialists.role_tiler'), 'name' => __('product.specialists.name_1')],
              ['bg' => '#f5fffb', 'role' => __('product.specialists.role_interior'), 'name' => __('product.specialists.name_2')],
              ['bg' => '#fff5f5', 'role' => __('product.specialists.role_tiler'), 'name' => __('product.specialists.name_1')],
          ];
      @endphp
      @foreach ($fallbackSpecialists as $s)
        <x-scard :bg="$s['bg']" :role="$s['role']" :reviews="__('product.specialists.reviews_416')"
                 :name="$s['name']" :exp="__('product.specialists.exp_12')" :proj="'0 ' . __('home.specialists.projects')" />
      @endforeach
    @endif
  </div>
</div></div>

</x-layout>
