{{--
  Home page (Figma 628:7820). Ported from the old index.html: the navbar/footer
  placeholders are gone (the layout renders them) and the four card grids, which the
  old page built in JS, are rendered server-side with the shared Blade components.
--}}
@php
    // Hero promo carousel: image + target of each slide, read by resources/js/pages/home.js
    $promoSlides = [
        ['img' => '/assets/hero-promo.png', 'href' => route('product')],
        ['img' => '/assets/blog-hero.jpg', 'href' => route('blog')],
        ['img' => '/assets/cat-laminant.png', 'href' => route('calculator')],
    ];

    $categories = [
        ['img' => '/assets/cat-bathroom.png', 'name' => __('home.categories.tiles'), 'count' => __('home.categories.count_860'), 'open' => true],
        ['img' => '/assets/cat-roof.png', 'name' => __('home.categories.roofing'), 'count' => __('home.categories.count_340')],
        ['img' => '/assets/cat-laminant.png', 'name' => __('home.categories.laminate'), 'count' => __('home.categories.count_340')],
        ['img' => '/assets/cat-electric.png', 'name' => __('home.categories.electrical'), 'count' => __('home.categories.count_340')],
        ['img' => '/assets/cat-sink.png', 'name' => __('home.categories.plumbing'), 'count' => __('home.categories.count_340')],
        ['img' => '/assets/cat-brick.png', 'name' => __('home.categories.brick'), 'count' => __('home.categories.count_340')],
        ['img' => '/assets/cat-cement.png', 'name' => __('home.categories.cement'), 'count' => __('home.categories.count_340')],
    ];

    $specialists = [
        ['bg' => '#f5fbff', 'role' => __('home.specialists.role_tiler'), 'name' => __('home.specialists.name_1')],
        ['bg' => '#fdf5ff', 'role' => __('home.specialists.role_tiler'), 'name' => __('home.specialists.name_1')],
        ['bg' => '#f5fffb', 'role' => __('home.specialists.role_interior'), 'name' => __('home.specialists.name_2')],
        ['bg' => '#fff5f5', 'role' => __('home.specialists.role_tiler'), 'name' => __('home.specialists.name_1')],
    ];
@endphp
<x-layout page="home" :title="__('home.title')">

{{-- ===================== HERO ===================== --}}
<div class="hero">
  <div class="inner hero-grid">
    <div class="hero-main">
      <img src="/assets/hero-main-usta.jpg" alt="">
      <div class="ov"></div>
      <div class="copy">
        <div class="hero-tag"><span class="line"></span><p>{{ __('home.hero.tag') }}</p></div>
        <div>
          <h1>{{ __('home.hero.title') }}</h1>
          <p class="sub">{{ __('home.hero.subtitle') }}</p>
        </div>
        <div class="hero-info"><p>{{ __('home.hero.info') }}</p><p class="u">{{ __('home.hero.info_link') }}</p></div>
      </div>
    </div>
    <div class="hero-side">
      <div class="hero-promo" id="heroPromo" data-slides="{{ json_encode($promoSlides, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}">
        <img src="/assets/hero-promo.png" alt="" id="hpImg">
        <a class="cta-white" id="hpCta" href="{{ route('product') }}"><p>{{ __('common.view_details') }}</p><img src="/assets/ic-arrow.svg" alt=""></a>
        <div class="dots" id="hpDots"><i class="on"></i><i></i><i></i></div>
      </div>
      {{-- Role banner: 3 slides sliding horizontally (Figma 630:8871 / 630:8835 / 630:8898) --}}
      <div class="hero-role" id="heroRole">
        <div class="hr-track" id="roleTrack">
          <div class="hr-slide">
            <img src="/assets/hero-usta.jpg" alt="">
            <div class="box">
              <div class="tag"><span class="line"></span><p>{{ __('home.roles.master_tag') }}</p></div>
              <div class="mid">
                <div>
                  <h3>{{ __('home.roles.master_title') }}</h3>
                  <div class="d">{{ __('home.roles.master_line1') }}<br>{{ __('home.roles.master_line2') }}</div>
                </div>
                <a class="reg" href="{{ route('register', ['role' => 'master']) }}">{{ __('common.sign_up') }}</a>
              </div>
            </div>
          </div>
          <div class="hr-slide">
            <img src="/assets/hero-satici.png" alt="">
            <div class="box">
              <div class="tag"><span class="line"></span><p>{{ __('home.roles.seller_tag') }}</p></div>
              <div class="mid">
                <div>
                  <h3>{{ __('home.roles.seller_title') }}</h3>
                  <div class="d">{{ __('home.roles.seller_line1') }}<br>{{ __('home.roles.seller_line2') }}</div>
                </div>
                <a class="reg" href="{{ route('register', ['role' => 'seller']) }}">{{ __('common.sign_up') }}</a>
              </div>
            </div>
          </div>
          <div class="hr-slide">
            <img src="/assets/hero-musteri.jpg" alt="">
            <div class="box">
              <div class="tag"><span class="line"></span><p>{{ __('home.roles.customer_tag') }}</p></div>
              <div class="mid">
                <div>
                  <h3>{{ __('home.roles.customer_title') }}</h3>
                  <div class="d">{{ __('home.roles.customer_line1') }}<br>{{ __('home.roles.customer_line2') }}</div>
                </div>
                <a class="reg" href="{{ route('register', ['role' => 'buyer']) }}">{{ __('common.sign_up') }}</a>
              </div>
            </div>
          </div>
        </div>
        <div class="dots" id="roleDots"><i class="on"></i><i></i><i></i></div>
      </div>
    </div>
  </div>
