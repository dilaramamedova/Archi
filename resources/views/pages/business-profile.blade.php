{{--
  Public company storefront (Figma 1105:24979) — what a buyer sees when opening a
  business profile. Source: the old static "biznes-profil" page.

  The page keeps its own legacy class names; the styles live in
  resources/css/pages/business-profile.css, scoped under the `.bp-page` root.
  The product card here (`.card*`) is NOT the shared <x-pcard> — different markup and
  measurements — so it is written out inline, driven by the $cards array below.
--}}
{{-- TODO: Replace with dynamic data from controller --}}
@php
    $cards = [
        ['shape' => 'a', 'img' => '/assets/product-facade-paint-bucket-grey.jpg', 'cat' => 'cat_paint', 'name' => 'paint'],
        ['shape' => 'b', 'img' => '/assets/product-mineral-wool-roll-grey.jpg', 'cat' => 'cat_insulation', 'name' => 'insulation'],
        ['shape' => 'a', 'img' => '/assets/product-facade-paint-bucket-grey.jpg', 'cat' => 'cat_paint', 'name' => 'paint'],
    ];
@endphp
<x-layout page="business-profile" :title="__('business-profile.title')">

<div class="page bp-page">

    {{-- ===== COVER ===== --}}
    <div class="cover-wrap">
        <div class="cover">
            <div class="bg">
                <img src="/assets/renovation-before-after-wide.jpg" alt="">
                <div class="ov"></div>
            </div>
        </div>
    </div>

    {{-- ===== IDENTITY ===== --}}
    <div class="identity">
        <div class="id-left">
            <div class="biz-logo"><p>{{ __('business-profile.identity.logo_initials') }}</p></div>
            <div class="id-info">
                <div class="id-name-row">
                    <p class="id-name">{{ __('business-profile.identity.name') }}</p>
                    <x-ui.badge tone="ok" size="sm" class="px-2.5 py-1 text-xs">{{ __('business-profile.identity.verified') }}</x-ui.badge>
                </div>
                <div class="id-meta">
                    <p class="m">{{ __('business-profile.identity.location') }}</p>
                    <p class="d">·</p>
                    <p class="m">{{ __('business-profile.identity.since') }}</p>
                    <p class="d">·</p>
                    <p class="m">{{ __('business-profile.identity.employees') }}</p>
                    <p class="d">·</p>
                    <p class="m">{{ __('business-profile.identity.showrooms') }}</p>
                </div>
                <div class="id-rating">
                    <p class="star">★</p>
                    <p class="val">{{ __('business-profile.identity.rating_value') }}</p>
                    <p class="lbl">{{ __('business-profile.identity.rating_label') }}</p>
                    <p class="dot">·</p>
                    <p class="cnt">{{ __('business-profile.identity.product_count') }}</p>
                </div>
            </div>
        </div>
        <div class="id-actions">
            <x-ui.button variant="primary" :href="route('search', ['tab' => 'prod'])"
                class="h-[46px] px-6 text-sm leading-[normal] font-bold whitespace-nowrap">{{ __('business-profile.identity.go_to_products') }}</x-ui.button>
            <x-ui.button variant="outline"
                class="h-[46px] px-6 text-sm leading-[normal] font-semibold whitespace-nowrap">{{ __('business-profile.identity.follow') }}</x-ui.button>
        </div>
    </div>

    {{-- ===== BODY ===== --}}
    <div class="bp-body">
        <div class="bp-left">

            <div class="sec-head">
                <x-ui.eyebrow variant="kicker" :label="__('business-profile.about.kicker')" />
                <p class="sec-title">{{ __('business-profile.about.title') }}</p>
            </div>

            <p class="about-p">{{ __('business-profile.about.text') }}</p>

            <div class="sec-head">
                <x-ui.eyebrow variant="kicker" :label="__('business-profile.showrooms.kicker')" />
                <p class="sec-title">{{ __('business-profile.showrooms.title') }}</p>
            </div>

            <div class="showrooms">
                @foreach (['r1', 'r2', 'r3'] as $room)
                    <div class="sr-card">
                        <p class="nm">{{ __("business-profile.showrooms.{$room}_name") }}</p>
                        <p class="ad">{{ __("business-profile.showrooms.{$room}_address") }}</p>
                        <p class="hr">{{ __("business-profile.showrooms.{$room}_hours") }}</p>
                    </div>
                @endforeach
            </div>

            <div class="catalog-head">
                <div class="sec-head">
                    <x-ui.eyebrow variant="kicker" :label="__('business-profile.catalog.kicker')" />
                    <p class="sec-title">{{ __('business-profile.catalog.title') }}</p>
                </div>
                <a class="see-all" href="{{ route('search', ['tab' => 'prod']) }}"><p>{{ __('business-profile.catalog.see_all') }}</p></a>
            </div>

            <div class="cards">
                @foreach ($cards as $card)
                    <div class="card">
                        <div class="card-img">
                            <button type="button" class="card-heart" aria-label="{{ __('business-profile.catalog.favourite') }}"><span class="ic20"><img src="/assets/icon-heart-pointed.svg" alt=""></span></button>
                            <div class="card-badges">
                                <div class="card-badge"><p>{{ __('common.badge_new') }}</p></div>
                                <div class="card-badge"><p>{{ __('common.badge_in_stock') }}</p></div>
                            </div>
                            <div class="card-dots"><div class="active"></div><div class="dot"></div><div class="dot"></div></div>
                            <div class="prod-img {{ $card['shape'] }}"><img src="{{ $card['img'] }}" alt=""></div>
                        </div>
                        <div class="card-title">
                            <div class="card-title-in">
                                <div class="card-stars">
                                    <div class="st"><img src="/assets/icon-star-yellow.svg" alt=""></div>
                                    <p>{{ __('business-profile.catalog.rating_value') }} <span class="muted">{{ __('business-profile.catalog.reviews') }}</span></p>
                                </div>
                                <p class="card-cat">{{ __("business-profile.catalog.{$card['cat']}") }}</p>
                                <p class="card-name">{{ __("business-profile.catalog.prod_{$card['name']}") }}</p>
                            </div>
                            <div class="card-price">
                                <div class="grp"><p class="now">{{ __('business-profile.catalog.price_now') }}</p><p class="old">{{ __('business-profile.catalog.price_old') }}</p></div>
                                <div class="card-off"><p>{{ __('business-profile.catalog.discount') }}</p></div>
                            </div>
                        </div>
                        {{-- Overlay link: keeps the whole card clickable without nesting the
                             favourite button inside an <a> (invalid HTML). --}}
                        <a class="card-link" href="{{ route('product') }}"
                           aria-label="{{ __("business-profile.catalog.prod_{$card['name']}") }}"></a>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ===== SIDEBAR ===== --}}
        <div class="bp-sidebar">
            <p class="sb-kicker">{{ __('business-profile.contact.kicker') }}</p>
            <div class="sb-row"><p class="k">{{ __('business-profile.contact.phone') }}</p><p class="v">{{ __('business-profile.contact.phone_value') }}</p></div>
            <div class="sb-row"><p class="k">{{ __('business-profile.contact.whatsapp') }}</p><p class="v">{{ __('business-profile.contact.whatsapp_value') }}</p></div>
            <div class="sb-row"><p class="k">{{ __('business-profile.contact.email') }}</p><p class="v">{{ __('business-profile.contact.email_value') }}</p></div>
            <div class="sb-row"><p class="k">{{ __('business-profile.contact.website') }}</p><p class="v">{{ __('business-profile.contact.website_value') }}</p></div>
            <div class="sb-row"><p class="k">{{ __('business-profile.contact.hours') }}</p><p class="v">{{ __('business-profile.contact.hours_value') }}</p></div>
            <div class="sb-line"></div>
            <div class="sb-langs">
                <p class="lbl">{{ __('business-profile.contact.languages') }}</p>
                <div class="sb-chip"><p>{{ __('business-profile.contact.lang_az') }}</p></div>
                <div class="sb-chip"><p>{{ __('business-profile.contact.lang_ru') }}</p></div>
                <div class="sb-chip"><p>{{ __('business-profile.contact.lang_en') }}</p></div>
            </div>
            <x-ui.button variant="primary"
                class="h-[50px] w-full text-[15px] leading-[normal] font-bold whitespace-nowrap">{{ __('business-profile.contact.get_in_touch') }}</x-ui.button>
            <x-ui.button variant="outline"
                class="h-12 w-full text-sm leading-[normal] font-semibold whitespace-nowrap">{{ __('business-profile.contact.send_message') }}</x-ui.button>
            <div class="sb-line"></div>
            <div class="sb-row"><p class="k">{{ __('business-profile.contact.response_time') }}</p><p class="v">{{ __('business-profile.contact.response_time_value') }}</p></div>
            <div class="sb-row"><p class="k">{{ __('business-profile.contact.products') }}</p><p class="v">{{ __('business-profile.contact.products_value') }}</p></div>
            <div class="sb-row"><p class="k">{{ __('business-profile.contact.member') }}</p><p class="v">{{ __('business-profile.contact.member_value') }}</p></div>
        </div>
    </div>

</div>

</x-layout>
