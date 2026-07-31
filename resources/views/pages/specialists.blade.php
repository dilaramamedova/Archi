{{--
  Specialists catalogue (Figma 1105:26941). Ported from the old specialists.html: the
  navbar/footer placeholders are gone (the layout renders them) and the 20 cards, which
  the old page built in JS, are rendered server-side so every string is localized.
  resources/js/pages/specialists.js only sorts, filters and mirrors the active chips,
  reading the data-* attributes below.
--}}
@php
    // Card tints repeat every 4 cards, exactly like the old JS array.
    $tints = ['#f5fbff', '#fdf5ff', '#f5fffb', '#fff5f5'];

    // id -> lang/{locale}/specialists.php `people` entry; the rest is filter/sort data.
    $people = [
        ['id' => 's1',  'rate' => 4.9, 'reviews' => 416, 'years' => 12, 'projects' => 320, 'price' => 25, 'city' => 'baku',    'top' => true,  'ok' => true,  'free' => true,  'specs' => ['tile']],
        ['id' => 's2',  'rate' => 4.8, 'reviews' => 289, 'years' => 9,  'projects' => 210, 'price' => 30, 'city' => 'baku',    'top' => true,  'ok' => true,  'free' => false, 'specs' => ['tile', 'plumber']],
        ['id' => 's3',  'rate' => 5.0, 'reviews' => 124, 'years' => 7,  'projects' => 85,  'price' => 35, 'city' => 'baku',    'top' => false, 'ok' => true,  'free' => true,  'specs' => ['tile', 'interior']],
        ['id' => 's4',  'rate' => 4.7, 'reviews' => 198, 'years' => 10, 'projects' => 260, 'price' => 22, 'city' => 'sumgait', 'top' => true,  'ok' => true,  'free' => false, 'specs' => ['tile']],
        ['id' => 's5',  'rate' => 4.6, 'reviews' => 154, 'years' => 6,  'projects' => 140, 'price' => 28, 'city' => 'baku',    'top' => false, 'ok' => true,  'free' => true,  'specs' => ['tile']],
        ['id' => 's6',  'rate' => 4.9, 'reviews' => 98,  'years' => 8,  'projects' => 72,  'price' => 32, 'city' => 'ganja',   'top' => true,  'ok' => true,  'free' => false, 'specs' => ['tile', 'interior']],
        ['id' => 's7',  'rate' => 4.8, 'reviews' => 342, 'years' => 11, 'projects' => 280, 'price' => 20, 'city' => 'baku',    'top' => true,  'ok' => true,  'free' => true,  'specs' => ['plumber']],
        ['id' => 's8',  'rate' => 4.5, 'reviews' => 176, 'years' => 5,  'projects' => 120, 'price' => 24, 'city' => 'sumgait', 'top' => false, 'ok' => true,  'free' => false, 'specs' => ['plumber']],
        ['id' => 's9',  'rate' => 4.7, 'reviews' => 263, 'years' => 9,  'projects' => 230, 'price' => 18, 'city' => 'baku',    'top' => true,  'ok' => true,  'free' => true,  'specs' => ['electrician']],
        ['id' => 's10', 'rate' => 4.9, 'reviews' => 141, 'years' => 7,  'projects' => 96,  'price' => 27, 'city' => 'baku',    'top' => true,  'ok' => true,  'free' => false, 'specs' => ['electrician']],
        ['id' => 's11', 'rate' => 4.4, 'reviews' => 212, 'years' => 6,  'projects' => 180, 'price' => 12, 'city' => 'ganja',   'top' => false, 'ok' => true,  'free' => true,  'specs' => ['painter']],
        ['id' => 's12', 'rate' => 4.3, 'reviews' => 88,  'years' => 4,  'projects' => 64,  'price' => 10, 'city' => 'baku',    'top' => false, 'ok' => false, 'free' => true,  'specs' => ['painter']],
        ['id' => 's13', 'rate' => 5.0, 'reviews' => 167, 'years' => 8,  'projects' => 110, 'price' => 45, 'city' => 'baku',    'top' => true,  'ok' => true,  'free' => false, 'specs' => ['interior']],
        ['id' => 's14', 'rate' => 4.8, 'reviews' => 132, 'years' => 6,  'projects' => 78,  'price' => 40, 'city' => 'baku',    'top' => false, 'ok' => true,  'free' => true,  'specs' => ['interior']],
        ['id' => 's15', 'rate' => 4.9, 'reviews' => 74,  'years' => 13, 'projects' => 52,  'price' => 60, 'city' => 'baku',    'top' => true,  'ok' => true,  'free' => false, 'specs' => ['architect']],
        ['id' => 's16', 'rate' => 4.7, 'reviews' => 59,  'years' => 9,  'projects' => 41,  'price' => 55, 'city' => 'sumgait', 'top' => false, 'ok' => true,  'free' => true,  'specs' => ['architect', 'interior']],
        ['id' => 's17', 'rate' => 4.6, 'reviews' => 225, 'years' => 10, 'projects' => 190, 'price' => 26, 'city' => 'baku',    'top' => true,  'ok' => true,  'free' => true,  'specs' => ['tile']],
        ['id' => 's18', 'rate' => 4.2, 'reviews' => 103, 'years' => 3,  'projects' => 58,  'price' => 19, 'city' => 'other',   'top' => false, 'ok' => false, 'free' => false, 'specs' => ['tile']],
        ['id' => 's19', 'rate' => 4.5, 'reviews' => 188, 'years' => 12, 'projects' => 240, 'price' => 23, 'city' => 'ganja',   'top' => false, 'ok' => true,  'free' => true,  'specs' => ['plumber', 'tile']],
        ['id' => 's20', 'rate' => 4.8, 'reviews' => 151, 'years' => 8,  'projects' => 135, 'price' => 21, 'city' => 'baku',    'top' => true,  'ok' => true,  'free' => false, 'specs' => ['electrician', 'interior']],
    ];
