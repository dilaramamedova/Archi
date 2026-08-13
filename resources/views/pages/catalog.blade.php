{{--
  Catalog page (Figma 959:10198). Ported from the old catalog.html.
  The product grid was built in JS there; it is rendered server-side here so the
  card copy is translated — resources/js/pages/catalog.js only reorders the cards
  when the sort option changes (numeric data comes from the data-* attributes).
--}}
@php
    // Classifier drill-down context: the deepest selected node names the page.
    $classifierNode = $selectedClass ?? $selectedGroup ?? $selectedSection ?? $selectedCategory;
    $pageTitle = $classifierNode ? $classifierNode->name : t('catalog.head.title');
@endphp
<x-layout page="catalog" :title="$classifierNode ? $pageTitle . ' — ' . t('catalog.head.eyebrow') . ' — ARCHİ' : t('catalog.title')">
@php
    // Ghost labels that reserve the widest sort option, so picking one cannot resize
    // the control (the widths differ per locale, hence the rendered list).
    $sizes = ['30×30', '60×60', '60×120', '80×80', '20×20'];

    $sortOptions = [
        'pop' => t('catalog.sort.popular'),
        'cheap' => t('catalog.sort.cheap'),
        'exp' => t('catalog.sort.expensive'),
        'rating' => t('catalog.sort.rating'),
        'new' => t('catalog.sort.newest'),
    ];

    // Active filter values from the request (used to preserve state after reload)
    $activeSort = request('sort', 'pop');
    $activeBrands = array_filter(explode(',', request('brand', '')));
    $activeSurfaces = array_filter(explode(',', request('surface', '')));
    $activeSizes = array_filter(explode(',', request('size', '')));
    $activeMinPrice = request('min_price', '');
    $activeMaxPrice = request('max_price', '');
    $activeInStock = request()->boolean('in_stock');
    $hasPriceFilter = request()->has('min_price') || request()->has('max_price');

    // Breadcrumb chain: Home / Kataloq / Section / Group / Class — each level
    // links back to its own catalog page except the current (deepest) one.
    $crumbs = [['label' => t('common.home'), 'href' => route('home')]];
    $crumbs[] = $classifierNode ? ['label' => t('common.catalog'), 'href' => route('catalog')] : ['label' => t('common.catalog')];
    if ($selectedSection) {
        $crumbs[] = ($selectedGroup || $selectedClass)
            ? ['label' => $selectedSection->name, 'href' => route('catalog', ['section' => $selectedSection->slug])]
            : ['label' => $selectedSection->name];
    }
    if ($selectedGroup) {
        $crumbs[] = $selectedClass
            ? ['label' => $selectedGroup->name, 'href' => route('catalog', ['group' => $selectedGroup->slug])]
            : ['label' => $selectedGroup->name];
    }
    if ($selectedClass) {
        $crumbs[] = ['label' => $selectedClass->name];
    }
    if ($selectedCategory) {
        $crumbs[] = ['label' => $selectedCategory->name];
    }

    // Attribute filter helpers (class pages): which filter panels hold an
    // active filter — those expanders must render open after a reload.
    $attrGroupActive = function ($attributes) use ($activeAttrOptions, $activeAttrRanges) {
        return collect($attributes)->contains(fn ($a) => isset($activeAttrOptions[$a->slug]) || isset($activeAttrRanges[$a->slug]));
    };
@endphp

