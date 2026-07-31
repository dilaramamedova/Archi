{{--
  Catalog page (Figma 959:10198). Ported from the old catalog.html.
  The product grid was built in JS there; it is rendered server-side here so the
  card copy is translated — resources/js/pages/catalog.js only reorders the cards
  when the sort option changes (numeric data comes from the data-* attributes).
--}}
<x-layout page="catalog" :title="__('catalog.title')">
@php
    $tilePaint = '/assets/fig/50873ec31b52.jpg';
    $tileWool = '/assets/fig/6146d21348a6.jpg';
    $tileMarble = '/assets/fig/1ed736a990f0.jpg';

    // Non-textual card data; the copy lives in lang/*/catalog.php ('products').
    $cards = [
        ['img' => $tilePaint,  'now' => 23.90, 'old' => 45.99, 'off' => '-48%', 'rate' => 4.4],
        ['img' => $tileWool,   'now' => 49.90, 'old' => 72.00, 'off' => '-31%', 'rate' => 4.8],
        ['img' => $tileMarble, 'now' => 31.50, 'old' => 40.00, 'off' => '-21%', 'rate' => 4.6],
        ['img' => $tileMarble, 'now' => 18.90, 'old' => 26.00, 'off' => '-27%', 'rate' => 4.3],
        ['img' => $tilePaint,  'now' => 12.90, 'old' => 19.00, 'off' => '-32%', 'rate' => 4.7],
        ['img' => $tileWool,   'now' => 38.00, 'old' => 55.00, 'off' => '-31%', 'rate' => 4.9],
        ['img' => $tileWool,   'now' => 27.40, 'old' => 39.00, 'off' => '-30%', 'rate' => 4.5],
        ['img' => $tileMarble, 'now' => 22.00, 'old' => 30.00, 'off' => '-27%', 'rate' => 4.2],
        ['img' => $tilePaint,  'now' => 15.50, 'old' => 21.00, 'off' => '-26%', 'rate' => 4.6],
        ['img' => $tileWool,   'now' => 34.80, 'old' => 48.00, 'off' => '-27%', 'rate' => 4.7],
        ['img' => $tileMarble, 'now' => 56.00, 'old' => 79.00, 'off' => '-29%', 'rate' => 4.8],
        ['img' => $tilePaint,  'now' => 14.20, 'old' => 18.50, 'off' => '-23%', 'rate' => 4.1],
        ['img' => $tileMarble, 'now' => 88.00, 'old' => 120.00, 'off' => '-27%', 'rate' => 4.9],
        ['img' => $tileWool,   'now' => 19.90, 'old' => 27.00, 'off' => '-26%', 'rate' => 4.4],
        ['img' => $tilePaint,  'now' => 26.70, 'old' => 35.00, 'off' => '-24%', 'rate' => 4.5],
        ['img' => $tileMarble, 'now' => 29.90, 'old' => 42.00, 'off' => '-29%', 'rate' => 4.6],
        ['img' => $tileWool,   'now' => 41.50, 'old' => 58.00, 'off' => '-28%', 'rate' => 4.3],
        ['img' => $tilePaint,  'now' => 33.20, 'old' => 45.00, 'off' => '-26%', 'rate' => 4.7],
        ['img' => $tileMarble, 'now' => 21.40, 'old' => 29.00, 'off' => '-26%', 'rate' => 4.2],
        ['img' => $tileWool,   'now' => 47.60, 'old' => 65.00, 'off' => '-27%', 'rate' => 4.8],
    ];

    $copy = __('catalog.products');
    $sizes = ['30×30', '60×60', '60×120', '80×80', '20×20'];
@endphp