</div>

{{-- ===================== SERVICE STRIP ===================== --}}
<div class="service-strip">
  <div class="inner">
    <div class="svc"><span class="ic"><img src="/assets/svc-person.svg" alt=""></span><div><div class="t1">{{ __('home.services.masters_t1') }}</div><div class="t2">{{ __('home.services.masters_t2') }}</div></div></div>
    <div class="svc"><span class="ic"><img src="/assets/svc-delivery.svg" alt=""></span><div><div class="t1">{{ __('home.services.delivery_t1') }}</div><div class="t2">{{ __('home.services.delivery_t2') }}</div></div></div>
    <div class="svc"><span class="ic"><img src="/assets/svc-security.svg" alt=""></span><div><div class="t1">{{ __('home.services.payment_t1') }}</div><div class="t2">{{ __('home.services.payment_t2') }}</div></div></div>
    <div class="svc"><span class="ic"><img src="/assets/svc-message.svg" alt=""></span><div><div class="t1">{{ __('home.services.consult_t1') }}</div><div class="t2">{{ __('home.services.consult_t2') }}</div></div></div>
  </div>
</div>

{{-- ===================== CATEGORIES ===================== --}}
<div class="wrap"><div class="inner section">
  <x-section-head :tag="__('home.categories.tag')" :title="__('home.categories.title')" />
  <div class="cat-row">
    @foreach ($categories as $c)
      <a @class(['cat-thumb', 'open' => ! empty($c['open'])]) href="{{ route('catalog') }}"><img src="{{ $c['img'] }}" alt=""><div class="ov"></div><div class="info"><div><h4>{{ $c['name'] }}</h4><p>{{ $c['count'] }}</p></div><img src="/assets/ic-arrow.svg" alt=""></div></a>
    @endforeach
  </div>
</div></div>

{{-- ===================== SALE MARQUEE ===================== --}}
<div class="sale-marquee" aria-hidden="true">
  <div class="sale-track">
    @for ($i = 0; $i < 10; $i++)<span>{{ __('home.sale.marquee') }}</span>@endfor
  </div>
</div>

