{{--
  Public company storefront (Figma 1105:24979) — what a buyer sees when opening a
  business profile. Source: the old static "biznes-profil" page.

  The page keeps its own legacy class names; the styles live in
  resources/css/pages/business-profile.css, scoped under the `.bp-page` root.
  The product card here (`.card*`) is NOT the shared <x-pcard> — different markup and
  measurements — so it is written out inline, driven by the $cards array below.
--}}
@php
    $cards = [
        ['shape' => 'a', 'img' => '/assets/fig/d4fa5ba5165a.jpg', 'cat' => 'cat_paint', 'name' => 'paint'],
        ['shape' => 'b', 'img' => '/assets/fig/8409fa281b3d.jpg', 'cat' => 'cat_insulation', 'name' => 'insulation'],
        ['shape' => 'a', 'img' => '/assets/fig/d4fa5ba5165a.jpg', 'cat' => 'cat_paint', 'name' => 'paint'],
    ];
@endphp
<x-layout page="business-profile" :title="__('business-profile.title')">

<div class="page bp-page">

    {{-- ===== COVER ===== --}}
    <div class="cover-wrap">
        <div class="cover">
            <div class="bg">
                <img src="/assets/fig/bd3b1542ad10.jpg" alt="">
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
                    <div class="id-badge"><p>{{ __('business-profile.identity.verified') }}</p></div>
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
                    <p class="val">4.8</p>
                    <p class="lbl">{{ __('business-profile.identity.rating_label') }}</p>
                    <p class="dot">·</p>
                    <p class="cnt">{{ __('business-profile.identity.product_count') }}</p>
                </div>
            </div>
        </div>
        <div class="id-actions">
            <div class="btn-yellow"><p>{{ __('business-profile.identity.go_to_products') }}</p></div>
            <div class="btn-outline"><p>{{ __('business-profile.identity.follow') }}</p></div>
        </div>
    </div>

    {{-- ===== BODY ===== --}}
    <div class="bp-body">
        <div class="bp-left">

            <div class="sec-head">
                <div class="kicker"><div class="bar"></div><p>{{ __('business-profile.about.kicker') }}</p></div>
                <p class="sec-title">{{ __('business-profile.about.title') }}</p>
            </div>

            <p class="about-p">{{ __('business-profile.about.text') }}</p>

            <div class="sec-head">
                <div class="kicker"><div class="bar"></div><p>{{ __('business-profile.showrooms.kicker') }}</p></div>
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
                    <div class="kicker"><div class="bar"></div><p>{{ __('business-profile.catalog.kicker') }}</p></div>
                    <p class="sec-title">{{ __('business-profile.catalog.title') }}</p>
                </div>
                <div class="see-all"><p>{{ __('business-profile.catalog.see_all') }}</p></div>
            </div>

            <div class="cards">
                @foreach ($cards as $card)
                    <div class="card">
                        <div class="card-img">
                            <div class="card-heart"><div class="ic20"><img src="/assets/ic-heart2.svg" alt=""></div></div>
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
                                    <div class="st"><img src="/assets/ic-star.svg" alt=""></div>
                                    <p>4.4 <span class="muted">{{ __('business-profile.catalog.reviews') }}</span></p>
                                </div>
                                <p class="card-cat">{{ __("business-profile.catalog.{$card['cat']}") }}</p>
                                <p class="card-name">{{ __("business-profile.catalog.prod_{$card['name']}") }}</p>
                            </div>
                            <div class="card-price">
                                <div class="grp"><p class="now">23.90 ₼</p><p class="old">15,99 ₼</p></div>
                                <div class="card-off"><p>-48%</p></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ===== SIDEBAR ===== --}}
        <div class="bp-sidebar">
            <p class="sb-kicker">{{ __('business-profile.contact.kicker') }}</p>
            <div class="sb-row"><p class="k">{{ __('business-profile.contact.phone') }}</p><p class="v">+994 12 555 08 15</p></div>
            <div class="sb-row"><p class="k">{{ __('business-profile.contact.whatsapp') }}</p><p class="v">+994 50 555 08 15</p></div>
            <div class="sb-row"><p class="k">{{ __('business-profile.contact.email') }}</p><p class="v">info@archibuild.az</p></div>
            <div class="sb-row"><p class="k">{{ __('business-profile.contact.website') }}</p><p class="v">archibuild.az</p></div>
            <div class="sb-row"><p class="k">{{ __('business-profile.contact.hours') }}</p><p class="v">{{ __('business-profile.contact.hours_value') }}</p></div>
            <div class="sb-line"></div>
            <div class="sb-langs">
                <p class="lbl">{{ __('business-profile.contact.languages') }}</p>
                <div class="sb-chip"><p>AZ</p></div>
                <div class="sb-chip"><p>RU</p></div>
                <div class="sb-chip"><p>EN</p></div>
            </div>
            <div class="sb-btn-yellow"><p>{{ __('business-profile.contact.get_in_touch') }}</p></div>
            <div class="sb-btn-outline"><p>{{ __('business-profile.contact.send_message') }}</p></div>
            <div class="sb-line"></div>
            <div class="sb-row"><p class="k">{{ __('business-profile.contact.response_time') }}</p><p class="v">{{ __('business-profile.contact.response_time_value') }}</p></div>
            <div class="sb-row"><p class="k">{{ __('business-profile.contact.products') }}</p><p class="v">1,240</p></div>
            <div class="sb-row"><p class="k">{{ __('business-profile.contact.member') }}</p><p class="v">{{ __('business-profile.contact.member_value') }}</p></div>
        </div>
    </div>

</div>

</x-layout>
