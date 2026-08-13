{{--
  Shared navbar. Ported from the `NAV` template in the old project's archi.js;
  the data-archi="nav" injection system is gone — this component replaces it.

  Class names match the old CSS (styles live in resources/css/app.css), internal
  links go through route(...), all text through t('nav.*'), images use /assets/...,
  and the active nav item is computed server-side rather than in JS.

  The navbar must render at the same width on every page: page CSS must never set a
  fixed `width`/`min-width: 1440px` on `body` or `.page` — that caused the width
  mismatch in the old project.
--}}
@props([
    'headerMenu' => collect(),
    'megaCatalog' => collect(),
    'megaCatalogClusters' => collect(),
    'megaSpecialists' => collect(),
    'megaBlog' => collect(),
    'megaBlogMenu' => collect(),
])
@php
    $locale = app()->getLocale();
    $langLabels = ['az' => 'AZ', 'ru' => 'RUS', 'en' => 'ENG'];

    $megaPanelMap = [
        'catalog' => 'catalog',
        'specialists' => 'spec',
        'blog' => 'blog',
    ];

    // "Məhsul yerləşdir" target depends on who is looking at it:
    //   guest        → register (they need an account first)
    //   seller       → straight to the product create form
    //   buyer/master → /sell landing page (explains how to become a seller;
    //                  the create form would 403 for them)
    $postProductHref = match (true) {
        ! auth()->check() => route('register'),
        auth()->user()->isSeller() => route('business.products.create'),
        default => route('sell'),
    };
@endphp

