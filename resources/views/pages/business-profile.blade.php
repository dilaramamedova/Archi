{{--
  Seller storefront (Figma 1105:24979) — what a buyer sees when opening a business
  profile. Fully dynamic: identity/showrooms/products/contact come from the seller's
  SellerProfile + Product data (BusinessProfileController@storefront).

  The page keeps its legacy class names; styles live in
  resources/css/pages/business-profile.css, scoped under `.bp-page`.
--}}
@php
  $displayName = $profile?->brand_name ?: ($profile?->legal_name ?: $user->name);
  $initials = mb_strtoupper(mb_substr($displayName, 0, 2));
  $memberYear = $user->created_at->format('Y');
  $langLabels = ['az' => 'AZ', 'ru' => 'RU', 'en' => 'EN', 'tr' => 'TR'];
@endphp
<x-layout page="business-profile" :title="$displayName . ' — ' . __('common.site_name')">

<div class="page bp-page">

    {{-- ===== OWNER BAR: edit + cabinet quick links ===== --}}
    @if ($isOwner)
    <div class="wrap pt-5">
      <div class="flex flex-wrap items-center justify-between gap-3 rounded border border-yellow bg-[#fffde0] px-5 py-3.5">
        <p class="text-sm font-semibold text-ink">{{ __('business-profile.owner.hint') }}</p>
        <div class="flex flex-wrap gap-2">
          <x-ui.button variant="primary" :href="route('business.profile.company')"
              class="h-10 rounded px-4 text-[13px] font-semibold">{{ __('business-profile.owner.edit') }}</x-ui.button>
          <x-ui.button variant="outline" :href="route('business.orders')"
              class="h-10 rounded px-4 text-[13px] font-semibold">{{ __('business-cabinet.nav.orders') }}</x-ui.button>
          <x-ui.button variant="outline" :href="route('business.profile.products')"
              class="h-10 rounded px-4 text-[13px] font-semibold">{{ __('business-cabinet.nav.products') }}</x-ui.button>
          <x-ui.button variant="outline" :href="route('business.products.create')"
              class="h-10 rounded px-4 text-[13px] font-semibold">{{ __('business-profile.owner.add_product') }}</x-ui.button>
          <x-ui.button variant="outline" :href="route('business.inventory')"
              class="h-10 rounded px-4 text-[13px] font-semibold">{{ __('business-cabinet.nav.inventory') }}</x-ui.button>
        </div>
      </div>
    </div>
    @endif

    {{-- ===== COVER ===== --}}
    <div class="cover-wrap">
        <div class="cover">
            <div class="bg">
                <img src="{{ storage_url($profile?->cover_path, '/assets/renovation-before-after-wide.jpg') }}" alt="">
                <div class="ov"></div>
            </div>
        </div>
    </div>

    {{-- ===== IDENTITY ===== --}}
    <div class="identity">
        <div class="id-left">
            <div class="biz-logo">
                @if ($profile?->logo_path)
                    <img src="{{ storage_url($profile->logo_path) }}" alt="{{ $displayName }}" class="size-full rounded object-cover">
                @else
                    <p>{{ $initials }}</p>
                @endif
            </div>
            <div class="id-info">
                <div class="id-name-row">
                    <p class="id-name">{{ $displayName }}</p>
                    @if ($profile?->tax_id_verified)
                        <x-ui.badge tone="ok" size="sm" class="px-2.5 py-1 text-xs">{{ __('business-profile.identity.verified') }}</x-ui.badge>
                    @endif
                </div>
                <div class="id-meta">
                    @if ($profile?->city)<p class="m">{{ $profile->city }}</p><p class="d">·</p>@endif
                    <p class="m">{{ __('business-profile.identity.member_since', ['year' => $memberYear]) }}</p>
                    @if ($showrooms->isNotEmpty())
                        <p class="d">·</p>
                        <p class="m">{{ trans_choice('business-profile.identity.showroom_count', $showrooms->count(), ['count' => $showrooms->count()]) }}</p>
                    @endif
                </div>
                <div class="id-rating">
                    @if ($avgRating !== null)
                        <p class="star">★</p>
                        <p class="val">{{ number_format($avgRating, 1) }}</p>
                        <p class="lbl">({{ $reviewsCount }} {{ __('common.reviews') }})</p>
                        <p class="dot">·</p>
                    @endif
                    <p class="cnt">{{ $productsCount }} {{ __('common.products_count') }}</p>
                </div>
            </div>
        </div>
        <div class="id-actions">
            <x-ui.button variant="primary"
                class="h-[46px] px-6 text-sm leading-[normal] font-bold whitespace-nowrap"
                onclick="window.ARCHI?.toast?.show({type:'info', title:'{{ __('business-profile.identity.coming_soon') }}'})">{{ __('business-profile.identity.send_message') }}</x-ui.button>
            <x-ui.button variant="outline"
                class="h-[46px] px-6 text-sm leading-[normal] font-semibold whitespace-nowrap"
                onclick="window.ARCHI?.toast?.show({type:'info', title:'{{ __('business-profile.identity.coming_soon') }}'})">{{ __('business-profile.identity.follow') }}</x-ui.button>
        </div>
    </div>

    {{-- ===== BODY ===== --}}
    <div class="bp-body">
        <div class="bp-left">

            @if ($profile?->about)
            <div class="sec-head">
                <x-ui.eyebrow variant="kicker" :label="__('business-profile.about.kicker')" />
                <p class="sec-title">{{ __('business-profile.about.title') }}</p>
            </div>

            <p class="about-p">{{ $profile->about }}</p>
            @endif

            @if ($showrooms->isNotEmpty())
            <div class="sec-head">
                <x-ui.eyebrow variant="kicker" :label="__('business-profile.showrooms.kicker')" />
                <p class="sec-title">{{ trans_choice('business-profile.identity.showroom_count', $showrooms->count(), ['count' => $showrooms->count()]) }}@if ($profile?->city) — {{ $profile->city }}@endif</p>
            </div>

            <div class="showrooms">
                @foreach ($showrooms as $showroom)
                    <div class="sr-card">
                        <p class="nm">{{ $showroom->name }}</p>
                        <p class="ad">{{ collect([$showroom->city, $showroom->address])->filter()->join(', ') }}</p>
                        <p class="hr">{{ collect([$showroom->work_hours, $showroom->phone])->filter()->join(' · ') }}</p>
                    </div>
                @endforeach
            </div>
            @endif

            <div class="catalog-head">
                <div class="sec-head">
                    <x-ui.eyebrow variant="kicker" :label="__('business-profile.catalog.kicker')" />
                    <p class="sec-title">{{ __('business-profile.catalog.title') }}</p>
                </div>
                @if ($productsCount > 0)
                    <a class="see-all" href="{{ route('search', ['tab' => 'prod']) }}"><p>{{ __('business-profile.catalog.see_all_count', ['count' => $productsCount]) }}</p></a>
                @endif
            </div>

            @if ($products->isEmpty())
                <x-ui.empty-state icon="📦"
                    :title="__('business-profile.catalog.empty_title')"
                    :description="$isOwner ? __('business-profile.catalog.empty_owner_desc') : ''"
                    :actionText="$isOwner ? __('business-profile.owner.add_product') : ''"
                    :actionUrl="$isOwner ? route('business.products.create') : ''" />
            @else
            <div class="cards">
                @foreach ($products as $product)
                    @php
                        $hasDiscount = $product->old_price && $product->old_price > $product->price;
                    @endphp
                    <div class="card">
                        <div class="card-img">
                            <button type="button" class="card-heart" aria-label="{{ __('business-profile.catalog.favourite') }}"><span class="ic20"><img src="/assets/icon-heart-pointed.svg" alt=""></span></button>
                            <div class="card-badges">
                                @if ($product->created_at->gt(now()->subDays(30)))
                                    <div class="card-badge"><p>{{ __('common.badge_new') }}</p></div>
                                @endif
                                <div class="card-badge"><p>{{ $product->stock > 0 ? __('common.badge_in_stock') : __('common.badge_out_of_stock') }}</p></div>
                            </div>
                            <div class="prod-img a"><img src="{{ $product->mainImageUrl ?? '/assets/product-marble-tile.png' }}" alt=""></div>
                        </div>
                        <div class="card-title">
                            <div class="card-title-in">
                                @if ($product->reviewsCount > 0)
                                <div class="card-stars">
                                    <div class="st"><img src="/assets/icon-star-yellow.svg" alt=""></div>
                                    <p>{{ number_format($product->averageRating, 1) }} <span class="muted">({{ $product->reviewsCount }})</span></p>
                                </div>
                                @endif
                                <p class="card-cat">{{ $product->category?->name ?? '' }}</p>
                                <p class="card-name">{{ $product->name }}</p>
                            </div>
                            <div class="card-price">
                                <div class="grp">
                                    <p class="now">{{ number_format($product->price, 2) }} ₼</p>
                                    @if ($hasDiscount)<p class="old">{{ number_format($product->old_price, 2) }} ₼</p>@endif
                                </div>
                                @if ($hasDiscount && $product->discount_percent)
                                    <div class="card-off"><p>-{{ $product->discount_percent }}%</p></div>
                                @endif
                            </div>
                        </div>
                        {{-- Overlay link: keeps the whole card clickable without nesting the
                             favourite button inside an <a> (invalid HTML). --}}
                        <a class="card-link" href="{{ route('product.show', $product->slug) }}"
                           aria-label="{{ $product->name }}"></a>
                    </div>
                @endforeach
            </div>
            @endif
        </div>

        {{-- ===== SIDEBAR ===== --}}
        <div class="bp-sidebar">
            <p class="sb-kicker">{{ __('business-profile.contact.kicker') }}</p>
            @if ($profile?->contact_phone)
                <div class="sb-row"><p class="k">{{ __('business-profile.contact.phone') }}</p><p class="v">{{ $profile->contact_phone }}</p></div>
            @elseif ($user->phone)
                <div class="sb-row"><p class="k">{{ __('business-profile.contact.phone') }}</p><p class="v">{{ $user->phone }}</p></div>
            @endif
            @if ($profile?->whatsapp)
                <div class="sb-row"><p class="k">{{ __('business-profile.contact.whatsapp') }}</p><p class="v">{{ $profile->whatsapp }}</p></div>
            @endif
            <div class="sb-row"><p class="k">{{ __('business-profile.contact.email') }}</p><p class="v">{{ $profile?->contact_email ?: $user->email }}</p></div>
            @if ($profile?->website)
                <div class="sb-row"><p class="k">{{ __('business-profile.contact.website') }}</p><p class="v">{{ $profile->website }}</p></div>
            @endif
            @if ($profile?->work_hours)
                <div class="sb-row"><p class="k">{{ __('business-profile.contact.hours') }}</p><p class="v">{{ $profile->work_hours }}</p></div>
            @endif
            <div class="sb-line"></div>
            @php $langs = $profile?->languages ?: ['az']; @endphp
            <div class="sb-langs">
                <p class="lbl">{{ __('business-profile.contact.languages') }}</p>
                @foreach ($langs as $lang)
                    <div class="sb-chip"><p>{{ $langLabels[$lang] ?? mb_strtoupper($lang) }}</p></div>
                @endforeach
            </div>
            <x-ui.button variant="primary"
                class="h-[50px] w-full text-[15px] leading-[normal] font-bold whitespace-nowrap"
                onclick="window.ARCHI?.toast?.show({type:'info', title:'{{ __('business-profile.identity.coming_soon') }}'})">{{ __('business-profile.contact.get_in_touch') }}</x-ui.button>
            <x-ui.button variant="outline"
                class="h-12 w-full text-sm leading-[normal] font-semibold whitespace-nowrap"
                onclick="window.ARCHI?.toast?.show({type:'info', title:'{{ __('business-profile.identity.coming_soon') }}'})">{{ __('business-profile.contact.send_message') }}</x-ui.button>
            <div class="sb-line"></div>
            <div class="sb-row"><p class="k">{{ __('business-profile.contact.products') }}</p><p class="v">{{ $productsCount }}</p></div>
            <div class="sb-row"><p class="k">{{ __('business-profile.contact.member') }}</p><p class="v">{{ $memberYear }}</p></div>
        </div>
    </div>

</div>

</x-layout>
