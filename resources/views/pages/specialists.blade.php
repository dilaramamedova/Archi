{{--
  Specialists catalogue (Figma 1105:26941). Dynamic version — data comes from the
  specialist_profiles table via SpecialistController. The JS sort/filter still works
  via data-* attributes on each card.
--}}
@php
    // Card tints repeat every 4 cards, exactly like the old JS array.
    $tints = ['#f5fbff', '#fdf5ff', '#f5fffb', '#fff5f5'];

    // Active filter values from the request (used to preserve state after reload)
    $activeSort = request('sort', 'rating');
    $activeSpec = request('spec', '');
    $activeCity = request('city', '');
    $activeMinYears = request('min_years', '');
    $activeMaxYears = request('max_years', '');
    $activeVerified = request()->boolean('verified');
    $activeFree = request()->boolean('free');
@endphp
<x-layout page="specialists" :title="t('specialists.title')">

<main class="wrap spec-cat">

  {{-- breadcrumbs --}}
  <x-ui.breadcrumbs class="sp-crumbs" :items="[
      ['label' => t('specialists.crumb_home'), 'href' => route('home')],
      ['label' => t('specialists.crumb_current')],
  ]" />

  {{-- heading --}}
  <div class="sp-head">
    <div class="sp-head-l">
      <x-ui.eyebrow variant="flat" class="sp-eyebrow" :label="t('specialists.head.eyebrow')" />
      <div class="sp-title">
        <h1>{{ t('specialists.head.title') }}</h1>
        <span class="count">{{ $specialists->total() }}</span>
      </div>
    </div>
    {{-- Sort + the phone-only filter trigger — below 980px .sp-side is a bottom sheet. --}}
    <div class="cat-tools sp-tools">
      <button type="button" class="fsheet-btn" id="spFilterBtn" aria-expanded="false" aria-controls="spFside">
        <svg class="ic" viewBox="0 0 20 20" fill="none" aria-hidden="true">
          <path d="M3 6h14M3 10.5h9M3 15h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
        </svg>
        <span class="t">{{ t('specialists.filters.mobile_title') }}</span>
        <span class="n" hidden></span>
      </button>
      <div class="fsort sp-sort" id="spSort" data-open="false">
        <span class="lbl">{{ t('specialists.sort.label') }}</span>
        @php
          $spSortOptions = [
              'rating'   => t('specialists.sort.rating'),
              'reviews'  => t('specialists.sort.reviews'),
              'exp'      => t('specialists.sort.exp'),
              'cheap'    => t('specialists.sort.cheap'),
              'projects' => t('specialists.sort.projects'),
          ];
        @endphp
        <span class="val" id="spSortVal">{{ $spSortOptions[$activeSort] ?? t('specialists.sort.rating') }}</span>
        <img class="car" src="/assets/icon-chevron-down-small.svg" alt="">
        <ul class="fsort-menu sp-sort-menu" id="spSortMenu">
          @foreach ($spSortOptions as $key => $label)
            <li data-sort="{{ $key }}" data-on="{{ $key === $activeSort ? 'true' : 'false' }}">{{ $label }}</li>
          @endforeach
        </ul>
      </div>
    </div>
  </div>

  {{-- active filter chips (built by specialists.js from the sidebar state) --}}
  <div class="sp-chips" id="spChips">
    <button class="sp-clear" id="spClear">{{ t('specialists.filters.clear') }}</button>
  </div>

  {{-- body --}}
  <div class="sp-body">

    {{-- filter sidebar --}}
    <aside class="fside sp-side fsheet" id="spFside" data-open="false">
     {{-- sheet handle + title: phone only (.fsheet-head is display:none above 980px) --}}
     <div class="fsheet-head">
       <span class="grip" aria-hidden="true"></span>
       <p class="ttl">{{ t('specialists.filters.mobile_title') }}</p>
       <button type="button" class="cls" id="spFilterClose" aria-label="{{ t('common.close') }}">✕</button>
     </div>
     <div class="fside-scroll sp-fs-scroll">

      {{-- specialization --}}
      <div class="sp-fs" id="spSpecBlock">
        <p class="sp-fs-title">{{ t('specialists.filters.spec_title') }}</p>
        @foreach ($specialties as $specialty)
          <div class="sp-cat" data-spec="{{ $specialty->id }}" data-on="{{ (string) $activeSpec === (string) $specialty->id ? 'true' : 'false' }}"><span class="t">{{ $specialty->name }}</span></div>
        @endforeach
        @if ($specialties->isEmpty())
          <div class="sp-cat" data-spec="tile" data-on="false"><span class="t">{{ t('specialists.filters.spec.tile') }}</span></div>
        @endif
      </div>

      <div class="sp-fs-div"></div>

      {{-- city --}}
      <div class="sp-fs" id="spCityBlock">
        <p class="sp-fs-title">{{ t('specialists.filters.city_title') }}</p>
        {{-- Canonical list from App\Enums\City — the label is both the stored value and
             the filter value, so no legacy slug can appear as an extra row. --}}
        @foreach ($cities as $city)
          <div class="sp-check" data-city="{{ $city }}" data-on="{{ \App\Enums\City::canonical($activeCity) === $city ? 'true' : 'false' }}"><span class="fside-box sp-box"></span><span class="lbl">{{ $city }}</span></div>
        @endforeach
      </div>

      <div class="sp-fs-div"></div>

      {{-- experience --}}
      <div class="sp-fs" id="spYearBlock">
        <p class="sp-fs-title">{{ t('specialists.filters.exp_title') }}</p>
        <div class="sp-years">
          <span class="sp-year" data-min="1" data-max="3" data-on="{{ $activeMinYears === '1' && $activeMaxYears === '3' ? 'true' : 'false' }}">{{ t('specialists.filters.years_1_3') }}</span>
          <span class="sp-year" data-min="3" data-max="5" data-on="{{ $activeMinYears === '3' && $activeMaxYears === '5' ? 'true' : 'false' }}">{{ t('specialists.filters.years_3_5') }}</span>
          <span class="sp-year" data-min="5" data-max="10" data-on="{{ $activeMinYears === '5' && $activeMaxYears === '10' ? 'true' : 'false' }}">{{ t('specialists.filters.years_5_10') }}</span>
          <span class="sp-year" data-min="10" data-max="99" data-on="{{ $activeMinYears === '10' && $activeMaxYears === '99' ? 'true' : 'false' }}">{{ t('specialists.filters.years_10') }}</span>
        </div>
      </div>

      <div class="sp-fs-div"></div>

      {{-- switches --}}
      <div class="sp-toggle">
        <span class="lbl">{{ t('specialists.filters.verified_only') }}</span>
        <x-ui.toggle id="spVerified" :on="$activeVerified" size="sm" :hover="false"
                     :aria-label="t('specialists.filters.verified_only')" />
      </div>
      <div class="sp-toggle">
        <span class="lbl">{{ t('specialists.filters.free_this_week') }}</span>
        <x-ui.toggle id="spFree" :on="$activeFree" size="sm" :hover="false"
                     :aria-label="t('specialists.filters.free_this_week')" />
      </div>

     </div>{{-- /.fside-scroll --}}

      <div class="fside-apply-sep"></div>
      <button type="button" class="fside-apply" id="spApply">{{ t('specialists.filters.apply') }}</button>
    </aside>
    <div class="fsheet-scrim" id="spFilterScrim" hidden></div>

    {{-- Results column: grid + pagination. `.sp-body` is a flex ROW (sidebar | results),
         so both have to live in one child of it — the same shape /catalog uses, where a
         pagination block left as a direct sibling rendered as a third column beside the
         cards. --}}
    <div class="sp-main">
    {{-- grid --}}
    <div class="sp-grid" id="spGrid">
      @forelse ($specialists as $i => $s)
        @php
            // Only the admin-set feature flag earns a badge. "Təsdiqlənmiş" is off until
            // a real verification column exists — the index already filters to active
            // users, so isActive() marked every single card.
            $badges = [];
            if ($s->is_featured) { $badges[] = ['class' => 'top-master', 'label' => t('specialists.card.badge_top')]; }
            $initials = mb_strtoupper(mb_substr($s->user->first_name ?? $s->user->name, 0, 1) . mb_substr($s->user->last_name ?? '', 0, 1));
            $skillsList = is_array($s->skills) ? implode(',', $s->skills) : '';
            // Both read denormalized columns (ReviewObserver keeps them current);
            // the old fallback ran a COUNT query per card.
            $avgRating = $s->average_rating;
            $revCount = $s->reviews_count;
        @endphp
        <a class="sp-card" href="{{ route('specialist.show', $s) }}"
           data-rate="{{ $avgRating }}"
           data-reviews="{{ $revCount }}"
           data-years="{{ $s->experience_years ?? 0 }}"
           data-projects="{{ $s->approvedPortfolioItems ? $s->approvedPortfolioItems->count() : 0 }}"
           data-price="0"
           data-city="{{ \App\Enums\City::display($s->city) ?? '' }}"
           data-specs="{{ $skillsList }}"
           data-verified="{{ $s->is_featured ? 'true' : 'false' }}"
           data-free="{{ $s->is_on_vacation ? 'false' : 'true' }}">
          <div class="top" style="background:{{ $tints[$i % 4] }}">
            <div class="badges">@foreach ($badges as $b)<span class="b {{ $b['class'] }}">{{ $b['label'] }}</span>@endforeach</div>
            @if ($s->avatar_path)
              <img class="avatar" src="{{ storage_url($s->avatar_path) }}" alt="{{ $s->user->name }}">
            @else
              <div class="avatar">{{ $initials }}</div>
            @endif
            <div class="role">{{ $s->craft_label ?: t('specialists.card.default_role') }}</div>
            <div class="rate"><img class="st" src="/assets/icon-star-yellow.svg" alt=""><span class="v">{{ $avgRating > 0 ? number_format($avgRating, 1) : '0.0' }}</span><span class="r">{{ t('specialists.card.reviews', ['count' => $revCount]) }}</span></div>
          </div>
          <div class="name">{{ $s->user->name }}</div>
          <div class="meta"><span class="m">{{ t('specialists.card.exp', ['years' => $s->experience_years ?? 0]) }}</span><span class="d">·</span><span class="m">{{ \App\Enums\City::display($s->city) ?? '' }}</span></div>
          <div class="foot">
            <span class="price"></span>
            <span class="ui-btn ui-btn-primary h-[38px] px-4 text-[13px] font-semibold whitespace-nowrap duration-200"
                  data-hover="true">{{ t('specialists.card.view_profile') }}</span>
          </div>
        </a>
      @empty
        <p class="sp-empty">{{ t('specialists.empty') }}</p>
      @endforelse
      <p class="sp-empty" id="spEmpty" hidden>{{ t('specialists.empty') }}</p>
    </div>

    {{-- pagination --}}
    @if ($specialists->hasPages())
      <div class="sp-pagination">
        {{ $specialists->withQueryString()->links() }}
      </div>
    @endif
    </div>{{-- /.sp-main --}}
  </div>

</main>

</x-layout>
