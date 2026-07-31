{{--
  Product page (Figma 572:10504). Ported from the old product.html: the navbar/footer
  placeholders are gone (the layout renders them) and everything the old page built in
  JS — the "bought together" cards, the rating distribution, the review cards and the
  two card grids — is rendered server-side here. The round card cursor comes from the
  shared resources/js/shared/cursor.js.
--}}
@php
    $thumbs = [
        '/assets/product-marble-tile-square.jpg',
        '/assets/interior-marble-corridor.jpg',
        '/assets/interior-marble-corridor.jpg',
        '/assets/interior-marble-corridor.jpg',
    ];

    $specs = ['size', 'thickness', 'surface', 'material', 'color', 'box', 'slip', 'usage', 'warranty', 'country'];

    // Rating distribution (Figma values): stars => count, the bar is scaled to the largest
    $dist = [5 => 2890, 4 => 920, 3 => 380, 2 => 150, 1 => 79];
    $distMax = 2890;

    // The 5th card repeats the 4th one, exactly like the old page
    $reviewCards = [
        ['i' => 1, 'bg' => '#eef1f4', 'fg' => '#5a6472', 'help' => 12],
        ['i' => 2, 'bg' => '#e8f5ec', 'fg' => '#2f7d44', 'help' => 8],
        ['i' => 3, 'bg' => '#fdf3e3', 'fg' => '#b07626', 'help' => 17],
        ['i' => 4, 'bg' => '#e9f0fb', 'fg' => '#3c62a8', 'help' => 5],
        ['i' => 4, 'bg' => '#e9f0fb', 'fg' => '#3c62a8', 'help' => 5],
    ];

    $similar = [
        ['img' => '/assets/product-facade-paint-bucket.jpg', 'cat' => __('product.similar.cat_paint'), 'name' => __('product.similar.name_paint')],
        ['img' => '/assets/product-mineral-wool-roll.jpg', 'cat' => __('product.similar.cat_insulation'), 'name' => __('product.similar.name_insulation')],
        ['img' => '/assets/product-facade-paint-bucket.jpg', 'cat' => __('product.similar.cat_paint'), 'name' => __('product.similar.name_paint')],
        ['img' => '/assets/product-marble-tile-wide.jpg', 'cat' => __('product.similar.cat_tiles'), 'name' => __('product.similar.name_tiles')],
    ];

    $specialists = [
        ['bg' => '#f5fbff', 'role' => __('product.specialists.role_tiler'), 'name' => __('product.specialists.name_1')],
        ['bg' => '#fdf5ff', 'role' => __('product.specialists.role_tiler'), 'name' => __('product.specialists.name_1')],
        ['bg' => '#f5fffb', 'role' => __('product.specialists.role_interior'), 'name' => __('product.specialists.name_2')],
        ['bg' => '#fff5f5', 'role' => __('product.specialists.role_tiler'), 'name' => __('product.specialists.name_1')],
    ];
@endphp
<x-layout page="product" :title="__('product.title')">

