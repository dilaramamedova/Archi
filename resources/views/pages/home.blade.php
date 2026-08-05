{{--
  Home page (Figma 628:7820). Ported from the old index.html: the navbar/footer
  placeholders are gone (the layout renders them) and the four card grids, which the
  old page built in JS, are rendered server-side with the shared Blade components.
--}}
@php
    // Hero promo carousel: image + target of each slide, read by resources/js/pages/home.js
    $promoSlides = $heroPromo->isNotEmpty()
        ? $heroPromo->map(fn ($b) => ['img' => $b->image_url, 'href' => $b->button_url ?? route('product'), 'btn' => strip_tags($b->button_text ?? __('common.view_details'))])->toArray()
        : [
            ['img' => '/assets/hero-promo-power-tools.png', 'href' => route('product'), 'btn' => __('common.view_details')],
            ['img' => '/assets/blog-cover-modern-villa.jpg', 'href' => route('blog'), 'btn' => __('common.view_details')],
            ['img' => '/assets/category-laminate-flooring.png', 'href' => route('calculator'), 'btn' => __('common.view_details')],
        ];

    // Fallback arrays used when dynamic collections are empty
    $fallbackCategories = [
        ['img' => '/assets/category-tile-showroom.png', 'name' => __('home.categories.tiles'), 'count' => __('home.categories.count_860'), 'open' => true],
        ['img' => '/assets/category-roofing.png', 'name' => __('home.categories.roofing'), 'count' => __('home.categories.count_340')],
        ['img' => '/assets/category-laminate-flooring.png', 'name' => __('home.categories.laminate'), 'count' => __('home.categories.count_340')],
        ['img' => '/assets/category-electrical.png', 'name' => __('home.categories.electrical'), 'count' => __('home.categories.count_340')],
        ['img' => '/assets/category-plumbing.png', 'name' => __('home.categories.plumbing'), 'count' => __('home.categories.count_340')],
        ['img' => '/assets/category-brick-and-block.png', 'name' => __('home.categories.brick'), 'count' => __('home.categories.count_340')],
        ['img' => '/assets/category-cement-bags.png', 'name' => __('home.categories.cement'), 'count' => __('home.categories.count_340')],
    ];

    $fallbackSpecialists = [
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
      <img src="{{ $heroMain ? $heroMain->image_url : '/assets/hero-bricklayer.jpg' }}" alt="">
      <div class="ov"></div>
      <div class="copy">
        <div class="hero-tag"><span class="line"></span><p>{!! $heroMain->subtitle ?? __('home.hero.tag') !!}</p></div>
        <div>
          <h1>{!! $heroMain->title ?? __('home.hero.title') !!}</h1>
          <p class="sub">{!! $heroMain ? ($heroMain->button_text ?? __('home.hero.subtitle')) : __('home.hero.subtitle') !!}</p>
        </div>
        <div class="hero-info">{!! $heroMain && $heroMain->description ? $heroMain->description : '<p>' . __('home.hero.info') . '</p><p class="u">' . __('home.hero.info_link') . '</p>' !!}</div>
      </div>
    </div>
    <div class="hero-side">
      <div class="hero-promo" id="heroPromo" data-slides="{{ json_encode($promoSlides, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}">
        <img src="/assets/hero-promo-power-tools.png" alt="" id="hpImg">
        <a class="cta-white" id="hpCta" href="{{ route('product') }}"><p id="hpBtnText">{{ __('common.view_details') }}</p><img src="/assets/icon-arrow-right.svg" alt=""></a>
        <div class="dots" id="hpDots"><i class="on"></i><i></i><i></i></div>
      </div>
      {{-- Role banner: slides sliding horizontally (Figma 630:8871 / 630:8835 / 630:8898) --}}
      <div class="hero-role" id="heroRole">
        <div class="hr-track" id="roleTrack">
          @if($heroRole->isNotEmpty())
            @foreach($heroRole as $role)
              <div class="hr-slide">
                <img src="{{ $role->image_url }}" alt="">
                <div class="box">
                  <div class="tag"><span class="line"></span><p>{!! $role->subtitle ?? '' !!}</p></div>
                  <div class="mid">
                    <div>
                      <h3>{!! $role->title !!}</h3>
                      <div class="d">{!! $role->description ?? '' !!}</div>
                    </div>
                    <a class="reg" href="{{ $role->button_url ?? route('register') }}">{!! strip_tags($role->button_text ?? __('common.sign_up')) !!}</a>
                  </div>
                </div>
              </div>
            @endforeach
          @else
            <div class="hr-slide">
              <img src="/assets/hero-tiler-at-work.jpg" alt="">
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
              <img src="/assets/hero-seller-with-tile.png" alt="">
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
              <img src="/assets/hero-customer-in-tile-store.jpg" alt="">
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
          @endif
        </div>
        <div class="dots" id="roleDots">
          @php $roleCount = $heroRole->isNotEmpty() ? $heroRole->count() : 3; @endphp
          @for($i = 0; $i < $roleCount; $i++)
            <i @if($i === 0) class="on" @endif></i>
          @endfor
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ===================== SERVICE STRIP ===================== --}}
<div class="service-strip">
  <div class="inner">
    <div class="svc"><span class="ic"><img src="/assets/icon-user-grey.svg" alt=""></span><div><div class="t1">{{ __('home.services.masters_t1') }}</div><div class="t2">{{ __('home.services.masters_t2') }}</div></div></div>
    <div class="svc"><span class="ic"><img src="/assets/icon-truck-grey.svg" alt=""></span><div><div class="t1">{{ __('home.services.delivery_t1') }}</div><div class="t2">{{ __('home.services.delivery_t2') }}</div></div></div>
    <div class="svc"><span class="ic"><img src="/assets/icon-shield-grey.svg" alt=""></span><div><div class="t1">{{ __('home.services.payment_t1') }}</div><div class="t2">{{ __('home.services.payment_t2') }}</div></div></div>
    <div class="svc"><span class="ic"><img src="/assets/icon-chat-grey.svg" alt=""></span><div><div class="t1">{{ __('home.services.consult_t1') }}</div><div class="t2">{{ __('home.services.consult_t2') }}</div></div></div>
  </div>