@endphp
<x-layout page="specialists" :title="__('specialists.title')">

<main class="wrap spec-cat">

  {{-- breadcrumbs --}}
  <x-ui.breadcrumbs class="sp-crumbs" :items="[
      ['label' => __('specialists.crumb_home'), 'href' => route('home')],
      ['label' => __('specialists.crumb_current')],
  ]" />

  {{-- heading --}}
  <div class="sp-head">
    <div class="sp-head-l">
      <x-ui.eyebrow variant="flat" class="sp-eyebrow" :label="__('specialists.head.eyebrow')" />
      <div class="sp-title">
        <h1>{{ __('specialists.head.title') }}</h1>
        <span class="count">{{ __('specialists.head.count') }}</span>
      </div>
    </div>
    <div class="fsort sp-sort" id="spSort" data-open="false">
      <span class="lbl">{{ __('specialists.sort.label') }}</span>
      <span class="val" id="spSortVal">{{ __('specialists.sort.rating') }}</span>
      <img class="car" src="/assets/ic-chevron-sm.svg" alt="">
      <ul class="fsort-menu sp-sort-menu" id="spSortMenu">
        <li data-sort="rating" data-on="true">{{ __('specialists.sort.rating') }}</li>
        <li data-sort="reviews" data-on="false">{{ __('specialists.sort.reviews') }}</li>
        <li data-sort="exp" data-on="false">{{ __('specialists.sort.exp') }}</li>
        <li data-sort="cheap" data-on="false">{{ __('specialists.sort.cheap') }}</li>
        <li data-sort="projects" data-on="false">{{ __('specialists.sort.projects') }}</li>
      </ul>
    </div>
  </div>

  {{-- active filter chips (built by specialists.js from the sidebar state) --}}
  <div class="sp-chips" id="spChips">
    <button class="sp-clear" id="spClear">{{ __('specialists.filters.clear') }}</button>
  </div>

  {{-- body --}}
  <div class="sp-body">

    {{-- filter sidebar --}}
    <aside class="fside sp-side">
     <div class="fside-scroll sp-fs-scroll">

      {{-- specialization --}}
      <div class="sp-fs" id="spSpecBlock">
        <p class="sp-fs-title">{{ __('specialists.filters.spec_title') }}</p>
        <div class="sp-cat" data-spec="tile" data-on="true"><span class="t">{{ __('specialists.filters.spec.tile') }}</span><span class="n">86</span></div>
        <div class="sp-cat" data-spec="plumber" data-on="false"><span class="t">{{ __('specialists.filters.spec.plumber') }}</span><span class="n">54</span></div>
        <div class="sp-cat" data-spec="electrician" data-on="false"><span class="t">{{ __('specialists.filters.spec.electrician') }}</span><span class="n">42</span></div>
        <div class="sp-cat" data-spec="painter" data-on="false"><span class="t">{{ __('specialists.filters.spec.painter') }}</span><span class="n">38</span></div>
        <div class="sp-cat" data-spec="interior" data-on="false"><span class="t">{{ __('specialists.filters.spec.interior') }}</span><span class="n">18</span></div>
        <div class="sp-cat" data-spec="architect" data-on="false"><span class="t">{{ __('specialists.filters.spec.architect') }}</span><span class="n">10</span></div>
      </div>

      <div class="sp-fs-div"></div>

      {{-- city --}}
      <div class="sp-fs" id="spCityBlock">
        <p class="sp-fs-title">{{ __('specialists.filters.city_title') }}</p>
        <div class="sp-check" data-city="baku" data-on="true"><span class="fside-box sp-box"></span><span class="lbl">{{ __('specialists.filters.city.baku') }}</span><span class="n">196</span></div>
        <div class="sp-check" data-city="sumgait" data-on="false"><span class="fside-box sp-box"></span><span class="lbl">{{ __('specialists.filters.city.sumgait') }}</span><span class="n">28</span></div>
        <div class="sp-check" data-city="ganja" data-on="false"><span class="fside-box sp-box"></span><span class="lbl">{{ __('specialists.filters.city.ganja') }}</span><span class="n">16</span></div>
        <div class="sp-check" data-city="other" data-on="false"><span class="fside-box sp-box"></span><span class="lbl">{{ __('specialists.filters.city.other') }}</span><span class="n">8</span></div>
      </div>

      <div class="sp-fs-div"></div>

      {{-- rating --}}
      <div class="sp-fs" id="spRateBlock">
        <p class="sp-fs-title">{{ __('specialists.filters.rating_title') }}</p>
        <div class="sp-radio" data-min="4.5" data-on="true" data-chip="{{ __('specialists.filters.rating_chip', ['min' => '4.5']) }}"><span class="sp-dot"></span><span class="lbl"><img src="/assets/ic-star.svg" alt="">{{ __('specialists.filters.rating_45') }}</span></div>
        <div class="sp-radio" data-min="4" data-on="false" data-chip="{{ __('specialists.filters.rating_chip', ['min' => '4']) }}"><span class="sp-dot"></span><span class="lbl"><img src="/assets/ic-star.svg" alt="">{{ __('specialists.filters.rating_40') }}</span></div>
        <div class="sp-radio" data-min="3.5" data-on="false" data-chip="{{ __('specialists.filters.rating_chip', ['min' => '3.5']) }}"><span class="sp-dot"></span><span class="lbl"><img src="/assets/ic-star.svg" alt="">{{ __('specialists.filters.rating_35') }}</span></div>
      </div>

      <div class="sp-fs-div"></div>

      {{-- experience --}}
      <div class="sp-fs" id="spYearBlock">
        <p class="sp-fs-title">{{ __('specialists.filters.exp_title') }}</p>
        <div class="sp-years">
          <span class="sp-year" data-min="1" data-max="3" data-on="false">{{ __('specialists.filters.years_1_3') }}</span>
          <span class="sp-year" data-min="3" data-max="5" data-on="false">{{ __('specialists.filters.years_3_5') }}</span>
          <span class="sp-year" data-min="5" data-max="10" data-on="true">{{ __('specialists.filters.years_5_10') }}</span>
          <span class="sp-year" data-min="10" data-max="99" data-on="false">{{ __('specialists.filters.years_10') }}</span>
        </div>
      </div>

      <div class="sp-fs-div"></div>

      {{-- switches --}}
      <div class="sp-toggle">
        <span class="lbl">{{ __('specialists.filters.verified_only') }}</span>
        <x-ui.toggle id="spVerified" :on="true" size="sm" :hover="false"
                     :aria-label="__('specialists.filters.verified_only')" />
      </div>
      <div class="sp-toggle">
        <span class="lbl">{{ __('specialists.filters.free_this_week') }}</span>
        <x-ui.toggle id="spFree" :on="false" size="sm" :hover="false"
                     :aria-label="__('specialists.filters.free_this_week')" />
      </div>

     </div>{{-- /.fside-scroll --}}

      <div class="fside-apply-sep"></div>
      <button class="fside-apply" id="spApply">{{ __('specialists.filters.apply') }}</button>
    </aside>

    {{-- grid — the cards start in the Figma order; sorting only reorders them --}}
    <div class="sp-grid" id="spGrid">
      @foreach ($people as $i => $p)
        @php
            $badges = [];
            if ($p['top']) { $badges[] = ['class' => 'top-master', 'label' => __('specialists.card.badge_top')]; }
            if ($p['ok']) { $badges[] = ['class' => 'ok', 'label' => __('specialists.card.badge_verified')]; }
        @endphp
        <a class="sp-card" href="{{ route('specialist') }}"
           data-rate="{{ $p['rate'] }}"
           data-reviews="{{ $p['reviews'] }}"
           data-years="{{ $p['years'] }}"
           data-projects="{{ $p['projects'] }}"
           data-price="{{ $p['price'] }}"
           data-city="{{ $p['city'] }}"
           data-specs="{{ implode(',', $p['specs']) }}"
           data-verified="{{ $p['ok'] ? 'true' : 'false' }}"
           data-free="{{ $p['free'] ? 'true' : 'false' }}">
          <div class="top" style="background:{{ $tints[$i % 4] }}">
            <div class="badges">@foreach ($badges as $b)<span class="b {{ $b['class'] }}">{{ $b['label'] }}</span>@endforeach</div>
            <div class="avatar">{{ __('specialists.people.' . $p['id'] . '.initials') }}</div>
            <div class="role">{{ __('specialists.people.' . $p['id'] . '.role') }}</div>
            <div class="rate"><img class="st" src="/assets/ic-star.svg" alt=""><span class="v">{{ number_format($p['rate'], 1) }}</span><span class="r">{{ __('specialists.card.reviews', ['count' => $p['reviews']]) }}</span></div>
          </div>
          <div class="name">{{ __('specialists.people.' . $p['id'] . '.name') }}</div>
          <div class="meta"><span class="m">{{ __('specialists.card.exp', ['years' => $p['years']]) }}</span><span class="d">·</span><span class="m">{{ __('specialists.card.projects', ['count' => $p['projects']]) }}</span></div>
          <div class="foot">
            <span class="price">{{ __('specialists.card.price', ['price' => $p['price']]) }}</span>
            {{-- design-system tone on a <span>: the card itself is the <a>, so this may not
                 become a real <x-ui.button> (interactive elements cannot nest). --}}
            <span class="ui-btn ui-btn-primary h-[38px] px-4 text-[13px] font-semibold whitespace-nowrap duration-200"
                  data-hover="true">{{ __('specialists.card.view_profile') }}</span>
          </div>
        </a>
      @endforeach
      <p class="sp-empty" id="spEmpty" hidden>{{ __('specialists.empty') }}</p>
    </div>
  </div>

</main>

</x-layout>
