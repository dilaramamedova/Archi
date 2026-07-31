{{--
  Shared navbar. Ported from the `NAV` template in the old project's archi.js;
  the data-archi="nav" injection system is gone — this component replaces it.

  Class names match the old CSS (styles live in resources/css/app.css), internal
  links go through route(...), all text through __('nav.*'), images use /assets/...,
  and the active nav item is computed server-side rather than in JS.

  The navbar must render at the same width on every page: page CSS must never set a
  fixed `width`/`min-width: 1440px` on `body` or `.page` — that caused the width
  mismatch in the old project.
--}}
@php
    // Active nav item, matched by route name
    $isCatalog = request()->routeIs('catalog');
    $isSpec = request()->routeIs('specialists') || request()->routeIs('specialist');
    $isBlog = request()->routeIs('blog');
    $isB2B = request()->routeIs('business.*');
    $locale = app()->getLocale();
    $langLabels = ['az' => 'AZ', 'ru' => 'RUS', 'en' => 'ENG'];

    // Locale-independent structure. The lang files hold only translatable text
    // (title/desc/name/cat/price); assets and layout flags are zipped in by index,
    // so renaming an asset is a one-line change instead of three.
    $catalogIcons = [
        'cat-ic-tikinti.svg',
        'cat-ic-santexnika.svg',
        'cat-ic-elektrik.svg',
        'cat-ic-dosheme.svg',
        'cat-ic-isiq.svg',
        'cat-ic-dekor.svg',
    ];
    $specIcons = [
        'spec-ic-memar.svg',
        'spec-ic-interyer.svg',
        'spec-ic-usta.svg',
        'spec-ic-sirket.svg',
    ];
    $blogImages = ['mega-blog1.jpg', 'mega-blog2.jpg', 'mega-blog2.jpg'];
    $blogCtas = ['read', 'read', 'pill'];

    // Search autocomplete demo dataset: images live here, text in lang/*/nav.php
    $searchProductImages = [
        '/assets/fig/1ed736a990f0.jpg',
        '/assets/fig/bca0ec1e.jpg',
        '/assets/fig/78886edf.jpg',
        '/assets/fig/50873ec31b52.jpg',
        '/assets/fig/6146d21348a6.jpg',
        '/assets/fig/2701238de96a.jpg',
    ];
    $searchProducts = [];
    foreach (__('nav.sd_demo_products') as $i => $product) {
        $searchProducts[] = $product + ['img' => $searchProductImages[$i] ?? ''];
    }
@endphp