</div>

{{-- ===================== CATEGORIES ===================== --}}
<div class="wrap"><div class="inner section">
  <x-section-head :tag="__('home.categories.tag')" :title="__('home.categories.title')" :more="route('catalog')" />
  <div class="cat-row">
    @if($categories->isNotEmpty())
      @foreach ($categories as $c)
        <a @class(['cat-thumb', 'open' => $loop->first]) href="{{ route('catalog', ['category' => $c->slug]) }}"><img src="{{ storage_url($c->image, '/assets/category-tile-showroom.png') }}" alt=""><div class="ov"></div><div class="info"><div><h4>{{ $c->name }}</h4><p>{{ $c->products()->count() }} {{ __('common.products_count') }}</p></div><img src="/assets/icon-arrow-right.svg" alt=""></div></a>
      @endforeach
    @else
      @foreach ($fallbackCategories as $c)
        <a @class(['cat-thumb', 'open' => ! empty($c['open'])]) href="{{ route('catalog') }}"><img src="{{ $c['img'] }}" alt=""><div class="ov"></div><div class="info"><div><h4>{{ $c['name'] }}</h4><p>{{ $c['count'] }}</p></div><img src="/assets/icon-arrow-right.svg" alt=""></div></a>
      @endforeach
    @endif
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
  @if($promoBanners->isNotEmpty() && ($promo = $promoBanners->first()))
    <div class="promo-banner" @if($promo->background_color) style="background-color: {{ $promo->background_color }}" @endif>
      <div class="pb-l">
        <span class="pb-badge">{{ $promo->badge_text ?? __('home.promo.badge') }}</span>
        <div>
          <h3>{{ $promo->title }}</h3>
          <p>{{ $promo->description }}</p>
        </div>
      </div>
      @if($promo->code)
        <button class="pb-copy" type="button" data-code="{{ $promo->code }}" data-on="false">{{ $promo->code }} · <span class="pb-swap"><span class="pb-copy-label">{{ __('home.promo.copy') }}</span><span class="pb-copied-label">{{ __('home.promo.copied') }}</span></span></button>
      @elseif($promo->button_url)
        <a class="pb-copy" href="{{ $promo->button_url }}">{{ $promo->button_text ?? __('common.view_details') }}</a>
      @endif
    </div>
  @else
    <div class="promo-banner">
      <div class="pb-l">
        <span class="pb-badge">{{ __('home.promo.badge') }}</span>
        <div>
          <h3>{{ __('home.promo.title_60') }}</h3>
          <p>{{ __('home.promo.text_before') }} <b>ARCHI60</b> {{ __('home.promo.text_after') }}</p>
        </div>
      </div>
      <button class="pb-copy" type="button" data-code="ARCHI60" data-on="false">ARCHI60 · <span class="pb-swap"><span class="pb-copy-label">{{ __('home.promo.copy') }}</span><span class="pb-copied-label">{{ __('home.promo.copied') }}</span></span></button>
    </div>
  @endif
  <x-section-head :tag="__('home.sale.tag')" :title="__('home.sale.title')" :more="route('catalog', ['sale' => 1])" />
  <div class="grid4" id="campGrid">
    @forelse ($saleProducts as $product)
      <x-pcard :href="route('product.show', $product->slug)"
               :img="$product->mainImageUrl ?? '/assets/product-marble-tile.png'"
               :cat="$product->category?->name ?? __('home.sale.cat_tiles')"
               :name="$product->name"
               :now="number_format($product->price, 2) . ' ₼'"
               :old="$product->old_price && $product->old_price > $product->price ? number_format($product->old_price, 2) . ' ₼' : null"
               :off="$product->old_price && $product->old_price > $product->price && $product->discount_percent ? '-' . $product->discount_percent . '%' : null"
               :rate="$product->reviewsCount > 0 ? number_format($product->averageRating, 1) : null"
               :reviews="$product->reviewsCount > 0 ? '(' . $product->reviewsCount . ' ' . __('common.reviews') . ')' : null" />
    {{-- TODO: Replace with dynamic data from controller --}}
    @empty
      <x-pcard :cat="__('home.sale.cat_tiles')" :name="__('home.sale.name_tile_matte')"
               :now="__('home.sale.price_now_1')" :old="__('home.sale.price_old_1')" :off="__('home.sale.discount_1')" :rate="__('home.sale.rate_1')" :reviews="__('home.sale.reviews_1876')"
               img="/assets/product-marble-tile.png" />
      <x-pcard :cat="__('home.sale.cat_laminate')" :name="__('home.sale.name_laminate')"
               :now="__('home.sale.price_now_2')" :old="__('home.sale.price_old_2')" :off="__('home.sale.discount_2')" :rate="__('home.sale.rate_2')" :reviews="__('home.sale.reviews_640')"
               img="/assets/category-laminate-flooring.png" />
      <x-pcard :cat="__('home.sale.cat_paint')" :name="__('home.sale.name_paint')"
               :now="__('home.sale.price_now_3')" :old="__('home.sale.price_old_3')" :off="__('home.sale.discount_3')" :rate="__('home.sale.rate_3')" :reviews="__('home.sale.reviews_932')"
               img="/assets/hero-modern-house.jpg" />
      <x-pcard :cat="__('home.sale.cat_plumbing')" :name="__('home.sale.name_mixer')"
               :now="__('home.sale.price_now_4')" :old="__('home.sale.price_old_4')" :off="__('home.sale.discount_4')" :rate="__('home.sale.rate_4')" :reviews="__('home.sale.reviews_210')"
               img="/assets/category-plumbing.png" />
    @endforelse
  </div>