<main class="wrap catalog">

  <x-ui.breadcrumbs class="cat-crumbs" :items="$crumbs" />

  <div class="cat-head">
    <div class="cat-head-l">
      <x-ui.eyebrow variant="flat" class="mb-3.5" :label="$classifierNode ? $pageTitle : t('catalog.head.eyebrow')" />
      <div class="title-row">
        <h1>{{ $pageTitle }}</h1>
        <span class="count">{{ $products->total() }}</span>
      </div>
    </div>
    {{-- Sort + the phone-only filter trigger. Below 980px the sidebar is a bottom
         sheet, so it needs an opener that sits next to the sort control. --}}
    <div class="cat-tools">
      <button type="button" class="fsheet-btn" id="catFilterBtn" aria-expanded="false" aria-controls="catFside">
        <svg class="ic" viewBox="0 0 20 20" fill="none" aria-hidden="true">
          <path d="M3 6h14M3 10.5h9M3 15h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
        </svg>
        <span class="t">{{ t('catalog.filters.mobile_title') }}</span>
        <span class="n" id="catFilterCount" hidden></span>
      </button>
      <div class="fsort cat-sort" id="catSort" data-open="false">
        <span class="lbl">{{ t('catalog.sort.label') }}</span>
        <span class="val-wrap">
          <span class="val" id="sortVal">{{ $sortOptions[$activeSort] ?? t('catalog.sort.popular') }}</span>
          <span class="val-ghost" aria-hidden="true">@foreach ($sortOptions as $label)<i>{{ $label }}</i>@endforeach</span>
        </span>
        <span class="car">⌄</span>
        <ul class="fsort-menu sort-menu" id="sortMenu">
          @foreach ($sortOptions as $key => $label)
            <li data-sort="{{ $key }}" data-on="{{ $key === $activeSort ? 'true' : 'false' }}">{{ $label }}</li>
          @endforeach
        </ul>
      </div>
    </div>
  </div>

  {{-- active filter chips — built by catalog.js from the sidebar state --}}
  <div class="cat-chips" id="catChips"
       data-l-size="{{ t('catalog.chips.size') }}"
       data-l-surface="{{ t('catalog.chips.surface') }}"
       data-l-price="{{ t('catalog.chips.price') }}"
       data-l-remove="{{ t('catalog.chips.remove') }}">
    <button class="cat-clear" id="catClear">{{ t('catalog.chips.clear') }}</button>
  </div>

  <div class="cat-body">

    <aside class="fside fsheet" id="catFside" data-open="false">
     {{-- sheet handle + title: phone only (.fsheet-head is display:none above 980px) --}}
     <div class="fsheet-head">
       <span class="grip" aria-hidden="true"></span>
       <p class="ttl">{{ t('catalog.filters.mobile_title') }}</p>
       <button type="button" class="cls" id="catFilterClose" aria-label="{{ t('common.close') }}">✕</button>
     </div>
     <div class="fside-scroll">

      {{-- Classifier rail: 5 customer clusters -> 12 sections; the selected
           section expands to its groups, the selected group to its product
           classes. Rows are plain links — the drill-down needs no JS. --}}
      <div class="fs-block">
        <p class="fs-title">{{ t('catalog.filters.categories') }}</p>
        @foreach ($clusters as $cluster)
          <p class="fs-cluster">{{ $cluster['label'] }}</p>
          @foreach ($cluster['sections'] as $section)
            <a class="fs-cat" href="{{ route('catalog', ['section' => $section->slug]) }}"
               data-on="{{ $selectedSection?->id === $section->id && ! $selectedGroup ? 'true' : 'false' }}">
              <span>{{ $section->name }}</span><span class="n">{{ $sectionCounts[$section->id] ?? 0 }}</span>
            </a>
            @if ($selectedSection?->id === $section->id)
              <div class="fs-tree">
                @foreach ($section->children as $group)
                  <a class="fs-cat fs-sub" href="{{ route('catalog', ['group' => $group->slug]) }}"
                     data-on="{{ $selectedGroup?->id === $group->id && ! $selectedClass ? 'true' : 'false' }}">
                    <span>{{ $group->name }}</span><span class="n">{{ $groupCounts[$group->id] ?? 0 }}</span>
                  </a>
                  @if ($selectedGroup?->id === $group->id && $groupClasses->isNotEmpty())
                    <div class="fs-tree fs-tree-classes">
                      @foreach ($groupClasses as $class)
                        <a class="fs-cat fs-sub2" href="{{ route('catalog', ['class' => $class->slug]) }}"
                           data-on="{{ $selectedClass?->id === $class->id ? 'true' : 'false' }}">
                          <span>{{ $class->name }}</span><span class="n">{{ $classCounts[$class->id] ?? 0 }}</span>
                        </a>
                      @endforeach
                    </div>
                  @endif
                @endforeach
              </div>
            @endif
          @endforeach
        @endforeach
      </div>

      <div class="fs-div"></div>

      <div class="fs-block">
        <p class="fs-title">{{ t('catalog.filters.price') }}</p>
        <div class="fs-price-inputs">
          <input class="in" id="fsMin" type="text" inputmode="numeric" value="{{ $activeMinPrice !== '' ? $activeMinPrice : $priceRange['min'] }}" aria-label="{{ t('catalog.filters.price_min_aria') }}">
          <span class="dash">—</span>
          <input class="in" id="fsMax" type="text" inputmode="numeric" value="{{ $activeMaxPrice !== '' ? $activeMaxPrice : $priceRange['max'] }}" aria-label="{{ t('catalog.filters.price_max_aria') }}">
        </div>
        <div class="fs-slider" id="fsSlider"
             data-price-min="{{ $priceRange['min'] }}"
             data-price-max="{{ $priceRange['max'] }}"
             data-price-filtered="{{ $hasPriceFilter ? 'true' : 'false' }}">
          <div class="fill" id="fsFill"></div>
          <div class="knob" id="fsKnobMin"></div>
          <div class="knob" id="fsKnobMax"></div>
        </div>
      </div>

      <div class="fs-div"></div>

      <div class="fs-block" id="brandBlock">
        <p class="fs-title">{{ t('catalog.filters.brand') }}</p>
        @forelse ($filterBrands as $brandItem)
          <div class="fs-check" data-brand="{{ $brandItem->slug }}" data-on="{{ in_array($brandItem->slug, $activeBrands) ? 'true' : 'false' }}"><span class="fside-box fs-box"></span><span class="lbl">{{ $brandItem->name }}</span><span class="n">{{ $brandItem->products_count }}</span></div>
        @empty
          <div class="fs-check" data-brand="no-brands" data-on="false"><span class="fside-box fs-box"></span><span class="lbl">—</span><span class="n">0</span></div>
        @endforelse
      </div>

      <div class="fs-div"></div>

      @if ($selectedClass && ($filterGroups['top']->isNotEmpty() || $filterGroups['secondary']->isNotEmpty() || $filterGroups['advanced']->isNotEmpty()))
        {{-- Dynamic filters from the class's filterable attributes. Top priority
             renders immediately; "secondary" sits in a collapsed group; "advanced"
             hides behind the "Bütün filtrlər" expander. --}}
        <div id="attrFilters">
          @foreach ($filterGroups['top'] as $attribute)
            @include('pages.partials.catalog-attr-filter', ['attribute' => $attribute])
            @if (! $loop->last)<div class="fs-div"></div>@endif
          @endforeach

          @if ($filterGroups['secondary']->isNotEmpty())
            @if ($filterGroups['top']->isNotEmpty())<div class="fs-div"></div>@endif
            <div class="fs-expander" data-open="{{ $attrGroupActive($filterGroups['secondary']) ? 'true' : 'false' }}">
              <button type="button" class="fs-exp-head">{{ t('catalog-classifier.filters.more') }} <span class="car">⌄</span></button>
              <div class="fs-exp-body">
                @foreach ($filterGroups['secondary'] as $attribute)
                  @include('pages.partials.catalog-attr-filter', ['attribute' => $attribute])
                  @if (! $loop->last)<div class="fs-div"></div>@endif
                @endforeach
              </div>
            </div>
          @endif

          @if ($filterGroups['advanced']->isNotEmpty())
            <div class="fs-div"></div>
            <div class="fs-expander" data-open="{{ $attrGroupActive($filterGroups['advanced']) ? 'true' : 'false' }}">
              <button type="button" class="fs-exp-head">{{ t('catalog-classifier.filters.all') }} <span class="car">⌄</span></button>
              <div class="fs-exp-body">
                @foreach ($filterGroups['advanced'] as $attribute)
                  @include('pages.partials.catalog-attr-filter', ['attribute' => $attribute])
                  @if (! $loop->last)<div class="fs-div"></div>@endif
                @endforeach
              </div>
            </div>
          @endif
        </div>

        <div class="fs-div"></div>
      @endif

      @unless ($selectedClass)
      {{-- Legacy demo filters (they grep the flat specifications JSON) — a class
           page gets real attribute filters above instead. --}}
      <div class="fs-block" id="surfBlock">
        <p class="fs-title">{{ t('catalog.filters.surface') }}</p>
        <div class="fs-check" data-surface="matte" data-on="{{ in_array('matte', $activeSurfaces) ? 'true' : 'false' }}"><span class="fside-box fs-box"></span><span class="lbl">{{ t('catalog.filters.surface_matte') }}</span></div>
        <div class="fs-check" data-surface="glossy" data-on="{{ in_array('glossy', $activeSurfaces) ? 'true' : 'false' }}"><span class="fside-box fs-box"></span><span class="lbl">{{ t('catalog.filters.surface_glossy') }}</span></div>
        <div class="fs-check" data-surface="structured" data-on="{{ in_array('structured', $activeSurfaces) ? 'true' : 'false' }}"><span class="fside-box fs-box"></span><span class="lbl">{{ t('catalog.filters.surface_structured') }}</span></div>
      </div>

      <div class="fs-div"></div>

      <div class="fs-block">
        <p class="fs-title">{{ t('catalog.filters.size') }}</p>
        <div class="fs-sizes">
          @foreach ($sizes as $size)
            {{-- data-size doubles as the bold ghost label (catalog.css) so selecting a
                 chip cannot change its width and rewrap the row. --}}
            <span class="fs-size" data-size="{{ $size }}" data-on="{{ in_array($size, $activeSizes) ? 'true' : 'false' }}"><span class="t">{{ $size }}</span></span>
          @endforeach
        </div>
      </div>

      <div class="fs-div"></div>
      @endunless

      <div class="fs-stock">
        <span class="lbl">{{ t('catalog.filters.stock_only') }}</span>
        <div class="cat-switch" id="stockSwitch" data-on="{{ $activeInStock ? 'true' : 'false' }}"><span class="knob"></span></div>
      </div>
     </div>{{-- /.fside-scroll --}}

      <div class="fside-apply-sep"></div>
      <button type="button" class="fside-apply" id="catApply">{{ t('catalog.filters.apply') }}</button>
    </aside>
    <div class="fsheet-scrim" id="catFilterScrim" hidden></div>

    <div class="cat-grid" id="catGrid">
      @forelse ($products as $product)
        <x-pcard
            :href="route('product.show', $product->slug)"
            :product="$product"
            :img="$product->mainImageUrl"
            :cat="$product->category?->name"
            :name="$product->name"
            :now="number_format($product->price, 2, '.', '') . ' ₼'"
            :old="$product->old_price && $product->old_price > $product->price ? number_format($product->old_price, 2, '.', '') . ' ₼' : null"
            :off="$product->old_price && $product->old_price > $product->price && $product->discount_percent ? '-' . $product->discount_percent . '%' : null"
            :rate="$product->averageRating ? number_format($product->averageRating, 1, '.', '') : null"
            :reviews="$product->reviewsCount ? $product->reviewsCount : null"
            data-now="{{ $product->price }}"
            data-rate="{{ $product->averageRating }}"
            data-cat="{{ $product->category?->slug }}" />
      @empty
        <p class="cat-empty">{{ t('catalog.empty') }}</p>
      @endforelse
    </div>

    @if ($products->hasPages())
      <div class="cat-pagination">
        {{ $products->links() }}
      </div>
    @endif

  </div>

