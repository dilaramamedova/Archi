{{--
  Specialist profile (Figma 777:2779, `pp-` prefix). Ported from the old specialist.html:
  the navbar/footer placeholders are gone (the layout renders them) and the two buttons
  that the old archi.js hijacked now carry their target URL as data-url, read by
  resources/js/pages/specialist.js.
--}}
@php
    // Portfolio tiles: label key -> image. The 6th tile is the "+24 projects" overlay.
    $tiles = ['t1' => 'pf1.jpg', 't2' => 'pf2.jpg', 't3' => 'pf3.jpg', 't4' => 'pf4.jpg', 't5' => 'pf5.jpg'];
@endphp
<x-layout page="specialist" :title="__('specialist.title')">

<main class="pp">

  {{-- breadcrumb --}}
  <nav class="pp-crumbs">
    <a href="{{ route('home') }}">{{ __('specialist.crumb_home') }}</a>
    <span class="sep">/</span>
    <a href="{{ route('specialists') }}">{{ __('specialist.crumb_specialists') }}</a>
    <span class="sep">/</span>
    <span class="cur">{{ __('specialist.crumb_current') }}</span>
  </nav>

  {{-- header --}}
  <div class="pp-head">
    <div class="pp-head-l">

      {{-- identity --}}
      <div class="pp-id">
        <div class="pp-ava">
          <div class="ring"><img src="/assets/sp-avatar.png" alt="{{ __('specialist.id.name') }}"></div>
          <span class="pp-badge"><img src="/assets/ic-crown.svg" alt="">{{ __('specialist.id.badge_top') }}</span>
          <button type="button" class="pp-fav" id="ppFav" data-on="false" aria-pressed="false" aria-label="{{ __('specialist.id.fav_aria') }}"><img src="/assets/ic-heart2.svg" alt=""></button>
        </div>
        <div class="pp-id-r">
          <span class="pp-verified"><img src="/assets/ic-check.svg" alt="">{{ __('specialist.id.verified') }}</span>
          <div class="pp-name">
            <div>
              <h1>{{ __('specialist.id.name') }}</h1>
              <p class="role">{{ __('specialist.id.role') }}</p>
            </div>
            <div class="pp-rate">
              <span class="pp-stars">
                @for ($i = 0; $i < 5; $i++)<img src="/assets/ic-star-amber.svg" alt="">@endfor
              </span>
              <span class="v">{{ __('specialist.id.rate') }}</span>
              <span class="r">{{ __('specialist.id.reviews') }}</span>
            </div>
          </div>
          <p class="pp-meta">{{ __('specialist.id.meta_exp') }}<span class="dot">·</span>{{ __('specialist.id.meta_projects') }}<span class="dot">·</span>{{ __('specialist.id.meta_city') }}</p>
        </div>
      </div>

      {{-- about --}}
      <section class="pp-about">
        <div class="pp-sechead">
          <div class="pp-eyebrow"><span class="line"></span><p>{{ __('specialist.about.eyebrow') }}</p></div>
          <h2>{{ __('specialist.about.title') }}</h2>
        </div>
        <p class="txt">{{ __('specialist.about.text') }}</p>
        <div class="pp-tags">
          @foreach (['t1', 't2', 't3', 't4', 't5', 't6'] as $tag)
            <span class="pp-tag">{{ __('specialist.about.tags.' . $tag) }}</span>
          @endforeach
        </div>
      </section>

    </div>

    {{-- booking --}}
    <aside class="pp-book">
      <div class="ph">
        <span class="lbl">{{ __('specialist.book.label') }}</span>
        <div class="prow"><span class="now">{{ __('specialist.book.price') }}</span><span class="sub">{{ __('specialist.book.price_sub') }}</span></div>
      </div>
      <button class="pp-btn y" id="ppCalc" data-url="{{ route('calculator') }}">{{ __('specialist.book.consult') }}</button>
      <button class="pp-btn w" id="ppMsg" data-url="{{ route('login') }}">{{ __('specialist.book.message') }}</button>
      <div class="div"></div>
      <div class="pp-stat"><span class="k">{{ __('specialist.book.response_k') }}</span><span class="v">{{ __('specialist.book.response_v') }}</span></div>
      <div class="pp-stat"><span class="k">{{ __('specialist.book.done_k') }}</span><span class="v">{{ __('specialist.book.done_v') }}</span></div>
      <div class="pp-stat"><span class="k">{{ __('specialist.book.member_k') }}</span><span class="v">{{ __('specialist.book.member_v') }}</span></div>
      <div class="pp-free"><span class="d"></span>{{ __('specialist.book.free') }}</div>
    </aside>
  </div>

  {{-- portfolio --}}
  <section class="pp-sec">
    <div class="pp-sechead">
      <div class="pp-eyebrow"><span class="line"></span><p>{{ __('specialist.portfolio.eyebrow') }}</p></div>
      <h2>{{ __('specialist.portfolio.title') }}</h2>
    </div>
    <div class="pp-grid">
      <div class="pp-grow">
        @foreach (array_slice($tiles, 0, 3, true) as $key => $img)
          <a class="pp-tile"><img src="/assets/{{ $img }}" alt="{{ __('specialist.portfolio.tiles.' . $key) }}"><span class="lb">{{ __('specialist.portfolio.tiles.' . $key) }}</span></a>
        @endforeach
      </div>
      <div class="pp-grow">
        @foreach (array_slice($tiles, 3, 2, true) as $key => $img)
          <a class="pp-tile"><img src="/assets/{{ $img }}" alt="{{ __('specialist.portfolio.tiles.' . $key) }}"><span class="lb">{{ __('specialist.portfolio.tiles.' . $key) }}</span></a>
        @endforeach
        <a class="pp-tile more">
          <img src="/assets/pf6.jpg" alt="">
          <span class="ov"><b>{{ __('specialist.portfolio.more_count') }}</b><span>{{ __('specialist.portfolio.more_link') }}</span></span>
        </a>
      </div>
    </div>
  </section>

  {{-- services --}}
  <section class="pp-sec">
    <div class="pp-sechead">
      <div class="pp-eyebrow"><span class="line"></span><p>{{ __('specialist.services.eyebrow') }}</p></div>
      <h2>{{ __('specialist.services.title') }}</h2>
    </div>
    <div class="pp-svc-list">
      @foreach (['s1', 's2', 's3', 's4'] as $svc)
        <a class="pp-svc">
          <span class="l"><span class="t">{{ __('specialist.services.items.' . $svc . '.title') }}</span><span class="s">{{ __('specialist.services.items.' . $svc . '.sub') }}</span></span>
          <span class="rr"><span class="pr">{{ __('specialist.services.items.' . $svc . '.price') }}</span><span class="ar">→</span></span>
        </a>
      @endforeach
    </div>
  </section>

  {{-- reviews --}}
  <section class="pp-sec">
    <div class="pp-sechead">
      <div class="pp-eyebrow"><span class="line"></span><p>{{ __('specialist.reviews.eyebrow') }}</p></div>
      <h2>{{ __('specialist.reviews.title') }}</h2>
    </div>
    <div class="pp-score">
      <span class="n">{{ __('specialist.reviews.score') }}</span>
      <span class="c">
        <span class="ss">
          @for ($i = 0; $i < 5; $i++)<img src="/assets/ic-star.svg" alt="">@endfor
        </span>
        <span class="cnt">{{ __('specialist.reviews.count') }}</span>
      </span>
    </div>
    <div class="pp-revs">
      @foreach (['r1', 'r2'] as $rev)
        <article class="pp-rev">
          <div class="hd">
            <span class="av">{{ __('specialist.reviews.items.' . $rev . '.initial') }}</span>
            <span class="nm"><span class="n">{{ __('specialist.reviews.items.' . $rev . '.name') }}</span><span class="d">{{ __('specialist.reviews.items.' . $rev . '.date') }}</span></span>
          </div>
          <div class="st">@for ($i = 0; $i < 5; $i++)<img src="/assets/ic-star.svg" alt="">@endfor</div>
          <p class="tx">{{ __('specialist.reviews.items.' . $rev . '.text') }}</p>
        </article>
      @endforeach
    </div>
    <a class="sec-more"><p>{{ __('specialist.reviews.more') }}</p><img src="/assets/ic-arrow.svg" alt=""></a>
  </section>

</main>

</x-layout>