{{-- ===================== CAMPAIGN (SALE) ===================== --}}
<div class="wrap"><div class="inner section">
  <div class="promo-banner">
    <div class="pb-l">
      <span class="pb-badge">{{ __('home.promo.badge') }}</span>
      <div>
        <h3>{{ __('home.promo.title_60') }}</h3>
        <p>{{ __('home.promo.text_before') }} <b>ARCHI60</b> {{ __('home.promo.text_after') }}</p>
      </div>
    </div>
    {{-- Both labels are stacked in one grid cell, so the confirmation never resizes the button --}}
    <button class="pb-copy" type="button" data-code="ARCHI60" data-on="false">ARCHI60 · <span class="pb-swap"><span class="pb-copy-label">{{ __('home.promo.copy') }}</span><span class="pb-copied-label">{{ __('home.promo.copied') }}</span></span></button>
  </div>
  <x-section-head :tag="__('home.sale.tag')" :title="__('home.sale.title')" :more="route('cart')" />
  <div class="grid4" id="campGrid">
    <x-pcard :cat="__('home.sale.cat_tiles')" :name="__('home.sale.name_tile_matte')"
             now="23.90 ₼" old="45.99 ₼" off="-48%" rate="4.6" :reviews="__('home.sale.reviews_1876')"
             img="/assets/prod-kafel.png" />
    <x-pcard :cat="__('home.sale.cat_laminate')" :name="__('home.sale.name_laminate')"
             now="29.90 ₼" old="42.00 ₼" off="-29%" rate="4.8" :reviews="__('home.sale.reviews_640')"
             img="/assets/cat-laminant.png" />
    <x-pcard :cat="__('home.sale.cat_paint')" :name="__('home.sale.name_paint')"
             now="49.00 ₼" old="72.00 ₼" off="-32%" rate="4.7" :reviews="__('home.sale.reviews_932')"
             img="/assets/hero-main.jpg" />
    <x-pcard :cat="__('home.sale.cat_plumbing')" :name="__('home.sale.name_mixer')"
             now="64.00 ₼" old="95.00 ₼" off="-33%" rate="4.9" :reviews="__('home.sale.reviews_210')"
             img="/assets/cat-sink.png" />
  </div>
</div></div>

{{-- ===================== PRODUCTS ===================== --}}
<div class="wrap"><div class="inner section">
  <div class="promo-banner">
    <div class="pb-l">
      <span class="pb-badge">{{ __('home.promo.badge') }}</span>
      <div>
        <h3>{{ __('home.promo.title_15') }}</h3>
        <p>{{ __('home.promo.text_before') }} <b>ARCHI15</b> {{ __('home.promo.text_after') }}</p>
      </div>
    </div>
    <button class="pb-copy" type="button" data-code="ARCHI15" data-on="false">ARCHI15 · <span class="pb-swap"><span class="pb-copy-label">{{ __('home.promo.copy') }}</span><span class="pb-copied-label">{{ __('home.promo.copied') }}</span></span></button>
  </div>
  <x-section-head :tag="__('home.products.tag')" :title="__('home.products.title')" :more="route('search', ['tab' => 'prod'])" />
  {{-- Products the visitor posted on /sell are stored in localStorage and prepended by home.js --}}
  <div class="grid4" id="prodGrid"
       data-url-product="{{ route('product') }}"
       data-l-cursor="{{ __('common.go_to_product') }}"
       data-l-mine="{{ __('common.your_listing') }}"
       data-l-new="{{ __('home.products.condition_new') }}">
    @for ($i = 0; $i < 4; $i++)
      <x-pcard :cat="__('home.products.cat_tiles')" :name="__('home.products.name_tile_matte')"
               now="23.90 ₼" old="15,99 ₼" off="-48%" rate="4.4" :reviews="__('home.products.reviews_1876')" />
    @endfor
  </div>
</div></div>

{{-- ===================== SPECIALISTS ===================== --}}
<div class="wrap"><div class="inner section">
  <x-section-head :tag="__('home.specialists.tag')" :title="__('home.specialists.title')" :more="route('search', ['tab' => 'usta'])" />
  <div class="grid4" id="specGrid">
    @foreach ($specialists as $s)
      <x-scard :bg="$s['bg']" :role="$s['role']" rate="4.9" :reviews="__('home.specialists.reviews_416')"
               :name="$s['name']" :exp="__('home.specialists.exp_12')" :proj="__('home.specialists.proj_320')" />
    @endforeach
  </div>
</div></div>

{{-- ===================== BLOG ===================== --}}
<div class="wrap"><div class="inner section">
  <x-section-head :tag="__('home.blog.tag')" :title="__('home.blog.title')" />
  <div class="blog-grid" id="blogGrid">
    @for ($i = 1; $i <= 4; $i++)
      <x-post :href="route('blog.article')" :time="__('home.blog.time_' . $i)" :title="__('home.blog.title_' . $i)" :excerpt="__('home.blog.excerpt_' . $i)" />
    @endfor
  </div>
</div></div>

</x-layout>