</div></div>

{{-- ===================== PRODUCTS ===================== --}}
<div class="wrap"><div class="inner section">
  @if($promoBanners->count() > 1 && ($promo2 = $promoBanners->skip(1)->first()))
    <div class="promo-banner" @if($promo2->background_color) style="background-color: {{ $promo2->background_color }}" @endif>
      <div class="pb-l">
        <span class="pb-badge">{{ $promo2->badge_text ?? __('home.promo.badge') }}</span>
        <div>
          <h3>{{ $promo2->title }}</h3>
          <p>{{ $promo2->description }}</p>
        </div>
      </div>
      @if($promo2->code)
        <button class="pb-copy" type="button" data-code="{{ $promo2->code }}" data-on="false">{{ $promo2->code }} · <span class="pb-swap"><span class="pb-copy-label">{{ __('home.promo.copy') }}</span><span class="pb-copied-label">{{ __('home.promo.copied') }}</span></span></button>
      @elseif($promo2->button_url)
        <a class="pb-copy" href="{{ $promo2->button_url }}">{{ $promo2->button_text ?? __('common.view_details') }}</a>
      @endif
    </div>
  @else
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
  @endif
  <x-section-head :tag="__('home.products.tag')" :title="__('home.products.title')" :more="route('search', ['tab' => 'prod'])" />
  {{-- Products the visitor posted on /sell are stored in localStorage and prepended by home.js --}}
  <div class="grid4" id="prodGrid"
       data-url-product="{{ route('product') }}"
       data-l-cursor="{{ __('common.go_to_product') }}"
       data-l-mine="{{ __('common.your_listing') }}"
       data-l-new="{{ __('home.products.condition_new') }}">
    @forelse ($featuredProducts as $product)
      <x-pcard :href="route('product.show', $product->slug)"
               :img="$product->mainImageUrl ?? '/assets/product-marble-tile.png'"
               :cat="$product->category?->name ?? __('home.products.cat_tiles')"
               :name="$product->name"
               :now="number_format($product->price, 2) . ' ₼'"
               :old="$product->old_price && $product->old_price > $product->price ? number_format($product->old_price, 2) . ' ₼' : null"
               :off="$product->old_price && $product->old_price > $product->price && $product->discount_percent ? '-' . $product->discount_percent . '%' : null"
               :rate="$product->reviewsCount > 0 ? number_format($product->averageRating, 1) : null"
               :reviews="$product->reviewsCount > 0 ? '(' . $product->reviewsCount . ' ' . __('common.reviews') . ')' : null" />
    {{-- TODO: Replace with dynamic data from controller --}}
    @empty
      @for ($i = 0; $i < 4; $i++)
        <x-pcard :cat="__('home.products.cat_tiles')" :name="__('home.products.name_tile_matte')"
                 :now="__('home.products.fallback_price_now')" :old="__('home.products.fallback_price_old')" :off="__('home.products.fallback_discount')" :rate="__('home.products.fallback_rate')" :reviews="__('home.products.reviews_1876')" />
      @endfor
    @endforelse
  </div>
