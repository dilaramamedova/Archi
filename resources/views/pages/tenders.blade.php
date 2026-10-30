{{--
  Tender board (Figma «11 · TENDERLƏR» T1 list · T6 no results · M1 · M4).
  Rendered by TenderController@index only when TenderAccess is granted; everyone else
  gets pages/tenders-locked.

  The search row, the filter sidebar, the tabs and the sort all live in ONE GET form,
  so every control submits the whole state (a new search keeps the filters, applying
  the filters keeps the search). Below 980px the sidebar becomes a bottom sheet opened
  by the "Filtrlər" button (resources/js/pages/tenders.js).
  Styles: resources/css/pages/tenders.css
--}}
@php
  // Translate, falling back to a literal while a key is still missing from the
  // translations table (t() echoes the key back otherwise).
  $tOr = function (string $key, string $fallback, array $replace = []) {
      $v = t($key, $replace);
      return is_string($v) && $v !== $key ? $v : strtr($fallback, array_combine(
          array_map(fn ($k) => ':'.$k, array_keys($replace)),
          array_map('strval', array_values($replace)),
      ));
  };

  $baku = fn ($date) => $date?->copy()->timezone('Asia/Baku');

  $statusOptions = [
      'all' => $tOr('tenders.filters.status_all', 'Bütün tenderlər'),
      'open' => $tOr('tenders.filters.status_open', 'Aktiv tenderlər'),
      'closed' => $tOr('tenders.filters.status_closed', 'Bağlanmış tenderlər'),
  ];
  $sortOptions = [
      'newest' => $tOr('tenders.sort.newest', 'Ən yeni'),
      'deadline' => $tOr('tenders.sort.deadline', 'Son tarix yaxın'),
      'budget' => $tOr('tenders.sort.budget', 'Büdcə (yüksək)'),
  ];

  // Tabs are links: they keep every other parameter but drop the page number.
  $keep = request()->except(['tab', 'page']);
  $tabUrls = [
      'all' => route('tenders.index', $keep),
      'saved' => route('tenders.index', array_merge($keep, ['tab' => 'saved'])),
  ];

  $isSavedTab = $filters['tab'] === 'saved';
  $hasCriteria = $filters['q'] !== '' || $activeFilterCount > 0;
  $resetUrl = route('tenders.index', $isSavedTab ? ['tab' => 'saved'] : []);
  $endsAt = $baku($access->endsAt);
@endphp
<x-layout page="tenders" :title="$tOr('tenders.meta.title', 'Tenderlər — ARCHİ')">

