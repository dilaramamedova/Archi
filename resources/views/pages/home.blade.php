{{--
  Home page (Figma 628:7820). Ported from the old index.html: the navbar/footer
  placeholders are gone (the layout renders them) and the four card grids, which the
  old page built in JS, are rendered server-side with the shared Blade components.
--}}
@php
    // Hero promo carousel: image + target of each slide, read by resources/js/pages/home.js
    $promoSlides = $heroPromo->isNotEmpty()
        ? $heroPromo->map(fn ($b) => ['img' => $b->image_url, 'href' => $b->button_url ?? route('product'), 'btn' => strip_tags($b->button_text ?? t('common.view_details'))])->toArray()
        : [
            ['img' => '/assets/hero-promo-power-tools.png', 'href' => route('product'), 'btn' => t('common.view_details')],
            ['img' => '/assets/blog-cover-modern-villa.jpg', 'href' => route('blog'), 'btn' => t('common.view_details')],
            ['img' => '/assets/category-laminate-flooring.png', 'href' => route('calculator'), 'btn' => t('common.view_details')],
        ];

    // Fallback arrays used when dynamic collections are empty
    $fallbackCategories = [
        ['img' => '/assets/category-tile-showroom.png', 'name' => t('home.categories.tiles'), 'count' => t('home.categories.count_860'), 'open' => true],
        ['img' => '/assets/category-roofing.png', 'name' => t('home.categories.roofing'), 'count' => t('home.categories.count_340')],
        ['img' => '/assets/category-laminate-flooring.png', 'name' => t('home.categories.laminate'), 'count' => t('home.categories.count_340')],
        ['img' => '/assets/category-electrical.png', 'name' => t('home.categories.electrical'), 'count' => t('home.categories.count_340')],
        ['img' => '/assets/category-plumbing.png', 'name' => t('home.categories.plumbing'), 'count' => t('home.categories.count_340')],
        ['img' => '/assets/category-brick-and-block.png', 'name' => t('home.categories.brick'), 'count' => t('home.categories.count_340')],
        ['img' => '/assets/category-cement-bags.png', 'name' => t('home.categories.cement'), 'count' => t('home.categories.count_340')],
    ];

    $fallbackSpecialists = [
        ['bg' => '#f5fbff', 'role' => t('home.specialists.role_tiler'), 'name' => t('home.specialists.name_1')],
        ['bg' => '#fdf5ff', 'role' => t('home.specialists.role_tiler'), 'name' => t('home.specialists.name_1')],
        ['bg' => '#f5fffb', 'role' => t('home.specialists.role_interior'), 'name' => t('home.specialists.name_2')],
        ['bg' => '#fff5f5', 'role' => t('home.specialists.role_tiler'), 'name' => t('home.specialists.name_1')],
    ];
@endphp
<x-layout page="home" :title="t('home.title')">