</div></div>

{{-- ===================== SPECIALISTS ===================== --}}
<div class="wrap"><div class="inner section">
  <x-section-head :tag="__('home.specialists.tag')" :title="__('home.specialists.title')" :more="route('search', ['tab' => 'usta'])" />
  <div class="grid4" id="specGrid">
    @if($specialists->isNotEmpty())
      @foreach ($specialists as $s)
        <x-scard :href="route('specialist.show', $s)"
                 :bg="['#f5fbff', '#fdf5ff', '#f5fffb', '#fff5f5'][$loop->index % 4]"
                 :avatar="$s->user?->avatar ?? '/assets/icon-user.svg'"
                 :role="translate_craft($s->craft)"
                 :rate="null"
                 :reviews="null"
                 :name="$s->user?->name ?? __('home.specialists.name_1')"
                 :exp="$s->experience_years ? $s->experience_years . ' ' . __('home.specialists.years') : __('home.specialists.exp_12')"
                 :proj="$s->portfolioItems()->count() . ' ' . __('home.specialists.projects')" />
      @endforeach
    @else
      {{-- TODO: Replace with dynamic data from controller --}}
      @foreach ($fallbackSpecialists as $s)
        <x-scard :bg="$s['bg']" :role="$s['role']" :rate="__('home.specialists.fallback_rate')" :reviews="__('home.specialists.reviews_416')"
                 :name="$s['name']" :exp="__('home.specialists.exp_12')" :proj="'0 ' . __('home.specialists.projects')" />
      @endforeach
    @endif
  </div>
</div></div>

{{-- ===================== BLOG ===================== --}}
<div class="wrap"><div class="inner section">
  <x-section-head :tag="__('home.blog.tag')" :title="__('home.blog.title')" :more="route('blog')" />
  <div class="blog-grid" id="blogGrid">
    @forelse ($blogPosts as $post)
      <x-post :href="route('blog.show', $post->slug)"
              :img="$post->cover_image_url"
              :time="$post->reading_time"
              :title="$post->title"
              :excerpt="$post->excerpt" />
    @empty
      @for ($i = 1; $i <= 4; $i++)
        <x-post :href="route('blog.article')" :time="__('home.blog.time_' . $i)" :title="__('home.blog.title_' . $i)" :excerpt="__('home.blog.excerpt_' . $i)" />
      @endfor
    @endforelse
  </div>
</div></div>

</x-layout>