<main class="wrap td-page" data-saved-label="{{ $tOr('tenders.card.saved', 'Yadda saxlanılıb') }}"
      data-save-label="{{ $tOr('tenders.card.save', 'Yadda saxla') }}">
  <div class="inner td-stack">

    <x-ui.breadcrumbs class="td-crumbs" :items="[
        ['label' => t('common.home'), 'href' => route('home')],
        ['label' => $tOr('tenders.crumb', 'Tenderlər')],
    ]" />

    {{-- paid package banner --}}
    <div class="td-banner td-banner-ok">
      <span class="td-banner-check">@include('pages.partials.tender-icon', ['name' => 'check', 'stroke' => '2.4'])</span>
      <div>
        <p class="t1">{{ $tOr('tenders.package.active', 'Ödənişli paket aktivdir') }}</p>
        <p class="t2">
          @if ($endsAt)
            {{ $tOr('tenders.package.active_until', 'Bütün tenderlərə :date tarixinədək çıxışınız var.', ['date' => $endsAt->format('d.m.Y')]) }}
          @else
            {{ $tOr('tenders.package.active_sub', 'Bütün tenderlərə çıxışınız var.') }}
          @endif
        </p>
      </div>
    </div>

    <div class="td-heading">
      <h1>{{ $tOr('tenders.heading.title', 'Tenderlər') }}</h1>
      <p>{{ $tOr('tenders.heading.subtitle', 'Layihənizə uyğun tenderi tapın və sifarişçi ilə əlaqə saxlayın.') }}</p>
    </div>

    <form class="td-form" id="tdForm" method="get" action="{{ route('tenders.index') }}" role="search">
      @if ($isSavedTab)
        <input type="hidden" name="tab" value="saved">
      @endif

      {{-- search row --}}
      <div class="td-search">
        <label class="td-search-field">
          @include('pages.partials.tender-icon', ['name' => 'search'])
          <span class="sr-only">{{ $tOr('tenders.search.label', 'Tender axtarışı') }}</span>
          <input type="search" name="q" value="{{ $filters['q'] }}" maxlength="120"
                 placeholder="{{ $tOr('tenders.search.placeholder', 'Tender və ya şirkət axtarın') }}">
        </label>
        <button type="submit" class="td-btn td-btn-primary td-search-btn">{{ $tOr('tenders.search.submit', 'Axtar') }}</button>
      </div>

      <div class="td-body">

        {{-- filters: sidebar ≥981px, bottom sheet below --}}
        <aside class="td-filters" id="tdFilters" data-open="false" aria-labelledby="tdFiltersTitle">
          <div class="td-filters-head">
            <h2 id="tdFiltersTitle">{{ $tOr('tenders.filters.title', 'Filtrlər') }}</h2>
            <button type="button" class="td-filters-close" id="tdFiltersClose" aria-label="{{ t('common.close') }}">
              @include('pages.partials.tender-icon', ['name' => 'x'])
            </button>
          </div>

          <div class="td-filters-scroll">
            @if ($categories->isNotEmpty())
              <fieldset class="td-fgroup">
                <legend>{{ $tOr('tenders.filters.category', 'Kateqoriya') }}</legend>
                @foreach ($categories as $category)
                  <label class="td-check">
                    <input type="checkbox" name="category[]" value="{{ $category->slug }}"
                           @checked(in_array($category->slug, $filters['categories'], true))>
                    <span class="box" aria-hidden="true">@include('pages.partials.tender-icon', ['name' => 'check', 'stroke' => '2.6'])</span>
                    <span class="lbl">{{ $category->name }}</span>
                    <span class="n">{{ $category->tenders_count }}</span>
                  </label>
                @endforeach
              </fieldset>
              <hr class="td-fdiv">
            @endif

            <div class="td-fgroup">
              <label class="td-flabel" for="tdCity">{{ $tOr('tenders.filters.city', 'Şəhər') }}</label>
              <div class="td-select">
                <select id="tdCity" name="city">
                  <option value="">{{ $tOr('tenders.filters.city_all', 'Bütün şəhərlər') }}</option>
                  @foreach ($cities as $city)
                    <option value="{{ $city }}" @selected($filters['city'] === $city)>{{ $city }}</option>
                  @endforeach
                </select>
                @include('pages.partials.tender-icon', ['name' => 'chevron-down'])
              </div>
            </div>

            <hr class="td-fdiv">

            <div class="td-fgroup">
              <label class="td-flabel" for="tdStatus">{{ $tOr('tenders.filters.status', 'Status') }}</label>
              <div class="td-select">
                <select id="tdStatus" name="status">
                  @foreach ($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                  @endforeach
                </select>
                @include('pages.partials.tender-icon', ['name' => 'chevron-down'])
              </div>
            </div>
          </div>

          <div class="td-filters-foot">
            <button type="submit" class="td-btn td-btn-primary td-apply">{{ $tOr('tenders.filters.apply', 'Filtrləri tətbiq et') }}</button>
            <a class="td-reset" href="{{ $resetUrl }}">{{ $tOr('tenders.filters.reset', 'Sıfırla') }}</a>
          </div>
        </aside>
        <div class="td-scrim" id="tdScrim" hidden></div>

        <div class="td-main">
          {{-- toolbar: tabs + sort (desktop) · filter button + sort, tabs below (phone) --}}
          <div class="td-toolbar">
            <button type="button" class="td-fbtn" id="tdFilterBtn" aria-expanded="false" aria-controls="tdFilters">
              @include('pages.partials.tender-icon', ['name' => 'sliders'])
              <span>{{ $tOr('tenders.filters.title', 'Filtrlər') }}</span>
              @if ($activeFilterCount > 0)
                <span class="n">{{ $activeFilterCount }}</span>
              @endif
            </button>

            <nav class="td-tabs" aria-label="{{ $tOr('tenders.tabs.label', 'Tender siyahısı') }}">
              <a href="{{ $tabUrls['all'] }}" @if (! $isSavedTab) aria-current="page" @endif>{{ $tOr('tenders.tabs.all', 'Bütün tenderlər') }}</a>
              <a href="{{ $tabUrls['saved'] }}" @if ($isSavedTab) aria-current="page" @endif>{{ $tOr('tenders.tabs.saved', 'Yadda saxlanılanlar') }}</a>
            </nav>

            <div class="td-select td-sort">
              <label class="sr-only" for="tdSort">{{ $tOr('tenders.sort.label', 'Sıralama') }}</label>
              <select id="tdSort" name="sort" data-autosubmit>
                @foreach ($sortOptions as $value => $label)
                  <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                @endforeach
              </select>
              @include('pages.partials.tender-icon', ['name' => 'chevron-down'])
            </div>
          </div>

          @if ($tenders->isEmpty())
            <div class="td-empty">
              <span class="td-empty-ic">@include('pages.partials.tender-icon', ['name' => $isSavedTab && ! $hasCriteria ? 'bookmark' : 'search', 'stroke' => '1.6'])</span>
              @if ($isSavedTab && ! $hasCriteria)
                <h2>{{ $tOr('tenders.empty.saved_title', 'Hələ yadda saxladığınız tender yoxdur') }}</h2>
                <p>{{ $tOr('tenders.empty.saved_text', 'Tender kartındakı əlfəcin işarəsinə basın — tender bu siyahıya əlavə olunacaq.') }}</p>
                <a class="td-btn td-btn-outline" href="{{ $tabUrls['all'] }}">{{ $tOr('tenders.empty.saved_cta', 'Bütün tenderlərə bax') }}</a>
              @else
                <h2>{{ $tOr('tenders.empty.title', 'Uyğun tender tapılmadı') }}</h2>
                <p>{{ $tOr('tenders.empty.text', 'Axtarışınıza və seçdiyiniz filtrlərə uyğun tender yoxdur. Filtrləri sıfırlayın və ya axtarış sözünü dəyişin.') }}</p>
                <a class="td-btn td-btn-outline" href="{{ $resetUrl }}">@include('pages.partials.tender-icon', ['name' => 'reset']){{ $tOr('tenders.empty.reset', 'Filtrləri sıfırla') }}</a>
              @endif
            </div>
          @else
            <div class="td-list">
              @foreach ($tenders as $tender)
                @php
                  $open = $tender->isOpen();
                  $saved = in_array($tender->id, $savedIds, true);
                  $deadline = $baku($tender->deadline_at);
                  $url = route('tenders.show', $tender->slug);
                @endphp
                <article class="td-card" data-closed="{{ $open ? 'false' : 'true' }}">
                  <div class="td-card-top">
                    @if ($tender->organizer_logo_url)
                      <img class="td-logo" src="{{ $tender->organizer_logo_url }}" alt="{{ $tender->organizer_name }}" loading="lazy">
                    @else
                      <span class="td-logo" aria-hidden="true">{{ $tender->initials() }}</span>
                    @endif

                    <div class="td-card-head">
                      <p class="org">{{ $tender->organizer_name }}</p>
                      @if ($tender->category)
                        <p class="cat">{{ $tender->category->name }}</p>
                      @endif
                    </div>

                    <h2 class="td-card-title"><a href="{{ $url }}">{{ $tender->title }}</a></h2>

                    @if (filled($tender->summary))
                      <p class="td-card-sum">{{ $tender->summary }}</p>
                    @endif

                    <div class="td-card-side">
                      <span class="td-chip" data-tone="{{ $open ? 'open' : 'closed' }}">{{ $open ? $tOr('tenders.status.open', 'Aktiv') : $tOr('tenders.status.closed', 'Bağlanıb') }}</span>
                      <button type="button" class="td-bm" data-save-url="{{ route('tenders.save', $tender->slug) }}"
                              aria-pressed="{{ $saved ? 'true' : 'false' }}"
                              aria-label="{{ $saved ? $tOr('tenders.card.saved', 'Yadda saxlanılıb') : $tOr('tenders.card.save', 'Yadda saxla') }}"
                              title="{{ $saved ? $tOr('tenders.card.saved', 'Yadda saxlanılıb') : $tOr('tenders.card.save', 'Yadda saxla') }}">
                        @include('pages.partials.tender-icon', ['name' => 'bookmark'])
                      </button>
                    </div>
                  </div>

                  <div class="td-card-meta">
                    <div class="td-meta">
                      @include('pages.partials.tender-icon', ['name' => 'pin'])
                      <div><p class="v">{{ $tender->city ?: '—' }}</p><p class="k">{{ $tOr('tenders.card.city', 'Şəhər') }}</p></div>
                    </div>
                    <div class="td-meta">
                      @include('pages.partials.tender-icon', ['name' => 'wallet'])
                      <div><p class="v">{{ $tOr('tenders.card.budget_value', 'Büdcə: :value', ['value' => $tender->budgetLabel() ?? $tOr('tenders.budget.on_request', 'Sorğu əsasında')]) }}</p><p class="k">{{ $tOr('tenders.card.budget', 'Büdcə') }}</p></div>
                    </div>
                    <div class="td-meta">
                      @include('pages.partials.tender-icon', ['name' => 'calendar'])
                      <div><p class="v">{{ $tOr('tenders.card.deadline_value', 'Son tarix: :value', ['value' => $deadline ? $deadline->format('d.m.Y') : $tOr('tenders.deadline.none', 'Müddətsiz')]) }}</p><p class="k">{{ $tOr('tenders.card.deadline', 'Son müraciət tarixi') }}</p></div>
                    </div>
                    <a class="td-more" href="{{ $url }}" aria-label="{{ $tOr('tenders.card.more', 'Ətraflı bax') }}: {{ $tender->title }}">
                      <span>{{ $tOr('tenders.card.more', 'Ətraflı bax') }}</span>
                      @include('pages.partials.tender-icon', ['name' => 'arrow-right'])
                    </a>
                  </div>
                </article>
              @endforeach
            </div>

            @if ($tenders->hasPages())
              <div class="td-pagination">
                {{ $tenders->links('pages.partials.tender-pagination') }}
              </div>
            @endif
          @endif
        </div>{{-- /.td-main --}}
      </div>{{-- /.td-body --}}
    </form>

    <p class="sr-only" id="tdLive" aria-live="polite"></p>
  </div>
</main>

</x-layout>