<header class="topbar">
  <div class="nav-row1">
    <a href="{{ route('home') }}" aria-label="{{ t('nav.logo_aria') }}"><img class="logo" src="/assets/logo-archi-black.png" alt="ARCHI"></a>

    <div class="search"
         data-url-api="/api/search"
         data-url-search="{{ route('search') }}"
         data-url-specialists="/specialist"
         data-url-product="/product"
         data-l-quick="{{ t('nav.sd_quick') }}"
         data-l-products="{{ t('nav.sd_products') }}"
         data-l-masters="{{ t('nav.sd_masters') }}"
         data-l-all="{{ t('nav.sd_all_results') }}"
         data-l-loading="{{ t('nav.sd_loading', [], app()->getLocale()) }}"
         data-l-no-results="{{ t('nav.sd_no_results', [], app()->getLocale()) }}">
      <img src="/assets/icon-search.svg" alt="">
      <input type="text" id="navSearch" aria-label="{{ t('nav.search_aria') }}" placeholder="{{ t('nav.search_placeholder') }}" autocomplete="off">
      <div class="search-dropdown" id="searchDrop"></div>
    </div>

    <div class="nav-menu">
      <div class="nav-icons">
        {{-- Language switcher — server-side: /lang/{locale} stores the session and redirects back --}}
        <div class="lang" id="langBtn" role="button" tabindex="0" aria-label="{{ t('nav.lang_aria') }}"
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

        <a href="{{ route('wishlist') }}" class="nav-wish" aria-label="{{ t('nav.favorites') }}"><img src="/assets/icon-heart-rounded.svg" alt=""></a>
        <a href="{{ route('cart') }}" class="nav-cart" aria-label="{{ t('nav.cart') }}"><img src="/assets/icon-cart.svg" alt=""><span class="cart-badge" id="navCartCount"></span></a>
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
            $userInitials = mb_strtoupper(mb_substr(Auth::user()->first_name, 0, 1) . mb_substr(Auth::user()->last_name ?? '', 0, 1));
          @endphp

          {{-- Account menu --}}
          <div class="nav-account-wrap relative" id="navAccountWrap">
            <button type="button" class="nav-account-btn flex items-center gap-2" id="navAccountBtn" aria-haspopup="true" aria-expanded="false">
              <span class="flex size-8 items-center justify-center rounded-full bg-[#f5f7f9] text-xs font-bold text-ink">{{ $userInitials }}</span>
              <span class="txt nav-user max-[900px]:hidden">{{ Auth::user()->first_name }}</span>
              <svg class="size-4 text-black/40 max-[900px]:hidden" viewBox="0 0 16 16" fill="none"><path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <div class="nav-account-panel absolute right-0 top-full z-50 mt-2 hidden w-[280px] rounded border border-black/10 bg-white shadow-lg" id="navAccountPanel">
              <div class="flex items-center gap-3 border-b border-black/8 p-4">
                <span class="flex size-10 items-center justify-center rounded-full bg-[#f5f7f9] text-sm font-bold text-ink">{{ $userInitials }}</span>
                <div class="flex flex-col gap-0.5">
                  <p class="text-sm font-semibold text-ink">{{ Auth::user()->first_name }} {{ Auth::user()->last_name }}</p>
                  <p class="text-xs text-black/50">{{ Auth::user()->email }}</p>
                </div>
              </div>
              <div class="py-1.5">
                <a href="{{ route('account.orders') }}" class="flex items-center justify-between px-4 py-2.5 text-sm font-medium text-ink hover:bg-[#f5f7f9]">{{ t('nav.my_orders') }}</a>
                <a href="{{ route('wishlist') }}" class="flex items-center justify-between px-4 py-2.5 text-sm font-medium text-ink hover:bg-[#f5f7f9]">{{ t('nav.favorites') }}</a>
                <a href="{{ $profileRoute }}" class="flex items-center justify-between px-4 py-2.5 text-sm font-medium text-ink hover:bg-[#f5f7f9]">{{ t('nav.account_settings') }}</a>
              </div>
              <div class="border-t border-black/8 py-1.5">
                <a href="{{ route('help') }}" class="flex items-center px-4 py-2.5 text-sm font-medium text-ink hover:bg-[#f5f7f9]">{{ t('nav.help_center') }}</a>
              </div>
              <div class="border-t border-black/8 py-1.5">
                <form method="POST" action="{{ route('logout') }}">
                  @csrf
                  <button type="submit" class="flex w-full items-center px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-[#f5f7f9]">{{ t('nav.logout') }}</button>
                </form>
              </div>
            </div>
          </div>
        @else
          <a class="txt" data-login href="{{ route('login') }}">{{ t('nav.sign_in') }}</a>
        @endauth
        <a class="btn-post" href="{{ $postProductHref }}"><img src="/assets/icon-plus.svg" alt=""><span>{{ t('nav.post_product') }}</span></a>
      </div>

      {{-- Mobile hamburger — visible only ≤900px --}}
      <button class="mob-burger" id="mobBurger" type="button" aria-label="{{ t('nav.menu_aria') }}" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
    </div>

    {{-- Mobile drawer — slides from right, holds all nav links --}}
    <div class="mob-overlay" id="mobOverlay"></div>
    <nav class="mob-drawer" id="mobDrawer" aria-label="{{ t('nav.mobile_nav_aria') }}">
      <div class="mob-drawer-head">
        <a href="{{ route('home') }}"><img class="logo" src="/assets/logo-archi-black.png" alt="ARCHI"></a>
        <button class="mob-close" id="mobClose" type="button" aria-label="{{ t('nav.close_menu_aria') }}">✕</button>
      </div>

      <div class="mob-drawer-search">
        <div class="search mob-search"
             data-url-api="/api/search"
             data-url-search="{{ route('search') }}"
             data-url-specialists="/specialist"
             data-url-product="/product"
             data-l-quick="{{ t('nav.sd_quick') }}"
             data-l-products="{{ t('nav.sd_products') }}"
             data-l-masters="{{ t('nav.sd_masters') }}"
             data-l-all="{{ t('nav.sd_all_results') }}"
             data-l-loading="{{ t('nav.sd_loading', [], app()->getLocale()) }}"
             data-l-no-results="{{ t('nav.sd_no_results', [], app()->getLocale()) }}">
          <img src="/assets/icon-search.svg" alt="">
          <input type="text" aria-label="{{ t('nav.search_aria') }}" placeholder="{{ t('nav.search_placeholder') }}" autocomplete="off">
        </div>
      </div>

      <div class="mob-drawer-body">
        @foreach ($headerMenu as $item)
          @php
              $href = $item->resolved_url ?? '#';
              $isCalc = $item->css_class === 'nav-calc';
          @endphp
          <a class="mob-link" href="{{ $href }}">
            @if ($isCalc)<img src="/assets/icon-calculator.svg" alt="">@endif
            {{ $item->label }}
          </a>
          {{-- admin sub-items: the mega panels are desktop-only, so children must be
               reachable here as indented links --}}
          @foreach ($item->children as $child)
            <a class="mob-link mob-sub" href="{{ $child->resolved_url ?? '#' }}"@if($child->open_in_new_tab) target="_blank" rel="noopener"@endif>{{ $child->label }}</a>
          @endforeach
          {{-- Kataloq: same nested structure as the desktop mega panel — the five
               classifier clusters as headings, their sections as indented links. --}}
          @if ($item->css_class === 'catalog' && $megaCatalogClusters->isNotEmpty())
            @foreach ($megaCatalogClusters as $cluster)
              @if ($cluster['label'])<p class="mob-group">{{ $cluster['label'] }}</p>@endif
              @foreach ($cluster['items'] as $sectionItem)
                <a class="mob-link mob-sub" href="{{ $sectionItem->resolved_url ?? '#' }}"@if($sectionItem->open_in_new_tab) target="_blank" rel="noopener"@endif>{{ $sectionItem->label }}</a>
              @endforeach
            @endforeach
          @endif
        @endforeach
      </div>

      <div class="mob-drawer-foot">
        @auth
          <a class="mob-link" href="{{ $profileRoute }}">{{ Auth::user()->first_name }}</a>
          <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button type="submit" class="mob-link">{{ t('nav.logout') }}</button>
          </form>
        @else
          <a class="mob-link" href="{{ route('login') }}">{{ t('nav.sign_in') }}</a>
          <a class="mob-link" href="{{ route('register') }}">{{ t('nav.register') }}</a>
        @endauth
        <a class="mob-post-btn" href="{{ $postProductHref }}"><img src="/assets/icon-plus.svg" alt=""><span>{{ t('nav.post_product') }}</span></a>

        <div class="mob-lang">
          @foreach ($langLabels as $code => $label)
            <a href="{{ route('lang', $code) }}" @class(['active' => $locale === $code])>{{ $label }}</a>
          @endforeach
        </div>
      </div>
    </nav>
  </div>

  <div class="nav-row2">
    <div class="inner">
      <div class="nav-left">
        @foreach ($headerMenu as $item)
          @php
              $href = $item->resolved_url ?? '#';
              $isActive = $item->route_name && request()->routeIs($item->route_name . '*');
              // A shared mega panel is used only when the admin turned the item's
              // "Dropdown var?" toggle ON — a plain link that merely points at the
              // catalog/specialists/blog route must NOT hijack the mega panel.
              $megaKey = $item->has_dropdown ? ($megaPanelMap[$item->route_name] ?? null) : null;
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

  {{-- MEGA: catalog — the classifier's five navigation clusters (Excel sheet 7,
       rec. R8), each heading followed by its sections. The cluster definition is
       shared with the catalog rail (App\Support\CatalogNavigation); labels come
       from the same t('catalog-classifier.clusters.*') keys the rail uses.
       Falls back to the flat card grid when no card maps to a section (fresh
       install without the classifier). --}}
  <div class="mega-panel" id="megaCatalog" data-panel="catalog">
    <div class="mega-inner">
      @if ($megaCatalogClusters->isNotEmpty())
        <div class="mega-cats mega-clusters">
          @foreach ($megaCatalogClusters as $cluster)
            <div class="mclus">
              @if ($cluster['label'])<p class="mclus-h">{{ $cluster['label'] }}</p>@endif
              @foreach ($cluster['items'] as $item)
                <a class="mcat mclus-link" href="{{ $item->resolved_url }}"@if($item->open_in_new_tab) target="_blank" rel="noopener"@endif>
                  @if ($item->icon)<img src="{{ storage_url($item->icon) }}" alt="">@endif
                  <span>{{ $item->label }}</span>
                </a>
              @endforeach
            </div>
          @endforeach
        </div>
      @else
        <div class="mega-cats">
          @foreach ($megaCatalog as $item)
            <a class="mcat" href="{{ $item->resolved_url }}">
              <div class="top"><img src="{{ storage_url($item->icon) }}" alt=""><p>{{ $item->label }}</p></div>
              <div class="desc">{{ $item->description }}</div>
            </a>
          @endforeach
        </div>
      @endif
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
            <p>{{ t('nav.mega_promo_text') }}</p>
            <a class="pill" href="{{ route('specialists') }}">{{ t('nav.mega_promo_cta') }}</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- MEGA: blog — admin cards first ("Header — Mega Bloq" location, then children of
       the Bloq nav item), latest blog posts as the fallback when no cards exist --}}
  @php
      $blogItem = $headerMenu->firstWhere('route_name', 'blog');
      $blogCards = $megaBlogMenu->isNotEmpty()
          ? $megaBlogMenu
          : ($blogItem ? $blogItem->children : collect());
  @endphp
  <div class="mega-panel" id="megaBlog" data-panel="blog">
    <div class="mega-inner">
      @if ($blogCards->isNotEmpty())
        <div class="mega-cats">
          @foreach ($blogCards as $child)
            <a class="mcat @if (!$child->icon) no-icon @endif" href="{{ $child->resolved_url ?? '#' }}"@if($child->open_in_new_tab) target="_blank" rel="noopener"@endif>
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
                  <span class="pill">{{ t('common.more') }}</span>
                @else
                  <span class="read">{{ t('common.read_more') }} <img src="/assets/icon-arrow-right.svg" alt=""></span>
                @endif
              </div>
            </a>
          @endforeach
        </div>
      @endif
    </div>
  </div>

  {{-- MEGA: dynamic panels for admin menu items with children. The condition must
       mirror the trigger loop above: an item only uses a shared mega panel when its
       "Dropdown var?" toggle is on AND its route is one of the three mapped ones —
       every other item with children gets its own generic panel here. --}}
  @foreach ($headerMenu as $item)
    @if ($item->children->isNotEmpty() && !($item->has_dropdown && isset($megaPanelMap[$item->route_name])))
      <div class="mega-panel" data-panel="menu-{{ $item->id }}">
        <div class="mega-inner">
          <div class="mega-cats">
            @foreach ($item->children as $child)
              <a class="mcat @if (!$child->icon) no-icon @endif" href="{{ $child->resolved_url ?? '#' }}"@if($child->open_in_new_tab) target="_blank" rel="noopener"@endif>
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