{{-- ===================== HERO ===================== --}}
<div class="hero">
  <div class="inner hero-grid">
    <div class="hero-main">
      <img src="{{ $heroMain ? $heroMain->image_url : '/assets/hero-bricklayer.jpg' }}" alt="">
      <div class="ov"></div>
      <div class="copy">
        <div class="hero-tag"><span class="line"></span><p>{!! $heroMain->subtitle ?? t('home.hero.tag') !!}</p></div>
        <div>
          <h1>{!! $heroMain->title ?? t('home.hero.title') !!}</h1>
          <p class="sub">{!! $heroMain ? ($heroMain->button_text ?? t('home.hero.subtitle')) : t('home.hero.subtitle') !!}</p>
        </div>
        <div class="hero-info">{!! $heroMain && $heroMain->description ? $heroMain->description : '<p>' . t('home.hero.info') . '</p><p class="u">' . t('home.hero.info_link') . '</p>' !!}</div>
      </div>
    </div>
    <div class="hero-side">
      <div class="hero-promo" id="heroPromo" data-slides="{{ json_encode($promoSlides, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}">
        <img src="/assets/hero-promo-power-tools.png" alt="" id="hpImg">
        <a class="cta-white" id="hpCta" href="{{ route('product') }}"><p id="hpBtnText">{{ t('common.view_details') }}</p><img src="/assets/icon-arrow-right.svg" alt=""></a>
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
                    <a class="reg" href="{{ $role->button_url ?? route('register') }}">{!! strip_tags($role->button_text ?? t('common.sign_up')) !!}</a>
                  </div>
                </div>
              </div>
            @endforeach
          @else
            <div class="hr-slide">
              <img src="/assets/hero-tiler-at-work.jpg" alt="">
              <div class="box">
                <div class="tag"><span class="line"></span><p>{{ t('home.roles.master_tag') }}</p></div>
                <div class="mid">
                  <div>
                    <h3>{{ t('home.roles.master_title') }}</h3>
                    <div class="d">{{ t('home.roles.master_line1') }}<br>{{ t('home.roles.master_line2') }}</div>
                  </div>
                  <a class="reg" href="{{ route('register', ['role' => 'master']) }}">{{ t('common.sign_up') }}</a>
                </div>
              </div>
            </div>
            <div class="hr-slide">
              <img src="/assets/hero-seller-with-tile.png" alt="">
              <div class="box">
                <div class="tag"><span class="line"></span><p>{{ t('home.roles.seller_tag') }}</p></div>
                <div class="mid">
                  <div>
                    <h3>{{ t('home.roles.seller_title') }}</h3>
                    <div class="d">{{ t('home.roles.seller_line1') }}<br>{{ t('home.roles.seller_line2') }}</div>
                  </div>
                  <a class="reg" href="{{ route('register', ['role' => 'seller']) }}">{{ t('common.sign_up') }}</a>
                </div>
              </div>
            </div>
            <div class="hr-slide">
              <img src="/assets/hero-customer-in-tile-store.jpg" alt="">
              <div class="box">
                <div class="tag"><span class="line"></span><p>{{ t('home.roles.customer_tag') }}</p></div>
                <div class="mid">
                  <div>
                    <h3>{{ t('home.roles.customer_title') }}</h3>
                    <div class="d">{{ t('home.roles.customer_line1') }}<br>{{ t('home.roles.customer_line2') }}</div>
                  </div>
                  <a class="reg" href="{{ route('register', ['role' => 'buyer']) }}">{{ t('common.sign_up') }}</a>
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
    @forelse ($serviceIcons as $svc)
      <div class="svc"><span class="ic"><img src="{{ $svc->icon_url }}" alt=""></span><div><div class="t1">{{ $svc->title }}</div><div class="t2">{{ $svc->text }}</div></div></div>
    @empty
      <div class="svc"><span class="ic"><img src="/assets/icon-user-grey.svg" alt=""></span><div><div class="t1">{{ t('home.services.masters_t1') }}</div><div class="t2">{{ t('home.services.masters_t2') }}</div></div></div>
      <div class="svc"><span class="ic"><img src="/assets/icon-truck-grey.svg" alt=""></span><div><div class="t1">{{ t('home.services.delivery_t1') }}</div><div class="t2">{{ t('home.services.delivery_t2') }}</div></div></div>
      <div class="svc"><span class="ic"><img src="/assets/icon-shield-grey.svg" alt=""></span><div><div class="t1">{{ t('home.services.payment_t1') }}</div><div class="t2">{{ t('home.services.payment_t2') }}</div></div></div>
      <div class="svc"><span class="ic"><img src="/assets/icon-chat-grey.svg" alt=""></span><div><div class="t1">{{ t('home.services.consult_t1') }}</div><div class="t2">{{ t('home.services.consult_t2') }}</div></div></div>
    @endforelse
  </div>
</div>

{{-- ===================== CATEGORIES ===================== --}}
<div class="wrap"><div class="inner section">
  <x-section-head :tag="t('home.categories.tag')" :title="t('home.categories.title')" :more="route('catalog')" />
  <div class="cat-row">
    @if($categories->isNotEmpty())
      @foreach ($categories as $c)
        <a @class(['cat-thumb', 'open' => $loop->first]) href="{{ route('catalog', ['category' => $c->slug]) }}"><img src="{{ storage_url($c->image, '/assets/category-tile-showroom.png') }}" alt=""><div class="ov"></div><div class="info"><div><h4>{{ $c->name }}</h4><p>{{ $c->products()->count() }} {{ t('common.products_count') }}</p></div><img src="/assets/icon-arrow-right.svg" alt=""></div></a>
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
    @if($saleBanners->isNotEmpty())
      @for ($i = 0; $i < 10; $i++)
        @foreach($saleBanners as $sb)
          <span>{!! strip_tags($sb->title) !!}</span>
        @endforeach
      @endfor
    @else
      @for ($i = 0; $i < 10; $i++)<span>{{ t('home.sale.marquee') }}</span>@endfor
    @endif
  </div>
