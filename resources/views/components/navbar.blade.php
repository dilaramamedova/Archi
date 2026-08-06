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
@props([
    'headerMenu' => collect(),
    'megaCatalog' => collect(),
    'megaSpecialists' => collect(),
    'megaBlog' => collect(),
])
@php
    $locale = app()->getLocale();
    $langLabels = ['az' => 'AZ', 'ru' => 'RUS', 'en' => 'ENG'];

    $megaPanelMap = [
        'catalog' => 'catalog',
        'specialists' => 'spec',
        'blog' => 'blog',
    ];
@endphp

<header class="topbar">
  <div class="nav-row1">
    <a href="{{ route('home') }}" aria-label="{{ __('nav.logo_aria') }}"><img class="logo" src="/assets/logo-archi-black.png" alt="ARCHI"></a>

    <div class="search"
         data-url-api="/api/search"
         data-url-search="{{ route('search') }}"
         data-url-specialists="/specialist"
         data-url-product="/product"
         data-l-quick="{{ __('nav.sd_quick') }}"
         data-l-products="{{ __('nav.sd_products') }}"
         data-l-masters="{{ __('nav.sd_masters') }}"
         data-l-all="{{ __('nav.sd_all_results') }}"
         data-l-loading="{{ __('nav.sd_loading', [], app()->getLocale()) }}"
         data-l-no-results="{{ __('nav.sd_no_results', [], app()->getLocale()) }}">
      <img src="/assets/icon-search.svg" alt="">
      <input type="text" id="navSearch" aria-label="{{ __('nav.search_aria') }}" placeholder="{{ __('nav.search_placeholder') }}" autocomplete="off">
      <div class="search-dropdown" id="searchDrop"></div>
    </div>

    <div class="nav-menu">
      <div class="nav-icons">
        {{-- Language switcher — server-side: /lang/{locale} stores the session and redirects back --}}
        <div class="lang" id="langBtn" role="button" tabindex="0" aria-label="{{ __('nav.lang_aria') }}"
             aria-haspopup="true" aria-controls="langMenu" aria-expanded="false">
          <span id="langLabel">{{ $langLabels[$locale] ?? 'AZ' }}</span> <img src="/assets/icon-chevron-down.svg" alt="">
          <ul class="lang-menu" id="langMenu">
            @foreach ($langLabels as $code => $label)
              <li @class(['active' => $locale === $code])>
                <a href="{{ route('lang', $code) }}" @if ($locale === $code) aria-current="true" @endif>{{ $label }}</a>
              </li>
            @endforeach
          </ul>
        </div>

        <a href="{{ route('wishlist') }}" class="nav-wish" aria-label="{{ __('nav.favorites') }}"><img src="/assets/icon-heart-rounded.svg" alt=""></a>
        <a href="{{ route('cart') }}" class="nav-cart" aria-label="{{ __('nav.cart') }}"><img src="/assets/icon-cart.svg" alt=""><span class="cart-badge" id="navCartCount"></span></a>
      </div>

      <div class="signin">
        <span class="divider"></span>
        @auth
          @php
            $profileRoute = match(Auth::user()->role) {
              \App\Enums\UserRole::Seller => route('business.profile'),
              \App\Enums\UserRole::Master => route('specialist.cabinet'),
              default => route('account'),
            };
          @endphp
          <a class="txt nav-user" href="{{ $profileRoute }}" data-user>{{ Auth::user()->first_name }}</a>
          <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button type="submit" class="txt nav-logout">{{ __('nav.logout') }}</button>
          </form>
        @else
          <a class="txt" href="{{ route('login') }}">{{ __('nav.sign_in') }}</a>
        @endauth
        <a class="btn-post" href="{{ route('sell') }}"><img src="/assets/icon-plus.svg" alt=""><span>{{ __('nav.post_product') }}</span></a>
      </div>
    </div>
  </div>

  <div class="nav-row2">
    <div class="inner">
      <div class="nav-left">
        @foreach ($headerMenu as $item)
          @php
              $href = $item->resolved_url ?? '#';
              $isActive = $item->route_name && request()->routeIs($item->route_name . '*');
              $megaKey = $megaPanelMap[$item->route_name] ?? null;
              $hasChildren = $item->children->isNotEmpty();
              $isCalc = $item->css_class === 'nav-calc';
              $isCatalogItem = $item->css_class === 'catalog';
              $panelId = $megaKey ?: ($hasChildren ? 'menu-' . $item->id : null);
          @endphp

          @if ($isCalc)
            @continue
          @endif

          @if ($panelId)
            <a class="nav-item {{ $isCatalogItem ? 'catalog' : '' }} @if ($isActive) active @endif"
               data-mega="{{ $panelId }}" href="{{ $href }}"
               aria-haspopup="true" aria-expanded="false">
              @if ($isCatalogItem)<img src="/assets/icon-menu.svg" alt="">@endif
              {{ $item->label }}
              <img class="mcaret" src="/assets/icon-chevron-down.svg" alt="">
            </a>
          @else
            <a class="nav-item @if ($isActive) active @endif" href="{{ $href }}">{{ $item->label }}</a>
          @endif
        @endforeach
      </div>
      @php $calcItem = $headerMenu->firstWhere('css_class', 'nav-calc'); @endphp
      @if ($calcItem)
        <a class="nav-calc" href="{{ $calcItem->resolved_url ?? '#' }}"><img src="/assets/icon-calculator.svg" alt="">{{ $calcItem->label }}</a>
      @endif
    </div>
  </div>

  {{-- MEGA: catalog (3x2) --}}
  <div class="mega-panel" id="megaCatalog" data-panel="catalog">
    <div class="mega-inner">
      <div class="mega-cats">
        @foreach ($megaCatalog as $item)
          <a class="mcat" href="{{ $item->resolved_url }}">
            <div class="top"><img src="{{ storage_url($item->icon) }}" alt=""><p>{{ $item->label }}</p></div>
            <div class="desc">{{ $item->description }}</div>
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
          @foreach ($megaSpecialists as $item)
            <a class="mcat" href="{{ $item->resolved_url }}">
              <div class="top"><img src="{{ storage_url($item->icon) }}" alt=""><p>{{ $item->label }}</p></div>
              <div class="desc">{{ $item->description }}</div>
            </a>
          @endforeach
        </div>
        <div class="promo">
          <img class="ph" src="/assets/architecture-house-sketch.jpg" alt="">
          <div class="card">
            <p>{{ __('nav.mega_promo_text') }}</p>
            <a class="pill" href="{{ route('specialists') }}">{{ __('nav.mega_promo_cta') }}</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- MEGA: blog --}}
  @php $blogItem = $headerMenu->firstWhere('route_name', 'blog'); @endphp
  <div class="mega-panel" id="megaBlog" data-panel="blog">
    <div class="mega-inner">
      @if ($blogItem && $blogItem->children->isNotEmpty())
        <div class="mega-cats">
          @foreach ($blogItem->children as $child)
            <a class="mcat" href="{{ $child->resolved_url ?? '#' }}"@if($child->open_in_new_tab) target="_blank" rel="noopener"@endif>
              <div class="top">
                @if ($child->icon)<img src="{{ storage_url($child->icon) }}" alt="">@endif
                <p>{{ $child->label }}</p>
              </div>
              @if ($child->description)<div class="desc">{{ $child->description }}</div>@endif
            </a>
          @endforeach
        </div>
      @else
        <div class="mega-blog">
          @foreach ($megaBlog as $i => $post)
            <a class="mblog" href="{{ route('blog.show', $post->slug) }}">
              <img class="ph" src="{{ $post->cover_image_url }}" alt="">
              <div class="info">
                <h4>{{ $post->title }}</h4>
                <div class="d">{{ $post->excerpt }}</div>
                @if ($loop->last)
                  <span class="pill">{{ __('common.more') }}</span>
                @else
                  <span class="read">{{ __('common.read_more') }} <img src="/assets/icon-arrow-right.svg" alt=""></span>
                @endif
              </div>
            </a>
          @endforeach
        </div>
      @endif
    </div>
  </div>

  {{-- MEGA: dynamic panels for menu items with children --}}
  @foreach ($headerMenu as $item)
    @if ($item->children->isNotEmpty() && !isset($megaPanelMap[$item->route_name]))
      <div class="mega-panel" data-panel="menu-{{ $item->id }}">
        <div class="mega-inner">
          <div class="mega-cats">
            @foreach ($item->children as $child)
              <a class="mcat" href="{{ $child->resolved_url ?? '#' }}"@if($child->open_in_new_tab) target="_blank" rel="noopener"@endif>
                <div class="top">
                  @if ($child->icon)<img src="{{ storage_url($child->icon) }}" alt="">@endif
                  <p>{{ $child->label }}</p>
                </div>
                @if ($child->description)<div class="desc">{{ $child->description }}</div>@endif
              </a>
            @endforeach
          </div>
        </div>
      </div>
    @endif
  @endforeach
</header>