<section class="pd">
  <div class="wrap"><div class="inner">

    {{-- breadcrumb --}}
    <x-ui.breadcrumbs class="pd-crumb" :items="[
        ['label' => __('common.home'), 'href' => route('home')],
        ['label' => __('product.crumb.flooring'), 'href' => '#'],
        ['label' => __('product.crumb.category'), 'href' => '#'],
        ['label' => __('product.crumb.current')],
    ]" />

    {{-- ===================== TOP: gallery + info ===================== --}}
    <div class="pd-top">
      {{-- gallery --}}
      <div class="pd-gallery">
        <div class="pd-thumbs" id="pdThumbs">
          @foreach ($thumbs as $i => $t)
            <div class="pd-thumb" data-on="{{ $i === 0 ? 'true' : 'false' }}"><img src="{{ $t }}" alt=""></div>
          @endforeach
        </div>
        <div class="pd-main">
          <div class="badges"><span class="b new">{{ __('common.badge_new') }}</span><span class="b">{{ __('common.badge_in_stock') }}</span></div>
          <div class="heart" id="pdHeartTop" data-liked="false"><img src="/assets/icon-heart-pointed.svg" alt="{{ __('product.gallery.favorite') }}"></div>
          <img id="pdMainImg" src="/assets/product-marble-tile-square.jpg" alt="{{ __('product.crumb.current') }}">
        </div>
      </div>

      {{-- info --}}
      <div class="pd-info">
        <div class="cat">{{ __('product.info.cat') }}</div>
        <h1>{{ __('product.info.name') }}</h1>

        <div class="pd-meta">
          <div class="pd-stars">
            <span class="s"><x-ui.stars /></span>
            <b>4.6</b>
          </div>
          <span class="rev" data-goto="reviews">{{ __('product.info.reviews') }}</span>
          <span class="dot"></span>
          <span class="sold">{{ __('product.info.sold') }}</span>
        </div>

        <div class="pd-price">
          <span class="now">23.90 ₼</span>
          <span class="old">45.99 ₼</span>
          <span class="off">-48%</span>
        </div>
        <div class="pd-unit">{{ __('product.info.unit') }}</div>

        <div class="pd-line"></div>

        <div class="pd-feats">
          <div class="f"><span class="tick"><img src="/assets/icon-check-green.svg" alt=""></span> {{ __('product.info.feat_1') }}</div>
          <div class="f"><span class="tick"><img src="/assets/icon-check-green.svg" alt=""></span> {{ __('product.info.feat_2') }}</div>
          <div class="f"><span class="tick"><img src="/assets/icon-check-green.svg" alt=""></span> {{ __('product.info.feat_3') }}</div>
        </div>

        <div class="pd-stock"><i></i> {{ __('product.info.stock') }}</div>

        <div class="pd-buy">
          <div class="pd-qty">
            <button type="button" id="qtyMinus" aria-label="{{ __('product.info.qty_minus') }}">−</button>
            <input type="number" id="qtyVal" value="1" min="1" aria-label="{{ __('product.info.qty') }}">
            <button type="button" id="qtyPlus" aria-label="{{ __('product.info.qty_plus') }}">+</button>
          </div>
          {{-- The labels/values the "add to cart" behaviour needs come from data-* --}}
          <button class="pd-add" id="addCart" data-added="false"
                  data-label-add="{{ __('product.info.add_cart') }}"
                  data-label-added="{{ __('product.info.added') }}"
                  data-label-in-cart="{{ __('product.info.in_cart') }}"
                  data-cart-unit="{{ __('product.info.cart_unit') }}"
                  data-cart-brand="{{ __('common.site_name') }}"
                  data-cart-stock="{{ __('common.badge_in_stock') }}"><img src="/assets/icon-cart.svg" alt="">{{ __('product.info.add_cart') }}</button>
          <button class="pd-wish" id="pdWish" data-liked="false" aria-label="{{ __('product.info.wish') }}"><img src="/assets/icon-heart-pointed.svg" alt=""></button>
        </div>

        <div class="pd-assure">
          <div class="a"><span class="ic"><img src="/assets/icon-check-green.svg" alt=""></span><div><div class="t1">{{ __('product.info.delivery_t1') }}</div><div class="t2">{{ __('product.info.delivery_t2') }}</div></div></div>
          <div class="a"><span class="ic"><img src="/assets/icon-check-green.svg" alt=""></span><div><div class="t1">{{ __('product.info.return_t1') }}</div><div class="t2">{{ __('product.info.return_t2') }}</div></div></div>
        </div>

        {{-- seller / store --}}
        <div class="pd-seller">
          <div class="ps-top">
            <div class="ps-logo"><img src="/assets/icon-tower-crane.svg" alt=""></div>
            <div class="ps-id">
              <div class="ps-name">{{ __('product.seller.name') }}</div>
              <div class="ps-sub">{{ __('product.seller.sub') }}</div>
            </div>
            <span class="ps-vf"><img src="/assets/icon-check-green.svg" alt="">{{ __('common.badge_verified') }}</span>
          </div>
          <div class="ps-stats">
            <div class="ps-stat"><b><img src="/assets/icon-star-yellow.svg" alt="">4.8</b><span>{{ __('product.seller.rating_label') }}</span></div>
            <div class="ps-stat"><b>1,240</b><span>{{ __('product.seller.products_label') }}</span></div>
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
          <p>{{ __('product.about.p1') }}</p>
          <p>{{ __('product.about.p2') }}</p>
          <h4>{{ __('product.about.h4') }}</h4>
          <p>{{ __('product.about.p3') }}</p>
        </div>
      </div>

      <div class="pd-pane" data-on="false" data-pane="specs">
        <div class="pd-specs">
          @foreach ($specs as $s)
            <div class="row"><div class="k">{{ __('product.specs.' . $s . '_k') }}</div><div class="v">{{ __('product.specs.' . $s . '_v') }}</div></div>
          @endforeach
        </div>
      </div>
    </div>

    {{-- ===================== FREQUENTLY BOUGHT TOGETHER ===================== --}}
    <div class="pd-section">
      <div class="sec-tag"><span class="line"></span><p>{{ __('product.fbt.tag') }}</p></div>
      <div class="sec-title mb-2.5">{{ __('product.fbt.title') }}</div>
      <p class="pdb-lead">{{ __('product.fbt.lead') }}</p>

      <div class="fbt-cards" id="fbtCards">
        @foreach ([true, false, false] as $k => $isMain)
          @if ($k > 0)<div class="fbt-plus">+</div>@endif
          @php
              $fbtBadges = [];
              if ($isMain) {
                  $fbtBadges[] = ['icon' => '/assets/icon-check-green.svg', 'class' => 'fbt-ok'];
                  $fbtBadges[] = ['label' => __('product.fbt.badge_this'), 'mine' => true];
              }
              $fbtBadges[] = ['label' => __('common.badge_in_stock')];
          @endphp
          <x-pcard img="/assets/product-marble-tile-wide.jpg" :badges="$fbtBadges"
                   rate="4.4" :reviews="__('product.fbt.card_reviews')"
                   :cat="__('product.fbt.card_cat')" :name="__('product.fbt.card_name')"
                   now="23.90 ₼" old="15,99 ₼" off="-48%" />
        @endforeach

        <div class="fbt-panel">
          <span class="lbl">{{ __('product.fbt.selected') }}</span>
          <div class="tot"><b>47.30 ₼</b><span class="old">75.99 ₼</span></div>
          <div class="fbt-save"><span class="ic"><img src="/assets/icon-check-green.svg" alt=""></span><p>{{ __('product.fbt.save_1') }}<br>{{ __('product.fbt.save_2') }}</p></div>
          <button class="fbt-addall" type="button"><img src="/assets/icon-cart.svg" alt="">{{ __('product.fbt.add_all') }}</button>
          <div class="fbt-hint">{{ __('product.fbt.hint') }}</div>
        </div>
      </div>
    </div>

    {{-- ===================== RATING / REVIEWS ===================== --}}
    <div class="pd-section" id="reviews">
      <div class="sec-tag"><span class="line"></span><p>{{ __('product.reviews.tag') }}</p></div>
      <div class="sec-title mb-6">{{ __('product.reviews.title') }}</div>

      <div class="rev-summary">
        <div class="rs-score">
          <div class="num">4.3</div>
          <div class="s"><x-ui.stars /></div>
          <div class="c1">{{ __('product.reviews.ratings') }}</div>
          <div class="c2">{{ __('product.reviews.written') }}</div>
        </div>
        <div class="rs-div"></div>
        <div class="rs-dist" id="rsDist">
          @foreach ($dist as $stars => $count)
            <div class="row">
              <span class="st">{{ $stars }} <img src="/assets/icon-star-yellow.svg" alt=""></span>
              <span class="track"><span style="width:{{ round($count / $distMax * 100) }}%"></span></span>
              <span class="cnt">{{ $count }}</span>
            </div>
          @endforeach
        </div>
        <div class="rs-div"></div>
        <div class="rs-rec">
          <div class="num">92%</div>
          <div class="t">{{ __('product.reviews.recommend') }}</div>
          <div class="track"><span style="width:92%"></span></div>
        </div>
      </div>

      <div class="rev-filter">
        <button type="button" data-on="true">{{ __('product.reviews.filter_helpful') }}</button>
        <button type="button" data-on="false">{{ __('product.reviews.filter_newest') }}</button>
        <button type="button" data-on="false">{{ __('product.reviews.filter_photo') }}</button>
        <button type="button" data-on="false">{{ __('product.reviews.filter_5star') }}</button>
      </div>

      <div class="rev-cards" id="revCards">
        @foreach ($reviewCards as $r)
          <div class="rev-card">
            <div class="h">
              <div class="av" style="background:{{ $r['bg'] }};color:{{ $r['fg'] }}">{{ __('product.reviews.ini_' . $r['i']) }}</div>
              <div>
                <div class="nm">{{ __('product.reviews.name_' . $r['i']) }}</div>
                <div class="vf"><img src="/assets/icon-check-green.svg" alt="">{{ __('product.reviews.verified') }}</div>
              </div>
            </div>
            <div class="sd"><span class="s"><x-ui.stars /></span><span class="date">{{ __('product.reviews.date_' . $r['i']) }}</span></div>
            <div class="txt">{{ __('product.reviews.text_' . $r['i']) }}</div>
            <button type="button" class="help" data-on="false" aria-pressed="false">{{ __('product.reviews.helpful') }}&nbsp;&nbsp;·&nbsp;&nbsp;<span class="n">{{ $r['help'] }}</span></button>
          </div>
        @endforeach
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
                  :more="route('home')" :more-label="__('common.view_all')" />
  <div class="grid4" id="simGrid">
    @foreach ($similar as $p)
      <x-pcard :img="$p['img']" :cat="$p['cat']" :name="$p['name']"
               now="23.90 ₼" old="15,99 ₼" off="-48%"
               rate="4.4" :reviews="__('product.similar.reviews')" />
    @endforeach
  </div>
</div></div>

{{-- ===================== FEATURED SPECIALISTS ===================== --}}
<div class="wrap"><div class="inner section">
  {{-- the old page left this "view more" without an href; it now points at /specialists --}}
  <x-section-head :tag="__('product.specialists.tag')" :title="__('product.specialists.title')"
                  :more="route('specialists')" />
  <div class="grid4" id="specGrid">
    @foreach ($specialists as $s)
      <x-scard :bg="$s['bg']" :role="$s['role']" rate="4.9" :reviews="__('product.specialists.reviews_416')"
               :name="$s['name']" :exp="__('product.specialists.exp_12')" :proj="__('product.specialists.proj_320')" />
    @endforeach
  </div>
</div></div>

</x-layout>