<main class="wrap catalog">

  <nav class="cat-crumbs">
    <a href="{{ route('home') }}">{{ __('common.home') }}</a>
    <span class="sep">/</span>
    <a href="#">{{ __('common.catalog') }}</a>
    <span class="sep">/</span>
    <span class="cur">{{ __('catalog.crumb_current') }}</span>
  </nav>

  <div class="cat-head">
    <div class="cat-head-l">
      <div class="eyebrow"><span class="line"></span><p>{{ __('catalog.head.eyebrow') }}</p></div>
      <div class="title-row">
        <h1>{{ __('catalog.head.title') }}</h1>
        <span class="count">{{ __('catalog.head.count') }}</span>
      </div>
    </div>
    <div class="fsort cat-sort" id="catSort" data-open="false">
      <span class="lbl">{{ __('catalog.sort.label') }}</span>
      <span class="val" id="sortVal">{{ __('catalog.sort.popular') }}</span>
      <span class="car">⌄</span>
      <ul class="fsort-menu sort-menu" id="sortMenu">
        <li data-sort="pop" data-on="true">{{ __('catalog.sort.popular') }}</li>
        <li data-sort="cheap" data-on="false">{{ __('catalog.sort.cheap') }}</li>
        <li data-sort="exp" data-on="false">{{ __('catalog.sort.expensive') }}</li>
        <li data-sort="rating" data-on="false">{{ __('catalog.sort.rating') }}</li>
        <li data-sort="new" data-on="false">{{ __('catalog.sort.newest') }}</li>
      </ul>
    </div>
  </div>

  {{-- active filter chips — built by catalog.js from the sidebar state --}}
  <div class="cat-chips" id="catChips"
       data-l-size="{{ __('catalog.chips.size') }}"
       data-l-surface="{{ __('catalog.chips.surface') }}"
       data-l-price="{{ __('catalog.chips.price') }}">
    <button class="cat-clear" id="catClear">{{ __('catalog.chips.clear') }}</button>
  </div>

  <div class="cat-body">

    <aside class="fside">
     <div class="fside-scroll">

      <div class="fs-block">
        <p class="fs-title">{{ __('catalog.filters.categories') }}</p>
        <div class="fs-cat" data-on="true"><span>{{ __('catalog.filters.cat_tiles') }}</span><span class="n">860</span></div>
        <div class="fs-cat" data-on="false"><span>{{ __('catalog.filters.cat_paint') }}</span><span class="n">412</span></div>
        <div class="fs-cat" data-on="false"><span>{{ __('catalog.filters.cat_laminate') }}</span><span class="n">340</span></div>
        <div class="fs-cat" data-on="false"><span>{{ __('catalog.filters.cat_plumbing') }}</span><span class="n">296</span></div>
        <div class="fs-cat" data-on="false"><span>{{ __('catalog.filters.cat_electric') }}</span><span class="n">188</span></div>
        <div class="fs-cat" data-on="false"><span>{{ __('catalog.filters.cat_insulation') }}</span><span class="n">154</span></div>
      </div>

      <div class="fs-div"></div>

      <div class="fs-block">
        <p class="fs-title">{{ __('catalog.filters.price') }}</p>
        <div class="fs-price-inputs">
          <input class="in" id="fsMin" type="text" inputmode="numeric" value="20" aria-label="{{ __('catalog.filters.price_min_aria') }}">
          <span class="dash">—</span>
          <input class="in" id="fsMax" type="text" inputmode="numeric" value="50" aria-label="{{ __('catalog.filters.price_max_aria') }}">
        </div>
        <div class="fs-slider" id="fsSlider">
          <div class="fill" id="fsFill"></div>
          <div class="knob" id="fsKnobMin"></div>
          <div class="knob" id="fsKnobMax"></div>
        </div>
      </div>

      <div class="fs-div"></div>

      <div class="fs-block" id="brandBlock">
        <p class="fs-title">{{ __('catalog.filters.brand') }}</p>
        <div class="fs-check" data-on="true"><span class="fside-box fs-box"></span><span class="lbl">{{ __('catalog.filters.brand_marca_corona') }}</span><span class="n">124</span></div>
        <div class="fs-check" data-on="false"><span class="fside-box fs-box"></span><span class="lbl">{{ __('catalog.filters.brand_kutahya') }}</span><span class="n">96</span></div>
        <div class="fs-check" data-on="true"><span class="fside-box fs-box"></span><span class="lbl">{{ __('catalog.filters.brand_vitra') }}</span><span class="n">88</span></div>
        <div class="fs-check" data-on="false"><span class="fside-box fs-box"></span><span class="lbl">{{ __('catalog.filters.brand_cersanit') }}</span><span class="n">64</span></div>
      </div>

      <div class="fs-div"></div>

      <div class="fs-block" id="surfBlock">
        <p class="fs-title">{{ __('catalog.filters.surface') }}</p>
        <div class="fs-check" data-on="true"><span class="fside-box fs-box"></span><span class="lbl">{{ __('catalog.filters.surface_matte') }}</span><span class="n">412</span></div>
        <div class="fs-check" data-on="false"><span class="fside-box fs-box"></span><span class="lbl">{{ __('catalog.filters.surface_glossy') }}</span><span class="n">296</span></div>
        <div class="fs-check" data-on="false"><span class="fside-box fs-box"></span><span class="lbl">{{ __('catalog.filters.surface_structured') }}</span><span class="n">152</span></div>
      </div>

      <div class="fs-div"></div>

      <div class="fs-block">
        <p class="fs-title">{{ __('catalog.filters.size') }}</p>
        <div class="fs-sizes">
          @foreach ($sizes as $size)
            <span class="fs-size" data-on="{{ $size === '60×60' ? 'true' : 'false' }}">{{ $size }}</span>
          @endforeach
        </div>
      </div>

      <div class="fs-div"></div>

      <div class="fs-stock">
        <span class="lbl">{{ __('catalog.filters.stock_only') }}</span>
        <div class="cat-switch" id="stockSwitch" data-on="true"><span class="knob"></span></div>
      </div>
     </div>{{-- /.fside-scroll --}}

      <div class="fside-apply-sep"></div>
      <button class="fside-apply">{{ __('catalog.filters.apply') }}</button>
    </aside>

    <div class="cat-grid" id="catGrid">
      @foreach ($cards as $i => $c)
        <x-pcard
            :img="$c['img']"
            :cat="__('catalog.products_cat')"
            :name="$copy[$i]['name']"
            :now="number_format($c['now'], 2, '.', '') . ' ₼'"
            :old="number_format($c['old'], 2, ',', '') . ' ₼'"
            :off="$c['off']"
            :rate="number_format($c['rate'], 1, '.', '')"
            :reviews="$copy[$i]['reviews']"
            data-i="{{ $i }}"
            data-now="{{ $c['now'] }}"
            data-rate="{{ $c['rate'] }}" />
      @endforeach
    </div>

  </div>

</main>

</x-layout>