</div>

{{-- ===================== CAMPAIGN (SALE) ===================== --}}
<div class="wrap"><div class="inner section">
  @if($promoBanners->isNotEmpty() && ($promo = $promoBanners->first()))
    <div class="promo-banner" @if($promo->background_color) style="background-color: {{ $promo->background_color }}" @endif>
      <div class="pb-l">
        <span class="pb-badge">{{ $promo->badge_text ?? t('home.promo.badge') }}</span>
        <div>
          <h3>{{ $promo->title }}</h3>
          <p>{{ $promo->description }}</p>
        </div>
      </div>
      @if($promo->code)
        <button class="pb-copy" type="button" data-code="{{ $promo->code }}" data-on="false">{{ $promo->code }}</button>
      @elseif($promo->button_url)
        <a class="pb-copy" href="{{ $promo->button_url }}">{{ $promo->button_text ?? t('common.view_details') }}</a>
      @endif
    </div>
  @else
    <div class="promo-banner">
      <div class="pb-l">
        <span class="pb-badge">{{ t('home.promo.badge') }}</span>
        <div>
          <h3>{{ t('home.promo.title_60') }}</h3>
          <p>{{ t('home.promo.text_before') }} <b>ARCHI60</b> {{ t('home.promo.text_after') }}</p>
        </div>
      </div>
      <button class="pb-copy" type="button" data-code="ARCHI60" data-on="false">ARCHI60</button>
    </div>
  @endif
  {{-- "Ətraflı bax" → catalog filtered to SALE-status products only --}}
  <x-section-head :tag="t('home.sale.tag')" :title="t('home.sale.title')" :more="route('catalog', ['sale' => 1])" />
  <div class="grid4" id="campGrid">
    @forelse ($saleProducts as $product)
      <x-pcard :href="route('product.show', $product->slug)"
               :img="$product->mainImageUrl ?? '/assets/product-marble-tile.png'"
               :cat="$product->category?->name ?? t('home.sale.cat_tiles')"
               :name="$product->name"
               :now="number_format($product->price, 2) . ' ₼'"
               :old="$product->old_price && $product->old_price > $product->price ? number_format($product->old_price, 2) . ' ₼' : null"
               :off="$product->old_price && $product->old_price > $product->price && $product->discount_percent ? '-' . $product->discount_percent . '%' : null"
               :rate="$product->reviewsCount > 0 ? number_format($product->averageRating, 1) : null"
               :reviews="$product->reviewsCount > 0 ? '(' . $product->reviewsCount . ' ' . t('common.reviews') . ')' : null" />
    {{-- TODO: Replace with dynamic data from controller --}}
    @empty
      <x-pcard :cat="t('home.sale.cat_tiles')" :name="t('home.sale.name_tile_matte')"
               :now="t('home.sale.price_now_1')" :old="t('home.sale.price_old_1')" :off="t('home.sale.discount_1')" :rate="t('home.sale.rate_1')" :reviews="t('home.sale.reviews_1876')"
               img="/assets/product-marble-tile.png" />
      <x-pcard :cat="t('home.sale.cat_laminate')" :name="t('home.sale.name_laminate')"
               :now="t('home.sale.price_now_2')" :old="t('home.sale.price_old_2')" :off="t('home.sale.discount_2')" :rate="t('home.sale.rate_2')" :reviews="t('home.sale.reviews_640')"
               img="/assets/category-laminate-flooring.png" />
      <x-pcard :cat="t('home.sale.cat_paint')" :name="t('home.sale.name_paint')"
               :now="t('home.sale.price_now_3')" :old="t('home.sale.price_old_3')" :off="t('home.sale.discount_3')" :rate="t('home.sale.rate_3')" :reviews="t('home.sale.reviews_932')"
               img="/assets/hero-modern-house.jpg" />
      <x-pcard :cat="t('home.sale.cat_plumbing')" :name="t('home.sale.name_mixer')"
               :now="t('home.sale.price_now_4')" :old="t('home.sale.price_old_4')" :off="t('home.sale.discount_4')" :rate="t('home.sale.rate_4')" :reviews="t('home.sale.reviews_210')"
               img="/assets/category-plumbing.png" />
    @endforelse
  </div>