</main>

{{-- ===================== FEATURED SPECIALISTS ===================== --}}
<div class="wrap"><div class="inner section">
  <x-section-head :tag="t('product.specialists.tag')" :title="t('product.specialists.title')"
                  :more="route('specialists', ['featured' => 1])" />
  <div class="grid4" id="specGrid">
    @if($featuredSpecialists->isNotEmpty())
      @foreach ($featuredSpecialists as $s)
        <x-scard :href="route('specialist.show', $s)"
                 :bg="['#f5fbff', '#fdf5ff', '#f5fffb', '#fff5f5'][$loop->index % 4]"
                 :avatar="$s->user?->avatar ?? '/assets/icon-user.svg'"
                 :role="$s->craft_label"
                 :rate="null"
                 :reviews="t('product.specialists.reviews_416')"
                 :name="$s->user?->name ?? t('product.specialists.name_1')"
                 :exp="$s->experience_years ? $s->experience_years . ' ' . t('home.specialists.years') : t('product.specialists.exp_12')"
                 :proj="$s->approvedPortfolioItems()->count() . ' ' . t('home.specialists.projects')" />
      @endforeach
    @else
      @php
          $fallbackSpecialists = [
              ['bg' => '#f5fbff', 'role' => t('product.specialists.role_tiler'), 'name' => t('product.specialists.name_1')],
              ['bg' => '#fdf5ff', 'role' => t('product.specialists.role_tiler'), 'name' => t('product.specialists.name_1')],
              ['bg' => '#f5fffb', 'role' => t('product.specialists.role_interior'), 'name' => t('product.specialists.name_2')],
              ['bg' => '#fff5f5', 'role' => t('product.specialists.role_tiler'), 'name' => t('product.specialists.name_1')],
          ];
      @endphp
      @foreach ($fallbackSpecialists as $s)
        <x-scard :bg="$s['bg']" :role="$s['role']" :reviews="t('product.specialists.reviews_416')"
                 :name="$s['name']" :exp="t('product.specialists.exp_12')" :proj="'0 ' . t('home.specialists.projects')" />
      @endforeach
    @endif
  </div>
