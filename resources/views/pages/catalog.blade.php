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
    // brand / surface / size / stock feed the sidebar filters (catalog.js).
    $cards = [
        ['img' => $tilePaint,  'now' => 23.90, 'old' => 45.99, 'off' => '-48%', 'rate' => 4.4, 'brand' => 'marca_corona', 'surface' => 'matte',      'size' => '60×60',  'stock' => true],
        ['img' => $tileWool,   'now' => 49.90, 'old' => 72.00, 'off' => '-31%', 'rate' => 4.8, 'brand' => 'vitra',        'surface' => 'glossy',     'size' => '60×120', 'stock' => true],
        ['img' => $tileMarble, 'now' => 31.50, 'old' => 40.00, 'off' => '-21%', 'rate' => 4.6, 'brand' => 'vitra',        'surface' => 'matte',      'size' => '60×60',  'stock' => true],
        ['img' => $tileMarble, 'now' => 18.90, 'old' => 26.00, 'off' => '-27%', 'rate' => 4.3, 'brand' => 'kutahya',      'surface' => 'matte',      'size' => '60×60',  'stock' => true],
        ['img' => $tilePaint,  'now' => 12.90, 'old' => 19.00, 'off' => '-32%', 'rate' => 4.7, 'brand' => 'cersanit',     'surface' => 'structured', 'size' => '30×30',  'stock' => true],
        ['img' => $tileWool,   'now' => 38.00, 'old' => 55.00, 'off' => '-31%', 'rate' => 4.9, 'brand' => 'kutahya',      'surface' => 'structured', 'size' => '60×60',  'stock' => true],
        ['img' => $tileWool,   'now' => 27.40, 'old' => 39.00, 'off' => '-30%', 'rate' => 4.5, 'brand' => 'marca_corona', 'surface' => 'matte',      'size' => '60×60',  'stock' => true],
        ['img' => $tileMarble, 'now' => 22.00, 'old' => 30.00, 'off' => '-27%', 'rate' => 4.2, 'brand' => 'vitra',        'surface' => 'matte',      'size' => '60×60',  'stock' => true],
        ['img' => $tilePaint,  'now' => 15.50, 'old' => 21.00, 'off' => '-26%', 'rate' => 4.6, 'brand' => 'cersanit',     'surface' => 'structured', 'size' => '15×15',  'stock' => false],
        ['img' => $tileWool,   'now' => 34.80, 'old' => 48.00, 'off' => '-27%', 'rate' => 4.7, 'brand' => 'kutahya',      'surface' => 'matte',      'size' => '20×120', 'stock' => true],
        ['img' => $tileMarble, 'now' => 56.00, 'old' => 79.00, 'off' => '-29%', 'rate' => 4.8, 'brand' => 'marca_corona', 'surface' => 'glossy',     'size' => '80×80',  'stock' => true],
        ['img' => $tilePaint,  'now' => 14.20, 'old' => 18.50, 'off' => '-23%', 'rate' => 4.1, 'brand' => 'cersanit',     'surface' => 'glossy',     'size' => '25×40',  'stock' => true],
        ['img' => $tileMarble, 'now' => 88.00, 'old' => 120.00, 'off' => '-27%', 'rate' => 4.9, 'brand' => 'marca_corona', 'surface' => 'glossy',    'size' => '60×120', 'stock' => false],
        ['img' => $tileWool,   'now' => 19.90, 'old' => 27.00, 'off' => '-26%', 'rate' => 4.4, 'brand' => 'kutahya',      'surface' => 'matte',      'size' => '20×20',  'stock' => true],
        ['img' => $tilePaint,  'now' => 26.70, 'old' => 35.00, 'off' => '-24%', 'rate' => 4.5, 'brand' => 'cersanit',     'surface' => 'matte',      'size' => '23×27',  'stock' => true],
        ['img' => $tileMarble, 'now' => 29.90, 'old' => 42.00, 'off' => '-29%', 'rate' => 4.6, 'brand' => 'vitra',        'surface' => 'matte',      'size' => '60×60',  'stock' => true],
        ['img' => $tileWool,   'now' => 41.50, 'old' => 58.00, 'off' => '-28%', 'rate' => 4.3, 'brand' => 'kutahya',      'surface' => 'structured', 'size' => '6×24',   'stock' => true],
        ['img' => $tilePaint,  'now' => 33.20, 'old' => 45.00, 'off' => '-26%', 'rate' => 4.7, 'brand' => 'vitra',        'surface' => 'glossy',     'size' => '12×24',  'stock' => true],
        ['img' => $tileMarble, 'now' => 21.40, 'old' => 29.00, 'off' => '-26%', 'rate' => 4.2, 'brand' => 'marca_corona', 'surface' => 'structured', 'size' => '30×60',  'stock' => true],
        ['img' => $tileWool,   'now' => 47.60, 'old' => 65.00, 'off' => '-27%', 'rate' => 4.8, 'brand' => 'cersanit',     'surface' => 'glossy',     'size' => '10×10',  'stock' => false],
    ];

    $copy = __('catalog.products');
    $sizes = ['30×30', '60×60', '60×120', '80×80', '20×20'];

    // Every demo product belongs to the tiles category (see 'products_cat').
    $cardCategory = 'tiles';

    // Ghost labels that reserve the widest sort option, so picking one cannot resize
    // the control (the widths differ per locale, hence the rendered list).
    $sortOptions = [
        'pop' => __('catalog.sort.popular'),
        'cheap' => __('catalog.sort.cheap'),
        'exp' => __('catalog.sort.expensive'),
        'rating' => __('catalog.sort.rating'),
        'new' => __('catalog.sort.newest'),
    ];
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
      <span class="val-wrap">
        <span class="val" id="sortVal">{{ __('catalog.sort.popular') }}</span>
        <span class="val-ghost" aria-hidden="true">@foreach ($sortOptions as $label)<i>{{ $label }}</i>@endforeach</span>
      </span>
      <span class="car">⌄</span>
      <ul class="fsort-menu sort-menu" id="sortMenu">
        @foreach ($sortOptions as $key => $label)
          <li data-sort="{{ $key }}" data-on="{{ $key === 'pop' ? 'true' : 'false' }}">{{ $label }}</li>
        @endforeach
      </ul>
    </div>
  </div>

  {{-- active filter chips — built by catalog.js from the sidebar state --}}
  <div class="cat-chips" id="catChips"
       data-l-size="{{ __('catalog.chips.size') }}"
       data-l-surface="{{ __('catalog.chips.surface') }}"
       data-l-price="{{ __('catalog.chips.price') }}"
       data-l-remove="{{ __('catalog.chips.remove') }}">
    <button class="cat-clear" id="catClear">{{ __('catalog.chips.clear') }}</button>
  </div>

  <div class="cat-body">

    <aside class="fside">
     <div class="fside-scroll">

      <div class="fs-block">
        <p class="fs-title">{{ __('catalog.filters.categories') }}</p>
        <div class="fs-cat" data-cat="tiles" data-on="true"><span>{{ __('catalog.filters.cat_tiles') }}</span><span class="n">860</span></div>
        <div class="fs-cat" data-cat="paint" data-on="false"><span>{{ __('catalog.filters.cat_paint') }}</span><span class="n">412</span></div>
        <div class="fs-cat" data-cat="laminate" data-on="false"><span>{{ __('catalog.filters.cat_laminate') }}</span><span class="n">340</span></div>
        <div class="fs-cat" data-cat="plumbing" data-on="false"><span>{{ __('catalog.filters.cat_plumbing') }}</span><span class="n">296</span></div>
        <div class="fs-cat" data-cat="electric" data-on="false"><span>{{ __('catalog.filters.cat_electric') }}</span><span class="n">188</span></div>
        <div class="fs-cat" data-cat="insulation" data-on="false"><span>{{ __('catalog.filters.cat_insulation') }}</span><span class="n">154</span></div>
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
        <div class="fs-check" data-brand="marca_corona" data-on="true"><span class="fside-box fs-box"></span><span class="lbl">{{ __('catalog.filters.brand_marca_corona') }}</span><span class="n">124</span></div>
        <div class="fs-check" data-brand="kutahya" data-on="false"><span class="fside-box fs-box"></span><span class="lbl">{{ __('catalog.filters.brand_kutahya') }}</span><span class="n">96</span></div>
        <div class="fs-check" data-brand="vitra" data-on="true"><span class="fside-box fs-box"></span><span class="lbl">{{ __('catalog.filters.brand_vitra') }}</span><span class="n">88</span></div>
        <div class="fs-check" data-brand="cersanit" data-on="false"><span class="fside-box fs-box"></span><span class="lbl">{{ __('catalog.filters.brand_cersanit') }}</span><span class="n">64</span></div>
      </div>

      <div class="fs-div"></div>

      <div class="fs-block" id="surfBlock">
        <p class="fs-title">{{ __('catalog.filters.surface') }}</p>
        <div class="fs-check" data-surface="matte" data-on="true"><span class="fside-box fs-box"></span><span class="lbl">{{ __('catalog.filters.surface_matte') }}</span><span class="n">412</span></div>
        <div class="fs-check" data-surface="glossy" data-on="false"><span class="fside-box fs-box"></span><span class="lbl">{{ __('catalog.filters.surface_glossy') }}</span><span class="n">296</span></div>
        <div class="fs-check" data-surface="structured" data-on="false"><span class="fside-box fs-box"></span><span class="lbl">{{ __('catalog.filters.surface_structured') }}</span><span class="n">152</span></div>
      </div>

      <div class="fs-div"></div>

      <div class="fs-block">
        <p class="fs-title">{{ __('catalog.filters.size') }}</p>
        <div class="fs-sizes">
          @foreach ($sizes as $size)
            {{-- data-size doubles as the bold ghost label (catalog.css) so selecting a
                 chip cannot change its width and rewrap the row. --}}
            <span class="fs-size" data-size="{{ $size }}" data-on="{{ $size === '60×60' ? 'true' : 'false' }}"><span class="t">{{ $size }}</span></span>
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
      <button type="button" class="fside-apply" id="catApply">{{ __('catalog.filters.apply') }}</button>
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
            data-rate="{{ $c['rate'] }}"
            data-cat="{{ $cardCategory }}"
            data-brand="{{ $c['brand'] }}"
            data-surface="{{ $c['surface'] }}"
            data-size="{{ $c['size'] }}"
            data-stock="{{ $c['stock'] ? 'true' : 'false' }}" />
      @endforeach
      <p class="cat-empty" id="catEmpty" hidden>{{ __('catalog.empty') }}</p>
    </div>

  </div>

</main>

</x-layout>