</div></div>

{{-- ===================== PRODUCTS ===================== --}}
<div class="wrap"><div class="inner section">
  @if($promoBanners->count() > 1 && ($promo2 = $promoBanners->skip(1)->first()))
    <div class="promo-banner" @if($promo2->background_color) style="background-color: {{ $promo2->background_color }}" @endif>
      <div class="pb-l">
        <span class="pb-badge">{{ $promo2->badge_text ?? t('home.promo.badge') }}</span>
        <div>
          <h3>{{ $promo2->title }}</h3>
          <p>{{ $promo2->description }}</p>
        </div>
      </div>
      @if($promo2->code)
        <button class="pb-copy" type="button" data-code="{{ $promo2->code }}" data-on="false">{{ $promo2->code }}</button>
      @elseif($promo2->button_url)
        <a class="pb-copy" href="{{ $promo2->button_url }}">{{ $promo2->button_text ?? t('common.view_details') }}</a>
      @endif
    </div>
  @else
    <div class="promo-banner">
      <div class="pb-l">
        <span class="pb-badge">{{ t('home.promo.badge') }}</span>
        <div>
          <h3>{{ t('home.promo.title_15') }}</h3>
          <p>{{ t('home.promo.text_before') }} <b>ARCHI15</b> {{ t('home.promo.text_after') }}</p>
        </div>
      </div>
      <button class="pb-copy" type="button" data-code="ARCHI15" data-on="false">ARCHI15</button>
    </div>
  @endif
  {{-- "Ətraflı bax" → catalog filtered to featured products only --}}
  <x-section-head :tag="t('home.products.tag')" :title="t('home.products.title')" :more="route('catalog', ['featured' => 1])" />
  {{-- Products the visitor posted on /sell are stored in localStorage and prepended by home.js --}}
  <div class="grid4" id="prodGrid"
       data-url-product="{{ route('product') }}"
       data-l-cursor="{{ t('common.go_to_product') }}"
       data-l-mine="{{ t('common.your_listing') }}"
       data-l-new="{{ t('home.products.condition_new') }}">
    @forelse ($featuredProducts as $product)
      <x-pcard :href="route('product.show', $product->slug)"
               :img="$product->mainImageUrl ?? '/assets/product-marble-tile.png'"
               :cat="$product->category?->name ?? t('home.products.cat_tiles')"
               :name="$product->name"
               :now="number_format($product->price, 2) . ' ₼'"
               :old="$product->old_price && $product->old_price > $product->price ? number_format($product->old_price, 2) . ' ₼' : null"
               :off="$product->old_price && $product->old_price > $product->price && $product->discount_percent ? '-' . $product->discount_percent . '%' : null"
               :rate="$product->reviewsCount > 0 ? number_format($product->averageRating, 1) : null"
               :reviews="$product->reviewsCount > 0 ? '(' . $product->reviewsCount . ' ' . t('common.reviews') . ')' : null" />
    {{-- TODO: Replace with dynamic data from controller --}}
    @empty
      @for ($i = 0; $i < 4; $i++)
        <x-pcard :cat="t('home.products.cat_tiles')" :name="t('home.products.name_tile_matte')"
                 :now="t('home.products.fallback_price_now')" :old="t('home.products.fallback_price_old')" :off="t('home.products.fallback_discount')" :rate="t('home.products.fallback_rate')" :reviews="t('home.products.reviews_1876')" />
      @endfor
    @endforelse
  </div>
</div></div>