</div></div>

{{-- ===================== CUSTOMER REVIEWS ===================== --}}
@if (false)
{{-- Müştəri qiymətləndirməsi bölməsi hazırda göstərilmir. --}}
<div class="wrap"><div class="inner section">
  <x-section-head :tag="t('product.reviews.tag')" :title="t('product.reviews.title')" />
  <div class="rev-cards" id="catalogRevCards">
    @php
        $avatarColors = [
            ['bg' => '#eef1f4', 'fg' => '#5a6472'],
            ['bg' => '#e8f5ec', 'fg' => '#2f7d44'],
            ['bg' => '#fdf3e3', 'fg' => '#b07626'],
            ['bg' => '#e9f0fb', 'fg' => '#3c62a8'],
        ];
    @endphp
    @forelse ($latestReviews as $i => $review)
      @php $color = $avatarColors[$i % count($avatarColors)]; @endphp
      <div class="rev-card">
        <div class="h">
          <div class="av" style="background:{{ $color['bg'] }};color:{{ $color['fg'] }}">{{ mb_strtoupper(mb_substr($review->user->name ?? 'U', 0, 1)) }}</div>
          <div>
            <div class="nm">{{ $review->user->name ?? t('product.reviews.anonymous') }}</div>
            @if ($review->is_verified_purchase)
              <div class="vf"><img src="/assets/icon-check-green.svg" alt="">{{ t('product.reviews.verified') }}</div>
            @endif
          </div>
        </div>
        <div class="sd"><span class="s"><x-ui.stars :rating="$review->rating" /></span><span class="date">{{ $review->created_at->format('d.m.Y') }}</span></div>
        <div class="txt">{{ $review->comment }}</div>
      </div>
    @empty
      <p class="text-center text-gray-500 py-8">{{ t('product.reviews.empty') }}</p>
    @endforelse
  </div>
</div></div>
@endif

</x-layout>