<header class="topbar">
  <div class="nav-row1">
    <a href="{{ route('home') }}" aria-label="{{ __('nav.logo_aria') }}"><img class="logo" src="/assets/logo.png" alt="ARCHI"></a>

    <div class="search"
         data-url-search="{{ route('search') }}"
         data-url-product="{{ route('product') }}"
         data-url-specialists="{{ route('specialists') }}"
         data-l-quick="{{ __('nav.sd_quick') }}"
         data-l-products="{{ __('nav.sd_products') }}"
         data-l-masters="{{ __('nav.sd_masters') }}"
         data-l-all="{{ __('nav.sd_all_results') }}"
         data-demo-suggests="{{ json_encode(__('nav.sd_demo_suggests'), JSON_UNESCAPED_UNICODE) }}"
         data-demo-products="{{ json_encode($searchProducts, JSON_UNESCAPED_UNICODE) }}"
         data-demo-masters="{{ json_encode(__('nav.sd_demo_masters'), JSON_UNESCAPED_UNICODE) }}">
      <img src="/assets/ic-search.svg" alt="">
      <input type="text" id="navSearch" aria-label="{{ __('nav.search_aria') }}" placeholder="{{ __('nav.search_placeholder') }}" autocomplete="off">
      <div class="search-dropdown" id="searchDrop"></div>
    </div>

    <div class="nav-menu">
      <div class="nav-icons">
        {{-- Language switcher — server-side: /lang/{locale} stores the session and redirects back --}}
        <div class="lang" id="langBtn" role="button" tabindex="0" aria-label="{{ __('nav.lang_aria') }}"
             aria-haspopup="true" aria-controls="langMenu" aria-expanded="false">
          <span id="langLabel">{{ $langLabels[$locale] ?? 'AZ' }}</span> <img src="/assets/ic-caret.svg" alt="">
          <ul class="lang-menu" id="langMenu">
            @foreach ($langLabels as $code => $label)
              <li @class(['active' => $locale === $code])>
                <a href="{{ route('lang', $code) }}" @if ($locale === $code) aria-current="true" @endif>{{ $label }}</a>
              </li>
            @endforeach
          </ul>
        </div>

        <img src="/assets/ic-heart.svg" alt="" role="button" tabindex="0" aria-label="{{ __('nav.favorites') }}">
        <a href="{{ route('cart') }}" class="nav-cart" aria-label="{{ __('nav.cart') }}"><img src="/assets/ic-cart.svg" alt=""><span class="cart-badge" id="navCartCount"></span></a>
      </div>

      <div class="signin">
        <span class="divider"></span>
        <a class="txt" href="{{ route('login') }}">{{ __('nav.sign_in') }}</a>
        <a class="btn-post" href="{{ route('sell') }}"><img src="/assets/ic-plus.svg" alt=""><span>{{ __('nav.post_product') }}</span></a>
      </div>
    </div>
  </div>

  <div class="nav-row2">
    <div class="inner">
      <div class="nav-left">
        {{-- The [data-mega] links open their panel on click (navbar.js preventDefaults);
             the href stays as the no-JS fallback and the panel repeats it as real links. --}}
        <a class="nav-item catalog @if ($isCatalog) active @endif" data-mega="catalog" href="{{ route('catalog') }}" aria-label="{{ __('nav.catalog') }}"
           aria-haspopup="true" aria-controls="megaCatalog" aria-expanded="false"><img src="/assets/ic-grip.svg" alt="">{{ __('nav.catalog') }}</a>
        <a class="nav-item @if ($isSpec) active @endif" data-mega="spec" href="{{ route('specialists') }}"
           aria-haspopup="true" aria-controls="megaSpec" aria-expanded="false">{{ __('nav.specialists') }} <img class="mcaret" src="/assets/ic-caret.svg" alt=""></a>
        <a class="nav-item @if ($isBlog) active @endif" data-mega="blog" href="{{ route('blog') }}"
           aria-haspopup="true" aria-controls="megaBlog" aria-expanded="false">{{ __('nav.blog') }} <img class="mcaret" src="/assets/ic-caret.svg" alt=""></a>
        <a class="nav-item" href="#">{{ __('nav.about') }}</a>
        <a class="nav-item @if ($isB2B) active @endif" href="{{ route('business.register') }}">{{ __('nav.b2b') }}</a>
      </div>
      <a class="nav-calc" href="{{ route('calculator') }}"><img src="/assets/ic-calculator.svg" alt="">{{ __('nav.calculator') }}</a>
    </div>
  </div>

  {{-- MEGA: catalog (3x2) --}}
  <div class="mega-panel" id="megaCatalog" data-panel="catalog">
    <div class="mega-inner">
      <div class="mega-cats">
        @foreach (__('nav.mega_catalog') as $i => $item)
          <a class="mcat" href="{{ route('catalog') }}">
            <div class="top"><img src="/assets/{{ $catalogIcons[$i] ?? '' }}" alt=""><p>{{ $item['title'] }}</p></div>
            <div class="desc">{{ $item['desc'] }}</div>
          </a>
        @endforeach
      </div>
    </div>
  </div>

  {{-- MEGA: specialists --}}
  <div class="mega-panel" id="megaSpec" data-panel="spec">
    <div class="mega-inner">
      <div class="mega-spec">
        <div class="grid">
          @foreach (__('nav.mega_spec') as $i => $item)
            <a class="mcat" href="{{ route('specialists') }}">
              <div class="top"><img src="/assets/{{ $specIcons[$i] ?? '' }}" alt=""><p>{{ $item['title'] }}</p></div>
              <div class="desc">{{ $item['desc'] }}</div>
            </a>
          @endforeach
        </div>
        <div class="promo">
          <img class="ph" src="/assets/mega-consult.jpg" alt="">
          <div class="card">
            <p>{{ __('nav.mega_promo_text') }}</p>
            <a class="pill" href="{{ route('specialists') }}">{{ __('nav.mega_promo_cta') }}</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- MEGA: blog (3 cards) --}}
  <div class="mega-panel" id="megaBlog" data-panel="blog">
    <div class="mega-inner">
      <div class="mega-blog">
        @foreach (__('nav.mega_blog') as $i => $item)
          <a class="mblog" href="{{ route('blog') }}">
            <img class="ph" src="/assets/{{ $blogImages[$i] ?? '' }}" alt="">
            <div class="info">
              <h4>{{ $item['title'] }}</h4>
              <div class="d">{{ $item['desc'] }}</div>
              @if (($blogCtas[$i] ?? 'read') === 'pill')
                <span class="pill">{{ __('common.more') }}</span>
              @else
                <span class="read">{{ __('common.read_more') }} <img src="/assets/ic-arrow.svg" alt=""></span>
              @endif
            </div>
          </a>
        @endforeach
      </div>
    </div>
  </div>
</header>