{{-- ===================== SPECIALISTS ===================== --}}
<div class="wrap"><div class="inner section">
  {{-- "Ətraflı bax" → specialists page filtered to featured specialists only --}}
  <x-section-head :tag="t('home.specialists.tag')" :title="t('home.specialists.title')" :more="route('specialists', ['featured' => 1])" />
  <div class="grid4" id="specGrid">
    @if($specialists->isNotEmpty())
      @foreach ($specialists as $s)
        <x-scard :href="route('specialist.show', $s)"
                 :bg="['#f5fbff', '#fdf5ff', '#f5fffb', '#fff5f5'][$loop->index % 4]"
                 :avatar="$s->user?->avatar ?? '/assets/icon-user.svg'"
                 :role="$s->specialty?->name ?? translate_craft($s->craft)"
                 :rate="null"
                 :reviews="null"
                 :name="$s->user?->name ?? t('home.specialists.name_1')"
                 :exp="$s->experience_years ? $s->experience_years . ' ' . t('home.specialists.years') : t('home.specialists.exp_12')"
                 :proj="$s->approvedPortfolioItems()->count() . ' ' . t('home.specialists.projects')" />
      @endforeach
    @else
      {{-- TODO: Replace with dynamic data from controller --}}
      @foreach ($fallbackSpecialists as $s)
        <x-scard :bg="$s['bg']" :role="$s['role']" :rate="t('home.specialists.fallback_rate')" :reviews="t('home.specialists.reviews_416')"
                 :name="$s['name']" :exp="t('home.specialists.exp_12')" :proj="'0 ' . t('home.specialists.projects')" />
      @endforeach
    @endif
  </div>
</div></div>

{{-- ===================== LEAD FORM ===================== --}}
<section class="bg-[#f5f7f9] py-[72px] max-[560px]:py-14">
  <div class="wrap">
    <div class="inner flex items-center gap-14 max-[900px]:flex-col">
      <div class="flex flex-1 flex-col gap-5">
        <x-ui.eyebrow variant="kicker" :label="t('home.lead.tag')" />
        <h2 class="text-[34px] font-bold leading-[1.18] text-ink max-[560px]:text-[26px]">{{ t('home.lead.title') }}</h2>
        <p class="text-[15px] leading-[1.65] text-black/50">{{ t('home.lead.subtitle') }}</p>
      </div>
      <div class="w-full max-w-[480px] shrink-0 rounded border border-black/10 bg-white p-8 max-[900px]:max-w-none">
        <form class="flex flex-col gap-4" id="leadForm" action="{{ route('consultation-requests.store') }}" method="post" data-l-error="{{ t('home.lead.error') }}">
          @csrf
          <x-ui.field :label="t('home.lead.name_label')">
            <x-ui.input name="full_name" type="text" :placeholder="t('home.lead.name_placeholder')" required maxlength="120" />
          </x-ui.field>
          <x-ui.field :label="t('home.lead.phone_label')">
            <x-ui.input name="phone" type="tel" :placeholder="t('home.lead.phone_placeholder')" required maxlength="25" />
          </x-ui.field>
          <x-ui.field :label="t('home.lead.message_label')">
            <x-ui.textarea name="message" :placeholder="t('home.lead.message_placeholder')" rows="3" maxlength="2000" />
          </x-ui.field>
          <x-ui.button variant="primary" type="submit" class="h-[50px] w-full rounded text-[15px] font-semibold">{{ t('home.lead.submit') }}</x-ui.button>
          <p class="hidden rounded border px-4 py-3 text-sm" id="leadFeedback" role="status"></p>
          <p class="text-center text-xs text-black/40">{{ t('home.lead.privacy') }}</p>
        </form>
      </div>
    </div>
  </div>
</section>

{{-- ===================== BLOG ===================== --}}
<div class="wrap"><div class="inner section">
  <x-section-head :tag="t('home.blog.tag')" :title="t('home.blog.title')" :more="route('blog')" />
  <div class="blog-grid" id="blogGrid">
    @forelse ($blogPosts as $post)
      <x-post :href="route('blog.show', $post->slug)"
              :img="$post->cover_image_url"
              :time="$post->reading_time"
              :title="$post->title"
              :excerpt="$post->excerpt" />
    @empty
      @for ($i = 1; $i <= 4; $i++)
        <x-post :href="route('blog.article')" :time="t('home.blog.time_' . $i)" :title="t('home.blog.title_' . $i)" :excerpt="t('home.blog.excerpt_' . $i)" />
      @endfor
    @endforelse
  </div>
</div></div>

</x-layout>
